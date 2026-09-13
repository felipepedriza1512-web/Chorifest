<?php
require_once __DIR__ . '/../config/database.php';
try {
    $db = getDatabaseConnection();
    $db->exec("ALTER TABLE producto ADD COLUMN stock INT NOT NULL DEFAULT 50 AFTER precio");
    $db->exec("ALTER TABLE producto ADD COLUMN stock_minimo INT NOT NULL DEFAULT 5 AFTER stock");
    echo "Migración completada exitosamente.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
