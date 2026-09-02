# Diccionario de datos - Modelo conceptual del sistema de hotelería

Evidencia **GA3-220501096-AA1-EV01**. Ficha ADSO 3239137.

Contiene **46 entidades** y **55 relaciones**. Todas las entidades incluyen el identificador `id` y los campos de auditoría descritos en la sección final.

## Convenciones

| Marca | Significado |
|---|---|
| PK | Llave primaria |
| FK | Llave foránea |
| UK | Restricción de unicidad |
| NN | No admite valores nulos |

## Módulo 1. Parametrización

### cliente

Persona natural o jurídica que reserva habitaciones y consume productos y servicios del hotel.

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| tipo_documento | VARCHAR(20) | NN |  | Tipo de documento de identidad (CC, CE, NIT, PAS). |
| numero_documento | VARCHAR(30) | NN |  | Número de documento; junto con el tipo identifica al cliente. |
| nombre | VARCHAR(100) | NN |  | Nombres del cliente. |
| apellido | VARCHAR(100) | NN |  | Apellidos del cliente. |
| telefono | VARCHAR(30) |  |  | Teléfono de contacto. |
| correo | VARCHAR(150) |  | UK | Correo electrónico de contacto. |
| direccion | VARCHAR(200) |  |  | Dirección de residencia o facturación. |

**Restricciones adicionales:** UNIQUE (tipo_documento, numero_documento).

### empleado

Persona vinculada laboralmente al hotel; atiende check in, check out y mantenimiento.

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| persona_id | BIGINT UNSIGNED | NN | FK,UK | Persona asociada al empleado (subtipo de persona). |
| cargo | VARCHAR(80) | NN |  | Cargo desempeñado. |
| fecha_ingreso | DATE | NN |  | Fecha de ingreso a la empresa. |
| telefono_laboral | VARCHAR(30) |  |  | Teléfono laboral. |
| correo_laboral | VARCHAR(150) |  | UK | Correo laboral. |

**Llaves foráneas:** `persona_id` &rarr; `persona.id` (1:1).

### empresa

Empresa hotelera propietaria de las sedes; raíz de la información corporativa y legal.

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| nombre | VARCHAR(150) | NN |  | Nombre comercial de la empresa. |
| nit | VARCHAR(20) | NN | UK | Número de identificación tributaria. |
| razon_social | VARCHAR(200) | NN |  | Razón social registrada. |
| telefono | VARCHAR(30) |  |  | Teléfono corporativo. |
| correo | VARCHAR(150) |  |  | Correo corporativo. |
| direccion | VARCHAR(200) |  |  | Dirección de la sede principal. |
| sitio_web | VARCHAR(150) |  |  | Sitio web institucional. |

### informacion_legal

Documentos legales asociados a la empresa (cámara de comercio, RUT, licencias, pólizas).

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| empresa_id | BIGINT UNSIGNED | NN | FK | Empresa propietaria del documento. |
| tipo_documento_legal | VARCHAR(50) | NN |  | Tipo de documento legal. |
| numero_documento_legal | VARCHAR(50) | NN |  | Número o radicado del documento. |
| descripcion | VARCHAR(255) |  |  | Descripción del documento. |
| fecha_expedicion | DATE | NN |  | Fecha de expedición. |
| fecha_vencimiento | DATE |  |  | Fecha de vencimiento (nula = sin vencimiento). |

**Restricciones adicionales:** UNIQUE (empresa_id, tipo_documento_legal, numero_documento_legal).

**Llaves foráneas:** `empresa_id` &rarr; `empresa.id` (1:N).

### metodo_pago

Medio de pago aceptado por el hotel (efectivo, tarjeta, transferencia).

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| nombre | VARCHAR(60) | NN | UK | Nombre del método de pago. |
| descripcion | VARCHAR(255) |  |  | Descripción del método de pago. |
| requiere_referencia | BOOLEAN | NN |  | Indica si exige número de referencia o autorización. |
| permite_pago_parcial | BOOLEAN | NN |  | Indica si admite abonos o pagos parciales. |

### precio

Regla de precio dinámico: valor de un tipo de habitación según el tipo de día y la vigencia.

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| tipo_habitacion_id | BIGINT UNSIGNED | NN | FK | Tipo de habitación al que aplica el precio. |
| tipo_dia_id | BIGINT UNSIGNED | NN | FK | Tipo de día (temporada, feriado, especial) al que aplica. |
| valor | DECIMAL(12,2) | NN |  | Valor de la tarifa. Debe ser mayor o igual a cero. |
| fecha_inicio | DATE | NN |  | Fecha desde la cual rige la tarifa. |
| fecha_fin | DATE |  |  | Fecha hasta la cual rige la tarifa (nula = vigente). |
| condicion | VARCHAR(255) |  |  | Condición comercial adicional aplicable. |

**Restricciones adicionales:** CHECK (valor >= 0); CHECK ((fecha_fin IS NULL OR fecha_fin >= fecha_inicio)).

**Llaves foráneas:** `tipo_habitacion_id` &rarr; `tipo_habitacion.id` (1:N); `tipo_dia_id` &rarr; `tipo_dia.id` (1:N).

### tipo_dia

Clasificación del día (ordinario, temporada, feriado, evento especial) que condiciona la tarifa.

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| nombre | VARCHAR(60) | NN | UK | Nombre del tipo de día. |
| descripcion | VARCHAR(255) |  |  | Descripción del tipo de día. |
| fecha | DATE |  |  | Fecha puntual cuando el tipo de día es específico. |
| aplica_temporada | BOOLEAN | NN |  | Indica si corresponde a temporada alta. |
| aplica_feriado | BOOLEAN | NN |  | Indica si corresponde a día feriado. |
| aplica_especial | BOOLEAN | NN |  | Indica si corresponde a evento especial. |

## Módulo 2. Distribución

### estado_habitacion

Estado operativo de la habitación (disponible, ocupada, reservada, limpieza, bloqueada, mantenimiento).

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| nombre | VARCHAR(40) | NN | UK | Nombre del estado. |
| descripcion | VARCHAR(255) |  |  | Descripción del estado. |
| permite_reserva | BOOLEAN | NN |  | Indica si en este estado la habitación puede reservarse. |
| permite_check_in | BOOLEAN | NN |  | Indica si en este estado se permite el check in. |

### habitacion

Unidad física alojable dentro de una sede; núcleo de la disponibilidad del negocio.

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| sede_id | BIGINT UNSIGNED | NN | FK | Sede donde se ubica la habitación. |
| tipo_habitacion_id | BIGINT UNSIGNED | NN | FK | Categoría comercial de la habitación. |
| estado_habitacion_id | BIGINT UNSIGNED | NN | FK | Estado operativo actual. |
| numero | VARCHAR(10) | NN |  | Número o identificador visible de la habitación. |
| piso | INT | NN |  | Piso en el que se encuentra. |
| capacidad | INT | NN |  | Capacidad real de huéspedes. |
| descripcion | VARCHAR(255) |  |  | Observaciones o dotación de la habitación. |

**Restricciones adicionales:** UNIQUE (sede_id, numero); CHECK (capacidad > 0).

**Llaves foráneas:** `sede_id` &rarr; `sede.id` (1:N); `tipo_habitacion_id` &rarr; `tipo_habitacion.id` (1:N); `estado_habitacion_id` &rarr; `estado_habitacion.id` (1:N).

### sede

Establecimiento físico de la empresa hotelera donde se ubican las habitaciones.

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| empresa_id | BIGINT UNSIGNED | NN | FK | Empresa a la que pertenece la sede. |
| nombre | VARCHAR(120) | NN |  | Nombre de la sede. |
| direccion | VARCHAR(200) | NN |  | Dirección de la sede. |
| ciudad | VARCHAR(80) | NN |  | Ciudad donde opera la sede. |
| telefono | VARCHAR(30) |  |  | Teléfono de la sede. |
| correo | VARCHAR(150) |  |  | Correo de la sede. |

**Restricciones adicionales:** UNIQUE (empresa_id, nombre).

**Llaves foráneas:** `empresa_id` &rarr; `empresa.id` (1:N).

### tipo_habitacion

Categoría comercial de habitación (sencilla, doble, suite) con su capacidad.

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| nombre | VARCHAR(60) | NN | UK | Nombre del tipo de habitación. |
| descripcion | VARCHAR(255) |  |  | Descripción de la categoría. |
| capacidad_base | INT | NN |  | Cantidad de huéspedes incluida en la tarifa base. |
| capacidad_maxima | INT | NN |  | Cantidad máxima de huéspedes permitida. |

**Restricciones adicionales:** CHECK (capacidad_base > 0); CHECK (capacidad_maxima >= capacidad_base).

## Módulo 3. Prestación de servicio

### cancelacion_habitacion

Registro de la anulación de una reserva, con motivo y penalidad aplicada.

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| reserva_habitacion_id | BIGINT UNSIGNED | NN | FK,UK | Reserva que se cancela. |
| motivo | VARCHAR(255) | NN |  | Motivo de la cancelación. |
| fecha_cancelacion | DATETIME | NN |  | Fecha y hora de la cancelación. |
| aplica_penalidad | BOOLEAN | NN |  | Indica si se cobra penalidad. |
| valor_penalidad | DECIMAL(12,2) |  |  | Valor de la penalidad aplicada. |

**Restricciones adicionales:** CHECK (valor_penalidad IS NULL OR valor_penalidad >= 0).

**Llaves foráneas:** `reserva_habitacion_id` &rarr; `reserva_habitacion.id` (1:1).

### catalogo_habitacion

Ficha comercial publicada de la habitación para consulta del cliente.

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| habitacion_id | BIGINT UNSIGNED | NN | FK,UK | Habitación publicada en el catálogo. |
| titulo | VARCHAR(120) | NN |  | Título comercial. |
| descripcion | TEXT |  |  | Descripción comercial extendida. |
| precio_base | DECIMAL(12,2) | NN |  | Precio base de referencia mostrado. |
| visible | BOOLEAN | NN |  | Indica si la ficha se muestra al público. |

**Restricciones adicionales:** CHECK (precio_base >= 0).

**Llaves foráneas:** `habitacion_id` &rarr; `habitacion.id` (1:1).

### check_in

Registro del ingreso efectivo del cliente a la habitación reservada.

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| reserva_habitacion_id | BIGINT UNSIGNED | NN | FK,UK | Reserva sobre la que se realiza el ingreso. |
| empleado_id | BIGINT UNSIGNED | NN | FK | Empleado que atiende el ingreso. |
| fecha_hora_ingreso | DATETIME | NN |  | Fecha y hora del ingreso. |
| observacion | VARCHAR(255) |  |  | Observaciones del ingreso. |

**Llaves foráneas:** `reserva_habitacion_id` &rarr; `reserva_habitacion.id` (1:1); `empleado_id` &rarr; `empleado.id` (1:N).

### check_out

Registro de la salida del cliente y liquidación de la estadía.

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| estadia_id | BIGINT UNSIGNED | NN | FK,UK | Estadía que se cierra. |
| empleado_id | BIGINT UNSIGNED | NN | FK | Empleado que atiende la salida. |
| fecha_hora_salida | DATETIME | NN |  | Fecha y hora de la salida. |
| observacion | VARCHAR(255) |  |  | Observaciones de la salida. |
| valor_total | DECIMAL(12,2) | NN |  | Valor total liquidado al cierre. |

**Restricciones adicionales:** CHECK (valor_total >= 0).

**Llaves foráneas:** `estadia_id` &rarr; `estadia.id` (1:1); `empleado_id` &rarr; `empleado.id` (1:N).

### disponibilidad_habitacion

Ventana de tiempo en la que una habitación está o no disponible para reservar.

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| habitacion_id | BIGINT UNSIGNED | NN | FK | Habitación evaluada. |
| fecha_inicio | DATE | NN |  | Inicio del periodo evaluado. |
| fecha_fin | DATE | NN |  | Fin del periodo evaluado. |
| disponible | BOOLEAN | NN |  | Indica si la habitación está disponible en el periodo. |
| motivo_no_disponible | VARCHAR(255) |  |  | Causa de la no disponibilidad. |

**Restricciones adicionales:** CHECK (fecha_fin >= fecha_inicio).

**Llaves foráneas:** `habitacion_id` &rarr; `habitacion.id` (1:N).

### estadia

Ocupación real de la habitación por el cliente; agrupa los consumos del periodo.

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| reserva_habitacion_id | BIGINT UNSIGNED | NN | FK,UK | Reserva que dio origen a la estadía. |
| cliente_id | BIGINT UNSIGNED | NN | FK | Cliente hospedado. |
| habitacion_id | BIGINT UNSIGNED | NN | FK | Habitación ocupada. |
| fecha_inicio | DATETIME | NN |  | Inicio real de la estadía. |
| fecha_fin | DATETIME |  |  | Fin real de la estadía (nulo = en curso). |
| estado_estadia | VARCHAR(30) | NN |  | Estado de la estadía (EN_CURSO, FINALIZADA). |

**Llaves foráneas:** `reserva_habitacion_id` &rarr; `reserva_habitacion.id` (1:1); `cliente_id` &rarr; `cliente.id` (1:N); `habitacion_id` &rarr; `habitacion.id` (1:N).

### reserva_habitacion

Solicitud de alojamiento de un cliente sobre una habitación en un rango de fechas.

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| cliente_id | BIGINT UNSIGNED | NN | FK | Cliente que realiza la reserva. |
| habitacion_id | BIGINT UNSIGNED | NN | FK | Habitación reservada. |
| fecha_inicio | DATE | NN |  | Fecha de inicio del alojamiento. |
| fecha_fin | DATE | NN |  | Fecha de finalización del alojamiento. |
| cantidad_persona | INT | NN |  | Número de huéspedes. |
| estado_reserva | VARCHAR(30) | NN |  | Estado de la reserva (PENDIENTE, CONFIRMADA, CANCELADA, CUMPLIDA). |
| valor_estimado | DECIMAL(12,2) |  |  | Valor estimado calculado con la regla de precio. |

**Restricciones adicionales:** CHECK (fecha_fin >= fecha_inicio); CHECK (cantidad_persona > 0).

**Llaves foráneas:** `cliente_id` &rarr; `cliente.id` (1:N); `habitacion_id` &rarr; `habitacion.id` (1:N).

### venta_producto

Consumo de un producto de inventario durante una estadía (entidad asociativa estadía-producto).

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| estadia_id | BIGINT UNSIGNED | NN | FK | Estadía en la que se realiza el consumo. |
| producto_id | BIGINT UNSIGNED | NN | FK | Producto consumido. |
| cantidad | INT | NN |  | Cantidad vendida. |
| valor_unitario | DECIMAL(12,2) | NN |  | Valor unitario aplicado. |
| valor_total | DECIMAL(12,2) | NN |  | Valor total de la línea de venta. |

**Restricciones adicionales:** CHECK (cantidad > 0); CHECK (valor_unitario >= 0); CHECK (valor_total >= 0).

**Llaves foráneas:** `estadia_id` &rarr; `estadia.id` (1:N); `producto_id` &rarr; `producto.id` (1:N).

### venta_servicio

Consumo de un servicio del hotel durante una estadía (entidad asociativa estadía-servicio).

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| estadia_id | BIGINT UNSIGNED | NN | FK | Estadía en la que se presta el servicio. |
| servicio_id | BIGINT UNSIGNED | NN | FK | Servicio prestado. |
| cantidad | INT | NN |  | Cantidad vendida. |
| valor_unitario | DECIMAL(12,2) | NN |  | Valor unitario aplicado. |
| valor_total | DECIMAL(12,2) | NN |  | Valor total de la línea de venta. |

**Restricciones adicionales:** CHECK (cantidad > 0); CHECK (valor_unitario >= 0); CHECK (valor_total >= 0).

**Llaves foráneas:** `estadia_id` &rarr; `estadia.id` (1:N); `servicio_id` &rarr; `servicio.id` (1:N).

## Módulo 4. Facturación

### detalle_compra

Línea de la factura correspondiente a un producto o a un servicio.

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| factura_id | BIGINT UNSIGNED | NN | FK | Factura a la que pertenece la línea. |
| producto_id | BIGINT UNSIGNED |  | FK | Producto facturado (nulo si la línea es de servicio). |
| servicio_id | BIGINT UNSIGNED |  | FK | Servicio facturado (nulo si la línea es de producto). |
| descripcion | VARCHAR(255) | NN |  | Descripción del ítem facturado. |
| cantidad | INT | NN |  | Cantidad facturada. |
| valor_unitario | DECIMAL(12,2) | NN |  | Valor unitario. |
| valor_total | DECIMAL(12,2) | NN |  | Valor total de la línea. |

**Restricciones adicionales:** CHECK (cantidad > 0); CHECK (((producto_id IS NOT NULL AND servicio_id IS NULL) OR (producto_id IS NULL AND servicio_id IS NOT NULL))).

**Llaves foráneas:** `factura_id` &rarr; `factura.id` (1:N); `producto_id` &rarr; `producto.id` (1:N); `servicio_id` &rarr; `servicio.id` (1:N).

### factura

Documento definitivo de cobro emitido al cliente al cierre de la estadía.

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| cliente_id | BIGINT UNSIGNED | NN | FK | Cliente facturado. |
| estadia_id | BIGINT UNSIGNED | NN | FK,UK | Estadía facturada. |
| numero_factura | VARCHAR(30) | NN | UK | Número consecutivo de la factura. |
| fecha_emision | DATETIME | NN |  | Fecha y hora de emisión. |
| subtotal | DECIMAL(12,2) | NN |  | Subtotal antes de impuestos. |
| impuesto | DECIMAL(12,2) | NN |  | Valor de impuestos. |
| descuento | DECIMAL(12,2) | NN |  | Valor de descuentos. |
| total | DECIMAL(12,2) | NN |  | Valor total a pagar. |
| estado_factura | VARCHAR(30) | NN |  | Estado (EMITIDA, PAGADA, ANULADA). |

**Restricciones adicionales:** CHECK (subtotal >= 0); CHECK (total >= 0).

**Llaves foráneas:** `cliente_id` &rarr; `cliente.id` (1:N); `estadia_id` &rarr; `estadia.id` (1:1).

### pago_parcial

Abono o pago aplicado a una reserva o a una factura mediante un método de pago.

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| reserva_habitacion_id | BIGINT UNSIGNED |  | FK | Reserva a la que se abona (anticipos). |
| factura_id | BIGINT UNSIGNED |  | FK | Factura a la que se aplica el pago. |
| metodo_pago_id | BIGINT UNSIGNED | NN | FK | Método de pago utilizado. |
| valor | DECIMAL(12,2) | NN |  | Valor pagado. |
| fecha_pago | DATETIME | NN |  | Fecha y hora del pago. |
| referencia_pago | VARCHAR(60) |  |  | Referencia o autorización del pago. |

**Restricciones adicionales:** CHECK (valor > 0); CHECK ((reserva_habitacion_id IS NOT NULL OR factura_id IS NOT NULL)).

**Llaves foráneas:** `reserva_habitacion_id` &rarr; `reserva_habitacion.id` (1:N); `factura_id` &rarr; `factura.id` (1:N); `metodo_pago_id` &rarr; `metodo_pago.id` (1:N).

### pre_factura

Consolidado anticipado de valores de la estadía antes de emitir la factura definitiva.

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| estadia_id | BIGINT UNSIGNED | NN | FK | Estadía liquidada de forma preliminar. |
| reserva_habitacion_id | BIGINT UNSIGNED |  | FK | Reserva asociada a la pre factura. |
| cliente_id | BIGINT UNSIGNED | NN | FK | Cliente responsable del pago. |
| subtotal | DECIMAL(12,2) | NN |  | Suma de alojamiento y consumos antes de impuestos. |
| impuesto | DECIMAL(12,2) | NN |  | Valor de impuestos calculados. |
| descuento | DECIMAL(12,2) | NN |  | Valor de descuentos aplicados. |
| total | DECIMAL(12,2) | NN |  | Valor total preliminar. |

**Restricciones adicionales:** CHECK (subtotal >= 0); CHECK (total >= 0).

**Llaves foráneas:** `estadia_id` &rarr; `estadia.id` (1:N); `reserva_habitacion_id` &rarr; `reserva_habitacion.id` (1:N); `cliente_id` &rarr; `cliente.id` (1:N).

## Módulo 5. Inventario

### disponibilidad_inventario

Estado de disponibilidad de un producto o de un servicio para la venta.

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| producto_id | BIGINT UNSIGNED |  | FK | Producto evaluado (nulo si evalúa un servicio). |
| servicio_id | BIGINT UNSIGNED |  | FK | Servicio evaluado (nulo si evalúa un producto). |
| cantidad_disponible | INT | NN |  | Cantidad disponible para la venta. |
| disponible | BOOLEAN | NN |  | Indica si puede venderse. |
| observacion | VARCHAR(255) |  |  | Observación de la disponibilidad. |

**Restricciones adicionales:** CHECK (cantidad_disponible >= 0); CHECK (((producto_id IS NOT NULL AND servicio_id IS NULL) OR (producto_id IS NULL AND servicio_id IS NOT NULL))).

**Llaves foráneas:** `producto_id` &rarr; `producto.id` (1:N); `servicio_id` &rarr; `servicio.id` (1:N).

### producto

Bien físico con existencias que el hotel vende durante la estadía.

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| proveedor_id | BIGINT UNSIGNED |  | FK | Proveedor que abastece el producto. |
| nombre | VARCHAR(120) | NN |  | Nombre del producto. |
| descripcion | VARCHAR(255) |  |  | Descripción del producto. |
| valor_venta | DECIMAL(12,2) | NN |  | Precio de venta al público. |
| stock_actual | INT | NN |  | Existencia actual. |
| stock_minimo | INT | NN |  | Existencia mínima antes de reabastecer. |

**Restricciones adicionales:** CHECK (valor_venta >= 0); CHECK (stock_actual >= 0); CHECK (stock_minimo >= 0).

**Llaves foráneas:** `proveedor_id` &rarr; `proveedor.id` (1:N).

### proveedor

Tercero que abastece los productos comercializados por el hotel.

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| nombre | VARCHAR(150) | NN |  | Nombre del proveedor. |
| nit | VARCHAR(20) | NN | UK | Identificación tributaria del proveedor. |
| telefono | VARCHAR(30) |  |  | Teléfono de contacto. |
| correo | VARCHAR(150) |  |  | Correo de contacto. |
| direccion | VARCHAR(200) |  |  | Dirección del proveedor. |

### seguimiento_producto

Movimiento de inventario (entrada, salida, ajuste) sobre un producto.

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| producto_id | BIGINT UNSIGNED | NN | FK | Producto afectado por el movimiento. |
| tipo_movimiento | VARCHAR(30) | NN |  | Tipo de movimiento (ENTRADA, SALIDA, AJUSTE). |
| cantidad | INT | NN |  | Cantidad movida. |
| fecha_movimiento | DATETIME | NN |  | Fecha y hora del movimiento. |
| observacion | VARCHAR(255) |  |  | Observación del movimiento. |

**Restricciones adicionales:** CHECK (cantidad > 0).

**Llaves foráneas:** `producto_id` &rarr; `producto.id` (1:N).

### servicio

Prestación intangible ofrecida por el hotel (lavandería, spa, transporte).

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| nombre | VARCHAR(120) | NN | UK | Nombre del servicio. |
| descripcion | VARCHAR(255) |  |  | Descripción del servicio. |
| valor_venta | DECIMAL(12,2) | NN |  | Precio de venta del servicio. |
| disponible | BOOLEAN | NN |  | Indica si el servicio está habilitado. |

**Restricciones adicionales:** CHECK (valor_venta >= 0).

## Módulo 6. Notificación

### alerta

Mensaje operativo enviado a un cliente, opcionalmente asociado a una reserva.

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| cliente_id | BIGINT UNSIGNED | NN | FK | Cliente destinatario. |
| reserva_habitacion_id | BIGINT UNSIGNED |  | FK | Reserva que origina la alerta. |
| titulo | VARCHAR(120) | NN |  | Título de la alerta. |
| mensaje | TEXT | NN |  | Contenido del mensaje. |
| canal | VARCHAR(30) | NN |  | Canal de envío. |
| fecha_envio | DATETIME | NN |  | Fecha y hora de envío. |

**Llaves foráneas:** `cliente_id` &rarr; `cliente.id` (1:N); `reserva_habitacion_id` &rarr; `reserva_habitacion.id` (1:N).

### fidelizacion_cliente

Programa de puntos y nivel de fidelización asociado a un cliente.

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| cliente_id | BIGINT UNSIGNED | NN | FK,UK | Cliente inscrito en el programa. |
| nivel | VARCHAR(30) | NN |  | Nivel alcanzado (BRONCE, PLATA, ORO). |
| puntos | INT | NN |  | Puntos acumulados. |
| fecha_ultima_interaccion | DATE |  |  | Fecha de la última interacción registrada. |
| observacion | VARCHAR(255) |  |  | Observación del programa. |

**Restricciones adicionales:** CHECK (puntos >= 0).

**Llaves foráneas:** `cliente_id` &rarr; `cliente.id` (1:1).

### promocion

Campaña comercial difundida por un canal durante un periodo de vigencia.

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| titulo | VARCHAR(120) | NN |  | Título de la promoción. |
| descripcion | VARCHAR(255) |  |  | Descripción de la promoción. |
| fecha_inicio | DATE | NN |  | Inicio de vigencia. |
| fecha_fin | DATE | NN |  | Fin de vigencia. |
| canal | VARCHAR(30) | NN |  | Canal de difusión (CORREO, SMS, APP). |
| activa | BOOLEAN | NN |  | Indica si la promoción está activa. |

**Restricciones adicionales:** CHECK (fecha_fin >= fecha_inicio).

### termino_condicion

Versión de los términos y condiciones aceptados antes de comprar producto o servicio.

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| titulo | VARCHAR(120) | NN |  | Título del documento. |
| contenido | TEXT | NN |  | Texto completo de los términos. |
| version | VARCHAR(20) | NN |  | Versión del documento. |
| fecha_vigencia | DATE | NN |  | Fecha desde la cual rige la versión. |
| obligatorio | BOOLEAN | NN |  | Indica si su aceptación es obligatoria. |

**Restricciones adicionales:** UNIQUE (titulo, version).

## Módulo 7. Seguridad

### modulo

Agrupación funcional del sistema (parametrización, distribución, facturación).

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| nombre | VARCHAR(80) | NN | UK | Nombre del módulo. |
| descripcion | VARCHAR(255) |  |  | Descripción del módulo. |
| ruta_base | VARCHAR(120) |  |  | Ruta base del módulo en la aplicación. |

### modulo_vista

Vinculación de vistas reutilizables a módulos (resuelve la relación N:M módulo-vista).

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| modulo_id | BIGINT UNSIGNED | NN | FK | Módulo que expone la vista. |
| vista_id | BIGINT UNSIGNED | NN | FK | Vista expuesta. |

**Restricciones adicionales:** UNIQUE (modulo_id, vista_id).

**Llaves foráneas:** `modulo_id` &rarr; `modulo.id` (1:N); `vista_id` &rarr; `vista.id` (1:N).

### permiso

Autorización elemental sobre una acción del sistema.

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| nombre | VARCHAR(80) | NN | UK | Nombre del permiso. |
| descripcion | VARCHAR(255) |  |  | Descripción del permiso. |
| accion | VARCHAR(40) | NN |  | Acción autorizada (CREAR, LEER, ACTUALIZAR, ELIMINAR). |

### persona

Datos básicos de una persona natural; supertipo de empleado y titular de usuario.

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| tipo_documento | VARCHAR(20) | NN |  | Tipo de documento de identidad. |
| numero_documento | VARCHAR(30) | NN |  | Número de documento; junto con el tipo identifica a la persona. |
| nombre | VARCHAR(100) | NN |  | Nombres de la persona. |
| apellido | VARCHAR(100) | NN |  | Apellidos de la persona. |
| telefono | VARCHAR(30) |  |  | Teléfono de contacto. |
| correo | VARCHAR(150) |  |  | Correo de contacto. |

**Restricciones adicionales:** UNIQUE (tipo_documento, numero_documento).

### rol

Perfil funcional que agrupa permisos (recepción, administración, mantenimiento).

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| nombre | VARCHAR(60) | NN | UK | Nombre del rol. |
| descripcion | VARCHAR(255) |  |  | Descripción del rol. |

### rol_permiso

Asignación de permisos a roles (resuelve la relación N:M rol-permiso).

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| rol_id | BIGINT UNSIGNED | NN | FK | Rol al que se asigna el permiso. |
| permiso_id | BIGINT UNSIGNED | NN | FK | Permiso asignado. |

**Restricciones adicionales:** UNIQUE (rol_id, permiso_id).

**Llaves foráneas:** `rol_id` &rarr; `rol.id` (1:N); `permiso_id` &rarr; `permiso.id` (1:N).

### usuario

Credencial de acceso al sistema asociada a una persona.

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| persona_id | BIGINT UNSIGNED | NN | FK,UK | Persona titular de la cuenta. |
| username | VARCHAR(60) | NN | UK | Nombre de usuario. |
| password_hash | VARCHAR(255) | NN |  | Hash de la contraseña. |
| ultimo_acceso | DATETIME |  |  | Fecha y hora del último ingreso. |
| bloqueado | BOOLEAN | NN |  | Indica si la cuenta está bloqueada. |

**Llaves foráneas:** `persona_id` &rarr; `persona.id` (1:1).

### usuario_rol

Asignación de roles a usuarios (resuelve la relación N:M usuario-rol).

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| usuario_id | BIGINT UNSIGNED | NN | FK | Usuario al que se asigna el rol. |
| rol_id | BIGINT UNSIGNED | NN | FK | Rol asignado. |

**Restricciones adicionales:** UNIQUE (usuario_id, rol_id).

**Llaves foráneas:** `usuario_id` &rarr; `usuario.id` (1:N); `rol_id` &rarr; `rol.id` (1:N).

### vista

Pantalla o vista perteneciente a un módulo del sistema.

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| modulo_id | BIGINT UNSIGNED | NN | FK | Módulo propietario de la vista. |
| nombre | VARCHAR(80) | NN |  | Nombre de la vista. |
| descripcion | VARCHAR(255) |  |  | Descripción de la vista. |
| ruta | VARCHAR(150) | NN | UK | Ruta de la vista en la aplicación. |

**Llaves foráneas:** `modulo_id` &rarr; `modulo.id` (1:N).

## Módulo 8. Mantenimiento

### dashboard_mantenimiento

Consolidado por sede del estado de las habitaciones a una fecha de corte.

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| sede_id | BIGINT UNSIGNED | NN | FK | Sede consolidada. |
| total_habitacion | INT | NN |  | Total de habitaciones de la sede. |
| habitacion_disponible | INT | NN |  | Habitaciones disponibles. |
| habitacion_ocupada | INT | NN |  | Habitaciones ocupadas. |
| habitacion_mantenimiento | INT | NN |  | Habitaciones en mantenimiento. |
| fecha_corte | DATE | NN |  | Fecha de corte del consolidado. |

**Restricciones adicionales:** UNIQUE (sede_id, fecha_corte); CHECK (total_habitacion >= 0).

**Llaves foráneas:** `sede_id` &rarr; `sede.id` (1:N).

### mantenimiento_habitacion

Orden de mantenimiento sobre una habitación; supertipo de mantenimiento por uso y por remodelación.

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| habitacion_id | BIGINT UNSIGNED | NN | FK | Habitación intervenida. |
| empleado_id | BIGINT UNSIGNED |  | FK | Empleado responsable de la intervención. |
| tipo_mantenimiento | VARCHAR(30) | NN |  | Discriminador de especialización (USO, REMODELACION). |
| fecha_inicio | DATETIME | NN |  | Inicio del mantenimiento. |
| fecha_fin | DATETIME |  |  | Fin del mantenimiento (nulo = en curso). |
| estado_mantenimiento | VARCHAR(30) | NN |  | Estado (PROGRAMADO, EN_PROCESO, FINALIZADO). |
| observacion | VARCHAR(255) |  |  | Observación de la intervención. |

**Restricciones adicionales:** CHECK (tipo_mantenimiento IN ('USO','REMODELACION')).

**Llaves foráneas:** `habitacion_id` &rarr; `habitacion.id` (1:N); `empleado_id` &rarr; `empleado.id` (1:N).

### mantenimiento_remodelacion

Especialización de mantenimiento por remodelación o adecuación de la habitación.

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| mantenimiento_habitacion_id | BIGINT UNSIGNED | NN | FK,UK | Orden de mantenimiento especializada. |
| descripcion_remodelacion | TEXT | NN |  | Alcance de la remodelación. |
| presupuesto_estimado | DECIMAL(12,2) |  |  | Presupuesto estimado de la obra. |

**Restricciones adicionales:** CHECK (presupuesto_estimado IS NULL OR presupuesto_estimado >= 0).

**Llaves foráneas:** `mantenimiento_habitacion_id` &rarr; `mantenimiento_habitacion.id` (1:1).

### mantenimiento_uso

Especialización de mantenimiento generado por el uso normal de la habitación.

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NN | PK | Identificador único del registro (autoincremental). |
| mantenimiento_habitacion_id | BIGINT UNSIGNED | NN | FK,UK | Orden de mantenimiento especializada. |
| motivo_uso | VARCHAR(120) | NN |  | Motivo derivado del uso. |
| detalle_actividad | TEXT |  |  | Detalle de las actividades ejecutadas. |

**Llaves foráneas:** `mantenimiento_habitacion_id` &rarr; `mantenimiento_habitacion.id` (1:1).

## Campos comunes de auditoría

Presentes en las 46 entidades del modelo.

| Atributo | Tipo de dato | Nulo | Clave | Descripción |
|---|---|---|---|---|
| created_by | BIGINT UNSIGNED |  | FK | Usuario que creó el registro. |
| created_at | DATETIME | NN |  | Fecha y hora de creación. |
| updated_by | BIGINT UNSIGNED |  | FK | Último usuario que modificó el registro. |
| updated_at | DATETIME |  |  | Fecha y hora de la última modificación. |
| deleted_by | BIGINT UNSIGNED |  | FK | Usuario que realizó la eliminación lógica. |
| deleted_at | DATETIME |  |  | Fecha y hora de la eliminación lógica. |
| status | VARCHAR(30) | NN |  | Estado funcional del registro (ACTIVE / INACTIVE). |

> Se documenta en el modelo conceptual y se implementa como llave foránea opcional en el modelo físico (AA2). No se dibuja en el diagrama EER porque generaría 3 aristas por cada entidad (138 en total) y haría ilegible el diagrama.
