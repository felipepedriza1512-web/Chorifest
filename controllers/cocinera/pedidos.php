<?php
session_start();
require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkRole(3);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/StaffModel.php';
require_once __DIR__ . '/../../models/IngredienteModel.php';

$db = getDatabaseConnection();
$staffModel = new StaffModel($db);
$ingredienteModel = new IngredienteModel($db);

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $idPedido = (int)$_POST['id_pedido'];
        $idEstado = (int)$_POST['id_estado'];
        if ($idPedido > 0 && $idEstado > 0) {
            $staffModel->updatePedidoStatus($idPedido, $idEstado);
        }
    } elseif ($_POST['action'] === 'toggle_ingrediente') {
        $idIngrediente = (int)$_POST['id_ingrediente'];
        if ($idIngrediente > 0) {
            $ingredienteModel->toggleEstado($idIngrediente);
        }
    }
    header('Location: pedidos.php');
    exit;
}

$pedidos = $staffModel->getPedidosCocina();

// Fetch details for each order
foreach ($pedidos as &$pedido) {
    $pedido['detalles'] = $staffModel->getDetallesPedido($pedido['id_pedido']);
}

$ingredientes = $ingredienteModel->getIngredientes();

$currentPage = 'cocina';
$pageTitle = 'Panel de Cocina';

require_once __DIR__ . '/../../views/layouts/empleado_header.php';
require_once __DIR__ . '/../../views/cocinera/pedidos.php';
require_once __DIR__ . '/../../views/layouts/empleado_footer.php';
