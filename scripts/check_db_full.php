<?php
require_once __DIR__ . '/../config/database.php';
$db = getDatabaseConnection();

echo "=== CATEGORIAS ===\n";
$stmt = $db->query('SELECT * FROM categoria');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "=== PRODUCTOS ===\n";
$stmt = $db->query('SELECT * FROM producto');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
