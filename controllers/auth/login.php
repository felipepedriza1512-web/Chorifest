<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


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
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $result = $authController->handleLogin($_POST);
    $message = $result['message'];
    $isSuccess = $result['success'];

    if ($isSuccess) {
        $rol = (int)($result['user']['rol'] ?? 1);
        header('Location: ' . getRoleRedirectUrl($rol));
        exit;
    }
}

// Cargar la vista de login
require_once __DIR__ . '/../../views/auth/login.php';

