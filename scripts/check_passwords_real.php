<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UserModel.php';
$db = getDatabaseConnection();
$userModel = new UserModel($db);

$countBlocked = $db->query("SELECT COUNT(*) FROM intentos_login")->fetchColumn();
echo "Intentos fallidos registrados en BD: $countBlocked\n";
if ($countBlocked > 0) {
    $db->exec("TRUNCATE TABLE intentos_login");
    echo "Tabla intentos_login limpiada para pruebas.\n";
}

$testUsers = [
    'admin@gmail.com',
    'cocina@gmail.com',
    'domi@gmail.com',
    'juan@gmail.com'
];

foreach ($testUsers as $email) {
    $u = $userModel->findByEmail($email);
    if ($u) {
        $p123456 = password_verify('123456', $u['password_hash']);
        echo "$email (Rol {$u['id_rol']}) -> '123456': " . ($p123456 ? 'YES' : 'NO') . "\n";
    }
}
