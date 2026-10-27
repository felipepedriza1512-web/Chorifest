<?php
require_once __DIR__ . '/../config/database.php';
foreach (['chorifest', 'chorifest_test'] as $dbn) {
    echo "=== BASE DE DATOS: $dbn ===\n";
    $db = getDatabaseConnection($dbn);
    $stmt = $db->query('
        SELECT p.id_pedido, p.id_cliente, u.nombre as cliente, p.id_estado, e.nombre as estado, p.total, p.fecha 
        FROM pedido p 
        LEFT JOIN usuario u ON p.id_cliente = u.id_usuario 
        LEFT JOIN estado_pedido e ON p.id_estado = e.id_estado 
        WHERE p.id_pedido IN (54, 56, 57, 58)
        ORDER BY p.id_pedido ASC
    ');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        echo "No se encontraron los pedidos 54, 56, 57, 58 en $dbn.\n";
    }
    foreach ($rows as $r) {
        echo "Pedido #{$r['id_pedido']} | Cliente: {$r['cliente']} (ID: {$r['id_cliente']}) | Estado: {$r['estado']} (ID: {$r['id_estado']}) | Total: \${$r['total']} | Fecha: {$r['fecha']}\n";
    }
    echo "\n";
}
