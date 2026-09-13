<?php
session_start();
require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkRole(2);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/AdminModel.php';

$db = getDatabaseConnection();
$adminModel = new AdminModel($db);

$stats = $adminModel->getDashboardStats();
$chartData = $adminModel->getSalesChartData();

$currentPage = 'dashboard';
$pageTitle = 'Dashboard General';

require_once __DIR__ . '/../../views/layouts/admin_header.php';
require_once __DIR__ . '/../../views/admin/dashboard.php';
require_once __DIR__ . '/../../views/layouts/admin_footer.php';
