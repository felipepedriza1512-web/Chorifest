<style>
    .header-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }
    
    .table-container {
        overflow-x: auto;
    }
    
    .data-grid {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 12px;
        margin-top: -12px;
    }
    
    .data-grid th {
        padding: 0 10px;
        text-align: left;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 1px;
        border: none;
    }
    
    .data-grid td {
        padding: 12px 10px;
        background: var(--surface);
        vertical-align: middle;
        font-size: 0.92rem;
        border-top: 1px solid var(--border);
        border-bottom: 1px solid var(--border);
        transition: var(--transition);
    }
    
    .data-grid td:first-child {
        border-left: 1px solid var(--border);
        border-top-left-radius: 16px;
        border-bottom-left-radius: 16px;
    }
    
    .data-grid td:last-child {
        border-right: 1px solid var(--border);
        border-top-right-radius: 16px;
        border-bottom-right-radius: 16px;
    }

    .data-grid tr:hover td {
        background: var(--surface-hover);
        border-color: var(--border-highlight);
    }
    
    .client-cell {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .client-avatar {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        background: rgba(255,255,255,0.05);
        border: 1px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: var(--text-muted);
        font-size: 0.85rem;
    }
    .client-name {
        font-weight: 600;
        color: var(--text-main);
        display: block;
    }
    .client-phone {
        font-size: 0.78rem;
        color: var(--text-muted);
    }

    .badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.78rem;
        font-weight: 700;
        display: inline-block;
        white-space: nowrap;
    }
    
    .badge-primary { background: rgba(234, 88, 12, 0.15); color: var(--primary); border: 1px solid rgba(234, 88, 12, 0.3); }
    .badge-success { background: rgba(46, 213, 115, 0.15); color: #2ed573; border: 1px solid rgba(46, 213, 115, 0.3); }
    .badge-warning { background: rgba(255, 165, 2, 0.15); color: #ffa502; border: 1px solid rgba(255, 165, 2, 0.3); }
    .badge-info { background: rgba(52, 152, 219, 0.15); color: #3498db; border: 1px solid rgba(52, 152, 219, 0.3); }
    .badge-danger { background: rgba(231, 76, 60, 0.15); color: #e74c3c; border: 1px solid rgba(231, 76, 60, 0.3); }
    .badge-secondary { background: rgba(255, 255, 255, 0.05); color: #aaa; border: 1px solid rgba(255,255,255,0.1); }

    .btn-action-sm {
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 0.8rem;
        font-weight: 700;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: transform 0.2s, background 0.2s;
        text-decoration: none;
    }
    .btn-action-sm:hover { transform: translateY(-1px); }

    .btn-aceptar { background: rgba(46, 213, 115, 0.2); color: #2ed573; border: 1px solid #2ed573; }
    .btn-aceptar:hover { background: #2ed573; color: white; }

    .btn-rechazar { background: rgba(231, 76, 60, 0.2); color: #e74c3c; border: 1px solid #e74c3c; }
    .btn-rechazar:hover { background: #e74c3c; color: white; }

    .btn-asignar { background: rgba(234, 88, 12, 0.2); color: var(--primary); border: 1px solid var(--primary); }
    .btn-asignar:hover { background: var(--primary); color: white; }

    .form-select-sm {
        background: rgba(0,0,0,0.4);
        color: white;
        border: 1px solid var(--border);
        padding: 5px 8px;
        border-radius: 8px;
        font-size: 0.8rem;
    }
</style>

<div>
    <div class="header-actions">
        <div>
            <h3 style="font-size: 1.4rem; font-weight: 700; letter-spacing: -0.5px;">Panel de Administración - Flujo de Pedidos</h3>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 4px;">Revisa nuevos pedidos, aprueba/rechaza pagos y asigna domiciliarios.</p>
        </div>
    </div>

    <?php if (!empty($flashError)): ?>
        <div style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #f87171; padding: 12px 16px; border-radius: 12px; margin-bottom: 20px; font-weight: 600;">
            ⚠️ <?= htmlspecialchars($flashError) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($flashSuccess)): ?>
        <div style="background: rgba(34, 197, 94, 0.2); border: 1px solid #22c55e; color: #4ade80; padding: 12px 16px; border-radius: 12px; margin-bottom: 20px; font-weight: 600;">
            ✓ <?= htmlspecialchars($flashSuccess) ?>
        </div>
    <?php endif; ?>
    
    <div class="table-container">
        <table class="data-grid">
            <thead>
                <tr>
                    <th>Pedido</th>
                    <th>Cliente</th>
                    <th>Fecha & Hora</th>
                    <th>Total</th>
                    <th>Pago</th>
                    <th>Estado Actual</th>
                    <th>Domiciliario</th>
                    <th>Acción del Admin</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pedidos)): ?>
                    <tr>
                        <td colspan="8" style="text-align:center; padding:50px;">
                            <p style="color: var(--text-muted);">No hay pedidos en el sistema.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pedidos as $pedido): 
                        $estadoId = (int)$pedido['id_estado'];
                        $badgeClass = 'badge-secondary';
                        if ($estadoId === 1 || $estadoId === 8) $badgeClass = 'badge-warning'; // Recibido / Pendiente Verificación
                        if ($estadoId === 2) $badgeClass = 'badge-info'; // Aceptado
                        if ($estadoId === 3) $badgeClass = 'badge-primary'; // En preparación
                        if ($estadoId === 4) $badgeClass = 'badge-primary'; // Empacado
                        if ($estadoId === 5) $badgeClass = 'badge-warning'; // En camino
                        if ($estadoId === 6) $badgeClass = 'badge-success'; // Entregado
                        if ($estadoId === 7 || $estadoId === 10 || $estadoId === 9) $badgeClass = 'badge-danger'; // Cancelado / Rechazado / Pago Rechazado
                        if ($estadoId === 11) $badgeClass = 'badge-warning'; // Demorado
                        if ($estadoId === 12) $badgeClass = 'badge-danger'; // Problema (Bloqueado)
                    ?>
                        <tr>
                            <td>
                                <strong style="font-family: monospace; font-size: 1rem; color: white;">
                                    #<?= str_pad($pedido['id_pedido'], 5, '0', STR_PAD_LEFT) ?>
                                </strong>
                            </td>
                            <td>
                                <div class="client-cell">
                                    <div class="client-avatar">
                                        <?= strtoupper(substr(trim($pedido['cliente_nombre'] ?? 'C'), 0, 1)) ?>
                                    </div>
                                    <div class="client-info">
                                        <span class="client-name"><?= htmlspecialchars($pedido['cliente_nombre'] ?? 'Cliente') ?></span>
                                        <span class="client-phone"><?= htmlspecialchars($pedido['cliente_telefono'] ?? '') ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="color: white; font-weight: 500;"><?= date('H:i:s', strtotime($pedido['fecha'])) ?></div>
                                <div style="font-size: 0.78rem; color: var(--text-muted);"><?= date('d/m/Y', strtotime($pedido['fecha'])) ?></div>
                            </td>
                            <td>
                                <strong style="color: white;">$<?= number_format($pedido['total'], 0, ',', '.') ?></strong>
                            </td>
                            <td>
                                <span class="badge <?= ($pedido['estado_pago'] ?? '') === 'Verificado' ? 'badge-success' : (($pedido['estado_pago'] ?? '') === 'Rechazado' ? 'badge-danger' : 'badge-warning') ?>">
                                    <?= htmlspecialchars($pedido['metodo_pago'] ?? 'N/A') ?> 
                                    (<?= htmlspecialchars($pedido['estado_pago'] ?? 'Pend') ?>)
                                </span>
                                <?php if (!empty($pedido['comprobante'])): ?>
                                    <a href="<?= baseUrl('/controllers/admin/ver_comprobante.php?id=' . $pedido['id_pedido']) ?>" target="_blank" style="display: block; font-size: 0.75rem; color: #3498db; margin-top: 3px;">
                                        🖼️ Ver Comprobante
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $badgeClass ?>" <?= $estadoId === 12 ? 'style="background:#dc2626; color:#fff; font-weight:700; border: 1px solid #f87171;"' : '' ?>>
                                    <?= $estadoId === 12 ? '🚫 BLOQUEADO: ' : '' ?><?= htmlspecialchars($pedido['estado_nombre'] ?? 'Desconocido') ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($pedido['domiciliario_nombre'])): ?>
                                    <span style="font-weight: 600; color: #2ed573; font-size: 0.85rem;">
                                        🛵 <?= htmlspecialchars($pedido['domiciliario_nombre']) ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 0.8rem;">Sin asignar</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 8px; align-items: flex-start;">
                                    
                                    <!-- 1. Para pedidos en Recibido (1) o Pendiente de verificación (8): Botones ACEPTAR / RECHAZAR -->
                                    <?php if ($estadoId === 1 || $estadoId === 8): 
                                        $stockCheck = isset($inventarioModel) ? $inventarioModel->verificarStockParaPedido((int)$pedido['id_pedido']) : ['suficiente' => true, 'detalle_texto' => ''];
                                    ?>
                                        <?php if (!$stockCheck['suficiente']): ?>
                                            <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; border-radius: 8px; padding: 6px 10px; font-size: 0.75rem; color: #fca5a5; max-width: 280px; margin-bottom: 4px;">
                                                ⚠️ <strong>Stock insuficiente para:</strong> <?= htmlspecialchars($stockCheck['detalle_texto']) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div style="display: flex; gap: 6px;">
                                            <form method="POST" action="pedidos.php" style="margin:0;" <?= !$stockCheck['suficiente'] ? 'onsubmit="return confirm(\'⚠️ ATENCIÓN: Stock insuficiente para: ' . addslashes(htmlspecialchars($stockCheck['detalle_texto'])) . '\n\n¿Deseas aceptar el pedido de todas formas?\');"' : '' ?>>
                                                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                                <input type="hidden" name="action" value="aceptar_pedido">
                                                <input type="hidden" name="id_pedido" value="<?= $pedido['id_pedido'] ?>">
                                                <?php if (!$stockCheck['suficiente']): ?>
                                                    <input type="hidden" name="confirmado_sin_stock" value="1">
                                                <?php endif; ?>
                                                <button type="submit" class="btn-action-sm btn-aceptar" title="Aceptar pedido">
                                                    ✓ Aceptar
                                                </button>
                                            </form>

                                            <button type="button" class="btn-action-sm btn-rechazar" onclick="rechazarPedido(<?= $pedido['id_pedido'] ?>)">
                                                ✕ Rechazar
                                            </button>
                                        </div>

                                    <!-- 2. Para pedidos en Empacado (4) o En Camino (5): Asignar / Reasignar Domiciliario -->
                                    <?php elseif ($estadoId === 4 || $estadoId === 5): ?>
                                        <form method="POST" action="pedidos.php" style="margin:0; display:flex; gap:6px; align-items:center;">
                                            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                            <input type="hidden" name="action" value="<?= !empty($pedido['id_domiciliario']) ? 'reasignar_domiciliario' : 'asignar_domiciliario' ?>">
                                            <input type="hidden" name="id_pedido" value="<?= $pedido['id_pedido'] ?>">
                                            <input type="hidden" name="id_domiciliario_actual" value="<?= $pedido['id_domiciliario'] ?? 0 ?>">
                                            
                                            <select name="id_domiciliario" class="form-select-sm" required>
                                                <option value="">-- Domiciliario --</option>
                                                <?php foreach ($domiciliarios as $d): ?>
                                                    <option value="<?= $d['id_usuario'] ?>" <?= (int)($pedido['id_domiciliario'] ?? 0) === (int)$d['id_usuario'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($d['nombre']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn-action-sm btn-asignar">
                                                <?= !empty($pedido['id_domiciliario']) ? 'Reasignar' : 'Asignar' ?>
                                            </button>
                                        </form>

                                    <!-- Demás estados: selector manual opcional para emergencias admin -->
                                    <?php else: ?>
                                        <form method="POST" action="pedidos.php" style="margin:0; display:flex; gap:6px;">
                                            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="id_pedido" value="<?= $pedido['id_pedido'] ?>">
                                            <select name="id_estado" class="form-select-sm" style="font-size: 0.75rem;">
                                                <?php foreach ($estados as $est): ?>
                                                    <option value="<?= $est['id_estado'] ?>" <?= $estadoId == $est['id_estado'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($est['nombre']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn-action-sm btn-asignar" style="padding: 4px 8px;">Ok</button>
                                        </form>
                                    <?php endif; ?>

                                    <!-- Botón ver seguimiento / historial -->
                                    <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                                        <a href="<?= baseUrl('/controllers/cliente/seguimiento.php?id=' . $pedido['id_pedido']) ?>" target="_blank" style="font-size: 0.75rem; color: #ff8a2a; text-decoration: none;">
                                            📍 Ver Mapa y Historial
                                        </a>

                                        <?php if ($estadoId !== 7 && $estadoId !== 10): ?>
                                            <button type="button" class="btn-action-sm btn-rechazar" style="padding: 2px 7px; font-size: 0.72rem;" onclick="rechazarPedido(<?= $pedido['id_pedido'] ?>, <?= $pedido['stock_descontado'] ?? 0 ?>, <?= $pedido['stock_reintegrado'] ?? 0 ?>)">
                                                ✕ Cancelar
                                            </button>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Aviso de Descuento de Inventario Pendiente (Regla 4 del prompt) -->
                                    <?php if ($estadoId >= 4 && $estadoId !== 7 && (int)($pedido['stock_descontado'] ?? 0) === 0): ?>
                                        <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; border-radius: 8px; padding: 6px 10px; font-size: 0.75rem; color: #fca5a5; display: flex; flex-direction: column; gap: 4px; margin-top: 4px;">
                                            <span>⚠️ Descuento de inventario pendiente</span>
                                            <form method="POST" action="pedidos.php" style="margin: 0;">
                                                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                                <input type="hidden" name="action" value="reintentar_descuento">
                                                <input type="hidden" name="id_pedido" value="<?= $pedido['id_pedido'] ?>">
                                                <button type="submit" class="btn-action-sm" style="background: #ef4444; color: white; border: none; padding: 3px 8px; font-size: 0.72rem; border-radius: 6px; cursor: pointer;">
                                                    ↻ Reintentar descuento
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?>

                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Cancelar / Rechazar Pedido -->
<div id="modalCancelarPedido" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.75); backdrop-filter:blur(6px); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#18181b; border:1px solid rgba(239,68,68,0.4); border-radius:18px; padding:28px; width:90%; max-width:440px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.7); color:white;">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
            <div style="width:40px; height:40px; border-radius:12px; background:rgba(239,68,68,0.15); border:1px solid rgba(239,68,68,0.3); display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
                ⚠️
            </div>
            <div>
                <h3 id="modalCancelarTitulo" style="margin:0; font-size:1.2rem; font-weight:700; color:#f87171;">Cancelar Pedido</h3>
                <span id="modalCancelarSub" style="font-size:0.8rem; color:var(--text-muted);">El pedido será marcado como Cancelado (7) o Rechazado (10)</span>
            </div>
        </div>

        <form id="form-rechazar" method="POST" action="pedidos.php">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            <input type="hidden" name="action" value="rechazar_pedido">
            <input type="hidden" name="id_pedido" id="rechazar_id_pedido" value="">

            <div style="margin-bottom:14px;">
                <label style="display:block; font-size:0.85rem; font-weight:600; color:var(--text-muted); margin-bottom:6px;">
                    Motivo obligatorio de cancelación / rechazo:
                </label>
                <textarea name="motivo" id="rechazar_motivo" rows="3" required placeholder="Ej: Pago no coincide, cliente no responde, falta de insumo..." style="width:100%; box-sizing:border-box; background:rgba(0,0,0,0.4); border:1px solid rgba(255,255,255,0.15); border-radius:10px; color:white; padding:10px; font-size:0.9rem; resize:none;"></textarea>
                <small style="color:#ef4444; font-size:0.75rem; display:none;" id="rechazar_error">El motivo es obligatorio.</small>
            </div>

            <!-- Recuadro Condicional: ¿Reintegrar ingredientes al inventario? (Regla 3 del prompt) -->
            <div id="div_reintegrar_stock" style="margin-bottom: 16px; background: rgba(234, 88, 12, 0.12); border: 1px solid rgba(234, 88, 12, 0.3); border-radius: 10px; padding: 12px; display: none;">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 0.85rem; color: #fed7aa; font-weight: 600;">
                    <input type="checkbox" name="reintegrar_stock" value="1" id="chk_reintegrar_stock" style="width: 17px; height: 17px; accent-color: #ea580c; cursor: pointer;">
                    <span>¿Reintegrar ingredientes al inventario?</span>
                </label>
                <small style="display: block; color: var(--text-muted); font-size: 0.75rem; margin-top: 4px; margin-left: 27px;">
                    Se devolverá al stock exactamente lo que salió físicamente (SALIDA_RECETA).
                </small>
            </div>

            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" onclick="cerrarModalCancelar()" style="padding:9px 16px; border-radius:10px; background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.1); color:white; font-size:0.85rem; cursor:pointer;">
                    Volver
                </button>
                <button type="submit" onclick="return validarMotivoCancelacion()" style="padding:9px 18px; border-radius:10px; background:#ef4444; border:none; color:white; font-weight:700; font-size:0.85rem; cursor:pointer;">
                    Confirmar Cancelación
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function rechazarPedido(idPedido, descontado, reintegrado) {
    document.getElementById('rechazar_id_pedido').value = idPedido;
    document.getElementById('modalCancelarTitulo').innerText = 'Cancelar / Rechazar Pedido #' + idPedido;
    document.getElementById('rechazar_motivo').value = '';
    document.getElementById('rechazar_error').style.display = 'none';
    
    var divReint = document.getElementById('div_reintegrar_stock');
    var chkReint = document.getElementById('chk_reintegrar_stock');
    chkReint.checked = false; // Desmarcado por defecto (Regla 3)

    // Solo aparece si stock_descontado = 1 y aún no ha sido reintegrado
    if (parseInt(descontado) === 1 && parseInt(reintegrado) === 0) {
        divReint.style.display = 'block';
    } else {
        divReint.style.display = 'none';
    }

    var m = document.getElementById('modalCancelarPedido');
    m.style.display = 'flex';
}

function cerrarModalCancelar() {
    var m = document.getElementById('modalCancelarPedido');
    m.style.display = 'none';
}

function validarMotivoCancelacion() {
    var txt = document.getElementById('rechazar_motivo').value.trim();
    if (!txt) {
        document.getElementById('rechazar_error').style.display = 'block';
        return false;
    }
    return true;
}
</script>
