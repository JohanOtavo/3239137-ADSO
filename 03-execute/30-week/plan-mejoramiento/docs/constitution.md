# Constitución — film-gear

Principios innegociables del proyecto. No son estilo ni preferencia: cada artículo trae **cómo se verifica**, porque un
principio que nadie puede comprobar es una intención. Un cambio que viole un artículo se rechaza sin discutir el resto.

---

## Artículo I — El dominio no depende de nada

`api/app/Domain/` no importa ningún otro módulo del proyecto ni librerías de terceros: solo PHP y su biblioteca estándar
(`final readonly class`, `enum`, `DateTimeImmutable`). Ni Eloquent, ni facades, ni `Illuminate\*`.

**Por qué:** es lo único que permite probar las invariantes sin base de datos, sin servidor y sin contenedor, y cambiar de
ORM sin tocar reglas.
**Verificación:** `api/tests/Architecture/LayerDependenciesTest.php` lee los `use` de cada archivo del dominio y falla si
aparece uno prohibido.

## Artículo II — El núcleo declara sus puertos; el mundo los implementa

La capa de aplicación solo conoce el dominio. Todo lo que necesita del exterior —repositorios, reloj, hash de claves,
almacenamiento de fotos, emisión de tokens— lo expresa como un **puerto que ella misma define**. Ningún tipo de framework
ni de ORM aparece en la firma de un puerto.

**Verificación:** el mismo test de arquitectura impide que `app/Application/` importe `app/Adapters/`. Las pruebas de
aplicación corren con dobles y sin referenciar adaptadores.

## Artículo III — Un único punto de composición

Un solo archivo, `api/app/Providers/PortBindingsProvider.php`, es donde un puerto se encuentra con su adaptador. Fuera de
él no se instancia infraestructura.
**Verificación:** el test de arquitectura busca `new <Adaptador>(` fuera de `app/Providers/` y falla si lo encuentra.

## Artículo IV — SOLID no es decorativo

| Principio | Aplicación concreta | Verificación |
|---|---|---|
| **SRP** | Un servicio de aplicación = un caso de uso. `RentEquipmentService` alquila y no hace nada más | Un servicio con dos razones para cambiar se parte |
| **OCP** | Cambiar el almacenamiento de fotos (disco → S3) no toca dominio ni aplicación | El adaptador nuevo solo añade una línea en la composición |
| **LSP** | Los dobles de prueba sustituyen al adaptador real sin cambiar expectativas | El mismo test pasa contra el doble y contra el real |
| **ISP** | Puertos pequeños: `Clock`, `PasswordHasher`, `TokenIssuer`, `PhotoStorage` | Ningún implementador deja métodos sin usar |
| **DIP** | El puerto lo define quien lo consume | Los puertos viven en el paquete de aplicación |

## Artículo V — El esquema es propiedad de las migraciones

`infra/` levanta un motor **vacío**. Todo el DDL —tablas, índices, restricciones y semilla— vive en las migraciones de la
aplicación. Ver [ADR-001](adr/adr-001-propiedad-del-esquema.md).
**Verificación:** `grep -ri "create table" infra/` no devuelve nada.

## Artículo VI — Las invariantes que la base puede imponer, la base las impone

Una regla que solo vive en código protege a la aplicación, no a los datos. Toda invariante expresable en el motor
(`CHECK`, `UNIQUE`, FK) se declara ahí. Lo que no está aplicado se marca **pendiente** en
[`data-model.md`](data-model.md); no se promete.
**Verificación:** las consultas de `data-model.md` §10 coinciden con lo que declara el documento.

## Artículo VII — Lo derivado se calcula, no se almacena

El estado de un alquiler (`open`, `overdue`, `closed`) y los días de retraso se calculan. No hay columna para ellos.
**Verificación:** `data-model.md` no lista ninguna columna `status` ni `days_late`.

## Artículo VIII — Los valores congelados no siguen al catálogo

La línea del alquiler guarda nombre, código de modelo y categoría del instante de la operación. Ningún reporte los lee del
catálogo vivo.
**Verificación:** una prueba renombra un equipo después de alquilarlo y comprueba que el reporte conserva el nombre viejo
([ADR-004](adr/adr-004-reporte-agregado-y-congelado.md)).

## Artículo IX — Ningún secreto entra al repositorio ni a un log

Ni contraseñas, ni hashes precalculados, ni la clave del token. Todo secreto viene del entorno. El `.env` real está en
`.gitignore`; solo se versiona `.env.example`.
**Verificación:** un `grep` de patrones en CI; ninguna respuesta HTTP incluye `password` ni `password_hash`.

## Artículo X — Gana el motor

Si el documento de datos y la base discrepan, el documento está roto y se corrige el documento.

## Artículo XI — Idioma

**El código va en inglés. Solo el texto que ve el usuario va en español.**

| Qué | Idioma |
|---|---|
| Clases, métodos, variables, constantes | Inglés |
| Nombres de archivo y de carpeta | Inglés |
| Tablas y columnas | Inglés |
| Rutas, tanto de la API como del frontend | Inglés |
| Nombres de las pruebas | Inglés |
| Comentarios y docblocks | **Inglés** |
| Mensajes de error y rótulos de pantalla | Español |
| Esta documentación | Español |

**Por qué:** un proyecto con identificadores en dos idiomas obliga a traducir mentalmente en cada lectura, y los
comentarios son código: viven junto a lo que explican y se leen con él.
**Verificación:** el workflow `.github/workflows/language-guard.yml` recorre los archivos de código, cuenta identificadores
y comentarios contra una lista de términos en español y **falla el build** si encuentra alguno.

## Artículo XII — Nada se da por hecho sin evidencia

Una tarea de [`tasks.md`](tasks.md) está terminada cuando su **evidencia** existe y se puede ejecutar, no cuando «ya está
programada». Un documento que declare terminado algo sin evidencia ejecutable está incumpliendo este artículo.
