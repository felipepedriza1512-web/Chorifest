-- Migración Fase 2: Marca de devolución pendiente para pagos de pedidos cancelados o rechazados
ALTER TABLE pago 
ADD COLUMN pendiente_devolucion TINYINT(1) NOT NULL DEFAULT 0 AFTER estado;
