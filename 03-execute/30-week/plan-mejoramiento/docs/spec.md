# Especificación — film-gear

**Qué se construye y por qué.** Un sistema para que una casa de alquiler de equipo audiovisual lleve su
catálogo de equipos, sus productores y sus alquileres, y vea qué equipos se solicitan más.

Rige bajo [`constitution.md`](constitution.md). El modelo de datos vive solo en [`data-model.md`](data-model.md)
y el contrato HTTP solo en [`api-contract.md`](api-contract.md).

---

## 1. Actores

| Actor | Qué hace | Cómo se autentica |
|---|---|---|
| **Administrador** (`admin`) | Mantiene el catálogo de equipos y los productores; da de alta asesores; ve todos los reportes | Usuario y contraseña → token |
| **Asesor** (`advisor`) | Registra alquileres y devoluciones; consulta catálogo, productores y vencidos | Usuario y contraseña → token |
| **Productor** | Quien alquila el equipo. **No entra al sistema**: es un dato, no un usuario | — |

El rol `admin` lo provisiona el despliegue desde variables de entorno. **Nadie lo otorga en ejecución.**

## 2. Alcance

**Dentro:** catálogo de equipos con foto opcional, cinco categorías fijas, productores, alquileres con varias
líneas, devolución por línea, equipos vencidos, reporte de más solicitados, inicio de sesión con roles.

**Fuera, y no se reabre:** tarifas, cobros y depósitos; reservas anticipadas; renovaciones; correo o SMS;
importación masiva; alquileres entre sedes; número de serie por unidad; estado de condición del equipo;
seguros; y cualquier atributo de equipo más allá de los de §3.

El sistema **no maneja importes**. Una unidad se cuenta, no se identifica: no hay inventario por número de
serie, y dos unidades del mismo modelo son intercambiables.
