<?php
$baseUrl = 'http://localhost/Chorifest';
$cookieFile = __DIR__ . '/test_cookie_debug.txt';
if (file_exists($cookieFile)) @unlink($cookieFile);

// Login as admin
$ch = curl_init("$baseUrl/controllers/auth/login.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'action' => 'login',
    'email' => 'admin@gmail.com',
    'password' => '123456'
]));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$body = curl_exec($ch);
$url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Login result: HTTP $code | URL: $url\n";
echo "Cookie file size: " . filesize($cookieFile) . " bytes\n";
echo "Cookie contents:\n" . file_get_contents($cookieFile) . "\n";

// Now request dashboard.php
$ch = curl_init("$baseUrl/controllers/admin/dashboard.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$dashBody = curl_exec($ch);
$dashCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Dashboard request: HTTP $dashCode\n";
if ($dashCode !== 200) {
    echo "Body sample:\n" . substr($dashBody, 0, 300) . "\n";
}
