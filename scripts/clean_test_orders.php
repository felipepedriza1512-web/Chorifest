<?php
/**
 * Script seguro para limpieza de pedidos generados exclusivamente por pruebas automatizadas.
 * 
 * REGLAS DE SEGURIDAD ESTRICTAS:
 * 1. NUNCA elimina pedidos por rango o condición genérica (id >= X).
 * 2. Requiere obligatoriamente una lista explícita de IDs pasados por argumento CLI (--ids=74,75,76)
 *    o mediante un archivo JSON generado por la suite de pruebas (--file=tests/temp_test_ids.json).
 * 3. Solo elimina movimientos de inventario vinculados a esos IDs específicos (WHERE id_pedido IN (...)).
 * 4. Jamás ejecuta DELETEs masivos basados en LIKE '%prueba%'.
 */

require_once __DIR__ . '/../config/database.php';

$options = getopt('', ['ids:', 'file:', 'dry-run']);
$idsToDelete = [];

if (isset($options['ids'])) {
    $parts = explode(',', $options['ids']);
    foreach ($parts as $p) {
        $val = (int)trim($p);
        if ($val > 0) {
            $idsToDelete[] = $val;
        }
    }
} elseif (isset($options['file'])) {
    $filePath = $options['file'];
    if (file_exists($filePath)) {
        $content = json_decode(file_get_contents($filePath), true);
        if (is_array($content)) {
            foreach ($content as $p) {
                $val = (int)$p;
                if ($val > 0) $idsToDelete[] = $val;
            }
        }
    } else {
        die("❌ Error: El archivo especificado en --file no existe: $filePath\n");
    }
}

if (empty($idsToDelete)) {
    echo "==============================================================================\n";
    echo "AVISO DE PROTECCIÓN DE DATOS: scripts/clean_test_orders.php\n";
    echo "==============================================================================\n";
    echo "Por motivos de seguridad e integridad, este script YA NO ELIMINA pedidos por rango.\n";
    echo "Para limpiar pedidos de prueba, debes especificar explícitamente los IDs registrados\n";
    echo "por el test al momento de crearlos.\n\n";
    echo "Uso:\n";
    echo "  php scripts/clean_test_orders.php --ids=101,102,103\n";
    echo "  php scripts/clean_test_orders.php --file=tests/test_run_ids.json\n";
    echo "  php scripts/clean_test_orders.php --ids=101,102 --dry-run\n";
    echo "==============================================================================\n";
    exit(0);
}

$isDryRun = isset($options['dry-run']);
$db = getDatabaseConnection();

$inClause = implode(',', $idsToDelete);
echo "Verificando pedidos solicitados para eliminación: $inClause...\n";

// Verificar pedidos en la base de datos
$stmt = $db->query("SELECT p.id_pedido, p.fecha, p.total, p.observaciones, ep.nombre as estado 
                    FROM pedido p 
                    LEFT JOIN estado_pedido ep ON p.id_estado = ep.id_estado 
                    WHERE p.id_pedido IN ($inClause)");
$foundOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($foundOrders)) {
    echo "No se encontró ningún pedido en la base de datos con los IDs especificados.\n";
    exit(0);
}

echo "Pedidos encontrados (" . count($foundOrders) . "):\n";
foreach ($foundOrders as $o) {
    echo "  - Pedido #{$o['id_pedido']} | {$o['fecha']} | {$o['estado']} | \${$o['total']} | Obs: '{$o['observaciones']}'\n";
}

if ($isDryRun) {
    echo "\n[MODO DRY-RUN ACTIVADO] No se realizaron cambios en la base de datos.\n";
    echo "Registros que se eliminarían:\n";
    $stats = [
        'movimiento_inventario' => (int)$db->query("SELECT COUNT(*) FROM movimiento_inventario WHERE id_pedido IN ($inClause)")->fetchColumn(),
        'detalle_pedido' => (int)$db->query("SELECT COUNT(*) FROM detalle_pedido WHERE id_pedido IN ($inClause)")->fetchColumn(),
        'pago' => (int)$db->query("SELECT COUNT(*) FROM pago WHERE id_pedido IN ($inClause)")->fetchColumn(),
        'historial_pedido' => (int)$db->query("SELECT COUNT(*) FROM historial_pedido WHERE id_pedido IN ($inClause)")->fetchColumn(),
        'novedad_entrega' => (int)$db->query("SELECT COUNT(*) FROM novedad_entrega WHERE id_pedido IN ($inClause)")->fetchColumn(),
        'asignacion_cocina' => (int)$db->query("SELECT COUNT(*) FROM asignacion_cocina WHERE id_pedido IN ($inClause)")->fetchColumn(),
        'asignacion_domicilio' => (int)$db->query("SELECT COUNT(*) FROM asignacion_domicilio WHERE id_pedido IN ($inClause)")->fetchColumn(),
        'notificacion' => (int)$db->query("SELECT COUNT(*) FROM notificacion WHERE id_pedido IN ($inClause)")->fetchColumn(),
        'pedido' => count($foundOrders)
    ];
    foreach ($stats as $tbl => $cnt) {
        echo "  - $tbl: $cnt registros\n";
    }
    exit(0);
}

$db->beginTransaction();
try {
    // 1. Eliminar movimientos de inventario SOLO asociados a estos IDs de pedidos específicos
    $delMov = $db->exec("DELETE FROM movimiento_inventario WHERE id_pedido IN ($inClause)");
    echo "  ✔ Movimientos de inventario eliminados: $delMov\n";

    // 2. Tablas secundarias con FKs asociadas
    $db->exec("DELETE FROM detalle_pedido WHERE id_pedido IN ($inClause)");
    $db->exec("DELETE FROM pago WHERE id_pedido IN ($inClause)");
    $db->exec("DELETE FROM historial_pedido WHERE id_pedido IN ($inClause)");
    $db->exec("DELETE FROM novedad_entrega WHERE id_pedido IN ($inClause)");
    $db->exec("DELETE FROM asignacion_cocina WHERE id_pedido IN ($inClause)");
    $db->exec("DELETE FROM asignacion_domicilio WHERE id_pedido IN ($inClause)");
    $db->exec("DELETE FROM notificacion WHERE id_pedido IN ($inClause)");

    // 3. Eliminar los pedidos específicos
    $delPed = $db->exec("DELETE FROM pedido WHERE id_pedido IN ($inClause)");
    echo "  ✔ Pedidos eliminados de forma segura: $delPed\n";

    $db->commit();
    echo "✔ Proceso de limpieza finalizado exitosamente bajo transacción.\n";
} catch (Exception $e) {
    $db->rollBack();
    echo "❌ Error durante la eliminación. Transacción revertida: " . $e->getMessage() . "\n";
    exit(1);
}
