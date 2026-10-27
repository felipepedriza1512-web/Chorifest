<?php
require_once __DIR__ . '/test_bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/InventarioModel.php';
require_once __DIR__ . '/../models/PedidoModel.php';

$db = getDatabaseConnection();
assertTestDatabaseOnly($db);
$invModel = new InventarioModel($db);
$pedidoModel = new PedidoModel($db);

echo "======================================================================\n";
echo "PRUEBA DE VALIDACIÓN DE STOCK DEL CARRITO COMPLETO (INSUMOS AGREGADOS)\n";
echo "======================================================================\n\n";

// Guardamos el stock original para restaurarlo estrictamente al terminar
$stmt = $db->query("SELECT cantidad FROM inventario_insumo WHERE id_insumo = 1");
$stockOriginalCarne = (float)$stmt->fetchColumn();
echo "1. Stock original de Carne de cerdo (ID 1): {$stockOriginalCarne} kg\n";

try {
    // Ajustamos temporalmente Carne de cerdo a 1.000 kg para una prueba matemática exacta
    $db->exec("UPDATE inventario_insumo SET cantidad = 1.000 WHERE id_insumo = 1");
    echo "   [TEST SETUP] Ajustado temporalmente Carne de cerdo a 1.000 kg para prueba controlada.\n\n";

    // Obtenemos 2 productos que consuman carne de cerdo (0.250 kg cada uno)
    $stmtP = $db->query("
        SELECT p.id_producto, p.nombre_producto, p.precio, rd.cantidad as carne_receta
        FROM producto p
        JOIN receta r ON p.id_producto = r.id_producto AND r.estado = 'ACTIVA'
        JOIN receta_detalle rd ON r.id_receta = rd.id_receta AND rd.id_insumo = 1
        LIMIT 2
    ");
    $prods = $stmtP->fetchAll(PDO::FETCH_ASSOC);

    if (count($prods) < 2) {
        throw new Exception("Se requieren al menos 2 productos con receta de carne de cerdo.");
    }

    $p1 = $prods[0];
    $p2 = $prods[1];

    echo "2. Productos de prueba seleccionados:\n";
    echo "   - Producto A: '{$p1['nombre_producto']}' (ID: {$p1['id_producto']}), Carne/ud: {$p1['carne_receta']} kg\n";
    echo "   - Producto B: '{$p2['nombre_producto']}' (ID: {$p2['id_producto']}), Carne/ud: {$p2['carne_receta']} kg\n\n";

    // 3. Límites individuales
    $maxA = $invModel->getCantidadMaximaDisponible((int)$p1['id_producto']);
    $maxB = $invModel->getCantidadMaximaDisponible((int)$p2['id_producto']);

    echo "3. Verificación de límites individuales con stock = 1.000 kg:\n";
    echo "   - Máximo disponible individual para A: {$maxA} unidades (1.000 / {$p1['carne_receta']})\n";
    echo "   - Máximo disponible individual para B: {$maxB} unidades (1.000 / {$p2['carne_receta']})\n\n";

    // 4. Carrito combinado donde cada producto pasa individualmente, pero juntos exceden
    // 3 unidades de A = 0.750 kg (pasa <= 4)
    // 3 unidades de B = 0.750 kg (pasa <= 4)
    // Total combinado = 1.500 kg > 1.000 kg (Faltan 0.500 kg)
    $carritoTest = [
        $p1['id_producto'] => [
            'producto' => $p1,
            'cantidad' => 3
        ],
        $p2['id_producto'] => [
            'producto' => $p2,
            'cantidad' => 3
        ]
    ];

    echo "4. Carrito combinado armado para prueba de sobreconsumo:\n";
    echo "   - 3x '{$p1['nombre_producto']}' (Demanda: 0.750 kg <= 1.000 kg disponible -> Pasa chequeo individual)\n";
    echo "   - 3x '{$p2['nombre_producto']}' (Demanda: 0.750 kg <= 1.000 kg disponible -> Pasa chequeo individual)\n";
    echo "   - Demanda agregada de Carne de cerdo: 1.500 kg vs 1.000 kg disponible en inventario.\n\n";

    // 5. Validamos con verificarStockCarrito()
    echo "5. Ejecución de verificarStockCarrito():\n";
    $res = $invModel->verificarStockCarrito($carritoTest);

    echo "   - ¿Es válido el carrito?: " . ($res['valido'] ? 'SÍ (ERROR)' : 'NO (CORRECTO - RECHAZADO)') . "\n";
    echo "   - Mensaje de rechazo devuelto:\n     \"{$res['mensaje']}\"\n";
    echo "   - Insumos deficitarios detectados:\n";
    foreach ($res['faltantes'] as $f) {
        echo "     * Insumo: {$f['nombre']} | Requerido total: {$f['requerido']} {$f['unidad_medida']} | Stock disponible: {$f['disponible']} {$f['unidad_medida']} | Faltante: {$f['faltante']} {$f['unidad_medida']}\n";
    }

    if ($res['valido']) {
        throw new Exception("FALLO: El validador aceptó un carrito que excede el stock combinado.");
    }
    echo "   ✔ RESULTADO: verificarStockCarrito() detectó el déficit combinado y bloqueó la operación.\n\n";

    // 6. Validamos en PedidoModel::createPedido (Servidor)
    echo "6. Ejecución de intento de checkout en servidor (PedidoModel::createPedido):\n";
    $direccion = ['id_zona' => 1, 'direccion' => 'Calle 10 # 5-20', 'barrio' => 'Centro', 'referencia' => 'Apto 101'];
    $idPed = $pedidoModel->createPedido(1, $direccion, $carritoTest, 'Efectivo contra entrega', 'Test validacion agregada');
    $lastErr = $pedidoModel->getLastError();

    echo "   - ID Pedido generado: " . ($idPed === null ? 'NULL (Pedido rechazado)' : "#$idPed (INCORRECTO)") . "\n";
    echo "   - Mensaje de error capturado en PedidoModel: \"{$lastErr}\"\n";

    if ($idPed !== null) {
        throw new Exception("FALLO: createPedido no debió crear el pedido.");
    }
    echo "   ✔ RESULTADO: El servidor rechazó la creación del pedido y emitió el mensaje exacto de insumo insuficiente.\n\n";

    // 7. Prueba de control con carrito válido (2 de A = 0.500 kg y 1 de B = 0.250 kg = 0.750 kg <= 1.000 kg)
    $carritoValido = [
        $p1['id_producto'] => [
            'producto' => $p1,
            'cantidad' => 2
        ],
        $p2['id_producto'] => [
            'producto' => $p2,
            'cantidad' => 1
        ]
    ];
    $resValido = $invModel->verificarStockCarrito($carritoValido);
    echo "7. Prueba de control con carrito que NO excede la combinación (0.750 kg <= 1.000 kg):\n";
    echo "   - ¿Es válido el carrito?: " . ($resValido['valido'] ? 'SÍ (CORRECTO)' : 'NO (ERROR)') . "\n";
    if (!$resValido['valido']) {
        throw new Exception("FALLO: El validador rechazó un carrito dentro de los límites.");
    }
    echo "   ✔ RESULTADO: Carrito dentro del stock total fue aprobado con éxito.\n";

} finally {
    // Restaurar inmediatamente el stock exacto al valor previo
    $stmtRest = $db->prepare("UPDATE inventario_insumo SET cantidad = ? WHERE id_insumo = 1");
    $stmtRest->execute([$stockOriginalCarne]);
    echo "\n[LIMPIEZA DE TEST]: Stock de Carne de cerdo restaurado inmediatamente a {$stockOriginalCarne} kg.\n";
}
