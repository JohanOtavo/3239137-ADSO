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
