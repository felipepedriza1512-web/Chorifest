<style>
    .pedidos-container {
        max-width: 800px;
        margin: 40px auto;
        padding: 0 20px;
    }
    .page-title {
        font-size: 2rem;
        font-weight: 800;
        margin-bottom: 30px;
        text-align: center;
        background: linear-gradient(to right, var(--primary), var(--secondary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .pedido-card {
        background: rgba(10, 10, 15, 0.6);
        border: 1px solid var(--glass-border);
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 25px;
        transition: transform 0.3s ease, border-color 0.3s ease;
    }
    .pedido-card:hover {
        transform: translateY(-5px);
        border-color: rgba(255, 75, 43, 0.4);
    }
    .pedido-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    .pedido-id {
        font-size: 1.2rem;
        font-weight: 800;
        color: white;
    }
    .pedido-fecha {
        color: #aaa;
        font-size: 0.9rem;
    }
    .badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: bold;
    }
    .badge-primary { background: rgba(255, 75, 43, 0.2); color: var(--primary); border: 1px solid var(--primary); }
    .badge-warning { background: rgba(255, 165, 2, 0.2); color: #ffa502; border: 1px solid #ffa502; }
    .badge-success { background: rgba(46, 213, 115, 0.2); color: #2ed573; border: 1px solid #2ed573; }
    .badge-info { background: rgba(30, 144, 255, 0.2); color: #1e90ff; border: 1px solid #1e90ff; }
    
    .pedido-items {
        margin-bottom: 20px;
    }
    .item-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px dashed rgba(255,255,255,0.05);
        color: #ccc;
    }
    .item-row:last-child { border-bottom: none; }
    .item-name { font-weight: 600; color: white; }
    
    .pedido-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 15px;
        border-top: 1px solid rgba(255,255,255,0.05);
    }
    .pedido-total {
        font-size: 1.3rem;
        font-weight: 800;
        color: var(--primary);
    }
    
    /* Status Tracker Timeline */
    .tracker {
        display: flex;
        justify-content: space-between;
        margin: 20px 0 30px;
        position: relative;
    }
    .tracker::before {
        content: '';
        position: absolute;
        top: 12px;
        left: 0;
        width: 100%;
        height: 2px;
        background: rgba(255,255,255,0.1);
        z-index: 1;
    }
    .tracker-step {
        position: relative;
        z-index: 2;
        text-align: center;
        width: 25%;
    }
    .step-dot {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: var(--dark);
        border: 2px solid rgba(255,255,255,0.2);
        margin: 0 auto 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
    }
    .step-label {
        font-size: 0.8rem;
        color: #aaa;
        font-weight: 600;
    }
    .tracker-step.active .step-dot {
        border-color: var(--primary);
        background: var(--primary);
        box-shadow: 0 0 10px rgba(255,75,43,0.5);
    }
    .tracker-step.active .step-label {
        color: white;
    }
    .tracker-step.completed .step-dot {
        border-color: #2ed573;
        background: #2ed573;
    }
    .tracker-step.completed .step-label {
        color: #2ed573;
    }
</style>

<div class="pedidos-container">
    <h1 class="page-title">Mis Pedidos</h1>

    <?php if (empty($misPedidos)): ?>
        <div class="pedido-card" style="text-align: center; padding: 60px 20px;">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1.5" style="margin-bottom: 20px;"><path d="M2 3h4l2 13h12l2-9H6"></path></svg>
            <h3 style="margin-bottom: 10px;">Aún no has hecho ningún pedido</h3>
            <p style="color: #aaa; margin-bottom: 25px;">¿Qué esperas para probar el mejor Chorizo Santandereano?</p>
            <a href="catalogo.php" class="btn btn-primary" style="padding: 10px 25px; border-radius: 20px; background: linear-gradient(to right, var(--primary), var(--secondary)); color: white; text-decoration: none; font-weight: 600;">Ir al Catálogo</a>
        </div>
    <?php else: ?>
        <?php foreach ($misPedidos as $p): ?>
            <div class="pedido-card">
                <div class="pedido-header">
                    <div>
                        <div class="pedido-id">Pedido #<?= str_pad($p['id_pedido'], 5, '0', STR_PAD_LEFT) ?></div>
                        <div class="pedido-fecha"><?= date('d M Y, H:i', strtotime($p['fecha'])) ?></div>
                    </div>
                    <?php
                        $estadoClass = 'badge-primary';
                        if (in_array($p['id_estado'], [2, 3])) $estadoClass = 'badge-warning'; // Preparando
                        if ($p['id_estado'] == 4) $estadoClass = 'badge-info'; // Listo
                        if ($p['id_estado'] == 5) $estadoClass = 'badge-primary'; // En Camino
                        if ($p['id_estado'] == 6) $estadoClass = 'badge-success'; // Entregado
                        if (in_array($p['id_estado'], [7, 10])) $estadoClass = 'badge-primary'; // Cancelado / Rechazado
                        if (in_array($p['id_estado'], [11, 12])) $estadoClass = 'badge-warning'; // Demorado / Problema
                    ?>
                    <span class="badge <?= $estadoClass ?>"><?= htmlspecialchars($p['estado_nombre']) ?></span>
                </div>

                <!-- Tracker (only if not cancelled/rechazado) -->
                <?php if (!in_array($p['id_estado'], [7, 10])): ?>
                    <div class="tracker">
                        <!-- Step 1: Recibido (1) -->
                        <div class="tracker-step <?= $p['id_estado'] >= 1 ? ($p['id_estado'] >= 2 ? 'completed' : 'active') : '' ?>">
                            <div class="step-dot"></div>
                            <div class="step-label">Recibido</div>
                        </div>
                        <!-- Step 2: Preparando/Listo (2, 3, 4) -->
                        <div class="tracker-step <?= $p['id_estado'] >= 2 ? ($p['id_estado'] >= 5 ? 'completed' : 'active') : '' ?>">
                            <div class="step-dot"></div>
                            <div class="step-label">Preparando</div>
                        </div>
                        <!-- Step 3: En Camino (5, 11, 12) -->
                        <div class="tracker-step <?= $p['id_estado'] >= 5 ? ($p['id_estado'] >= 6 && !in_array($p['id_estado'], [11, 12]) ? 'completed' : 'active') : '' ?>">
                            <div class="step-dot"></div>
                            <div class="step-label">En Camino</div>
                        </div>
                        <!-- Step 4: Entregado (6) -->
                        <div class="tracker-step <?= $p['id_estado'] == 6 ? 'completed' : '' ?>">
                            <div class="step-dot"></div>
                            <div class="step-label">Entregado</div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="pedido-items">
                    <?php foreach ($p['detalles'] as $item): ?>
                        <div class="item-row">
                            <div>
                                <strong><?= $item['cantidad'] ?>x</strong> 
                                <span class="item-name"><?= htmlspecialchars($item['nombre_producto']) ?></span>
                            </div>
                            <div>$<?= number_format($item['subtotal'], 2) ?></div>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($p['costo_domicilio'] > 0): ?>
                        <div class="item-row" style="color: #aaa;">
                            <div>Costo de Envío</div>
                            <div>$<?= number_format($p['costo_domicilio'], 2) ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="pedido-footer">
                    <div style="color: #aaa; font-size: 0.9rem;">
                        Pago: <strong><?= htmlspecialchars($p['metodo_pago']) ?></strong>
                        <span class="badge <?= ($p['estado_pago'] ?? 'Pendiente') === 'Verificado' ? 'badge-success' : 'badge-warning' ?>" style="margin-left: 5px; font-size: 0.7rem; padding: 3px 8px;">
                            <?= htmlspecialchars($p['estado_pago'] ?? 'Pendiente') ?>
                        </span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <a href="factura.php?id=<?= $p['id_pedido'] ?>" target="_blank" style="text-decoration: none; display: flex; align-items: center; gap: 5px; color: var(--primary); font-size: 0.9rem; font-weight: 600; padding: 5px 10px; border: 1px solid rgba(255, 75, 43, 0.3); border-radius: 8px; transition: all 0.3s;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                            Ver Factura
                        </a>
                        <div class="pedido-total">
                            Total: $<?= number_format($p['total'], 2) ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
