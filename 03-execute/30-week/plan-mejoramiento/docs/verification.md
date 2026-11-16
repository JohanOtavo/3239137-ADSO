# Verification - the run from an empty machine

What was executed, in order, and what came back. Article XII says a task is
finished when its evidence exists **and can be executed**, so this is the
record of executing it rather than a claim that it would work.

Run on 2026-11-15 against the compose stack, starting from a wiped volume.

## 1. The engine arrives empty (NFR-5, ADR-001)

```bash
docker compose down -v
docker compose up -d db
docker compose exec db psql -U filmgear -d filmgear -c "\dt filmgear.*"
```

```
Did not find any relation named "filmgear.*".
```

Counted across both schemas, including `public`:

```
select count(*) from information_schema.tables where table_schema in ('filmgear','public')
0
```

Zero tables. Not one `CREATE TABLE` reached the engine from `infra`, which is
the whole claim of article V: the migration owns the schema and the
infrastructure folder holds none of it.

## 2. The API fills it at boot (T-01, T-02, T-17)

```bash
docker compose up -d --build
docker compose logs api
```

```
Creating migration table ...................................... 25.29ms DONE
INFO  Running migrations.
Administrator admin created.
INFO  Server running on [http://0.0.0.0:8000].
```

Three things in one boot, in the order `docker/entrypoint.sh` declares: the
schema, the first administrator from the environment, and the server. The
administrator step is idempotent, so the second boot prints `already exists`
and changes no password.

```
GET /api/v1/health  ->  200  {"status":"ok"}
```

## 3. The schema is what `data-model.md` section 10 promises (T-02)

```
columns with a default: 0
tables in filmgear:     6
history rows in public: 1
```

No column carries a `DEFAULT`. That is deliberate and it is the rule the
previous delivery broke: a default is the database deciding a value the domain
is responsible for, and it hides a missing field instead of refusing it.

The history sits in `public` and the project schema holds exactly its six
tables. The name is written with its schema in `config/database.php` for a
reason: unqualified, it resolved through the search path and landed in a
different schema on the first boot than on the second, which made every
restart after the first die on `relation "category" already exists`.

The three `CHECK` constraints the engine enforces (article VI):

```
ck_equipment_available_range  CHECK (available_units >= 0 AND available_units <= total_units)
ck_equipment_total_positive   CHECK (total_units >= 1)
ck_user_role                  CHECK (role IN ('admin','advisor'))
```

The three partial indexes:

```
ux_equipment_model_code_active     unique model code among active equipment (AC-03.2)
ix_equipment_category_name_active  the catalogue's own access path
ix_rental_item_due_pending         the overdue list, over unreturned lines only
```

The first is why retiring equipment frees its model code: the index covers
only the rows where `deleted_at is null`.

## 4. The seeder fills it through the API (T-21)

```bash
API_URL=http://localhost:8000/api/v1 node seed.mjs
```

```
advisor:   asesor
equipment: 50
producers: 10
rentals:   15
overdue:   0
report:    30 rows
```

Every row went through the same validation a user's request passes; nothing
was written to the database directly.

The overdue count is zero and that is correct, not a gap: a line falls due
four days after the rental and the server owns that clock. The seeder reports
what it could not stage instead of writing a date the API would never produce.
The integration suite reaches that state honestly by ageing a line, which is
the row the API would have written days ago.

## 5. The roles hold on the running system (AC-01.3, AC-10.5)

Both tokens were taken from `POST /auth/login` and used against the live API:

| Request | admin | advisor |
|---|---|---|
| `GET /equipment` | 200 | 200 |
| `GET /rentals` | 200 | 200 |
| `GET /reports/most-requested` | 200 | **403** |
| `POST /users` | 201 | **403** |

The report is the one that was wrong until yesterday: the route was declared
twice, once in each group, and Laravel keeps the last registration, so the
advisor's copy was serving. Closing the first declaration changed nothing and
the contract test is what caught it.

## 6. The frontend is served (T-20, T-22)

```
GET http://localhost:5173/  ->  200
```

Seven screens behind nginx, talking to the API from the browser and not from
inside the compose network. The signed-in walk is the operator's own and is
the one piece of T-22 no script should claim.

## 7. The signed-in walk (T-32)

Walked by the operator on 2026-11-15 against this stack, signed in as both
`admin` and `asesor`. Reported: nothing misbehaved.

This section is a person's report and is marked as one. Everything above was
produced by a command whose output is quoted; this was not, and writing it as
though a tool had checked it would be the claim article XII exists to stop.

What a tool did check, separately, is the half a walk cannot show: the server
refuses what the menu hides. Section 5 has those four results. The advisor's
missing menu entries are a convenience; if the menu were the control, typing
the URL would be enough to get past it.

## 8. The search under 5,000 pieces of equipment (NFR-1)

```bash
API_URL=http://localhost:8000/api/v1 node load-search.mjs
```

The catalogue was filled from 50 to 5,000 through the API, and then six access
paths were timed from outside the server, 60 samples each.

| Path | p50 | p95 | worst |
|---|---|---|---|
| first page, no filter | 28.0 ms | 43.9 ms | 51.2 ms |
| name match | 33.5 ms | 41.2 ms | 50.1 ms |
| brand match | 36.5 ms | 46.2 ms | 49.1 ms |
| one category | 28.2 ms | 32.2 ms | 36.9 ms |
| category and term | 29.6 ms | 45.2 ms | 53.8 ms |
| deep page | 31.9 ms | 39.5 ms | 43.6 ms |

The slowest path is 46.2 ms at the 95th percentile, against a 300 ms budget.

The run on 2026-11-13 put the slowest at 40.2 ms. The six millisecond
difference is the machine, not the code: this run happened with the whole
stack and a browser open, and nothing in the query path changed between them.
Both are far enough inside the budget that the gap does not matter, and
quoting only the better of the two would be picking a number.

What the margin does not prove is in `tool/README.md`, and it is worth saying
out loud before somebody asks: `php artisan serve` handles one request at a
time, so these are the milliseconds one user waits and not the throughput of
several; and a term search is `ILIKE '%term%'`, a sequential scan that is
cheap over 5,000 rows and would not be over half a million.

## What this run does not cover

- **The overdue screen with data in it.** See section 4: the clock belongs to
  the server, and the seeder says so rather than writing a date the API would
  never produce.
- **Browsers other than the one the operator used.** The stylesheets lean on
  `backdrop-filter`, and where it is missing the surfaces fall back to a solid
  tint, which keeps every contrast ratio the same. That fallback has been
  written, not seen.

## Repeating it

Everything above is five commands. Nothing here needs a note from anybody
about what it used to do:

```bash
cd infra && docker compose down -v && docker compose up -d --build
docker compose exec db psql -U filmgear -d filmgear -c "\dt filmgear.*"
curl http://localhost:8000/api/v1/health
cd ../tool && API_URL=http://localhost:8000/api/v1 node seed.mjs
node load-search.mjs
```

The last one takes a few minutes and leaves the catalogue holding 5,000
pieces. Running the first two again puts it back to 50, which is the size the
screens are nicer to look at.
