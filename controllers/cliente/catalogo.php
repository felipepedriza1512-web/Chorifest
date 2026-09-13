<?php
session_start();
require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkClientOrGuest();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/ProductoModel.php';

$db = getDatabaseConnection();
$productoModel = new ProductoModel($db);

$categorias = $productoModel->getCategorias();

// Check if filtering by category
$categoriaActiva = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
$productos = $productoModel->getProductos($categoriaActiva);

$currentPage = 'catalogo';
$pageTitle = 'Catálogo de Productos';

require_once __DIR__ . '/../../views/layouts/cliente_header.php';
require_once __DIR__ . '/../../views/cliente/catalogo.php';
require_once __DIR__ . '/../../views/layouts/cliente_footer.php';
