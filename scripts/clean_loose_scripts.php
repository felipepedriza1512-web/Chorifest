<?php
$filesToRemove = [
    __DIR__ . '/../controllers/auth/fix_pass.php',
    __DIR__ . '/fix_cocina_pass.php',
    __DIR__ . '/scratch.php',
    __DIR__ . '/scratch_asig.php',
    __DIR__ . '/scratch_estados.php',
    __DIR__ . '/scratch_migration.php',
    __DIR__ . '/scratch_recipes.php',
    __DIR__ . '/scratch_sql.php',
    __DIR__ . '/scratch_sql2.php',
    __DIR__ . '/scratch_tables.php',
    __DIR__ . '/public_scratch.php'
];

echo "=== ELIMINACIÓN DE SCRIPTS SUELTOS Y TEMPORALES ===\n";
foreach ($filesToRemove as $f) {
    $rel = str_replace(realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR, '', $f);
    if (file_exists($f)) {
        unlink($f);
        echo "✔ ELIMINADO: $rel\n";
    } else {
        echo "- YA NO EXISTE: $rel\n";
    }
}
