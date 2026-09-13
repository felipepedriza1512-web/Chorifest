<?php
session_start();
require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkRole(2);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/ProductoModel.php';

$db = getDatabaseConnection();
$productoModel = new ProductoModel($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'create') {
        $data = [
            'id_categoria' => $_POST['id_categoria'] ?? 1,
            'nombre_producto' => $_POST['nombre_producto'] ?? '',
            'descripcion' => $_POST['descripcion'] ?? '',
            'precio' => (float)($_POST['precio'] ?? 0),
            'stock' => (int)($_POST['stock'] ?? 0),
            'stock_minimo' => (int)($_POST['stock_minimo'] ?? 5),
            'disponible' => 1
        ];
        if (!empty($data['nombre_producto']) && $data['precio'] > 0) {
            $productoModel->createProducto($data);
        }
    } elseif ($action === 'update') {
        $id = (int)$_POST['id_producto'];
        $data = [
            'id_categoria' => $_POST['id_categoria'] ?? 1,
            'nombre_producto' => $_POST['nombre_producto'] ?? '',
            'descripcion' => $_POST['descripcion'] ?? '',
            'precio' => (float)($_POST['precio'] ?? 0),
            'stock' => (int)($_POST['stock'] ?? 0),
            'stock_minimo' => (int)($_POST['stock_minimo'] ?? 5),
            'disponible' => isset($_POST['disponible']) ? 1 : 0
        ];
        if ($id > 0 && !empty($data['nombre_producto']) && $data['precio'] > 0) {
            $productoModel->updateProducto($id, $data);
        }
    } elseif ($action === 'toggle') {
        $id = (int)$_POST['id_producto'];
        if ($id > 0) {
            $productoModel->toggleDisponible($id);
        }
    }
    
    header('Location: productos.php');
    exit;
}

$productos = $productoModel->getProductos();
$categorias = $productoModel->getCategorias();

$currentPage = 'productos';
$pageTitle = 'Catálogo de Productos';

require_once __DIR__ . '/../../views/layouts/admin_header.php';
require_once __DIR__ . '/../../views/admin/productos.php';
require_once __DIR__ . '/../../views/layouts/admin_footer.php';
