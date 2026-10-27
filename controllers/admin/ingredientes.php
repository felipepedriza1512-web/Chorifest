<?php
require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkRole(2);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/InventarioModel.php';

$db = getDatabaseConnection();
$inventarioModel = new InventarioModel($db);
$idAdmin = (int)($_SESSION['user']['id'] ?? $_SESSION['user']['id_usuario'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        http_response_code(403);
        echo "<!DOCTYPE html><html><body><h1>403 Forbidden - Token CSRF Inválido o Ausente</h1></body></html>";
        exit;
    }

    $action = $_POST['action'];

    if ($action === 'entrada') {
        $idInsumo = (int)($_POST['id_insumo'] ?? 0);
        $cantidad = (float)($_POST['cantidad'] ?? 0);
        $motivo = trim($_POST['motivo'] ?? '');

        if ($idInsumo > 0 && $cantidad > 0 && !empty($motivo)) {
            $res = $inventarioModel->registrarEntrada($idInsumo, $cantidad, $motivo, $idAdmin);
            if ($res['success']) {
                $_SESSION['flash_success'] = 'Entrada de inventario registrada exitosamente.';
            } else {
                $_SESSION['flash_error'] = $res['message'];
            }
        } else {
            $_SESSION['flash_error'] = 'Todos los campos son obligatorios: insumo, cantidad mayor a 0 y motivo.';
        }

    } elseif ($action === 'ajuste') {
        $idInsumo = (int)($_POST['id_insumo'] ?? 0);
        $nuevoStock = (float)($_POST['nuevo_stock'] ?? -1);
        $motivo = trim($_POST['motivo'] ?? '');

        if ($idInsumo > 0 && $nuevoStock >= 0 && !empty($motivo)) {
            $res = $inventarioModel->registrarAjuste($idInsumo, $nuevoStock, $motivo, $idAdmin);
            if ($res['success']) {
                $_SESSION['flash_success'] = 'Ajuste de inventario aplicado exitosamente.';
            } else {
                $_SESSION['flash_error'] = $res['message'];
            }
        } else {
            $_SESSION['flash_error'] = 'Datos inválidos. El nuevo stock no puede ser negativo y el motivo es obligatorio.';
        }
    }

    header('Location: ingredientes.php');
    exit;
}

$flashError = $_SESSION['flash_error'] ?? '';
$flashSuccess = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

$insumos = $inventarioModel->getInsumos();
$movimientos = $inventarioModel->getMovimientos(0, 50);
$alertas = $inventarioModel->getAlertasStockMinimo();

$currentPage = 'ingredientes';
$pageTitle = 'Inventario e Ingredientes';

require_once __DIR__ . '/../../views/layouts/admin_header.php';
require_once __DIR__ . '/../../views/admin/ingredientes.php';
require_once __DIR__ . '/../../views/layouts/admin_footer.php';
