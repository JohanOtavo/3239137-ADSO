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
| T-12 | Adaptador de persistencia: repositorios de equipo, productor y usuario | T-02, T-08 | Pruebas de integración contra PostgreSQL real |
| T-13 | Adaptador de persistencia: repositorio de alquileres con escritura condicionada | T-12, T-09 | Dos alquileres de la última unidad: uno 201, otro 409 (RNF-2) |
| T-14 | Adaptador de seguridad: hash de claves y token JWT | T-07 | La clave nunca aparece en logs ni respuestas (RNF-3) |
| T-15 | Adaptador de fotos (disco) | T-08 | Subir PNG de 2 MB ok; 3 MB → 422; sin foto → `photo_key` nulo |
| T-16 | Adaptador HTTP: rutas de [`api-contract.md`](api-contract.md), cuerpo de error único, autorización por rol | T-10, T-11, T-14, T-15 | Prueba de contrato que recorre todas las rutas y los catorce códigos |
| T-17 | Composición y arranque (admin inicial desde entorno) | T-16 | El sistema arranca con la base vacía y se puede iniciar sesión como admin |
| T-18 | Guardia de idioma: `tool/check-language.mjs` y su workflow | T-01 | El script falla si se introduce un identificador o un comentario en español |
| T-19 | Pruebas de arquitectura (artículos I, II y III) | T-17 | El test falla si se introduce una importación prohibida |
| T-20 | `infra`: `docker compose up` levanta motor vacío, API y frontend | T-17 | Un comando; la base arranca sin tablas hasta correr las migraciones |
| T-21 | `tool`: sembrador por la **API** (≥ 40 equipos, 10 productores, 15 alquileres, 3 vencidos) | T-17 | Tras correrlo, `/rentals/overdue` devuelve 3 líneas y el reporte tiene filas |
| T-22 | `app`: entrar, catálogo, productores, alquilar, devolver, vencidos, reporte | T-16 | Recorrido manual de punta a punta |
| T-23 | `page`: sitio público estático de presentación | — | Se abre sin la API; correcto en móvil y escritorio |

## Archivos de prueba

El acta pide **15 o más**. Se promete esta lista:

| Carpeta | Archivos |
|---|---|
| `tests/Unit/Domain/` | `ModelCodeTest`, `QuantityTest`, `DocumentNumberTest`, `DateRangeTest`, `EquipmentTest`, `RentalTest`, `RentalItemTest`, `ProducerBorrowingLimitTest`, `UserTest` |
| `tests/Unit/Application/` | `LoginServiceTest`, `CreateAdvisorServiceTest`, `RentEquipmentServiceTest`, `ReturnRentalItemServiceTest`, `MostRequestedReportServiceTest`, `EquipmentCatalogServiceTest` |
| `tests/Unit/Adapters/` | `JwtTokenIssuerTest`, `MultipartBodyTest` |
| `tests/Architecture/` | `LayerDependenciesTest` |
| `tests/Integration/` | `EquipmentRepositoryTest`, `RentalRepositoryTest`, `ApiContractTest`, `ConcurrencyTest`, `CatalogSearchPerformanceTest` |

Veintitrés archivos. Los cinco de integración necesitan PostgreSQL; los demás corren sin nada.

## Definición de terminado

Una tarea pasa a **hecha** solo cuando su evidencia se ha ejecutado y ha pasado. No cuando el código existe, no
cuando «debería funcionar» y no cuando la prueba está escrita pero no se ha corrido. Es el artículo XII, y es lo
que el proyecto de referencia incumplió al declarar veintitrés tareas terminadas con once de ellas sin evidencia
ejecutable.

El estado real de cada tarea vive en el historial de commits y en la salida de `composer test`, no en una tabla
de este documento que haya que mantener a mano.
