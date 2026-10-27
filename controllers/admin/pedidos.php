<?php
require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkRole(2);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/AdminModel.php';
require_once __DIR__ . '/../../models/InventarioModel.php';

$db = getDatabaseConnection();
$adminModel = new AdminModel($db);
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
    $idPedido = (int)($_POST['id_pedido'] ?? 0);

    if ($action === 'aceptar_pedido' && $idPedido > 0) {
        $stockCheck = $inventarioModel->verificarStockParaPedido($idPedido);
        $confirmadoSinStock = !empty($_POST['confirmado_sin_stock']);

        if (!$stockCheck['suficiente'] && !$confirmadoSinStock) {
            $_SESSION['flash_error'] = "Stock insuficiente para: " . $stockCheck['detalle_texto'] . ". Debes confirmar para aceptarlo.";
        } else {
            $comentario = 'Pedido Aceptado por Administrador';
            if (!$stockCheck['suficiente']) {
                $comentario .= ' (Aceptado con stock insuficiente: ' . $stockCheck['detalle_texto'] . ')';
            }
            $ok = $adminModel->updatePedidoEstadoWithAudit($idPedido, EstadoPedido::ACEPTADO, $idAdmin, $comentario);
            if (!$ok) {
                $_SESSION['flash_error'] = 'No se pudo aceptar el pedido.';
            } else {
                $_SESSION['flash_success'] = 'Pedido #' . $idPedido . ' aceptado exitosamente.';
            }
        }
    } 
    elseif ($action === 'rechazar_pedido' && $idPedido > 0) {
        $motivo = trim($_POST['motivo'] ?? 'Pedido rechazado por el restaurante.');
        $ok = $adminModel->updatePedidoEstadoWithAudit($idPedido, EstadoPedido::RECHAZADO, $idAdmin, $motivo);
        if (!$ok) {
            $_SESSION['flash_error'] = 'No se pudo rechazar el pedido.';
        } else {
            // Reintegrar stock si se marcó el checkbox (Opción 3)
            if (!empty($_POST['reintegrar_stock'])) {
                require_once __DIR__ . '/../../models/InventarioModel.php';
                $inv = new InventarioModel($db);
                $resReint = $inv->reintegrarStockPedido($idPedido, $motivo, $idAdmin);
                if ($resReint['success']) {
                    $_SESSION['flash_success'] = 'Pedido cancelado y stock de insumos reintegrado exitosamente.';
                }
            }
        }
    }
    elseif ($action === 'reintentar_descuento' && $idPedido > 0) {
        require_once __DIR__ . '/../../models/InventarioModel.php';
        $inv = new InventarioModel($db);
        $res = $inv->descontarStockPedido($idPedido);
        if ($res['success']) {
            $_SESSION['flash_success'] = 'Descuento de inventario procesado exitosamente.';
        } else {
            $_SESSION['flash_error'] = 'No se pudo descontar el stock: ' . $res['message'];
        }
    }
    elseif (($action === 'asignar_domiciliario' || $action === 'reasignar_domiciliario') && $idPedido > 0) {
        $idDomiciliarioNuevo = (int)($_POST['id_domiciliario'] ?? 0);
        $idDomiciliarioActual = isset($_POST['id_domiciliario_actual']) ? (int)$_POST['id_domiciliario_actual'] : null;

        if ($idDomiciliarioNuevo > 0) {
            $res = $adminModel->reasignarDomiciliario($idPedido, $idDomiciliarioNuevo, $idAdmin, $idDomiciliarioActual);
            if (!$res['success']) {
                $_SESSION['flash_error'] = $res['message'];
            } else {
                $_SESSION['flash_success'] = $res['message'];
            }
        }
    }
    elseif ($action === 'update_status' && $idPedido > 0) {
        $idEstado = (int)$_POST['id_estado'];
        if ($idEstado > 0) {
            $adminModel->updatePedidoStatus($idPedido, $idEstado);
            $db->prepare("INSERT INTO historial_pedido (id_pedido, id_estado, id_usuario, comentario, fecha) VALUES (?, ?, ?, 'Estado actualizado por administrador', NOW())")
               ->execute([$idPedido, $idEstado, $idAdmin]);
        }
    }

    header('Location: pedidos.php');
    exit;
}

$flashError = $_SESSION['flash_error'] ?? '';
$flashSuccess = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

$pedidos = $adminModel->getPedidos();
$estados = $adminModel->getEstadosPedido();
$domiciliarios = $adminModel->getDomiciliarios();

$currentPage = 'pedidos';
$pageTitle = 'Gestión de Pedidos';

require_once __DIR__ . '/../../views/layouts/admin_header.php';
require_once __DIR__ . '/../../views/admin/pedidos.php';
require_once __DIR__ . '/../../views/layouts/admin_footer.php';
