<?php
$urls = [
    'http://localhost/Chorifest/.git/config',
    'http://localhost/Chorifest/config/database.php',
    'http://localhost/Chorifest/models/UserModel.php',
    'http://localhost/Chorifest/database/',
    'http://localhost/Chorifest/docs/',
    'http://localhost/Chorifest/README.md',
    'http://localhost/Chorifest/views/'
];

foreach ($urls as $u) {
    $ch = curl_init($u);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "$u => HTTP $code\n";
}
