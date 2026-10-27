<style>
    .table-container {
        overflow-x: auto;
        border-radius: 12px;
        border: 1px solid var(--border);
        background: var(--dark);
    }
    .table {
        width: 100%;
        border-collapse: collapse;
    }
    .table th, .table td {
        padding: 14px 18px;
        text-align: left;
        border-bottom: 1px solid var(--border);
        vertical-align: middle;
        font-size: 0.92rem;
    }
    .table th {
        background: var(--surface);
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
    }
    .table tr:last-child td { border-bottom: none; }
    .table tr:hover { background: rgba(255, 255, 255, 0.02); }

    .badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.78rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        white-space: nowrap;
    }
    .badge-success { background: rgba(46, 213, 115, 0.15); color: #2ed573; border: 1px solid rgba(46, 213, 115, 0.3); }
    .badge-danger { background: rgba(255, 71, 87, 0.15); color: #ff4757; border: 1px solid rgba(255, 71, 87, 0.3); }
    .badge-warning { background: rgba(255, 165, 2, 0.15); color: #ffa502; border: 1px solid rgba(255, 165, 2, 0.3); }

    .btn {
        padding: 8px 16px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        border: none;
        display: inline-block;
        text-decoration: none;
        transition: transform 0.15s, background 0.15s;
    }
    .btn-primary { background: var(--primary); color: white; }
    .btn-secondary { background: var(--surface); color: var(--text-main); border: 1px solid var(--border); }
    .btn-secondary:hover { background: var(--border); }
    .btn-sm { padding: 5px 10px; font-size: 0.8rem; border-radius: 6px; }

    /* Modal styles */
    .modal {
        display: none;
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.8);
        z-index: 1000;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(5px);
    }
    .modal.active { display: flex; }
    .modal-content {
        background: #18181b;
        border: 1px solid var(--border);
        border-radius: 16px;
        width: 90%;
        max-width: 520px;
        padding: 28px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.7);
        color: white;
    }
    .form-group { margin-bottom: 16px; }
    .form-group label {
        display: block;
        margin-bottom: 6px;
        font-weight: 600;
        color: var(--text-muted);
        font-size: 0.85rem;
    }
    .form-control {
        width: 100%;
        box-sizing: border-box;
        padding: 10px 14px;
        background: rgba(0,0,0,0.3);
        border: 1px solid var(--border);
        border-radius: 8px;
        color: white;
        font-size: 0.92rem;
    }
</style>

<div>
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
        <div>
            <h3 style="font-size:1.4rem; font-weight:700;">Control de Inventario e Insumos</h3>
            <p style="color:var(--text-muted); font-size:0.85rem; margin-top:3px;">
                Supervisa el stock en tiempo real, umbrales mínimos, reposiciones y movimientos.
            </p>
        </div>
        <div style="display:flex; gap:10px;">
            <button class="btn btn-secondary" onclick="openHistorialModal()">📜 Historial de Movimientos</button>
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

    <!-- Alertas de Stock Bajo Umbral Mínimo -->
    <?php if (!empty($alertas)): ?>
        <div style="background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 14px; padding: 16px 20px; margin-bottom: 22px;">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                <span style="font-size:1.2rem;">⚠️</span>
                <strong style="color:#f87171; font-size:0.95rem;">Alertas de Stock Crítico (Bajo Umbral Mínimo):</strong>
            </div>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <?php foreach ($alertas as $alt): ?>
                    <span class="badge badge-danger" style="font-size:0.82rem; padding:6px 12px;">
                        <?= htmlspecialchars($alt['nombre']) ?>: <?= number_format($alt['stock_actual'], 3) ?> <?= $alt['unidad_medida'] ?> (Mín: <?= number_format($alt['stock_minimo'], 3) ?>)
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tabla Principal de Insumos -->
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th>Insumo / Ingrediente</th>
                    <th>Stock Actual</th>
                    <th>Umbral Mínimo</th>
                    <th>Estado de Alerta</th>
                    <th>Recetas Asociadas</th>
                    <th style="text-align: right;">Acciones de Inventario</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($insumos)): ?>
                    <tr><td colspan="7" style="text-align:center; padding:30px; color:var(--text-muted);">No hay insumos registrados.</td></tr>
                <?php else: ?>
                    <?php foreach ($insumos as $ins): 
                        $stock = (float)$ins['stock_actual'];
                        $minimo = (float)$ins['stock_minimo'];
                        $isBajo = ($stock <= $minimo);
                        $isAgotado = ($stock <= 0);
                    ?>
                        <tr>
                            <td style="font-family:monospace; color:var(--text-muted);">#<?= $ins['id_insumo'] ?></td>
                            <td>
                                <strong style="color:white; font-size:0.95rem; display:block;"><?= htmlspecialchars($ins['nombre']) ?></strong>
                                <span style="font-size:0.75rem; color:var(--text-muted);">Unidad: <?= htmlspecialchars($ins['unidad_medida']) ?></span>
                            </td>
                            <td>
                                <div style="font-size:1.15rem; font-weight:800; font-family:monospace; <?= $isBajo ? 'color:#ef4444;' : 'color:#2ed573;' ?>">
                                    <?= number_format($stock, 3) ?> <span style="font-size:0.8rem; font-weight:normal;"><?= htmlspecialchars($ins['unidad_medida']) ?></span>
                                </div>
                            </td>
                            <td>
                                <span style="font-family:monospace; color:var(--text-muted); font-size:0.95rem;">
                                    <?= number_format($minimo, 3) ?> <?= htmlspecialchars($ins['unidad_medida']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($isAgotado): ?>
                                    <span class="badge badge-danger">🚫 AGOTADO (0)</span>
                                <?php elseif ($isBajo): ?>
                                    <span class="badge badge-warning">⚠️ BAJO STOCK</span>
                                <?php else: ?>
                                    <span class="badge badge-success">✓ STOCK ÓPTIMO</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge" style="background:rgba(255,255,255,0.06); color:var(--text-muted); border:1px solid rgba(255,255,255,0.1);">
                                    <?= (int)$ins['total_productos_receta'] ?> producto(s)
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display:inline-flex; gap:8px;">
                                    <button class="btn btn-primary btn-sm" onclick='openEntradaModal(<?= json_encode($ins) ?>)'>
                                        + Entrada
                                    </button>
                                    <button class="btn btn-secondary btn-sm" onclick='openAjusteModal(<?= json_encode($ins) ?>)'>
                                        ⚙️ Ajustar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Registrar Entrada -->
<div id="modalEntrada" class="modal">
    <div class="modal-content">
        <h3 style="margin-bottom: 6px; font-size:1.3rem;">Registrar Entrada de Stock</h3>
        <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom:18px;">
            Añade existencias por compra o reposición de insumo.
        </p>
        
        <form method="POST" action="ingredientes.php">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            <input type="hidden" name="action" value="entrada">
            <input type="hidden" name="id_insumo" id="entrada_id_insumo">

            <div class="form-group">
                <label>Insumo:</label>
                <input type="text" id="entrada_nombre" class="form-control" readonly style="opacity:0.8;">
            </div>

            <div class="form-group">
                <label id="entrada_label_cant">Cantidad a Ingresar *</label>
                <input type="number" name="cantidad" step="0.001" min="0.001" class="form-control" required placeholder="Ej: 10.5">
            </div>

            <div class="form-group">
                <label>Motivo Obligatorio *</label>
                <input type="text" name="motivo" class="form-control" required placeholder="Ej: Compra proveedor Frigorífico La 14, Factura #4582">
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:24px;">
                <button type="button" class="btn btn-secondary" onclick="closeModals()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Registrar Entrada</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Ajustar Stock -->
<div id="modalAjuste" class="modal">
    <div class="modal-content">
        <h3 style="margin-bottom: 6px; font-size:1.3rem;">Ajuste de Inventario</h3>
        <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom:18px;">
            Corrige el stock físico real tras conteo o inventario. No se permite stock negativo.
        </p>
        
        <form method="POST" action="ingredientes.php">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            <input type="hidden" name="action" value="ajuste">
            <input type="hidden" name="id_insumo" id="ajuste_id_insumo">

            <div class="form-group">
                <label>Insumo:</label>
                <input type="text" id="ajuste_nombre" class="form-control" readonly style="opacity:0.8;">
            </div>

            <div class="form-group">
                <label id="ajuste_label_stock">Nuevo Stock Físico * (Mínimo 0)</label>
                <input type="number" name="nuevo_stock" step="0.001" min="0" class="form-control" required placeholder="Ej: 15.000">
            </div>

            <div class="form-group">
                <label>Motivo Obligatorio del Ajuste *</label>
                <input type="text" name="motivo" class="form-control" required placeholder="Ej: Conteo físico mensual, Merma por descongelación...">
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:24px;">
                <button type="button" class="btn btn-secondary" onclick="closeModals()">Cancelar</button>
                <button type="submit" class="btn btn-primary" style="background:#eab308; color:#000;">Aplicar Ajuste</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Historial de Movimientos -->
<div id="modalHistorial" class="modal">
    <div class="modal-content" style="max-width: 780px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <div>
                <h3 style="font-size:1.3rem; margin:0;">Historial de Movimientos de Inventario</h3>
                <span style="font-size:0.8rem; color:var(--text-muted);">Últimos 50 movimientos registrados</span>
            </div>
            <button type="button" class="btn btn-secondary btn-sm" onclick="closeModals()">✕ Cerrar</button>
        </div>

        <div style="max-height: 440px; overflow-y: auto; border: 1px solid var(--border); border-radius: 10px;">
            <table class="table" style="font-size: 0.85rem;">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Insumo</th>
                        <th>Tipo</th>
                        <th>Cantidad</th>
                        <th>Motivo / Auditoría</th>
                        <th>Responsable</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($movimientos)): ?>
                        <tr><td colspan="6" style="text-align:center; padding:20px; color:var(--text-muted);">No hay movimientos registrados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($movimientos as $m): 
                            $tipo = $m['tipo'];
                            $tipoBadge = 'badge-success';
                            if ($tipo === 'SALIDA_RECETA' || $tipo === 'SALIDA') $tipoBadge = 'badge-danger';
                            if ($tipo === 'AJUSTE') $tipoBadge = 'badge-warning';
                            if ($tipo === 'FALTANTE') $tipoBadge = 'badge-danger';
                            if ($tipo === 'REINTEGRO_CANCELACION') $tipoBadge = 'badge-info';
                        ?>
                            <tr>
                                <td style="font-family:monospace; color:var(--text-muted); font-size:0.75rem;">
                                    <?= date('d/m/y H:i', strtotime($m['fecha'])) ?>
                                </td>
                                <td><strong><?= htmlspecialchars($m['insumo_nombre'] ?? 'N/A') ?></strong></td>
                                <td><span class="badge <?= $tipoBadge ?>" style="font-size:0.7rem;"><?= htmlspecialchars($tipo) ?></span></td>
                                <td style="font-family:monospace; font-weight:700;">
                                    <?= number_format($m['cantidad'], 3) ?> <?= htmlspecialchars($m['unidad_medida'] ?? '') ?>
                                </td>
                                <td style="color:var(--text-muted); max-width:220px; word-break:break-word;">
                                    <?= htmlspecialchars($m['motivo'] ?? '') ?>
                                </td>
                                <td style="color:white; font-size:0.8rem;">
                                    <?= htmlspecialchars($m['usuario_nombre'] ?? 'Sistema') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function openEntradaModal(insumo) {
    document.getElementById('entrada_id_insumo').value = insumo.id_insumo;
    document.getElementById('entrada_nombre').value = insumo.nombre + ' (' + insumo.unidad_medida + ') - Stock actual: ' + parseFloat(insumo.stock_actual).toFixed(3);
    document.getElementById('entrada_label_cant').innerText = 'Cantidad a Ingresar (' + insumo.unidad_medida + ') *';
    document.getElementById('modalEntrada').classList.add('active');
}

function openAjusteModal(insumo) {
    document.getElementById('ajuste_id_insumo').value = insumo.id_insumo;
    document.getElementById('ajuste_nombre').value = insumo.nombre + ' (' + insumo.unidad_medida + ') - Stock actual: ' + parseFloat(insumo.stock_actual).toFixed(3);
    document.getElementById('ajuste_label_stock').innerText = 'Nuevo Stock Físico Real (' + insumo.unidad_medida + ') *';
    document.getElementById('modalAjuste').classList.add('active');
}

function openHistorialModal() {
    document.getElementById('modalHistorial').classList.add('active');
}

function closeModals() {
    document.getElementById('modalEntrada').classList.remove('active');
    document.getElementById('modalAjuste').classList.remove('active');
    document.getElementById('modalHistorial').classList.remove('active');
}
</script>
