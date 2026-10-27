<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UserModel.php';

$db = getDatabaseConnection();
$m = new UserModel($db);

$emails = [
    'admin@gmail.com',
    'admin@chorifest.com',
    'cocina@gmail.com',
    'cocina@chorifest.com',
    'domi@gmail.com',
    'domi@chorifest.com',
    'juan@gmail.com',
    'cliente@chorifest.com'
];

$passwords = ['123456', 'admin123', 'admin', 'password', '12345678', 'chorifest123', 'cocina123', 'domi123'];

foreach ($emails as $e) {
    $found = false;
    foreach ($passwords as $p) {
        $res = $m->login($e, $p);
        if ($res['success']) {
            echo "LOGIN OK: $e con clave '$p' (Rol: {$res['user']['rol']})\n";
            $found = true;
            break;
        }
    }
    if (!$found) {
        echo "NO MATCH: $e\n";
    }
}
