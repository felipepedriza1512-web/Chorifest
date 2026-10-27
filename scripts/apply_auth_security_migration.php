<?php
require_once __DIR__ . '/../config/database.php';

$sql = file_get_contents(__DIR__ . '/../database/migrations/migration_auth_security.sql');

foreach (['chorifest', 'chorifest_test'] as $dbName) {
    echo "Aplicando migración a $dbName...\n";
    $db = getDatabaseConnection($dbName);
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            $db->exec($stmt);
        }
    }
    echo "  ✔ Migración aplicada con éxito a $dbName.\n";
}
