# infra

Containers, network, volumes and the **empty** database engine for film-gear.

## What is here and what is not

| Yes | No |
|---|---|
| `docker-compose.yml`: engine and API | Not one `CREATE TABLE` |
| Named network and volumes | No SQL seeds |
| Every service's environment variables | No real credentials |

The schema is **not defined here**. The engine starts with no tables and the
API's migrations create them at boot (article V, ADR-001). Check it:

```bash
grep -ri "create table" .    # returns nothing
```

## One command

```bash
cp .env.example .env          # fill in DB_PASSWORD, JWT_SECRET and ADMIN_PASSWORD
docker compose up --build
```

When it settles:

| Service | Where | What it is |
|---|---|---|
| API | http://localhost:8000/api/v1/health | `{"status":"ok"}` |
| Engine | `localhost:5432` | PostgreSQL 16, schema `filmgear` |

The API boots in this order: wait for the engine to accept connections, run the
migrations, then serve.

## Proving the database arrives empty

```bash
docker compose up -d db
docker compose exec db psql -U filmgear -d filmgear -c "\dt filmgear.*"
# "Did not find any relation named filmgear.*" - correct, the engine is empty

docker compose up -d api
docker compose exec db psql -U filmgear -d filmgear -c "\dt filmgear.*"
# now the six tables, created by the migrations
```

## The queries from data-model section 10

To check the data document is not lying:

```bash
docker compose exec db psql -U filmgear -d filmgear -c "select table_name, column_name, data_type, is_nullable, column_default from information_schema.columns where table_schema = 'filmgear' order by table_name, ordinal_position"

docker compose exec db psql -U filmgear -d filmgear -c "select conname, contype::text, pg_get_constraintdef(oid) from pg_constraint where connamespace = 'filmgear'::regnamespace order by contype, conname"

docker compose exec db psql -U filmgear -d filmgear -c "select indexname, indexdef from pg_indexes where schemaname = 'filmgear' order by indexname"
```

No column may come back with a `column_default`.

## Backend tests inside the container

```bash
docker compose exec api vendor/bin/phpunit
```

## Shut down

```bash
docker compose down        # keeps the data
docker compose down -v     # drops the volume: the database starts empty again
```
