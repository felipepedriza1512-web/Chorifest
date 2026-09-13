<?php
require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkClientOnly();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/PedidoModel.php';

$currentUser = $_SESSION['user'];


$db = getDatabaseConnection();
$pedidoModel = new PedidoModel($db);

$misPedidos = $pedidoModel->getMisPedidos($currentUser['id']);

$currentPage = 'pedidos';
$pageTitle = 'Mis Pedidos';
require_once __DIR__ . '/../../views/layouts/cliente_header.php';
require_once __DIR__ . '/../../views/cliente/pedidos.php';
require_once __DIR__ . '/../../views/layouts/cliente_footer.php';
