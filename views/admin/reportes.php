<?php
// Vista Reportes y Liquidación - Fase 5
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .reports-container {
        display: flex;
        flex-direction: column;
        gap: 24px;
    }

    /* Filter Bar */
    .filter-card {
        background: var(--surface-card);
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 20px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }

    .filter-group {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .date-input-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
        background: rgba(12, 5, 5, 0.6);
        border: 1px solid var(--border);
        padding: 6px 12px;
        border-radius: 8px;
    }

    .date-input-wrap label {
        font-size: 0.75rem;
        color: var(--text-muted);
        text-transform: uppercase;
        font-weight: 600;
    }

    .date-input-wrap input[type="date"] {
        background: transparent;
        border: none;
        color: var(--text-main);
        font-size: 0.88rem;
        outline: none;
    }

    .quick-ranges {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }

    .btn-quick {
        background: rgba(191, 165, 147, 0.08);
        border: 1px solid var(--border);
        color: var(--text-muted);
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 0.78rem;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        text-decoration: none;
    }

    .btn-quick:hover {
        background: var(--surface-hover);
        color: var(--text-main);
        border-color: var(--border-highlight);
    }

    .export-actions {
        display: flex;
        gap: 10px;
    }

    .btn-export {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 600;
        text-decoration: none;
        transition: var(--transition);
    }

    .btn-export-csv {
        background: rgba(16, 185, 129, 0.15);
        color: #2ed573;
        border: 1px solid rgba(16, 185, 129, 0.3);
    }
    .btn-export-csv:hover {
        background: rgba(16, 185, 129, 0.3);
        transform: translateY(-1px);
    }

    .btn-export-pdf {
        background: rgba(220, 38, 38, 0.15);
        color: #ff6b6b;
        border: 1px solid rgba(220, 38, 38, 0.3);
    }
    .btn-export-pdf:hover {
        background: rgba(220, 38, 38, 0.3);
        transform: translateY(-1px);
    }

    /* Tabs Navigation */
    .tabs-nav {
        display: flex;
        gap: 8px;
        border-bottom: 1px solid var(--border);
        padding-bottom: 12px;
        overflow-x: auto;
    }

    .tab-btn {
        padding: 10px 20px;
        border-radius: 10px;
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--text-muted);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: var(--transition);
        border: 1px solid transparent;
        white-space: nowrap;
    }

    .tab-btn:hover {
        color: var(--text-main);
        background: var(--surface-card);
    }

    .tab-btn.active {
        background: linear-gradient(135deg, rgba(168, 57, 31, 0.25), rgba(255, 138, 42, 0.1));
        color: var(--text-main);
        border-color: var(--accent);
        box-shadow: 0 4px 12px rgba(168, 57, 31, 0.2);
    }

    /* KPI Cards Grid */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
    }

    @media (max-width: 1100px) {
        .kpi-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 600px) {
        .kpi-grid { grid-template-columns: 1fr; }
    }

    .kpi-card {
        background: var(--surface-card);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 20px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 10px;
    }

    .kpi-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: var(--text-muted);
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .kpi-value {
        font-size: 1.8rem;
        font-weight: 800;
        font-family: var(--font-title);
        color: var(--text-main);
    }

    .kpi-sub {
        font-size: 0.8rem;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .badge-variation-pos { color: #2ed573; font-weight: 700; }
    .badge-variation-neg { color: #ff4757; font-weight: 700; }

    /* Tables & Cards */
    .report-card {
        background: var(--surface-card);
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 24px;
    }

    .report-card-title {
        font-family: var(--font-title);
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .data-table-responsive {
        width: 100%;
        overflow-x: auto;
    }

    .styled-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    .styled-table th {
        background: rgba(12, 5, 5, 0.4);
        padding: 12px 14px;
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid var(--border);
    }

    .styled-table td {
        padding: 14px;
        border-bottom: 1px solid var(--border);
        font-size: 0.88rem;
    }

    .styled-table tbody tr:hover {
        background: rgba(255, 255, 255, 0.02);
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 50px 20px;
        color: var(--text-muted);
    }

    .empty-icon {
        font-size: 3rem;
        margin-bottom: 12px;
        opacity: 0.7;
    }

    /* Alert Banner (Devoluciones Pendientes) */
    .alert-devoluciones {
        background: rgba(255, 171, 0, 0.08);
        border: 1px solid rgba(255, 171, 0, 0.3);
        border-radius: 14px;
        padding: 18px 24px;
        margin-bottom: 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }
</style>

<div class="reports-container">

    <!-- Flash Messages -->
    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.4); color: #2ed573; padding: 14px 20px; border-radius: 10px; font-weight: 600;">
            <?= htmlspecialchars($_SESSION['flash_success']) ?>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div style="background: rgba(220, 38, 38, 0.15); border: 1px solid rgba(220, 38, 38, 0.4); color: #ff6b6b; padding: 14px 20px; border-radius: 10px; font-weight: 600;">
            <?= htmlspecialchars($_SESSION['flash_error']) ?>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_warning'])): ?>
        <div style="background: rgba(255, 171, 0, 0.15); border: 1px solid rgba(255, 171, 0, 0.4); color: #ffab00; padding: 14px 20px; border-radius: 10px; font-weight: 600;">
            <?= htmlspecialchars($_SESSION['flash_warning']) ?>
        </div>
        <?php unset($_SESSION['flash_warning']); ?>
    <?php endif; ?>

    <!-- Barra de Filtros y Rango de Fechas -->
    <div class="filter-card">
        <form method="GET" action="reportes.php" class="filter-group">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
            
            <div class="date-input-wrap">
                <label>Desde:</label>
                <input type="date" name="desde" id="input-desde" value="<?= htmlspecialchars($fechaInicio) ?>">
            </div>

            <div class="date-input-wrap">
                <label>Hasta:</label>
                <input type="date" name="hasta" id="input-hasta" value="<?= htmlspecialchars($fechaFin) ?>">
            </div>

            <?php if ($tab === 'calificaciones'): ?>
                <div class="date-input-wrap">
                    <label>Puntaje:</label>
                    <select name="puntaje" style="background: transparent; border: none; color: var(--text-main); font-size: 0.88rem; outline: none; cursor: pointer;">
                        <option value="" style="background: #1a1a1a; color: white;">Todas (1-5★)</option>
                        <option value="5" <?= ($puntajeFiltro === 5) ? 'selected' : '' ?> style="background: #1a1a1a; color: white;">⭐⭐⭐⭐⭐ (5 estrellas)</option>
                        <option value="4" <?= ($puntajeFiltro === 4) ? 'selected' : '' ?> style="background: #1a1a1a; color: white;">⭐⭐⭐⭐ (4 estrellas)</option>
                        <option value="3" <?= ($puntajeFiltro === 3) ? 'selected' : '' ?> style="background: #1a1a1a; color: white;">⭐⭐⭐ (3 estrellas)</option>
                        <option value="2" <?= ($puntajeFiltro === 2) ? 'selected' : '' ?> style="background: #1a1a1a; color: white;">⭐⭐ (2 estrellas)</option>
                        <option value="1" <?= ($puntajeFiltro === 1) ? 'selected' : '' ?> style="background: #1a1a1a; color: white;">⭐ (1 estrella)</option>
                    </select>
                </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary" style="padding: 8px 18px; font-size: 0.85rem;">
                Filtrar
            </button>

            <!-- Accesos rápidos -->
            <div class="quick-ranges">
                <a href="reportes.php?tab=<?= urlencode($tab) ?>&desde=<?= date('Y-m-d') ?>&hasta=<?= date('Y-m-d') ?><?= $puntajeFiltro ? '&puntaje=' . $puntajeFiltro : '' ?>" class="btn-quick">Hoy</a>
                <a href="reportes.php?tab=<?= urlencode($tab) ?>&desde=<?= date('Y-m-d', strtotime('-1 day')) ?>&hasta=<?= date('Y-m-d', strtotime('-1 day')) ?><?= $puntajeFiltro ? '&puntaje=' . $puntajeFiltro : '' ?>" class="btn-quick">Ayer</a>
                <a href="reportes.php?tab=<?= urlencode($tab) ?>&desde=<?= date('Y-m-d', strtotime('-6 days')) ?>&hasta=<?= date('Y-m-d') ?><?= $puntajeFiltro ? '&puntaje=' . $puntajeFiltro : '' ?>" class="btn-quick">7 Días</a>
                <a href="reportes.php?tab=<?= urlencode($tab) ?>&desde=<?= date('Y-m-01') ?>&hasta=<?= date('Y-m-d') ?><?= $puntajeFiltro ? '&puntaje=' . $puntajeFiltro : '' ?>" class="btn-quick">Este Mes</a>
                <a href="reportes.php?tab=<?= urlencode($tab) ?>&desde=<?= date('Y-m-d', strtotime('-89 days')) ?>&hasta=<?= date('Y-m-d') ?><?= $puntajeFiltro ? '&puntaje=' . $puntajeFiltro : '' ?>" class="btn-quick">90 Días</a>
            </div>
        </form>

        <!-- Botones de Exportación -->
        <div class="export-actions">
            <a href="reportes.php?tab=<?= urlencode($tab) ?>&desde=<?= urlencode($fechaInicio) ?>&hasta=<?= urlencode($fechaFin) ?><?= $puntajeFiltro ? '&puntaje=' . $puntajeFiltro : '' ?>&export=csv&tipo=<?= urlencode($tab) ?>" class="btn-export btn-export-csv" title="Descargar archivo CSV compatible con Excel">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                Exportar CSV
            </a>
            <a href="reportes.php?tab=<?= urlencode($tab) ?>&desde=<?= urlencode($fechaInicio) ?>&hasta=<?= urlencode($fechaFin) ?><?= $puntajeFiltro ? '&puntaje=' . $puntajeFiltro : '' ?>&export=pdf&tipo=<?= urlencode($tab) ?>" class="btn-export btn-export-pdf" title="Descargar documento PDF con formato oficial">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                Exportar PDF
            </a>
        </div>
    </div>

    <!-- Pestañas de Reportes -->
    <nav class="tabs-nav">
        <a href="reportes.php?tab=ventas&desde=<?= urlencode($fechaInicio) ?>&hasta=<?= urlencode($fechaFin) ?>" class="tab-btn <?= $tab === 'ventas' ? 'active' : '' ?>">
            <span>💰</span> Reporte de Ventas
        </a>
        <a href="reportes.php?tab=categorias&desde=<?= urlencode($fechaInicio) ?>&hasta=<?= urlencode($fechaFin) ?>" class="tab-btn <?= $tab === 'categorias' ? 'active' : '' ?>">
            <span>🏷️</span> Ventas por Categoría
        </a>
        <a href="reportes.php?tab=productos&desde=<?= urlencode($fechaInicio) ?>&hasta=<?= urlencode($fechaFin) ?>" class="tab-btn <?= $tab === 'productos' ? 'active' : '' ?>">
            <span>🍔</span> Top Productos
        </a>
        <a href="reportes.php?tab=calificaciones&desde=<?= urlencode($fechaInicio) ?>&hasta=<?= urlencode($fechaFin) ?>" class="tab-btn <?= $tab === 'calificaciones' ? 'active' : '' ?>">
            <span>⭐</span> Calificaciones
        </a>
        <a href="reportes.php?tab=domiciliarios&desde=<?= urlencode($fechaInicio) ?>&hasta=<?= urlencode($fechaFin) ?>" class="tab-btn <?= $tab === 'domiciliarios' ? 'active' : '' ?>">
            <span>🛵</span> Domiciliarios & Liquidación
        </a>
        <a href="reportes.php?tab=clientes&desde=<?= urlencode($fechaInicio) ?>&hasta=<?= urlencode($fechaFin) ?>" class="tab-btn <?= $tab === 'clientes' ? 'active' : '' ?>">
            <span>👥</span> Clientes y Fidelidad
        </a>
        <a href="reportes.php?tab=inventario&desde=<?= urlencode($fechaInicio) ?>&hasta=<?= urlencode($fechaFin) ?>" class="tab-btn <?= $tab === 'inventario' ? 'active' : '' ?>">
            <span>📦</span> Consumo de Insumos
        </a>
    </nav>

    <!-- =================================================================== -->
    <!-- TAB 1: REPORTE DE VENTAS -->
    <!-- =================================================================== -->
    <?php if ($tab === 'ventas'): 
        $r = $ventasData['resumen'];
        $rAnt = $ventasAntData['resumen'];
        $diffVentasPct = null;
        if ($rAnt['total_ventas'] > 0) {
            $diffVentasPct = round((($r['total_ventas'] - $rAnt['total_ventas']) / $rAnt['total_ventas']) * 100, 1);
        }
    ?>
        <!-- Alerta de Pagos Pendientes de Devolución -->
        <?php if (!empty($devolucionesPendientes)): ?>
            <div class="alert-devoluciones">
                <div>
                    <strong style="color: #ffab00; font-size: 1rem; display: block;">⚠️ Pagos Pendientes de Devolución (<?= count($devolucionesPendientes) ?>)</strong>
                    <span style="font-size: 0.85rem; color: var(--text-muted); display: block; margin-top: 3px;">
                        Existen pedidos cancelados o rechazados con dinero transferido que aún no ha sido devuelto al cliente. <em>Estos montos NO están incluidos en las ventas entregadas.</em>
                    </span>
                </div>
                <div>
                    <button type="button" class="btn btn-secondary" onclick="toggleDevolucionesModal()" style="font-size: 0.82rem; padding: 8px 14px;">
                        Ver Detalle de Devoluciones
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Tarjetas KPI -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-header">
                    <span>Total Ventas</span>
                    <span>📈</span>
                </div>
                <div class="kpi-value">$<?= number_format($r['total_ventas'], 2) ?></div>
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px; line-height: 1.4;">
                    <div>Ventas de productos: <strong style="color: white;">$<?= number_format($r['subtotal_productos'] ?? $r['total_ventas'], 2) ?></strong></div>
                    <?php if (!empty($r['domicilios_historicos']) && (float)$r['domicilios_historicos'] > 0): ?>
                        <div style="color: #f59e0b;">Domicilios cobrados (histórico): <strong>$<?= number_format($r['domicilios_historicos'], 2) ?></strong></div>
                    <?php endif; ?>
                </div>
                <div class="kpi-sub">
                    <?php if ($diffVentasPct !== null): ?>
                        <span class="<?= $diffVentasPct >= 0 ? 'badge-variation-pos' : 'badge-variation-neg' ?>">
                            <?= $diffVentasPct >= 0 ? '+' : '' ?><?= $diffVentasPct ?>%
                        </span> vs período anterior
                    <?php else: ?>
                        <span>Período anterior sin base</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-header">
                    <span>Pedidos Entregados</span>
                    <span>📦</span>
                </div>
                <div class="kpi-value"><?= number_format($r['cantidad_pedidos']) ?></div>
                <div class="kpi-sub">
                    <span>Ventas cerradas con éxito</span>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-header">
                    <span>Ticket Promedio</span>
                    <span>🏷️</span>
                </div>
                <div class="kpi-value">$<?= number_format($r['ticket_promedio'], 2) ?></div>
                <div class="kpi-sub">
                    <span>Por orden entregada</span>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-header">
                    <span>Efectivo vs Digital</span>
                    <span>💳</span>
                </div>
                <div class="kpi-value" style="font-size: 1.3rem;">
                    $<?= number_format($r['ventas_efectivo'], 0) ?> <span style="font-size: 0.8rem; color: var(--text-muted);">/</span> $<?= number_format($r['ventas_transferencia'], 0) ?>
                </div>
                <div class="kpi-sub">
                    <span>💵 <?= $r['pedidos_efectivo'] ?> efec. | 📲 <?= $r['pedidos_transferencia'] ?> trans.</span>
                </div>
            </div>
        </div>

        <!-- Gráfico de Ventas Diarias -->
        <div class="report-card">
            <div class="report-card-title">
                <span>Evolución de Ventas por Día</span>
                <span style="font-size: 0.85rem; color: var(--text-muted); font-weight: normal;">
                    <?= htmlspecialchars($fechaInicio) ?> al <?= htmlspecialchars($fechaFin) ?>
                </span>
            </div>
            <?php if (empty($ventasData['por_dia'])): ?>
                <div class="empty-state">
                    <div class="empty-icon">📊</div>
                    <h3>No hay ventas registradas en este período</h3>
                    <p>Prueba ampliando el rango de fechas en los filtros superiores.</p>
                </div>
            <?php else: ?>
                <div style="height: 320px; width: 100%;">
                    <canvas id="ventasChart"></canvas>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tabla Detallada de Pedidos Entregados -->
        <div class="report-card">
            <div class="report-card-title">
                <span>Detalle de Órdenes Vendidas (<?= count($ventasData['pedidos']) ?>)</span>
            </div>
            <?php if (empty($ventasData['pedidos'])): ?>
                <div class="empty-state">
                    <div class="empty-icon">🧾</div>
                    <h3>Sin transacciones para mostrar</h3>
                </div>
            <?php else: ?>
                <div class="data-table-responsive">
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th>Pedido</th>
                                <th>Fecha Entrega (Venta)</th>
                                <th>Cliente</th>
                                <th>Zona</th>
                                <th>Método</th>
                                <th>Subtotal</th>
                                <th>Domicilio</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ventasData['pedidos'] as $p): ?>
                                <tr>
                                    <td><strong>#<?= str_pad($p['id_pedido'], 5, '0', STR_PAD_LEFT) ?></strong></td>
                                    <td><?= htmlspecialchars($p['fecha_venta']) ?></td>
                                    <td><?= htmlspecialchars($p['cliente_nombre']) ?></td>
                                    <td><?= htmlspecialchars($p['nombre_zona'] ?? 'Directa') ?></td>
                                    <td>
                                        <span class="badge" style="background: <?= strtoupper($p['metodo_pago']) === 'EFECTIVO' ? 'rgba(16, 185, 129, 0.2)' : 'rgba(59, 130, 246, 0.2)' ?>; color: <?= strtoupper($p['metodo_pago']) === 'EFECTIVO' ? '#2ed573' : '#60a5fa' ?>;">
                                            <?= htmlspecialchars($p['metodo_pago']) ?>
                                        </span>
                                    </td>
                                    <td>$<?= number_format($p['subtotal'], 2) ?></td>
                                    <td><?= ((float)$p['costo_domicilio'] > 0) ? '$' . number_format($p['costo_domicilio'], 2) : '<span style="color:#22c55e;font-weight:600;">Gratis</span>' ?></td>
                                    <td><strong>$<?= number_format($p['total'], 2) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($ventasData['por_dia'])): ?>
        <script>
            (function() {
                var ctx = document.getElementById('ventasChart').getContext('2d');
                var porDia = <?= json_encode($ventasData['por_dia']) ?>;
                var labels = porDia.map(function(d) { return d.fecha; });
                var dataEfectivo = porDia.map(function(d) { return d.efectivo; });
                var dataTrans = porDia.map(function(d) { return d.transferencia; });

                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Efectivo ($)',
                                data: dataEfectivo,
                                backgroundColor: '#2ed573',
                                borderRadius: 4
                            },
                            {
                                label: 'Transferencia ($)',
                                data: dataTrans,
                                backgroundColor: '#ff8a2a',
                                borderRadius: 4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: { stacked: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#bfa593' } },
                            y: { stacked: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#bfa593' } }
                        },
                        plugins: {
                            legend: { labels: { color: '#F3ECDA' } }
                        }
                    }
                });
            })();
        </script>
        <?php endif; ?>

    <!-- =================================================================== -->
    <!-- TAB: VENTAS POR CATEGORÍA -->
    <!-- =================================================================== -->
    <?php elseif ($tab === 'categorias'): 
        $rCat = $categoriasData['resumen'] ?? ['total_recaudado' => 0, 'total_unidades' => 0, 'total_categorias' => 0];
        $topCat = !empty($categoriasData['categorias']) ? $categoriasData['categorias'][0] : null;
    ?>
        <!-- Tarjetas KPI Categorías -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-header">
                    <span>Recaudo por Categorías</span>
                    <span>🏷️</span>
                </div>
                <div class="kpi-value">$<?= number_format($rCat['total_recaudado'], 2) ?></div>
                <div class="kpi-sub"><span>Ventas netas entregadas</span></div>
            </div>

            <div class="kpi-card">
                <div class="kpi-header">
                    <span>Unidades Vendidas</span>
                    <span>📦</span>
                </div>
                <div class="kpi-value"><?= number_format($rCat['total_unidades']) ?></div>
                <div class="kpi-sub"><span>En todas las categorías</span></div>
            </div>

            <div class="kpi-card">
                <div class="kpi-header">
                    <span>Categorías Activas</span>
                    <span>📊</span>
                </div>
                <div class="kpi-value"><?= number_format($rCat['total_categorias']) ?></div>
                <div class="kpi-sub"><span>Con ventas en el período</span></div>
            </div>

            <div class="kpi-card">
                <div class="kpi-header">
                    <span>Categoría Líder</span>
                    <span>🏆</span>
                </div>
                <div class="kpi-value" style="font-size: 1.3rem;">
                    <?= htmlspecialchars($topCat['categoria_nombre'] ?? 'N/A') ?>
                </div>
                <div class="kpi-sub">
                    <span class="badge-variation-pos"><?= $topCat ? $topCat['porcentaje_ventas'] . '%' : '0%' ?></span> del recaudo
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 20px;">
            <!-- Gráfico de Dona -->
            <div class="report-card">
                <div class="report-card-title">Distribución de Ventas por Categoría</div>
                <div style="height: 280px; position: relative;">
                    <canvas id="chartCategorias"></canvas>
                </div>
            </div>

            <!-- Tabla Resumen -->
            <div class="report-card">
                <div class="report-card-title">Participación de Mercado</div>
                <div class="data-table-responsive">
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th>Categoría</th>
                                <th style="text-align: center;">Uds</th>
                                <th style="text-align: right;">Total</th>
                                <th style="text-align: center;">% Ventas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categoriasData['categorias'])): ?>
                                <tr><td colspan="4" class="text-center" style="padding: 20px;">Sin ventas registradas en el período.</td></tr>
                            <?php else: foreach ($categoriasData['categorias'] as $cat): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($cat['categoria_nombre']) ?></strong></td>
                                    <td style="text-align: center;"><?= number_format($cat['total_unidades']) ?></td>
                                    <td style="text-align: right; font-weight: 700; color: #2ed573;">$<?= number_format($cat['total_recaudado'], 2) ?></td>
                                    <td style="text-align: center;">
                                        <div style="display: flex; align-items: center; gap: 6px; justify-content: center;">
                                            <div style="width: 50px; background: rgba(255,255,255,0.1); height: 6px; border-radius: 3px; overflow: hidden;">
                                                <div style="width: <?= min(100, $cat['porcentaje_ventas']) ?>%; background: #ff8a2a; height: 100%;"></div>
                                            </div>
                                            <span style="font-weight: 700;"><?= $cat['porcentaje_ventas'] ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <script>
            (function() {
                var catData = <?= json_encode($categoriasData['categorias'] ?? []) ?>;
                if (!catData || catData.length === 0) return;
                var ctx = document.getElementById('chartCategorias').getContext('2d');
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: catData.map(function(c) { return c.categoria_nombre; }),
                        datasets: [{
                            data: catData.map(function(c) { return c.total_recaudado; }),
                            backgroundColor: ['#ff8a2a', '#2ed573', '#e74c3c', '#3498db', '#9b59b6', '#f1c40f', '#1abc9c', '#e67e22'],
                            borderWidth: 2,
                            borderColor: '#181212'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom', labels: { color: '#F3ECDA', boxWidth: 12 } }
                        }
                    }
                });
            })();
        </script>

    <!-- =================================================================== -->
    <!-- TAB 2: TOP PRODUCTOS -->
    <!-- =================================================================== -->
    <?php elseif ($tab === 'productos'): ?>
        <div class="report-card">
            <div class="report-card-title">
                <span>Ranking de Productos Más Vendidos</span>
            </div>
            <?php if (empty($topProductosData)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🍔</div>
                    <h3>No hay datos de productos en este período</h3>
                </div>
            <?php else: ?>
                <div class="data-table-responsive">
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th># Ranking</th>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th style="text-align: center;">Unidades Vendidas</th>
                                <th style="text-align: right;">Ingresos Totales</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $pos = 1; foreach ($topProductosData as $pr): ?>
                                <tr>
                                    <td>
                                        <span style="font-weight: 800; color: <?= $pos === 1 ? '#ffc107' : ($pos === 2 ? '#ced6e0' : ($pos === 3 ? '#cd7f32' : 'var(--text-muted)')) ?>;">
                                            #<?= $pos++ ?>
                                        </span>
                                    </td>
                                    <td><strong><?= htmlspecialchars($pr['nombre_producto']) ?></strong></td>
                                    <td><?= htmlspecialchars($pr['categoria'] ?? 'General') ?></td>
                                    <td style="text-align: center; font-weight: 700;"><?= number_format($pr['total_unidades']) ?> uds</td>
                                    <td style="text-align: right; font-weight: 800; color: #2ed573;">
                                        $<?= number_format($pr['total_recaudado'], 2) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    <!-- =================================================================== -->
    <!-- TAB 3: DOMICILIARIOS Y LIQUIDACIÓN -->
    <!-- =================================================================== -->
    <?php elseif ($tab === 'domiciliarios'): ?>
        <!-- Rendimiento de Domiciliarios -->
        <div class="report-card">
            <div class="report-card-title">
                <span>Rendimiento y Entregas por Domiciliario</span>
            </div>
            <?php if (empty($domiciliariosData)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🛵</div>
                    <h3>Sin registros de entregas para este rango</h3>
                </div>
            <?php else: ?>
                <div class="data-table-responsive">
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th>Domiciliario</th>
                                <th>Vehículo / Placa</th>
                                <th style="text-align: center;">Entregas</th>
                                <th style="text-align: right;">Efectivo Cobrado</th>
                                <th style="text-align: right;">Digital Cobrado</th>
                                <th style="text-align: right;">Total Recaudado</th>
                                <th style="text-align: center;">Calificación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($domiciliariosData as $d): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($d['domiciliario_nombre']) ?></strong>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($d['domiciliario_telefono']) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars(($d['tipo_vehiculo'] ?? 'N/A') . ' ' . ($d['placa'] ?? '')) ?></td>
                                    <td style="text-align: center; font-weight: 700;"><?= $d['total_entregas'] ?></td>
                                    <td style="text-align: right; color: #2ed573; font-weight: 700;">$<?= number_format($d['efectivo_cobrado'], 2) ?></td>
                                    <td style="text-align: right; color: #60a5fa;">$<?= number_format($d['digital_cobrado'], 2) ?></td>
                                    <td style="text-align: right; font-weight: 800;">$<?= number_format($d['total_recaudado'], 2) ?></td>
                                    <td style="text-align: center;">
                                        ★ <?= number_format($d['calificacion_promedio'], 1) ?>
                                        <span style="font-size: 0.75rem; color: var(--text-muted);">(<?= $d['total_calificaciones'] ?>)</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- MÓDULO DE LIQUIDACIÓN DE TURNO -->
        <div class="report-card" style="border-color: rgba(255, 138, 42, 0.4);">
            <div class="report-card-title">
                <span style="color: #ff8a2a;">⚡ Módulo de Liquidación de Turno en Caja</span>
                <span style="font-size: 0.82rem; color: var(--text-muted); font-weight: normal;">Cierre de caja transaccional</span>
            </div>

            <form method="POST" action="reportes.php" onsubmit="return confirm('¿Confirmas que recibiste el total en efectivo y deseas cerrar la liquidación de este turno?');">
                <input type="hidden" name="action" value="liquidar_turno">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr 2fr auto; gap: 14px; align-items: flex-end; margin-bottom: 20px;">
                    <div>
                        <label style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-bottom: 6px; font-weight: 600;">Domiciliario:</label>
                        <select name="id_domiciliario" id="select-domi-liq" class="form-control" required style="background: var(--surface); color: white; border: 1px solid var(--border); padding: 9px; border-radius: 8px; width: 100%;">
                            <option value="">-- Seleccionar --</option>
                            <?php foreach ($domiciliariosList as $domi): ?>
                                <option value="<?= $domi['id_usuario'] ?>"><?= htmlspecialchars($domi['nombre']) ?> (<?= htmlspecialchars($domi['tipo_vehiculo'] ?? 'Moto') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-bottom: 6px; font-weight: 600;">Fecha y Hora de Corte:</label>
                        <input type="text" name="fecha_corte" value="<?= date('Y-m-d H:i:s') ?>" class="form-control" style="background: var(--surface); color: white; border: 1px solid var(--border); padding: 9px; border-radius: 8px; width: 100%;" readonly>
                    </div>

                    <div>
                        <label style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-bottom: 6px; font-weight: 600;">Observaciones del Turno:</label>
                        <input type="text" name="observaciones" placeholder="Ej. Turno tarde / entrega sin novedades..." class="form-control" style="background: var(--surface); color: white; border: 1px solid var(--border); padding: 9px; border-radius: 8px; width: 100%;">
                    </div>

                    <div>
                        <button type="submit" class="btn btn-primary" style="padding: 10px 22px; font-weight: 700; white-space: nowrap;">
                            ✓ Liquidar Turno
                        </button>
                    </div>
                </div>
            </form>

            <!-- Historial de Liquidaciones -->
            <div style="margin-top: 30px;">
                <h4 style="font-size: 1rem; color: var(--text-main); margin-bottom: 12px; font-weight: 700;">Historial Reciente de Liquidaciones</h4>
                <?php if (empty($liquidacionesData)): ?>
                    <p style="font-size: 0.85rem; color: var(--text-muted);">No hay liquidaciones registradas en el sistema.</p>
                <?php else: ?>
                    <div class="data-table-responsive">
                        <table class="styled-table">
                            <thead>
                                <tr>
                                    <th># Liq</th>
                                    <th>Fecha y Hora</th>
                                    <th>Domiciliario</th>
                                    <th>Liquidado Por</th>
                                    <th>Pedidos</th>
                                    <th style="text-align: right;">Total Efectivo</th>
                                    <th>Estado</th>
                                    <th style="text-align: right;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($liquidacionesData as $l): ?>
                                    <tr>
                                        <td><strong>#<?= $l['id_liquidacion'] ?></strong></td>
                                        <td><?= htmlspecialchars($l['fecha_liquidacion']) ?></td>
                                        <td><strong><?= htmlspecialchars($l['domiciliario_nombre']) ?></strong></td>
                                        <td><?= htmlspecialchars($l['admin_nombre']) ?></td>
                                        <td><?= $l['total_pedidos_liquidados'] ?> pedidos</td>
                                        <td style="text-align: right; font-weight: 800; color: <?= $l['anulado'] ? 'var(--text-muted)' : '#2ed573' ?>;">
                                            $<?= number_format($l['total_efectivo'], 2) ?>
                                        </td>
                                        <td>
                                            <?php if ($l['anulado']): ?>
                                                <span class="badge" style="background: rgba(220, 38, 38, 0.2); color: #ff6b6b;" title="Motivo: <?= htmlspecialchars($l['motivo_anulacion'] ?? '') ?>">
                                                    ANULADA
                                                </span>
                                            <?php else: ?>
                                                <span class="badge" style="background: rgba(16, 185, 129, 0.2); color: #2ed573;">
                                                    CONFIRMADA
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: right;">
                                            <?php if (!$l['anulado']): ?>
                                                <button type="button" class="btn btn-secondary" style="font-size: 0.75rem; padding: 4px 10px;" onclick="abrirModalAnular(<?= $l['id_liquidacion'] ?>)">
                                                    Anular
                                                </button>
                                            <?php else: ?>
                                                <span style="font-size: 0.75rem; color: var(--text-muted);">Por: <?= htmlspecialchars($l['anulador_nombre'] ?? 'Admin') ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Modal Anular Liquidación -->
        <div id="modal-anular-liq" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 9999; align-items: center; justify-content: center;">
            <div style="background: var(--surface-card); border: 1px solid var(--border); border-radius: 16px; width: 90%; max-width: 480px; padding: 24px;">
                <h3 style="font-family: var(--font-title); color: #ff6b6b; margin-bottom: 12px;">Anular Liquidación de Turno</h3>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 16px;">
                    Al anular una liquidación, los pedidos asociados volverán a quedar pendientes para que puedan ser recalculados o reliquidados.
                </p>
                <form method="POST" action="reportes.php">
                    <input type="hidden" name="action" value="anular_liquidacion">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="id_liquidacion" id="modal-id-liq" value="">

                    <div style="margin-bottom: 16px;">
                        <label style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-bottom: 6px;">Motivo obligatorio de anulación:</label>
                        <textarea name="motivo_anulacion" class="form-control" rows="3" required placeholder="Explica la razón de anulación (error en conteo, etc.)..." style="width: 100%; background: var(--surface); color: white; border: 1px solid var(--border); border-radius: 8px; padding: 10px;"></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" class="btn btn-secondary" onclick="cerrarModalAnular()">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Confirmar Anulación</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function abrirModalAnular(id) {
                document.getElementById('modal-id-liq').value = id;
                var m = document.getElementById('modal-anular-liq');
                m.style.display = 'flex';
            }
            function cerrarModalAnular() {
                var m = document.getElementById('modal-anular-liq');
                m.style.display = 'none';
            }
        </script>

    <!-- =================================================================== -->
    <!-- TAB: CALIFICACIONES Y RESEÑAS -->
    <!-- =================================================================== -->
    <?php elseif ($tab === 'calificaciones'): 
        $rCal = $calificacionesData['resumen'] ?? ['promedio' => 0.0, 'total_calificaciones' => 0, 'distribucion' => []];
        $prom = (float)($rCal['promedio'] ?? 0.0);
        $totalCals = (int)($rCal['total_calificaciones'] ?? 0);
        $dist = $rCal['distribucion'] ?? [];
        $positivas = (($dist[5]['cantidad'] ?? 0) + ($dist[4]['cantidad'] ?? 0));
        $pctPositivas = $totalCals > 0 ? round(($positivas / $totalCals) * 100, 1) : 0.0;
    ?>
        <!-- Tarjetas KPI Calificaciones -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-header">
                    <span>Calificación Promedio</span>
                    <span>⭐</span>
                </div>
                <div class="kpi-value" style="color: #ffc107;">
                    <?= number_format($prom, 2) ?> <span style="font-size: 1rem; color: var(--text-muted);">/ 5.0</span>
                </div>
                <div class="kpi-sub">
                    <span style="color: #ffc107;"><?= str_repeat('★', (int)round($prom)) . str_repeat('☆', max(0, 5 - (int)round($prom))) ?></span>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-header">
                    <span>Total Evaluaciones</span>
                    <span>📝</span>
                </div>
                <div class="kpi-value"><?= number_format($totalCals) ?></div>
                <div class="kpi-sub"><span>Clientes que evaluaron</span></div>
            </div>

            <div class="kpi-card">
                <div class="kpi-header">
                    <span>Satisfacción (4★ y 5★)</span>
                    <span>😊</span>
                </div>
                <div class="kpi-value" style="color: #2ed573;"><?= $pctPositivas ?>%</div>
                <div class="kpi-sub"><span><?= $positivas ?> de <?= $totalCals ?> opiniones positivas</span></div>
            </div>

            <div class="kpi-card">
                <div class="kpi-header">
                    <span>Filtro de Puntaje</span>
                    <span>🔍</span>
                </div>
                <div class="kpi-value" style="font-size: 1.3rem;">
                    <?= $puntajeFiltro !== null ? $puntajeFiltro . ' Estrellas' : 'Todas (1-5★)' ?>
                </div>
                <div class="kpi-sub">
                    <span><?= count($calificacionesData['comentarios'] ?? []) ?> comentario(s) filtrado(s)</span>
                </div>
            </div>
        </div>

        <!-- Distribución y Barras de 1 a 5 Estrellas -->
        <div class="report-card">
            <div class="report-card-title">
                <span>Distribución Detallada de Calificaciones (1 a 5 Estrellas)</span>
            </div>
            <div style="display: flex; flex-direction: column; gap: 12px; max-width: 650px;">
                <?php for ($s = 5; $s >= 1; $s--): 
                    $rowS = $dist[$s] ?? ['cantidad' => 0, 'porcentaje' => 0.0];
                ?>
                    <div style="display: flex; align-items: center; gap: 14px;">
                        <span style="width: 80px; font-weight: 700; color: #ffc107; font-size: 0.9rem;">
                            <?= $s ?> Estrellas
                        </span>
                        <div style="flex: 1; background: rgba(255,255,255,0.06); height: 12px; border-radius: 6px; overflow: hidden;">
                            <div style="width: <?= min(100, $rowS['porcentaje']) ?>%; background: linear-gradient(90deg, #ff8a2a, #ffc107); height: 100%; border-radius: 6px; transition: width 0.5s ease;"></div>
                        </div>
                        <span style="width: 45px; text-align: right; font-weight: 700; color: var(--text-main); font-size: 0.85rem;">
                            <?= $rowS['cantidad'] ?>
                        </span>
                        <span style="width: 55px; text-align: right; color: var(--text-muted); font-size: 0.8rem;">
                            (<?= $rowS['porcentaje'] ?>%)
                        </span>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Lista de Comentarios de Clientes con Escapado Estricto de Seguridad -->
        <div class="report-card">
            <div class="report-card-title">
                <span>Comentarios de Clientes (Escapados contra XSS/Inyección)</span>
                <span style="font-size: 0.8rem; font-weight: normal; color: var(--text-muted);">
                    <?= $puntajeFiltro !== null ? "Filtro activo: $puntajeFiltro estrellas" : "Mostrando todos los puntajes" ?>
                </span>
            </div>

            <?php if (empty($calificacionesData['comentarios'])): ?>
                <div class="empty-state">
                    <div class="empty-icon">⭐</div>
                    <h3>No hay comentarios de calificaciones para el rango o filtro seleccionado</h3>
                    <p style="color: var(--text-muted); font-size: 0.85rem;">Prueba ampliando el rango de fechas o seleccionando 'Todas las estrellas'.</p>
                </div>
            <?php else: ?>
                <div class="data-table-responsive">
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th style="width: 10%;">Pedido</th>
                                <th style="width: 15%;">Fecha</th>
                                <th style="width: 20%;">Cliente</th>
                                <th style="width: 15%; text-align: center;">Puntaje</th>
                                <th style="width: 40%;">Comentario del Cliente (Texto Seguro)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($calificacionesData['comentarios'] as $c): ?>
                                <tr>
                                    <td><strong>#<?= str_pad($c['id_pedido'], 5, '0', STR_PAD_LEFT) ?></strong></td>
                                    <td style="color: var(--text-muted); font-size: 0.85rem;"><?= htmlspecialchars($c['fecha']) ?></td>
                                    <td><strong><?= htmlspecialchars($c['cliente_nombre']) ?></strong></td>
                                    <td style="text-align: center;">
                                        <span style="color: #ffc107; font-weight: 800; font-size: 1rem;">
                                            <?= str_repeat('★', (int)$c['puntuacion']) ?>
                                        </span>
                                        <span style="color: var(--text-muted); font-size: 0.8rem; margin-left: 4px;">
                                            (<?= $c['puntuacion'] ?>)
                                        </span>
                                    </td>
                                    <td style="font-size: 0.9rem; color: #f3ecda; word-break: break-word;">
                                        <?= htmlspecialchars((string)($c['comentario'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    <!-- =================================================================== -->
    <!-- TAB: CLIENTES Y FRECUENCIA DE COMPRA (DATOS PROTEGIDOS Y AUDITADOS) -->
    <!-- =================================================================== -->
    <?php elseif ($tab === 'clientes'): ?>
        <div class="report-card">
            <div class="report-card-title">
                <span>Comportamiento y Frecuencia de Compra de Clientes</span>
                <span class="badge" style="background: rgba(52, 152, 219, 0.15); color: #3498db; font-size: 0.75rem; border: 1px solid rgba(52, 152, 219, 0.3);">
                    🔒 Solo Administrador • Consulta Auditada • Teléfonos y Direcciones Omitidos
                </span>
            </div>
            <?php if (empty($clientesData)): ?>
                <div class="empty-state">
                    <div class="empty-icon">👥</div>
                    <h3>Sin actividad de clientes en este período</h3>
                </div>
            <?php else: ?>
                <div class="data-table-responsive">
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th style="width: 15%;">ID Cliente</th>
                                <th style="width: 35%;">Cliente</th>
                                <th style="text-align: center;">Órdenes Entregadas</th>
                                <th style="text-align: right;">Gasto Total Acumulado</th>
                                <th style="text-align: right;">Ticket Promedio</th>
                                <th>Última Compra</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clientesData as $c): ?>
                                <tr>
                                    <td><strong>#<?= htmlspecialchars($c['id_cliente']) ?></strong></td>
                                    <td><strong><?= htmlspecialchars($c['cliente_nombre']) ?></strong></td>
                                    <td style="text-align: center; font-weight: 700;"><?= $c['total_pedidos'] ?></td>
                                    <td style="text-align: right; font-weight: 800; color: #2ed573;">
                                        $<?= number_format($c['total_comprado'], 2) ?>
                                    </td>
                                    <td style="text-align: right;">$<?= number_format($c['ticket_promedio'], 2) ?></td>
                                    <td><?= htmlspecialchars($c['ultima_compra']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    <!-- =================================================================== -->
    <!-- TAB 5: CONSUMO DE INSUMOS -->
    <!-- =================================================================== -->
    <?php elseif ($tab === 'inventario'): ?>
        <div class="report-card">
            <div class="report-card-title">
                <span>Consumo de Insumos vs Mermas y Ajustes</span>
            </div>
            <?php if (empty($inventarioData)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📦</div>
                    <h3>Sin movimientos de inventario en este período</h3>
                </div>
            <?php else: ?>
                <div class="data-table-responsive">
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th>Insumo</th>
                                <th>Unidad</th>
                                <th>Stock Actual</th>
                                <th style="text-align: right;">Consumo Recetas</th>
                                <th style="text-align: right;">Mermas / Ajustes</th>
                                <th style="text-align: right;">Entradas</th>
                                <th>Alerta Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inventarioData as $inv): 
                                $esBajo = ($inv['stock_actual'] <= $inv['stock_minimo']);
                            ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($inv['nombre_insumo']) ?></strong></td>
                                    <td><?= htmlspecialchars($inv['unidad_medida']) ?></td>
                                    <td><strong><?= number_format($inv['stock_actual'], 2) ?></strong></td>
                                    <td style="text-align: right; color: #ff8a2a; font-weight: 700;">
                                        <?= number_format($inv['salidas_receta'], 2) ?>
                                    </td>
                                    <td style="text-align: right; color: #ff6b6b;">
                                        <?= number_format($inv['salidas_manuales'] + $inv['faltantes'], 2) ?>
                                    </td>
                                    <td style="text-align: right; color: #2ed573;">
                                        <?= number_format($inv['entradas'], 2) ?>
                                    </td>
                                    <td>
                                        <?php if ($esBajo): ?>
                                            <span class="badge" style="background: rgba(220, 38, 38, 0.2); color: #ff6b6b;">
                                                ⚠️ Bajo Mínimo (<?= $inv['stock_minimo'] ?>)
                                            </span>
                                        <?php else: ?>
                                            <span class="badge" style="background: rgba(16, 185, 129, 0.2); color: #2ed573;">
                                                ✓ Normal
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    <?php endif; ?>

</div>
