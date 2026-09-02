# Relaciones y cardinalidades del modelo conceptual

Evidencia **GA3-220501096-AA1-EV01**. Ficha ADSO 3239137.

La cardinalidad se expresa en notación mín..máx desde cada extremo. La entidad hija es la que contiene la llave foránea.

| # | Entidad padre | Entidad hija | Llave foránea | Cardinalidad | Participación de la hija | ON DELETE | Lectura de negocio |
|---|---|---|---|---|---|---|---|
| 1 | tipo_habitacion | precio | tipo_habitacion_id | 1 : 0..N | 1..1 | RESTRICT | Un tipo de habitación tiene muchas tarifas; cada tarifa aplica a un solo tipo de habitación. |
| 2 | tipo_dia | precio | tipo_dia_id | 1 : 0..N | 1..1 | RESTRICT | Un tipo de día condiciona muchas tarifas; cada tarifa aplica a un solo tipo de día. |
| 3 | empresa | informacion_legal | empresa_id | 1 : 0..N | 1..1 | CASCADE | Una empresa registra muchos documentos legales; cada documento pertenece a una sola empresa. |
| 4 | persona | empleado | persona_id | 1 : 0..1 | 1..1 | RESTRICT | Una persona puede ser a lo sumo un empleado; cada empleado corresponde a una sola persona. |
| 5 | empresa | sede | empresa_id | 1 : 0..N | 1..1 | RESTRICT | Una empresa opera muchas sedes; cada sede pertenece a una sola empresa. |
| 6 | sede | habitacion | sede_id | 1 : 0..N | 1..1 | RESTRICT | Una sede contiene muchas habitaciones; cada habitación pertenece a una sola sede. |
| 7 | tipo_habitacion | habitacion | tipo_habitacion_id | 1 : 0..N | 1..1 | RESTRICT | Un tipo de habitación clasifica muchas habitaciones; cada habitación tiene un solo tipo. |
| 8 | estado_habitacion | habitacion | estado_habitacion_id | 1 : 0..N | 1..1 | RESTRICT | Un estado aplica a muchas habitaciones; cada habitación tiene un solo estado vigente. |
| 9 | cliente | reserva_habitacion | cliente_id | 1 : 0..N | 1..1 | RESTRICT | Un cliente realiza muchas reservas; cada reserva pertenece a un solo cliente. |
| 10 | habitacion | reserva_habitacion | habitacion_id | 1 : 0..N | 1..1 | RESTRICT | Una habitación recibe muchas reservas en el tiempo; cada reserva es sobre una sola habitación. |
| 11 | reserva_habitacion | cancelacion_habitacion | reserva_habitacion_id | 1 : 0..1 | 1..1 | CASCADE | Una reserva puede tener a lo sumo una cancelación; cada cancelación anula una sola reserva. |
| 12 | habitacion | disponibilidad_habitacion | habitacion_id | 1 : 0..N | 1..1 | CASCADE | Una habitación tiene muchos periodos de disponibilidad; cada periodo es de una sola habitación. |
| 13 | habitacion | catalogo_habitacion | habitacion_id | 1 : 0..1 | 1..1 | CASCADE | Una habitación se publica en a lo sumo una ficha de catálogo; cada ficha describe una habitación. |
| 14 | reserva_habitacion | check_in | reserva_habitacion_id | 1 : 0..1 | 1..1 | RESTRICT | Una reserva genera a lo sumo un check in; cada check in corresponde a una sola reserva. |
| 15 | empleado | check_in | empleado_id | 1 : 0..N | 1..1 | RESTRICT | Un empleado atiende muchos check in; cada check in es atendido por un solo empleado. |
| 16 | reserva_habitacion | estadia | reserva_habitacion_id | 1 : 0..1 | 1..1 | RESTRICT | Una reserva origina a lo sumo una estadía; cada estadía proviene de una sola reserva. |
| 17 | cliente | estadia | cliente_id | 1 : 0..N | 1..1 | RESTRICT | Un cliente acumula muchas estadías; cada estadía corresponde a un solo cliente. |
| 18 | habitacion | estadia | habitacion_id | 1 : 0..N | 1..1 | RESTRICT | Una habitación aloja muchas estadías en el tiempo; cada estadía ocupa una sola habitación. |
| 19 | estadia | check_out | estadia_id | 1 : 0..1 | 1..1 | RESTRICT | Una estadía se cierra con a lo sumo un check out; cada check out cierra una sola estadía. |
| 20 | empleado | check_out | empleado_id | 1 : 0..N | 1..1 | RESTRICT | Un empleado atiende muchos check out; cada check out es atendido por un solo empleado. |
| 21 | estadia | venta_producto | estadia_id | 1 : 0..N | 1..1 | CASCADE | Una estadía registra muchas ventas de producto; cada venta pertenece a una sola estadía. |
| 22 | producto | venta_producto | producto_id | 1 : 0..N | 1..1 | RESTRICT | Un producto se vende en muchas ventas; cada venta de producto referencia un solo producto. |
| 23 | estadia | venta_servicio | estadia_id | 1 : 0..N | 1..1 | CASCADE | Una estadía registra muchas ventas de servicio; cada venta pertenece a una sola estadía. |
| 24 | servicio | venta_servicio | servicio_id | 1 : 0..N | 1..1 | RESTRICT | Un servicio se vende en muchas ventas; cada venta de servicio referencia un solo servicio. |
| 25 | estadia | pre_factura | estadia_id | 1 : 0..N | 1..1 | CASCADE | Una estadía puede tener varias pre facturas; cada pre factura liquida una sola estadía. |
| 26 | reserva_habitacion | pre_factura | reserva_habitacion_id | 1 : 0..N | 0..1 | RESTRICT | Una reserva puede aparecer en varias pre facturas; la pre factura referencia a lo sumo una reserva. |
| 27 | cliente | pre_factura | cliente_id | 1 : 0..N | 1..1 | RESTRICT | Un cliente recibe muchas pre facturas; cada pre factura es de un solo cliente. |
| 28 | cliente | factura | cliente_id | 1 : 0..N | 1..1 | RESTRICT | Un cliente recibe muchas facturas; cada factura se emite a un solo cliente. |
| 29 | estadia | factura | estadia_id | 1 : 0..1 | 1..1 | RESTRICT | Una estadía se factura a lo sumo una vez; cada factura corresponde a una sola estadía. |
| 30 | factura | detalle_compra | factura_id | 1 : 0..N | 1..1 | CASCADE | Una factura contiene muchos detalles; cada detalle pertenece a una sola factura. |
| 31 | producto | detalle_compra | producto_id | 1 : 0..N | 0..1 | RESTRICT | Un producto aparece en muchos detalles; el detalle referencia a lo sumo un producto. |
| 32 | servicio | detalle_compra | servicio_id | 1 : 0..N | 0..1 | RESTRICT | Un servicio aparece en muchos detalles; el detalle referencia a lo sumo un servicio. |
| 33 | reserva_habitacion | pago_parcial | reserva_habitacion_id | 1 : 0..N | 0..1 | RESTRICT | Una reserva recibe muchos abonos; cada abono se aplica a lo sumo a una reserva. |
| 34 | factura | pago_parcial | factura_id | 1 : 0..N | 0..1 | RESTRICT | Una factura recibe muchos pagos; cada pago se aplica a lo sumo a una factura. |
| 35 | metodo_pago | pago_parcial | metodo_pago_id | 1 : 0..N | 1..1 | RESTRICT | Un método de pago se usa en muchos pagos; cada pago usa un solo método de pago. |
| 36 | proveedor | producto | proveedor_id | 1 : 0..N | 0..1 | SET NULL | Un proveedor abastece muchos productos; cada producto tiene a lo sumo un proveedor. |
| 37 | producto | seguimiento_producto | producto_id | 1 : 0..N | 1..1 | CASCADE | Un producto registra muchos movimientos; cada movimiento afecta un solo producto. |
| 38 | producto | disponibilidad_inventario | producto_id | 1 : 0..N | 0..1 | CASCADE | Un producto tiene muchos registros de disponibilidad; el registro evalúa a lo sumo un producto. |
| 39 | servicio | disponibilidad_inventario | servicio_id | 1 : 0..N | 0..1 | CASCADE | Un servicio tiene muchos registros de disponibilidad; el registro evalúa a lo sumo un servicio. |
| 40 | cliente | alerta | cliente_id | 1 : 0..N | 1..1 | CASCADE | Un cliente recibe muchas alertas; cada alerta se dirige a un solo cliente. |
| 41 | reserva_habitacion | alerta | reserva_habitacion_id | 1 : 0..N | 0..1 | SET NULL | Una reserva puede generar muchas alertas; cada alerta se asocia a lo sumo a una reserva. |
| 42 | cliente | fidelizacion_cliente | cliente_id | 1 : 0..1 | 1..1 | CASCADE | Un cliente tiene a lo sumo un registro de fidelización; cada registro es de un solo cliente. |
| 43 | persona | usuario | persona_id | 1 : 0..1 | 1..1 | RESTRICT | Una persona tiene a lo sumo un usuario; cada usuario pertenece a una sola persona. |
| 44 | modulo | vista | modulo_id | 1 : 0..N | 1..1 | CASCADE | Un módulo agrupa muchas vistas; cada vista pertenece a un módulo propietario. |
| 45 | usuario | usuario_rol | usuario_id | 1 : 0..N | 1..1 | CASCADE | Un usuario puede tener muchos roles asignados. |
| 46 | rol | usuario_rol | rol_id | 1 : 0..N | 1..1 | CASCADE | Un rol puede estar asignado a muchos usuarios. |
| 47 | rol | rol_permiso | rol_id | 1 : 0..N | 1..1 | CASCADE | Un rol agrupa muchos permisos. |
| 48 | permiso | rol_permiso | permiso_id | 1 : 0..N | 1..1 | CASCADE | Un permiso puede pertenecer a muchos roles. |
| 49 | modulo | modulo_vista | modulo_id | 1 : 0..N | 1..1 | CASCADE | Un módulo puede exponer muchas vistas reutilizadas. |
| 50 | vista | modulo_vista | vista_id | 1 : 0..N | 1..1 | CASCADE | Una vista puede ser expuesta por muchos módulos. |
| 51 | habitacion | mantenimiento_habitacion | habitacion_id | 1 : 0..N | 1..1 | RESTRICT | Una habitación recibe muchos mantenimientos; cada mantenimiento interviene una sola habitación. |
| 52 | empleado | mantenimiento_habitacion | empleado_id | 1 : 0..N | 0..1 | SET NULL | Un empleado ejecuta muchos mantenimientos; cada mantenimiento tiene a lo sumo un responsable. |
| 53 | mantenimiento_habitacion | mantenimiento_uso | mantenimiento_habitacion_id | 1 : 0..1 | 1..1 | CASCADE | Un mantenimiento se especializa en a lo sumo un mantenimiento por uso. |
| 54 | mantenimiento_habitacion | mantenimiento_remodelacion | mantenimiento_habitacion_id | 1 : 0..1 | 1..1 | CASCADE | Un mantenimiento se especializa en a lo sumo un mantenimiento por remodelación. |
| 55 | sede | dashboard_mantenimiento | sede_id | 1 : 0..N | 1..1 | CASCADE | Una sede genera muchos consolidados; cada consolidado corresponde a una sola sede. |

## Relaciones muchos a muchos resueltas con entidad asociativa

| Entidad A | Entidad B | Entidad asociativa | Lectura |
|---|---|---|---|
| estadia | producto | venta_producto | Una estadía consume muchos productos y un producto se consume en muchas estadías. |
| estadia | servicio | venta_servicio | Una estadía consume muchos servicios y un servicio se presta en muchas estadías. |
| factura | producto / servicio | detalle_compra | Una factura detalla muchos productos o servicios y un producto o servicio aparece en muchas facturas. |
| usuario | rol | usuario_rol | Un usuario tiene muchos roles y un rol se asigna a muchos usuarios. |
| rol | permiso | rol_permiso | Un rol agrupa muchos permisos y un permiso pertenece a muchos roles. |
| modulo | vista | modulo_vista | Un módulo expone muchas vistas y una vista puede reutilizarse en muchos módulos. |

## Especializaciones (constructos del modelo EER)

### mantenimiento_habitacion

- **Subtipos:** mantenimiento_uso, mantenimiento_remodelacion
- **Discriminador:** tipo_mantenimiento
- **Restricción:** Disyunta y total
- **Nota:** Toda orden de mantenimiento es de uso o de remodelación, nunca de ambos tipos a la vez. El atributo tipo_mantenimiento actúa como discriminador y se controla con una restricción CHECK.

### persona

- **Subtipos:** empleado, usuario
- **Discriminador:** (por existencia del registro especializado)
- **Restricción:** Solapada y parcial
- **Nota:** Una persona puede ser empleado, tener usuario, ambas cosas o ninguna. Se implementa con llaves foráneas únicas (persona_id) en empleado y en usuario.

## Relación transversal de auditoría

- **Columnas:** created_by, updated_by, deleted_by
- **Entidad referenciada:** usuario
- **Cardinalidad:** 1:N
- **Lectura:** Un usuario crea, modifica o elimina lógicamente muchos registros de cualquier entidad; cada registro guarda a lo sumo un usuario por cada acción de auditoría.
- **Nota:** Se documenta en el modelo conceptual y se implementa como llave foránea opcional en el modelo físico (AA2). No se dibuja en el diagrama EER porque generaría 3 aristas por cada entidad (138 en total) y haría ilegible el diagrama.
