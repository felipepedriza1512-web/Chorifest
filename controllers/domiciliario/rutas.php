<?php
session_start();
require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkRole(4);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/StaffModel.php';

$db = getDatabaseConnection();
$staffModel = new StaffModel($db);

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $idPedido = (int)$_POST['id_pedido'];
    $idEstado = (int)$_POST['id_estado'];
    if ($idPedido > 0 && $idEstado > 0) {
        $staffModel->updatePedidoStatus($idPedido, $idEstado);
    }
    header('Location: rutas.php');
    exit;
}

$rutas = $staffModel->getRutasDomicilio();

$currentPage = 'rutas';
$pageTitle = 'Rutas de Entrega';

require_once __DIR__ . '/../../views/layouts/empleado_header.php';
require_once __DIR__ . '/../../views/domiciliario/rutas.php';
require_once __DIR__ . '/../../views/layouts/empleado_footer.php';
