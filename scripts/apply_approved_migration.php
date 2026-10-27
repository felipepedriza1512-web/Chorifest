<?php
require_once __DIR__ . '/../config/database.php';

$db = getDatabaseConnection();

echo "1. Ejecutando rollback previo para limpiar columnas antiguas...\n";
try {
    $db->exec("ALTER TABLE pago DROP COLUMN comprobante_devolucion");
    echo "  - Columna 'comprobante_devolucion' eliminada.\n";
} catch (Exception $e) {
    echo "  - 'comprobante_devolucion' no existía o ya fue eliminada: " . $e->getMessage() . "\n";
}

try {
    $db->exec("ALTER TABLE pago DROP COLUMN pendiente_devolucion");
    echo "  - Columna 'pendiente_devolucion' limpiada previamente.\n";
} catch (Exception $e) {
    // ignorar
}

echo "2. Aplicando migración aprobada (sql/migration_pago_devolucion.sql)...\n";
$sql = file_get_contents(__DIR__ . '/../sql/migration_pago_devolucion.sql');
$db->exec($sql);
echo "  - Migración aprobada ejecutada con éxito.\n\n";

echo "3. Estructura final de la tabla 'pago':\n";
$cols = $db->query("DESCRIBE pago")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo "  {$c['Field']} | {$c['Type']} | Null: {$c['Null']} | Default: {$c['Default']}\n";
}
