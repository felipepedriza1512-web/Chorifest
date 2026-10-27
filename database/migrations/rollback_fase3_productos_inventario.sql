-- ============================================================================
-- ROLLBACK FASE 3: PRODUCTOS E INVENTARIO
-- ============================================================================
-- ADVERTENCIA: Este rollback solo es seguro si no existen filas en movimiento_inventario
-- con tipo de más de 20 caracteres (como 'REINTEGRO_CANCELACION'). De existir tales
-- registros, reducirlos a VARCHAR(20) causará error de truncamiento en MySQL strict mode.
-- ============================================================================

ALTER TABLE movimiento_inventario DROP FOREIGN KEY fk_movimiento_pedido;
ALTER TABLE movimiento_inventario DROP COLUMN id_pedido;
ALTER TABLE pedido DROP COLUMN stock_reintegrado;
ALTER TABLE pedido DROP COLUMN stock_descontado;
ALTER TABLE detalle_pedido DROP COLUMN nombre_producto;
ALTER TABLE producto DROP COLUMN imagen;

ALTER TABLE movimiento_inventario 
MODIFY COLUMN tipo VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;
