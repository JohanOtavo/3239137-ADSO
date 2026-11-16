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

## What this run does not cover

- **The load test.** NFR-1 was measured on 2026-11-13 over 5,000 pieces of
  equipment and the figures are in `tool/README.md`. It was not re-run here
  because it would leave the instance holding 5,000 rows, and the next thing
  this stack is for is a walk through the screens.
- **The overdue screen with data in it.** See section 4.
- **The signed-in screens.** They need the operator's credentials.

## Repeating it

Everything above is five commands. Nothing here needs a note from anybody
about what it used to do:

```bash
cd infra && docker compose down -v && docker compose up -d --build
docker compose exec db psql -U filmgear -d filmgear -c "\dt filmgear.*"
curl http://localhost:8000/api/v1/health
cd ../tool && API_URL=http://localhost:8000/api/v1 node seed.mjs
```
