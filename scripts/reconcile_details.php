<?php
require_once __DIR__ . '/../config/database.php';
$db = getDatabaseConnection();

$raw = file_get_contents(__DIR__ . '/../storage/backups/backup_chorifest_fase3_previa.sql');
$sql = (substr($raw, 0, 2) === "\xFF\xFE" || strpos($raw, "\x00") !== false) ? mb_convert_encoding($raw, 'UTF-8', 'UTF-16LE') : $raw;

// Parse inventario_insumo in backup
$bkStock = [];
if (preg_match('/INSERT INTO `inventario_insumo` VALUES\s*(.*?);/s', $sql, $m)) {
    preg_match_all('/\(\s*(\d+)\s*,\s*(\d+)\s*,\s*([0-9\.]+)/', $m[1], $rows, PREG_SET_ORDER);
    foreach ($rows as $r) {
        $bkStock[(int)$r[2]] = (float)$r[3];
    }
}

// Parse movimiento_inventario in backup
$bkMovs = [];
$maxBkMovId = 0;
if (preg_match('/INSERT INTO `movimiento_inventario` VALUES\s*(.*?);/s', $sql, $m)) {
    // Schema: id_movimiento, id_insumo, id_producto, id_usuario, tipo, cantidad, motivo, fecha
    // Wait, let's parse using a tokenizer or regex
    preg_match_all('/\(\s*(\d+)\s*,\s*([^,]+)\s*,\s*([^,]+)\s*,\s*([^,]+)\s*,\s*\'([^\']+)\'\s*,\s*([0-9\.\-]+)\s*,\s*\'([^\']*)\'\s*,\s*\'([^\']+)\'\s*\)/', $m[1], $rows, PREG_SET_ORDER);
    foreach ($rows as $r) {
        $id = (int)$r[1];
        if ($id > $maxBkMovId) $maxBkMovId = $id;
        $bkMovs[$id] = [
            'id_movimiento' => $id,
            'id_insumo' => trim($r[2]) === 'NULL' ? null : (int)$r[2],
            'id_producto' => trim($r[3]) === 'NULL' ? null : (int)$r[3],
            'id_usuario' => trim($r[4]) === 'NULL' ? null : (int)$r[4],
            'tipo' => $r[5],
            'cantidad' => (float)$r[6],
            'motivo' => $r[7],
            'fecha' => $r[8]
        ];
    }
}

// Parse pedido in backup
$bkOrders = [];
$maxBkOrderId = 0;
if (preg_match('/INSERT INTO `pedido` VALUES\s*(.*?);/s', $sql, $m)) {
    // Schema: id_pedido, id_cliente, id_direccion, id_zona, id_estado, fecha, subtotal, costo_domicilio, total, observaciones
    preg_match_all('/\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*,\s*([^,]+)\s*,\s*(\d+)\s*,\s*\'([^\']+)\'\s*,\s*([0-9\.]+)\s*,\s*([0-9\.]+)\s*,\s*([0-9\.]+)\s*,\s*\'([^\']*)\'\s*\)/', $m[1], $rows, PREG_SET_ORDER);
    foreach ($rows as $r) {
        $id = (int)$r[1];
        if ($id > $maxBkOrderId) $maxBkOrderId = $id;
        $bkOrders[$id] = [
            'id_pedido' => $id,
            'id_cliente' => (int)$r[2],
            'id_direccion' => (int)$r[3],
            'id_zona' => trim($r[4]) === 'NULL' ? null : (int)$r[4],
            'id_estado' => (int)$r[5],
            'fecha' => $r[6],
            'subtotal' => (float)$r[7],
            'costo_domicilio' => (float)$r[8],
            'total' => (float)$r[9],
            'observaciones' => $r[10]
        ];
    }
}

echo "========================================================================\n";
echo "1. PEDIDOS EN BACKUP VS PEDIDOS EN BD ACTUAL\n";
echo "========================================================================\n";
echo "Total pedidos en backup: " . count($bkOrders) . " (IDs: " . min(array_keys($bkOrders)) . " al " . max(array_keys($bkOrders)) . ")\n";

$currOrders = $db->query("SELECT p.id_pedido, p.id_cliente, p.id_estado, ep.nombre as estado, p.fecha, p.total, p.observaciones FROM pedido p JOIN estado_pedido ep ON p.id_estado = ep.id_estado ORDER BY p.id_pedido ASC")->fetchAll(PDO::FETCH_ASSOC);
$currOrdersById = [];
foreach ($currOrders as $co) {
    $currOrdersById[(int)$co['id_pedido']] = $co;
}
echo "Total pedidos en BD actual: " . count($currOrders) . " (IDs: " . min(array_keys($currOrdersById)) . " al " . max(array_keys($currOrdersById)) . ")\n";

$missingFromBackup = array_diff(array_keys($bkOrders), array_keys($currOrdersById));
if (!empty($missingFromBackup)) {
    echo "ALERTA: Hay pedidos del backup que NO están en la BD actual: " . implode(', ', $missingFromBackup) . "\n";
} else {
    echo "✔ Ningún pedido del backup fue borrado. Todos los pedidos del backup (IDs 1 al " . max(array_keys($bkOrders)) . ") existen intactos.\n";
}

echo "\nPedidos en BD actual que NO estaban en el backup (creados después):\n";
$createdPostBackup = array_diff(array_keys($currOrdersById), array_keys($bkOrders));
if (empty($createdPostBackup)) {
    echo "Ninguno existente actualmente.\n";
} else {
    foreach ($createdPostBackup as $pid) {
        $po = $currOrdersById[$pid];
        echo "ID $pid | Cliente: {$po['id_cliente']} | Estado: {$po['estado']} | Total: \${$po['total']} | Obs: {$po['observaciones']} | Fecha: {$po['fecha']}\n";
    }
}

echo "\n¿Qué pasó con los pedidos id >= 74 que clean_test_orders.php borró?\n";
echo "El backup llegaba hasta el pedido ID " . max(array_keys($bkOrders)) . ".\n";
echo "Por tanto, los pedidos >= 74 NO existían en el backup_chorifest_fase3_previa.sql.\n";

echo "\n========================================================================\n";
echo "2. MOVIMIENTOS Y CONCILIACIÓN DE INSUMOS\n";
echo "========================================================================\n";
echo "Max ID movimiento en backup: $maxBkMovId\n";

$currMovs = $db->query("SELECT m.*, i.nombre as insumo_nombre FROM movimiento_inventario m LEFT JOIN insumo i ON m.id_insumo = i.id_insumo ORDER BY m.id_movimiento ASC")->fetchAll(PDO::FETCH_ASSOC);
$postBackupMovs = array_filter($currMovs, function($m) use ($maxBkMovId) {
    return (int)$m['id_movimiento'] > $maxBkMovId;
});

echo "Movimientos registrados después del backup (id_movimiento > $maxBkMovId): " . count($postBackupMovs) . "\n";
foreach ($postBackupMovs as $pm) {
    echo "ID {$pm['id_movimiento']} | Insumo {$pm['id_insumo']} ({$pm['insumo_nombre']}) | Pedido: " . ($pm['id_pedido'] ?? 'N/A') . " | Tipo: {$pm['tipo']} | Cantidad: {$pm['cantidad']} | Motivo: {$pm['motivo']} | Fecha: {$pm['fecha']}\n";
}

// Insumos current stock
$insumos = $db->query("SELECT i.id_insumo, i.nombre, i.unidad_medida, COALESCE(inv.cantidad, 0) as stock_actual FROM insumo i LEFT JOIN inventario_insumo inv ON i.id_insumo = inv.id_insumo ORDER BY i.id_insumo ASC")->fetchAll(PDO::FETCH_ASSOC);

echo "\n========================================================================================================================\n";
echo sprintf("%-3s | %-20s | %-8s | %-12s | %-12s | %-12s | %s\n", "ID", "Insumo", "Unidad", "Stock Backup", "Stock Actual", "Diferencia", "Movimientos Post-Backup por Tipo");
echo "========================================================================================================================\n";

foreach ($insumos as $ins) {
    $id = (int)$ins['id_insumo'];
    $sBk = $bkStock[$id] ?? 0;
    $sAct = (float)$ins['stock_actual'];
    $diff = $sAct - $sBk;
    
    // Group movements for this insumo post backup
    $movsInsumo = array_filter($postBackupMovs, function($m) use ($id) {
        return (int)$m['id_insumo'] === $id;
    });
    
    $typesSummary = [];
    foreach ($movsInsumo as $mi) {
        $t = $mi['tipo'];
        if (!isset($typesSummary[$t])) $typesSummary[$t] = 0;
        $typesSummary[$t] += (float)$mi['cantidad'];
    }
    
    $summaryStr = [];
    foreach ($typesSummary as $t => $qty) {
        $summaryStr[] = "$t: " . number_format($qty, 3);
    }
    $summaryText = empty($summaryStr) ? "(Sin movs post-backup en BD)" : implode(', ', $summaryStr);
    
    echo sprintf("%-3d | %-20s | %-8s | %-12s | %-12s | %-12s | %s\n",
        $id,
        $ins['nombre'],
        $ins['unidad_medida'],
        number_format($sBk, 3),
        number_format($sAct, 3),
        number_format($diff, 3),
        $summaryText
    );
}
echo "========================================================================================================================\n";
