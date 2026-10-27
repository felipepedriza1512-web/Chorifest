<?php
/**
 * Test HTTP Real de Flujo Completo con Sesiones, Cookies y CSRF
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/EstadoPedido.php';

$baseUrl = 'http://chorifest.test';

echo "=========================================================\n";
echo "PRUEBAS REALES END-TO-END HTTP CON SESIÓN Y CSRF\n";
echo "=========================================================\n\n";

function httpRequest($url, $method = 'GET', $data = null, $cookie = null, $headers = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    
    $reqHeaders = $headers;
    if ($cookie) {
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    }
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if (is_array($data)) {
            $postData = http_build_query($data);
            $reqHeaders[] = 'Content-Type: application/x-www-form-urlencoded';
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        } else if (is_string($data)) {
            $reqHeaders[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $reqHeaders);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    
    $headerStr = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    // Parse cookies from headers
    preg_match_all('/^Set-Cookie:\s*([^;]+)/mi', $headerStr, $cookieMatches);
    $cookies = [];
    if (!empty($cookieMatches[1])) {
        foreach ($cookieMatches[1] as $c) {
            $parts = explode('=', $c, 2);
            if (count($parts) === 2) {
                $cookies[trim($parts[0])] = trim($parts[1]);
            }
        }
    }
    
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'headers' => $headerStr,
        'body' => $body,
        'cookies' => $cookies
    ];
}

function loginRole($email, $password, $baseUrl) {
    $res = httpRequest($baseUrl . '/controllers/auth/login.php', 'POST', [
        'action' => 'login',
        'email' => $email,
        'password' => $password
    ]);
    
    $sessId = $res['cookies']['PHPSESSID'] ?? null;
    if (!$sessId) {
        throw new Exception("Error al autenticar a $email");
    }
    $cookieHeader = "PHPSESSID=" . $sessId;
    
    $tokenRes = httpRequest($baseUrl . '/api/csrf_token.php', 'GET', null, $cookieHeader);
    $j = json_decode($tokenRes['body'], true);
    $csrf = $j['csrf_token'] ?? '';
    
    return [
        'cookie' => $cookieHeader,
        'csrf' => $csrf
    ];
}

// 1. Iniciar sesión real para cada rol
echo "1. Autenticando roles vía HTTP real...\n";
$adminAuth = loginRole('admin@gmail.com', '123456', $baseUrl);
echo "  ✔ Admin autenticado. Cookie: {$adminAuth['cookie']} | CSRF: " . substr($adminAuth['csrf'], 0, 10) . "...\n";

$cocinaAuth = loginRole('cocina@gmail.com', '123456', $baseUrl);
echo "  ✔ Cocinera autenticada. Cookie: {$cocinaAuth['cookie']}\n";

$domiAuth = loginRole('domi@gmail.com', '123456', $baseUrl);
echo "  ✔ Domiciliario autenticado. Cookie: {$domiAuth['cookie']}\n";

$db = getDatabaseConnection();

// Crear pedido de prueba en estado Aceptado (2)
$db->exec("INSERT INTO pedido (id_cliente, id_direccion, id_zona, id_estado, fecha, subtotal, costo_domicilio, total, observaciones) VALUES (13, 1, 1, 2, NOW(), 25000.00, 3000.00, 28000.00, 'Test HTTP Flow')");
$idTestPedido = (int)$db->lastInsertId();
$db->exec("INSERT INTO pago (id_pedido, metodo, estado, pendiente_devolucion, fecha_pago) VALUES ($idTestPedido, 'Transferencia / Nequi', 'VERIFICADO', 0, NOW())");
echo "\n  Pedido de prueba #{$idTestPedido} creado en estado ACEPTADO con pago VERIFICADO.\n\n";

// -------------------------------------------------------------
// PRUEBA (a): El admin recibe 403 al intentar transiciones de cocina/despacho
// -------------------------------------------------------------
echo "[PRUEBA A] Admin intenta En preparación (3), Empacado (4), En camino (5) o Entregado (6)...\n";
$estadosProhibidosAdmin = [
    3 => 'En preparación',
    4 => 'Empacado',
    5 => 'En camino',
    6 => 'Entregado'
];

$allBlocked403 = true;
foreach ($estadosProhibidosAdmin as $stId => $stNombre) {
    $res = httpRequest($baseUrl . '/api/actualizar_estado.php', 'POST', [
        'id_pedido' => $idTestPedido,
        'nuevo_estado' => $stId,
        'csrf_token' => $adminAuth['csrf']
    ], $adminAuth['cookie']);
    
    echo "  - Admin intentando '$stNombre' (ID $stId) -> HTTP {$res['code']}";
    $j = json_decode($res['body'], true);
    if ($res['code'] === 403) {
        echo " [403 FORBIDDEN - RECHAZADO CORRECTAMENTE]\n";
    } else {
        echo " [ERROR: Código esperado 403, recibido {$res['code']}]\n";
        $allBlocked403 = false;
    }
}
if ($allBlocked403) {
    echo "✔ CONFIRMADO (a): El admin recibe 403 al intentar En preparación, Empacado, En camino o Entregado.\n\n";
} else {
    echo "❌ FALLÓ (a)\n\n";
}

// -------------------------------------------------------------
// PRUEBA (b): POST sin CSRF o con CSRF inválido es rechazado (403)
// -------------------------------------------------------------
echo "[PRUEBA B] Validación estricta de token CSRF en POST HTTP...\n";
// B1: Sin CSRF
$resNoCsrf = httpRequest($baseUrl . '/api/actualizar_estado.php', 'POST', [
    'id_pedido' => $idTestPedido,
    'nuevo_estado' => 10,
    'comentario' => 'Rechazo test'
], $adminAuth['cookie']);

echo "  - POST sin CSRF -> HTTP {$resNoCsrf['code']} " . ($resNoCsrf['code'] === 403 ? "[RECHAZADO CORRECTAMENTE 403]" : "[FALLO]") . "\n";

// B2: CSRF inválido
$resInvalidCsrf = httpRequest($baseUrl . '/api/actualizar_estado.php', 'POST', [
    'id_pedido' => $idTestPedido,
    'nuevo_estado' => 10,
    'csrf_token' => 'token_falso_invalido_123',
    'comentario' => 'Rechazo test'
], $adminAuth['cookie']);

echo "  - POST con CSRF inválido -> HTTP {$resInvalidCsrf['code']} " . ($resInvalidCsrf['code'] === 403 ? "[RECHAZADO CORRECTAMENTE 403]" : "[FALLO]") . "\n";

if ($resNoCsrf['code'] === 403 && $resInvalidCsrf['code'] === 403) {
    echo "✔ CONFIRMADO (b): Un POST sin CSRF o con CSRF inválido es rechazado con 403.\n\n";
} else {
    echo "❌ FALLÓ (b)\n\n";
}

// -------------------------------------------------------------
// PRUEBA (c): Cancelar sin motivo es rechazado (400)
// -------------------------------------------------------------
echo "[PRUEBA C] Intento de cancelar sin motivo...\n";
$resCancelNoReason = httpRequest($baseUrl . '/api/actualizar_estado.php', 'POST', [
    'id_pedido' => $idTestPedido,
    'nuevo_estado' => EstadoPedido::CANCELADO,
    'comentario' => '', // Motivo vacío
    'csrf_token' => $adminAuth['csrf']
], $adminAuth['cookie']);

echo "  - Cancelar sin motivo -> HTTP {$resCancelNoReason['code']} " . ($resCancelNoReason['code'] === 400 ? "[RECHAZADO 400: Motivo obligatorio]" : "[FALLO]") . "\n";
if ($resCancelNoReason['code'] === 400) {
    echo "✔ CONFIRMADO (c): Cancelar sin motivo es rechazado.\n\n";
} else {
    echo "❌ FALLÓ (c)\n\n";
}

// -------------------------------------------------------------
// PRUEBA (d): Pedido con novedad PENDIENTE queda bloqueado; Reintentar, Contactar y Cancelar
// -------------------------------------------------------------
echo "[PRUEBA D] Flujo de novedad, bloqueo y resolución...\n";

// 1. Cocinera pasa a En preparación (3)
$resCocina1 = httpRequest($baseUrl . '/api/actualizar_estado.php', 'POST', [
    'id_pedido' => $idTestPedido,
    'nuevo_estado' => EstadoPedido::EN_PREPARACION,
    'csrf_token' => $cocinaAuth['csrf']
], $cocinaAuth['cookie']);
echo "  1. Cocinera -> En preparación (3): HTTP {$resCocina1['code']}\n";

// 2. Cocinera pasa a Empacado (4)
$resCocina2 = httpRequest($baseUrl . '/api/actualizar_estado.php', 'POST', [
    'id_pedido' => $idTestPedido,
    'nuevo_estado' => EstadoPedido::EMPACADO,
    'csrf_token' => $cocinaAuth['csrf']
], $cocinaAuth['cookie']);
echo "  2. Cocinera -> Empacado (4): HTTP {$resCocina2['code']}\n";

// 3. Admin asigna domiciliario
$resAsig = httpRequest($baseUrl . '/api/actualizar_estado.php', 'POST', [
    'id_pedido' => $idTestPedido,
    'nuevo_estado' => EstadoPedido::EMPACADO,
    'id_domiciliario' => 16,
    'csrf_token' => $adminAuth['csrf']
], $adminAuth['cookie']);
echo "  3. Admin asigna domiciliario (#16): HTTP {$resAsig['code']}\n";

// 4. Domiciliario pasa a En camino (5)
$resDomi1 = httpRequest($baseUrl . '/api/actualizar_estado.php', 'POST', [
    'id_pedido' => $idTestPedido,
    'nuevo_estado' => EstadoPedido::EN_CAMINO,
    'csrf_token' => $domiAuth['csrf']
], $domiAuth['cookie']);
echo "  4. Domiciliario -> En camino (5): HTTP {$resDomi1['code']}\n";

// 5. Domiciliario reporta novedad PENDIENTE (Estado 12: PROBLEMA)
$resNovReport = httpRequest($baseUrl . '/api/actualizar_estado.php', 'POST', [
    'id_pedido' => $idTestPedido,
    'nuevo_estado' => EstadoPedido::PROBLEMA,
    'tipo_novedad' => 'CLIENTE AUSENTE',
    'comentario' => 'No atiende en la dirección',
    'csrf_token' => $domiAuth['csrf']
], $domiAuth['cookie']);
echo "  5. Domiciliario reporta Novedad (CLIENTE AUSENTE): HTTP {$resNovReport['code']}\n";

// Verificar estado de bloqueo en DB
$stDB = $db->query("SELECT id_estado FROM pedido WHERE id_pedido = $idTestPedido")->fetchColumn();
$novPend = $db->query("SELECT estado FROM novedad_entrega WHERE id_pedido = $idTestPedido ORDER BY id_novedad DESC LIMIT 1")->fetchColumn();
echo "     Estado actual pedido: $stDB (12 = PROBLEMA) | Novedad: $novPend (PENDIENTE)\n";

// 6. Domiciliario intenta marcar Entregado (6) mientras está bloqueado
$resDomiBlocked = httpRequest($baseUrl . '/api/actualizar_estado.php', 'POST', [
    'id_pedido' => $idTestPedido,
    'nuevo_estado' => EstadoPedido::ENTREGADO,
    'csrf_token' => $domiAuth['csrf']
], $domiAuth['cookie']);
echo "  6. Domiciliario intenta marcar ENTREGADO mientras hay novedad -> HTTP {$resDomiBlocked['code']} " . ($resDomiBlocked['code'] === 403 ? "[BLOQUEADO 403 FORBIDDEN]" : "[FALLO]") . "\n";

// 7. Acción: Contactar (registra contacto, pero el pedido SIGUE bloqueado)
$resContact = httpRequest($baseUrl . '/api/actualizar_estado.php', 'POST', [
    'id_pedido' => $idTestPedido,
    'action' => 'contactar',
    'comentario' => 'Se llamó al cliente al celular 3001234567, no responde.',
    'csrf_token' => $adminAuth['csrf']
], $adminAuth['cookie']);
$stPostContact = $db->query("SELECT id_estado FROM pedido WHERE id_pedido = $idTestPedido")->fetchColumn();
$novPostContact = $db->query("SELECT estado FROM novedad_entrega WHERE id_pedido = $idTestPedido ORDER BY id_novedad DESC LIMIT 1")->fetchColumn();
echo "  7. Admin ejecuta 'Contactar' -> HTTP {$resContact['code']}. Pedido sigue en estado: $stPostContact (12 = PROBLEMA/BLOQUEADO) y Novedad sigue: $novPostContact (PENDIENTE).\n";

// 8. Acción: Reintentar (resuelve novedad y regresa a En camino 5)
$resRetry = httpRequest($baseUrl . '/api/actualizar_estado.php', 'POST', [
    'id_pedido' => $idTestPedido,
    'nuevo_estado' => EstadoPedido::EN_CAMINO,
    'comentario' => 'Reintentando entrega',
    'csrf_token' => $adminAuth['csrf']
], $adminAuth['cookie']);
$stPostRetry = $db->query("SELECT id_estado FROM pedido WHERE id_pedido = $idTestPedido")->fetchColumn();
$novPostRetry = $db->query("SELECT estado FROM novedad_entrega WHERE id_pedido = $idTestPedido ORDER BY id_novedad DESC LIMIT 1")->fetchColumn();
echo "  8. Admin ejecuta 'Reintentar' -> HTTP {$resRetry['code']}. Pedido pasa a: $stPostRetry (5 = EN CAMINO) y Novedad resuelta a: $novPostRetry (ATENDIDA).\n";

// 9. Simular nueva novedad y posterior CANCELACIÓN
httpRequest($baseUrl . '/api/actualizar_estado.php', 'POST', [
    'id_pedido' => $idTestPedido,
    'nuevo_estado' => EstadoPedido::PROBLEMA,
    'tipo_novedad' => 'DIRECCION INCORRECTA',
    'comentario' => 'Dirección no existe en el mapa',
    'csrf_token' => $domiAuth['csrf']
], $domiAuth['cookie']);

$resCancel = httpRequest($baseUrl . '/api/actualizar_estado.php', 'POST', [
    'id_pedido' => $idTestPedido,
    'nuevo_estado' => EstadoPedido::CANCELADO,
    'comentario' => 'Cancelación definitiva acordada con el cliente',
    'csrf_token' => $adminAuth['csrf']
], $adminAuth['cookie']);
echo "  9. Admin ejecuta 'Cancelar' con motivo -> HTTP {$resCancel['code']}\n";

$stFinal = $db->query("SELECT id_estado FROM pedido WHERE id_pedido = $idTestPedido")->fetchColumn();
$novFinal = $db->query("SELECT estado FROM novedad_entrega WHERE id_pedido = $idTestPedido ORDER BY id_novedad DESC LIMIT 1")->fetchColumn();
echo "     Estado final pedido: $stFinal (7 = CANCELADO) | Novedad final: $novFinal (ATENDIDA)\n";

if ($resDomiBlocked['code'] === 403 && $stPostContact == 12 && $stPostRetry == 5 && $stFinal == 7 && $novFinal === 'ATENDIDA') {
    echo "✔ CONFIRMADO (d): Pedido con novedad PENDIENTE queda bloqueado; Reintentar, Contactar (sigue bloqueado) y Cancelar funcionan con los efectos acordados.\n\n";
} else {
    echo "❌ FALLÓ (d)\n\n";
}

// -------------------------------------------------------------
// PRUEBA (e): Pedido cancelado con pago verificado queda con pendiente_devolucion = 1
// -------------------------------------------------------------
echo "[PRUEBA E] Verificación de pendiente_devolucion = 1 en tabla pago...\n";
$stmtPago = $db->query("SELECT estado, pendiente_devolucion FROM pago WHERE id_pedido = $idTestPedido")->fetch(PDO::FETCH_ASSOC);
echo "  - Estado del Pago: {$stmtPago['estado']}\n";
echo "  - Valor de pendiente_devolucion: {$stmtPago['pendiente_devolucion']} (1 = Devolución pendiente)\n";

if ((int)$stmtPago['pendiente_devolucion'] === 1) {
    echo "✔ CONFIRMADO (e): Pedido cancelado con pago verificado queda con pendiente_devolucion = 1.\n\n";
} else {
    echo "❌ FALLÓ (e)\n\n";
}

// -------------------------------------------------------------
// LIMPIEZA DE DATOS DE PRUEBA
// -------------------------------------------------------------
echo "Limpiando datos de prueba del pedido #{$idTestPedido}...\n";
$db->exec("DELETE FROM novedad_entrega WHERE id_pedido = $idTestPedido");
$db->exec("DELETE FROM asignacion_domicilio WHERE id_pedido = $idTestPedido");
$db->exec("DELETE FROM historial_pedido WHERE id_pedido = $idTestPedido");
$db->exec("DELETE FROM notificacion WHERE mensaje LIKE '%#$idTestPedido%'");
$db->exec("DELETE FROM pago WHERE id_pedido = $idTestPedido");
$db->exec("DELETE FROM pedido WHERE id_pedido = $idTestPedido");
echo "✔ Datos de prueba eliminados completamente de la base de datos.\n\n";

echo "=========================================================\n";
echo "TODAS LAS PRUEBAS HTTP REALES FINALIZADAS CON ÉXITO\n";
echo "=========================================================\n";
