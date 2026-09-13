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
        background: var(--dark);
    }
</style>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h3>Productos del Catálogo</h3>
        <button class="btn btn-primary" onclick="openModal('create')">+ Nuevo Producto</button>
    </div>
    
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Imagen</th>
                    <th>Nombre</th>
                    <th>Precio</th>
                    <th>Inventario</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productos as $prod): ?>
                    <tr>
                        <td><?= $prod['id_producto'] ?></td>
                        <td>
                            <img src="<?= $productoModel->getImagenUrl($prod['nombre_producto'], $prod['id_producto'], 60) ?>" alt="" style="width:40px;height:40px;border-radius:8px;object-fit:cover;">
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($prod['nombre_producto']) ?></strong>
                            <div style="font-size:0.85rem;color:#888;"><?= htmlspecialchars(substr($prod['descripcion'], 0, 50)) ?>...</div>
                        </td>
                        <td>$<?= number_format($prod['precio'], 2) ?></td>
                        <td>
                            <?php 
                                $stock = (int)($prod['stock'] ?? 0);
                                $minimo = (int)($prod['stock_minimo'] ?? 0);
                            ?>
                            <div style="font-weight: bold; font-size: 1.1rem; <?= $stock <= $minimo ? 'color: #ff4757;' : 'color: #2ed573;' ?>">
                                <?= $stock ?> <span style="font-size: 0.75rem; font-weight: normal; color: #888;">(Min: <?= $minimo ?>)</span>
                            </div>
                            <?php if ($stock <= 0): ?>
                                <span class="badge badge-danger" style="margin-top: 5px; font-size: 0.7rem;">AGOTADO</span>
                            <?php elseif ($stock <= $minimo): ?>
                                <span class="badge" style="background: rgba(255, 165, 2, 0.2); color: #ffa502; border: 1px solid #ffa502; margin-top: 5px; font-size: 0.7rem;">BAJO STOCK</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($prod['disponible']): ?>
                                <span class="badge badge-success">Activo</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display:flex; gap:10px;">
                                <button class="btn btn-secondary btn-sm" onclick='openEditModal(<?= json_encode($prod) ?>)'>Editar</button>
                                <form method="POST" action="productos.php" style="margin:0;">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id_producto" value="<?= $prod['id_producto'] ?>">
                                    <button class="btn btn-sm <?= $prod['disponible'] ? 'btn-secondary' : 'btn-primary' ?>">
                                        <?= $prod['disponible'] ? 'Desactivar' : 'Activar' ?>
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

<!-- Modal Form -->
<div id="productModal" class="modal">
    <div class="modal-content">
        <h3 id="modalTitle" style="margin-bottom: 20px;">Crear Producto</h3>
        <form method="POST" action="productos.php">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id_producto" id="formId">
            
            <div class="form-group">
                <label>Nombre del Producto</label>
                <input type="text" name="nombre_producto" id="formNombre" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Descripción</label>
                <textarea name="descripcion" id="formDesc" class="form-control" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Precio ($)</label>
                <input type="number" name="precio" id="formPrecio" class="form-control" step="0.01" required>
            </div>
            <div class="form-group">
                <label>Categoría</label>
                <select name="id_categoria" id="formCat" class="form-control">
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat['id_categoria'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label>Unidades en Bodega (Stock)</label>
                    <input type="number" name="stock" id="formStock" class="form-control" step="1" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Alerta Stock Mínimo</label>
                    <input type="number" name="stock_minimo" id="formStockMin" class="form-control" step="1" required>
                </div>
            </div>
            <div class="form-group" id="statusGroup" style="display:none;">
                <label>
                    <input type="checkbox" name="disponible" id="formDisp" value="1"> Producto Disponible para la venta
                </label>
            </div>
            
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Producto</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(mode) {
    document.getElementById('productModal').classList.add('active');
    if (mode === 'create') {
        document.getElementById('modalTitle').innerText = 'Nuevo Producto';
        document.getElementById('formAction').value = 'create';
        document.getElementById('formId').value = '';
        document.getElementById('formNombre').value = '';
        document.getElementById('formDesc').value = '';
        document.getElementById('formPrecio').value = '';
        document.getElementById('formStock').value = '50';
        document.getElementById('formStockMin').value = '5';
        document.getElementById('statusGroup').style.display = 'none';
    }
}

function openEditModal(prod) {
    openModal('edit');
    document.getElementById('modalTitle').innerText = 'Editar Producto';
    document.getElementById('formAction').value = 'update';
    document.getElementById('formId').value = prod.id_producto;
    document.getElementById('formNombre').value = prod.nombre_producto;
    document.getElementById('formDesc').value = prod.descripcion;
    document.getElementById('formPrecio').value = prod.precio;
    document.getElementById('formCat').value = prod.id_categoria;
    document.getElementById('formStock').value = prod.stock ?? 0;
    document.getElementById('formStockMin').value = prod.stock_minimo ?? 5;
    
    document.getElementById('statusGroup').style.display = 'block';
    document.getElementById('formDisp').checked = prod.disponible == 1;
}

function closeModal() {
    document.getElementById('productModal').classList.remove('active');
}
</script>
