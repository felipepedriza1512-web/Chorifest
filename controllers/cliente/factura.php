<?php
session_start();
require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkClientOnly(); // Solo clientes pueden acceder a sus facturas
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/AdminModel.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID de pedido no válido.");
}

$idPedido = (int)$_GET['id'];
$currentUser = $_SESSION['user'];

$db = getDatabaseConnection();
// Reutilizamos AdminModel para obtener el detalle completo, pero validaremos la pertenencia
$adminModel = new AdminModel($db);

$pedido = $adminModel->getDetallePedidoCompleto($idPedido);

if (!$pedido) {
    die("Pedido no encontrado.");
}

// Validar que el pedido pertenece al cliente logueado
if ($pedido['id_cliente'] != $currentUser['id']) {
    die("No tienes permiso para ver esta factura.");
}

$pageTitle = 'Factura Pedido #' . str_pad($pedido['id_pedido'], 4, '0', STR_PAD_LEFT);
$isClient = true;

// Renderizar la misma vista de factura que usa el administrador
require_once __DIR__ . '/../../views/admin/factura_view.php';
