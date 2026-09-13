<?php
session_start();
require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkRole(1); // Solo admin
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/IngredienteModel.php';
require_once __DIR__ . '/../../models/ProductoModel.php';

$db = getDatabaseConnection();
$ingredienteModel = new IngredienteModel($db);
$productoModel = new ProductoModel($db);

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'create') {
        $nombre = trim($_POST['nombre'] ?? '');
        if (!empty($nombre)) {
            $ingredienteModel->createIngrediente($nombre);
        }
    } elseif ($action === 'update') {
        $id = (int)($_POST['id_ingrediente'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        if ($id > 0 && !empty($nombre)) {
            $ingredienteModel->updateIngrediente($id, $nombre);
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id_ingrediente'] ?? 0);
        if ($id > 0) {
            $ingredienteModel->deleteIngrediente($id);
        }
    } elseif ($action === 'toggle_estado') {
        $id = (int)($_POST['id_ingrediente'] ?? 0);
        if ($id > 0) {
            $ingredienteModel->toggleEstado($id);
        }
    } elseif ($action === 'assign_products') {
        $idIngrediente = (int)($_POST['id_ingrediente'] ?? 0);
        $productosIds = $_POST['productos'] ?? [];
        if ($idIngrediente > 0) {
            // Eliminar todas las relaciones para este ingrediente y crear nuevas
            $stmtDel = $db->prepare("DELETE FROM producto_ingrediente WHERE id_ingrediente = ?");
            $stmtDel->execute([$idIngrediente]);
            
            if (!empty($productosIds)) {
                $stmtIns = $db->prepare("INSERT INTO producto_ingrediente (id_producto, id_ingrediente) VALUES (?, ?)");
                foreach ($productosIds as $idProd) {
                    $stmtIns->execute([(int)$idProd, $idIngrediente]);
                }
            }
        }
    }

    header('Location: ingredientes.php');
    exit;
}

$ingredientes = $ingredienteModel->getIngredientes();
$productos = $productoModel->getProductos();

// Para cada ingrediente, obtener los IDs de los productos a los que pertenece
foreach ($ingredientes as &$ing) {
    $stmt = $db->prepare("SELECT id_producto FROM producto_ingrediente WHERE id_ingrediente = ?");
    $stmt->execute([$ing['id_ingrediente']]);
    $ing['productos'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$currentPage = 'ingredientes';
$pageTitle = 'Gestión de Ingredientes';

require_once __DIR__ . '/../../views/layouts/admin_header.php';
require_once __DIR__ . '/../../views/admin/ingredientes.php';
require_once __DIR__ . '/../../views/layouts/admin_footer.php';
