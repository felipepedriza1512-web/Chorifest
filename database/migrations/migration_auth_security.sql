-- Migración de Seguridad de Autenticación
-- 1. Ampliar columna de token de recuperación para almacenar hash seguro (SHA-256)
ALTER TABLE `recuperacion_password` MODIFY `codigo` VARCHAR(128) NOT NULL;

-- 2. Tabla para control y bloqueo temporal de intentos fallidos de login por IP y correo
CREATE TABLE IF NOT EXISTS `intentos_login` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ip` VARCHAR(45) NOT NULL,
  `correo` VARCHAR(120) NOT NULL,
  `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_ip_fecha` (`ip`, `fecha`),
  INDEX `idx_correo_fecha` (`correo`, `fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
