<?php
require_once __DIR__ . '/../config/database.php';
$db = getDatabaseConnection();
$stmt = $db->query('SELECT id_producto, nombre_producto, descripcion FROM producto');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "ID: {$row['id_producto']} | Nombre: {$row['nombre_producto']} | Desc: {$row['descripcion']}\n";
}
