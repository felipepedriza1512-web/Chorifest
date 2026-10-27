<?php
require_once __DIR__ . '/../auth/AuthMiddleware.php';
enforceSecureSession();
require_once __DIR__ . '/../../config/database.php';

$currentUser = $_SESSION['user'] ?? null;
if (!$currentUser) {
    header('Location: ' . baseUrl('/controllers/auth/login.php'));
    exit;
}

$userRole = (int)($currentUser['rol'] ?? $currentUser['id_rol'] ?? 0);
$currentUserId = (int)($currentUser['id'] ?? $currentUser['id_usuario'] ?? 0);

if ($userRole !== 1 && $userRole !== 2) {
    header('Location: ' . baseUrl('/controllers/auth/login.php'));
    exit;
}

$idPedido = (int)($_GET['id'] ?? 0);

if ($idPedido <= 0) {
    header('Location: ' . ($userRole === 2 ? baseUrl('/controllers/admin/pedidos.php') : 'pedidos.php'));
    exit;
}

$db = getDatabaseConnection();
$stmtCheck = $db->prepare("SELECT id_cliente FROM pedido WHERE id_pedido = ?");
$stmtCheck->execute([$idPedido]);
$pedOwner = $stmtCheck->fetchColumn();

if (!$pedOwner || ($userRole !== 2 && (int)$pedOwner !== $currentUserId)) {
    http_response_code(403);
    echo "<!DOCTYPE html><html><head><title>403 Prohibido</title></head><body style='font-family:sans-serif; text-align:center; padding:50px; background:#18181b; color:#fff;'>";
    echo "<h1 style='color:#ef4444;'>403 - Acceso Denegado</h1>";
    echo "<p>No tienes permiso para ver el seguimiento de este pedido ajeno.</p>";
    echo "<a href='pedidos.php' style='color:#ea580c; text-decoration:none;'>Volver</a>";
    echo "</body></html>";
    exit;
}

$currentPage = 'pedidos';
$pageTitle = "Seguimiento en Vivo - Pedido #" . str_pad($idPedido, 5, '0', STR_PAD_LEFT);

require_once __DIR__ . '/../../views/layouts/cliente_header.php';
require_once __DIR__ . '/../../views/cliente/seguimiento.php';
require_once __DIR__ . '/../../views/layouts/cliente_footer.php';
