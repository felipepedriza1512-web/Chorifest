<?php
require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkRole(2); // Validar sesión de administrador

require_once __DIR__ . '/../../config/database.php';

$idPedido = (int)($_GET['id_pedido'] ?? $_GET['id'] ?? 0);
$tipo = trim($_GET['tipo'] ?? 'cliente'); // 'cliente' o 'devolucion'

if ($idPedido <= 0) {
    http_response_code(400);
    echo "ID de pedido inválido.";
    exit;
}

$db = getDatabaseConnection();
$stmt = $db->prepare("SELECT comprobante FROM pago WHERE id_pedido = ?");
$stmt->execute([$idPedido]);
$pago = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pago) {
    http_response_code(404);
    echo "No se encontró registro de pago para este pedido.";
    exit;
}

$relPath = $pago['comprobante'] ?? '';

if (empty($relPath)) {
    http_response_code(404);
    echo "No hay comprobante registrado para este pedido.";
    exit;
}

// Raíz del proyecto y directorios permitidos
$projectRoot = realpath(__DIR__ . '/../../');
$allowedDirs = [
    realpath(__DIR__ . '/../../public/img/comprobantes'),
    realpath(__DIR__ . '/../../storage/comprobantes')
];

// Remover prefijos de barra o subdirectorio para construir ruta absoluta segura
$cleanRelPath = ltrim(str_replace(['/Chorifest', '/chorifest'], '', $relPath), '/\\');
$fullPath = realpath($projectRoot . '/' . $cleanRelPath);

if (!$fullPath) {
    http_response_code(404);
    echo "El archivo del comprobante no existe en el servidor.";
    exit;
}

// Validar que la ruta real empiece por uno de los directorios permitidos (anti Path Traversal)
$isPathAllowed = false;
foreach ($allowedDirs as $allowedDir) {
    if ($allowedDir && strpos($fullPath, $allowedDir) === 0) {
        $isPathAllowed = true;
        break;
    }
}

if (!$isPathAllowed) {
    http_response_code(403);
    echo "Acceso denegado: Ruta de archivo no permitida.";
    exit;
}

// Validar extensión
$ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
$allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

if (!in_array($ext, $allowedExts, true)) {
    http_response_code(415);
    echo "Tipo de archivo no soportado.";
    exit;
}

// Validar tipo MIME real
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $fullPath);
finfo_close($finfo);

$allowedMimes = [
    'image/jpeg',
    'image/png',
    'image/webp',
    'application/pdf'
];

if (!in_array($mimeType, $allowedMimes, true)) {
    http_response_code(415);
    echo "Formato de contenido no permitido.";
    exit;
}

// Enviar cabeceras de seguridad y servir archivo
header('Content-Type: ' . $mimeType);
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . filesize($fullPath));
header('Content-Disposition: inline; filename="' . basename($fullPath) . '"');
header('Cache-Control: private, max-age=3600');

readfile($fullPath);
exit;
