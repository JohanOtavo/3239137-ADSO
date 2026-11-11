# ADR-002 · Concurrencia optimista sobre las unidades

- **Estado:** aceptada
- **Contexto:** dos asesores pueden entregar la **última unidad** del mismo equipo al mismo tiempo (RNF-2). Si
  ambos leen `available_units = 1` y ambos escriben, el sistema alquila una unidad que no tiene.
- **Decisión:** control de concurrencia **optimista**, usando `available_units` como testigo. La escritura lleva
  la condición del valor leído:

  ```sql
  UPDATE filmgear.equipment
     SET available_units = :nuevo
   WHERE id = :id
     AND available_units = :lo_que_se_leyo
     AND deleted_at IS NULL;
  ```

  Cero filas afectadas significa que otro llegó primero, y el servidor responde **409
  `EQUIPMENT_NOT_AVAILABLE`**. La **última barrera** es el `CHECK ck_equipment_available_range`.
- **Alternativas descartadas:** una columna `version` aparte, que `data-model.md` no declara y que no hace falta
  porque el propio contador sirve de testigo; bloqueo pesimista (`SELECT … FOR UPDATE`) en cada alquiler, que
  serializa todo el catálogo por un caso raro; un contador en memoria, que no sobrevive a más de un proceso.
- **Consecuencias:** si el `CHECK` salta, **algo escribió fuera del adaptador**: es un defecto, no un caso de
  negocio. Devolver dos veces la misma línea no suma dos veces porque `returned_at` ya está lleno (CA-07.2).
- **Cómo se verifica:** `tests/Integration/ConcurrencyTest` lanza dos procesos simultáneos sobre un equipo con una
  unidad: exactamente uno recibe 201.
