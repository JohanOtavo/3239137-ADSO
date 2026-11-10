Este proyecto es la solución del plan de mejoramiento del aprendiz A07, ficha 3239137, dominio film-gear.

# film-gear · Alquiler de cámaras y drones para producción

Una casa de alquiler de equipo audiovisual necesita llevar su catálogo, sus
productores y sus alquileres, y saber qué equipos se solicitan más. Eso es todo
lo que hace este sistema, y nada más.

## Qué resuelve

| Concepto | Qué es aquí |
|---|---|
| **Equipo** | Un modelo del catálogo, identificado por su código de modelo. Las unidades se cuentan, no se identifican |
| **Unidad** | Una copia física de un equipo. Hay un total y hay disponibles |
| **Categoría** | Cinco fijas y sembradas: cámaras, lentes, drones, audio, estabilizadores |
| **Productor** | Quien alquila el equipo. No entra al sistema: es un dato |
| **Asesor** | Quien atiende el mostrador y registra las operaciones |
| **Alquiler** | Un hecho consumado: quién pidió, quién lo registró y cuándo |

## Las reglas que no se negocian

- Plazo de **4 días** desde el momento del alquiler.
- Máximo **2 equipos sin devolver** por productor.
- Quien tenga algo **vencido** no recibe más hasta devolverlo.
- Las unidades disponibles van **entre 0 y el total**, siempre.
- Lo que la línea de la operación **congela** no sigue al catálogo.
- Operaciones y devoluciones **no se borran**; el catálogo solo tiene baja lógica.
- Las fechas son instantes **UTC**. Los mensajes al usuario van en español.

## Las seis carpetas

| Carpeta | Qué va ahí |
|---|---|
| [`docs/`](docs/) | La especificación propia: constitución, spec, plan, arquitectura, modelo de datos, contrato de la API, tareas y las ADR |
| [`api/`](api/) | Backend en PHP con Laravel |
| [`app/`](app/) | Frontend en React |
| [`page/`](page/) | Sitio público estático que presenta el producto |
| [`infra/`](infra/) | Contenedores y base de datos, con un solo comando |
| [`tool/`](tool/) | Sembrador de datos de demostración, por la API |

## Cómo se lee

Empiece por [`docs/constitution.md`](docs/constitution.md): son los principios
que el resto del proyecto no puede romper, cada uno con su forma de
comprobarse. Después [`docs/spec.md`](docs/spec.md), que dice qué debe hacer el
sistema.

## Estado

En construcción. El avance queda en el historial de commits de esta carpeta, un
commit por pieza terminada.

Los documentos de la especificación, y cuáles existen ya. Los enlaces a los que
aún no están darán 404 hasta la fecha indicada:

| Documento | Estado |
|---|---|
| `docs/constitution.md` | Escrito |
| `docs/spec.md` | Escrito |
| `docs/data-model.md` | Escrito |
| `docs/api-contract.md` | Pendiente |
| `docs/plan.md` | Pendiente |
| `docs/architecture.md` | Pendiente |
| `docs/tasks.md` | Pendiente |
| `docs/adr/` (cuatro) | Pendiente |

Nada en esta carpeta declara terminado lo que no lo está: es el artículo XII, y
es la razón por la que esta tabla existe.

## Documento del plan

El acta del plan de mejoramiento, en su versión con código y sin datos
personales: [`Plan-Mejoramiento-A07.pdf`](Plan-Mejoramiento-A07.pdf).
