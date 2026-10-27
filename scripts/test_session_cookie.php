<?php
$cookieFile = __DIR__ . '/test_cookie.txt';
@unlink($cookieFile);

$baseUrl = 'http://localhost/Chorifest';

// Step 1: GET login
$ch = curl_init("$baseUrl/controllers/auth/login.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_HEADER, true);
$res1 = curl_exec($ch);
curl_close($ch);

echo "COOKIES AFTER GET LOGIN:\n";
echo file_get_contents($cookieFile) . "\n";

preg_match('/name="csrf_token"\s+value="([^"]+)"/', $res1, $m);
$csrf = $m[1] ?? '';
echo "CSRF: $csrf\n";

// Step 2: POST login
$ch = curl_init("$baseUrl/controllers/auth/login.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'action' => 'login',
    'email' => 'admin@gmail.com',
    'password' => '123456',
    'csrf_token' => $csrf
]));
$res2 = curl_exec($ch);
curl_close($ch);

echo "HEADERS AFTER POST LOGIN:\n";
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
echo substr($res2, 0, 500) . "\n";

echo "COOKIES AFTER POST LOGIN:\n";
echo file_get_contents($cookieFile) . "\n";
