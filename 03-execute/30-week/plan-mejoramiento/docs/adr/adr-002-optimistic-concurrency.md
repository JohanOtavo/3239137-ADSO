# ADR-002 - Optimistic concurrency over the units

- **Status:** accepted
- **Context:** two advisors can hand out the **last unit** of the same equipment at the same time (NFR-2). If
  both read `available_units = 1` and both write, the system rents a unit it does not have.
- **Decision:** **optimistic** concurrency control, using `available_units` as the token. The write carries the
  value that was read as a condition:

  ```sql
  UPDATE filmgear.equipment
     SET available_units = :new_value
   WHERE id = :id
     AND available_units = :what_was_read
     AND deleted_at IS NULL;
  ```

  Zero rows affected means somebody got there first, and the server answers **409
  `EQUIPMENT_NOT_AVAILABLE`**. The **last line of defence** is `CHECK ck_equipment_available_range`.
- **Alternatives rejected:** a separate `version` column, which `data-model.md` does not declare and which is not
  needed because the counter itself serves as the token; pessimistic locking (`SELECT … FOR UPDATE`) on every
  rental, which serialises the whole catalogue for a rare case; an in-memory counter, which does not survive more
  than one process.
- **Consequences:** if the `CHECK` fires, **something wrote outside the adapter**: that is a defect, not a
  business case. Returning the same line twice does not give the unit back twice, because `returned_at` is
  already set (AC-07.2).
- **How it is checked:** `tests/Integration/ConcurrencyTest` launches two simultaneous processes against
  equipment holding one unit: exactly one receives 201.
