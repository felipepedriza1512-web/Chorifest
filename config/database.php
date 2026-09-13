<?php

function getDatabaseConnection(): PDO
{
    // Detección de entorno (Local vs Producción)
    $isLocal = true;
    if (isset($_SERVER['HTTP_HOST'])) {
        $hostName = $_SERVER['HTTP_HOST'];
        if (strpos($hostName, 'infinityfree') !== false || strpos($hostName, 'chorifest') !== false && strpos($hostName, '.local') === false && strpos($hostName, '.test') === false && strpos($hostName, 'localhost') === false && strpos($hostName, '127.0.0.1') === false) {
            // Entorno de producción si no es un host local
            $isLocal = false;
        }
    }

    if ($isLocal) {
        // Configuración Local (Laragon)
        $host = '127.0.0.1';
        $user = 'root';
        $password = ''; 
        $dbname = 'chorifest';
        $port = 3306;
    } else {
        // Configuración para Producción (InfinityFree)
        $host = 'sql204.infinityfree.com'; 
        $user = 'if0_42809912';
        $password = 'v0OlAT7F2Wfj2'; 
        $dbname = 'if0_42809912_chorifest';
        $port = 3306;
    }

    try {
        // Nos conectamos a la base de datos
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;port=$port;charset=utf8mb4", $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $pdo;
    } catch (PDOException $e) {
        die('Error de conexión a la base de datos: ' . $e->getMessage());
    }
}
