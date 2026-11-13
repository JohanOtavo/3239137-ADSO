# tool

Two scripts, both plain Node with no dependencies.

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
API_URL=http://localhost:8080/api/v1 ADMIN_USERNAME=admin ADMIN_PASSWORD=… node seed.mjs
```
