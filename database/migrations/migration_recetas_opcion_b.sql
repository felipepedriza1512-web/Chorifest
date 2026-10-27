-- =============================================================================
-- MIGRACIÓN: RECETAS OPCIÓN B + CORRECCIONES EN CODIFICACIÓN E INSUMOS
-- Archivo: database/migrations/migration_recetas_opcion_b.sql
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Insertar insumos de demostración claramente identificados
INSERT INTO `insumo` (`nombre`, `unidad_medida`, `stock_minimo`, `estado`)
VALUES 
('Gaseosa 350 ml (DEMO)', 'unidad', 10.000, 'ACTIVO'),
('Pulpa de fruta (DEMO)', 'kg', 3.000, 'ACTIVO')
ON DUPLICATE KEY UPDATE `estado` = 'ACTIVO';

-- Obtener IDs dinámicamente o asignar a inventario_insumo
INSERT INTO `inventario_insumo` (`id_insumo`, `cantidad`, `fecha_actualizacion`)
SELECT id_insumo, 50.000, NOW() FROM `insumo` WHERE `nombre` = 'Gaseosa 350 ml (DEMO)'
ON DUPLICATE KEY UPDATE `cantidad` = 50.000;

INSERT INTO `inventario_insumo` (`id_insumo`, `cantidad`, `fecha_actualizacion`)
SELECT id_insumo, 20.000, NOW() FROM `insumo` WHERE `nombre` = 'Pulpa de fruta (DEMO)'
ON DUPLICATE KEY UPDATE `cantidad` = 20.000;

-- 2. Corregir nombre dañado de Receta Antioqueña y agregar Tripa natural
UPDATE `receta` 
SET `nombre` = 'Receta Antioqueña' 
WHERE `id_producto` = 2;

-- Agregar Tripa natural (ID 2) a Chorizo Antioqueño (Receta de Producto 2)
DELETE FROM `receta_detalle` 
WHERE `id_receta` = (SELECT id_receta FROM `receta` WHERE id_producto = 2 LIMIT 1) 
  AND `id_insumo` = 2;

INSERT INTO `receta_detalle` (`id_receta`, `id_insumo`, `cantidad`)
SELECT id_receta, 2, 1.000 
FROM `receta` 
WHERE `id_producto` = 2 LIMIT 1;

-- 3. Corregir Receta de Gaseosa (ID Producto: 6)
-- Eliminar salsa de tomate y asignar Gaseosa 350 ml (DEMO)
DELETE FROM `receta_detalle` 
WHERE `id_receta` = (SELECT id_receta FROM `receta` WHERE id_producto = 6 LIMIT 1);

UPDATE `receta` SET `nombre` = 'Receta Gaseosa' WHERE `id_producto` = 6;

INSERT INTO `receta_detalle` (`id_receta`, `id_insumo`, `cantidad`)
SELECT (SELECT id_receta FROM `receta` WHERE id_producto = 6 LIMIT 1), 
       (SELECT id_insumo FROM `insumo` WHERE `nombre` = 'Gaseosa 350 ml (DEMO)' LIMIT 1), 
       1.000;

-- 4. Corregir Receta de Jugo Natural (ID Producto: 7)
-- Eliminar comino y asignar Pulpa de fruta (DEMO)
DELETE FROM `receta_detalle` 
WHERE `id_receta` = (SELECT id_receta FROM `receta` WHERE id_producto = 7 LIMIT 1);

UPDATE `receta` SET `nombre` = 'Receta Jugo Natural' WHERE `id_producto` = 7;

INSERT INTO `receta_detalle` (`id_receta`, `id_insumo`, `cantidad`)
SELECT (SELECT id_receta FROM `receta` WHERE id_producto = 7 LIMIT 1), 
       (SELECT id_insumo FROM `insumo` WHERE `nombre` = 'Pulpa de fruta (DEMO)' LIMIT 1), 
       0.150;

-- 5. Receta completa para Papa Francesa (ID Producto: 9)
-- 250g papa + 40ml aceite + 5g sal
DELETE FROM `receta_detalle` 
WHERE `id_receta` = (SELECT id_receta FROM `receta` WHERE id_producto = 9 LIMIT 1);

UPDATE `receta` SET `nombre` = 'Receta Papa Francesa' WHERE `id_producto` = 9;

INSERT INTO `receta_detalle` (`id_receta`, `id_insumo`, `cantidad`) VALUES
((SELECT id_receta FROM `receta` WHERE id_producto = 9 LIMIT 1), 8, 0.250), -- Papa: 0.250 kg
((SELECT id_receta FROM `receta` WHERE id_producto = 9 LIMIT 1), 9, 0.040), -- Aceite: 0.040 L
((SELECT id_receta FROM `receta` WHERE id_producto = 9 LIMIT 1), 3, 0.005); -- Sal: 0.005 kg

-- 6. Receta completa para Combo Pareja (ID Producto: 4)
-- Catálogo: "2 chorizos artesanales + papas a la francesa + 2 bebidas a elección"
DELETE FROM `receta_detalle` 
WHERE `id_receta` = (SELECT id_receta FROM `receta` WHERE id_producto = 4 LIMIT 1);

UPDATE `receta` SET `nombre` = 'Receta Combo Pareja' WHERE `id_producto` = 4;

INSERT INTO `receta_detalle` (`id_receta`, `id_insumo`, `cantidad`) VALUES
((SELECT id_receta FROM `receta` WHERE id_producto = 4 LIMIT 1), 1, 0.500), -- Carne de cerdo: 0.500 kg (2 chorizos)
((SELECT id_receta FROM `receta` WHERE id_producto = 4 LIMIT 1), 2, 2.000), -- Tripa natural: 2.000 m (2 chorizos)
((SELECT id_receta FROM `receta` WHERE id_producto = 4 LIMIT 1), 3, 0.020), -- Sal: 0.020 kg (Conserva cantidad existente solicitada)
((SELECT id_receta FROM `receta` WHERE id_producto = 4 LIMIT 1), 4, 0.010), -- Ajo: 0.010 kg
((SELECT id_receta FROM `receta` WHERE id_producto = 4 LIMIT 1), 8, 0.250), -- Papa: 0.250 kg (1 porcion papas)
((SELECT id_receta FROM `receta` WHERE id_producto = 4 LIMIT 1), 9, 0.040), -- Aceite: 0.040 L (fritura papas)
((SELECT id_receta FROM `receta` WHERE id_producto = 4 LIMIT 1), 
 (SELECT id_insumo FROM `insumo` WHERE `nombre` = 'Gaseosa 350 ml (DEMO)' LIMIT 1), 2.000); -- 2 bebidas

-- 7. Receta completa para Combo Familiar (ID Producto: 5)
-- Catálogo: "4 chorizos artesanales + 2 porciones de papas + salsas de la casa + 4 bebidas"
DELETE FROM `receta_detalle` 
WHERE `id_receta` = (SELECT id_receta FROM `receta` WHERE id_producto = 5 LIMIT 1);

UPDATE `receta` SET `nombre` = 'Receta Combo Familiar' WHERE `id_producto` = 5;

INSERT INTO `receta_detalle` (`id_receta`, `id_insumo`, `cantidad`) VALUES
((SELECT id_receta FROM `receta` WHERE id_producto = 5 LIMIT 1), 1, 1.000), -- Carne de cerdo: 1.000 kg (4 chorizos)
((SELECT id_receta FROM `receta` WHERE id_producto = 5 LIMIT 1), 2, 4.000), -- Tripa natural: 4.000 m (4 chorizos)
((SELECT id_receta FROM `receta` WHERE id_producto = 5 LIMIT 1), 3, 0.030), -- Sal: 0.030 kg
((SELECT id_receta FROM `receta` WHERE id_producto = 5 LIMIT 1), 4, 0.020), -- Ajo: 0.020 kg
((SELECT id_receta FROM `receta` WHERE id_producto = 5 LIMIT 1), 8, 0.500), -- Papa: 0.500 kg (2 porciones papas)
((SELECT id_receta FROM `receta` WHERE id_producto = 5 LIMIT 1), 9, 0.080), -- Aceite: 0.080 L (fritura 2 porciones)
((SELECT id_receta FROM `receta` WHERE id_producto = 5 LIMIT 1), 10, 0.100), -- Salsa de tomate: 0.100 L (salsas de la casa)
((SELECT id_receta FROM `receta` WHERE id_producto = 5 LIMIT 1), 
 (SELECT id_insumo FROM `insumo` WHERE `nombre` = 'Gaseosa 350 ml (DEMO)' LIMIT 1), 4.000); -- 4 bebidas

SET FOREIGN_KEY_CHECKS = 1;
