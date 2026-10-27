<?php
require_once __DIR__ . '/../config/database.php';
$db = getDatabaseConnection();

echo "=== INSUMOS Y SU STOCK ===\n";
$ins = $db->query("
    SELECT i.id_insumo, i.nombre, i.unidad_medida, i.stock_minimo, i.estado, COALESCE(inv.cantidad, 0) as stock_actual
    FROM insumo i
    LEFT JOIN inventario_insumo inv ON i.id_insumo = inv.id_insumo
    ORDER BY i.id_insumo
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($ins as $item) {
    echo "ID {$item['id_insumo']}: {$item['nombre']} | Cant: {$item['stock_actual']} {$item['unidad_medida']} | Min: {$item['stock_minimo']} | Estado: {$item['estado']}\n";
}

echo "\n=== PRODUCTOS Y RECETAS ===\n";
$prods = $db->query("
    SELECT p.id_producto, p.nombre_producto, p.disponible, r.id_receta, r.nombre as nombre_receta, r.estado as receta_estado,
           rd.id_insumo, i.nombre as insumo_nombre, i.unidad_medida, rd.cantidad
    FROM producto p
    LEFT JOIN receta r ON p.id_producto = r.id_producto
    LEFT JOIN receta_detalle rd ON r.id_receta = rd.id_receta
    LEFT JOIN insumo i ON rd.id_insumo = i.id_insumo
    ORDER BY p.id_producto, rd.id_insumo
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($prods as $p) {
    echo "Prod [{$p['id_producto']}] {$p['nombre_producto']} (Disp: {$p['disponible']}) | Receta [{$p['id_receta']}] {$p['nombre_receta']} | Insumo: [{$p['id_insumo']}] {$p['insumo_nombre']} - Cant: {$p['cantidad']} {$p['unidad_medida']}\n";
}



