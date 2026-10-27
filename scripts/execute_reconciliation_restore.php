<?php
require_once __DIR__ . '/../config/database.php';
$db = getDatabaseConnection();

$backupPath = __DIR__ . '/../storage/backups/backup_chorifest_fase3_previa.sql';
$raw = file_get_contents($backupPath);
$sql = (substr($raw, 0, 2) === "\xFF\xFE" || strpos($raw, "\x00") !== false) ? mb_convert_encoding($raw, 'UTF-8', 'UTF-16LE') : $raw;

// 1. Stock in backup
$bkStock = [];
if (preg_match('/INSERT INTO `inventario_insumo` VALUES\s*(.*?);/s', $sql, $m)) {
    preg_match_all('/\(\s*(\d+)\s*,\s*(\d+)\s*,\s*([0-9\.]+)/', $m[1], $rows, PREG_SET_ORDER);
    foreach ($rows as $r) {
        $bkStock[(int)$r[2]] = (float)$r[3];
    }
}

// 2. Fetch all movements registered after backup (backup max id_movimiento was 10)
$maxBkMovId = 10;
$postMovs = $db->query("SELECT * FROM movimiento_inventario WHERE id_movimiento > $maxBkMovId ORDER BY id_movimiento ASC")->fetchAll(PDO::FETCH_ASSOC);

// 3. Current stock
$insumos = $db->query("SELECT i.id_insumo, i.nombre, i.unidad_medida, COALESCE(inv.cantidad, 0) as stock_actual 
                       FROM insumo i 
                       LEFT JOIN inventario_insumo inv ON i.id_insumo = inv.id_insumo 
                       ORDER BY i.id_insumo ASC")->fetchAll(PDO::FETCH_ASSOC);

echo "========================================================================================================================\n";
echo "CONCILIACIÓN PREVIA A LA RESTAURACIÓN (10 INSUMOS)\n";
echo "========================================================================================================================\n";
printf("%-3s | %-20s | %-6s | %-12s | %-12s | %-12s | %s\n", "ID", "Insumo", "Unidad", "Stock Backup", "Stock Actual", "Diferencia", "Movimientos Post-Backup en BD");
echo str_repeat('-', 120) . "\n";

$changesNeeded = [];

foreach ($insumos as $ins) {
    $id = (int)$ins['id_insumo'];
    $sBk = $bkStock[$id] ?? 0;
    $sAct = (float)$ins['stock_actual'];
    $diff = $sAct - $sBk;
    
    // Group movements
    $mList = array_filter($postMovs, function($m) use ($id) {
        return (int)$m['id_insumo'] === $id;
    });
    $types = [];
    foreach ($mList as $ml) {
        $t = $ml['tipo'];
        if (!isset($types[$t])) $types[$t] = 0;
        $types[$t] += (float)$ml['cantidad'];
    }
    $tStr = [];
    foreach ($types as $t => $qty) {
        $tStr[] = "$t: " . number_format($qty, 3);
    }
    $movSummary = empty($tStr) ? "(Sin movs activos en BD)" : implode(', ', $tStr);
    
    printf("%-3d | %-20s | %-6s | %-12s | %-12s | %-12s | %s\n",
        $id,
        $ins['nombre'],
        $ins['unidad_medida'],
        number_format($sBk, 3),
        number_format($sAct, 3),
        number_format($diff, 3),
        $movSummary
    );

    if (abs($diff) > 0.0001) {
        $changesNeeded[] = [
            'id_insumo' => $id,
            'nombre' => $ins['nombre'],
            'unidad' => $ins['unidad_medida'],
            'stock_anterior' => $sAct,
            'stock_nuevo' => $sBk,
            'diferencia' => $sBk - $sAct // lo que se suma para restaurar
        ];
    }
}
echo "========================================================================================================================\n\n";

echo "EJECUTANDO RESTAURACIÓN ATÓMICA BAJO TRANSACCIÓN...\n";
$db->beginTransaction();

try {
    foreach ($insumos as $ins) {
        $id = (int)$ins['id_insumo'];
        $sBk = $bkStock[$id] ?? 0;
        $sAct = (float)$ins['stock_actual'];
        $diffToRestore = $sBk - $sAct;

        // Actualizar inventario_insumo a valor exacto del backup
        $stmtUpd = $db->prepare("UPDATE inventario_insumo SET cantidad = ?, fecha_actualizacion = NOW() WHERE id_insumo = ?");
        $stmtUpd->execute([$sBk, $id]);

        if (abs($diffToRestore) > 0.0001) {
            // Dejar movimiento tipo AJUSTE con el motivo solicitado
            $stmtMov = $db->prepare("INSERT INTO movimiento_inventario (id_insumo, id_usuario, tipo, cantidad, motivo, fecha) VALUES (?, 14, 'AJUSTE', ?, 'Restauración tras pruebas de la fase 3', NOW())");
            $stmtMov->execute([$id, $diffToRestore]);
            echo "  ✔ Insumo #{$id} ({$ins['nombre']}): actualizado de " . number_format($sAct, 3) . " a " . number_format($sBk, 3) . " {$ins['unidad_medida']} (Movimiento AJUSTE: " . ($diffToRestore > 0 ? '+' : '') . number_format($diffToRestore, 3) . ").\n";
        } else {
            echo "  - Insumo #{$id} ({$ins['nombre']}): ya coincidía exactamente con el backup (" . number_format($sBk, 3) . " {$ins['unidad_medida']}). Verificado y mantenido.\n";
        }
    }

    $db->commit();
    echo "\n✔ Transacción confirmada exitosamente (COMMIT).\n\n";
} catch (Exception $e) {
    $db->rollBack();
    die("❌ ERROR: Transacción revertida (ROLLBACK): " . $e->getMessage() . "\n");
}

// 4. Mostrar stock final verificado
echo "=====================================================================================\n";
echo "STOCK FINAL VERIFICADO EN BD (10 INSUMOS RESTAURADOS):\n";
echo "=====================================================================================\n";
$finalInsumos = $db->query("SELECT i.id_insumo, i.nombre, i.unidad_medida, i.stock_minimo, COALESCE(inv.cantidad, 0) as stock_final 
                            FROM insumo i 
                            LEFT JOIN inventario_insumo inv ON i.id_insumo = inv.id_insumo 
                            ORDER BY i.id_insumo ASC")->fetchAll(PDO::FETCH_ASSOC);

printf("%-3s | %-20s | %-8s | %-12s | %-12s | %s\n", "ID", "Insumo", "Unidad", "Stock Final", "Stock Min", "Estado vs Backup");
echo str_repeat('-', 85) . "\n";
foreach ($finalInsumos as $fi) {
    $id = (int)$fi['id_insumo'];
    $sBk = $bkStock[$id];
    $sFin = (float)$fi['stock_final'];
    $match = abs($sFin - $sBk) < 0.0001 ? "EXACTO (100% OK)" : "DISCREPANCIA";
    printf("%-3d | %-20s | %-8s | %-12s | %-12s | %s\n",
        $id,
        $fi['nombre'],
        $fi['unidad_medida'],
        number_format($sFin, 3),
        number_format((float)$fi['stock_minimo'], 3),
        $match
    );
}
echo "=====================================================================================\n";
