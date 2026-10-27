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

$userRole = (int)($currentUser['rol'] ?? $currentUser['id_rol'] ?? 0);
$userId = (int)($currentUser['id'] ?? $currentUser['id_usuario'] ?? 0);

// Solo el rol Domiciliario (4) puede enviar su ubicación GPS
if ($userRole !== 4) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado. Solo domiciliarios pueden compartir ubicación.']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true) ?? $_POST;

$idPedido = (int)($data['id_pedido'] ?? 0);
$lat = (float)($data['lat'] ?? $data['latitud'] ?? 0);
$lng = (float)($data['lng'] ?? $data['longitud'] ?? 0);
$heading = isset($data['heading']) ? (float)$data['heading'] : null;
$speed = isset($data['speed']) ? (float)$data['speed'] : null;

if ($idPedido <= 0 || $lat == 0 || $lng == 0) {
    echo json_encode(['success' => false, 'message' => 'Coordenadas GPS inválidas.']);
    exit;
}

try {
    $db = getDatabaseConnection();

    // 1. Verificar primero que el pedido esté asignado específicamente a este domiciliario
    $stmtAsig = $db->prepare("SELECT 1 FROM asignacion_domicilio WHERE id_pedido = ? AND id_domiciliario = ?");
    $stmtAsig->execute([$idPedido, $userId]);
    if (!$stmtAsig->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Acceso denegado: El pedido no está asignado a tu usuario domiciliario.']);
        exit;
    }

    // 2. Verificar que el pedido existe y está en estado "En camino" (5)
    $stmt = $db->prepare("SELECT id_estado FROM pedido WHERE id_pedido = ?");
    $stmt->execute([$idPedido]);
    $estadoActual = (int)$stmt->fetchColumn();

    if ($estadoActual !== 5) { // 5 = En camino
        echo json_encode(['success' => false, 'message' => 'El pedido no se encuentra en estado En camino. Transmisión GPS desactivada.']);
        exit;
    }

    // Insertar o actualizar última ubicación por pedido
    $stmtUbic = $db->prepare("INSERT INTO ubicacion_domiciliario 
        (id_pedido, id_domiciliario, latitud, longitud, heading, velocidad, ultima_actualizacion)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
            latitud = VALUES(latitud),
            longitud = VALUES(longitud),
            heading = VALUES(heading),
            velocidad = VALUES(velocidad),
            ultima_actualizacion = NOW()");
    
    $stmtUbic->execute([$idPedido, $userId, $lat, $lng, $heading, $speed]);

    echo json_encode([
        'success' => true,
        'message' => 'Ubicación GPS registrada correctamente.',
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al guardar ubicación: ' . $e->getMessage()]);
}
