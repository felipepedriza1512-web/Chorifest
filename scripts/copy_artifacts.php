<?php
$destDir = 'C:/Users/felip/.gemini/antigravity-ide/brain/b083ef4a-b6bc-488b-86c2-daa6812cebf5';
$srcDir = __DIR__ . '/../docs/screenshots';

$files = [
    'fase4_admin_zonas_desktop.png',
    'fase4_admin_zonas_mobile.png',
    'fase4_admin_empleados_desktop.png',
    'fase4_admin_empleados_mobile.png',
    'fase4_cliente_checkout_desktop.png',
    'fase4_cliente_checkout_mobile.png',
    'evidencia_1_sin_mapa_en_preparacion.png',
    'evidencia_2_en_camino_posicion_1.png',
    'evidencia_3_en_camino_posicion_2.png',
    'evidencia_4_senal_perdida_aviso.png',
    'evidencia_5_sin_coordenadas_no_rompe.png',
    'evidencia_6_entregado_mapa_desaparece.png',
    'evidencia_7_cancelado_mapa_desaparece.png',
    '06_dashboard_celular.png',
    '02_tabla_pedidos_celular.png',
    '07_productos_celular.png',
    '09_zonas_celular.png',
    '10_empleados_celular.png',
    '01_tablero_cocina_celular.png'
];

foreach ($files as $f) {
    $src = "$srcDir/$f";
    $dst = "$destDir/$f";
    if (file_exists($src)) {
        copy($src, $dst);
        echo "Copiado a artefactos: $f\n";
    } else {
        echo "No encontrado: $f\n";
    }
}
