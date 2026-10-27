<?php
require_once __DIR__ . '/../config/database.php';
$db = getDatabaseConnection('chorifest');

echo "ROLES:\n";
print_r($db->query('SELECT * FROM rol')->fetchAll(PDO::FETCH_ASSOC));

echo "\nUSUARIOS DE PRUEBA:\n";
print_r($db->query("SELECT id_usuario, correo, id_rol FROM usuario WHERE correo IN ('admin@gmail.com', 'cocina@gmail.com', 'domi@gmail.com', 'juan@gmail.com')")->fetchAll(PDO::FETCH_ASSOC));

// Check checkRole in AuthMiddleware.php
require_once __DIR__ . '/../controllers/auth/AuthMiddleware.php';
echo "\nRedirecciones esperadas:\n";
foreach ([1, 2, 3, 4] as $r) {
    echo "Rol $r -> " . getRoleRedirectUrl($r) . "\n";
}
