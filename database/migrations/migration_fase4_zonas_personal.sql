-- =============================================================================
-- MIGRACIÓN FASE 4: ZONAS DE ENVÍO Y PERSONAL
-- Archivo: database/migrations/migration_fase4_zonas_personal.sql
-- =============================================================================

-- 1. Ampliar zona_envio con monto_minimo y poligono_geojson
ALTER TABLE `zona_envio`
  ADD COLUMN `monto_minimo` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `costo_domicilio`,
  ADD COLUMN `poligono_geojson` LONGTEXT NULL AFTER `estado`;

-- 2. Almacenar nombre de zona histórico en cada pedido
ALTER TABLE `pedido`
  ADD COLUMN `nombre_zona` VARCHAR(100) NULL AFTER `id_zona`;

-- Poblar nombre_zona histórico a partir de los datos existentes
UPDATE `pedido` p
LEFT JOIN `zona_envio` z ON p.id_zona = z.id_zona
SET p.nombre_zona = COALESCE(z.nombre, 'Zona Histórica')
WHERE p.nombre_zona IS NULL;

-- 3. Ampliar tabla usuario para forzar cambio de contraseña temporal y registro de actividad
ALTER TABLE `usuario`
  ADD COLUMN `debe_cambiar_password` TINYINT(1) NOT NULL DEFAULT 0 AFTER `estado`,
  ADD COLUMN `ultima_actividad` DATETIME NULL AFTER `debe_cambiar_password`;
