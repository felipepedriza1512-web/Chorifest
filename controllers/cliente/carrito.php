<?php
session_start();
require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkClientOrGuest();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/ProductoModel.php';

if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

$db = getDatabaseConnection();
$productoModel = new ProductoModel($db);

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

if ($action === 'add') {
    $id = (int)($_POST['id_producto'] ?? 0);
    $cantidad = (int)($_POST['cantidad'] ?? 1);
    
    if ($id > 0) {
        $prod = $productoModel->getProductoById($id);
        if ($prod && $prod['disponible']) {
            if (isset($_SESSION['carrito'][$id])) {
                $_SESSION['carrito'][$id]['cantidad'] += $cantidad;
            } else {
                $_SESSION['carrito'][$id] = [
                    'producto' => $prod,
                    'cantidad' => $cantidad
                ];
            }
        }
    }
    header('Location: catalogo.php');
    exit;
} elseif ($action === 'update') {
    $id = (int)($_POST['id_producto'] ?? 0);
    $cantidad = (int)($_POST['cantidad'] ?? 1);
    if ($id > 0 && isset($_SESSION['carrito'][$id])) {
        if ($cantidad > 0) {
            $_SESSION['carrito'][$id]['cantidad'] = $cantidad;
        } else {
            unset($_SESSION['carrito'][$id]);
        }
    }
    header('Location: carrito.php');
    exit;
} elseif ($action === 'remove') {
    $id = (int)($_POST['id_producto'] ?? 0);
    if ($id > 0 && isset($_SESSION['carrito'][$id])) {
        unset($_SESSION['carrito'][$id]);
    }
    header('Location: carrito.php');
    exit;
} elseif ($action === 'clear') {
    $_SESSION['carrito'] = [];
    header('Location: carrito.php');
    exit;
}

$currentPage = 'carrito';
$pageTitle = 'Mi Carrito';

require_once __DIR__ . '/../../views/layouts/cliente_header.php';
require_once __DIR__ . '/../../views/cliente/carrito.php';
require_once __DIR__ . '/../../views/layouts/cliente_footer.php';
