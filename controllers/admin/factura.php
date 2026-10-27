<?php
require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkRole(2); // Solo administradores pueden acceder a facturas de todos
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/AdminModel.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID de pedido no válido.");
}

$idPedido = (int)$_GET['id'];
$db = getDatabaseConnection();
$adminModel = new AdminModel($db);

$pedido = $adminModel->getDetallePedidoCompleto($idPedido);

if (!$pedido) {
    die("Pedido no encontrado.");
}

$pageTitle = 'Factura Pedido #' . str_pad($pedido['id_pedido'], 4, '0', STR_PAD_LEFT);

// Renderizar la vista de la factura
require_once __DIR__ . '/../../views/admin/factura_view.php';
