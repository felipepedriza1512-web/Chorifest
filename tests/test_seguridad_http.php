<?php
echo "======================================================================\n";
echo "PRUEBAS DE SEGURIDAD HTTP REALES (403 Y CSRF EN ENDPOINTS NUEVOS)\n";
echo "======================================================================\n\n";

$baseUrl = 'http://localhost/Chorifest';
$cookieJarAnon = tempnam(sys_get_temp_dir(), 'cj_anon_');
$cookieJarCliente = tempnam(sys_get_temp_dir(), 'cj_cli_');
$cookieJarCocina = tempnam(sys_get_temp_dir(), 'cj_coc_');
$cookieJarDomi = tempnam(sys_get_temp_dir(), 'cj_dom_');
$cookieJarAdmin = tempnam(sys_get_temp_dir(), 'cj_adm_');

function httpReq(string $url, string $method = 'GET', ?array $postData = null, ?string $cookieFile = null): array {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // No seguir redirecciones para ver el código exacto
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($postData !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        }
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    curl_close($ch);

    return [
        'code' => $httpCode,
        'header' => $header,
        'body' => $body
    ];
}

function loginUser(string $email, string $password, string $cookieFile, string $baseUrl): bool {
    // 1. Obtener cookie de sesión y página de login
    $resGet = httpReq("$baseUrl/controllers/auth/login.php", 'GET', null, $cookieFile);
    
    // 2. Hacer POST de login
    $post = [
        'email' => $email,
        'password' => $password
    ];
    $resPost = httpReq("$baseUrl/controllers/auth/login.php", 'POST', $post, $cookieFile);
    return ($resPost['code'] === 302 || $resPost['code'] === 200);
}

// 1. Iniciar sesión para cada rol
echo "--- 1. AUTENTICACIÓN DE ROLES VIA HTTP ---\n";
echo "  Iniciando sesión Cliente (juan@gmail.com)... ";
$okCli = loginUser('juan@gmail.com', '123456', $cookieJarCliente, $baseUrl);
echo $okCli ? "OK\n" : "FALLÓ\n";

echo "  Iniciando sesión Cocinera (cocina@gmail.com)... ";
$okCoc = loginUser('cocina@gmail.com', '123456', $cookieJarCocina, $baseUrl);
echo $okCoc ? "OK\n" : "FALLÓ\n";

echo "  Iniciando sesión Domiciliario (domi@gmail.com)... ";
$okDom = loginUser('domi@gmail.com', '123456', $cookieJarDomi, $baseUrl);
echo $okDom ? "OK\n" : "FALLÓ\n";

echo "  Iniciando sesión Admin (admin@gmail.com)... ";
$okAdm = loginUser('admin@gmail.com', '123456', $cookieJarAdmin, $baseUrl);
echo $okAdm ? "OK\n\n" : "FALLÓ\n\n";

// Endpoints a evaluar
$endpoints = [
    'Gestión de Productos y Recetas' => "$baseUrl/controllers/admin/productos.php",
    'Inventario y Ajustes'            => "$baseUrl/controllers/admin/ingredientes.php",
    'Gestión de Pedidos y Reintegro'  => "$baseUrl/controllers/admin/pedidos.php"
];

// 2. Probar acceso NO AUTORIZADO (Sin sesión, Cliente, Cocinera, Domiciliario)
echo "--- 2. PRUEBAS DE ACCESO NO AUTORIZADO (DEBEN DEVOLVER HTTP 403) ---\n";

$rolesTest = [
    'Sin Sesión (Anónimo)' => $cookieJarAnon,
    'Cliente (Rol 1)'       => $cookieJarCliente,
    'Cocinera (Rol 3)'      => $cookieJarCocina,
    'Domiciliario (Rol 4)'  => $cookieJarDomi
];

$all403Passed = true;

foreach ($rolesTest as $rolNombre => $cj) {
    echo "Verificando acceso para: $rolNombre:\n";
    foreach ($endpoints as $nombreEp => $url) {
        $res = httpReq($url, 'GET', null, $cj);
        $status = $res['code'];
        $ok = ($status === 403);
        if (!$ok) $all403Passed = false;
        echo "  - $nombreEp -> HTTP $status " . ($ok ? "✔ [PASÓ 403]" : "❌ [FALLÓ - Esperado 403]") . "\n";
    }
}

// 3. Probar rechazo de POST sin CSRF en los endpoints (incluso con sesión de Admin)
echo "\n--- 3. PRUEBAS DE RECHAZO DE POST SIN CSRF (ADMIN LOGUEADO) ---\n";

$postTests = [
    'Crear Producto' => [
        'url' => "$baseUrl/controllers/admin/productos.php",
        'data' => ['action' => 'create', 'nombre_producto' => 'Hacker Prod', 'precio' => 1000]
    ],
    'Editar Receta' => [
        'url' => "$baseUrl/controllers/admin/productos.php",
        'data' => ['action' => 'save_receta', 'id_producto' => 1, 'nombre_receta' => 'Hack Receta']
    ],
    'Entrada de Inventario' => [
        'url' => "$baseUrl/controllers/admin/ingredientes.php",
        'data' => ['action' => 'entrada', 'id_insumo' => 1, 'cantidad' => 10, 'motivo' => 'Hack Entrada']
    ],
    'Ajuste de Inventario' => [
        'url' => "$baseUrl/controllers/admin/ingredientes.php",
        'data' => ['action' => 'ajuste', 'id_insumo' => 1, 'nuevo_stock' => 50, 'motivo' => 'Hack Ajuste']
    ],
    'Reintentar Descuento' => [
        'url' => "$baseUrl/controllers/admin/pedidos.php",
        'data' => ['action' => 'reintentar_descuento', 'id_pedido' => 1]
    ],
    'Reintegro en Cancelación' => [
        'url' => "$baseUrl/controllers/admin/pedidos.php",
        'data' => ['action' => 'rechazar_pedido', 'id_pedido' => 1, 'reintegrar_stock' => 1]
    ]
];

$allCsrfPassed = true;

foreach ($postTests as $testName => $cfg) {
    // POST sin 'csrf_token'
    $resPost = httpReq($cfg['url'], 'POST', $cfg['data'], $cookieJarAdmin);
    $status = $resPost['code'];
    $ok = ($status === 403);
    if (!$ok) $allCsrfPassed = false;
    echo "  - POST $testName (Sin CSRF) -> HTTP $status " . ($ok ? "✔ [PASÓ 403 RECHAZADO]" : "❌ [FALLÓ - Esperado 403]") . "\n";
}

echo "\n======================================================================\n";
if ($all403Passed && $allCsrfPassed) {
    echo "✔ TODAS LAS PRUEBAS DE SEGURIDAD HTTP Y CSRF PASARON AL 100% (HTTP 403 GARANTIZADO)\n";
} else {
    echo "❌ HUBO FALLAS EN LAS PRUEBAS DE SEGURIDAD HTTP\n";
}
echo "======================================================================\n";

// Limpiar archivos de cookies
@unlink($cookieJarAnon);
@unlink($cookieJarCliente);
@unlink($cookieJarCocina);
@unlink($cookieJarDomi);
@unlink($cookieJarAdmin);
