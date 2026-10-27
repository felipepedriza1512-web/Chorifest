<?php
require_once __DIR__ . '/../config/database.php';
$db = getDatabaseConnection();

echo "=== ESCANEO PRECISO DE CARACTERES CORRUPTOS EN TODA LA BASE DE DATOS ===\n\n";

$tables = $db->query("SHOW FULL TABLES WHERE Table_Type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM);
$corruptedList = [];

// Patrones de doble codificación UTF-8 / Mojibake comunes
$searchChars = ['â', 'Ã', '├', '┼', 'Ô', '', 'Â', '€'];

foreach ($tables as $tRow) {
    $tableName = $tRow[0];
    
    $cols = $db->query("SHOW COLUMNS FROM `$tableName`")->fetchAll(PDO::FETCH_ASSOC);
    $textCols = [];
    $primaryKey = 'id_' . $tableName;
    
    foreach ($cols as $c) {
        $type = strtolower($c['Type']);
        if ($c['Key'] === 'PRI') {
            $primaryKey = $c['Field'];
        }
        if (strpos($type, 'char') !== false || strpos($type, 'text') !== false) {
            $textCols[] = $c['Field'];
        }
    }
    
    if (empty($textCols)) continue;
    
    foreach ($textCols as $col) {
        $whereClauses = [];
        $params = [];
        foreach ($searchChars as $idx => $char) {
            $paramName = ":p_{$idx}";
            $whereClauses[] = "`$col` LIKE $paramName";
            $params[$paramName] = "%$char%";
        }
        $sql = "SELECT `$primaryKey` as pk, `$col` as val FROM `$tableName` WHERE " . implode(' OR ', $whereClauses);
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($matches)) {
                foreach ($matches as $m) {
                    $val = $m['val'];
                    // Exclude legitimate accents if any
                    // Check if it really contains mojibake
                    if (preg_match('/[âÃ├┼ÔÂ€]/u', $val) || preg_match('/Ã[¡©­³º±]/', $val) || strpos($val, 'â') !== false || strpos($val, '├') !== false) {
                        $corruptedList[] = [
                            'tabla' => $tableName,
                            'columna' => $col,
                            'pk_col' => $primaryKey,
                            'pk_val' => $m['pk'],
                            'valor' => $val
                        ];
                    }
                }
            }
        } catch (Exception $e) {}
    }
}

echo "TOTAL DE REGISTROS CON TEXTO CORRUPTO O MOJIBAKE ENCONTRADOS: " . count($corruptedList) . "\n\n";
printf("%-20s | %-20s | %-8s | %s\n", "Tabla", "Columna", "ID / PK", "Texto Dañado");
echo str_repeat('-', 100) . "\n";
foreach ($corruptedList as $item) {
    printf("%-20s | %-20s | %-8s | %s\n", 
        $item['tabla'], 
        $item['columna'], 
        $item['pk_val'], 
        $item['valor']
    );
}
