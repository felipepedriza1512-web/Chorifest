<?php
require_once __DIR__ . '/../config/database.php';
$db = getDatabaseConnection();
$cols = $db->query("DESCRIBE inventario_insumo")->fetchAll(PDO::FETCH_ASSOC);
print_r($cols);
