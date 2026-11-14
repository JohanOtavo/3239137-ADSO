# tool

Three scripts and one shared client, all plain Node with no dependencies.

## `check-language.mjs`

Fails the build when a Spanish identifier or comment is introduced into the
source of `api`, `app`, `page` or `tool` (T-18, article XI).

The previous delivery lost the whole language mark, so the rule is no longer a
habit anybody has to remember: it is a check that runs on every push.

```bash
node check-language.mjs            # the four code folders
node check-language.mjs ../api     # one folder
```

It reads identifiers and comments only. It does **not** read Markdown, and it
does not read the Spanish strings the API returns to the user: those are
messages a person reads, and the contract requires them in Spanish
(article XI). A check that flagged them would be a check nobody could keep
green.

Exit code `0` when clean, `1` with a list of `file:line` otherwise.

## `seed.mjs`

Fills a running instance with enough data to see every screen work: 50 pieces of
equipment, 10 producers and 15 rentals, 5 of them closed (T-21).

Fifteen rentals out of ten producers is not something the rules allow in one
pass: a producer may hold two pieces of equipment at a time, and a rental
carries at most two lines. So the first round fills every producer to the limit,
five of those rentals are returned, and the freed producers rent again.

**On overdue lines.** The task asks for three. The seeder cannot produce them,
and says so instead of pretending: a line falls due four days after the rental
and the server owns that clock, so the only way to stage an overdue line is to
run the API with its clock moved forward. A seeder that wrote the date directly
would be writing a state the API cannot reach, which is the one thing seeding
through the API is meant to rule out.

It goes **through the API**, never into the database. A seeder that wrote rows
directly would be free to write states the API cannot produce, and the data it
left would prove nothing about the rules.

```bash
API_URL=http://localhost:8000/api/v1 ADMIN_USERNAME=admin ADMIN_PASSWORD=… node seed.mjs
```

## `load-search.mjs`

Measures the equipment search against NFR-1: under 300 ms with 5,000 pieces of
equipment.

It fills the catalogue to 5,000 through the API, then times six access paths
from outside the server, so the number is what a browser waits for: routing,
middleware, token check, the query and the serialisation. A query timed inside
PostgreSQL would answer a different question, and it is not the question the
requirement asks.

```bash
API_URL=http://localhost:8000/api/v1 ADMIN_USERNAME=admin ADMIN_PASSWORD=… node load-search.mjs
```

It reports the **95th percentile** per path, not the mean. A mean hides exactly
the requests that matter: nobody notices the fast half. The percentile is a
nearest-rank value with no interpolation, so every figure printed is a time
some request really took.

The deep page is in the list on purpose. An offset still walks the rows it
skips, so page 40 is the dearest path the catalogue has, and a report that
measured only the first page would name a budget nobody is ever inside.

Exit code `0` only when the catalogue reached 5,000 pieces **and** every path
is inside the budget. A run that filled less says so and still fails: half a
catalogue cannot answer the requirement.

Knobs, all optional: `BUDGET_MS`, `TARGET_EQUIPMENT`, `SAMPLES`,
`FILL_CONCURRENCY`.

## `api.mjs`

The HTTP client the seeder and the load test share. It keeps the token private
and throws an `ApiError` carrying the status and the body, so a caller can tell
a refused rule from a broken script.

It exists because both scripts needed the same fetch wrapper, and a second copy
is a second place for the `Authorization` header to be forgotten.

## Tests

```bash
node --test
```

23 tests over the parts where a mistake is silent: the guard in both directions,
the percentile, the generated model codes, and the client's token, statuses and
query string.

### The measured run

5,000 pieces of equipment, 60 samples per path, against the compose stack:

| Path | p50 | p95 | worst |
|---|---|---|---|
| first page, no filter | 24.2 ms | 26.4 ms | 36.1 ms |
| name match | 29.0 ms | 31.3 ms | 33.3 ms |
| brand match | 29.0 ms | 31.6 ms | 32.5 ms |
| one category | 23.9 ms | 26.9 ms | 27.6 ms |
| category and term | 25.1 ms | 27.3 ms | 27.8 ms |
| deep page | 30.0 ms | 40.2 ms | 50.3 ms |

NFR-1 met: the slowest path is 40.2 ms at p95 against a 300 ms budget.

Two things that margin does **not** prove, and it is better to say so than to
be asked:

- The API is served by `php artisan serve`, which handles one request at a
  time. These are the numbers one user waits for, not the throughput of
  several at once.
- A term search is `ILIKE '%term%'`, which no btree index can serve, so it is
  a sequential scan. Over 5,000 rows that is cheap and the figures show it.
  Over half a million it would not be, and the fix then is a trigram index,
  not a faster loop.
