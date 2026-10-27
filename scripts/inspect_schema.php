<?php
$raw = file_get_contents(__DIR__ . '/../storage/backups/backup_chorifest_fase3_previa.sql');
$sql = (substr($raw, 0, 2) === "\xFF\xFE" || strpos($raw, "\x00") !== false) ? mb_convert_encoding($raw, 'UTF-8', 'UTF-16LE') : $raw;

function printTableSchemaAndData($tableName, $sql) {
    echo "================= TABLE $tableName =================\n";
    if (preg_match("/CREATE TABLE `$tableName` \((.*?)\) ENGINE/s", $sql, $m)) {
        echo "SCHEMA:\n" . $m[1] . "\n\n";
    }
    if (preg_match("/INSERT INTO `$tableName` VALUES (.*?);/s", $sql, $m)) {
        echo "INSERT VALUES (preview first 500 chars):\n" . substr($m[1], 0, 500) . "\n\n";
    }
}

printTableSchemaAndData('movimiento_inventario', $sql);
printTableSchemaAndData('pedido', $sql);
printTableSchemaAndData('inventario_insumo', $sql);
