# Arquitectura — film-gear

La forma del sistema. Las decisiones están en [`plan.md`](plan.md); el esquema, en
[`data-model.md`](data-model.md).

## 1. Las capas y la dirección de las dependencias

```
        HTTP / consola
              │
    ┌─────────▼──────────┐
    │     Adapters       │  Laravel, PostgreSQL, disco, JWT
    └─────────┬──────────┘
              │ implementa
    ┌─────────▼──────────┐
    │    Application     │  Casos de uso + los puertos que declara
    └─────────┬──────────┘
              │ usa
    ┌─────────▼──────────┐
    │      Domain        │  Entidades, objetos de valor, reglas
    └────────────────────┘      no importa nada
```

Las flechas apuntan hacia adentro y **nunca** al revés.

| Carpeta | Qué vive ahí | Qué puede importar |
|---|---|---|
| `api/app/Domain/` | `Equipment`, `Rental`, `RentalItem`, `Producer`, `User`, objetos de valor | Solo PHP (artículo I) |
| `api/app/Application/` | Un caso de uso por archivo, y los puertos | Solo el dominio (artículo II) |
| `api/app/Adapters/` | Persistencia, seguridad, fotos, HTTP, consola | Lo que haga falta |
| `api/app/Providers/` | `PortBindingsProvider`: el único punto de composición | Todo (artículo III) |

`api/tests/Architecture/LayerDependenciesTest.php` lee los `use` de cada archivo y falla si alguien invierte una
flecha. Es el contrato escrito como prueba, porque PHP no tiene una herramienta equivalente a `import-linter`.

## 2. Los puertos

La aplicación declara lo que necesita del exterior y no sabe quién lo cumple:

| Puerto | Para qué | Adaptador |
|---|---|---|
| `Clock` | Saber qué hora es | Reloj del sistema, en UTC |
| `IdGenerator` | UUID v4 | `random_bytes` |
| `PasswordHasher` | Hash y verificación | Argon2id nativo |
| `TokenIssuer` | Emitir y verificar el token | JWT HS256 |
| `PhotoStorage` | Guardar y leer el binario | Disco local |
| `TransactionManager` | Agrupar escrituras en una operación | Transacción de PostgreSQL |
| `EquipmentRepository` | Catálogo | Constructor de consultas |
| `ProducerRepository` | Productores y sus conteos | Constructor de consultas |
| `RentalRepository` | Alquileres y sus líneas | Constructor de consultas |
| `UserRepository` | Usuarios | Constructor de consultas |
| `CategoryRepository` | Las cinco categorías | Constructor de consultas |
| `MostRequestedQuery` | El reporte, de un tirón | Una consulta agregada |

Cambiar el disco de fotos por S3 es escribir un adaptador nuevo y cambiar una línea en
`PortBindingsProvider`. Ni el dominio ni los casos de uso se enteran: eso es el OCP del artículo IV.
