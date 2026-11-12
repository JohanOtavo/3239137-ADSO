# Technical plan - film-gear

How what [`spec.md`](spec.md) and [`constitution.md`](constitution.md) describe gets built. The schema is **not**
described here: it lives in [`data-model.md`](data-model.md). The shape of the system is in
[`architecture.md`](architecture.md).

## 1. Technical decisions

| # | Decision | Choice | Why |
|---|---|---|---|
| D-01 | Language and runtime | PHP 8.3 | Required by the plan: backend in PHP with Laravel |
| D-02 | HTTP framework | Laravel 12 | Contract in [`api-contract.md`](api-contract.md) |
| D-03 | Persistence | Laravel query builder plus migrations, over PostgreSQL 16 | Migrations own the schema ([ADR-001](adr/adr-001-schema-ownership.md)) |
| D-04 | Concurrency | Conditional write over `available_units` ([ADR-002](adr/adr-002-optimistic-concurrency.md)) | The last unit (NFR-2) |
| D-05 | Dates and clock | UTC instants (`timestamptz`); the clock is a port (`Clock`) | Test a due date without waiting four days |
| D-06 | Report | One aggregated query behind a read port ([ADR-004](adr/adr-004-aggregated-frozen-report.md)) | Stable and cheap |
| D-07 | Value objects | `ModelCode`, `Quantity`, `DocumentNumber`, `DateRange`: they live inside their owner's row, with no table | They have no identity |
| D-08 | Photos | Binary in external storage (local disk); the database stores only `photo_key` | Opaque key, never a path |
| D-09 | Passwords | Argon2id behind the `PasswordHasher` port; the domain never sees the plain password | Article IX |
| D-10 | Boot | The application creates the first admin from `ADMIN_USERNAME` / `ADMIN_PASSWORD` | The database cannot produce the hash |
| D-11 | Authentication | JWT HS256, 8 hours, key in `JWT_SECRET` | Simple and stateless |
| D-12 | Tests | PHPUnit, with doubles for the ports and a real database only in integration tests | Test pyramid |
| D-13 | Frontend | React with Vite, no component library | Seven screens of tables and forms do not justify a design system |
| D-14 | Language | `tool/check-language.mjs` run in CI | Article XI needs a check, not a promise |

**No Eloquent models.** One model per table would be a second domain running in parallel, with its own rules and
its own idea of what a piece of equipment is. The domain already exists in `api/app/Domain/`; repositories only
translate rows into those objects and back.

## 2. Environment configuration

| Variable | Required | Example |
|---|---|---|
| `DATABASE_URL` or `DB_*` | yes | `pgsql://filmgear@db:5432/filmgear` |
| `DB_SCHEMA` | yes | `filmgear` |
| `JWT_SECRET` | yes | long random string |
| `ADMIN_USERNAME` / `ADMIN_PASSWORD` | yes | set in `.env`, never in the repository |
| `PHOTOS_DIR` | no | `storage/app/photos` |
| `CORS_ORIGIN` | yes | `http://localhost:5173` |

A missing secret shows up at boot, not on the first request: startup fails when `JWT_SECRET` is absent.

## 3. Test strategy

| Level | What it checks | Against what |
|---|---|---|
| Domain | Invariants: two item limit, four day term, units in range | Nothing: plain objects |
| Application | Use cases: rent, return, report | Port doubles |
| Integration | Repositories and the report query | A real PostgreSQL |
| Contract | Single error body and status codes | The running API |
| Architecture | Articles I, II and III | The source files |
| Concurrency | Two processes after the last unit | A real PostgreSQL |
| Load | Search over 5,000 pieces of equipment under 300 ms | A real PostgreSQL |
| Language | Article XI | The source files |

The plan asks for **15 or more test files**. That count is kept in [`tasks.md`](tasks.md), not here.

## 4. Build order

Skeleton and health, then the initial migration with its seed, then the domain, then use cases against doubles,
then adapters, then the API, then contract, concurrency and load tests, then the frontend, then the public site.

The detail, with its evidence, is in [`tasks.md`](tasks.md).
