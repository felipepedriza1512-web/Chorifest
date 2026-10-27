<?php
require_once __DIR__ . '/../config/database.php';

$dbReal = getDatabaseConnection('chorifest');
$dbTest = getDatabaseConnection('chorifest_test');

echo "======================================================================\n";
echo "ESTADO DE AMBAS BASES DE DATOS TRAS EJECUCIÓN DE PRUEBAS:\n";
echo "======================================================================\n";

echo "1. BASE DE DATOS REAL ('chorifest'):\n";
$pedReal = $dbReal->query("SELECT COUNT(*) FROM pedido")->fetchColumn();
echo "  - Total pedidos: $pedReal (intactos)\n";
$stockReal = $dbReal->query("SELECT i.id_insumo, i.nombre, inv.cantidad FROM insumo i JOIN inventario_insumo inv ON i.id_insumo = inv.id_insumo ORDER BY i.id_insumo")->fetchAll(PDO::FETCH_ASSOC);
echo "  - Stock de los 10 insumos en chorifest real:\n";
foreach ($stockReal as $sr) {
    echo "    Insumo #{$sr['id_insumo']} [{$sr['nombre']}]: {$sr['cantidad']}\n";
}

echo "\n2. BASE DE DATOS DE PRUEBAS ('chorifest_test'):\n";
$pedTest = $dbTest->query("SELECT COUNT(*) FROM pedido")->fetchColumn();
echo "  - Total pedidos: $pedTest\n";
$stockTest = $dbTest->query("SELECT i.id_insumo, i.nombre, inv.cantidad FROM insumo i JOIN inventario_insumo inv ON i.id_insumo = inv.id_insumo ORDER BY i.id_insumo")->fetchAll(PDO::FETCH_ASSOC);
echo "  - Stock de los 10 insumos en chorifest_test:\n";
foreach ($stockTest as $st) {
    echo "    Insumo #{$st['id_insumo']} [{$st['nombre']}]: {$st['cantidad']}\n";
}
echo "======================================================================\n";
