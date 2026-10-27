<?php
require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkRole(4);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/StaffModel.php';

$db = getDatabaseConnection();
$staffModel = new StaffModel($db);

$idDomiciliario = (int)($_SESSION['user']['id'] ?? $_SESSION['user']['id_usuario'] ?? 0);

// Control estricto anti-IDOR: Si se manipula un id_domiciliario ajeno en la URL, se bloquea con 403
if (isset($_GET['id_domiciliario'])) {
    $reqId = (int)$_GET['id_domiciliario'];
    if ($reqId !== $idDomiciliario) {
        http_response_code(403);
        echo "403 Forbidden: No tienes autorización para consultar rutas o cobros de otro domiciliario.";
        exit;
    }
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $idPedido = (int)$_POST['id_pedido'];
    $idEstado = (int)$_POST['id_estado'];
    
    // Regla 1: Domiciliario solo puede hacer transiciones a En camino (5) o Entregado (6)
    if ($idPedido > 0 && ($idEstado === 5 || $idEstado === 6)) {
        // Verificar asignación estricta
        $stmtCheckAsig = $db->prepare("SELECT 1 FROM asignacion_domicilio WHERE id_pedido = ? AND id_domiciliario = ?");
        $stmtCheckAsig->execute([$idPedido, $idDomiciliario]);
        if (!$stmtCheckAsig->fetch()) {
            http_response_code(403);
            die("Acceso denegado (403): No puedes modificar el estado de un pedido que no tienes asignado.");
        }
        $staffModel->updatePedidoStatus($idPedido, $idEstado, $idDomiciliario);
    }
    header('Location: rutas.php');
    exit;
}

$rutas = $staffModel->getRutasDomicilio($idDomiciliario);

require_once __DIR__ . '/../../models/ReporteModel.php';
$reporteModel = new ReporteModel($db);
$pedidosPendientesEfectivo = $reporteModel->getPedidosPendientesLiquidacion($idDomiciliario);
$totalEfectivoTurno = 0.0;
foreach ($pedidosPendientesEfectivo as $pe) {
    $totalEfectivoTurno += (float)$pe['total'];
}

$currentPage = 'rutas';
$pageTitle = 'Rutas y Entregas en Vivo';

require_once __DIR__ . '/../../views/layouts/empleado_header.php';
require_once __DIR__ . '/../../views/domiciliario/rutas.php';
require_once __DIR__ . '/../../views/layouts/empleado_footer.php';
