<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UserModel.php';
require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/AuthMiddleware.php';

enforceSecureSession();

$currentUser = $_SESSION['user'] ?? null;
if (!$currentUser) {
    header('Location: ' . baseUrl('/controllers/auth/login.php'));
    exit;
}

$db = getDatabaseConnection();
$userId = (int)($currentUser['id'] ?? $currentUser['id_usuario'] ?? 0);

// Verificar en base de datos si realmente debe cambiar contraseña
$stmtCheck = $db->prepare("SELECT debe_cambiar_password FROM usuario WHERE id_usuario = ?");
$stmtCheck->execute([$userId]);
$debeCambiar = (int)$stmtCheck->fetchColumn();

if ($debeCambiar === 0) {
    header('Location: ' . getRoleRedirectUrl((int)($currentUser['rol'] ?? 1)));
    exit;
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        http_response_code(403);
        die("Error 403: Token CSRF inválido o ausente.");
    }

    $pass = $_POST['password'] ?? '';
    $passConfirm = $_POST['password_confirm'] ?? '';

    // POLÍTICA DE CONTRASEÑAS MÍNIMA ESTRICTA (Requisito 6)
    $trivialList = ['123456', '12345678', '123456789', 'password', 'contraseña', 'admin123', 'admin', 'qwerty', '111111', '123123', 'chorifest', 'chorifest123'];

    if (strlen($pass) < 8) {
        $error = "La nueva contraseña debe tener al menos 8 caracteres.";
    } elseif (in_array(strtolower($pass), $trivialList)) {
        $error = "La contraseña elegida es demasiado común o trivial. Por favor utiliza una contraseña más segura.";
    } elseif ($pass !== $passConfirm) {
        $error = "Las contraseñas no coinciden.";
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmtUpd = $db->prepare("UPDATE usuario SET password_hash = ?, debe_cambiar_password = 0 WHERE id_usuario = ?");
        $stmtUpd->execute([$hash, $userId]);

        // Registrar auditoría
        $stmtAudit = $db->prepare("INSERT INTO auditoria (id_usuario, accion, entidad, id_registro, descripcion, fecha) VALUES (?, 'CAMBIAR_PASSWORD', 'usuario', ?, 'Cambio de contraseña temporal obligatorio completado', NOW())");
        $stmtAudit->execute([$userId, $userId]);

        $_SESSION['user']['debe_cambiar_password'] = 0;
        $rol = (int)($currentUser['rol'] ?? $currentUser['id_rol'] ?? 1);
        header('Location: ' . getRoleRedirectUrl($rol));
        exit;
    }
}

require_once __DIR__ . '/../../views/auth/cambiar_password_obligatorio.php';
