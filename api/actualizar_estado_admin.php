<?php
require_once __DIR__ . '/../controllers/auth/AuthMiddleware.php';
checkRole(2);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/AdminModel.php';

$db = getDatabaseConnection();
$adminModel = new AdminModel($db);

$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?? [];
}

$csrfToken = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verifyCsrfToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF inválido o expirado.']);
    exit;
}

$idPedido = (int)($input['id_pedido'] ?? 0);
$action = trim($input['action'] ?? '');
$motivo = trim($input['motivo'] ?? '');
$idAdmin = (int)($_SESSION['user']['id'] ?? $_SESSION['user']['id_usuario'] ?? 0);

if ($idPedido <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de pedido no válido.']);
    exit;
}

$ok = false;
$msg = 'Acción desconocida';

if ($action === 'aceptar') {
    // Transición a Aceptado (2)
    $ok = $adminModel->updatePedidoEstadoWithAudit($idPedido, 2, $idAdmin, 'Pedido aceptado desde el panel de control.');
    $msg = $ok ? 'Pedido aceptado correctamente.' : 'No se pudo aceptar el pedido. Transición no permitida.';
} elseif ($action === 'rechazar') {
    if (empty($motivo)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Debe proporcionar un motivo para rechazar el pedido.']);
        exit;
    }
    // Transición a Rechazado (10)
    $ok = $adminModel->updatePedidoEstadoWithAudit($idPedido, 10, $idAdmin, $motivo);
    $msg = $ok ? 'Pedido rechazado correctamente.' : 'No se pudo rechazar el pedido.';
} elseif ($action === 'verificar_pago') {
    $ok = $adminModel->verificarPagoWithAudit($idPedido, $idAdmin);
    $msg = $ok ? 'Pago verificado y pedido aceptado.' : 'No se pudo verificar el pago.';
} elseif ($action === 'cancelar') {
    if (empty($motivo)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Debe proporcionar un motivo para cancelar el pedido.']);
        exit;
    }
    // Transición a Cancelado (7)
    $ok = $adminModel->updatePedidoEstadoWithAudit($idPedido, EstadoPedido::CANCELADO, $idAdmin, "Cancelado: " . $motivo);
    if ($ok) {
        // Resolver novedad pendiente si existía
        $db->prepare("UPDATE novedad_entrega SET estado = 'ATENDIDA', atendida_por = ?, fecha_atencion = NOW() WHERE id_pedido = ? AND estado = 'PENDIENTE'")
           ->execute([$idAdmin, $idPedido]);
        $msg = 'Pedido cancelado correctamente.';
    } else {
        $msg = 'No se pudo cancelar el pedido.';
    }
} elseif ($action === 'reintentar' || $action === 'reintentar_novedad') {
    // Resolver novedad pendiente y volver a En camino (5)
    $db->prepare("UPDATE novedad_entrega SET estado = 'ATENDIDA', atendida_por = ?, fecha_atencion = NOW() WHERE id_pedido = ? AND estado = 'PENDIENTE'")
       ->execute([$idAdmin, $idPedido]);
    $ok = $adminModel->updatePedidoEstadoWithAudit($idPedido, EstadoPedido::EN_CAMINO, $idAdmin, "Novedad resuelta por admin: Reintentando entrega");
    $msg = $ok ? 'Novedad resuelta. Pedido nuevamente En camino.' : 'No se pudo reintentar la entrega.';
} elseif ($action === 'contactar' || $action === 'contactar_cliente') {
    $detalle = trim($motivo ?: 'Se contactó al cliente respecto a la novedad. El pedido permanece bloqueado.');
    $db->prepare("INSERT INTO historial_pedido (id_pedido, id_estado, id_usuario, comentario, fecha) VALUES (?, ?, ?, ?, NOW())")
       ->execute([$idPedido, EstadoPedido::PROBLEMA, $idAdmin, $detalle]);
    $ok = true;
    $msg = 'Gestión de contacto registrada. El pedido sigue bloqueado.';
}

echo json_encode([
    'success' => $ok,
    'message' => $msg,
    'id_pedido' => $idPedido,
    'action' => $action
], JSON_UNESCAPED_UNICODE);
