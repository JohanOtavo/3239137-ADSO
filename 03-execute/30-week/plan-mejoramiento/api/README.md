# api

film-gear backend: PHP 8.3 with Laravel 12 over PostgreSQL 16.

## Layout

The folders are the layers, and the architecture test enforces the direction of
the dependencies:

| Folder | What lives there | What it may import |
|---|---|---|
| `app/Domain` | Entities, value objects, business rules | Nothing but PHP (article I) |
| `app/Application` | One use case per file, and the ports they declare | Only the domain (article II) |
| `app/Adapters` | Persistence, security, photos, HTTP, console | Whatever it needs |
| `app/Providers` | `PortBindingsProvider`: the single composition root | Everything (article III) |

## Run it

```bash
cp .env.example .env     # fill in JWT_SECRET, ADMIN_USERNAME, ADMIN_PASSWORD
composer install
php artisan key:generate
php artisan migrate      # migrations own the schema, not infra
php artisan serve
```

With `infra` up, none of this is needed: Compose builds and runs this folder.

## Test it

```bash
composer test                              # domain, application, adapters, architecture
vendor/bin/phpunit --testsuite=integration # needs a live PostgreSQL
```

The integration suite rebuilds the schema before every test, so point `DB_*`
at a throwaway database and never at one holding data. It signs its own tokens
and provisions its own administrator, so no `JWT_SECRET` or `ADMIN_*` is read
from the environment for it.

| Suite | What it checks | Against what |
|---|---|---|
| `domain` | Invariants: model code, units in range, four day term, two item limit | Nothing: plain objects |
| `application` | Use cases: rent, return, report | Test doubles |
| `adapters` | Token issuing and multipart parsing | Plain objects |
| `architecture` | Articles I, II and III | The source files |
| `integration` | HTTP contract, derived state, frozen values, two session races | A real PostgreSQL |

## Language

Identifiers, comments, file names and routes are English. Error messages and
anything a user reads are Spanish. That is article XI, and `tool/check-language.mjs`
fails the build when it is broken.
