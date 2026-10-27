<?php
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
    $idUser = (int)($_SESSION['user']['id'] ?? $_SESSION['user']['id_usuario'] ?? 0);
    if ($_POST['action'] === 'update_status') {
        $idPedido = (int)$_POST['id_pedido'];
        $idEstado = (int)$_POST['id_estado'];

        // Verificar estado actual del pedido para impedir saltarse pasos
        $stmtCur = $db->prepare("SELECT id_estado FROM pedido WHERE id_pedido = ?");
        $stmtCur->execute([$idPedido]);
        $currentEstado = (int)$stmtCur->fetchColumn();

        $valido = ($currentEstado === 2 && $idEstado === 3) || ($currentEstado === 3 && $idEstado === 4);
        if (!$valido) {
            http_response_code(403);
            die("Acceso denegado (403): La cocina no puede saltarse pasos ni modificar pedidos fuera de secuencia.");
        }

        $staffModel->updatePedidoStatus($idPedido, $idEstado, $idUser);
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
