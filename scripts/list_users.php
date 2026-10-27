<?php
require_once __DIR__ . '/../config/database.php';
$db = getDatabaseConnection();
$users = $db->query('SELECT id_usuario, correo, nombre, id_rol, estado, debe_cambiar_password FROM usuario')->fetchAll(PDO::FETCH_ASSOC);
foreach ($users as $u) {
    echo "ID: {$u['id_usuario']} | Email: '{$u['correo']}' | Rol: {$u['id_rol']} | Estado: {$u['estado']} | DebeCambiar: {$u['debe_cambiar_password']}\n";
}
