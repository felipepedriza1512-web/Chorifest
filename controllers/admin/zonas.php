<?php
session_start();
require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkRole(2);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/AdminModel.php';

$db = getDatabaseConnection();
$adminModel = new AdminModel($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'create') {
        $data = [
            'nombre' => $_POST['nombre'] ?? '',
            'costo_domicilio' => (float)($_POST['costo_domicilio'] ?? 0),
            'tiempo_estimado_min' => (int)($_POST['tiempo_estimado_min'] ?? 30)
        ];
        if (!empty($data['nombre'])) {
            $adminModel->createZona($data);
        }
    } elseif ($action === 'update') {
        $id = (int)$_POST['id_zona'];
        $data = [
            'nombre' => $_POST['nombre'] ?? '',
            'costo_domicilio' => (float)($_POST['costo_domicilio'] ?? 0),
            'tiempo_estimado_min' => (int)($_POST['tiempo_estimado_min'] ?? 30),
            'estado' => $_POST['estado'] ?? 'ACTIVA'
        ];
        if ($id > 0 && !empty($data['nombre'])) {
            $adminModel->updateZona($id, $data);
        }
    }
    
    header('Location: zonas.php');
    exit;
}

$zonas = $adminModel->getZonasEnvio();

$currentPage = 'zonas';
$pageTitle = 'Zonas de Envío';

require_once __DIR__ . '/../../views/layouts/admin_header.php';
require_once __DIR__ . '/../../views/admin/zonas.php';
require_once __DIR__ . '/../../views/layouts/admin_footer.php';
