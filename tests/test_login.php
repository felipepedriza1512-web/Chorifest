<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../controllers/auth/AuthController.php';

$db = getDatabaseConnection();
$userModel = new UserModel($db);

$accounts = [
    'cliente@chorifest.com',
    'juan@gmail.com',
    'admin@chorifest.com',
    'admin@gmail.com',
    'cocina@chorifest.com',
    'cocina@gmail.com',
    'domi@chorifest.com',
    'domi@gmail.com'
];

echo "Probando inicio de sesión para los 8 usuarios de prueba (password: '123456'):\n\n";

foreach ($accounts as $email) {
    $res = $userModel->login($email, '123456');
    if ($res['success']) {
        $u = $res['user'];
        echo "✅ OK | Email: {$u['email']} | ID: {$u['id']} | Rol: {$u['rol']} | Nombre: {$u['name']}\n";
    } else {
        echo "❌ ERROR | Email: $email | Mensaje: {$res['message']}\n";
    }
}
