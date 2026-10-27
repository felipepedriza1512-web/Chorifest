<?php
/**
 * Master runner para pruebas de concurrencia real y deadlock
 */
require_once __DIR__ . '/../tests/test_bootstrap.php';
require_once __DIR__ . '/../config/database.php';

$db = getDatabaseConnection();
assertTestDatabaseOnly($db);

echo "======================================================================\n";
echo "PRUEBAS DE CONCURRENCIA REAL Y DEADLOCK (PROCESOS PARALELOS EN TIEMPO REAL)\n";
echo "======================================================================\n\n";

// Guardar stock original de insumos 1 y 2
$s1Orig = (float)$db->query("SELECT cantidad FROM inventario_insumo WHERE id_insumo = 1")->fetchColumn();
$s2Orig = (float)$db->query("SELECT cantidad FROM inventario_insumo WHERE id_insumo = 2")->fetchColumn();

try {
    // -------------------------------------------------------------------------
    // TEST 1: DEADLOCK REAL CON ORDEN INVERSO DE INSUMOS Y REINTENTO AUTOMÁTICO
    // -------------------------------------------------------------------------
    echo "--- PARTE 1: DEADLOCK REAL CON ORDEN INVERSO Y REINTENTO AUTOMÁTICO ---\n";
    echo "Escenario: Dos procesos paralelos compiten por los insumos #1 y #2.\n";
    echo "  - Proceso A: bloquea Insumo 1, espera 200ms, y luego intenta bloquear Insumo 2.\n";
    echo "  - Proceso B: bloquea Insumo 2, espera 200ms, y luego intenta bloquear Insumo 1 (Orden inverso).\n";
    echo "  - MySQL detecta el ciclo de espera (Deadlock 1213) en uno de los dos procesos.\n";
    echo "  - El proceso afectado captura el error 1213, hace rollback, reintenta y completa con éxito.\n\n";

    $cmdA = 'php ' . escapeshellarg(__DIR__ . '/worker_concurrency.php') . ' --mode=inverse_lock --worker-id=Proceso_A --order=ASC --delay-ms=250';
    $cmdB = 'php ' . escapeshellarg(__DIR__ . '/worker_concurrency.php') . ' --mode=inverse_lock --worker-id=Proceso_B --order=DESC --delay-ms=250';

    $desc = [
        0 => ["pipe", "r"],
        1 => ["pipe", "w"],
        2 => ["pipe", "w"]
    ];

    $startTime = microtime(true);
    // Lanzamos ambos procesos en paralelo de inmediato
    $procA = proc_open($cmdA, $desc, $pipesA);
    $procB = proc_open($cmdB, $desc, $pipesB);

    $outA = stream_get_contents($pipesA[1]);
    $errA = stream_get_contents($pipesA[2]);
    fclose($pipesA[0]); fclose($pipesA[1]); fclose($pipesA[2]);
    $statusA = proc_close($procA);

    $outB = stream_get_contents($pipesB[1]);
    $errB = stream_get_contents($pipesB[2]);
    fclose($pipesB[0]); fclose($pipesB[1]); fclose($pipesB[2]);
    $statusB = proc_close($procB);

    $jsonA = json_decode(trim($outA), true);
    $jsonB = json_decode(trim($outB), true);

    echo "Salida Proceso A:\n";
    echo "  - Estado: " . ($jsonA['success'] ? 'EXITOSO' : 'FALLIDO') . "\n";
    echo "  - Mensaje: " . ($jsonA['message'] ?? $errA) . "\n";
    echo "  - Deadlocks encontrados: " . ($jsonA['deadlocks_encountered'] ?? 0) . " | Reintentos: " . ($jsonA['retries'] ?? 0) . "\n\n";

    echo "Salida Proceso B (Orden Inverso):\n";
    echo "  - Estado: " . ($jsonB['success'] ? 'EXITOSO' : 'FALLIDO') . "\n";
    echo "  - Mensaje: " . ($jsonB['message'] ?? $errB) . "\n";
    echo "  - Deadlocks encontrados: " . ($jsonB['deadlocks_encountered'] ?? 0) . " | Reintentos: " . ($jsonB['retries'] ?? 0) . "\n\n";

    $deadlocksTotal = ($jsonA['deadlocks_encountered'] ?? 0) + ($jsonB['deadlocks_encountered'] ?? 0);
    $retriesTotal = ($jsonA['retries'] ?? 0) + ($jsonB['retries'] ?? 0);

    echo "Resumen Concurrencia Parte 1:\n";
    echo "  - Total Deadlocks 1213 detectados por MySQL: {$deadlocksTotal}\n";
    echo "  - Total Reintentos automáticos ejecutados: {$retriesTotal}\n";
    echo "  - Ambos procesos terminaron con éxito: " . (($jsonA['success'] && $jsonB['success']) ? 'SÍ (PASÓ)' : 'NO (FALLÓ)') . "\n\n";

    // -------------------------------------------------------------------------
    // TEST 2: EMPAQUE SIMULTÁNEO DE DOS PEDIDOS QUE COMPARTEN INGREDIENTES
    // -------------------------------------------------------------------------
    echo "--- PARTE 2: DOS PEDIDOS EMPACADOS EN PARALELO (COMPARTIENDO INSUMOS) ---\n";
    
    // Crear dos pedidos de prueba temporales con recetas
    // Ambos piden Chorizo Santarrosano (ID 1, Carne 0.250kg, Tripa 1m, Sal 0.005kg, Ajo 0.002kg)
    // Pedido 1: 2 unidades (Carne: 0.500kg, Tripa: 2m)
    // Pedido 2: 3 unidades (Carne: 0.750kg, Tripa: 3m)
    // Total a descontar: Carne = 1.250kg, Tripa = 5m

    $sCarnePre = (float)$db->query("SELECT cantidad FROM inventario_insumo WHERE id_insumo = 1")->fetchColumn();
    $sTripaPre = (float)$db->query("SELECT cantidad FROM inventario_insumo WHERE id_insumo = 2")->fetchColumn();

    echo "Stock ANTES del empaque paralelo:\n";
    echo "  - Carne de cerdo: {$sCarnePre} kg\n";
    echo "  - Tripa natural:  {$sTripaPre} m\n\n";

    // Insertar temporalmente 2 pedidos
    $db->exec("INSERT INTO pedido (id_cliente, id_direccion, id_zona, id_estado, stock_descontado, fecha, subtotal, total, observaciones) 
               VALUES (1, 1, 1, 3, 0, NOW(), 28000, 28000, 'Test Concurrencia Pedido A')");
    $idPedA = (int)$db->lastInsertId();

    $db->exec("INSERT INTO detalle_pedido (id_pedido, id_producto, nombre_producto, cantidad, precio_unitario, subtotal)
               VALUES ($idPedA, 1, 'Chorizo Santandereano', 2, 14000, 28000)");

    $db->exec("INSERT INTO pedido (id_cliente, id_direccion, id_zona, id_estado, stock_descontado, fecha, subtotal, total, observaciones) 
               VALUES (1, 1, 1, 3, 0, NOW(), 42000, 42000, 'Test Concurrencia Pedido B')");
    $idPedB = (int)$db->lastInsertId();

    $db->exec("INSERT INTO detalle_pedido (id_pedido, id_producto, nombre_producto, cantidad, precio_unitario, subtotal)
               VALUES ($idPedB, 1, 'Chorizo Santandereano', 3, 14000, 42000)");

    echo "Pedidos temporales creados para empaque paralelo:\n";
    echo "  - Pedido #$idPedA: 2x Chorizo Santandereano (demanda: Carne 0.500 kg, Tripa 2.000 m)\n";
    echo "  - Pedido #$idPedB: 3x Chorizo Santandereano (demanda: Carne 0.750 kg, Tripa 3.000 m)\n";
    echo "  - Demanda combinada esperada: Carne 1.250 kg, Tripa 5.000 m\n\n";

    // Lanzamos ambos descuentos simultáneamente en procesos paralelos separados
    $cmdEmpA = 'php ' . escapeshellarg(__DIR__ . '/worker_concurrency.php') . " --mode=parallel_discount --worker-id=Empaque_Ped_$idPedA --pedido=$idPedA";
    $cmdEmpB = 'php ' . escapeshellarg(__DIR__ . '/worker_concurrency.php') . " --mode=parallel_discount --worker-id=Empaque_Ped_$idPedB --pedido=$idPedB";

    $procEmpA = proc_open($cmdEmpA, $desc, $pipesEmpA);
    $procEmpB = proc_open($cmdEmpB, $desc, $pipesEmpB);

    $outEmpA = stream_get_contents($pipesEmpA[1]);
    fclose($pipesEmpA[0]); fclose($pipesEmpA[1]); fclose($pipesEmpA[2]);
    proc_close($procEmpA);

    $outEmpB = stream_get_contents($pipesEmpB[1]);
    fclose($pipesEmpB[0]); fclose($pipesEmpB[1]); fclose($pipesEmpB[2]);
    proc_close($procEmpB);

    $jsonEmpA = json_decode(trim($outEmpA), true);
    $jsonEmpB = json_decode(trim($outEmpB), true);

    echo "Resultados del empaque en paralelo:\n";
    echo "  - Pedido #$idPedA: " . ($jsonEmpA['success'] ? 'ÉXITO' : 'FALLO') . " ({$jsonEmpA['message']})\n";
    echo "  - Pedido #$idPedB: " . ($jsonEmpB['success'] ? 'ÉXITO' : 'FALLO') . " ({$jsonEmpB['message']})\n\n";

    $sCarnePost = (float)$db->query("SELECT cantidad FROM inventario_insumo WHERE id_insumo = 1")->fetchColumn();
    $sTripaPost = (float)$db->query("SELECT cantidad FROM inventario_insumo WHERE id_insumo = 2")->fetchColumn();

    $descCarne = $sCarnePre - $sCarnePost;
    $descTripa = $sTripaPre - $sTripaPost;

    echo "Stock DESPUÉS del empaque paralelo:\n";
    echo "  - Carne de cerdo: {$sCarnePost} kg (Descuento real: {$descCarne} kg | Esperado: 1.250 kg)\n";
    echo "  - Tripa natural:  {$sTripaPost} m  (Descuento real: {$descTripa} m  | Esperado: 5.000 m)\n\n";

    $flagA = (int)$db->query("SELECT stock_descontado FROM pedido WHERE id_pedido = $idPedA")->fetchColumn();
    $flagB = (int)$db->query("SELECT stock_descontado FROM pedido WHERE id_pedido = $idPedB")->fetchColumn();
    echo "Banderas stock_descontado: Pedido #$idPedA = $flagA | Pedido #$idPedB = $flagB\n";

    $precisionOk = (abs($descCarne - 1.250) < 0.001) && (abs($descTripa - 5.000) < 0.001);
    echo "Resultado Parte 2: " . ($precisionOk && $flagA === 1 && $flagB === 1 ? "✔ PASÓ EXACTO SIN CONDICIÓN DE CARRERA" : "❌ FALLÓ") . "\n\n";

    // -------------------------------------------------------------------------
    // TEST 3: INTENTO DE DOBLE DESCUENTO SIMULTÁNEO SOBRE EL MISMO PEDIDO (IDEMPOTENCIA)
    // -------------------------------------------------------------------------
    echo "--- PARTE 3: PREVENCIÓN DE DOBLE DESCUENTO CONCURRENTE EN EL MISMO PEDIDO ---\n";
    echo "Escenario: Dos procesos paralelos compiten por empaquetar el MISMO pedido #$idPedA al mismo instante.\n";
    
    // Resetear temporalmente stock_descontado = 0 para simular la carrera
    $db->exec("UPDATE pedido SET stock_descontado = 0 WHERE id_pedido = $idPedA");
    $sCarneRacePre = (float)$db->query("SELECT cantidad FROM inventario_insumo WHERE id_insumo = 1")->fetchColumn();

    $cmdRace1 = 'php ' . escapeshellarg(__DIR__ . '/worker_concurrency.php') . " --mode=parallel_discount --worker-id=Hebra_1 --pedido=$idPedA";
    $cmdRace2 = 'php ' . escapeshellarg(__DIR__ . '/worker_concurrency.php') . " --mode=parallel_discount --worker-id=Hebra_2 --pedido=$idPedA";

    $pRace1 = proc_open($cmdRace1, $desc, $pipesR1);
    $pRace2 = proc_open($cmdRace2, $desc, $pipesR2);

    $outR1 = stream_get_contents($pipesR1[1]);
    fclose($pipesR1[0]); fclose($pipesR1[1]); fclose($pipesR1[2]);
    proc_close($pRace1);

    $outR2 = stream_get_contents($pipesR2[1]);
    fclose($pipesR2[0]); fclose($pipesR2[1]); fclose($pipesR2[2]);
    proc_close($pRace2);

    $jR1 = json_decode(trim($outR1), true);
    $jR2 = json_decode(trim($outR2), true);

    echo "Respuesta Hebra 1: {$jR1['message']}\n";
    echo "Respuesta Hebra 2: {$jR2['message']}\n";

    $sCarneRacePost = (float)$db->query("SELECT cantidad FROM inventario_insumo WHERE id_insumo = 1")->fetchColumn();
    $descRace = $sCarneRacePre - $sCarneRacePost;

    echo "Descuento aplicado en stock: {$descRace} kg (Esperado: exactamente 0.500 kg, NO 1.000 kg)\n";
    $noDobleDescuento = (abs($descRace - 0.500) < 0.001);
    echo "Resultado Parte 3: " . ($noDobleDescuento ? "✔ PASÓ (SIN DOBLE DESCUENTO, IDEMPOTENCIA CONCURRENTE GARANTIZADA)" : "❌ FALLÓ DOBLE DESCUENTO") . "\n\n";

    // -------------------------------------------------------------------------
    // LIMPIEZA INMEDIATA DE DATOS GENERADOS POR EL TEST
    // -------------------------------------------------------------------------
    echo "--- LIMPIEZA DE PRUEBAS TEMPORALES ---\n";
    // Eliminar movimientos de los pedidos de prueba
    $db->exec("DELETE FROM movimiento_inventario WHERE id_pedido IN ($idPedA, $idPedB)");
    $db->exec("DELETE FROM detalle_pedido WHERE id_pedido IN ($idPedA, $idPedB)");
    $db->exec("DELETE FROM pedido WHERE id_pedido IN ($idPedA, $idPedB)");
    echo "  ✔ Pedidos de prueba #$idPedA y #$idPedB y sus movimientos eliminados.\n";

} finally {
    // Restaurar stock exacto de insumos 1 y 2 al valor que tenían antes del test
    $stmtRest1 = $db->prepare("UPDATE inventario_insumo SET cantidad = ? WHERE id_insumo = 1");
    $stmtRest1->execute([$s1Orig]);
    $stmtRest2 = $db->prepare("UPDATE inventario_insumo SET cantidad = ? WHERE id_insumo = 2");
    $stmtRest2->execute([$s2Orig]);
    echo "  ✔ Stock restaurado exactamente a su estado inicial: Insumo 1 = {$s1Orig} kg, Insumo 2 = {$s2Orig} m.\n";
    echo "======================================================================\n";
}
