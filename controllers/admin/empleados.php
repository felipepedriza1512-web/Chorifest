<?php
session_start();
require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkRole(2); // Only admin can access
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/AdminModel.php';

$db = getDatabaseConnection();
$adminModel = new AdminModel($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create' || $action === 'update') {
        $data = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'correo' => trim($_POST['correo'] ?? ''),
            'telefono' => trim($_POST['telefono'] ?? ''),
            'id_rol' => (int)($_POST['id_rol'] ?? 0),
            'estado' => $_POST['estado'] ?? 'ACTIVO'
        ];
        
        if (!empty($_POST['password'])) {
            $data['password'] = $_POST['password'];
        }
        
        // Specific role data
        if ($data['id_rol'] === 3) {
            $data['turno'] = trim($_POST['turno'] ?? '');
        } elseif ($data['id_rol'] === 4) {
            $data['tipo_vehiculo'] = trim($_POST['tipo_vehiculo'] ?? '');
            $data['placa'] = trim($_POST['placa'] ?? '');
            $data['estado_domiciliario'] = trim($_POST['estado_domiciliario'] ?? 'ACTIVO');
        }

        if ($action === 'create') {
            $adminModel->createEmpleado($data);
        } else {
            $id = (int)$_POST['id_usuario'];
            $adminModel->updateEmpleado($id, $data);
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
