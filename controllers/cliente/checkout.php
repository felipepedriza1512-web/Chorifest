<?php
require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkClientOnly();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/PedidoModel.php';
require_once __DIR__ . '/../../models/InventarioModel.php';

$currentUser = $_SESSION['user'];

$db = getDatabaseConnection();
$pedidoModel = new PedidoModel($db);
$inventarioModel = new InventarioModel($db);

$zonasEnvio = $pedidoModel->getZonasEnvio();
$error = '';
$success = false;
$idPedidoGenerado = null;

if (empty($_SESSION['carrito']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: catalogo.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die("Error 403: Token CSRF inválido o ausente.");
    }

    $direccionData = [
        'id_zona' => $_POST['id_zona'] ?? '',
        'direccion' => $_POST['direccion'] ?? '',
        'barrio' => $_POST['barrio'] ?? '',
        'referencia' => $_POST['referencia'] ?? '',
        'latitud' => $_POST['latitud'] ?? '',
        'longitud' => $_POST['longitud'] ?? ''
    ];
    $metodoPago = $_POST['metodo_pago'] ?? '';
    $observaciones = $_POST['observaciones'] ?? '';

    // Validación de stock máximo disponible por receta (Regla 5 del prompt)
    if (!empty($_SESSION['carrito'])) {
        foreach ($_SESSION['carrito'] as $item) {
            $idProd = (int)$item['producto']['id_producto'];
            $cantItem = (int)$item['cantidad'];
            $maxDisp = $inventarioModel->getCantidadMaximaDisponible($idProd);
            if ($maxDisp !== null && $cantItem > $maxDisp) {
                $nombre = $item['producto']['nombre_producto'] ?? 'Producto';
                $error = "Stock insuficiente para '{$nombre}'. Solicitaste {$cantItem} unidad(es), pero actualmente solo hay insumos disponibles para {$maxDisp}. Por favor ajusta tu carrito.";
                break;
            }
        }
    }

    // Validación combinada de consumo total de insumos para el carrito completo
    if (empty($error) && !empty($_SESSION['carrito'])) {
        $valCart = $inventarioModel->verificarStockCarrito($_SESSION['carrito']);
        if (!$valCart['valido']) {
            $error = $valCart['mensaje'];
        }
    }

    // Validación básica
    if (empty($error)) {
        if (empty($_SESSION['carrito'])) {
            $error = "Tu carrito está vacío. Agrega productos antes de realizar el pedido.";
        } elseif (empty($direccionData['id_zona']) || empty($direccionData['direccion']) || empty($metodoPago)) {
            $error = "Por favor, completa los campos requeridos (Zona, Dirección y Método de pago).";
        } else {
        $idCliente = (int)$currentUser['id'];
        $comprobantePath = null;

        // Manejar subida de comprobante si es Nequi
        if ($metodoPago === 'Transferencia / Nequi' && isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../public/img/comprobantes/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileExtension = strtolower(pathinfo($_FILES['comprobante']['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
            
            if (in_array($fileExtension, $allowedExtensions)) {
                $fileName = 'comp_' . time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
                $destination = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['comprobante']['tmp_name'], $destination)) {
                    $comprobantePath = '/public/img/comprobantes/' . $fileName;
                } else {
                    $error = "No se pudo guardar el comprobante.";
                }
            } else {
                $error = "Formato de archivo no permitido. Sube una imagen o PDF.";
            }
        }

        if (empty($error)) {
            $idPedido = $pedidoModel->createPedido($idCliente, $direccionData, $_SESSION['carrito'], $metodoPago, $observaciones, $comprobantePath);

            if ($idPedido) {
                // Vaciar carrito
                $_SESSION['carrito'] = [];
                $success = true;
                $idPedidoGenerado = $idPedido;
            } else {
                $error = $pedidoModel->getLastError() ?: "Ocurrió un error al procesar tu pedido. Inténtalo de nuevo.";
            }
        }
    }
}
}

$currentPage = 'checkout';
$pageTitle = 'Finalizar Compra';

require_once __DIR__ . '/../../views/layouts/cliente_header.php';
require_once __DIR__ . '/../../views/cliente/checkout.php';
require_once __DIR__ . '/../../views/layouts/cliente_footer.php';
