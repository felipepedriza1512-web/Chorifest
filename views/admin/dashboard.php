<?php
// Vista Dashboard Operativo Fase 1
?>
<style>
    /* Estilo oscuro para mapas Leaflet con OpenStreetMap */
    .dark-map-tiles {
        filter: invert(100%) hue-rotate(180deg) brightness(95%) contrast(90%);
    }

    /* Responsive Grid 4 Cards */
    .dashboard-metrics-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 32px;
    }

    @media (max-width: 1200px) {
        .dashboard-metrics-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 640px) {
        .dashboard-metrics-grid {
            grid-template-columns: 1fr;
        }
    }

    .metric-card {
        background: var(--surface-card);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 22px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 12px;
        transition: var(--transition);
    }

    .metric-card:hover {
        border-color: var(--border-highlight);
    }

    .metric-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .metric-title {
        font-size: 0.88rem;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .metric-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        background: rgba(191, 165, 147, 0.08);
        border: 1px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-muted);
    }

    .metric-card.accent .metric-icon {
        background: rgba(168, 57, 31, 0.15);
        color: var(--accent);
        border-color: rgba(168, 57, 31, 0.3);
    }

    .metric-value-container {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 10px;
    }

    .metric-value {
        font-family: var(--font-title);
        font-size: 2.2rem;
        font-weight: 800;
        color: var(--text-main);
        line-height: 1;
    }

    .metric-compare {
        font-size: 0.8rem;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .metric-compare.up {
        background: rgba(16, 185, 129, 0.15);
        color: #34d399;
        border: 1px solid rgba(16, 185, 129, 0.3);
    }

    .metric-compare.down {
        background: rgba(239, 68, 68, 0.15);
        color: #fca5a5;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .metric-compare.neutral {
        background: rgba(191, 165, 147, 0.1);
        color: var(--text-muted);
        border: 1px solid var(--border);
    }

    .metric-subtext {
        font-size: 0.78rem;
        color: var(--text-muted);
    }

    /* Main Dashboard Layout split */
    .dashboard-two-cols {
        display: grid;
        grid-template-columns: 1.6fr 1fr;
        gap: 24px;
        margin-bottom: 32px;
    }

    @media (max-width: 1024px) {
        .dashboard-two-cols {
            grid-template-columns: 1fr;
        }
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 18px;
    }

    .section-title {
        font-family: var(--font-title);
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--text-main);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* Queue Orders List */
    .queue-item {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 18px;
        margin-bottom: 14px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        transition: var(--transition);
    }

    .queue-item:hover {
        border-color: var(--border-highlight);
    }

    .queue-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .queue-id {
        font-family: var(--font-title);
        font-weight: 800;
        font-size: 1.1rem;
        color: var(--text-main);
    }

    .badge-status {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .badge-recibido {
        background: rgba(59, 130, 246, 0.15);
        color: #60a5fa;
        border: 1px solid rgba(59, 130, 246, 0.3);
    }

    .badge-verificacion {
        background: rgba(245, 158, 11, 0.15);
        color: #fbbf24;
        border: 1px solid rgba(245, 158, 11, 0.3);
    }

    .queue-body {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
    }

    .queue-details {
        font-size: 0.88rem;
        color: var(--text-muted);
        line-height: 1.5;
    }

    .queue-details strong {
        color: var(--text-main);
    }

    .queue-products {
        background: rgba(0,0,0,0.2);
        padding: 8px 12px;
        border-radius: 8px;
        margin-top: 8px;
        font-size: 0.82rem;
        border: 1px solid rgba(191, 165, 147, 0.08);
    }

    .queue-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 8px;
    }

    /* Alerts Panel */
    .alert-box {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 14px 16px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        font-size: 0.85rem;
    }

    .alert-box.stock { border-left: 4px solid var(--ember); }
    .alert-box.retraso { border-left: 4px solid #ef4444; }
    .alert-box.novedad { border-left: 4px solid #f59e0b; }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 36px 20px;
        color: var(--text-muted);
        font-size: 0.9rem;
        background: var(--surface);
        border: 1px dashed var(--border);
        border-radius: 12px;
    }

    /* Modal Styling */
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.8);
        backdrop-filter: blur(4px);
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.25s ease;
    }

    .modal-overlay.active {
        opacity: 1;
        pointer-events: auto;
    }

    .modal-card {
        background: var(--surface-card);
        border: 1px solid var(--border-highlight);
        border-radius: 16px;
        padding: 28px;
        width: 100%;
        max-width: 480px;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .modal-title {
        font-family: var(--font-title);
        font-size: 1.2rem;
        font-weight: 700;
    }

    .modal-textarea {
        width: 100%;
        height: 100px;
        background: var(--bg);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 12px;
        color: var(--text-main);
        font-family: var(--font-body);
        font-size: 0.9rem;
        outline: none;
        resize: none;
    }

    .modal-textarea:focus {
        border-color: var(--accent);
    }
</style>

<!-- 1. CUADRÍCULA DE 4 TARJETAS OPERATIVAS -->
<div class="dashboard-metrics-grid">
    <!-- Tarjeta 1: Pedidos Hoy -->
    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-title">Pedidos Hoy</span>
            <div class="metric-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
            </div>
        </div>
        <div class="metric-value-container">
            <span class="metric-value" id="valPedidosHoy"><?= number_format($stats['pedidos_hoy'] ?? 0) ?></span>
            <?php if ($stats['pedidos_pct'] !== null): ?>
                <span class="metric-compare <?= $stats['pedidos_pct'] >= 0 ? 'up' : 'down' ?>">
                    <?= $stats['pedidos_pct'] >= 0 ? '↑' : '↓' ?> <?= abs($stats['pedidos_pct']) ?>% vs ayer
                </span>
            <?php else: ?>
                <span class="metric-compare neutral">Nuevo / —</span>
            <?php endif; ?>
        </div>
        <span class="metric-subtext">Total creados hoy</span>
    </div>

    <!-- Tarjeta 2: Ventas Hoy (Icono $ SOLO en ventas) -->
    <div class="metric-card accent">
        <div class="metric-header">
            <span class="metric-title">Ventas Hoy</span>
            <div class="metric-icon">
                <!-- Icono $ exclusivo para ventas -->
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
            </div>
        </div>
        <div class="metric-value-container">
            <span class="metric-value" id="valVentasHoy">$<?= number_format($stats['ventas_hoy'] ?? 0, 0, ',', '.') ?></span>
            <?php if ($stats['ventas_pct'] !== null): ?>
                <span class="metric-compare <?= $stats['ventas_pct'] >= 0 ? 'up' : 'down' ?>">
                    <?= $stats['ventas_pct'] >= 0 ? '↑' : '↓' ?> <?= abs($stats['ventas_pct']) ?>% vs ayer
                </span>
            <?php else: ?>
                <span class="metric-compare neutral">Nuevo / —</span>
            <?php endif; ?>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 0.78rem; color: var(--text-muted); border-top: 1px solid var(--border); padding-top: 8px;">
            <span>Efectivo: <strong style="color: var(--text-main);">$<?= number_format($stats['ventas_efectivo_hoy'] ?? 0, 0, ',', '.') ?></strong></span>
            <span>Transf: <strong style="color: var(--text-main);">$<?= number_format($stats['ventas_transferencia_hoy'] ?? 0, 0, ',', '.') ?></strong></span>
        </div>
        <span class="metric-subtext" style="font-size: 0.72rem; color: var(--text-muted); font-style: italic;">Solo pedidos entregados hoy</span>
    </div>

    <!-- Tarjeta 3: Por Atender -->
    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-title">Por Atender</span>
            <div class="metric-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            </div>
        </div>
        <div class="metric-value-container">
            <span class="metric-value" style="color: var(--ember);" id="valPorAtender"><?= number_format($stats['por_atender'] ?? 0) ?></span>
        </div>
        <span class="metric-subtext">Pendientes de aceptar / verificar pago</span>
    </div>

    <!-- Tarjeta 4: En Curso -->
    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-title">En Curso</span>
            <div class="metric-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path></svg>
            </div>
        </div>
        <div class="metric-value-container">
            <span class="metric-value" id="valEnCurso"><?= number_format($stats['en_curso'] ?? 0) ?></span>
        </div>
        <span class="metric-subtext">En cocina o en camino</span>
    </div>
</div>

<!-- 2. LAYOUT PRINCIPAL DE 2 COLUMNAS (COLA EN VIVO Y ALERTAS OPERATIVAS) -->
<div class="dashboard-two-cols">
    <!-- COLUMNA IZQUIERDA: COLA DE PEDIDOS EN VIVO -->
    <div>
        <div class="section-header">
            <h3 class="section-title">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                Cola de Pedidos por Atender
            </h3>
            <span style="font-size: 0.8rem; color: var(--text-muted);" id="liveStatus">En vivo (5s)</span>
        </div>

        <div id="queueContainer">
            <?php if (empty($colaPedidos)): ?>
                <div class="empty-state">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="1.5" style="margin-bottom: 10px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <p style="font-weight: 600; color: var(--text-main);">¡Todo al día!</p>
                    <p style="margin-top: 4px;">No hay pedidos pendientes de aceptar ni transferencias por verificar en este momento.</p>
                </div>
            <?php else: ?>
                <?php foreach ($colaPedidos as $ped): ?>
                    <div class="queue-item" id="order-card-<?= $ped['id_pedido'] ?>">
                        <div class="queue-header">
                            <span class="queue-id">Pedido #<?= $ped['id_pedido'] ?></span>
                            <span class="badge-status <?= $ped['id_estado'] == 8 ? 'badge-verificacion' : 'badge-recibido' ?>">
                                <?= htmlspecialchars($ped['estado_nombre'] ?? 'Recibido') ?>
                            </span>
                        </div>
                        <div class="queue-body">
                            <div class="queue-details" style="flex: 1;">
                                <p><strong>Cliente:</strong> <?= htmlspecialchars($ped['cliente_nombre'] ?? 'Cliente') ?> (<?= htmlspecialchars($ped['cliente_telefono'] ?? 'S/T') ?>)</p>
                                <p><strong>Dirección:</strong> <?= htmlspecialchars($ped['direccion'] ?? 'Recogida') ?> <?= $ped['barrio'] ? '('.htmlspecialchars($ped['barrio']).')' : '' ?></p>
                                <p><strong>Pago:</strong> <?= htmlspecialchars($ped['metodo_pago'] ?? 'EFECTIVO') ?> - <span style="color: var(--ember); font-weight: 700;">$<?= number_format($ped['total'], 0, ',', '.') ?></span></p>
                                
                                <?php if (!empty($ped['comprobante']) || !empty($ped['ref_pago'])): ?>
                                    <p style="margin-top: 4px; font-size: 0.8rem; color: #fbbf24;">
                                        📷 Comprobante: <?= htmlspecialchars($ped['ref_pago'] ?: 'Adjunto') ?>
                                        <?php if (!empty($ped['comprobante'])): ?>
                                            <a href="<?= baseUrl('/controllers/admin/ver_comprobante.php?id=' . $ped['id_pedido']) ?>" target="_blank" style="color: #60a5fa; text-decoration: underline; margin-left: 6px;">Ver comprobante</a>
                                        <?php endif; ?>
                                    </p>
                                <?php endif; ?>

                                <div class="queue-products">
                                    <strong>Productos:</strong>
                                    <?php 
                                        $prods = array_map(function($d) {
                                            return $d['cantidad'] . 'x ' . $d['nombre_producto'];
                                        }, $ped['detalles'] ?? []);
                                        echo htmlspecialchars(implode(', ', $prods));
                                    ?>
                                </div>
                            </div>
                        </div>
                        <div class="queue-actions">
                            <?php if ($ped['id_estado'] == 8): ?>
                                <button type="button" class="btn btn-primary" onclick="verificarPago(<?= $ped['id_pedido'] ?>)">
                                    ✓ Verificar Pago y Aceptar
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-primary" onclick="aceptarPedido(<?= $ped['id_pedido'] ?>)">
                                    ✓ Aceptar Pedido
                                </button>
                            <?php endif; ?>
                            
                            <button type="button" class="btn btn-danger" onclick="abrirModalRechazo(<?= $ped['id_pedido'] ?>)">
                                ✕ Rechazar
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- COLUMNA DERECHA: ALERTAS OPERATIVAS & MAPA EN VIVO -->
    <div style="display: flex; flex-direction: column; gap: 24px;">
        <!-- PANEL DE ALERTAS OPERATIVAS -->
        <div class="card" id="alertasSection" style="margin-bottom: 0;">
            <div class="section-header" style="margin-bottom: 14px;">
                <h3 class="section-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--ember)" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    Alertas Operativas
                </h3>
            </div>

            <div id="alertasContainer">
                <?php 
                    $hasAlerts = !empty($alertas['stock_bajo']) || !empty($alertas['pedidos_retrasados']) || !empty($alertas['pagos_por_revisar']) || !empty($alertas['novedades']);
                ?>
                <?php if (!$hasAlerts): ?>
                    <div style="text-align: center; color: var(--text-muted); font-size: 0.85rem; padding: 16px;">
                        ✓ Sin alertas operativas. Stock adecuado y entregas a tiempo.
                    </div>
                <?php else: ?>
                    <!-- Pagos por revisar (Separado de Novedades de Entrega) -->
                    <?php foreach ($alertas['pagos_por_revisar'] ?? [] as $pag): ?>
                        <div class="alert-box pago" style="border-left: 4px solid #a855f7;">
                            <div>
                                <strong style="color: #c084fc;">💳 Pago Rechazado #<?= $pag['id_pedido'] ?> (Problema de Pago)</strong>
                                <p style="font-size: 0.78rem; color: var(--text-muted);"><?= htmlspecialchars($pag['cliente_nombre'] ?? 'Cliente') ?> &bull; $<?= number_format($pag['total'], 0, ',', '.') ?> (<?= htmlspecialchars($pag['metodo_pago'] ?? 'Transferencia') ?>)</p>
                            </div>
                            <a href="<?= baseUrl('/controllers/admin/pedidos.php') ?>" class="btn btn-secondary" style="padding: 4px 10px; font-size: 0.75rem;">Revisar Pago</a>
                        </div>
                    <?php endforeach; ?>

                    <!-- Retrasados -->
                    <?php foreach ($alertas['pedidos_retrasados'] as $ret): ?>
                        <div class="alert-box retraso">
                            <div>
                                <strong style="color: #fca5a5;">⏱ Pedido #<?= $ret['id_pedido'] ?> Retrasado</strong>
                                <p style="font-size: 0.78rem; color: var(--text-muted);"><?= $ret['minutos_transcurridos'] ?> min en preparación / despacho (<?= htmlspecialchars($ret['cliente_nombre'] ?? 'Cliente') ?>)</p>
                            </div>
                            <a href="<?= baseUrl('/controllers/admin/pedidos.php') ?>" class="btn btn-secondary" style="padding: 4px 10px; font-size: 0.75rem;">Ver</a>
                        </div>
                    <?php endforeach; ?>

                    <!-- Stock Bajo -->
                    <?php foreach ($alertas['stock_bajo'] as $stk): ?>
                        <div class="alert-box stock">
                            <div>
                                <strong style="color: var(--ember);">⚠️ Stock Bajo: <?= htmlspecialchars($stk['nombre']) ?></strong>
                                <p style="font-size: 0.78rem; color: var(--text-muted);">Actual: <?= $stk['stock_actual'] ?> <?= $stk['unidad_medida'] ?> (Mínimo: <?= $stk['stock_minimo'] ?>)</p>
                            </div>
                            <a href="<?= baseUrl('/controllers/admin/ingredientes.php') ?>" class="btn btn-secondary" style="padding: 4px 10px; font-size: 0.75rem;">Ajustar</a>
                        </div>
                    <?php endforeach; ?>

                    <!-- Novedades de Entrega en Ruta (Demorado / Problema) -->
                    <?php foreach ($alertas['novedades'] as $nov): ?>
                        <div class="alert-box novedad">
                            <div>
                                <strong style="color: #fbbf24;">🚨 Novedad de Entrega #<?= $nov['id_pedido'] ?>: <?= htmlspecialchars($nov['estado_nombre']) ?></strong>
                                <p style="font-size: 0.78rem; color: var(--text-muted);"><?= htmlspecialchars($nov['cliente_nombre'] ?? 'Cliente') ?></p>
                            </div>
                            <a href="<?= baseUrl('/controllers/admin/pedidos.php') ?>" class="btn btn-secondary" style="padding: 4px 10px; font-size: 0.75rem;">Revisar</a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- MAPA LEAFLET DOMICILIARIOS EN CAMINO -->
        <div class="card" style="margin-bottom: 0;">
            <div class="section-header" style="margin-bottom: 12px;">
                <h3 class="section-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    Domiciliarios En Camino
                </h3>
            </div>
            
            <div id="liveMap" style="height: 260px; width: 100%; border-radius: 10px; background: var(--bg); border: 1px solid var(--border);"></div>
            
            <div id="noDriversText" class="empty-state" style="padding: 16px; margin-top: 10px; font-size: 0.82rem; <?= !empty($domiciliariosEnCamino) ? 'display: none;' : '' ?>">
                No hay domiciliarios realizando entregas en vivo en este instante.
            </div>
        </div>
    </div>
</div>

<!-- 3. SECCIÓN INFERIOR: GRÁFICO DE VENTAS 7 DÍAS Y TOP PRODUCTOS -->
<div class="dashboard-two-cols">
    <div class="card">
        <div class="section-header">
            <h3 class="section-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                Ventas de los Últimos 7 Días (Entregados)
            </h3>
        </div>
        <div style="height: 280px; position: relative;">
            <canvas id="salesChartDetailed"></canvas>
        </div>
    </div>

    <div class="card">
        <div class="section-header">
            <h3 class="section-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--ember)" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                Top 5 Productos Más Vendidos
            </h3>
        </div>
        
        <?php if (empty($topProductos)): ?>
            <div class="empty-state" style="padding: 24px;">
                No hay ventas registradas en los últimos 7 días.
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <?php foreach ($topProductos as $idx => $tp): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; background: var(--surface); border: 1px solid var(--border); border-radius: 8px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <span style="font-family: var(--font-title); font-weight: 800; color: var(--accent); font-size: 1.1rem;">#<?= $idx + 1 ?></span>
                            <div>
                                <strong style="font-size: 0.9rem; color: var(--text-main);"><?= htmlspecialchars($tp['nombre_producto']) ?></strong>
                                <p style="font-size: 0.78rem; color: var(--text-muted);"><?= number_format($tp['total_vendido']) ?> unidades vendidas</p>
                            </div>
                        </div>
                        <span style="font-weight: 700; color: var(--text-main); font-size: 0.9rem;">$<?= number_format($tp['total_recaudado'], 0, ',', '.') ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL DE RECHAZO DE PEDIDO CON MOTIVO -->
<div class="modal-overlay" id="modalRechazo">
    <div class="modal-card">
        <h3 class="modal-title">Rechazar Pedido</h3>
        <p style="font-size: 0.85rem; color: var(--text-muted);">Indica la razón del rechazo para informar al cliente y registrar en auditoría:</p>
        
        <input type="hidden" id="rechazoOrderId" value="0">
        <textarea id="rechazoMotivo" class="modal-textarea" placeholder="Ej: Dirección fuera de cobertura, falta de insumos..."></textarea>
        
        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 8px;">
            <button type="button" class="btn btn-secondary" onclick="cerrarModalRechazo()">Cancelar</button>
            <button type="button" class="btn btn-danger" onclick="confirmarRechazo()">Confirmar Rechazo</button>
        </div>
    </div>
</div>

<!-- SCRIPTS Leaflet, Chart.js & Polling JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
const CSRF_TOKEN = <?= json_encode($csrfToken) ?>;
const BASE_URL = <?= json_encode(baseUrl()) ?>;

// 1. Chart.js desglosado por Efectivo vs Transferencia
document.addEventListener('DOMContentLoaded', function() {
    const chartCtx = document.getElementById('salesChartDetailed').getContext('2d');
    const chartData = <?= json_encode($chartData) ?>;

    new Chart(chartCtx, {
        type: 'bar',
        data: {
            labels: chartData.labels,
            datasets: [
                {
                    label: 'Efectivo ($)',
                    data: chartData.efectivo,
                    backgroundColor: 'rgba(168, 57, 31, 0.8)',
                    borderColor: '#A8391F',
                    borderWidth: 1,
                    borderRadius: 4
                },
                {
                    label: 'Transferencia ($)',
                    data: chartData.transferencia,
                    backgroundColor: 'rgba(255, 138, 42, 0.8)',
                    borderColor: '#ff8a2a',
                    borderWidth: 1,
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: { color: '#bfa593', font: { family: 'Inter', size: 12 } }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': $' + new Intl.NumberFormat('es-CO').format(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { color: 'rgba(191, 165, 147, 0.1)' },
                    ticks: { color: '#bfa593' }
                },
                y: {
                    grid: { color: 'rgba(191, 165, 147, 0.1)' },
                    ticks: { 
                        color: '#bfa593',
                        callback: function(v) { return '$' + (v / 1000) + 'k'; }
                    }
                }
            }
        }
    });
});

// 2. Leaflet Map setup con atribución a OpenStreetMap y CARTO (Condition 8)
let map, markersGroup;
function initMap() {
    const defaultLat = 4.6097; // Bogotá
    const defaultLng = -74.0817;
    
    map = L.map('liveMap').setView([defaultLat, defaultLng], 12);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        className: 'dark-map-tiles',
        maxZoom: 19
    }).addTo(map);

    markersGroup = L.layerGroup().addTo(map);
    renderMapMarkers(<?= json_encode($domiciliariosEnCamino) ?>);
}

function renderMapMarkers(domiciliarios) {
    if (!markersGroup) return;
    markersGroup.clearLayers();
    
    const containerNoDrivers = document.getElementById('noDriversText');
    if (!domiciliarios || domiciliarios.length === 0) {
        if (containerNoDrivers) containerNoDrivers.style.display = 'block';
        return;
    }
    
    if (containerNoDrivers) containerNoDrivers.style.display = 'none';

    let bounds = L.latLngBounds();
    domiciliarios.forEach(d => {
        if (d.latitud && d.longitud) {
            const marker = L.marker([d.latitud, d.longitud]).addTo(markersGroup);
            marker.bindPopup(`
                <strong style="color:#000;">🛵 ${d.nombre}</strong><br>
                <span style="color:#333; font-size: 0.8rem;">Pedido #${d.id_pedido} (${d.direccion || ''})</span>
            `);
            bounds.extend([d.latitud, d.longitud]);
        }
    });
    
    if (domiciliarios.length > 0) {
        map.fitBounds(bounds, { padding: [30, 30] });
    }
}

document.addEventListener('DOMContentLoaded', initMap);

// 3. Acciones de Administración (Aceptar, Rechazar, Verificar Pago)
function aceptarPedido(idPedido) {
    if (!confirm(`¿Confirmar aceptación del Pedido #${idPedido}?`)) return;
    
    ejecutarAccionAdmin(idPedido, 'aceptar');
}

function abrirModalRechazo(idPedido) {
    document.getElementById('rechazoOrderId').value = idPedido;
    document.getElementById('rechazoMotivo').value = '';
    document.getElementById('modalRechazo').classList.add('active');
}

function cerrarModalRechazo() {
    document.getElementById('modalRechazo').classList.remove('active');
}

function confirmarRechazo() {
    const idPedido = document.getElementById('rechazoOrderId').value;
    const motivo = document.getElementById('rechazoMotivo').value.trim();
    
    if (!motivo) {
        alert('Por favor ingresa un motivo para el rechazo.');
        return;
    }
    
    cerrarModalRechazo();
    ejecutarAccionAdmin(idPedido, 'rechazar', motivo);
}

function verificarPago(idPedido) {
    if (!confirm(`¿Confirmar verificación de transferencia del Pedido #${idPedido}?`)) return;
    ejecutarAccionAdmin(idPedido, 'verificar_pago');
}

function ejecutarAccionAdmin(idPedido, action, motivo = '') {
    const formData = new FormData();
    formData.append('id_pedido', idPedido);
    formData.append('action', action);
    formData.append('motivo', motivo);
    formData.append('csrf_token', CSRF_TOKEN);

    fetch(BASE_URL + '/api/actualizar_estado_admin.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            triggerLivePolling();
        } else {
            alert(data.message || 'Ocurrió un error al procesar la acción.');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Error de conexión con el servidor.');
    });
}

// 4. Polling en vivo cada 5s (pausado cuando la pestaña no está visible - Condition 8)
let pollingTimer = null;

function triggerLivePolling() {
    if (document.hidden) return; // Regla 8: pausar si la pestaña no es visible

    fetch(BASE_URL + '/api/dashboard_live.php')
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            updateDashboardUI(data);
        }
    })
    .catch(err => console.log('Polling Error:', err));
}

function updateDashboardUI(data) {
    // 1. Actualizar números métricas
    if (data.stats) {
        document.getElementById('valPedidosHoy').textContent = data.stats.pedidos_hoy;
        document.getElementById('valVentasHoy').textContent = '$' + new Intl.NumberFormat('es-CO').format(data.stats.ventas_hoy);
        document.getElementById('valPorAtender').textContent = data.stats.por_atender;
        document.getElementById('valEnCurso').textContent = data.stats.en_curso;
        document.getElementById('topNotificationCount').textContent = data.stats.por_atender;
    }

    // 2. Renderizar Cola de Pedidos
    const queueContainer = document.getElementById('queueContainer');
    if (data.cola_pedidos && data.cola_pedidos.length > 0) {
        let html = '';
        data.cola_pedidos.forEach(ped => {
            const isVerif = ped.id_estado == 8;
            const prodsStr = (ped.detalles || []).map(d => d.cantidad + 'x ' + d.nombre_producto).join(', ');
            
            html += `
                <div class="queue-item" id="order-card-${ped.id_pedido}">
                    <div class="queue-header">
                        <span class="queue-id">Pedido #${ped.id_pedido}</span>
                        <span class="badge-status ${isVerif ? 'badge-verificacion' : 'badge-recibido'}">
                            ${ped.estado_nombre || 'Recibido'}
                        </span>
                    </div>
                    <div class="queue-body">
                        <div class="queue-details" style="flex: 1;">
                            <p><strong>Cliente:</strong> ${ped.cliente_nombre || 'Cliente'} (${ped.cliente_telefono || 'S/T'})</p>
                            <p><strong>Dirección:</strong> ${ped.direccion || 'Recogida'} ${ped.barrio ? '('+ped.barrio+')' : ''}</p>
                            <p><strong>Pago:</strong> ${ped.metodo_pago || 'EFECTIVO'} - <span style="color: var(--ember); font-weight: 700;">$${new Intl.NumberFormat('es-CO').format(ped.total)}</span></p>
                            ${(ped.comprobante || ped.ref_pago) ? `<p style="margin-top: 4px; font-size: 0.8rem; color: #fbbf24;">📷 Comprobante: ${ped.ref_pago || 'Adjunto'} ${ped.comprobante ? `<a href="${BASE_URL}/controllers/admin/ver_comprobante.php?id=${ped.id_pedido}" target="_blank" style="color: #60a5fa; text-decoration: underline; margin-left: 6px;">Ver comprobante</a>` : ''}</p>` : ''}
                            <div class="queue-products">
                                <strong>Productos:</strong> ${prodsStr}
                            </div>
                        </div>
                    </div>
                    <div class="queue-actions">
                        ${isVerif 
                            ? `<button type="button" class="btn btn-primary" onclick="verificarPago(${ped.id_pedido})">✓ Verificar Pago y Aceptar</button>`
                            : `<button type="button" class="btn btn-primary" onclick="aceptarPedido(${ped.id_pedido})">✓ Aceptar Pedido</button>`
                        }
                        <button type="button" class="btn btn-danger" onclick="abrirModalRechazo(${ped.id_pedido})">✕ Rechazar</button>
                    </div>
                </div>
            `;
        });
        queueContainer.innerHTML = html;
    } else {
        queueContainer.innerHTML = `
            <div class="empty-state">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="1.5" style="margin-bottom: 10px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                <p style="font-weight: 600; color: var(--text-main);">¡Todo al día!</p>
                <p style="margin-top: 4px;">No hay pedidos pendientes de aceptar ni transferencias por verificar en este momento.</p>
            </div>
        `;
    }

    // 3. Renderizar Alertas Operativas
    const alertasContainer = document.getElementById('alertasContainer');
    const al = data.alertas || {};
    const hasAlerts = (al.stock_bajo && al.stock_bajo.length > 0) || (al.pedidos_retrasados && al.pedidos_retrasados.length > 0) || (al.pagos_por_revisar && al.pagos_por_revisar.length > 0) || (al.novedades && al.novedades.length > 0);

    if (hasAlerts) {
        let htmlAlerts = '';
        (al.pagos_por_revisar || []).forEach(pag => {
            htmlAlerts += `
                <div class="alert-box pago" style="border-left: 4px solid #a855f7;">
                    <div>
                        <strong style="color: #c084fc;">💳 Pago Rechazado #${pag.id_pedido} (Problema de Pago)</strong>
                        <p style="font-size: 0.78rem; color: var(--text-muted);">${pag.cliente_nombre || 'Cliente'} &bull; $${new Intl.NumberFormat('es-CO').format(pag.total || 0)} (${pag.metodo_pago || 'Transferencia'})</p>
                    </div>
                    <a href="${BASE_URL}/controllers/admin/pedidos.php" class="btn btn-secondary" style="padding: 4px 10px; font-size: 0.75rem;">Revisar Pago</a>
                </div>
            `;
        });
        (al.pedidos_retrasados || []).forEach(ret => {
            htmlAlerts += `
                <div class="alert-box retraso">
                    <div>
                        <strong style="color: #fca5a5;">⏱ Pedido #${ret.id_pedido} Retrasado</strong>
                        <p style="font-size: 0.78rem; color: var(--text-muted);">${ret.minutos_transcurridos} min en preparación / despacho (${ret.cliente_nombre || 'Cliente'})</p>
                    </div>
                    <a href="${BASE_URL}/controllers/admin/pedidos.php" class="btn btn-secondary" style="padding: 4px 10px; font-size: 0.75rem;">Ver</a>
                </div>
            `;
        });
        (al.stock_bajo || []).forEach(stk => {
            htmlAlerts += `
                <div class="alert-box stock">
                    <div>
                        <strong style="color: var(--ember);">⚠️ Stock Bajo: ${stk.nombre}</strong>
                        <p style="font-size: 0.78rem; color: var(--text-muted);">Actual: ${stk.stock_actual} ${stk.unidad_medida} (Mínimo: ${stk.stock_minimo})</p>
                    </div>
                    <a href="${BASE_URL}/controllers/admin/ingredientes.php" class="btn btn-secondary" style="padding: 4px 10px; font-size: 0.75rem;">Ajustar</a>
                </div>
            `;
        });
        (al.novedades || []).forEach(nov => {
            htmlAlerts += `
                <div class="alert-box novedad">
                    <div>
                        <strong style="color: #fbbf24;">🚨 Novedad de Entrega #${nov.id_pedido}: ${nov.estado_nombre}</strong>
                        <p style="font-size: 0.78rem; color: var(--text-muted);">${nov.cliente_nombre || 'Cliente'}</p>
                    </div>
                    <a href="${BASE_URL}/controllers/admin/pedidos.php" class="btn btn-secondary" style="padding: 4px 10px; font-size: 0.75rem;">Revisar</a>
                </div>
            `;
        });
        alertasContainer.innerHTML = htmlAlerts;
    } else {
        alertasContainer.innerHTML = `<div style="text-align: center; color: var(--text-muted); font-size: 0.85rem; padding: 16px;">✓ Sin alertas operativas. Stock adecuado y entregas a tiempo.</div>`;
    }

    // 4. Renderizar mapa domiciliarios
    if (data.domiciliarios_en_camino) {
        renderMapMarkers(data.domiciliarios_en_camino);
    }
}

// Iniciar Polling cada 5 segundos y pausar cuando la pestaña no está visible (Condition 8)
pollingTimer = setInterval(triggerLivePolling, 5000);

document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        if (pollingTimer) clearInterval(pollingTimer);
    } else {
        triggerLivePolling();
        pollingTimer = setInterval(triggerLivePolling, 5000);
    }
});
</script>
