<?php
require_once __DIR__ . '/../config/database.php';
$db = getDatabaseConnection();

$users = $db->query("SELECT u.id_usuario, u.nombre, u.correo, u.password_hash, r.nombre as rol_nombre 
                     FROM usuario u 
                     JOIN rol r ON u.id_rol = r.id_rol 
                     WHERE u.correo IN ('admin@gmail.com', 'cocina@gmail.com', 'domi@gmail.com', 'juan@gmail.com')")->fetchAll(PDO::FETCH_ASSOC);

$candidates = ['123456', '12345678', 'admin', 'admin123', 'admin@gmail.com', 'cocina', 'cocina123', 'domi', 'domi123', 'juan', 'juan123', 'Chorifest123', 'chorifest', 'password', '1234', 'admin2026', 'secret'];

echo "=== USUARIOS DE PRUEBA Y COMPROBACIÓN DE CONTRASEÑA ===\n";
foreach ($users as $u) {
    echo "ID: {$u['id_usuario']} | Rol: {$u['rol_nombre']} | Nombre: {$u['nombre']} | Correo: {$u['correo']}\n";
    $passHash = $u['password_hash'];
    echo "Hash: " . substr($passHash, 0, 30) . "...\n";
    $found = false;
    foreach ($candidates as $c) {
        if (password_verify($c, $passHash)) {
            echo "  ✔ CONTRASEÑA ENCONTRADA: '{$c}' (¡Contraseña débil/simple confirmada!)\n";
            $found = true;
            break;
        }
    }
    if (!$found) {
        echo "  - Contraseña no encontrada en la lista básica.\n";
    }
}
