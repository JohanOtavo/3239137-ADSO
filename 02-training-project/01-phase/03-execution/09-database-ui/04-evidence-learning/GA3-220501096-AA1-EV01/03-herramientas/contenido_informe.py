# -*- coding: utf-8 -*-
"""
GA3-220501096-AA1-EV01
Contenido narrativo del informe: decisiones de diseño, trazabilidad con los
requisitos, autoevaluación con la lista de chequeo del instrumento EV01 y
cuerpo del documento.
"""

FICHA = "3239137"
PROGRAMA = "Análisis y Desarrollo de Software (ADSO)"
CODIGO_PROGRAMA = "228118"
PROYECTO = "Desarrollo de software orientado a servicios"
FASE = "Ejecución"
COMPETENCIA = ("220501096 - Desarrollar la solución de software de acuerdo con el diseño y "
               "metodologías de desarrollo.")
RAP = "593107-02 - Construir la base de datos para el software a partir del modelo de datos."
ACTIVIDAD = ("GA3-220501096-AA1 - Construir el modelo conceptual de la base de datos del proyecto "
             "a partir del modelo de datos del diseño.")
EVIDENCIA = "GA3-220501096-AA1-EV01 - Modelo conceptual de la base de datos del proyecto formativo."
SISTEMA = "Sistema de hotelería"
APRENDIZ = "Johan Otavo"
INSTRUCTOR = "Jesús Ariel González Bonilla"
CENTRO = "CIES - Regional Huila"
CIUDAD = "Neiva"
FECHA = "1 de septiembre de 2026"

# --------------------------------------------------------------------------
# Decisiones de diseño
# --------------------------------------------------------------------------
DECISIONES = [
    ("Identificador subrogado único",
     "Todas las entidades usan id BIGINT UNSIGNED AUTO_INCREMENT como llave primaria.",
     "El modelo de datos del diseño dejó abierto el tipo de identificador. Se elige un entero "
     "autoincremental por su menor tamaño de índice, su rendimiento en uniones y su compatibilidad "
     "directa con las llaves foráneas. El uso de UUID (CHAR(36) o BINARY(16)) queda documentado como "
     "alternativa para un escenario de integración entre sedes o sincronización sin conexión."),
    ("Auditoría y eliminación lógica en todas las entidades",
     "Las 46 entidades incluyen created_by, created_at, updated_by, updated_at, deleted_by, "
     "deleted_at y status.",
     "Se conserva el criterio definido en 05_id_y_auditoria.md: la trazabilidad de quién hizo qué y "
     "cuándo es obligatoria en reserva, factura, pago, mantenimiento, inventario y seguridad. La "
     "eliminación lógica evita perder historial contable y operativo."),
    ("La auditoría no se dibuja en el diagrama EER",
     "created_by, updated_by y deleted_by referencian usuario.id; la relación se documenta y se "
     "implementa en el modelo físico (AA2).",
     "Dibujarla generaría tres aristas por cada entidad, es decir 138 líneas adicionales, y haría "
     "ilegible el diagrama sin aportar información nueva, porque la regla es idéntica en todas las "
     "entidades."),
    ("cliente y persona se mantienen separadas",
     "cliente conserva sus propios datos de identificación; persona es la base de identidad de "
     "empleado y usuario.",
     "cliente es un actor comercial externo que puede ser persona natural o jurídica (por eso admite "
     "NIT), mientras que persona sostiene la identidad de quienes operan el sistema. Se documenta la "
     "alternativa de convertir cliente en subtipo de persona; queda como pendiente por confirmar con "
     "el instructor, porque implicaría manejar la coincidencia entre un empleado y un cliente."),
    ("Especialización del mantenimiento",
     "mantenimiento_habitacion es el supertipo; mantenimiento_uso y mantenimiento_remodelacion son "
     "subtipos con llave foránea única y discriminador tipo_mantenimiento.",
     "Es el constructo de especialización propio del modelo EER. La especialización es disyunta y "
     "total: toda orden es de uso o de remodelación, nunca de las dos, lo que se controla con la "
     "restricción CHECK tipo_mantenimiento IN ('USO','REMODELACION')."),
    ("Relaciones uno a uno con llave foránea única",
     "estadia, check_in, check_out, cancelacion_habitacion, catalogo_habitacion, "
     "fidelizacion_cliente, empleado y usuario declaran UNIQUE sobre su llave foránea.",
     "Es la forma estándar de representar una cardinalidad 1:1 en un modelo relacional y garantiza, "
     "por ejemplo, que una reserva no pueda tener dos check in o que una estadía no se facture dos "
     "veces."),
    ("Relaciones muchos a muchos con entidad asociativa",
     "venta_producto, venta_servicio, detalle_compra, usuario_rol, rol_permiso y modulo_vista "
     "resuelven las seis relaciones N:M del modelo.",
     "El modelo relacional no admite relaciones N:M directas. Las entidades asociativas además "
     "permiten guardar atributos propios de la relación, como cantidad, valor_unitario y valor_total "
     "en las ventas."),
    ("Llaves foráneas excluyentes para producto o servicio",
     "detalle_compra y disponibilidad_inventario tienen producto_id y servicio_id opcionales con un "
     "CHECK que obliga a diligenciar exactamente uno de los dos.",
     "Conserva los atributos definidos en el modelo de datos del diseño y a la vez impide filas "
     "ambiguas (ninguno o ambos). La alternativa de crear una entidad genérica ítem se documenta "
     "para evaluarla en el modelo físico."),
    ("Se conserva modulo_vista pese a vista.modulo_id",
     "vista.modulo_id indica el módulo propietario de la vista; modulo_vista permite exponer una "
     "misma vista en varios módulos.",
     "Las dos estructuras estaban en el modelo de datos del diseño. Se documenta la redundancia "
     "parcial y su significado distinto para que, si el negocio confirma que una vista pertenece a un "
     "solo módulo, modulo_vista pueda eliminarse en el modelo físico."),
    ("estado_habitacion es entidad; los demás estados son atributos de dominio",
     "estado_habitacion se modela como entidad con permite_reserva y permite_check_in; "
     "estado_reserva, estado_estadia, estado_factura y estado_mantenimiento son atributos "
     "controlados por CHECK.",
     "El estado de la habitación gobierna reglas de negocio (si permite o no reservar y hacer check "
     "in), por lo que necesita atributos propios. Los demás estados todavía están pendientes de "
     "catálogo oficial, así que se dejan como dominio controlado y podrán promoverse a entidad "
     "cuando el negocio los confirme."),
    ("Tipos de dato monetarios exactos",
     "Todos los valores monetarios usan DECIMAL(12,2) y las cantidades usan INT.",
     "DECIMAL evita los errores de redondeo de los tipos de punto flotante, inaceptables en "
     "facturación, y 12 dígitos cubren con holgura los valores del negocio hotelero."),
    ("Política de integridad referencial diferenciada",
     "ON DELETE RESTRICT para entidades maestras, CASCADE para entidades dependientes y SET NULL "
     "para referencias opcionales.",
     "Impide borrar un cliente, una habitación o un producto que ya tiene movimiento; permite "
     "eliminar en bloque los registros que no existen sin su padre (detalles de una factura, vistas "
     "de un módulo) y conserva el registro cuando la referencia es informativa, como el empleado "
     "responsable de un mantenimiento."),
    ("El valor de la reserva se almacena",
     "reserva_habitacion.valor_estimado, y los valores unitarios de las ventas, se guardan aunque "
     "sean derivables de precio.",
     "El precio dinámico cambia con la temporada y las reglas comerciales. Guardar el valor aplicado "
     "conserva el histórico y evita que una liquidación antigua cambie cuando se actualiza una "
     "tarifa."),
    ("dashboard_mantenimiento se conserva como consolidado por fecha de corte",
     "Es un registro histórico por sede y fecha, no un cálculo en línea.",
     "Permite comparar la ocupación entre cortes sin recalcular sobre datos que ya cambiaron. Si el "
     "negocio solo requiere la foto actual, en el modelo físico puede reemplazarse por una vista SQL."),
    ("Codificación y motor de almacenamiento",
     "Esquema en utf8mb4 con collation utf8mb4_unicode_ci y tablas InnoDB.",
     "utf8mb4 soporta tildes, ñ y emojis en descripciones y mensajes de notificación; InnoDB es el "
     "único motor de MySQL que aplica integridad referencial, indispensable para las 55 llaves "
     "foráneas del modelo."),
]

# --------------------------------------------------------------------------
# Trazabilidad: funcionalidad del diseño -> entidades del modelo conceptual
# --------------------------------------------------------------------------
TRAZABILIDAD = [
    ("1. Parametrización", "Gestión de cliente", "cliente"),
    ("1. Parametrización", "Gestión de precio dinámico", "precio, tipo_dia, tipo_habitacion"),
    ("1. Parametrización", "Registro de empresa", "empresa"),
    ("1. Parametrización", "Gestión de información legal", "informacion_legal"),
    ("1. Parametrización", "Gestión de empleado", "empleado, persona"),
    ("1. Parametrización", "Gestión de tipo de día", "tipo_dia"),
    ("1. Parametrización", "Gestión de método de pago", "metodo_pago"),
    ("2. Distribución", "Gestión de sede", "sede"),
    ("2. Distribución", "Gestión de habitación", "habitacion"),
    ("2. Distribución", "Gestión de tipo de habitación", "tipo_habitacion"),
    ("2. Distribución", "Gestión de estado de habitación", "estado_habitacion"),
    ("3. Prestación de servicio", "Consulta de catálogo de habitación",
     "catalogo_habitacion, habitacion, tipo_habitacion, precio"),
    ("3. Prestación de servicio", "Consulta de disponibilidad de habitación",
     "disponibilidad_habitacion, estado_habitacion, mantenimiento_habitacion"),
    ("3. Prestación de servicio", "Gestión de reserva de habitación",
     "reserva_habitacion, cliente, habitacion"),
    ("3. Prestación de servicio", "Cancelación de reserva de habitación", "cancelacion_habitacion"),
    ("3. Prestación de servicio", "Check in", "check_in, reserva_habitacion, empleado"),
    ("3. Prestación de servicio", "Check out", "check_out, estadia, empleado"),
    ("3. Prestación de servicio", "Secuencia de estadía",
     "estadia, venta_producto, venta_servicio"),
    ("3. Prestación de servicio", "Venta de producto", "venta_producto, producto"),
    ("3. Prestación de servicio", "Venta de servicio", "venta_servicio, servicio"),
    ("4. Facturación", "Pre facturación", "pre_factura, estadia, reserva_habitacion"),
    ("4. Facturación", "Seguimiento de pago parcial", "pago_parcial, metodo_pago, factura"),
    ("4. Facturación", "Gestión de factura", "factura, cliente, estadia"),
    ("4. Facturación", "Detalle de compra", "detalle_compra, producto, servicio"),
    ("5. Inventario", "Gestión de producto", "producto"),
    ("5. Inventario", "Gestión de servicio", "servicio"),
    ("5. Inventario", "Gestión de proveedor", "proveedor"),
    ("5. Inventario", "Seguimiento de producto", "seguimiento_producto"),
    ("5. Inventario", "Consulta de disponibilidad", "disponibilidad_inventario"),
    ("6. Notificación", "Promoción", "promocion"),
    ("6. Notificación", "Alerta", "alerta, cliente, reserva_habitacion"),
    ("6. Notificación", "Término y condición", "termino_condicion"),
    ("6. Notificación", "Fidelización de cliente", "fidelizacion_cliente"),
    ("7. Seguridad", "Registro de persona", "persona"),
    ("7. Seguridad", "Registro de usuario", "usuario"),
    ("7. Seguridad", "Gestión de rol", "rol, usuario_rol"),
    ("7. Seguridad", "Gestión de permiso", "permiso, rol_permiso"),
    ("7. Seguridad", "Gestión de módulo", "modulo"),
    ("7. Seguridad", "Gestión de vista", "vista, modulo_vista"),
    ("8. Mantenimiento", "Mantenimiento de habitación por uso",
     "mantenimiento_habitacion, mantenimiento_uso"),
    ("8. Mantenimiento", "Mantenimiento por remodelación",
     "mantenimiento_habitacion, mantenimiento_remodelacion"),
    ("8. Mantenimiento", "Dashboard operativo", "dashboard_mantenimiento, sede"),
]

# --------------------------------------------------------------------------
# Autoevaluación con el instrumento Instrumento_EV01_ModeloConceptualBD.docx
# --------------------------------------------------------------------------
CHECKLIST = [
    ("1", "El modelo conceptual identifica todas las entidades del sistema definidas en el modelo "
          "de datos.", "Sí",
     "Se representan las 46 entidades de los 8 módulos definidos en "
     "03_estructura_modulo_entidad_sin_atributo.md, sin omisiones ni entidades nuevas no "
     "justificadas."),
    ("2", "Los atributos de cada entidad están correctamente definidos con sus tipos de datos.", "Sí",
     "El diccionario de datos define tipo, obligatoriedad y descripción de cada atributo, más el "
     "identificador y los siete campos de auditoría comunes."),
    ("3", "Se identifican las claves primarias para cada entidad.", "Sí",
     "Todas las entidades declaran PRIMARY KEY (id) con BIGINT UNSIGNED AUTO_INCREMENT; además se "
     "declaran las llaves candidatas como restricciones UNIQUE."),
    ("4", "Las relaciones entre entidades están correctamente definidas con sus cardinalidades.", "Sí",
     "Se documentan 55 relaciones con su cardinalidad, participación y acción referencial, seis "
     "relaciones N:M resueltas con entidad asociativa y dos especializaciones EER."),
    ("5", "El diagrama EER utiliza la notación estándar y fue elaborado con una herramienta CASE.",
     "Sí",
     "El diagrama se construye en MySQL Workbench mediante ingeniería inversa del script "
     "modelo_conceptual_hotel.sql, con notación de pata de gallo; se anexan además los diagramas por "
     "módulo y la versión Mermaid."),
    ("6", "El modelo conceptual es coherente con la especificación de requisitos del proyecto.", "Sí",
     "La matriz de trazabilidad relaciona las 42 funcionalidades del documento de diseño con las "
     "entidades que las soportan; no quedan funcionalidades sin respaldo ni entidades sin uso."),
    ("7", "Presenta el informe cumpliendo con las normas básicas de presentación de documentos.",
     "Sí",
     "El informe incluye portada institucional, tabla de contenido, numeración de secciones, "
     "figuras y tablas rotuladas, conclusiones y referencias bibliográficas."),
]

# --------------------------------------------------------------------------
# Pendientes heredados del modelo de datos del diseño
# --------------------------------------------------------------------------
PENDIENTES = [
    ("Valores oficiales de status y de los estados de reserva, estadía, factura, pago y "
     "mantenimiento.",
     "Se implementan como dominio controlado por CHECK; si el negocio define catálogos, se "
     "promueven a entidad en el modelo físico."),
    ("Reglas exactas de cálculo del precio dinámico.",
     "El modelo soporta la vigencia por tipo de habitación y tipo de día; la fórmula se resolverá "
     "en la lógica de la aplicación."),
    ("Política de cancelación, devolución y penalidad.",
     "cancelacion_habitacion ya prevé aplica_penalidad y valor_penalidad; falta la regla de cálculo."),
    ("Definir si cliente debe ser subtipo de persona.",
     "Afecta la unificación de identidades cuando un empleado también es cliente."),
    ("Confirmar si los campos de auditoría referencian usuario.id.",
     "El modelo lo asume; se implementará como llave foránea opcional en el modelo físico."),
    ("Canales de notificación y tipos de servicio que venderá el hotel.",
     "Hoy son atributos de dominio (canal) que podrán convertirse en catálogos."),
]

# --------------------------------------------------------------------------
# Cuerpo del informe. Bloques:
#   ("h1"|"h2"|"h3", texto)
#   ("p", texto) | ("ul", [items]) | ("ol", [items])
#   ("img", archivo, pie) | ("tabla", clave, titulo) | ("nota", texto)
# --------------------------------------------------------------------------
BLOQUES = [
    ("h1", "1. Introducción"),
    ("p", "Este documento presenta el modelo conceptual de la base de datos del sistema de "
          "hotelería, proyecto formativo de la ficha ADSO {ficha}. Corresponde a la evidencia "
          "GA3-220501096-AA1-EV01 de la actividad de aprendizaje GA3-220501096-AA1 y da continuidad "
          "al modelo de datos elaborado en la fase de diseño."),
    ("p", "El insumo de este trabajo son los documentos de arquitectura del modelo de datos "
          "elaborados en la semana 10 del proceso formativo: el entendimiento de la necesidad del "
          "producto, las funcionalidades posibles por módulo, la estructura de módulos y entidades, "
          "la estructura de entidades con sus atributos y la definición del identificador y de los "
          "campos de auditoría. Sobre esa base, el aporte de esta evidencia es la definición de los "
          "tipos de datos, las llaves, las restricciones de integridad y, principalmente, las "
          "relaciones y cardinalidades que aún no estaban representadas."),
    ("h1", "2. Objetivos"),
    ("h2", "2.1 Objetivo general"),
    ("p", "Construir el modelo conceptual de la base de datos del sistema de hotelería a partir del "
          "modelo de datos del diseño, representándolo en un diagrama entidad-relación extendido "
          "(EER) elaborado con una herramienta CASE."),
    ("h2", "2.2 Objetivos específicos"),
    ("ol", ["Representar las 46 entidades de los 8 módulos del sistema con sus atributos, tipos de "
            "datos y restricciones.",
            "Definir las llaves primarias, las llaves candidatas y las llaves foráneas de cada "
            "entidad.",
            "Establecer las relaciones entre entidades con su cardinalidad, su participación y su "
            "acción referencial.",
            "Resolver las relaciones muchos a muchos mediante entidades asociativas y representar "
            "las especializaciones propias del modelo EER.",
            "Validar la completitud y la coherencia del modelo frente a la especificación de "
            "requisitos del proyecto.",
            "Documentar el modelo y las decisiones de diseño como insumo del modelo físico "
            "(GA3-220501096-AA2-EV02)."]),
    ("h1", "3. Alcance"),
    ("p", "El alcance de esta evidencia es el modelo conceptual: entidades, atributos, dominios, "
          "llaves, restricciones y relaciones, expresados de forma independiente de la explotación "
          "física de la base de datos. La creación efectiva de la base de datos en el SGBD, los "
          "índices de rendimiento, las vistas, los procedimientos almacenados y las pruebas de "
          "integridad referencial corresponden a la actividad GA3-220501096-AA2."),
    ("p", "Se conservan íntegramente las entidades y los atributos definidos en el modelo de datos "
          "del diseño. No se eliminó ninguna entidad; cuando se detectó una posible redundancia se "
          "documentó como decisión de diseño para resolverla con el instructor antes de la "
          "implementación física."),
    ("h1", "4. Metodología y herramientas"),
    ("p", "El modelo se construyó en cuatro pasos. Primero se analizaron los documentos del modelo "
          "de datos del diseño para extraer entidades y atributos. Segundo, se asignó a cada "
          "atributo su tipo de dato, su obligatoriedad y su rol de llave. Tercero, se dedujeron las "
          "relaciones a partir de los atributos de referencia (los que terminan en _id), se "
          "definieron sus cardinalidades y se agregaron las relaciones que faltaban. Cuarto, se "
          "verificó el modelo contra las funcionalidades del diseño mediante una matriz de "
          "trazabilidad."),
    ("p", "El diagrama EER se construye en MySQL Workbench 8.0 mediante ingeniería inversa del "
          "script modelo_conceptual_hotel.sql que acompaña esta evidencia, siguiendo la ruta "
          "File > Import > Reverse Engineer MySQL Create Script, con la opción \"Place imported "
          "objects on a diagram\" activada. Ese procedimiento produce el diagrama EER con notación "
          "de pata de gallo, que es la notación estándar de la herramienta."),
    ("p", "Para que el modelo y su documentación no se desactualicen entre sí, las entidades y las "
          "relaciones se declararon una sola vez en los archivos modelo_datos.py y "
          "modelo_relaciones.py, y a partir de ellos se generan el script SQL, el diccionario de "
          "datos, la tabla de relaciones y los diagramas de este informe. Los mismos archivos "
          "verifican de forma automática que cada llave foránea tenga una relación declarada, que "
          "la opcionalidad coincida y que toda relación 1:1 tenga su restricción UNIQUE."),
    ("h1", "5. Estructura general del modelo"),
    ("p", "El modelo conceptual está conformado por 46 entidades organizadas en 8 módulos y "
          "relacionadas entre sí mediante 55 relaciones. La siguiente figura muestra los módulos y "
          "la cantidad de relaciones que existen entre ellos."),
    ("img", "eer_00_mapa_modulos.png",
     "Figura 1. Mapa de módulos del modelo conceptual del sistema de hotelería."),
    ("tabla", "modulos", "Tabla 1. Entidades por módulo."),
    ("p", "El núcleo del negocio se concentra en el módulo de prestación de servicio: la reserva "
          "conecta al cliente con la habitación, la estadía representa la ocupación real y sobre "
          "ella se registran los consumos que después alimentan la pre factura y la factura. Los "
          "módulos de parametrización y distribución aportan los datos maestros, mientras que "
          "seguridad, notificación y mantenimiento actúan como módulos de soporte."),
    ("h1", "6. Notación del diagrama EER"),
    ("p", "El diagrama utiliza notación de pata de gallo (crow's foot), la que emplea MySQL "
          "Workbench por defecto:"),
    ("ul", ["Cada entidad se representa como un rectángulo con su nombre en el encabezado y sus "
            "atributos en el cuerpo.",
            "PK identifica la llave primaria, FK la llave foránea y U la restricción de unicidad.",
            "El asterisco marca los atributos obligatorios (NOT NULL).",
            "La línea continua indica participación obligatoria (la llave foránea no admite nulos) "
            "y la línea punteada indica participación opcional.",
            "Los extremos de cada relación se rotulan con su cardinalidad máxima: 1 en el extremo "
            "de la entidad padre y 1 o N en el extremo de la entidad hija."]),
    ("h1", "7. Modelo conceptual por módulo"),
    ("p", "A continuación se presenta cada módulo con sus entidades, sus atributos y las relaciones "
          "internas. Las relaciones con entidades de otros módulos se listan al pie de cada figura."),
    ("modulos_detalle", None),
    ("h1", "8. Diccionario de datos"),
    ("p", "El diccionario de datos completo, con el tipo, la obligatoriedad, el rol de llave y la "
          "descripción de cada atributo de las 46 entidades, se encuentra en el archivo "
          "01-documentacion/02_diccionario_datos.md de esta evidencia y está incorporado como "
          "comentario de cada columna en el script modelo_conceptual_hotel.sql, de modo que la "
          "documentación viaja con el propio modelo dentro de la herramienta CASE."),
    ("p", "Todas las entidades comparten el identificador y los campos de auditoría descritos en la "
          "siguiente tabla."),
    ("tabla", "auditoria", "Tabla 2. Identificador y campos de auditoría comunes a las 46 entidades."),
    ("h1", "9. Relaciones y cardinalidades"),
    ("p", "El modelo declara 55 relaciones. En todas ellas la entidad hija es la que contiene la "
          "llave foránea. La cardinalidad se expresa como mínimo y máximo desde cada extremo: una "
          "relación 1 : 0..N indica que un registro padre puede tener cero o muchos hijos y que "
          "cada hijo pertenece exactamente a un padre."),
    ("tabla", "relaciones", "Tabla 3. Relaciones, cardinalidades y acciones referenciales."),
    ("h2", "9.1 Relaciones muchos a muchos"),
    ("p", "El modelo relacional no admite relaciones muchos a muchos directas, por lo que las seis "
          "identificadas se resuelven con entidades asociativas que, además, guardan los atributos "
          "propios de la relación."),
    ("tabla", "nm", "Tabla 4. Relaciones muchos a muchos y sus entidades asociativas."),
    ("h2", "9.2 Especializaciones"),
    ("p", "El modelo extendido (EER) permite representar la relación de supertipo y subtipo. El "
          "modelo del sistema de hotelería contiene dos especializaciones."),
    ("tabla", "especializaciones", "Tabla 5. Especializaciones del modelo EER."),
    ("h2", "9.3 Relación transversal de auditoría"),
    ("p", "Los campos created_by, updated_by y deleted_by de cada entidad referencian a usuario.id. "
          "Un usuario crea, modifica o elimina lógicamente muchos registros de cualquier entidad, y "
          "cada registro conserva a lo sumo un usuario por cada acción. La relación se documenta "
          "aquí y se implementa en el modelo físico; no se dibuja en el diagrama porque agregaría "
          "138 líneas que repiten la misma regla."),
    ("h1", "10. Restricciones de integridad"),
    ("h2", "10.1 Integridad de entidad"),
    ("p", "Las 46 entidades declaran una llave primaria simple sobre el atributo id, que no admite "
          "nulos y es única por definición. Adicionalmente se declaran las llaves candidatas del "
          "negocio como restricciones UNIQUE: el documento de identidad de cliente y de persona, el "
          "NIT de empresa y de proveedor, el número de factura, el nombre de usuario, el número de "
          "habitación dentro de su sede y los pares de las entidades asociativas."),
    ("h2", "10.2 Integridad referencial"),
    ("p", "Las 55 llaves foráneas referencian siempre la llave primaria de la entidad padre. Se "
          "definió una política diferenciada de acciones referenciales: RESTRICT para impedir el "
          "borrado de datos maestros con movimiento, CASCADE para los registros que no tienen "
          "sentido sin su padre y SET NULL para las referencias informativas opcionales."),
    ("h2", "10.3 Integridad de dominio"),
    ("p", "Cada atributo tiene un tipo de dato acotado a su significado: cadenas de longitud "
          "limitada para nombres e identificaciones, DATE para fechas de calendario, DATETIME para "
          "marcas de tiempo, BOOLEAN para indicadores, INT para cantidades y DECIMAL(12,2) para "
          "valores monetarios. La obligatoriedad se controla con NOT NULL y las reglas de negocio "
          "con restricciones CHECK."),
    ("tabla", "checks", "Tabla 6. Restricciones CHECK definidas en el modelo."),
    ("h1", "11. Decisiones de diseño"),
    ("p", "Las siguientes decisiones explican por qué el modelo quedó como quedó, especialmente en "
          "los puntos que el modelo de datos del diseño dejó abiertos."),
    ("tabla", "decisiones", "Tabla 7. Decisiones de diseño y su justificación."),
    ("h1", "12. Validación del modelo frente a los requisitos"),
    ("p", "La validación se hizo en dos sentidos. Del requisito hacia el modelo, verificando que "
          "cada funcionalidad definida en el documento de diseño tenga entidades que la soporten. "
          "Del modelo hacia el requisito, verificando que no existan entidades sin una funcionalidad "
          "asociada. Las 42 funcionalidades quedaron cubiertas y las 46 entidades quedaron "
          "utilizadas."),
    ("tabla", "trazabilidad", "Tabla 8. Matriz de trazabilidad entre funcionalidades y entidades."),
    ("h2", "12.1 Autoevaluación con la lista de chequeo del instrumento EV01"),
    ("tabla", "checklist", "Tabla 9. Autoevaluación con el instrumento Instrumento_EV01_ModeloConceptualBD."),
    ("h1", "13. Pendientes por confirmar"),
    ("p", "Estos puntos vienen señalados como pendientes en el modelo de datos del diseño. El "
          "modelo conceptual los resuelve de forma provisional para no bloquear el avance y deja "
          "documentada la alternativa."),
    ("tabla", "pendientes", "Tabla 10. Pendientes por confirmar y tratamiento provisional."),
    ("h1", "14. Próximo paso: modelo físico (GA3-220501096-AA2)"),
    ("p", "El script modelo_conceptual_hotel.sql es el punto de partida del modelo físico. Para la "
          "actividad AA2 se deberá seleccionar el SGBD objetivo, ajustar los tipos de datos nativos, "
          "agregar los índices que requieran las consultas frecuentes (disponibilidad por fechas, "
          "búsqueda de reservas por cliente, movimientos por producto), implementar las llaves "
          "foráneas de auditoría, elaborar los scripts DML de datos de prueba y ejecutar las pruebas "
          "de integridad referencial."),
    ("h1", "15. Conclusiones"),
    ("ol", ["El modelo conceptual representa la totalidad del modelo de datos del diseño: 46 "
            "entidades en 8 módulos, con sus atributos, tipos de datos, llaves y restricciones.",
            "El aporte principal de esta evidencia son las 55 relaciones con sus cardinalidades, "
            "las seis relaciones muchos a muchos resueltas con entidades asociativas y las dos "
            "especializaciones EER, que no estaban representadas en el modelo de datos de partida.",
            "La matriz de trazabilidad demuestra que el modelo es coherente con la especificación "
            "de requisitos: las 42 funcionalidades tienen entidades que las soportan y no hay "
            "entidades sin uso.",
            "Generar el script SQL, el diccionario de datos y los diagramas desde una sola "
            "declaración del modelo evita que la documentación y el diagrama se contradigan, y "
            "permite verificar automáticamente la consistencia entre llaves foráneas, "
            "cardinalidades y restricciones.",
            "El modelo queda listo para transformarse en modelo físico en la actividad "
            "GA3-220501096-AA2, con seis puntos pendientes de confirmación del negocio que ya "
            "tienen un tratamiento provisional documentado."]),
    ("h1", "16. Referencias"),
    ("ul", ["Date, C. J. (2019). Database design and relational theory (2nd ed.). O'Reilly Media.",
            "Elmasri, R., & Navathe, S. B. (2020). Fundamentals of database systems (8th ed.). "
            "Pearson.",
            "IEEE Std 1016-2009. IEEE standard for software design descriptions. IEEE Computer "
            "Society.",
            "ISO/IEC 9075-1:2023. Information technology - Database languages - SQL. ISO.",
            "Oracle Corporation. (2024). MySQL Workbench manual: Reverse engineering a create "
            "script.",
            "Servicio Nacional de Aprendizaje (SENA). (2026). Guía de aprendizaje: construcción de "
            "la base de datos. Programa Análisis y Desarrollo de Software.",
            "Silberschatz, A., Korth, H. F., & Sudarshan, S. (2024). Database system concepts "
            "(8th ed.). McGraw-Hill."]),
    ("h1", "Anexo A. Contenido de la evidencia"),
    ("tabla", "anexos", "Tabla 11. Archivos que componen la evidencia GA3-220501096-AA1-EV01."),
]

MODULO_DESC = {
    "parametrizacion":
        "Contiene los datos maestros que sostienen la operación diaria: cliente, empresa y su "
        "información legal, empleado, método de pago y las dos entidades que soportan el precio "
        "dinámico, tipo_dia y precio. La entidad precio depende simultáneamente de tipo_habitacion y "
        "de tipo_dia, y maneja vigencia por fechas, de modo que una misma categoría de habitación "
        "puede tener tarifas distintas en temporada alta, en día feriado o en día ordinario. "
        "empleado es un subtipo de persona, entidad que vive en el módulo de seguridad.",
    "distribucion":
        "Representa la estructura física y comercial del hotel. Una empresa opera muchas sedes y "
        "cada sede contiene muchas habitaciones. Cada habitación se clasifica por su tipo, que "
        "define capacidades y tarifas, y tiene un estado operativo que gobierna dos reglas de "
        "negocio: si la habitación puede reservarse y si permite check in. El número de habitación "
        "es único dentro de su sede, no en todo el sistema.",
    "prestacion":
        "Es el núcleo del negocio. La reserva conecta cliente y habitación en un rango de fechas; de "
        "ella se derivan, con cardinalidad uno a uno, la cancelación, el check in y la estadía. La "
        "estadía representa la ocupación real y agrupa los consumos: venta_producto y venta_servicio "
        "son entidades asociativas que resuelven las relaciones muchos a muchos entre la estadía y "
        "el inventario. El check out cierra la estadía y liquida su valor. El catálogo y la "
        "disponibilidad describen la habitación para la consulta comercial.",
    "facturacion":
        "Consolida los valores generados por la estadía. La pre factura es la vista anticipada, la "
        "factura es el documento definitivo (una por estadía, con número único) y el detalle_compra "
        "es la línea de la factura, que apunta a un producto o a un servicio pero nunca a los dos. "
        "pago_parcial permite abonar tanto sobre la reserva, antes de la llegada, como sobre la "
        "factura, y exige que al menos una de las dos referencias esté diligenciada.",
    "inventario":
        "Controla lo que el hotel vende durante la estadía. El producto tiene existencias, proveedor "
        "y movimientos registrados en seguimiento_producto; el servicio no tiene existencia física. "
        "disponibilidad_inventario responde la pregunta de si un producto o un servicio puede "
        "venderse, con la misma regla de exclusión que el detalle de compra.",
    "notificacion":
        "Agrupa la comunicación con el cliente: promoción comercial, alerta operativa asociada "
        "opcionalmente a una reserva, términos y condiciones versionados y el programa de "
        "fidelización, que mantiene un único registro de puntos y nivel por cliente.",
    "seguridad":
        "Controla el acceso al sistema. persona es la base de identidad y tiene a lo sumo un "
        "usuario; el usuario recibe roles y el rol agrupa permisos, dos relaciones muchos a muchos "
        "resueltas con usuario_rol y rol_permiso. El módulo organiza las vistas de la aplicación: "
        "vista.modulo_id indica el módulo propietario y modulo_vista permite reutilizar una vista en "
        "varios módulos.",
    "mantenimiento":
        "Controla la disponibilidad operativa de las habitaciones. mantenimiento_habitacion es el "
        "supertipo de la especialización EER, con el discriminador tipo_mantenimiento y dos "
        "subtipos: mantenimiento_uso y mantenimiento_remodelacion. dashboard_mantenimiento guarda el "
        "consolidado por sede y fecha de corte que alimenta el tablero operativo.",
}

ANEXOS = [
    ("GA3-220501096-AA1-EV01_Modelo_Conceptual_BD.docx", "Este informe."),
    ("02-diagrama-eer/modelo_conceptual_hotel.sql",
     "Script de creación del modelo para ingeniería inversa en la herramienta CASE. Genera el "
     "diagrama EER en MySQL Workbench."),
    ("02-diagrama-eer/diagrama_eer.md",
     "Diagrama EER general y por módulo en notación Mermaid, renderizable en GitHub."),
    ("02-diagrama-eer/img/", "Diagramas del mapa de módulos y de cada uno de los 8 módulos."),
    ("01-documentacion/01_modelo_conceptual.md", "Versión en Markdown de este informe."),
    ("01-documentacion/02_diccionario_datos.md", "Diccionario de datos completo."),
    ("01-documentacion/03_relaciones_cardinalidades.md",
     "Tabla de relaciones, cardinalidades, relaciones N:M y especializaciones."),
    ("01-documentacion/04_trazabilidad_y_validacion.md",
     "Matriz de trazabilidad y autoevaluación con la lista de chequeo."),
    ("03-herramientas/", "Declaración del modelo y generadores de la evidencia."),
]
