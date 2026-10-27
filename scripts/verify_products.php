<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ProductoModel.php';

$db = getDatabaseConnection();
$model = new ProductoModel($db);

$productos = $model->getProductos();
foreach ($productos as $p) {
    $img = $model->getImagenUrl($p['nombre_producto'], $p['id_producto']);
    echo "ID {$p['id_producto']} | Nombre: {$p['nombre_producto']} | Desc: {$p['descripcion']} | Img: {$img}\n";
}
