-- =====================================================================
-- GA3-220501096-AA1-EV01 - Modelo conceptual de la base de datos
-- Proyecto formativo: sistema de hotelería - Ficha ADSO 3239137
--
-- Este script es el insumo para construir el diagrama EER en la
-- herramienta CASE. En MySQL Workbench:
--   File > Import > Reverse Engineer MySQL Create Script...
--   seleccionar este archivo y marcar "Place imported objects on a diagram".
--
-- Entidades: 46 | Relaciones (llaves foráneas): 55
-- Convenciones: identificador y campos de auditoría comunes a todas las
-- entidades, según 05_id_y_auditoria.md del modelo de datos del diseño.
-- =====================================================================

DROP SCHEMA IF EXISTS `sistema_hotelero`;
CREATE SCHEMA `sistema_hotelero` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `sistema_hotelero`;

-- ---------------------------------------------------------------------
-- Entidad: cliente   [módulo 1. Parametrización]
-- Persona natural o jurídica que reserva habitaciones y consume productos y servicios del hotel.
-- ---------------------------------------------------------------------
CREATE TABLE `cliente` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `tipo_documento` VARCHAR(20) NOT NULL COMMENT 'Tipo de documento de identidad (CC, CE, NIT, PAS).',
  `numero_documento` VARCHAR(30) NOT NULL COMMENT 'Número de documento; junto con el tipo identifica al cliente.',
  `nombre` VARCHAR(100) NOT NULL COMMENT 'Nombres del cliente.',
  `apellido` VARCHAR(100) NOT NULL COMMENT 'Apellidos del cliente.',
  `telefono` VARCHAR(30) NULL COMMENT 'Teléfono de contacto.',
  `correo` VARCHAR(150) NULL COMMENT 'Correo electrónico de contacto.',
  `direccion` VARCHAR(200) NULL COMMENT 'Dirección de residencia o facturación.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `uk_cliente_correo` UNIQUE (`correo`),
  CONSTRAINT `uk_cliente_1` UNIQUE (`tipo_documento`, `numero_documento`)
) ENGINE = InnoDB COMMENT = 'Persona natural o jurídica que reserva habitaciones y consume productos y servicios del hotel.';

-- ---------------------------------------------------------------------
-- Entidad: empresa   [módulo 1. Parametrización]
-- Empresa hotelera propietaria de las sedes; raíz de la información corporativa y legal.
-- ---------------------------------------------------------------------
CREATE TABLE `empresa` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `nombre` VARCHAR(150) NOT NULL COMMENT 'Nombre comercial de la empresa.',
  `nit` VARCHAR(20) NOT NULL COMMENT 'Número de identificación tributaria.',
  `razon_social` VARCHAR(200) NOT NULL COMMENT 'Razón social registrada.',
  `telefono` VARCHAR(30) NULL COMMENT 'Teléfono corporativo.',
  `correo` VARCHAR(150) NULL COMMENT 'Correo corporativo.',
  `direccion` VARCHAR(200) NULL COMMENT 'Dirección de la sede principal.',
  `sitio_web` VARCHAR(150) NULL COMMENT 'Sitio web institucional.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `uk_empresa_nit` UNIQUE (`nit`)
) ENGINE = InnoDB COMMENT = 'Empresa hotelera propietaria de las sedes; raíz de la información corporativa y legal.';

-- ---------------------------------------------------------------------
-- Entidad: informacion_legal   [módulo 1. Parametrización]
-- Documentos legales asociados a la empresa (cámara de comercio, RUT, licencias, pólizas).
-- ---------------------------------------------------------------------
CREATE TABLE `informacion_legal` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `empresa_id` BIGINT UNSIGNED NOT NULL COMMENT 'Empresa propietaria del documento.',
  `tipo_documento_legal` VARCHAR(50) NOT NULL COMMENT 'Tipo de documento legal.',
  `numero_documento_legal` VARCHAR(50) NOT NULL COMMENT 'Número o radicado del documento.',
  `descripcion` VARCHAR(255) NULL COMMENT 'Descripción del documento.',
  `fecha_expedicion` DATE NOT NULL COMMENT 'Fecha de expedición.',
  `fecha_vencimiento` DATE NULL COMMENT 'Fecha de vencimiento (nula = sin vencimiento).',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `uk_informacion_legal_1` UNIQUE (`empresa_id`, `tipo_documento_legal`, `numero_documento_legal`),
  CONSTRAINT `fk_informacion_legal_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB COMMENT = 'Documentos legales asociados a la empresa (cámara de comercio, RUT, licencias, pólizas).';

-- ---------------------------------------------------------------------
-- Entidad: tipo_dia   [módulo 1. Parametrización]
-- Clasificación del día (ordinario, temporada, feriado, evento especial) que condiciona la tarifa.
-- ---------------------------------------------------------------------
CREATE TABLE `tipo_dia` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `nombre` VARCHAR(60) NOT NULL COMMENT 'Nombre del tipo de día.',
  `descripcion` VARCHAR(255) NULL COMMENT 'Descripción del tipo de día.',
  `fecha` DATE NULL COMMENT 'Fecha puntual cuando el tipo de día es específico.',
  `aplica_temporada` BOOLEAN NOT NULL COMMENT 'Indica si corresponde a temporada alta.',
  `aplica_feriado` BOOLEAN NOT NULL COMMENT 'Indica si corresponde a día feriado.',
  `aplica_especial` BOOLEAN NOT NULL COMMENT 'Indica si corresponde a evento especial.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `uk_tipo_dia_nombre` UNIQUE (`nombre`)
) ENGINE = InnoDB COMMENT = 'Clasificación del día (ordinario, temporada, feriado, evento especial) que condiciona la tarifa.';

-- ---------------------------------------------------------------------
-- Entidad: metodo_pago   [módulo 1. Parametrización]
-- Medio de pago aceptado por el hotel (efectivo, tarjeta, transferencia).
-- ---------------------------------------------------------------------
CREATE TABLE `metodo_pago` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `nombre` VARCHAR(60) NOT NULL COMMENT 'Nombre del método de pago.',
  `descripcion` VARCHAR(255) NULL COMMENT 'Descripción del método de pago.',
  `requiere_referencia` BOOLEAN NOT NULL COMMENT 'Indica si exige número de referencia o autorización.',
  `permite_pago_parcial` BOOLEAN NOT NULL COMMENT 'Indica si admite abonos o pagos parciales.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `uk_metodo_pago_nombre` UNIQUE (`nombre`)
) ENGINE = InnoDB COMMENT = 'Medio de pago aceptado por el hotel (efectivo, tarjeta, transferencia).';

-- ---------------------------------------------------------------------
-- Entidad: sede   [módulo 2. Distribución]
-- Establecimiento físico de la empresa hotelera donde se ubican las habitaciones.
-- ---------------------------------------------------------------------
CREATE TABLE `sede` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `empresa_id` BIGINT UNSIGNED NOT NULL COMMENT 'Empresa a la que pertenece la sede.',
  `nombre` VARCHAR(120) NOT NULL COMMENT 'Nombre de la sede.',
  `direccion` VARCHAR(200) NOT NULL COMMENT 'Dirección de la sede.',
  `ciudad` VARCHAR(80) NOT NULL COMMENT 'Ciudad donde opera la sede.',
  `telefono` VARCHAR(30) NULL COMMENT 'Teléfono de la sede.',
  `correo` VARCHAR(150) NULL COMMENT 'Correo de la sede.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `uk_sede_1` UNIQUE (`empresa_id`, `nombre`),
  CONSTRAINT `fk_sede_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB COMMENT = 'Establecimiento físico de la empresa hotelera donde se ubican las habitaciones.';

-- ---------------------------------------------------------------------
-- Entidad: tipo_habitacion   [módulo 2. Distribución]
-- Categoría comercial de habitación (sencilla, doble, suite) con su capacidad.
-- ---------------------------------------------------------------------
CREATE TABLE `tipo_habitacion` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `nombre` VARCHAR(60) NOT NULL COMMENT 'Nombre del tipo de habitación.',
  `descripcion` VARCHAR(255) NULL COMMENT 'Descripción de la categoría.',
  `capacidad_base` INT NOT NULL COMMENT 'Cantidad de huéspedes incluida en la tarifa base.',
  `capacidad_maxima` INT NOT NULL COMMENT 'Cantidad máxima de huéspedes permitida.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `uk_tipo_habitacion_nombre` UNIQUE (`nombre`),
  CONSTRAINT `ck_tipo_habitacion_1` CHECK (capacidad_base > 0),
  CONSTRAINT `ck_tipo_habitacion_2` CHECK (capacidad_maxima >= capacidad_base)
) ENGINE = InnoDB COMMENT = 'Categoría comercial de habitación (sencilla, doble, suite) con su capacidad.';

-- ---------------------------------------------------------------------
-- Entidad: estado_habitacion   [módulo 2. Distribución]
-- Estado operativo de la habitación (disponible, ocupada, reservada, limpieza, bloqueada, mantenimiento).
-- ---------------------------------------------------------------------
CREATE TABLE `estado_habitacion` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `nombre` VARCHAR(40) NOT NULL COMMENT 'Nombre del estado.',
  `descripcion` VARCHAR(255) NULL COMMENT 'Descripción del estado.',
  `permite_reserva` BOOLEAN NOT NULL COMMENT 'Indica si en este estado la habitación puede reservarse.',
  `permite_check_in` BOOLEAN NOT NULL COMMENT 'Indica si en este estado se permite el check in.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `uk_estado_habitacion_nombre` UNIQUE (`nombre`)
) ENGINE = InnoDB COMMENT = 'Estado operativo de la habitación (disponible, ocupada, reservada, limpieza, bloqueada, mantenimiento).';

-- ---------------------------------------------------------------------
-- Entidad: habitacion   [módulo 2. Distribución]
-- Unidad física alojable dentro de una sede; núcleo de la disponibilidad del negocio.
-- ---------------------------------------------------------------------
CREATE TABLE `habitacion` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `sede_id` BIGINT UNSIGNED NOT NULL COMMENT 'Sede donde se ubica la habitación.',
  `tipo_habitacion_id` BIGINT UNSIGNED NOT NULL COMMENT 'Categoría comercial de la habitación.',
  `estado_habitacion_id` BIGINT UNSIGNED NOT NULL COMMENT 'Estado operativo actual.',
  `numero` VARCHAR(10) NOT NULL COMMENT 'Número o identificador visible de la habitación.',
  `piso` INT NOT NULL COMMENT 'Piso en el que se encuentra.',
  `capacidad` INT NOT NULL COMMENT 'Capacidad real de huéspedes.',
  `descripcion` VARCHAR(255) NULL COMMENT 'Observaciones o dotación de la habitación.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `uk_habitacion_1` UNIQUE (`sede_id`, `numero`),
  CONSTRAINT `ck_habitacion_1` CHECK (capacidad > 0),
  CONSTRAINT `fk_habitacion_sede` FOREIGN KEY (`sede_id`) REFERENCES `sede` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_habitacion_tipo_habitacion` FOREIGN KEY (`tipo_habitacion_id`) REFERENCES `tipo_habitacion` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_habitacion_estado_habitacion` FOREIGN KEY (`estado_habitacion_id`) REFERENCES `estado_habitacion` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB COMMENT = 'Unidad física alojable dentro de una sede; núcleo de la disponibilidad del negocio.';

-- ---------------------------------------------------------------------
-- Entidad: reserva_habitacion   [módulo 3. Prestación de servicio]
-- Solicitud de alojamiento de un cliente sobre una habitación en un rango de fechas.
-- ---------------------------------------------------------------------
CREATE TABLE `reserva_habitacion` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `cliente_id` BIGINT UNSIGNED NOT NULL COMMENT 'Cliente que realiza la reserva.',
  `habitacion_id` BIGINT UNSIGNED NOT NULL COMMENT 'Habitación reservada.',
  `fecha_inicio` DATE NOT NULL COMMENT 'Fecha de inicio del alojamiento.',
  `fecha_fin` DATE NOT NULL COMMENT 'Fecha de finalización del alojamiento.',
  `cantidad_persona` INT NOT NULL COMMENT 'Número de huéspedes.',
  `estado_reserva` VARCHAR(30) NOT NULL COMMENT 'Estado de la reserva (PENDIENTE, CONFIRMADA, CANCELADA, CUMPLIDA).',
  `valor_estimado` DECIMAL(12,2) NULL COMMENT 'Valor estimado calculado con la regla de precio.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `ck_reserva_habitacion_1` CHECK (fecha_fin >= fecha_inicio),
  CONSTRAINT `ck_reserva_habitacion_2` CHECK (cantidad_persona > 0),
  CONSTRAINT `fk_reserva_habitacion_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `cliente` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_reserva_habitacion_habitacion` FOREIGN KEY (`habitacion_id`) REFERENCES `habitacion` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB COMMENT = 'Solicitud de alojamiento de un cliente sobre una habitación en un rango de fechas.';

-- ---------------------------------------------------------------------
-- Entidad: cancelacion_habitacion   [módulo 3. Prestación de servicio]
-- Registro de la anulación de una reserva, con motivo y penalidad aplicada.
-- ---------------------------------------------------------------------
CREATE TABLE `cancelacion_habitacion` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `reserva_habitacion_id` BIGINT UNSIGNED NOT NULL COMMENT 'Reserva que se cancela.',
  `motivo` VARCHAR(255) NOT NULL COMMENT 'Motivo de la cancelación.',
  `fecha_cancelacion` DATETIME NOT NULL COMMENT 'Fecha y hora de la cancelación.',
  `aplica_penalidad` BOOLEAN NOT NULL COMMENT 'Indica si se cobra penalidad.',
  `valor_penalidad` DECIMAL(12,2) NULL COMMENT 'Valor de la penalidad aplicada.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `uk_cancelacion_habitacion_reserva_habitacion_id` UNIQUE (`reserva_habitacion_id`),
  CONSTRAINT `ck_cancelacion_habitacion_1` CHECK (valor_penalidad IS NULL OR valor_penalidad >= 0),
  CONSTRAINT `fk_cancelacion_habitacion_reserva_habitacion` FOREIGN KEY (`reserva_habitacion_id`) REFERENCES `reserva_habitacion` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB COMMENT = 'Registro de la anulación de una reserva, con motivo y penalidad aplicada.';

-- ---------------------------------------------------------------------
-- Entidad: disponibilidad_habitacion   [módulo 3. Prestación de servicio]
-- Ventana de tiempo en la que una habitación está o no disponible para reservar.
-- ---------------------------------------------------------------------
CREATE TABLE `disponibilidad_habitacion` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `habitacion_id` BIGINT UNSIGNED NOT NULL COMMENT 'Habitación evaluada.',
  `fecha_inicio` DATE NOT NULL COMMENT 'Inicio del periodo evaluado.',
  `fecha_fin` DATE NOT NULL COMMENT 'Fin del periodo evaluado.',
  `disponible` BOOLEAN NOT NULL COMMENT 'Indica si la habitación está disponible en el periodo.',
  `motivo_no_disponible` VARCHAR(255) NULL COMMENT 'Causa de la no disponibilidad.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `ck_disponibilidad_habitacion_1` CHECK (fecha_fin >= fecha_inicio),
  CONSTRAINT `fk_disponibilidad_habitacion_habitacion` FOREIGN KEY (`habitacion_id`) REFERENCES `habitacion` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB COMMENT = 'Ventana de tiempo en la que una habitación está o no disponible para reservar.';

-- ---------------------------------------------------------------------
-- Entidad: catalogo_habitacion   [módulo 3. Prestación de servicio]
-- Ficha comercial publicada de la habitación para consulta del cliente.
-- ---------------------------------------------------------------------
CREATE TABLE `catalogo_habitacion` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `habitacion_id` BIGINT UNSIGNED NOT NULL COMMENT 'Habitación publicada en el catálogo.',
  `titulo` VARCHAR(120) NOT NULL COMMENT 'Título comercial.',
  `descripcion` TEXT NULL COMMENT 'Descripción comercial extendida.',
  `precio_base` DECIMAL(12,2) NOT NULL COMMENT 'Precio base de referencia mostrado.',
  `visible` BOOLEAN NOT NULL COMMENT 'Indica si la ficha se muestra al público.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `uk_catalogo_habitacion_habitacion_id` UNIQUE (`habitacion_id`),
  CONSTRAINT `ck_catalogo_habitacion_1` CHECK (precio_base >= 0),
  CONSTRAINT `fk_catalogo_habitacion_habitacion` FOREIGN KEY (`habitacion_id`) REFERENCES `habitacion` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB COMMENT = 'Ficha comercial publicada de la habitación para consulta del cliente.';

-- ---------------------------------------------------------------------
-- Entidad: estadia   [módulo 3. Prestación de servicio]
-- Ocupación real de la habitación por el cliente; agrupa los consumos del periodo.
-- ---------------------------------------------------------------------
CREATE TABLE `estadia` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `reserva_habitacion_id` BIGINT UNSIGNED NOT NULL COMMENT 'Reserva que dio origen a la estadía.',
  `cliente_id` BIGINT UNSIGNED NOT NULL COMMENT 'Cliente hospedado.',
  `habitacion_id` BIGINT UNSIGNED NOT NULL COMMENT 'Habitación ocupada.',
  `fecha_inicio` DATETIME NOT NULL COMMENT 'Inicio real de la estadía.',
  `fecha_fin` DATETIME NULL COMMENT 'Fin real de la estadía (nulo = en curso).',
  `estado_estadia` VARCHAR(30) NOT NULL COMMENT 'Estado de la estadía (EN_CURSO, FINALIZADA).',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `uk_estadia_reserva_habitacion_id` UNIQUE (`reserva_habitacion_id`),
  CONSTRAINT `fk_estadia_reserva_habitacion` FOREIGN KEY (`reserva_habitacion_id`) REFERENCES `reserva_habitacion` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_estadia_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `cliente` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_estadia_habitacion` FOREIGN KEY (`habitacion_id`) REFERENCES `habitacion` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB COMMENT = 'Ocupación real de la habitación por el cliente; agrupa los consumos del periodo.';

-- ---------------------------------------------------------------------
-- Entidad: pre_factura   [módulo 4. Facturación]
-- Consolidado anticipado de valores de la estadía antes de emitir la factura definitiva.
-- ---------------------------------------------------------------------
CREATE TABLE `pre_factura` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `estadia_id` BIGINT UNSIGNED NOT NULL COMMENT 'Estadía liquidada de forma preliminar.',
  `reserva_habitacion_id` BIGINT UNSIGNED NULL COMMENT 'Reserva asociada a la pre factura.',
  `cliente_id` BIGINT UNSIGNED NOT NULL COMMENT 'Cliente responsable del pago.',
  `subtotal` DECIMAL(12,2) NOT NULL COMMENT 'Suma de alojamiento y consumos antes de impuestos.',
  `impuesto` DECIMAL(12,2) NOT NULL COMMENT 'Valor de impuestos calculados.',
  `descuento` DECIMAL(12,2) NOT NULL COMMENT 'Valor de descuentos aplicados.',
  `total` DECIMAL(12,2) NOT NULL COMMENT 'Valor total preliminar.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `ck_pre_factura_1` CHECK (subtotal >= 0),
  CONSTRAINT `ck_pre_factura_2` CHECK (total >= 0),
  CONSTRAINT `fk_pre_factura_estadia` FOREIGN KEY (`estadia_id`) REFERENCES `estadia` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pre_factura_reserva_habitacion` FOREIGN KEY (`reserva_habitacion_id`) REFERENCES `reserva_habitacion` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_pre_factura_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `cliente` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB COMMENT = 'Consolidado anticipado de valores de la estadía antes de emitir la factura definitiva.';

-- ---------------------------------------------------------------------
-- Entidad: factura   [módulo 4. Facturación]
-- Documento definitivo de cobro emitido al cliente al cierre de la estadía.
-- ---------------------------------------------------------------------
CREATE TABLE `factura` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `cliente_id` BIGINT UNSIGNED NOT NULL COMMENT 'Cliente facturado.',
  `estadia_id` BIGINT UNSIGNED NOT NULL COMMENT 'Estadía facturada.',
  `numero_factura` VARCHAR(30) NOT NULL COMMENT 'Número consecutivo de la factura.',
  `fecha_emision` DATETIME NOT NULL COMMENT 'Fecha y hora de emisión.',
  `subtotal` DECIMAL(12,2) NOT NULL COMMENT 'Subtotal antes de impuestos.',
  `impuesto` DECIMAL(12,2) NOT NULL COMMENT 'Valor de impuestos.',
  `descuento` DECIMAL(12,2) NOT NULL COMMENT 'Valor de descuentos.',
  `total` DECIMAL(12,2) NOT NULL COMMENT 'Valor total a pagar.',
  `estado_factura` VARCHAR(30) NOT NULL COMMENT 'Estado (EMITIDA, PAGADA, ANULADA).',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `uk_factura_estadia_id` UNIQUE (`estadia_id`),
  CONSTRAINT `uk_factura_numero_factura` UNIQUE (`numero_factura`),
  CONSTRAINT `ck_factura_1` CHECK (subtotal >= 0),
  CONSTRAINT `ck_factura_2` CHECK (total >= 0),
  CONSTRAINT `fk_factura_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `cliente` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_factura_estadia` FOREIGN KEY (`estadia_id`) REFERENCES `estadia` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB COMMENT = 'Documento definitivo de cobro emitido al cliente al cierre de la estadía.';

-- ---------------------------------------------------------------------
-- Entidad: pago_parcial   [módulo 4. Facturación]
-- Abono o pago aplicado a una reserva o a una factura mediante un método de pago.
-- ---------------------------------------------------------------------
CREATE TABLE `pago_parcial` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `reserva_habitacion_id` BIGINT UNSIGNED NULL COMMENT 'Reserva a la que se abona (anticipos).',
  `factura_id` BIGINT UNSIGNED NULL COMMENT 'Factura a la que se aplica el pago.',
  `metodo_pago_id` BIGINT UNSIGNED NOT NULL COMMENT 'Método de pago utilizado.',
  `valor` DECIMAL(12,2) NOT NULL COMMENT 'Valor pagado.',
  `fecha_pago` DATETIME NOT NULL COMMENT 'Fecha y hora del pago.',
  `referencia_pago` VARCHAR(60) NULL COMMENT 'Referencia o autorización del pago.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `ck_pago_parcial_1` CHECK (valor > 0),
  CONSTRAINT `ck_pago_parcial_2` CHECK ((reserva_habitacion_id IS NOT NULL OR factura_id IS NOT NULL)),
  CONSTRAINT `fk_pago_parcial_reserva_habitacion` FOREIGN KEY (`reserva_habitacion_id`) REFERENCES `reserva_habitacion` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_pago_parcial_factura` FOREIGN KEY (`factura_id`) REFERENCES `factura` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_pago_parcial_metodo_pago` FOREIGN KEY (`metodo_pago_id`) REFERENCES `metodo_pago` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB COMMENT = 'Abono o pago aplicado a una reserva o a una factura mediante un método de pago.';

-- ---------------------------------------------------------------------
-- Entidad: proveedor   [módulo 5. Inventario]
-- Tercero que abastece los productos comercializados por el hotel.
-- ---------------------------------------------------------------------
CREATE TABLE `proveedor` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `nombre` VARCHAR(150) NOT NULL COMMENT 'Nombre del proveedor.',
  `nit` VARCHAR(20) NOT NULL COMMENT 'Identificación tributaria del proveedor.',
  `telefono` VARCHAR(30) NULL COMMENT 'Teléfono de contacto.',
  `correo` VARCHAR(150) NULL COMMENT 'Correo de contacto.',
  `direccion` VARCHAR(200) NULL COMMENT 'Dirección del proveedor.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `uk_proveedor_nit` UNIQUE (`nit`)
) ENGINE = InnoDB COMMENT = 'Tercero que abastece los productos comercializados por el hotel.';

-- ---------------------------------------------------------------------
-- Entidad: producto   [módulo 5. Inventario]
-- Bien físico con existencias que el hotel vende durante la estadía.
-- ---------------------------------------------------------------------
CREATE TABLE `producto` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `proveedor_id` BIGINT UNSIGNED NULL COMMENT 'Proveedor que abastece el producto.',
  `nombre` VARCHAR(120) NOT NULL COMMENT 'Nombre del producto.',
  `descripcion` VARCHAR(255) NULL COMMENT 'Descripción del producto.',
  `valor_venta` DECIMAL(12,2) NOT NULL COMMENT 'Precio de venta al público.',
  `stock_actual` INT NOT NULL COMMENT 'Existencia actual.',
  `stock_minimo` INT NOT NULL COMMENT 'Existencia mínima antes de reabastecer.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `ck_producto_1` CHECK (valor_venta >= 0),
  CONSTRAINT `ck_producto_2` CHECK (stock_actual >= 0),
  CONSTRAINT `ck_producto_3` CHECK (stock_minimo >= 0),
  CONSTRAINT `fk_producto_proveedor` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedor` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB COMMENT = 'Bien físico con existencias que el hotel vende durante la estadía.';

-- ---------------------------------------------------------------------
-- Entidad: servicio   [módulo 5. Inventario]
-- Prestación intangible ofrecida por el hotel (lavandería, spa, transporte).
-- ---------------------------------------------------------------------
CREATE TABLE `servicio` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `nombre` VARCHAR(120) NOT NULL COMMENT 'Nombre del servicio.',
  `descripcion` VARCHAR(255) NULL COMMENT 'Descripción del servicio.',
  `valor_venta` DECIMAL(12,2) NOT NULL COMMENT 'Precio de venta del servicio.',
  `disponible` BOOLEAN NOT NULL COMMENT 'Indica si el servicio está habilitado.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `uk_servicio_nombre` UNIQUE (`nombre`),
  CONSTRAINT `ck_servicio_1` CHECK (valor_venta >= 0)
) ENGINE = InnoDB COMMENT = 'Prestación intangible ofrecida por el hotel (lavandería, spa, transporte).';

-- ---------------------------------------------------------------------
-- Entidad: seguimiento_producto   [módulo 5. Inventario]
-- Movimiento de inventario (entrada, salida, ajuste) sobre un producto.
-- ---------------------------------------------------------------------
CREATE TABLE `seguimiento_producto` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `producto_id` BIGINT UNSIGNED NOT NULL COMMENT 'Producto afectado por el movimiento.',
  `tipo_movimiento` VARCHAR(30) NOT NULL COMMENT 'Tipo de movimiento (ENTRADA, SALIDA, AJUSTE).',
  `cantidad` INT NOT NULL COMMENT 'Cantidad movida.',
  `fecha_movimiento` DATETIME NOT NULL COMMENT 'Fecha y hora del movimiento.',
  `observacion` VARCHAR(255) NULL COMMENT 'Observación del movimiento.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `ck_seguimiento_producto_1` CHECK (cantidad > 0),
  CONSTRAINT `fk_seguimiento_producto_producto` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB COMMENT = 'Movimiento de inventario (entrada, salida, ajuste) sobre un producto.';

-- ---------------------------------------------------------------------
-- Entidad: disponibilidad_inventario   [módulo 5. Inventario]
-- Estado de disponibilidad de un producto o de un servicio para la venta.
-- ---------------------------------------------------------------------
CREATE TABLE `disponibilidad_inventario` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `producto_id` BIGINT UNSIGNED NULL COMMENT 'Producto evaluado (nulo si evalúa un servicio).',
  `servicio_id` BIGINT UNSIGNED NULL COMMENT 'Servicio evaluado (nulo si evalúa un producto).',
  `cantidad_disponible` INT NOT NULL COMMENT 'Cantidad disponible para la venta.',
  `disponible` BOOLEAN NOT NULL COMMENT 'Indica si puede venderse.',
  `observacion` VARCHAR(255) NULL COMMENT 'Observación de la disponibilidad.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `ck_disponibilidad_inventario_1` CHECK (cantidad_disponible >= 0),
  CONSTRAINT `ck_disponibilidad_inventario_2` CHECK (((producto_id IS NOT NULL AND servicio_id IS NULL) OR (producto_id IS NULL AND servicio_id IS NOT NULL))),
  CONSTRAINT `fk_disponibilidad_inventario_producto` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_disponibilidad_inventario_servicio` FOREIGN KEY (`servicio_id`) REFERENCES `servicio` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB COMMENT = 'Estado de disponibilidad de un producto o de un servicio para la venta.';

-- ---------------------------------------------------------------------
-- Entidad: promocion   [módulo 6. Notificación]
-- Campaña comercial difundida por un canal durante un periodo de vigencia.
-- ---------------------------------------------------------------------
CREATE TABLE `promocion` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `titulo` VARCHAR(120) NOT NULL COMMENT 'Título de la promoción.',
  `descripcion` VARCHAR(255) NULL COMMENT 'Descripción de la promoción.',
  `fecha_inicio` DATE NOT NULL COMMENT 'Inicio de vigencia.',
  `fecha_fin` DATE NOT NULL COMMENT 'Fin de vigencia.',
  `canal` VARCHAR(30) NOT NULL COMMENT 'Canal de difusión (CORREO, SMS, APP).',
  `activa` BOOLEAN NOT NULL COMMENT 'Indica si la promoción está activa.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `ck_promocion_1` CHECK (fecha_fin >= fecha_inicio)
) ENGINE = InnoDB COMMENT = 'Campaña comercial difundida por un canal durante un periodo de vigencia.';

-- ---------------------------------------------------------------------
-- Entidad: alerta   [módulo 6. Notificación]
-- Mensaje operativo enviado a un cliente, opcionalmente asociado a una reserva.
-- ---------------------------------------------------------------------
CREATE TABLE `alerta` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `cliente_id` BIGINT UNSIGNED NOT NULL COMMENT 'Cliente destinatario.',
  `reserva_habitacion_id` BIGINT UNSIGNED NULL COMMENT 'Reserva que origina la alerta.',
  `titulo` VARCHAR(120) NOT NULL COMMENT 'Título de la alerta.',
  `mensaje` TEXT NOT NULL COMMENT 'Contenido del mensaje.',
  `canal` VARCHAR(30) NOT NULL COMMENT 'Canal de envío.',
  `fecha_envio` DATETIME NOT NULL COMMENT 'Fecha y hora de envío.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_alerta_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `cliente` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_alerta_reserva_habitacion` FOREIGN KEY (`reserva_habitacion_id`) REFERENCES `reserva_habitacion` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB COMMENT = 'Mensaje operativo enviado a un cliente, opcionalmente asociado a una reserva.';

-- ---------------------------------------------------------------------
-- Entidad: termino_condicion   [módulo 6. Notificación]
-- Versión de los términos y condiciones aceptados antes de comprar producto o servicio.
-- ---------------------------------------------------------------------
CREATE TABLE `termino_condicion` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `titulo` VARCHAR(120) NOT NULL COMMENT 'Título del documento.',
  `contenido` TEXT NOT NULL COMMENT 'Texto completo de los términos.',
  `version` VARCHAR(20) NOT NULL COMMENT 'Versión del documento.',
  `fecha_vigencia` DATE NOT NULL COMMENT 'Fecha desde la cual rige la versión.',
  `obligatorio` BOOLEAN NOT NULL COMMENT 'Indica si su aceptación es obligatoria.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `uk_termino_condicion_1` UNIQUE (`titulo`, `version`)
) ENGINE = InnoDB COMMENT = 'Versión de los términos y condiciones aceptados antes de comprar producto o servicio.';

-- ---------------------------------------------------------------------
-- Entidad: fidelizacion_cliente   [módulo 6. Notificación]
-- Programa de puntos y nivel de fidelización asociado a un cliente.
-- ---------------------------------------------------------------------
CREATE TABLE `fidelizacion_cliente` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `cliente_id` BIGINT UNSIGNED NOT NULL COMMENT 'Cliente inscrito en el programa.',
  `nivel` VARCHAR(30) NOT NULL COMMENT 'Nivel alcanzado (BRONCE, PLATA, ORO).',
  `puntos` INT NOT NULL COMMENT 'Puntos acumulados.',
  `fecha_ultima_interaccion` DATE NULL COMMENT 'Fecha de la última interacción registrada.',
  `observacion` VARCHAR(255) NULL COMMENT 'Observación del programa.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `uk_fidelizacion_cliente_cliente_id` UNIQUE (`cliente_id`),
  CONSTRAINT `ck_fidelizacion_cliente_1` CHECK (puntos >= 0),
  CONSTRAINT `fk_fidelizacion_cliente_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `cliente` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB COMMENT = 'Programa de puntos y nivel de fidelización asociado a un cliente.';

-- ---------------------------------------------------------------------
-- Entidad: persona   [módulo 7. Seguridad]
-- Datos básicos de una persona natural; supertipo de empleado y titular de usuario.
-- ---------------------------------------------------------------------
CREATE TABLE `persona` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `tipo_documento` VARCHAR(20) NOT NULL COMMENT 'Tipo de documento de identidad.',
  `numero_documento` VARCHAR(30) NOT NULL COMMENT 'Número de documento; junto con el tipo identifica a la persona.',
  `nombre` VARCHAR(100) NOT NULL COMMENT 'Nombres de la persona.',
  `apellido` VARCHAR(100) NOT NULL COMMENT 'Apellidos de la persona.',
  `telefono` VARCHAR(30) NULL COMMENT 'Teléfono de contacto.',
  `correo` VARCHAR(150) NULL COMMENT 'Correo de contacto.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `uk_persona_1` UNIQUE (`tipo_documento`, `numero_documento`)
) ENGINE = InnoDB COMMENT = 'Datos básicos de una persona natural; supertipo de empleado y titular de usuario.';

-- ---------------------------------------------------------------------
-- Entidad: usuario   [módulo 7. Seguridad]
-- Credencial de acceso al sistema asociada a una persona.
-- ---------------------------------------------------------------------
CREATE TABLE `usuario` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `persona_id` BIGINT UNSIGNED NOT NULL COMMENT 'Persona titular de la cuenta.',
  `username` VARCHAR(60) NOT NULL COMMENT 'Nombre de usuario.',
  `password_hash` VARCHAR(255) NOT NULL COMMENT 'Hash de la contraseña.',
  `ultimo_acceso` DATETIME NULL COMMENT 'Fecha y hora del último ingreso.',
  `bloqueado` BOOLEAN NOT NULL COMMENT 'Indica si la cuenta está bloqueada.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `uk_usuario_persona_id` UNIQUE (`persona_id`),
  CONSTRAINT `uk_usuario_username` UNIQUE (`username`),
  CONSTRAINT `fk_usuario_persona` FOREIGN KEY (`persona_id`) REFERENCES `persona` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB COMMENT = 'Credencial de acceso al sistema asociada a una persona.';

-- ---------------------------------------------------------------------
-- Entidad: rol   [módulo 7. Seguridad]
-- Perfil funcional que agrupa permisos (recepción, administración, mantenimiento).
-- ---------------------------------------------------------------------
CREATE TABLE `rol` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `nombre` VARCHAR(60) NOT NULL COMMENT 'Nombre del rol.',
  `descripcion` VARCHAR(255) NULL COMMENT 'Descripción del rol.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `uk_rol_nombre` UNIQUE (`nombre`)
) ENGINE = InnoDB COMMENT = 'Perfil funcional que agrupa permisos (recepción, administración, mantenimiento).';

-- ---------------------------------------------------------------------
-- Entidad: permiso   [módulo 7. Seguridad]
-- Autorización elemental sobre una acción del sistema.
-- ---------------------------------------------------------------------
CREATE TABLE `permiso` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `nombre` VARCHAR(80) NOT NULL COMMENT 'Nombre del permiso.',
  `descripcion` VARCHAR(255) NULL COMMENT 'Descripción del permiso.',
  `accion` VARCHAR(40) NOT NULL COMMENT 'Acción autorizada (CREAR, LEER, ACTUALIZAR, ELIMINAR).',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `uk_permiso_nombre` UNIQUE (`nombre`)
) ENGINE = InnoDB COMMENT = 'Autorización elemental sobre una acción del sistema.';

-- ---------------------------------------------------------------------
-- Entidad: modulo   [módulo 7. Seguridad]
-- Agrupación funcional del sistema (parametrización, distribución, facturación).
-- ---------------------------------------------------------------------
CREATE TABLE `modulo` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `nombre` VARCHAR(80) NOT NULL COMMENT 'Nombre del módulo.',
  `descripcion` VARCHAR(255) NULL COMMENT 'Descripción del módulo.',
  `ruta_base` VARCHAR(120) NULL COMMENT 'Ruta base del módulo en la aplicación.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `uk_modulo_nombre` UNIQUE (`nombre`)
) ENGINE = InnoDB COMMENT = 'Agrupación funcional del sistema (parametrización, distribución, facturación).';

-- ---------------------------------------------------------------------
-- Entidad: vista   [módulo 7. Seguridad]
-- Pantalla o vista perteneciente a un módulo del sistema.
-- ---------------------------------------------------------------------
CREATE TABLE `vista` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `modulo_id` BIGINT UNSIGNED NOT NULL COMMENT 'Módulo propietario de la vista.',
  `nombre` VARCHAR(80) NOT NULL COMMENT 'Nombre de la vista.',
  `descripcion` VARCHAR(255) NULL COMMENT 'Descripción de la vista.',
  `ruta` VARCHAR(150) NOT NULL COMMENT 'Ruta de la vista en la aplicación.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `uk_vista_ruta` UNIQUE (`ruta`),
  CONSTRAINT `fk_vista_modulo` FOREIGN KEY (`modulo_id`) REFERENCES `modulo` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB COMMENT = 'Pantalla o vista perteneciente a un módulo del sistema.';

-- ---------------------------------------------------------------------
-- Entidad: usuario_rol   [módulo 7. Seguridad]
-- Asignación de roles a usuarios (resuelve la relación N:M usuario-rol).
-- ---------------------------------------------------------------------
CREATE TABLE `usuario_rol` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `usuario_id` BIGINT UNSIGNED NOT NULL COMMENT 'Usuario al que se asigna el rol.',
  `rol_id` BIGINT UNSIGNED NOT NULL COMMENT 'Rol asignado.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `uk_usuario_rol_1` UNIQUE (`usuario_id`, `rol_id`),
  CONSTRAINT `fk_usuario_rol_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_usuario_rol_rol` FOREIGN KEY (`rol_id`) REFERENCES `rol` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB COMMENT = 'Asignación de roles a usuarios (resuelve la relación N:M usuario-rol).';

-- ---------------------------------------------------------------------
-- Entidad: rol_permiso   [módulo 7. Seguridad]
-- Asignación de permisos a roles (resuelve la relación N:M rol-permiso).
-- ---------------------------------------------------------------------
CREATE TABLE `rol_permiso` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `rol_id` BIGINT UNSIGNED NOT NULL COMMENT 'Rol al que se asigna el permiso.',
  `permiso_id` BIGINT UNSIGNED NOT NULL COMMENT 'Permiso asignado.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `uk_rol_permiso_1` UNIQUE (`rol_id`, `permiso_id`),
  CONSTRAINT `fk_rol_permiso_rol` FOREIGN KEY (`rol_id`) REFERENCES `rol` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rol_permiso_permiso` FOREIGN KEY (`permiso_id`) REFERENCES `permiso` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB COMMENT = 'Asignación de permisos a roles (resuelve la relación N:M rol-permiso).';

-- ---------------------------------------------------------------------
-- Entidad: modulo_vista   [módulo 7. Seguridad]
-- Vinculación de vistas reutilizables a módulos (resuelve la relación N:M módulo-vista).
-- ---------------------------------------------------------------------
CREATE TABLE `modulo_vista` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `modulo_id` BIGINT UNSIGNED NOT NULL COMMENT 'Módulo que expone la vista.',
  `vista_id` BIGINT UNSIGNED NOT NULL COMMENT 'Vista expuesta.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `uk_modulo_vista_1` UNIQUE (`modulo_id`, `vista_id`),
  CONSTRAINT `fk_modulo_vista_modulo` FOREIGN KEY (`modulo_id`) REFERENCES `modulo` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_modulo_vista_vista` FOREIGN KEY (`vista_id`) REFERENCES `vista` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB COMMENT = 'Vinculación de vistas reutilizables a módulos (resuelve la relación N:M módulo-vista).';

-- ---------------------------------------------------------------------
-- Entidad: dashboard_mantenimiento   [módulo 8. Mantenimiento]
-- Consolidado por sede del estado de las habitaciones a una fecha de corte.
-- ---------------------------------------------------------------------
CREATE TABLE `dashboard_mantenimiento` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `sede_id` BIGINT UNSIGNED NOT NULL COMMENT 'Sede consolidada.',
  `total_habitacion` INT NOT NULL COMMENT 'Total de habitaciones de la sede.',
  `habitacion_disponible` INT NOT NULL COMMENT 'Habitaciones disponibles.',
  `habitacion_ocupada` INT NOT NULL COMMENT 'Habitaciones ocupadas.',
  `habitacion_mantenimiento` INT NOT NULL COMMENT 'Habitaciones en mantenimiento.',
  `fecha_corte` DATE NOT NULL COMMENT 'Fecha de corte del consolidado.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `uk_dashboard_mantenimiento_1` UNIQUE (`sede_id`, `fecha_corte`),
  CONSTRAINT `ck_dashboard_mantenimiento_1` CHECK (total_habitacion >= 0),
  CONSTRAINT `fk_dashboard_mantenimiento_sede` FOREIGN KEY (`sede_id`) REFERENCES `sede` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB COMMENT = 'Consolidado por sede del estado de las habitaciones a una fecha de corte.';

-- ---------------------------------------------------------------------
-- Entidad: precio   [módulo 1. Parametrización]
-- Regla de precio dinámico: valor de un tipo de habitación según el tipo de día y la vigencia.
-- ---------------------------------------------------------------------
CREATE TABLE `precio` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `tipo_habitacion_id` BIGINT UNSIGNED NOT NULL COMMENT 'Tipo de habitación al que aplica el precio.',
  `tipo_dia_id` BIGINT UNSIGNED NOT NULL COMMENT 'Tipo de día (temporada, feriado, especial) al que aplica.',
  `valor` DECIMAL(12,2) NOT NULL COMMENT 'Valor de la tarifa. Debe ser mayor o igual a cero.',
  `fecha_inicio` DATE NOT NULL COMMENT 'Fecha desde la cual rige la tarifa.',
  `fecha_fin` DATE NULL COMMENT 'Fecha hasta la cual rige la tarifa (nula = vigente).',
  `condicion` VARCHAR(255) NULL COMMENT 'Condición comercial adicional aplicable.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `ck_precio_1` CHECK (valor >= 0),
  CONSTRAINT `ck_precio_2` CHECK ((fecha_fin IS NULL OR fecha_fin >= fecha_inicio)),
  CONSTRAINT `fk_precio_tipo_habitacion` FOREIGN KEY (`tipo_habitacion_id`) REFERENCES `tipo_habitacion` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_precio_tipo_dia` FOREIGN KEY (`tipo_dia_id`) REFERENCES `tipo_dia` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB COMMENT = 'Regla de precio dinámico: valor de un tipo de habitación según el tipo de día y la vigencia.';

-- ---------------------------------------------------------------------
-- Entidad: empleado   [módulo 1. Parametrización]
-- Persona vinculada laboralmente al hotel; atiende check in, check out y mantenimiento.
-- ---------------------------------------------------------------------
CREATE TABLE `empleado` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `persona_id` BIGINT UNSIGNED NOT NULL COMMENT 'Persona asociada al empleado (subtipo de persona).',
  `cargo` VARCHAR(80) NOT NULL COMMENT 'Cargo desempeñado.',
  `fecha_ingreso` DATE NOT NULL COMMENT 'Fecha de ingreso a la empresa.',
  `telefono_laboral` VARCHAR(30) NULL COMMENT 'Teléfono laboral.',
  `correo_laboral` VARCHAR(150) NULL COMMENT 'Correo laboral.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `uk_empleado_persona_id` UNIQUE (`persona_id`),
  CONSTRAINT `uk_empleado_correo_laboral` UNIQUE (`correo_laboral`),
  CONSTRAINT `fk_empleado_persona` FOREIGN KEY (`persona_id`) REFERENCES `persona` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB COMMENT = 'Persona vinculada laboralmente al hotel; atiende check in, check out y mantenimiento.';

-- ---------------------------------------------------------------------
-- Entidad: check_in   [módulo 3. Prestación de servicio]
-- Registro del ingreso efectivo del cliente a la habitación reservada.
-- ---------------------------------------------------------------------
CREATE TABLE `check_in` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `reserva_habitacion_id` BIGINT UNSIGNED NOT NULL COMMENT 'Reserva sobre la que se realiza el ingreso.',
  `empleado_id` BIGINT UNSIGNED NOT NULL COMMENT 'Empleado que atiende el ingreso.',
  `fecha_hora_ingreso` DATETIME NOT NULL COMMENT 'Fecha y hora del ingreso.',
  `observacion` VARCHAR(255) NULL COMMENT 'Observaciones del ingreso.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `uk_check_in_reserva_habitacion_id` UNIQUE (`reserva_habitacion_id`),
  CONSTRAINT `fk_check_in_reserva_habitacion` FOREIGN KEY (`reserva_habitacion_id`) REFERENCES `reserva_habitacion` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_check_in_empleado` FOREIGN KEY (`empleado_id`) REFERENCES `empleado` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB COMMENT = 'Registro del ingreso efectivo del cliente a la habitación reservada.';

-- ---------------------------------------------------------------------
-- Entidad: check_out   [módulo 3. Prestación de servicio]
-- Registro de la salida del cliente y liquidación de la estadía.
-- ---------------------------------------------------------------------
CREATE TABLE `check_out` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `estadia_id` BIGINT UNSIGNED NOT NULL COMMENT 'Estadía que se cierra.',
  `empleado_id` BIGINT UNSIGNED NOT NULL COMMENT 'Empleado que atiende la salida.',
  `fecha_hora_salida` DATETIME NOT NULL COMMENT 'Fecha y hora de la salida.',
  `observacion` VARCHAR(255) NULL COMMENT 'Observaciones de la salida.',
  `valor_total` DECIMAL(12,2) NOT NULL COMMENT 'Valor total liquidado al cierre.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `uk_check_out_estadia_id` UNIQUE (`estadia_id`),
  CONSTRAINT `ck_check_out_1` CHECK (valor_total >= 0),
  CONSTRAINT `fk_check_out_estadia` FOREIGN KEY (`estadia_id`) REFERENCES `estadia` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_check_out_empleado` FOREIGN KEY (`empleado_id`) REFERENCES `empleado` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB COMMENT = 'Registro de la salida del cliente y liquidación de la estadía.';

-- ---------------------------------------------------------------------
-- Entidad: venta_producto   [módulo 3. Prestación de servicio]
-- Consumo de un producto de inventario durante una estadía (entidad asociativa estadía-producto).
-- ---------------------------------------------------------------------
CREATE TABLE `venta_producto` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `estadia_id` BIGINT UNSIGNED NOT NULL COMMENT 'Estadía en la que se realiza el consumo.',
  `producto_id` BIGINT UNSIGNED NOT NULL COMMENT 'Producto consumido.',
  `cantidad` INT NOT NULL COMMENT 'Cantidad vendida.',
  `valor_unitario` DECIMAL(12,2) NOT NULL COMMENT 'Valor unitario aplicado.',
  `valor_total` DECIMAL(12,2) NOT NULL COMMENT 'Valor total de la línea de venta.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `ck_venta_producto_1` CHECK (cantidad > 0),
  CONSTRAINT `ck_venta_producto_2` CHECK (valor_unitario >= 0),
  CONSTRAINT `ck_venta_producto_3` CHECK (valor_total >= 0),
  CONSTRAINT `fk_venta_producto_estadia` FOREIGN KEY (`estadia_id`) REFERENCES `estadia` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_venta_producto_producto` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB COMMENT = 'Consumo de un producto de inventario durante una estadía (entidad asociativa estadía-producto).';

-- ---------------------------------------------------------------------
-- Entidad: venta_servicio   [módulo 3. Prestación de servicio]
-- Consumo de un servicio del hotel durante una estadía (entidad asociativa estadía-servicio).
-- ---------------------------------------------------------------------
CREATE TABLE `venta_servicio` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `estadia_id` BIGINT UNSIGNED NOT NULL COMMENT 'Estadía en la que se presta el servicio.',
  `servicio_id` BIGINT UNSIGNED NOT NULL COMMENT 'Servicio prestado.',
  `cantidad` INT NOT NULL COMMENT 'Cantidad vendida.',
  `valor_unitario` DECIMAL(12,2) NOT NULL COMMENT 'Valor unitario aplicado.',
  `valor_total` DECIMAL(12,2) NOT NULL COMMENT 'Valor total de la línea de venta.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `ck_venta_servicio_1` CHECK (cantidad > 0),
  CONSTRAINT `ck_venta_servicio_2` CHECK (valor_unitario >= 0),
  CONSTRAINT `ck_venta_servicio_3` CHECK (valor_total >= 0),
  CONSTRAINT `fk_venta_servicio_estadia` FOREIGN KEY (`estadia_id`) REFERENCES `estadia` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_venta_servicio_servicio` FOREIGN KEY (`servicio_id`) REFERENCES `servicio` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB COMMENT = 'Consumo de un servicio del hotel durante una estadía (entidad asociativa estadía-servicio).';

-- ---------------------------------------------------------------------
-- Entidad: detalle_compra   [módulo 4. Facturación]
-- Línea de la factura correspondiente a un producto o a un servicio.
-- ---------------------------------------------------------------------
CREATE TABLE `detalle_compra` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `factura_id` BIGINT UNSIGNED NOT NULL COMMENT 'Factura a la que pertenece la línea.',
  `producto_id` BIGINT UNSIGNED NULL COMMENT 'Producto facturado (nulo si la línea es de servicio).',
  `servicio_id` BIGINT UNSIGNED NULL COMMENT 'Servicio facturado (nulo si la línea es de producto).',
  `descripcion` VARCHAR(255) NOT NULL COMMENT 'Descripción del ítem facturado.',
  `cantidad` INT NOT NULL COMMENT 'Cantidad facturada.',
  `valor_unitario` DECIMAL(12,2) NOT NULL COMMENT 'Valor unitario.',
  `valor_total` DECIMAL(12,2) NOT NULL COMMENT 'Valor total de la línea.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `ck_detalle_compra_1` CHECK (cantidad > 0),
  CONSTRAINT `ck_detalle_compra_2` CHECK (((producto_id IS NOT NULL AND servicio_id IS NULL) OR (producto_id IS NULL AND servicio_id IS NOT NULL))),
  CONSTRAINT `fk_detalle_compra_factura` FOREIGN KEY (`factura_id`) REFERENCES `factura` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_detalle_compra_producto` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_detalle_compra_servicio` FOREIGN KEY (`servicio_id`) REFERENCES `servicio` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB COMMENT = 'Línea de la factura correspondiente a un producto o a un servicio.';

-- ---------------------------------------------------------------------
-- Entidad: mantenimiento_habitacion   [módulo 8. Mantenimiento]
-- Orden de mantenimiento sobre una habitación; supertipo de mantenimiento por uso y por remodelación.
-- ---------------------------------------------------------------------
CREATE TABLE `mantenimiento_habitacion` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `habitacion_id` BIGINT UNSIGNED NOT NULL COMMENT 'Habitación intervenida.',
  `empleado_id` BIGINT UNSIGNED NULL COMMENT 'Empleado responsable de la intervención.',
  `tipo_mantenimiento` VARCHAR(30) NOT NULL COMMENT 'Discriminador de especialización (USO, REMODELACION).',
  `fecha_inicio` DATETIME NOT NULL COMMENT 'Inicio del mantenimiento.',
  `fecha_fin` DATETIME NULL COMMENT 'Fin del mantenimiento (nulo = en curso).',
  `estado_mantenimiento` VARCHAR(30) NOT NULL COMMENT 'Estado (PROGRAMADO, EN_PROCESO, FINALIZADO).',
  `observacion` VARCHAR(255) NULL COMMENT 'Observación de la intervención.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `ck_mantenimiento_habitacion_1` CHECK (tipo_mantenimiento IN ('USO','REMODELACION')),
  CONSTRAINT `fk_mantenimiento_habitacion_habitacion` FOREIGN KEY (`habitacion_id`) REFERENCES `habitacion` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_mantenimiento_habitacion_empleado` FOREIGN KEY (`empleado_id`) REFERENCES `empleado` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB COMMENT = 'Orden de mantenimiento sobre una habitación; supertipo de mantenimiento por uso y por remodelación.';

-- ---------------------------------------------------------------------
-- Entidad: mantenimiento_uso   [módulo 8. Mantenimiento]
-- Especialización de mantenimiento generado por el uso normal de la habitación.
-- ---------------------------------------------------------------------
CREATE TABLE `mantenimiento_uso` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `mantenimiento_habitacion_id` BIGINT UNSIGNED NOT NULL COMMENT 'Orden de mantenimiento especializada.',
  `motivo_uso` VARCHAR(120) NOT NULL COMMENT 'Motivo derivado del uso.',
  `detalle_actividad` TEXT NULL COMMENT 'Detalle de las actividades ejecutadas.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `uk_mantenimiento_uso_mantenimiento_habitacion_id` UNIQUE (`mantenimiento_habitacion_id`),
  CONSTRAINT `fk_mantenimiento_uso_mantenimiento_habitacion` FOREIGN KEY (`mantenimiento_habitacion_id`) REFERENCES `mantenimiento_habitacion` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB COMMENT = 'Especialización de mantenimiento generado por el uso normal de la habitación.';

-- ---------------------------------------------------------------------
-- Entidad: mantenimiento_remodelacion   [módulo 8. Mantenimiento]
-- Especialización de mantenimiento por remodelación o adecuación de la habitación.
-- ---------------------------------------------------------------------
CREATE TABLE `mantenimiento_remodelacion` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro (autoincremental).',
  `mantenimiento_habitacion_id` BIGINT UNSIGNED NOT NULL COMMENT 'Orden de mantenimiento especializada.',
  `descripcion_remodelacion` TEXT NOT NULL COMMENT 'Alcance de la remodelación.',
  `presupuesto_estimado` DECIMAL(12,2) NULL COMMENT 'Presupuesto estimado de la obra.',
  `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el registro.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación.',
  `updated_by` BIGINT UNSIGNED NULL COMMENT 'Último usuario que modificó el registro.',
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la última modificación.',
  `deleted_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó la eliminación lógica.',
  `deleted_at` DATETIME NULL COMMENT 'Fecha y hora de la eliminación lógica.',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE' COMMENT 'Estado funcional del registro (ACTIVE / INACTIVE).',
  PRIMARY KEY (`id`),
  CONSTRAINT `uk_mantenimiento_remodelacion_mantenimiento_habitacion_id` UNIQUE (`mantenimiento_habitacion_id`),
  CONSTRAINT `ck_mantenimiento_remodelacion_1` CHECK (presupuesto_estimado IS NULL OR presupuesto_estimado >= 0),
  CONSTRAINT `fk_mantenimiento_remodelacion_mantenimiento_habitacion` FOREIGN KEY (`mantenimiento_habitacion_id`) REFERENCES `mantenimiento_habitacion` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB COMMENT = 'Especialización de mantenimiento por remodelación o adecuación de la habitación.';

-- ---------------------------------------------------------------------
-- Auditoría: created_by, updated_by y deleted_by referencian usuario.id.
-- Se documentan en el modelo conceptual y se implementan en el modelo
-- físico (GA3-220501096-AA2-EV02) para no saturar el diagrama EER con
-- tres aristas adicionales por cada entidad.
-- ---------------------------------------------------------------------
