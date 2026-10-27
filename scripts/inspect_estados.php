<?php
require_once __DIR__ . '/../config/database.php';
$db = getDatabaseConnection();
$rows = $db->query("SELECT * FROM estado_pedido")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    echo "ID: {$row['id_estado']} | Nombre: '{$row['nombre']}' | Desc: '{$row['descripcion']}'\n";
}
