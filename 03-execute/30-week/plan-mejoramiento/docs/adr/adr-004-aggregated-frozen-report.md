# ADR-004 - Aggregated report over frozen values

- **Status:** accepted
- **Context:** the most-requested report has to be **stable**: a closed period cannot change because somebody
  renames a piece of equipment or moves its category (AC-10.4). If the report reads the live catalogue,
  November's figures change in December.
- **Decision:** the rental line **freezes** `equipment_name`, `model_code` and `category_name` at the instant of
  the operation, and the report groups by those values, never by the catalogue. `rental_item.category_name`
  carries **no foreign key on purpose**: it is not a reference, it is a copy. The report resolves in a single
  aggregated query behind the `MostRequestedQuery` port, not by walking entities.
- **Alternatives rejected:** reading the current name from the catalogue with a `JOIN`, which is exactly what
  makes the report unstable; a materialised report table, which has to be maintained and can drift; versioning
  the catalogue with validity dates, which solves the same problem with ten times the model.
- **Consequences:** there is deliberate duplication between `equipment` and `rental_item`, and it is correct:
  they are two different facts, what the equipment is today and what it was when it went out. The report exposes
  no producers and no advisors (AC-10.3) because it aggregates by equipment and never queries them.
- **How it is checked:** a test rents a piece of equipment, renames it afterwards, and asserts the report still
  returns the old name.
