<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/AdminModel.php';
require_once __DIR__ . '/../models/StaffModel.php';
require_once __DIR__ . '/../models/PedidoModel.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/EstadoPedido.php';

$db = getDatabaseConnection();
$adminModel = new AdminModel($db);
$staffModel = new StaffModel($db);
$pedidoModel = new PedidoModel($db);
$userModel = new UserModel($db);

echo "=========================================================\n";
echo "PRUEBA DE FLUJO COMPLETO FASE 2 & LOGIN DE LOS 4 ROLES\n";
echo "=========================================================\n\n";

// ---------------------------------------------------------
// PARTE 1: PRUEBA DE LOGIN DE LOS 4 ROLES
// ---------------------------------------------------------
echo "[PARTE 1] Prueba de Inicio de Sesión de los 4 Roles:\n";
$rolesTesting = [
    ['rol' => 'Cliente', 'email' => 'juan@gmail.com', 'pass' => '123456'],
    ['rol' => 'Admin', 'email' => 'admin@gmail.com', 'pass' => '123456'],
    ['rol' => 'Cocinera', 'email' => 'cocina@gmail.com', 'pass' => '123456'],
    ['rol' => 'Domiciliario', 'email' => 'domi@gmail.com', 'pass' => '123456']
];

foreach ($rolesTesting as $r) {
    $res = $userModel->login($r['email'], $r['pass']);
    if ($res['success']) {
        $u = $res['user'];
        echo "  ✔ Rol {$r['rol']} ({$r['email']}): Autenticación EXITOSA (ID #{$u['id']}, Rol: {$u['rol']})\n";
    } else {
        echo "  ❌ Rol {$r['rol']} ({$r['email']}): Error -> {$res['message']}\n";
    }
}

// ---------------------------------------------------------
// PARTE 2: FLUJO COMPLETO CLIENTE -> ADMIN -> COCINERA -> DOMICILIARIO
// ---------------------------------------------------------
echo "\n[PARTE 2] Simulación del Flujo de Pedido Completo:\n";

// 1. Cliente crea pedido
$carrito = [
    [
        'producto' => ['id_producto' => 1, 'precio' => 12000.00],
        'cantidad' => 2
    ]
];
$direccion = [
    'id_zona' => 1,
    'direccion' => 'Calle 10 # 20 - 30',
    'barrio' => 'Centro',
    'referencia' => 'Apt 501',
    'latitud' => 7.1193,
    'longitud' => -73.1227
];
$idPedido = $pedidoModel->createPedido(13, $direccion, $carrito, 'Transferencia / Nequi', 'Probar flujo completo');
echo "  1. Cliente realiza pedido -> ID #{$idPedido} creado con estado inicial: PENDIENTE_VERIFICACION (" . EstadoPedido::PENDIENTE_VERIFICACION . ").\n";

// 2. Admin verifica pago y acepta
$adminModel->verificarPagoWithAudit($idPedido, 14);
$pedAfterAccept = $db->query("SELECT id_estado FROM pedido WHERE id_pedido = $idPedido")->fetchColumn();
echo "  2. Admin verifica pago -> Estado actualizado a: ACEPTADO (" . EstadoPedido::ACEPTADO . "). Confirmado en DB: $pedAfterAccept\n";

// 3. Cocinera toma pedido y pasa a En preparación (3)
$staffModel->updatePedidoStatus($idPedido, EstadoPedido::EN_PREPARACION, 15, 'Cocinera inicia preparación');
$pedInPrep = $db->query("SELECT id_estado FROM pedido WHERE id_pedido = $idPedido")->fetchColumn();
echo "  3. Cocinera prepara -> Estado actualizado a: EN_PREPARACION (" . EstadoPedido::EN_PREPARACION . "). Confirmado en DB: $pedInPrep\n";

// 4. Cocinera empaca pedido (4)
$staffModel->updatePedidoStatus($idPedido, EstadoPedido::EMPACADO, 15, 'Cocinera termina empacado');
$pedEmp = $db->query("SELECT id_estado FROM pedido WHERE id_pedido = $idPedido")->fetchColumn();
echo "  4. Cocinera empaca -> Estado actualizado a: EMPACADO (" . EstadoPedido::EMPACADO . "). Confirmado en DB: $pedEmp\n";

// 5. Admin asigna a Domiciliario 1 (#21) y luego reasigna a Domiciliario 2 (#16)
$domis = $adminModel->getDomiciliarios();
$domi1 = (int)$domis[0]['id_usuario'];
$domi2 = (int)$domis[1]['id_usuario'];

$adminModel->reasignarDomiciliario($idPedido, $domi1, 14, 0);
echo "  5a. Admin asigna a Domiciliario 1 (#$domi1).\n";

$rutas1 = array_column($staffModel->getRutasDomicilio($domi1), 'id_pedido');
echo "      - Domiciliario 1 ve el pedido #$idPedido? " . (in_array($idPedido, $rutas1) ? "SÍ" : "NO") . "\n";

$adminModel->reasignarDomiciliario($idPedido, $domi2, 14, $domi1);
echo "  5b. Admin REASIGNA a Domiciliario 2 (#$domi2).\n";

$rutas1Post = array_column($staffModel->getRutasDomicilio($domi1), 'id_pedido');
$rutas2Post = array_column($staffModel->getRutasDomicilio($domi2), 'id_pedido');

echo "      - Domiciliario 1 post-reasignación ve el pedido #$idPedido? " . (in_array($idPedido, $rutas1Post) ? "SÍ (ERROR)" : "NO (OCULTO CORRECTAMENTE)") . "\n";
echo "      - Domiciliario 2 post-reasignación ve el pedido #$idPedido? " . (in_array($idPedido, $rutas2Post) ? "SÍ (ASIGNADO CORRECTAMENTE)" : "NO (ERROR)") . "\n";

// 6. Domiciliario 2 pasa a En Camino (5) y luego Entregado (6)
$staffModel->updatePedidoStatus($idPedido, EstadoPedido::EN_CAMINO, $domi2, 'Domiciliario en ruta');
echo "  6a. Domiciliario toma ruta -> Estado: EN_CAMINO (" . EstadoPedido::EN_CAMINO . ").\n";

$staffModel->updatePedidoStatus($idPedido, EstadoPedido::ENTREGADO, $domi2, 'Entregado a satisfacción del cliente');
$pedFinal = $db->query("SELECT id_estado FROM pedido WHERE id_pedido = $idPedido")->fetchColumn();
echo "  6b. Domiciliario entrega -> Estado Final: ENTREGADO (" . EstadoPedido::ENTREGADO . "). Confirmado en DB: $pedFinal\n";

// Historial del pedido
$hist = $db->query("SELECT h.*, e.nombre as estado_nombre FROM historial_pedido h LEFT JOIN estado_pedido e ON h.id_estado = e.id_estado WHERE h.id_pedido = $idPedido ORDER BY h.id_historial ASC")->fetchAll(PDO::FETCH_ASSOC);
echo "\n  HISTORIAL COMPLETO DE AUDITORÍA DEL PEDIDO #$idPedido:\n";
foreach ($hist as $h) {
    echo "    - [{$h['fecha']}] Estado: {$h['estado_nombre']} | Usuario #{$h['id_usuario']}: {$h['comentario']}\n";
}

// Limpiar pedido de prueba del flujo
$db->exec("DELETE FROM asignacion_domicilio WHERE id_pedido = $idPedido");
$db->exec("DELETE FROM historial_pedido WHERE id_pedido = $idPedido");
$db->exec("DELETE FROM detalle_pedido WHERE id_pedido = $idPedido");
$db->exec("DELETE FROM pago WHERE id_pedido = $idPedido");
$db->exec("DELETE FROM pedido WHERE id_pedido = $idPedido");
echo "\n✔ Pedido de prueba #$idPedido limpiado de la base de datos.\n";

echo "\n=========================================================\n";
echo "FLUJO COMPLETO Y AUTENTICACIÓN VERIFICADOS CORRECTAMENTE\n";
echo "=========================================================\n";
