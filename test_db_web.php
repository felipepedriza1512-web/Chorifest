<?php
require_once __DIR__ . '/config/database.php';
$db = getDatabaseConnection();

echo "ESTADOS:\n";
$stmt = $db->query('SELECT * FROM estado_pedido');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\nPEDIDOS ESTRUCTURA:\n";
$stmt = $db->query('DESCRIBE pedido');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
