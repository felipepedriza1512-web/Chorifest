<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/auth/AuthMiddleware.php';

enforceSecureSession();

$currentUser = $_SESSION['user'] ?? null;
if (!$currentUser) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Sesión no iniciada.']);
    exit;
}

$userId = (int)($currentUser['id'] ?? $currentUser['id_usuario'] ?? 0);

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true) ?? $_POST;

$csrfToken = $data['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verifyCsrfToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF inválido o ausente.']);
    exit;
}

$idPedido = (int)($data['id_pedido'] ?? 0);
$puntuacion = (int)($data['puntuacion'] ?? 0);
$comentario = trim($data['comentario'] ?? '');

if ($idPedido <= 0 || $puntuacion < 1 || $puntuacion > 5) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Por favor selecciona una puntuación entre 1 y 5 estrellas.']);
    exit;
}

try {
    $db = getDatabaseConnection();

    // Verificar que el pedido pertenece a este cliente y está entregado (6)
    $stmt = $db->prepare("SELECT id_cliente, id_estado FROM pedido WHERE id_pedido = ?");
    $stmt->execute([$idPedido]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pedido || (int)$pedido['id_cliente'] !== $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No tienes permiso para calificar un pedido ajeno.']);
        exit;
    }

    if ((int)$pedido['id_estado'] !== 6) { // 6 = Entregado
        echo json_encode(['success' => false, 'message' => 'Solo puedes calificar un pedido cuando haya sido entregado.']);
        exit;
    }

    // Insertar o actualizar calificación
    $stmtCalif = $db->prepare("INSERT INTO calificacion (id_pedido, id_cliente, puntuacion, comentario, fecha) 
        VALUES (?, ?, ?, ?, NOW()) 
        ON DUPLICATE KEY UPDATE puntuacion = VALUES(puntuacion), comentario = VALUES(comentario), fecha = NOW()");
    
    $stmtCalif->execute([$idPedido, $userId, $puntuacion, $comentario]);

    echo json_encode(['success' => true, 'message' => '¡Muchas gracias por tu calificación! Nos alegra servirte.']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al guardar calificación: ' . $e->getMessage()]);
}
