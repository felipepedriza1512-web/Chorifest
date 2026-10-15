<?php

/**
 * Carga las variables de un archivo .env sin dependencias externas.
 * Solo lee líneas KEY=VALOR; ignora comentarios (#) y líneas vacías.
 */
function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value, " \t\"'");
        if ($key !== '' && !array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Cargar .env desde la raíz del proyecto (un nivel arriba de config/)
loadEnv(dirname(__DIR__) . '/.env');

function isDevelopmentEnvironment(): bool
{
    $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? __DIR__;
    $httpHost = $_SERVER['HTTP_HOST'] ?? '';
    $appEnv = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: '';

    // Si el .env dice explícitamente "production", no es desarrollo
    if ($appEnv === 'production') {
        return false;
    }

    return (
        DIRECTORY_SEPARATOR === '\\' ||
        strpos($documentRoot, 'laragon') !== false ||
        strpos($httpHost, 'localhost') !== false ||
        strpos($httpHost, '127.0.0.1') !== false ||
        strpos($httpHost, '192.168.') !== false ||
        strpos($httpHost, '10.') !== false ||
        strpos($httpHost, '.test') !== false ||
        strpos($httpHost, '.local') !== false ||
        php_sapi_name() === 'cli'
    );
}

// Configuración de visualización de errores según entorno
if (isDevelopmentEnvironment()) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(0);
    // En producción, redirigir errores PHP al log configurado en .env
    $phpErrorLog = $_ENV['PHP_ERROR_LOG'] ?? getenv('PHP_ERROR_LOG') ?: '';
    if ($phpErrorLog !== '') {
        ini_set('log_errors', '1');
        ini_set('error_log', $phpErrorLog);
    }
}

function getDatabaseConnection(?string $customDbName = null): PDO
{
    $isLocal = isDevelopmentEnvironment();

    if ($isLocal) {
        // Configuración Local (Laragon) — sin .env requerido
        $host     = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
        $user     = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
        $password = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '';
        $envDb    = $_ENV['DB_NAME'] ?? getenv('CHORIFEST_DB_NAME') ?: getenv('DB_NAME') ?: null;
        $headerDb = ($_SERVER['HTTP_X_CHORIFEST_TEST_DB'] ?? '') === 'chorifest_test' ? 'chorifest_test' : null;
        $dbname   = $customDbName ?: ($envDb ?: ($headerDb ?: 'chorifest'));
        $port     = (int)($_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: 3306);
    } else {
        // Configuración para Producción — TODAS las variables deben estar en .env
        $host     = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '';
        $user     = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: '';
        $password = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '';
        $dbname   = $customDbName ?: ($_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: '');
        $port     = (int)($_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: 3306);

        // Fallo explícito si faltan credenciales (evita mensajes de error con datos internos)
        if ($host === '' || $user === '' || $dbname === '') {
            error_log('[Chorifest] ERROR CRÍTICO: Variables de entorno de base de datos no configuradas. Revisa el archivo .env en el servidor.');
            http_response_code(500);
            die('Error de configuración del servidor. Contacta al administrador.');
        }
    }

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;port=$port;charset=utf8mb4", $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Configurar zona horaria en MySQL (-05:00 para Colombia / America/Bogota)
        try {
            $pdo->exec("SET time_zone = '-05:00';");
        } catch (Exception $tzEx) {
            // Ignorar si el hosting restringe SET time_zone
        }

        return $pdo;
    } catch (PDOException $e) {
        error_log('[Chorifest] Error de conexión PDO: ' . $e->getMessage());
        if (isDevelopmentEnvironment()) {
            die('Error de conexión a la base de datos: ' . $e->getMessage());
        }
        http_response_code(500);
        die('Error de conexión al servidor. Contacta al administrador.');
    }
}

function baseUrl(string $path = ''): string
{
    static $base = null;
    if ($base === null) {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
        $projectDir = str_replace('\\', '/', dirname(__DIR__));

        if (!empty($docRoot) && strpos($projectDir, $docRoot) === 0) {
            $subDir = trim(substr($projectDir, strlen($docRoot)), '/');
            $base = $subDir ? '/' . $subDir : '';
        } else {
            if (preg_match('#^/(Chorifest|chorifest)#i', $scriptName, $m)) {
                $base = '/' . $m[1];
            } else {
                $base = '';
            }
        }
    }
    $path = '/' . ltrim($path, '/');
    return $base . $path;
}

/**
 * ZONA HORARIA Y DESPLIEGUE EN HOSTING:
 * 1. En PHP se establece date_default_timezone_set('America/Bogota').
 * 2. Al desplegar en un hosting (ej. InfinityFree, Hostinger, cPanel/VPS), la zona horaria del servidor
 *    físico puede estar en UTC o en otra región.
 * 3. Se debe verificar que MySQL use el mismo offset que PHP (-05:00). Si las funciones SQL CURDATE(), NOW()
 *    o INTERVAL entregan diferencias de horas, asegúrate de mantener $pdo->exec("SET time_zone = '-05:00';")
 *    o configurar default_time_zone en php.ini y my.cnf del servidor.
 */
date_default_timezone_set('America/Bogota');

if (!headers_sent()) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
}

function startSecureSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        if (!headers_sent()) {
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_samesite', 'Lax');
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
        session_start();
    }
}

function getCsrfToken(): string
{
    startSecureSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): bool
{
    startSecureSession();
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

