<?php
/**
 * Test Suite Integral de Control de Acceso y Pruebas entre Usuarios
 * Chorifest - Fase 4
 */

$baseUrl = 'http://localhost/Chorifest';

function makeRequest(string $url, array $options = []): array {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // No seguir redirecciones automáticas para capturar 302
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    if (isset($options['method']) && $options['method'] === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if (isset($options['data'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $options['data']);
        }
    }
    
    if (isset($options['headers'])) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $options['headers']);
    }
    
    if (isset($options['cookie_file'])) {
        curl_setopt($ch, CURLOPT_COOKIEFILE, $options['cookie_file']);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $options['cookie_file']);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    curl_close($ch);
    
    // Obtener Location si fue redirect 302
    $location = '';
    if (preg_match('/^Location:\s*(.*)$/mi', $headers, $m)) {
        $location = trim($m[1]);
    }

    return [
        'code' => $httpCode,
        'headers' => $headers,
        'body' => $body,
        'location' => $location
    ];
}

echo "====================================================================================================================\n";
echo "PARTE (a): ACCESO A TODOS LOS ARCHIVOS PHP SIN SESIÓN (AUDITORÍA COMPLETA)\n";
echo "====================================================================================================================\n";

$filesToTest = [
    // Raíz
    'index.php' => ['tipo' => 'Público', 'esperado' => [200]],
    
    // API
    'api/actualizar_estado.php' => ['tipo' => 'API Protegida', 'esperado' => [403]],
    'api/actualizar_estado_admin.php' => ['tipo' => 'API Protegida (Admin)', 'esperado' => [403]],
    'api/actualizar_ubicacion.php' => ['tipo' => 'API Protegida (Domi)', 'esperado' => [403]],
    'api/csrf_token.php' => ['tipo' => 'API Pública (CSRF)', 'esperado' => [200]],
    'api/dashboard_live.php' => ['tipo' => 'API Protegida (Admin)', 'esperado' => [403]],
    'api/guardar_calificacion.php' => ['tipo' => 'API Protegida (Cliente)', 'esperado' => [403]],
    'api/obtener_seguimiento.php' => ['tipo' => 'API Protegida', 'esperado' => [403]],

    // Controllers - Admin
    'controllers/admin/dashboard.php' => ['tipo' => 'Panel Admin', 'esperado' => [403]],
    'controllers/admin/empleados.php' => ['tipo' => 'Panel Admin', 'esperado' => [403]],
    'controllers/admin/factura.php' => ['tipo' => 'Panel Admin', 'esperado' => [403]],
    'controllers/admin/ingredientes.php' => ['tipo' => 'Panel Admin', 'esperado' => [403]],
    'controllers/admin/pedidos.php' => ['tipo' => 'Panel Admin', 'esperado' => [403]],
    'controllers/admin/productos.php' => ['tipo' => 'Panel Admin', 'esperado' => [403]],
    'controllers/admin/ver_comprobante.php' => ['tipo' => 'Panel Admin', 'esperado' => [403]],
    'controllers/admin/zonas.php' => ['tipo' => 'Panel Admin', 'esperado' => [403]],

    // Controllers - Cocina y Domicilio
    'controllers/cocinera/pedidos.php' => ['tipo' => 'Panel Cocina', 'esperado' => [403]],
    'controllers/domiciliario/rutas.php' => ['tipo' => 'Panel Domiciliario', 'esperado' => [403]],

    // Controllers - Cliente
    'controllers/cliente/catalogo.php' => ['tipo' => 'Público / Catálogo', 'esperado' => [200]],
    'controllers/cliente/carrito.php' => ['tipo' => 'Público / Carrito', 'esperado' => [200]],
    'controllers/cliente/checkout.php' => ['tipo' => 'Protegido Cliente', 'esperado' => [302]],
    'controllers/cliente/factura.php' => ['tipo' => 'Protegido Cliente', 'esperado' => [302]],
    'controllers/cliente/pedidos.php' => ['tipo' => 'Protegido Cliente', 'esperado' => [302]],
    'controllers/cliente/seguimiento.php' => ['tipo' => 'Protegido Cliente', 'esperado' => [302]],

    // Controllers - Auth
    'controllers/auth/login.php' => ['tipo' => 'Público Auth', 'esperado' => [200]],
    'controllers/auth/register.php' => ['tipo' => 'Público Auth', 'esperado' => [200]],
    'controllers/auth/forgot_password.php' => ['tipo' => 'Público Auth', 'esperado' => [200]],
    'controllers/auth/reset_password.php' => ['tipo' => 'Público Auth', 'esperado' => [200]],
    'controllers/auth/logout.php' => ['tipo' => 'Acción Auth', 'esperado' => [302]],

    // Views protegidas vía .htaccess (deben ser 403 Forbidden)
    'views/admin/dashboard.php' => ['tipo' => 'Vista interna (HTACCESS)', 'esperado' => [403]],
    'views/admin/pedidos.php' => ['tipo' => 'Vista interna (HTACCESS)', 'esperado' => [403]],
    'views/admin/ingredientes.php' => ['tipo' => 'Vista interna (HTACCESS)', 'esperado' => [403]],
    'views/admin/productos.php' => ['tipo' => 'Vista interna (HTACCESS)', 'esperado' => [403]],
    'views/cocinera/pedidos.php' => ['tipo' => 'Vista interna (HTACCESS)', 'esperado' => [403]],
    'views/domiciliario/rutas.php' => ['tipo' => 'Vista interna (HTACCESS)', 'esperado' => [403]],
    'views/cliente/catalogo.php' => ['tipo' => 'Vista interna (HTACCESS)', 'esperado' => [403]],
    'views/cliente/carrito.php' => ['tipo' => 'Vista interna (HTACCESS)', 'esperado' => [403]],
    'views/cliente/pedidos.php' => ['tipo' => 'Vista interna (HTACCESS)', 'esperado' => [403]],
    'views/cliente/seguimiento.php' => ['tipo' => 'Vista interna (HTACCESS)', 'esperado' => [403]],
    'views/layouts/admin_header.php' => ['tipo' => 'Layout interno (HTACCESS)', 'esperado' => [403]],
    'views/layouts/cliente_header.php' => ['tipo' => 'Layout interno (HTACCESS)', 'esperado' => [403]]
];

printf("%-40s | %-25s | %-8s | %-15s | %s\n", "Archivo / URL", "Propósito", "HTTP Code", "Esperado", "Resultado");
echo str_repeat('-', 120) . "\n";

foreach ($filesToTest as $file => $cfg) {
    $res = makeRequest($baseUrl . '/' . $file);
    $ok = in_array($res['code'], $cfg['esperado']);
    $statusText = $ok ? "✔ CORRECTO" : "❌ INSEGURO";
    $extra = ($res['code'] === 302 && !empty($res['location'])) ? " -> " . basename($res['location']) : "";
    
    printf("%-40s | %-25s | %-8s | %-15s | %s%s\n",
        $file,
        $cfg['tipo'],
        $res['code'],
        implode('/', $cfg['esperado']),
        $statusText,
        $extra
    );
}

echo "\n====================================================================================================================\n";
echo "PARTE (b): PRUEBAS DE ACCESO ENTRE USUARIOS (CONTROL IDOR Y ROLES)\n";
echo "====================================================================================================================\n";

// Helper para iniciar sesión vía formulario real y guardar cookies
function loginUser($email, $password, $cookieFile, $baseUrl): array {
    // 1. Obtener página de login para sacar CSRF
    $getRes = makeRequest($baseUrl . '/controllers/auth/login.php', ['cookie_file' => $cookieFile]);
    preg_match('/name="csrf_token"\s+value="([^"]+)"/', $getRes['body'], $m);
    $csrf = $m[1] ?? '';
    
    // 2. Enviar POST
    $postRes = makeRequest($baseUrl . '/controllers/auth/login.php', [
        'method' => 'POST',
        'cookie_file' => $cookieFile,
        'data' => http_build_query([
            'action' => 'login',
            'email' => $email,
            'password' => $password,
            'csrf_token' => $csrf
        ])
    ]);
    return ['csrf' => $csrf, 'login' => $postRes];
}

$tempCookieDir = __DIR__ . '/../storage';
$cookieClientA = $tempCookieDir . '/cookie_client_a.txt';
$cookieClientB = $tempCookieDir . '/cookie_client_b.txt';
$cookieDomiA = $tempCookieDir . '/cookie_domi_a.txt';
@unlink($cookieClientA); @unlink($cookieClientB); @unlink($cookieDomiA);

// Cliente A: juan@gmail.com (ID 13)
loginUser('juan@gmail.com', '123456', $cookieClientA, $baseUrl);
// Domiciliario: domi@gmail.com (ID 16)
loginUser('domi@gmail.com', '123456', $cookieDomiA, $baseUrl);

require_once __DIR__ . '/../config/database.php';
$db = getDatabaseConnection();

// Buscar un pedido que NO pertenezca a Juan (ID 13)
$orderOtherClient = $db->query("SELECT id_pedido, id_cliente FROM pedido WHERE id_cliente != 13 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$idPedAjeno = (int)$orderOtherClient['id_pedido'];
$ownerAjeno = (int)$orderOtherClient['id_cliente'];

// Buscar un pedido asignado a OTRO domiciliario distinto al ID 16 (o asignar temporalmente pedido a domiciliario 5)
$db->exec("INSERT INTO asignacion_domicilio (id_pedido, id_domiciliario, fecha_asignacion) VALUES ($idPedAjeno, 5, NOW()) ON DUPLICATE KEY UPDATE id_domiciliario = 5");

echo "Configuración de prueba cruzada:\n";
echo "  - Cliente autenticado: ID 13 (juan@gmail.com)\n";
echo "  - Pedido ajeno de prueba: Pedido #$idPedAjeno (Pertenece al Cliente ID $ownerAjeno)\n";
echo "  - Domiciliario autenticado: ID 16 (domi@gmail.com)\n";
echo "  - Pedido #$idPedAjeno asignado a Domiciliario ID: 5 (Distinto de 16)\n\n";

// 1. Cliente A intenta ver la factura del Pedido ajeno
$resFactura = makeRequest($baseUrl . "/controllers/cliente/factura.php?id=$idPedAjeno", ['cookie_file' => $cookieClientA]);
echo "1. Cliente A intenta ver FACTURA de Cliente B (Pedido #$idPedAjeno):\n";
echo "   - Código HTTP: {$resFactura['code']} (Esperado: 403)\n";
echo "   - Resultado: " . ($resFactura['code'] === 403 ? "✔ BLOQUEADO (403 Prohibido)" : "❌ VULNERABLE") . "\n\n";

// 2. Cliente A intenta ver el SEGUIMIENTO web de Pedido ajeno
$resSegWeb = makeRequest($baseUrl . "/controllers/cliente/seguimiento.php?id=$idPedAjeno", ['cookie_file' => $cookieClientA]);
echo "2. Cliente A intenta ver SEGUIMIENTO WEB de Cliente B (Pedido #$idPedAjeno):\n";
echo "   - Código HTTP: {$resSegWeb['code']} (Esperado: 403)\n";
echo "   - Resultado: " . ($resSegWeb['code'] === 403 ? "✔ BLOQUEADO (403 Prohibido)" : "❌ VULNERABLE") . "\n\n";

// 3. Cliente A intenta consultar la API de seguimiento / ubicación del Pedido ajeno
$resSegApi = makeRequest($baseUrl . "/api/obtener_seguimiento.php?id_pedido=$idPedAjeno", ['cookie_file' => $cookieClientA]);
echo "3. Cliente A intenta consultar API de seguimiento/ubicación de Pedido ajeno (#$idPedAjeno):\n";
echo "   - Código HTTP: {$resSegApi['code']} (Esperado: 403)\n";
echo "   - Respuesta JSON: " . trim($resSegApi['body']) . "\n";
echo "   - Resultado: " . ($resSegApi['code'] === 403 ? "✔ BLOQUEADO (403 Prohibido)" : "❌ VULNERABLE") . "\n\n";

// 4. Domiciliario (ID 16) intenta actualizar ubicación GPS de pedido no asignado (#$idPedAjeno, asignado al 5)
$resGps = makeRequest($baseUrl . "/api/actualizar_ubicacion.php", [
    'method' => 'POST',
    'cookie_file' => $cookieDomiA,
    'headers' => ['Content-Type: application/json'],
    'data' => json_encode(['id_pedido' => $idPedAjeno, 'lat' => 4.6097, 'lng' => -74.0817])
]);
echo "4. Domiciliario (ID 16) intenta enviar GPS para pedido asignado a otro domiciliario (#$idPedAjeno):\n";
echo "   - Código HTTP: {$resGps['code']} (Esperado: 403)\n";
echo "   - Respuesta JSON: " . trim($resGps['body']) . "\n";
echo "   - Resultado: " . ($resGps['code'] === 403 ? "✔ BLOQUEADO (403 Prohibido)" : "❌ VULNERABLE") . "\n\n";

// 5. Cliente A intenta abrir comprobante ajeno en controllers/admin/ver_comprobante.php
$resComp = makeRequest($baseUrl . "/controllers/admin/ver_comprobante.php?id_pedido=$idPedAjeno", ['cookie_file' => $cookieClientA]);
echo "5. Cliente A intenta acceder a comprobante ajeno vía ver_comprobante.php:\n";
echo "   - Código HTTP: {$resComp['code']} (Esperado: 403)\n";
echo "   - Resultado: " . ($resComp['code'] === 403 ? "✔ BLOQUEADO (403 Prohibido)" : "❌ VULNERABLE") . "\n\n";

// 6. Cliente A intenta abrir el panel administrativo (controllers/admin/dashboard.php)
$resDash = makeRequest($baseUrl . "/controllers/admin/dashboard.php", ['cookie_file' => $cookieClientA]);
echo "6. Cliente A intenta abrir panel de administración (controllers/admin/dashboard.php):\n";
echo "   - Código HTTP: {$resDash['code']} (Esperado: 403)\n";
echo "   - Resultado: " . ($resDash['code'] === 403 ? "✔ BLOQUEADO (403 Prohibido)" : "❌ VULNERABLE") . "\n\n";

@unlink($cookieClientA); @unlink($cookieClientB); @unlink($cookieDomiA);
echo "====================================================================================================================\n";
