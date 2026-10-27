<?php
/**
 * Prueba de login de los 4 roles con formularios reales (HTTP POST y Sesión Real)
 */

$baseUrl = 'http://localhost/Chorifest';

function testRoleLogin($email, $password, $expectedRoleName, $expectedPanelKeyword, $baseUrl) {
    $cookieFile = __DIR__ . "/../storage/cookie_test_" . preg_replace('/[^a-z0-9]/', '_', $email) . ".txt";
    @unlink($cookieFile);

    // Enviar POST con el formulario real de login y seguir redirección
    $ch = curl_init("$baseUrl/controllers/auth/login.php");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'action' => 'login',
        'email' => $email,
        'password' => $password
    ]));

    $response = curl_exec($ch);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $finalCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    @unlink($cookieFile);

    $hasKeyword = (stripos($response, $expectedPanelKeyword) !== false);
    $success = ($finalCode === 200 && $hasKeyword);

    printf("%-15s | %-20s | %-45s | HTTP %-3d | %s\n",
        $expectedRoleName,
        $email,
        basename($finalUrl),
        $finalCode,
        $success ? "✔ AUTENTICADO Y EN SU PANEL" : "❌ FALLÓ"
    );

    return $success;
}

echo "====================================================================================================================\n";
echo "LOGIN REAL DE LOS 4 ROLES CON FORMULARIOS HTML (HTTP POST + SESIONES SEGURAS):\n";
echo "====================================================================================================================\n";
printf("%-15s | %-20s | %-45s | %-8s | %s\n", "Rol", "Correo de Prueba", "Página de Destino", "Estado", "Resultado");
echo str_repeat('-', 120) . "\n";

testRoleLogin('admin@gmail.com', '123456', 'Administrador', 'Dashboard', $baseUrl);
testRoleLogin('cocina@gmail.com', '123456', 'Cocinera', 'Cocina', $baseUrl);
testRoleLogin('domi@gmail.com', '123456', 'Domiciliario', 'Rutas', $baseUrl);
testRoleLogin('juan@gmail.com', '123456', 'Cliente', 'Catálogo', $baseUrl);

echo "====================================================================================================================\n";
