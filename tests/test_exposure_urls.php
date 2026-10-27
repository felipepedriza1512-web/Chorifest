<?php
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

echo "=== ESTADO DE EXPOSICIÓN POR URL (ACTUAL) ===\n";
foreach ($routes as $r) {
    $url = 'http://localhost/Chorifest' . $r;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo sprintf("%-26s => HTTP %d\n", $r, $code);
}
