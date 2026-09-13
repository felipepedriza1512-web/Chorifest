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
        padding: 10px 10px;
        background: var(--surface);
        vertical-align: middle;
        font-size: 0.95rem;
        border-top: 1px solid var(--border);
        border-bottom: 1px solid var(--border);
        transition: var(--transition);
    }
    
    /* Rounded corners for first and last cells in a row */
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

    /* Hover effect for entire row */
    .data-grid tr:hover td {
        background: var(--surface-hover);
        border-color: var(--border-highlight);
        transform: scale(1.002);
    }
    
    /* Client Avatar */
    .client-cell {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .client-avatar {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: rgba(255,255,255,0.05);
        border: 1px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: var(--text-muted);
        font-size: 0.9rem;
    }
    .data-grid tr:hover .client-avatar {
        background: rgba(234, 88, 12, 0.1);
        color: var(--primary);
        border-color: rgba(234, 88, 12, 0.2);
    }
    .client-info {
        display: flex;
        flex-direction: column;
    }
    .client-name {
        font-weight: 600;
        color: var(--text-main);
    }
    .client-phone {
        font-size: 0.8rem;
        color: var(--text-muted);
    }

    /* Badges */
    .badge {
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 700;
        display: inline-block;
        white-space: nowrap;
        text-align: center;
        letter-spacing: 0.3px;
        box-shadow: 0 0 10px rgba(0,0,0,0.2);
    }
    
    /* Dynamic Badge Colors */
    .badge-primary { background: rgba(234, 88, 12, 0.15); color: var(--primary); border: 1px solid rgba(234, 88, 12, 0.3); }
    .badge-success { background: rgba(46, 213, 115, 0.15); color: #2ed573; border: 1px solid rgba(46, 213, 115, 0.3); }
    .badge-warning { background: rgba(255, 165, 2, 0.15); color: #ffa502; border: 1px solid rgba(255, 165, 2, 0.3); }
    .badge-info { background: rgba(52, 152, 219, 0.15); color: #3498db; border: 1px solid rgba(52, 152, 219, 0.3); }
    .badge-danger { background: rgba(231, 76, 60, 0.15); color: #e74c3c; border: 1px solid rgba(231, 76, 60, 0.3); }
    .badge-secondary { background: rgba(255, 255, 255, 0.05); color: #aaa; border: 1px solid rgba(255,255,255,0.1); }

    /* Forms & Inputs */
    .action-form {
        display: flex;
        gap: 12px;
        align-items: center;
    }
    
    .form-select {
        background: rgba(0,0,0,0.3);
        color: var(--text-main);
        border: 1px solid var(--border);
        padding: 10px 16px;
        border-radius: 12px;
        font-family: inherit;
        font-size: 0.9rem;
        outline: none;
        cursor: pointer;
        transition: var(--transition);
        appearance: none;
        background-image: url('data:image/svg+xml;utf8,<svg fill="%23a1a1aa" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>');
        background-repeat: no-repeat;
        background-position-x: 95%;
        background-position-y: 50%;
        padding-right: 30px;
    }
    
    .form-select:hover {
        border-color: var(--border-highlight);
    }
    
    .form-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.1);
    }
    
    .form-select option {
        background-color: var(--surface);
        color: var(--text-main);
    }
    
    .btn-update {
        background: linear-gradient(135deg, var(--primary), #fb923c);
        color: white;
        border: none;
        padding: 10px;
        border-radius: 12px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: var(--transition);
        box-shadow: 0 4px 15px var(--primary-glow);
    }
    .btn-update:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px var(--primary-glow);
    }
    .btn-update:active {
        transform: scale(0.95);
    }
</style>

<div>
    <div class="header-actions">
        <h3 style="font-size: 1.4rem; font-weight: 700; letter-spacing: -0.5px;">Registro de Pedidos</h3>
        <button class="btn btn-primary" style="display: flex; gap: 8px; align-items: center;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Nuevo Pedido Manual
        </button>
    </div>
    
    <div class="table-container">
        <table class="data-grid">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Fecha & Hora</th>
                    <th>Total</th>
                    <th>Método Pago</th>
                    <th>Estado Actual</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pedidos)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding:50px;">
                            <div style="opacity: 0.5; margin-bottom: 15px;">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
                            </div>
                            <p>No hay pedidos registrados en el sistema</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pedidos as $pedido): 
                        // Determine badge colors dynamically
                        $estadoId = (int)$pedido['id_estado'];
                        $badgeClass = 'badge-secondary';
                        if ($estadoId === 1) $badgeClass = 'badge-warning'; // Pendiente
                        if ($estadoId === 2 || $estadoId === 5) $badgeClass = 'badge-info'; // Confirmado / En camino
                        if ($estadoId === 3) $badgeClass = 'badge-primary'; // Preparacion
                        if ($estadoId === 4 || $estadoId === 6) $badgeClass = 'badge-success'; // Listo / Entregado
                        if ($estadoId === 7 || $estadoId === 12) $badgeClass = 'badge-danger'; // Cancelado / Problema
                    ?>
                        <tr>
                            <td>
                                <strong style="font-family: monospace; font-size: 1.1rem; color: var(--text-main);">
                                    #<?= str_pad($pedido['id_pedido'], 4, '0', STR_PAD_LEFT) ?>
                                </strong>
                            </td>
                            <td>
                                <div class="client-cell">
                                    <div class="client-avatar">
                                        <?= strtoupper(substr(trim($pedido['cliente_nombre']), 0, 1)) ?>
                                    </div>
                                    <div class="client-info">
                                        <span class="client-name"><?= htmlspecialchars($pedido['cliente_nombre']) ?></span>
                                        <span class="client-phone"><?= htmlspecialchars($pedido['cliente_telefono']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="color: var(--text-main); font-weight: 500;"><?= date('H:i', strtotime($pedido['fecha'])) ?></div>
                                <div style="font-size: 0.8rem; color: var(--text-muted);"><?= date('d M, Y', strtotime($pedido['fecha'])) ?></div>
                            </td>
                            <td>
                                <strong style="color: var(--text-main);">$<?= number_format($pedido['total'], 2) ?></strong>
                            </td>
                            <td>
                                <span class="badge <?= $pedido['estado_pago'] === 'Verificado' ? 'badge-success' : ($pedido['estado_pago'] === 'Rechazado' ? 'badge-danger' : 'badge-warning') ?>">
                                    <?= str_replace(['Transferencia / Nequi', 'Efectivo (Contra entrega)', 'Tarjeta'], ['Nequi', 'Efectivo', 'Tarjeta'], $pedido['metodo_pago'] ?? 'N/A') ?> 
                                    (<?= htmlspecialchars($pedido['estado_pago'] ?? 'Pend') ?>)
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= $badgeClass ?>">
                                    <?= htmlspecialchars($pedido['estado_nombre'] ?? 'Desconocido') ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 10px; align-items: flex-start;">
                                    <!-- Estado Pedido -->
                                    <form method="POST" action="pedidos.php" class="action-form" style="margin:0; display:flex; gap:8px; width: 100%;">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="id_pedido" value="<?= $pedido['id_pedido'] ?>">
                                        <select name="id_estado" class="form-select" style="padding: 6px 20px 6px 8px; font-size: 0.8rem; flex: 1;">
                                            <?php foreach ($estados as $estado): ?>
                                                <option value="<?= $estado['id_estado'] ?>" <?= $pedido['id_estado'] == $estado['id_estado'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($estado['nombre']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn-update" title="Guardar cambios" style="padding: 5px;">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                        </button>
                                    </form>

                                    <!-- Acciones Pago/Factura -->
                                    <div style="display: flex; gap: 8px; align-items: center; width: 100%;">
                                        <?php if (($pedido['estado_pago'] ?? 'Pendiente') === 'Pendiente'): ?>
                                            <?php if (!empty($pedido['comprobante'])): ?>
                                                <a href="<?= htmlspecialchars('/Chorifest' . $pedido['comprobante']) ?>" target="_blank" title="Ver Comprobante" style="background: rgba(142, 68, 173, 0.15); color: #8e44ad; border: 1px solid rgba(142, 68, 173, 0.3); padding: 6px; border-radius: 8px; cursor: pointer; display:flex;">
                                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                                                </a>
                                            <?php endif; ?>
                                            <form method="POST" action="pedidos.php" style="margin:0;">
                                                <input type="hidden" name="action" value="update_pago">
                                                <input type="hidden" name="id_pedido" value="<?= $pedido['id_pedido'] ?>">
                                                <input type="hidden" name="estado_pago" value="Verificado">
                                                <button type="submit" title="Verificar Pago" style="background: rgba(46, 213, 115, 0.15); color: #2ed573; border: 1px solid rgba(46, 213, 115, 0.3); padding: 6px; border-radius: 8px; cursor: pointer; display:flex;">
                                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                                </button>
                                            </form>
                                            <form method="POST" action="pedidos.php" style="margin:0;">
                                                <input type="hidden" name="action" value="update_pago">
                                                <input type="hidden" name="id_pedido" value="<?= $pedido['id_pedido'] ?>">
                                                <input type="hidden" name="estado_pago" value="Rechazado">
                                                <button type="submit" title="Rechazar Pago" style="background: rgba(231, 76, 60, 0.15); color: #e74c3c; border: 1px solid rgba(231, 76, 60, 0.3); padding: 6px; border-radius: 8px; cursor: pointer; display:flex;">
                                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <a href="factura.php?id=<?= $pedido['id_pedido'] ?>" target="_blank" style="text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 5px; background: rgba(52, 152, 219, 0.15); color: #3498db; border: 1px solid rgba(52, 152, 219, 0.3); padding: 6px 10px; border-radius: 8px; cursor: pointer; font-size: 0.8rem; font-weight: 600;">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                                            Factura
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
