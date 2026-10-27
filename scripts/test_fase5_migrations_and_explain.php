<?php
/**
 * Test de migraciones, rollbacks y EXPLAIN con 10.000 pedidos en chorifest_test
 */

require_once __DIR__ . '/../config/database.php';

$db = getDatabaseConnection('chorifest_test');

echo "====================================================================\n";
echo "1. CICLO DE MIGRACIÓN / ROLLBACK / RE-MIGRACIÓN EN chorifest_test\n";
echo "====================================================================\n";

function runSqlFile($db, $filePath, $label, $ignoreErrors = false) {
    echo "Ejecutando $label ($filePath)...\n";
    $sql = file_get_contents($filePath);
    // Remover comentarios de una sola línea y bloques
    $sqlClean = preg_replace('/--.*$/m', '', $sql);
    $queries = array_filter(array_map('trim', explode(';', $sqlClean)));
    foreach ($queries as $q) {
        if ($q === '') continue;
        try {
            $db->exec($q);
        } catch (Exception $e) {
            if (!$ignoreErrors) {
                echo "  [ERROR] en query: " . substr($q, 0, 80) . " -> " . $e->getMessage() . "\n";
                throw $e;
            }
        }
    }
    echo "  -> OK sin errores.\n";
}

// Limpieza inicial previa para asegurar estado base limpio
runSqlFile($db, __DIR__ . '/../sql/rollback_fase5_reportes.sql', 'LIMPIEZA PREVIA REPORTES', true);
runSqlFile($db, __DIR__ . '/../sql/rollback_fase5_liquidacion.sql', 'LIMPIEZA PREVIA LIQUIDACIÓN', true);

// 1. Primera pasada: Migraciones
runSqlFile($db, __DIR__ . '/../sql/migration_fase5_reportes.sql', 'MIGRACIÓN REPORTES (Paso 1)');
runSqlFile($db, __DIR__ . '/../sql/migration_fase5_liquidacion.sql', 'MIGRACIÓN LIQUIDACIÓN (Paso 1)');

// 2. Rollback
runSqlFile($db, __DIR__ . '/../sql/rollback_fase5_reportes.sql', 'ROLLBACK REPORTES (Paso 2)');
runSqlFile($db, __DIR__ . '/../sql/rollback_fase5_liquidacion.sql', 'ROLLBACK LIQUIDACIÓN (Paso 2)');

// 3. Segunda pasada: Re-migración
runSqlFile($db, __DIR__ . '/../sql/migration_fase5_reportes.sql', 'RE-MIGRACIÓN REPORTES (Paso 3)');
runSqlFile($db, __DIR__ . '/../sql/migration_fase5_liquidacion.sql', 'RE-MIGRACIÓN LIQUIDACIÓN (Paso 3)');

echo "\n>> CICLO MIGRACIÓN -> ROLLBACK -> RE-MIGRACIÓN COMPLETADO CON ÉXITO <<\n\n";

echo "====================================================================\n";
echo "2. POBLADO DE 10.000 PEDIDOS DE PRUEBA EN chorifest_test\n";
echo "====================================================================\n";

// Asegurar cliente y dirección para los pedidos de prueba
$stmtCl = $db->query("SELECT id_usuario FROM usuario WHERE id_rol = 1 LIMIT 1");
$idCli = (int)$stmtCl->fetchColumn();
if (!$idCli) $idCli = 13;

$stmtDir = $db->query("SELECT id_direccion FROM direccion LIMIT 1");
$idDir = (int)$stmtDir->fetchColumn();
if (!$idDir) {
    $db->exec("INSERT INTO direccion (id_cliente, direccion, barrio) VALUES ($idCli, 'Calle Test', 'Centro')");
    $idDir = (int)$db->lastInsertId();
}

// Obtener un insumo y producto para detalle y movimiento
$idProd = (int)$db->query("SELECT id_producto FROM producto LIMIT 1")->fetchColumn();
$idIns = (int)$db->query("SELECT id_insumo FROM insumo LIMIT 1")->fetchColumn();

// Verificar cuántos pedidos hay actualmente en chorifest_test
$pedidosActuales = (int)$db->query("SELECT COUNT(*) FROM pedido")->fetchColumn();
$necesarios = 10000 - $pedidosActuales;

if ($necesarios > 0) {
    echo "Insertando $necesarios pedidos de prueba con historial, detalle e inventario...\n";
    $db->beginTransaction();
    
    // Inserción en bloques de 1000 para máximo rendimiento
    $batchSize = 1000;
    for ($i = 0; $i < $necesarios; $i += $batchSize) {
        $count = min($batchSize, $necesarios - $i);
        $valuesPed = [];
        for ($j = 0; $j < $count; $j++) {
            // Distribuir fechas en los últimos 90 días
            $diasAtras = rand(0, 89);
            $hora = rand(10, 23);
            $min = rand(0, 59);
            $fecha = date('Y-m-d H:i:s', strtotime("-$diasAtras days $hora:$min:00"));
            $estado = (rand(1, 100) <= 85) ? 6 : (rand(1, 100) <= 50 ? 7 : 5); // 85% entregados
            $total = rand(15000, 80000);
            $valuesPed[] = "($idCli, $idDir, 1, 'Zona Test', $estado, '$fecha', $total, $total)";
        }
        $db->exec("INSERT INTO pedido (id_cliente, id_direccion, id_zona, nombre_zona, id_estado, fecha, subtotal, total) VALUES " . implode(',', $valuesPed));
    }
    
    // Obtener los IDs insertados para generar historial_pedido y detalle_pedido
    $minId = (int)$db->query("SELECT MIN(id_pedido) FROM pedido WHERE id_zona = 1 AND nombre_zona = 'Zona Test'")->fetchColumn();
    $maxId = (int)$db->query("SELECT MAX(id_pedido) FROM pedido WHERE id_zona = 1 AND nombre_zona = 'Zona Test'")->fetchColumn();
    
    echo "Generando historial_pedido para pedidos entregados ($minId a $maxId)...\n";
    $db->exec("
        INSERT INTO historial_pedido (id_pedido, id_estado, comentario, fecha)
        SELECT id_pedido, 6, 'Pedido entregado al cliente', DATE_ADD(fecha, INTERVAL 35 MINUTE)
        FROM pedido
        WHERE id_pedido BETWEEN $minId AND $maxId AND id_estado = 6
    ");
    
    echo "Generando detalle_pedido...\n";
    $db->exec("
        INSERT INTO detalle_pedido (id_pedido, id_producto, nombre_producto, cantidad, precio_unitario, subtotal)
        SELECT id_pedido, $idProd, 'Producto Test', 2, 12000.00, 24000.00
        FROM pedido
        WHERE id_pedido BETWEEN $minId AND $maxId
    ");
    
    echo "Generando pagos...\n";
    $db->exec("
        INSERT INTO pago (id_pedido, metodo, estado, fecha_pago)
        SELECT id_pedido, IF(MOD(id_pedido, 2) = 0, 'EFECTIVO', 'TRANSFERENCIA'), 'APROBADO', fecha
        FROM pedido
        WHERE id_pedido BETWEEN $minId AND $maxId
    ");
    
    echo "Generando movimientos de inventario...\n";
    $db->exec("
        INSERT INTO movimiento_inventario (id_insumo, id_pedido, tipo, cantidad, motivo, fecha)
        SELECT $idIns, id_pedido, 'SALIDA_RECETA', 0.250, 'Consumo de orden', fecha
        FROM pedido
        WHERE id_pedido BETWEEN $minId AND $maxId AND id_estado = 6
    ");
    
    $db->commit();
    echo ">> 10.000 pedidos listos y poblados en chorifest_test <<\n\n";
} else {
    echo "Ya existen $pedidosActuales pedidos en chorifest_test.\n\n";
}

$totalReal = (int)$db->query("SELECT COUNT(*) FROM pedido")->fetchColumn();
echo "Total pedidos en chorifest_test: $totalReal\n\n";

echo "====================================================================\n";
echo "3. EXPLAIN DE LAS CONSULTAS OPTIMIZADAS CON ÍNDICES FASE 5\n";
echo "====================================================================\n";

function printExplain($db, $title, $sql, $params = []) {
    echo "--------------------------------------------------------------------\n";
    echo "EXPLAIN: $title\n";
    echo "SQL: " . trim(preg_replace('/\s+/', ' ', $sql)) . "\n";
    echo "--------------------------------------------------------------------\n";
    $stmt = $db->prepare("EXPLAIN $sql");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $headers = ['id', 'select_type', 'table', 'type', 'possible_keys', 'key', 'key_len', 'ref', 'rows', 'Extra'];
    printf("%-3s | %-12s | %-15s | %-7s | %-30s | %-25s | %-6s | %-10s | %-8s | %s\n", 
        'id', 'select_type', 'table', 'type', 'possible_keys', 'key', 'key_len', 'ref', 'rows', 'Extra');
    echo str_repeat('-', 130) . "\n";
    foreach ($rows as $r) {
        printf("%-3s | %-12s | %-15s | %-7s | %-30s | %-25s | %-6s | %-10s | %-8s | %s\n",
            $r['id'] ?? '',
            $r['select_type'] ?? '',
            $r['table'] ?? '',
            $r['type'] ?? '',
            substr($r['possible_keys'] ?? 'NULL', 0, 30),
            substr($r['key'] ?? 'NULL', 0, 25),
            $r['key_len'] ?? '',
            substr($r['ref'] ?? '', 0, 10),
            $r['rows'] ?? '',
            $r['Extra'] ?? ''
        );
    }
    echo "\n";
}

// 1. Ventas por Rango (Fuente única: subconsulta MIN(fecha) id_estado = 6 con respaldo a p.fecha, filtro fecha >= ? AND fecha < ?)
$sqlVentas = "
SELECT 
    p.id_pedido,
    p.total,
    COALESCE(h.fecha_entrega, p.fecha) AS fecha_venta,
    COALESCE(pg.metodo, 'EFECTIVO') AS metodo_pago
FROM pedido p
INNER JOIN (
    SELECT id_pedido, MIN(fecha) AS fecha_entrega
    FROM historial_pedido
    WHERE id_estado = 6
    GROUP BY id_pedido
) h ON p.id_pedido = h.id_pedido
LEFT JOIN pago pg ON p.id_pedido = pg.id_pedido
WHERE p.id_estado = 6
  AND COALESCE(h.fecha_entrega, p.fecha) >= ?
  AND COALESCE(h.fecha_entrega, p.fecha) < ?
";

printExplain($db, "VENTAS POR RANGO (Fuente Única)", $sqlVentas, ['2026-08-01 00:00:00', '2026-10-31 23:59:59']);

// 2. Top Productos en el rango
$sqlTopProd = "
SELECT 
    dp.id_producto,
    dp.nombre_producto,
    SUM(dp.cantidad) AS total_unidades,
    SUM(dp.subtotal) AS total_recaudado
FROM detalle_pedido dp
INNER JOIN pedido p ON dp.id_pedido = p.id_pedido
INNER JOIN (
    SELECT id_pedido, MIN(fecha) AS fecha_entrega
    FROM historial_pedido
    WHERE id_estado = 6
    GROUP BY id_pedido
) h ON p.id_pedido = h.id_pedido
WHERE p.id_estado = 6
  AND COALESCE(h.fecha_entrega, p.fecha) >= ?
  AND COALESCE(h.fecha_entrega, p.fecha) < ?
GROUP BY dp.id_producto, dp.nombre_producto
ORDER BY total_unidades DESC
LIMIT 10
";

printExplain($db, "TOP PRODUCTOS VENDIDOS", $sqlTopProd, ['2026-08-01 00:00:00', '2026-10-31 23:59:59']);

// 3. Consumo de Insumos
$sqlConsumo = "
SELECT 
    m.id_insumo,
    i.nombre AS nombre_insumo,
    i.unidad_medida,
    SUM(m.cantidad) AS total_consumido,
    COUNT(m.id_movimiento) AS total_movimientos
FROM movimiento_inventario m
INNER JOIN insumo i ON m.id_insumo = i.id_insumo
WHERE m.tipo = 'SALIDA_RECETA'
  AND m.fecha >= ?
  AND m.fecha < ?
GROUP BY m.id_insumo, i.nombre, i.unidad_medida
ORDER BY total_consumido DESC
";

printExplain($db, "CONSUMO DE INSUMOS", $sqlConsumo, ['2026-08-01 00:00:00', '2026-10-31 23:59:59']);

