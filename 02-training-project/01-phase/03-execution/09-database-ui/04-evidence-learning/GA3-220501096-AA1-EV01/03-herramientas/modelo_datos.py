# -*- coding: utf-8 -*-
"""
GA3-220501096-AA1-EV01 - Modelo conceptual de la base de datos
Sistema de hoteleria - Ficha ADSO 3239137

Fuente unica de verdad del modelo conceptual. A partir de este archivo se generan:
  - el script SQL para ingenieria inversa en la herramienta CASE (MySQL Workbench),
  - el diccionario de datos,
  - los diagramas EER (Mermaid y PNG),
  - el informe formal en formato .docx.

Insumo: 03-execute/10-week/01-session/sistema-hotelero/docs/architecture/model-data/es/
        (archivos 03, 04 y 05: entidades, atributos y campos de auditoria).
"""

BD_NOMBRE = "sistema_hotelero"

# --------------------------------------------------------------------------
# Campos comunes: identificador y auditoria (archivo 05_id_y_auditoria.md)
# --------------------------------------------------------------------------
CAMPO_ID = ("id", "BIGINT UNSIGNED", False, "PK", "Identificador único del registro (autoincremental).")

CAMPOS_AUDITORIA = [
    ("created_by", "BIGINT UNSIGNED", True,  "FK", "Usuario que creó el registro."),
    ("created_at", "DATETIME",        False, "",   "Fecha y hora de creación."),
    ("updated_by", "BIGINT UNSIGNED", True,  "FK", "Último usuario que modificó el registro."),
    ("updated_at", "DATETIME",        True,  "",   "Fecha y hora de la última modificación."),
    ("deleted_by", "BIGINT UNSIGNED", True,  "FK", "Usuario que realizó la eliminación lógica."),
    ("deleted_at", "DATETIME",        True,  "",   "Fecha y hora de la eliminación lógica."),
    ("status",     "VARCHAR(30)",     False, "",   "Estado funcional del registro (ACTIVE / INACTIVE)."),
]

# --------------------------------------------------------------------------
# Modulos (color usado en los diagramas)
# --------------------------------------------------------------------------
MODULOS = [
    ("parametrizacion", "1. Parametrización", "#1f6feb"),
    ("distribucion",    "2. Distribución",    "#2f8f46"),
    ("prestacion",      "3. Prestación de servicio", "#b5651d"),
    ("facturacion",     "4. Facturación",     "#8250df"),
    ("inventario",      "5. Inventario",      "#0f7b8a"),
    ("notificacion",    "6. Notificación",    "#bf3989"),
    ("seguridad",       "7. Seguridad",       "#57606a"),
    ("mantenimiento",   "8. Mantenimiento",   "#9a6700"),
]

# --------------------------------------------------------------------------
# Entidades
#   attrs  : (nombre, tipo, admite_nulo, clave, descripcion)
#   uk     : listas de columnas con restriccion UNIQUE compuesta
#   checks : restricciones CHECK a nivel de tabla
#   pos    : (columna, fila) usada para ubicar la entidad en el diagrama del modulo
# --------------------------------------------------------------------------
ENTIDADES = {}


def E(nombre, modulo, desc, attrs, pos, uk=None, checks=None, tipo="fuerte"):
    ENTIDADES[nombre] = {
        "nombre": nombre, "modulo": modulo, "desc": desc, "attrs": attrs,
        "pos": pos, "uk": uk or [], "checks": checks or [], "tipo": tipo,
    }


# ----------------------------- 1. Parametrizacion -------------------------
E("cliente", "parametrizacion",
  "Persona natural o jurídica que reserva habitaciones y consume productos y servicios del hotel.",
  [("tipo_documento",   "VARCHAR(20)",  False, "",   "Tipo de documento de identidad (CC, CE, NIT, PAS)."),
   ("numero_documento", "VARCHAR(30)",  False, "",   "Número de documento; junto con el tipo identifica al cliente."),
   ("nombre",           "VARCHAR(100)", False, "",   "Nombres del cliente."),
   ("apellido",         "VARCHAR(100)", False, "",   "Apellidos del cliente."),
   ("telefono",         "VARCHAR(30)",  True,  "",   "Teléfono de contacto."),
   ("correo",           "VARCHAR(150)", True,  "UK", "Correo electrónico de contacto."),
   ("direccion",        "VARCHAR(200)", True,  "",   "Dirección de residencia o facturación.")],
  (0, 0), uk=[["tipo_documento", "numero_documento"]])

E("precio", "parametrizacion",
  "Regla de precio dinámico: valor de un tipo de habitación según el tipo de día y la vigencia.",
  [("tipo_habitacion_id", "BIGINT UNSIGNED", False, "FK", "Tipo de habitación al que aplica el precio."),
   ("tipo_dia_id",        "BIGINT UNSIGNED", False, "FK", "Tipo de día (temporada, feriado, especial) al que aplica."),
   ("valor",              "DECIMAL(12,2)",   False, "",   "Valor de la tarifa. Debe ser mayor o igual a cero."),
   ("fecha_inicio",       "DATE",            False, "",   "Fecha desde la cual rige la tarifa."),
   ("fecha_fin",          "DATE",            True,  "",   "Fecha hasta la cual rige la tarifa (nula = vigente)."),
   ("condicion",          "VARCHAR(255)",    True,  "",   "Condición comercial adicional aplicable.")],
  (1, 1), checks=["valor >= 0", "(fecha_fin IS NULL OR fecha_fin >= fecha_inicio)"])

E("empresa", "parametrizacion",
  "Empresa hotelera propietaria de las sedes; raíz de la información corporativa y legal.",
  [("nombre",       "VARCHAR(150)", False, "",   "Nombre comercial de la empresa."),
   ("nit",          "VARCHAR(20)",  False, "UK", "Número de identificación tributaria."),
   ("razon_social", "VARCHAR(200)", False, "",   "Razón social registrada."),
   ("telefono",     "VARCHAR(30)",  True,  "",   "Teléfono corporativo."),
   ("correo",       "VARCHAR(150)", True,  "",   "Correo corporativo."),
   ("direccion",    "VARCHAR(200)", True,  "",   "Dirección de la sede principal."),
   ("sitio_web",    "VARCHAR(150)", True,  "",   "Sitio web institucional.")],
  (2, 0))

E("informacion_legal", "parametrizacion",
  "Documentos legales asociados a la empresa (cámara de comercio, RUT, licencias, pólizas).",
  [("empresa_id",             "BIGINT UNSIGNED", False, "FK", "Empresa propietaria del documento."),
   ("tipo_documento_legal",   "VARCHAR(50)",     False, "",   "Tipo de documento legal."),
   ("numero_documento_legal", "VARCHAR(50)",     False, "",   "Número o radicado del documento."),
   ("descripcion",            "VARCHAR(255)",    True,  "",   "Descripción del documento."),
   ("fecha_expedicion",       "DATE",            False, "",   "Fecha de expedición."),
   ("fecha_vencimiento",      "DATE",            True,  "",   "Fecha de vencimiento (nula = sin vencimiento).")],
  (3, 1), uk=[["empresa_id", "tipo_documento_legal", "numero_documento_legal"]])

E("empleado", "parametrizacion",
  "Persona vinculada laboralmente al hotel; atiende check in, check out y mantenimiento.",
  [("persona_id",       "BIGINT UNSIGNED", False, "FK,UK", "Persona asociada al empleado (subtipo de persona)."),
   ("cargo",            "VARCHAR(80)",     False, "",      "Cargo desempeñado."),
   ("fecha_ingreso",    "DATE",            False, "",      "Fecha de ingreso a la empresa."),
   ("telefono_laboral", "VARCHAR(30)",     True,  "",      "Teléfono laboral."),
   ("correo_laboral",   "VARCHAR(150)",    True,  "UK",    "Correo laboral.")],
  (0, 1))

E("tipo_dia", "parametrizacion",
  "Clasificación del día (ordinario, temporada, feriado, evento especial) que condiciona la tarifa.",
  [("nombre",           "VARCHAR(60)",  False, "UK", "Nombre del tipo de día."),
   ("descripcion",      "VARCHAR(255)", True,  "",   "Descripción del tipo de día."),
   ("fecha",            "DATE",         True,  "",   "Fecha puntual cuando el tipo de día es específico."),
   ("aplica_temporada", "BOOLEAN",      False, "",   "Indica si corresponde a temporada alta."),
   ("aplica_feriado",   "BOOLEAN",      False, "",   "Indica si corresponde a día feriado."),
   ("aplica_especial",  "BOOLEAN",      False, "",   "Indica si corresponde a evento especial.")],
  (2, 1))

E("metodo_pago", "parametrizacion",
  "Medio de pago aceptado por el hotel (efectivo, tarjeta, transferencia).",
  [("nombre",               "VARCHAR(60)",  False, "UK", "Nombre del método de pago."),
   ("descripcion",          "VARCHAR(255)", True,  "",   "Descripción del método de pago."),
   ("requiere_referencia",  "BOOLEAN",      False, "",   "Indica si exige número de referencia o autorización."),
   ("permite_pago_parcial", "BOOLEAN",      False, "",   "Indica si admite abonos o pagos parciales.")],
  (1, 0))

# ----------------------------- 2. Distribucion ----------------------------
E("sede", "distribucion",
  "Establecimiento físico de la empresa hotelera donde se ubican las habitaciones.",
  [("empresa_id", "BIGINT UNSIGNED", False, "FK", "Empresa a la que pertenece la sede."),
   ("nombre",     "VARCHAR(120)",    False, "",   "Nombre de la sede."),
   ("direccion",  "VARCHAR(200)",    False, "",   "Dirección de la sede."),
   ("ciudad",     "VARCHAR(80)",     False, "",   "Ciudad donde opera la sede."),
   ("telefono",   "VARCHAR(30)",     True,  "",   "Teléfono de la sede."),
   ("correo",     "VARCHAR(150)",    True,  "",   "Correo de la sede.")],
  (0, 0), uk=[["empresa_id", "nombre"]])

E("tipo_habitacion", "distribucion",
  "Categoría comercial de habitación (sencilla, doble, suite) con su capacidad.",
  [("nombre",           "VARCHAR(60)",  False, "UK", "Nombre del tipo de habitación."),
   ("descripcion",      "VARCHAR(255)", True,  "",   "Descripción de la categoría."),
   ("capacidad_base",   "INT",          False, "",   "Cantidad de huéspedes incluida en la tarifa base."),
   ("capacidad_maxima", "INT",          False, "",   "Cantidad máxima de huéspedes permitida.")],
  (1, 0), checks=["capacidad_base > 0", "capacidad_maxima >= capacidad_base"])

E("estado_habitacion", "distribucion",
  "Estado operativo de la habitación (disponible, ocupada, reservada, limpieza, bloqueada, mantenimiento).",
  [("nombre",           "VARCHAR(40)",  False, "UK", "Nombre del estado."),
   ("descripcion",      "VARCHAR(255)", True,  "",   "Descripción del estado."),
   ("permite_reserva",  "BOOLEAN",      False, "",   "Indica si en este estado la habitación puede reservarse."),
   ("permite_check_in", "BOOLEAN",      False, "",   "Indica si en este estado se permite el check in.")],
  (2, 0))

E("habitacion", "distribucion",
  "Unidad física alojable dentro de una sede; núcleo de la disponibilidad del negocio.",
  [("sede_id",              "BIGINT UNSIGNED", False, "FK", "Sede donde se ubica la habitación."),
   ("tipo_habitacion_id",   "BIGINT UNSIGNED", False, "FK", "Categoría comercial de la habitación."),
   ("estado_habitacion_id", "BIGINT UNSIGNED", False, "FK", "Estado operativo actual."),
   ("numero",               "VARCHAR(10)",     False, "",   "Número o identificador visible de la habitación."),
   ("piso",                 "INT",             False, "",   "Piso en el que se encuentra."),
   ("capacidad",            "INT",             False, "",   "Capacidad real de huéspedes."),
   ("descripcion",          "VARCHAR(255)",    True,  "",   "Observaciones o dotación de la habitación.")],
  (1, 1), uk=[["sede_id", "numero"]], checks=["capacidad > 0"])

# ------------------------ 3. Prestacion de servicio -----------------------
E("reserva_habitacion", "prestacion",
  "Solicitud de alojamiento de un cliente sobre una habitación en un rango de fechas.",
  [("cliente_id",       "BIGINT UNSIGNED", False, "FK", "Cliente que realiza la reserva."),
   ("habitacion_id",    "BIGINT UNSIGNED", False, "FK", "Habitación reservada."),
   ("fecha_inicio",     "DATE",            False, "",   "Fecha de inicio del alojamiento."),
   ("fecha_fin",        "DATE",            False, "",   "Fecha de finalización del alojamiento."),
   ("cantidad_persona", "INT",             False, "",   "Número de huéspedes."),
   ("estado_reserva",   "VARCHAR(30)",     False, "",   "Estado de la reserva (PENDIENTE, CONFIRMADA, CANCELADA, CUMPLIDA)."),
   ("valor_estimado",   "DECIMAL(12,2)",   True,  "",   "Valor estimado calculado con la regla de precio.")],
  (1, 1), checks=["fecha_fin >= fecha_inicio", "cantidad_persona > 0"])

E("cancelacion_habitacion", "prestacion",
  "Registro de la anulación de una reserva, con motivo y penalidad aplicada.",
  [("reserva_habitacion_id", "BIGINT UNSIGNED", False, "FK,UK", "Reserva que se cancela."),
   ("motivo",                "VARCHAR(255)",    False, "",      "Motivo de la cancelación."),
   ("fecha_cancelacion",     "DATETIME",        False, "",      "Fecha y hora de la cancelación."),
   ("aplica_penalidad",      "BOOLEAN",         False, "",      "Indica si se cobra penalidad."),
   ("valor_penalidad",       "DECIMAL(12,2)",   True,  "",      "Valor de la penalidad aplicada.")],
  (0, 2), checks=["valor_penalidad IS NULL OR valor_penalidad >= 0"])

E("disponibilidad_habitacion", "prestacion",
  "Ventana de tiempo en la que una habitación está o no disponible para reservar.",
  [("habitacion_id",        "BIGINT UNSIGNED", False, "FK", "Habitación evaluada."),
   ("fecha_inicio",         "DATE",            False, "",   "Inicio del periodo evaluado."),
   ("fecha_fin",            "DATE",            False, "",   "Fin del periodo evaluado."),
   ("disponible",           "BOOLEAN",         False, "",   "Indica si la habitación está disponible en el periodo."),
   ("motivo_no_disponible", "VARCHAR(255)",    True,  "",   "Causa de la no disponibilidad.")],
  (0, 0), checks=["fecha_fin >= fecha_inicio"])

E("catalogo_habitacion", "prestacion",
  "Ficha comercial publicada de la habitación para consulta del cliente.",
  [("habitacion_id", "BIGINT UNSIGNED", False, "FK,UK", "Habitación publicada en el catálogo."),
   ("titulo",        "VARCHAR(120)",    False, "",      "Título comercial."),
   ("descripcion",   "TEXT",            True,  "",      "Descripción comercial extendida."),
   ("precio_base",   "DECIMAL(12,2)",   False, "",      "Precio base de referencia mostrado."),
   ("visible",       "BOOLEAN",         False, "",      "Indica si la ficha se muestra al público.")],
  (2, 0), checks=["precio_base >= 0"])

E("check_in", "prestacion",
  "Registro del ingreso efectivo del cliente a la habitación reservada.",
  [("reserva_habitacion_id", "BIGINT UNSIGNED", False, "FK,UK", "Reserva sobre la que se realiza el ingreso."),
   ("empleado_id",           "BIGINT UNSIGNED", False, "FK",    "Empleado que atiende el ingreso."),
   ("fecha_hora_ingreso",    "DATETIME",        False, "",      "Fecha y hora del ingreso."),
   ("observacion",           "VARCHAR(255)",    True,  "",      "Observaciones del ingreso.")],
  (2, 1))

E("estadia", "prestacion",
  "Ocupación real de la habitación por el cliente; agrupa los consumos del periodo.",
  [("reserva_habitacion_id", "BIGINT UNSIGNED", False, "FK,UK", "Reserva que dio origen a la estadía."),
   ("cliente_id",            "BIGINT UNSIGNED", False, "FK",    "Cliente hospedado."),
   ("habitacion_id",         "BIGINT UNSIGNED", False, "FK",    "Habitación ocupada."),
   ("fecha_inicio",          "DATETIME",        False, "",      "Inicio real de la estadía."),
   ("fecha_fin",             "DATETIME",        True,  "",      "Fin real de la estadía (nulo = en curso)."),
   ("estado_estadia",        "VARCHAR(30)",     False, "",      "Estado de la estadía (EN_CURSO, FINALIZADA).")],
  (1, 2))

E("check_out", "prestacion",
  "Registro de la salida del cliente y liquidación de la estadía.",
  [("estadia_id",        "BIGINT UNSIGNED", False, "FK,UK", "Estadía que se cierra."),
   ("empleado_id",       "BIGINT UNSIGNED", False, "FK",    "Empleado que atiende la salida."),
   ("fecha_hora_salida", "DATETIME",        False, "",      "Fecha y hora de la salida."),
   ("observacion",       "VARCHAR(255)",    True,  "",      "Observaciones de la salida."),
   ("valor_total",       "DECIMAL(12,2)",   False, "",      "Valor total liquidado al cierre.")],
  (2, 2), checks=["valor_total >= 0"])

E("venta_producto", "prestacion",
  "Consumo de un producto de inventario durante una estadía (entidad asociativa estadía-producto).",
  [("estadia_id",     "BIGINT UNSIGNED", False, "FK", "Estadía en la que se realiza el consumo."),
   ("producto_id",    "BIGINT UNSIGNED", False, "FK", "Producto consumido."),
   ("cantidad",       "INT",             False, "",   "Cantidad vendida."),
   ("valor_unitario", "DECIMAL(12,2)",   False, "",   "Valor unitario aplicado."),
   ("valor_total",    "DECIMAL(12,2)",   False, "",   "Valor total de la línea de venta.")],
  (0, 3), checks=["cantidad > 0", "valor_unitario >= 0", "valor_total >= 0"], tipo="asociativa")

E("venta_servicio", "prestacion",
  "Consumo de un servicio del hotel durante una estadía (entidad asociativa estadía-servicio).",
  [("estadia_id",     "BIGINT UNSIGNED", False, "FK", "Estadía en la que se presta el servicio."),
   ("servicio_id",    "BIGINT UNSIGNED", False, "FK", "Servicio prestado."),
   ("cantidad",       "INT",             False, "",   "Cantidad vendida."),
   ("valor_unitario", "DECIMAL(12,2)",   False, "",   "Valor unitario aplicado."),
   ("valor_total",    "DECIMAL(12,2)",   False, "",   "Valor total de la línea de venta.")],
  (2, 3), checks=["cantidad > 0", "valor_unitario >= 0", "valor_total >= 0"], tipo="asociativa")

# ----------------------------- 4. Facturacion -----------------------------
E("pre_factura", "facturacion",
  "Consolidado anticipado de valores de la estadía antes de emitir la factura definitiva.",
  [("estadia_id",            "BIGINT UNSIGNED", False, "FK", "Estadía liquidada de forma preliminar."),
   ("reserva_habitacion_id", "BIGINT UNSIGNED", True,  "FK", "Reserva asociada a la pre factura."),
   ("cliente_id",            "BIGINT UNSIGNED", False, "FK", "Cliente responsable del pago."),
   ("subtotal",              "DECIMAL(12,2)",   False, "",   "Suma de alojamiento y consumos antes de impuestos."),
   ("impuesto",              "DECIMAL(12,2)",   False, "",   "Valor de impuestos calculados."),
   ("descuento",             "DECIMAL(12,2)",   False, "",   "Valor de descuentos aplicados."),
   ("total",                 "DECIMAL(12,2)",   False, "",   "Valor total preliminar.")],
  (0, 0), checks=["subtotal >= 0", "total >= 0"])

E("factura", "facturacion",
  "Documento definitivo de cobro emitido al cliente al cierre de la estadía.",
  [("cliente_id",     "BIGINT UNSIGNED", False, "FK",    "Cliente facturado."),
   ("estadia_id",     "BIGINT UNSIGNED", False, "FK,UK", "Estadía facturada."),
   ("numero_factura", "VARCHAR(30)",     False, "UK",    "Número consecutivo de la factura."),
   ("fecha_emision",  "DATETIME",        False, "",      "Fecha y hora de emisión."),
   ("subtotal",       "DECIMAL(12,2)",   False, "",      "Subtotal antes de impuestos."),
   ("impuesto",       "DECIMAL(12,2)",   False, "",      "Valor de impuestos."),
   ("descuento",      "DECIMAL(12,2)",   False, "",      "Valor de descuentos."),
   ("total",          "DECIMAL(12,2)",   False, "",      "Valor total a pagar."),
   ("estado_factura", "VARCHAR(30)",     False, "",      "Estado (EMITIDA, PAGADA, ANULADA).")],
  (1, 1), checks=["subtotal >= 0", "total >= 0"])

E("detalle_compra", "facturacion",
  "Línea de la factura correspondiente a un producto o a un servicio.",
  [("factura_id",     "BIGINT UNSIGNED", False, "FK", "Factura a la que pertenece la línea."),
   ("producto_id",    "BIGINT UNSIGNED", True,  "FK", "Producto facturado (nulo si la línea es de servicio)."),
   ("servicio_id",    "BIGINT UNSIGNED", True,  "FK", "Servicio facturado (nulo si la línea es de producto)."),
   ("descripcion",    "VARCHAR(255)",    False, "",   "Descripción del ítem facturado."),
   ("cantidad",       "INT",             False, "",   "Cantidad facturada."),
   ("valor_unitario", "DECIMAL(12,2)",   False, "",   "Valor unitario."),
   ("valor_total",    "DECIMAL(12,2)",   False, "",   "Valor total de la línea.")],
  (1, 2),
  checks=["cantidad > 0",
          "((producto_id IS NOT NULL AND servicio_id IS NULL) OR (producto_id IS NULL AND servicio_id IS NOT NULL))"],
  tipo="debil")

E("pago_parcial", "facturacion",
  "Abono o pago aplicado a una reserva o a una factura mediante un método de pago.",
  [("reserva_habitacion_id", "BIGINT UNSIGNED", True,  "FK", "Reserva a la que se abona (anticipos)."),
   ("factura_id",            "BIGINT UNSIGNED", True,  "FK", "Factura a la que se aplica el pago."),
   ("metodo_pago_id",        "BIGINT UNSIGNED", False, "FK", "Método de pago utilizado."),
   ("valor",                 "DECIMAL(12,2)",   False, "",   "Valor pagado."),
   ("fecha_pago",            "DATETIME",        False, "",   "Fecha y hora del pago."),
   ("referencia_pago",       "VARCHAR(60)",     True,  "",   "Referencia o autorización del pago.")],
  (2, 1),
  checks=["valor > 0",
          "(reserva_habitacion_id IS NOT NULL OR factura_id IS NOT NULL)"])

# ------------------------------ 5. Inventario -----------------------------
E("proveedor", "inventario",
  "Tercero que abastece los productos comercializados por el hotel.",
  [("nombre",    "VARCHAR(150)", False, "",   "Nombre del proveedor."),
   ("nit",       "VARCHAR(20)",  False, "UK", "Identificación tributaria del proveedor."),
   ("telefono",  "VARCHAR(30)",  True,  "",   "Teléfono de contacto."),
   ("correo",    "VARCHAR(150)", True,  "",   "Correo de contacto."),
   ("direccion", "VARCHAR(200)", True,  "",   "Dirección del proveedor.")],
  (0, 0))

E("producto", "inventario",
  "Bien físico con existencias que el hotel vende durante la estadía.",
  [("proveedor_id", "BIGINT UNSIGNED", True,  "FK", "Proveedor que abastece el producto."),
   ("nombre",       "VARCHAR(120)",    False, "",   "Nombre del producto."),
   ("descripcion",  "VARCHAR(255)",    True,  "",   "Descripción del producto."),
   ("valor_venta",  "DECIMAL(12,2)",   False, "",   "Precio de venta al público."),
   ("stock_actual", "INT",             False, "",   "Existencia actual."),
   ("stock_minimo", "INT",             False, "",   "Existencia mínima antes de reabastecer.")],
  (0, 1), checks=["valor_venta >= 0", "stock_actual >= 0", "stock_minimo >= 0"])

E("servicio", "inventario",
  "Prestación intangible ofrecida por el hotel (lavandería, spa, transporte).",
  [("nombre",      "VARCHAR(120)",  False, "UK", "Nombre del servicio."),
   ("descripcion", "VARCHAR(255)",  True,  "",   "Descripción del servicio."),
   ("valor_venta", "DECIMAL(12,2)", False, "",   "Precio de venta del servicio."),
   ("disponible",  "BOOLEAN",       False, "",   "Indica si el servicio está habilitado.")],
  (2, 1), checks=["valor_venta >= 0"])

E("seguimiento_producto", "inventario",
  "Movimiento de inventario (entrada, salida, ajuste) sobre un producto.",
  [("producto_id",     "BIGINT UNSIGNED", False, "FK", "Producto afectado por el movimiento."),
   ("tipo_movimiento", "VARCHAR(30)",     False, "",   "Tipo de movimiento (ENTRADA, SALIDA, AJUSTE)."),
   ("cantidad",        "INT",             False, "",   "Cantidad movida."),
   ("fecha_movimiento","DATETIME",        False, "",   "Fecha y hora del movimiento."),
   ("observacion",     "VARCHAR(255)",    True,  "",   "Observación del movimiento.")],
  (0, 2), checks=["cantidad > 0"])

E("disponibilidad_inventario", "inventario",
  "Estado de disponibilidad de un producto o de un servicio para la venta.",
  [("producto_id",         "BIGINT UNSIGNED", True,  "FK", "Producto evaluado (nulo si evalúa un servicio)."),
   ("servicio_id",         "BIGINT UNSIGNED", True,  "FK", "Servicio evaluado (nulo si evalúa un producto)."),
   ("cantidad_disponible", "INT",             False, "",   "Cantidad disponible para la venta."),
   ("disponible",          "BOOLEAN",         False, "",   "Indica si puede venderse."),
   ("observacion",         "VARCHAR(255)",    True,  "",   "Observación de la disponibilidad.")],
  (1, 2),
  checks=["cantidad_disponible >= 0",
          "((producto_id IS NOT NULL AND servicio_id IS NULL) OR (producto_id IS NULL AND servicio_id IS NOT NULL))"])

# ----------------------------- 6. Notificacion ----------------------------
E("promocion", "notificacion",
  "Campaña comercial difundida por un canal durante un periodo de vigencia.",
  [("titulo",       "VARCHAR(120)", False, "",   "Título de la promoción."),
   ("descripcion",  "VARCHAR(255)", True,  "",   "Descripción de la promoción."),
   ("fecha_inicio", "DATE",         False, "",   "Inicio de vigencia."),
   ("fecha_fin",    "DATE",         False, "",   "Fin de vigencia."),
   ("canal",        "VARCHAR(30)",  False, "",   "Canal de difusión (CORREO, SMS, APP)."),
   ("activa",       "BOOLEAN",      False, "",   "Indica si la promoción está activa.")],
  (0, 0), checks=["fecha_fin >= fecha_inicio"])

E("alerta", "notificacion",
  "Mensaje operativo enviado a un cliente, opcionalmente asociado a una reserva.",
  [("cliente_id",            "BIGINT UNSIGNED", False, "FK", "Cliente destinatario."),
   ("reserva_habitacion_id", "BIGINT UNSIGNED", True,  "FK", "Reserva que origina la alerta."),
   ("titulo",                "VARCHAR(120)",    False, "",   "Título de la alerta."),
   ("mensaje",               "TEXT",            False, "",   "Contenido del mensaje."),
   ("canal",                 "VARCHAR(30)",     False, "",   "Canal de envío."),
   ("fecha_envio",           "DATETIME",        False, "",   "Fecha y hora de envío.")],
  (1, 1))

E("termino_condicion", "notificacion",
  "Versión de los términos y condiciones aceptados antes de comprar producto o servicio.",
  [("titulo",         "VARCHAR(120)", False, "", "Título del documento."),
   ("contenido",      "TEXT",         False, "", "Texto completo de los términos."),
   ("version",        "VARCHAR(20)",  False, "", "Versión del documento."),
   ("fecha_vigencia", "DATE",         False, "", "Fecha desde la cual rige la versión."),
   ("obligatorio",    "BOOLEAN",      False, "", "Indica si su aceptación es obligatoria.")],
  (2, 0), uk=[["titulo", "version"]])

E("fidelizacion_cliente", "notificacion",
  "Programa de puntos y nivel de fidelización asociado a un cliente.",
  [("cliente_id",              "BIGINT UNSIGNED", False, "FK,UK", "Cliente inscrito en el programa."),
   ("nivel",                   "VARCHAR(30)",     False, "",      "Nivel alcanzado (BRONCE, PLATA, ORO)."),
   ("puntos",                  "INT",             False, "",      "Puntos acumulados."),
   ("fecha_ultima_interaccion","DATE",            True,  "",      "Fecha de la última interacción registrada."),
   ("observacion",             "VARCHAR(255)",    True,  "",      "Observación del programa.")],
  (0, 1), checks=["puntos >= 0"])

# ------------------------------ 7. Seguridad ------------------------------
E("persona", "seguridad",
  "Datos básicos de una persona natural; supertipo de empleado y titular de usuario.",
  [("tipo_documento",   "VARCHAR(20)",  False, "",   "Tipo de documento de identidad."),
   ("numero_documento", "VARCHAR(30)",  False, "",   "Número de documento; junto con el tipo identifica a la persona."),
   ("nombre",           "VARCHAR(100)", False, "",   "Nombres de la persona."),
   ("apellido",         "VARCHAR(100)", False, "",   "Apellidos de la persona."),
   ("telefono",         "VARCHAR(30)",  True,  "",   "Teléfono de contacto."),
   ("correo",           "VARCHAR(150)", True,  "",   "Correo de contacto.")],
  (0, 0), uk=[["tipo_documento", "numero_documento"]])

E("usuario", "seguridad",
  "Credencial de acceso al sistema asociada a una persona.",
  [("persona_id",    "BIGINT UNSIGNED", False, "FK,UK", "Persona titular de la cuenta."),
   ("username",      "VARCHAR(60)",     False, "UK",    "Nombre de usuario."),
   ("password_hash", "VARCHAR(255)",    False, "",      "Hash de la contraseña."),
   ("ultimo_acceso", "DATETIME",        True,  "",      "Fecha y hora del último ingreso."),
   ("bloqueado",     "BOOLEAN",         False, "",      "Indica si la cuenta está bloqueada.")],
  (0, 1))

E("rol", "seguridad",
  "Perfil funcional que agrupa permisos (recepción, administración, mantenimiento).",
  [("nombre",      "VARCHAR(60)",  False, "UK", "Nombre del rol."),
   ("descripcion", "VARCHAR(255)", True,  "",   "Descripción del rol.")],
  (2, 1))

E("permiso", "seguridad",
  "Autorización elemental sobre una acción del sistema.",
  [("nombre",      "VARCHAR(80)",  False, "UK", "Nombre del permiso."),
   ("descripcion", "VARCHAR(255)", True,  "",   "Descripción del permiso."),
   ("accion",      "VARCHAR(40)",  False, "",   "Acción autorizada (CREAR, LEER, ACTUALIZAR, ELIMINAR).")],
  (2, 3))

E("modulo", "seguridad",
  "Agrupación funcional del sistema (parametrización, distribución, facturación).",
  [("nombre",      "VARCHAR(80)",  False, "UK", "Nombre del módulo."),
   ("descripcion", "VARCHAR(255)", True,  "",   "Descripción del módulo."),
   ("ruta_base",   "VARCHAR(120)", True,  "",   "Ruta base del módulo en la aplicación.")],
  (0, 3))

E("vista", "seguridad",
  "Pantalla o vista perteneciente a un módulo del sistema.",
  [("modulo_id",   "BIGINT UNSIGNED", False, "FK", "Módulo propietario de la vista."),
   ("nombre",      "VARCHAR(80)",     False, "",   "Nombre de la vista."),
   ("descripcion", "VARCHAR(255)",    True,  "",   "Descripción de la vista."),
   ("ruta",        "VARCHAR(150)",    False, "UK", "Ruta de la vista en la aplicación.")],
  (0, 4))

E("usuario_rol", "seguridad",
  "Asignación de roles a usuarios (resuelve la relación N:M usuario-rol).",
  [("usuario_id", "BIGINT UNSIGNED", False, "FK", "Usuario al que se asigna el rol."),
   ("rol_id",     "BIGINT UNSIGNED", False, "FK", "Rol asignado.")],
  (1, 1), uk=[["usuario_id", "rol_id"]], tipo="asociativa")

E("rol_permiso", "seguridad",
  "Asignación de permisos a roles (resuelve la relación N:M rol-permiso).",
  [("rol_id",     "BIGINT UNSIGNED", False, "FK", "Rol al que se asigna el permiso."),
   ("permiso_id", "BIGINT UNSIGNED", False, "FK", "Permiso asignado.")],
  (2, 2), uk=[["rol_id", "permiso_id"]], tipo="asociativa")

E("modulo_vista", "seguridad",
  "Vinculación de vistas reutilizables a módulos (resuelve la relación N:M módulo-vista).",
  [("modulo_id", "BIGINT UNSIGNED", False, "FK", "Módulo que expone la vista."),
   ("vista_id",  "BIGINT UNSIGNED", False, "FK", "Vista expuesta.")],
  (1, 4), uk=[["modulo_id", "vista_id"]], tipo="asociativa")

# ---------------------------- 8. Mantenimiento ----------------------------
E("mantenimiento_habitacion", "mantenimiento",
  "Orden de mantenimiento sobre una habitación; supertipo de mantenimiento por uso y por remodelación.",
  [("habitacion_id",         "BIGINT UNSIGNED", False, "FK", "Habitación intervenida."),
   ("empleado_id",           "BIGINT UNSIGNED", True,  "FK", "Empleado responsable de la intervención."),
   ("tipo_mantenimiento",    "VARCHAR(30)",     False, "",   "Discriminador de especialización (USO, REMODELACION)."),
   ("fecha_inicio",          "DATETIME",        False, "",   "Inicio del mantenimiento."),
   ("fecha_fin",             "DATETIME",        True,  "",   "Fin del mantenimiento (nulo = en curso)."),
   ("estado_mantenimiento",  "VARCHAR(30)",     False, "",   "Estado (PROGRAMADO, EN_PROCESO, FINALIZADO)."),
   ("observacion",           "VARCHAR(255)",    True,  "",   "Observación de la intervención.")],
  (1, 0), checks=["tipo_mantenimiento IN ('USO','REMODELACION')"])

E("mantenimiento_uso", "mantenimiento",
  "Especialización de mantenimiento generado por el uso normal de la habitación.",
  [("mantenimiento_habitacion_id", "BIGINT UNSIGNED", False, "FK,UK", "Orden de mantenimiento especializada."),
   ("motivo_uso",                  "VARCHAR(120)",    False, "",      "Motivo derivado del uso."),
   ("detalle_actividad",           "TEXT",            True,  "",      "Detalle de las actividades ejecutadas.")],
  (0, 1), tipo="subtipo")

E("mantenimiento_remodelacion", "mantenimiento",
  "Especialización de mantenimiento por remodelación o adecuación de la habitación.",
  [("mantenimiento_habitacion_id", "BIGINT UNSIGNED", False, "FK,UK", "Orden de mantenimiento especializada."),
   ("descripcion_remodelacion",    "TEXT",            False, "",      "Alcance de la remodelación."),
   ("presupuesto_estimado",        "DECIMAL(12,2)",   True,  "",      "Presupuesto estimado de la obra.")],
  (2, 1), checks=["presupuesto_estimado IS NULL OR presupuesto_estimado >= 0"], tipo="subtipo")

E("dashboard_mantenimiento", "mantenimiento",
  "Consolidado por sede del estado de las habitaciones a una fecha de corte.",
  [("sede_id",                  "BIGINT UNSIGNED", False, "FK", "Sede consolidada."),
   ("total_habitacion",         "INT",             False, "",   "Total de habitaciones de la sede."),
   ("habitacion_disponible",    "INT",             False, "",   "Habitaciones disponibles."),
   ("habitacion_ocupada",       "INT",             False, "",   "Habitaciones ocupadas."),
   ("habitacion_mantenimiento", "INT",             False, "",   "Habitaciones en mantenimiento."),
   ("fecha_corte",              "DATE",            False, "",   "Fecha de corte del consolidado.")],
  (1, 2), uk=[["sede_id", "fecha_corte"]], checks=["total_habitacion >= 0"])
