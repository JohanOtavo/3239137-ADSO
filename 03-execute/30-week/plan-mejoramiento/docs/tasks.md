# Tareas — film-gear

Cada tarea termina cuando su **evidencia** existe y se puede ejecutar (artículo XII). Estado inicial de todas:
**pendiente**.

Este documento es contra lo que se mide la completitud del proyecto. Promete lo que se va a entregar, no lo que
se querría entregar.

| # | Tarea | Depende de | Evidencia |
|---|---|---|---|
| T-01 | Esqueleto de Laravel y `GET /health` | — | `GET /api/v1/health` responde `{"status":"ok"}`; el contenedor arranca con un comando |
| T-02 | Migración inicial: seis tablas, FK, índices únicos, los tres `CHECK` y las cinco categorías | T-01 | Las consultas de `data-model.md` §10 devuelven lo documentado; ninguna columna con `DEFAULT` |
| T-03 | Dominio: `ModelCode`, `Quantity`, `DocumentNumber`, `DateRange` | T-01 | Pruebas unitarias: código de 4 a 20 caracteres, cantidad > 0, fin ≥ inicio |
| T-04 | Dominio: `Equipment` (`withdraw`, `restock`, cambio de total, baja lógica) | T-03 | Pruebas: nunca < 0, nunca > total, bajar el total por debajo de lo alquilado falla |
| T-05 | Dominio: `Rental` (`addItem`, `returnItem`, estado calculado, 4 días, máx. 2 líneas) | T-04 | Pruebas con un `Clock` falso: `open`, `overdue`, `closed` |
| T-06 | Dominio: `Producer` y `User` (normalización de nombre, rol válido) | T-03 | `"Ana "` queda `ana`; rol `admin` por API rechazado |
| T-07 | Puertos y casos de uso de **autenticación** (`LoginService`, `CreateAdvisorService`) | T-06 | HU-01 y HU-02 pasan contra dobles |
| T-08 | Puertos y casos de uso de **equipos** (crear, buscar, editar, baja, foto) | T-04 | HU-03 y HU-04 pasan contra dobles |
| T-09 | Caso de uso **alquilar** (`RentEquipmentService`) | T-05, T-08 | HU-06 completa: límite de 2, vencidos, sin unidades |
| T-10 | Caso de uso **devolver** y **vencidos** | T-09 | HU-07 y HU-09; devolver dos veces → `ITEM_ALREADY_RETURNED` |
| T-11 | Caso de uso **reporte de más solicitados** y su puerto de lectura | T-09 | HU-10; el nombre viejo se conserva tras renombrar ([ADR-004](adr/adr-004-reporte-agregado-y-congelado.md)) |
