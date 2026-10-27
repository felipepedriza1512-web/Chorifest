<?php
/**
 * Test integral de la Fase 4: Zonas de Envío y Personal
 * Base de datos: chorifest_test (aislada mediante X-Chorifest-Test-Db)
 */

require_once __DIR__ . '/test_bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/AdminModel.php';
require_once __DIR__ . '/../models/ZonaModel.php';
require_once __DIR__ . '/../models/PedidoModel.php';
require_once __DIR__ . '/../models/EstadoPedido.php';

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
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => $response,
        'final_url' => $finalUrl,
        'redirect_url' => $redirectUrl
    ];
}

function loginRole($email, $password, $cookieFile, $baseUrl) {
    @unlink($cookieFile);
    $res = makeHttpRequest("$baseUrl/controllers/auth/login.php", 'POST', [
        'action' => 'login',
        'email' => $email,
        'password' => $password
    ], $cookieFile);
    return $res;
}

function fetchCsrfToken($cookieFile, $baseUrl) {
    $res = makeHttpRequest("$baseUrl/api/csrf_token.php", 'GET', null, $cookieFile);
    $data = json_decode($res['body'], true);
    return $data['csrf_token'] ?? '';
}

$tempDir = __DIR__ . '/cookies_fase4';
if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);

$cookieAdmin = "$tempDir/cookie_admin.txt";
$cookieCocina = "$tempDir/cookie_cocina.txt";
$cookieDomi = "$tempDir/cookie_domi.txt";
$cookieCliente = "$tempDir/cookie_cliente.txt";
$cookieAnon = "$tempDir/cookie_anon.txt";

echo "====================================================================================================\n";
echo "PRUEBAS INTEGRALES DE LA FASE 4: ZONAS DE ENVÍO Y PERSONAL\n";
echo "Base de datos en uso: chorifest_test (completamente aislada)\n";
echo "====================================================================================================\n\n";

$passedCount = 0;
$totalCount = 0;

function runTest($title, $condition, $details = '') {
    global $passedCount, $totalCount;
    $totalCount++;
    if ($condition) {
        $passedCount++;
        echo " [PASS] $title\n";
        if ($details) echo "        $details\n";
    } else {
        echo " [FAIL] $title\n";
        if ($details) echo "        $details\n";
    }
}

// -----------------------------------------------------------------------------
// 1. CONTROL DE ACCESO A PANELES DE ZONAS Y PERSONAL
// -----------------------------------------------------------------------------
echo "\n--- 1. CONTROL DE ACCESO A PANELES ADMINISTRATIVOS (HTTP 403) ---\n";

// Login con roles
loginRole('admin@gmail.com', '123456', $cookieAdmin, $baseUrl);
loginRole('cocina@gmail.com', '123456', $cookieCocina, $baseUrl);
loginRole('domi@gmail.com', '123456', $cookieDomi, $baseUrl);
loginRole('juan@gmail.com', '123456', $cookieCliente, $baseUrl);

$rutasProtegidas = [
    "$baseUrl/controllers/admin/zonas.php",
    "$baseUrl/controllers/admin/empleados.php"
];

foreach ($rutasProtegidas as $url) {
    $slug = basename($url);
    
    // Sin sesión
    $resAnon = makeHttpRequest($url, 'GET', null, $cookieAnon);
    runTest("Anónimo intentando acceder a $slug -> 403", $resAnon['code'] === 403, "Código recibido: {$resAnon['code']}");
    
    // Cliente (rol 1)
    $resCli = makeHttpRequest($url, 'GET', null, $cookieCliente);
    runTest("Cliente (rol 1) intentando acceder a $slug -> 403", $resCli['code'] === 403, "Código recibido: {$resCli['code']}");
    
    // Cocinera (rol 3)
    $resCoc = makeHttpRequest($url, 'GET', null, $cookieCocina);
    runTest("Cocinera (rol 3) intentando acceder a $slug -> 403", $resCoc['code'] === 403, "Código recibido: {$resCoc['code']}");

    // Domiciliario (rol 4)
    $resDom = makeHttpRequest($url, 'GET', null, $cookieDomi);
    runTest("Domiciliario (rol 4) intentando acceder a $slug -> 403", $resDom['code'] === 403, "Código recibido: {$resDom['code']}");

    // Admin (rol 2)
    $resAdm = makeHttpRequest($url, 'GET', null, $cookieAdmin);
    runTest("Admin (rol 2) accediendo a $slug -> 200", $resAdm['code'] === 200, "Código recibido: {$resAdm['code']}");
}

// -----------------------------------------------------------------------------
// 2. PRUEBAS DE GEOMETRÍA DE ZONAS Y COBERTURA (RAY-CASTING & DESEMPATE)
// -----------------------------------------------------------------------------
echo "\n--- 2. GEOMETRÍA DE ZONAS, COBERTURA Y REGLAS DE DESEMPATE ---\n";

$zonaModel = new ZonaModel($db);

// Crear dos zonas de prueba solapadas para probar el algoritmo
// Zona A: Zona Cabecera ($5.000 domicilio, $30.000 mínimo)
// Zona B: Zona Centro ($4.000 domicilio, $20.000 mínimo) -> más barata, debe ganar por desempate
$polyGeoJsonA = json_encode([
    'type' => 'Polygon',
    'coordinates' => [[
        [-73.130, 7.110],
        [-73.110, 7.110],
        [-73.110, 7.130],
        [-73.130, 7.130],
        [-73.130, 7.110]
    ]]
]);

$polyGeoJsonB = json_encode([
    'type' => 'Polygon',
    'coordinates' => [[
        [-73.125, 7.115],
        [-73.115, 7.115],
        [-73.115, 7.125],
        [-73.125, 7.125],
        [-73.125, 7.115]
    ]]
]);

// Limpiar zonas y direcciones de pruebas anteriores si existen
$db->exec("DELETE FROM direccion WHERE direccion = 'Calle 10 # 20-30'");
$db->exec("DELETE FROM zona_envio WHERE nombre IN ('Test Zona A Premium', 'Test Zona B Economica')");

$resZa = $zonaModel->createZona([
    'nombre' => 'Test Zona A Premium',
    'costo_domicilio' => 5000.00,
    'monto_minimo' => 30000.00,
    'tiempo_estimado_min' => 25,
    'estado' => 'ACTIVA',
    'poligono_geojson' => $polyGeoJsonA
], 14);

$resZb = $zonaModel->createZona([
    'nombre' => 'Test Zona B Economica',
    'costo_domicilio' => 4000.00,
    'monto_minimo' => 20000.00,
    'tiempo_estimado_min' => 20,
    'estado' => 'ACTIVA',
    'poligono_geojson' => $polyGeoJsonB
], 14);

$idZa = is_array($resZa) ? (int)($resZa['id_zona'] ?? 0) : (int)$resZa;
$idZb = is_array($resZb) ? (int)($resZb['id_zona'] ?? 0) : (int)$resZb;

runTest("Creación de zonas de prueba con polígonos GeoJSON", $idZa > 0 && $idZb > 0, "IDs creados: Zona A=$idZa, Zona B=$idZb");

// Punto dentro de ambas zonas (-73.120, 7.120)
// Debe ganar Zona B porque tiene menor monto mínimo (20000 < 30000)
$puntoSolapado = $zonaModel->determinarZonaPorPunto(7.120, -73.120);
runTest(
    "Desempate de zonas solapadas: gana menor monto mínimo (y luego menor ID)",
    $puntoSolapado !== null && (int)$puntoSolapado['id_zona'] === $idZb,
    "Zona ganadora: " . ($puntoSolapado['nombre'] ?? 'Ninguna') . " (Mínimo: $" . ($puntoSolapado['monto_minimo'] ?? 0) . ")"
);

// Punto dentro solo de Zona A (-73.111, 7.111)
$puntoSoloA = $zonaModel->determinarZonaPorPunto(7.111, -73.111);
runTest(
    "Punto dentro únicamente de Zona A asigna Zona A",
    $puntoSoloA !== null && (int)$puntoSoloA['id_zona'] === $idZa,
    "Zona asignada: " . ($puntoSoloA['nombre'] ?? 'Ninguna')
);

// Punto fuera de toda cobertura (ej: Bogotá 4.7110, -74.0721)
$puntoFuera = $zonaModel->determinarZonaPorPunto(4.7110, -74.0721);
runTest(
    "Punto fuera de polígonos retorna null (sin cobertura)",
    $puntoFuera === null,
    "Resultado: " . ($puntoFuera ? 'Tiene cobertura errónea' : 'Sin cobertura detectada correctamente')
);

// Test HTTP api/validar_cobertura.php
$resApiCob = makeHttpRequest("$baseUrl/api/validar_cobertura.php", 'POST', [
    'lat' => 7.120,
    'lng' => -73.120
], $cookieCliente);
$jsonCob = json_decode($resApiCob['body'], true);

runTest(
    "API validar_cobertura con coordenadas dentro de polígono retorna cobertura=true",
    ($jsonCob['cobertura'] ?? false) === true && (int)($jsonCob['id_zona'] ?? 0) === $idZb,
    "Costo recibido: $" . ($jsonCob['costo_domicilio'] ?? 0) . ", Zona: " . ($jsonCob['nombre'] ?? '')
);

$resApiFuera = makeHttpRequest("$baseUrl/api/validar_cobertura.php", 'POST', [
    'lat' => 4.7110,
    'lng' => -74.0721
], $cookieCliente);
$jsonFuera = json_decode($resApiFuera['body'], true);

runTest(
    "API validar_cobertura con coordenadas fuera de polígono retorna cobertura=false",
    ($jsonFuera['cobertura'] ?? true) === false,
    "Mensaje recibido: " . ($jsonFuera['message'] ?? '')
);

// -----------------------------------------------------------------------------
// 3. VALIDACIÓN DE MONTO MÍNIMO Y SNAPSHOT HISTÓRICO DE ZONA EN PEDIDO
// -----------------------------------------------------------------------------
echo "\n--- 3. MONTO MÍNIMO DE ZONA Y SNAPSHOT HISTÓRICO EN PEDIDOS ---\n";

$pedidoModel = new PedidoModel($db);

// Intentar crear pedido con total menor a monto_minimo ($20.000 para Zona B)
// Producto 1 = Chorizo Tradicional ($15.000) -> Menor que $20.000
$cartSubMin = [
    [
        'producto' => ['id_producto' => 1, 'precio' => 15000.00, 'nombre_producto' => 'Chorizo Tradicional'],
        'cantidad' => 1
    ]
];

$dirData = [
    'direccion' => 'Calle 10 # 20-30',
    'barrio' => 'Centro',
    'referencia' => 'Casa blanca',
    'latitud' => 7.120, // Dentro de Zona B
    'longitud' => -73.120
];

$idPedTestSub = $pedidoModel->createPedido(13, $dirData, $cartSubMin, 'Efectivo', 'Nota test');

runTest(
    "Pedido rechazado si el total de productos no alcanza el monto_minimo de la zona",
    $idPedTestSub === null,
    "Error capturado: " . ($pedidoModel->getLastError() ?? '')
);

// Crear pedido con total suficiente (2 x $15.000 = $30.000 >= $20.000)
$cartOk = [
    [
        'producto' => ['id_producto' => 1, 'precio' => 15000.00, 'nombre_producto' => 'Chorizo Tradicional'],
        'cantidad' => 2
    ]
];

$idPedTest = $pedidoModel->createPedido(13, $dirData, $cartOk, 'Efectivo', 'Nota test fase 4');

runTest(
    "Pedido creado exitosamente cuando cumple monto_minimo y cobertura",
    $idPedTest !== null && $idPedTest > 0,
    "ID de pedido generado: #" . ($idPedTest ?? 0)
);

if ($idPedTest > 0) {
    $stmtSnap = $db->prepare("SELECT id_zona, nombre_zona, costo_domicilio, total FROM pedido WHERE id_pedido = ?");
    $stmtSnap->execute([$idPedTest]);
    $snapRow = $stmtSnap->fetch(PDO::FETCH_ASSOC);

    runTest(
        "Snapshot histórico de zona y costo_domicilio guardado en el pedido",
        (int)$snapRow['id_zona'] === $idZb && 
        $snapRow['nombre_zona'] === 'Test Zona B Economica' && 
        (float)$snapRow['costo_domicilio'] === (PedidoModel::COBRAR_DOMICILIO ? 4000.00 : 0.00),
        "Snapshot en BD: Zona '{$snapRow['nombre_zona']}', Costo: \${$snapRow['costo_domicilio']}, Total: \${$snapRow['total']}"
    );

    // Limpiar pedido de prueba
    $db->exec("DELETE FROM detalle_pedido WHERE id_pedido = $idPedTest");
    $db->exec("DELETE FROM historial_pedido WHERE id_pedido = $idPedTest");
    $db->exec("DELETE FROM pago WHERE id_pedido = $idPedTest");
    $db->exec("DELETE FROM pedido WHERE id_pedido = $idPedTest");
}

// -----------------------------------------------------------------------------
// 4. GESTIÓN DE PERSONAL, ROLES FIJOS, POLÍTICA DE CLAVE Y AUDITORÍA
// -----------------------------------------------------------------------------
echo "\n--- 4. GESTIÓN DE PERSONAL, ROLES FIJOS Y POLÍTICA DE CONTRASEÑAS ---\n";

$adminModel = new AdminModel($db);
$tokenAdmin = fetchCsrfToken($cookieAdmin, $baseUrl);

// Rechazo CSRF en empleados.php
$resCsrfFail = makeHttpRequest("$baseUrl/controllers/admin/empleados.php", 'POST', [
    'csrf_token' => 'token_falso_invalido',
    'action' => 'create',
    'nombre' => 'Test Hack',
    'correo' => 'hack@chorifest.local',
    'telefono' => '3000000000',
    'id_rol' => 3,
    'password' => 'PasswordSegura123!'
], $cookieAdmin);

runTest(
    "Rechazo con HTTP 403 ante token CSRF inválido en empleados.php",
    $resCsrfFail['code'] === 403,
    "Código recibido: {$resCsrfFail['code']}"
);

// Intento de crear rol 2 (Admin) desde formulario de empleados -> servidor rechaza
$resCrearAdmin = $adminModel->createEmpleado([
    'nombre' => 'Intento Admin No Autorizado',
    'correo' => 'fakeadmin@chorifest.local',
    'telefono' => '3001112233',
    'id_rol' => 2, // Intentando admin
    'password' => 'SuperSecret123!'
], 14);

runTest(
    "Servidor rechaza asignar rol Admin (2) desde gestión de personal",
    ($resCrearAdmin['success'] ?? true) === false,
    "Mensaje: " . ($resCrearAdmin['message'] ?? '')
);

// Intento con contraseña trivial (lista negra)
$resClaveTrivial = $adminModel->createEmpleado([
    'nombre' => 'Cocinera Clave Debil',
    'correo' => 'cocina_debil@chorifest.local',
    'telefono' => '3001112233',
    'id_rol' => 3,
    'password' => '12345678' // En lista negra
], 14);

runTest(
    "Servidor rechaza contraseña trivial que viola la política de seguridad",
    ($resClaveTrivial['success'] ?? true) === false,
    "Mensaje: " . ($resClaveTrivial['message'] ?? '')
);

// Creación exitosa de Cocinera de prueba con clave temporal válida
$emailTestCocinera = 'cocinera_fase4_' . time() . '@chorifest.local';
$passTestCocinera = 'TempSecret2026!';

$resCreaCoc = $adminModel->createEmpleado([
    'nombre' => 'María Cocinera Fase 4',
    'correo' => $emailTestCocinera,
    'telefono' => '3157778899',
    'id_rol' => 3,
    'turno' => 'Tarde',
    'password' => $passTestCocinera
], 14);

$idTestCocinera = (int)($resCreaCoc['id_usuario'] ?? 0);
runTest(
    "Creación exitosa de Cocinera con debe_cambiar_password = 1",
    $resCreaCoc['success'] && $idTestCocinera > 0,
    "ID Empleado: $idTestCocinera, Mensaje: {$resCreaCoc['message']}"
);

// Verificar flag debe_cambiar_password y auditoría en BD
$stmtAuditCoc = $db->prepare("SELECT debe_cambiar_password FROM usuario WHERE id_usuario = ?");
$stmtAuditCoc->execute([$idTestCocinera]);
$debeCambiarCoc = (int)$stmtAuditCoc->fetchColumn();

$stmtRegAudit = $db->prepare("SELECT COUNT(*) FROM auditoria WHERE accion = 'CREAR_EMPLEADO' AND id_registro = ?");
$stmtRegAudit->execute([$idTestCocinera]);
$countAuditCrear = (int)$stmtRegAudit->fetchColumn();

runTest(
    "Flag debe_cambiar_password = 1 y registro de auditoría CREAR_EMPLEADO en base de datos",
    $debeCambiarCoc === 1 && $countAuditCrear > 0,
    "debe_cambiar_password: $debeCambiarCoc, Registros de auditoría: $countAuditCrear"
);

// -----------------------------------------------------------------------------
// 5. FLUJO DE CAMBIO OBLIGATORIO DE CONTRASEÑA EN PRIMER INICIO
// -----------------------------------------------------------------------------
echo "\n--- 5. FLUJO DE CAMBIO OBLIGATORIO DE CONTRASEÑA ---\n";

$cookieTempCoc = "$tempDir/cookie_temp_cocinera.txt";
@unlink($cookieTempCoc);

// Login con la nueva cocinera
$resLoginCoc = loginRole($emailTestCocinera, $passTestCocinera, $cookieTempCoc, $baseUrl);
runTest(
    "Login de usuario nuevo redirige a cambiar_password_obligatorio.php",
    strpos($resLoginCoc['redirect_url'] ?? '', 'cambiar_password_obligatorio.php') !== false,
    "URL Redirección: " . ($resLoginCoc['redirect_url'] ?? 'Ninguna')
);

// Intento de saltarse el cambio accediendo al panel de cocina
$resBypass = makeHttpRequest("$baseUrl/controllers/cocinera/pedidos.php", 'GET', null, $cookieTempCoc);
runTest(
    "Middleware bloquea acceso al panel y fuerza redirección a cambiar_password_obligatorio",
    $resBypass['code'] === 302 && strpos($resBypass['redirect_url'] ?? '', 'cambiar_password_obligatorio.php') !== false,
    "Código: {$resBypass['code']}, Redirección: {$resBypass['redirect_url']}"
);

// Envío de formulario de cambio obligatorio con clave demasiado corta
$tokenCoc = fetchCsrfToken($cookieTempCoc, $baseUrl);
$resChgShort = makeHttpRequest("$baseUrl/controllers/auth/cambiar_password_obligatorio.php", 'POST', [
    'csrf_token' => $tokenCoc,
    'password' => 'corta',
    'password_confirm' => 'corta'
], $cookieTempCoc);

runTest(
    "Formulario rechaza nueva clave que no cumple la longitud mínima",
    strpos($resChgShort['body'], 'al menos 8 caracteres') !== false,
    "Validación cliente/servidor activa"
);

// Envío exitoso de contraseña definitiva
$nuevaPassDefinitiva = 'MiNuevaClaveSegura2026!';
$tokenCoc2 = fetchCsrfToken($cookieTempCoc, $baseUrl);
$resChgOk = makeHttpRequest("$baseUrl/controllers/auth/cambiar_password_obligatorio.php", 'POST', [
    'csrf_token' => $tokenCoc2,
    'password' => $nuevaPassDefinitiva,
    'password_confirm' => $nuevaPassDefinitiva
], $cookieTempCoc);

runTest(
    "Cambio obligatorio de contraseña exitoso redirige al panel del rol correspondiente",
    $resChgOk['code'] === 302 && strpos($resChgOk['redirect_url'] ?? '', 'cocinera') !== false,
    "Redirección: {$resChgOk['redirect_url']}"
);

// Verificar en BD que debe_cambiar_password pasó a 0
$stmtAuditCoc->execute([$idTestCocinera]);
$debeCambiarCocPost = (int)$stmtAuditCoc->fetchColumn();
runTest(
    "Flag debe_cambiar_password actualizado a 0 en base de datos tras cambio exitoso",
    $debeCambiarCocPost === 0,
    "Nuevo valor: $debeCambiarCocPost"
);

// Acceso al panel de cocina ahora permitido
$resCocPanelOk = makeHttpRequest("$baseUrl/controllers/cocinera/pedidos.php", 'GET', null, $cookieTempCoc);
runTest(
    "Acceso al panel de cocina ahora habilitado (HTTP 200)",
    $resCocPanelOk['code'] === 200,
    "Código recibido: {$resCocPanelOk['code']}"
);

// -----------------------------------------------------------------------------
// 6. RESET DE CONTRASEÑA POR ADMINISTRADOR (CLAVE TEMPORAL DE UN SOLO USO)
// -----------------------------------------------------------------------------
echo "\n--- 6. RESET DE CONTRASEÑA POR ADMINISTRADOR ---\n";

$resReset = $adminModel->resetPasswordEmpleado($idTestCocinera, 14);
runTest(
    "Admin genera clave temporal de un solo uso para empleado",
    $resReset['success'] && !empty($resReset['temp_password']),
    "Nueva clave temporal generada: {$resReset['temp_password']}"
);

$stmtAuditCoc->execute([$idTestCocinera]);
$debeCambiarPostReset = (int)$stmtAuditCoc->fetchColumn();

$stmtRegResetAudit = $db->prepare("SELECT COUNT(*) FROM auditoria WHERE accion = 'RESET_PASSWORD_EMPLEADO' AND id_registro = ?");
$stmtRegResetAudit->execute([$idTestCocinera]);
$countAuditReset = (int)$stmtRegResetAudit->fetchColumn();

runTest(
    "Reset de clave restablece debe_cambiar_password = 1 y registra auditoría",
    $debeCambiarPostReset === 1 && $countAuditReset > 0,
    "debe_cambiar_password: $debeCambiarPostReset, Auditorías registradas: $countAuditReset"
);

// -----------------------------------------------------------------------------
// 7. PROTECCIÓN DE DOMICILIARIOS CON PEDIDOS ACTIVOS Y REVOCACIÓN INMEDIATA
// -----------------------------------------------------------------------------
echo "\n--- 7. CONTROL DE DESACTIVACIÓN Y REVOCACIÓN INMEDIATA DE SESIÓN ---\n";

// Crear domiciliario de prueba
$emailTestDomi = 'domi_fase4_' . time() . '@chorifest.local';
$resCreaDomi = $adminModel->createEmpleado([
    'nombre' => 'Carlos Domiciliario Fase 4',
    'correo' => $emailTestDomi,
    'telefono' => '3114445566',
    'id_rol' => 4,
    'tipo_vehiculo' => 'Moto',
    'placa' => 'XYZ-99F',
    'password' => 'TempDomiPass2026!'
], 14);

$idTestDomi = (int)($resCreaDomi['id_usuario'] ?? 0);
runTest("Creación de domiciliario de prueba", $idTestDomi > 0, "ID Domiciliario: $idTestDomi");

// Crear un pedido activo específico para la prueba
$stmtPedAct = $db->prepare("
    INSERT INTO pedido (id_cliente, id_estado, id_direccion, id_zona, nombre_zona, total, fecha)
    VALUES (13, ?, 1, 1, 'Zona Test', 25000.00, NOW())
");
$stmtPedAct->execute([EstadoPedido::EN_CAMINO]);
$idPedActivo = (int)$db->lastInsertId();

$db->exec("INSERT INTO asignacion_domicilio (id_pedido, id_domiciliario, fecha_asignacion) VALUES ($idPedActivo, $idTestDomi, NOW())");

// Intento de desactivar al domiciliario mientras tiene un pedido activo
$resDesactivarBloqueado = $adminModel->toggleEstadoEmpleado($idTestDomi, 14);
runTest(
    "Impedir desactivar domiciliario que tiene pedidos en curso",
    $resDesactivarBloqueado['success'] === false && strpos($resDesactivarBloqueado['message'], 'pedido(s) activo(s)') !== false,
    "Respuesta del sistema: {$resDesactivarBloqueado['message']}"
);

// Liberar el pedido (marcar como entregado)
$db->exec("UPDATE pedido SET id_estado = " . EstadoPedido::ENTREGADO . " WHERE id_pedido = $idPedActivo");

// Ahora la desactivación debe ser exitosa
$resDesactivarPermitido = $adminModel->toggleEstadoEmpleado($idTestDomi, 14);
runTest(
    "Desactivación exitosa de domiciliario sin pedidos activos",
    $resDesactivarPermitido['success'] === true && $resDesactivarPermitido['nuevo_estado'] === 'INACTIVO',
    "Respuesta: {$resDesactivarPermitido['message']}"
);

// Probar revocación inmediata de sesión:
// Activar temporalmente a la cocinera, asegurar hash de clave y loguearla
$hashedClave = password_hash($nuevaPassDefinitiva, PASSWORD_DEFAULT);
$db->exec("UPDATE usuario SET estado = 'ACTIVO', debe_cambiar_password = 0, password_hash = '$hashedClave' WHERE id_usuario = $idTestCocinera");
$cookieRevoc = "$tempDir/cookie_revocacion.txt";
@unlink($cookieRevoc);
$loginRevocRes = loginRole($emailTestCocinera, $nuevaPassDefinitiva, $cookieRevoc, $baseUrl);

// Verificar que puede acceder al panel
$resAntesRevoc = makeHttpRequest("$baseUrl/controllers/cocinera/pedidos.php", 'GET', null, $cookieRevoc);
runTest("Usuario activo accede a su panel antes de desactivación", $resAntesRevoc['code'] === 200, "Código: {$resAntesRevoc['code']}");

// Admin desactiva a la cocinera
$adminModel->toggleEstadoEmpleado($idTestCocinera, 14);

// Siguiente solicitud del usuario desactivado: debe revocar sesión inmediatamente y redirigir a login
$resPostRevoc = makeHttpRequest("$baseUrl/controllers/cocinera/pedidos.php", 'GET', null, $cookieRevoc);
runTest(
    "Revocación inmediata de sesión: usuario desactivado es expulsado al login con mensaje inactivo",
    $resPostRevoc['code'] === 302 && strpos($resPostRevoc['redirect_url'] ?? '', 'error=inactivo') !== false,
    "Código: {$resPostRevoc['code']}, Redirección: {$resPostRevoc['redirect_url']}"
);

// -----------------------------------------------------------------------------
// 8. DETECTOR DE ACTIVIDAD EN LÍNEA (VENTANA 2 MINUTOS)
// -----------------------------------------------------------------------------
echo "\n--- 8. DETECTOR DE ACTIVIDAD EN LÍNEA (VENTANA 2 MINUTOS) ---\n";

// Simular usuario con actividad reciente (NOW())
$db->exec("UPDATE usuario SET ultima_actividad = NOW() WHERE id_usuario = 14"); // Admin
// Simular usuario sin actividad reciente (hace 10 minutos)
$db->exec("UPDATE usuario SET ultima_actividad = DATE_SUB(NOW(), INTERVAL 10 MINUTE) WHERE id_usuario = $idTestDomi");

$listaEmpleados = $adminModel->getEmpleados();
$domiEncontrado = null;
foreach ($listaEmpleados as $emp) {
    if ((int)$emp['id_usuario'] === $idTestDomi) {
        $domiEncontrado = $emp;
        break;
    }
}

runTest(
    "Detector de actividad marca esta_online = 0 para usuario inactivo hace > 2 minutos",
    $domiEncontrado !== null && (int)$domiEncontrado['esta_online'] === 0,
    "Empleado: {$domiEncontrado['nombre']}, esta_online: {$domiEncontrado['esta_online']}"
);

// Simular que el usuario vuelve a tener actividad reciente (NOW() - 30 segundos)
$db->exec("UPDATE usuario SET ultima_actividad = DATE_SUB(NOW(), INTERVAL 30 SECOND) WHERE id_usuario = $idTestDomi");
$listaEmpleados2 = $adminModel->getEmpleados();
$domiEncontrado2 = null;
foreach ($listaEmpleados2 as $emp) {
    if ((int)$emp['id_usuario'] === $idTestDomi) {
        $domiEncontrado2 = $emp;
        break;
    }
}

runTest(
    "Detector de actividad marca esta_online = 1 para usuario activo en últimos 2 minutos",
    $domiEncontrado2 !== null && (int)$domiEncontrado2['esta_online'] === 1,
    "Empleado: {$domiEncontrado2['nombre']}, esta_online: {$domiEncontrado2['esta_online']}"
);

// -----------------------------------------------------------------------------
// 9. RESILIENCIA CON PEDIDOS LEGADOS SIN COORDENADAS
// -----------------------------------------------------------------------------
echo "\n--- 9. MANEJO DE PEDIDOS LEGADOS SIN COORDENADAS ---\n";

// Crear un pedido legado sin coordenadas (lat=null, lng=null)
$stmtLeg = $db->prepare("
    INSERT INTO pedido (id_cliente, id_estado, id_direccion, id_zona, nombre_zona, costo_domicilio, subtotal, total, fecha)
    VALUES (13, 5, 1, 1, 'Zona Histórica', 3000.00, 20000.00, 23000.00, NOW())
");
$stmtLeg->execute();
$idPedLeg = (int)$db->lastInsertId();

// Asignar al domiciliario
$db->exec("INSERT INTO asignacion_domicilio (id_pedido, id_domiciliario, fecha_asignacion) VALUES ($idPedLeg, 16, NOW())");

// Consultar api/obtener_seguimiento.php
$resApiLeg = makeHttpRequest("$baseUrl/api/obtener_seguimiento.php?id_pedido=$idPedLeg", 'GET', null, $cookieCliente);
$jsonLeg = json_decode($resApiLeg['body'], true);

runTest(
    "api/obtener_seguimiento devuelve tiene_coordenadas = false sin crashear en pedidos antiguos",
    ($jsonLeg['success'] ?? false) === true && ($jsonLeg['cliente']['tiene_coordenadas'] ?? true) === false,
    "tiene_coordenadas: " . var_export($jsonLeg['cliente']['tiene_coordenadas'] ?? null, true)
);

// Limpiar pedidos y asignaciones de prueba
$db->exec("DELETE FROM asignacion_domicilio WHERE id_pedido IN ($idPedLeg, $idPedActivo) OR id_domiciliario = $idTestDomi");
$db->exec("DELETE FROM pedido WHERE id_pedido IN ($idPedLeg, $idPedActivo)");

// Limpiar empleados de prueba
$db->exec("DELETE FROM cocinera WHERE id_usuario = $idTestCocinera");
$db->exec("DELETE FROM domiciliario WHERE id_usuario = $idTestDomi");
$db->exec("DELETE FROM auditoria WHERE id_registro IN ($idTestCocinera, $idTestDomi)");
$db->exec("DELETE FROM usuario WHERE id_usuario IN ($idTestCocinera, $idTestDomi)");

// Limpiar zonas y direcciones de prueba
$db->exec("DELETE FROM direccion WHERE id_zona IN ($idZa, $idZb)");
$db->exec("DELETE FROM zona_envio WHERE id_zona IN ($idZa, $idZb)");

// -----------------------------------------------------------------------------
// 10. VERIFICACIÓN DE LOGIN DE LOS 4 ROLES
// -----------------------------------------------------------------------------
echo "\n--- 10. VERIFICACIÓN FINAL DE LOGIN DE LOS 4 ROLES ---\n";

$roles = [
    'Administrador' => ['email' => 'admin@gmail.com', 'pass' => '123456', 'target' => 'admin'],
    'Cocinera'      => ['email' => 'cocina@gmail.com', 'pass' => '123456', 'target' => 'cocinera'],
    'Domiciliario'  => ['email' => 'domi@gmail.com', 'pass' => '123456', 'target' => 'domiciliario'],
    'Cliente'       => ['email' => 'juan@gmail.com', 'pass' => '123456', 'target' => 'catalogo']
];

foreach ($roles as $nombreRol => $r) {
    $cFile = "$tempDir/cookie_check_" . strtolower($nombreRol) . ".txt";
    $resLog = loginRole($r['email'], $r['pass'], $cFile, $baseUrl);
    runTest(
        "Login exitoso rol $nombreRol ({$r['email']}) -> 302 hacia panel",
        $resLog['code'] === 302 && strpos($resLog['redirect_url'] ?? '', $r['target']) !== false,
        "Redirección: {$resLog['redirect_url']}"
    );
    @unlink($cFile);
}

// Limpiar cookies temporales
array_map('unlink', glob("$tempDir/*.txt"));
@rmdir($tempDir);

echo "\n====================================================================================================\n";
echo "RESUMEN DE PRUEBAS DE FASE 4: $passedCount / $totalCount APROBADAS (" . round(($passedCount / $totalCount) * 100, 1) . "%)\n";
echo "====================================================================================================\n";

if ($passedCount === $totalCount) {
    echo ">> TODAS LAS PRUEBAS DE LA FASE 4 PASARON EXITOSAMENTE. <<\n\n";
    exit(0);
} else {
    echo ">> HUBO FALLOS EN LAS PRUEBAS DE LA FASE 4. <<\n\n";
    exit(1);
}
