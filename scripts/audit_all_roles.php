<?php
/**
 * AUDITORÍA FINAL COMPLETA: TODOS LOS ROLES Y ENDPOINTS
 */

$baseUrl = 'http://localhost/Chorifest';
$rootDir = realpath(__DIR__ . '/..');

echo "====================================================================================================\n";
echo "   CHORIFEST - AUDITORÍA EXHAUSTIVA DE ESTADO Y ERRORES PARA TODOS LOS ROLES\n";
echo "====================================================================================================\n\n";

// 1. CHEQUEO DE SINTAXIS PHP EN TODOS LOS ARCHIVOS
echo "--- 1. ANÁLISIS DE SINTAXIS PHP (LINTING) ---\n";
$dirsToScan = ['controllers', 'models', 'views', 'api', 'config'];
$allPhpFiles = [];

foreach ($dirsToScan as $d) {
    $dirPath = "$rootDir/$d";
    if (is_dir($dirPath)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirPath));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $allPhpFiles[] = $file->getPathname();
            }
        }
    }
}

$syntaxErrors = 0;
foreach ($allPhpFiles as $phpFile) {
    $cmd = "php -l " . escapeshellarg($phpFile) . " 2>&1";
    $output = shell_exec($cmd);
    if (strpos($output, 'No syntax errors detected') === false) {
        echo "❌ ERROR DE SINTAXIS en $phpFile:\n$output\n";
        $syntaxErrors++;
    }
}

if ($syntaxErrors === 0) {
    echo "✔ Se analizaron " . count($allPhpFiles) . " archivos PHP. ¡CERO errores de sintaxis!\n\n";
} else {
    echo "❌ Se encontraron $syntaxErrors errores de sintaxis.\n\n";
}

// 2. FUNCIÓN DE NAVEGACIÓN Y REVISIÓN HTTP
function httpGet($url, $cookieFile) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    return ['code' => $code, 'url' => $finalUrl, 'body' => $body];
}

function checkErrorsInBody($body) {
    $errorsFound = [];
    if (preg_match('/<b>Fatal error<\/b>:(.*?)<br/i', $body, $m)) $errorsFound[] = "Fatal Error: " . strip_tags($m[1]);
    if (preg_match('/<b>Parse error<\/b>:(.*?)<br/i', $body, $m)) $errorsFound[] = "Parse Error: " . strip_tags($m[1]);
    if (preg_match('/Fatal error: (.*?)\n/i', $body, $m)) $errorsFound[] = "Fatal: " . trim($m[1]);
    if (preg_match('/<b>Warning<\/b>:(.*?)<br/i', $body, $m)) $errorsFound[] = "Warning: " . strip_tags($m[1]);
    if (preg_match('/Warning: (.*?)\n/i', $body, $m)) $errorsFound[] = "Warning: " . trim($m[1]);
    if (strpos($body, 'Uncaught PDOException') !== false) $errorsFound[] = "Uncaught PDOException";
    if (strpos($body, '403 Forbidden - Token CSRF') !== false) $errorsFound[] = "403 CSRF Error";
    return $errorsFound;
}

function auditRole($roleName, $email, $password, $routes, $baseUrl) {
    echo "----------------------------------------------------------------------------------------------------\n";
    echo "ROL: " . strtoupper($roleName) . " ($email)\n";
    echo "----------------------------------------------------------------------------------------------------\n";

    $cookieFile = __DIR__ . "/cookie_audit_" . preg_replace('/[^a-z0-9]/', '_', $email) . ".txt";
    if (file_exists($cookieFile)) @unlink($cookieFile);

    // Login
    $ch = curl_init("$baseUrl/controllers/auth/login.php");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'action' => 'login',
        'email' => $email,
        'password' => $password
    ]));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $loginRes = curl_exec($ch);
    $loginCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $loginUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);

    printf("  %-45s -> HTTP %-3d | Destino: %s\n", "Inicio de sesión", $loginCode, basename($loginUrl));

    $allPassed = true;
    foreach ($routes as $label => $path) {
        $fullUrl = "$baseUrl/$path";
        $res = httpGet($fullUrl, $cookieFile);
        $errs = checkErrorsInBody($res['body']);

        $statusStr = "✔ OK";
        if ($res['code'] !== 200 || !empty($errs)) {
            $allPassed = false;
            $statusStr = "❌ ERROR: " . (!empty($errs) ? implode(" | ", $errs) : "HTTP " . $res['code']);
        }

        printf("  %-45s -> HTTP %-3d | %s\n", $label, $res['code'], $statusStr);
    }

    @unlink($cookieFile);
    echo $allPassed ? "  ✔ Todas las pantallas de este rol cargan limpiamente al 100%.\n\n" : "  ❌ Hay alertas pendientes para este rol.\n\n";
    return $allPassed;
}

// 3. AUDITAR CADA ROL
$results = [];

// CLIENTE
$rutasCliente = [
    'Catálogo de Productos' => 'controllers/cliente/catalogo.php',
    'Vista de Carrito' => 'controllers/cliente/carrito.php',
    'Pantalla de Checkout' => 'controllers/cliente/checkout.php',
    'Historial Mis Pedidos' => 'controllers/cliente/pedidos.php',
    'Seguimiento en Vivo (Pedido #54)' => 'controllers/cliente/seguimiento.php?id=54',
    'API Obtener Seguimiento (JSON)' => 'api/obtener_seguimiento.php?id=54'
];
$results['cliente'] = auditRole('Cliente', 'juan@gmail.com', '123456', $rutasCliente, $baseUrl);

// COCINERA
$rutasCocinera = [
    'Tablero de Cocina (KDS)' => 'controllers/cocinera/pedidos.php'
];
$results['cocinera'] = auditRole('Cocinera', 'cocina@gmail.com', '123456', $rutasCocinera, $baseUrl);

// DOMICILIARIO
$rutasDomiciliario = [
    'Panel de Rutas y Entregas' => 'controllers/domiciliario/rutas.php'
];
$results['domiciliario'] = auditRole('Domiciliario', 'domi@gmail.com', '123456', $rutasDomiciliario, $baseUrl);

// ADMINISTRADOR
$rutasAdmin = [
    'Dashboard General y Métricas' => 'controllers/admin/dashboard.php',
    'Flujo de Gestión de Pedidos' => 'controllers/admin/pedidos.php',
    'Catálogo e Inventario Productos' => 'controllers/admin/productos.php',
    'Zonas de Cobertura y Polígonos' => 'controllers/admin/zonas.php',
    'Gestión de Personal y Turnos' => 'controllers/admin/empleados.php',
    'Ingredientes y Recetas' => 'controllers/admin/ingredientes.php',
    'Reportes: Ventas' => 'controllers/admin/reportes.php?tipo=ventas',
    'Reportes: Categorías' => 'controllers/admin/reportes.php?tipo=categorias',
    'Reportes: Calificaciones' => 'controllers/admin/reportes.php?tipo=calificaciones',
    'Reportes: Domiciliarios' => 'controllers/admin/reportes.php?tipo=domiciliarios',
    'Reportes: Clientes' => 'controllers/admin/reportes.php?tipo=clientes'
];
$results['admin'] = auditRole('Administrador', 'admin@gmail.com', '123456', $rutasAdmin, $baseUrl);

echo "====================================================================================================\n";
echo "RESUMEN DE AUDITORÍA GLOBAL:\n";
foreach ($results as $role => $ok) {
    echo " - " . ucfirst($role) . ": " . ($ok ? "✔ 100% OPERATIVO SIN ERRORES" : "❌ REQUIERE ATENCIÓN") . "\n";
}
echo "====================================================================================================\n";
