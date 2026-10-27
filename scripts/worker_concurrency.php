<?php
/**
 * Worker para pruebas de concurrencia y deadlock
 * Modos:
 * 1. parallel_discount: Descuenta un pedido usando InventarioModel::descontarStockPedido
 * 2. inverse_lock: Adquiere locks manuales con retardo para forzar y resolver un deadlock (1213)
 * 3. same_order_race: Intenta descontar el mismo pedido simultáneamente
 */
require_once __DIR__ . '/../tests/test_bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/InventarioModel.php';

$options = getopt('', ['mode:', 'pedido:', 'worker-id:', 'insumos:', 'order:', 'delay-ms:']);

$mode = $options['mode'] ?? 'parallel_discount';
$workerId = $options['worker-id'] ?? 'W1';
$pedidoId = isset($options['pedido']) ? (int)$options['pedido'] : 0;
$order = $options['order'] ?? 'ASC'; // ASC o DESC
$delayMs = isset($options['delay-ms']) ? (int)$options['delay-ms'] : 200;

$db = getDatabaseConnection();
assertTestDatabaseOnly($db);
$invModel = new InventarioModel($db);

$result = [
    'worker' => $workerId,
    'mode' => $mode,
    'start_time' => microtime(true),
    'deadlocks_encountered' => 0,
    'retries' => 0,
    'success' => false,
    'message' => ''
];

if ($mode === 'parallel_discount') {
    // Descuento formal a través del modelo
    $res = $invModel->descontarStockPedido($pedidoId);
    $result['success'] = $res['success'];
    $result['message'] = $res['message'];
    $result['end_time'] = microtime(true);
    echo json_encode($result);
    exit;
}

if ($mode === 'inverse_lock') {
    // Insumos a bloquear en el orden especificado
    // Si order=ASC: bloquea [1, 2]. Si order=DESC: bloquea [2, 1]
    $insumos = ($order === 'DESC') ? [2, 1] : [1, 2];
    $maxRetries = 2;
    $attempt = 0;

    while ($attempt < $maxRetries) {
        $attempt++;
        try {
            $db->beginTransaction();

            // Bloquear primer insumo
            $first = $insumos[0];
            $stmt1 = $db->prepare("SELECT id_insumo, cantidad FROM inventario_insumo WHERE id_insumo = ? FOR UPDATE");
            $stmt1->execute([$first]);
            $row1 = $stmt1->fetch(PDO::FETCH_ASSOC);

            // Pausa controlada para garantizar que el otro proceso tome el otro lock
            usleep($delayMs * 1000);

            // Bloquear segundo insumo (aquí se dispara el Deadlock en el proceso que colisiona)
            $second = $insumos[1];
            $stmt2 = $db->prepare("SELECT id_insumo, cantidad FROM inventario_insumo WHERE id_insumo = ? FOR UPDATE");
            $stmt2->execute([$second]);
            $row2 = $stmt2->fetch(PDO::FETCH_ASSOC);

            // Descontar una cantidad simbólica
            $db->prepare("UPDATE inventario_insumo SET cantidad = cantidad - 0.100 WHERE id_insumo = ?")->execute([$first]);
            $db->prepare("UPDATE inventario_insumo SET cantidad = cantidad - 0.100 WHERE id_insumo = ?")->execute([$second]);

            $db->commit();
            $result['success'] = true;
            $result['message'] = "Locks adquiridos exitosamente en orden " . implode('->', $insumos) . " (Intento $attempt)";
            break;

        } catch (PDOException $e) {
            // MySQL 1213: Deadlock found when trying to get lock; try restarting transaction
            $isDeadlock = ($e->getCode() == '40001' || strpos($e->getMessage(), '1213') !== false);
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            if ($isDeadlock) {
                $result['deadlocks_encountered']++;
                if ($attempt < $maxRetries) {
                    $result['retries']++;
                    // Pausa de 120ms antes de reintentar
                    usleep(120000);
                    continue;
                }
            }

            $result['success'] = false;
            $result['message'] = "Error PDO: " . $e->getMessage();
            break;
        }
    }

    $result['end_time'] = microtime(true);
    echo json_encode($result);
    exit;
}
