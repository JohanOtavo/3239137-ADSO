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

Twenty-three files. The five integration files need PostgreSQL; the rest run against nothing.

## Definition of done

A task becomes **done** only when its evidence has been executed and has passed. Not when the code exists, not
when it "should work", and not when the test is written but has never been run. That is article XII, and it is
what the reference project broke by declaring twenty-three tasks finished with eleven of them lacking executable
evidence.

The real state of each task lives in the commit history and in the output of `composer test`, not in a table in
this document that somebody has to keep up to date by hand.
