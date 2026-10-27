<?php
require_once __DIR__ . '/../../config/database.php';
startSecureSession();
require_once __DIR__ . '/../../models/UserModel.php';
require_once __DIR__ . '/AuthController.php';

$db = getDatabaseConnection();
$userModel = new UserModel($db);
$authController = new AuthController($userModel);

// If user is already logged in, redirect to index
if ($authController->isLoggedIn()) {
    header('Location: ../../public/index.php');
    exit;
}

$message = null;
$isSuccess = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'register') {
    $result = $authController->handleRegister($_POST);
    $message = $result['message'];
    $isSuccess = $result['success'];

    if ($isSuccess) {
        $loginRes = $authController->handleLogin([
            'email' => $_POST['email'] ?? '',
            'password' => $_POST['password'] ?? ''
        ]);
        if ($loginRes['success'] ?? false) {
            header('Location: ../cliente/catalogo.php');
            exit;
        }
    }
}

// Include the view
require_once __DIR__ . '/../../views/auth/register.php';
