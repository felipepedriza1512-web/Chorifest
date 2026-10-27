<?php
$cookieFile = __DIR__ . '/test_cookie.txt';
@unlink($cookieFile);

$baseUrl = 'http://localhost/Chorifest';

// 1. Iniciar sesión vía POST con cURL manteniendo la cookie
$ch = curl_init("$baseUrl/controllers/auth/login.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Dejar que cURL siga la redirección automáticamente
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'action' => 'login',
    'email' => 'admin@gmail.com',
    'password' => '123456'
]));

$resp = curl_exec($ch);
$finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Final URL: $finalUrl\n";
echo "HTTP Code: $code\n";
echo "Body snippet: " . substr(strip_tags($resp), 0, 300) . "\n";
