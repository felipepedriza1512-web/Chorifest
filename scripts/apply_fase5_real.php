<?php
/**
 * Aplica las migraciones de la Fase 5 a la base de datos real chorifest
 */

require_once __DIR__ . '/../config/database.php';

$db = getDatabaseConnection('chorifest');

$files = [
    __DIR__ . '/../sql/migration_fase5_reportes.sql',
    __DIR__ . '/../sql/migration_fase5_liquidacion.sql'
];

echo "Aplicando migraciones a base real 'chorifest'...\n";

foreach ($files as $file) {
    echo "Ejecutando: " . basename($file) . "\n";
    $sql = file_get_contents($file);
    $sqlClean = preg_replace('/--.*$/m', '', $sql);
    $queries = array_filter(array_map('trim', explode(';', $sqlClean)));
    foreach ($queries as $q) {
        if ($q === '') continue;
        $db->exec($q);
    }
    echo "  -> OK: " . basename($file) . " aplicado sin errores.\n";
}

echo ">> TODAS LAS MIGRACIONES DE LA FASE 5 APLICADAS CON ÉXITO EN CHORIFEST <<\n";
