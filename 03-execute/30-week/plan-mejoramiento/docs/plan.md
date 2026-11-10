# Plan técnico — film-gear

Cómo se construye lo que dicen [`spec.md`](spec.md) y [`constitution.md`](constitution.md). El esquema **no** se
describe aquí: vive en [`data-model.md`](data-model.md). La forma del sistema, en
[`architecture.md`](architecture.md).

## 1. Decisiones técnicas

| # | Decisión | Elección | Por qué |
|---|---|---|---|
| D-01 | Lenguaje y runtime | PHP 8.3 | Lo exige el acta: backend en PHP con Laravel |
| D-02 | Framework HTTP | Laravel 12 | Contrato en [`api-contract.md`](api-contract.md) |
| D-03 | Persistencia | Constructor de consultas de Laravel + migraciones, sobre PostgreSQL 16 | El esquema es de las migraciones ([ADR-001](adr/adr-001-propiedad-del-esquema.md)) |
| D-04 | Concurrencia | Escritura condicionada sobre `available_units` ([ADR-002](adr/adr-002-concurrencia-optimista.md)) | Última unidad (RNF-2) |
| D-05 | Fechas | Instantes UTC (`timestamptz`); el reloj es un puerto (`Clock`) | Probar vencimientos sin esperar 4 días |
| D-06 | Reporte | Una consulta agregada por un puerto de lectura ([ADR-004](adr/adr-004-reporte-agregado-y-congelado.md)) | Estable y barato |
| D-07 | Objetos de valor | `ModelCode`, `Quantity`, `DocumentNumber`, `DateRange`: viven dentro de la fila de su dueño, sin tabla | No tienen identidad |
| D-08 | Fotos | Binario en almacenamiento externo (disco en local); la base guarda solo `photo_key` | Clave opaca, nunca ruta |
| D-09 | Contraseñas | Argon2id detrás del puerto `PasswordHasher`; el dominio nunca ve la clave en claro | Artículo IX |
| D-10 | Arranque | La aplicación crea el admin inicial desde `ADMIN_USERNAME` / `ADMIN_PASSWORD` | La base no puede producir el hash |
| D-11 | Autenticación | JWT HS256, 8 horas, clave en `JWT_SECRET` | Simple y sin estado |
| D-12 | Pruebas | PHPUnit, con dobles para los puertos y una base real solo en pruebas de integración | Pirámide de pruebas |
| D-13 | Frontend | React con Vite, sin librería de componentes | Siete pantallas de tablas y formularios no justifican un sistema de diseño |
| D-14 | Idioma | `tool/check-language.mjs` ejecutado en CI | El artículo XI necesita una verificación, no una promesa |

**Sin modelos de Eloquent.** Un modelo por tabla sería un segundo dominio en paralelo, con sus propias reglas y
su propia idea de qué es un equipo. El dominio ya existe en `api/app/Domain/`; los repositorios solo traducen
filas a esos objetos y al revés.

## 2. Configuración por entorno

| Variable | Obligatoria | Ejemplo |
|---|---|---|
| `DATABASE_URL` o `DB_*` | sí | `pgsql://filmgear@db:5432/filmgear` |
| `DB_SCHEMA` | sí | `filmgear` |
| `JWT_SECRET` | sí | cadena larga aleatoria |
| `ADMIN_USERNAME` / `ADMIN_PASSWORD` | sí | definidos en `.env`, nunca en el repositorio |
| `PHOTOS_DIR` | no | `storage/app/photos` |
| `CORS_ORIGIN` | sí | `http://localhost:5173` |

Un secreto ausente se nota al levantar, no en la primera petición: el arranque falla si falta `JWT_SECRET`.

## 3. Estrategia de pruebas

| Nivel | Qué prueba | Contra qué |
|---|---|---|
| Dominio | Invariantes: máximo 2, vencimiento a 4 días, unidades en rango | Nada: objetos puros |
| Aplicación | Casos de uso: alquilar, devolver, reporte | Dobles de los puertos |
| Integración | Repositorios y consulta del reporte | PostgreSQL real |
| Contrato | Cuerpo de error único y códigos de estado | La API levantada |
| Arquitectura | Artículos I, II y III | Los propios archivos |
| Concurrencia | Dos procesos por la última unidad | PostgreSQL real |
| Carga | Búsqueda con 5 000 equipos bajo 300 ms | PostgreSQL real |
| Idioma | Artículo XI | Los propios archivos |

El acta pide **15 o más archivos de prueba**. Esa cuenta se lleva en [`tasks.md`](tasks.md), no aquí.

## 4. Orden de construcción

Esqueleto y salud → migración inicial con semilla → dominio → casos de uso con dobles → adaptadores → API →
pruebas de contrato, concurrencia y carga → frontend → sitio público.

El detalle, con su evidencia, está en [`tasks.md`](tasks.md).
