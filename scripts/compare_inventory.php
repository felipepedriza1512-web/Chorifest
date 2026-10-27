<?php
require_once __DIR__ . '/../config/database.php';

$backupPath = __DIR__ . '/../storage/backups/backup_chorifest_fase3_previa.sql';
$raw = file_get_contents($backupPath);
if (substr($raw, 0, 2) === "\xFF\xFE" || strpos($raw, "\x00") !== false) {
    $sql = mb_convert_encoding($raw, 'UTF-8', 'UTF-16LE');
} else {
    $sql = $raw;
}

// Extract inventario_insumo from backup
$backupStock = [];
if (preg_match('/INSERT INTO `inventario_insumo` VALUES\s*(.*?);/s', $sql, $matchInv)) {
    preg_match_all('/\(\s*(\d+)\s*,\s*(\d+)\s*,\s*([0-9\.]+)/', $matchInv[1], $rows, PREG_SET_ORDER);
    foreach ($rows as $r) {
        $backupStock[(int)$r[2]] = (float)$r[3];
    }
}

$db = getDatabaseConnection();
$insumos = $db->query("SELECT i.id_insumo, i.nombre, i.unidad_medida, i.stock_minimo, COALESCE(inv.cantidad, 0) as cantidad_actual 
                       FROM insumo i 
                       LEFT JOIN inventario_insumo inv ON i.id_insumo = inv.id_insumo 
                       ORDER BY i.id_insumo ASC")->fetchAll(PDO::FETCH_ASSOC);

echo "=========================================================================================================\n";
echo sprintf("%-4s | %-25s | %-8s | %-12s | %-12s | %-12s | %s\n", "ID", "Insumo", "Unidad", "Stock Backup", "Stock Actual", "Diferencia", "Estado");
echo "=========================================================================================================\n";

$restoreQueries = [];
foreach ($insumos as $ins) {
    $id = (int)$ins['id_insumo'];
    $bkQty = isset($backupStock[$id]) ? (float)$backupStock[$id] : null;
    $currQty = (float)$ins['cantidad_actual'];
    
    $diff = ($bkQty !== null) ? ($currQty - $bkQty) : 0;
    $status = ($bkQty !== null && abs($diff) < 0.0001) ? "OK (Intacto)" : "ALTERADO";
    
    echo sprintf("%-4d | %-25s | %-8s | %-12s | %-12s | %-12s | %s\n",
        $id,
        $ins['nombre'],
        $ins['unidad_medida'],
        $bkQty !== null ? number_format($bkQty, 3) : 'N/A',
        number_format($currQty, 3),
        $bkQty !== null ? number_format($diff, 3) : 'N/A',
        $status
    );

    if ($bkQty !== null && abs($diff) >= 0.0001) {
        $restoreQueries[] = "UPDATE inventario_insumo SET cantidad = " . number_format($bkQty, 3, '.', '') . " WHERE id_insumo = {$id}; -- {$ins['nombre']}: actual {$currQty} -> original {$bkQty}";
    }
}

echo "=========================================================================================================\n\n";
echo "SENTENCIAS SQL EXACTAS PARA RESTAURAR STOCK A SU ESTADO PREVIO (backup_chorifest_fase3_previa.sql):\n";
echo "----------------------------------------------------------------------------------------------------\n";
foreach ($restoreQueries as $q) {
    echo $q . "\n";
}

// Check orders >= 70
$stmtOrders = $db->query("SELECT p.id_pedido, p.fecha, p.total, ep.nombre as estado, p.observaciones 
                         FROM pedido p 
                         JOIN estado_pedido ep ON p.id_estado = ep.id_estado 
                         WHERE p.id_pedido >= 70
                         ORDER BY p.id_pedido ASC");
$testOrders = $stmtOrders->fetchAll(PDO::FETCH_ASSOC);
echo "\nPEDIDOS CREADOS POR PRUEBAS (ID >= 70):\n";
echo "----------------------------------------\n";
if (empty($testOrders)) {
    echo "No hay pedidos con ID >= 70.\n";
} else {
    foreach ($testOrders as $o) {
        echo "Pedido #{$o['id_pedido']} | Fecha: {$o['fecha']} | Estado: {$o['estado']} | Total: \${$o['total']} | Obs: {$o['observaciones']}\n";
    }
}

// Check test movements
$stmtMov = $db->query("SELECT m.id_movimiento, m.id_insumo, i.nombre, m.tipo, m.cantidad, m.motivo, m.fecha, m.id_pedido 
                      FROM movimiento_inventario m 
                      JOIN insumo i ON m.id_insumo = i.id_insumo 
                      WHERE m.id_movimiento >= 75 OR m.motivo LIKE '%prueba%' OR m.motivo LIKE '%test%' OR m.motivo LIKE '%Fase%' OR m.motivo LIKE '%Restauración%' OR m.motivo LIKE '%Merma%' OR m.motivo LIKE '%Reposición%'
                      ORDER BY m.id_movimiento ASC");
$testMov = $stmtMov->fetchAll(PDO::FETCH_ASSOC);
echo "\nMOVIMIENTOS DE INVENTARIO ASOCIADOS A PRUEBAS/AJUSTES RECIENTES:\n";
echo "----------------------------------------------------------------\n";
foreach ($testMov as $m) {
    echo "#{$m['id_movimiento']} | Insumo: {$m['nombre']} | Tipo: {$m['tipo']} | Cant: {$m['cantidad']} | Pedido: " . ($m['id_pedido'] ?? 'N/A') . " | Motivo: {$m['motivo']}\n";
}
