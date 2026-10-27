<?php
require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkRole(2);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/AdminModel.php';

$db = getDatabaseConnection();
$adminModel = new AdminModel($db);

$stats = $adminModel->getDashboardStats();
$colaPedidos = $adminModel->getColaPedidos();
$alertas = $adminModel->getAlertasOperativas();
$chartData = $adminModel->getSalesChartData();
$topProductos = $adminModel->getTopProductos7Dias();
$domiciliariosEnCamino = $adminModel->getDomiciliariosEnCamino();
$csrfToken = getCsrfToken();

$currentPage = 'dashboard';
$pageTitle = 'Dashboard Operativo';

require_once __DIR__ . '/../../views/layouts/admin_header.php';
require_once __DIR__ . '/../../views/admin/dashboard.php';
require_once __DIR__ . '/../../views/layouts/admin_footer.php';

