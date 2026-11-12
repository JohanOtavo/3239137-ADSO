Este proyecto es la solución del plan de mejoramiento del aprendiz A07, ficha 3239137, dominio film-gear.

# film-gear · Camera and drone rental for production

A rental house needs to know three things: what it owns, who took it and when it comes back. That is all this
system does.

> The sentence above is kept in Spanish because the improvement plan requires the README to start with it
> verbatim. Everything else in this repository is written in English, except text a user reads.

## What it covers

| Concept | What it is here |
|---|---|
| **Equipment** | A catalogue model, identified by its model code. Units are counted, not identified |
| **Unit** | A physical copy. There is a total and there is an available count |
| **Category** | Five fixed and seeded: cameras, lenses, drones, audio, stabilisers |
| **Producer** | The party that rents. Does not sign in: a record, not a user |
| **Advisor** | Whoever works the counter and records the operations |
| **Rental** | A settled fact: who asked, who recorded it and when |

## The rules that define it

- A **4 day** term from the moment of the rental.
- At most **2 pieces of equipment** out per producer.
- Anyone holding something **overdue** gets nothing else until it comes back.
- Available units stay **between 0 and the total**, always.
- A recorded rental is **never edited and never deleted**.
- Equipment removed from the catalogue **stays in the history**.

## The six folders

| Folder | What goes there |
|---|---|
| [`docs/`](docs/) | The specification: constitution, spec, plan, architecture, data model, API contract, tasks and the ADRs |
| [`api/`](api/) | Backend in PHP with Laravel |
| [`app/`](app/) | Frontend in React |
| [`page/`](page/) | Public static site presenting the product |
| [`infra/`](infra/) | Containers and database, with one command |
| [`tool/`](tool/) | Demo data seeder, through the API, and the language guard |

## Where to start

Read [`docs/constitution.md`](docs/constitution.md) first: those are the principles the rest of the project
cannot break, each with the way it is checked. Then [`docs/spec.md`](docs/spec.md), which says what the system
has to do.

## State

Under construction. Progress lives in this folder's commit history, one commit per finished piece.

The specification documents, and which ones exist:

| Document | State |
|---|---|
| `docs/constitution.md` | Written |
| `docs/spec.md` | Written |
| `docs/data-model.md` | Written |
| `docs/api-contract.md` | Written |
| `docs/plan.md` | Written |
| `docs/architecture.md` | Written |
| `docs/tasks.md` | Written |
| `docs/adr/` (four) | Written |

Nothing in this folder declares finished what is not: that is article XII, and it is why this table exists.

## Language

Code, comments, file names, routes and this documentation are English. Error messages, screen labels and the
five seeded category names are Spanish, because a user reads them. `tool/check-language.mjs` fails the build
when that line is crossed.

## Plan document

The improvement plan record, in its coded version with no personal data:
[`Plan-Mejoramiento-A07.pdf`](Plan-Mejoramiento-A07.pdf).
