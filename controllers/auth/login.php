<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UserModel.php';
require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/AuthMiddleware.php';

enforceSecureSession();

$db = getDatabaseConnection();
$userModel = new UserModel($db);
$authController = new AuthController($userModel);

$currentUser = $_SESSION['user'] ?? null;
$message = null;
$isSuccess = true;

if (isset($_GET['timeout'])) {
    $message = "Tu sesión ha expirado por inactividad. Inicia sesión nuevamente.";
    $isSuccess = false;
} elseif (isset($_GET['error']) && $_GET['error'] === 'inactivo') {
    $message = "Tu cuenta se encuentra inactiva o ha sido desactivada por un administrador.";
    $isSuccess = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $result = $authController->handleLogin($_POST);
    $message = $result['message'];
    $isSuccess = $result['success'];

    if ($isSuccess) {
        if (!empty($result['user']['debe_cambiar_password'])) {
            header('Location: ' . baseUrl('/controllers/auth/cambiar_password_obligatorio.php'));
            exit;
        }
        $rol = (int)($result['user']['rol'] ?? 1);
        header('Location: ' . getRoleRedirectUrl($rol));
        exit;
    }
}

// Cargar la vista de login
require_once __DIR__ . '/../../views/auth/login.php';

