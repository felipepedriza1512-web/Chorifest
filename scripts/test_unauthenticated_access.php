<?php
$baseUrl = 'http://localhost/Chorifest';

$pages = [
    // FASE 1 & FASE 2
    'Admin Dashboard' => ['url' => "$baseUrl/controllers/admin/dashboard.php", 'expected' => 403],
    'Admin Pedidos' => ['url' => "$baseUrl/controllers/admin/pedidos.php", 'expected' => 403],
    'Admin Empleados' => ['url' => "$baseUrl/controllers/admin/empleados.php", 'expected' => 403],
    'Admin Zonas' => ['url' => "$baseUrl/controllers/admin/zonas.php", 'expected' => 403],
    'Admin Ver Comprobante' => ['url' => "$baseUrl/controllers/admin/ver_comprobante.php", 'expected' => 403],
    'Admin Factura' => ['url' => "$baseUrl/controllers/admin/factura.php", 'expected' => 403],
    'Cocinera Pedidos' => ['url' => "$baseUrl/controllers/cocinera/pedidos.php", 'expected' => 403],
    'Domiciliario Rutas' => ['url' => "$baseUrl/controllers/domiciliario/rutas.php", 'expected' => 403],
    'Cliente Checkout' => ['url' => "$baseUrl/controllers/cliente/checkout.php", 'expected' => 302],
    'Cliente Mis Pedidos' => ['url' => "$baseUrl/controllers/cliente/pedidos.php", 'expected' => 302],
    
    // FASE 3
    'Admin Productos' => ['url' => "$baseUrl/controllers/admin/productos.php", 'expected' => 403],
    'Admin Ingredientes' => ['url' => "$baseUrl/controllers/admin/ingredientes.php", 'expected' => 403],
    'Cliente Catálogo' => ['url' => "$baseUrl/controllers/cliente/catalogo.php", 'expected' => 200],
    'Cliente Carrito' => ['url' => "$baseUrl/controllers/cliente/carrito.php", 'expected' => 200]
];

echo "======================================================================\n";
echo "PRUEBAS DE ACCESO SIN SESIÓN (ANÓNIMO) EN FASES 1, 2 Y 3\n";
echo "======================================================================\n\n";

$allPassed = true;
foreach ($pages as $title => $cfg) {
    $ch = curl_init($cfg['url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $ok = ($code === $cfg['expected']);
    if (!$ok) $allPassed = false;
    $statusText = $ok ? "✔ [PASÓ]" : "❌ [FALLÓ - Esperado {$cfg['expected']}]";
    $actionDesc = ($code === 403) ? "Bloqueado con 403" : (($code === 302) ? "Redirige a Login (302)" : "Público (200)");
    echo str_pad($title, 25) . " -> HTTP $code ($actionDesc) $statusText\n";
}

echo "\n======================================================================\n";
echo $allPassed ? "✔ TODAS LAS PÁGINAS PROTEGIDAS BLOQUEAN O REDIRIGEN CORRECTAMENTE SIN SESIÓN.\n" : "❌ HUBO FALLOS EN LA PROTECCIÓN DE ACCESO.\n";
echo "======================================================================\n";
