# Trazabilidad y validación del modelo conceptual

Evidencia **GA3-220501096-AA1-EV01**. Ficha ADSO 3239137.

## 1. Matriz de trazabilidad: funcionalidad del diseño - entidades del modelo

Las 42 funcionalidades definidas en `02_posibles_funcionalidades_sistema.md` están soportadas por las 46 entidades del modelo conceptual.

| Módulo | Funcionalidad del diseño | Entidades que la soportan |
|---|---|---|
| 1. Parametrización | Gestión de cliente | cliente |
| 1. Parametrización | Gestión de precio dinámico | precio, tipo_dia, tipo_habitacion |
| 1. Parametrización | Registro de empresa | empresa |
| 1. Parametrización | Gestión de información legal | informacion_legal |
| 1. Parametrización | Gestión de empleado | empleado, persona |
| 1. Parametrización | Gestión de tipo de día | tipo_dia |
| 1. Parametrización | Gestión de método de pago | metodo_pago |
| 2. Distribución | Gestión de sede | sede |
| 2. Distribución | Gestión de habitación | habitacion |
| 2. Distribución | Gestión de tipo de habitación | tipo_habitacion |
| 2. Distribución | Gestión de estado de habitación | estado_habitacion |
| 3. Prestación de servicio | Consulta de catálogo de habitación | catalogo_habitacion, habitacion, tipo_habitacion, precio |
| 3. Prestación de servicio | Consulta de disponibilidad de habitación | disponibilidad_habitacion, estado_habitacion, mantenimiento_habitacion |
| 3. Prestación de servicio | Gestión de reserva de habitación | reserva_habitacion, cliente, habitacion |
| 3. Prestación de servicio | Cancelación de reserva de habitación | cancelacion_habitacion |
| 3. Prestación de servicio | Check in | check_in, reserva_habitacion, empleado |
| 3. Prestación de servicio | Check out | check_out, estadia, empleado |
| 3. Prestación de servicio | Secuencia de estadía | estadia, venta_producto, venta_servicio |
| 3. Prestación de servicio | Venta de producto | venta_producto, producto |
| 3. Prestación de servicio | Venta de servicio | venta_servicio, servicio |
| 4. Facturación | Pre facturación | pre_factura, estadia, reserva_habitacion |
| 4. Facturación | Seguimiento de pago parcial | pago_parcial, metodo_pago, factura |
| 4. Facturación | Gestión de factura | factura, cliente, estadia |
| 4. Facturación | Detalle de compra | detalle_compra, producto, servicio |
| 5. Inventario | Gestión de producto | producto |
| 5. Inventario | Gestión de servicio | servicio |
| 5. Inventario | Gestión de proveedor | proveedor |
| 5. Inventario | Seguimiento de producto | seguimiento_producto |
| 5. Inventario | Consulta de disponibilidad | disponibilidad_inventario |
| 6. Notificación | Promoción | promocion |
| 6. Notificación | Alerta | alerta, cliente, reserva_habitacion |
| 6. Notificación | Término y condición | termino_condicion |
| 6. Notificación | Fidelización de cliente | fidelizacion_cliente |
| 7. Seguridad | Registro de persona | persona |
| 7. Seguridad | Registro de usuario | usuario |
| 7. Seguridad | Gestión de rol | rol, usuario_rol |
| 7. Seguridad | Gestión de permiso | permiso, rol_permiso |
| 7. Seguridad | Gestión de módulo | modulo |
| 7. Seguridad | Gestión de vista | vista, modulo_vista |
| 8. Mantenimiento | Mantenimiento de habitación por uso | mantenimiento_habitacion, mantenimiento_uso |
| 8. Mantenimiento | Mantenimiento por remodelación | mantenimiento_habitacion, mantenimiento_remodelacion |
| 8. Mantenimiento | Dashboard operativo | dashboard_mantenimiento, sede |

## 2. Autoevaluación con el instrumento Instrumento_EV01_ModeloConceptualBD

| # | Variable / indicador de logro | Cumple | Evidencia en este documento |
|---|---|---|---|
| 1 | El modelo conceptual identifica todas las entidades del sistema definidas en el modelo de datos. | Sí | Se representan las 46 entidades de los 8 módulos definidos en 03_estructura_modulo_entidad_sin_atributo.md, sin omisiones ni entidades nuevas no justificadas. |
| 2 | Los atributos de cada entidad están correctamente definidos con sus tipos de datos. | Sí | El diccionario de datos define tipo, obligatoriedad y descripción de cada atributo, más el identificador y los siete campos de auditoría comunes. |
| 3 | Se identifican las claves primarias para cada entidad. | Sí | Todas las entidades declaran PRIMARY KEY (id) con BIGINT UNSIGNED AUTO_INCREMENT; además se declaran las llaves candidatas como restricciones UNIQUE. |
| 4 | Las relaciones entre entidades están correctamente definidas con sus cardinalidades. | Sí | Se documentan 55 relaciones con su cardinalidad, participación y acción referencial, seis relaciones N:M resueltas con entidad asociativa y dos especializaciones EER. |
| 5 | El diagrama EER utiliza la notación estándar y fue elaborado con una herramienta CASE. | Sí | El diagrama se construye en MySQL Workbench mediante ingeniería inversa del script modelo_conceptual_hotel.sql, con notación de pata de gallo; se anexan además los diagramas por módulo y la versión Mermaid. |
| 6 | El modelo conceptual es coherente con la especificación de requisitos del proyecto. | Sí | La matriz de trazabilidad relaciona las 42 funcionalidades del documento de diseño con las entidades que las soportan; no quedan funcionalidades sin respaldo ni entidades sin uso. |
| 7 | Presenta el informe cumpliendo con las normas básicas de presentación de documentos. | Sí | El informe incluye portada institucional, tabla de contenido, numeración de secciones, figuras y tablas rotuladas, conclusiones y referencias bibliográficas. |

## 3. Decisiones de diseño

| # | Decisión | Definición adoptada | Justificación |
|---|---|---|---|
| 1 | Identificador subrogado único | Todas las entidades usan id BIGINT UNSIGNED AUTO_INCREMENT como llave primaria. | El modelo de datos del diseño dejó abierto el tipo de identificador. Se elige un entero autoincremental por su menor tamaño de índice, su rendimiento en uniones y su compatibilidad directa con las llaves foráneas. El uso de UUID (CHAR(36) o BINARY(16)) queda documentado como alternativa para un escenario de integración entre sedes o sincronización sin conexión. |
| 2 | Auditoría y eliminación lógica en todas las entidades | Las 46 entidades incluyen created_by, created_at, updated_by, updated_at, deleted_by, deleted_at y status. | Se conserva el criterio definido en 05_id_y_auditoria.md: la trazabilidad de quién hizo qué y cuándo es obligatoria en reserva, factura, pago, mantenimiento, inventario y seguridad. La eliminación lógica evita perder historial contable y operativo. |
| 3 | La auditoría no se dibuja en el diagrama EER | created_by, updated_by y deleted_by referencian usuario.id; la relación se documenta y se implementa en el modelo físico (AA2). | Dibujarla generaría tres aristas por cada entidad, es decir 138 líneas adicionales, y haría ilegible el diagrama sin aportar información nueva, porque la regla es idéntica en todas las entidades. |
| 4 | cliente y persona se mantienen separadas | cliente conserva sus propios datos de identificación; persona es la base de identidad de empleado y usuario. | cliente es un actor comercial externo que puede ser persona natural o jurídica (por eso admite NIT), mientras que persona sostiene la identidad de quienes operan el sistema. Se documenta la alternativa de convertir cliente en subtipo de persona; queda como pendiente por confirmar con el instructor, porque implicaría manejar la coincidencia entre un empleado y un cliente. |
| 5 | Especialización del mantenimiento | mantenimiento_habitacion es el supertipo; mantenimiento_uso y mantenimiento_remodelacion son subtipos con llave foránea única y discriminador tipo_mantenimiento. | Es el constructo de especialización propio del modelo EER. La especialización es disyunta y total: toda orden es de uso o de remodelación, nunca de las dos, lo que se controla con la restricción CHECK tipo_mantenimiento IN ('USO','REMODELACION'). |
| 6 | Relaciones uno a uno con llave foránea única | estadia, check_in, check_out, cancelacion_habitacion, catalogo_habitacion, fidelizacion_cliente, empleado y usuario declaran UNIQUE sobre su llave foránea. | Es la forma estándar de representar una cardinalidad 1:1 en un modelo relacional y garantiza, por ejemplo, que una reserva no pueda tener dos check in o que una estadía no se facture dos veces. |
| 7 | Relaciones muchos a muchos con entidad asociativa | venta_producto, venta_servicio, detalle_compra, usuario_rol, rol_permiso y modulo_vista resuelven las seis relaciones N:M del modelo. | El modelo relacional no admite relaciones N:M directas. Las entidades asociativas además permiten guardar atributos propios de la relación, como cantidad, valor_unitario y valor_total en las ventas. |
| 8 | Llaves foráneas excluyentes para producto o servicio | detalle_compra y disponibilidad_inventario tienen producto_id y servicio_id opcionales con un CHECK que obliga a diligenciar exactamente uno de los dos. | Conserva los atributos definidos en el modelo de datos del diseño y a la vez impide filas ambiguas (ninguno o ambos). La alternativa de crear una entidad genérica ítem se documenta para evaluarla en el modelo físico. |
| 9 | Se conserva modulo_vista pese a vista.modulo_id | vista.modulo_id indica el módulo propietario de la vista; modulo_vista permite exponer una misma vista en varios módulos. | Las dos estructuras estaban en el modelo de datos del diseño. Se documenta la redundancia parcial y su significado distinto para que, si el negocio confirma que una vista pertenece a un solo módulo, modulo_vista pueda eliminarse en el modelo físico. |
| 10 | estado_habitacion es entidad; los demás estados son atributos de dominio | estado_habitacion se modela como entidad con permite_reserva y permite_check_in; estado_reserva, estado_estadia, estado_factura y estado_mantenimiento son atributos controlados por CHECK. | El estado de la habitación gobierna reglas de negocio (si permite o no reservar y hacer check in), por lo que necesita atributos propios. Los demás estados todavía están pendientes de catálogo oficial, así que se dejan como dominio controlado y podrán promoverse a entidad cuando el negocio los confirme. |
| 11 | Tipos de dato monetarios exactos | Todos los valores monetarios usan DECIMAL(12,2) y las cantidades usan INT. | DECIMAL evita los errores de redondeo de los tipos de punto flotante, inaceptables en facturación, y 12 dígitos cubren con holgura los valores del negocio hotelero. |
| 12 | Política de integridad referencial diferenciada | ON DELETE RESTRICT para entidades maestras, CASCADE para entidades dependientes y SET NULL para referencias opcionales. | Impide borrar un cliente, una habitación o un producto que ya tiene movimiento; permite eliminar en bloque los registros que no existen sin su padre (detalles de una factura, vistas de un módulo) y conserva el registro cuando la referencia es informativa, como el empleado responsable de un mantenimiento. |
| 13 | El valor de la reserva se almacena | reserva_habitacion.valor_estimado, y los valores unitarios de las ventas, se guardan aunque sean derivables de precio. | El precio dinámico cambia con la temporada y las reglas comerciales. Guardar el valor aplicado conserva el histórico y evita que una liquidación antigua cambie cuando se actualiza una tarifa. |
| 14 | dashboard_mantenimiento se conserva como consolidado por fecha de corte | Es un registro histórico por sede y fecha, no un cálculo en línea. | Permite comparar la ocupación entre cortes sin recalcular sobre datos que ya cambiaron. Si el negocio solo requiere la foto actual, en el modelo físico puede reemplazarse por una vista SQL. |
| 15 | Codificación y motor de almacenamiento | Esquema en utf8mb4 con collation utf8mb4_unicode_ci y tablas InnoDB. | utf8mb4 soporta tildes, ñ y emojis en descripciones y mensajes de notificación; InnoDB es el único motor de MySQL que aplica integridad referencial, indispensable para las 55 llaves foráneas del modelo. |

## 4. Pendientes por confirmar

| Pendiente por confirmar | Tratamiento en el modelo conceptual |
|---|---|
| Valores oficiales de status y de los estados de reserva, estadía, factura, pago y mantenimiento. | Se implementan como dominio controlado por CHECK; si el negocio define catálogos, se promueven a entidad en el modelo físico. |
| Reglas exactas de cálculo del precio dinámico. | El modelo soporta la vigencia por tipo de habitación y tipo de día; la fórmula se resolverá en la lógica de la aplicación. |
| Política de cancelación, devolución y penalidad. | cancelacion_habitacion ya prevé aplica_penalidad y valor_penalidad; falta la regla de cálculo. |
| Definir si cliente debe ser subtipo de persona. | Afecta la unificación de identidades cuando un empleado también es cliente. |
| Confirmar si los campos de auditoría referencian usuario.id. | El modelo lo asume; se implementará como llave foránea opcional en el modelo físico. |
| Canales de notificación y tipos de servicio que venderá el hotel. | Hoy son atributos de dominio (canal) que podrán convertirse en catálogos. |
