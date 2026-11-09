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

### HU-06 · Registrar un alquiler
- **CA-06.1** Un alquiler es de **un** productor, lo registra el usuario autenticado y trae de 1 a 2 líneas.
- **CA-06.2** No se repite el mismo equipo en un alquiler (422).
- **CA-06.3** Cada línea descuenta **1** unidad disponible en la misma operación. Sin unidades: 409 y **no se
  registra nada**.
- **CA-06.4** Un productor no puede tener más de **2 equipos sin devolver** en total (422 con el conteo actual).
- **CA-06.5** Un productor con algún equipo **vencido** no puede alquilar otro (409).
- **CA-06.6** La fecha de vencimiento es `rented_at + 4 días`. La línea congela nombre, código de modelo y nombre
  de categoría **en ese instante**.
- **CA-06.7** El alquiler registrado no se edita ni se borra.

### HU-07 · Registrar una devolución
- **CA-07.1** Se devuelve **una línea** a la vez. Pone `returned_at` y suma 1 a las unidades disponibles.
- **CA-07.2** Devolver una línea ya devuelta: 409. **Nunca** suma dos veces.
- **CA-07.3** Las unidades disponibles jamás superan el total (la base lo garantiza).
- **CA-07.4** Cuando todas las líneas están devueltas, el alquiler figura como **cerrado**.

### HU-08 · Ver un alquiler y listar alquileres
- **CA-08.1** Consultar un alquiler trae sus líneas y su estado calculado (`open`, `overdue`, `closed`).
- **CA-08.2** Listar por rango de fechas, estado y productor, orden por fecha descendente, paginado.
- **CA-08.3** El estado **se calcula**, no se almacena.

### HU-09 · Equipos vencidos
- **CA-09.1** Lista las líneas sin devolver con `due_at` anterior a ahora: equipo, productor, días de retraso.
- **CA-09.2** Ordenadas por más días de retraso primero. Solo `admin` y `advisor`.

### HU-10 · Reporte de equipos más solicitados
- **CA-10.1** Dado un rango de fechas, una fila por **equipo y nombre de categoría congelado**, con la cantidad de
  veces alquilado, ordenado de mayor a menor.
- **CA-10.2** Fin anterior al inicio: 422. Rango sin alquileres: lista vacía, no error.
- **CA-10.3** El reporte **no** se desglosa por asesor ni por productor.
- **CA-10.4** Un reporte de un periodo cerrado **no cambia** aunque luego se renombre el equipo o su categoría.
- **CA-10.5** Solo `admin`.

## 4. Reglas de negocio

| # | Regla |
|---|---|
| RN-1 | Unidades disponibles entre 0 y el total, siempre. |
| RN-2 | Plazo de alquiler: 4 días. Máximo 2 equipos sin devolver por productor. |
| RN-3 | Un productor con vencidos no alquila más hasta devolverlos. |
| RN-4 | Lo congelado en la línea (nombre, código de modelo, categoría) no sigue al catálogo. |
| RN-5 | Alquileres y devoluciones no se borran. Los equipos solo tienen baja lógica. |
| RN-6 | Las fechas son instantes UTC. El texto al usuario va en español; el código, en inglés. |

## 5. Requisitos no funcionales

| # | Requisito | Cómo se mide |
|---|---|---|
| RNF-1 | La búsqueda de equipos responde en menos de 300 ms con 5 000 equipos | Prueba de carga con el sembrador de `tool` |
| RNF-2 | Dos asesores entregando la **última unidad** a la vez: uno gana, el otro recibe 409 | Prueba concurrente con dos procesos |
| RNF-3 | La contraseña nunca viaja ni se guarda en claro; nunca aparece en logs | Revisión de logs y de respuestas |
| RNF-4 | Todo error HTTP usa el mismo cuerpo (ver contrato) | Prueba de contrato |
| RNF-5 | El sistema levanta con un solo comando y la base arranca vacía | `README` de `infra` |
