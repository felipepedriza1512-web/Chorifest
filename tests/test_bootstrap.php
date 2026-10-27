<?php
/**
 * Bootstrap de seguridad para tests automatizados.
 * Asegura que todas las pruebas se ejecuten exclusivamente en chorifest_test
 * y rechaza terminantemente cualquier intento de ejecución sobre la base de datos real.
 */

// Si no está seteada explícitamente en el entorno, apuntamos por defecto a chorifest_test
if (empty(getenv('CHORIFEST_DB_NAME'))) {
    putenv('CHORIFEST_DB_NAME=chorifest_test');
    putenv('DB_NAME=chorifest_test');
    $_ENV['CHORIFEST_DB_NAME'] = 'chorifest_test';
    $_ENV['DB_NAME'] = 'chorifest_test';
}

function assertTestDatabaseOnly(PDO $db): void {
    $currentDb = $db->query("SELECT DATABASE()")->fetchColumn();
    if (strtolower($currentDb) === 'chorifest' || strpos(strtolower($currentDb), '_test') === false) {
        echo "\n" . str_repeat('!', 80) . "\n";
        echo "🚨 ERROR DE SEGURIDAD CRÍTICO:\n";
        echo "Los tests tienen TERMINANTEMENTE PROHIBIDO ejecutarse sobre la base de datos real ('$currentDb').\n";
        echo "Deben apuntar y aislarse en 'chorifest_test'.\n";
        echo "Abortando ejecución inmediatamente para proteger los datos de producción.\n";
        echo str_repeat('!', 80) . "\n\n";
        exit(1);
    }
}
