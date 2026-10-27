<?php
$lines = file(__DIR__ . '/../views/admin/pedidos.php');
for ($i = 255; $i <= 265; $i++) {
    echo ($i + 1) . ': ' . json_encode($lines[$i]) . "\n";
}
