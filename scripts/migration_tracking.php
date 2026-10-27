<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = getDatabaseConnection();
    echo "Conectado a la base de datos...\n";

    $db->exec("SET FOREIGN_KEY_CHECKS=0");

    // 1. Modificar tabla direccion para latitud y longitud
    try {
        $db->exec("ALTER TABLE direccion ADD COLUMN latitud DECIMAL(10, 8) NULL AFTER referencia");
        echo "Columna latitud agregada a direccion.\n";
    } catch (Exception $e) {}
    try {
        $db->exec("ALTER TABLE direccion ADD COLUMN longitud DECIMAL(11, 8) NULL AFTER latitud");
        echo "Columna longitud agregada a direccion.\n";
    } catch (Exception $e) {}

    // 2. Crear tabla ubicacion_domiciliario
    $db->exec("CREATE TABLE IF NOT EXISTS ubicacion_domiciliario (
        id_ubicacion INT AUTO_INCREMENT PRIMARY KEY,
        id_pedido INT NOT NULL,
        id_domiciliario INT NOT NULL,
        latitud DECIMAL(10,8) NOT NULL,
        longitud DECIMAL(11,8) NOT NULL,
        heading FLOAT NULL,
        velocidad FLOAT NULL,
        ultima_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_ubic_pedido (id_pedido),
        INDEX idx_ubic_domi (id_domiciliario)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 3. Crear tabla calificacion
    $db->exec("CREATE TABLE IF NOT EXISTS calificacion (
        id_calificacion INT AUTO_INCREMENT PRIMARY KEY,
        id_pedido INT NOT NULL,
        id_cliente INT NOT NULL,
        puntuacion INT NOT NULL,
        comentario TEXT NULL,
        fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_calif_pedido (id_pedido)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 4. Crear tabla notificacion
    $db->exec("CREATE TABLE IF NOT EXISTS notificacion (
        id_notificacion INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario INT NOT NULL,
        titulo VARCHAR(150) NOT NULL,
        mensaje TEXT NOT NULL,
        leido TINYINT(1) DEFAULT 0,
        fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_notif_user (id_usuario)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 5. Normalizar estado_pedido
    $estados = [
        1 => ['Recibido', 'Pedido recibido en el sistema'],
        2 => ['Aceptado', 'Pedido aceptado por el restaurante'],
        3 => ['En preparación', 'Pedido en preparación en cocina'],
        4 => ['Empacado', 'Pedido empacado y listo para despacho'],
        5 => ['En camino', 'Pedido en camino con el domiciliario'],
        6 => ['Entregado', 'Pedido entregado al cliente'],
        7 => ['Cancelado', 'Pedido cancelado'],
        8 => ['Pendiente de verificación', 'Transferencia pendiente de verificación de pago'],
        10 => ['Rechazado', 'Pedido rechazado por el restaurante']
    ];

    foreach ($estados as $id => $data) {
        $stmt = $db->prepare("UPDATE estado_pedido SET nombre = ?, descripcion = ? WHERE id_estado = ?");
        $stmt->execute([$data[0], $data[1], $id]);
        if ($stmt->rowCount() === 0) {
            $stmtIns = $db->prepare("INSERT IGNORE INTO estado_pedido (id_estado, nombre, descripcion) VALUES (?, ?, ?)");
            $stmtIns->execute([$id, $data[0], $data[1]]);
        }
    }
    echo "Estados de pedido normalizados.\n";

    // 6. Asegurar usuarios demo
    $passHash = password_hash('123456', PASSWORD_DEFAULT);

    $usuariosDemo = [
        ['correo' => 'cliente@chorifest.com', 'nombre' => 'Cliente Demo', 'rol' => 1, 'tel' => '3001112233'],
        ['correo' => 'juan@gmail.com', 'nombre' => 'Juan Pérez (Cliente)', 'rol' => 1, 'tel' => '3115679856'],
        ['correo' => 'admin@chorifest.com', 'nombre' => 'Administrador', 'rol' => 2, 'tel' => '3002223344'],
        ['correo' => 'admin@gmail.com', 'nombre' => 'Administrador Demo', 'rol' => 2, 'tel' => '3228460174'],
        ['correo' => 'cocina@chorifest.com', 'nombre' => 'Cocinera Demo', 'rol' => 3, 'tel' => '3003334455'],
        ['correo' => 'cocina@gmail.com', 'nombre' => 'Cocinera Principal', 'rol' => 3, 'tel' => '3456782345'],
        ['correo' => 'domi@chorifest.com', 'nombre' => 'Domiciliario Demo', 'rol' => 4, 'tel' => '3004445566'],
        ['correo' => 'domi@gmail.com', 'nombre' => 'Domiciliario Express', 'rol' => 4, 'tel' => '3216730989']
    ];

    foreach ($usuariosDemo as $u) {
        $stmtCheck = $db->prepare("SELECT id_usuario FROM usuario WHERE correo = ?");
        $stmtCheck->execute([$u['correo']]);
        $existingId = $stmtCheck->fetchColumn();

        if ($existingId) {
            $stmtUpd = $db->prepare("UPDATE usuario SET password_hash = ?, nombre = ?, id_rol = ? WHERE id_usuario = ?");
            $stmtUpd->execute([$passHash, $u['nombre'], $u['rol'], $existingId]);
            $userId = (int)$existingId;
        } else {
            $stmtIns = $db->prepare("INSERT INTO usuario (nombre, correo, password_hash, telefono, id_rol, estado) VALUES (?, ?, ?, ?, ?, 'ACTIVO')");
            $stmtIns->execute([$u['nombre'], $u['correo'], $passHash, $u['tel'], $u['rol']]);
            $userId = (int)$db->lastInsertId();
        }

        if ($u['rol'] == 1) {
            $db->exec("INSERT IGNORE INTO cliente (id_usuario) VALUES ($userId)");
        } elseif ($u['rol'] == 3) {
            $db->exec("INSERT IGNORE INTO cocinera (id_usuario) VALUES ($userId)");
        } elseif ($u['rol'] == 4) {
            $db->exec("INSERT IGNORE INTO domiciliario (id_usuario) VALUES ($userId)");
        } elseif ($u['rol'] == 2) {
            $db->exec("INSERT IGNORE INTO administrador (id_usuario) VALUES ($userId)");
        }
    }

    $db->exec("SET FOREIGN_KEY_CHECKS=1");
    echo "Usuarios demo configurados exitosamente con contraseña '123456'.\n";
    echo "Migración completada con éxito.\n";
} catch (Exception $e) {
    echo "Error en migración: " . $e->getMessage() . "\n";
}
