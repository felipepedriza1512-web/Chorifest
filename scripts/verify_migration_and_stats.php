<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/AdminModel.php';

$db = getDatabaseConnection();
$adminModel = new AdminModel($db);

echo "=== ESTADÍSTICAS DEL DASHBOARD ANTES DE MIGRACIÓN ===\n";
$statsBefore = $adminModel->getDashboardStats();
print_r($statsBefore);

echo "\n--- Ejecutando sql/migration_pago_devolucion.sql ---\n";
$sql = file_get_contents(__DIR__ . '/../sql/migration_pago_devolucion.sql');
$db->exec($sql);
echo "Migración ejecutada con éxito.\n\n";

echo "=== COMPROBACIÓN DE COLUMNA EN TABLA 'pago' ===\n";
$cols = $db->query("DESCRIBE pago")->fetchAll(PDO::FETCH_ASSOC);
$found = false;
foreach ($cols as $c) {
    if ($c['Field'] === 'comprobante_devolucion') {
        $found = true;
        echo "COLUMNA ENCONTRADA: {$c['Field']} | Tipo: {$c['Type']} | Null: {$c['Null']}\n";
    }
}
if (!$found) {
    echo "ERROR: La columna comprobante_devolucion no se encontró.\n";
}

echo "\n=== ESTADÍSTICAS DEL DASHBOARD DESPUÉS DE MIGRACIÓN ===\n";
$statsAfter = $adminModel->getDashboardStats();
print_r($statsAfter);

if ($statsBefore === $statsAfter) {
    echo "\nVERIFICACIÓN CORRECTA: Las estadísticas del dashboard se mantuvieron idénticas.\n";
} else {
    echo "\nADVERTENCIA: Las estadísticas cambiaron después de la migración.\n";
}
