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
    
    .checkbox-list {
        max-height: 200px;
        overflow-y: auto;
        background: rgba(0,0,0,0.2);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 10px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .checkbox-item {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.9rem;
    }
</style>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h3>Gestión de Ingredientes</h3>
        <button class="btn btn-primary" onclick="openModal('create')">+ Nuevo Ingrediente</button>
    </div>
    
    <div class="table-container" style="margin-top: 20px;">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Productos Asignados</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ingredientes as $ing): ?>
                    <tr>
                        <td><?= $ing['id_ingrediente'] ?></td>
                        <td><strong><?= htmlspecialchars($ing['nombre']) ?></strong></td>
                        <td>
                            <span class="badge" style="background: rgba(255,255,255,0.1); border: 1px solid var(--border);">
                                <?= count($ing['productos']) ?> productos
                            </span>
                        </td>
                        <td>
                            <?php if ($ing['estado'] === 'disponible'): ?>
                                <span class="badge badge-success">Disponible</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Agotado</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display:flex; gap:10px;">
                                <button class="btn btn-secondary btn-sm" onclick='openEditModal(<?= json_encode($ing) ?>)'>Editar</button>
                                <button class="btn btn-secondary btn-sm" onclick='openAssignModal(<?= json_encode($ing) ?>)'>Asignar a Productos</button>
                                <form method="POST" action="ingredientes.php" style="margin:0;">
                                    <input type="hidden" name="action" value="toggle_estado">
                                    <input type="hidden" name="id_ingrediente" value="<?= $ing['id_ingrediente'] ?>">
                                    <button class="btn btn-sm <?= $ing['estado'] === 'disponible' ? 'btn-danger' : 'btn-success' ?>" style="background: <?= $ing['estado'] === 'disponible' ? '#ff4757' : '#2ed573' ?>; color: white;">
                                        <?= $ing['estado'] === 'disponible' ? 'Marcar Agotado' : 'Marcar Disponible' ?>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Form Create/Edit -->
<div id="ingredienteModal" class="modal">
    <div class="modal-content">
        <h3 id="modalTitle" style="margin-bottom: 20px;">Crear Ingrediente</h3>
        <form method="POST" action="ingredientes.php">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id_ingrediente" id="formId">
            
            <div class="form-group">
                <label>Nombre del Ingrediente</label>
                <input type="text" name="nombre" id="formNombre" class="form-control" required>
            </div>
            
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('ingredienteModal')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Form Assign -->
<div id="assignModal" class="modal">
    <div class="modal-content">
        <h3 id="assignModalTitle" style="margin-bottom: 20px;">Asignar Productos</h3>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 15px;">Selecciona los productos que contienen este ingrediente. Si el ingrediente se agota, estos productos se desactivarán.</p>
        <form method="POST" action="ingredientes.php">
            <input type="hidden" name="action" value="assign_products">
            <input type="hidden" name="id_ingrediente" id="assignFormId">
            
            <div class="form-group">
                <div class="checkbox-list">
                    <?php foreach ($productos as $prod): ?>
                        <label class="checkbox-item">
                            <input type="checkbox" name="productos[]" value="<?= $prod['id_producto'] ?>" class="prod-checkbox">
                            <?= htmlspecialchars($prod['nombre_producto']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('assignModal')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Asignaciones</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(mode) {
    document.getElementById('ingredienteModal').classList.add('active');
    if (mode === 'create') {
        document.getElementById('modalTitle').innerText = 'Nuevo Ingrediente';
        document.getElementById('formAction').value = 'create';
        document.getElementById('formId').value = '';
        document.getElementById('formNombre').value = '';
    }
}

function openEditModal(ing) {
    document.getElementById('ingredienteModal').classList.add('active');
    document.getElementById('modalTitle').innerText = 'Editar Ingrediente';
    document.getElementById('formAction').value = 'update';
    document.getElementById('formId').value = ing.id_ingrediente;
    document.getElementById('formNombre').value = ing.nombre;
}

function openAssignModal(ing) {
    document.getElementById('assignModal').classList.add('active');
    document.getElementById('assignModalTitle').innerText = 'Asignar: ' + ing.nombre;
    document.getElementById('assignFormId').value = ing.id_ingrediente;
    
    // Check checkboxes for already assigned products
    const checkboxes = document.querySelectorAll('.prod-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = ing.productos.includes(parseInt(cb.value));
    });
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}
</script>
