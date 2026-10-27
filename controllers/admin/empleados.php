<?php
require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkRole(2); // Only admin can access
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/AdminModel.php';

$db = getDatabaseConnection();
$adminModel = new AdminModel($db);
$currentAdminId = (int)($_SESSION['user']['id'] ?? $_SESSION['user']['id_usuario'] ?? 0);

$message = $_SESSION['flash_msg'] ?? null;
$error = $_SESSION['flash_error'] ?? null;
$tempPasswordInfo = $_SESSION['flash_temp_password'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_error'], $_SESSION['flash_temp_password']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        http_response_code(403);
        die("Error 403: Token CSRF inválido o ausente.");
    }

    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $data = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'correo' => trim($_POST['correo'] ?? ''),
            'telefono' => trim($_POST['telefono'] ?? ''),
            'id_rol' => (int)($_POST['id_rol'] ?? 0),
            'password' => $_POST['password'] ?? '',
            'estado' => $_POST['estado'] ?? 'ACTIVO'
        ];
        
        if ($data['id_rol'] === 3) {
            $data['turno'] = trim($_POST['turno'] ?? 'Mañana');
        } elseif ($data['id_rol'] === 4) {
            $data['tipo_vehiculo'] = trim($_POST['tipo_vehiculo'] ?? 'Moto');
            $data['placa'] = trim($_POST['placa'] ?? '');
            $data['estado_domiciliario'] = trim($_POST['estado_domiciliario'] ?? 'ACTIVO');
        }

        $res = $adminModel->createEmpleado($data, $currentAdminId);
        if ($res['success']) {
            $_SESSION['flash_msg'] = $res['message'];
        } else {
            $_SESSION['flash_error'] = $res['message'];
        }
        header('Location: empleados.php');
        exit;

    } elseif ($action === 'update') {
        $id = (int)($_POST['id_usuario'] ?? 0);
        $data = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'telefono' => trim($_POST['telefono'] ?? ''),
            'estado' => $_POST['estado'] ?? 'ACTIVO'
        ];

        if (!empty($_POST['password'])) {
            $data['password'] = $_POST['password'];
        }

        $idRol = (int)($_POST['id_rol'] ?? 0);
        if ($idRol === 3) {
            $data['turno'] = trim($_POST['turno'] ?? 'Mañana');
        } elseif ($idRol === 4) {
            $data['tipo_vehiculo'] = trim($_POST['tipo_vehiculo'] ?? 'Moto');
            $data['placa'] = trim($_POST['placa'] ?? '');
            $data['estado_domiciliario'] = trim($_POST['estado_domiciliario'] ?? 'ACTIVO');
        }

        $res = $adminModel->updateEmpleado($id, $data, $currentAdminId);
        if ($res['success']) {
            $_SESSION['flash_msg'] = $res['message'];
        } else {
            $_SESSION['flash_error'] = $res['message'];
        }
        header('Location: empleados.php');
        exit;

    } elseif ($action === 'reset_password') {
        $id = (int)($_POST['id_usuario'] ?? 0);
        $res = $adminModel->resetPasswordEmpleado($id, $currentAdminId);
        if ($res['success']) {
            $_SESSION['flash_msg'] = $res['message'];
            $_SESSION['flash_temp_password'] = [
                'password' => $res['temp_password'],
                'correo' => $res['empleado_correo']
            ];
        } else {
            $_SESSION['flash_error'] = $res['message'];
        }
        header('Location: empleados.php');
        exit;

    } elseif ($action === 'toggle_estado') {
        $id = (int)($_POST['id_usuario'] ?? 0);
        $res = $adminModel->toggleEstadoEmpleado($id, $currentAdminId);
        if ($res['success']) {
            $_SESSION['flash_msg'] = $res['message'];
        } else {
            $_SESSION['flash_error'] = $res['message'];
        }
        header('Location: empleados.php');
        exit;
    }
}

$empleados = $adminModel->getEmpleados();

$currentPage = 'empleados';
$pageTitle = 'Gestión de Personal';

require_once __DIR__ . '/../../views/layouts/admin_header.php';
require_once __DIR__ . '/../../views/admin/empleados.php';
require_once __DIR__ . '/../../views/layouts/admin_footer.php';
