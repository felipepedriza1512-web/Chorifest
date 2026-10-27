<?php
require_once __DIR__ . '/../config/database.php';
$db = getDatabaseConnection();

$backupPath = __DIR__ . '/../storage/backups/backup_chorifest_fase3_previa.sql';
$raw = file_get_contents($backupPath);
if (substr($raw, 0, 2) === "\xFF\xFE" || strpos($raw, "\x00") !== false) {
    $sql = mb_convert_encoding($raw, 'UTF-8', 'UTF-16LE');
} else {
    $sql = $raw;
}

// 1. Max id_movimiento in backup
echo "=== ANALISIS DE MOVIMIENTO_INVENTARIO EN BACKUP ===\n";
if (preg_match('/INSERT INTO `movimiento_inventario` VALUES\s*(.*?);/s', $sql, $matchMov)) {
    // Each row: (id_movimiento, id_insumo, id_pedido, tipo, cantidad, motivo, fecha)
    preg_match_all('/\(\s*(\d+)\s*,\s*(\d+)\s*,\s*([^,]+)\s*,\s*\'([^\']+)\'\s*,\s*([0-9\.]+)\s*,\s*\'([^\']*)\'\s*,\s*\'([^\']+)\'\s*\)/', $matchMov[1], $movRows, PREG_SET_ORDER);
    echo "Total movimientos en backup: " . count($movRows) . "\n";
    $maxBkId = 0;
    $bkMovs = [];
    foreach ($movRows as $r) {
        $id = (int)$r[1];
        if ($id > $maxBkId) $maxBkId = $id;
        $bkMovs[$id] = [
            'id_movimiento' => $id,
            'id_insumo' => (int)$r[2],
            'id_pedido' => trim($r[3]) === 'NULL' ? null : (int)$r[3],
            'tipo' => $r[4],
            'cantidad' => (float)$r[5],
            'motivo' => $r[6],
            'fecha' => $r[7]
        ];
    }
    echo "Max ID movimiento en backup: $maxBkId\n";
} else {
    echo "No se encontró INSERT INTO movimiento_inventario en el backup\n";
}

// 2. Movimientos en la BD actual
echo "\n=== MOVIMIENTOS EN BD ACTUAL ===\n";
$currMovs = $db->query("SELECT * FROM movimiento_inventario ORDER BY id_movimiento ASC")->fetchAll(PDO::FETCH_ASSOC);
echo "Total movimientos en BD actual: " . count($currMovs) . "\n";
$currMaxId = 0;
foreach ($currMovs as $cm) {
    if ((int)$cm['id_movimiento'] > $currMaxId) $currMaxId = (int)$cm['id_movimiento'];
}
echo "Max ID movimiento en BD actual: $currMaxId\n";

// Movimientos registrados después del backup (id_movimiento > $maxBkId)
$postBackupMovs = array_filter($currMovs, function($m) use ($maxBkId) {
    return (int)$m['id_movimiento'] > $maxBkId;
});
echo "Movimientos posteriores al backup (id > $maxBkId): " . count($postBackupMovs) . "\n";
foreach ($postBackupMovs as $pm) {
    echo "ID: {$pm['id_movimiento']} | Insumo: {$pm['id_insumo']} | Pedido: {$pm['id_pedido']} | Tipo: {$pm['tipo']} | Cant: {$pm['cantidad']} | Motivo: {$pm['motivo']} | Fecha: {$pm['fecha']}\n";
}

// Check pedidos in backup
echo "\n=== ANALISIS DE PEDIDOS EN BACKUP VS ACTUAL ===\n";
$bkOrders = [];
if (preg_match('/INSERT INTO `pedido` VALUES\s*(.*?);/s', $sql, $matchPed)) {
    // Matches all (id_pedido, id_cliente, fecha, total, id_estado, id_direccion, observaciones)
    preg_match_all('/\(\s*(\d+)\s*,\s*(\d+)\s*,\s*\'([^\']+)\'\s*,\s*([0-9\.]+)\s*,\s*(\d+)\s*,\s*([^,]+)\s*,\s*\'([^\']*)\'\s*\)/', $matchPed[1], $pedRows, PREG_SET_ORDER);
    echo "Total pedidos en backup: " . count($pedRows) . "\n";
    foreach ($pedRows as $pr) {
        $bkOrders[(int)$pr[1]] = [
            'id_pedido' => (int)$pr[1],
            'id_cliente' => (int)$pr[2],
            'fecha' => $pr[3],
            'total' => (float)$pr[4],
            'id_estado' => (int)$pr[5],
            'id_direccion' => trim($pr[6]) === 'NULL' ? null : (int)$pr[6],
            'observaciones' => $pr[7],
            'raw' => $pr[0]
        ];
    }
    $bkOrderIds = array_keys($bkOrders);
    echo "Rango de IDs de pedidos en backup: Min " . min($bkOrderIds) . " a Max " . max($bkOrderIds) . "\n";
    echo "Lista de IDs de pedidos en backup: " . implode(', ', $bkOrderIds) . "\n";
}

$currOrders = $db->query("SELECT * FROM pedido ORDER BY id_pedido ASC")->fetchAll(PDO::FETCH_ASSOC);
$currOrderIds = array_column($currOrders, 'id_pedido');
echo "Total pedidos en BD actual: " . count($currOrders) . "\n";
echo "Rango de IDs de pedidos en BD actual: Min " . (!empty($currOrderIds) ? min($currOrderIds) : 'N/A') . " a Max " . (!empty($currOrderIds) ? max($currOrderIds) : 'N/A') . "\n";
echo "Lista de IDs en BD actual: " . implode(', ', $currOrderIds) . "\n";

$missingOrdersFromBackup = array_diff(array_keys($bkOrders), $currOrderIds);
echo "\nPedidos que estaban en el backup pero FALTAN en la BD actual: " . (empty($missingOrdersFromBackup) ? "NINGUNO" : implode(', ', $missingOrdersFromBackup)) . "\n";
