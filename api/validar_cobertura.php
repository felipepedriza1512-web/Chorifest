<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ZonaModel.php';
require_once __DIR__ . '/../models/PedidoModel.php';
require_once __DIR__ . '/../controllers/auth/AuthMiddleware.php';

enforceSecureSession();

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true) ?? $_POST;

$lat = isset($data['lat']) ? (float)$data['lat'] : (isset($_GET['lat']) ? (float)$_GET['lat'] : null);
$lng = isset($data['lng']) ? (float)$data['lng'] : (isset($_GET['lng']) ? (float)$_GET['lng'] : null);
$subtotal = isset($data['subtotal']) ? (float)$data['subtotal'] : (isset($_GET['subtotal']) ? (float)$_GET['subtotal'] : 0);

if ($lat === null || $lng === null || $lat == 0 || $lng == 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'en_cobertura' => false, 'message' => 'Coordenadas de ubicación requeridas.']);
    exit;
}

$db = getDatabaseConnection();
$zonaModel = new ZonaModel($db);

$bestZone = $zonaModel->findMatchingZone($lat, $lng);

if (!$bestZone) {
    echo json_encode([
        'success' => true,
        'cobertura' => false,
        'en_cobertura' => false,
        'message' => 'Tu ubicación se encuentra fuera de nuestra zona de cobertura para entregas.'
    ]);
    exit;
}

$montoMinimo = (float)$bestZone['monto_minimo'];
// Regla de Negocio Centralizada: Domicilio Gratis
$costoDomicilio = PedidoModel::COBRAR_DOMICILIO ? (float)($bestZone['costo_domicilio'] ?? 0) : 0.00;
$cumpleMinimo = ($subtotal <= 0 || $subtotal >= $montoMinimo);

echo json_encode([
    'success' => true,
    'cobertura' => true,
    'en_cobertura' => true,
    'id_zona' => (int)$bestZone['id_zona'],
    'nombre' => $bestZone['nombre'],
    'costo_domicilio' => $costoDomicilio,
    'monto_minimo' => $montoMinimo,
    'zona' => [
        'id_zona' => (int)$bestZone['id_zona'],
        'nombre' => $bestZone['nombre'],
        'costo_domicilio' => $costoDomicilio,
        'monto_minimo' => $montoMinimo,
        'tiempo_estimado_min' => (int)$bestZone['tiempo_estimado_min'],
        'cumple_minimo' => $cumpleMinimo
    ],
    'message' => $cumpleMinimo 
        ? "Ubicación confirmada dentro de '{$bestZone['nombre']}'." 
        : "El monto mínimo de compra para esta zona es $" . number_format($montoMinimo, 2) . "."
]);
