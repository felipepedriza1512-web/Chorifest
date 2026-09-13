<style>
    .kanban-board {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 30px;
        align-items: start;
        margin-top: 10px;
    }
    
    .kanban-col {
        background: rgba(15, 15, 18, 0.4);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-radius: 20px;
        padding: 24px;
        min-height: 600px;
        border: 1px solid var(--border);
        box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
        position: relative;
        overflow: hidden;
    }
    
    .kanban-col::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; height: 1px;
    }
    
    .col-header {
        font-size: 1.2rem;
        font-weight: 800;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 1px solid var(--border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: var(--text-main);
        letter-spacing: -0.3px;
    }
    
    .col-header .count {
        background: rgba(255,255,255,0.05);
        color: var(--text-muted);
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 0.85rem;
        font-weight: 800;
        border: 1px solid var(--border);
    }
    
    .ticket {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 18px;
        position: relative;
        transition: var(--transition);
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }
    
    .ticket:hover {
        transform: translateY(-4px);
        border-color: var(--border-highlight);
        box-shadow: 0 12px 24px -8px rgba(0,0,0,0.6);
    }
    
    .ticket-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
        font-weight: 700;
    }
    
    .ticket-header .order-id {
        font-size: 1.15rem;
        font-family: monospace;
        letter-spacing: -0.5px;
    }
    
    .ticket-time {
        font-size: 0.8rem;
        color: var(--text-muted);
        background: rgba(0, 0, 0, 0.3);
        padding: 4px 10px;
        border-radius: 8px;
        border: 1px solid var(--border);
    }
    
    .ticket-items {
        list-style: none;
        margin-bottom: 22px;
        font-size: 0.95rem;
        color: var(--text-main);
        background: rgba(0,0,0,0.2);
        border-radius: 10px;
        padding: 10px 15px;
        border: 1px solid rgba(255,255,255,0.03);
    }
    
    .ticket-items li {
        padding: 10px 0;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .ticket-items li:last-child {
        border-bottom: none;
    }
    
    .ticket-items li strong {
        background: rgba(255,255,255,0.08);
        padding: 4px 10px;
        border-radius: 8px;
        font-size: 0.85rem;
    }
    
    .ticket-actions {
        display: flex;
        gap: 10px;
    }
    
    .btn-block {
        width: 100%;
        text-align: center;
        padding: 12px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 0.95rem;
        border: none;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
    }
    
    /* Column: Pendientes */
    .col-new::before { background: linear-gradient(90deg, transparent, var(--primary), transparent); }
    .col-new .count { color: var(--primary); border-color: rgba(234, 88, 12, 0.3); background: rgba(234, 88, 12, 0.1); }
    .ticket-new .order-id { color: var(--primary); }
    .ticket-new .btn-block {
        background: linear-gradient(135deg, var(--primary), #fb923c);
        color: white;
        box-shadow: 0 4px 15px var(--primary-glow);
    }
    .ticket-new .btn-block:hover { transform: translateY(-2px); box-shadow: 0 6px 20px var(--primary-glow); }
    
    /* Column: En Preparación */
    .col-prep::before { background: linear-gradient(90deg, transparent, #ffa502, transparent); }
    .col-prep .count { color: #ffa502; border-color: rgba(255, 165, 2, 0.3); background: rgba(255, 165, 2, 0.1); }
    .ticket-prep { border-left: 3px solid #ffa502; }
    .ticket-prep .order-id { color: #ffa502; }
    .ticket-prep .btn-block {
        background: linear-gradient(135deg, #ffa502, #ff7f50);
        color: white;
        box-shadow: 0 4px 15px rgba(255, 165, 2, 0.4);
    }
    .ticket-prep .btn-block:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(255, 165, 2, 0.6); }

    /* Column: Listos */
    .col-ready::before { background: linear-gradient(90deg, transparent, #2ed573, transparent); }
    .col-ready .count { color: #2ed573; border-color: rgba(46, 213, 115, 0.3); background: rgba(46, 213, 115, 0.1); }
    .ticket-ready { border-left: 3px solid #2ed573; opacity: 0.8; }
    .ticket-ready:hover { opacity: 1; border-color: #2ed573; }
    .ticket-ready .order-id { color: #2ed573; }
    .ticket-ready .ticket-items li strong { color: #2ed573; background: rgba(46, 213, 115, 0.15); }
    .wait-badge {
        text-align: center; color: #2ed573; font-weight: 700; padding: 14px 0; 
        background: rgba(46, 213, 115, 0.08); border-radius: 10px; border: 1px solid rgba(46, 213, 115, 0.2);
        display: flex; justify-content: center; align-items: center; gap: 8px;
    }
    
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
    .modal.active {
        display: flex;
    }
    .modal-content {
        background: var(--dark);
        border: 1px solid var(--glass-border);
        border-radius: 15px;
        width: 100%;
        max-width: 500px;
        padding: 30px;
        position: relative;
    }
    
    .ingrediente-list {
        max-height: 400px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-top: 15px;
    }
    
    .ingrediente-item {
        background: rgba(255,255,255,0.05);
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .ingrediente-item.agotado {
        border-color: #ff4757;
        background: rgba(255, 71, 87, 0.05);
    }
    
    .btn-ingrediente {
        background: var(--surface);
        border: 1px solid var(--border);
        color: var(--text-main);
        padding: 8px 16px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: var(--transition);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .btn-ingrediente:hover {
        background: rgba(255,255,255,0.1);
    }
</style>

<?php
// Organize orders by status
$pendientes = [];
$en_preparacion = [];
$listos = [];

foreach ($pedidos as $p) {
    if ($p['id_estado'] == 1 || $p['id_estado'] == 2) {
        $pendientes[] = $p;
    } elseif ($p['id_estado'] == 3) {
        $en_preparacion[] = $p;
    } elseif ($p['id_estado'] == 4) {
        $listos[] = $p;
    }
}
?>

<div style="display: flex; justify-content: flex-end; margin-bottom: 20px;">
    <button class="btn-ingrediente" onclick="openIngredientesModal()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
        Gestionar Ingredientes
    </button>
</div>

<div class="kanban-board">
    <!-- Columna: Pendientes -->
    <div class="kanban-col col-new">
        <div class="col-header">
            Nuevos Pedidos
            <span class="count"><?= count($pendientes) ?></span>
        </div>
        <?php foreach ($pendientes as $p): ?>
            <div class="ticket ticket-new">
                <div class="ticket-header">
                    <div>
                        <span class="order-id">#<?= str_pad($p['id_pedido'], 4, '0', STR_PAD_LEFT) ?></span>
                        <div style="font-size: 0.9rem; color: var(--text-muted); margin-top: 4px;"><?= htmlspecialchars($p['cliente_nombre']) ?></div>
                    </div>
                    <span class="ticket-time"><?= date('H:i', strtotime($p['fecha'])) ?></span>
                </div>
                <ul class="ticket-items">
                    <?php foreach ($p['detalles'] as $item): ?>
                        <li>
                            <span><?= htmlspecialchars($item['nombre_producto']) ?></span>
                            <strong>x<?= $item['cantidad'] ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <form method="POST" action="pedidos.php" class="ticket-actions">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="id_pedido" value="<?= $p['id_pedido'] ?>">
                    <input type="hidden" name="id_estado" value="3"> <!-- 3 = En preparación -->
                    <button type="submit" class="btn-block">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20"></path><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                        Preparar
                    </button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Columna: En Preparación -->
    <div class="kanban-col col-prep">
        <div class="col-header">
            En Preparación
            <span class="count"><?= count($en_preparacion) ?></span>
        </div>
        <?php foreach ($en_preparacion as $p): ?>
            <div class="ticket ticket-prep">
                <div class="ticket-header">
                    <div>
                        <span class="order-id">#<?= str_pad($p['id_pedido'], 4, '0', STR_PAD_LEFT) ?></span>
                        <div style="font-size: 0.9rem; color: var(--text-muted); margin-top: 4px;"><?= htmlspecialchars($p['cliente_nombre']) ?></div>
                    </div>
                    <span class="ticket-time"><?= date('H:i', strtotime($p['fecha'])) ?></span>
                </div>
                <ul class="ticket-items">
                    <?php foreach ($p['detalles'] as $item): ?>
                        <li>
                            <span><?= htmlspecialchars($item['nombre_producto']) ?></span>
                            <strong>x<?= $item['cantidad'] ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <form method="POST" action="pedidos.php" class="ticket-actions">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="id_pedido" value="<?= $p['id_pedido'] ?>">
                    <input type="hidden" name="id_estado" value="4"> <!-- 4 = Listo -->
                    <button type="submit" class="btn-block">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        Marcar como Listo
                    </button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Columna: Listos -->
    <div class="kanban-col col-ready">
        <div class="col-header">
            Listos para Despacho
            <span class="count"><?= count($listos) ?></span>
        </div>
        <?php foreach ($listos as $p): ?>
            <div class="ticket ticket-ready">
                <div class="ticket-header">
                    <div>
                        <span class="order-id">#<?= str_pad($p['id_pedido'], 4, '0', STR_PAD_LEFT) ?></span>
                        <div style="font-size: 0.9rem; color: var(--text-muted); margin-top: 4px;"><?= htmlspecialchars($p['cliente_nombre']) ?></div>
                    </div>
                    <span class="ticket-time"><?= date('H:i', strtotime($p['fecha'])) ?></span>
                </div>
                <ul class="ticket-items">
                    <?php foreach ($p['detalles'] as $item): ?>
                        <li>
                            <span><?= htmlspecialchars($item['nombre_producto']) ?></span>
                            <strong>x<?= $item['cantidad'] ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="wait-badge">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    Esperando domiciliario
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    </div>
</div>

<!-- Modal de Ingredientes -->
<div id="ingredientesModal" class="modal">
    <div class="modal-content">
        <h3 style="margin-bottom: 10px;">Gestión de Ingredientes</h3>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 20px;">Marca los ingredientes que se han agotado. Esto desactivará automáticamente los productos que los contienen.</p>
        
        <div class="ingrediente-list">
            <?php foreach ($ingredientes as $ing): ?>
                <div class="ingrediente-item <?= $ing['estado'] === 'agotado' ? 'agotado' : '' ?>">
                    <div>
                        <strong><?= htmlspecialchars($ing['nombre']) ?></strong>
                        <div style="font-size: 0.8rem; color: <?= $ing['estado'] === 'agotado' ? '#ff4757' : '#2ed573' ?>;">
                            <?= $ing['estado'] === 'agotado' ? 'Agotado' : 'Disponible' ?>
                        </div>
                    </div>
                    <form method="POST" action="pedidos.php" style="margin: 0;">
                        <input type="hidden" name="action" value="toggle_ingrediente">
                        <input type="hidden" name="id_ingrediente" value="<?= $ing['id_ingrediente'] ?>">
                        <button type="submit" class="btn-ingrediente" style="background: <?= $ing['estado'] === 'agotado' ? '#2ed573' : '#ff4757' ?>; color: white; border: none;">
                            <?= $ing['estado'] === 'agotado' ? 'Marcar Disponible' : 'Marcar Agotado' ?>
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
            <?php if (empty($ingredientes)): ?>
                <div style="text-align: center; color: var(--text-muted); padding: 20px;">
                    No hay ingredientes configurados.
                </div>
            <?php endif; ?>
        </div>
        
        <div style="display:flex; justify-content:flex-end; margin-top:20px;">
            <button type="button" class="btn-block" style="background: var(--surface); border: 1px solid var(--border); color: var(--text-main); width: auto; padding: 10px 20px;" onclick="closeIngredientesModal()">Cerrar</button>
        </div>
    </div>
</div>

<script>
function openIngredientesModal() {
    document.getElementById('ingredientesModal').classList.add('active');
}

function closeIngredientesModal() {
    document.getElementById('ingredientesModal').classList.remove('active');
}
</script>
