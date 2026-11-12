# ADR-003 - Soft delete for the catalogue, no physical removal

- **Status:** accepted
- **Context:** equipment leaves the catalogue when it is sold, lost or broken. But that equipment appears in past
  rentals, and rentals are never deleted (BR-5). If the row disappears, the history is orphaned and the report
  lies.
- **Decision:** `equipment.deleted_at`. Null means active. Soft-deleted equipment stops appearing in the
  catalogue and in searches, but still exists for the rental lines that reference it. Equipment with open rentals
  **cannot be soft deleted**: it answers 409 `EQUIPMENT_HAS_OPEN_RENTALS` (AC-03.5).
- **Alternatives rejected:** physical delete with `ON DELETE SET NULL` on the line, which loses what was rented;
  a separate archive table, which duplicates the model and forces every query to look in two places; a boolean
  `active` column, which does not record when the equipment was retired.
- **Consequences:** every catalogue query carries `WHERE deleted_at IS NULL`, and the unique index on
  `model_code` is **partial** over that same condition: retiring equipment frees its code for a new one. The
  `rental_item.equipment_id` foreign key is RESTRICT, as a last line of defence against a manual delete.
- **How it is checked:** a test soft deletes equipment and asserts it disappears from the catalogue, that
  `GET /equipment/{id}` answers 404, and that the rental line referencing it still returns its data.
