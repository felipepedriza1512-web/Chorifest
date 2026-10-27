<?php
/**
 * Inicializador de la Base de Datos de Pruebas: chorifest_test
 */
require_once __DIR__ . '/../config/database.php';

echo "======================================================================\n";
echo "INICIALIZANDO BASE DE DATOS DE PRUEBAS: chorifest_test\n";
echo "======================================================================\n";

// Conectar al servidor MySQL sin seleccionar base de datos inicialmente
$pdo = new PDO("mysql:host=localhost;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 1. Crear base de datos chorifest_test si no existe
echo "1. Creando base de datos 'chorifest_test'...\n";
$pdo->exec("CREATE DATABASE IF NOT EXISTS `chorifest_test` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `chorifest_test`");
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

// 2. Obtener lista de tablas de chorifest original para clonar estructura y catálogos
echo "2. Clonando estructura de tablas desde 'chorifest'...\n";
$dbMain = getDatabaseConnection('chorifest');
$tables = $dbMain->query("SHOW FULL TABLES WHERE Table_Type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM);

foreach ($tables as $tRow) {
    $tableName = $tRow[0];
    // Dropear en test
    $pdo->exec("DROP TABLE IF EXISTS `$tableName`");
    // Crear como en chorifest
    $createSql = $dbMain->query("SHOW CREATE TABLE `chorifest`.`$tableName`")->fetch(PDO::FETCH_NUM)[1];
    $pdo->exec($createSql);
    echo "  ✔ Tabla `$tableName` creada en chorifest_test.\n";
}

// 3. Copiar datos esenciales (roles, usuarios, clientes, cocineras, domiciliarios, administradores, categorias, productos, insumos, inventario, recetas, zonas, estados)
echo "\n3. Copiando datos iniciales de catálogo, recetas y los 10 insumos...\n";
$essentialTables = [
    'rol',
    'usuario',
    'cliente',
    'cocinera',
    'domiciliario',
    'administrador',
    'estado_pedido',
    'zona_envio',
    'direccion',
    'categoria',
    'producto',
    'insumo',
    'inventario_insumo',
    'inventario_producto',
    'receta',
    'receta_detalle'
];

foreach ($essentialTables as $table) {
    $pdo->exec("TRUNCATE TABLE `$table`");
    $pdo->exec("INSERT INTO `chorifest_test`.`$table` SELECT * FROM `chorifest`.`$table`");
    $cnt = $pdo->query("SELECT COUNT(*) FROM `chorifest_test`.`$table`")->fetchColumn();
    echo "  ✔ `$table`: $cnt registros copiados a chorifest_test.\n";
}

// Vaciar tablas transaccionales en chorifest_test para que quede limpia para tests
$transientTables = ['pedido', 'detalle_pedido', 'pago', 'historial_pedido', 'novedad_entrega', 'asignacion_cocina', 'asignacion_domicilio', 'movimiento_inventario', 'notificacion', 'calificacion', 'ubicacion_domiciliario'];
foreach ($transientTables as $t) {
    $pdo->exec("TRUNCATE TABLE `$t`");
}

$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

echo "\n✔ BASE DE DATOS chorifest_test CONFIGURADA Y LISTA.\n";
echo "======================================================================\n";
