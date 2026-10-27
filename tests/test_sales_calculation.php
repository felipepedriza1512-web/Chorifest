<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/AdminModel.php';

$db = getDatabaseConnection();
$adminModel = new AdminModel($db);

echo "=========================================================\n";
echo "PRUEBA DE CÁLCULO DE VENTAS (HOY VS AYER + DESGLOSE + CANCELADO)\n";
echo "=========================================================\n\n";

// 1. Estadísticas Base iniciales
$statsBase = $adminModel->getDashboardStats();
echo "1. ESTADÍSTICAS BASE ANTES DE INSERTAR DATOS DE PRUEBA:\n";
print_r($statsBase);

// 2. Insertar Pedidos de Prueba
echo "\n2. Insertando pedidos de prueba...\n";

// Pedido A: Entregado HOY con EFECTIVO ($30.000)
$db->exec("INSERT INTO pedido (id_cliente, id_direccion, id_zona, id_estado, fecha, subtotal, costo_domicilio, total, observaciones) VALUES (13, 1, 1, 6, NOW(), 27000.00, 3000.00, 30000.00, 'Test Ventas Hoy Cash')");
$idA = (int)$db->lastInsertId();
$db->exec("INSERT INTO historial_pedido (id_pedido, id_estado, fecha, comentario) VALUES ($idA, 6, NOW(), 'Entregado hoy')");
$db->exec("INSERT INTO pago (id_pedido, metodo, estado, fecha_pago) VALUES ($idA, 'Efectivo', 'APROBADO', NOW())");
echo "  - Pedido A (HOY - EFECTIVO $30.000) -> ID #$idA Creado.\n";

// Pedido B: Entregado HOY con TRANSFERENCIA ($50.000)
$db->exec("INSERT INTO pedido (id_cliente, id_direccion, id_zona, id_estado, fecha, subtotal, costo_domicilio, total, observaciones) VALUES (13, 1, 1, 6, NOW(), 47000.00, 3000.00, 50000.00, 'Test Ventas Hoy Transfer')");
$idB = (int)$db->lastInsertId();
$db->exec("INSERT INTO historial_pedido (id_pedido, id_estado, fecha, comentario) VALUES ($idB, 6, NOW(), 'Entregado hoy')");
$db->exec("INSERT INTO pago (id_pedido, metodo, estado, fecha_pago) VALUES ($idB, 'Transferencia / Nequi', 'VERIFICADO', NOW())");
echo "  - Pedido B (HOY - TRANSFERENCIA $50.000) -> ID #$idB Creado.\n";

// Pedido C: Entregado AYER ($40.000)
$db->exec("INSERT INTO pedido (id_cliente, id_direccion, id_zona, id_estado, fecha, subtotal, costo_domicilio, total, observaciones) VALUES (13, 1, 1, 6, NOW() - INTERVAL 1 DAY, 37000.00, 3000.00, 40000.00, 'Test Ventas Ayer')");
$idC = (int)$db->lastInsertId();
$db->exec("INSERT INTO historial_pedido (id_pedido, id_estado, fecha, comentario) VALUES ($idC, 6, NOW() - INTERVAL 1 DAY, 'Entregado ayer')");
$db->exec("INSERT INTO pago (id_pedido, metodo, estado, fecha_pago) VALUES ($idC, 'Efectivo', 'APROBADO', NOW() - INTERVAL 1 DAY)");
echo "  - Pedido C (AYER - EFECTIVO $40.000) -> ID #$idC Creado.\n";

// Pedido D: CANCELADO HOY ($25.000) pero con pago previamente verificado
$db->exec("INSERT INTO pedido (id_cliente, id_direccion, id_zona, id_estado, fecha, subtotal, costo_domicilio, total, observaciones) VALUES (13, 1, 1, 7, NOW(), 22000.00, 3000.00, 25000.00, 'Test Pedido Cancelado')");
$idD = (int)$db->lastInsertId();
$db->exec("INSERT INTO historial_pedido (id_pedido, id_estado, fecha, comentario) VALUES ($idD, 7, NOW(), 'Cancelado hoy')");
$db->exec("INSERT INTO pago (id_pedido, metodo, estado, pendiente_devolucion, fecha_pago) VALUES ($idD, 'Transferencia / Nequi', 'VERIFICADO', 1, NOW())");
echo "  - Pedido D (CANCELADO HOY $25.000 con pago verificado y pendiente_devolucion = 1) -> ID #$idD Creado.\n";

// 3. Consultar estadísticas con datos de prueba
echo "\n3. ESTADÍSTICAS CALCULADAS POR EL DASHBOARD:\n";
$statsTest = $adminModel->getDashboardStats();
print_r($statsTest);

// Validaciones cuantitativas
$diffVentasHoy = $statsTest['ventas_hoy'] - $statsBase['ventas_hoy'];
$diffEfectivo = $statsTest['ventas_efectivo_hoy'] - $statsBase['ventas_efectivo_hoy'];
$diffTransfer = $statsTest['ventas_transferencia_hoy'] - $statsBase['ventas_transferencia_hoy'];
$diffVentasAyer = $statsTest['ventas_ayer'] - $statsBase['ventas_ayer'];

echo "\n4. VERIFICACIÓN MATEMÁTICA Y DE REGLAS DE NEGOCIO:\n";
echo "  - Incremento en Ventas Hoy: $" . number_format($diffVentasHoy, 2) . " (Esperado: $80.000,00)\n";
echo "  - Incremento en Efectivo Hoy: $" . number_format($diffEfectivo, 2) . " (Esperado: $30.000,00)\n";
echo "  - Incremento en Transferencia Hoy: $" . number_format($diffTransfer, 2) . " (Esperado: $50.000,00)\n";
echo "  - Incremento en Ventas Ayer: $" . number_format($diffVentasAyer, 2) . " (Esperado: $40.000,00)\n";
echo "  - Porcentaje Crecimiento Hoy vs Ayer: {$statsTest['ventas_pct']}%\n";

// Comprobar Pedido Cancelado
$stmtCheckCancel = $db->query("SELECT p.id_estado, m.estado as estado_pago, m.pendiente_devolucion FROM pedido p JOIN pago m ON p.id_pedido = m.id_pedido WHERE p.id_pedido = $idD")->fetch(PDO::FETCH_ASSOC);
echo "  - Estado del Pedido D (Cancelado): id_estado = {$stmtCheckCancel['id_estado']} (7 = CANCELADO)\n";
echo "  - Marca de devolución en Pago D: pendiente_devolucion = {$stmtCheckCancel['pendiente_devolucion']} (1 = Devolución pendiente)\n";

$pass = ($diffVentasHoy == 80000.0 && $diffEfectivo == 30000.0 && $diffTransfer == 50000.0 && $diffVentasAyer == 40000.0 && $stmtCheckCancel['pendiente_devolucion'] == 1);

if ($pass) {
    echo "\n✔ PRUEBA DE VENTAS SUPERADA CON ÉXITO: Los totales de ventas hoy/ayer, desgloses y exclusión de cancelados son 100% exactos.\n";
} else {
    echo "\n❌ PRUEBA DE VENTAS FALLIDA.\n";
}

// 5. Limpieza total de datos de prueba
echo "\n5. Limpiando datos de prueba...\n";
$testIds = implode(',', [$idA, $idB, $idC, $idD]);
$db->exec("DELETE FROM pago WHERE id_pedido IN ($testIds)");
$db->exec("DELETE FROM pedido WHERE id_pedido IN ($testIds)");
echo "  - Pedidos #$testIds eliminados de la base de datos.\n";

echo "\n=========================================================\n";
