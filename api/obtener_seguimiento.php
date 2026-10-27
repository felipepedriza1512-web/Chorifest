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

$idPedido = (int)($_GET['id_pedido'] ?? $_GET['id'] ?? 0);
if ($idPedido <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de pedido no especificado.']);
    exit;
}

try {
    $db = getDatabaseConnection();

    // Consultar información del pedido
    $query = "SELECT p.*, e.nombre as estado_nombre, e.descripcion as estado_descripcion,
                     m.metodo as metodo_pago, m.estado as estado_pago, m.comprobante,
                     dir.direccion as dir_texto, dir.barrio, dir.referencia, dir.latitud as cliente_lat, dir.longitud as cliente_lng,
                     c.nombre as cliente_nombre, c.telefono as cliente_telefono
              FROM pedido p
              LEFT JOIN estado_pedido e ON p.id_estado = e.id_estado
              LEFT JOIN pago m ON p.id_pedido = m.id_pedido
              LEFT JOIN direccion dir ON p.id_direccion = dir.id_direccion
              LEFT JOIN usuario c ON p.id_cliente = c.id_usuario
              WHERE p.id_pedido = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$idPedido]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        echo json_encode(['success' => false, 'message' => 'Pedido no encontrado.']);
        exit;
    }

    // CONTROL DE ACCESO STRICTO (Regla 3 del prompt):
    // Pueden ver la ubicación/seguimiento únicamente:
    // 1) El cliente dueño del pedido (id_cliente === $userId)
    // 2) El admin (userRole === 2)
    // 3) El domiciliario asignado (userRole === 4)
    $esDuenio = ((int)$pedido['id_cliente'] === $userId);
    $esAdmin = ($userRole === 2);
    
    // Obtener domiciliario asignado
    $stmtDom = $db->prepare("SELECT a.id_domiciliario, u.nombre, u.telefono 
                             FROM asignacion_domicilio a 
                             JOIN usuario u ON a.id_domiciliario = u.id_usuario 
                             WHERE a.id_pedido = ? LIMIT 1");
    $stmtDom->execute([$idPedido]);
    $domiciliario = $stmtDom->fetch(PDO::FETCH_ASSOC);

    $esDomiciliarioAsignado = ($domiciliario && (int)$domiciliario['id_domiciliario'] === $userId);

    if (!$esDuenio && !$esAdmin && !$esDomiciliarioAsignado && $userRole !== 3) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No tienes permiso para ver el seguimiento de este pedido.']);
        exit;
    }

    // Obtener historial con horas de cambio
    $stmtHist = $db->prepare("SELECT h.*, e.nombre as estado_nombre 
                              FROM historial_pedido h 
                              JOIN estado_pedido e ON h.id_estado = e.id_estado 
                              WHERE h.id_pedido = ? 
                              ORDER BY h.fecha ASC");
    $stmtHist->execute([$idPedido]);
    $historialRaw = $stmtHist->fetchAll(PDO::FETCH_ASSOC);

    $historial = [];
    foreach ($historialRaw as $h) {
        $historial[] = [
            'id_estado' => (int)$h['id_estado'],
            'estado_nombre' => $h['estado_nombre'],
            'comentario' => $h['comentario'],
            'fecha' => $h['fecha'],
            'hora_formateada' => date('h:i A', strtotime($h['fecha'])),
            'fecha_formateada' => date('d/m/Y h:i A', strtotime($h['fecha']))
        ];
    }

    // Coordenadas para el cliente
    $tieneCoordenadas = (!empty($pedido['cliente_lat']) && !empty($pedido['cliente_lng']));
    $clienteLat = $tieneCoordenadas ? (float)$pedido['cliente_lat'] : null;
    $clienteLng = $tieneCoordenadas ? (float)$pedido['cliente_lng'] : null;

    // Obtener ubicación actual del domiciliario si el pedido está "En camino" (5)
    $ubicacionDomi = null;
    if ((int)$pedido['id_estado'] === 5) { // En camino
        $stmtUbic = $db->prepare("SELECT latitud, longitud, heading, velocidad, ultima_actualizacion 
                                  FROM ubicacion_domiciliario 
                                  WHERE id_pedido = ? 
                                  LIMIT 1");
        $stmtUbic->execute([$idPedido]);
        $uRaw = $stmtUbic->fetch(PDO::FETCH_ASSOC);

        if ($uRaw) {
            $segundos = time() - strtotime($uRaw['ultima_actualizacion']);
            $ubicacionDomi = [
                'lat' => (float)$uRaw['latitud'],
                'lng' => (float)$uRaw['longitud'],
                'heading' => $uRaw['heading'] ? (float)$uRaw['heading'] : 0,
                'velocidad' => $uRaw['velocidad'] ? (float)$uRaw['velocidad'] : 0,
                'ultima_actualizacion' => $uRaw['ultima_actualizacion'],
                'segundos_transcurridos' => $segundos,
                'es_reciente' => ($segundos <= 15)
            ];
        }
    }

    // Obtener calificación si ya existe
    $stmtCalif = $db->prepare("SELECT puntuacion, comentario, fecha FROM calificacion WHERE id_pedido = ? LIMIT 1");
    $stmtCalif->execute([$idPedido]);
    $calificacion = $stmtCalif->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'pedido' => [
            'id_pedido' => (int)$pedido['id_pedido'],
            'id_estado' => (int)$pedido['id_estado'],
            'estado_nombre' => $pedido['estado_nombre'],
            'estado_descripcion' => $pedido['estado_descripcion'],
            'fecha' => $pedido['fecha'],
            'fecha_formateada' => date('d/m/Y h:i A', strtotime($pedido['fecha'])),
            'total' => (float)$pedido['total'],
            'metodo_pago' => $pedido['metodo_pago'],
            'estado_pago' => $pedido['estado_pago'],
            'comprobante' => $pedido['comprobante'],
            'observaciones' => $pedido['observaciones']
        ],
        'historial' => $historial,
        'cliente' => [
            'nombre' => $pedido['cliente_nombre'],
            'direccion' => $pedido['dir_texto'],
            'barrio' => $pedido['barrio'],
            'referencia' => $pedido['referencia'],
            'lat' => $clienteLat,
            'lng' => $clienteLng,
            'tiene_coordenadas' => $tieneCoordenadas
        ],
        'domiciliario' => $domiciliario ? [
            'id' => (int)$domiciliario['id_domiciliario'],
            'nombre' => $domiciliario['nombre'],
            'telefono' => $domiciliario['telefono']
        ] : null,
        'ubicacion_domiciliario' => $ubicacionDomi,
        'calificacion' => $calificacion ?: null
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()]);
}
