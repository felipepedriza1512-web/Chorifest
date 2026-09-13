<style>
    .routes-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
        gap: 30px;
        margin-top: 10px;
    }
    
    .route-card {
        background: rgba(15, 15, 18, 0.4);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid var(--border);
        border-radius: 20px;
        position: relative;
        overflow: hidden;
        transition: var(--transition);
        box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
        display: flex;
        flex-direction: column;
    }
    
    .route-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; height: 1px;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
    }
    
    .route-card:hover {
        transform: translateY(-5px);
        border-color: var(--border-highlight);
        box-shadow: 0 15px 40px -10px rgba(0, 0, 0, 0.7);
    }
    
    .route-header {
        padding: 24px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: rgba(0,0,0,0.2);
    }
    
    .route-header strong {
        font-family: monospace;
        letter-spacing: -0.5px;
    }
    
    .route-body {
        padding: 24px;
        flex: 1;
    }
    
    .route-footer {
        padding: 20px 24px;
        border-top: 1px dashed rgba(255, 255, 255, 0.05);
        background: rgba(0, 0, 0, 0.15);
    }
    
    .info-row {
        display: flex;
        margin-bottom: 18px;
        align-items: flex-start;
    }
    .info-row:last-child {
        margin-bottom: 0;
    }
    
    .info-icon {
        color: var(--primary);
        margin-right: 15px;
        margin-top: 2px;
        background: rgba(234, 88, 12, 0.1);
        padding: 8px;
        border-radius: 10px;
        border: 1px solid rgba(234, 88, 12, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .info-content strong {
        display: block;
        color: var(--text-main);
        font-size: 1.05rem;
        font-weight: 600;
        margin-bottom: 4px;
    }
    .info-content span {
        color: var(--text-muted);
        font-size: 0.9rem;
        line-height: 1.4;
        display: block;
    }
    
    .btn-block {
        width: 100%;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 14px;
        font-size: 1rem;
        font-weight: 600;
        border-radius: 12px;
        border: none;
        cursor: pointer;
        transition: var(--transition);
    }
    
    .btn-block:active {
        transform: scale(0.97);
    }

    /* Premium Status Badges */
    .badge-route {
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.3px;
    }
    .badge-route.ready {
        background: rgba(46, 213, 115, 0.15);
        color: #2ed573;
        border: 1px solid rgba(46, 213, 115, 0.3);
    }
    .badge-route.delayed {
        background: rgba(255, 165, 2, 0.15);
        color: #ffa502;
        border: 1px solid rgba(255, 165, 2, 0.3);
    }
    .badge-route.transit {
        background: rgba(234, 88, 12, 0.15);
        color: var(--primary);
        border: 1px solid rgba(234, 88, 12, 0.3);
    }

    /* Premium Action Buttons */
    .btn-start {
        background: linear-gradient(135deg, var(--primary), #fb923c);
        color: white;
        box-shadow: 0 4px 15px var(--primary-glow);
    }
    .btn-start:hover { transform: translateY(-2px); box-shadow: 0 6px 20px var(--primary-glow); }

    .btn-delivered {
        background: linear-gradient(135deg, #10b981, #34d399);
        color: white;
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
    }
    .btn-delivered:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(16, 185, 129, 0.5); }

    .btn-delayed {
        background: linear-gradient(135deg, #f59e0b, #fbbf24);
        color: white;
        box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
    }
    .btn-delayed:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(245, 158, 11, 0.5); }

    .btn-problem {
        background: linear-gradient(135deg, #ef4444, #f87171);
        color: white;
        box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
    }
    .btn-problem:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(239, 68, 68, 0.5); }
</style>

<div class="routes-grid">
    <?php if (empty($rutas)): ?>
        <div class="card" style="grid-column: 1 / -1; text-align: center; padding: 60px; background: rgba(15, 15, 18, 0.4); backdrop-filter: blur(12px);">
            <div style="width: 80px; height: 80px; border-radius: 20px; background: rgba(234, 88, 12, 0.1); border: 1px solid rgba(234, 88, 12, 0.2); display: flex; align-items: center; justify-content: center; margin: 0 auto 25px auto;">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="1.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            </div>
            <h3 style="font-size: 1.5rem; font-weight: 700; color: var(--text-main); margin-bottom: 10px;">¡Todo entregado!</h3>
            <p style="color: var(--text-muted); font-size: 1.05rem;">No hay pedidos listos para despachar en este momento. Tómate un respiro.</p>
        </div>
    <?php else: ?>
        <?php foreach ($rutas as $r): ?>
            <div class="route-card">
                <div class="route-header">
                    <div>
                        <strong style="color: var(--primary); font-size: 1.3rem;">#<?= str_pad($r['id_pedido'], 4, '0', STR_PAD_LEFT) ?></strong>
                        <span style="color:var(--text-muted); font-size: 0.9rem; margin-left: 10px; background: rgba(0,0,0,0.3); padding: 4px 8px; border-radius: 6px;"><?= date('H:i', strtotime($r['fecha'])) ?></span>
                    </div>
                    <?php if ($r['id_estado'] == 4): ?>
                        <span class="badge-route ready">Listo</span>
                    <?php elseif ($r['id_estado'] == 11): ?>
                        <span class="badge-route delayed">Demorado</span>
                    <?php else: ?>
                        <span class="badge-route transit">En Camino</span>
                    <?php endif; ?>
                </div>
                
                <div class="route-body">
                    <div class="info-row">
                        <div class="info-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                        </div>
                        <div class="info-content">
                            <strong><?= htmlspecialchars($r['direccion']) ?></strong>
                            <span><?= htmlspecialchars($r['barrio']) ?> &bull; <?= htmlspecialchars($r['zona_nombre']) ?></span>
                            <?php if (!empty($r['direccion_detalles'])): ?>
                                <span style="margin-top:8px; color:var(--text-muted); background:rgba(255,255,255,0.03); padding:8px 12px; border-radius:8px; border: 1px solid rgba(255,255,255,0.05);">
                                    <strong style="display:inline; font-size:0.85rem; color:var(--primary-light);">Nota:</strong> <?= htmlspecialchars($r['direccion_detalles']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        </div>
                        <div class="info-content">
                            <strong><?= htmlspecialchars($r['cliente_nombre']) ?></strong>
                            <span><?= htmlspecialchars($r['cliente_telefono']) ?></span>
                        </div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"></rect><circle cx="12" cy="12" r="2"></circle><path d="M6 12h.01M18 12h.01"></path></svg>
                        </div>
                        <div class="info-content">
                            <strong style="color: <?= $r['estado_pago'] === 'Pagado' ? '#10b981' : '#f59e0b' ?>; font-size: 1.15rem;">
                                A cobrar: $<?= $r['estado_pago'] === 'Pagado' ? '0.00 (Pagado)' : number_format($r['total'], 2) ?>
                            </strong>
                            <span>Método: <?= htmlspecialchars($r['metodo_pago'] ?? 'N/A') ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="route-footer">
                    <?php if ($r['id_estado'] == 4): ?>
                        <form method="POST" action="rutas.php">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="id_pedido" value="<?= $r['id_pedido'] ?>">
                            <input type="hidden" name="id_estado" value="5"> <!-- 5 = En camino -->
                            <button type="submit" class="btn-block btn-start">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                                Iniciar Ruta
                            </button>
                        </form>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <form method="POST" action="rutas.php">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="id_pedido" value="<?= $r['id_pedido'] ?>">
                                <input type="hidden" name="id_estado" value="6"> <!-- 6 = Entregado -->
                                <button type="submit" class="btn-block btn-delivered" style="padding: 18px; font-size: 1.1rem;">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    Entregado
                                </button>
                            </form>
                            <div style="display: flex; gap: 12px;">
                                <form method="POST" action="rutas.php" style="flex: 1;">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="id_pedido" value="<?= $r['id_pedido'] ?>">
                                    <input type="hidden" name="id_estado" value="11"> <!-- 11 = Demorado -->
                                    <button type="submit" class="btn-block btn-delayed">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                        Demorado
                                    </button>
                                </form>
                                <form method="POST" action="rutas.php" style="flex: 1;">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="id_pedido" value="<?= $r['id_pedido'] ?>">
                                    <input type="hidden" name="id_estado" value="12"> <!-- 12 = Problema -->
                                    <button type="submit" class="btn-block btn-problem">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"></polygon><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                                        Problema
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
