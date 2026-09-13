<?php
session_start();
require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkRole(2);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/AdminModel.php';

$db = getDatabaseConnection();
$adminModel = new AdminModel($db);

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $idPedido = (int)$_POST['id_pedido'];
    $idEstado = (int)$_POST['id_estado'];
    if ($idPedido > 0 && $idEstado > 0) {
        $adminModel->updatePedidoStatus($idPedido, $idEstado);
    }
    header('Location: pedidos.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_pago') {
    $idPedido = (int)$_POST['id_pedido'];
    $estadoPago = $_POST['estado_pago']; // 'Verificado' o 'Rechazado'
    $idAdmin = (int)($_SESSION['user']['id'] ?? 0);
    
    if ($idPedido > 0 && in_array($estadoPago, ['Verificado', 'Rechazado'])) {
        $adminModel->updatePagoStatus($idPedido, $estadoPago, $idAdmin);
        
        // Automatización: Cambiar estado del pedido según el pago
        $nuevoEstadoPedido = ($estadoPago === 'Verificado') ? 2 : 10;
        $adminModel->updatePedidoStatus($idPedido, $nuevoEstadoPedido);
    }
    header('Location: pedidos.php');
    exit;
}

$pedidos = $adminModel->getPedidos();
$estados = $adminModel->getEstadosPedido();

$currentPage = 'pedidos';
$pageTitle = 'Gestión de Pedidos';

require_once __DIR__ . '/../../views/layouts/admin_header.php';
require_once __DIR__ . '/../../views/admin/pedidos.php';
require_once __DIR__ . '/../../views/layouts/admin_footer.php';
