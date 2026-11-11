# ADR-001 · El esquema es propiedad de las migraciones

- **Estado:** aceptada
- **Contexto:** el esquema de la base puede nacer en dos sitios: en un script que el contenedor de PostgreSQL
  ejecuta al arrancar, o en las migraciones de la aplicación. Si nace en los dos, acaban discrepando y nadie sabe
  cuál manda.
- **Decisión:** `infra` levanta un motor **vacío**. Todo el DDL —las seis tablas, las claves foráneas, los
  índices, los tres `CHECK` y la semilla de las cinco categorías— vive en las migraciones de `api`.
- **Alternativas descartadas:** un `init.sql` montado en el contenedor, que deja el esquema fuera del control de
  versiones de la aplicación y obliga a recrear el volumen por cada cambio; un ORM que sincroniza el esquema solo,
  que hace imposible revisar en un PR qué cambió en la base.
- **Consecuencias:** levantar el sistema son dos pasos, no uno: arrancar el motor y migrar. El arranque del
  contenedor de la API los encadena. A cambio, el esquema se revisa como código y se puede revertir.
- **Cómo se verifica:** `grep -ri "create table" infra/` no devuelve nada. Con el volumen recién creado, la base
  no tiene tablas hasta que corren las migraciones.
