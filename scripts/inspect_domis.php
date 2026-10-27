<?php
require_once __DIR__ . '/../config/database.php';
$db = getDatabaseConnection();
$domis = $db->query('SELECT * FROM domiciliario')->fetchAll(PDO::FETCH_ASSOC);
print_r($domis);
