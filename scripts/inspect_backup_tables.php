<?php
$raw = file_get_contents(__DIR__ . '/../storage/backups/backup_chorifest_fase3_previa.sql');
if (substr($raw, 0, 2) === "\xFF\xFE" || strpos($raw, "\x00") !== false) {
    $sql = mb_convert_encoding($raw, 'UTF-8', 'UTF-16LE');
} else {
    $sql = $raw;
}

preg_match_all('/INSERT INTO `(\w+)`/i', $sql, $matches);
echo "Tablas con INSERT en el backup:\n";
print_r(array_count_values($matches[1]));

$lines = explode("\n", $sql);
foreach ($lines as $idx => $line) {
    if (strpos($line, '`pedido`') !== false || strpos($line, '`movimiento_inventario`') !== false) {
        if (strpos($line, 'INSERT') !== false || strpos($line, 'Dumping') !== false) {
            echo "Line " . ($idx + 1) . ": " . substr($line, 0, 100) . "...\n";
            for ($k = 1; $k <= 3; $k++) {
                if (isset($lines[$idx + $k])) {
                    echo "  + Line " . ($idx + 1 + $k) . ": " . substr($lines[$idx + $k], 0, 100) . "...\n";
                }
            }
        }
    }
}
