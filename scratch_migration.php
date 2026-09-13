<?php
require_once __DIR__ . '/config/database.php';

try {
    $db = getDatabaseConnection();

    // Create ingrediente table
    $db->exec("
        CREATE TABLE IF NOT EXISTS ingrediente (
            id_ingrediente INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(255) NOT NULL,
            estado ENUM('disponible', 'agotado') DEFAULT 'disponible'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Create producto_ingrediente table
    $db->exec("
        CREATE TABLE IF NOT EXISTS producto_ingrediente (
            id_producto INT NOT NULL,
            id_ingrediente INT NOT NULL,
            PRIMARY KEY (id_producto, id_ingrediente),
            FOREIGN KEY (id_producto) REFERENCES producto(id_producto) ON DELETE CASCADE,
            FOREIGN KEY (id_ingrediente) REFERENCES ingrediente(id_ingrediente) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    echo "Tables created successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
