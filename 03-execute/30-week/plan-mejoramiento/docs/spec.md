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

## 3. Historias de usuario y criterios de aceptación

### HU-01 · Iniciar sesión
Como usuario quiero autenticarme para operar según mi rol.
- **CA-01.1** Con credenciales válidas se recibe un token con vigencia de 8 horas.
- **CA-01.2** Con credenciales inválidas responde 401 con un mensaje que **no** dice si falló el usuario o la clave.
- **CA-01.3** Todo endpoint salvo el inicio de sesión y `/health` exige token. Sin token: 401. Rol insuficiente: 403.

### HU-02 · Dar de alta asesores (admin)
- **CA-02.1** El admin crea un usuario con nombre, clave y rol `advisor`.
- **CA-02.2** El nombre de usuario se guarda en minúsculas y recortado; es único. Repetido: 409.
- **CA-02.3** Pedir el rol `admin` por la API responde 422. La clave nunca se devuelve ni se registra en logs.

### HU-03 · Gestionar equipos (admin)
- **CA-03.1** Crear equipo con nombre, marca, código de modelo, categoría y total de unidades. Las unidades
  disponibles arrancan iguales al total.
- **CA-03.2** El código de modelo tiene de 4 a 20 caracteres en mayúsculas, dígitos o guiones, y es único entre
  equipos activos. Repetido: 409.
- **CA-03.3** Total de unidades ≥ 1. Cambiar el total ajusta las disponibles en la misma diferencia y **nunca**
  deja disponibles < 0 (422).
- **CA-03.4** Subir foto (JPG/PNG, máx. 2 MB) guarda una **clave opaca**; sin foto el campo es `null`, nunca
  cadena vacía.
- **CA-03.5** Eliminar un equipo es **baja lógica**: deja de aparecer en el catálogo pero sigue en los alquileres
  pasados. Un equipo con alquileres abiertos no se da de baja (409).

### HU-04 · Buscar equipos
- **CA-04.1** Búsqueda por texto parcial en nombre o marca, filtro por categoría, solo equipos activos, orden por
  nombre, paginada (`page`, `size`, máx. 50).
- **CA-04.2** La respuesta incluye el total de resultados.
- **CA-04.3** Consultar un equipo por id inexistente o dado de baja: 404.

### HU-05 · Gestionar productores
- **CA-05.1** El admin y el asesor crean productores con nombre y documento. El documento es único: repetido 409.
- **CA-05.2** Se lista y se busca por nombre o documento, paginado.
- **CA-05.3** Los productores no se eliminan.
