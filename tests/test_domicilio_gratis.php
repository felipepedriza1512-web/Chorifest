<?php
/**
 * Test Suite Integral: Cambio de Negocio - Domicilio Gratis en Chorifest
 * Base de datos: chorifest_test
 */

require_once __DIR__ . '/test_bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/PedidoModel.php';
require_once __DIR__ . '/../models/ZonaModel.php';
require_once __DIR__ . '/../models/ReporteModel.php';
require_once __DIR__ . '/../models/AdminModel.php';
require_once __DIR__ . '/../models/EstadoPedido.php';

date_default_timezone_set('America/Bogota');

$db = getDatabaseConnection('chorifest_test');
assertTestDatabaseOnly($db);

$pedidoModel = new PedidoModel($db);
$zonaModel = new ZonaModel($db);
$reporteModel = new ReporteModel($db);
$adminModel = new AdminModel($db);

echo "====================================================================\n";
echo "PRUEBAS DE NEGOCIO: DOMICILIO GRATIS (chorifest_test)\n";
echo "====================================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertCondition(string $desc, bool $passed, string $details = '') {
    global $passCount, $failCount;
    if ($passed) {
        $passCount++;
        echo "  [PASO] $desc\n";
        if ($details) echo "         $details\n";
    } else {
        $failCount++;
        echo "  [FALLO] $desc\n";
        if ($details) echo "          Detalles: $details\n";
    }
}

// Limpiar datos de prueba previos si existen
$db->exec("DELETE FROM detalle_pedido WHERE id_pedido >= 800000");
$db->exec("DELETE FROM historial_pedido WHERE id_pedido >= 800000");
$db->exec("DELETE FROM pago WHERE id_pedido >= 800000");
$db->exec("DELETE FROM asignacion_domicilio WHERE id_pedido >= 800000");
$db->exec("DELETE FROM turno_liquidacion_pedido WHERE id_pedido >= 800000");
$db->exec("DELETE FROM pedido WHERE id_pedido >= 800000");

// Asegurar que Zona 1 (Centro) tenga GeoJSON para pruebas de punto en polígono
$polyCentro = json_encode([
    'type' => 'Polygon',
    'coordinates' => [[
        [-73.130, 7.110],
        [-73.110, 7.110],
        [-73.110, 7.130],
        [-73.130, 7.130],
        [-73.130, 7.110]
    ]]
]);
$db->exec("UPDATE zona_envio SET poligono_geojson = '$polyCentro', monto_minimo = 0.00 WHERE id_zona = 1");

// -----------------------------------------------------------------------------
// PRUEBA (a): UN PEDIDO NUEVO TIENE TOTAL = SUBTOTAL Y COSTO_DOMICILIO = 0
// -----------------------------------------------------------------------------
echo "--- (a) PEDIDO NUEVO TIENE TOTAL = SUBTOTAL Y COSTO_DOMICILIO = 0 ---\n";

$carritoPrueba = [
    [
        'producto' => ['id_producto' => 1, 'nombre_producto' => 'Chorizo Santarrosano', 'precio' => 15000.00],
        'cantidad' => 2
    ]
];
$subtotalEsperado = 30000.00;

$direccionData = [
    'direccion' => 'Calle 35 # 18-20, Centro',
    'barrio' => 'Centro',
    'referencia' => 'Frente a la plaza',
    'latitud' => 7.1193,
    'longitud' => -73.1227,
    'id_zona' => 1
];

$idNuevo = $pedidoModel->createPedido(13, $direccionData, $carritoPrueba, 'Efectivo', 'Prueba Pedido Nuevo Gratis');
assertCondition("Pedido nuevo creado con exito", $idNuevo !== null && $idNuevo > 0, "ID Pedido: #$idNuevo");

if ($idNuevo) {
    $rowPed = $db->query("SELECT id_pedido, subtotal, costo_domicilio, total FROM pedido WHERE id_pedido = $idNuevo")->fetch(PDO::FETCH_ASSOC);
    assertCondition(
        "Pedido nuevo tiene costo_domicilio = 0.00",
        (float)$rowPed['costo_domicilio'] === 0.00,
        "costo_domicilio registrado: \${$rowPed['costo_domicilio']}"
    );
    assertCondition(
        "Pedido nuevo tiene total = subtotal (\${$subtotalEsperado})",
        (float)$rowPed['total'] === $subtotalEsperado && (float)$rowPed['subtotal'] === $subtotalEsperado,
        "subtotal: \${$rowPed['subtotal']} | total: \${$rowPed['total']}"
    );
}

// -----------------------------------------------------------------------------
// PRUEBA (b): POST MANIPULADO CON COSTO_DOMICILIO = 5000 ES IGNORADO EN SERVIDOR
// -----------------------------------------------------------------------------
echo "\n--- (b) POST MANIPULADO CON COSTO_DOMICILIO = 5000 ES IGNORADO ---\n";

$direccionManipulada = $direccionData;
$direccionManipulada['costo_domicilio'] = 5000.00;
$direccionManipulada['total'] = 99999.00;

$idManipulado = $pedidoModel->createPedido(13, $direccionManipulada, $carritoPrueba, 'Efectivo', 'Test Inyeccion Costo');
$rowManip = $db->query("SELECT id_pedido, subtotal, costo_domicilio, total FROM pedido WHERE id_pedido = $idManipulado")->fetch(PDO::FETCH_ASSOC);

assertCondition(
    "Servidor ignora costo_domicilio manipulado y guarda 0.00",
    (float)$rowManip['costo_domicilio'] === 0.00,
    "costo_domicilio en BD: \${$rowManip['costo_domicilio']} (Se envio 5000.00)"
);
assertCondition(
    "Servidor recalcula total exactamente igual al subtotal",
    (float)$rowManip['total'] === (float)$rowManip['subtotal'],
    "total en BD: \${$rowManip['total']} (Se envio 99999.00)"
);

// -----------------------------------------------------------------------------
// PRUEBA (c): RECHAZO DE PEDIDO BAJO EL MINIMO Y FUERA DE ZONA
// -----------------------------------------------------------------------------
echo "\n--- (c) VALIDACION ESTRICTA: RECHAZO FUERA DE ZONA Y BAJO EL MINIMO ---\n";

$dirFuera = [
    'direccion' => 'Plaza de Bolivar, Bogota',
    'barrio' => 'La Candelaria',
    'latitud' => 4.7110,
    'longitud' => -74.0721
];
$falloFueraZona = false;
$msgFuera = '';
try {
    $resFuera = $pedidoModel->createPedido(13, $dirFuera, $carritoPrueba, 'Efectivo', 'Test Fuera de Cobertura');
    if ($resFuera === null) {
        $falloFueraZona = true;
        $msgFuera = $pedidoModel->getLastError() ?: 'Rechazado por estar fuera de cobertura';
    }
} catch (Exception $e) {
    $falloFueraZona = true;
    $msgFuera = $e->getMessage();
}
assertCondition(
    "Pedido fuera de zona de cobertura es rechazado por el servidor",
    $falloFueraZona,
    "Mensaje de rechazo: '$msgFuera'"
);

$db->exec("UPDATE zona_envio SET monto_minimo = 50000.00 WHERE id_zona = 1");
$falloBajoMinimo = false;
$msgMinimo = '';
try {
    $resMin = $pedidoModel->createPedido(13, $direccionData, $carritoPrueba, 'Efectivo', 'Test Bajo Minimo');
    if ($resMin === null) {
        $falloBajoMinimo = true;
        $msgMinimo = $pedidoModel->getLastError() ?: 'Rechazado por no alcanzar monto mínimo';
    }
} catch (Exception $e) {
    $falloBajoMinimo = true;
    $msgMinimo = $e->getMessage();
}
$db->exec("UPDATE zona_envio SET monto_minimo = 0.00 WHERE id_zona = 1");
assertCondition(
    "Pedido con subtotal inferior al monto minimo es rechazado por el servidor",
    $falloBajoMinimo,
    "Mensaje de rechazo: '$msgMinimo'"
);

// -----------------------------------------------------------------------------
// PRUEBA (d): PEDIDO VIEJO CON DOMICILIO COBRADO CONSERVA SU TARIFA
// -----------------------------------------------------------------------------
echo "\n--- (d) PEDIDO HISTORICO CONSERVA COSTO DE DOMICILIO Y FACTURA ---\n";

$idViejo = 800001;
$subtotalViejo = 25000.00;
$costoViejo = 4000.00;
$totalViejo = 29000.00;
$fechaViejo = '2029-05-10 12:00:00';

$db->exec("
    INSERT INTO pedido (id_pedido, id_cliente, id_direccion, id_zona, nombre_zona, id_estado, fecha, subtotal, costo_domicilio, total, observaciones)
    VALUES ($idViejo, 13, 1, 1, 'Zona Centro', 6, '$fechaViejo', $subtotalViejo, $costoViejo, $totalViejo, 'Pedido Historico Domicilio Cobrado')
");
$db->exec("
    INSERT INTO historial_pedido (id_pedido, id_estado, fecha, comentario)
    VALUES ($idViejo, 6, '$fechaViejo', 'Entregado historico')
");
$db->exec("
    INSERT INTO pago (id_pedido, metodo, estado, fecha_pago)
    VALUES ($idViejo, 'Efectivo', 'APROBADO', '$fechaViejo')
");

$pedViejoBd = $db->query("SELECT * FROM pedido WHERE id_pedido = $idViejo")->fetch(PDO::FETCH_ASSOC);
assertCondition(
    "Pedido historico conserva costo_domicilio = \$$costoViejo",
    (float)$pedViejoBd['costo_domicilio'] === $costoViejo,
    "costo_domicilio en BD: \${$pedViejoBd['costo_domicilio']}"
);
assertCondition(
    "Pedido historico conserva total = \$$totalViejo (subtotal + costo)",
    (float)$pedViejoBd['total'] === $totalViejo,
    "total en BD: \${$pedViejoBd['total']}"
);

$textoFacturaViejo = ((float)$pedViejoBd['costo_domicilio'] > 0) ? '$' . number_format($pedViejoBd['costo_domicilio'], 2) : 'Gratis';
$textoFacturaNuevo = ((float)$rowPed['costo_domicilio'] > 0) ? '$' . number_format($rowPed['costo_domicilio'], 2) : 'Gratis';
assertCondition(
    "Factura de pedido viejo muestra exactamente la tarifa cobrada ($textoFacturaViejo)",
    $textoFacturaViejo === '$4,000.00',
    "Resultado renderizado: $textoFacturaViejo"
);
assertCondition(
    "Factura de pedido nuevo muestra 'Gratis'",
    $textoFacturaNuevo === 'Gratis',
    "Resultado renderizado: $textoFacturaNuevo"
);

// -----------------------------------------------------------------------------
// PRUEBA (e): PERIODO CON PEDIDOS VIEJOS Y NUEVOS (REPORTES Y DASHBOARD COINCIDEN)
// -----------------------------------------------------------------------------
echo "\n--- (e) PERIODO MIXTO (VIEJOS + NUEVOS): REPORTES Y DASHBOARD CUADRAN EXACTO ---\n";

$idNuevoEnRango = 800002;
$subtotalNuevoRango = 35000.00;
$costoNuevoRango = 0.00;
$totalNuevoRango = 35000.00;
$fechaNuevo = '2029-05-11 13:00:00';

$db->exec("
    INSERT INTO pedido (id_pedido, id_cliente, id_direccion, id_zona, nombre_zona, id_estado, fecha, subtotal, costo_domicilio, total, observaciones)
    VALUES ($idNuevoEnRango, 13, 1, 1, 'Zona Centro', 6, '$fechaNuevo', $subtotalNuevoRango, $costoNuevoRango, $totalNuevoRango, 'Pedido Nuevo Domicilio Gratis')
");
$db->exec("
    INSERT INTO historial_pedido (id_pedido, id_estado, fecha, comentario)
    VALUES ($idNuevoEnRango, 6, '$fechaNuevo', 'Entregado nuevo')
");
$db->exec("
    INSERT INTO pago (id_pedido, metodo, estado, fecha_pago)
    VALUES ($idNuevoEnRango, 'Efectivo', 'APROBADO', '$fechaNuevo')
");

$reporteMixto = $reporteModel->ventasPorRango('2029-05-10 00:00:00', '2029-05-11 23:59:59');
$resMixto = $reporteMixto['resumen'];

assertCondition(
    "Total ventas del periodo mixto cuadra exactamente ($64.000,00)",
    (float)$resMixto['total_ventas'] === 64000.00,
    "total_ventas: \${$resMixto['total_ventas']}"
);
assertCondition(
    "Reporte separa 'Ventas de productos' ($60.000,00)",
    (float)$resMixto['subtotal_productos'] === 60000.00,
    "subtotal_productos: \${$resMixto['subtotal_productos']}"
);
assertCondition(
    "Reporte separa 'Domicilios cobrados (historico)' ($4.000,00)",
    (float)$resMixto['domicilios_historicos'] === 4000.00,
    "domicilios_historicos: \${$resMixto['domicilios_historicos']}"
);
assertCondition(
    "Suma aritmetica: Subtotal productos + Domicilios historicos == Total Ventas",
    ((float)$resMixto['subtotal_productos'] + (float)$resMixto['domicilios_historicos']) === (float)$resMixto['total_ventas'],
    "{$resMixto['subtotal_productos']} + {$resMixto['domicilios_historicos']} = {$resMixto['total_ventas']}"
);

// -----------------------------------------------------------------------------
// PRUEBA (f): LIQUIDACION DE EFECTIVO EXACTA BASADA EN TOTAL
// -----------------------------------------------------------------------------
echo "\n--- (f) LIQUIDACION DE TURNO EN EFECTIVO BASADA ESTRICTAMENTE EN TOTAL ---\n";

$idDomiTest = 16;
$db->exec("INSERT INTO asignacion_domicilio (id_pedido, id_domiciliario, fecha_asignacion) VALUES ($idNuevoEnRango, $idDomiTest, NOW())");

$pendientesLiq = $reporteModel->getPedidosPendientesLiquidacion($idDomiTest);
$encontradoPed = null;
foreach ($pendientesLiq as $p) {
    if ((int)$p['id_pedido'] === $idNuevoEnRango) {
        $encontradoPed = $p;
        break;
    }
}
assertCondition(
    "Pedido nuevo aparece pendiente de liquidacion con su total real ($35.000)",
    $encontradoPed !== null && (float)$encontradoPed['total'] === 35000.00,
    "Total en pendiente de liquidar: \${$encontradoPed['total']}"
);

$resLiq = $reporteModel->liquidarTurno($idDomiTest, 14, date('Y-m-d H:i:s'), 'Prueba Liquidacion Domicilio Gratis');
assertCondition("Liquidacion de turno ejecutada exitosamente", $resLiq['success'] === true, $resLiq['message'] ?? '');

// -----------------------------------------------------------------------------
// PRUEBA (g): REGLA DE SOLAPAMIENTO: MENOR MONTO MINIMO Y MENOR ID
// -----------------------------------------------------------------------------
echo "\n--- (g) DESEMPATE DE SOLAPAMIENTO (MENOR MONTO MINIMO -> MENOR ID) ---\n";

$polyGeoJson = json_encode([
    'type' => 'Polygon',
    'coordinates' => [[
        [-73.520, 7.490],
        [-73.480, 7.490],
        [-73.480, 7.520],
        [-73.520, 7.520],
        [-73.520, 7.490]
    ]]
]);

$db->exec("DELETE FROM zona_envio WHERE nombre IN ('Zona Solapada Min 30k', 'Zona Solapada Min 20k', 'Zona Empate ID Mayor')");

$idZ1 = $zonaModel->createZona([
    'nombre' => 'Zona Solapada Min 30k',
    'monto_minimo' => 30000.00,
    'tiempo_estimado_min' => 25,
    'estado' => 'ACTIVA',
    'poligono_geojson' => $polyGeoJson
], 14);

$idZ2 = $zonaModel->createZona([
    'nombre' => 'Zona Solapada Min 20k',
    'monto_minimo' => 20000.00,
    'tiempo_estimado_min' => 25,
    'estado' => 'ACTIVA',
    'poligono_geojson' => $polyGeoJson
], 14);

$ganadoraSolapada = $zonaModel->findMatchingZone(7.500, -73.500);
assertCondition(
    "En solapamiento gana la zona con menor monto minimo ($20.000 vs $30.000)",
    $ganadoraSolapada !== null && (int)$ganadoraSolapada['id_zona'] === $idZ2,
    "Zona ganadora: {$ganadoraSolapada['nombre']} (#{$ganadoraSolapada['id_zona']})"
);

$idZ3 = $zonaModel->createZona([
    'nombre' => 'Zona Empate ID Mayor',
    'monto_minimo' => 20000.00,
    'tiempo_estimado_min' => 25,
    'estado' => 'ACTIVA',
    'poligono_geojson' => $polyGeoJson
], 14);

$ganadoraEmpate = $zonaModel->findMatchingZone(7.500, -73.500);
$menorIdEsperado = min($idZ2, $idZ3);
assertCondition(
    "En empate de monto minimo gana la zona con MENOR ID (#$menorIdEsperado)",
    $ganadoraEmpate !== null && (int)$ganadoraEmpate['id_zona'] === $menorIdEsperado,
    "Zona ganadora: {$ganadoraEmpate['nombre']} (#{$ganadoraEmpate['id_zona']})"
);

$db->exec("DELETE FROM zona_envio WHERE id_zona IN ($idZ1, $idZ2, $idZ3)");

// -----------------------------------------------------------------------------
// PRUEBA (h): EDICION DE ZONA PRESERVA TARIFA Y POST MANIPULADO NO LA CAMBIA
// -----------------------------------------------------------------------------
echo "\n--- (h) EDICION DE ZONA CONSERVA TARIFA HISTORICA ANTE POST MANIPULADO ---\n";

$tarifaAntes = (float)$db->query("SELECT costo_domicilio FROM zona_envio WHERE id_zona = 1")->fetchColumn();
echo "  - Tarifa en BD antes de editar: \$$tarifaAntes\n";

$zonaModel->updateZona(1, [
    'nombre' => 'Centro',
    'costo_domicilio' => 9999.00,
    'monto_minimo' => 0.00,
    'tiempo_estimado_min' => 20,
    'estado' => 'ACTIVA',
    'poligono_geojson' => null
], 14);

$tarifaDespues = (float)$db->query("SELECT costo_domicilio FROM zona_envio WHERE id_zona = 1")->fetchColumn();
assertCondition(
    "updateZona() NO modifico la tarifa en BD; la tarifa previa se mantiene intacta",
    $tarifaDespues === $tarifaAntes && $tarifaDespues !== 9999.00,
    "Tarifa despues de update: \$$tarifaDespues (Se intento inyectar $9999.00)"
);

// Limpieza final de pedidos de prueba
$db->exec("DELETE FROM detalle_pedido WHERE id_pedido IN ($idViejo, $idNuevoEnRango, $idNuevo, $idManipulado)");
$db->exec("DELETE FROM historial_pedido WHERE id_pedido IN ($idViejo, $idNuevoEnRango, $idNuevo, $idManipulado)");
$db->exec("DELETE FROM pago WHERE id_pedido IN ($idViejo, $idNuevoEnRango, $idNuevo, $idManipulado)");
$db->exec("DELETE FROM asignacion_domicilio WHERE id_pedido IN ($idViejo, $idNuevoEnRango, $idNuevo, $idManipulado)");
$db->exec("DELETE FROM turno_liquidacion_pedido WHERE id_pedido IN ($idViejo, $idNuevoEnRango, $idNuevo, $idManipulado)");
$db->exec("DELETE FROM pedido WHERE id_pedido IN ($idViejo, $idNuevoEnRango, $idNuevo, $idManipulado)");

echo "\n====================================================================\n";
echo "RESUMEN DE PRUEBAS DOMICILIO GRATIS:\n";
echo "  Total: " . ($passCount + $failCount) . " | Pasaron: $passCount | Fallaron: $failCount\n";
echo "====================================================================\n";

exit($failCount > 0 ? 1 : 0);
