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
