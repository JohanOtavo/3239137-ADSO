# ADR-003 · Baja lógica del catálogo, sin borrado físico

- **Estado:** aceptada
- **Contexto:** un equipo se retira del catálogo cuando se vende, se pierde o se rompe. Pero ese equipo aparece en
  alquileres pasados, y esos alquileres no se borran nunca (RN-5). Si la fila desaparece, el histórico queda
  huérfano y el reporte miente.
- **Decisión:** `equipment.deleted_at`. Nula significa activo. Un equipo dado de baja deja de aparecer en el
  catálogo y en las búsquedas, pero sigue existiendo para las líneas de alquiler que lo referencian. Un equipo con
  alquileres abiertos **no se da de baja**: responde 409 `EQUIPMENT_HAS_OPEN_RENTALS` (CA-03.5).
- **Alternativas descartadas:** borrado físico con `ON DELETE SET NULL` en la línea, que pierde qué se alquiló;
  una tabla de archivo aparte, que duplica el modelo y obliga a consultar en dos sitios; una columna `active`
  booleana, que no dice cuándo se retiró.
- **Consecuencias:** toda consulta del catálogo lleva `WHERE deleted_at IS NULL`, y el índice único del
  `model_code` es **parcial** sobre esa misma condición: retirar un equipo libera su código para uno nuevo. La FK
  `rental_item.equipment_id` es RESTRICT, como barrera de última instancia contra un borrado manual.
- **Cómo se verifica:** una prueba da de baja un equipo y comprueba que desaparece del catálogo, que
  `GET /equipment/{id}` responde 404, y que la línea de alquiler que lo referencia sigue devolviendo sus datos.
