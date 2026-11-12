# API contract - film-gear

The HTTP contract is **language independent**: it defines what is asked and what comes back, not how it is
implemented. What each operation does is in [`spec.md`](spec.md); what gets stored, in
[`data-model.md`](data-model.md).

## 1. Conventions

| Topic | Rule |
|---|---|
| Base | `/api/v1` |
| Format | JSON, UTF-8. Field names in `snake_case` |
| Identifiers | UUID v4 as text |
| Dates | ISO 8601 in UTC, with `Z` (`2026-11-12T14:15:00Z`) |
| Money | None. The system handles no amounts |
| Authentication | `Authorization: Bearer <token>` on everything except `POST /auth/login` and `GET /health` |
| Pagination | `?page=1&size=20`. `size` caps at 50. The response is `{ "items": [...], "page": 1, "size": 20, "total": 134 }` |
| Language | Routes, fields and codes in English; error messages in Spanish (article XI) |

### Error body (one shape for every error)

```json
{
  "code": "EQUIPMENT_NOT_AVAILABLE",
  "message": "No quedan unidades disponibles de «Sony FX3».",
  "details": [ { "field": "items[0].equipment_id", "issue": "sin unidades" } ]
}
```

`details` may be empty. It **never** carries stack traces, SQL, server paths or the password. The `message` and
the `issue` text are written in Spanish because they are shown to the person using the application.

### Status codes

| Status | When |
|---|---|
| 200 / 201 / 204 | Success / created / no content |
| 401 | No token, invalid or expired token, invalid credentials |
| 403 | Insufficient role |
| 404 | Does not exist (or is soft deleted) |
| 409 | State conflict: duplicate, no units, already returned, producer with overdue items, equipment with open rentals |
| 422 | Validation: invalid field, broken input rule, inverted date range |

## 2. Business error codes

| `code` | Status | Source |
|---|---|---|
| `INVALID_CREDENTIALS` | 401 | AC-01.2 |
| `FORBIDDEN_ROLE` | 403 | AC-01.3 |
| `NOT_FOUND` | 404 | Any resource |
| `USERNAME_TAKEN` | 409 | AC-02.2 |
| `MODEL_CODE_TAKEN` | 409 | AC-03.2 |
| `DOCUMENT_TAKEN` | 409 | AC-05.1 |
| `EQUIPMENT_HAS_OPEN_RENTALS` | 409 | AC-03.5 |
| `EQUIPMENT_NOT_AVAILABLE` | 409 | AC-06.3 |
| `PRODUCER_HAS_OVERDUE` | 409 | AC-06.5 |
| `ITEM_ALREADY_RETURNED` | 409 | AC-07.2 |
| `PRODUCER_LIMIT_EXCEEDED` | 422 | AC-06.4 |
| `DUPLICATE_EQUIPMENT_IN_RENTAL` | 422 | AC-06.2 |
| `VALIDATION_ERROR` | 422 | Any invalid field |
| `INVALID_DATE_RANGE` | 422 | AC-10.2 |

Fourteen codes and no more. An error that does not fit this table is a defect, not a business case.

## 3. Endpoints

### 3.1 Authentication and health

| Method | Route | Role | Description |
|---|---|---|---|
| POST | `/auth/login` | public | Returns a token |
| GET | `/health` | public | `{ "status": "ok" }` |

`POST /auth/login`

```json
// request
{ "username": "ana", "password": "********" }
// 200
{ "access_token": "eyJ...", "token_type": "Bearer", "expires_in": 28800, "role": "advisor" }
```

### 3.2 Users

| Method | Route | Role | Description |
|---|---|---|---|
| POST | `/users` | admin | Creates an advisor |

```json
// request  (role only accepts "advisor")
{ "username": "Ana ", "password": "********", "role": "advisor" }
// 201  (no password, no password_hash)
{ "id": "…", "username": "ana", "role": "advisor" }
```

### 3.3 Categories

| Method | Route | Role | Description |
|---|---|---|---|
| GET | `/categories` | authenticated | The five categories, ordered by name |

### 3.4 Equipment

| Method | Route | Role | Description |
|---|---|---|---|
| GET | `/equipment` | authenticated | Search: `q`, `category_id`, `page`, `size` |
| GET | `/equipment/{id}` | authenticated | Detail |
| POST | `/equipment` | admin | Creates |
| PUT | `/equipment/{id}` | admin | Replaces name, brand, category and total units |
| DELETE | `/equipment/{id}` | admin | Soft delete |
| PUT | `/equipment/{id}/photo` | admin | Uploads a photo (`multipart/form-data`, field `file`) |
| DELETE | `/equipment/{id}/photo` | admin | Removes the photo |
| GET | `/equipment/{id}/photo` | authenticated | Returns the binary, or 404 |

Equipment (response):

```json
{
  "id": "…", "name": "Sony FX3", "brand": "Sony", "model_code": "FX3-BODY",
  "category": { "id": "…", "name": "Cámaras" },
  "total_units": 4, "available_units": 3, "photo_key": null
}
```

`POST /equipment` (request): `name`, `brand`, `model_code`, `category_id`, `total_units`. Available units are
computed by the server. `model_code` is not editable: it identifies the equipment.

`photo_key` is an opaque key: to render the photo a client calls `GET /equipment/{id}/photo`. Uploads are
validated by **content**, not by extension, and accept only `image/jpeg` and `image/png` up to 2 MB.

### 3.5 Producers

| Method | Route | Role | Description |
|---|---|---|---|
| GET | `/producers` | admin, advisor | Lists and searches: `q`, `page`, `size` |
| GET | `/producers/{id}` | admin, advisor | Detail with `open_rentals` (count of items still out) |
| POST | `/producers` | admin, advisor | Creates: `full_name`, `document_number` |

```json
// 201
{ "id": "…", "full_name": "Laura Ortiz", "document_number": "1098765432" }
```

### 3.6 Rentals

| Method | Route | Role | Description |
|---|---|---|---|
| POST | `/rentals` | admin, advisor | Records a rental |
| GET | `/rentals` | admin, advisor | Lists: `from`, `to`, `status`, `producer_id`, `page`, `size` |
| GET | `/rentals/{id}` | admin, advisor | Detail with lines and state |
| POST | `/rentals/{rental_id}/items/{item_id}/return` | admin, advisor | Returns one line |
| GET | `/rentals/overdue` | admin, advisor | Overdue lines |

`POST /rentals`

```json
// request  (1 or 2 items, no repeated equipment)
{ "producer_id": "…", "items": [ { "equipment_id": "…" }, { "equipment_id": "…" } ] }
// 201
{
  "id": "…", "producer_id": "…", "rented_at": "2026-11-12T14:15:00Z", "status": "open",
  "items": [
    { "id": "…", "equipment_id": "…", "equipment_name": "Sony FX3", "model_code": "FX3-BODY",
      "category_name": "Cámaras", "due_at": "2026-11-16T14:15:00Z", "returned_at": null }
  ]
}
```

`status` is computed: `open` (lines still out and none overdue), `overdue` (something out and past due),
`closed` (everything returned).

`POST /rentals/{rental_id}/items/{item_id}/return` returns 200 with the line, `returned_at` filled in.

`GET /rentals/overdue` returns `items`:
`{ "rental_id", "item_id", "equipment_name", "producer_name", "due_at", "days_late" }`.
The producer name does appear here, because this is an operational list; **the report in 3.7 does not show it**.

### 3.7 Report

| Method | Route | Role | Description |
|---|---|---|---|
| GET | `/reports/most-requested` | admin | `from` and `to` required (dates, both inclusive) |

```json
{
  "from": "2026-11-01", "to": "2026-11-30",
  "rows": [
    { "equipment_id": "…", "equipment_name": "Sony FX3", "category_name": "Cámaras", "times_rented": 12 }
  ]
}
```

One row per frozen `equipment_id + equipment_name + category_name`. Order: `times_rented` descending, then name.
No producers, no advisors.

## 4. Security

| Aspect | Rule |
|---|---|
| Token | Signed JWT, 8 hours, carrying `sub` and `role`. No personal data inside |
| Passwords | Only the hash is stored; the hash never leaves in any response |
| Authorisation | Evaluated per endpoint by role; a 403 does not reveal whether the resource exists |
| CORS | Only the frontend origin configured per environment |
| Photo upload | `image/jpeg` and `image/png` only, max 2 MB; the type is validated by content, not by extension |
