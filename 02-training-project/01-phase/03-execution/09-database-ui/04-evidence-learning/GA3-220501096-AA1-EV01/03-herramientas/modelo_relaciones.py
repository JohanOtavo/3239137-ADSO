# -*- coding: utf-8 -*-
"""
GA3-220501096-AA1-EV01 - Relaciones y cardinalidades del modelo conceptual.

Cada relación se declara desde la entidad HIJA (la que contiene la llave foránea)
hacia la entidad PADRE (la referenciada).

  hijo        : entidad que contiene la llave foránea
  padre       : entidad referenciada
  fk          : columna de la llave foránea
  card        : "1:N" o "1:1"
  opcional    : True cuando la llave foránea admite NULL (participación parcial)
  on_delete   : acción referencial ante el borrado del padre
  lectura     : lectura de negocio de la relación, en ambos sentidos
"""

RELACIONES = [
    # ---------------------------- Parametrización -------------------------
    dict(hijo="precio", padre="tipo_habitacion", fk="tipo_habitacion_id", card="1:N", opcional=False,
         on_delete="RESTRICT",
         lectura="Un tipo de habitación tiene muchas tarifas; cada tarifa aplica a un solo tipo de habitación."),
    dict(hijo="precio", padre="tipo_dia", fk="tipo_dia_id", card="1:N", opcional=False,
         on_delete="RESTRICT",
         lectura="Un tipo de día condiciona muchas tarifas; cada tarifa aplica a un solo tipo de día."),
    dict(hijo="informacion_legal", padre="empresa", fk="empresa_id", card="1:N", opcional=False,
         on_delete="CASCADE",
         lectura="Una empresa registra muchos documentos legales; cada documento pertenece a una sola empresa."),
    dict(hijo="empleado", padre="persona", fk="persona_id", card="1:1", opcional=False,
         on_delete="RESTRICT",
         lectura="Una persona puede ser a lo sumo un empleado; cada empleado corresponde a una sola persona."),

    # ----------------------------- Distribución ---------------------------
    dict(hijo="sede", padre="empresa", fk="empresa_id", card="1:N", opcional=False,
         on_delete="RESTRICT",
         lectura="Una empresa opera muchas sedes; cada sede pertenece a una sola empresa."),
    dict(hijo="habitacion", padre="sede", fk="sede_id", card="1:N", opcional=False,
         on_delete="RESTRICT",
         lectura="Una sede contiene muchas habitaciones; cada habitación pertenece a una sola sede."),
    dict(hijo="habitacion", padre="tipo_habitacion", fk="tipo_habitacion_id", card="1:N", opcional=False,
         on_delete="RESTRICT",
         lectura="Un tipo de habitación clasifica muchas habitaciones; cada habitación tiene un solo tipo."),
    dict(hijo="habitacion", padre="estado_habitacion", fk="estado_habitacion_id", card="1:N", opcional=False,
         on_delete="RESTRICT",
         lectura="Un estado aplica a muchas habitaciones; cada habitación tiene un solo estado vigente."),

    # ------------------------ Prestación de servicio ----------------------
    dict(hijo="reserva_habitacion", padre="cliente", fk="cliente_id", card="1:N", opcional=False,
         on_delete="RESTRICT",
         lectura="Un cliente realiza muchas reservas; cada reserva pertenece a un solo cliente."),
    dict(hijo="reserva_habitacion", padre="habitacion", fk="habitacion_id", card="1:N", opcional=False,
         on_delete="RESTRICT",
         lectura="Una habitación recibe muchas reservas en el tiempo; cada reserva es sobre una sola habitación."),
    dict(hijo="cancelacion_habitacion", padre="reserva_habitacion", fk="reserva_habitacion_id", card="1:1",
         opcional=False, on_delete="CASCADE",
         lectura="Una reserva puede tener a lo sumo una cancelación; cada cancelación anula una sola reserva."),
    dict(hijo="disponibilidad_habitacion", padre="habitacion", fk="habitacion_id", card="1:N", opcional=False,
         on_delete="CASCADE",
         lectura="Una habitación tiene muchos periodos de disponibilidad; cada periodo es de una sola habitación."),
    dict(hijo="catalogo_habitacion", padre="habitacion", fk="habitacion_id", card="1:1", opcional=False,
         on_delete="CASCADE",
         lectura="Una habitación se publica en a lo sumo una ficha de catálogo; cada ficha describe una habitación."),
    dict(hijo="check_in", padre="reserva_habitacion", fk="reserva_habitacion_id", card="1:1", opcional=False,
         on_delete="RESTRICT",
         lectura="Una reserva genera a lo sumo un check in; cada check in corresponde a una sola reserva."),
    dict(hijo="check_in", padre="empleado", fk="empleado_id", card="1:N", opcional=False,
         on_delete="RESTRICT",
         lectura="Un empleado atiende muchos check in; cada check in es atendido por un solo empleado."),
    dict(hijo="estadia", padre="reserva_habitacion", fk="reserva_habitacion_id", card="1:1", opcional=False,
         on_delete="RESTRICT",
         lectura="Una reserva origina a lo sumo una estadía; cada estadía proviene de una sola reserva."),
    dict(hijo="estadia", padre="cliente", fk="cliente_id", card="1:N", opcional=False,
         on_delete="RESTRICT",
         lectura="Un cliente acumula muchas estadías; cada estadía corresponde a un solo cliente."),
    dict(hijo="estadia", padre="habitacion", fk="habitacion_id", card="1:N", opcional=False,
         on_delete="RESTRICT",
         lectura="Una habitación aloja muchas estadías en el tiempo; cada estadía ocupa una sola habitación."),
    dict(hijo="check_out", padre="estadia", fk="estadia_id", card="1:1", opcional=False,
         on_delete="RESTRICT",
         lectura="Una estadía se cierra con a lo sumo un check out; cada check out cierra una sola estadía."),
    dict(hijo="check_out", padre="empleado", fk="empleado_id", card="1:N", opcional=False,
         on_delete="RESTRICT",
         lectura="Un empleado atiende muchos check out; cada check out es atendido por un solo empleado."),
    dict(hijo="venta_producto", padre="estadia", fk="estadia_id", card="1:N", opcional=False,
         on_delete="CASCADE",
         lectura="Una estadía registra muchas ventas de producto; cada venta pertenece a una sola estadía."),
    dict(hijo="venta_producto", padre="producto", fk="producto_id", card="1:N", opcional=False,
         on_delete="RESTRICT",
         lectura="Un producto se vende en muchas ventas; cada venta de producto referencia un solo producto."),
    dict(hijo="venta_servicio", padre="estadia", fk="estadia_id", card="1:N", opcional=False,
         on_delete="CASCADE",
         lectura="Una estadía registra muchas ventas de servicio; cada venta pertenece a una sola estadía."),
    dict(hijo="venta_servicio", padre="servicio", fk="servicio_id", card="1:N", opcional=False,
         on_delete="RESTRICT",
         lectura="Un servicio se vende en muchas ventas; cada venta de servicio referencia un solo servicio."),

    # ------------------------------ Facturación ---------------------------
    dict(hijo="pre_factura", padre="estadia", fk="estadia_id", card="1:N", opcional=False,
         on_delete="CASCADE",
         lectura="Una estadía puede tener varias pre facturas; cada pre factura liquida una sola estadía."),
    dict(hijo="pre_factura", padre="reserva_habitacion", fk="reserva_habitacion_id", card="1:N", opcional=True,
         on_delete="RESTRICT",
         lectura="Una reserva puede aparecer en varias pre facturas; la pre factura referencia a lo sumo una reserva."),
    dict(hijo="pre_factura", padre="cliente", fk="cliente_id", card="1:N", opcional=False,
         on_delete="RESTRICT",
         lectura="Un cliente recibe muchas pre facturas; cada pre factura es de un solo cliente."),
    dict(hijo="factura", padre="cliente", fk="cliente_id", card="1:N", opcional=False,
         on_delete="RESTRICT",
         lectura="Un cliente recibe muchas facturas; cada factura se emite a un solo cliente."),
    dict(hijo="factura", padre="estadia", fk="estadia_id", card="1:1", opcional=False,
         on_delete="RESTRICT",
         lectura="Una estadía se factura a lo sumo una vez; cada factura corresponde a una sola estadía."),
    dict(hijo="detalle_compra", padre="factura", fk="factura_id", card="1:N", opcional=False,
         on_delete="CASCADE",
         lectura="Una factura contiene muchos detalles; cada detalle pertenece a una sola factura."),
    dict(hijo="detalle_compra", padre="producto", fk="producto_id", card="1:N", opcional=True,
         on_delete="RESTRICT",
         lectura="Un producto aparece en muchos detalles; el detalle referencia a lo sumo un producto."),
    dict(hijo="detalle_compra", padre="servicio", fk="servicio_id", card="1:N", opcional=True,
         on_delete="RESTRICT",
         lectura="Un servicio aparece en muchos detalles; el detalle referencia a lo sumo un servicio."),
    dict(hijo="pago_parcial", padre="reserva_habitacion", fk="reserva_habitacion_id", card="1:N", opcional=True,
         on_delete="RESTRICT",
         lectura="Una reserva recibe muchos abonos; cada abono se aplica a lo sumo a una reserva."),
    dict(hijo="pago_parcial", padre="factura", fk="factura_id", card="1:N", opcional=True,
         on_delete="RESTRICT",
         lectura="Una factura recibe muchos pagos; cada pago se aplica a lo sumo a una factura."),
    dict(hijo="pago_parcial", padre="metodo_pago", fk="metodo_pago_id", card="1:N", opcional=False,
         on_delete="RESTRICT",
         lectura="Un método de pago se usa en muchos pagos; cada pago usa un solo método de pago."),

    # ------------------------------ Inventario ----------------------------
    dict(hijo="producto", padre="proveedor", fk="proveedor_id", card="1:N", opcional=True,
         on_delete="SET NULL",
         lectura="Un proveedor abastece muchos productos; cada producto tiene a lo sumo un proveedor."),
    dict(hijo="seguimiento_producto", padre="producto", fk="producto_id", card="1:N", opcional=False,
         on_delete="CASCADE",
         lectura="Un producto registra muchos movimientos; cada movimiento afecta un solo producto."),
    dict(hijo="disponibilidad_inventario", padre="producto", fk="producto_id", card="1:N", opcional=True,
         on_delete="CASCADE",
         lectura="Un producto tiene muchos registros de disponibilidad; el registro evalúa a lo sumo un producto."),
    dict(hijo="disponibilidad_inventario", padre="servicio", fk="servicio_id", card="1:N", opcional=True,
         on_delete="CASCADE",
         lectura="Un servicio tiene muchos registros de disponibilidad; el registro evalúa a lo sumo un servicio."),

    # ----------------------------- Notificación ---------------------------
    dict(hijo="alerta", padre="cliente", fk="cliente_id", card="1:N", opcional=False,
         on_delete="CASCADE",
         lectura="Un cliente recibe muchas alertas; cada alerta se dirige a un solo cliente."),
    dict(hijo="alerta", padre="reserva_habitacion", fk="reserva_habitacion_id", card="1:N", opcional=True,
         on_delete="SET NULL",
         lectura="Una reserva puede generar muchas alertas; cada alerta se asocia a lo sumo a una reserva."),
    dict(hijo="fidelizacion_cliente", padre="cliente", fk="cliente_id", card="1:1", opcional=False,
         on_delete="CASCADE",
         lectura="Un cliente tiene a lo sumo un registro de fidelización; cada registro es de un solo cliente."),

    # ------------------------------ Seguridad -----------------------------
    dict(hijo="usuario", padre="persona", fk="persona_id", card="1:1", opcional=False,
         on_delete="RESTRICT",
         lectura="Una persona tiene a lo sumo un usuario; cada usuario pertenece a una sola persona."),
    dict(hijo="vista", padre="modulo", fk="modulo_id", card="1:N", opcional=False,
         on_delete="CASCADE",
         lectura="Un módulo agrupa muchas vistas; cada vista pertenece a un módulo propietario."),
    dict(hijo="usuario_rol", padre="usuario", fk="usuario_id", card="1:N", opcional=False,
         on_delete="CASCADE",
         lectura="Un usuario puede tener muchos roles asignados."),
    dict(hijo="usuario_rol", padre="rol", fk="rol_id", card="1:N", opcional=False,
         on_delete="CASCADE",
         lectura="Un rol puede estar asignado a muchos usuarios."),
    dict(hijo="rol_permiso", padre="rol", fk="rol_id", card="1:N", opcional=False,
         on_delete="CASCADE",
         lectura="Un rol agrupa muchos permisos."),
    dict(hijo="rol_permiso", padre="permiso", fk="permiso_id", card="1:N", opcional=False,
         on_delete="CASCADE",
         lectura="Un permiso puede pertenecer a muchos roles."),
    dict(hijo="modulo_vista", padre="modulo", fk="modulo_id", card="1:N", opcional=False,
         on_delete="CASCADE",
         lectura="Un módulo puede exponer muchas vistas reutilizadas."),
    dict(hijo="modulo_vista", padre="vista", fk="vista_id", card="1:N", opcional=False,
         on_delete="CASCADE",
         lectura="Una vista puede ser expuesta por muchos módulos."),

    # ---------------------------- Mantenimiento ---------------------------
    dict(hijo="mantenimiento_habitacion", padre="habitacion", fk="habitacion_id", card="1:N", opcional=False,
         on_delete="RESTRICT",
         lectura="Una habitación recibe muchos mantenimientos; cada mantenimiento interviene una sola habitación."),
    dict(hijo="mantenimiento_habitacion", padre="empleado", fk="empleado_id", card="1:N", opcional=True,
         on_delete="SET NULL",
         lectura="Un empleado ejecuta muchos mantenimientos; cada mantenimiento tiene a lo sumo un responsable."),
    dict(hijo="mantenimiento_uso", padre="mantenimiento_habitacion", fk="mantenimiento_habitacion_id", card="1:1",
         opcional=False, on_delete="CASCADE",
         lectura="Un mantenimiento se especializa en a lo sumo un mantenimiento por uso."),
    dict(hijo="mantenimiento_remodelacion", padre="mantenimiento_habitacion", fk="mantenimiento_habitacion_id",
         card="1:1", opcional=False, on_delete="CASCADE",
         lectura="Un mantenimiento se especializa en a lo sumo un mantenimiento por remodelación."),
    dict(hijo="dashboard_mantenimiento", padre="sede", fk="sede_id", card="1:N", opcional=False,
         on_delete="CASCADE",
         lectura="Una sede genera muchos consolidados; cada consolidado corresponde a una sola sede."),
]

# --------------------------------------------------------------------------
# Relaciones N:M resueltas mediante entidad asociativa
# --------------------------------------------------------------------------
RELACIONES_NM = [
    ("estadia", "producto", "venta_producto",
     "Una estadía consume muchos productos y un producto se consume en muchas estadías."),
    ("estadia", "servicio", "venta_servicio",
     "Una estadía consume muchos servicios y un servicio se presta en muchas estadías."),
    ("factura", "producto / servicio", "detalle_compra",
     "Una factura detalla muchos productos o servicios y un producto o servicio aparece en muchas facturas."),
    ("usuario", "rol", "usuario_rol",
     "Un usuario tiene muchos roles y un rol se asigna a muchos usuarios."),
    ("rol", "permiso", "rol_permiso",
     "Un rol agrupa muchos permisos y un permiso pertenece a muchos roles."),
    ("modulo", "vista", "modulo_vista",
     "Un módulo expone muchas vistas y una vista puede reutilizarse en muchos módulos."),
]

# --------------------------------------------------------------------------
# Especializaciones (constructos EER)
# --------------------------------------------------------------------------
ESPECIALIZACIONES = [
    dict(supertipo="mantenimiento_habitacion",
         subtipos=["mantenimiento_uso", "mantenimiento_remodelacion"],
         discriminador="tipo_mantenimiento",
         restriccion="Disyunta y total",
         nota="Toda orden de mantenimiento es de uso o de remodelación, nunca de ambos tipos a la vez. "
              "El atributo tipo_mantenimiento actúa como discriminador y se controla con una restricción CHECK."),
    dict(supertipo="persona",
         subtipos=["empleado", "usuario"],
         discriminador="(por existencia del registro especializado)",
         restriccion="Solapada y parcial",
         nota="Una persona puede ser empleado, tener usuario, ambas cosas o ninguna. Se implementa con "
              "llaves foráneas únicas (persona_id) en empleado y en usuario."),
]

# --------------------------------------------------------------------------
# Relación de auditoría (transversal, documentada y no dibujada para no saturar el EER)
# --------------------------------------------------------------------------
AUDITORIA_REL = dict(
    columnas=["created_by", "updated_by", "deleted_by"],
    padre="usuario",
    card="1:N",
    lectura="Un usuario crea, modifica o elimina lógicamente muchos registros de cualquier entidad; "
            "cada registro guarda a lo sumo un usuario por cada acción de auditoría.",
    nota="Se documenta en el modelo conceptual y se implementa como llave foránea opcional en el modelo "
         "físico (AA2). No se dibuja en el diagrama EER porque generaría 3 aristas por cada entidad "
         "(138 en total) y haría ilegible el diagrama.",
)
