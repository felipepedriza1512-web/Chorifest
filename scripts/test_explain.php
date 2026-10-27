<?php
require_once __DIR__ . '/../config/database.php';
$db = getDatabaseConnection();

echo "=== SHOW INDEX FROM historial_pedido ===\n";
$stmt = $db->query("SHOW INDEX FROM historial_pedido");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $idx) {
    echo "{$idx['Key_name']} | Seq: {$idx['Seq_in_index']} | Column: {$idx['Column_name']}\n";
}

echo "\n=== EXPLAIN QUERY ACTUAL ===\n";
$sqlActual = "
    EXPLAIN SELECT 
        p.id_pedido,
        p.id_cliente,
        COALESCE(u.nombre, 'Cliente Mostrador') AS cliente_nombre,
        p.id_direccion,
        p.id_zona,
        p.nombre_zona,
        p.id_estado,
        p.subtotal,
        p.costo_domicilio,
        p.total,
        p.fecha AS fecha_creacion,
        COALESCE(h.fecha_entrega, p.fecha) AS fecha_venta,
        COALESCE(pg.metodo, 'EFECTIVO') AS metodo_pago,
        pg.estado AS estado_pago,
        COALESCE(pg.pendiente_devolucion, 0) AS pendiente_devolucion
    FROM pedido p
    INNER JOIN (
        SELECT id_pedido, MIN(fecha) AS fecha_entrega
        FROM historial_pedido
        WHERE id_estado = 6
        GROUP BY id_pedido
    ) h ON p.id_pedido = h.id_pedido
    LEFT JOIN pago pg ON p.id_pedido = pg.id_pedido
    LEFT JOIN usuario u ON p.id_cliente = u.id_usuario
    WHERE p.id_estado = 6
      AND COALESCE(h.fecha_entrega, p.fecha) >= '2026-08-01 00:00:00'
      AND COALESCE(h.fecha_entrega, p.fecha) <= '2026-10-31 23:59:59'
    ORDER BY fecha_venta ASC
";
$stmtExp = $db->query($sqlActual);
$rows = $stmtExp->fetchAll(PDO::FETCH_ASSOC);
echo str_pad("id", 4) . str_pad("select_type", 16) . str_pad("table", 18) . str_pad("type", 10) . str_pad("possible_keys", 40) . str_pad("key", 32) . str_pad("rows", 8) . str_pad("Extra", 30) . "\n";
echo str_repeat("-", 150) . "\n";
foreach ($rows as $r) {
    echo str_pad($r['id'] ?? '', 4) . 
         str_pad($r['select_type'] ?? '', 16) . 
         str_pad($r['table'] ?? '', 18) . 
         str_pad($r['type'] ?? '', 10) . 
         str_pad($r['possible_keys'] ?? '', 40) . 
         str_pad($r['key'] ?? '', 32) . 
         str_pad($r['rows'] ?? '', 8) . 
         ($r['Extra'] ?? '') . "\n";
}

echo "\n=== CREANDO ÍNDICE COMPUESTO OPTIMIZADO (id_estado, id_pedido, fecha) ===\n";
try {
    $db->exec("CREATE INDEX idx_hist_estado_pedido_fecha ON historial_pedido (id_estado, id_pedido, fecha)");
} catch (Exception $e) {
    // Ya existe
}

echo "\n=== EXPLAIN CON NUEVO ÍNDICE (MISMA CONSULTA) ===\n";
$stmtExp2 = $db->query($sqlActual);
$rows2 = $stmtExp2->fetchAll(PDO::FETCH_ASSOC);
echo str_pad("id", 4) . str_pad("select_type", 16) . str_pad("table", 18) . str_pad("type", 10) . str_pad("possible_keys", 40) . str_pad("key", 32) . str_pad("rows", 8) . str_pad("Extra", 30) . "\n";
echo str_repeat("-", 150) . "\n";
foreach ($rows2 as $r) {
    echo str_pad($r['id'] ?? '', 4) . 
         str_pad($r['select_type'] ?? '', 16) . 
         str_pad($r['table'] ?? '', 18) . 
         str_pad($r['type'] ?? '', 10) . 
         str_pad($r['possible_keys'] ?? '', 40) . 
         str_pad($r['key'] ?? '', 32) . 
         str_pad($r['rows'] ?? '', 8) . 
         ($r['Extra'] ?? '') . "\n";
}

