# Contrato de la API — film-gear

El contrato HTTP es **independiente del lenguaje**: define qué se pide y qué se responde, no cómo se implementa.
Qué hace cada operación está en [`spec.md`](spec.md); qué se guarda, en [`data-model.md`](data-model.md).

## 1. Convenciones

| Tema | Regla |
|---|---|
| Base | `/api/v1` |
| Formato | JSON, UTF-8. Nombres de campo en `snake_case` |
| Identificadores | UUID v4 como texto |
| Fechas | ISO 8601 en UTC, con `Z` (`2026-11-10T14:15:00Z`) |
| Dinero | No hay. El sistema no maneja importes |
| Autenticación | `Authorization: Bearer <token>` en todo salvo `POST /auth/login` y `GET /health` |
| Paginación | `?page=1&size=20`. `size` máximo 50. La respuesta es `{ "items": [...], "page": 1, "size": 20, "total": 134 }` |
| Idioma | Rutas, campos y códigos en inglés; mensajes de error en español (artículo XI) |

### Cuerpo de error (único para todos los errores)

```json
{
  "code": "EQUIPMENT_NOT_AVAILABLE",
  "message": "No quedan unidades disponibles de «Sony FX3».",
  "details": [ { "field": "items[0].equipment_id", "issue": "sin unidades" } ]
}
```

`details` puede ir vacío. **Nunca** incluye trazas, SQL, rutas del servidor ni la contraseña.

### Códigos de estado

| Estado | Cuándo |
|---|---|
| 200 / 201 / 204 | Éxito / creado / sin contenido |
| 401 | Sin token, token inválido o vencido, credenciales inválidas |
| 403 | Rol insuficiente |
| 404 | No existe (o está dado de baja) |
| 409 | Conflicto de estado: duplicado, sin unidades, ya devuelto, productor con vencidos, equipo con alquileres abiertos |
| 422 | Validación: campo inválido, regla de entrada rota, rango de fechas invertido |

## 2. Códigos de error de negocio

| `code` | Estado | Origen |
|---|---|---|
| `INVALID_CREDENTIALS` | 401 | CA-01.2 |
| `FORBIDDEN_ROLE` | 403 | CA-01.3 |
| `NOT_FOUND` | 404 | Cualquier recurso |
| `USERNAME_TAKEN` | 409 | CA-02.2 |
| `MODEL_CODE_TAKEN` | 409 | CA-03.2 |
| `DOCUMENT_TAKEN` | 409 | CA-05.1 |
| `EQUIPMENT_HAS_OPEN_RENTALS` | 409 | CA-03.5 |
| `EQUIPMENT_NOT_AVAILABLE` | 409 | CA-06.3 |
| `PRODUCER_HAS_OVERDUE` | 409 | CA-06.5 |
| `ITEM_ALREADY_RETURNED` | 409 | CA-07.2 |
| `PRODUCER_LIMIT_EXCEEDED` | 422 | CA-06.4 |
| `DUPLICATE_EQUIPMENT_IN_RENTAL` | 422 | CA-06.2 |
| `VALIDATION_ERROR` | 422 | Cualquier campo inválido |
| `INVALID_DATE_RANGE` | 422 | CA-10.2 |

Catorce códigos y ninguno más. Un error que no encaje en esta tabla es un defecto, no un caso de negocio.

## 3. Endpoints

### 3.1 Autenticación y salud

| Método | Ruta | Rol | Descripción |
|---|---|---|---|
| POST | `/auth/login` | público | Devuelve token |
| GET | `/health` | público | `{ "status": "ok" }` |

`POST /auth/login`

```json
// petición
{ "username": "ana", "password": "********" }
// 200
{ "access_token": "eyJ...", "token_type": "Bearer", "expires_in": 28800, "role": "advisor" }
```

### 3.2 Usuarios

| Método | Ruta | Rol | Descripción |
|---|---|---|---|
| POST | `/users` | admin | Crea un asesor |

```json
// petición  (role solo admite "advisor")
{ "username": "Ana ", "password": "********", "role": "advisor" }
// 201  (sin password ni password_hash)
{ "id": "…", "username": "ana", "role": "advisor" }
```

### 3.3 Categorías

| Método | Ruta | Rol | Descripción |
|---|---|---|---|
| GET | `/categories` | autenticado | Las cinco categorías, orden por nombre |

### 3.4 Equipos

| Método | Ruta | Rol | Descripción |
|---|---|---|---|
| GET | `/equipment` | autenticado | Búsqueda: `q`, `category_id`, `page`, `size` |
| GET | `/equipment/{id}` | autenticado | Detalle |
| POST | `/equipment` | admin | Crea |
| PUT | `/equipment/{id}` | admin | Reemplaza nombre, marca, categoría y total de unidades |
| DELETE | `/equipment/{id}` | admin | Baja lógica |
| PUT | `/equipment/{id}/photo` | admin | Sube foto (`multipart/form-data`, campo `file`) |
| DELETE | `/equipment/{id}/photo` | admin | Quita la foto |
| GET | `/equipment/{id}/photo` | autenticado | Devuelve el binario o 404 |

Equipo (respuesta):

```json
{
  "id": "…", "name": "Sony FX3", "brand": "Sony", "model_code": "FX3-BODY",
  "category": { "id": "…", "name": "Cámaras" },
  "total_units": 4, "available_units": 3, "photo_key": null
}
```

`POST /equipment` (petición): `name`, `brand`, `model_code`, `category_id`, `total_units`. Las unidades
disponibles las calcula el servidor. `model_code` no se edita: identifica al equipo.

`photo_key` es una clave opaca: para pintar la foto se usa `GET /equipment/{id}/photo`. La subida se valida por
**contenido**, no por extensión, y acepta solo `image/jpeg` e `image/png` hasta 2 MB.

### 3.5 Productores

| Método | Ruta | Rol | Descripción |
|---|---|---|---|
| GET | `/producers` | admin, advisor | Lista y busca: `q`, `page`, `size` |
| GET | `/producers/{id}` | admin, advisor | Detalle con `open_rentals` (conteo sin devolver) |
| POST | `/producers` | admin, advisor | Crea: `full_name`, `document_number` |

```json
// 201
{ "id": "…", "full_name": "Laura Ortiz", "document_number": "1098765432" }
```
