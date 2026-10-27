-- Reversión de Seguridad de Autenticación
DROP TABLE IF EXISTS `intentos_login`;
ALTER TABLE `recuperacion_password` MODIFY `codigo` VARCHAR(10) NOT NULL;
