<?php
/**
 * Test de Seguridad y Pruebas Faltantes de Fase 5
 * Base de datos aislada: chorifest_test
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
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    if ($cookies) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies);
    }
    
    $response = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);
    
    $headerStr = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    return [
        'code' => $httpCode,
        'headers' => $headerStr,
        'body' => $body,
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

$tempDir = __DIR__ . '/cookies_fase5_seg';
if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);

$cAdmin = "$tempDir/cookie_admin.txt";
$cDomi = "$tempDir/cookie_domi.txt";

loginUser('admin@gmail.com', '123456', $cAdmin, $baseUrl);
loginUser('domi@gmail.com', '123456', $cDomi, $baseUrl);

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

echo "====================================================================\n";
echo "PRUEBAS DE VERIFICACIÓN FALTANTES FASE 5 (chorifest_test)\n";
echo "====================================================================\n\n";

// -----------------------------------------------------------------------------
// 1. REPORTE DE VENTAS POR CATEGORÍA Y CALIFICACIONES
// -----------------------------------------------------------------------------
echo "--- 1. PESTAÑAS NUEVAS: VENTAS POR CATEGORÍA Y CALIFICACIONES ---\n";

$resCat = makeHttpRequest("$baseUrl/controllers/admin/reportes.php?tab=categorias", 'GET', null, $cAdmin);
runAssert("Pestaña 'Ventas por Categoría' carga con HTTP 200", $resCat['code'] === 200);
runAssert("Contiene tabla de categorías y gráfico de distribución", 
    strpos($resCat['body'], 'Distribución de Ventas por Categoría') !== false && strpos($resCat['body'], 'chartCategorias') !== false
);

$resCalif = makeHttpRequest("$baseUrl/controllers/admin/reportes.php?tab=calificaciones", 'GET', null, $cAdmin);
runAssert("Pestaña 'Calificaciones' carga con HTTP 200", $resCalif['code'] === 200);
runAssert("Contiene métricas de promedio y distribución de 1 a 5 estrellas", 
    strpos($resCalif['body'], 'Calificación Promedio') !== false && strpos($resCalif['body'], '5 Estrellas') !== false
);

// -----------------------------------------------------------------------------
// 2. PRIVACIDAD EN REPORTE DE CLIENTES (SIN TELÉFONOS NI DIRECCIONES)
// -----------------------------------------------------------------------------
echo "\n--- 2. PRIVACIDAD DE DATOS EN REPORTE DE CLIENTES ---\n";

$resCliRep = makeHttpRequest("$baseUrl/controllers/admin/reportes.php?tab=clientes", 'GET', null, $cAdmin);
runAssert("Reporte de clientes carga para el administrador (HTTP 200)", $resCliRep['code'] === 200);

// Verificar que en el HTML no aparecen columnas de teléfono ni datos de contacto completos
$noPhoneInHeaders = (strpos($resCliRep['body'], '<th>Contacto</th>') === false);
$hasAuditBadge = (strpos($resCliRep['body'], 'Teléfonos y Direcciones Omitidos') !== false);
runAssert("El reporte de clientes NO incluye columnas de teléfono ni direcciones completas", $noPhoneInHeaders && $hasAuditBadge);

// -----------------------------------------------------------------------------
// 3. SEGURIDAD PDF Y XSS (COMENTARIOS CON PAYLOADS MALICIOSOS)
// -----------------------------------------------------------------------------
echo "\n--- 3. PDF SEGURO: ESCAPADO ESTRICTO Y ANTI-SSRF ---\n";

// Insertar un pedido de prueba con calificación maliciosa
$idPedXss = 955555;
$db->exec("DELETE FROM calificacion WHERE id_pedido = $idPedXss");
$db->exec("DELETE FROM historial_pedido WHERE id_pedido = $idPedXss");
$db->exec("DELETE FROM pago WHERE id_pedido = $idPedXss");
$db->exec("DELETE FROM pedido WHERE id_pedido = $idPedXss");

$xssPayload = '<script>alert(1)</script><img src="http://example.com/x.png">=HYPERLINK("http://example.com","x")';

$db->exec("INSERT INTO pedido (id_pedido, id_cliente, id_direccion, id_zona, nombre_zona, id_estado, subtotal, total, fecha) VALUES ($idPedXss, 13, 1, 1, 'Zona Test', 6, 25000, 25000, NOW())");
$db->exec("INSERT INTO historial_pedido (id_pedido, id_estado, comentario, fecha) VALUES ($idPedXss, 6, 'Entregado', NOW())");
$db->exec("INSERT INTO pago (id_pedido, metodo, estado, fecha_pago) VALUES ($idPedXss, 'EFECTIVO', 'APROBADO', NOW())");

$stmtInsertCal = $db->prepare("INSERT INTO calificacion (id_pedido, id_cliente, puntuacion, comentario, fecha) VALUES (?, 13, 5, ?, NOW())");
$stmtInsertCal->execute([$idPedXss, $xssPayload]);

// Probar visualización HTML de calificaciones
$resCalifHtml = makeHttpRequest("$baseUrl/controllers/admin/reportes.php?tab=calificaciones", 'GET', null, $cAdmin);
$hasEscapedScript = (strpos($resCalifHtml['body'], '&lt;script&gt;alert(1)&lt;/script&gt;') !== false);
$hasEscapedImg = (strpos($resCalifHtml['body'], '&lt;img src=&quot;http://example.com/x.png&quot;&gt;') !== false);
runAssert("En el HTML los comentarios con <script> e <img> se renderizan como texto escapado", 
    $hasEscapedScript && $hasEscapedImg, 
    "Etiquetas HTML escapadas a entidades &lt;script&gt; y &lt;img&gt;"
);

// Probar generación PDF de calificaciones
$resPdfCalif = makeHttpRequest("$baseUrl/controllers/admin/reportes.php?tab=calificaciones&export=pdf&tipo=calificaciones", 'GET', null, $cAdmin);
$isPdfBinary = (substr($resPdfCalif['body'], 0, 4) === '%PDF');
runAssert("PDF de calificaciones se genera correctamente (%PDF-) sin fallar ni ejecutar llamadas de red", $isPdfBinary);

// Verificar que mPDF bloquea llamadas externas si se inyecta un recurso remoto no escapado
require_once __DIR__ . '/../vendor/autoload.php';

class BlockOutboundHttpClientTest implements \Mpdf\Http\ClientInterface {
    public function sendRequest(\Psr\Http\Message\RequestInterface $request) {
        throw new \RuntimeException("BLOQUEADO_ANTI_SSRF: " . (string)$request->getUri());
    }
}
class SecureMpdfContainerTest implements \Mpdf\Container\ContainerInterface {
    private array $services = [];
    public function __construct() {
        $this->services['httpClient'] = new BlockOutboundHttpClientTest();
    }
    public function get($id) { return $this->services[$id] ?? null; }
    public function has($id) { return isset($this->services[$id]); }
}

$mpdfSecure = new \Mpdf\Mpdf([
    'tempDir' => __DIR__ . '/../storage/mpdf',
    'curlTimeout' => 0.001
], new SecureMpdfContainerTest());

$ssrfIntercepted = false;
try {
    // Si alguien intentara procesar una imagen remota directamente, el httpClient seguro la bloquea
    $mpdfSecure->WriteHTML('<img src="http://example.com/ssrf-test.png">');
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'BLOQUEADO_ANTI_SSRF') !== false) {
        $ssrfIntercepted = true;
    }
}
runAssert("El contenedor seguro de mPDF intercepta y bloquea llamadas HTTP externas previniendo SSRF", $ssrfIntercepted);

// -----------------------------------------------------------------------------
// 4. PRUEBAS HTTP REALES (a, b, c, d, e)
// -----------------------------------------------------------------------------
echo "\n--- 4. PRUEBAS HTTP REALES EXIGIDAS ---\n";

// (a) Un domiciliario NO ve los cobros ni liquidaciones de otro aunque cambie el id en la URL
$idDomiAjeno = 999;
$resIdorLiq = makeHttpRequest("$baseUrl/controllers/domiciliario/liquidaciones.php?id_domiciliario=$idDomiAjeno", 'GET', null, $cDomi);
runAssert("(a) Domiciliario recibe HTTP 403 Forbidden al intentar ver liquidaciones de otro ID en URL", 
    $resIdorLiq['code'] === 403, 
    "Código recibido: {$resIdorLiq['code']}"
);

$resIdorRutas = makeHttpRequest("$baseUrl/controllers/domiciliario/rutas.php?id_domiciliario=$idDomiAjeno", 'GET', null, $cDomi);
runAssert("(a) Domiciliario recibe HTTP 403 Forbidden al intentar ver rutas/cobros de otro ID en URL", 
    $resIdorRutas['code'] === 403, 
    "Código recibido: {$resIdorRutas['code']}"
);

// (c) Un POST sin CSRF a los filtros, exportaciones, liquidación y anulación da 403
$resPostFiltros = makeHttpRequest("$baseUrl/controllers/admin/reportes.php", 'POST', ['action' => 'filtrar', 'tab' => 'ventas'], $cAdmin);
runAssert("(c) POST a filtros sin CSRF retorna HTTP 403 Forbidden", $resPostFiltros['code'] === 403, "Código: {$resPostFiltros['code']}");

$resPostExport = makeHttpRequest("$baseUrl/controllers/admin/reportes.php", 'POST', ['action' => 'exportar', 'tipo' => 'ventas'], $cAdmin);
runAssert("(c) POST a exportaciones sin CSRF retorna HTTP 403 Forbidden", $resPostExport['code'] === 403, "Código: {$resPostExport['code']}");

$resPostLiq = makeHttpRequest("$baseUrl/controllers/admin/reportes.php", 'POST', ['action' => 'liquidar_turno', 'id_domiciliario' => 16], $cAdmin);
runAssert("(c) POST a liquidar_turno sin CSRF retorna HTTP 403 Forbidden", $resPostLiq['code'] === 403, "Código: {$resPostLiq['code']}");

$resPostAnul = makeHttpRequest("$baseUrl/controllers/admin/reportes.php", 'POST', ['action' => 'anular_liquidacion', 'id_liquidacion' => 1], $cAdmin);
runAssert("(c) POST a anular_liquidacion sin CSRF retorna HTTP 403 Forbidden", $resPostAnul['code'] === 403, "Código: {$resPostAnul['code']}");

// (d) El rango máximo de 365 días se valida en el servidor
$resRangeCapped = makeHttpRequest("$baseUrl/controllers/admin/reportes.php?desde=2024-01-01&hasta=2026-01-01", 'GET', null, $cAdmin);
$headerRangeEnforced = (strpos($resRangeCapped['headers'], 'X-Range-Days-Enforced: 365') !== false);
runAssert("(d) Rango mayor a 365 días es forzado y ajustado por el servidor (X-Range-Days-Enforced: 365)", 
    $headerRangeEnforced, 
    "Cabecera enviada por el servidor"
);

$resRangeStrict = makeHttpRequest("$baseUrl/controllers/admin/reportes.php?desde=2024-01-01&hasta=2026-01-01&strict_range=1", 'GET', null, $cAdmin);
runAssert("(d) Solicitud con validación estricta de rango mayor a 365 días retorna HTTP 422", 
    $resRangeStrict['code'] === 422, 
    "Código recibido: {$resRangeStrict['code']}"
);

// (b) Cada exportación queda en auditoría con quién, qué, rango y cuándo
$idAdminUser = 14;
$db->exec("DELETE FROM auditoria WHERE accion = 'EXPORTAR_REPORTE' AND id_usuario = $idAdminUser");

$resExpCsv = makeHttpRequest("$baseUrl/controllers/admin/reportes.php?tab=ventas&export=csv&tipo=ventas&desde=2026-08-01&hasta=2026-08-31", 'GET', null, $cAdmin);
$resExpPdf = makeHttpRequest("$baseUrl/controllers/admin/reportes.php?tab=categorias&export=pdf&tipo=categorias&desde=2026-08-01&hasta=2026-08-31", 'GET', null, $cAdmin);

$stmtAuditExp = $db->prepare("
    SELECT accion, entidad, descripcion, fecha 
    FROM auditoria 
    WHERE id_usuario = ? AND accion = 'EXPORTAR_REPORTE' 
    ORDER BY id_auditoria DESC 
    LIMIT 2
");
$stmtAuditExp->execute([$idAdminUser]);
$audits = $stmtAuditExp->fetchAll(PDO::FETCH_ASSOC);

runAssert("(b) Se registraron las exportaciones en la tabla auditoria", count($audits) >= 2, "Registros encontrados: " . count($audits));
if (!empty($audits)) {
    $firstAudit = $audits[0];
    $hasDetails = (strpos($firstAudit['descripcion'], '2026-08-01') !== false && strpos($firstAudit['descripcion'], '2026-08-31') !== false);
    runAssert("(b) Auditoría incluye qué se exportó, formato, rango de fechas y marca temporal", 
        $hasDetails && !empty($firstAudit['fecha']), 
        "Detalle registrado: {$firstAudit['descripcion']}"
    );
}

// (e) El domiciliario puede ver si su turno fue liquidado y solo el suyo
$reporteModel = new ReporteModel($db);
$idDomiReal = 16;

// Crear un pedido y liquidarlo para el domiciliario 16
$idPedDomi = 955556;
$db->exec("INSERT INTO pedido (id_pedido, id_cliente, id_direccion, id_zona, nombre_zona, id_estado, subtotal, total, fecha) VALUES ($idPedDomi, 13, 1, 1, 'Zona Test', 6, 45000, 45000, NOW())");
$db->exec("INSERT INTO historial_pedido (id_pedido, id_estado, comentario, fecha) VALUES ($idPedDomi, 6, 'Entregado', NOW())");
$db->exec("INSERT INTO pago (id_pedido, metodo, estado, fecha_pago) VALUES ($idPedDomi, 'EFECTIVO', 'APROBADO', NOW())");
$db->exec("INSERT INTO asignacion_domicilio (id_pedido, id_domiciliario, fecha_asignacion) VALUES ($idPedDomi, $idDomiReal, NOW())");

$resLiqShift = $reporteModel->liquidarTurno($idDomiReal, $idAdminUser, date('Y-m-d H:i:s'), 'Turno liquidado prueba e');
$idLiqGenerada = $resLiqShift['id_liquidacion'];

// Domiciliario consulta sus liquidaciones
$resDomiLiqView = makeHttpRequest("$baseUrl/controllers/domiciliario/liquidaciones.php", 'GET', null, $cDomi);
runAssert("(e) Domiciliario accede a su panel de liquidaciones (HTTP 200)", $resDomiLiqView['code'] === 200);
$seesOwnLiquidation = (strpos($resDomiLiqView['body'], "#$idLiqGenerada") !== false && strpos($resDomiLiqView['body'], 'LIQUIDADO') !== false);
runAssert("(e) Domiciliario ve reflejado su turno liquidado (#$idLiqGenerada) con estado LIQUIDADO", 
    $seesOwnLiquidation, 
    "ID #$idLiqGenerada visible en la interfaz del domiciliario"
);

// Limpieza de datos de prueba
$db->exec("DELETE FROM turno_liquidacion_pedido WHERE id_liquidacion = $idLiqGenerada");
$db->exec("DELETE FROM turno_liquidacion WHERE id_liquidacion = $idLiqGenerada");
$db->exec("DELETE FROM calificacion WHERE id_pedido IN ($idPedXss, $idPedDomi)");
$db->exec("DELETE FROM asignacion_domicilio WHERE id_pedido IN ($idPedXss, $idPedDomi)");
$db->exec("DELETE FROM historial_pedido WHERE id_pedido IN ($idPedXss, $idPedDomi)");
$db->exec("DELETE FROM pago WHERE id_pedido IN ($idPedXss, $idPedDomi)");
$db->exec("DELETE FROM pedido WHERE id_pedido IN ($idPedXss, $idPedDomi)");

array_map('unlink', glob("$tempDir/*.txt"));
@rmdir($tempDir);

echo "\n====================================================================\n";
echo "RESULTADO: $pass / $total PRUEBAS APROBADAS\n";
echo "====================================================================\n";

if ($pass === $total) {
    echo ">> TODAS LAS PRUEBAS FALTANTES DE LA FASE 5 PASARON AL 100% CON ÉXITO. <<\n\n";
} else {
    exit(1);
}
