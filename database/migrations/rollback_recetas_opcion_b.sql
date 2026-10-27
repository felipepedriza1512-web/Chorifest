-- =============================================================================
-- ROLLBACK: REVERSIÓN DE RECETAS OPCIÓN B AL ESTADO PREVIO
-- Archivo: database/migrations/rollback_recetas_opcion_b.sql
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Restaurar Receta de Chorizo Antioqueño
DELETE FROM `receta_detalle` 
WHERE `id_receta` = (SELECT id_receta FROM `receta` WHERE id_producto = 2 LIMIT 1) 
  AND `id_insumo` = 2; -- Quitar tripa

-- 2. Restaurar Receta de Gaseosa (con su valor previo salsa de tomate)
DELETE FROM `receta_detalle` 
WHERE `id_receta` = (SELECT id_receta FROM `receta` WHERE id_producto = 6 LIMIT 1);

INSERT INTO `receta_detalle` (`id_receta`, `id_insumo`, `cantidad`)
VALUES ((SELECT id_receta FROM `receta` WHERE id_producto = 6 LIMIT 1), 10, 0.050);

-- 3. Restaurar Receta de Jugo Natural (con su valor previo comino)
DELETE FROM `receta_detalle` 
WHERE `id_receta` = (SELECT id_receta FROM `receta` WHERE id_producto = 7 LIMIT 1);

INSERT INTO `receta_detalle` (`id_receta`, `id_insumo`, `cantidad`)
VALUES ((SELECT id_receta FROM `receta` WHERE id_producto = 7 LIMIT 1), 7, 0.010);

-- 4. Restaurar Receta Papa Francesa
DELETE FROM `receta_detalle` 
WHERE `id_receta` = (SELECT id_receta FROM `receta` WHERE id_producto = 9 LIMIT 1);

INSERT INTO `receta_detalle` (`id_receta`, `id_insumo`, `cantidad`) VALUES
((SELECT id_receta FROM `receta` WHERE id_producto = 9 LIMIT 1), 8, 0.250),
((SELECT id_receta FROM `receta` WHERE id_producto = 9 LIMIT 1), 9, 0.050);

-- 5. Restaurar Combo Pareja al estado previo
DELETE FROM `receta_detalle` 
WHERE `id_receta` = (SELECT id_receta FROM `receta` WHERE id_producto = 4 LIMIT 1);

INSERT INTO `receta_detalle` (`id_receta`, `id_insumo`, `cantidad`) VALUES
((SELECT id_receta FROM `receta` WHERE id_producto = 4 LIMIT 1), 1, 0.500),
((SELECT id_receta FROM `receta` WHERE id_producto = 4 LIMIT 1), 2, 2.000),
((SELECT id_receta FROM `receta` WHERE id_producto = 4 LIMIT 1), 3, 0.020);

-- 6. Restaurar Combo Familiar al estado previo
DELETE FROM `receta_detalle` 
WHERE `id_receta` = (SELECT id_receta FROM `receta` WHERE id_producto = 5 LIMIT 1);

INSERT INTO `receta_detalle` (`id_receta`, `id_insumo`, `cantidad`) VALUES
((SELECT id_receta FROM `receta` WHERE id_producto = 5 LIMIT 1), 1, 1.000),
((SELECT id_receta FROM `receta` WHERE id_producto = 5 LIMIT 1), 2, 4.000),
((SELECT id_receta FROM `receta` WHERE id_producto = 5 LIMIT 1), 8, 0.500);

-- 7. Eliminar insumos de demostración y sus inventarios
DELETE FROM `inventario_insumo` WHERE `id_insumo` IN (
    SELECT id_insumo FROM `insumo` WHERE `nombre` IN ('Gaseosa 350 ml (DEMO)', 'Pulpa de fruta (DEMO)')
);

DELETE FROM `insumo` WHERE `nombre` IN ('Gaseosa 350 ml (DEMO)', 'Pulpa de fruta (DEMO)');

SET FOREIGN_KEY_CHECKS = 1;
