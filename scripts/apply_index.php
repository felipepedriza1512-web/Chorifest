<?php
require_once __DIR__ . '/../config/database.php';
foreach (['chorifest', 'chorifest_test'] as $dbname) {
    try {
        $db = getDatabaseConnection($dbname);
        $db->exec('CREATE INDEX idx_hist_estado_pedido_fecha ON historial_pedido (id_estado, id_pedido, fecha)');
        echo "Index idx_hist_estado_pedido_fecha created on $dbname\n";
    } catch (Exception $e) {
        echo "Note on $dbname: " . $e->getMessage() . "\n";
    }
}
