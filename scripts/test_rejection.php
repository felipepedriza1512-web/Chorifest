<?php
// Simular que alguien intenta forzar chorifest real
putenv('CHORIFEST_DB_NAME=chorifest');
require_once __DIR__ . '/../tests/test_bootstrap.php';
require_once __DIR__ . '/../config/database.php';

$db = getDatabaseConnection();
assertTestDatabaseOnly($db);
echo "Esto no debería imprimirse nunca si la protección funciona.\n";
