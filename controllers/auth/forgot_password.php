<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/../../config/database.php';
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'forgot_password') {
    $result = $authController->handleForgotPassword($_POST);
    $message = $result['message'];
    $isSuccess = $result['success'];

    if ($isSuccess) {
        header('Location: reset_password.php?email=' . urlencode($_POST['email']));
        exit;
    }
}

require_once __DIR__ . '/../../views/auth/forgot_password.php';
