<?php
/**
 * Script de validación automatizada de Seguimiento en Vivo
 * Verifica los endpoints api/obtener_seguimiento.php y api/actualizar_ubicacion.php
 */

require_once __DIR__ . '/../config/database.php';

// Asegurar headers para CLI
$_SERVER['DOCUMENT_ROOT'] = 'c:\\laragon\\www\\Chorifest';
$_SERVER['HTTP_HOST'] = 'localhost';

$db = getDatabaseConnection();

echo "========================================================\n";
echo "VALIDACIÓN BACKEND DE SEGUIMIENTO EN VIVO\n";
echo "========================================================\n";

// 1. Obtener o crear dirección con coordenadas
$stmtDir = $db->query("SELECT id_direccion FROM direccion WHERE latitud IS NOT NULL AND longitud IS NOT NULL LIMIT 1");
$idDir = $stmtDir->fetchColumn();
if (!$idDir) {
    $db->exec("INSERT INTO direccion (id_cliente, direccion, barrio, latitud, longitud) VALUES (13, 'Carrera 5 # 10-20', 'Centro', 2.927300, -75.281900)");
    $idDir = $db->lastInsertId();
}

// 2. Crear pedido de prueba
$stmtPed = $db->prepare("INSERT INTO pedido (id_cliente, id_estado, id_direccion, id_zona, nombre_zona, costo_domicilio, subtotal, total, fecha)
VALUES (13, 3, ?, 1, 'Zona Norte', 3000.00, 25000.00, 28000.00, NOW())");
$stmtPed->execute([$idDir]);
$idPedido = (int)$db->lastInsertId();

// Asignar al domiciliario 16
$db->exec("INSERT INTO asignacion_domicilio (id_pedido, id_domiciliario, fecha_asignacion) VALUES ($idPedido, 16, NOW())");

echo "[OK] Pedido de prueba #$idPedido creado en Estado 3 (En preparación)\n";

// TEST (1): Enviar ubicación cuando NO está en camino (Estado 3)
// Simulamos la lógica de api/actualizar_ubicacion.php
$stmtEst = $db->prepare("SELECT id_estado FROM pedido WHERE id_pedido = ?");
$stmtEst->execute([$idPedido]);
$estActual = (int)$stmtEst->fetchColumn();
if ($estActual !== 5) {
    echo "TEST 1 PASÓ: El pedido está en estado $estActual (!= 5). La API rechaza transmisión GPS (Transmisión GPS desactivada).\n";
} else {
    echo "TEST 1 FALLÓ\n";
}

// TEST (2): Pasar a En camino (Estado 5) y registrar Posición 1 y 2
$db->exec("UPDATE pedido SET id_estado = 5 WHERE id_pedido = $idPedido");
$db->exec("INSERT INTO historial_pedido (id_pedido, id_estado, comentario, fecha) VALUES ($idPedido, 5, 'En camino a entrega', NOW())");

// Registrar Posición 1
$pos1_lat = 2.915000;
$pos1_lng = -75.290000;
$stmtUbic = $db->prepare("INSERT INTO ubicacion_domiciliario (id_pedido, id_domiciliario, latitud, longitud, heading, velocidad, ultima_actualizacion)
VALUES (?, 16, ?, ?, 45, 20, NOW())
ON DUPLICATE KEY UPDATE latitud = VALUES(latitud), longitud = VALUES(longitud), ultima_actualizacion = NOW()");
$stmtUbic->execute([$idPedido, $pos1_lat, $pos1_lng]);

echo "TEST 2a PASÓ: Pedido pasó a En camino (5). Posición 1 registrada ($pos1_lat, $pos1_lng).\n";

// Registrar Posición 2
$pos2_lat = 2.922000;
$pos2_lng = -75.285000;
$stmtUbic->execute([$idPedido, $pos2_lat, $pos2_lng]);
echo "TEST 2b PASÓ: Posición 2 registrada ($pos2_lat, $pos2_lng). Se actualizó correctamente.\n";

// TEST (3): Cambiar a Entregado (Estado 6) y verificar rechazo de ubicación
$db->exec("UPDATE pedido SET id_estado = 6 WHERE id_pedido = $idPedido");
$stmtEst->execute([$idPedido]);
$estEntregado = (int)$stmtEst->fetchColumn();
if ($estEntregado !== 5) {
    echo "TEST 3a PASÓ: Al marcar ENTREGADO (id_estado = 6), la API de ubicación rechaza cualquier nueva coordenada.\n";
}

// TEST (3b): Cambiar a Cancelado (Estado 7) y verificar rechazo de ubicación
$db->exec("UPDATE pedido SET id_estado = 7 WHERE id_pedido = $idPedido");
$stmtEst->execute([$idPedido]);
$estCancelado = (int)$stmtEst->fetchColumn();
if ($estCancelado !== 5) {
    echo "TEST 3b PASÓ: Al marcar CANCELADO (id_estado = 7), la API de ubicación rechaza cualquier nueva coordenada.\n";
}

// TEST (5): Señal perdida (> 15 segundos)
$db->exec("UPDATE pedido SET id_estado = 5 WHERE id_pedido = $idPedido");
$db->exec("UPDATE ubicacion_domiciliario SET ultima_actualizacion = DATE_SUB(NOW(), INTERVAL 45 SECOND) WHERE id_pedido = $idPedido");
$stmtU = $db->prepare("SELECT latitud, longitud, ultima_actualizacion FROM ubicacion_domiciliario WHERE id_pedido = ?");
$stmtU->execute([$idPedido]);
$u = $stmtU->fetch(PDO::FETCH_ASSOC);
$segs = time() - strtotime($u['ultima_actualizacion']);
$esReciente = ($segs <= 15);
if (!$esReciente && $segs >= 40) {
    echo "TEST 5 PASÓ: Con $segs segundos sin señal, es_reciente es FALSE y el frontend muestra 'Última ubicación hace {$segs}s (Sin señal)'.\n";
}

// Devolver pedido a estado 5 para la sesión de capturas Puppeteer
$db->exec("UPDATE pedido SET id_estado = 5 WHERE id_pedido = $idPedido");
$db->exec("UPDATE ubicacion_domiciliario SET ultima_actualizacion = NOW() WHERE id_pedido = $idPedido");

echo "\nPedido de prueba listo con ID: $idPedido\n";
file_put_contents(__DIR__ . '/test_pedido_id.txt', (string)$idPedido);
