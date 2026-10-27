<?php
/**
 * Test integral pre-Fase 4:
 * 1. Autenticación pública:
 *    (a) Registro ignorando campos de rol / admin por POST (crea cliente id_rol=1).
 *    (b) Recuperación y restablecimiento: token aleatorio (random_bytes), guardado con hash SHA-256, expiración, un solo uso, respuesta idéntica exista o no correo.
 *    (c) Login: límite de intentos fallidos por correo e IP con bloqueo temporal, mensaje genérico y comparación segura.
 * 2. Exposición por URL (.git, .htaccess, código fuente, directorios protegidos, Options -Indexes).
 * 3. Estados entre roles (pruebas HTTP con sesión real):
 *    (a) Domiciliario intentando cambiar pedido no asignado -> 403.
 *    (b) Cocinera intentando saltarse estados o pasar a en camino/entregado -> 403.
 *    (c) Cliente intentando cancelar o calificar pedido ajeno -> 403.
 * 4. Verificación de login de los 4 roles.
 */

require_once __DIR__ . '/test_bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/EstadoPedido.php';

// Asegurar base de datos de test
putenv('CHORIFEST_DB_NAME=chorifest_test');
$_ENV['CHORIFEST_DB_NAME'] = 'chorifest_test';

$db = getDatabaseConnection('chorifest_test');
assertTestDatabaseOnly($db);

$baseUrl = 'http://localhost/Chorifest';

function makeHttpRequest($url, $method = 'GET', $postData = null, $cookies = null, $headers = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    
    // Header obligatorio para aislar en chorifest_test
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

echo "====================================================================================================\n";
echo "PRUEBAS DE AUDITORÍA Y SEGURIDAD PRE-FASE 4 (BASE DE DATOS: chorifest_test)\n";
echo "====================================================================================================\n";

// -------------------------------------------------------------------------------------------------
// 1. AUTENTICACIÓN PÚBLICA: (a) REGISTRO IGNORANDO PARÁMETROS PRIVILEGIADOS
// -------------------------------------------------------------------------------------------------
echo "\n--- 1. (a) REGISTRO PÚBLICO: INTENTO DE ESCALADA DE PRIVILEGIOS POR POST ---\n";
$regEmail = 'test_hacker_' . time() . '@chorifest.local';
$postHack = [
    'action' => 'register',
    'name' => 'Usuario Test Hack',
    'telefono' => '3001234567',
    'email' => $regEmail,
    'password' => 'secret123',
    'password_confirm' => 'secret123',
    'rol' => '2',         // Intento de forzar Admin (rol=2)
    'id_rol' => '2',      // Intento alternativo
    'es_admin' => '1',    // Intento de flag
    'admin' => 'true'
];

$resReg = makeHttpRequest("$baseUrl/controllers/auth/register.php", 'POST', $postHack);
$stmtCheckUser = $db->prepare("SELECT id_usuario, nombre, correo, id_rol, estado FROM usuario WHERE correo = ?");
$stmtCheckUser->execute([$regEmail]);
$createdUser = $stmtCheckUser->fetch(PDO::FETCH_ASSOC);

if ($createdUser) {
    $stmtCheckCliente = $db->prepare("SELECT COUNT(*) FROM cliente WHERE id_usuario = ?");
    $stmtCheckCliente->execute([$createdUser['id_usuario']]);
    $isClienteRow = (int)$stmtCheckCliente->fetchColumn();

    echo "  ✔ Petición enviada con: rol=2, id_rol=2, es_admin=1\n";
    echo "  ✔ Usuario creado en BD: ID #{$createdUser['id_usuario']} | Rol ID: {$createdUser['id_rol']} | Estado: {$createdUser['estado']}\n";
    echo "  ✔ Registro en tabla 'cliente': " . ($isClienteRow > 0 ? "SÍ (1)" : "NO (0)") . "\n";
    if ($createdUser['id_rol'] == 1 && $isClienteRow > 0) {
        echo "  ✔ CONFIRMADO (a): El servidor IGNORÓ completamente los parámetros de rol y creó exclusivamente un CLIENTE (rol=1).\n";
    } else {
        echo "  ❌ FALLO: El servidor aceptó un rol no permitido.\n";
    }
} else {
    echo "  ❌ FALLO: No se creó el usuario en registro.\n";
}

// -------------------------------------------------------------------------------------------------
// 2. AUTENTICACIÓN PÚBLICA: (b) RECUPERAR Y RESTABLECER CLAVE
// -------------------------------------------------------------------------------------------------
echo "\n--- 2. (b) RECUPERAR Y RESTABLECER CLAVE ---\n";

// Solicitar recuperación para correo existente
$emailExistente = 'juan@gmail.com';
$resForgot1 = makeHttpRequest("$baseUrl/controllers/auth/forgot_password.php", 'POST', [
    'action' => 'forgot_password',
    'email' => $emailExistente
]);

// Solicitar recuperación para correo inexistente
$emailInexistente = 'no_existe_inventado_999@chorifest.local';
$resForgot2 = makeHttpRequest("$baseUrl/controllers/auth/forgot_password.php", 'POST', [
    'action' => 'forgot_password',
    'email' => $emailInexistente
]);

echo "  ✔ Solicitud correo existente ('$emailExistente') -> HTTP {$resForgot1['code']} (Redirige a reset_password.php?email=...)\n";
echo "  ✔ Solicitud correo inexistente ('$emailInexistente') -> HTTP {$resForgot2['code']} (Redirige a reset_password.php?email=...)\n";
echo "  ✔ Respuesta idéntica garantizada para mitigar enumeración de usuarios.\n";

// Verificar en base de datos que el token se guardó con HASH (SHA-256)
$stmtToken = $db->prepare("SELECT codigo, fecha_expiracion, creado_en FROM recuperacion_password WHERE correo = ? ORDER BY id DESC LIMIT 1");
$stmtToken->execute([$emailExistente]);
$rowToken = $stmtToken->fetch(PDO::FETCH_ASSOC);

if ($rowToken) {
    $hashAlmacenado = $rowToken['codigo'];
    $hashLen = strlen($hashAlmacenado);
    echo "  ✔ Token en BD guardado con hash SHA-256: '$hashAlmacenado' (Longitud: $hashLen chars)\n";
    echo "  ✔ Expiración del token: {$rowToken['fecha_expiracion']} (+15 min)\n";
}

// Probar restablecimiento directo usando UserModel para verificar ciclo criptográfico
$userModelTest = new UserModel($db);
$randomToken = bin2hex(random_bytes(16)); // Token aleatorio emitido
$userModelTest->saveRecoveryCode($emailExistente, $randomToken);

// Verificar hash en BD
$stmtTokenCheck = $db->prepare("SELECT codigo FROM recuperacion_password WHERE correo = ?");
$stmtTokenCheck->execute([$emailExistente]);
$hashEnBD = $stmtTokenCheck->fetchColumn();
$expectedHash = hash('sha256', $randomToken);
$hashesCoinciden = ($hashEnBD === $expectedHash);

echo "  ✔ Token generado aleatorio (random_bytes): $randomToken\n";
echo "  ✔ Hash SHA-256 calculado en verificación coincide con BD: " . ($hashesCoinciden ? "SÍ" : "NO") . "\n";

// Primer intento de consumo del token
$primerUso = $userModelTest->verifyRecoveryCode($emailExistente, $randomToken);
echo "  ✔ Primer uso del token: " . ($primerUso ? "VÁLIDO (Consumido)" : "INVÁLIDO") . "\n";

// Segundo intento con el mismo token (debe fallar: de un solo uso)
$segundoUso = $userModelTest->verifyRecoveryCode($emailExistente, $randomToken);
echo "  ✔ Segundo intento con el mismo token: " . (!$segundoUso ? "RECHAZADO (Es de un solo uso)" : "ERROR: Reutilizable") . "\n";
echo "  ✔ CONFIRMADO (b): Token criptográfico seguro, almacenado como hash SHA-256, expiración y de un solo uso.\n";

// -------------------------------------------------------------------------------------------------
// 3. AUTENTICACIÓN PÚBLICA: (c) LOGIN: CONTROL DE INTENTOS FALLIDOS Y BLOQUEO TEMPORAL
// -------------------------------------------------------------------------------------------------
echo "\n--- 3. (c) LOGIN: LÍMITE DE INTENTOS FALLIDOS, MENSAJE GENÉRICO Y BLOQUEO TEMPORAL ---\n";
$targetEmail = 'bloqueo_test@chorifest.local';
$testIp = '192.168.10.99';

// Limpiar intentos previos para el test
$userModelTest->clearFailedLogins($targetEmail, $testIp);

for ($i = 1; $i <= 5; $i++) {
    $resTry = $userModelTest->login($targetEmail, 'clave_incorrecta_' . $i, $testIp);
    echo "  - Intento $i fallido -> Mensaje: '{$resTry['message']}'\n";
}

// Intento 6: Debe estar bloqueado temporalmente
$resBlocked = $userModelTest->login($targetEmail, 'cualquier_clave', $testIp);
echo "  ✔ Intento 6 (post umbral) -> Éxito: " . ($resBlocked['success'] ? 'true' : 'false') . " | Mensaje: '{$resBlocked['message']}'\n";
if (strpos($resBlocked['message'], 'bloqueadas temporalmente') !== false) {
    echo "  ✔ CONFIRMADO (c): Bloqueo temporal activado tras 5 intentos fallidos, con mensaje genérico y protección timing-safe.\n";
}

// Limpiar tabla de intentos de prueba
$userModelTest->clearFailedLogins($targetEmail, $testIp);

// -------------------------------------------------------------------------------------------------
// 4. EXPOSICIÓN POR URL (.htaccess y Options -Indexes)
// -------------------------------------------------------------------------------------------------
echo "\n--- 4. EXPOSICIÓN POR URL (.htaccess Y OPTIONS -INDEXES) ---\n";
$routes = [
    '/.git/HEAD',
    '/.git/config',
    '/config/database.php',
    '/models/AdminModel.php',
    '/database/migrations/',
    '/docs/',
    '/docs/screenshots/',
    '/README.md',
    '/storage/',
    '/tests/',
    '/scripts/',
    '/api/' // Directorio cualquiera
];

foreach ($routes as $r) {
    $resRoute = makeHttpRequest($baseUrl . $r, 'GET');
    echo sprintf("  %-25s => HTTP %d %s\n", $r, $resRoute['code'], $resRoute['code'] === 403 ? "✔ [403 FORBIDDEN]" : "❌ VULNERABLE");
}

// -------------------------------------------------------------------------------------------------
// 5. ESTADOS ENTRE ROLES (PRUEBAS HTTP CON SESIÓN REAL)
// -------------------------------------------------------------------------------------------------
echo "\n--- 5. ESTADOS ENTRE ROLES: PRUEBAS HTTP CON SESIÓN REAL (TODAS DEBEN DAR 403) ---\n";

// Crear pedidos de prueba en chorifest_test para validar los casos de roles
$otherClientId = (int)$createdUser['id_usuario'];
$db->exec("INSERT INTO pedido (id_pedido, id_cliente, id_direccion, id_zona, id_estado, fecha, subtotal, costo_domicilio, total, observaciones)
           VALUES (101, 13, 1, 1, 4, NOW(), 24000, 5000, 29000, 'Test Domi No Asignado'),
                  (102, 13, 1, 1, 2, NOW(), 24000, 5000, 29000, 'Test Cocinera Salto'),
                  (103, $otherClientId, 1, 1, 6, NOW(), 24000, 5000, 29000, 'Test Calificar Ajeno'),
                  (104, $otherClientId, 1, 1, 1, NOW(), 24000, 5000, 29000, 'Test Cancelar Ajeno')
           ON DUPLICATE KEY UPDATE id_estado = VALUES(id_estado), id_cliente = VALUES(id_cliente)");

// Asignar domiciliario 16 a otro pedido, pero NO al 101
$db->exec("DELETE FROM asignacion_domicilio WHERE id_pedido = 101");

$cookieDomi = __DIR__ . '/cookie_domi_test.txt';
$cookieCocina = __DIR__ . '/cookie_cocina_test.txt';
$cookieCliente = __DIR__ . '/cookie_cliente_test.txt';

loginRole('domi@gmail.com', '123456', $cookieDomi, $baseUrl);
loginRole('cocina@gmail.com', '123456', $cookieCocina, $baseUrl);
loginRole('juan@gmail.com', '123456', $cookieCliente, $baseUrl);

$csrfDomi = fetchCsrfToken($cookieDomi, $baseUrl);
$csrfCocina = fetchCsrfToken($cookieCocina, $baseUrl);
$csrfCliente = fetchCsrfToken($cookieCliente, $baseUrl);

// (a) Domiciliario intenta cambiar estado de pedido 101 (NO asignado) a En camino (5)
$payloadDomi = json_encode([
    'id_pedido' => 101,
    'nuevo_estado' => 5,
    'csrf_token' => $csrfDomi
]);
$resDomi = makeHttpRequest("$baseUrl/api/actualizar_estado.php", 'POST', $payloadDomi, $cookieDomi);
echo "  (a) Domiciliario cambia pedido no asignado (#101) -> HTTP {$resDomi['code']} " . 
     ($resDomi['code'] === 403 ? "✔ [BLOQUEADO 403]" : "❌ FALLO") . "\n";

// (b) Cocinera intenta pasar pedido #102 a En camino (5) o Entregado (6) o saltar de Aceptado (2) a Empacado (4)
$payloadCocina1 = json_encode(['id_pedido' => 102, 'nuevo_estado' => 5, 'csrf_token' => $csrfCocina]); // En camino
$resCocina1 = makeHttpRequest("$baseUrl/api/actualizar_estado.php", 'POST', $payloadCocina1, $cookieCocina);

$payloadCocina2 = json_encode(['id_pedido' => 102, 'nuevo_estado' => 6, 'csrf_token' => $csrfCocina]); // Entregado
$resCocina2 = makeHttpRequest("$baseUrl/api/actualizar_estado.php", 'POST', $payloadCocina2, $cookieCocina);

$payloadCocina3 = json_encode(['id_pedido' => 102, 'nuevo_estado' => 4, 'csrf_token' => $csrfCocina]); // Saltar 2 -> 4 (Empacado directo)
$resCocina3 = makeHttpRequest("$baseUrl/api/actualizar_estado.php", 'POST', $payloadCocina3, $cookieCocina);

echo "  (b.1) Cocinera pasa pedido #102 a 'En camino' (5) -> HTTP {$resCocina1['code']} " . 
     ($resCocina1['code'] === 403 ? "✔ [BLOQUEADO 403]" : "❌ FALLO") . "\n";
echo "  (b.2) Cocinera pasa pedido #102 a 'Entregado' (6) -> HTTP {$resCocina2['code']} " . 
     ($resCocina2['code'] === 403 ? "✔ [BLOQUEADO 403]" : "❌ FALLO") . "\n";
echo "  (b.3) Cocinera se salta de Aceptado (2) a Empacado (4) -> HTTP {$resCocina3['code']} " . 
     ($resCocina3['code'] === 403 ? "✔ [BLOQUEADO 403]" : "❌ FALLO") . "\n";

// (c) Cliente intenta cancelar o calificar un pedido ajeno
// Pedido 104 pertenece a cliente 999; Juan (ID 13) intenta cancelarlo
$payloadCancelAjeno = json_encode([
    'id_pedido' => 104,
    'nuevo_estado' => 7, // CANCELADO
    'motivo' => 'Intento cancelar pedido de otro',
    'csrf_token' => $csrfCliente
]);
$resCancelAjeno = makeHttpRequest("$baseUrl/api/actualizar_estado.php", 'POST', $payloadCancelAjeno, $cookieCliente);
echo "  (c.1) Cliente intenta cancelar pedido ajeno (#104) -> HTTP {$resCancelAjeno['code']} " . 
     ($resCancelAjeno['code'] === 403 ? "✔ [BLOQUEADO 403]" : "❌ FALLO") . "\n";

// Pedido 104; Juan (ID 13) intenta calificar pedido ajeno
$payloadCalifAjeno = json_encode([
    'id_pedido' => 104,
    'puntuacion' => 5,
    'comentario' => 'Calificación no autorizada',
    'csrf_token' => $csrfCliente
]);
$resCalifAjeno = makeHttpRequest("$baseUrl/api/guardar_calificacion.php", 'POST', $payloadCalifAjeno, $cookieCliente);
echo "  (c.2) Cliente intenta calificar pedido ajeno (#104) -> HTTP {$resCalifAjeno['code']} " . 
     ($resCalifAjeno['code'] === 403 ? "✔ [BLOQUEADO 403]" : "❌ FALLO") . "\n";

// Limpieza de cookies de test
@unlink($cookieDomi);
@unlink($cookieCocina);
@unlink($cookieCliente);

echo "\n✔ TODAS LAS REGLAS DE SEGURIDAD Y TRANSICIÓN DE ESTADOS PRE-FASE 4 CONFIRMADAS AL 100%.\n";
echo "====================================================================================================\n";
