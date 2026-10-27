<?php
require_once __DIR__ . '/../controllers/auth/AuthMiddleware.php';
checkRole(2);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/AdminModel.php';

$db = getDatabaseConnection();
$adminModel = new AdminModel($db);

$stats = $adminModel->getDashboardStats();
$cola = $adminModel->getColaPedidos();
$alertas = $adminModel->getAlertasOperativas();
$domiciliarios = $adminModel->getDomiciliariosEnCamino();
$csrfToken = getCsrfToken();

echo json_encode([
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'stats' => $stats,
    'cola_pedidos' => $cola,
    'alertas' => $alertas,
    'domiciliarios_en_camino' => $domiciliarios,
    'csrf_token' => $csrfToken
], JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
