# ADR-001 - Migrations own the schema

- **Status:** accepted
- **Context:** the database schema can be born in two places: a script the PostgreSQL container runs at startup,
  or the application's migrations. If it is born in both, they drift apart and nobody knows which one is right.
- **Decision:** `infra` starts an **empty** engine. All DDL - the six tables, the foreign keys, the indexes, the
  three `CHECK` constraints and the seed of five categories - lives in the migrations under `api`.
- **Alternatives rejected:** an `init.sql` mounted into the container, which leaves the schema outside the
  application's version control and forces recreating the volume for every change; an ORM that syncs the schema
  by itself, which makes it impossible to review in a pull request what changed in the database.
- **Consequences:** starting the system is two steps, not one: start the engine, then migrate. The API
  container's entrypoint chains them. In exchange, the schema is reviewed as code and can be rolled back.
- **How it is checked:** `grep -ri "create table" infra/` returns nothing. With a freshly created volume, the
  database has no tables until the migrations run.
