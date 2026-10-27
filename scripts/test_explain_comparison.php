<?php
require_once __DIR__ . '/../config/database.php';
$db = getDatabaseConnection();

$countOrders = $db->query("SELECT COUNT(*) FROM pedido")->fetchColumn();
$countHist = $db->query("SELECT COUNT(*) FROM historial_pedido")->fetchColumn();

echo "Estadísticas actuales:\n";
echo "- Total pedidos en base de datos: $countOrders\n";
echo "- Total filas en historial_pedido: $countHist\n\n";

// Query BEFORE (forcing standard previous index idx_id_estado or ignoring idx_hist_estado_pedido_fecha)
$sqlBefore = "
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
        FROM historial_pedido IGNORE INDEX (idx_hist_estado_pedido_fecha)
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

$sqlAfter = "
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
        FROM historial_pedido FORCE INDEX (idx_hist_estado_pedido_fecha)
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

function printExplain($rows, $label) {
    echo "=== $label ===\n";
    printf("%-3s | %-12s | %-16s | %-6s | %-32s | %-28s | %-6s | %s\n", "id", "select_type", "table", "type", "possible_keys", "key", "rows", "Extra");
    echo str_repeat("-", 140) . "\n";
    foreach ($rows as $r) {
        printf("%-3s | %-12s | %-16s | %-6s | %-32s | %-28s | %-6s | %s\n",
            $r['id'] ?? '',
            $r['select_type'] ?? '',
            $r['table'] ?? '',
            $r['type'] ?? '',
            substr($r['possible_keys'] ?? '', 0, 32),
            substr($r['key'] ?? '', 0, 28),
            $r['rows'] ?? '',
            $r['Extra'] ?? ''
        );
    }
    echo "\n";
}

$rowsBefore = $db->query($sqlBefore)->fetchAll(PDO::FETCH_ASSOC);
printExplain($rowsBefore, "EXPLAIN ANTES (Sin idx_hist_estado_pedido_fecha: usa idx_id_estado con Using temporary; Using filesort)");

$rowsAfter = $db->query($sqlAfter)->fetchAll(PDO::FETCH_ASSOC);
printExplain($rowsAfter, "EXPLAIN DESPUÉS (Con idx_hist_estado_pedido_fecha: Covering index, Using index, SIN tabla temporal)");
