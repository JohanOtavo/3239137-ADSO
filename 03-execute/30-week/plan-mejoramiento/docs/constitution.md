# Constitution - film-gear

The project's non-negotiable principles. These are not style or preference: every article carries **how it is
checked**, because a principle nobody can verify is an intention. A change that breaks an article is rejected
without discussing the rest.

---

## Article I - The domain depends on nothing

`api/app/Domain/` imports no other module of the project and no third-party library: only PHP and its standard
library (`final readonly class`, `enum`, `DateTimeImmutable`). No Eloquent, no facades, no `Illuminate\*`.

**Why:** it is the only thing that lets the invariants be tested without a database, without a server and without
a container, and the ORM be replaced without touching rules.
**Check:** `api/tests/Architecture/LayerDependenciesTest.php` reads the `use` statements of every domain file and
fails when a forbidden one appears.

## Article II - The core declares its ports; the outside world implements them

The application layer knows only the domain. Everything it needs from outside - repositories, clock, password
hashing, photo storage, token issuing - it expresses as a **port it declares itself**. No framework or ORM type
appears in a port signature.

**Check:** the same architecture test stops `app/Application/` from importing `app/Adapters/`. Application tests
run against doubles and never reference an adapter.

## Article III - A single composition root

One file, `api/app/Providers/PortBindingsProvider.php`, is where a port meets its adapter. Infrastructure is not
built anywhere else.
**Check:** the architecture test looks for `new <Adapter>(` outside `app/Providers/` and fails when it finds one.

## Article IV - SOLID is not decoration

| Principle | Concrete application | Check |
|---|---|---|
| **SRP** | One application service = one use case. `RentEquipmentService` rents and does nothing else | A service with two reasons to change gets split |
| **OCP** | Swapping photo storage (disk to S3) touches neither domain nor application | The new adapter only adds one line to the composition root |
| **LSP** | Test doubles stand in for the real adapter without changing expectations | The same test passes against the double and against the real one |
| **ISP** | Small ports: `Clock`, `PasswordHasher`, `TokenIssuer`, `PhotoStorage` | No implementer leaves methods unused |
| **DIP** | The port is declared by whoever consumes it | Ports live in the application package |

## Article V - Migrations own the schema

`infra/` starts an **empty** engine. All DDL - tables, foreign keys, indexes, the three `CHECK` constraints and the
seed - lives in the application's migrations. See [ADR-001](adr/adr-001-schema-ownership.md).
**Check:** `grep -ri "create table" infra/` returns nothing.

## Article VI - Invariants the database can enforce, the database enforces

A rule that lives only in code protects the application, not the data. Every invariant expressible in the engine
(`CHECK`, `UNIQUE`, FK) is declared there. Anything not applied is marked **pending** in
[`data-model.md`](data-model.md); it is not promised.
**Check:** the queries in `data-model.md` section 10 match what the document declares.

## Article VII - Derived values are computed, not stored

A rental's state (`open`, `overdue`, `closed`) and the days late are computed. There is no column for them.
**Check:** `data-model.md` lists no `status` and no `days_late` column.

## Article VIII - Frozen values do not follow the catalogue

A rental line stores the name, model code and category of the moment the rental happened. No report reads them
from the live catalogue.
**Check:** a test renames a piece of equipment after renting it and asserts the report still shows the old name
([ADR-004](adr/adr-004-aggregated-frozen-report.md)).

## Article IX - No secret enters the repository or a log

Not passwords, not precomputed hashes, not the token key. Every secret comes from the environment. The real
`.env` is in `.gitignore`; only `.env.example` is versioned.
**Check:** a pattern `grep` in CI; no HTTP response ever contains `password` or `password_hash`.

## Article X - The engine wins

When the data document and the database disagree, the document is broken and the document gets fixed.

## Article XI - Language

**Code is written in English. Only text a user reads is written in Spanish.**

| What | Language |
|---|---|
| Classes, methods, variables, constants | English |
| File and folder names | English |
| Tables and columns | English |
| Routes, both API and frontend | English |
| Test names | English |
| Comments and docblocks | **English** |
| Error messages and screen labels | Spanish |
| This documentation | English |

**Why:** a project with identifiers in two languages forces a mental translation on every read, and comments are
code: they live beside what they explain and are read with it.
**Check:** `tool/check-language.mjs` walks the code files, counts identifiers and comments against a list of
Spanish terms and exits non-zero when it finds any. It runs by hand with `node tool/check-language.mjs` and on
every push from `.github/workflows/language-guard.yml`, which lives at the **repository root** - GitHub Actions
does not read workflows in subfolders - and is scoped to this folder with `paths`.

## Article XII - Nothing is assumed done without evidence

A task in [`tasks.md`](tasks.md) is finished when its **evidence** exists and can be executed, not when it is
"already written". A document that declares something done without executable evidence is breaking this article.

---

## The architecture decisions

Four of them, and this document cites each by file name:

| ADR | Decision |
|---|---|
| [ADR-001](adr/adr-001-schema-ownership.md) | Migrations own the schema; `infra` starts an empty engine |
| [ADR-002](adr/adr-002-optimistic-concurrency.md) | Optimistic concurrency over the available units |
| [ADR-003](adr/adr-003-soft-delete.md) | Soft delete for the catalogue, no physical removal |
| [ADR-004](adr/adr-004-aggregated-frozen-report.md) | Aggregated report over frozen values |

The plan asks for at least three. There are four because four decisions had to be made.
