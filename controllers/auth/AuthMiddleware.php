<?php
require_once __DIR__ . '/../../config/database.php';

function enforceSecureSession(): void {
    startSecureSession();
    
    if (!headers_sent()) {
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
    }

    $_SESSION['LAST_ACTIVITY'] = time();

    // Verificación de estado de cuenta, última actividad y cambio obligatorio de contraseña
    if (!empty($_SESSION['user'])) {
        $userId = (int)($_SESSION['user']['id'] ?? $_SESSION['user']['id_usuario'] ?? 0);
        if ($userId > 0) {
            try {
                $db = getDatabaseConnection();
                $stmt = $db->prepare("SELECT estado, debe_cambiar_password FROM usuario WHERE id_usuario = ?");
                $stmt->execute([$userId]);
                $userData = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$userData || strtoupper($userData['estado'] ?? '') !== 'ACTIVO') {
                    // Revocación inmediata si fue desactivado
                    $_SESSION = [];
                    if (session_status() === PHP_SESSION_ACTIVE) {
                        session_destroy();
                    }
                    if ((!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
                        (!empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)) {
                        http_response_code(403);
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['success' => false, 'message' => 'Tu cuenta ha sido desactivada.']);
                        exit;
                    }
                    header('Location: ' . baseUrl('/controllers/auth/login.php?error=inactivo'));
                    exit;
                }

                $_SESSION['user']['debe_cambiar_password'] = (int)$userData['debe_cambiar_password'];

                // Actualizar timestamp de última actividad cada 30 segundos
                $now = time();
                if (!isset($_SESSION['LAST_DB_ACTIVITY_UPDATE']) || ($now - $_SESSION['LAST_DB_ACTIVITY_UPDATE']) > 30) {
                    $_SESSION['LAST_DB_ACTIVITY_UPDATE'] = $now;
                    $stmtAct = $db->prepare("UPDATE usuario SET ultima_actividad = NOW() WHERE id_usuario = ?");
                    $stmtAct->execute([$userId]);
                }

                // Redirección obligatoria si debe cambiar contraseña
                if ((int)$userData['debe_cambiar_password'] === 1) {
                    $currentScript = basename($_SERVER['PHP_SELF'] ?? '');
                    if ($currentScript !== 'cambiar_password_obligatorio.php' && $currentScript !== 'logout.php') {
                        if ((!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
                            (!empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)) {
                            http_response_code(403);
                            header('Content-Type: application/json; charset=utf-8');
                            echo json_encode(['success' => false, 'message' => 'Debe actualizar su contraseña obligatoria antes de continuar.']);
                            exit;
                        }
                        header('Location: ' . baseUrl('/controllers/auth/cambiar_password_obligatorio.php'));
                        exit;
                    }
                }
            } catch (\Throwable $e) {
                // Silencioso ante excepciones
            }
        }
    }
}

function getRoleRedirectUrl(int $rol): string {
    switch ($rol) {
        case 2:
            return baseUrl('/controllers/admin/dashboard.php');
        case 3:
            return baseUrl('/controllers/cocinera/pedidos.php');
        case 4:
            return baseUrl('/controllers/domiciliario/rutas.php');
        case 1:
        default:
            return baseUrl('/controllers/cliente/catalogo.php');
    }
}

/**
 * Valida el rol para paneles administrativos y de empleados.
 * Devuelve 403 Forbidden estricto si no hay sesión o si el rol no coincide.
 */
function checkRole(int $requiredRole): void {
    enforceSecureSession();
    
    $userRole = (int)($_SESSION['user']['rol'] ?? $_SESSION['user']['id_rol'] ?? 0);
    
    if (!isset($_SESSION['user']) || empty($_SESSION['user']) || $userRole !== $requiredRole) {
        http_response_code(403);
        if ((!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
            (!empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Acceso Denegado (403): Permisos insuficientes.']);
            exit;
        }
        header('Content-Type: text/html; charset=utf-8');
        echo "<!DOCTYPE html><html><head><title>403 Prohibido</title></head><body style='font-family:sans-serif; text-align:center; padding:50px; background:#18181b; color:#fff;'>";
        echo "<h1 style='color:#ef4444;'>403 - Acceso Denegado</h1>";
        echo "<p>No tienes los permisos requeridos para acceder a este recurso.</p>";
        echo "<a href='" . baseUrl('/controllers/auth/login.php') . "' style='color:#ea580c; text-decoration:none;'>Iniciar Sesión</a>";
        echo "</body></html>";
        exit;
    }
}

/**
 * Controla el acceso a páginas públicas o de cliente (Catálogo, Carrito, Inicio).
 */
function checkClientOrGuest(): void {
    enforceSecureSession();
}

/**
 * Valida acceso a compras y pedidos del cliente.
 */
function checkClientOnly(): void {
    enforceSecureSession();
    
    $userRole = (int)($_SESSION['user']['rol'] ?? $_SESSION['user']['id_rol'] ?? 0);
    if (!isset($_SESSION['user']) || empty($_SESSION['user']) || $userRole !== 1) {
        header('Location: ' . baseUrl('/controllers/auth/login.php'));
        exit;
    }
}






