# ADR-004 · Reporte agregado sobre valores congelados

- **Estado:** aceptada
- **Contexto:** el reporte de equipos más solicitados tiene que ser **estable**: un periodo cerrado no puede
  cambiar porque alguien renombre un equipo o mueva su categoría (CA-10.4). Si el reporte lee el catálogo vivo,
  el informe de noviembre cambia en diciembre.
- **Decisión:** la línea de alquiler **congela** `equipment_name`, `model_code` y `category_name` en el instante
  de la operación, y el reporte agrupa por esos valores, nunca por el catálogo. `rental_item.category_name` va
  **sin clave foránea a propósito**: no es una referencia, es una copia. El reporte se resuelve con una sola
  consulta agregada detrás del puerto `MostRequestedQuery`, no recorriendo entidades.
- **Alternativas descartadas:** leer el nombre actual del catálogo con un `JOIN`, que es justo lo que hace
  inestable el reporte; una tabla de reporte materializada, que hay que mantener y puede quedar desincronizada;
  versionar el catálogo con fechas de vigencia, que resuelve el mismo problema con diez veces más modelo.
- **Consecuencias:** hay duplicación deliberada entre `equipment` y `rental_item`, y es correcta: son dos hechos
  distintos, lo que el equipo es hoy y lo que era cuando se alquiló. El reporte no expone productores ni asesores
  (CA-10.3) porque agrega por equipo y no los consulta.
- **Cómo se verifica:** una prueba alquila un equipo, lo renombra después y comprueba que el reporte sigue
  devolviendo el nombre viejo.
