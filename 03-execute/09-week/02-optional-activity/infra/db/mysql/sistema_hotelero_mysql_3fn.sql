-- Sistema Hotelero MySQL 8.0+ en 3FN
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS sistema_hotelero
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_0900_ai_ci;

USE sistema_hotelero;

-- Reejecución segura
DROP VIEW IF EXISTS vw_dashboard_mantenimiento;
DROP VIEW IF EXISTS vw_disponibilidad_inventario;
DROP VIEW IF EXISTS vw_stock_producto;
DROP VIEW IF EXISTS vw_modulo_vista;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS mantenimiento_remodelacion;
DROP TABLE IF EXISTS mantenimiento_uso;
DROP TABLE IF EXISTS mantenimiento_habitacion;
DROP TABLE IF EXISTS venta_servicio;
DROP TABLE IF EXISTS venta_producto;
DROP TABLE IF EXISTS fidelizacion_cliente;
DROP TABLE IF EXISTS cliente_termino_condicion;
DROP TABLE IF EXISTS promocion_canal;
DROP TABLE IF EXISTS alerta;
DROP TABLE IF EXISTS pago;
DROP TABLE IF EXISTS factura_detalle;
DROP TABLE IF EXISTS factura;
DROP TABLE IF EXISTS pre_factura;
DROP TABLE IF EXISTS check_out;
DROP TABLE IF EXISTS check_in;
DROP TABLE IF EXISTS estadia_habitacion;
DROP TABLE IF EXISTS estadia;
DROP TABLE IF EXISTS cancelacion_habitacion;
DROP TABLE IF EXISTS reserva_habitacion;
DROP TABLE IF EXISTS reserva;
DROP TABLE IF EXISTS disponibilidad_habitacion;
DROP TABLE IF EXISTS catalogo_habitacion;
DROP TABLE IF EXISTS precio;
DROP TABLE IF EXISTS seguimiento_producto;
DROP TABLE IF EXISTS producto;
DROP TABLE IF EXISTS proveedor;
DROP TABLE IF EXISTS servicio;
DROP TABLE IF EXISTS habitacion;
DROP TABLE IF EXISTS sede;
DROP TABLE IF EXISTS informacion_legal;
DROP TABLE IF EXISTS empresa;
DROP TABLE IF EXISTS empleado;
DROP TABLE IF EXISTS cliente;
DROP TABLE IF EXISTS usuario_rol;
DROP TABLE IF EXISTS rol_permiso;
DROP TABLE IF EXISTS permiso;
DROP TABLE IF EXISTS accion_permiso;
DROP TABLE IF EXISTS vista;
DROP TABLE IF EXISTS modulo;
DROP TABLE IF EXISTS rol;
DROP TABLE IF EXISTS usuario;
DROP TABLE IF EXISTS persona;

DROP TABLE IF EXISTS estado_mantenimiento;
DROP TABLE IF EXISTS tipo_mantenimiento;
DROP TABLE IF EXISTS nivel_fidelizacion;
DROP TABLE IF EXISTS estado_notificacion;
DROP TABLE IF EXISTS canal_notificacion;
DROP TABLE IF EXISTS tipo_movimiento_inventario;
DROP TABLE IF EXISTS estado_pago;
DROP TABLE IF EXISTS metodo_pago;
DROP TABLE IF EXISTS estado_factura;
DROP TABLE IF EXISTS tipo_concepto_factura;
DROP TABLE IF EXISTS estado_estadia;
DROP TABLE IF EXISTS estado_reserva;
DROP TABLE IF EXISTS temporada;
DROP TABLE IF EXISTS tipo_dia;
DROP TABLE IF EXISTS estado_habitacion;
DROP TABLE IF EXISTS tipo_habitacion;
DROP TABLE IF EXISTS cargo;
DROP TABLE IF EXISTS tipo_documento_legal;
DROP TABLE IF EXISTS tipo_documento;
DROP TABLE IF EXISTS fidelizacion_cliente;
DROP TABLE IF EXISTS cliente_termino_condicion;
DROP TABLE IF EXISTS termino_condicion;

DROP TABLE IF EXISTS promocion_canal;
DROP TABLE IF EXISTS promocion;

DROP TABLE IF EXISTS alerta;
SET FOREIGN_KEY_CHECKS = 1;

-- Catálogos
CREATE TABLE tipo_documento (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(30) NOT NULL UNIQUE,
  nombre VARCHAR(100) NOT NULL,
  descripcion VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE tipo_documento_legal (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(30) NOT NULL UNIQUE,
  nombre VARCHAR(100) NOT NULL,
  descripcion VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE cargo (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(30) NOT NULL UNIQUE,
  nombre VARCHAR(120) NOT NULL,
  descripcion VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE tipo_habitacion (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(30) NOT NULL UNIQUE,
  nombre VARCHAR(100) NOT NULL,
  descripcion VARCHAR(255) NULL,
  capacidad_base TINYINT UNSIGNED NOT NULL,
  capacidad_maxima TINYINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  CHECK (capacidad_base > 0),
  CHECK (capacidad_maxima >= capacidad_base),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE estado_habitacion (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(30) NOT NULL UNIQUE,
  nombre VARCHAR(100) NOT NULL,
  descripcion VARCHAR(255) NULL,
  permite_reserva BOOLEAN NOT NULL DEFAULT TRUE,
  permite_check_in BOOLEAN NOT NULL DEFAULT TRUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE tipo_dia (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(30) NOT NULL UNIQUE,
  nombre VARCHAR(100) NOT NULL,
  descripcion VARCHAR(255) NULL,
  multiplicador DECIMAL(5,2) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  CHECK (multiplicador > 0),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE temporada (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(30) NOT NULL UNIQUE,
  nombre VARCHAR(100) NOT NULL,
  descripcion VARCHAR(255) NULL,
  multiplicador DECIMAL(5,2) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  CHECK (multiplicador > 0),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE estado_reserva (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(40) NOT NULL UNIQUE,
  nombre VARCHAR(100) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE estado_estadia (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(40) NOT NULL UNIQUE,
  nombre VARCHAR(100) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE tipo_concepto_factura (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(40) NOT NULL UNIQUE,
  nombre VARCHAR(120) NOT NULL,
  descripcion VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE estado_factura (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(40) NOT NULL UNIQUE,
  nombre VARCHAR(120) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE metodo_pago (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(40) NOT NULL UNIQUE,
  nombre VARCHAR(120) NOT NULL,
  descripcion VARCHAR(255) NULL,
  requiere_referencia BOOLEAN NOT NULL DEFAULT FALSE,
  permite_pago_parcial BOOLEAN NOT NULL DEFAULT TRUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE estado_pago (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(40) NOT NULL UNIQUE,
  nombre VARCHAR(120) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE tipo_movimiento_inventario (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(40) NOT NULL UNIQUE,
  nombre VARCHAR(120) NOT NULL,
  signo SMALLINT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  CHECK (signo IN (-1,1)),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE canal_notificacion (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(40) NOT NULL UNIQUE,
  nombre VARCHAR(120) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE estado_notificacion (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(40) NOT NULL UNIQUE,
  nombre VARCHAR(120) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE nivel_fidelizacion (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(40) NOT NULL UNIQUE,
  nombre VARCHAR(120) NOT NULL,
  puntos_minimos INT NOT NULL,
  beneficios VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  CHECK (puntos_minimos >= 0),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE tipo_mantenimiento (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(40) NOT NULL UNIQUE,
  nombre VARCHAR(120) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE estado_mantenimiento (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(40) NOT NULL UNIQUE,
  nombre VARCHAR(120) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Seguridad
CREATE TABLE persona (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tipo_documento_id BIGINT UNSIGNED NOT NULL,
  numero_documento VARCHAR(30) NOT NULL,
  nombres VARCHAR(120) NOT NULL,
  apellidos VARCHAR(120) NOT NULL,
  telefono VARCHAR(25) NULL,
  correo VARCHAR(180) NULL,
  direccion VARCHAR(255) NULL,
  fecha_nacimiento DATE NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  UNIQUE KEY uk_persona_doc (tipo_documento_id, numero_documento),
  KEY idx_persona_apellidos (apellidos),
  KEY idx_persona_correo (correo),
  CONSTRAINT fk_persona_tipo_documento FOREIGN KEY (tipo_documento_id) REFERENCES tipo_documento(id),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE rol (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(40) NOT NULL UNIQUE,
  nombre VARCHAR(120) NOT NULL,
  descripcion VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE modulo (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(40) NOT NULL UNIQUE,
  nombre VARCHAR(120) NOT NULL,
  descripcion VARCHAR(255) NULL,
  ruta_base VARCHAR(180) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE vista (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  modulo_id BIGINT UNSIGNED NOT NULL,
  codigo VARCHAR(50) NOT NULL UNIQUE,
  nombre VARCHAR(120) NOT NULL,
  descripcion VARCHAR(255) NULL,
  ruta VARCHAR(200) NOT NULL,
  es_activa BOOLEAN NOT NULL DEFAULT TRUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  KEY idx_vista_modulo (modulo_id),
  CONSTRAINT fk_vista_modulo FOREIGN KEY (modulo_id) REFERENCES modulo(id),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE accion_permiso (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(40) NOT NULL UNIQUE,
  nombre VARCHAR(120) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE permiso (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  modulo_id BIGINT UNSIGNED NOT NULL,
  vista_id BIGINT UNSIGNED NULL,
  accion_permiso_id BIGINT UNSIGNED NOT NULL,
  codigo VARCHAR(80) NOT NULL UNIQUE,
  nombre VARCHAR(150) NOT NULL,
  descripcion VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  KEY idx_permiso_modulo (modulo_id),
  KEY idx_permiso_vista (vista_id),
  KEY idx_permiso_accion (accion_permiso_id),
  CONSTRAINT fk_permiso_modulo FOREIGN KEY (modulo_id) REFERENCES modulo(id),
  CONSTRAINT fk_permiso_vista FOREIGN KEY (vista_id) REFERENCES vista(id),
  CONSTRAINT fk_permiso_accion FOREIGN KEY (accion_permiso_id) REFERENCES accion_permiso(id),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE usuario (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  persona_id BIGINT UNSIGNED NOT NULL,
  username VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  ultimo_acceso DATETIME NULL,
  bloqueado BOOLEAN NOT NULL DEFAULT FALSE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  KEY idx_usuario_persona (persona_id),
  CONSTRAINT fk_usuario_persona FOREIGN KEY (persona_id) REFERENCES persona(id),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE usuario_rol (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id BIGINT UNSIGNED NOT NULL,
  rol_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  UNIQUE KEY uk_usuario_rol (usuario_id, rol_id),
  KEY idx_usuario_rol_rol (rol_id),
  CONSTRAINT fk_usuario_rol_usuario FOREIGN KEY (usuario_id) REFERENCES usuario(id),
  CONSTRAINT fk_usuario_rol_rol FOREIGN KEY (rol_id) REFERENCES rol(id),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE rol_permiso (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rol_id BIGINT UNSIGNED NOT NULL,
  permiso_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  UNIQUE KEY uk_rol_permiso (rol_id, permiso_id),
  KEY idx_rol_permiso_permiso (permiso_id),
  CONSTRAINT fk_rol_permiso_rol FOREIGN KEY (rol_id) REFERENCES rol(id),
  CONSTRAINT fk_rol_permiso_permiso FOREIGN KEY (permiso_id) REFERENCES permiso(id),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Terceros y organización
CREATE TABLE cliente (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  persona_id BIGINT UNSIGNED NOT NULL,
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  observacion VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  UNIQUE KEY uk_cliente_persona (persona_id),
  CONSTRAINT fk_cliente_persona FOREIGN KEY (persona_id) REFERENCES persona(id),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE empleado (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  persona_id BIGINT UNSIGNED NOT NULL,
  cargo_id BIGINT UNSIGNED NOT NULL,
  fecha_ingreso DATE NOT NULL,
  telefono_laboral VARCHAR(25) NULL,
  correo_laboral VARCHAR(180) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  UNIQUE KEY uk_empleado_persona (persona_id),
  KEY idx_empleado_cargo (cargo_id),
  CONSTRAINT fk_empleado_persona FOREIGN KEY (persona_id) REFERENCES persona(id),
  CONSTRAINT fk_empleado_cargo FOREIGN KEY (cargo_id) REFERENCES cargo(id),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE empresa (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  nit VARCHAR(30) NOT NULL UNIQUE,
  razon_social VARCHAR(180) NULL,
  telefono VARCHAR(25) NULL,
  correo VARCHAR(180) NULL,
  direccion VARCHAR(255) NULL,
  sitio_web VARCHAR(200) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE informacion_legal (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  empresa_id BIGINT UNSIGNED NOT NULL,
  tipo_documento_legal_id BIGINT UNSIGNED NOT NULL,
  numero_documento_legal VARCHAR(60) NOT NULL,
  descripcion VARCHAR(255) NULL,
  fecha_expedicion DATE NULL,
  fecha_vencimiento DATE NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  KEY idx_info_legal_empresa (empresa_id),
  KEY idx_info_legal_tipo (tipo_documento_legal_id),
  CONSTRAINT fk_info_legal_empresa FOREIGN KEY (empresa_id) REFERENCES empresa(id),
  CONSTRAINT fk_info_legal_tipo_documento FOREIGN KEY (tipo_documento_legal_id) REFERENCES tipo_documento_legal(id),
  CHECK (fecha_vencimiento IS NULL OR fecha_expedicion IS NULL OR fecha_vencimiento >= fecha_expedicion),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE sede (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  empresa_id BIGINT UNSIGNED NOT NULL,
  nombre VARCHAR(150) NOT NULL,
  direccion VARCHAR(255) NOT NULL,
  ciudad VARCHAR(100) NOT NULL,
  telefono VARCHAR(25) NULL,
  correo VARCHAR(180) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  KEY idx_sede_empresa (empresa_id),
  KEY idx_sede_ciudad (ciudad),
  CONSTRAINT fk_sede_empresa FOREIGN KEY (empresa_id) REFERENCES empresa(id),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE habitacion (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sede_id BIGINT UNSIGNED NOT NULL,
  tipo_habitacion_id BIGINT UNSIGNED NOT NULL,
  estado_habitacion_id BIGINT UNSIGNED NOT NULL,
  numero VARCHAR(20) NOT NULL,
  piso SMALLINT NOT NULL,
  capacidad SMALLINT NOT NULL,
  descripcion VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  UNIQUE KEY uk_habitacion_sede_numero (sede_id, numero),
  KEY idx_habitacion_tipo (tipo_habitacion_id),
  KEY idx_habitacion_estado (estado_habitacion_id),
  CONSTRAINT fk_habitacion_sede FOREIGN KEY (sede_id) REFERENCES sede(id),
  CONSTRAINT fk_habitacion_tipo FOREIGN KEY (tipo_habitacion_id) REFERENCES tipo_habitacion(id),
  CONSTRAINT fk_habitacion_estado FOREIGN KEY (estado_habitacion_id) REFERENCES estado_habitacion(id),
  CHECK (capacidad > 0),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE catalogo_habitacion (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  habitacion_id BIGINT UNSIGNED NOT NULL,
  titulo VARCHAR(180) NOT NULL,
  descripcion TEXT NULL,
  precio_base_noche DECIMAL(12,2) NOT NULL,
  visible BOOLEAN NOT NULL DEFAULT TRUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  KEY idx_catalogo_habitacion (habitacion_id),
  CONSTRAINT fk_catalogo_habitacion FOREIGN KEY (habitacion_id) REFERENCES habitacion(id),
  CHECK (precio_base_noche >= 0),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE disponibilidad_habitacion (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  habitacion_id BIGINT UNSIGNED NOT NULL,
  fecha_inicio DATETIME NOT NULL,
  fecha_fin DATETIME NOT NULL,
  disponible BOOLEAN NOT NULL DEFAULT TRUE,
  motivo_no_disponible VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  KEY idx_disponibilidad_hab (habitacion_id),
  KEY idx_disponibilidad_rango (fecha_inicio, fecha_fin),
  CONSTRAINT fk_disponibilidad_habitacion FOREIGN KEY (habitacion_id) REFERENCES habitacion(id),
  CHECK (fecha_fin > fecha_inicio),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE precio (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tipo_habitacion_id BIGINT UNSIGNED NOT NULL,
  tipo_dia_id BIGINT UNSIGNED NOT NULL,
  temporada_id BIGINT UNSIGNED NOT NULL,
  sede_id BIGINT UNSIGNED NULL,
  valor_base DECIMAL(12,2) NOT NULL,
  descuento_reserva_anticipada_pct DECIMAL(5,2) NOT NULL DEFAULT 5.00,
  recargo_ultimo_minuto_pct DECIMAL(5,2) NOT NULL DEFAULT 10.00,
  recargo_persona_adicional_pct DECIMAL(5,2) NOT NULL DEFAULT 15.00,
  dias_anticipacion_descuento SMALLINT NOT NULL DEFAULT 30,
  horas_ultimo_minuto SMALLINT NOT NULL DEFAULT 24,
  fecha_inicio DATE NOT NULL,
  fecha_fin DATE NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  KEY idx_precio_tipo_habitacion (tipo_habitacion_id),
  KEY idx_precio_tipo_dia (tipo_dia_id),
  KEY idx_precio_temporada (temporada_id),
  KEY idx_precio_sede (sede_id),
  KEY idx_precio_vigencia (fecha_inicio, fecha_fin),
  CONSTRAINT fk_precio_tipo_habitacion FOREIGN KEY (tipo_habitacion_id) REFERENCES tipo_habitacion(id),
  CONSTRAINT fk_precio_tipo_dia FOREIGN KEY (tipo_dia_id) REFERENCES tipo_dia(id),
  CONSTRAINT fk_precio_temporada FOREIGN KEY (temporada_id) REFERENCES temporada(id),
  CONSTRAINT fk_precio_sede FOREIGN KEY (sede_id) REFERENCES sede(id),
  CHECK (valor_base >= 0),
  CHECK (fecha_fin >= fecha_inicio),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Reservas y estadías
CREATE TABLE reserva (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cliente_id BIGINT UNSIGNED NOT NULL,
  sede_id BIGINT UNSIGNED NOT NULL,
  estado_reserva_id BIGINT UNSIGNED NOT NULL,
  codigo_reserva VARCHAR(50) NOT NULL UNIQUE,
  fecha_reserva DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  check_in_programado DATETIME NOT NULL,
  check_out_programado DATETIME NOT NULL,
  cantidad_personas SMALLINT UNSIGNED NOT NULL,
  observacion VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  KEY idx_reserva_cliente (cliente_id),
  KEY idx_reserva_sede (sede_id),
  KEY idx_reserva_estado (estado_reserva_id),
  KEY idx_reserva_fechas (check_in_programado, check_out_programado),
  CONSTRAINT fk_reserva_cliente FOREIGN KEY (cliente_id) REFERENCES cliente(id),
  CONSTRAINT fk_reserva_sede FOREIGN KEY (sede_id) REFERENCES sede(id),
  CONSTRAINT fk_reserva_estado FOREIGN KEY (estado_reserva_id) REFERENCES estado_reserva(id),
  CHECK (check_out_programado > check_in_programado),
  CHECK (cantidad_personas > 0),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE reserva_habitacion (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reserva_id BIGINT UNSIGNED NOT NULL,
  habitacion_id BIGINT UNSIGNED NOT NULL,
  fecha_inicio DATETIME NOT NULL,
  fecha_fin DATETIME NOT NULL,
  cantidad_personas SMALLINT UNSIGNED NOT NULL,
  valor_estimado DECIMAL(12,2) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  KEY idx_reserva_hab_reserva (reserva_id),
  KEY idx_reserva_hab_habitacion (habitacion_id),
  CONSTRAINT fk_reserva_habitacion_reserva FOREIGN KEY (reserva_id) REFERENCES reserva(id),
  CONSTRAINT fk_reserva_habitacion_habitacion FOREIGN KEY (habitacion_id) REFERENCES habitacion(id),
  CHECK (fecha_fin > fecha_inicio),
  CHECK (cantidad_personas > 0),
  CHECK (valor_estimado >= 0),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE cancelacion_habitacion (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reserva_habitacion_id BIGINT UNSIGNED NOT NULL,
  motivo VARCHAR(255) NOT NULL,
  fecha_cancelacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  aplica_penalidad BOOLEAN NOT NULL DEFAULT FALSE,
  valor_penalidad DECIMAL(12,2) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  KEY idx_cancelacion_reserva_hab (reserva_habitacion_id),
  CONSTRAINT fk_cancelacion_reserva_hab FOREIGN KEY (reserva_habitacion_id) REFERENCES reserva_habitacion(id),
  CHECK (valor_penalidad >= 0),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE estadia (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reserva_id BIGINT UNSIGNED NOT NULL,
  cliente_id BIGINT UNSIGNED NOT NULL,
  estado_estadia_id BIGINT UNSIGNED NOT NULL,
  fecha_inicio DATETIME NOT NULL,
  fecha_fin DATETIME NULL,
  observacion VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  KEY idx_estadia_reserva (reserva_id),
  KEY idx_estadia_cliente (cliente_id),
  KEY idx_estadia_estado (estado_estadia_id),
  CONSTRAINT fk_estadia_reserva FOREIGN KEY (reserva_id) REFERENCES reserva(id),
  CONSTRAINT fk_estadia_cliente FOREIGN KEY (cliente_id) REFERENCES cliente(id),
  CONSTRAINT fk_estadia_estado FOREIGN KEY (estado_estadia_id) REFERENCES estado_estadia(id),
  CHECK (fecha_fin IS NULL OR fecha_fin >= fecha_inicio),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE estadia_habitacion (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  estadia_id BIGINT UNSIGNED NOT NULL,
  habitacion_id BIGINT UNSIGNED NOT NULL,
  fecha_inicio DATETIME NOT NULL,
  fecha_fin DATETIME NULL,
  tarifa_noche DECIMAL(12,2) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  KEY idx_estadia_hab_estadia (estadia_id),
  KEY idx_estadia_hab_habitacion (habitacion_id),
  CONSTRAINT fk_estadia_hab_estadia FOREIGN KEY (estadia_id) REFERENCES estadia(id),
  CONSTRAINT fk_estadia_hab_habitacion FOREIGN KEY (habitacion_id) REFERENCES habitacion(id),
  CHECK (fecha_fin IS NULL OR fecha_fin >= fecha_inicio),
  CHECK (tarifa_noche >= 0),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE check_in (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  estadia_id BIGINT UNSIGNED NOT NULL,
  empleado_id BIGINT UNSIGNED NOT NULL,
  fecha_hora_ingreso DATETIME NOT NULL,
  observacion VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  UNIQUE KEY uk_check_in_estadia (estadia_id),
  KEY idx_check_in_empleado (empleado_id),
  CONSTRAINT fk_check_in_estadia FOREIGN KEY (estadia_id) REFERENCES estadia(id),
  CONSTRAINT fk_check_in_empleado FOREIGN KEY (empleado_id) REFERENCES empleado(id),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE check_out (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  estadia_id BIGINT UNSIGNED NOT NULL,
  empleado_id BIGINT UNSIGNED NOT NULL,
  fecha_hora_salida DATETIME NOT NULL,
  observacion VARCHAR(255) NULL,
  valor_total DECIMAL(12,2) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  UNIQUE KEY uk_check_out_estadia (estadia_id),
  KEY idx_check_out_empleado (empleado_id),
  CONSTRAINT fk_check_out_estadia FOREIGN KEY (estadia_id) REFERENCES estadia(id),
  CONSTRAINT fk_check_out_empleado FOREIGN KEY (empleado_id) REFERENCES empleado(id),
  CHECK (valor_total >= 0),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Facturación y pagos
CREATE TABLE pre_factura (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  estadia_id BIGINT UNSIGNED NULL,
  reserva_id BIGINT UNSIGNED NULL,
  cliente_id BIGINT UNSIGNED NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL,
  impuesto DECIMAL(12,2) NOT NULL DEFAULT 0,
  descuento DECIMAL(12,2) NOT NULL DEFAULT 0,
  total DECIMAL(12,2) AS (subtotal + impuesto - descuento) STORED,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  KEY idx_pre_factura_estadia (estadia_id),
  KEY idx_pre_factura_reserva (reserva_id),
  KEY idx_pre_factura_cliente (cliente_id),
  CONSTRAINT fk_pre_factura_estadia FOREIGN KEY (estadia_id) REFERENCES estadia(id),
  CONSTRAINT fk_pre_factura_reserva FOREIGN KEY (reserva_id) REFERENCES reserva(id),
  CONSTRAINT fk_pre_factura_cliente FOREIGN KEY (cliente_id) REFERENCES cliente(id),
  CHECK (subtotal >= 0 AND impuesto >= 0 AND descuento >= 0),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE factura (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pre_factura_id BIGINT UNSIGNED NULL,
  cliente_id BIGINT UNSIGNED NOT NULL,
  estadia_id BIGINT UNSIGNED NULL,
  estado_factura_id BIGINT UNSIGNED NOT NULL,
  numero_factura VARCHAR(50) NOT NULL UNIQUE,
  fecha_emision DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  subtotal DECIMAL(12,2) NOT NULL,
  impuesto DECIMAL(12,2) NOT NULL DEFAULT 0,
  descuento DECIMAL(12,2) NOT NULL DEFAULT 0,
  total DECIMAL(12,2) AS (subtotal + impuesto - descuento) STORED,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  KEY idx_factura_pre_factura (pre_factura_id),
  KEY idx_factura_cliente (cliente_id),
  KEY idx_factura_estadia (estadia_id),
  KEY idx_factura_estado (estado_factura_id),
  CONSTRAINT fk_factura_pre_factura FOREIGN KEY (pre_factura_id) REFERENCES pre_factura(id),
  CONSTRAINT fk_factura_cliente FOREIGN KEY (cliente_id) REFERENCES cliente(id),
  CONSTRAINT fk_factura_estadia FOREIGN KEY (estadia_id) REFERENCES estadia(id),
  CONSTRAINT fk_factura_estado FOREIGN KEY (estado_factura_id) REFERENCES estado_factura(id),
  CHECK (subtotal >= 0 AND impuesto >= 0 AND descuento >= 0),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE factura_detalle (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  factura_id BIGINT UNSIGNED NOT NULL,
  tipo_concepto_factura_id BIGINT UNSIGNED NOT NULL,
  producto_id BIGINT UNSIGNED NULL,
  servicio_id BIGINT UNSIGNED NULL,
  descripcion VARCHAR(255) NOT NULL,
  cantidad DECIMAL(12,2) NOT NULL,
  valor_unitario DECIMAL(12,2) NOT NULL,
  valor_total DECIMAL(12,2) AS (cantidad * valor_unitario) STORED,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  KEY idx_factura_detalle_factura (factura_id),
  KEY idx_factura_detalle_tipo (tipo_concepto_factura_id),
  KEY idx_factura_detalle_producto (producto_id),
  KEY idx_factura_detalle_servicio (servicio_id),
  CONSTRAINT fk_factura_detalle_factura FOREIGN KEY (factura_id) REFERENCES factura(id),
  CONSTRAINT fk_factura_detalle_tipo_concepto FOREIGN KEY (tipo_concepto_factura_id) REFERENCES tipo_concepto_factura(id),
  CHECK (cantidad > 0 AND valor_unitario >= 0),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE pago (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  factura_id BIGINT UNSIGNED NOT NULL,
  metodo_pago_id BIGINT UNSIGNED NOT NULL,
  estado_pago_id BIGINT UNSIGNED NOT NULL,
  valor DECIMAL(12,2) NOT NULL,
  fecha_pago DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  referencia_pago VARCHAR(120) NULL,
  observacion VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  KEY idx_pago_factura (factura_id),
  KEY idx_pago_metodo (metodo_pago_id),
  KEY idx_pago_estado (estado_pago_id),
  CONSTRAINT fk_pago_factura FOREIGN KEY (factura_id) REFERENCES factura(id),
  CONSTRAINT fk_pago_metodo FOREIGN KEY (metodo_pago_id) REFERENCES metodo_pago(id),
  CONSTRAINT fk_pago_estado FOREIGN KEY (estado_pago_id) REFERENCES estado_pago(id),
  CHECK (valor > 0),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Inventario y ventas
CREATE TABLE proveedor (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(180) NOT NULL,
  nit VARCHAR(30) NOT NULL UNIQUE,
  telefono VARCHAR(25) NULL,
  correo VARCHAR(180) NULL,
  direccion VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE producto (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  proveedor_id BIGINT UNSIGNED NOT NULL,
  sede_id BIGINT UNSIGNED NOT NULL,
  codigo VARCHAR(60) NOT NULL UNIQUE,
  nombre VARCHAR(180) NOT NULL,
  descripcion VARCHAR(255) NULL,
  valor_venta DECIMAL(12,2) NOT NULL,
  stock_minimo DECIMAL(12,2) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  KEY idx_producto_proveedor (proveedor_id),
  KEY idx_producto_sede (sede_id),
  KEY idx_producto_nombre (nombre),
  CONSTRAINT fk_producto_proveedor FOREIGN KEY (proveedor_id) REFERENCES proveedor(id),
  CONSTRAINT fk_producto_sede FOREIGN KEY (sede_id) REFERENCES sede(id),
  CHECK (valor_venta >= 0),
  CHECK (stock_minimo >= 0),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE servicio (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(60) NOT NULL UNIQUE,
  nombre VARCHAR(180) NOT NULL,
  descripcion VARCHAR(255) NULL,
  valor_venta DECIMAL(12,2) NOT NULL,
  disponible BOOLEAN NOT NULL DEFAULT TRUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  CHECK (valor_venta >= 0),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE seguimiento_producto (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  producto_id BIGINT UNSIGNED NOT NULL,
  sede_id BIGINT UNSIGNED NOT NULL,
  tipo_movimiento_inventario_id BIGINT UNSIGNED NOT NULL,
  cantidad DECIMAL(12,2) NOT NULL,
  fecha_movimiento DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  referencia VARCHAR(120) NULL,
  observacion VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  KEY idx_seguimiento_producto (producto_id),
  KEY idx_seguimiento_sede (sede_id),
  KEY idx_seguimiento_tipo (tipo_movimiento_inventario_id),
  KEY idx_seguimiento_fecha (fecha_movimiento),
  CONSTRAINT fk_seguimiento_producto FOREIGN KEY (producto_id) REFERENCES producto(id),
  CONSTRAINT fk_seguimiento_sede FOREIGN KEY (sede_id) REFERENCES sede(id),
  CONSTRAINT fk_seguimiento_tipo_mov FOREIGN KEY (tipo_movimiento_inventario_id) REFERENCES tipo_movimiento_inventario(id),
  CHECK (cantidad > 0),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

ALTER TABLE factura_detalle
  ADD CONSTRAINT fk_factura_detalle_producto FOREIGN KEY (producto_id) REFERENCES producto(id),
  ADD CONSTRAINT fk_factura_detalle_servicio FOREIGN KEY (servicio_id) REFERENCES servicio(id);

CREATE TABLE venta_producto (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  estadia_id BIGINT UNSIGNED NOT NULL,
  producto_id BIGINT UNSIGNED NOT NULL,
  cantidad DECIMAL(12,2) NOT NULL,
  valor_unitario DECIMAL(12,2) NOT NULL,
  valor_total DECIMAL(12,2) AS (cantidad * valor_unitario) STORED,
  fecha_venta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  KEY idx_venta_producto_estadia (estadia_id),
  KEY idx_venta_producto_producto (producto_id),
  CONSTRAINT fk_venta_producto_estadia FOREIGN KEY (estadia_id) REFERENCES estadia(id),
  CONSTRAINT fk_venta_producto_producto FOREIGN KEY (producto_id) REFERENCES producto(id),
  CHECK (cantidad > 0 AND valor_unitario >= 0),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE venta_servicio (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  estadia_id BIGINT UNSIGNED NOT NULL,
  servicio_id BIGINT UNSIGNED NOT NULL,
  cantidad DECIMAL(12,2) NOT NULL,
  valor_unitario DECIMAL(12,2) NOT NULL,
  valor_total DECIMAL(12,2) AS (cantidad * valor_unitario) STORED,
  fecha_venta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  KEY idx_venta_servicio_estadia (estadia_id),
  KEY idx_venta_servicio_servicio (servicio_id),
  CONSTRAINT fk_venta_servicio_estadia FOREIGN KEY (estadia_id) REFERENCES estadia(id),
  CONSTRAINT fk_venta_servicio_servicio FOREIGN KEY (servicio_id) REFERENCES servicio(id),
  CHECK (cantidad > 0 AND valor_unitario >= 0),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Notificación
CREATE TABLE termino_condicion (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(180) NOT NULL,
  contenido TEXT NOT NULL,
  version VARCHAR(20) NOT NULL,
  fecha_vigencia DATE NOT NULL,
  obligatorio BOOLEAN NOT NULL DEFAULT TRUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  UNIQUE KEY uk_termino_version (version),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE cliente_termino_condicion (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cliente_id BIGINT UNSIGNED NOT NULL,
  termino_condicion_id BIGINT UNSIGNED NOT NULL,
  fecha_aceptacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ip_aceptacion VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  UNIQUE KEY uk_cliente_termino (cliente_id, termino_condicion_id),
  KEY idx_ct_termino (termino_condicion_id),
  CONSTRAINT fk_ct_cliente FOREIGN KEY (cliente_id) REFERENCES cliente(id),
  CONSTRAINT fk_ct_termino FOREIGN KEY (termino_condicion_id) REFERENCES termino_condicion(id),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE fidelizacion_cliente (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cliente_id BIGINT UNSIGNED NOT NULL,
  nivel_fidelizacion_id BIGINT UNSIGNED NOT NULL,
  puntos INT NOT NULL DEFAULT 0,
  fecha_ultima_interaccion DATETIME NULL,
  observacion VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  UNIQUE KEY uk_fidelizacion_cliente (cliente_id),
  KEY idx_fidelizacion_nivel (nivel_fidelizacion_id),
  CONSTRAINT fk_fidelizacion_cliente FOREIGN KEY (cliente_id) REFERENCES cliente(id),
  CONSTRAINT fk_fidelizacion_nivel FOREIGN KEY (nivel_fidelizacion_id) REFERENCES nivel_fidelizacion(id),
  CHECK (puntos >= 0),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE promocion (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(180) NOT NULL,
  descripcion TEXT NULL,
  fecha_inicio DATETIME NOT NULL,
  fecha_fin DATETIME NOT NULL,
  activa BOOLEAN NOT NULL DEFAULT TRUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  CHECK (fecha_fin > fecha_inicio),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE promocion_canal (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  promocion_id BIGINT UNSIGNED NOT NULL,
  canal_notificacion_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  UNIQUE KEY uk_promocion_canal (promocion_id, canal_notificacion_id),
  KEY idx_promocion_canal_canal (canal_notificacion_id),
  CONSTRAINT fk_promocion_canal_promocion FOREIGN KEY (promocion_id) REFERENCES promocion(id),
  CONSTRAINT fk_promocion_canal_canal FOREIGN KEY (canal_notificacion_id) REFERENCES canal_notificacion(id),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE alerta (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cliente_id BIGINT UNSIGNED NULL,
  reserva_id BIGINT UNSIGNED NULL,
  canal_notificacion_id BIGINT UNSIGNED NOT NULL,
  estado_notificacion_id BIGINT UNSIGNED NOT NULL,
  titulo VARCHAR(180) NOT NULL,
  mensaje TEXT NOT NULL,
  fecha_envio DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  KEY idx_alerta_cliente (cliente_id),
  KEY idx_alerta_reserva (reserva_id),
  KEY idx_alerta_canal (canal_notificacion_id),
  KEY idx_alerta_estado (estado_notificacion_id),
  CONSTRAINT fk_alerta_cliente FOREIGN KEY (cliente_id) REFERENCES cliente(id),
  CONSTRAINT fk_alerta_reserva FOREIGN KEY (reserva_id) REFERENCES reserva(id),
  CONSTRAINT fk_alerta_canal FOREIGN KEY (canal_notificacion_id) REFERENCES canal_notificacion(id),
  CONSTRAINT fk_alerta_estado_notificacion FOREIGN KEY (estado_notificacion_id) REFERENCES estado_notificacion(id),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Mantenimiento
CREATE TABLE mantenimiento_habitacion (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  habitacion_id BIGINT UNSIGNED NOT NULL,
  empleado_id BIGINT UNSIGNED NULL,
  tipo_mantenimiento_id BIGINT UNSIGNED NOT NULL,
  estado_mantenimiento_id BIGINT UNSIGNED NOT NULL,
  fecha_inicio DATETIME NOT NULL,
  fecha_fin DATETIME NULL,
  costo_estimado DECIMAL(12,2) NOT NULL DEFAULT 0,
  costo_real DECIMAL(12,2) NULL,
  observacion VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  KEY idx_mant_hab_habitacion (habitacion_id),
  KEY idx_mant_hab_empleado (empleado_id),
  KEY idx_mant_hab_tipo (tipo_mantenimiento_id),
  KEY idx_mant_hab_estado (estado_mantenimiento_id),
  CONSTRAINT fk_mant_hab_habitacion FOREIGN KEY (habitacion_id) REFERENCES habitacion(id),
  CONSTRAINT fk_mant_hab_empleado FOREIGN KEY (empleado_id) REFERENCES empleado(id),
  CONSTRAINT fk_mant_hab_tipo FOREIGN KEY (tipo_mantenimiento_id) REFERENCES tipo_mantenimiento(id),
  CONSTRAINT fk_mant_hab_estado FOREIGN KEY (estado_mantenimiento_id) REFERENCES estado_mantenimiento(id),
  CHECK (fecha_fin IS NULL OR fecha_fin >= fecha_inicio),
  CHECK (costo_estimado >= 0),
  CHECK (costo_real IS NULL OR costo_real >= 0),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE mantenimiento_uso (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  mantenimiento_habitacion_id BIGINT UNSIGNED NOT NULL,
  motivo_uso VARCHAR(255) NOT NULL,
  detalle_actividad TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  UNIQUE KEY uk_mantenimiento_uso (mantenimiento_habitacion_id),
  CONSTRAINT fk_mantenimiento_uso_mh FOREIGN KEY (mantenimiento_habitacion_id) REFERENCES mantenimiento_habitacion(id),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE mantenimiento_remodelacion (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  mantenimiento_habitacion_id BIGINT UNSIGNED NOT NULL,
  descripcion_remodelacion TEXT NOT NULL,
  presupuesto_estimado DECIMAL(12,2) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
  UNIQUE KEY uk_mantenimiento_remodelacion (mantenimiento_habitacion_id),
  CONSTRAINT fk_mantenimiento_remodelacion_mh FOREIGN KEY (mantenimiento_habitacion_id) REFERENCES mantenimiento_habitacion(id),
  CHECK (presupuesto_estimado >= 0),
  CHECK (status IN ('ACTIVE','INACTIVE','DELETED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Seeds mínimas
INSERT INTO tipo_documento (codigo, nombre, descripcion) VALUES
('CC','Cédula de ciudadanía','Documento nacional'),
('CE','Cédula de extranjería','Documento para extranjeros'),
('PASAPORTE','Pasaporte','Documento internacional');

INSERT INTO tipo_documento_legal (codigo, nombre, descripcion) VALUES
('RUT','Registro Único Tributario','Identificación tributaria'),
('CAMARA_COMERCIO','Cámara de Comercio','Registro mercantil');

INSERT INTO cargo (codigo, nombre, descripcion) VALUES
('RECEPCIONISTA','Recepcionista','Atención de huéspedes'),
('ADMIN','Administrador','Administración de sede'),
('MANTENIMIENTO','Técnico de mantenimiento','Mantenimiento general');

INSERT INTO metodo_pago (codigo, nombre, descripcion, requiere_referencia, permite_pago_parcial) VALUES
('EFECTIVO','Efectivo','Pago en efectivo',0,1),
('TRANSFERENCIA','Transferencia','Transferencia bancaria',1,1),
('TARJETA_PASARELA','Tarjeta / Pasarela','Pago con tarjeta',1,1),
('MIXTO','Mixto','Combinación de métodos',0,1);

INSERT INTO tipo_habitacion (codigo, nombre, descripcion, capacidad_base, capacidad_maxima) VALUES
('SENCILLA','Sencilla','Habitación individual',1,2),
('DOBLE','Doble','Habitación doble',2,3),
('SUITE','Suite','Suite ejecutiva',2,4);

INSERT INTO tipo_dia (codigo, nombre, descripcion, multiplicador) VALUES
('NORMAL','Normal','Día normal',1.00),
('FIN_SEMANA','Fin de semana','Sábado y domingo',1.15),
('FERIADO','Feriado','Día festivo',1.25),
('EVENTO_ESPECIAL','Evento especial','Evento especial',1.40);

INSERT INTO temporada (codigo, nombre, descripcion, multiplicador) VALUES
('ALTA','Temporada alta','Alta demanda',1.30),
('BAJA','Temporada baja','Baja demanda',0.90);

INSERT INTO estado_habitacion (codigo, nombre, descripcion, permite_reserva, permite_check_in) VALUES
('DISPONIBLE','Disponible','Lista para asignación',1,1),
('OCUPADA','Ocupada','Con huésped',0,0),
('MANTENIMIENTO','Mantenimiento','Fuera de servicio temporal',0,0);

INSERT INTO estado_reserva (codigo, nombre) VALUES
('PENDIENTE_PAGO','Pendiente de pago'),
('CONFIRMADA','Confirmada'),
('CANCELADA','Cancelada'),
('VENCIDA','Vencida'),
('NO_SHOW','No show'),
('CHECK_IN','Check in realizado'),
('FINALIZADA','Finalizada');

INSERT INTO estado_estadia (codigo, nombre) VALUES
('PROGRAMADA','Programada'),
('ACTIVA','Activa'),
('FINALIZADA','Finalizada'),
('CANCELADA','Cancelada');

INSERT INTO estado_factura (codigo, nombre) VALUES
('BORRADOR','Borrador'),
('EMITIDA','Emitida'),
('PARCIALMENTE_PAGADA','Parcialmente pagada'),
('PAGADA','Pagada'),
('ANULADA','Anulada');

INSERT INTO estado_pago (codigo, nombre) VALUES
('REGISTRADO','Registrado'),
('APROBADO','Aprobado'),
('RECHAZADO','Rechazado'),
('ANULADO','Anulado'),
('REEMBOLSADO','Reembolsado');

INSERT INTO tipo_concepto_factura (codigo, nombre, descripcion) VALUES
('HOSPEDAJE','Hospedaje','Cobro por habitación'),
('PRODUCTO','Producto','Venta de producto'),
('SERVICIO','Servicio','Venta de servicio'),
('PENALIDAD','Penalidad','Cobro por cancelación');

INSERT INTO estado_mantenimiento (codigo, nombre) VALUES
('PROGRAMADO','Programado'),
('EN_PROCESO','En proceso'),
('FINALIZADO','Finalizado'),
('CANCELADO','Cancelado');

INSERT INTO tipo_mantenimiento (codigo, nombre) VALUES
('USO','Uso'),
('REMODELACION','Remodelación'),
('PREVENTIVO','Preventivo'),
('CORRECTIVO','Correctivo');

INSERT INTO estado_notificacion (codigo, nombre) VALUES
('PENDIENTE','Pendiente'),
('ENVIADA','Enviada'),
('FALLIDA','Fallida'),
('LEIDA','Leída'),
('CANCELADA','Cancelada');

INSERT INTO canal_notificacion (codigo, nombre) VALUES
('EMAIL','Correo electrónico'),
('SMS','SMS'),
('WHATSAPP','WhatsApp'),
('IN_APP','In App');

INSERT INTO tipo_movimiento_inventario (codigo, nombre, signo) VALUES
('ENTRADA','Entrada',1),
('SALIDA','Salida',-1),
('CONSUMO','Consumo',-1),
('AJUSTE','Ajuste',1),
('DEVOLUCION','Devolución',1);

INSERT INTO nivel_fidelizacion (codigo, nombre, puntos_minimos, beneficios) VALUES
('BRONCE','Bronce',0,'Beneficios básicos'),
('PLATA','Plata',1000,'Descuentos preferenciales'),
('ORO','Oro',3000,'Beneficios premium');

INSERT INTO rol (codigo, nombre, descripcion) VALUES
('ADMIN','Administrador','Acceso total'),
('RECEPCION','Recepción','Operación de reservas y estadías'),
('CAJA','Caja','Gestión de pagos y facturas');

INSERT INTO accion_permiso (codigo, nombre) VALUES
('CREATE','Crear'),
('READ','Consultar'),
('UPDATE','Actualizar'),
('DELETE','Eliminar lógico'),
('APPROVE','Aprobar');

INSERT INTO modulo (codigo, nombre, descripcion, ruta_base) VALUES
('PARAMETRIZACION','Parametrización','Configuración general','/parametrizacion'),
('DISTRIBUCION','Distribución','Habitaciones y sedes','/distribucion'),
('PRESTACION_SERVICIO','Prestación de servicio','Reservas y estadías','/servicio'),
('FACTURACION','Facturación','Facturas y pagos','/facturacion'),
('INVENTARIO','Inventario','Gestión de inventario','/inventario'),
('NOTIFICACION','Notificación','Alertas y promociones','/notificacion'),
('SEGURIDAD','Seguridad','Roles y permisos','/seguridad'),
('MANTENIMIENTO','Mantenimiento','Mantenimiento de habitaciones','/mantenimiento');

INSERT INTO vista (modulo_id, codigo, nombre, descripcion, ruta, es_activa)
SELECT m.id, CONCAT(m.codigo,'_MAIN'), CONCAT('Vista ', m.nombre), CONCAT('Vista principal de ', m.nombre), CONCAT(m.ruta_base,'/main'), 1
FROM modulo m;

INSERT INTO permiso (modulo_id, vista_id, accion_permiso_id, codigo, nombre, descripcion)
SELECT m.id, v.id, a.id,
       CONCAT(m.codigo,'_',a.codigo),
       CONCAT(a.nombre,' ',m.nombre),
       CONCAT('Permiso para ',a.nombre,' en módulo ',m.nombre)
FROM modulo m
JOIN vista v ON v.modulo_id = m.id AND v.codigo = CONCAT(m.codigo,'_MAIN')
JOIN accion_permiso a ON a.codigo IN ('CREATE','READ','UPDATE');

INSERT INTO servicio (codigo, nombre, descripcion, valor_venta, disponible) VALUES
('RESTAURANTE','RESTAURANTE','Consumo en restaurante',0,1),
('MINIBAR','MINIBAR','Consumo de minibar',0,1),
('LAVANDERIA','LAVANDERIA','Servicio de lavandería',0,1),
('PARQUEADERO','PARQUEADERO','Servicio de parqueadero',0,1),
('TRANSPORTE','TRANSPORTE','Servicio de transporte',0,1),
('SPA','SPA','Servicio de spa',0,1),
('ROOM_SERVICE','ROOM_SERVICE','Servicio a la habitación',0,1),
('EVENTOS','EVENTOS','Eventos y salones',0,1),
('LATE_CHECK_OUT','LATE_CHECK_OUT','Salida tardía',0,1),
('CAMA_ADICIONAL','CAMA_ADICIONAL','Cama adicional',0,1),
('MASCOTA','MASCOTA','Alojamiento de mascota',0,1),
('COWORKING','COWORKING','Espacio de coworking',0,1);

-- Vistas
CREATE VIEW vw_modulo_vista AS
SELECT
  m.id AS modulo_id,
  m.codigo AS modulo_codigo,
  m.nombre AS modulo_nombre,
  v.id AS vista_id,
  v.codigo AS vista_codigo,
  v.nombre AS vista_nombre,
  v.ruta AS vista_ruta
FROM modulo m
JOIN vista v ON v.modulo_id = m.id
WHERE m.status = 'ACTIVE'
  AND v.status = 'ACTIVE'
  AND v.es_activa = 1
  AND m.deleted_at IS NULL
  AND v.deleted_at IS NULL;

CREATE VIEW vw_stock_producto AS
SELECT
  p.id AS producto_id,
  p.codigo AS producto_codigo,
  p.nombre AS producto,
  s.id AS sede_id,
  s.nombre AS sede,
  COALESCE(SUM(sp.cantidad * tmi.signo), 0) AS stock_actual
FROM producto p
JOIN sede s ON s.id = p.sede_id
LEFT JOIN seguimiento_producto sp ON sp.producto_id = p.id AND sp.sede_id = s.id AND sp.status <> 'DELETED' AND sp.deleted_at IS NULL
LEFT JOIN tipo_movimiento_inventario tmi ON tmi.id = sp.tipo_movimiento_inventario_id
WHERE p.status <> 'DELETED' AND p.deleted_at IS NULL
GROUP BY p.id, p.codigo, p.nombre, s.id, s.nombre;

CREATE VIEW vw_disponibilidad_inventario AS
SELECT
  vsp.producto_id,
  vsp.producto_codigo,
  vsp.producto,
  vsp.sede_id,
  vsp.sede,
  vsp.stock_actual,
  p.stock_minimo,
  CASE
    WHEN vsp.stock_actual <= 0 THEN 'SIN_STOCK'
    WHEN vsp.stock_actual < p.stock_minimo THEN 'BAJO_STOCK'
    ELSE 'DISPONIBLE'
  END AS estado_inventario
FROM vw_stock_producto vsp
JOIN producto p ON p.id = vsp.producto_id;

CREATE VIEW vw_dashboard_mantenimiento AS
SELECT
  mh.id AS mantenimiento_id,
  se.id AS sede_id,
  se.nombre AS sede,
  h.id AS habitacion_id,
  h.numero AS habitacion,
  tm.codigo AS tipo_mantenimiento_codigo,
  tm.nombre AS tipo_mantenimiento,
  em.codigo AS estado_mantenimiento_codigo,
  em.nombre AS estado_mantenimiento,
  mh.fecha_inicio,
  mh.fecha_fin,
  mh.costo_estimado,
  mh.costo_real,
  mh.observacion
FROM mantenimiento_habitacion mh
JOIN habitacion h ON h.id = mh.habitacion_id
JOIN sede se ON se.id = h.sede_id
JOIN tipo_mantenimiento tm ON tm.id = mh.tipo_mantenimiento_id
JOIN estado_mantenimiento em ON em.id = mh.estado_mantenimiento_id
WHERE mh.status = 'ACTIVE'
  AND mh.deleted_at IS NULL
  AND em.codigo IN ('PROGRAMADO','EN_PROCESO');
