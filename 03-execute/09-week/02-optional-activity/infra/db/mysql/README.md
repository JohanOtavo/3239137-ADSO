# Base de datos MySQL 8.0 - Sistema Hotelero (3FN)

## Requisitos
- Docker
- Docker Compose

## Configuración de variables de entorno
```bash
cp .env.example .env
```

## Levantar la base de datos
```bash
docker compose up -d mysql
```

## Revisar logs
```bash
docker logs sistema_hotelero_mysql
```

## Conectarse por terminal con usuario de aplicación
```bash
docker exec -it sistema_hotelero_mysql mysql -uhotel_user -photel_password sistema_hotelero
```

## Conectarse con root
```bash
docker exec -it sistema_hotelero_mysql mysql -uroot -proot_password sistema_hotelero
```

## Reiniciar todo desde cero
```bash
docker compose down -v
docker compose up -d mysql
```

## Consultas de verificación
```bash
docker exec -i sistema_hotelero_mysql mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "SHOW DATABASES;"

docker exec -i sistema_hotelero_mysql mysql -uroot -p$MYSQL_ROOT_PASSWORD -D sistema_hotelero -e "
SELECT COUNT(*) AS total_tablas
FROM information_schema.tables
WHERE table_schema = 'sistema_hotelero'
  AND table_type = 'BASE TABLE';"

docker exec -i sistema_hotelero_mysql mysql -uroot -p$MYSQL_ROOT_PASSWORD -D sistema_hotelero -e "
SELECT COUNT(*) AS total_vistas
FROM information_schema.tables
WHERE table_schema = 'sistema_hotelero'
  AND table_type = 'VIEW';"

docker exec -i sistema_hotelero_mysql mysql -uroot -p$MYSQL_ROOT_PASSWORD -D sistema_hotelero -e "SELECT * FROM vw_stock_producto LIMIT 5;"
docker exec -i sistema_hotelero_mysql mysql -uroot -p$MYSQL_ROOT_PASSWORD -D sistema_hotelero -e "SELECT * FROM vw_disponibilidad_inventario LIMIT 5;"
docker exec -i sistema_hotelero_mysql mysql -uroot -p$MYSQL_ROOT_PASSWORD -D sistema_hotelero -e "SELECT * FROM vw_dashboard_mantenimiento LIMIT 5;"
```

## Resumen de diseño
- **Tablas principales:**
  - Seguridad: `persona`, `usuario`, `rol`, `permiso`, `modulo`, `vista`, `usuario_rol`, `rol_permiso`, `accion_permiso`.
  - Operación hotelera: `reserva` (cabecera) + `reserva_habitacion` (detalle), `estadia` (cabecera) + `estadia_habitacion` (detalle), `factura` (cabecera) + `factura_detalle` (detalle).
  - Inventario: `producto`, `servicio`, `proveedor`, `seguimiento_producto`.
  - Notificación: `alerta`, `promocion`, `promocion_canal`, `termino_condicion`, `cliente_termino_condicion`, `fidelizacion_cliente`.
  - Mantenimiento: `mantenimiento_habitacion`, `mantenimiento_uso`, `mantenimiento_remodelacion`.

- **Vistas:**
  - `vw_modulo_vista`: módulos y vistas activas.
  - `vw_stock_producto`: stock calculado por producto/sede.
  - `vw_disponibilidad_inventario`: estado de inventario (`SIN_STOCK`, `BAJO_STOCK`, `DISPONIBLE`).
  - `vw_dashboard_mantenimiento`: mantenimientos activos con contexto de sede/habitación.

- **Normalización aplicada (3FN):**
  - Catálogos separados para estados, tipos, canales, métodos, conceptos y acciones.
  - Evita duplicidad de persona en cliente/empleado/usuario (todos referencian `persona`).
  - Relación muchos-a-muchos resuelta por tablas intermedias (`usuario_rol`, `rol_permiso`, `promocion_canal`).
  - Se evita persistir stock actual; se calcula por vista a partir de movimientos.
  - Se usan columnas generadas para totales en facturación/ventas cuando aplica.

- **Datos semilla:**
  - Incluye catálogos mínimos solicitados y servicios base.

- **Eliminación lógica:**
  - Tablas operativas con `created_at`, `updated_at`, `deleted_at`, `status`.
  - Política esperada: marcar `status='DELETED'` y `deleted_at`, sin borrado físico en reservas, estadías, facturas, pagos, movimientos y auditoría.
