-- =============================================================================
-- REVERSIÓN (ROLLBACK) FASE 4: ZONAS DE ENVÍO Y PERSONAL
-- Archivo: database/migrations/rollback_fase4_zonas_personal.sql
-- =============================================================================

ALTER TABLE `zona_envio`
  DROP COLUMN `monto_minimo`,
  DROP COLUMN `poligono_geojson`;

ALTER TABLE `pedido`
  DROP COLUMN `nombre_zona`;

ALTER TABLE `usuario`
  DROP COLUMN `debe_cambiar_password`,
  DROP COLUMN `ultima_actividad`;
