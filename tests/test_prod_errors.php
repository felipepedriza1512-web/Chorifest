<?php
// Simular entorno de producción
function testProductionSettings() {
    $code = '<?php
    $_SERVER["DOCUMENT_ROOT"] = "/home/vol15_1/epizy.com/epiz_12345678/htdocs";
    $_SERVER["HTTP_HOST"] = "chorifest.infinityfreeapp.com";
    define("PHP_OS_FAMILY_OVERRIDE", "Linux");
    require "config/database.php";
    echo "display_errors=" . ini_get("display_errors");
    ';
    
    // Ejecutar en proceso PHP independiente con simulación
    $file = __DIR__ . '/temp_check_prod.php';
    file_put_contents($file, '<?php
    // Simular servidor linux de producción
    function isDevSim() {
        $doc = "/home/production/htdocs";
        $host = "chorifest.com";
        return (
            strpos($doc, "laragon") !== false ||
            strpos($host, "localhost") !== false
        );
    }
    if (isDevSim()) {
        ini_set("display_errors", "1");
    } else {
        ini_set("display_errors", "0");
    }
    echo "display_errors=" . ini_get("display_errors");
    ');
    
    $out = shell_exec('php ' . escapeshellarg($file));
    @unlink($file);
    return $out;
}

echo "Resultado simulación producción: " . testProductionSettings() . "\n";
