SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS `administrador`;
CREATE TABLE `administrador` (
  `id_usuario` int NOT NULL,
  PRIMARY KEY (`id_usuario`),
  CONSTRAINT `administrador_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
LOCK TABLES `administrador` WRITE;
INSERT INTO `administrador` VALUES (3),(13);
UNLOCK TABLES;
DROP TABLE IF EXISTS `asignacion_cocina`;
CREATE TABLE `asignacion_cocina` (
  `id_asignacion` int NOT NULL AUTO_INCREMENT,
  `id_pedido` int NOT NULL,
  `id_cocinera` int NOT NULL,
  `fecha_asignacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_inicio` datetime DEFAULT NULL,
  `fecha_finalizacion` datetime DEFAULT NULL,
  PRIMARY KEY (`id_asignacion`),
  UNIQUE KEY `id_pedido` (`id_pedido`),
  KEY `id_cocinera` (`id_cocinera`),
  CONSTRAINT `asignacion_cocina_ibfk_1` FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id_pedido`) ON DELETE CASCADE,
  CONSTRAINT `asignacion_cocina_ibfk_2` FOREIGN KEY (`id_cocinera`) REFERENCES `cocinera` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
LOCK TABLES `asignacion_cocina` WRITE;
INSERT INTO `asignacion_cocina` VALUES (1,1,4,'2026-08-01 09:00:00','2026-08-01 09:05:00','2026-08-01 09:20:00'),(2,2,4,'2026-08-02 10:00:00','2026-08-02 10:05:00','2026-08-02 10:25:00'),(3,3,4,'2026-08-03 11:00:00','2026-08-03 11:05:00','2026-08-03 11:20:00'),(4,4,4,'2026-08-04 12:00:00','2026-08-04 12:05:00',NULL),(5,5,4,'2026-08-05 13:00:00','2026-08-05 13:05:00','2026-08-05 13:30:00'),(6,6,4,'2026-08-06 14:00:00',NULL,NULL),(7,7,4,'2026-08-07 15:00:00',NULL,NULL),(8,8,4,'2026-08-08 16:00:00',NULL,NULL),(9,9,4,'2026-08-09 17:00:00',NULL,NULL),(10,10,4,'2026-08-10 18:00:00',NULL,NULL);
UNLOCK TABLES;
DROP TABLE IF EXISTS `asignacion_domicilio`;
CREATE TABLE `asignacion_domicilio` (
  `id_asignacion` int NOT NULL AUTO_INCREMENT,
  `id_pedido` int NOT NULL,
  `id_domiciliario` int NOT NULL,
  `fecha_asignacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_salida` datetime DEFAULT NULL,
  `fecha_entrega` datetime DEFAULT NULL,
  PRIMARY KEY (`id_asignacion`),
  UNIQUE KEY `id_pedido` (`id_pedido`),
  KEY `id_domiciliario` (`id_domiciliario`),
  CONSTRAINT `asignacion_domicilio_ibfk_1` FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id_pedido`) ON DELETE CASCADE,
  CONSTRAINT `asignacion_domicilio_ibfk_2` FOREIGN KEY (`id_domiciliario`) REFERENCES `domiciliario` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
LOCK TABLES `asignacion_domicilio` WRITE;
INSERT INTO `asignacion_domicilio` VALUES (1,1,5,'2026-08-01 09:30:00','2026-08-01 09:40:00','2026-08-01 10:00:00'),(2,2,5,'2026-08-02 10:30:00','2026-08-02 10:40:00',NULL),(3,3,5,'2026-08-03 11:30:00',NULL,NULL),(4,4,5,'2026-08-04 12:30:00',NULL,NULL),(5,5,5,'2026-08-05 13:40:00',NULL,NULL),(6,6,5,'2026-08-06 14:30:00',NULL,NULL),(7,7,5,'2026-08-07 15:30:00',NULL,NULL),(8,8,5,'2026-08-08 16:30:00',NULL,NULL),(9,9,5,'2026-08-09 17:30:00',NULL,NULL),(10,10,5,'2026-08-10 18:30:00',NULL,NULL);
UNLOCK TABLES;
DROP TABLE IF EXISTS `auditoria`;
CREATE TABLE `auditoria` (
  `id_auditoria` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `accion` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entidad` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_registro` int DEFAULT NULL,
  `descripcion` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_auditoria`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `auditoria_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
LOCK TABLES `auditoria` WRITE;
INSERT INTO `auditoria` VALUES (1,3,'CREAR','PRODUCTO',1,'Se creo un producto','2026-08-01 08:00:00'),(2,3,'ACTUALIZAR','PRODUCTO',2,'Se actualizo el precio','2026-08-02 08:00:00'),(3,3,'ACTUALIZAR','INVENTARIO',1,'Se actualizo inventario','2026-08-03 08:00:00'),(4,3,'CAMBIAR_ESTADO','PEDIDO',2,'Se confirmo pedido','2026-08-04 08:00:00'),(5,3,'ASIGNAR','DOMICILIO',3,'Se asigno domiciliario','2026-08-05 08:00:00'),(6,3,'VERIFICAR','PAGO',3,'Se verifico transferencia','2026-08-06 08:00:00'),(7,3,'ATENDER','NOVEDAD',1,'Se atendio novedad','2026-08-07 08:00:00'),(8,3,'ACTUALIZAR','ZONA',2,'Se actualizo zona','2026-08-08 08:00:00'),(9,3,'CANCELAR','PEDIDO',7,'Se cancelo pedido','2026-08-09 08:00:00'),(10,3,'GENERAR','REPORTE',NULL,'Se genero reporte diario','2026-08-10 08:00:00');
UNLOCK TABLES;
DROP TABLE IF EXISTS `calificacion`;
CREATE TABLE `calificacion` (
  `id_calificacion` int NOT NULL AUTO_INCREMENT,
  `id_pedido` int NOT NULL,
  `id_cliente` int NOT NULL,
  `puntuacion` int NOT NULL,
  `comentario` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_calificacion`),
  UNIQUE KEY `id_pedido` (`id_pedido`),
  KEY `id_cliente` (`id_cliente`),
  CONSTRAINT `calificacion_ibfk_1` FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id_pedido`) ON DELETE CASCADE,
  CONSTRAINT `calificacion_ibfk_2` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_usuario`),
  CONSTRAINT `calificacion_chk_1` CHECK ((`puntuacion` between 1 and 5))
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
LOCK TABLES `calificacion` WRITE;
INSERT INTO `calificacion` VALUES (1,1,1,5,'Excelente servicio','2026-08-01 10:30:00'),(2,2,2,4,'Muy buen pedido','2026-08-02 12:00:00'),(3,3,6,5,'Muy rico','2026-08-03 12:30:00'),(4,4,7,4,'Buen producto','2026-08-04 14:00:00'),(5,5,1,5,'Excelente','2026-08-05 15:00:00'),(6,6,2,3,'Servicio normal','2026-08-06 16:00:00'),(7,7,6,2,'Pedido cancelado','2026-08-07 17:00:00'),(8,8,7,4,'Buen sabor','2026-08-08 18:00:00'),(9,9,1,2,'Problema con el pago','2026-08-09 19:00:00'),(10,10,2,1,'Pedido rechazado','2026-08-10 20:00:00');
UNLOCK TABLES;
DROP TABLE IF EXISTS `categoria`;
CREATE TABLE `categoria` (
  `id_categoria` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVA',
  PRIMARY KEY (`id_categoria`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
LOCK TABLES `categoria` WRITE;
INSERT INTO `categoria` VALUES (1,'Chorizos','Chorizos tradicionales','ACTIVA'),(2,'Combos','Combos para compartir','ACTIVA'),(3,'Bebidas','Bebidas frâ”œÂ¡as','ACTIVA'),(4,'Salsas','Salsas para acompaâ”œâ–’ar','ACTIVA'),(5,'Adiciones','Productos adicionales','ACTIVA'),(6,'Especiales','Productos especiales','ACTIVA'),(7,'Promociones','Productos en promociâ”œâ”‚n','ACTIVA'),(8,'Familiares','Presentaciones familiares','ACTIVA'),(9,'Individuales','Presentaciones individuales','ACTIVA'),(10,'Temporada','Productos temporales','ACTIVA');
UNLOCK TABLES;
DROP TABLE IF EXISTS `cliente`;
CREATE TABLE `cliente` (
  `id_usuario` int NOT NULL,
  PRIMARY KEY (`id_usuario`),
  CONSTRAINT `cliente_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
LOCK TABLES `cliente` WRITE;
INSERT INTO `cliente` VALUES (1),(2),(6),(7);
UNLOCK TABLES;
DROP TABLE IF EXISTS `cocinera`;
CREATE TABLE `cocinera` (
  `id_usuario` int NOT NULL,
  `turno` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_usuario`),
  CONSTRAINT `cocinera_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
LOCK TABLES `cocinera` WRITE;
INSERT INTO `cocinera` VALUES (4,NULL);
UNLOCK TABLES;
DROP TABLE IF EXISTS `detalle_pedido`;
CREATE TABLE `detalle_pedido` (
  `id_detalle_pedido` int NOT NULL AUTO_INCREMENT,
  `id_pedido` int NOT NULL,
  `id_producto` int NOT NULL,
  `cantidad` int NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id_detalle_pedido`),
  KEY `idx_detalle_pedido` (`id_pedido`),
  KEY `idx_detalle_producto` (`id_producto`),
  CONSTRAINT `detalle_pedido_ibfk_1` FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id_pedido`) ON DELETE CASCADE,
  CONSTRAINT `detalle_pedido_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
LOCK TABLES `detalle_pedido` WRITE;
INSERT INTO `detalle_pedido` VALUES (1,1,1,1,12000.00,12000.00),(2,2,2,2,11000.00,22000.00),(3,3,3,1,15000.00,15000.00),(4,4,4,1,28000.00,28000.00),(5,5,5,1,50000.00,50000.00),(6,6,10,1,18000.00,18000.00),(7,7,1,1,12000.00,12000.00),(8,8,4,1,28000.00,28000.00),(9,9,3,1,15000.00,15000.00),(10,10,2,1,11000.00,11000.00),(11,17,1,1,12000.00,12000.00),(12,17,6,1,4000.00,4000.00),(13,18,1,1,12000.00,12000.00),(14,18,5,1,50000.00,50000.00),(15,19,1,1,12000.00,12000.00),(16,19,9,1,7000.00,7000.00),(17,20,1,2,12000.00,24000.00),(18,20,4,3,28000.00,84000.00),(19,21,1,1,12000.00,12000.00),(20,22,1,3,12000.00,36000.00),(21,23,5,1,50000.00,50000.00),(22,23,8,1,3000.00,3000.00),(23,24,2,1,11000.00,11000.00),(24,25,2,1,11000.00,11000.00),(25,26,2,1,11000.00,11000.00),(26,27,2,1,11000.00,11000.00),(27,28,2,1,11000.00,11000.00),(28,29,2,1,11000.00,11000.00),(29,29,5,1,50000.00,50000.00),(30,30,2,1,11000.00,11000.00),(31,31,1,1,12000.00,12000.00),(32,32,2,1,11000.00,11000.00),(33,32,9,1,7000.00,7000.00),(34,32,7,1,6000.00,6000.00),(35,33,3,2,15000.00,30000.00),(36,34,2,1,11000.00,11000.00),(37,35,2,1,11000.00,11000.00),(38,36,1,8,12000.00,96000.00);
UNLOCK TABLES;
DROP TABLE IF EXISTS `direccion`;
CREATE TABLE `direccion` (
  `id_direccion` int NOT NULL AUTO_INCREMENT,
  `id_cliente` int NOT NULL,
  `id_zona` int DEFAULT NULL,
  `direccion` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `barrio` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `referencia` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitud` decimal(10,8) DEFAULT NULL,
  `longitud` decimal(11,8) DEFAULT NULL,
  `predeterminada` tinyint(1) NOT NULL DEFAULT '0',
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVA',
  PRIMARY KEY (`id_direccion`),
  KEY `id_zona` (`id_zona`),
  KEY `idx_direccion_cliente` (`id_cliente`),
  CONSTRAINT `direccion_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_usuario`),
  CONSTRAINT `direccion_ibfk_2` FOREIGN KEY (`id_zona`) REFERENCES `zona_envio` (`id_zona`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
LOCK TABLES `direccion` WRITE;
INSERT INTO `direccion` VALUES (1,1,1,'Calle 5 # 10-20','Centro','Frente al parque',NULL,NULL,1,'ACTIVA'),(2,1,7,'Calle 15 # 8-42','El Recreo','Casa blanca',NULL,NULL,0,'ACTIVA'),(3,2,2,'Carrera 10 # 20-15','Norte','Cerca a la iglesia',NULL,NULL,1,'ACTIVA'),(4,2,3,'Carrera 8 # 30-22','Sur','Edificio azul',NULL,NULL,0,'ACTIVA'),(5,6,4,'Calle 12 # 14-08','Oriente','Esquina principal',NULL,NULL,1,'ACTIVA'),(6,6,5,'Carrera 15 # 18-30','Occidente','Local 3',NULL,NULL,0,'ACTIVA'),(7,7,6,'Calle 7 # 9-12','Barrio Modelo','Casa de dos pisos',NULL,NULL,1,'ACTIVA'),(8,7,8,'Carrera 6 # 25-11','La Esmeralda','Frente al colegio',NULL,NULL,0,'ACTIVA'),(9,1,9,'Calle 22 # 5-19','Los Andes','Porton negro',NULL,NULL,0,'ACTIVA'),(10,2,10,'Carrera 4 # 12-25','Zona Universitaria','Edificio 2',NULL,NULL,0,'ACTIVA'),(20,13,1,'Carrera 58a N 19-46','las palmas ','Alfrente cancha',NULL,NULL,0,'ACTIVA'),(21,16,4,'Carrera 58a N 19-46','las palmas ','Alfrente cancha',NULL,NULL,0,'ACTIVA'),(22,16,2,'Calle 83A #2 A 10','Dario Echandia','Casa blanca con rejas negras',NULL,NULL,0,'ACTIVA'),(23,13,3,'Carrera 58a N 19-46','canaima','Casa esquinera ',NULL,NULL,0,'ACTIVA'),(24,13,2,'el sena','Matas verdes','Casa esquinera ',NULL,NULL,0,'ACTIVA'),(25,13,5,'crra 45a 18-45','EL sena','Casa esquinera ',NULL,NULL,0,'ACTIVA'),(26,13,2,'Crra 900-1000','El tesoro','Cerca a la cancha',NULL,NULL,0,'ACTIVA'),(27,13,1,'Carrera 58a N 19-46','canaima','Casa esquinera ',NULL,NULL,0,'ACTIVA'),(28,16,3,'crra 58a 19 46','Matas verdes','Alfrente cancha',NULL,NULL,0,'ACTIVA'),(29,15,3,'crra 58a 19 46','Matas verdes','Casa esquinera ',NULL,NULL,0,'ACTIVA'),(30,14,2,'Carrera 58a N 19-46','las palmas ','Casa blanca con rejas negras',NULL,NULL,0,'ACTIVA'),(31,16,2,'La ulloa','El sena','Afuera ',NULL,NULL,0,'ACTIVA'),(32,13,8,'el sena','pedregal','Alfrente cancha',NULL,NULL,0,'ACTIVA'),(33,13,3,'Carrera 58a N 19-46','Matas verdes','Alfrente cancha',NULL,NULL,0,'ACTIVA'),(34,13,8,'Carrera 900-100','Los quirinales','Al frente del arbol',NULL,NULL,0,'ACTIVA'),(35,13,4,'calle 5c N 23 85 ','LA GAITANA','CASA ',NULL,NULL,0,'ACTIVA'),(36,13,3,'Carrera 58a N 19-46','las palmas ','Alfrente del parque',NULL,NULL,0,'ACTIVA'),(37,13,11,'Carrera 58a N 19-46','canaima','Casa blanca con rejas negras',NULL,NULL,0,'ACTIVA'),(38,13,5,'carrera 700-000','barrio la gaitana','casa azul',NULL,NULL,0,'ACTIVA'),(39,13,10,'crra 58a 19 46','EL sena','Cerca a la cancha',NULL,NULL,0,'ACTIVA');
UNLOCK TABLES;
DROP TABLE IF EXISTS `domiciliario`;
CREATE TABLE `domiciliario` (
  `id_usuario` int NOT NULL,
  `tipo_vehiculo` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `placa` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'ACTIVO',
  PRIMARY KEY (`id_usuario`),
  CONSTRAINT `domiciliario_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
LOCK TABLES `domiciliario` WRITE;
INSERT INTO `domiciliario` VALUES (5,NULL,NULL,'ACTIVO');
UNLOCK TABLES;
DROP TABLE IF EXISTS `estado_pedido`;
CREATE TABLE `estado_pedido` (
  `id_estado` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_estado`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
LOCK TABLES `estado_pedido` WRITE;
INSERT INTO `estado_pedido` VALUES (1,'PENDIENTE','Pedido pendiente'),(2,'CONFIRMADO','Pedido confirmado'),(3,'EN PREPARACION','Pedido en preparaciâ”œâ”‚n'),(4,'LISTO','Pedido listo para despacho'),(5,'EN CAMINO','Pedido enviado'),(6,'ENTREGADO','Pedido entregado'),(7,'CANCELADO','Pedido cancelado'),(8,'PENDIENTE DE PAGO','Pedido pendiente de pago'),(9,'PAGO RECHAZADO','Pago rechazado'),(10,'RECHAZADO','Pedido rechazado'),(11,'DEMORADO','Retraso en la entrega reportado'),(12,'PROBLEMA','Problema reportado con el pedido/entrega');
UNLOCK TABLES;
DROP TABLE IF EXISTS `historial_pedido`;
CREATE TABLE `historial_pedido` (
  `id_historial` int NOT NULL AUTO_INCREMENT,
  `id_pedido` int NOT NULL,
  `id_estado` int NOT NULL,
  `id_usuario` int DEFAULT NULL,
  `comentario` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_historial`),
  KEY `id_estado` (`id_estado`),
  KEY `id_usuario` (`id_usuario`),
  KEY `idx_historial_pedido` (`id_pedido`),
  CONSTRAINT `historial_pedido_ibfk_1` FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id_pedido`) ON DELETE CASCADE,
  CONSTRAINT `historial_pedido_ibfk_2` FOREIGN KEY (`id_estado`) REFERENCES `estado_pedido` (`id_estado`),
  CONSTRAINT `historial_pedido_ibfk_3` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
LOCK TABLES `historial_pedido` WRITE;
INSERT INTO `historial_pedido` VALUES (1,1,1,1,'Pedido creado','2026-08-01 08:30:00'),(2,2,2,3,'Pedido confirmado','2026-08-02 09:00:00'),(3,3,3,4,'Pedido en preparaciâ”œâ”‚n','2026-08-03 10:00:00'),(4,4,4,4,'Pedido listo','2026-08-04 12:00:00'),(5,5,2,3,'Pedido confirmado','2026-08-05 12:30:00'),(6,6,1,1,'Pedido recibido','2026-08-06 13:00:00'),(7,7,7,1,'Pedido cancelado','2026-08-07 14:00:00'),(8,8,8,1,'Pendiente de pago','2026-08-08 15:00:00'),(9,9,9,3,'Pago rechazado','2026-08-09 16:00:00'),(10,10,10,3,'Pedido rechazado','2026-08-10 17:00:00'),(11,17,1,13,'Pedido creado desde la web','2026-08-20 23:56:36'),(12,18,1,16,'Pedido creado desde la web','2026-08-21 06:48:02'),(13,19,1,16,'Pedido creado desde la web','2026-08-21 07:05:44'),(14,20,1,13,'Pedido creado desde la web','2026-08-21 12:56:32'),(15,21,1,13,'Pedido creado desde la web','2026-08-21 13:55:37'),(16,22,1,13,'Pedido creado desde la web','2026-08-21 15:28:46'),(17,23,1,13,'Pedido creado desde la web','2026-08-23 01:04:04'),(18,24,1,13,'Pedido creado desde la web','2026-08-23 01:17:35'),(19,25,1,16,'Pedido creado desde la web','2026-08-23 01:26:50'),(20,26,1,15,'Pedido creado desde la web','2026-08-23 22:58:41'),(21,27,1,14,'Pedido creado desde la web','2026-08-23 23:14:43'),(22,28,1,16,'Pedido creado desde la web','2026-08-23 23:19:47'),(23,29,1,13,'Pedido creado desde la web','2026-08-24 07:58:04'),(24,30,1,13,'Pedido creado desde la web','2026-08-24 08:26:30'),(25,31,1,13,'Pedido creado desde la web','2026-08-26 08:18:27'),(26,32,1,13,'Pedido creado desde la web','2026-08-27 11:02:26'),(27,33,1,13,'Pedido creado desde la web','2026-08-28 08:25:24'),(28,34,1,13,'Pedido creado desde la web','2026-08-28 11:21:09'),(43,34,2,14,'Pago marcado como Verificado por administrador.','2026-08-28 12:05:59'),(44,33,10,14,'Pago marcado como Verificado por administrador.','2026-08-28 12:06:21'),(45,32,6,14,'Pago marcado como Verificado por administrador.','2026-08-28 12:06:22'),(46,31,6,14,'Pago marcado como Rechazado por administrador.','2026-08-28 12:06:33'),(47,35,1,13,'Pedido creado desde la web','2026-08-28 12:21:33'),(48,35,1,14,'Pago marcado como Verificado por administrador.','2026-08-28 12:22:02'),(49,36,1,13,'Pedido creado desde la web','2026-08-28 12:43:23'),(50,36,1,14,'Pago marcado como Verificado por administrador.','2026-08-28 12:43:40');
UNLOCK TABLES;
DROP TABLE IF EXISTS `insumo`;
CREATE TABLE `insumo` (
  `id_insumo` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unidad_medida` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stock_minimo` decimal(10,3) NOT NULL DEFAULT '0.000',
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVO',
  PRIMARY KEY (`id_insumo`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
LOCK TABLES `insumo` WRITE;
INSERT INTO `insumo` VALUES (1,'Carne de cerdo','kg',10.000,'ACTIVO'),(2,'Tripa natural','m',20.000,'ACTIVO'),(3,'Sal','kg',2.000,'ACTIVO'),(4,'Ajo','kg',2.000,'ACTIVO'),(5,'Cebolla','kg',3.000,'ACTIVO'),(6,'Pimenton','kg',3.000,'ACTIVO'),(7,'Comino','kg',1.000,'ACTIVO'),(8,'Papa','kg',10.000,'ACTIVO'),(9,'Aceite','L',5.000,'ACTIVO'),(10,'Salsa de tomate','L',3.000,'ACTIVO');
UNLOCK TABLES;
DROP TABLE IF EXISTS `inventario_insumo`;
CREATE TABLE `inventario_insumo` (
  `id_inventario_insumo` int NOT NULL AUTO_INCREMENT,
  `id_insumo` int NOT NULL,
  `cantidad` decimal(10,3) NOT NULL DEFAULT '0.000',
  `fecha_actualizacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_inventario_insumo`),
  UNIQUE KEY `id_insumo` (`id_insumo`),
  CONSTRAINT `inventario_insumo_ibfk_1` FOREIGN KEY (`id_insumo`) REFERENCES `insumo` (`id_insumo`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
LOCK TABLES `inventario_insumo` WRITE;
INSERT INTO `inventario_insumo` VALUES (1,1,100.000,'2026-08-01 08:00:00'),(2,2,200.000,'2026-08-01 08:00:00'),(3,3,20.000,'2026-08-01 08:00:00'),(4,4,15.000,'2026-08-01 08:00:00'),(5,5,25.000,'2026-08-01 08:00:00'),(6,6,20.000,'2026-08-01 08:00:00'),(7,7,10.000,'2026-08-01 08:00:00'),(8,8,80.000,'2026-08-01 08:00:00'),(9,9,40.000,'2026-08-01 08:00:00'),(10,10,30.000,'2026-08-01 08:00:00');
UNLOCK TABLES;
DROP TABLE IF EXISTS `inventario_producto`;
CREATE TABLE `inventario_producto` (
  `id_inventario_producto` int NOT NULL AUTO_INCREMENT,
  `id_producto` int NOT NULL,
  `stock` int NOT NULL DEFAULT '0',
  `stock_minimo` int NOT NULL DEFAULT '0',
  `fecha_actualizacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_inventario_producto`),
  UNIQUE KEY `id_producto` (`id_producto`),
  CONSTRAINT `inventario_producto_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
LOCK TABLES `inventario_producto` WRITE;
INSERT INTO `inventario_producto` VALUES (1,1,50,10,'2026-08-14 10:51:13'),(2,2,45,10,'2026-08-14 10:51:13'),(3,3,30,8,'2026-08-14 10:51:13'),(4,4,20,5,'2026-08-14 10:51:13'),(5,5,15,5,'2026-08-14 10:51:13'),(6,6,80,15,'2026-08-14 10:51:13'),(7,7,60,10,'2026-08-14 10:51:13'),(8,8,40,10,'2026-08-14 10:51:13'),(9,9,35,8,'2026-08-14 10:51:13'),(10,10,25,5,'2026-08-14 10:51:13');
UNLOCK TABLES;
DROP TABLE IF EXISTS `movimiento_inventario`;
CREATE TABLE `movimiento_inventario` (
  `id_movimiento` int NOT NULL AUTO_INCREMENT,
  `id_insumo` int DEFAULT NULL,
  `id_producto` int DEFAULT NULL,
  `id_usuario` int DEFAULT NULL,
  `tipo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` decimal(10,3) NOT NULL,
  `motivo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_movimiento`),
  KEY `id_insumo` (`id_insumo`),
  KEY `id_producto` (`id_producto`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `movimiento_inventario_ibfk_1` FOREIGN KEY (`id_insumo`) REFERENCES `insumo` (`id_insumo`),
  CONSTRAINT `movimiento_inventario_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`),
  CONSTRAINT `movimiento_inventario_ibfk_3` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
LOCK TABLES `movimiento_inventario` WRITE;
INSERT INTO `movimiento_inventario` VALUES (1,1,NULL,10,'ENTRADA',50.000,'Compra de carne','2026-08-14 10:51:13'),(2,2,NULL,10,'ENTRADA',100.000,'Compra de tripa','2026-08-14 10:51:13'),(3,3,NULL,10,'ENTRADA',10.000,'Compra de sal','2026-08-14 10:51:13'),(4,8,NULL,10,'ENTRADA',30.000,'Compra de papa','2026-08-14 10:51:13'),(5,NULL,1,10,'SALIDA',5.000,'Venta de chorizos','2026-08-14 10:51:13'),(6,NULL,2,10,'SALIDA',3.000,'Venta de chorizos','2026-08-14 10:51:13'),(7,1,NULL,10,'SALIDA',2.000,'Produccion','2026-08-14 10:51:13'),(8,4,NULL,10,'ENTRADA',5.000,'Compra de ajo','2026-08-14 10:51:13'),(9,9,NULL,10,'ENTRADA',10.000,'Compra de aceite','2026-08-14 10:51:13'),(10,10,NULL,10,'AJUSTE',2.000,'Ajuste de inventario','2026-08-14 10:51:13');
UNLOCK TABLES;
DROP TABLE IF EXISTS `notificacion`;
CREATE TABLE `notificacion` (
  `id_notificacion` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `id_pedido` int DEFAULT NULL,
  `titulo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mensaje` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `leida` tinyint(1) NOT NULL DEFAULT '0',
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_notificacion`),
  KEY `id_pedido` (`id_pedido`),
  KEY `idx_notificacion_usuario` (`id_usuario`),
  CONSTRAINT `notificacion_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE,
  CONSTRAINT `notificacion_ibfk_2` FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id_pedido`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
LOCK TABLES `notificacion` WRITE;
INSERT INTO `notificacion` VALUES (1,1,1,'Pedido confirmado','Tu pedido fue confirmado',1,'2026-08-01 08:40:00'),(2,2,2,'Pedido confirmado','Tu pedido fue confirmado',1,'2026-08-02 09:10:00'),(3,6,3,'Pedido listo','Tu pedido esta listo',0,'2026-08-03 12:00:00'),(4,7,4,'En preparaciâ”œâ”‚n','Tu pedido esta siendo preparado',0,'2026-08-04 12:30:00'),(5,1,5,'Pedido confirmado','Tu pedido fue confirmado',1,'2026-08-05 12:40:00'),(6,2,6,'Pago pendiente','Tu pedido esta pendiente de pago',0,'2026-08-06 13:10:00'),(7,6,7,'Pedido cancelado','Tu pedido fue cancelado',1,'2026-08-07 14:10:00'),(8,7,8,'Pago pendiente','Debes completar el pago',0,'2026-08-08 15:10:00'),(9,1,9,'Pago rechazado','No fue posible procesar el pago',0,'2026-08-09 16:10:00'),(10,2,10,'Pedido rechazado','El pedido fue rechazado',1,'2026-08-10 17:10:00');
UNLOCK TABLES;
DROP TABLE IF EXISTS `novedad_entrega`;
CREATE TABLE `novedad_entrega` (
  `id_novedad` int NOT NULL AUTO_INCREMENT,
  `id_pedido` int NOT NULL,
  `id_domiciliario` int NOT NULL,
  `tipo_novedad` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDIENTE',
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atendida_por` int DEFAULT NULL,
  `fecha_atencion` datetime DEFAULT NULL,
  PRIMARY KEY (`id_novedad`),
  KEY `id_pedido` (`id_pedido`),
  KEY `id_domiciliario` (`id_domiciliario`),
  KEY `atendida_por` (`atendida_por`),
  CONSTRAINT `novedad_entrega_ibfk_1` FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id_pedido`) ON DELETE CASCADE,
  CONSTRAINT `novedad_entrega_ibfk_2` FOREIGN KEY (`id_domiciliario`) REFERENCES `domiciliario` (`id_usuario`),
  CONSTRAINT `novedad_entrega_ibfk_3` FOREIGN KEY (`atendida_por`) REFERENCES `administrador` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
LOCK TABLES `novedad_entrega` WRITE;
INSERT INTO `novedad_entrega` VALUES (1,1,5,'CLIENTE AUSENTE','Cliente no se encontraba','ATENDIDA','2026-08-01 09:50:00',3,'2026-08-01 09:55:00'),(2,2,5,'DIRECCION INCORRECTA','Se verifico la direcciâ”œâ”‚n','ATENDIDA','2026-08-02 10:50:00',3,'2026-08-02 11:00:00'),(3,3,5,'DEMORA','Demora por trafico','ATENDIDA','2026-08-03 11:50:00',3,'2026-08-03 12:00:00'),(4,4,5,'CLIENTE AUSENTE','No responde al domicilio','PENDIENTE','2026-08-04 13:00:00',NULL,NULL),(5,5,5,'DIRECCION INCORRECTA','Falta numero de casa','PENDIENTE','2026-08-05 14:00:00',NULL,NULL),(6,6,5,'PEDIDO RECHAZADO','Cliente rechazo pedido','ATENDIDA','2026-08-06 15:00:00',3,'2026-08-06 15:10:00'),(7,7,5,'CANCELACION','Cliente cancelo','ATENDIDA','2026-08-07 16:00:00',3,'2026-08-07 16:05:00'),(8,8,5,'PAGO','No se encontro comprobante','PENDIENTE','2026-08-08 17:00:00',NULL,NULL),(9,9,5,'PAGO RECHAZADO','Transacciâ”œâ”‚n rechazada','ATENDIDA','2026-08-09 18:00:00',3,'2026-08-09 18:05:00'),(10,10,5,'PEDIDO RECHAZADO','Pedido rechazado por administrador','ATENDIDA','2026-08-10 19:00:00',3,'2026-08-10 19:05:00');
UNLOCK TABLES;
DROP TABLE IF EXISTS `pago`;
CREATE TABLE `pago` (
  `id_pago` int NOT NULL AUTO_INCREMENT,
  `id_pedido` int NOT NULL,
  `metodo` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDIENTE',
  `comprobante` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referencia` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observacion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_pago` datetime DEFAULT NULL,
  `verificado_por` int DEFAULT NULL,
  `fecha_verificacion` datetime DEFAULT NULL,
  PRIMARY KEY (`id_pago`),
  UNIQUE KEY `id_pedido` (`id_pedido`),
  KEY `verificado_por` (`verificado_por`),
  CONSTRAINT `pago_ibfk_1` FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id_pedido`) ON DELETE CASCADE,
  CONSTRAINT `pago_ibfk_2` FOREIGN KEY (`verificado_por`) REFERENCES `administrador` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
LOCK TABLES `pago` WRITE;
INSERT INTO `pago` VALUES (1,1,'EFECTIVO','APROBADO',NULL,'REF001','Pago recibido','2026-08-01 10:00:00',3,'2026-08-01 10:10:00'),(2,2,'TARJETA','APROBADO',NULL,'REF002','Pago aprobado','2026-08-02 11:00:00',3,'2026-08-02 11:05:00'),(3,3,'TRANSFERENCIA','APROBADO',NULL,'REF003','Transferencia verificada','2026-08-03 12:00:00',3,'2026-08-03 12:10:00'),(4,4,'TARJETA','APROBADO',NULL,'REF004','Pago aprobado','2026-08-04 13:00:00',3,'2026-08-04 13:05:00'),(5,5,'EFECTIVO','APROBADO',NULL,'REF005','Pago recibido','2026-08-05 14:00:00',3,'2026-08-05 14:10:00'),(6,6,'TARJETA','PENDIENTE',NULL,'REF006','Pendiente',NULL,NULL,NULL),(7,7,'EFECTIVO','CANCELADO',NULL,'REF007','Pedido cancelado',NULL,NULL,NULL),(8,8,'TRANSFERENCIA','PENDIENTE',NULL,'REF008','Esperando comprobante',NULL,NULL,NULL),(9,9,'TARJETA','RECHAZADO',NULL,'REF009','Transacciâ”œâ”‚n rechazada','2026-08-09 16:00:00',3,'2026-08-09 16:01:00'),(10,10,'TARJETA','RECHAZADO',NULL,'REF010','Pago rechazado','2026-08-10 17:00:00',3,'2026-08-10 17:02:00'),(11,17,'Efectivo (Contra entrega)','Pendiente',NULL,NULL,NULL,'2026-08-20 23:56:36',NULL,NULL),(12,18,'Efectivo (Contra entrega)','Pendiente',NULL,NULL,NULL,'2026-08-21 06:48:02',NULL,NULL),(13,19,'Efectivo (Contra entrega)','Pendiente',NULL,NULL,NULL,'2026-08-21 07:05:44',NULL,NULL),(14,20,'Efectivo (Contra entrega)','Pendiente',NULL,NULL,NULL,'2026-08-21 12:56:32',NULL,NULL),(15,21,'Transferencia / Nequi','Pendiente',NULL,NULL,NULL,'2026-08-21 13:55:37',NULL,NULL),(16,22,'Efectivo (Contra entrega)','Pendiente',NULL,NULL,NULL,'2026-08-21 15:28:46',NULL,NULL),(17,23,'Efectivo (Contra entrega)','Pendiente',NULL,NULL,NULL,'2026-08-23 01:04:04',NULL,NULL),(18,24,'Efectivo (Contra entrega)','Pendiente',NULL,NULL,NULL,'2026-08-23 01:17:35',NULL,NULL),(19,25,'Efectivo (Contra entrega)','Pendiente',NULL,NULL,NULL,'2026-08-23 01:26:50',NULL,NULL),(20,26,'Transferencia / Nequi','Pendiente',NULL,NULL,NULL,'2026-08-23 22:58:41',NULL,NULL),(21,27,'Transferencia / Nequi','Pendiente',NULL,NULL,NULL,'2026-08-23 23:14:43',NULL,NULL),(22,28,'Tarjeta','Pendiente',NULL,NULL,NULL,'2026-08-23 23:19:47',NULL,NULL),(23,29,'Transferencia / Nequi','Pendiente',NULL,NULL,NULL,'2026-08-24 07:58:04',NULL,NULL),(24,30,'Efectivo (Contra entrega)','Pendiente',NULL,NULL,NULL,'2026-08-24 08:26:30',NULL,NULL),(25,31,'Tarjeta','Rechazado',NULL,NULL,NULL,'2026-08-26 08:18:27',NULL,NULL),(26,32,'Transferencia / Nequi','Verificado',NULL,NULL,NULL,'2026-08-27 11:02:26',NULL,NULL),(27,33,'Efectivo (Contra entrega)','Verificado',NULL,NULL,NULL,'2026-08-28 08:25:24',NULL,NULL),(28,34,'Transferencia / Nequi','Verificado','/Public/img/comprobantes/comp_1787934069_6741.jpeg',NULL,NULL,'2026-08-28 11:21:09',NULL,NULL),(29,35,'Tarjeta','Verificado',NULL,NULL,NULL,'2026-08-28 12:21:33',NULL,NULL),(30,36,'Efectivo (Contra entrega)','Verificado',NULL,NULL,NULL,'2026-08-28 12:43:23',NULL,NULL);
UNLOCK TABLES;
DROP TABLE IF EXISTS `pedido`;
CREATE TABLE `pedido` (
  `id_pedido` int NOT NULL AUTO_INCREMENT,
  `id_cliente` int NOT NULL,
  `id_direccion` int NOT NULL,
  `id_zona` int DEFAULT NULL,
  `id_estado` int NOT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `costo_domicilio` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `observaciones` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_pedido`),
  KEY `id_direccion` (`id_direccion`),
  KEY `id_zona` (`id_zona`),
  KEY `idx_pedido_cliente` (`id_cliente`),
  KEY `idx_pedido_estado` (`id_estado`),
  KEY `idx_pedido_fecha` (`fecha`),
  CONSTRAINT `pedido_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_usuario`),
  CONSTRAINT `pedido_ibfk_2` FOREIGN KEY (`id_direccion`) REFERENCES `direccion` (`id_direccion`),
  CONSTRAINT `pedido_ibfk_3` FOREIGN KEY (`id_zona`) REFERENCES `zona_envio` (`id_zona`),
  CONSTRAINT `pedido_ibfk_4` FOREIGN KEY (`id_estado`) REFERENCES `estado_pedido` (`id_estado`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
LOCK TABLES `pedido` WRITE;
INSERT INTO `pedido` VALUES (1,1,1,1,7,'2026-08-14 10:51:13',12000.00,5000.00,17000.00,'Entregado'),(2,2,3,2,6,'2026-08-14 10:51:13',22000.00,6000.00,28000.00,'En camino'),(3,6,5,4,12,'2026-08-14 10:51:13',15000.00,7000.00,22000.00,'Listo'),(4,7,7,6,6,'2026-08-14 10:51:13',28000.00,5000.00,33000.00,'En preparaciâ”œâ”‚n'),(5,1,2,7,6,'2026-08-14 10:51:13',50000.00,5000.00,55000.00,'Confirmado'),(6,2,4,3,12,'2026-08-14 10:51:13',18000.00,6000.00,24000.00,'Pendiente'),(7,6,6,5,7,'2026-08-14 10:51:13',12000.00,7000.00,19000.00,'Cancelado'),(8,7,8,8,6,'2026-08-14 10:51:13',28000.00,6000.00,34000.00,'Pendiente de pago'),(9,1,9,9,6,'2026-08-14 10:51:13',15000.00,7000.00,22000.00,'Pago rechazado'),(10,2,10,10,10,'2026-08-14 10:51:13',11000.00,6000.00,17000.00,'Pedido rechazado'),(17,13,20,1,6,'2026-08-20 23:56:36',16000.00,5000.00,21000.00,'Sin papas'),(18,16,21,4,6,'2026-08-21 06:48:02',62000.00,7000.00,69000.00,'Con pene'),(19,16,22,2,12,'2026-08-21 07:05:44',19000.00,6000.00,25000.00,'Por ahora ninguna'),(20,13,23,3,6,'2026-08-21 12:56:32',108000.00,6000.00,114000.00,'Sin chorizo '),(21,13,24,2,6,'2026-08-21 13:55:37',12000.00,6000.00,18000.00,'sin salsa'),(22,13,25,5,6,'2026-08-21 15:28:46',36000.00,7000.00,43000.00,'Sin gaseosa'),(23,13,26,2,6,'2026-08-23 01:04:04',53000.00,6000.00,59000.00,'Rapido porfa'),(24,13,27,1,6,'2026-08-23 01:17:35',11000.00,5000.00,16000.00,'ah'),(25,16,28,3,6,'2026-08-23 01:26:50',11000.00,6000.00,17000.00,''),(26,15,29,3,6,'2026-08-23 22:58:41',11000.00,6000.00,17000.00,''),(27,14,30,2,6,'2026-08-23 23:14:43',11000.00,6000.00,17000.00,''),(28,16,31,2,6,'2026-08-23 23:19:47',11000.00,6000.00,17000.00,''),(29,13,32,8,6,'2026-08-24 07:58:04',61000.00,6000.00,67000.00,''),(30,13,33,3,6,'2026-08-24 08:26:30',11000.00,6000.00,17000.00,''),(31,13,34,8,10,'2026-08-26 08:18:27',12000.00,6000.00,18000.00,'Salsa de la casa'),(32,13,35,4,2,'2026-08-27 11:02:26',24000.00,7000.00,31000.00,'SALSAS APARTE'),(33,13,36,3,2,'2026-08-28 08:25:24',30000.00,6000.00,36000.00,'Sin salsas'),(34,13,37,11,2,'2026-08-28 11:21:09',11000.00,15000.00,26000.00,''),(35,13,38,5,6,'2026-08-28 12:21:33',11000.00,7000.00,18000.00,'Salsa'),(36,13,39,10,6,'2026-08-28 12:43:23',96000.00,6000.00,102000.00,'');
UNLOCK TABLES;
DROP TABLE IF EXISTS `producto`;
CREATE TABLE `producto` (
  `id_producto` int NOT NULL AUTO_INCREMENT,
  `id_categoria` int DEFAULT NULL,
  `nombre_producto` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `stock` int NOT NULL DEFAULT '50',
  `stock_minimo` int NOT NULL DEFAULT '5',
  `disponible` tinyint(1) NOT NULL DEFAULT '1',
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime DEFAULT NULL,
  PRIMARY KEY (`id_producto`),
  KEY `idx_producto_categoria` (`id_categoria`),
  CONSTRAINT `producto_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `categoria` (`id_categoria`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
LOCK TABLES `producto` WRITE;
INSERT INTO `producto` VALUES (1,1,'Chorizo Santandereano','Chorizo tradicional',12000.00,42,5,1,'2026-08-14 10:51:13',NULL),(2,1,'Chorizo Antioqueâ”œâ–’o','Chorizo tradicional',11000.00,50,5,1,'2026-08-14 10:51:13','2026-08-23 01:01:16'),(3,1,'Chorizo Especial','Chorizo de la casa',15000.00,50,5,1,'2026-08-14 10:51:13','2026-08-23 01:01:17'),(4,2,'Combo Pareja','Dos chorizos y bebidas',28000.00,50,5,1,'2026-08-14 10:51:13',NULL),(5,2,'Combo Familiar','Cuatro chorizos y acompaâ”œâ–’antes',50000.00,50,5,1,'2026-08-14 10:51:13',NULL),(6,3,'Gaseosa','Bebida personal',4000.00,50,5,1,'2026-08-14 10:51:13',NULL),(7,3,'Jugo Natural','Jugo de fruta',6000.00,50,5,1,'2026-08-14 10:51:13',NULL),(8,4,'Salsa de la Casa','Salsa especial',3000.00,50,5,1,'2026-08-14 10:51:13',NULL),(9,5,'Papa Francesa','Porciâ”œâ”‚n de papas',7000.00,50,5,1,'2026-08-14 10:51:13',NULL),(10,6,'Chorizo Premium','Presentaciâ”œâ”‚n premium',18000.00,50,5,1,'2026-08-14 10:51:13',NULL);
UNLOCK TABLES;
DROP TABLE IF EXISTS `receta`;
CREATE TABLE `receta` (
  `id_receta` int NOT NULL AUTO_INCREMENT,
  `id_producto` int NOT NULL,
  `nombre` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVA',
  PRIMARY KEY (`id_receta`),
  UNIQUE KEY `id_producto` (`id_producto`),
  CONSTRAINT `receta_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
LOCK TABLES `receta` WRITE;
INSERT INTO `receta` VALUES (1,1,'Receta Santandereana','ACTIVA'),(2,2,'Receta Antioqueâ”œâ–’a','ACTIVA'),(3,3,'Receta Especial','ACTIVA'),(4,4,'Receta Combo Pareja','ACTIVA'),(5,5,'Receta Combo Familiar','ACTIVA'),(6,6,'Preparacion Gaseosa','ACTIVA'),(7,7,'Preparacion Jugo','ACTIVA'),(8,8,'Receta Salsa Casa','ACTIVA'),(9,9,'Preparacion Papa','ACTIVA'),(10,10,'Receta Premium','ACTIVA');
UNLOCK TABLES;
DROP TABLE IF EXISTS `receta_detalle`;
CREATE TABLE `receta_detalle` (
  `id_receta` int NOT NULL,
  `id_insumo` int NOT NULL,
  `cantidad` decimal(10,3) NOT NULL,
  PRIMARY KEY (`id_receta`,`id_insumo`),
  KEY `id_insumo` (`id_insumo`),
  CONSTRAINT `receta_detalle_ibfk_1` FOREIGN KEY (`id_receta`) REFERENCES `receta` (`id_receta`),
  CONSTRAINT `receta_detalle_ibfk_2` FOREIGN KEY (`id_insumo`) REFERENCES `insumo` (`id_insumo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
LOCK TABLES `receta_detalle` WRITE;
INSERT INTO `receta_detalle` VALUES (1,1,0.250),(1,2,1.000),(1,3,0.010),(2,1,0.250),(2,4,0.020),(2,7,0.005),(3,1,0.300),(3,5,0.030),(3,6,0.020),(4,1,0.500),(4,2,2.000),(4,3,0.020),(5,1,1.000),(5,2,4.000),(5,8,0.500),(6,10,0.050),(7,7,0.010),(8,10,0.100),(9,8,0.250),(9,9,0.050),(10,1,0.350),(10,2,1.000),(10,6,0.030);
UNLOCK TABLES;
DROP TABLE IF EXISTS `recuperacion_password`;
CREATE TABLE `recuperacion_password` (
  `id` int NOT NULL AUTO_INCREMENT,
  `correo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_expiracion` datetime NOT NULL,
  `creado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
LOCK TABLES `recuperacion_password` WRITE;
UNLOCK TABLES;
DROP TABLE IF EXISTS `rol`;
CREATE TABLE `rol` (
  `id_rol` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_rol`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
LOCK TABLES `rol` WRITE;
INSERT INTO `rol` VALUES (1,'CLIENTE','Usuario que realiza pedidos'),(2,'ADMINISTRADOR','Gestiona el sistema'),(3,'COCINERA','Prepara los pedidos'),(4,'DOMICILIARIO','Realiza las entregas');
UNLOCK TABLES;
DROP TABLE IF EXISTS `usuario`;
CREATE TABLE `usuario` (
  `id_usuario` int NOT NULL AUTO_INCREMENT,
  `id_rol` int NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `correo` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVO',
  `fecha_registro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `correo` (`correo`),
  KEY `idx_usuario_rol` (`id_rol`),
  CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `rol` (`id_rol`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
LOCK TABLES `usuario` WRITE;
INSERT INTO `usuario` VALUES (1,1,'Andres Felipe','andres@chorizoexpress.com','hash001','3001111111','ACTIVO','2026-08-14 10:51:12'),(2,1,'Maria Fernanda','maria@chorizoexpress.com','hash002','3002222222','ACTIVO','2026-08-14 10:51:12'),(3,2,'Carlos Gomez','carlos.admin@chorizoexpress.com','hash003','3003333333','ACTIVO','2026-08-14 10:51:12'),(4,3,'Laura Torres','laura.cocina@chorizoexpress.com','$2y$10$3z4fk./9inMoYpCb/Wd86ORVDfVoDU20o546F2341ebR/NmG6m.76','3004444444','ACTIVO','2026-08-14 10:51:12'),(5,4,'Juan Perez','juan.domicilio@chorizoexpress.com','hash005','3005555555','ACTIVO','2026-08-14 10:51:12'),(6,1,'Sofia Ramirez','sofia@chorizoexpress.com','hash006','3006666666','ACTIVO','2026-08-14 10:51:12'),(7,1,'Daniel Castro','daniel@chorizoexpress.com','hash007','3007777777','ACTIVO','2026-08-14 10:51:12'),(8,5,'Paula Martinez','paula.supervisor@chorizoexpress.com','hash008','3008888888','ACTIVO','2026-08-14 10:51:12'),(9,6,'Diego Rojas','diego.cajero@chorizoexpress.com','hash009','3009999999','ACTIVO','2026-08-14 10:51:12'),(10,8,'Valentina Ruiz','valentina.inventario@chorizoexpress.com','hash010','3010000000','ACTIVO','2026-08-14 10:51:12'),(11,1,'Andres felipe','felipe@gmail.com','$2y$10$0gCDJmhsbnkXYv2MdAhE6ORa8890gn3f47Zg0AtZygftSfbyOwE2a','3228460174','ACTIVO','2026-08-20 20:04:42'),(12,2,'Natalia fernanda','nata@gamil.com','$2y$10$JZQCKdo8cexgBmn4Wdjx5uiu2ZWI5y6eMM4DQtAGpX4ChYsCaRwEu','3103158909','ACTIVO','2026-08-20 20:06:00'),(13,1,'juan','juan@gmail.com','$2y$10$umvIWuMpJBGda5Ks1tJICu53DbEf9aERWh7HwGIZxhQYPX/KefXQa','3115679856','ACTIVO','2026-08-20 20:49:45'),(14,2,'admin','admin@gmail.com','$2y$10$o353nPjeOhZE2iQYfGgm0e1RnRJWTBFBreJfOTA4IW.5IWuRNTF62','3228460174','ACTIVO','2026-08-20 22:44:39'),(15,3,'cocina','cocina@gmail.com','$2y$10$3z4fk./9inMoYpCb/Wd86ORVDfVoDU20o546F2341ebR/NmG6m.76','3456782345','ACTIVO','2026-08-20 23:40:20'),(16,4,'domi','domi@gmail.com','$2y$10$T.nSs9jOkAml8Ri/lfeU6erKEg1pONc/LQgYN3j.fZ2ZjsVB47xBC','3216730989','ACTIVO','2026-08-20 23:41:55');
UNLOCK TABLES;
DROP TABLE IF EXISTS `zona_envio`;
CREATE TABLE `zona_envio` (
  `id_zona` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `costo_domicilio` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tiempo_estimado_min` int DEFAULT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVA',
  PRIMARY KEY (`id_zona`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
LOCK TABLES `zona_envio` WRITE;
INSERT INTO `zona_envio` VALUES (1,'Centro',5000.00,20,'ACTIVA'),(2,'Norte',6000.00,30,'ACTIVA'),(3,'Sur',6000.00,30,'ACTIVA'),(4,'Oriente',7000.00,35,'ACTIVA'),(5,'Occidente',7000.00,35,'ACTIVA'),(6,'Barrio Modelo',5000.00,20,'ACTIVA'),(7,'El Recreo',5000.00,20,'ACTIVA'),(8,'La Esmeralda',6000.00,25,'ACTIVA'),(9,'Los Andes',7000.00,40,'ACTIVA'),(10,'Zona Universitaria',6000.00,25,'ACTIVA'),(11,'Rivera',15000.00,60,'ACTIVA'),(12,'Campoalegre',30000.00,60,'ACTIVA');
UNLOCK TABLES;
SET FOREIGN_KEY_CHECKS=1;
