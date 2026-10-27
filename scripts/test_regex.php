<?php
$raw = file_get_contents(__DIR__ . '/../storage/backups/backup_chorifest_fase3_previa.sql');
$content = mb_convert_encoding($raw, 'UTF-8', 'UTF-16LE');
echo 'Converted strlen: ' . strlen($content) . "\n";
echo 'strpos inventario_insumo: ' . var_export(strpos($content, 'inventario_insumo'), true) . "\n";
if (preg_match('/INSERT INTO `inventario_insumo` VALUES\s*(.*?);/s', $content, $m)) {
    echo "Found INSERT statement: " . $m[1] . "\n";
}
