<?php
/**
 * Script PHP para ejecutar migraciones y rollbacks de forma segura
 * Uso CLI: php sql/run_migration.php [chorifest|chorifest_test] [migrate|rollback] [reportes|liquidacion]
 */
require_once __DIR__ . '/../config/database.php';

$dbName  = $argv[1] ?? 'chorifest_test';
$action  = $argv[2] ?? 'migrate';
$target  = $argv[3] ?? 'reportes';

if (!in_array($dbName, ['chorifest', 'chorifest_test'])) {
    die("DB must be chorifest or chorifest_test\n");
}

$files = [
    'migrate'  => ['reportes' => 'migration_fase5_reportes.sql',    'liquidacion' => 'migration_fase5_liquidacion.sql'],
    'rollback' => ['reportes' => 'rollback_fase5_reportes.sql',     'liquidacion' => 'rollback_fase5_liquidacion.sql'],
];

$sqlFile = __DIR__ . '/' . ($files[$action][$target] ?? '');
if (!file_exists($sqlFile)) {
    die("File not found: $sqlFile\n");
}

$pdo = getDatabaseConnection($dbName);
$sql = file_get_contents($sqlFile);

// Normalize line endings then split on semicolons
$sql = str_replace("\r\n", "\n", $sql);
$rawStmts = explode(';', $sql);
$statements = array_filter(
    array_map('trim', $rawStmts),
    function($s) {
        if ($s === '') return false;
        // Skip if only comments/whitespace
        $lines = array_filter(array_map('trim', explode("\n", $s)));
        $nonComment = array_filter($lines, fn($l) => !str_starts_with($l, '--'));
        return !empty($nonComment);
    }
);

$errors = [];
foreach ($statements as $stmt) {
    try {
        $pdo->exec($stmt);
        echo "OK: " . substr(str_replace("\n", ' ', $stmt), 0, 80) . "\n";
    } catch (PDOException $e) {
        $msg = "ERR [{$e->getCode()}]: " . $e->getMessage() . "\n  SQL: " . substr($stmt, 0, 120);
        echo $msg . "\n";
        $errors[] = $msg;
    }
}

if (empty($errors)) {
    echo "\n✅ $action/$target on $dbName: ALL OK\n";
} else {
    echo "\n❌ $action/$target on $dbName: " . count($errors) . " error(s)\n";
    exit(1);
}
