<?php
require_once __DIR__ . '/../config/database.php';
$db = getDatabaseConnection();

$backupFile = __DIR__ . '/../storage/backups/backup_chorifest_fase3_previa.sql';
$content = file_get_contents($backupFile);

// Extract insumo insert statements
echo "=== INSUMOS EN BACKUP ===\n";
if (preg_match_all('/INSERT INTO `?insumo`?\s*\(([^)]+)\)\s*VALUES\s*(.+?);/is', $content, $matches, PREG_SET_ORDER)) {
    foreach ($matches as $m) {
        echo "Cols: " . $m[1] . "\n";
        echo "Values: " . substr($m[2], 0, 300) . "...\n";
    }
} else if (preg_match_all('/INSERT INTO `?insumo`?\s*VALUES\s*(.+?);/is', $content, $matches, PREG_SET_ORDER)) {
    foreach ($matches as $m) {
        echo "Values: " . substr($m[1], 0, 300) . "...\n";
    }
}

// Extract pedido insert statements
echo "\n=== PEDIDOS EN BACKUP ===\n";
if (preg_match_all('/INSERT INTO `?pedido`?\s*(\([^)]+\))?\s*VALUES\s*(.+?);/is', $content, $matches, PREG_SET_ORDER)) {
    foreach ($matches as $m) {
        echo "Cols: " . ($m[1] ?: 'DEFAULT') . "\n";
        echo "Values: " . substr($m[2], 0, 300) . "...\n";
    }
}
