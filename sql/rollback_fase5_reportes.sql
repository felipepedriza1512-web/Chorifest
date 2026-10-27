-- Rollback Fase 5 Reportes - Indices
-- MySQL 8.0.30

DROP INDEX idx_pedido_estado_fecha ON pedido;
DROP INDEX idx_hist_pedido_estado_fecha ON historial_pedido;
DROP INDEX idx_hist_estado_pedido_fecha ON historial_pedido;
DROP INDEX idx_detalle_prod_ped ON detalle_pedido;
DROP INDEX idx_calificacion_puntaje_fecha ON calificacion;
DROP INDEX idx_mov_insumo_tipo_fecha ON movimiento_inventario;
