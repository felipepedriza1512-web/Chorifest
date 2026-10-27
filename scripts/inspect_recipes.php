<?php
require_once __DIR__ . '/../config/database.php';
$db = getDatabaseConnection();
$tbls = $db->query("SELECT TABLE_NAME, TABLE_COLLATION FROM information_schema.tables WHERE TABLE_SCHEMA = 'chorifest'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($tbls as $t) {
    echo "{$t['TABLE_NAME']}: {$t['TABLE_COLLATION']}\n";
}
