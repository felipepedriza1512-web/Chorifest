-- Migracion Fase 5 Reportes - Indices
-- MySQL 8.0.30

CREATE INDEX idx_pedido_estado_fecha ON pedido (id_estado, fecha);
CREATE INDEX idx_hist_pedido_estado_fecha ON historial_pedido (id_pedido, id_estado, fecha);
CREATE INDEX idx_hist_estado_pedido_fecha ON historial_pedido (id_estado, id_pedido, fecha);
CREATE INDEX idx_detalle_prod_ped ON detalle_pedido (id_producto, id_pedido);
CREATE INDEX idx_calificacion_puntaje_fecha ON calificacion (puntuacion, fecha);
CREATE INDEX idx_mov_insumo_tipo_fecha ON movimiento_inventario (id_insumo, tipo, fecha);

