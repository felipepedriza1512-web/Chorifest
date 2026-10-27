<?php
require_once __DIR__ . '/../../config/database.php';
startSecureSession();
require_once __DIR__ . '/../../models/UserModel.php';
require_once __DIR__ . '/AuthController.php';

$db = getDatabaseConnection();
$userModel = new UserModel($db);
$authController = new AuthController($userModel);

if ($authController->isLoggedIn()) {
    header('Location: ../../public/index.php');
    exit;
}

$message = null;
$isSuccess = true;
$email = $_GET['email'] ?? ($_POST['email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset_password') {
    $result = $authController->handleResetPassword($_POST);
    $message = $result['message'];
    $isSuccess = $result['success'];
}

require_once __DIR__ . '/../../views/auth/reset_password.php';
