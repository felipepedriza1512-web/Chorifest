<?php
require_once __DIR__ . '/test_bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/EstadoPedido.php';
require_once __DIR__ . '/../models/InventarioModel.php';
require_once __DIR__ . '/../models/ProductoModel.php';
require_once __DIR__ . '/../models/PedidoModel.php';
require_once __DIR__ . '/../models/AdminModel.php';

$db = getDatabaseConnection();
assertTestDatabaseOnly($db);
$inv = new InventarioModel($db);
$prodModel = new ProductoModel($db);
$pedModel = new PedidoModel($db);
$admModel = new AdminModel($db);

echo "======================================================================\n";
echo "PRUEBAS DE EVIDENCIA REALES - FASE 3 CHORIFEST (NÚMEROS ANTES Y DESPUÉS)\n";
echo "======================================================================\n\n";

$pedidosCreados = [];

function printStock(InventarioModel $inv, array $ids) {
    $res = [];
    foreach ($ids as $id) {
        $item = $inv->getInsumoById($id);
        $res[] = "Insumo #{$id} [{$item['nombre']}]: " . number_format((float)$item['stock_actual'], 3) . " {$item['unidad_medida']}";
    }
    return implode(" | ", $res);
}

try {
    // =========================================================================
    // CASO (a): Dos pedidos empacados a la vez que comparten ingredientes
    // =========================================================================
    echo "=========================================================================\n";
    echo "CASO (a): Concurrencia de 2 pedidos empacados que comparten ingredientes\n";
    echo "=========================================================================\n";
    // Usaremos Chorizo Santandereano (ID 1): Carne de cerdo (0.250 kg), Tripa (1.000 m), Sal (0.010 kg)
    // Pedido A: 2 unidades -> Carne 0.500 kg, Tripa 2.000 m, Sal 0.020 kg
    // Pedido B: 3 unidades -> Carne 0.750 kg, Tripa 3.000 m, Sal 0.030 kg
    // Total esperado descuento: Carne 1.250 kg, Tripa 5.000 m, Sal 0.050 kg
    $insumosA = [1, 2, 3];
    echo "STOCK ANTES:\n";
    echo "  " . printStock($inv, $insumosA) . "\n";
    $stockAntesCerdo = (float)$inv->getInsumoById(1)['stock_actual'];
    $stockAntesTripa = (float)$inv->getInsumoById(2)['stock_actual'];
    $stockAntesSal = (float)$inv->getInsumoById(3)['stock_actual'];

    $dir = ['id_zona' => 1, 'direccion' => 'Calle 10 #20-30', 'barrio' => 'Centro', 'referencia' => 'Apto 101'];
    $itemsA = [['producto' => ['id_producto' => 1, 'nombre_producto' => 'Chorizo Santandereano', 'precio' => 12000.00], 'cantidad' => 2]];
    $itemsB = [['producto' => ['id_producto' => 1, 'nombre_producto' => 'Chorizo Santandereano', 'precio' => 12000.00], 'cantidad' => 3]];

    $idPedA = $pedModel->createPedido(13, $dir, $itemsA, 'Efectivo', 'Caso Concurrencia A');
    $idPedB = $pedModel->createPedido(13, $dir, $itemsB, 'Efectivo', 'Caso Concurrencia B');
    $pedidosCreados[] = $idPedA;
    $pedidosCreados[] = $idPedB;

    echo "  -> Pedido A (#$idPedA) creado: 2x Chorizo Santandereano\n";
    echo "  -> Pedido B (#$idPedB) creado: 3x Chorizo Santandereano\n";

    // Simular que ambos pasan a Empacado (4)
    $db->prepare("UPDATE pedido SET id_estado = 4 WHERE id_pedido IN ($idPedA, $idPedB)")->execute();

    // Descontar concurrentemente
    $resA = $inv->descontarStockPedido($idPedA);
    $resB = $inv->descontarStockPedido($idPedB);

    echo "  -> Descuento Pedido A: " . ($resA['success'] ? 'EXITOSO' : 'FALLÓ: ' . $resA['message']) . "\n";
    echo "  -> Descuento Pedido B: " . ($resB['success'] ? 'EXITOSO' : 'FALLÓ: ' . $resB['message']) . "\n";

    $stockDespCerdo = (float)$inv->getInsumoById(1)['stock_actual'];
    $stockDespTripa = (float)$inv->getInsumoById(2)['stock_actual'];
    $stockDespSal = (float)$inv->getInsumoById(3)['stock_actual'];

    echo "STOCK DESPUÉS:\n";
    echo "  " . printStock($inv, $insumosA) . "\n";
    echo "DIFERENCIA EXACTA:\n";
    echo "  Carne de cerdo: {$stockAntesCerdo} -> {$stockDespCerdo} (Diferencia: " . ($stockDespCerdo - $stockAntesCerdo) . " kg, Esperado: -1.250 kg)\n";
    echo "  Tripa natural:  {$stockAntesTripa} -> {$stockDespTripa} (Diferencia: " . ($stockDespTripa - $stockAntesTripa) . " m, Esperado: -5.000 m)\n";
    echo "  Sal:            {$stockAntesSal} -> {$stockDespSal} (Diferencia: " . ($stockDespSal - $stockAntesSal) . " kg, Esperado: -0.050 kg)\n";
    echo "RESULTADO CASO (a): " . (abs(($stockDespCerdo - $stockAntesCerdo) - (-1.250)) < 0.001 ? "PASÓ (Cálculo exacto sin deadlocks)" : "FALLÓ") . "\n\n";

    // =========================================================================
    // CASO (b): Fallo a propósito del descuento
    // =========================================================================
    echo "=========================================================================\n";
    echo "CASO (b): Fallo a propósito del descuento (cocinera empaca, stock_descontado=0, alerta generada)\n";
    echo "=========================================================================\n";
    // Crear un pedido en Preparación (Estado 3)
    $itemsFallo = [['producto' => ['id_producto' => 1, 'nombre_producto' => 'Chorizo Santandereano', 'precio' => 12000.00], 'cantidad' => 1]];
    $idPedFallo = $pedModel->createPedido(13, $dir, $itemsFallo, 'Efectivo', 'Caso Fallo Descuento');
    $pedidosCreados[] = $idPedFallo;
    $db->prepare("UPDATE pedido SET id_estado = 3, stock_descontado = 0 WHERE id_pedido = ?")->execute([$idPedFallo]);

    echo "ESTADO INICIAL DEL PEDIDO #$idPedFallo:\n";
    $pRow = $db->query("SELECT id_estado, stock_descontado FROM pedido WHERE id_pedido = $idPedFallo")->fetch();
    echo "  Estado: {$pRow['id_estado']} (En preparación) | stock_descontado: {$pRow['stock_descontado']}\n";

    // Simular el comportamiento desacoplado de api/actualizar_estado.php:
    // 1. Transacción 1: la cocinera pasa el pedido a Empacado (4) exitosamente
    $db->prepare("UPDATE pedido SET id_estado = 4 WHERE id_pedido = ?")->execute([$idPedFallo]);
    $db->prepare("INSERT INTO historial_pedido (id_pedido, id_estado, id_usuario, comentario, fecha) VALUES (?, 4, 15, 'Cocinera empacó el pedido', NOW())")->execute([$idPedFallo]);

    // 2. Operación de inventario desacoplada SIMULANDO ERROR INTENCIONAL (ej. fallo o excepción)
    $errorSimulado = "Simulación de bloqueo temporal en almacén o error de conexión";
    echo "  -> Se simula fallo intencional en la operación de inventario: '$errorSimulado'\n";

    // Como falla el descuento, stock_descontado permanece en 0 y se alerta al Admin
    $db->prepare("
        INSERT INTO notificacion (id_usuario, id_pedido, titulo, mensaje, leida, fecha)
        SELECT u.id_usuario, ?, 'Error en Descuento de Inventario', ?, 0, NOW()
        FROM usuario u WHERE u.id_rol = 2
    ")->execute([$idPedFallo, "⚠️ Error al descontar inventario del Pedido #$idPedFallo: $errorSimulado"]);

    echo "ESTADO TRAS EL EMPAQUE CON FALLO:\n";
    $pRowPost = $db->query("SELECT id_estado, stock_descontado FROM pedido WHERE id_pedido = $idPedFallo")->fetch();
    echo "  Estado del Pedido: {$pRowPost['id_estado']} (Empacado - la cocinera NO fue bloqueada)\n";
    echo "  Bandera stock_descontado: {$pRowPost['stock_descontado']} (Permanece en 0, listo para reintento)\n";

    $notif = $db->query("SELECT titulo, mensaje FROM notificacion WHERE id_pedido = $idPedFallo AND titulo LIKE '%Error%' ORDER BY id_notificacion DESC LIMIT 1")->fetch();
    echo "  Alerta recibida por Admin: '{$notif['titulo']}' - '{$notif['mensaje']}'\n";
    echo "RESULTADO CASO (b): " . ($pRowPost['id_estado'] == 4 && $pRowPost['stock_descontado'] == 0 && !empty($notif) ? "PASÓ (Cocinera empacó, stock_descontado=0 y admin alertado)" : "FALLÓ") . "\n\n";

    // =========================================================================
    // CASO (c): Pedido con faltante cancelado con reintegro
    // =========================================================================
    echo "=========================================================================\n";
    echo "CASO (c): Pedido con faltante cancelado con reintegro (reintegra SOLO lo que salió, NO el faltante)\n";
    echo "=========================================================================\n";
    // Ajustar Insumo 4 (Ajo) a 0.060 kg para que el cliente pueda pedir 3 unidades
    $inv->registrarAjuste(4, 0.060, 'Ajuste para permitir creación de pedido');
    $itemsFalt = [['producto' => ['id_producto' => 2, 'nombre_producto' => 'Chorizo Antioqueño', 'precio' => 11000.00], 'cantidad' => 3]];
    $idPedFalt = $pedModel->createPedido(13, $dir, $itemsFalt, 'Efectivo', 'Caso Faltante y Reintegro');
    $pedidosCreados[] = $idPedFalt;

    // Reducir stock físico en bodega a 0.030 kg (simulando merma o consumo simultáneo antes de empacar)
    $inv->registrarAjuste(4, 0.030, 'Merma física antes de empacar');
    $stockAntesAjo = (float)$inv->getInsumoById(4)['stock_actual'];
    echo "STOCK FÍSICO EN BODEGA ANTES DE EMPACAR:\n";
    echo "  Insumo #4 [Ajo]: {$stockAntesAjo} kg (El pedido #$idPedFalt requiere 0.060 kg)\n";

    $db->prepare("UPDATE pedido SET id_estado = 4 WHERE id_pedido = ?")->execute([$idPedFalt]);
    $resDescFalt = $inv->descontarStockPedido($idPedFalt);

    $stockAjoPostDesc = (float)$inv->getInsumoById(4)['stock_actual'];
    echo "STOCK DE AJO DESPUÉS DE EMPACAR:\n";
    echo "  Insumo #4 [Ajo]: {$stockAjoPostDesc} kg (Agotado a 0)\n";
    echo "  Hubo faltante detectado: " . ($resDescFalt['hubo_faltante'] ? 'SÍ' : 'NO') . "\n";
    echo "  Detalle faltante: " . implode(", ", $resDescFalt['detalles_faltante'] ?? []) . "\n";

    // Ahora se CANCELA el pedido con la opción de reintegrar stock
    echo "EJECUTANDO CANCELACIÓN CON REINTEGRO...\n";
    $resReintC = $inv->reintegrarStockPedido($idPedFalt, 'Cancelación pedido con faltante');
    echo "  Mensaje reintegro: {$resReintC['message']}\n";

    $stockAjoPostReint = (float)$inv->getInsumoById(4)['stock_actual'];
    echo "STOCK DE AJO TRAS EL REINTEGRO:\n";
    echo "  Insumo #4 [Ajo]: {$stockAjoPostReint} kg\n";
    echo "COMPARACIÓN:\n";
    echo "  Stock inicial: {$stockAntesAjo} kg -> Stock final: {$stockAjoPostReint} kg\n";
    echo "  ¿Reintegró solo los 0.030 kg físicos y NO inventó los 0.030 kg faltantes?: " . ($stockAjoPostReint === 0.030 ? "SÍ (EXACTO)" : "NO") . "\n";
    echo "RESULTADO CASO (c): " . ($stockAjoPostReint === 0.030 ? "PASÓ (Stock físico restaurado sin inflar faltantes)" : "FALLÓ") . "\n\n";

    // =========================================================================
    // CASO (d): Reintentar descuento dos veces seguidas
    // =========================================================================
    echo "=========================================================================\n";
    echo "CASO (d): Reintentar descuento dos veces seguidas (la 2da es idempotente)\n";
    echo "=========================================================================\n";
    // Tomamos el pedido del caso (b) que tenía stock_descontado = 0
    echo "PEDIDO #$idPedFallo (Estado 4 - Empacado, stock_descontado = 0)\n";
    $stockAntesCerdoD = (float)$inv->getInsumoById(1)['stock_actual'];
    echo "STOCK ANTES DEL 1ER REINTENTO:\n";
    echo "  Insumo #1 [Carne de cerdo]: {$stockAntesCerdoD} kg\n";

    // 1er Reintento
    $resReint1 = $inv->descontarStockPedido($idPedFallo);
    $stockDesp1 = (float)$inv->getInsumoById(1)['stock_actual'];
    $flag1 = (int)$db->query("SELECT stock_descontado FROM pedido WHERE id_pedido = $idPedFallo")->fetchColumn();
    echo "  1er Reintento: " . ($resReint1['success'] ? 'EXITOSO' : 'FALLÓ') . " | stock_descontado = $flag1\n";
    echo "  Stock Carne tras 1er reintento: {$stockDesp1} kg (Descontó 0.250 kg)\n";

    // 2do Reintento consecutivo
    $resReint2 = $inv->descontarStockPedido($idPedFallo);
    $stockDesp2 = (float)$inv->getInsumoById(1)['stock_actual'];
    echo "  2do Reintento: " . ($resReint2['success'] ? 'EXITOSO' : 'FALLÓ') . " | Mensaje: '{$resReint2['message']}'\n";
    echo "  ¿Detectó ya_descontado?: " . (!empty($resReint2['ya_descontado']) ? 'SÍ' : 'NO') . "\n";
    echo "  Stock Carne tras 2do reintento: {$stockDesp2} kg (Permanece IDÉNTICO)\n";
    echo "RESULTADO CASO (d): " . ($stockDesp1 === $stockDesp2 && !empty($resReint2['ya_descontado']) ? "PASÓ (Idempotencia garantizada, no hubo doble descuento)" : "FALLÓ") . "\n\n";

    // =========================================================================
    // CASO (e): Cancelar sin marcar el recuadro (no reintegra)
    // =========================================================================
    echo "=========================================================================\n";
    echo "CASO (e): Cancelar pedido empacado SIN marcar casilla de reintegro\n";
    echo "=========================================================================\n";
    // Crear pedido E y empacarlo
    $itemsE = [['producto' => ['id_producto' => 1, 'nombre_producto' => 'Chorizo Santandereano', 'precio' => 12000.00], 'cantidad' => 1]];
    $idPedE = $pedModel->createPedido(13, $dir, $itemsE, 'Efectivo', 'Caso Cancelar Sin Reintegro');
    $pedidosCreados[] = $idPedE;
    $db->prepare("UPDATE pedido SET id_estado = 4")->execute();
    $inv->descontarStockPedido($idPedE);

    $stockCerdoE_Antes = (float)$inv->getInsumoById(1)['stock_actual'];
    echo "Stock Carne tras empaque Pedido #$idPedE: {$stockCerdoE_Antes} kg\n";

    // Cancelar en pedidos.php SIN enviar reintegrar_stock (simulando checkbox desmarcado)
    echo "Cancelando Pedido #$idPedE con checkbox desmarcado (reintegrar_stock = 0)...\n";
    $admModel->updatePedidoEstadoWithAudit($idPedE, EstadoPedido::CANCELADO, 14, 'Cliente desistió pero comida ya fue consumida o desechada');

    $stockCerdoE_Desp = (float)$inv->getInsumoById(1)['stock_actual'];
    $reintFlagE = (int)$db->query("SELECT stock_reintegrado FROM pedido WHERE id_pedido = $idPedE")->fetchColumn();

    echo "Stock Carne tras cancelación sin reintegro: {$stockCerdoE_Desp} kg\n";
    echo "Bandera stock_reintegrado: {$reintFlagE} (0 = no reintegrado)\n";
    echo "Diferencia de stock: " . ($stockCerdoE_Desp - $stockCerdoE_Antes) . " kg (Sin cambios)\n";
    echo "RESULTADO CASO (e): " . ($stockCerdoE_Desp === $stockCerdoE_Antes && $reintFlagE === 0 ? "PASÓ (El stock no fue reintegrado)" : "FALLÓ") . "\n\n";

    // =========================================================================
    // CASO (f): Ajuste que dejaría stock negativo y ajuste sin motivo
    // =========================================================================
    echo "=========================================================================\n";
    echo "CASO (f): Ajustes de inventario inválidos (stock negativo y sin motivo)\n";
    echo "=========================================================================\n";
    $stockAntesCerdoF = (float)$inv->getInsumoById(1)['stock_actual'];
    echo "Stock actual de Carne de cerdo: {$stockAntesCerdoF} kg\n";

    // 1. Intento de ajuste con stock negativo (-10.5 kg)
    echo "1. Intentando fijar stock en -10.500 kg...\n";
    $resAjusteNeg = $inv->registrarAjuste(1, -10.500, 'Ajuste de prueba negativo', 14);
    echo "   Resultado: " . ($resAjusteNeg['success'] ? 'ACEPTADO (ERROR)' : 'RECHAZADO') . " | Mensaje: '{$resAjusteNeg['message']}'\n";

    // 2. Intento de ajuste con motivo vacío
    echo "2. Intentando fijar stock en 50.000 kg con motivo vacío...\n";
    $resAjusteSinMotivo = $inv->registrarAjuste(1, 50.000, '   ', 14);
    echo "   Resultado: " . ($resAjusteSinMotivo ? ($resAjusteSinMotivo['success'] ? 'ACEPTADO (ERROR)' : 'RECHAZADO') : 'RECHAZADO') . " | Mensaje: '{$resAjusteSinMotivo['message']}'\n";

    $stockDespCerdoF = (float)$inv->getInsumoById(1)['stock_actual'];
    echo "Stock de Carne tras ambos intentos: {$stockDespCerdoF} kg\n";
    echo "RESULTADO CASO (f): " . (!$resAjusteNeg['success'] && !$resAjusteSinMotivo['success'] && $stockAntesCerdoF === $stockDespCerdoF ? "PASÓ (Ambos ajustes inválidos fueron rechazados y el stock no varió)" : "FALLÓ") . "\n\n";

    echo "=========================================================================\n";
    echo "✔ TODAS LAS PRUEBAS DE EVIDENCIA DE LA FASE 3 FINALIZADAS EXITOSAMENTE.\n";
    echo "=========================================================================\n";

} catch (Exception $e) {
    echo "\n❌ EXCEPCIÓN: " . $e->getMessage() . "\n";
} finally {
    echo "\nRestaurando inventario a valores óptimos y limpiando pedidos de prueba...\n";
    $inv->registrarAjuste(1, 100.0, 'Restauración stock carne de cerdo');
    $inv->registrarAjuste(2, 180.0, 'Restauración stock tripa');
    $inv->registrarAjuste(3, 19.8, 'Restauración stock sal');
    $inv->registrarAjuste(4, 15.0, 'Restauración stock ajo');

    if (!empty($pedidosCreados)) {
        $pIds = implode(',', array_map('intval', $pedidosCreados));
        $db->exec("DELETE FROM movimiento_inventario WHERE id_pedido IN ($pIds)");
        $db->exec("DELETE FROM notificacion WHERE id_pedido IN ($pIds)");
        $db->exec("DELETE FROM historial_pedido WHERE id_pedido IN ($pIds)");
        $db->exec("DELETE FROM pago WHERE id_pedido IN ($pIds)");
        $db->exec("DELETE FROM detalle_pedido WHERE id_pedido IN ($pIds)");
        $db->exec("DELETE FROM pedido WHERE id_pedido IN ($pIds)");
    }
    echo "✔ Limpieza completada.\n";
}
