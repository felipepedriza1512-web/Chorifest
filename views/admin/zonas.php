<style>
    .table-container {
        overflow-x: auto;
        border-radius: 8px;
        border: 1px solid var(--border);
        background: var(--dark);
    }
    .table {
        width: 100%;
        border-collapse: collapse;
    }
    .table th, .table td {
        padding: 16px 20px;
        text-align: left;
        border-bottom: 1px solid var(--border);
        vertical-align: middle;
        font-size: 0.95rem;
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
    .table tr {
        transition: background 0.2s ease;
    }
    .table tr:hover {
        background: rgba(255, 255, 255, 0.02);
    }
    .badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: bold;
        display: inline-block;
        white-space: nowrap;
        text-align: center;
    }
    .badge-success {
        background: rgba(46, 213, 115, 0.2);
        color: #2ed573;
        border: 1px solid #2ed573;
    }
    .badge-danger {
        background: rgba(255, 71, 87, 0.2);
        color: #ff4757;
        border: 1px solid #ff4757;
    }
    .btn {
        padding: 8px 16px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        border: none;
        display: inline-block;
        text-decoration: none;
        transition: background 0.2s, transform 0.1s;
    }
    .btn-primary {
        background: var(--primary);
        color: white;
    }
    .btn-primary:hover {
        background: var(--primary-light);
        color: var(--primary);
    }
    .btn-secondary {
        background: var(--border);
        color: var(--text-main);
    }
    .btn-secondary:hover {
        background: var(--text-muted);
        color: var(--dark);
    }
    .btn-sm {
        padding: 5px 12px;
        font-size: 0.85rem;
    }
    .btn:active { transform: scale(0.97); }
    
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
    }
    .form-group {
        margin-bottom: 15px;
    }
    .form-group label {
        display: block;
        margin-bottom: 5px;
        color: #ccc;
    }
    .form-control {
        width: 100%;
        padding: 10px;
        background: rgba(255,255,255,0.05);
        border: 1px solid var(--glass-border);
        border-radius: 8px;
        color: white;
    }
    .form-control option {
        background-color: var(--dark);
        color: white;
    }
</style>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h3>Configuración de Zonas de Envío</h3>
        <button class="btn btn-primary" onclick="openModal('create')">+ Nueva Zona</button>
    </div>
    
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre de la Zona</th>
                    <th>Costo Domicilio</th>
                    <th>Tiempo Estimado</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($zonas as $zona): ?>
                    <tr>
                        <td><?= $zona['id_zona'] ?></td>
                        <td><strong><?= htmlspecialchars($zona['nombre']) ?></strong></td>
                        <td>$<?= number_format($zona['costo_domicilio'], 2) ?></td>
                        <td><?= $zona['tiempo_estimado_min'] ?> mins</td>
                        <td>
                            <?php if ($zona['estado'] === 'ACTIVA'): ?>
                                <span class="badge badge-success">Activa</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inactiva</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-secondary btn-sm" onclick='openEditModal(<?= json_encode($zona) ?>)'>Editar</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Form -->
<div id="zonaModal" class="modal">
    <div class="modal-content">
        <h3 id="modalTitle" style="margin-bottom: 20px;">Crear Zona de Envío</h3>
        <form method="POST" action="zonas.php">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id_zona" id="formId">
            
            <div class="form-group">
                <label>Nombre de la Zona (Ej: Norte, Sur, Centro)</label>
                <input type="text" name="nombre" id="formNombre" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Costo del Domicilio ($)</label>
                <input type="number" name="costo_domicilio" id="formCosto" class="form-control" step="0.01" required>
            </div>
            <div class="form-group">
                <label>Tiempo Estimado (Minutos)</label>
                <input type="number" name="tiempo_estimado_min" id="formTiempo" class="form-control" required>
            </div>
            
            <div class="form-group" id="statusGroup" style="display:none;">
                <label>Estado</label>
                <select name="estado" id="formEstado" class="form-control">
                    <option value="ACTIVA">Activa</option>
                    <option value="INACTIVA">Inactiva</option>
                </select>
            </div>
            
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Zona</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(mode) {
    document.getElementById('zonaModal').classList.add('active');
    if (mode === 'create') {
        document.getElementById('modalTitle').innerText = 'Nueva Zona de Envío';
        document.getElementById('formAction').value = 'create';
        document.getElementById('formId').value = '';
        document.getElementById('formNombre').value = '';
        document.getElementById('formCosto').value = '';
        document.getElementById('formTiempo').value = '30';
        document.getElementById('statusGroup').style.display = 'none';
    }
}

function openEditModal(zona) {
    openModal('edit');
    document.getElementById('modalTitle').innerText = 'Editar Zona de Envío';
    document.getElementById('formAction').value = 'update';
    document.getElementById('formId').value = zona.id_zona;
    document.getElementById('formNombre').value = zona.nombre;
    document.getElementById('formCosto').value = zona.costo_domicilio;
    document.getElementById('formTiempo').value = zona.tiempo_estimado_min;
    
    document.getElementById('statusGroup').style.display = 'block';
    document.getElementById('formEstado').value = zona.estado;
}

function closeModal() {
    document.getElementById('zonaModal').classList.remove('active');
}
</script>
