-- Script de Reversión (Rollback) para Fase 2
ALTER TABLE pago DROP COLUMN IF EXISTS pendiente_devolucion;
ALTER TABLE pago DROP COLUMN IF EXISTS comprobante_devolucion;
