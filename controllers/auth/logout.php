<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UserModel.php';
require_once __DIR__ . '/AuthController.php';

$db = getDatabaseConnection();
$userModel = new UserModel($db);
$authController = new AuthController($userModel);

$authController->logout(null); // Force complete logout

if (isset($_GET['redirect']) && $_GET['redirect'] === 'login') {
    header('Location: login.php');
} else {
    header('Location: ../../index.php?logout=success');
}
exit;

