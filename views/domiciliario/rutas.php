<style>
    .domi-panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 15px;
    }

    .routes-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
        gap: 25px;
        margin-top: 15px;
    }
    
    .route-card {
        background: rgba(15, 15, 18, 0.6);
        backdrop-filter: blur(12px);
        border: 1px solid var(--border);
        border-radius: 20px;
        position: relative;
        overflow: hidden;
        transition: var(--transition);
        box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
        display: flex;
        flex-direction: column;
    }
    
    .route-card.active-transit {
        border-color: #ff8a2a;
        box-shadow: 0 0 25px rgba(255, 138, 42, 0.3);
    }

    .route-header {
        padding: 20px 24px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: rgba(0,0,0,0.2);
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
        padding: 16px;
        font-size: 1.05rem;
        font-weight: 700;
        border-radius: 14px;
        border: none;
        cursor: pointer;
        transition: var(--transition);
    }

    .badge-route {
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 700;
    }
    .badge-route.empacado {
        background: rgba(52, 152, 219, 0.15);
        color: #3498db;
        border: 1px solid rgba(52, 152, 219, 0.3);
    }
    .badge-route.transit {
        background: rgba(255, 138, 42, 0.15);
        color: #ff8a2a;
        border: 1px solid rgba(255, 138, 42, 0.3);
    }

    .btn-sali {
        background: linear-gradient(135deg, var(--primary), #ff8a2a);
        color: white;
        box-shadow: 0 4px 15px rgba(234, 88, 12, 0.4);
    }
    .btn-sali:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(234, 88, 12, 0.6); }

    .btn-entregado {
        background: linear-gradient(135deg, #10b981, #2ed573);
        color: white;
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
    }
    .btn-entregado:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(16, 185, 129, 0.6); }

    /* Caja de Control del Simulador GPS (Requisito 5) */
    .simulador-box {
        background: rgba(255, 138, 42, 0.08);
        border: 1px dashed rgba(255, 138, 42, 0.4);
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .simulador-toggle {
        display: flex;
        align-items: center;
        gap: 12px;
        cursor: pointer;
        user-select: none;
    }

    .switch-slider {
        width: 50px;
        height: 26px;
        background: #333;
        border-radius: 20px;
        position: relative;
        transition: background 0.3s;
    }
    .switch-slider::after {
        content: '';
        position: absolute;
        top: 3px;
        left: 3px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: white;
        transition: transform 0.3s;
    }
    #simulador-check:checked + .switch-slider {
        background: #ff8a2a;
    }
    #simulador-check:checked + .switch-slider::after {
        transform: translateX(24px);
    }

    .gps-status-pill {
        font-size: 0.85rem;
        font-weight: bold;
        padding: 6px 14px;
        border-radius: 20px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        color: var(--text-muted);
    }
    .gps-status-pill.active {
        background: rgba(46, 213, 115, 0.15);
        border-color: #2ed573;
        color: #2ed573;
    }
</style>

<div class="domi-panel-header">
    <div>
        <h3 style="font-size: 1.4rem; font-weight: 700; margin: 0;">Panel de Domicilios - Rutas de Entrega</h3>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 4px;">Tus pedidos asignados. Haz clic en "Salí a entregar" para activar el mapa GPS.</p>
    </div>
    <div id="gps-status-indicator" class="gps-status-pill">
        ⚪ GPS Inactivo
    </div>
</div>

<!-- Resumen de Efectivo en Turno por Liquidar -->
<div style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 14px; padding: 18px 24px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
    <div>
        <div style="font-size: 0.85rem; color: #2ed573; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">💵 Efectivo por Liquidar en Turno Actual</div>
        <div style="font-size: 1.6rem; font-weight: 800; color: white; margin-top: 4px;">$<?= number_format($totalEfectivoTurno ?? 0, 2) ?></div>
        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
            <?= count($pedidosPendientesEfectivo ?? []) ?> pedido(s) entregado(s) en efectivo pendientes de entrega al administrador.
        </div>
    </div>
    <div style="text-align: right;">
        <span style="display: inline-block; padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; background: rgba(16, 185, 129, 0.2); color: #2ed573; border: 1px solid rgba(16, 185, 129, 0.4);">
            Cobros al día
        </span>
    </div>
</div>

<?php if (function_exists('isDevelopmentEnvironment') && isDevelopmentEnvironment()): ?>
<!-- SIMULADOR DE UBICACIÓN GPS PARA DESARROLLO (Solo entorno local/desarrollo) -->
<div class="simulador-box">
    <div>
        <strong style="color: #ff8a2a; font-size: 1rem; display: block;">⚡ Simulador de GPS (Modo Desarrollador)</strong>
        <span style="font-size: 0.82rem; color: var(--text-muted); display: block; margin-top: 2px;">
            Enciende este interruptor para probar la ruta y el movimiento en vivo desde un computador sin desplazarte físicamente. <em>Los estados del pedido NUNCA se simulan solos.</em>
        </span>
    </div>
    <label class="simulador-toggle">
        <input type="checkbox" id="simulador-check" style="display: none;" onchange="toggleSimulador(this.checked)">
        <div class="switch-slider"></div>
        <span id="sim-label-text" style="font-weight: 700; font-size: 0.9rem; color: white;">Apagado</span>
    </label>
</div>
<?php endif; ?>

<div class="routes-grid">
    <?php if (empty($rutas)): ?>
        <div class="card" style="grid-column: 1 / -1; text-align: center; padding: 60px; background: rgba(15, 15, 18, 0.4);">
            <div style="width: 80px; height: 80px; border-radius: 20px; background: rgba(234, 88, 12, 0.1); border: 1px solid rgba(234, 88, 12, 0.2); display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto; font-size: 2.2rem;">
                🛵
            </div>
            <h3 style="font-size: 1.4rem; font-weight: 700; color: white; margin-bottom: 8px;">No tienes pedidos asignados por entregar</h3>
            <p style="color: var(--text-muted); font-size: 0.95rem;">Cuando el administrador te asigne un pedido empacado, aparecerá automáticamente aquí.</p>
        </div>
    <?php else: ?>
        <?php foreach ($rutas as $r): 
            $isEnCamino = ($r['id_estado'] == 5);
            $clienteLat = !empty($r['cliente_lat']) ? (float)$r['cliente_lat'] : 2.9273;
            $clienteLng = !empty($r['cliente_lng']) ? (float)$r['cliente_lng'] : -75.2819;
        ?>
            <div class="route-card <?= $isEnCamino ? 'active-transit' : '' ?>" data-id-pedido="<?= $r['id_pedido'] ?>" data-cliente-lat="<?= $clienteLat ?>" data-cliente-lng="<?= $clienteLng ?>">
                <div class="route-header">
                    <div>
                        <strong style="color: var(--primary); font-size: 1.3rem;">#<?= str_pad($r['id_pedido'], 5, '0', STR_PAD_LEFT) ?></strong>
                        <span style="color:var(--text-muted); font-size: 0.85rem; margin-left: 8px; background: rgba(0,0,0,0.3); padding: 4px 8px; border-radius: 6px;"><?= date('H:i', strtotime($r['fecha'])) ?></span>
                    </div>
                    <?php if ($isEnCamino): ?>
                        <span class="badge-route transit">🛵 En camino</span>
                    <?php else: ?>
                        <span class="badge-route empacado">📦 Empacado</span>
                    <?php endif; ?>
                </div>
                
                <div class="route-body">
                    <div class="info-row">
                        <div class="info-icon">
                            📍
                        </div>
                        <div class="info-content">
                            <strong><?= htmlspecialchars($r['direccion']) ?></strong>
                            <span><?= htmlspecialchars($r['barrio']) ?> &bull; <?= htmlspecialchars($r['zona_nombre'] ?? 'Zona Central') ?></span>
                            <?php if (empty($r['cliente_lat']) || empty($r['cliente_lng'])): ?>
                                <span style="display:inline-block; margin-top:6px; font-size:0.75rem; color:#f59e0b; background:rgba(245,158,11,0.1); border:1px solid rgba(245,158,11,0.25); padding:2px 8px; border-radius:6px;">
                                    📍 Coordenadas no registradas (Pedido antiguo)
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($r['direccion_detalles'])): ?>
                                <span style="margin-top:8px; color:var(--text-muted); background:rgba(255,255,255,0.03); padding:8px 12px; border-radius:8px; border: 1px solid rgba(255,255,255,0.05);">
                                    <strong style="display:inline; font-size:0.82rem; color:var(--primary);">Nota:</strong> <?= htmlspecialchars($r['direccion_detalles']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-icon">
                            👤
                        </div>
                        <div class="info-content">
                            <strong><?= htmlspecialchars($r['cliente_nombre']) ?></strong>
                            <span>📞 <?= htmlspecialchars($r['cliente_telefono']) ?></span>
                        </div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-icon">
                            💵
                        </div>
                        <div class="info-content">
                            <strong style="color: #2ed573; font-size: 1.1rem;">
                                Total a cobrar: $<?= number_format($r['total'], 0, ',', '.') ?>
                            </strong>
                            <span>Método: <?= htmlspecialchars($r['metodo_pago'] ?? 'Efectivo') ?> (<?= htmlspecialchars($r['estado_pago'] ?? 'Pend') ?>)</span>
                        </div>
                    </div>

                    <?php if ($isEnCamino): ?>
                        <div style="background: rgba(255, 138, 42, 0.1); border: 1px solid rgba(255, 138, 42, 0.3); padding: 12px; border-radius: 10px; text-align: center; color: #ff8a2a; font-weight: bold; font-size: 0.88rem; margin-top: 10px;">
                            🛰️ Compartiendo ubicación GPS en tiempo real con el cliente...
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="route-footer">
                    <!-- BOTÓN 1: Salí a entregar (Regla 1 del prompt) -->
                    <?php if ($r['id_estado'] == 4): ?>
                        <form method="POST" action="rutas.php" onsubmit="iniciarEntrega(event, <?= $r['id_pedido'] ?>)">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="id_pedido" value="<?= $r['id_pedido'] ?>">
                            <input type="hidden" name="id_estado" value="5"> <!-- 5 = En camino -->
                            <button type="submit" class="btn-block btn-sali">
                                🛵 Salí a entregar
                            </button>
                        </form>

                    <!-- BOTÓN 2: Entregado / Novedad (Regla 1 del prompt) -->
                    <?php elseif ($r['id_estado'] == 5): ?>
                        <div style="display: flex; gap: 10px;">
                            <form method="POST" action="rutas.php" onsubmit="finalizarEntrega(event, <?= $r['id_pedido'] ?>)" style="flex: 1; margin: 0;">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="id_pedido" value="<?= $r['id_pedido'] ?>">
                                <input type="hidden" name="id_estado" value="6"> <!-- 6 = Entregado -->
                                <button type="submit" class="btn-block btn-entregado">
                                    ✓ Entregado
                                </button>
                            </form>
                            <button type="button" class="btn-block" style="background: rgba(245, 158, 11, 0.2); border: 1px solid #f59e0b; color: #fbbf24; flex: 1; padding: 12px; font-size: 0.95rem;" onclick="abrirModalNovedad(<?= $r['id_pedido'] ?>)">
                                ⚠️ Novedad
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal de Reportar Novedad de Entrega -->
<div id="modalNovedadDomi" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.75); backdrop-filter:blur(6px); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#13131a; border:1px solid rgba(255,255,255,0.1); border-radius:18px; width:92%; max-width:480px; padding:28px; box-shadow:0 20px 50px rgba(0,0,0,0.6);">
        <h3 style="font-size:1.3rem; margin-bottom:8px; color:white; display:flex; align-items:center; gap:8px;">
            ⚠️ Reportar Novedad de Entrega
        </h3>
        <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom:18px;">
            El pedido quedará bloqueado temporalmente y pasará a atención de la administración.
        </p>
        <form method="POST" action="/Chorifest/api/actualizar_estado.php" id="formReportarNov">
            <input type="hidden" name="id_pedido" id="nov_id_pedido" value="0">
            <input type="hidden" name="nuevo_estado" value="12">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            <div style="margin-bottom:14px;">
                <label style="display:block; font-size:0.82rem; color:var(--text-muted); margin-bottom:6px; font-weight:600;">Tipo de Novedad</label>
                <select name="tipo_novedad" style="width:100%; background:rgba(0,0,0,0.4); color:white; border:1px solid rgba(255,255,255,0.1); border-radius:10px; padding:10px;" required>
                    <option value="CLIENTE AUSENTE">Cliente Ausente / No responde</option>
                    <option value="DIRECCION INCORRECTA">Dirección Incorrecta / Incompleta</option>
                    <option value="DEMORA EN RUTA">Demora por tráfico o clima</option>
                    <option value="PEDIDO RECHAZADO">Cliente rechaza pedido</option>
                    <option value="OTRO INCIDENTE">Otro incidente en ruta</option>
                </select>
            </div>
            <div style="margin-bottom:20px;">
                <label style="display:block; font-size:0.82rem; color:var(--text-muted); margin-bottom:6px; font-weight:600;">Detalles / Observaciones</label>
                <textarea name="comentario" rows="3" style="width:100%; background:rgba(0,0,0,0.4); color:white; border:1px solid rgba(255,255,255,0.1); border-radius:10px; padding:10px; font-size:0.88rem;" placeholder="Describe qué ocurrió..." required></textarea>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-secondary" onclick="cerrarModalNovedad()" style="padding:10px 18px; border-radius:10px; background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.1); color:white;">Cancelar</button>
                <button type="submit" class="btn btn-primary" style="padding:10px 20px; border-radius:10px; background:linear-gradient(135deg, #f59e0b, #d97706); color:white; font-weight:700; border:none; cursor:pointer;">Registrar Novedad</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalNovedad(idPedido) {
    document.getElementById('nov_id_pedido').value = idPedido;
    var m = document.getElementById('modalNovedadDomi');
    m.style.display = 'flex';
}
function cerrarModalNovedad() {
    var m = document.getElementById('modalNovedadDomi');
    m.style.display = 'none';
}
</script>

<script>
(function() {
    var watchId = null;
    var activePedidoId = null;
    var devSimuladorActive = false;
    var simulatorInterval = null;
    var currentSimStep = 0;

    // Detectar si hay algún pedido actualmente "En camino" (5) para iniciar GPS
    var activeCard = document.querySelector('.route-card.active-transit');
    if (activeCard) {
        activePedidoId = parseInt(activeCard.dataset.idPedido);
        iniciarGPS(activePedidoId);
    }

    // Iniciar Geolocalización real navigator.geolocation.watchPosition (Requisito 3)
    function iniciarGPS(idPedido) {
        if (!navigator.geolocation) {
            alert("Tu navegador no soporta geolocalización GPS.");
            return;
        }

        // Solicitar permiso explicando el motivo exacto (Requisito 3)
        var msgPermiso = "Chorifest necesita tu ubicación GPS para transmitir la ruta de entrega en tiempo real al cliente y la administración.";
        
        var indicator = document.getElementById('gps-status-indicator');
        indicator.innerHTML = '🟢 Transmitiendo GPS real...';
        indicator.className = 'gps-status-pill active';

        watchId = navigator.geolocation.watchPosition(function(pos) {
            var lat = pos.coords.latitude;
            var lng = pos.coords.longitude;
            var heading = pos.coords.heading || 0;
            var speed = pos.coords.speed || 0;

            enviarCoordenadasAlBackend(idPedido, lat, lng, heading, speed);
        }, function(err) {
            console.warn("Error GPS real:", err.message);
            indicator.innerHTML = '⚠️ Esperando señal GPS...';
        }, {
            enableHighAccuracy: true,
            maximumAge: 3000,
            timeout: 10000
        });
    }

    // Detener transmisión GPS
    function detenerGPS() {
        if (watchId !== null) {
            navigator.geolocation.clearWatch(watchId);
            watchId = null;
        }
        if (simulatorInterval !== null) {
            clearInterval(simulatorInterval);
            simulatorInterval = null;
        }
        var indicator = document.getElementById('gps-status-indicator');
        indicator.innerHTML = '⚪ GPS Inactivo';
        indicator.className = 'gps-status-pill';
    }

    // Enviar coordenadas a /api/actualizar_ubicacion.php (Requisito 3)
    function enviarCoordenadasAlBackend(idPedido, lat, lng, heading, speed) {
        fetch('/Chorifest/api/actualizar_ubicacion.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id_pedido: idPedido,
                lat: lat,
                lng: lng,
                heading: heading || 0,
                speed: speed || 0
            })
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (!d.success) {
                console.warn("API GPS Resp:", d.message);
            }
        })
        .catch(function(e) { console.error("Error enviando GPS:", e); });
    }

    <?php if (function_exists('isDevelopmentEnvironment') && isDevelopmentEnvironment()): ?>
    // SIMULADOR GPS PARA DESARROLLO (Exclusivo entorno de pruebas)
    window.toggleSimulador = function(checked) {
        devSimuladorActive = checked;
        var label = document.getElementById('sim-label-text');
        if (label) label.textContent = checked ? 'ACTIVADO' : 'Apagado';

        if (checked) {
            if (!activePedidoId) {
                alert("Debes tener un pedido en estado 'En camino' (haz clic en 'Salí a entregar') para que el simulador envíe coordenadas.");
                return;
            }

            if (watchId !== null) {
                navigator.geolocation.clearWatch(watchId);
                watchId = null;
            }

            var card = document.querySelector('.route-card.active-transit');
            if (!card) return;

            var targetLat = parseFloat(card.dataset.clienteLat) || 2.9273;
            var targetLng = parseFloat(card.dataset.clienteLng) || -75.2819;

            // Punto de inicio simulado (~1.5 km de distancia)
            var startLat = targetLat - 0.012;
            var startLng = targetLng - 0.012;

            currentSimStep = 0;
            var totalSteps = 40;

            var indicator = document.getElementById('gps-status-indicator');
            if (indicator) {
                indicator.innerHTML = '⚡ Simulador GPS Activo';
                indicator.className = 'gps-status-pill active';
            }

            if (simulatorInterval) clearInterval(simulatorInterval);

            simulatorInterval = setInterval(function() {
                currentSimStep++;
                if (currentSimStep > totalSteps) currentSimStep = totalSteps;

                var factor = currentSimStep / totalSteps;
                var currentLat = startLat + (targetLat - startLat) * factor;
                var currentLng = startLng + (targetLng - startLng) * factor;

                enviarCoordenadasAlBackend(activePedidoId, currentLat, currentLng, 45, 25);
            }, 5000); // Cada ~5 segundos (Requisito 3)

        } else {
            if (simulatorInterval) {
                clearInterval(simulatorInterval);
                simulatorInterval = null;
            }
            if (activePedidoId) {
                iniciarGPS(activePedidoId);
            }
        }
    };
    <?php endif; ?>

    window.iniciarEntrega = function(e, idPedido) {
        // Al pasar a En camino, se inicia transmisión
        alert("¡Iniciando ruta de entrega! Chorifest utilizará tu GPS para compartir tu ubicación en tiempo real con el cliente.");
    };

    window.finalizarEntrega = function(e, idPedido) {
        // Al pasar a Entregado, se detiene transmisión (Requisito 3)
        detenerGPS();
    };

})();
</script>
