<?php
/**
 * Controlador de Reportes y Liquidación - Fase 5
 * Solo Administrador (id_rol = 2)
 * Zona horaria: America/Bogota
 */

require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkRole(2);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/ReporteModel.php';
require_once __DIR__ . '/../../models/AdminModel.php';

date_default_timezone_set('America/Bogota');

$db = getDatabaseConnection();
$reporteModel = new ReporteModel($db);
$adminModel = new AdminModel($db);

$currentUser = $_SESSION['user'] ?? null;
$adminId = (int)($currentUser['id'] ?? $currentUser['id_usuario'] ?? 0);

// Helper de saneamiento para exportación CSV contra inyección de fórmulas (CSV Injection / Formula Injection)
function sanitizeCsvValue($val): string {
    $str = (string)$val;
    if (preg_match('/^[=\+\-@\t\r]/', $str)) {
        return "'" . $str;
    }
    return $str;
}

// Helper de registro de auditoría en base de datos
function registrarAuditoriaReporte(PDO $db, int $idUsuario, string $accion, string $entidad, string $descripcion): void {
    try {
        $stmt = $db->prepare("
            INSERT INTO auditoria (id_usuario, accion, entidad, id_registro, descripcion, fecha) 
            VALUES (?, ?, ?, NULL, ?, NOW())
        ");
        $stmt->execute([$idUsuario, $accion, $entidad, $descripcion]);
    } catch (Exception $e) {
        error_log("Error registrando auditoría de reporte: " . $e->getMessage());
    }
}

// -----------------------------------------------------------------------------
// PROCESAMIENTO DE ACCIONES POST (FILTROS, EXPORTACIÓN, LIQUIDACIÓN Y ANULACIÓN) CON CSRF ESTRICTO
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    // Cualquier POST sin token CSRF válido o ausente responde estrictamente HTTP 403 Forbidden
    if (!verifyCsrfToken($csrfToken)) {
        http_response_code(403);
        echo "403 Forbidden: Token de seguridad CSRF inválido o ausente.";
        exit;
    }

    if ($postAction === 'filtrar') {
        $tabRedirect = $_POST['tab'] ?? 'ventas';
        $dInicio = $_POST['desde'] ?? date('Y-m-d', strtotime('-29 days'));
        $dFin = $_POST['hasta'] ?? date('Y-m-d');
        $puntaje = $_POST['puntaje'] ?? '';
        $redirectUrl = "reportes.php?tab=" . urlencode($tabRedirect) . "&desde=" . urlencode($dInicio) . "&hasta=" . urlencode($dFin);
        if ($puntaje !== '') $redirectUrl .= "&puntaje=" . urlencode($puntaje);
        header("Location: $redirectUrl");
        exit;
    }

    if ($postAction === 'exportar') {
        $tipoExp = $_POST['tipo'] ?? 'ventas';
        $fmtExp = $_POST['formato'] ?? 'csv';
        $dInicio = $_POST['desde'] ?? date('Y-m-d', strtotime('-29 days'));
        $dFin = $_POST['hasta'] ?? date('Y-m-d');
        $puntaje = $_POST['puntaje'] ?? '';
        $redirectUrl = "reportes.php?tab=" . urlencode($tipoExp) . "&export=" . urlencode($fmtExp) . "&tipo=" . urlencode($tipoExp) . "&desde=" . urlencode($dInicio) . "&hasta=" . urlencode($dFin);
        if ($puntaje !== '') $redirectUrl .= "&puntaje=" . urlencode($puntaje);
        header("Location: $redirectUrl");
        exit;
    }

    if ($postAction === 'liquidar_turno') {
        $idDomi = (int)($_POST['id_domiciliario'] ?? 0);
        $fechaCorte = trim($_POST['fecha_corte'] ?? date('Y-m-d H:i:s'));
        $observaciones = trim($_POST['observaciones'] ?? '');

        if ($idDomi <= 0) {
            $_SESSION['flash_error'] = 'Debes seleccionar un domiciliario válido.';
        } else {
            $resLiq = $reporteModel->liquidarTurno($idDomi, $adminId, $fechaCorte, $observaciones ?: null);
            if ($resLiq['success']) {
                registrarAuditoriaReporte(
                    $db,
                    $adminId,
                    'LIQUIDAR_TURNO',
                    'turno_liquidacion',
                    "Turno #{$resLiq['id_liquidacion']} liquidado para domiciliario #{$idDomi} por total $" . number_format($resLiq['total_efectivo'], 2)
                );
                $_SESSION['flash_success'] = "✓ Turno liquidado exitosamente (#{$resLiq['id_liquidacion']}). Total efectivo recibido: $" . number_format($resLiq['total_efectivo'], 2) . " ({$resLiq['total_pedidos']} pedidos).";
            } else {
                $_SESSION['flash_error'] = $resLiq['message'];
            }
        }
        header('Location: reportes.php?tab=domiciliarios');
        exit;
    }

    if ($postAction === 'anular_liquidacion') {
        $idLiq = (int)($_POST['id_liquidacion'] ?? 0);
        $motivo = trim($_POST['motivo_anulacion'] ?? '');

        if ($idLiq <= 0 || mb_strlen($motivo) < 5) {
            $_SESSION['flash_error'] = 'Para anular una liquidación debes especificar un motivo justificado (mínimo 5 caracteres).';
        } else {
            // Anulación segura y transaccional
            try {
                $db->beginTransaction();
                $stmtCheck = $db->prepare("SELECT anulado FROM turno_liquidacion WHERE id_liquidacion = ?");
                $stmtCheck->execute([$idLiq]);
                $anulado = $stmtCheck->fetchColumn();

                if ($anulado === false) {
                    $_SESSION['flash_error'] = 'La liquidación solicitada no existe.';
                } elseif ((int)$anulado === 1) {
                    $_SESSION['flash_error'] = 'Esta liquidación ya se encuentra anulada.';
                } else {
                    $stmtAnular = $db->prepare("
                        UPDATE turno_liquidacion 
                        SET anulado = 1, anulado_por = ?, fecha_anulacion = NOW(), motivo_anulacion = ?
                        WHERE id_liquidacion = ?
                    ");
                    $stmtAnular->execute([$adminId, $motivo, $idLiq]);
                    
                    // Liberar los pedidos asociados para que puedan ser reliquidados
                    $db->prepare("DELETE FROM turno_liquidacion_pedido WHERE id_liquidacion = ?")->execute([$idLiq]);

                    $db->commit();

                    registrarAuditoriaReporte(
                        $db,
                        $adminId,
                        'ANULAR_LIQUIDACION',
                        'turno_liquidacion',
                        "Liquidación #{$idLiq} anulada. Motivo: {$motivo}"
                    );

                    $_SESSION['flash_success'] = "✓ Liquidación #$idLiq anulada correctamente. Los pedidos han sido liberados para nueva liquidación.";
                }
            } catch (Exception $e) {
                if ($db->inTransaction()) $db->rollBack();
                $_SESSION['flash_error'] = 'Error al anular liquidación: ' . $e->getMessage();
            }
        }
        header('Location: reportes.php?tab=domiciliarios');
        exit;
    }
}

// -----------------------------------------------------------------------------
// FILTROS DE FECHAS (Validación: Máximo 1 año)
// -----------------------------------------------------------------------------
$tab = $_GET['tab'] ?? 'ventas';
$fechaFin = $_GET['hasta'] ?? date('Y-m-d');
$fechaInicio = $_GET['desde'] ?? date('Y-m-d', strtotime('-29 days'));

// Asegurar formato Y-m-d
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio)) $fechaInicio = date('Y-m-d', strtotime('-29 days'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFin)) $fechaFin = date('Y-m-d');

// Invertir si inicio > fin
if ($fechaInicio > $fechaFin) {
    $temp = $fechaInicio;
    $fechaInicio = $fechaFin;
    $fechaFin = $temp;
}

// Límite de 1 año (365 días) con validación estricta en el servidor
$diffDays = (strtotime($fechaFin) - strtotime($fechaInicio)) / 86400;
if ($diffDays > 365) {
    if (isset($_GET['strict_range']) || isset($_POST['strict_range'])) {
        http_response_code(422);
        echo json_encode(['error' => 'El rango máximo de consulta permitido es de 365 días']);
        exit;
    }
    header('X-Range-Days-Enforced: 365');
    $fechaInicio = date('Y-m-d', strtotime($fechaFin . ' -365 days'));
    $_SESSION['flash_warning'] = 'El rango máximo de consulta permitido es de 1 año (365 días). Se ajustó la fecha inicial.';
}

$inicioFull = "$fechaInicio 00:00:00";
$finFull    = "$fechaFin 23:59:59";
$puntajeFiltro = isset($_GET['puntaje']) && $_GET['puntaje'] !== '' ? (int)$_GET['puntaje'] : null;

// Período anterior para comparación (mismo número de días antes de $fechaInicio)
$numDias = max(1, (int)round($diffDays));
$antFinTimestamp = strtotime($fechaInicio . ' -1 day');
$antFin = date('Y-m-d 23:59:59', $antFinTimestamp);
$antInicio = date('Y-m-d 00:00:00', strtotime($antFin . " -{$numDias} days"));

// -----------------------------------------------------------------------------
// EXPORTACIONES CSV Y PDF
// -----------------------------------------------------------------------------
$exportFormat = $_GET['export'] ?? null;
if ($exportFormat === 'csv' || $exportFormat === 'pdf') {
    $tipo = $_GET['tipo'] ?? $tab;

    // 1. Exportación CSV con BOM UTF-8 y protección contra CSV Formula Injection
    if ($exportFormat === 'csv') {
        $filename = "reporte_{$tipo}_" . date('Ymd_His') . ".csv";
        header('Content-Type: text/csv; charset=UTF-8');
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        // Escribir Byte Order Mark (BOM) UTF-8 para apertura correcta en Excel
        fwrite($out, "\xEF\xBB\xBF");

        if ($tipo === 'ventas') {
            $data = $reporteModel->ventasPorRango($inicioFull, $finFull);
            fputcsv($out, ['ID Pedido', 'Fecha Venta', 'Cliente', 'Zona', 'Metodo Pago', 'Subtotal', 'Domicilio', 'Total'], ';');
            foreach ($data['pedidos'] as $p) {
                fputcsv($out, [
                    sanitizeCsvValue($p['id_pedido']),
                    sanitizeCsvValue($p['fecha_venta']),
                    sanitizeCsvValue($p['cliente_nombre']),
                    sanitizeCsvValue($p['nombre_zona'] ?? 'Sin zona'),
                    sanitizeCsvValue($p['metodo_pago']),
                    sanitizeCsvValue($p['subtotal']),
                    sanitizeCsvValue($p['costo_domicilio']),
                    sanitizeCsvValue($p['total'])
                ], ';');
            }
        } elseif ($tipo === 'categorias') {
            $data = $reporteModel->ventasPorCategoria($inicioFull, $finFull);
            fputcsv($out, ['ID Categoria', 'Categoria', 'Unidades Vendidas', 'Total Recaudado', 'Porcentaje Ventas', 'Total Pedidos'], ';');
            foreach ($data['categorias'] as $cat) {
                fputcsv($out, [
                    sanitizeCsvValue($cat['id_categoria']),
                    sanitizeCsvValue($cat['categoria_nombre']),
                    sanitizeCsvValue($cat['total_unidades']),
                    sanitizeCsvValue($cat['total_recaudado']),
                    sanitizeCsvValue($cat['porcentaje_ventas'] . '%'),
                    sanitizeCsvValue($cat['total_pedidos'])
                ], ';');
            }
        } elseif ($tipo === 'productos') {
            $data = $reporteModel->topProductos($inicioFull, $finFull, 100);
            fputcsv($out, ['ID Producto', 'Producto', 'Categoria', 'Unidades Vendidas', 'Total Recaudado'], ';');
            foreach ($data as $pr) {
                fputcsv($out, [
                    sanitizeCsvValue($pr['id_producto']),
                    sanitizeCsvValue($pr['nombre_producto']),
                    sanitizeCsvValue($pr['categoria'] ?? 'General'),
                    sanitizeCsvValue($pr['total_unidades']),
                    sanitizeCsvValue($pr['total_recaudado'])
                ], ';');
            }
        } elseif ($tipo === 'calificaciones') {
            $data = $reporteModel->calificaciones($inicioFull, $finFull, $puntajeFiltro);
            fputcsv($out, ['ID Calificacion', 'ID Pedido', 'Cliente', 'Puntuacion', 'Fecha', 'Comentario'], ';');
            foreach ($data['comentarios'] as $c) {
                fputcsv($out, [
                    sanitizeCsvValue($c['id_calificacion']),
                    sanitizeCsvValue($c['id_pedido']),
                    sanitizeCsvValue($c['cliente_nombre']),
                    sanitizeCsvValue($c['puntuacion']),
                    sanitizeCsvValue($c['fecha']),
                    sanitizeCsvValue($c['comentario'])
                ], ';');
            }
        } elseif ($tipo === 'domiciliarios') {
            $data = $reporteModel->reporteDomiciliarios($inicioFull, $finFull);
            fputcsv($out, ['ID Domiciliario', 'Nombre', 'Telefono', 'Vehiculo', 'Placa', 'Entregas', 'Efectivo', 'Digital', 'Total Recaudado', 'Calificacion'], ';');
            foreach ($data as $d) {
                fputcsv($out, [
                    sanitizeCsvValue($d['id_domiciliario']),
                    sanitizeCsvValue($d['domiciliario_nombre']),
                    sanitizeCsvValue($d['domiciliario_telefono']),
                    sanitizeCsvValue($d['tipo_vehiculo'] ?? 'N/A'),
                    sanitizeCsvValue($d['placa'] ?? 'N/A'),
                    sanitizeCsvValue($d['total_entregas']),
                    sanitizeCsvValue($d['efectivo_cobrado']),
                    sanitizeCsvValue($d['digital_cobrado']),
                    sanitizeCsvValue($d['total_recaudado']),
                    sanitizeCsvValue(round($d['calificacion_promedio'], 1))
                ], ';');
            }
        } elseif ($tipo === 'clientes') {
            // Protección de datos personales: NO incluye teléfonos ni direcciones completas
            $data = $reporteModel->reporteClientes($inicioFull, $finFull, 200);
            fputcsv($out, ['ID Cliente', 'Nombre', 'Total Pedidos', 'Total Comprado', 'Ticket Promedio', 'Ultima Compra'], ';');
            foreach ($data as $c) {
                fputcsv($out, [
                    sanitizeCsvValue($c['id_cliente']),
                    sanitizeCsvValue($c['cliente_nombre']),
                    sanitizeCsvValue($c['total_pedidos']),
                    sanitizeCsvValue($c['total_comprado']),
                    sanitizeCsvValue($c['ticket_promedio']),
                    sanitizeCsvValue($c['ultima_compra'])
                ], ';');
            }
        } elseif ($tipo === 'inventario') {
            $data = $reporteModel->consumoInsumos($inicioFull, $finFull);
            fputcsv($out, ['ID Insumo', 'Insumo', 'Unidad', 'Stock Actual', 'Stock Minimo', 'Salidas Receta', 'Salidas Manuales', 'Faltantes', 'Entradas', 'Reintegros'], ';');
            foreach ($data as $inv) {
                fputcsv($out, [
                    sanitizeCsvValue($inv['id_insumo']),
                    sanitizeCsvValue($inv['nombre_insumo']),
                    sanitizeCsvValue($inv['unidad_medida']),
                    sanitizeCsvValue($inv['stock_actual']),
                    sanitizeCsvValue($inv['stock_minimo']),
                    sanitizeCsvValue($inv['salidas_receta']),
                    sanitizeCsvValue($inv['salidas_manuales']),
                    sanitizeCsvValue($inv['faltantes']),
                    sanitizeCsvValue($inv['entradas']),
                    sanitizeCsvValue($inv['reintegros'])
                ], ';');
            }
        }

        // Registro de auditoría obligatorio
        registrarAuditoriaReporte(
            $db,
            $adminId,
            'EXPORTAR_REPORTE',
            "reporte_{$tipo}",
            "Exportación de reporte '{$tipo}' en formato CSV (Rango: {$fechaInicio} a {$fechaFin})"
        );

        fclose($out);
        exit;
    }

    // 2. Exportación PDF con mPDF y mitigación estricta de SSRF
    if ($exportFormat === 'pdf') {
        require_once __DIR__ . '/../../vendor/autoload.php';

        $tempDir = __DIR__ . '/../../storage/mpdf';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // HttpClient de seguridad que bloquea llamadas de red salientes (Anti-SSRF)
        if (!class_exists('BlockOutboundHttpClient')) {
            class BlockOutboundHttpClient implements \Mpdf\Http\ClientInterface {
                public function sendRequest(\Psr\Http\Message\RequestInterface $request) {
                    $url = (string)$request->getUri();
                    error_log("SECURITY ALERT: Blocked outbound HTTP request in mPDF to: " . $url);
                    throw new \RuntimeException("Outbound HTTP requests are blocked for security in PDF export: " . $url);
                }
            }
        }

        // Restricted local content loader that prevents LFI outside public directory
        if (!class_exists('RestrictedLocalContentLoader')) {
            class RestrictedLocalContentLoader implements \Mpdf\File\LocalContentLoaderInterface {
                private string $allowedDir;
                public function __construct(string $allowedDir) {
                    $this->allowedDir = realpath($allowedDir) ?: $allowedDir;
                }
                public function load($path) {
                    // Normalize path and strip file:// or file:///
                    $cleanPath = preg_replace('#^file:(//)?#i', '', $path);
                    // On Windows, fix leading slash like /c:/
                    if (PHP_OS_FAMILY === 'Windows') {
                        $cleanPath = preg_replace('#^/([a-zA-Z]:)#', '$1', $cleanPath);
                    }
                    $realPath = realpath($cleanPath);
                    if (!$realPath || !str_starts_with($realPath, $this->allowedDir)) {
                        error_log("SECURITY ALERT: Blocked local file access in mPDF outside allowed dir: " . $path);
                        throw new \RuntimeException("Local file access outside allowed directory is blocked: " . $path);
                    }
                    return file_get_contents($realPath);
                }
            }
        }

        if (!class_exists('SecureMpdfContainer')) {
            class SecureMpdfContainer implements \Mpdf\Container\ContainerInterface {
                private array $services = [];
                public function __construct() {
                    $this->services['httpClient'] = new BlockOutboundHttpClient();
                    $this->services['localContentLoader'] = new RestrictedLocalContentLoader(realpath(__DIR__ . '/../../public') ?: (__DIR__ . '/../../public'));
                }
                public function get($id) {
                    return $this->services[$id] ?? null;
                }
                public function has($id) {
                    return isset($this->services[$id]);
                }
            }
        }

        $mpdf = new \Mpdf\Mpdf([
            'tempDir' => $tempDir,
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 20,
            'margin_bottom' => 20,
            'default_font' => 'Helvetica',
            'format' => 'A4',
            'curlTimeout' => 0.001,
            'curlExecutionTimeout' => 0.001,
            'curlAllowUnsafeSslRequests' => false
        ], new SecureMpdfContainer());

        $mpdf->SetTitle("Reporte " . ucfirst($tipo) . " - Chorifest");
        $mpdf->SetAuthor("Chorifest Grill Admin");
        $mpdf->SetFooter('Chorifest Grill | Generado el ' . date('d/m/Y h:i A') . ' | Página {PAGENO} de {nbpg}');

        ob_start();
        ?>
        <style>
            body { font-family: Helvetica, Arial, sans-serif; color: #111; font-size: 11px; }
            .header-table { width: 100%; border-bottom: 2px solid #a8391f; padding-bottom: 10px; margin-bottom: 15px; }
            .brand-title { font-size: 22px; font-weight: bold; color: #a8391f; }
            .report-title { font-size: 14px; font-weight: bold; color: #333; margin-top: 4px; }
            .meta-text { font-size: 10px; color: #666; }
            table.data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            table.data-table th { background-color: #a8391f; color: white; padding: 7px 6px; text-align: left; font-size: 10px; font-weight: bold; }
            table.data-table td { padding: 6px; border-bottom: 1px solid #ddd; font-size: 10px; }
            table.data-table tr:nth-child(even) td { background-color: #fafafa; }
            .text-right { text-align: right; }
            .text-center { text-align: center; }
            .kpi-box { background: #fdf6f4; border: 1px solid #f3d4cb; padding: 10px; border-radius: 6px; margin-bottom: 15px; }
        </style>

        <table class="header-table">
            <tr>
                <td>
                    <div class="brand-title">CHORIFEST GOURMET GRILL</div>
                    <div class="report-title">Reporte Oficial de <?= htmlspecialchars(ucfirst($tipo)) ?></div>
                    <div class="meta-text">Período: <?= htmlspecialchars($fechaInicio) ?> al <?= htmlspecialchars($fechaFin) ?> (Zona: America/Bogota)</div>
                </td>
                <td style="text-align: right;">
                    <div class="meta-text">Generado por: <?= htmlspecialchars($currentUser['name'] ?? 'Administrador') ?></div>
                    <div class="meta-text">Fecha emisión: <?= date('d/m/Y h:i A') ?></div>
                </td>
            </tr>
        </table>
        <?php

        if ($tipo === 'ventas') {
            $data = $reporteModel->ventasPorRango($inicioFull, $finFull);
            $r = $data['resumen'];
            ?>
            <div class="kpi-box">
                <strong>Resumen Ejecutivo:</strong> Total Ventas: <strong>$<?= number_format($r['total_ventas'], 2) ?></strong> | 
                Pedidos: <strong><?= $r['cantidad_pedidos'] ?></strong> | 
                Ticket Promedio: <strong>$<?= number_format($r['ticket_promedio'], 2) ?></strong> | 
                Efectivo: <strong>$<?= number_format($r['ventas_efectivo'], 2) ?></strong> | 
                Transferencia: <strong>$<?= number_format($r['ventas_transferencia'], 2) ?></strong>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 10%;">ID Pedido</th>
                        <th style="width: 20%;">Fecha Venta</th>
                        <th style="width: 25%;">Cliente</th>
                        <th style="width: 15%;">Método</th>
                        <th style="width: 15%;" class="text-right">Subtotal</th>
                        <th style="width: 15%;" class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data['pedidos'])): ?>
                        <tr><td colspan="6" class="text-center" style="padding: 20px;">No se registraron ventas en el período seleccionado.</td></tr>
                    <?php else: foreach (array_slice($data['pedidos'], 0, 300) as $p): ?>
                        <tr>
                            <td>#<?= str_pad($p['id_pedido'], 5, '0', STR_PAD_LEFT) ?></td>
                            <td><?= htmlspecialchars($p['fecha_venta']) ?></td>
                            <td><?= htmlspecialchars($p['cliente_nombre']) ?></td>
                            <td><?= htmlspecialchars($p['metodo_pago']) ?></td>
                            <td class="text-right">$<?= number_format($p['subtotal'], 2) ?></td>
                            <td class="text-right"><strong>$<?= number_format($p['total'], 2) ?></strong></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
            <?php
        } elseif ($tipo === 'categorias') {
            $data = $reporteModel->ventasPorCategoria($inicioFull, $finFull);
            $r = $data['resumen'];
            ?>
            <div class="kpi-box">
                <strong>Resumen por Categoría:</strong> Total Ventas: <strong>$<?= number_format($r['total_recaudado'], 2) ?></strong> | Unidades: <strong><?= number_format($r['total_unidades']) ?></strong> | Categorías Activas: <strong><?= $r['total_categorias'] ?></strong>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 10%;">#</th>
                        <th style="width: 35%;">Categoría</th>
                        <th style="width: 15%;" class="text-center">Unidades</th>
                        <th style="width: 20%;" class="text-right">Total Recaudado</th>
                        <th style="width: 10%;" class="text-center">% Ventas</th>
                        <th style="width: 10%;" class="text-center">Pedidos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data['categorias'])): ?>
                        <tr><td colspan="6" class="text-center" style="padding: 20px;">Sin ventas registradas en el período.</td></tr>
                    <?php else: $idx = 1; foreach ($data['categorias'] as $cat): ?>
                        <tr>
                            <td><?= $idx++ ?></td>
                            <td><strong><?= htmlspecialchars($cat['categoria_nombre']) ?></strong></td>
                            <td class="text-center"><?= number_format($cat['total_unidades']) ?></td>
                            <td class="text-right"><strong>$<?= number_format($cat['total_recaudado'], 2) ?></strong></td>
                            <td class="text-center"><?= $cat['porcentaje_ventas'] ?>%</td>
                            <td class="text-center"><?= number_format($cat['total_pedidos']) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
            <?php
        } elseif ($tipo === 'productos') {
            $data = $reporteModel->topProductos($inicioFull, $finFull, 50);
            ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 10%;">#</th>
                        <th style="width: 45%;">Producto</th>
                        <th style="width: 20%;">Categoría</th>
                        <th style="width: 12%;" class="text-center">Uds Vendidas</th>
                        <th style="width: 13%;" class="text-right">Recaudado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data)): ?>
                        <tr><td colspan="5" class="text-center" style="padding: 20px;">Sin ventas registradas en el período.</td></tr>
                    <?php else: $idx = 1; foreach ($data as $pr): ?>
                        <tr>
                            <td><?= $idx++ ?></td>
                            <td><strong><?= htmlspecialchars($pr['nombre_producto']) ?></strong></td>
                            <td><?= htmlspecialchars($pr['categoria'] ?? 'General') ?></td>
                            <td class="text-center"><?= number_format($pr['total_unidades']) ?></td>
                            <td class="text-right"><strong>$<?= number_format($pr['total_recaudado'], 2) ?></strong></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
            <?php
        } elseif ($tipo === 'calificaciones') {
            $data = $reporteModel->calificaciones($inicioFull, $finFull, $puntajeFiltro);
            $r = $data['resumen'];
            ?>
            <div class="kpi-box">
                <strong>Resumen de Calificaciones:</strong> Promedio: <strong><?= number_format($r['promedio'], 2) ?> / 5.0</strong> | 
                Total Calificaciones: <strong><?= $r['total_calificaciones'] ?></strong> | 
                5★: <?= $r['distribucion'][5]['cantidad'] ?> (<?= $r['distribucion'][5]['porcentaje'] ?>%) | 
                4★: <?= $r['distribucion'][4]['cantidad'] ?> (<?= $r['distribucion'][4]['porcentaje'] ?>%) | 
                3★: <?= $r['distribucion'][3]['cantidad'] ?> (<?= $r['distribucion'][3]['porcentaje'] ?>%) | 
                2★: <?= $r['distribucion'][2]['cantidad'] ?> (<?= $r['distribucion'][2]['porcentaje'] ?>%) | 
                1★: <?= $r['distribucion'][1]['cantidad'] ?> (<?= $r['distribucion'][1]['porcentaje'] ?>%)
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 12%;">Pedido</th>
                        <th style="width: 18%;">Fecha</th>
                        <th style="width: 20%;">Cliente</th>
                        <th style="width: 12%;" class="text-center">Puntaje</th>
                        <th style="width: 38%;">Comentario del Cliente</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data['comentarios'])): ?>
                        <tr><td colspan="5" class="text-center" style="padding: 20px;">Sin calificaciones registradas en el período.</td></tr>
                    <?php else: foreach ($data['comentarios'] as $c): ?>
                        <tr>
                            <td>#<?= str_pad($c['id_pedido'], 5, '0', STR_PAD_LEFT) ?></td>
                            <td><?= htmlspecialchars($c['fecha']) ?></td>
                            <td><?= htmlspecialchars($c['cliente_nombre']) ?></td>
                            <td class="text-center"><strong><?= str_repeat('★', (int)$c['puntuacion']) ?> (<?= $c['puntuacion'] ?>)</strong></td>
                            <td><?= htmlspecialchars((string)$c['comentario'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
            <?php
        } elseif ($tipo === 'domiciliarios') {
            $data = $reporteModel->reporteDomiciliarios($inicioFull, $finFull);
            ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Domiciliario</th>
                        <th>Vehículo / Placa</th>
                        <th class="text-center">Entregas</th>
                        <th class="text-right">Efectivo Cobrado</th>
                        <th class="text-right">Digital</th>
                        <th class="text-right">Total Recaudado</th>
                        <th class="text-center">Calificación</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data)): ?>
                        <tr><td colspan="7" class="text-center" style="padding: 20px;">Sin registros de domicilios en el período.</td></tr>
                    <?php else: foreach ($data as $d): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($d['domiciliario_nombre']) ?></strong></td>
                            <td><?= htmlspecialchars(($d['tipo_vehiculo'] ?? 'N/A') . ' ' . ($d['placa'] ?? '')) ?></td>
                            <td class="text-center"><?= $d['total_entregas'] ?></td>
                            <td class="text-right">$<?= number_format($d['efectivo_cobrado'], 2) ?></td>
                            <td class="text-right">$<?= number_format($d['digital_cobrado'], 2) ?></td>
                            <td class="text-right"><strong>$<?= number_format($d['total_recaudado'], 2) ?></strong></td>
                            <td class="text-center">★ <?= number_format($d['calificacion_promedio'], 1) ?> (<?= $d['total_calificaciones'] ?>)</td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
            <?php
        } elseif ($tipo === 'clientes') {
            // Protección de datos personales: NO incluye teléfonos ni direcciones completas
            $data = $reporteModel->reporteClientes($inicioFull, $finFull, 50);
            ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 15%;">ID Cliente</th>
                        <th style="width: 35%;">Cliente</th>
                        <th style="width: 12%;" class="text-center">Pedidos</th>
                        <th style="width: 18%;" class="text-right">Total Comprado</th>
                        <th style="width: 20%;" class="text-right">Ticket Promedio</th>
                        <th style="width: 15%;" class="text-center">Última Compra</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data)): ?>
                        <tr><td colspan="6" class="text-center" style="padding: 20px;">Sin registros de clientes en el período.</td></tr>
                    <?php else: foreach ($data as $c): ?>
                        <tr>
                            <td>#<?= htmlspecialchars($c['id_cliente']) ?></td>
                            <td><strong><?= htmlspecialchars($c['cliente_nombre']) ?></strong></td>
                            <td class="text-center"><?= $c['total_pedidos'] ?></td>
                            <td class="text-right"><strong>$<?= number_format($c['total_comprado'], 2) ?></strong></td>
                            <td class="text-right">$<?= number_format($c['ticket_promedio'], 2) ?></td>
                            <td class="text-center"><?= htmlspecialchars(substr($c['ultima_compra'], 0, 10)) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
            <?php
        } elseif ($tipo === 'inventario') {
            $data = $reporteModel->consumoInsumos($inicioFull, $finFull);
            ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Insumo</th>
                        <th class="text-center">Unidad</th>
                        <th class="text-right">Stock Actual</th>
                        <th class="text-right">Consumo Recetas</th>
                        <th class="text-right">Mermas / Ajustes</th>
                        <th class="text-right">Entradas Compra</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data)): ?>
                        <tr><td colspan="6" class="text-center" style="padding: 20px;">Sin movimientos de inventario en el período.</td></tr>
                    <?php else: foreach ($data as $inv): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($inv['nombre_insumo']) ?></strong></td>
                            <td class="text-center"><?= htmlspecialchars($inv['unidad_medida']) ?></td>
                            <td class="text-right"><?= number_format($inv['stock_actual'], 2) ?></td>
                            <td class="text-right"><strong><?= number_format($inv['salidas_receta'], 2) ?></strong></td>
                            <td class="text-right"><?= number_format($inv['salidas_manuales'] + $inv['faltantes'], 2) ?></td>
                            <td class="text-right"><?= number_format($inv['entradas'], 2) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
            <?php
        }

        $html = ob_get_clean();
        $mpdf->WriteHTML($html);

        // Registro de auditoría obligatorio
        registrarAuditoriaReporte(
            $db,
            $adminId,
            'EXPORTAR_REPORTE',
            "reporte_{$tipo}",
            "Exportación de reporte '{$tipo}' en formato PDF (Rango: {$fechaInicio} a {$fechaFin})"
        );

        $mpdf->Output("reporte_{$tipo}_" . date('Ymd_His') . ".pdf", 'D');
        exit;
    }
}

// -----------------------------------------------------------------------------
// CONSULTA DE DATOS SEGÚN PESTAÑA ACTIVA
// -----------------------------------------------------------------------------
$ventasData = null;
$ventasAntData = null;
$topProductosData = null;
$domiciliariosData = null;
$liquidacionesData = null;
$domiciliariosList = null;
$clientesData = null;
$inventarioData = null;
$devolucionesPendientes = null;
$calificacionesData = null;
$categoriasData = null;

if ($tab === 'ventas') {
    $ventasData = $reporteModel->ventasPorRango($inicioFull, $finFull);
    $ventasAntData = $reporteModel->ventasPorRango($antInicio, $antFin);
    $devolucionesPendientes = $reporteModel->getPagosPendientesDevolucion();
} elseif ($tab === 'categorias') {
    $categoriasData = $reporteModel->ventasPorCategoria($inicioFull, $finFull);
} elseif ($tab === 'productos') {
    $topProductosData = $reporteModel->topProductos($inicioFull, $finFull, 20);
} elseif ($tab === 'calificaciones') {
    $calificacionesData = $reporteModel->calificaciones($inicioFull, $finFull, $puntajeFiltro);
} elseif ($tab === 'domiciliarios') {
    $domiciliariosData = $reporteModel->reporteDomiciliarios($inicioFull, $finFull);
    $liquidacionesData = $reporteModel->getHistorialLiquidaciones();
    // Lista de domiciliarios activos para selector de liquidación
    $stmtDomiList = $db->query("
        SELECT u.id_usuario, u.nombre, d.tipo_vehiculo, d.placa 
        FROM usuario u 
        INNER JOIN domiciliario d ON u.id_usuario = d.id_usuario 
        WHERE u.estado = 'ACTIVO' 
        ORDER BY u.nombre ASC
    ");
    $domiciliariosList = $stmtDomiList->fetchAll(PDO::FETCH_ASSOC);
} elseif ($tab === 'clientes') {
    // Auditoría de consulta a reporte de clientes
    registrarAuditoriaReporte(
        $db,
        $adminId,
        'CONSULTAR_REPORTE_CLIENTES',
        'reporte_clientes',
        "Consulta administrativa de clientes y frecuencia de compra (Rango: {$fechaInicio} a {$fechaFin})"
    );
    $clientesData = $reporteModel->reporteClientes($inicioFull, $finFull, 50);
} elseif ($tab === 'inventario') {
    $inventarioData = $reporteModel->consumoInsumos($inicioFull, $finFull);
}

$csrfToken = getCsrfToken();
$currentPage = 'reportes';
$pageTitle = 'Reportes y Métricas Operativas';

require_once __DIR__ . '/../../views/layouts/admin_header.php';
require_once __DIR__ . '/../../views/admin/reportes.php';
require_once __DIR__ . '/../../views/layouts/admin_footer.php';

