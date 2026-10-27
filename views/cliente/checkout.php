<style>
.checkout-container {
    display: flex;
    gap: 40px;
    align-items: flex-start;
}
.checkout-form {
    flex: 2;
    background: rgba(10, 10, 15, 0.6);
    border: 1px solid var(--glass-border);
    border-radius: 20px;
    padding: 30px;
}
.checkout-summary {
    flex: 1;
    background: rgba(10, 10, 15, 0.6);
    border: 1px solid var(--glass-border);
    border-radius: 20px;
    padding: 30px;
    position: sticky;
    top: 100px;
}
.form-group {
    margin-bottom: 20px;
}
.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
}
.form-control {
    width: 100%;
    padding: 12px 15px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid var(--glass-border);
    border-radius: 12px;
    color: white;
    font-size: 1rem;
}
.form-control:focus {
    outline: none;
    border-color: var(--primary);
    background: rgba(255, 255, 255, 0.1);
}
select.form-control option {
    background-color: var(--dark);
    color: white;
}
.btn-submit {
    display: block;
    width: 100%;
    text-align: center;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    padding: 15px;
    border-radius: 12px;
    text-decoration: none;
    font-weight: bold;
    font-size: 1.1rem;
    margin-top: 30px;
    transition: transform 0.3s;
    border: none;
    cursor: pointer;
}
.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(255, 75, 43, 0.4);
}
.success-message {
    background: rgba(46, 213, 115, 0.2);
    border: 1px solid #2ed573;
    color: #2ed573;
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    margin-bottom: 20px;
}
.error-message {
    background: rgba(255, 71, 87, 0.2);
    border: 1px solid #ff4757;
    color: #ff4757;
    padding: 15px;
    border-radius: 12px;
    margin-bottom: 20px;
}
@media (max-width: 900px) {
    .checkout-container { flex-direction: column; }
    .checkout-form, .checkout-summary { width: 100%; }
}
</style>

<div class="catalog-header">
    <h1 class="catalog-title">Finalizar Compra</h1>
</div>

<?php if ($success): ?>
    <div class="success-message">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 15px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        <h2>¡Pedido Confirmado!</h2>
        <p style="margin-top: 10px; font-size: 1.1rem;">Tu número de pedido es: <strong>#<?= str_pad($idPedidoGenerado, 5, '0', STR_PAD_LEFT) ?></strong></p>
        <p style="margin-top: 10px;">En breve comenzaremos a prepararlo. Puedes darle seguimiento desde la sección "Mis Pedidos".</p>
        <br>
        <a href="pedidos.php" class="btn-submit" style="display: inline-block; width: auto; padding: 10px 30px;">Ver mis pedidos</a>
    </div>
<?php else: ?>

    <?php if ($error): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="checkout-container">
        <form class="checkout-form" method="POST" action="checkout.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="checkout">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            
            <h3 style="margin-bottom: 20px; border-bottom: 1px solid var(--glass-border); padding-bottom: 10px;">Información de Envío</h3>
            
            <div class="form-group">
                <label for="id_zona">Zona de Cobertura Detectada *</label>
                <select id="id_zona" name="id_zona" class="form-control" required onchange="updateShippingCost()">
                    <option value="">Arrastra el pin en el mapa para detectar tu zona...</option>
                    <?php foreach ($zonasEnvio as $zona): ?>
                        <option value="<?= $zona['id_zona'] ?>" data-cost="0" data-min="<?= $zona['monto_minimo'] ?? 0 ?>">
                            <?= htmlspecialchars($zona['nombre']) ?> (¡Envío Gratis! | Mínimo: $<?= number_format($zona['monto_minimo'] ?? 0, 0) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <small style="color:var(--text-muted); display:block; margin-top:4px;">
                    * La cobertura y el monto mínimo se validan automáticamente con las coordenadas de tu pin en el servidor.
                </small>
            </div>

            <!-- Mapa Leaflet para confirmar ubicación exacta (Requisito 3) -->
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

            <div class="form-group" style="margin-top: 25px;">
                <label style="display:flex; justify-content:space-between; align-items:center;">
                    <span>📍 Confirma tu Ubicación Exacta con el Pin Arrastrable *</span>
                    <small style="color: var(--primary); font-weight: normal; cursor: pointer;" onclick="usarGpsCliente()">📍 Usar mi GPS</small>
                </label>
                <div id="checkout-picker-map" style="height: 280px; border-radius: 12px; border: 1px solid var(--glass-border); overflow: hidden; margin-top: 8px;"></div>
                <input type="hidden" name="latitud" id="latitud_input" value="7.1193">
                <input type="hidden" name="longitud" id="longitud_input" value="-73.1227">
                
                <div id="coverage-status-box" style="margin-top: 10px; padding: 12px 16px; border-radius: 10px; font-size: 0.92rem; display: none;"></div>
            </div>

            <div class="form-group">
                <label for="direccion">Dirección exacta *</label>
                <input type="text" id="direccion" name="direccion" class="form-control" placeholder="Ej: Carrera 15 # 45-20, Apto 301" required>
            </div>

            <div class="form-group">
                <label for="barrio">Barrio</label>
                <input type="text" id="barrio" name="barrio" class="form-control" placeholder="Ej: San Francisco, Cabecera, Provenza...">
            </div>

            <div class="form-group">
                <label for="referencia">Referencia / Indicaciones</label>
                <input type="text" id="referencia" name="referencia" class="form-control" placeholder="Ej: Casa esquinera reja negra, dejar en portería...">
            </div>

            <script>
            var activeZonesGeoJson = <?= json_encode($zonasEnvio) ?>;
            var currentCartSubtotal = <?= (float)$subtotal ?>;

            document.addEventListener('DOMContentLoaded', function() {
                var defaultLat = 7.1193;
                var defaultLng = -73.1227;

                var map = L.map('checkout-picker-map').setView([defaultLat, defaultLng], 14);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap'
                }).addTo(map);

                // Dibujar polígonos de zonas activas
                activeZonesGeoJson.forEach(function(z) {
                    if (z.poligono_geojson) {
                        try {
                            var g = JSON.parse(z.poligono_geojson);
                            L.geoJSON(g, {
                                style: { color: '#22c55e', weight: 2, fillColor: '#22c55e', fillOpacity: 0.18 }
                            }).bindPopup("<strong>" + z.nombre + "</strong><br>Tarifa: <span style='color:#22c55e;font-weight:700;'>¡Envío Gratis!</span><br>Mínimo: $" + Number(z.monto_minimo || 0).toLocaleString()).addTo(map);
                        } catch(e) {}
                    }
                });

                var marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);
                marker.bindPopup("<b>Lugar de Entrega</b><br>Arrastra este pin a tu ubicación exacta.").openPopup();

                function verificarCoberturaServidor(lat, lng) {
                    var box = document.getElementById('coverage-status-box');
                    var btnSubmit = document.querySelector('.btn-submit');
                    box.style.display = 'block';
                    box.style.background = 'rgba(255,255,255,0.05)';
                    box.style.color = '#ccc';
                    box.innerHTML = 'Verificando cobertura en el servidor...';

                    fetch('../../api/validar_cobertura.php?lat=' + lat + '&lng=' + lng + '&subtotal=' + currentCartSubtotal)
                        .then(function(r) { return r.json(); })
                        .then(function(res) {
                            if (res.en_cobertura && res.zona) {
                                // Seleccionar zona en select
                                var select = document.getElementById('id_zona');
                                select.value = res.zona.id_zona;
                                updateShippingCost();

                                if (!res.zona.cumple_minimo) {
                                    box.style.background = 'rgba(234, 179, 8, 0.15)';
                                    box.style.border = '1px solid rgba(234, 179, 8, 0.4)';
                                    box.style.color = '#fef08a';
                                    box.innerHTML = "⚠️ <strong>Zona: " + res.zona.nombre + "</strong>.<br>El monto mínimo de compra es de $" + Number(res.zona.monto_minimo).toLocaleString() + ". Tu subtotal actual es $" + Number(currentCartSubtotal).toLocaleString() + ". Por favor agrega más productos a tu carrito.";
                                    if (btnSubmit) btnSubmit.disabled = true;
                                } else {
                                    box.style.background = 'rgba(34, 197, 94, 0.15)';
                                    box.style.border = '1px solid rgba(34, 197, 94, 0.4)';
                                    box.style.color = '#86efac';
                                    box.innerHTML = "✔ <strong>¡Ubicación dentro de cobertura!</strong><br>Zona asignada: <strong>" + res.zona.nombre + "</strong> (¡Envío Gratis!).";
                                    if (btnSubmit) btnSubmit.disabled = false;
                                }
                            } else {
                                box.style.background = 'rgba(239, 68, 68, 0.15)';
                                box.style.border = '1px solid rgba(239, 68, 68, 0.4)';
                                box.style.color = '#fca5a5';
                                box.innerHTML = "❌ <strong>Fuera de cobertura:</strong> " + (res.message || 'Tu ubicación no está dentro de nuestras zonas activas de entrega.');
                                if (btnSubmit) btnSubmit.disabled = true;
                            }
                        })
                        .catch(function(err) {
                            box.innerText = 'Error al validar cobertura en el servidor.';
                        });
                }

                function setCoords(lat, lng) {
                    document.getElementById('latitud_input').value = lat.toFixed(7);
                    document.getElementById('longitud_input').value = lng.toFixed(7);
                    verificarCoberturaServidor(lat, lng);
                }

                setCoords(defaultLat, defaultLng);

                marker.on('dragend', function(e) {
                    var pos = marker.getLatLng();
                    setCoords(pos.lat, pos.lng);
                });

                map.on('click', function(e) {
                    marker.setLatLng(e.latlng);
                    setCoords(e.latlng.lat, e.latlng.lng);
                });

                window.usarGpsCliente = function() {
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(function(pos) {
                            var lat = pos.coords.latitude;
                            var lng = pos.coords.longitude;
                            map.setView([lat, lng], 16);
                            marker.setLatLng([lat, lng]);
                            setCoords(lat, lng);
                        }, function() {
                            alert('No se pudo obtener tu ubicación GPS automáticamente. Por favor arrastra el pin en el mapa.');
                        });
                    }
                };
            });
            </script>

            <h3 style="margin-top: 40px; margin-bottom: 20px; border-bottom: 1px solid var(--glass-border); padding-bottom: 10px;">Opciones de Pago</h3>

            <div class="form-group">
                <label for="metodo_pago">Método de pago *</label>
                <select id="metodo_pago" name="metodo_pago" class="form-control" required onchange="toggleNequiInfo()">
                    <option value="">Selecciona cómo vas a pagar</option>
                    <option value="Efectivo (Contra entrega)">Efectivo (Pago al recibir)</option>
                    <option value="Transferencia / Nequi">Transferencia Bancaria / Nequi</option>
                    <option value="Tarjeta">Tarjeta (Datafono al entregar)</option>
                </select>
            </div>

            <div id="nequi-info" style="display: none; background: rgba(52, 152, 219, 0.1); border: 1px solid rgba(52, 152, 219, 0.3); padding: 20px; border-radius: 12px; margin-bottom: 20px;">
                <h4 style="color: #3498db; margin-top: 0; margin-bottom: 10px;">Instrucciones para Transferencia</h4>
                <p style="margin: 5px 0;"><strong>Banco/App:</strong> Nequi</p>
                <p style="margin: 5px 0;"><strong>Número:</strong> 322 846 0174</p>
                <p style="margin: 5px 0; margin-bottom: 15px;"><strong>Nombre:</strong> Andres felipe pedriza</p>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="comprobante" style="color: white;">Sube tu comprobante (Captura de pantalla) *</label>
                    <input type="file" id="comprobante" name="comprobante" class="form-control" accept="image/*">
                </div>
            </div>

            <div id="datafono-info" style="display: none; background: rgba(234, 88, 12, 0.1); border: 1px solid rgba(234, 88, 12, 0.3); padding: 20px; border-radius: 12px; margin-bottom: 20px;">
                <h4 style="color: var(--primary); margin-top: 0; margin-bottom: 10px;">Pago con Tarjeta en Casa</h4>
                <p style="margin: 5px 0;">Nuestro domiciliario llevará el <strong>datáfono</strong> hasta tu puerta.</p>
                <p style="margin: 5px 0;">Por favor, ten lista tu tarjeta (Débito o Crédito) al momento de recibir el pedido.</p>
            </div>

            <div class="form-group">
                <label for="observaciones">Observaciones del Pedido</label>
                <textarea id="observaciones" name="observaciones" class="form-control" rows="3" placeholder="Sin cebolla, salsas aparte, etc."></textarea>
            </div>

            <button type="submit" class="btn-submit">Confirmar Pedido</button>
        </form>

        <div class="checkout-summary">
            <h3>Resumen</h3>
            <div style="margin-top: 20px;">
                <?php 
                $subtotal = 0;
                $itemsCarrito = $_SESSION['carrito'] ?? [];
                if (!empty($itemsCarrito)) {
                    foreach ($itemsCarrito as $item) {
                        $itemTotal = ($item['producto']['precio'] ?? 0) * ($item['cantidad'] ?? 1);
                        $subtotal += $itemTotal;
                ?>
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <span><?= $item['cantidad'] ?>x <?= htmlspecialchars($item['producto']['nombre_producto'] ?? 'Producto') ?></span>
                    <span>$<?= number_format($itemTotal, 2) ?></span>
                </div>
                <?php 
                    }
                } else {
                ?>
                <div style="color: #aaa; padding: 15px 0; text-align: center;">
                    Tu carrito está vacío. <a href="catalogo.php" style="color: var(--primary); text-decoration: none; font-weight: 700;">Ver menú &rarr;</a>
                </div>
                <?php } ?>
                
                <div style="display: flex; justify-content: space-between; margin-top: 15px; color: #aaa;">
                    <span>Subtotal</span>
                    <span>$<?= number_format($subtotal, 2) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-top: 10px; color: #aaa;">
                    <span>Domicilio:</span>
                    <span id="shipping-cost" style="color:#22c55e; font-weight:700;">Gratis</span>
                </div>
                
                <div style="display: flex; justify-content: space-between; margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--glass-border); font-size: 1.5rem; font-weight: 800; color: var(--primary);">
                    <span>Total</span>
                    <span id="total-price" data-subtotal="<?= $subtotal ?>">$<?= number_format($subtotal, 2) ?></span>
                </div>
            </div>
        </div>
    </div>

    <script>
    function updateShippingCost() {
        // En Chorifest el envío es gratis; no se realiza ningún cálculo adicional
    }

    function toggleNequiInfo() {
        const metodoPago = document.getElementById('metodo_pago').value;
        const nequiInfo = document.getElementById('nequi-info');
        const datafonoInfo = document.getElementById('datafono-info');
        const comprobanteInput = document.getElementById('comprobante');

        // Reset display
        nequiInfo.style.display = 'none';
        datafonoInfo.style.display = 'none';
        comprobanteInput.required = false;

        if (metodoPago === 'Transferencia / Nequi') {
            nequiInfo.style.display = 'block';
            comprobanteInput.required = true;
        } else if (metodoPago === 'Tarjeta') {
            datafonoInfo.style.display = 'block';
        }
    }
    </script>
<?php endif; ?>
