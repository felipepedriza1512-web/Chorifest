<?php
/**
 * Aplicación de migración de recetas Opción B y corrección de codificación
 * Chorifest - Fase 4
 */
require_once __DIR__ . '/../config/database.php';

function applyMigrationToDatabase(string $dbTargetName) {
    echo "=====================================================================================\n";
    echo "APLICANDO MIGRACIÓN Y CORRECCIONES EN BASE DE DATOS: '$dbTargetName'\n";
    echo "=====================================================================================\n";

    $db = getDatabaseConnection($dbTargetName);
    $db->beginTransaction();

    try {
        // -------------------------------------------------------------------------
        // 1. CORRECCIÓN DE CODIFICACIÓN EN LAS 7 FILAS DETECTADAS
        // -------------------------------------------------------------------------
        echo "1. Corrigiendo las 7 filas con texto dañado (mojibake)...\n";
        
        $fixes = [
            ["UPDATE historial_pedido SET comentario = ? WHERE id_historial = ?", ['Pedido en preparación', 3]],
            ["UPDATE notificacion SET titulo = ? WHERE id_notificacion = ?", ['En preparación', 4]],
            ["UPDATE novedad_entrega SET descripcion = ? WHERE id_novedad = ?", ['Se verificó la dirección', 2]],
            ["UPDATE novedad_entrega SET descripcion = ? WHERE id_novedad = ?", ['Transacción rechazada', 9]],
            ["UPDATE pago SET observacion = ? WHERE id_pago = ?", ['Transacción rechazada', 9]],
            ["UPDATE pedido SET observaciones = ? WHERE id_pedido = ?", ['En preparación', 4]],
            ["UPDATE receta SET nombre = ? WHERE id_receta = ?", ['Receta Antioqueña', 2]],
        ];

        foreach ($fixes as $f) {
            $stmt = $db->prepare($f[0]);
            $stmt->execute($f[1]);
        }
        echo "  ✔ 7 registros con codificación corregidos a UTF-8 válido.\n\n";

        // -------------------------------------------------------------------------
        // 2. APLICAR MIGRACIÓN DE RECETAS OPCIÓN B
        // -------------------------------------------------------------------------
        echo "2. Aplicando recetas Opción B (database/migrations/migration_recetas_opcion_b.sql)...\n";
        
        // Insumos
        $db->exec("INSERT INTO `insumo` (`nombre`, `unidad_medida`, `stock_minimo`, `estado`)
                   VALUES 
                   ('Gaseosa 350 ml (DEMO)', 'unidad', 10.000, 'ACTIVO'),
                   ('Pulpa de fruta (DEMO)', 'kg', 3.000, 'ACTIVO')
                   ON DUPLICATE KEY UPDATE `estado` = 'ACTIVO'");

        $idGaseosa = (int)$db->query("SELECT id_insumo FROM `insumo` WHERE `nombre` = 'Gaseosa 350 ml (DEMO)' LIMIT 1")->fetchColumn();
        $idPulpa = (int)$db->query("SELECT id_insumo FROM `insumo` WHERE `nombre` = 'Pulpa de fruta (DEMO)' LIMIT 1")->fetchColumn();

        $db->exec("INSERT INTO `inventario_insumo` (`id_insumo`, `cantidad`, `fecha_actualizacion`)
                   VALUES ($idGaseosa, 50.000, NOW())
                   ON DUPLICATE KEY UPDATE `cantidad` = 50.000");

        $db->exec("INSERT INTO `inventario_insumo` (`id_insumo`, `cantidad`, `fecha_actualizacion`)
                   VALUES ($idPulpa, 20.000, NOW())
                   ON DUPLICATE KEY UPDATE `cantidad` = 20.000");

        // Producto 2 (Chorizo Antioqueño): asegurar Tripa natural (ID 2)
        $idReceta2 = (int)$db->query("SELECT id_receta FROM `receta` WHERE id_producto = 2 LIMIT 1")->fetchColumn();
        $db->exec("UPDATE `receta` SET `nombre` = 'Receta Antioqueña' WHERE id_receta = $idReceta2");
        $db->exec("DELETE FROM `receta_detalle` WHERE `id_receta` = $idReceta2 AND `id_insumo` = 2");
        $db->exec("INSERT INTO `receta_detalle` (`id_receta`, `id_insumo`, `cantidad`) VALUES ($idReceta2, 2, 1.000)");

        // Producto 6 (Gaseosa)
        $idReceta6 = (int)$db->query("SELECT id_receta FROM `receta` WHERE id_producto = 6 LIMIT 1")->fetchColumn();
        $db->exec("UPDATE `receta` SET `nombre` = 'Receta Gaseosa' WHERE id_receta = $idReceta6");
        $db->exec("DELETE FROM `receta_detalle` WHERE `id_receta` = $idReceta6");
        $db->exec("INSERT INTO `receta_detalle` (`id_receta`, `id_insumo`, `cantidad`) VALUES ($idReceta6, $idGaseosa, 1.000)");

        // Producto 7 (Jugo Natural)
        $idReceta7 = (int)$db->query("SELECT id_receta FROM `receta` WHERE id_producto = 7 LIMIT 1")->fetchColumn();
        $db->exec("UPDATE `receta` SET `nombre` = 'Receta Jugo Natural' WHERE id_receta = $idReceta7");
        $db->exec("DELETE FROM `receta_detalle` WHERE `id_receta` = $idReceta7");
        $db->exec("INSERT INTO `receta_detalle` (`id_receta`, `id_insumo`, `cantidad`) VALUES ($idReceta7, $idPulpa, 0.150)");

        // Producto 9 (Papa Francesa)
        $idReceta9 = (int)$db->query("SELECT id_receta FROM `receta` WHERE id_producto = 9 LIMIT 1")->fetchColumn();
        $db->exec("UPDATE `receta` SET `nombre` = 'Receta Papa Francesa' WHERE id_receta = $idReceta9");
        $db->exec("DELETE FROM `receta_detalle` WHERE `id_receta` = $idReceta9");
        $db->exec("INSERT INTO `receta_detalle` (`id_receta`, `id_insumo`, `cantidad`) VALUES 
                   ($idReceta9, 8, 0.250),
                   ($idReceta9, 9, 0.040),
                   ($idReceta9, 3, 0.005)");

        // Producto 4 (Combo Pareja): CONSERVA Sal = 0.020 kg
        $idReceta4 = (int)$db->query("SELECT id_receta FROM `receta` WHERE id_producto = 4 LIMIT 1")->fetchColumn();
        $db->exec("UPDATE `receta` SET `nombre` = 'Receta Combo Pareja' WHERE id_receta = $idReceta4");
        $db->exec("DELETE FROM `receta_detalle` WHERE `id_receta` = $idReceta4");
        $db->exec("INSERT INTO `receta_detalle` (`id_receta`, `id_insumo`, `cantidad`) VALUES 
                   ($idReceta4, 1, 0.500),
                   ($idReceta4, 2, 2.000),
                   ($idReceta4, 3, 0.020),
                   ($idReceta4, 4, 0.010),
                   ($idReceta4, 8, 0.250),
                   ($idReceta4, 9, 0.040),
                   ($idReceta4, $idGaseosa, 2.000)");

        // Producto 5 (Combo Familiar)
        $idReceta5 = (int)$db->query("SELECT id_receta FROM `receta` WHERE id_producto = 5 LIMIT 1")->fetchColumn();
        $db->exec("UPDATE `receta` SET `nombre` = 'Receta Combo Familiar' WHERE id_receta = $idReceta5");
        $db->exec("DELETE FROM `receta_detalle` WHERE `id_receta` = $idReceta5");
        $db->exec("INSERT INTO `receta_detalle` (`id_receta`, `id_insumo`, `cantidad`) VALUES 
                   ($idReceta5, 1, 1.000),
                   ($idReceta5, 2, 4.000),
                   ($idReceta5, 3, 0.030),
                   ($idReceta5, 4, 0.020),
                   ($idReceta5, 8, 0.500),
                   ($idReceta5, 9, 0.080),
                   ($idReceta5, 10, 0.100),
                   ($idReceta5, $idGaseosa, 4.000)");

        $db->commit();
        echo "✔ Transacción confirmada exitosamente (COMMIT).\n\n";

    } catch (Exception $e) {
        $db->rollBack();
        die("❌ ERROR en transacción: " . $e->getMessage() . "\n");
    }
}

// Aplicar en la base de datos real y de pruebas
applyMigrationToDatabase('chorifest');
applyMigrationToDatabase('chorifest_test');

// -------------------------------------------------------------------------
// 3. SELECT CON LAS RECETAS FINALES DE LOS 10 PRODUCTOS
// -------------------------------------------------------------------------
$db = getDatabaseConnection('chorifest');
echo "========================================================================================================================\n";
echo "SELECT RECETAS FINALES DE LOS 10 PRODUCTOS (BASE DE DATOS: chorifest)\n";
echo "========================================================================================================================\n";

$sql = "
    SELECT p.id_producto, p.nombre_producto, c.nombre as categoria, p.precio,
           r.id_receta, r.nombre as receta_nombre,
           rd.id_insumo, i.nombre as insumo_nombre, rd.cantidad as cant_insumo, i.unidad_medida,
           inv.cantidad as stock_insumo_bodega
    FROM producto p
    JOIN categoria c ON p.id_categoria = c.id_categoria
    JOIN receta r ON p.id_producto = r.id_producto
    JOIN receta_detalle rd ON r.id_receta = rd.id_receta
    JOIN insumo i ON rd.id_insumo = i.id_insumo
    LEFT JOIN inventario_insumo inv ON i.id_insumo = inv.id_insumo
    ORDER BY p.id_producto ASC, rd.id_insumo ASC
";

$rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$currentProd = null;
foreach ($rows as $r) {
    if ($currentProd !== $r['id_producto']) {
        if ($currentProd !== null) echo "\n";
        $currentProd = $r['id_producto'];
        echo "PRODUCTO #{$r['id_producto']}: {$r['nombre_producto']} | Cat: {$r['categoria']} | Precio: \${$r['precio']} | Receta: '{$r['receta_nombre']}' (#{$r['id_receta']})\n";
    }
    printf("   -> Insumo #%-2d %-25s | Cant Req: %-7s %-6s | Stock Bodega: %s %s\n",
        $r['id_insumo'],
        $r['insumo_nombre'],
        number_format((float)$r['cant_insumo'], 3),
        $r['unidad_medida'],
        number_format((float)$r['stock_insumo_bodega'], 3),
        $r['unidad_medida']
    );
}
echo "========================================================================================================================\n\n";

// -------------------------------------------------------------------------
// 4. VERIFICAR QUE GASEOSA Y JUGO MUESTRAN "Máx: X uds" EN CATÁLOGO
// -------------------------------------------------------------------------
echo "=====================================================================================\n";
echo "DISPONIBILIDAD Y 'MÁX: X UDS' EN CATÁLOGO DEL CLIENTE (INCLUIDAS BEBIDAS):\n";
echo "=====================================================================================\n";

require_once __DIR__ . '/../models/InventarioModel.php';
$invModel = new InventarioModel($db);
$stockStatus = $invModel->getProductosStockStatus();

foreach ([6, 7, 4, 5, 1, 2, 3, 8, 9, 10] as $id) {
    $p = $stockStatus[$id] ?? null;
    if ($p) {
        $maxTxt = $p['cantidad_maxima'] !== null ? "Máx: {$p['cantidad_maxima']} uds" : "Ilimitado / Sin receta";
        $visTxt = $p['visible_cliente'] ? "VISIBLE" : "AGOTADO/OCULTO";
        printf("ID %-2d | %-25s | %-15s | %s\n",
            $p['id_producto'],
            $p['nombre_producto'],
            $maxTxt,
            $visTxt
        );
    }
}
echo "=====================================================================================\n";
