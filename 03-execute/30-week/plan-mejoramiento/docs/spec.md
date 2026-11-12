# Specification - film-gear

**What is being built and why.** A system for an audiovisual rental house to keep its equipment catalogue, its
producers and its rentals, and to see which equipment is requested most.

Governed by [`constitution.md`](constitution.md). The data model lives only in [`data-model.md`](data-model.md)
and the HTTP contract only in [`api-contract.md`](api-contract.md).

---

## 1. Actors

| Actor | What they do | How they authenticate |
|---|---|---|
| **Administrator** (`admin`) | Maintains the equipment catalogue and the producers; creates advisors; sees every report | Username and password, then a token |
| **Advisor** (`advisor`) | Records rentals and returns; reads the catalogue, producers and overdue items | Username and password, then a token |
| **Producer** | The person who rents equipment. **Does not sign in**: a record, not a user | - |

The `admin` role is provisioned by the deployment from environment variables. **Nobody grants it at runtime.**

## 2. Scope

**In:** equipment catalogue with an optional photo, five fixed categories, producers, rentals with several lines,
return line by line, overdue equipment, most-requested report, sign-in with roles.

**Out, and not reopened:** rates, charges and deposits; advance reservations; renewals; email or SMS; bulk
import; cross-branch rentals; per-unit serial numbers; equipment condition tracking; insurance; and any equipment
attribute beyond those in section 3.

The system **handles no money**. A unit is counted, not identified: there is no serial-number inventory, and two
units of the same model are interchangeable.

## 3. User stories and acceptance criteria

### US-01 - Sign in
As a user I want to authenticate so I can work according to my role.
- **AC-01.1** Valid credentials return a token valid for 8 hours.
- **AC-01.2** Invalid credentials return 401 with a message that does **not** say whether the username or the
  password was wrong.
- **AC-01.3** Every endpoint except sign-in and `/health` requires a token. No token: 401. Insufficient role: 403.

### US-02 - Create advisors (admin)
- **AC-02.1** The admin creates a user with a name, a password and the `advisor` role.
- **AC-02.2** The username is stored lowercased and trimmed; it is unique. Duplicate: 409.
- **AC-02.3** Asking for the `admin` role through the API returns 422. The password is never returned and never
  logged.

### US-03 - Manage equipment (admin)
- **AC-03.1** Create equipment with a name, brand, model code, category and total units. Available units start
  equal to the total.
- **AC-03.2** The model code is 4 to 20 characters of uppercase letters, digits or hyphens, and is unique among
  active equipment. Duplicate: 409.
- **AC-03.3** Total units >= 1. Changing the total shifts the available units by the same difference and **never**
  leaves available below zero (422).
- **AC-03.4** Uploading a photo (JPEG/PNG, max 2 MB) stores an **opaque key**; with no photo the field is `null`,
  never an empty string.
- **AC-03.5** Deleting equipment is a **soft delete**: it leaves the catalogue but stays in past rentals.
  Equipment with open rentals cannot be deleted (409).

### US-04 - Search equipment
- **AC-04.1** Partial-text search over name or brand, filter by category, active equipment only, ordered by name,
  paginated (`page`, `size`, max 50).
- **AC-04.2** The response includes the total number of results.
- **AC-04.3** Reading equipment by an unknown or soft-deleted id: 404.

### US-05 - Manage producers
- **AC-05.1** The admin and the advisor create producers with a name and a document number. The document number
  is unique: duplicate 409.
- **AC-05.2** Producers are listed and searched by name or document number, paginated.
- **AC-05.3** Producers are never deleted.

### US-06 - Record a rental
- **AC-06.1** A rental belongs to **one** producer, is recorded by the authenticated user and carries 1 or 2 lines.
- **AC-06.2** The same equipment cannot appear twice in one rental (422).
- **AC-06.3** Each line takes **1** available unit in the same operation. With no units: 409 and **nothing is
  recorded**.
- **AC-06.4** A producer cannot hold more than **2 pieces of equipment** out at once (422, with the current count).
- **AC-06.5** A producer with any **overdue** equipment cannot rent again (409).
- **AC-06.6** The due date is `rented_at + 4 days`. The line freezes the name, model code and category name **at
  that instant**.
- **AC-06.7** A recorded rental is never edited and never deleted.

### US-07 - Record a return
- **AC-07.1** One **line** is returned at a time. It sets `returned_at` and gives one unit back.
- **AC-07.2** Returning an already-returned line: 409. It **never** adds the unit twice.
- **AC-07.3** Available units never exceed the total (the database guarantees it).
- **AC-07.4** Once every line is returned, the rental reads as **closed**.

### US-08 - Read a rental and list rentals
- **AC-08.1** Reading a rental returns its lines and its computed state (`open`, `overdue`, `closed`).
- **AC-08.2** List by date range, state and producer, ordered by date descending, paginated.
- **AC-08.3** The state is **computed**, not stored.

### US-09 - Overdue equipment
- **AC-09.1** Lists the unreturned lines whose `due_at` is in the past: equipment, producer, days late.
- **AC-09.2** Ordered by most days late first. `admin` and `advisor` only.

### US-10 - Most-requested report
- **AC-10.1** Given a date range, one row per **equipment and frozen category name**, with how many times it was
  rented, ordered descending.
- **AC-10.2** End before start: 422. A range with no rentals: an empty list, not an error.
- **AC-10.3** The report is **not** broken down by advisor or by producer.
- **AC-10.4** A report over a closed period **does not change** when the equipment or its category is renamed later.
- **AC-10.5** `admin` only.

## 4. Business rules

| # | Rule |
|---|---|
| BR-1 | Available units stay between 0 and the total, always. |
| BR-2 | Rental term: 4 days. At most 2 pieces of equipment out per producer. |
| BR-3 | A producer with overdue equipment rents nothing else until it comes back. |
| BR-4 | What a line freezes (name, model code, category) does not follow the catalogue. |
| BR-5 | Rentals and returns are never deleted. Equipment is only soft deleted. |
| BR-6 | Dates are UTC instants. Text a user reads is Spanish; code is English. |

## 5. Non-functional requirements

| # | Requirement | How it is measured |
|---|---|---|
| NFR-1 | Equipment search answers in under 300 ms with 5,000 pieces of equipment | Load test using the `tool` seeder |
| NFR-2 | Two advisors handing out the **last unit** at once: one wins, the other gets 409 | Concurrent test with two processes |
| NFR-3 | The password never travels or is stored in clear, and never appears in logs | Log and response review |
| NFR-4 | Every HTTP error uses the same body (see the contract) | Contract test |
| NFR-5 | The system starts with one command and the database starts empty | `infra` README |
