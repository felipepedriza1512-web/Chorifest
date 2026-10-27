<?php
/**
 * Test de Fuente Única de Ventas - Fase 5
 * Base de datos: chorifest_test (completamente aislada)
 */

require_once __DIR__ . '/test_bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/EstadoPedido.php';
require_once __DIR__ . '/../models/ReporteModel.php';
require_once __DIR__ . '/../models/AdminModel.php';

date_default_timezone_set('America/Bogota');

$db = getDatabaseConnection('chorifest_test');
assertTestDatabaseOnly($db);

$reporteModel = new ReporteModel($db);
$adminModel = new AdminModel($db);

echo "====================================================================\n";
echo "PRUEBAS DE FUENTE ÚNICA DE VENTAS (ReporteModel vs Dashboard)\n";
echo "Base de datos en uso: chorifest_test\n";
echo "====================================================================\n\n";

$passedTests = 0;
$totalTests = 0;

function assertTest($name, $condition, $details = '') {
    global $passedTests, $totalTests;
    $totalTests++;
    if ($condition) {
        $passedTests++;
        echo "  [PASÓ] $name\n";
        if ($details) echo "         $details\n";
    } else {
        echo "  [FALLÓ] $name\n";
        if ($details) echo "          Detalles: $details\n";
    }
}

// Limpiar pedidos previos de prueba
$db->exec("DELETE FROM detalle_pedido WHERE id_pedido >= 900000");
$db->exec("DELETE FROM historial_pedido WHERE id_pedido >= 900000");
$db->exec("DELETE FROM pago WHERE id_pedido >= 900000");
$db->exec("DELETE FROM pedido WHERE id_pedido >= 900000");

// -----------------------------------------------------------------------------
// PRUEBA 1: PEDIDO QUE CRUZA LA MEDIANOCHE (23:50 -> 00:10)
// -----------------------------------------------------------------------------
echo "--- 1. PEDIDO QUE CRUZA LA MEDIANOCHE ---\n";

$diaA = date('Y-m-d', strtotime('-1 day'));
$diaB = date('Y-m-d');

$idPed1 = 900001;
$montoPed1 = 45000.00;

// Pedido creado a las 23:50 del Día A
$db->exec("
    INSERT INTO pedido (id_pedido, id_cliente, id_direccion, id_zona, nombre_zona, id_estado, subtotal, total, fecha)
    VALUES ($idPed1, 13, 1, 1, 'Zona Norte', 6, $montoPed1, $montoPed1, '$diaA 23:50:00')
");

// Historial: entregado a las 00:10 del Día B
$db->exec("
    INSERT INTO historial_pedido (id_pedido, id_estado, comentario, fecha)
    VALUES ($idPed1, 6, 'Entregado al cliente', '$diaB 00:10:00')
");

$db->exec("
    INSERT INTO pago (id_pedido, metodo, estado, fecha_pago)
    VALUES ($idPed1, 'EFECTIVO', 'APROBADO', '$diaB 00:10:00')
");

// Consultar Día A (2026-10-14 00:00:00 a 23:59:59)
$ventasDiaA = $reporteModel->ventasPorRango("$diaA 00:00:00", "$diaA 23:59:59");
$pedidosDiaA = array_column($ventasDiaA['pedidos'], 'id_pedido');

// Consultar Día B (2026-10-15 00:00:00 a 23:59:59)
$ventasDiaB = $reporteModel->ventasPorRango("$diaB 00:00:00", "$diaB 23:59:59");
$pedidosDiaB = array_column($ventasDiaB['pedidos'], 'id_pedido');

assertTest(
    "Pedido creado 23:50 NO suma en las ventas del día de creación ($diaA)",
    !in_array($idPed1, $pedidosDiaA),
    "Ventas $diaA: total $" . number_format($ventasDiaA['resumen']['total_ventas'], 2)
);

assertTest(
    "Pedido entregado 00:10 SÍ suma en las ventas del día siguiente ($diaB)",
    in_array($idPed1, $pedidosDiaB),
    "Ventas $diaB: total $" . number_format($ventasDiaB['resumen']['total_ventas'], 2)
);

// Coincidencia con Dashboard
$statsDash = $adminModel->getDashboardStats();
assertTest(
    "Totales del Dashboard coinciden exactamente con ReporteModel para el mismo día",
    $statsDash['ventas_hoy'] === $ventasDiaB['resumen']['total_ventas'],
    "Dashboard hoy: \${$statsDash['ventas_hoy']} == Reporte hoy: \${$ventasDiaB['resumen']['total_ventas']}"
);

// -----------------------------------------------------------------------------
// PRUEBA 2: PEDIDO CON DOS FILAS 'ENTREGADO' EN EL HISTORIAL
// -----------------------------------------------------------------------------
echo "\n--- 2. PEDIDO CON DOS FILAS 'ENTREGADO' EN EL HISTORIAL ---\n";

$idPed2 = 900002;
$montoPed2 = 32000.00;

$db->exec("
    INSERT INTO pedido (id_pedido, id_cliente, id_direccion, id_zona, nombre_zona, id_estado, subtotal, total, fecha)
    VALUES ($idPed2, 13, 1, 1, 'Zona Norte', 6, $montoPed2, $montoPed2, '$diaB 14:00:00')
");

// Dos filas de entrega: 14:30:00 y 14:35:00
$db->exec("
    INSERT INTO historial_pedido (id_pedido, id_estado, comentario, fecha)
    VALUES 
    ($idPed2, 6, 'Primera confirmación de entrega', '$diaB 14:30:00'),
    ($idPed2, 6, 'Segunda confirmación duplicada por reintento', '$diaB 14:35:00')
");

$db->exec("
    INSERT INTO pago (id_pedido, metodo, estado, fecha_pago)
    VALUES ($idPed2, 'TRANSFERENCIA', 'APROBADO', '$diaB 14:30:00')
");

$resumenP2 = $reporteModel->ventasPorRango("$diaB 00:00:00", "$diaB 23:59:59");
$filasPed2 = array_filter($resumenP2['pedidos'], fn($p) => (int)$p['id_pedido'] === $idPed2);
$filaPed2 = reset($filasPed2);

assertTest(
    "Pedido con 2 filas de entrega en historial devuelve exactamente 1 sola fila en el reporte",
    count($filasPed2) === 1,
    "Filas devueltas para Pedido #$idPed2: " . count($filasPed2)
);

assertTest(
    "La fecha de venta toma la primera entrega (MIN(fecha) = 14:30:00) y el total no se duplica",
    $filaPed2 && $filaPed2['fecha_venta'] === "$diaB 14:30:00" && (float)$filaPed2['total'] === $montoPed2,
    "fecha_venta tomada: {$filaPed2['fecha_venta']}, total: {$filaPed2['total']}"
);

// -----------------------------------------------------------------------------
// PRUEBA 3: CANCELADO CON PAGO PENDIENTE DE DEVOLUCIÓN
// -----------------------------------------------------------------------------
echo "\n--- 3. CANCELADO CON PAGO PENDIENTE DE DEVOLUCIÓN ---\n";

$idPed3 = 900003;
$montoPed3 = 50000.00;

// Pedido cancelado (id_estado = 7)
$db->exec("
    INSERT INTO pedido (id_pedido, id_cliente, id_direccion, id_zona, nombre_zona, id_estado, subtotal, total, fecha)
    VALUES ($idPed3, 13, 1, 1, 'Zona Norte', 7, $montoPed3, $montoPed3, '$diaB 16:00:00')
");

// Historial cancelación
$db->exec("
    INSERT INTO historial_pedido (id_pedido, id_estado, comentario, fecha)
    VALUES ($idPed3, 7, 'Pedido cancelado por cliente', '$diaB 16:15:00')
");

// Pago con marca pendiente_devolucion = 1
$db->exec("
    INSERT INTO pago (id_pedido, metodo, estado, pendiente_devolucion, referencia, fecha_pago)
    VALUES ($idPed3, 'TRANSFERENCIA', 'APROBADO', 1, 'REF-DEV-900003', '$diaB 16:05:00')
");

$resumenP3 = $reporteModel->ventasPorRango("$diaB 00:00:00", "$diaB 23:59:59");
$filasPed3 = array_filter($resumenP3['pedidos'], fn($p) => (int)$p['id_pedido'] === $idPed3);

assertTest(
    "Pedido cancelado con devolución pendiente NO suma en el reporte de ventas",
    empty($filasPed3),
    "Pedido #$idPed3 no aparece en pedidos entregados"
);

$pendientesDev = $reporteModel->getPagosPendientesDevolucion();
$encontradoDev = array_filter($pendientesDev, fn($p) => (int)$p['id_pedido'] === $idPed3);

assertTest(
    "Pedido cancelado con devolución pendiente se reporta por separado en getPagosPendientesDevolucion()",
    !empty($encontradoDev),
    "Listado en pagos pendientes de devolución con monto: \$$montoPed3"
);

// -----------------------------------------------------------------------------
// PRUEBA 4: PEDIDO DE AYER (AISLAMIENTO ESTRICTO DE FECHA)
// -----------------------------------------------------------------------------
echo "\n--- 4. PEDIDO DE AYER (AISLAMIENTO ESTRICTO DE FECHA) ---\n";

$idPed4 = 900004;
$montoPed4 = 28000.00;
$fechaAyer = date('Y-m-d', strtotime('-1 day'));

$db->exec("
    INSERT INTO pedido (id_pedido, id_cliente, id_direccion, id_zona, nombre_zona, id_estado, subtotal, total, fecha)
    VALUES ($idPed4, 13, 1, 1, 'Zona Norte', 6, $montoPed4, $montoPed4, '$fechaAyer 18:00:00')
");

$db->exec("
    INSERT INTO historial_pedido (id_pedido, id_estado, comentario, fecha)
    VALUES ($idPed4, 6, 'Entregado ayer', '$fechaAyer 18:40:00')
");

$db->exec("
    INSERT INTO pago (id_pedido, metodo, estado, fecha_pago)
    VALUES ($idPed4, 'EFECTIVO', 'APROBADO', '$fechaAyer 18:40:00')
");

// Consultar hoy
$ventasHoyReal = $reporteModel->ventasPorRango(date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59'));
$enHoy = array_filter($ventasHoyReal['pedidos'], fn($p) => (int)$p['id_pedido'] === $idPed4);

// Consultar ayer
$ventasAyerReal = $reporteModel->ventasPorRango("$fechaAyer 00:00:00", "$fechaAyer 23:59:59");
$enAyer = array_filter($ventasAyerReal['pedidos'], fn($p) => (int)$p['id_pedido'] === $idPed4);

assertTest(
    "Pedido entregado ayer NO aparece en las ventas de hoy",
    empty($enHoy),
    "No está presente en ventas de hoy (" . date('Y-m-d') . ")"
);

assertTest(
    "Pedido entregado ayer SÍ aparece en el reporte de ayer",
    !empty($enAyer),
    "Presente en ventas de ayer ($fechaAyer)"
);

$statsDashAyer = $adminModel->getDashboardStats();
assertTest(
    "Métricas de ayer en el Dashboard coinciden exactamente con ReporteModel",
    $statsDashAyer['ventas_ayer'] === $ventasAyerReal['resumen']['total_ventas'],
    "Dashboard ayer: \${$statsDashAyer['ventas_ayer']} == Reporte ayer: \${$ventasAyerReal['resumen']['total_ventas']}"
);

// Limpieza de pedidos de prueba
$db->exec("DELETE FROM detalle_pedido WHERE id_pedido >= 900000");
$db->exec("DELETE FROM historial_pedido WHERE id_pedido >= 900000");
$db->exec("DELETE FROM pago WHERE id_pedido >= 900000");
$db->exec("DELETE FROM pedido WHERE id_pedido >= 900000");

echo "\n====================================================================\n";
echo "RESUMEN DE PRUEBAS DE VENTAS: $passedTests / $totalTests APROBADAS\n";
echo "====================================================================\n";

if ($passedTests === $totalTests) {
    echo ">> TODAS LAS PRUEBAS DE FUENTE ÚNICA PASARON CON ÉXITO AL 100%. <<\n\n";
} else {
    exit(1);
}
