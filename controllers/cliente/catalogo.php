<?php
require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkClientOrGuest();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/ProductoModel.php';
require_once __DIR__ . '/../../models/InventarioModel.php';

$db = getDatabaseConnection();
$productoModel = new ProductoModel($db);
$inventarioModel = new InventarioModel($db);

$categorias = $productoModel->getCategorias();

// Check if filtering by category
$categoriaActiva = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
$todosProductos = $productoModel->getProductos($categoriaActiva);
$stockStatus = $inventarioModel->getProductosStockStatus();

// REGLAS 5 Y 6 DEL PROMPT:
// 1) Un producto cuyo ingrediente tenga stock < cantidad_unitaria de su receta se oculta del menú del cliente.
// 2) Vuelve automáticamente cuando se repone el stock.
// 3) No pisa el interruptor manual del admin: si el admin lo desactivó (disponible = 0), sigue desactivado.
$productosVisibles = [];
foreach ($todosProductos as $p) {
    $id = (int)$p['id_producto'];
    $st = $stockStatus[$id] ?? null;
    $disponibleAdmin = ((int)$p['disponible'] === 1);
    $disponibleStock = $st ? $st['disponible_por_stock'] : true;

    if ($disponibleAdmin && $disponibleStock) {
        $productosVisibles[] = $p;
    }
}

$productos = $productosVisibles;

$currentPage = 'catalogo';
$pageTitle = 'Catálogo de Productos';

require_once __DIR__ . '/../../views/layouts/cliente_header.php';
require_once __DIR__ . '/../../views/cliente/catalogo.php';
require_once __DIR__ . '/../../views/layouts/cliente_footer.php';
