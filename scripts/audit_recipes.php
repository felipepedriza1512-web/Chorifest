<?php
require_once __DIR__ . '/../config/database.php';
$db = getDatabaseConnection();

$prods = $db->query("
    SELECT p.id_producto, p.nombre_producto, p.precio, c.nombre as categoria, r.id_receta, r.nombre as receta_nombre, r.estado as receta_estado
    FROM producto p
    LEFT JOIN categoria c ON p.id_categoria = c.id_categoria
    LEFT JOIN receta r ON p.id_producto = r.id_producto
    ORDER BY p.id_producto
")->fetchAll(PDO::FETCH_ASSOC);

echo "=== AUDITORIA COMPLETA DE PRODUCTOS Y RECETAS ===\n\n";

foreach ($prods as $p) {
    echo "PRODUCTO #{$p['id_producto']}: {$p['nombre_producto']} (Cat: {$p['categoria']}, Precio: \${$p['precio']})\n";
    if ($p['id_receta']) {
        echo "  RECETA #{$p['id_receta']}: {$p['receta_nombre']} (Estado: {$p['receta_estado']})\n";
        $det = $db->query("
            SELECT rd.id_insumo, i.nombre, rd.cantidad, i.unidad_medida, COALESCE(inv.cantidad, 0) as stock_actual, i.stock_minimo
            FROM receta_detalle rd
            JOIN insumo i ON rd.id_insumo = i.id_insumo
            LEFT JOIN inventario_insumo inv ON i.id_insumo = inv.id_insumo
            WHERE rd.id_receta = {$p['id_receta']}
            ORDER BY rd.id_insumo
        ")->fetchAll(PDO::FETCH_ASSOC);

        if (empty($det)) {
            echo "    [!] Receta sin insumos asignados en receta_detalle.\n";
        } else {
            foreach ($det as $d) {
                echo "    - Insumo #{$d['id_insumo']} [{$d['nombre']}]: {$d['cantidad']} {$d['unidad_medida']} (Stock en bodega: {$d['stock_actual']} {$d['unidad_medida']})\n";
            }
        }
    } else {
        echo "  [Sin receta registrada]\n";
    }
    echo "\n";
}

echo "=== INSUMOS HUÉRFANOS O NO USADOS EN RECETAS ===\n";
$insUnused = $db->query("
    SELECT i.id_insumo, i.nombre, i.unidad_medida, COALESCE(inv.cantidad, 0) as stock_actual
    FROM insumo i
    LEFT JOIN inventario_insumo inv ON i.id_insumo = inv.id_insumo
    LEFT JOIN receta_detalle rd ON i.id_insumo = rd.id_insumo
    WHERE rd.id_insumo IS NULL
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($insUnused as $iu) {
    echo "  - Insumo #{$iu['id_insumo']}: {$iu['nombre']} ({$iu['stock_actual']} {$iu['unidad_medida']}) - NO SE USA EN NINGUNA RECETA\n";
}
