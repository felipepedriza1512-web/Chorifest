<?php
/**
 * Controlador de Liquidaciones de Domiciliario - Fase 5
 * Rol: Domiciliario (id_rol = 4)
 * Aislamiento estricto IDOR: Un domiciliario solo puede ver sus propias liquidaciones y cobros.
 */

require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkRole(4);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/ReporteModel.php';

$db = getDatabaseConnection();
$reporteModel = new ReporteModel($db);

$currentUser = $_SESSION['user'] ?? null;
$idDomiciliario = (int)($currentUser['id'] ?? $currentUser['id_usuario'] ?? 0);

// Control estricto anti-IDOR: Si se manipula un id_domiciliario ajeno en la URL, se bloquea con 403
if (isset($_GET['id_domiciliario'])) {
    $requestedId = (int)$_GET['id_domiciliario'];
    if ($requestedId !== $idDomiciliario) {
        http_response_code(403);
        echo "403 Forbidden: No tienes autorización para consultar las liquidaciones o cobros de otro domiciliario.";
        exit;
    }
}

// Consultar únicamente las liquidaciones y cobros del domiciliario autenticado
$liquidaciones = $reporteModel->getHistorialLiquidaciones($idDomiciliario);
$pedidosPendientesEfectivo = $reporteModel->getPedidosPendientesLiquidacion($idDomiciliario);

$totalEfectivoPendiente = 0.0;
foreach ($pedidosPendientesEfectivo as $pe) {
    $totalEfectivoPendiente += (float)$pe['total'];
}

$currentPage = 'liquidaciones';
$pageTitle = 'Mis Liquidaciones de Turno';

require_once __DIR__ . '/../../views/layouts/empleado_header.php';
require_once __DIR__ . '/../../views/domiciliario/liquidaciones.php';
require_once __DIR__ . '/../../views/layouts/empleado_footer.php';
