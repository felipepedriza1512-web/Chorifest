<?php
require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkRole(2);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/AdminModel.php';

require_once __DIR__ . '/../../models/ZonaModel.php';

$db = getDatabaseConnection();
$adminModel = new AdminModel($db);
$zonaModel = new ZonaModel($db);
$currentUserId = (int)($_SESSION['user']['id'] ?? $_SESSION['user']['id_usuario'] ?? 0);

$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        http_response_code(403);
        die("Error 403: Token CSRF inválido o ausente.");
    }

    $action = $_POST['action'];
    
    if ($action === 'create') {
        $nombre = trim($_POST['nombre'] ?? '');
        $minimo = (float)($_POST['monto_minimo'] ?? 0);
        $tiempo = (int)($_POST['tiempo_estimado_min'] ?? 30);
        $estado = ($_POST['estado'] ?? 'ACTIVA') === 'INACTIVA' ? 'INACTIVA' : 'ACTIVA';
        $geojson = trim($_POST['poligono_geojson'] ?? '');

        if (empty($nombre)) {
            $_SESSION['flash_error'] = "El nombre de la zona es obligatorio.";
            header('Location: zonas.php');
            exit;
        }

        try {
            $zonaModel->createZona([
                'nombre' => $nombre,
                'monto_minimo' => $minimo,
                'tiempo_estimado_min' => $tiempo,
                'estado' => $estado,
                'poligono_geojson' => $geojson
            ], $currentUserId);
            $_SESSION['flash_success'] = "Zona '$nombre' creada exitosamente.";
            header('Location: zonas.php?msg=creada');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000 || (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062)) {
                $_SESSION['flash_error'] = "Ya existe una zona con el nombre '$nombre'. Si deseas editar su polígono o monto mínimo, usa el botón 'Editar / Polígono' de la zona en la lista, o ingresa un nombre diferente.";
            } else {
                $_SESSION['flash_error'] = "Error al guardar la zona: " . $e->getMessage();
            }
            header('Location: zonas.php');
            exit;
        }
    } elseif ($action === 'update') {
        $id = (int)($_POST['id_zona'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $minimo = (float)($_POST['monto_minimo'] ?? 0);
        $tiempo = (int)($_POST['tiempo_estimado_min'] ?? 30);
        $estado = ($_POST['estado'] ?? 'ACTIVA') === 'INACTIVA' ? 'INACTIVA' : 'ACTIVA';
        $geojson = trim($_POST['poligono_geojson'] ?? '');

        if ($id <= 0 || empty($nombre)) {
            $_SESSION['flash_error'] = "Parámetros inválidos para actualizar la zona.";
            header('Location: zonas.php');
            exit;
        }

        try {
            // Nota: updateZona no modifica costo_domicilio, protegiendo tarifas históricas contra POST manipulados
            $zonaModel->updateZona($id, [
                'nombre' => $nombre,
                'monto_minimo' => $minimo,
                'tiempo_estimado_min' => $tiempo,
                'estado' => $estado,
                'poligono_geojson' => $geojson
            ], $currentUserId);
            $_SESSION['flash_success'] = "Zona '$nombre' actualizada exitosamente.";
            header('Location: zonas.php?msg=actualizada');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000 || (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062)) {
                $_SESSION['flash_error'] = "Ya existe otra zona con el nombre '$nombre'. Por favor elige un nombre único.";
            } else {
                $_SESSION['flash_error'] = "Error al actualizar la zona: " . $e->getMessage();
            }
            header('Location: zonas.php');
            exit;
        }
    } elseif ($action === 'toggle_estado') {
        $id = (int)($_POST['id_zona'] ?? 0);
        $zona = $zonaModel->getZonaById($id);
        if ($zona) {
            $nuevoEstado = ($zona['estado'] === 'ACTIVA') ? 'INACTIVA' : 'ACTIVA';
            $zonaModel->updateZona($id, [
                'nombre' => $zona['nombre'],
                'costo_domicilio' => $zona['costo_domicilio'],
                'monto_minimo' => $zona['monto_minimo'],
                'tiempo_estimado_min' => $zona['tiempo_estimado_min'],
                'estado' => $nuevoEstado,
                'poligono_geojson' => $zona['poligono_geojson']
            ], $currentUserId);
            header('Location: zonas.php?msg=estado_cambiado');
            exit;
        }
    }
}

$zonas = $adminModel->getZonasEnvio();

$currentPage = 'zonas';
$pageTitle = 'Zonas de Envío';

require_once __DIR__ . '/../../views/layouts/admin_header.php';
require_once __DIR__ . '/../../views/admin/zonas.php';
require_once __DIR__ . '/../../views/layouts/admin_footer.php';
