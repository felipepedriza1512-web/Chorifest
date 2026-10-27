<?php
$idPedido = (int)($_GET['id'] ?? 0);
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
.dark-map-tiles {
    filter: invert(100%) hue-rotate(180deg) brightness(95%) contrast(90%);
}
.seguimiento-wrapper {
    max-width: 950px;
    margin: 30px auto;
    padding: 0 20px;
}

.seguimiento-card {
    background: rgba(10, 10, 15, 0.7);
    border: 1px solid var(--glass-border);
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.5);
}

.seguimiento-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--glass-border);
    padding-bottom: 20px;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 15px;
}

.pedido-title-code {
    font-size: 1.8rem;
    font-weight: 800;
    color: white;
    font-family: 'Lora', Georgia, serif;
}

.estado-pill-lg {
    padding: 8px 20px;
    border-radius: 30px;
    font-weight: 800;
    font-size: 0.95rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

/* Timeline Stepper (Regla 2 del prompt) */
.timeline-stepper {
    display: flex;
    justify-content: space-between;
    position: relative;
    margin: 40px 0 40px;
    padding: 0 10px;
}

.timeline-stepper::before {
    content: '';
    position: absolute;
    top: 22px;
    left: 40px;
    right: 40px;
    height: 4px;
    background: rgba(255, 255, 255, 0.1);
    z-index: 1;
}

.timeline-progress-bar {
    position: absolute;
    top: 22px;
    left: 40px;
    height: 4px;
    background: linear-gradient(to right, #ea580c, #2ed573);
    z-index: 1;
    transition: width 0.5s ease;
    width: 0%;
}

.step-node {
    position: relative;
    z-index: 2;
    text-align: center;
    flex: 1;
}

.step-circle {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: var(--darker);
    border: 3px solid rgba(255, 255, 255, 0.2);
    margin: 0 auto 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.1rem;
    color: var(--text-muted);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.step-node.completed .step-circle {
    background: #2ed573;
    border-color: #2ed573;
    color: white;
    box-shadow: 0 0 15px rgba(46, 213, 115, 0.4);
}

.step-node.active .step-circle {
    background: var(--primary);
    border-color: #ff8a2a;
    color: white;
    box-shadow: 0 0 25px rgba(234, 88, 12, 0.8);
    transform: scale(1.15);
    animation: pulseGlow 2s infinite;
}

@keyframes pulseGlow {
    0% { box-shadow: 0 0 0 0 rgba(234, 88, 12, 0.7); }
    70% { box-shadow: 0 0 0 15px rgba(234, 88, 12, 0); }
    100% { box-shadow: 0 0 0 0 rgba(234, 88, 12, 0); }
}

.step-title {
    font-weight: 700;
    font-size: 0.88rem;
    color: var(--text-muted);
    margin-bottom: 4px;
}

.step-node.active .step-title, .step-node.completed .step-title {
    color: white;
}

.step-time {
    font-size: 0.75rem;
    color: var(--primary);
    font-weight: 600;
    min-height: 18px;
}

/* Map Container */
.map-section {
    position: relative;
    border-radius: 16px;
    overflow: hidden;
    border: 1px solid var(--glass-border);
    margin-top: 30px;
    display: none; /* Se activa SOLO cuando está en camino (Regla 2) */
}

#mapa-seguimiento {
    height: 420px;
    width: 100%;
    background: #0a0303;
}

.map-overlay-info {
    position: absolute;
    top: 15px;
    left: 15px;
    right: 15px;
    z-index: 1000;
    display: flex;
    justify-content: space-between;
    align-items: center;
    pointer-events: none;
    flex-wrap: wrap;
    gap: 10px;
}

.eta-pill {
    background: rgba(10, 3, 3, 0.85);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 138, 42, 0.4);
    padding: 10px 20px;
    border-radius: 30px;
    color: white;
    font-weight: 700;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.5);
    pointer-events: auto;
}

.signal-status {
    background: rgba(10, 3, 3, 0.85);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.8rem;
    color: var(--text-muted);
    pointer-events: auto;
}

/* Banner de estado (Cuando el mapa está oculto) */
.info-banner {
    background: rgba(255, 255, 255, 0.03);
    border: 1px dashed var(--glass-border);
    border-radius: 16px;
    padding: 30px;
    text-align: center;
    margin-top: 30px;
}

/* Calificación (Al entregar) */
.calificacion-container {
    background: rgba(46, 213, 115, 0.05);
    border: 1px solid rgba(46, 213, 115, 0.3);
    border-radius: 16px;
    padding: 30px;
    text-align: center;
    margin-top: 30px;
    display: none;
}

.stars-rating {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin: 20px 0;
}

.star-btn {
    font-size: 2.2rem;
    color: rgba(255,255,255,0.2);
    cursor: pointer;
    transition: transform 0.2s, color 0.2s;
    user-select: none;
}

.star-btn:hover, .star-btn.selected {
    color: #ffc107;
    transform: scale(1.2);
}

@media (max-width: 768px) {
    .timeline-stepper { flex-wrap: wrap; gap: 15px; }
    .timeline-stepper::before, .timeline-progress-bar { display: none; }
    .step-node { width: 30%; margin-bottom: 15px; }
}
</style>

<div class="seguimiento-wrapper">
    <div class="seguimiento-card">
        <div class="seguimiento-header">
            <div>
                <a href="pedidos.php" style="color: var(--primary); text-decoration: none; font-size: 0.9rem; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; margin-bottom: 8px;">
                    ← Volver a Mis Pedidos
                </a>
                <div class="pedido-title-code">Pedido #<?= str_pad($idPedido, 5, '0', STR_PAD_LEFT) ?></div>
            </div>
            <div>
                <span id="pill-estado-actual" class="estado-pill-lg badge-primary">
                    Cargando estado...
                </span>
            </div>
        </div>

        <!-- Línea de tiempo de 6 pasos (Regla 2) -->
        <div class="timeline-stepper">
            <div class="timeline-progress-bar" id="timeline-bar"></div>

            <div class="step-node" id="step-1">
                <div class="step-circle">1</div>
                <div class="step-title">Recibido</div>
                <div class="step-time" id="time-step-1">--:--</div>
            </div>

            <div class="step-node" id="step-2">
                <div class="step-circle">2</div>
                <div class="step-title">Aceptado</div>
                <div class="step-time" id="time-step-2">--:--</div>
            </div>

            <div class="step-node" id="step-3">
                <div class="step-circle">3</div>
                <div class="step-title">En preparación</div>
                <div class="step-time" id="time-step-3">--:--</div>
            </div>

            <div class="step-node" id="step-4">
                <div class="step-circle">4</div>
                <div class="step-title">Empacado</div>
                <div class="step-time" id="time-step-4">--:--</div>
            </div>

            <div class="step-node" id="step-5">
                <div class="step-circle">5</div>
                <div class="step-title">En camino</div>
                <div class="step-time" id="time-step-5">--:--</div>
            </div>

            <div class="step-node" id="step-6">
                <div class="step-circle">6</div>
                <div class="step-title">Entregado</div>
                <div class="step-time" id="time-step-6">--:--</div>
            </div>
        </div>

        <!-- Banner descriptivo mientras se prepara (Antes de "En camino") -->
        <div id="info-banner-preparando" class="info-banner">
            <div style="font-size: 3rem; margin-bottom: 10px;">🔥</div>
            <h3 id="banner-titulo" style="font-size: 1.3rem; margin-bottom: 8px;">Tu pedido se encuentra en preparación</h3>
            <p id="banner-sub" style="color: var(--text-muted); max-width: 500px; margin: 0 auto 15px;">
                Nuestros maestros parilleros están preparando tus productos. El mapa interactivo en tiempo real se activará automáticamente cuando el domiciliario salga con tu pedido.
            </p>
            <div style="font-size: 0.85rem; color: var(--primary); font-weight: 600;">
                ⚡ La pantalla se actualiza en tiempo real sin recargar la página.
            </div>
        </div>

        <!-- Mapa de seguimiento en vivo con Leaflet (Paso 5: En camino) -->
        <div id="map-section-wrapper" class="map-section">
            <div class="map-overlay-info">
                <div class="eta-pill">
                    <span style="font-size: 1.2rem;">🛵</span>
                    <span id="eta-text">Calculando tiempo estimado...</span>
                </div>
                <div class="signal-status" id="signal-status">
                    🟢 Transmitiendo GPS en vivo
                </div>
            </div>
            <div id="mapa-seguimiento"></div>
        </div>

        <!-- Sección de Agradecimiento y Calificación (Al llegar a Entregado) -->
        <div id="calificacion-section" class="calificacion-container">
            <div style="font-size: 3.5rem; margin-bottom: 10px;">🎉</div>
            <h2 style="color: #2ed573; font-size: 1.8rem; font-weight: 800; margin-bottom: 10px;">¡Gracias por tu compra en Chorifest!</h2>
            <p style="color: var(--text-muted); font-size: 1rem; max-width: 600px; margin: 0 auto 20px;">
                Tu pedido ha sido entregado exitosamente. Esperamos que disfrutes del mejor sabor a la brasa. Por favor, califica tu experiencia:
            </p>

            <div id="form-calificacion">
                <div class="stars-rating">
                    <span class="star-btn" data-val="1">★</span>
                    <span class="star-btn" data-val="2">★</span>
                    <span class="star-btn" data-val="3">★</span>
                    <span class="star-btn" data-val="4">★</span>
                    <span class="star-btn" data-val="5">★</span>
                </div>
                <input type="hidden" id="puntuacion_val" value="5">

                <div class="form-group" style="max-width: 500px; margin: 15px auto;">
                    <textarea id="calif_comentario" class="form-control" rows="2" placeholder="Déjanos un comentario adicional (opcional)..."></textarea>
                </div>

                <button type="button" class="btn btn-primary" onclick="enviarCalificacion()" style="padding: 12px 30px; border-radius: 25px; font-weight: 700;">
                    Enviar Calificación
                </button>
            </div>

            <div id="calificacion-exito" style="display: none; color: #2ed573; font-weight: bold; font-size: 1.1rem; margin-top: 20px;">
                ✓ ¡Tu calificación ha sido registrada! Gracias por tus comentarios.
            </div>
        </div>

    </div>
</div>

<script>
(function() {
    var idPedido = <?= $idPedido ?>;
    var leafletMap = null;
    var markerCliente = null;
    var markerDomi = null;
    var polylineRuta = null;
    var lastRouteFetchTime = 0;

    var currentStatusId = 0;
    var currentDomiLat = null;
    var currentDomiLng = null;

    // Íconos personalizados para el mapa Leaflet
    var iconClient = L.divIcon({
        className: 'custom-pin-client',
        html: '<div style="background:#e74c3c; width:34px; height:34px; border-radius:50%; border:3px solid white; display:flex; align-items:center; justify-content:center; box-shadow:0 0 15px rgba(231,76,60,0.8); font-size:16px;">🏠</div>',
        iconSize: [34, 34],
        iconAnchor: [17, 17]
    });

    var iconDomi = L.divIcon({
        className: 'custom-pin-domi',
        html: '<div style="background:#ff8a2a; width:40px; height:40px; border-radius:50%; border:3px solid white; display:flex; align-items:center; justify-content:center; box-shadow:0 0 20px rgba(255,138,42,0.9); font-size:20px; transition: all 0.5s ease;">🛵</div>',
        iconSize: [40, 40],
        iconAnchor: [20, 20]
    });

    // Mapeo de estados a pasos de la línea de tiempo
    // 1 / 8 -> paso 1 (Recibido / Pendiente verificación)
    // 2 -> paso 2 (Aceptado)
    // 3 -> paso 3 (En preparación)
    // 4 -> paso 4 (Empacado)
    // 5 -> paso 5 (En camino)
    // 6 -> paso 6 (Entregado)

    function mapStatusToStep(id) {
        if (id === 1 || id === 8) return 1;
        if (id === 2) return 2;
        if (id === 3) return 3;
        if (id === 4) return 4;
        if (id === 5) return 5;
        if (id === 6) return 6;
        return 1;
    }

    function cargarSeguimiento() {
        fetch('/Chorifest/api/obtener_seguimiento.php?id_pedido=' + idPedido)
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (!data.success) {
                    return;
                }

                var pedido = data.pedido;
                var historial = data.historial || [];
                var cliente = data.cliente || {};
                var ubicDomi = data.ubicacion_domiciliario;
                var calif = data.calificacion;

                currentStatusId = pedido.id_estado;

                // 1. Actualizar Badge de Estado Principal
                var pill = document.getElementById('pill-estado-actual');
                pill.textContent = pedido.estado_nombre;
                
                if (currentStatusId === 6) {
                    pill.className = 'estado-pill-lg badge-success';
                } else if (currentStatusId === 7 || currentStatusId === 10) {
                    pill.className = 'estado-pill-lg badge-primary';
                } else {
                    pill.className = 'estado-pill-lg badge-primary';
                }

                // 2. Actualizar Stepper Timeline con timestamps históricos
                var stepActivo = mapStatusToStep(currentStatusId);
                var timestampsMap = {};

                historial.forEach(function(item) {
                    var s = mapStatusToStep(item.id_estado);
                    if (!timestampsMap[s]) {
                        timestampsMap[s] = item.hora_formateada;
                    }
                });

                for (var i = 1; i <= 6; i++) {
                    var node = document.getElementById('step-' + i);
                    var timeElem = document.getElementById('time-step-' + i);
                    
                    timeElem.textContent = timestampsMap[i] || '';

                    node.classList.remove('active', 'completed');
                    if (i < stepActivo) {
                        node.classList.add('completed');
                    } else if (i === stepActivo) {
                        node.classList.add(currentStatusId === 6 ? 'completed' : 'active');
                    }
                }

                // Barra de progreso
                var pct = ((stepActivo - 1) / 5) * 100;
                document.getElementById('timeline-bar').style.width = pct + '%';

                // 3. Control de visualización del Mapa vs Banners (Regla 2 del prompt)
                var mapSection = document.getElementById('map-section-wrapper');
                var infoBanner = document.getElementById('info-banner-preparando');
                var califSection = document.getElementById('calificacion-section');

                if (currentStatusId === 5) { // En camino
                    if (cliente && cliente.tiene_coordenadas && cliente.lat && cliente.lng) {
                        mapSection.style.display = 'block';
                        infoBanner.style.display = 'none';
                        califSection.style.display = 'none';

                        // Inicializar mapa Leaflet si no existe
                        if (!leafletMap) {
                            initLeafletMap(cliente.lat, cliente.lng);
                        }

                        // Actualizar marcadores y posición del domiciliario
                        if (ubicDomi) {
                            actualizarPosicionDomi(ubicDomi.lat, ubicDomi.lng, cliente.lat, cliente.lng);
                            
                            // Estado de señal (Regla 3)
                            var signalElem = document.getElementById('signal-status');
                            if (ubicDomi.es_reciente) {
                                signalElem.innerHTML = '🟢 Transmitiendo GPS en vivo';
                                signalElem.style.borderColor = 'rgba(46, 213, 115, 0.4)';
                            } else {
                                signalElem.innerHTML = '⚠️ Última ubicación hace ' + ubicDomi.segundos_transcurridos + 's (Sin señal)';
                                signalElem.style.borderColor = 'rgba(255, 165, 2, 0.4)';
                            }
                        }
                    } else {
                        // Pedido histórico o sin geolocalización: mensaje amigable sin crasheo de mapa
                        mapSection.style.display = 'none';
                        infoBanner.style.display = 'block';
                        califSection.style.display = 'none';

                        document.getElementById('banner-titulo').textContent = '🛵 Pedido en camino a tu dirección';
                        document.getElementById('banner-sub').innerHTML = '<strong>📍 Coordenadas no registradas (Pedido antiguo).</strong><br>El domiciliario se dirige a tu dirección física: <em>' + (cliente.direccion || 'Dirección acordada') + '</em>.';
                    }
                } else if (currentStatusId === 6) { // Entregado -> OCULTAR MAPA & MOSTRAR AGRADECIMIENTO
                    mapSection.style.display = 'none';
                    infoBanner.style.display = 'none';
                    califSection.style.display = 'block';

                    if (calif) {
                        document.getElementById('form-calificacion').style.display = 'none';
                        var exito = document.getElementById('calificacion-exito');
                        exito.style.display = 'block';
                        exito.textContent = '✓ Ya calificaste este pedido con ' + calif.puntuacion + ' ★';
                    }
                } else { // Antes de "En camino" (1, 2, 3, 4) -> OCULTAR MAPA & MOSTRAR BANNER
                    mapSection.style.display = 'none';
                    infoBanner.style.display = 'block';
                    califSection.style.display = 'none';

                    document.getElementById('banner-titulo').textContent = 'Estado: ' + pedido.estado_nombre;
                    document.getElementById('banner-sub').textContent = 'Tu pedido está siendo procesado en nuestro restaurante. El mapa con el rastreo GPS en vivo se activará tan pronto como el domiciliario salga en camino a tu dirección.';
                }
            })
            .catch(function(err) {
                console.error("Error actualizando seguimiento:", err);
            });
    }

    // Inicializar Mapa Leaflet con Tiles Oscuras CartoDB
    function initLeafletMap(cLat, cLng) {
        leafletMap = L.map('mapa-seguimiento').setView([cLat, cLng], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            className: 'dark-map-tiles',
            maxZoom: 19
        }).addTo(leafletMap);

        // Marcador del Cliente
        markerCliente = L.marker([cLat, cLng], { icon: iconClient }).addTo(leafletMap);
        markerCliente.bindPopup("<b>Tu Dirección de Entrega</b>").openPopup();
    }

    // Actualizar marcadores e interpolar suavemente el movimiento del domiciliario (Regla 3)
    function actualizarPosicionDomi(dLat, dLng, cLat, cLng) {
        if (!markerDomi) {
            markerDomi = L.marker([dLat, dLng], { icon: iconDomi }).addTo(leafletMap);
            markerDomi.bindPopup("<b>Domiciliario Chorifest</b>");
            
            // Ajustar vista para incluir ambos puntos
            var group = new L.featureGroup([markerCliente, markerDomi]);
            leafletMap.fitBounds(group.getBounds().pad(0.2));
        } else {
            // Animación suave de interpolación
            markerDomi.setLatLng([dLat, dLng]);
        }

        // Consultar OSRM para trazar la ruta y calcular ETA cada ~15 segundos (Regla 4)
        var now = Date.now();
        if (now - lastRouteFetchTime > 15000 || !polylineRuta) {
            lastRouteFetchTime = now;
            calcularRutaOSRM(dLat, dLng, cLat, cLng);
        }
    }

    // Calcular Ruta y Tiempo Estimado usando API OSRM (Regla 4 del prompt - sin Google)
    function calcularRutaOSRM(dLat, dLng, cLat, cLng) {
        var url = 'https://router.project-osrm.org/route/v1/driving/' + dLng + ',' + dLat + ';' + cLng + ',' + cLat + '?overview=full&geometries=geojson';

        fetch(url)
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.routes && res.routes.length > 0) {
                    var route = res.routes[0];
                    var duracionMins = Math.ceil(route.duration / 60);
                    var distanciaKm = (route.distance / 1000).toFixed(1);

                    document.getElementById('eta-text').textContent = 'Llegada estimada: ~' + duracionMins + ' min (' + distanciaKm + ' km)';

                    // Dibujar línea de ruta GeoJSON en el mapa
                    var coords = route.geometry.coordinates.map(function(c) { return [c[1], c[0]]; });
                    
                    if (polylineRuta) {
                        leafletMap.removeLayer(polylineRuta);
                    }

                    polylineRuta = L.polyline(coords, {
                        color: '#ff8a2a',
                        weight: 5,
                        opacity: 0.85,
                        dashArray: '8, 8'
                    }).addTo(leafletMap);

                } else {
                    document.getElementById('eta-text').textContent = 'Tiempo estimado no disponible';
                }
            })
            .catch(function() {
                // Manejo de fallas de API gratuita (Regla 4)
                document.getElementById('eta-text').textContent = 'Tiempo estimado no disponible';
            });
    }

    // Calificación por estrellas (Paso 6: Entregado)
    var stars = document.querySelectorAll('.star-btn');
    stars.forEach(function(star) {
        star.addEventListener('click', function() {
            var val = parseInt(this.dataset.val);
            document.getElementById('puntuacion_val').value = val;

            stars.forEach(function(s) {
                var sVal = parseInt(s.dataset.val);
                if (sVal <= val) {
                    s.classList.add('selected');
                } else {
                    s.classList.remove('selected');
                }
            });
        });
    });

    window.enviarCalificacion = function() {
        var punt = document.getElementById('puntuacion_val').value;
        var com = document.getElementById('calif_comentario').value;

        fetch('/Chorifest/api/guardar_calificacion.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id_pedido: idPedido,
                puntuacion: punt,
                comentario: com
            })
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) {
                document.getElementById('form-calificacion').style.display = 'none';
                var exito = document.getElementById('calificacion-exito');
                exito.style.display = 'block';
                exito.textContent = '✓ ' + d.message;
            } else {
                alert(d.message);
            }
        });
    };

    // Polling cada 5 segundos para actualización en tiempo real sin recargar (Regla 2)
    cargarSeguimiento();
    setInterval(cargarSeguimiento, 5000);
})();
</script>
