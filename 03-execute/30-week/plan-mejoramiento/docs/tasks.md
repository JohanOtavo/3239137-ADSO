# Tasks - film-gear

A task is finished when its **evidence** exists and can be executed (article XII). Starting state for all of
them: **pending**.

This document is what the completeness of the project is measured against. It promises what will be delivered,
not what would be nice to deliver.

| # | Task | Depends on | Evidence |
|---|---|---|---|
| T-01 | Laravel skeleton and `GET /health` | - | `GET /api/v1/health` answers `{"status":"ok"}`; the container starts with one command |
| T-02 | Initial migration: six tables, FKs, unique indexes, the three `CHECK` constraints and the five categories | T-01 | The queries in `data-model.md` section 10 return what is documented; no column carries a `DEFAULT` |
| T-03 | Domain: `ModelCode`, `Quantity`, `DocumentNumber`, `DateRange` | T-01 | Unit tests: model code of 4 to 20 characters, quantity above zero, end on or after start |
| T-04 | Domain: `Equipment` (`withdraw`, `restock`, total change, soft delete) | T-03 | Tests: never below zero, never above the total, lowering the total below what is out fails |
| T-05 | Domain: `Rental` (`addItem`, `returnItem`, computed state, four days, at most two lines) | T-04 | Tests with a fake `Clock`: `open`, `overdue`, `closed` |
| T-06 | Domain: `Producer` and `User` (name normalisation, valid role) | T-03 | `"Ana "` becomes `ana`; the `admin` role is rejected through the API |
| T-07 | Ports and **authentication** use cases (`LoginService`, `CreateAdvisorService`) | T-06 | US-01 and US-02 pass against doubles |
| T-08 | Ports and **equipment** use cases (create, search, edit, soft delete, photo) | T-04 | US-03 and US-04 pass against doubles |
| T-09 | **Rent** use case (`RentEquipmentService`) | T-05, T-08 | US-06 in full: the two item limit, overdue items, no units |
| T-10 | **Return** and **overdue** use cases | T-09 | US-07 and US-09; returning twice gives `ITEM_ALREADY_RETURNED` |
| T-11 | **Most-requested report** use case and its read port | T-09 | US-10; the old name survives a rename ([ADR-004](adr/adr-004-aggregated-frozen-report.md)) |
| T-12 | Persistence adapter: equipment, producer and user repositories | T-02, T-08 | Integration tests against a real PostgreSQL |
| T-13 | Persistence adapter: rental repository with conditional write | T-12, T-09 | Two rentals of the last unit: one 201, one 409 (NFR-2) |
| T-14 | Security adapter: password hashing and JWT | T-07 | The password never appears in logs or responses (NFR-3) |
| T-15 | Photo adapter (disk) | T-08 | A 2 MB PNG is accepted; 3 MB gives 422; no photo leaves `photo_key` null |
| T-16 | HTTP adapter: routes from [`api-contract.md`](api-contract.md), single error body, role authorisation | T-10, T-11, T-14, T-15 | A contract test walking every route and the fourteen codes |
| T-17 | Composition root and boot (first admin from the environment) | T-16 | The system starts against an empty database and the admin can sign in |
| T-18 | Language guard: `tool/check-language.mjs` and its workflow | T-01 | The script fails when a Spanish identifier or comment is introduced |
| T-19 | Architecture tests (articles I, II and III) | T-17 | The test fails when a forbidden import is introduced |
| T-20 | `infra`: `docker compose up` starts an empty engine, the API and the frontend | T-17 | One command; the database has no tables until migrations run |
| T-21 | `tool`: seeder through the **API** (40+ equipment, 10 producers, 15 rentals, 3 overdue) | T-17 | After running it, `/rentals/overdue` returns 3 lines and the report has rows |
| T-22 | `app`: sign in, catalogue, producers, rent, return, overdue, report | T-16 | A manual end-to-end run |
| T-23 | `page`: public presentation site | - | Opens without the API; reads well on phone and desktop |

## Test files

The plan asks for **15 or more**. This list is what is promised:

| Folder | Files |
|---|---|
| `tests/Unit/Domain/` | `ModelCodeTest`, `QuantityTest`, `DocumentNumberTest`, `DateRangeTest`, `EquipmentTest`, `RentalTest`, `RentalItemTest`, `ProducerBorrowingLimitTest`, `UserTest` |
| `tests/Unit/Application/` | `LoginServiceTest`, `CreateAdvisorServiceTest`, `RentEquipmentServiceTest`, `ReturnRentalItemServiceTest`, `MostRequestedReportServiceTest`, `EquipmentCatalogServiceTest` |
| `tests/Unit/Adapters/` | `JwtTokenIssuerTest`, `MultipartBodyTest` |
| `tests/Architecture/` | `LayerDependenciesTest` |
| `tests/Integration/` | `EquipmentRepositoryTest`, `RentalRepositoryTest`, `ApiContractTest`, `ConcurrencyTest`, `CatalogSearchPerformanceTest` |

Twenty-three files were promised and twenty-nine exist. Four of the promised
names are not among them, and this is what replaced each one and why (T-30).

| Promised | Shipped as | Why |
|---|---|---|
| `EquipmentRepositoryTest` | `EquipmentCatalogTest` | It drives the catalogue over HTTP, repository included. A name saying "repository" would promise a unit test of one class and deliver an end-to-end one |
| `RentalRepositoryTest` | `RentalFlowTest` | The same: a rental from the producer to the report, not one adapter in isolation |
| `ApiContractTest` | `ErrorContractTest` | It walks the fourteen error codes, which is the part of the contract a client cannot discover by reading a happy path |
| `ConcurrencyTest` | `ConcurrentWithdrawalTest` | There is one race in this system and the name says which |
| `CatalogSearchPerformanceTest` | `tool/load-search.mjs` | NFR-1 is measured from outside the server over 5,000 pieces of equipment. Inside PHPUnit it would measure a query, not what a browser waits for |

Three more exist that were never promised: `AuthFlowTest`, `PasswordNeverLeaksTest`
and `SetEquipmentPhotoServiceTest`, plus four adapter tests. They came from
gaps found while running the finished system.

Five of the integration files need PostgreSQL; the rest run against nothing.

## Definition of done

A task becomes **done** only when its evidence has been executed and has passed. Not when the code exists, not
when it "should work", and not when the test is written but has never been run. That is article XII, and it is
what the reference project broke by declaring twenty-three tasks finished with eleven of them lacking executable
evidence.

The real state of each task lives in the commit history and in the output of `composer test`, not in a table in
this document that somebody has to keep up to date by hand.

## What is left, and what each piece has to show

The twenty-three tasks above were written before anything was built. These are
what running the finished system turned up: three defects, five promises whose
evidence does not exist yet, and one promise that cannot be kept as written.

They are ordered by what costs the most if it is left undone.

| # | Task | Why it matters | Evidence it has to leave |
|---|---|---|---|
| T-24 | Close `/reports/most-requested` to the advisor | AC-10.5 and the contract both say `admin`; the route says `admin,advisor` and the menu offers it. It is the one authorisation hole in the system | An advisor's token answers 403, asserted by a test, and the entry leaves the advisor's menu |
| T-25 | Make the contract match the API | The document says `rows` where the API sends `items`, `days_late` where it sends `days_overdue`, and promises a `from`/`to` echo that is not there | The three names agree, and a test reads the documented shape |
| T-26 | Cover the fourteen error codes | T-16 promises a contract test over every route and every code. Eight are asserted; `USERNAME_TAKEN`, `DOCUMENT_TAKEN`, `EQUIPMENT_HAS_OPEN_RENTALS`, `PRODUCER_HAS_OVERDUE`, `INVALID_DATE_RANGE` and `VALIDATION_ERROR` are not | One test per missing code, each asserting the status and the code |
| T-27 | Test the photo use case | T-15 promises a 2 MB PNG accepted, 3 MB refused and `photo_key` null without one. Nothing tests any of it | Three assertions against the use case, with the type decided by content and not by extension |
| T-28 | Prove NFR-3 instead of asserting it | "The password never travels and never appears in a log" is the kind of claim that is true until somebody adds a `Log::info($request->all())` | A test that signs in and fails if the password appears in the response or in the log channel |
| T-29 | Write `RentalItemTest` | It is on the promised file list and does not exist. The line is where the freezing and the single return live | The line freezes its three values and refuses a second return |
| T-30 | Reconcile the integration file names | The list promises `ApiContractTest`, `ConcurrencyTest`, `EquipmentRepositoryTest`, `RentalRepositoryTest` and `CatalogSearchPerformanceTest`. What exists covers all of it under four other names, and the performance one lives in `tool` | Either the files carry the promised names or this document says what replaced them and why |
| T-31 | Decide what to do about the three overdue lines | T-21 promises them and the seeder cannot produce them: the due date is computed from the rental and the server owns the clock. Leaving it unexplained reads as a task quietly dropped | Either a documented way to run the stack with its clock moved, or this document's evidence for T-21 corrected to what the API can actually reach |
| T-32 | Walk the seven screens signed in, as both roles | The one piece of T-22 that no script should claim. The operator has the credentials; nobody else does | A note of what was walked, with both roles, and anything that did not behave |


T-24 to T-30 are done and their evidence runs in CI. T-31 was answered along
the way: the contract test ages a line through the database, which is the row
the API would have written days ago, so an overdue line is reachable in a test
without waiting four days.

What is left is T-32, the walk through the seven screens signed in as both
roles, which belongs to whoever holds the credentials.
