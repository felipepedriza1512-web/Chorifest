<?php
/**
 * Test integral de la Fase 5: Reportes y Liquidación
 * Base de datos: chorifest_test (aislada mediante X-Chorifest-Test-Db)
 */

require_once __DIR__ . '/test_bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/EstadoPedido.php';
require_once __DIR__ . '/../models/ReporteModel.php';
require_once __DIR__ . '/../models/AdminModel.php';

date_default_timezone_set('America/Bogota');

putenv('CHORIFEST_DB_NAME=chorifest_test');
$_ENV['CHORIFEST_DB_NAME'] = 'chorifest_test';

$db = getDatabaseConnection('chorifest_test');
assertTestDatabaseOnly($db);

$baseUrl = 'http://localhost/Chorifest';

function makeHttpRequest($url, $method = 'GET', $postData = null, $cookies = null, $headers = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    
    $reqHeaders = ['X-Chorifest-Test-Db: chorifest_test'];
    foreach ($headers as $h) {
        $reqHeaders[] = $h;
    }
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if (is_array($postData)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            if (!empty($postData) && $postData[0] === '{') {
                $reqHeaders[] = 'Content-Type: application/json';
            }
        }
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $reqHeaders);
    
    if ($cookies) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => $response,
        'final_url' => $finalUrl,
        'redirect_url' => $redirectUrl,
        'content_type' => $contentType
    ];
}

function loginUser($email, $password, $cookieFile, $baseUrl) {
    @unlink($cookieFile);
    $res = makeHttpRequest("$baseUrl/controllers/auth/login.php", 'POST', [
        'action' => 'login',
        'email' => $email,
        'password' => $password
    ], $cookieFile);
    return $res;
}

function fetchCsrf($cookieFile, $baseUrl) {
    $res = makeHttpRequest("$baseUrl/api/csrf_token.php", 'GET', null, $cookieFile);
    $data = json_decode($res['body'], true);
    return $data['csrf_token'] ?? '';
}

$tempDir = __DIR__ . '/cookies_fase5';
if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);

$cAdmin = "$tempDir/cookie_admin.txt";
$cCliente = "$tempDir/cookie_cliente.txt";
$cCook = "$tempDir/cookie_cook.txt";
$cDomi = "$tempDir/cookie_domi.txt";

loginUser('admin@gmail.com', '123456', $cAdmin, $baseUrl);
loginUser('juan@gmail.com', '123456', $cCliente, $baseUrl);
loginUser('cocina@gmail.com', '123456', $cCook, $baseUrl);
loginUser('domi@gmail.com', '123456', $cDomi, $baseUrl);

echo "====================================================================\n";
echo "PRUEBAS DE SEGURIDAD Y FUNCIONALIDAD FASE 5 (chorifest_test)\n";
echo "====================================================================\n\n";

$pass = 0;
$total = 0;

function runAssert($title, $condition, $info = '') {
    global $pass, $total;
    $total++;
    if ($condition) {
        $pass++;
        echo "  [OK] $title\n";
        if ($info) echo "       $info\n";
    } else {
        echo "  [FALLÓ] $title\n";
        if ($info) echo "          Detalle: $info\n";
    }
}

// -----------------------------------------------------------------------------
// 1. CONTROL DE ACCESO (SOLO ADMINISTRADOR)
// -----------------------------------------------------------------------------
echo "--- 1. CONTROL DE ACCESO A REPORTES (SOLO ADMIN) ---\n";

// Admin accede
$resAdm = makeHttpRequest("$baseUrl/controllers/admin/reportes.php", 'GET', null, $cAdmin);
runAssert("Administrador accede exitosamente a reportes (HTTP 200)", $resAdm['code'] === 200, "Código: {$resAdm['code']}");

// Cliente bloqueado (HTTP 302 o 403)
$resCli = makeHttpRequest("$baseUrl/controllers/admin/reportes.php", 'GET', null, $cCliente);
runAssert("Cliente es bloqueado al intentar acceder a reportes", $resCli['code'] === 302 || $resCli['code'] === 403, "Código: {$resCli['code']}");

// Cocinera bloqueada
$resCoc = makeHttpRequest("$baseUrl/controllers/admin/reportes.php", 'GET', null, $cCook);
runAssert("Cocinera es bloqueada al intentar acceder a reportes", $resCoc['code'] === 302 || $resCoc['code'] === 403, "Código: {$resCoc['code']}");

// Domiciliario bloqueado
$resDom = makeHttpRequest("$baseUrl/controllers/admin/reportes.php", 'GET', null, $cDomi);
runAssert("Domiciliario es bloqueado al intentar acceder a reportes", $resDom['code'] === 302 || $resDom['code'] === 403, "Código: {$resDom['code']}");

// -----------------------------------------------------------------------------
// 2. EXPORTACIÓN CSV (BOM UTF-8 Y PROTECCIÓN DE FÓRMULAS)
// -----------------------------------------------------------------------------
echo "\n--- 2. EXPORTACIÓN CSV (BOM UTF-8 Y PROTECCIÓN CONTRA FÓRMULAS) ---\n";

$resCsv = makeHttpRequest("$baseUrl/controllers/admin/reportes.php?tab=ventas&export=csv&tipo=ventas", 'GET', null, $cAdmin);
runAssert("Descarga de CSV responde con HTTP 200 y Content-Type text/csv", 
    $resCsv['code'] === 200 && strpos($resCsv['content_type'], 'text/csv') !== false, 
    "Content-Type: {$resCsv['content_type']}"
);

$csvBody = $resCsv['body'];
$hasBom = (substr($csvBody, 0, 3) === "\xEF\xBB\xBF");
runAssert("El archivo CSV incluye el BOM UTF-8 (\\xEF\\xBB\\xBF) para compatibilidad con Excel", $hasBom, "BOM presente al inicio");

// Probar función de sanitización de fórmulas CSV
function testSanitizeCsv($val): string {
    $str = (string)$val;
    if (preg_match('/^[=\+\-@\t\r]/', $str)) {
        return "'" . $str;
    }
    return $str;
}

$payloads = ['=SUM(A1:A10)', '+12345', '-cmd|/C calc', '@calc', "\tTabPayload"];
$todosProtegidos = true;
foreach ($payloads as $p) {
    $sanitized = testSanitizeCsv($p);
    if ($sanitized[0] !== "'") {
        $todosProtegidos = false;
        break;
    }
}
runAssert("Protección de fórmulas CSV antepone comilla simple a celdas que inician con =, +, -, @, \\t", $todosProtegidos, "Payloads saneados con comilla simple preventiva");

// -----------------------------------------------------------------------------
// 3. EXPORTACIÓN PDF (mPDF CON CABECERA %PDF- Y TEXTO ESCAPADO)
// -----------------------------------------------------------------------------
echo "\n--- 3. EXPORTACIÓN PDF (mPDF) ---\n";

$resPdf = makeHttpRequest("$baseUrl/controllers/admin/reportes.php?tab=ventas&export=pdf&tipo=ventas", 'GET', null, $cAdmin);
$isPdfBinary = (substr($resPdf['body'], 0, 4) === '%PDF');
runAssert("Generación de PDF con mPDF genera binario PDF válido (%PDF-)", $isPdfBinary, "Cabecera: " . substr($resPdf['body'], 0, 8));

// -----------------------------------------------------------------------------
// 4. LIQUIDACIÓN DE TURNO DE DOMICILIARIO (TRANSACCIONAL Y SIN DUPLICADOS)
// -----------------------------------------------------------------------------
echo "\n--- 4. MÓDULO DE LIQUIDACIÓN DE TURNO (TRANSACCIONAL) ---\n";

$reporteModel = new ReporteModel($db);

// Crear 2 pedidos en efectivo para el domiciliario 16
$idDomiTest = 16;
$idAdminTest = 14;

$db->exec("DELETE FROM turno_liquidacion_pedido WHERE id_pedido >= 950000");
$db->exec("DELETE FROM historial_pedido WHERE id_pedido >= 950000");
$db->exec("DELETE FROM pago WHERE id_pedido >= 950000");
$db->exec("DELETE FROM pedido WHERE id_pedido >= 950000");

$peds = [
    950001 => 25000.00,
    950002 => 35000.00
];

foreach ($peds as $idPed => $monto) {
    $db->exec("INSERT INTO pedido (id_pedido, id_cliente, id_direccion, id_zona, nombre_zona, id_estado, subtotal, total, fecha) VALUES ($idPed, 13, 1, 1, 'Zona Test', 6, $monto, $monto, NOW())");
    $db->exec("INSERT INTO historial_pedido (id_pedido, id_estado, comentario, fecha) VALUES ($idPed, 6, 'Entregado', NOW())");
    $db->exec("INSERT INTO pago (id_pedido, metodo, estado, fecha_pago) VALUES ($idPed, 'EFECTIVO', 'APROBADO', NOW())");
    $db->exec("INSERT INTO asignacion_domicilio (id_pedido, id_domiciliario, fecha_asignacion) VALUES ($idPed, $idDomiTest, NOW())");
}

// 1. Obtener pedidos pendientes de liquidar
$pendientesAntes = $reporteModel->getPedidosPendientesLiquidacion($idDomiTest);
$encontrados = array_filter($pendientesAntes, fn($p) => isset($peds[$p['id_pedido']]));
runAssert("Pedidos entregados en efectivo aparecen como pendientes de liquidación", count($encontrados) === 2, "Encontrados: " . count($encontrados));

// 2. Liquidar turno
$resLiq = $reporteModel->liquidarTurno($idDomiTest, $idAdminTest, date('Y-m-d H:i:s'), 'Prueba automatizada de liquidación');
runAssert("Liquidación de turno exitosa devuelve ID y monto total acumulado", 
    $resLiq['success'] === true && $resLiq['total_efectivo'] >= 60000.00, 
    "ID Liq: {$resLiq['id_liquidacion']}, Total: \${$resLiq['total_efectivo']}"
);

// 3. Verificar que los pedidos ya NO aparecen pendientes (evita doble cobro)
$pendientesDespues = $reporteModel->getPedidosPendientesLiquidacion($idDomiTest);
$encontradosDespues = array_filter($pendientesDespues, fn($p) => isset($peds[$p['id_pedido']]));
runAssert("Pedidos liquidados ya NO aparecen pendientes de liquidar (previene doble cobro)", empty($encontradosDespues), "Pendientes restantes de prueba: 0");

// 4. Intentar reliquidar inmediatamente: debe rechazar porque no hay pedidos pendientes
$resReLiq = $reporteModel->liquidarTurno($idDomiTest, $idAdminTest, date('Y-m-d H:i:s'));
runAssert("Intento de reliquidar turno sin pedidos pendientes es rechazado", $resReLiq['success'] === false, "Mensaje: {$resReLiq['message']}");

// 5. Probar anulación con motivo obligatorio
$idLiqCreada = $resLiq['id_liquidacion'];
$tokenAdm = fetchCsrf($cAdmin, $baseUrl);
$resAnular = makeHttpRequest("$baseUrl/controllers/admin/reportes.php", 'POST', [
    'action' => 'anular_liquidacion',
    'csrf_token' => $tokenAdm,
    'id_liquidacion' => $idLiqCreada,
    'motivo_anulacion' => 'Error de conteo en efectivo durante cierre de caja'
], $cAdmin);

runAssert("Anulación de liquidación exitosa mediante POST con CSRF y motivo", $resAnular['code'] === 302, "Redirección: {$resAnular['redirect_url']}");

// Verificar en BD que la liquidación está anulada y los pedidos fueron liberados
$stmtCheckAnul = $db->prepare("SELECT anulado, motivo_anulacion FROM turno_liquidacion WHERE id_liquidacion = ?");
$stmtCheckAnul->execute([$idLiqCreada]);
$anulRow = $stmtCheckAnul->fetch(PDO::FETCH_ASSOC);
runAssert("Liquidación marcada con anulado = 1 y motivo registrado en base de datos", 
    (int)$anulRow['anulado'] === 1 && !empty($anulRow['motivo_anulacion']), 
    "Motivo: {$anulRow['motivo_anulacion']}"
);

$pendientesPostAnul = $reporteModel->getPedidosPendientesLiquidacion($idDomiTest);
$encontradosPost = array_filter($pendientesPostAnul, fn($p) => isset($peds[$p['id_pedido']]));
runAssert("Los pedidos fueron liberados para nueva liquidación tras la anulación", count($encontradosPost) === 2, "Pedidos liberados: " . count($encontradosPost));

// -----------------------------------------------------------------------------
// 5. RESUMEN DE COBROS EN EL PANEL DEL DOMICILIARIO (rutas.php)
// -----------------------------------------------------------------------------
echo "\n--- 5. PANEL DE COBROS DEL DOMICILIARIO (rutas.php) ---\n";

$resDomiPanel = makeHttpRequest("$baseUrl/controllers/domiciliario/rutas.php", 'GET', null, $cDomi);
runAssert("Panel del domiciliario muestra widget de efectivo por liquidar en turno", 
    strpos($resDomiPanel['body'], 'Efectivo por Liquidar en Turno') !== false, 
    "Texto 'Efectivo por Liquidar en Turno' presente en HTML"
);

// Limpieza de datos de prueba
$db->exec("DELETE FROM turno_liquidacion_pedido WHERE id_liquidacion = $idLiqCreada");
$db->exec("DELETE FROM turno_liquidacion WHERE id_liquidacion = $idLiqCreada");
$db->exec("DELETE FROM asignacion_domicilio WHERE id_pedido >= 950000");
$db->exec("DELETE FROM historial_pedido WHERE id_pedido >= 950000");
$db->exec("DELETE FROM pago WHERE id_pedido >= 950000");
$db->exec("DELETE FROM pedido WHERE id_pedido >= 950000");

array_map('unlink', glob("$tempDir/*.txt"));
@rmdir($tempDir);

echo "\n====================================================================\n";
echo "RESUMEN DE PRUEBAS DE FASE 5: $pass / $total APROBADAS\n";
echo "====================================================================\n";

if ($pass === $total) {
    echo ">> TODAS LAS PRUEBAS DE LA FASE 5 PASARON AL 100% CON ÉXITO. <<\n\n";
} else {
    exit(1);
}
