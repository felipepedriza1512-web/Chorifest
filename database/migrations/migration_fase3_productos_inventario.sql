-- ============================================================================
-- MIGRACIÓN FASE 3: PRODUCTOS E INVENTARIO
-- ============================================================================

-- 1. Ampliar longitud de movimiento_inventario.tipo para soportar REINTEGRO_CANCELACION (21 chars)
ALTER TABLE movimiento_inventario 
MODIFY COLUMN tipo VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;

-- 2. Columna imagen en producto
ALTER TABLE producto 
ADD COLUMN imagen VARCHAR(255) NULL DEFAULT NULL AFTER descripcion;

-- 3. Snapshot de nombre histórico en detalle_pedido (VARCHAR(120), idéntico a producto.nombre_producto)
ALTER TABLE detalle_pedido 
ADD COLUMN nombre_producto VARCHAR(120) NULL DEFAULT NULL AFTER id_producto;

-- Poblar nombres históricos en pedidos existentes
UPDATE detalle_pedido dp
JOIN producto p ON dp.id_producto = p.id_producto
SET dp.nombre_producto = p.nombre_producto
WHERE dp.nombre_producto IS NULL;

-- 4. Banderas en pedido para inventario desacoplado y guardia de reintegro único
ALTER TABLE pedido 
ADD COLUMN stock_descontado TINYINT(1) NOT NULL DEFAULT 0 AFTER id_estado,
ADD COLUMN stock_reintegrado TINYINT(1) NOT NULL DEFAULT 0 AFTER stock_descontado;

-- 5. Vincular movimiento_inventario con pedido para auditoría exacta
ALTER TABLE movimiento_inventario 
ADD COLUMN id_pedido INT NULL DEFAULT NULL AFTER id_usuario,
ADD CONSTRAINT fk_movimiento_pedido FOREIGN KEY (id_pedido) REFERENCES pedido(id_pedido) ON DELETE SET NULL;
