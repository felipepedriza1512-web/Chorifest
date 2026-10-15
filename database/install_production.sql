-- ============================================================
-- CHORIFEST: SCRIPT DE INSTALACION LIMPIA DE PRODUCCION
-- ============================================================
-- USO:
--   1. Crea la base de datos en el panel del hosting.
--   2. Importa este archivo via phpMyAdmin o CLI:
--        mysql -u usuario -p nombre_base < install_production.sql
--   3. Cambia la contrasena del administrador en el primer login.
-- ============================================================

SET FOREIGN_KEY_CHECKS=0;
SET NAMES utf8mb4;

-- ========== ESTRUCTURA: ROLES ==========
DROP TABLE IF EXISTS 
ol;
CREATE TABLE 
ol (
  id_rol int NOT NULL AUTO_INCREMENT,
  
ombre varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  descripcion varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (id_rol),
  UNIQUE KEY 
ombre (
ombre)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO 
ol VALUES
  (1,'CLIENTE','Usuario que realiza pedidos'),
  (2,'ADMINISTRADOR','Gestiona el sistema'),
  (3,'COCINERA','Prepara los pedidos'),
  (4,'DOMICILIARIO','Realiza las entregas');

-- ========== ESTRUCTURA: ESTADO_PEDIDO ==========
DROP TABLE IF EXISTS estado_pedido;
CREATE TABLE estado_pedido (
  id_estado int NOT NULL AUTO_INCREMENT,
  
ombre varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  descripcion varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (id_estado),
  UNIQUE KEY 
ombre (
ombre)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO estado_pedido VALUES
  (1, 'Pendiente de pago',  'Esperando confirmacion de pago'),
  (2, 'Confirmado',         'Pago confirmado, en espera de cocina'),
  (3, 'En preparacion',     'La cocinera esta preparando el pedido'),
  (4, 'Listo',              'Pedido listo para despacho'),
  (5, 'En camino',          'Domiciliario en ruta'),
  (6, 'Entregado',          'Pedido entregado correctamente'),
  (7, 'Cancelado',          'Pedido cancelado por el cliente o administrador'),
  (8, 'Rechazado',          'Pedido rechazado por el administrador'),
  (9, 'Pago rechazado',     'El pago fue rechazado o no verificado'),
  (10,'Pendiente',          'Estado inicial'),
  (11,'Devolucion',         'Se procesa devolucion'),
  (12,'En proceso',         'Pedido en proceso general');

-- ========== ESTRUCTURA: CATEGORIA ==========
DROP TABLE IF EXISTS categoria;
CREATE TABLE categoria (
  id_categoria int NOT NULL AUTO_INCREMENT,
  
ombre varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  descripcion varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  estado varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVA',
  PRIMARY KEY (id_categoria),
  UNIQUE KEY 
ombre (
ombre)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO categoria VALUES
  (1,'Chorizos',    'Chorizos artesanales',          'ACTIVA'),
  (2,'Combos',      'Combos para compartir',          'ACTIVA'),
  (3,'Bebidas',     'Bebidas frias y calientes',      'ACTIVA'),
  (4,'Salsas',      'Salsas para acompanar',          'ACTIVA'),
  (5,'Adiciones',   'Productos adicionales',          'ACTIVA'),
  (6,'Especiales',  'Productos especiales',           'ACTIVA'),
  (7,'Promociones', 'Productos en promocion',         'ACTIVA'),
  (8,'Familiares',  'Presentaciones familiares',      'ACTIVA'),
  (9,'Individuales','Presentaciones individuales',    'ACTIVA'),
  (10,'Temporada',  'Productos de temporada',         'ACTIVA');

-- ========== ESTRUCTURA: ZONA_ENVIO ==========
DROP TABLE IF EXISTS zona_envio;
CREATE TABLE zona_envio (
  id_zona int NOT NULL AUTO_INCREMENT,
  
ombre varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  costo_domicilio decimal(10,2) NOT NULL DEFAULT '0.00',
  	iempo_estimado_min int DEFAULT NULL,
  estado varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVA',
  PRIMARY KEY (id_zona),
  UNIQUE KEY 
ombre (
ombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO zona_envio VALUES
  (1,'Centro',            5000.00, 20,'ACTIVA'),
  (2,'Norte',             6000.00, 30,'ACTIVA'),
  (3,'Sur',               6000.00, 30,'ACTIVA'),
  (4,'Oriente',           7000.00, 35,'ACTIVA'),
  (5,'Occidente',         7000.00, 35,'ACTIVA'),
  (6,'Barrio Modelo',     5000.00, 20,'ACTIVA'),
  (7,'El Recreo',         5000.00, 20,'ACTIVA'),
  (8,'La Esmeralda',      6000.00, 25,'ACTIVA'),
  (9,'Los Andes',         7000.00, 40,'ACTIVA'),
  (10,'Zona Universitaria',6000.00,25,'ACTIVA'),
  (11,'Rivera',          15000.00, 60,'ACTIVA'),
  (12,'Campoalegre',     30000.00, 60,'ACTIVA');

-- ========== ESTRUCTURA: USUARIO ==========
DROP TABLE IF EXISTS usuario;
CREATE TABLE usuario (
  id_usuario int NOT NULL AUTO_INCREMENT,
  id_rol int NOT NULL,
  
ombre varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  correo varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  password_hash varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  	elefono varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  estado varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVO',
  echa_registro datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  debe_cambiar_password tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id_usuario),
  UNIQUE KEY correo (correo),
  KEY idx_usuario_rol (id_rol),
  CONSTRAINT usuario_ibfk_1 FOREIGN KEY (id_rol) REFERENCES 
ol (id_rol)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ADMINISTRADOR INICIAL DE PRODUCCION
-- Contrasena temporal: Chorifest@2026!
-- DEBE CAMBIARSE EN EL PRIMER LOGIN (debe_cambiar_password = 1)
-- Hash: bcrypt de "Chorifest@2026!" generado con PASSWORD_BCRYPT cost=12
-- Regenerar con: php -r "echo password_hash('Chorifest@2026!', PASSWORD_BCRYPT, ['cost'=>12]);"
-- ============================================================
INSERT INTO usuario (id_rol, 
ombre, correo, password_hash, 	elefono, estado, debe_cambiar_password) VALUES
  (2, 'Administrador', 'admin@chorifest.com',
   '\\\.Hash.Reemplazar.Con.PHP.password_hash.Run',
   NULL, 'ACTIVO', 1);

-- ========== TABLAS ADICIONALES (esquema sin datos de prueba) ==========
-- Nota: Las demas tablas (pedido, pago, producto, etc.) se crean vacias.
-- Importa bd_final_fk.sql SOLO para la estructura (sin INSERTs de prueba)
-- o crea los productos desde el panel de administrador.

SET FOREIGN_KEY_CHECKS=1;
