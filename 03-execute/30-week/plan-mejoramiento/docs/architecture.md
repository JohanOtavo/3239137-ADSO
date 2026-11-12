# Architecture - film-gear

The shape of the system. The decisions are in [`plan.md`](plan.md); the schema, in
[`data-model.md`](data-model.md).

## 1. The layers and the direction of the dependencies

```
        HTTP / console
              │
    ┌─────────▼──────────┐
    │     Adapters       │  Laravel, PostgreSQL, disk, JWT
    └─────────┬──────────┘
              │ implements
    ┌─────────▼──────────┐
    │    Application     │  Use cases plus the ports they declare
    └─────────┬──────────┘
              │ uses
    ┌─────────▼──────────┐
    │      Domain        │  Entities, value objects, rules
    └────────────────────┘      imports nothing
```

The arrows point inward and **never** the other way.

| Folder | What lives there | What it may import |
|---|---|---|
| `api/app/Domain/` | `Equipment`, `Rental`, `RentalItem`, `Producer`, `User`, value objects | PHP only (article I) |
| `api/app/Application/` | One use case per file, and the ports | The domain only (article II) |
| `api/app/Adapters/` | Persistence, security, photos, HTTP, console | Whatever it needs |
| `api/app/Providers/` | `PortBindingsProvider`: the single composition root | Everything (article III) |

`api/tests/Architecture/LayerDependenciesTest.php` reads the `use` statements of every file and fails when
somebody flips an arrow. It is the contract written as a test, because PHP has no equivalent of `import-linter`.

## 2. The ports

The application declares what it needs from outside and does not know who satisfies it:

| Port | For what | Adapter |
|---|---|---|
| `Clock` | Knowing the time | System clock, in UTC |
| `IdGenerator` | UUID v4 | `random_bytes` |
| `PasswordHasher` | Hashing and verifying | Native Argon2id |
| `TokenIssuer` | Issuing and verifying the token | JWT HS256 |
| `PhotoStorage` | Storing and reading the binary | Local disk |
| `TransactionManager` | Grouping writes into one operation | PostgreSQL transaction |
| `EquipmentRepository` | The catalogue | Query builder |
| `ProducerRepository` | Producers and their counts | Query builder |
| `RentalRepository` | Rentals and their lines | Query builder |
| `UserRepository` | Users | Query builder |
| `CategoryRepository` | The five categories | Query builder |
| `MostRequestedQuery` | The report, in one go | One aggregated query |

Swapping disk photo storage for S3 means writing a new adapter and changing one line in
`PortBindingsProvider`. Neither the domain nor the use cases notice: that is the OCP of article IV.

## 3. Walking through one operation: renting

`POST /rentals` with one producer and two pieces of equipment.

```
Controller            validates the JSON shape and reads the user from the token
   │
RentEquipmentService  inside a transaction:
   │                    1. does the producer exist?                   → 404
   │                    2. how many items does it already hold?       → 422 over 2
   │                    3. does it hold anything overdue?             → 409
   │                    4. for each piece of equipment:
   │                         read it                                  → 404 if not active
   │                         Equipment::withdraw() decides if a unit is free → 409 if not
   │                         conditional write to the database        → 409 if somebody won first
   │                         Rental::addItem() freezes name, code and category
   │                    5. stores the rental with its lines
   │
Presenter             computes the state with the clock and builds the response
```

If any step fails, the transaction leaves nothing written. That is what AC-06.3 asks for: with no units,
**nothing is recorded**.

Step 4 is the only point of contention in the system. Whether a unit is free is decided by the domain; who wins
when two advisors ask for the last one is decided by the database, with an `UPDATE` carrying
`WHERE available_units = <what was read>`. Zero rows affected means somebody got there first, and the server
answers 409 ([ADR-002](adr/adr-002-optimistic-concurrency.md)).

## 4. The six folders and their job

| Folder | Job | Checked by |
|---|---|---|
| `docs` | The specification | Whether reading it is enough to build |
| `api` | Business rules and HTTP contract | Domain, application, contract, concurrency and load tests |
| `app` | Operating screens | An end-to-end run: sign in, rent, return, report |
| `page` | Public presentation, no API calls | Reading well on phone and desktop |
| `infra` | `docker compose up` and an empty database | Starting with one command |
| `tool` | Demo data and the language guard | Having real data at the defence |

## 5. The five ideas holding the design together

1. **A rental is an immutable fact**; only `returned_at` on a line ever changes.
2. **Units are taken and returned inside the same operation** that creates or closes the line.
3. **State is computed**: `open`, `overdue` and `closed` are not in the database.
4. **Frozen values do not follow the catalogue**: November's report does not change when equipment is renamed in December.
5. **The database is the last line of defence**: if the units `CHECK` fires, something wrote outside the system.
