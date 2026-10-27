<?php
/**
 * Vista de Mis Liquidaciones - Panel Domiciliario
 */
?>
<div class="domi-panel-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
    <div>
        <h3 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: white;">Mis Turnos y Liquidaciones de Efectivo</h3>
        <p style="color: var(--text-muted); font-size: 0.88rem; margin-top: 4px;">
            Consulta el estado de liquidación de tus turnos de entrega y el efectivo recaudado.
        </p>
    </div>
    <div style="font-size: 0.82rem; background: rgba(234, 88, 12, 0.15); border: 1px solid rgba(234, 88, 12, 0.3); color: #ff8a2a; padding: 6px 14px; border-radius: 20px; font-weight: 600;">
        🔒 Sesión Protegida: <?= htmlspecialchars($currentUser['name'] ?? 'Domiciliario') ?> (#<?= $idDomiciliario ?>)
    </div>
</div>

<!-- Resumen de Efectivo en Turno -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div class="card" style="margin-bottom: 0;">
        <div style="display: flex; justify-content: space-between; align-items: center; color: var(--text-muted); font-size: 0.82rem; font-weight: 700; text-transform: uppercase;">
            <span>Efectivo Pendiente por Liquidar</span>
            <span>💵</span>
        </div>
        <div style="font-size: 2rem; font-weight: 800; color: <?= $totalEfectivoPendiente > 0 ? '#ff8a2a' : '#2ed573' ?>; margin-top: 8px;">
            $<?= number_format($totalEfectivoPendiente, 2) ?>
        </div>
        <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 4px;">
            <?= count($pedidosPendientesEfectivo) ?> pedido(s) entregado(s) en efectivo pendientes de entrega al administrador.
        </div>
    </div>

    <div class="card" style="margin-bottom: 0;">
        <div style="display: flex; justify-content: space-between; align-items: center; color: var(--text-muted); font-size: 0.82rem; font-weight: 700; text-transform: uppercase;">
            <span>Historial de Liquidaciones</span>
            <span>📋</span>
        </div>
        <div style="font-size: 2rem; font-weight: 800; color: white; margin-top: 8px;">
            <?= count($liquidaciones) ?>
        </div>
        <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 4px;">
            Turnos liquidados y cerrados ante la administración.
        </div>
    </div>
</div>

<!-- Pedidos Pendientes de Liquidar en el Turno Actual -->
<?php if (!empty($pedidosPendientesEfectivo)): ?>
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
        <h4 style="font-size: 1.15rem; font-weight: 700; color: white; margin: 0;">Pedidos en Efectivo Pendientes de Liquidación</h4>
        <span class="badge" style="background: rgba(255, 138, 42, 0.15); color: #ff8a2a; border: 1px solid rgba(255, 138, 42, 0.3);">
            Turno Abierto
        </span>
    </div>
    <div class="data-table-responsive" style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
            <thead>
                <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.08); color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">
                    <th style="padding: 12px 10px;">ID Pedido</th>
                    <th style="padding: 12px 10px;">Cliente</th>
                    <th style="padding: 12px 10px;">Dirección</th>
                    <th style="padding: 12px 10px;">Fecha Entrega</th>
                    <th style="padding: 12px 10px; text-align: right;">Efectivo Cobrado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pedidosPendientesEfectivo as $p): ?>
                    <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.04);">
                        <td style="padding: 12px 10px;"><strong>#<?= str_pad($p['id_pedido'], 5, '0', STR_PAD_LEFT) ?></strong></td>
                        <td style="padding: 12px 10px;"><?= htmlspecialchars($p['cliente_nombre']) ?></td>
                        <td style="padding: 12px 10px; color: var(--text-muted); font-size: 0.85rem;"><?= htmlspecialchars($p['direccion'] ?? 'N/A') ?></td>
                        <td style="padding: 12px 10px; color: var(--text-muted);"><?= htmlspecialchars($p['fecha_entrega']) ?></td>
                        <td style="padding: 12px 10px; text-align: right; font-weight: 700; color: #ff8a2a;">$<?= number_format($p['total'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Historial de Turnos Liquidados -->
<div class="card">
    <h4 style="font-size: 1.15rem; font-weight: 700; color: white; margin-bottom: 18px;">Historial de Turnos Liquidados</h4>
    <?php if (empty($liquidaciones)): ?>
        <div style="text-align: center; padding: 40px; color: var(--text-muted);">
            <div style="font-size: 2.5rem; margin-bottom: 10px;">📦</div>
            <p style="font-size: 0.95rem;">Aún no tienes turnos liquidados registrados por la administración.</p>
        </div>
    <?php else: ?>
        <div class="data-table-responsive" style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
                <thead>
                    <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.08); color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">
                        <th style="padding: 12px 10px;"># Liq</th>
                        <th style="padding: 12px 10px;">Fecha y Hora</th>
                        <th style="padding: 12px 10px;">Liquidado Por</th>
                        <th style="padding: 12px 10px; text-align: center;">Pedidos</th>
                        <th style="padding: 12px 10px; text-align: right;">Total Entregado</th>
                        <th style="padding: 12px 10px; text-align: center;">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($liquidaciones as $l): ?>
                        <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.04);">
                            <td style="padding: 12px 10px;"><strong>#<?= $l['id_liquidacion'] ?></strong></td>
                            <td style="padding: 12px 10px; color: var(--text-muted);"><?= htmlspecialchars($l['fecha_liquidacion']) ?></td>
                            <td style="padding: 12px 10px;"><?= htmlspecialchars($l['admin_nombre']) ?></td>
                            <td style="padding: 12px 10px; text-align: center;"><?= $l['total_pedidos_liquidados'] ?> pedidos</td>
                            <td style="padding: 12px 10px; text-align: right; font-weight: 800; color: <?= $l['anulado'] ? 'var(--text-muted)' : '#2ed573' ?>;">
                                $<?= number_format($l['total_efectivo'], 2) ?>
                            </td>
                            <td style="padding: 12px 10px; text-align: center;">
                                <?php if ($l['anulado']): ?>
                                    <span class="badge" style="background: rgba(220, 38, 38, 0.2); color: #ff6b6b;" title="Motivo: <?= htmlspecialchars($l['motivo_anulacion'] ?? '') ?>">
                                        ANULADA
                                    </span>
                                <?php else: ?>
                                    <span class="badge" style="background: rgba(16, 185, 129, 0.2); color: #2ed573;">
                                        LIQUIDADO
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
