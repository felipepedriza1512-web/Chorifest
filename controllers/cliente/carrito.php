<?php
require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkClientOrGuest();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/ProductoModel.php';
require_once __DIR__ . '/../../models/InventarioModel.php';

if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

$db = getDatabaseConnection();
$productoModel = new ProductoModel($db);
$inventarioModel = new InventarioModel($db);

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

if ($action === 'add') {
    $id = (int)($_POST['id_producto'] ?? 0);
    $cantidad = (int)($_POST['cantidad'] ?? 1);
    
    if ($id > 0 && $cantidad > 0) {
        $prod = $productoModel->getProductoById($id);
        if ($prod && $prod['disponible']) {
            $maxDisp = $inventarioModel->getCantidadMaximaDisponible($id);
            $cantActual = isset($_SESSION['carrito'][$id]) ? (int)$_SESSION['carrito'][$id]['cantidad'] : 0;
            $cantNueva = $cantActual + $cantidad;

            if ($maxDisp !== null) {
                if ($cantNueva > $maxDisp) {
                    $cantNueva = $maxDisp;
                }
            }

            if ($cantNueva > 0) {
                // Simular carrito con el nuevo ítem para validar insumos totales combinados
                $carritoPropuesto = $_SESSION['carrito'];
                $carritoPropuesto[$id] = [
                    'producto' => $prod,
                    'cantidad' => $cantNueva
                ];

                $valCarrito = $inventarioModel->verificarStockCarrito($carritoPropuesto);
                if (!$valCarrito['valido']) {
                    $_SESSION['cart_error'] = $valCarrito['mensaje'];
                } else {
                    $_SESSION['carrito'][$id] = [
                        'producto' => $prod,
                        'cantidad' => $cantNueva
                    ];
                }
            }
        }
    }
    header('Location: ' . (!empty($_POST['from_cart']) ? 'carrito.php' : 'catalogo.php'));
    exit;
} elseif ($action === 'update') {
    $id = (int)($_POST['id_producto'] ?? 0);
    $cantidad = (int)($_POST['cantidad'] ?? 1);
    if ($id > 0 && isset($_SESSION['carrito'][$id])) {
        if ($cantidad > 0) {
            $maxDisp = $inventarioModel->getCantidadMaximaDisponible($id);
            if ($maxDisp !== null && $cantidad > $maxDisp) {
                $cantidad = $maxDisp;
            }

            // Simular actualización para validar stock total combinado
            $carritoPropuesto = $_SESSION['carrito'];
            $carritoPropuesto[$id]['cantidad'] = $cantidad;
            $valCarrito = $inventarioModel->verificarStockCarrito($carritoPropuesto);

            if (!$valCarrito['valido']) {
                $_SESSION['cart_error'] = $valCarrito['mensaje'];
            } else {
                $_SESSION['carrito'][$id]['cantidad'] = $cantidad;
            }
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
