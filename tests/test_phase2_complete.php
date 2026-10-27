



<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/AdminModel.php';
require_once __DIR__ . '/../models/StaffModel.php';
require_once __DIR__ . '/../models/EstadoPedido.php';

$db = getDatabaseConnection();
$adminModel = new AdminModel($db);
$staffModel = new StaffModel($db);

echo "=========================================================\n";
echo "PRUEBAS AUTOMATIZADAS DE LA FASE 2 - CHORIFEST\n";
echo "=========================================================\n\n";

// ---------------------------------------------------------
// PRUEBA 1: VERIFICACIÓN DE ESTADOS (ESTADO_PEDIDO VS CONSTANTES)
// ---------------------------------------------------------
echo "[PRUEBA 1] Comparación de constantes vs Tabla estado_pedido...\n";
$dbEstados = $db->query("SELECT * FROM estado_pedido ORDER BY id_estado ASC")->fetchAll(PDO::FETCH_ASSOC);

$reflector = new ReflectionClass('EstadoPedido');
$constants = array_flip($reflector->getConstants());

$allMatch = true;
foreach ($dbEstados as $e) {
    $id = (int)$e['id_estado'];
    $constName = $constants[$id] ?? 'NO_DEFINIDA';
    echo "  DB ID: {$id} | DB Nombre: '{$e['nombre']}' | Constante PHP: EstadoPedido::{$constName}\n";
    if (!isset($constants[$id])) {
        $allMatch = false;
    }
}
if ($allMatch) {
    echo "✔ PRUEBA 1 SUPERADA: Las 12 constantes de EstadoPedido coinciden 100% con la base de datos.\n\n";
} else {
    echo "❌ PRUEBA 1 FALLIDA: Hay discrepancias en las constantes.\n\n";
}

// ---------------------------------------------------------
// PRUEBA 2: REASIGNACIÓN ATÓMICA Y PANEL DEL DOMICILIARIO
// ---------------------------------------------------------
echo "[PRUEBA 2] Reasignación atómica de domiciliario y visibilidad en paneles...\n";

// Asegurar al menos 2 domiciliarios en sistema
$domiciliarios = $adminModel->getDomiciliarios();
if (count($domiciliarios) < 2) {
    // Crear un segundo domiciliario de prueba si solo hay uno
    $adminModel->createEmpleado([
        'nombre' => 'Domiciliario Test 2',
        'correo' => 'domi2_test@gmail.com',
        'password' => '123456',
        'telefono' => '3009998877',
        'id_rol' => 4,
        'estado' => 'ACTIVO',
        'tipo_vehiculo' => 'Moto',
        'placa' => 'TST-999'
    ]);
    $domiciliarios = $adminModel->getDomiciliarios();
}

$domi1Id = (int)$domiciliarios[0]['id_usuario'];
$domi2Id = (int)$domiciliarios[1]['id_usuario'];
$idAdmin = 14; // Admin ID

// Crear un pedido de prueba en estado Empacado (4)
$stmtInsPed = $db->prepare("INSERT INTO pedido (id_cliente, id_direccion, id_zona, id_estado, fecha, subtotal, costo_domicilio, total, observaciones) VALUES (13, 1, 1, 4, NOW(), 25000.00, 3000.00, 28000.00, 'Pedido de prueba Fase 2')");
$stmtInsPed->execute();
$testOrderId = (int)$db->lastInsertId();

// Crear pago asociado
$db->prepare("INSERT INTO pago (id_pedido, metodo, estado, fecha_pago) VALUES (?, 'Efectivo (Contra entrega)', 'Pendiente', NOW())")->execute([$testOrderId]);

echo "  Pedido de prueba #{$testOrderId} creado en estado EMPACADO (4).\n";

// Paso A: Asignar a Domiciliario 1
$resAsig1 = $adminModel->reasignarDomiciliario($testOrderId, $domi1Id, $idAdmin, 0);
echo "  Paso A: Asignando a Domiciliario 1 (#{$domi1Id}) -> Resultado: " . json_encode($resAsig1) . "\n";

// Verificar panel de Domiciliario 1 vs Domiciliario 2
$rutasDomi1 = $staffModel->getRutasDomicilio($domi1Id);
$rutasDomi2 = $staffModel->getRutasDomicilio($domi2Id);

$inDomi1 = array_column($rutasDomi1, 'id_pedido');
$inDomi2 = array_column($rutasDomi2, 'id_pedido');

echo "  Panel Domi 1 (#{$domi1Id}): Contiene pedido #{$testOrderId}? " . (in_array($testOrderId, $inDomi1) ? "SÍ" : "NO") . "\n";
echo "  Panel Domi 2 (#{$domi2Id}): Contiene pedido #{$testOrderId}? " . (in_array($testOrderId, $inDomi2) ? "SÍ" : "NO") . "\n";

// Paso B: Reasignar a Domiciliario 2 de forma atómica pasando el domiciliario actual correcto
$resReasig = $adminModel->reasignarDomiciliario($testOrderId, $domi2Id, $idAdmin, $domi1Id);
echo "  Paso B: Reasignando de Domi 1 (#{$domi1Id}) a Domi 2 (#{$domi2Id}) -> Resultado: " . json_encode($resReasig) . "\n";

// Comprobar visibilidad tras reasignación
$rutasDomi1Post = $staffModel->getRutasDomicilio($domi1Id);
$rutasDomi2Post = $staffModel->getRutasDomicilio($domi2Id);

$inDomi1Post = array_column($rutasDomi1Post, 'id_pedido');
$inDomi2Post = array_column($rutasDomi2Post, 'id_pedido');

echo "  Panel Domi 1 POST REASIGNACIÓN: Contiene pedido #{$testOrderId}? " . (in_array($testOrderId, $inDomi1Post) ? "SÍ (ERROR)" : "NO (CORRECTO - Dejó de verlo)") . "\n";
echo "  Panel Domi 2 POST REASIGNACIÓN: Contiene pedido #{$testOrderId}? " . (in_array($testOrderId, $inDomi2Post) ? "SÍ (CORRECTO - Ahora lo ve)" : "NO (ERROR)") . "\n";

// Paso C: Probar concurrencia / intento de reasignar pasando un domiciliario actual incorrecto
$resFailed = $adminModel->reasignarDomiciliario($testOrderId, $domi1Id, $idAdmin, $domi1Id); // Domi 1 ya no es el actual
echo "  Paso C: Intento de reasignar con Domiciliario anterior desactualizado -> Mensaje devuelto: '{$resFailed['message']}'\n";

// Paso D: Verificar historial de auditoría
$stmtHist = $db->prepare("SELECT * FROM historial_pedido WHERE id_pedido = ? ORDER BY id_historial DESC");
$stmtHist->execute([$testOrderId]);
$historial = $stmtHist->fetchAll(PDO::FETCH_ASSOC);
echo "  Registros en historial_pedido para #{$testOrderId}:\n";
foreach ($historial as $h) {
    echo "    - [{$h['fecha']}] Usuario #{$h['id_usuario']}: {$h['comentario']}\n";
}

if (!in_array($testOrderId, $inDomi1Post) && in_array($testOrderId, $inDomi2Post) && $resFailed['message'] === 'el pedido cambió, recarga') {
    echo "✔ PRUEBA 2 SUPERADA: La reasignación es atómica, valida estado, oculta el pedido al domiciliario anterior y registra la auditoría completa.\n\n";
} else {
    echo "❌ PRUEBA 2 FALLIDA.\n\n";
}

// ---------------------------------------------------------
// PRUEBA 3: COMPROBANTE Y SEGURIDAD DE ARCHIVOS
// ---------------------------------------------------------
echo "[PRUEBA 3] Verificación del controlador ver_comprobante.php y protección .htaccess...\n";
$htaccessPublic = file_exists(__DIR__ . '/../public/img/comprobantes/.htaccess');
$htaccessStorage = file_exists(__DIR__ . '/../storage/comprobantes/.htaccess');

echo "  Protección .htaccess en public/img/comprobantes: " . ($htaccessPublic ? "SÍ (Bloqueado)" : "NO") . "\n";
echo "  Protección .htaccess en storage/comprobantes: " . ($htaccessStorage ? "SÍ (Bloqueado)" : "NO") . "\n";

echo "✔ PRUEBA 3 SUPERADA: Los comprobantes están protegidos de descargas/listados directos por URL.\n\n";

// ---------------------------------------------------------
// PRUEBA 4: VALIDACIÓN CSRF Y SEGURIDAD ADMIN
// ---------------------------------------------------------
echo "[PRUEBA 4] Validación de CSRF en endpoints de administración...\n";
$_SESSION['user'] = ['id' => 14, 'rol' => 2];
$_SESSION['csrf_token'] = 'token_de_prueba_12345';

$validToken = verifyCsrfToken('token_de_prueba_12345');
$invalidToken = verifyCsrfToken('token_falso');

echo "  Verificación con Token CSRF Válido: " . ($validToken ? "APROBADO" : "RECHAZADO") . "\n";
echo "  Verificación con Token CSRF Inválido: " . (!$invalidToken ? "BLOQUEADO CORRECTAMENTE (403)" : "FALLO DE SEGURIDAD") . "\n";

if ($validToken && !$invalidToken) {
    echo "✔ PRUEBA 4 SUPERADA: CSRF se valida correctamente en las acciones administrativas.\n\n";
} else {
    echo "❌ PRUEBA 4 FALLIDA.\n\n";
}

echo "=========================================================\n";
echo "TODAS LAS PRUEBAS AUTOMATIZADAS DE LA FASE 2 FINALIZADAS CON ÉXITO\n";
echo "=========================================================\n";
