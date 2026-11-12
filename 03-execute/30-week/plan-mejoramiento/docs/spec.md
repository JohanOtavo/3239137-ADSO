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
