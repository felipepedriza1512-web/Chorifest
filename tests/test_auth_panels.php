<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../controllers/auth/AuthController.php';
require_once __DIR__ . '/../controllers/auth/AuthMiddleware.php';

$db = getDatabaseConnection();
$userModel = new UserModel($db);
$authController = new AuthController($userModel);

$testUsers = [
    'Cliente' => ['email' => 'juan@gmail.com', 'pass' => '123456', 'expected_role' => 1],
    'Admin' => ['email' => 'admin@gmail.com', 'pass' => '123456', 'expected_role' => 2],
    'Cocinera' => ['email' => 'cocina@gmail.com', 'pass' => '123456', 'expected_role' => 3],
    'Domiciliario' => ['email' => 'domi@gmail.com', 'pass' => '123456', 'expected_role' => 4],
];

echo "=== PRUEBA DE AUTENTICACION Y PANELES POR ROL ===\n\n";

foreach ($testUsers as $roleName => $data) {
    echo "Probando $roleName ({$data['email']})...\n";
    $result = $authController->handleLogin(['email' => $data['email'], 'password' => $data['pass']]);
    if ($result['success']) {
        $userRole = (int)($result['user']['rol'] ?? 0);
        $redirectUrl = getRoleRedirectUrl($userRole);
        echo "  [OK] Login exitoso! Rol obtenido: $userRole | Redireccion a: $redirectUrl\n";
    } else {
        echo "  [ERROR] Fallo el login: " . $result['message'] . "\n";
    }
}
