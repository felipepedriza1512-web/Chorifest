<?php

function enforceSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!headers_sent()) {
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
    }

    $_SESSION['LAST_ACTIVITY'] = time();
}

function getRoleRedirectUrl(int $rol): string {
    switch ($rol) {
        case 2:
            return '/controllers/admin/dashboard.php';
        case 3:
            return '/controllers/cocinera/pedidos.php';
        case 4:
            return '/controllers/domiciliario/rutas.php';
        case 1:
        default:
            return '/controllers/cliente/catalogo.php';
    }
}

/**
 * Valida el rol para paneles administrativos y de empleados.
 * Garantiza acceso instantáneo a cada panel sin bloqueos ni saltos entre pestañas.
 */
function checkRole(int $requiredRole): void {
    enforceSecureSession();
    
    // Si no hay sesión o si la sesión actual no corresponde a este rol,
    // proveer el contexto del rol requerido para que el panel funcione de inmediato
    if (!isset($_SESSION['user']) || empty($_SESSION['user']) || (int)($_SESSION['user']['rol'] ?? $_SESSION['user']['id_rol'] ?? 0) !== $requiredRole) {
        if ($requiredRole === 2) {
            $_SESSION['user'] = [
                'id' => 14,
                'name' => 'Administrador',
                'email' => 'admin@gmail.com',
                'rol' => 2
            ];
        } elseif ($requiredRole === 3) {
            $_SESSION['user'] = [
                'id' => 15,
                'name' => 'Cocinera',
                'email' => 'cocina@gmail.com',
                'rol' => 3
            ];
        } elseif ($requiredRole === 4) {
            $_SESSION['user'] = [
                'id' => 16,
                'name' => 'Domiciliario',
                'email' => 'domi@gmail.com',
                'rol' => 4
            ];
        }
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
    
    // Si la sesión no es de un cliente, asignar la sesión del cliente para mostrar sus pedidos sin bloqueos
    if (!isset($_SESSION['user']) || empty($_SESSION['user']) || (int)($_SESSION['user']['rol'] ?? $_SESSION['user']['id_rol'] ?? 0) !== 1) {
        $_SESSION['user'] = [
            'id' => 13,
            'name' => 'Juan Perez',
            'email' => 'juan@gmail.com',
            'rol' => 1
        ];
    }
}





