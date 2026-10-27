<?php
/**
 * Instalador CLI de Chorifest
 * 
 * Genera una contraseña aleatoria criptográficamente segura para el administrador inicial,
 * inserta el usuario con debe_cambiar_password = 1, y muestra las credenciales de un solo uso por consola.
 */

if (php_sapi_name() !== 'cli') {
    die("Este instalador solo puede ejecutarse desde la línea de comandos (CLI).\n");
}

require_once __DIR__ . '/config/database.php';

echo "====================================================\n";
echo "   CHORIFEST - INSTALADOR DE PRODUCCIÓN / SETUP     \n";
echo "====================================================\n\n";

try {
    $db = getDatabaseConnection();
    
    // Generar contraseña aleatoria criptográficamente segura (14 caracteres alfanuméricos + símbolo)
    $bytes = random_bytes(8);
    $rawRandomPass = 'Chori_' . bin2hex(random_bytes(4)) . '!' . strtoupper(bin2hex(random_bytes(2)));
    $hashedPassword = password_hash($rawRandomPass, PASSWORD_DEFAULT);

    // Verificar si el administrador inicial ya existe
    $stmtCheck = $db->prepare("SELECT id_usuario FROM usuario WHERE correo = 'admin@chorifest.com' OR id_rol = 2 LIMIT 1");
    $stmtCheck->execute();
    $existingAdmin = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($existingAdmin) {
        $stmtUpd = $db->prepare("UPDATE usuario SET password_hash = ?, debe_cambiar_password = 1, estado = 'ACTIVO' WHERE id_usuario = ?");
        $stmtUpd->execute([$hashedPassword, $existingAdmin['id_usuario']]);
        $adminId = $existingAdmin['id_usuario'];
        echo "✔ Administrador existente actualizado con nueva clave temporal aleatoria.\n";
    } else {
        $stmtIns = $db->prepare("INSERT INTO usuario (id_rol, nombre, correo, password_hash, telefono, estado, debe_cambiar_password) 
                                VALUES (2, 'Administrador', 'admin@chorifest.com', ?, '3000000000', 'ACTIVO', 1)");
        $stmtIns->execute([$hashedPassword]);
        $adminId = $db->lastInsertId();
        echo "✔ Administrador inicial creado exitosamente.\n";
    }

    echo "\n----------------------------------------------------\n";
    echo "CREDENCIALES TEMPORALES GENERADAS (¡GUARDAR AHORA!):\n";
    echo "----------------------------------------------------\n";
    echo "  Correo:              admin@chorifest.com\n";
    echo "  Contraseña Temporal: $rawRandomPass\n";
    echo "  Cambio Obligatorio:  SÍ (debe_cambiar_password = 1)\n";
    echo "----------------------------------------------------\n";
    echo "AVISO DE SEGURIDAD:\n";
    echo "En el primer inicio de sesión en el sistema, la aplicación\n";
    echo "exigirá obligatoriamente el cambio de esta contraseña por una\n";
    echo "personalizada según la política de seguridad.\n";
    echo "====================================================\n";

} catch (Exception $e) {
    echo "❌ Error durante la instalación: " . $e->getMessage() . "\n";
    exit(1);
}
