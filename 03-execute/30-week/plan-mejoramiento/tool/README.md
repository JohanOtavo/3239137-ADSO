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

Fills a running instance with enough data to see every screen work: 40 pieces of
equipment, 10 producers and 15 rentals, 3 of them overdue (T-21).

It goes **through the API**, never into the database. A seeder that wrote rows
directly would be free to write states the API cannot produce, and the data it
left would prove nothing about the rules.

```bash
API_URL=http://localhost:8080/api/v1 ADMIN_USERNAME=admin ADMIN_PASSWORD=… node seed.mjs
```
