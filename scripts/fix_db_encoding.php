<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = getDatabaseConnection();

    // 1. Fix Categorías
    $categorias = [
        1 => ['nombre' => 'Chorizos', 'descripcion' => 'Chorizos artesanales a la parrilla'],
        2 => ['nombre' => 'Combos', 'descripcion' => 'Combos especiales para compartir'],
        3 => ['nombre' => 'Bebidas', 'descripcion' => 'Bebidas frías y refrescantes'],
        4 => ['nombre' => 'Salsas', 'descripcion' => 'Salsas especiales de la casa'],
        5 => ['nombre' => 'Adiciones', 'descripcion' => 'Acompañantes y adiciones crujientes'],
        6 => ['nombre' => 'Especiales', 'descripcion' => 'Especialidades gourmet de la casa'],
        7 => ['nombre' => 'Promociones', 'descripcion' => 'Promociones y ofertas de temporada'],
        8 => ['nombre' => 'Familiares', 'descripcion' => 'Presentaciones ideales para la familia'],
        9 => ['nombre' => 'Individuales', 'descripcion' => 'Porciones individuales a la parrilla'],
        10 => ['nombre' => 'Temporada', 'descripcion' => 'Platos de temporada especiales']
    ];

    $stmtCat = $db->prepare("UPDATE categoria SET nombre = ?, descripcion = ? WHERE id_categoria = ?");
    foreach ($categorias as $id => $data) {
        $stmtCat->execute([$data['nombre'], $data['descripcion'], $id]);
    }
    echo "Categorías actualizadas correctamente.\n";

    // 2. Fix Productos
    $productos = [
        1 => ['nombre' => 'Chorizo Santandereano', 'descripcion' => 'Chorizo autóctono de Santander con especias seleccionadas y sabor ahumado.'],
        2 => ['nombre' => 'Chorizo Antioqueño', 'descripcion' => 'Chorizo tradicional paisa, jugoso y doradito a la parrilla.'],
        3 => ['nombre' => 'Chorizo Especial', 'descripcion' => 'Chorizo gourmet relleno de queso derretido y finas hierbas.'],
        4 => ['nombre' => 'Combo Pareja', 'descripcion' => '2 chorizos artesanales + papas a la francesa + 2 bebidas a elección.'],
        5 => ['nombre' => 'Combo Familiar', 'descripcion' => '4 chorizos artesanales + 2 porciones de papas + salsas de la casa + 4 bebidas.'],
        6 => ['nombre' => 'Gaseosa', 'descripcion' => 'Gaseosa refrescante personal de 400ml.'],
        7 => ['nombre' => 'Jugo Natural', 'descripcion' => 'Jugo natural de fruta fresca en agua o leche.'],
        8 => ['nombre' => 'Salsa de la Casa', 'descripcion' => 'Salsa especial ahumada con receta secreta de Chorifest.'],
        9 => ['nombre' => 'Papa Francesa', 'descripcion' => 'Porción de papas crujientes doradas al punto perfecto.'],
        10 => ['nombre' => 'Chorizo Premium', 'descripcion' => 'Corte especial de chorizo 100% lomo de cerdo madurado.']
    ];

    $stmtProd = $db->prepare("UPDATE producto SET nombre_producto = ?, descripcion = ? WHERE id_producto = ?");
    foreach ($productos as $id => $data) {
        $stmtProd->execute([$data['nombre'], $data['descripcion'], $id]);
    }
    echo "Productos actualizados correctamente.\n";

} catch (Exception $e) {
    echo "Error actualizando BD: " . $e->getMessage() . "\n";
}
