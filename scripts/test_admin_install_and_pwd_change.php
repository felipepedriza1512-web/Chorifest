<?php
require_once __DIR__ . '/../config/database.php';

echo "=== TEST: INSTALADOR CON CLAVE ALEATORIA Y CAMBIO OBLIGATORIO HTTP ===\n\n";

$baseUrl = 'http://localhost/Chorifest';
$db = getDatabaseConnection();

// 1. Ejecutar el generador aleatorio de install.php
$rawRandomPass = 'Chori_' . bin2hex(random_bytes(4)) . '!' . strtoupper(bin2hex(random_bytes(2)));
$hashedPassword = password_hash($rawRandomPass, PASSWORD_DEFAULT);

$stmtUpd = $db->prepare("UPDATE usuario SET password_hash = ?, debe_cambiar_password = 1, estado = 'ACTIVO' WHERE correo = 'admin@chorifest.com'");
$stmtUpd->execute([$hashedPassword]);
echo "1. Contraseña temporal aleatoria generada en base de datos: $rawRandomPass\n";
echo "   Flag debe_cambiar_password = 1 establecido para admin@chorifest.com\n\n";

// 2. Iniciar sesión mediante HTTP POST real
$cookieFile = __DIR__ . '/cookie_admin_install_test.txt';
if (file_exists($cookieFile)) @unlink($cookieFile);

// Obtener CSRF del login
$ch = curl_init("$baseUrl/controllers/auth/login.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
$loginHtml = curl_exec($ch);
curl_close($ch);

preg_match('/name="csrf_token"\s+value="([^"]+)"/', $loginHtml, $mCsrf);
$csrfToken = $mCsrf[1] ?? '';

$loginPost = [
    'action' => 'login',
    'email' => 'admin@chorifest.com',
    'password' => $rawRandomPass,
    'csrf_token' => $csrfToken
];

$ch = curl_init("$baseUrl/controllers/auth/login.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($loginPost));
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
$resLogin = curl_exec($ch);
$httpCodeLogin = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$redirectUrlLogin = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
curl_close($ch);

echo "2. Intento de Login con contraseña temporal aleatoria:\n";
echo "   - Código HTTP: $httpCodeLogin\n";
echo "   - Redirección: $redirectUrlLogin\n";
$isRedirectedToChange = strpos($redirectUrlLogin, 'cambiar_password_obligatorio.php') !== false;
echo "   - ¿Redirigió obligatoriamente a cambiar_password_obligatorio.php?: " . ($isRedirectedToChange ? "✔ SÍ" : "❌ NO") . "\n\n";

// 3. Obtener página de cambiar contraseña para extraer CSRF
$ch = curl_init("$baseUrl/controllers/auth/cambiar_password_obligatorio.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
$changeHtml = curl_exec($ch);
curl_close($ch);

preg_match('/name="csrf_token"\s+value="([^"]+)"/', $changeHtml, $mCsrf2);
$csrfTokenChange = $mCsrf2[1] ?? '';

// 4. Intentar ingresar contraseña trivial '123456'
echo "3. Intento de cambio con contraseña débil '123456':\n";
$ch = curl_init("$baseUrl/controllers/auth/cambiar_password_obligatorio.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'password' => '123456',
    'password_confirm' => '123456',
    'csrf_token' => $csrfTokenChange
]));
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
$resTrivial = curl_exec($ch);
curl_close($ch);

$rejectedTrivial = (strpos($resTrivial, 'demasiado común') !== false || strpos($resTrivial, 'al menos 8 caracteres') !== false);
echo "   - ¿Fue rechazada la contraseña trivial?: " . ($rejectedTrivial ? "✔ SÍ (Rechazada con mensaje de política de seguridad)" : "❌ NO") . "\n\n";

// 5. Cambiar a una contraseña segura definitiva
echo "4. Intento de cambio con contraseña segura válida 'ChoriAdmin2026#Gourmet':\n";
$newSecurePass = 'ChoriAdmin2026#Gourmet';
$ch = curl_init("$baseUrl/controllers/auth/cambiar_password_obligatorio.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'password' => $newSecurePass,
    'password_confirm' => $newSecurePass,
    'csrf_token' => $csrfTokenChange
]));
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
$resChangeSuccess = curl_exec($ch);
$httpCodeChange = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$redirectUrlChange = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
curl_close($ch);

echo "   - Código HTTP: $httpCodeChange\n";
echo "   - Redirección: $redirectUrlChange\n";
$isRedirectedToDashboard = strpos($redirectUrlChange, 'dashboard.php') !== false || strpos($redirectUrlChange, 'reportes.php') !== false || strpos($redirectUrlChange, 'admin') !== false;
echo "   - ¿Redirigió al panel administrativo?: " . ($isRedirectedToDashboard ? "✔ SÍ" : "❌ NO") . "\n";

// 6. Verificar en base de datos el flag y auditoría
$stmtCheckFinal = $db->prepare("SELECT debe_cambiar_password FROM usuario WHERE correo = 'admin@chorifest.com'");
$stmtCheckFinal->execute();
$flagFinal = (int)$stmtCheckFinal->fetchColumn();
echo "   - Flag debe_cambiar_password en BD: $flagFinal (0 = Libre de cambio)\n";

$stmtAudit = $db->query("SELECT * FROM auditoria WHERE accion = 'CAMBIAR_PASSWORD' ORDER BY id_auditoria DESC LIMIT 1");
$auditRow = $stmtAudit->fetch(PDO::FETCH_ASSOC);
echo "   - Registro en auditoría: ID {$auditRow['id_auditoria']} | {$auditRow['accion']} | {$auditRow['descripcion']} | {$auditRow['fecha']}\n\n";

// Restaurar para facilitar tests futuros (o mantener conocida)
$hashRestore = password_hash('admin123', PASSWORD_DEFAULT);
$db->prepare("UPDATE usuario SET password_hash = ?, debe_cambiar_password = 0 WHERE correo = 'admin@chorifest.com'")->execute([$hashRestore]);
echo "✔ Entorno restaurado para credenciales estándar de desarrollo (admin@chorifest.com / admin123).\n";

@unlink($cookieFile);
