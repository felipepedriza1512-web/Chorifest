<?php
require_once __DIR__ . '/config/database.php';
try {
    $db = getDatabaseConnection();
    $stmt = $db->query('DESCRIBE producto');
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo $e->getMessage();
}
