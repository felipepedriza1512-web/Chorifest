<?php
require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkRole(2);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/ProductoModel.php';
require_once __DIR__ . '/../../models/InventarioModel.php';

$db = getDatabaseConnection();
$productoModel = new ProductoModel($db);
$inventarioModel = new InventarioModel($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        http_response_code(403);
        echo "<!DOCTYPE html><html><body><h1>403 Forbidden - Token CSRF Inválido o Ausente</h1></body></html>";
        exit;
    }

    $action = $_POST['action'];
    $fileFoto = $_FILES['foto'] ?? null;
    
    if ($action === 'create') {
        $data = [
            'id_categoria' => (int)($_POST['id_categoria'] ?? 1),
            'nombre_producto' => trim($_POST['nombre_producto'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'precio' => (float)($_POST['precio'] ?? 0),
            'disponible' => isset($_POST['disponible']) ? 1 : 0
        ];

        $res = $productoModel->createProducto($data, $fileFoto);
        if ($res['success']) {
            $_SESSION['flash_success'] = 'Producto creado exitosamente.';
        } else {
            $_SESSION['flash_error'] = $res['message'];
        }

    } elseif ($action === 'update') {
        $id = (int)($_POST['id_producto'] ?? 0);
        $data = [
            'id_categoria' => (int)($_POST['id_categoria'] ?? 1),
            'nombre_producto' => trim($_POST['nombre_producto'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'precio' => (float)($_POST['precio'] ?? 0),
            'disponible' => isset($_POST['disponible']) ? 1 : 0
        ];

        if ($id > 0) {
            $res = $productoModel->updateProducto($id, $data, $fileFoto);
            if ($res['success']) {
                $_SESSION['flash_success'] = 'Producto actualizado exitosamente.';
            } else {
                $_SESSION['flash_error'] = $res['message'];
            }
        }

    } elseif ($action === 'toggle') {
        $id = (int)($_POST['id_producto'] ?? 0);
        if ($id > 0) {
            $productoModel->toggleDisponible($id);
            $_SESSION['flash_success'] = 'Disponibilidad del producto actualizada.';
        }

    } elseif ($action === 'delete') {
        $id = (int)($_POST['id_producto'] ?? 0);
        if ($id > 0) {
            $res = $productoModel->ocultarOEliminar($id);
            if ($res['success']) {
                $_SESSION['flash_success'] = $res['message'];
            } else {
                $_SESSION['flash_error'] = $res['message'];
            }
        }

    } elseif ($action === 'save_receta') {
        $id = (int)($_POST['id_producto'] ?? 0);
        $nombreReceta = trim($_POST['nombre_receta'] ?? '');
        $insumosRaw = $_POST['insumos'] ?? [];

        $insumos = [];
        if (is_array($insumosRaw)) {
            foreach ($insumosRaw as $item) {
                $idIns = (int)($item['id_insumo'] ?? 0);
                $cant = (float)($item['cantidad'] ?? 0);
                if ($idIns > 0) {
                    $insumos[] = ['id_insumo' => $idIns, 'cantidad' => $cant];
                }
            }
        }

        if ($id > 0) {
            $res = $inventarioModel->guardarReceta($id, $nombreReceta, $insumos);
            if ($res['success']) {
                $msg = 'Receta guardada exitosamente.';
                if (!empty($res['pedidos_en_curso'])) {
                    $msg .= " (⚠️ Aviso: Hay {$res['pedidos_en_curso']} pedido(s) en curso que aplicarán esta nueva receta al empacar).";
                }
                $_SESSION['flash_success'] = $msg;
            } else {
                $_SESSION['flash_error'] = $res['message'];
            }
        }
    }
    
    header('Location: productos.php');
    exit;
}

// Endpoint AJAX para obtener detalles de receta
if (isset($_GET['action']) && $_GET['action'] === 'get_receta') {
    $id = (int)($_GET['id_producto'] ?? 0);
    $receta = $inventarioModel->getRecetaByProducto($id);
    $pedidosEnCurso = $inventarioModel->getPedidosEnCursoByProducto($id);
    $prod = $productoModel->getProductoById($id);
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'producto' => $prod,
        'receta' => $receta,
        'pedidos_en_curso' => $pedidosEnCurso
    ]);
    exit;
}

$flashError = $_SESSION['flash_error'] ?? '';
$flashSuccess = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

$productos = $productoModel->getProductos();
$categorias = $productoModel->getCategorias();
$stockStatus = $inventarioModel->getProductosStockStatus();
$todosInsumos = $inventarioModel->getInsumos();


$currentPage = 'productos';
$pageTitle = 'Catálogo de Productos';

require_once __DIR__ . '/../../views/layouts/admin_header.php';
require_once __DIR__ . '/../../views/admin/productos.php';
require_once __DIR__ . '/../../views/layouts/admin_footer.php';
