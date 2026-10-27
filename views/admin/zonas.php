<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<link rel="stylesheet" href="https://unpkg.com/@geoman-io/leaflet-geoman-free@2.14.2/dist/leaflet-geoman.css" />

<style>
    .table-container {
        overflow-x: auto;
        border-radius: 8px;
        border: 1px solid var(--border);
        background: var(--dark);
        margin-top: 15px;
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
    .table tr:last-child td {
        border-bottom: none;
    }
    .table tr:hover {
        background: rgba(255, 255, 255, 0.02);
    }
    .badge {
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 700;
        display: inline-block;
        white-space: nowrap;
        text-align: center;
    }
    .badge-success { background: rgba(46, 213, 115, 0.2); color: #2ed573; border: 1px solid #2ed573; }
    .badge-danger { background: rgba(255, 71, 87, 0.2); color: #ff4757; border: 1px solid #ff4757; }
    .badge-info { background: rgba(30, 144, 255, 0.2); color: #1e90ff; border: 1px solid #1e90ff; }

    .btn {
        padding: 8px 16px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    .btn-primary { background: var(--primary); color: white; }
    .btn-primary:hover { opacity: 0.9; }
    .btn-secondary { background: rgba(255,255,255,0.08); color: var(--text-main); border: 1px solid var(--glass-border); }
    .btn-secondary:hover { background: rgba(255,255,255,0.15); }
    .btn-sm { padding: 5px 10px; font-size: 0.8rem; border-radius: 6px; }
    
    /* Switch interruptor */
    .switch {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 24px;
    }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider {
        position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
        background-color: #3f3f46; transition: .3s; border-radius: 24px;
    }
    .slider:before {
        position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px;
        background-color: white; transition: .3s; border-radius: 50%;
    }
    input:checked + .slider { background-color: #22c55e; }
    input:checked + .slider:before { transform: translateX(20px); }

    /* Modal styles */
    .modal {
        display: none;
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.85);
        z-index: 10000;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(6px);
        padding: 20px;
    }
    .modal.active { display: flex; }
    .modal-content {
        background: #18181b;
        border: 1px solid var(--glass-border);
        border-radius: 16px;
        width: 100%;
        max-width: 900px;
        max-height: 90vh;
        overflow-y: auto;
        padding: 28px;
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.8);
    }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 6px; color: #a1a1aa; font-size: 0.88rem; font-weight: 500; }
    .form-control {
        width: 100%; padding: 10px 14px;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 8px; color: white; font-size: 0.95rem;
    }
    .form-control:focus { outline: none; border-color: var(--primary); }
    .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }

    #mapModalContainer {
        height: 380px;
        width: 100%;
        border-radius: 10px;
        border: 1px solid rgba(255,255,255,0.15);
        margin-top: 8px;
        z-index: 1;
    }
    #overviewMap {
        height: 300px;
        width: 100%;
        border-radius: 12px;
        border: 1px solid var(--border);
        margin-bottom: 20px;
        z-index: 1;
    }
</style>

<div class="card" style="margin-bottom:20px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; flex-wrap:wrap; gap:10px;">
        <div>
            <h2 style="font-size:1.4rem; font-weight:800; margin-bottom:4px;">Zonas de Cobertura y Tarifas</h2>
            <p style="color:#a1a1aa; font-size:0.88rem; margin:0;">
                Dibuja polígonos con OpenStreetMap + Leaflet-Geoman v2.14.2 para delimitar áreas de servicio y monto mínimo.
            </p>
        </div>
        <button class="btn btn-primary" onclick="openModal('create')">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
            + Nueva Zona
        </button>
    </div>

    <?php 
    $flashError = $_SESSION['flash_error'] ?? '';
    $flashSuccess = $_SESSION['flash_success'] ?? '';
    unset($_SESSION['flash_error'], $_SESSION['flash_success']);
    ?>

    <?php if (!empty($flashError)): ?>
        <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; border-radius: 10px; padding: 14px 18px; color: #fca5a5; font-size: 0.92rem; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <span style="font-size: 1.3rem;">⚠️</span>
            <div><?= htmlspecialchars($flashError) ?></div>
        </div>
    <?php endif; ?>

    <?php if (!empty($flashSuccess)): ?>
        <div style="background: rgba(34, 197, 94, 0.15); border: 1px solid #22c55e; border-radius: 10px; padding: 14px 18px; color: #86efac; font-size: 0.92rem; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <span style="font-size: 1.3rem;">✔</span>
            <div><?= htmlspecialchars($flashSuccess) ?></div>
        </div>
    <?php endif; ?>

    <!-- Mapa General con todas las zonas -->
    <div id="overviewMap"></div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Zona</th>
                    <th>Tarifa Domicilio</th>
                    <th>Monto Mínimo</th>
                    <th>Tiempo Estimado</th>
                    <th>Polígono</th>
                    <th>Estado / Interruptor</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($zonas)): ?>
                    <tr><td colspan="8" style="text-align:center; padding:30px; color:#71717a;">No hay zonas registradas.</td></tr>
                <?php else: ?>
                    <?php foreach ($zonas as $zona): ?>
                        <tr>
                            <td>#<?= $zona['id_zona'] ?></td>
                            <td><strong><?= htmlspecialchars($zona['nombre']) ?></strong></td>
                            <td>
                                <?php if ((float)$zona['costo_domicilio'] == 0): ?>
                                    <span class="badge badge-success">¡Gratis! ($0.00)</span>
                                <?php else: ?>
                                    <span style="color:#22c55e; font-weight:700;">$<?= number_format($zona['costo_domicilio'], 2) ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="color:#f59e0b; font-weight:700;">$<?= number_format($zona['monto_minimo'] ?? 0, 2) ?></td>
                            <td><?= (int)$zona['tiempo_estimado_min'] ?> mins</td>
                            <td>
                                <?php if (!empty($zona['poligono_geojson'])): ?>
                                    <span class="badge badge-info">✔ GeoJSON Definido</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">⚠️ Sin Polígono</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" action="zonas.php" style="display:inline;" id="toggleForm_<?= $zona['id_zona'] ?>">
                                    <input type="hidden" name="action" value="toggle_estado">
                                    <input type="hidden" name="id_zona" value="<?= $zona['id_zona'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                    <label class="switch" title="Activar / Desactivar zona">
                                        <input type="checkbox" <?= $zona['estado'] === 'ACTIVA' ? 'checked' : '' ?> onchange="document.getElementById('toggleForm_<?= $zona['id_zona'] ?>').submit()">
                                        <span class="slider"></span>
                                    </label>
                                </form>
                                <span style="font-size:0.8rem; margin-left:6px; color:<?= $zona['estado'] === 'ACTIVA' ? '#22c55e' : '#ef4444' ?>;">
                                    <?= $zona['estado'] ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-secondary btn-sm" onclick='openEditModal(<?= json_encode($zona) ?>)'>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                                    Editar / Polígono
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Form con Leaflet Draw -->
<div id="zonaModal" class="modal">
    <div class="modal-content">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
            <h3 id="modalTitle" style="margin:0; font-size:1.2rem; font-weight:700;">Crear Zona de Envío</h3>
            <button type="button" onclick="closeModal()" style="background:none; border:none; color:#a1a1aa; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>
        
        <form method="POST" action="zonas.php" id="formZona">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id_zona" id="formId">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            <input type="hidden" name="poligono_geojson" id="formGeojson">

            <div class="form-row">
                <div class="form-group">
                    <label>Nombre de la Zona *</label>
                    <input type="text" name="nombre" id="formNombre" class="form-control" placeholder="Ej: Zona Norte, Cabecera, Centro" required>
                </div>
                <div class="form-group">
                    <label>Monto Mínimo de Pedido ($) *</label>
                    <input type="number" name="monto_minimo" id="formMinimo" class="form-control" step="0.01" min="0" value="0" placeholder="0" required>
                </div>
                <div class="form-group">
                    <label>Tiempo Estimado (Minutos) *</label>
                    <input type="number" name="tiempo_estimado_min" id="formTiempo" class="form-control" min="5" value="30" required>
                </div>
            </div>

            <div class="form-group">
                <label>Estado</label>
                <select name="estado" id="formEstado" class="form-control" style="max-width:200px;">
                    <option value="ACTIVA">ACTIVA</option>
                    <option value="INACTIVA">INACTIVA</option>
                </select>
            </div>

            <div class="form-group">
                <label style="display:flex; justify-content:space-between; align-items:center;">
                    <span>Polígono de Cobertura (Usa la barra de herramientas del mapa para dibujar o editar)</span>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="clearModalPolygon()" style="font-size:0.75rem;">Limpiar Polígono</button>
                </label>
                <div id="mapModalContainer"></div>
                <span id="geoJsonStatus" style="font-size:0.78rem; color:#a1a1aa; display:block; margin-top:5px;">
                    Ningún polígono dibujado aún. Haz clic en el botón de polígono para comenzar.
                </span>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnGuardar">Guardar Zona</button>
            </div>
        </form>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="https://unpkg.com/@geoman-io/leaflet-geoman-free@2.14.2/dist/leaflet-geoman.min.js"></script>

<script>
var allZonesData = <?= json_encode($zonas) ?>;
var overviewMap = null;
var modalMap = null;
var drawnLayer = null;

// Inicializar mapa general
document.addEventListener('DOMContentLoaded', function() {
    initOverviewMap();
});

function initOverviewMap() {
    // Centro por defecto: Bucaramanga / Floridablanca (7.1193, -73.1227)
    overviewMap = L.map('overviewMap').setView([7.1193, -73.1227], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    }).addTo(overviewMap);

    var bounds = [];
    allZonesData.forEach(function(z) {
        if (z.poligono_geojson) {
            try {
                var geo = JSON.parse(z.poligono_geojson);
                var isActiva = (z.estado === 'ACTIVA');
                var tarifaTexto = Number(z.costo_domicilio) === 0 ? "<span style='color:#22c55e;font-weight:700;'>¡Gratis!</span>" : "$" + Number(z.costo_domicilio).toLocaleString();
                var layer = L.geoJSON(geo, {
                    style: {
                        color: isActiva ? '#22c55e' : '#ef4444',
                        weight: 2,
                        fillColor: isActiva ? '#22c55e' : '#ef4444',
                        fillOpacity: 0.25
                    }
                }).bindPopup("<strong>" + z.nombre + "</strong><br>Tarifa: " + tarifaTexto + "<br>Mínimo: $" + Number(z.monto_minimo).toLocaleString() + "<br>Estado: " + z.estado).addTo(overviewMap);
                bounds.push(layer.getBounds());
            } catch(e) {}
        }
    });

    if (bounds.length > 0) {
        var groupBounds = bounds[0];
        bounds.forEach(function(b) { groupBounds.extend(b); });
        overviewMap.fitBounds(groupBounds, { padding: [20, 20] });
    }
}

function initModalMap() {
    if (modalMap) {
        modalMap.remove();
        modalMap = null;
    }

    modalMap = L.map('mapModalContainer').setView([7.1193, -73.1227], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    }).addTo(modalMap);

    // Configurar Leaflet-Geoman Free v2.14.2
    modalMap.pm.addControls({
        position: 'topleft',
        drawMarker: false,
        drawCircleMarker: false,
        drawPolyline: false,
        drawRectangle: true,
        drawPolygon: true,
        drawCircle: false,
        editMode: true,
        dragMode: true,
        cutPolygon: false,
        removalMode: true
    });

    modalMap.on('pm:create', function(e) {
        if (drawnLayer) {
            modalMap.removeLayer(drawnLayer);
        }
        drawnLayer = e.layer;
        updateGeoJsonInput();

        drawnLayer.on('pm:edit', updateGeoJsonInput);
        drawnLayer.on('pm:dragend', updateGeoJsonInput);
    });

    modalMap.on('pm:remove', function(e) {
        if (drawnLayer === e.layer) {
            drawnLayer = null;
            updateGeoJsonInput();
        }
    });
}

function updateGeoJsonInput() {
    if (drawnLayer) {
        var geojson = drawnLayer.toGeoJSON();
        document.getElementById('formGeojson').value = JSON.stringify(geojson.geometry);
        document.getElementById('geoJsonStatus').innerHTML = "<span style='color:#22c55e;'>✔ Polígono delimitado (" + (geojson.geometry.type || 'Polygon') + ")</span>";
    } else {
        document.getElementById('formGeojson').value = '';
        document.getElementById('geoJsonStatus').innerHTML = "<span style='color:#f87171;'>⚠️ Sin polígono dibujado.</span>";
    }
}

function clearModalPolygon() {
    if (drawnLayer && modalMap) {
        modalMap.removeLayer(drawnLayer);
        drawnLayer = null;
        updateGeoJsonInput();
    }
}

function openModal(mode) {
    document.getElementById('zonaModal').classList.add('active');
    setTimeout(function() {
        initModalMap();
        if (mode === 'create') {
            document.getElementById('modalTitle').innerText = 'Nueva Zona de Envío';
            document.getElementById('formAction').value = 'create';
            document.getElementById('formId').value = '';
            document.getElementById('formNombre').value = '';
            document.getElementById('formMinimo').value = '0';
            document.getElementById('formTiempo').value = '30';
            document.getElementById('formEstado').value = 'ACTIVA';
            document.getElementById('formGeojson').value = '';
            document.getElementById('geoJsonStatus').innerText = 'Dibuja el polígono de cobertura en el mapa.';
            drawnLayer = null;
        }
    }, 200);
}

function openEditModal(zona) {
    document.getElementById('zonaModal').classList.add('active');
    setTimeout(function() {
        initModalMap();
        document.getElementById('modalTitle').innerText = 'Editar Zona #' + zona.id_zona + ' (' + zona.nombre + ')';
        document.getElementById('formAction').value = 'update';
        document.getElementById('formId').value = zona.id_zona;
        document.getElementById('formNombre').value = zona.nombre;
        document.getElementById('formMinimo').value = zona.monto_minimo || 0;
        document.getElementById('formTiempo').value = zona.tiempo_estimado_min;
        document.getElementById('formEstado').value = zona.estado;
        document.getElementById('formGeojson').value = zona.poligono_geojson || '';

        drawnLayer = null;
        if (zona.poligono_geojson) {
            try {
                var parsed = JSON.parse(zona.poligono_geojson);
                var geoLayer = L.geoJSON(parsed, {
                    style: { color: '#3b82f6', weight: 3, fillColor: '#3b82f6', fillOpacity: 0.3 }
                });
                
                // Extraer la primera capa como drawnLayer
                geoLayer.eachLayer(function(l) {
                    drawnLayer = l;
                    l.addTo(modalMap);
                    l.on('pm:edit', updateGeoJsonInput);
                    l.on('pm:dragend', updateGeoJsonInput);
                });

                modalMap.fitBounds(geoLayer.getBounds(), { padding: [30, 30] });
                document.getElementById('geoJsonStatus').innerHTML = "<span style='color:#22c55e;'>✔ Polígono cargado listo para editar o modificar vértices.</span>";
            } catch(e) {
                document.getElementById('geoJsonStatus').innerText = 'Error al cargar GeoJSON.';
            }
        } else {
            document.getElementById('geoJsonStatus').innerText = 'Esta zona no tiene coordenadas. Dibuja un polígono.';
        }
    }, 200);
}

function closeModal() {
    document.getElementById('zonaModal').classList.remove('active');
    if (modalMap) {
        modalMap.remove();
        modalMap = null;
    }
}
</script>
