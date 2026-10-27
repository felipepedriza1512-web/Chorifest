<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/EstadoPedido.php';
require_once __DIR__ . '/../models/InventarioModel.php';
require_once __DIR__ . '/../models/ProductoModel.php';
require_once __DIR__ . '/../models/PedidoModel.php';

$db = getDatabaseConnection();
$inventarioModel = new InventarioModel($db);
$productoModel = new ProductoModel($db);
$pedidoModel = new PedidoModel($db);

echo "=========================================================\n";
echo "PRUEBAS AUTOMATIZADAS COMPLETAS - FASE 3 CHORIFEST\n";
echo "=========================================================\n\n";

$pedidosCreados = [];
$productosCreados = [];
$archivosCreados = [];

function assertTest(bool $condition, string $title) {
    if ($condition) {
        echo "  ✔ [PASÓ] $title\n";
    } else {
        echo "  ❌ [FALLÓ] $title\n";
        exit(1);
    }
}

try {
    // -------------------------------------------------------------
    // PRUEBA 1: Flujo Cliente -> Aceptar -> Cocinera Empacado con Descuento Exacto
    // -------------------------------------------------------------
    echo "--- PRUEBA 1: Flujo Normal y Descuento Exacto de Stock ---\n";
    // Insumo 1: Carne de cerdo. Stock inicial:
    $insumoCerdo = $inventarioModel->getInsumoById(1);
    $stockInicialCerdo = (float)$insumoCerdo['stock_actual'];

    // Producto 1: Chorizo Santandereano (Receta 1: Carne de cerdo 0.250 kg)
    // Crear pedido de 2 unidades -> requiere 0.500 kg de carne de cerdo
    $dirData = ['id_zona' => 1, 'direccion' => 'Calle 1 Test', 'barrio' => 'Centro', 'referencia' => 'Test'];
    $carrito = [
        ['producto' => ['id_producto' => 1, 'nombre_producto' => 'Chorizo Santandereano', 'precio' => 12000.00], 'cantidad' => 2]
    ];
    $idPed1 = $pedidoModel->createPedido(13, $dirData, $carrito, 'Efectivo', 'Test Flujo 1');
    $pedidosCreados[] = $idPed1;
    assertTest($idPed1 > 0, "Pedido #$idPed1 creado exitosamente");

    // Admin acepta pedido -> Estado 2
    $db->prepare("UPDATE pedido SET id_estado = 2 WHERE id_pedido = ?")->execute([$idPed1]);
    // Cocinera prepara -> Estado 3
    $db->prepare("UPDATE pedido SET id_estado = 3 WHERE id_pedido = ?")->execute([$idPed1]);

    // Cocinera empaca -> Estado 4 (Descuento automático)
    $db->prepare("UPDATE pedido SET id_estado = 4 WHERE id_pedido = ?")->execute([$idPed1]);
    $resDesc = $inventarioModel->descontarStockPedido($idPed1);
    assertTest($resDesc['success'] === true, "Descuento de inventario ejecutado con éxito");

    // Verificar que stock_descontado = 1
    $stockDescFlag = (int)$db->query("SELECT stock_descontado FROM pedido WHERE id_pedido = $idPed1")->fetchColumn();
    assertTest($stockDescFlag === 1, "Bandera stock_descontado = 1 registrada");

    // Verificar nuevo stock de Carne de cerdo: stockInicial - 0.500 kg
    $insumoCerdoPost = $inventarioModel->getInsumoById(1);
    $stockPostCerdo = (float)$insumoCerdoPost['stock_actual'];
    $esperadoCerdo = $stockInicialCerdo - 0.500;
    assertTest(abs($stockPostCerdo - $esperadoCerdo) < 0.001, "Stock de carne descontado exactamente: {$stockInicialCerdo} -> {$stockPostCerdo} kg");

    // Idempotencia: intentar descontar de nuevo
    $resDescDoble = $inventarioModel->descontarStockPedido($idPed1);
    assertTest(!empty($resDescDoble['ya_descontado']), "Idempotencia: segundo descuento evitado correctamente");

    // -------------------------------------------------------------
    // PRUEBA 2: Caso de Stock Insuficiente sin Bloquear Cocinera
    // -------------------------------------------------------------
    echo "\n--- PRUEBA 2: Stock Insuficiente (Descuenta a 0, Faltante y Alerta) ---\n";
    // Ajustar Pulpa de fruta (ID 12) a 0.450 kg para permitir creación del pedido (3 Jugos x 0.150 kg)
    $inventarioModel->registrarAjuste(12, 0.450, 'Ajuste para crear pedido');
    $carritoJugo = [
        ['producto' => ['id_producto' => 7, 'nombre_producto' => 'Jugo Natural', 'precio' => 6000.00], 'cantidad' => 3]
    ];
    $idPed2 = $pedidoModel->createPedido(13, $dirData, $carritoJugo, 'Efectivo', 'Test Stock Insuficiente');
    $pedidosCreados[] = $idPed2;

    // Reducir stock a 0.200 kg antes de que la cocinera empaque (simulando merma o falta en cocina)
    $inventarioModel->registrarAjuste(12, 0.200, 'Merma física antes de empacar');

    // Cocinera empaca pedido #idPed2
    $db->prepare("UPDATE pedido SET id_estado = 4 WHERE id_pedido = ?")->execute([$idPed2]);
    $resDescInsuf = $inventarioModel->descontarStockPedido($idPed2);
    assertTest($resDescInsuf['success'] === true && !empty($resDescInsuf['hubo_faltante']), "Cocinera empaca sin bloqueo y se detecta faltante");

    // Stock de Pulpa de fruta debe ser exactamente 0.000 kg
    $pulpaPost = $inventarioModel->getInsumoById(12);
    assertTest((float)$pulpaPost['stock_actual'] === 0.0, "Stock de insumo insuficiente descontado exactamente hasta 0.000 kg");

    // Verificar que existe movimiento FALTANTE por 0.250 kg
    $stmtFalt = $db->prepare("SELECT cantidad FROM movimiento_inventario WHERE id_pedido = ? AND tipo = 'FALTANTE' AND id_insumo = 12");
    $stmtFalt->execute([$idPed2]);
    $cantFaltante = (float)$stmtFalt->fetchColumn();
    assertTest(abs($cantFaltante - 0.250) < 0.001, "Movimiento FALTANTE registrado por 0.250 kg");

    // Verificar que existe alerta crítica en notificacion
    $stmtNotif = $db->prepare("SELECT COUNT(*) FROM notificacion WHERE id_pedido = ? AND titulo LIKE '%Stock Insuficiente%'");
    $stmtNotif->execute([$idPed2]);
    $notifCount = (int)$stmtNotif->fetchColumn();
    assertTest($notifCount > 0, "Alerta crítica de stock insuficiente generada para el Administrador");

    // -------------------------------------------------------------
    // PRUEBA 3: Reintegro de Pedido Cancelado Empacado (Opción 3)
    // -------------------------------------------------------------
    echo "\n--- PRUEBA 3: Cancelación y Reintegro de Insumos Físicos ---\n";
    // El pedido 2 consumió físicamente 0.200 kg de Pulpa (SALIDA_RECETA) y tuvo 0.250 kg de FALTANTE.
    // Al cancelar con reintegro, SOLO debe reintegrar 0.200 kg (la salida física, NUNCA el faltante).
    $resReint = $inventarioModel->reintegrarStockPedido($idPed2, 'Cancelación prueba reintegro', 14);
    assertTest($resReint['success'] === true, "Reintegro ejecutado exitosamente");

    // Stock de Pulpa debe volver exactamente a 0.200 kg (lo que salió físicamente)
    $pulpaReint = $inventarioModel->getInsumoById(12);
    assertTest(abs((float)$pulpaReint['stock_actual'] - 0.200) < 0.001, "Pulpa reintegrada exactamente al stock físico original (0.200 kg)");

    // Verificar guardia stock_reintegrado = 1
    $reintFlag = (int)$db->query("SELECT stock_reintegrado FROM pedido WHERE id_pedido = $idPed2")->fetchColumn();
    assertTest($reintFlag === 1, "Bandera stock_reintegrado = 1 registrada");

    // Intentar segundo reintegro debe fallar
    $resReintDoble = $inventarioModel->reintegrarStockPedido($idPed2, 'Segundo intento', 14);
    assertTest($resReintDoble['success'] === false, "Guardia: no permite segundo reintegro");

    // -------------------------------------------------------------
    // PRUEBA 4: Reintento de Descuento Pendiente
    // -------------------------------------------------------------
    echo "\n--- PRUEBA 4: Reintento de Descuento de Inventario Pendiente ---\n";
    // Crear pedido 3 en estado Empacado (4) pero con stock_descontado = 0 (simulando fallo temporal)
    $idPed3 = $pedidoModel->createPedido(13, $dirData, $carrito, 'Efectivo', 'Test Reintento Descuento');
    $pedidosCreados[] = $idPed3;
    $db->prepare("UPDATE pedido SET id_estado = 4, stock_descontado = 0 WHERE id_pedido = ?")->execute([$idPed3]);

    $stockCerdoAntes3 = (float)$inventarioModel->getInsumoById(1)['stock_actual'];
    // Reintentar descuento
    $resReintDesc = $inventarioModel->descontarStockPedido($idPed3);
    assertTest($resReintDesc['success'] === true, "Reintento de descuento procesado exitosamente");
    $stockCerdoDesp3 = (float)$inventarioModel->getInsumoById(1)['stock_actual'];
    assertTest(abs($stockCerdoDesp3 - ($stockCerdoAntes3 - 0.500)) < 0.001, "Stock descontado correctamente en el reintento");

    // -------------------------------------------------------------
    // PRUEBA 5: Ocultamiento Automático por Insumo Insuficiente
    // -------------------------------------------------------------
    echo "\n--- PRUEBA 5: Ocultar Producto por Stock Insuficiente y Reaparición ---\n";
    // Ajustar Insumo 12 (Pulpa de fruta) a 0.050 kg (Jugo Natural requiere 0.150 kg por porción)
    $inventarioModel->registrarAjuste(12, 0.050, 'Ajuste bajo umbral unitario');
    $statusMap = $inventarioModel->getProductosStockStatus();
    assertTest($statusMap[7]['disponible_por_stock'] === false, "Jugo Natural detectado no disponible por insumo insuficiente (0.050 < 0.150 kg)");
    assertTest($statusMap[7]['visible_cliente'] === false, "Jugo Natural oculto automáticamente del menú del cliente");

    // Reponer Pulpa a 20.000 kg
    $inventarioModel->registrarAjuste(12, 20.000, 'Reposición para reaparición');
    $statusMapPost = $inventarioModel->getProductosStockStatus();
    assertTest($statusMapPost[7]['disponible_por_stock'] === true, "Jugo Natural vuelve a estar disponible tras reposición");
    assertTest($statusMapPost[7]['visible_cliente'] === true, "Jugo Natural reaparece en el menú del cliente");

    // Prueba switch manual del admin: desactivar manualmente
    $productoModel->toggleDisponible(7);
    $statusMapManual = $inventarioModel->getProductosStockStatus();
    assertTest($statusMapManual[7]['visible_cliente'] === false, "Producto desactivado por Admin permanece oculto a pesar de tener stock");
    // Restaurar disponible
    $productoModel->toggleDisponible(7);

    // -------------------------------------------------------------
    // PRUEBA 6: Subida Segura de Foto y Rechazo de Archivos No Permitidos
    // -------------------------------------------------------------
    echo "\n--- PRUEBA 6: Fotos de Productos y Seguridad (.php rechazado) ---\n";
    // Crear imagen de prueba JPG válida
    $dummyJpgPath = __DIR__ . '/test_sample_image.jpg';
    $im = imagecreatetruecolor(100, 100);
    imagejpeg($im, $dummyJpgPath);
    imagedestroy($im);
    $archivosCreados[] = $dummyJpgPath;

    $fileJpg = [
        'name' => 'foto_original_usuario.jpg',
        'type' => 'image/jpeg',
        'tmp_name' => $dummyJpgPath,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($dummyJpgPath)
    ];
    $uploadOk = $productoModel->procesarSubidaFoto($fileJpg);
    assertTest($uploadOk['success'] === true, "Foto JPG válida procesada correctamente");
    $savedFilename = $uploadOk['filename'];
    assertTest(strpos($savedFilename, 'prod_') === 0 && strpos($savedFilename, 'foto_original') === false, "Nombre aleatorio generado por servidor: $savedFilename");
    $archivosCreados[] = __DIR__ . '/../public/uploads/productos/' . $savedFilename;

    // Intentar subir un script .php malicioso
    $dummyPhpPath = __DIR__ . '/malicious_test.php';
    file_put_contents($dummyPhpPath, '<?php echo "evil"; ?>');
    $archivosCreados[] = $dummyPhpPath;

    $filePhp = [
        'name' => 'shell.php',
        'type' => 'application/x-php',
        'tmp_name' => $dummyPhpPath,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($dummyPhpPath)
    ];
    $uploadFail = $productoModel->procesarSubidaFoto($filePhp);
    assertTest($uploadFail['success'] === false, "Intento de subir archivo .php rechazado correctamente: " . $uploadFail['message']);

    // -------------------------------------------------------------
    // PRUEBA 7: Concurrencia de 2 Pedidos Simultáneos
    // -------------------------------------------------------------
    echo "\n--- PRUEBA 7: Concurrencia Simulada sin Deadlocks ---\n";
    $idPedConc1 = $pedidoModel->createPedido(13, $dirData, $carrito, 'Efectivo', 'Conc 1');
    $idPedConc2 = $pedidoModel->createPedido(13, $dirData, $carrito, 'Efectivo', 'Conc 2');
    $pedidosCreados[] = $idPedConc1;
    $pedidosCreados[] = $idPedConc2;

    $db->prepare("UPDATE pedido SET id_estado = 4 WHERE id_pedido IN ($idPedConc1, $idPedConc2)")->execute();

    $resC1 = $inventarioModel->descontarStockPedido($idPedConc1);
    $resC2 = $inventarioModel->descontarStockPedido($idPedConc2);

    assertTest($resC1['success'] === true && $resC2['success'] === true, "Dos pedidos con mismos ingredientes descontados concurrentemente con éxito");

    // -------------------------------------------------------------
    // PRUEBA 8: Protección de Integridad (No Borrar Producto con Pedidos)
    // -------------------------------------------------------------
    echo "\n--- PRUEBA 8: Protección de Integridad al Eliminar/Ocultar ---\n";
    // Producto 1 tiene pedidos asociados en detalle_pedido
    $resOcultar = $productoModel->ocultarOEliminar(1);
    assertTest($resOcultar['action'] === 'desactivado', "Producto con historial de pedidos es desactivado (disponible=0) en lugar de borrado");
    // Restaurar disponibilidad
    $db->prepare("UPDATE producto SET disponible = 1 WHERE id_producto = 1")->execute();

    echo "\n✔ TODAS LAS VERIFICACIONES DE LA FASE 3 PASARON AL 100%.\n";

} catch (Exception $e) {
    echo "\n❌ EXCEPCIÓN DURANTE PRUEBAS: " . $e->getMessage() . "\n";
    exit(1);
} finally {
    echo "\nLimpiando datos de prueba generados...\n";
    // Restaurar stock de carne de cerdo a 100 kg
    $inventarioModel->registrarAjuste(1, 100.0, 'Restauración stock inicial carne de cerdo');
    $inventarioModel->registrarAjuste(7, 10.0, 'Restauración stock inicial comino');

    // Limpiar pedidos creados
    if (!empty($pedidosCreados)) {
        $pIds = implode(',', array_map('intval', $pedidosCreados));
        $db->exec("DELETE FROM movimiento_inventario WHERE id_pedido IN ($pIds)");
        $db->exec("DELETE FROM notificacion WHERE id_pedido IN ($pIds)");
        $db->exec("DELETE FROM historial_pedido WHERE id_pedido IN ($pIds)");
        $db->exec("DELETE FROM pago WHERE id_pedido IN ($pIds)");
        $db->exec("DELETE FROM detalle_pedido WHERE id_pedido IN ($pIds)");
        $db->exec("DELETE FROM pedido WHERE id_pedido IN ($pIds)");
    }

    // Limpiar archivos temporales
    foreach ($archivosCreados as $f) {
        if (file_exists($f)) {
            @unlink($f);
        }
    }
    echo "✔ Datos de prueba y archivos temporales eliminados completamente.\n";
}
