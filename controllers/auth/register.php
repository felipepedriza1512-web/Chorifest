<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/../../config/database.php';
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
        // Automatically login the user after successful registration or redirect to login
        // For now, redirect to login page with a success message
        // Since we don't have login.php yet, we will just show the success message in the view
    }
}

// Include the view
require_once __DIR__ . '/../../views/auth/register.php';
