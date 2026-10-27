<style>
    .table-container {
        overflow-x: auto;
        border-radius: 12px;
        border: 1px solid var(--border);
        background: var(--dark);
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
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.78rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        white-space: nowrap;
    }
    .badge-success { background: rgba(46, 213, 115, 0.15); color: #2ed573; border: 1px solid rgba(46, 213, 115, 0.3); }
    .badge-danger { background: rgba(255, 71, 87, 0.15); color: #ff4757; border: 1px solid rgba(255, 71, 87, 0.3); }
    .badge-warning { background: rgba(255, 165, 2, 0.15); color: #ffa502; border: 1px solid rgba(255, 165, 2, 0.3); }
    .badge-info { background: rgba(52, 152, 219, 0.15); color: #3498db; border: 1px solid rgba(52, 152, 219, 0.3); }

    .btn {
        padding: 8px 16px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        border: none;
        display: inline-block;
        text-decoration: none;
        transition: transform 0.15s, background 0.15s;
    }
    .btn-primary { background: var(--primary); color: white; }
    .btn-primary:hover { transform: translateY(-1px); }
    .btn-secondary { background: var(--surface); color: var(--text-main); border: 1px solid var(--border); }
    .btn-secondary:hover { background: var(--border); }
    .btn-sm { padding: 5px 10px; font-size: 0.8rem; border-radius: 6px; }

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
    .modal.active { display: flex; }
    .modal-content {
        background: #18181b;
        border: 1px solid var(--border);
        border-radius: 16px;
        width: 90%;
        max-width: 540px;
        padding: 30px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.7);
        color: white;
    }
    .form-group { margin-bottom: 16px; }
    .form-group label {
        display: block;
        margin-bottom: 6px;
        font-weight: 600;
        color: var(--text-muted);
        font-size: 0.85rem;
    }
    .form-control {
        width: 100%;
        box-sizing: border-box;
        padding: 10px 14px;
        background: rgba(0,0,0,0.3);
        border: 1px solid var(--border);
        border-radius: 8px;
        color: white;
        font-size: 0.92rem;
    }
    .form-control option { background: #18181b; }
</style>

<div>
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <div>
            <h3 style="font-size:1.4rem; font-weight:700;">Catálogo de Productos</h3>
            <p style="color:var(--text-muted); font-size:0.85rem; margin-top:3px;">
                Crea, edita y gestiona la disponibilidad y recetas de tus productos.
            </p>
        </div>
        <button class="btn btn-primary" onclick="openModal('create')">+ Nuevo Producto</button>
    </div>

    <?php if (!empty($flashError)): ?>
        <div style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #f87171; padding: 12px 16px; border-radius: 12px; margin-bottom: 20px; font-weight: 600;">
            ⚠️ <?= htmlspecialchars($flashError) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($flashSuccess)): ?>
        <div style="background: rgba(34, 197, 94, 0.2); border: 1px solid #22c55e; color: #4ade80; padding: 12px 16px; border-radius: 12px; margin-bottom: 20px; font-weight: 600;">
            ✓ <?= htmlspecialchars($flashSuccess) ?>
        </div>
    <?php endif; ?>
    
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th style="width: 70px;">Foto</th>
                    <th>Producto & Categoría</th>
                    <th>Precio</th>
                    <th>Estado de Receta / Insumos</th>
                    <th>Disponibilidad</th>
                    <th style="text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($productos)): ?>
                    <tr><td colspan="7" style="text-align:center; padding:30px; color:var(--text-muted);">No hay productos registrados.</td></tr>
                <?php else: ?>
                    <?php foreach ($productos as $prod): 
                        $idProd = (int)$prod['id_producto'];
                        $st = $stockStatus[$idProd] ?? null;
                        $tieneReceta = $st ? $st['tiene_receta'] : false;
                        $disponiblePorStock = $st ? $st['disponible_por_stock'] : true;
                        $disponibleAdmin = (int)$prod['disponible'] === 1;
                        $visibleCliente = $disponibleAdmin && $disponiblePorStock;
                        $imgUrl = $productoModel->getImagenUrl($prod['nombre_producto'], $idProd, $prod['imagen']);
                    ?>
                        <tr>
                            <td style="font-family:monospace; color:var(--text-muted);">#<?= $idProd ?></td>
                            <td>
                                <img src="<?= $imgUrl ?>" alt="<?= htmlspecialchars($prod['nombre_producto']) ?>" style="width:48px; height:48px; border-radius:10px; object-fit:cover; border:1px solid var(--border);">
                            </td>
                            <td>
                                <strong style="color:white; font-size:0.95rem; display:block;"><?= htmlspecialchars($prod['nombre_producto']) ?></strong>
                                <span class="badge" style="background:rgba(234,88,12,0.12); color:#ea580c; border:1px solid rgba(234,88,12,0.25); font-size:0.7rem; margin-top:3px;">
                                    <?= htmlspecialchars($prod['categoria_nombre'] ?? 'Sin categoría') ?>
                                </span>
                            </td>
                            <td>
                                <strong style="color:#2ed573; font-size:1rem;">$<?= number_format($prod['precio'], 0, ',', '.') ?></strong>
                            </td>
                            <td>
                                <?php if (!$tieneReceta): ?>
                                    <span class="badge badge-info" title="Este producto no tiene receta configurada; no descontará insumos al empacar">
                                        ℹ️ Sin receta: no descuenta stock
                                    </span>
                                <?php elseif ($disponiblePorStock): ?>
                                    <span class="badge badge-success" title="Todos los ingredientes tienen stock suficiente para preparar al menos una porción">
                                        ✓ Insumos Disponibles
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-danger" title="Uno o más insumos requeridos no alcanzan la porción mínima">
                                        ⚠️ Insumos Agotados
                                    </span>
                                    <?php if (!empty($st['insumos_insuficientes'])): ?>
                                        <div style="font-size:0.75rem; color:#f87171; margin-top:3px;">
                                            Falta: <?= htmlspecialchars($st['insumos_insuficientes'][0]['insumo']) ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display:flex; flex-direction:column; gap:4px; align-items:flex-start;">
                                    <!-- Switch Admin -->
                                    <span class="badge <?= $disponibleAdmin ? 'badge-success' : 'badge-danger' ?>" style="font-size:0.72rem;">
                                        Admin: <?= $disponibleAdmin ? 'Habilitado' : 'Deshabilitado' ?>
                                    </span>
                                    <!-- Visibilidad final en Menú de Cliente -->
                                    <span class="badge <?= $visibleCliente ? 'badge-success' : 'badge-danger' ?>" style="font-size:0.72rem; <?= !$visibleCliente ? 'background:rgba(239,68,68,0.25);' : '' ?>">
                                        <?= $visibleCliente ? '🟢 Visible en Menú' : '🔴 Oculto en Menú' ?>
                                    </span>
                                </div>
                            </td>
                            <td style="text-align: right;">
                                <div style="display:inline-flex; gap:6px; align-items:center;">
                                    <button class="btn btn-sm" onclick="openRecetaModal(<?= $idProd ?>, '<?= addslashes(htmlspecialchars($prod['nombre_producto'])) ?>')" style="background:rgba(234,88,12,0.18); color:#fb923c; border:1px solid rgba(234,88,12,0.35);" title="Ver o editar receta e insumos">🧪 Receta</button>
                                    <button class="btn btn-secondary btn-sm" onclick='openEditModal(<?= json_encode($prod) ?>)'>Editar</button>

                                    <form method="POST" action="productos.php" style="margin:0;">
                                        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id_producto" value="<?= $idProd ?>">
                                        <button type="submit" class="btn btn-sm <?= $disponibleAdmin ? 'btn-secondary' : 'btn-primary' ?>" title="Alternar disponibilidad manual">
                                            <?= $disponibleAdmin ? 'Ocultar' : 'Activar' ?>
                                        </button>
                                    </form>

                                    <form method="POST" action="productos.php" style="margin:0;" onsubmit="return confirm('¿Seguro que deseas eliminar o desactivar este producto? Si tiene pedidos históricos, solo se desactivará para proteger el historial.');">
                                        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id_producto" value="<?= $idProd ?>">
                                        <button type="submit" class="btn btn-sm" style="background:rgba(239,68,68,0.15); color:#f87171; border:1px solid rgba(239,68,68,0.3);" title="Eliminar o desactivar de forma segura">
                                            🗑️
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Form (Crear / Editar Producto) -->
<div id="productModal" class="modal">
    <div class="modal-content">
        <h3 id="modalTitle" style="margin-bottom: 20px; font-size:1.3rem;">Crear Producto</h3>
        <form method="POST" action="productos.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id_producto" id="formId">
            
            <div class="form-group">
                <label>Nombre del Producto *</label>
                <input type="text" name="nombre_producto" id="formNombre" class="form-control" required placeholder="Ej: Chorizo Santandereano">
            </div>

            <div class="form-group">
                <label>Categoría *</label>
                <select name="id_categoria" id="formCat" class="form-control" required>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat['id_categoria'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Precio ($) *</label>
                <input type="number" name="precio" id="formPrecio" class="form-control" step="100" min="100" required placeholder="Ej: 15000">
            </div>

            <div class="form-group">
                <label>Descripción</label>
                <textarea name="descripcion" id="formDesc" class="form-control" rows="2" placeholder="Ingredientes, acompañamiento o notas para el cliente..."></textarea>
            </div>

            <!-- Carga de Foto de Producto con Validación Segura -->
            <div class="form-group">
                <label>Foto del Producto (JPG, PNG, WEBP - Máx 2 MB)</label>
                <input type="file" name="foto" id="formFoto" class="form-control" accept="image/jpeg,image/png,image/webp">
                <small id="currentPhotoNotice" style="display:none; color:#38bdf8; font-size:0.78rem; margin-top:4px;">
                    🖼️ El producto ya cuenta con foto. Selecciona una nueva solo si deseas reemplazarla.
                </small>
            </div>

            <div class="form-group" id="statusGroup" style="margin-top:10px;">
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" name="disponible" id="formDisp" value="1" checked style="width:17px; height:17px; accent-color:#ea580c;">
                    <span style="color:white; font-size:0.9rem;">Producto Habilitado por el Administrador</span>
                </label>
                <small style="display:block; color:var(--text-muted); font-size:0.75rem; margin-left:25px;">
                    Si se desmarca, el producto nunca aparecerá en el menú del cliente, incluso si tiene insumos.
                </small>
            </div>
            
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:24px;">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Producto</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(mode) {
    document.getElementById('productModal').classList.add('active');
    var notice = document.getElementById('currentPhotoNotice');
    if (mode === 'create') {
        document.getElementById('modalTitle').innerText = 'Nuevo Producto';
        document.getElementById('formAction').value = 'create';
        document.getElementById('formId').value = '';
        document.getElementById('formNombre').value = '';
        document.getElementById('formDesc').value = '';
        document.getElementById('formPrecio').value = '';
        document.getElementById('formCat').selectedIndex = 0;
        document.getElementById('formFoto').value = '';
        document.getElementById('formDisp').checked = true;
        notice.style.display = 'none';
    }
}

function openEditModal(prod) {
    openModal('edit');
    document.getElementById('modalTitle').innerText = 'Editar Producto #' + prod.id_producto;
    document.getElementById('formAction').value = 'update';
    document.getElementById('formId').value = prod.id_producto;
    document.getElementById('formNombre').value = prod.nombre_producto;
    document.getElementById('formDesc').value = prod.descripcion || '';
    document.getElementById('formPrecio').value = prod.precio;
    document.getElementById('formCat').value = prod.id_categoria;
    document.getElementById('formFoto').value = '';
    document.getElementById('formDisp').checked = (parseInt(prod.disponible) === 1);

    var notice = document.getElementById('currentPhotoNotice');
    if (prod.imagen) {
        notice.style.display = 'block';
    } else {
        notice.style.display = 'none';
    }
}

function closeModal() {
    document.getElementById('productModal').classList.remove('active');
}

// Variables globales para el modal de recetas
var availableInsumos = <?= json_encode($todosInsumos ?? []) ?>;
var currentPedidosEnCurso = 0;

function openRecetaModal(idProd, prodNombre) {
    document.getElementById('recetaIdProducto').value = idProd;
    document.getElementById('recetaProdNombre').innerText = prodNombre;
    document.getElementById('recetaInsumosRows').innerHTML = '<tr><td colspan="4" style="text-align:center; padding:20px; color:var(--text-muted);">Cargando receta...</td></tr>';
    document.getElementById('pedidosEnCursoNotice').style.display = 'none';
    document.getElementById('modalReceta').classList.add('active');

    fetch('productos.php?action=get_receta&id_producto=' + idProd)
        .then(response => response.json())
        .then(data => {
            currentPedidosEnCurso = data.pedidos_en_curso || 0;
            var notice = document.getElementById('pedidosEnCursoNotice');
            if (currentPedidosEnCurso > 0) {
                document.getElementById('pedidosEnCursoCount').innerText = currentPedidosEnCurso;
                notice.style.display = 'block';
            } else {
                notice.style.display = 'none';
            }

            var nombreInput = document.getElementById('recetaNombre');
            if (data.receta && data.receta.nombre) {
                nombreInput.value = data.receta.nombre;
            } else {
                nombreInput.value = 'Receta de ' + prodNombre;
            }

            var container = document.getElementById('recetaInsumosRows');
            container.innerHTML = '';

            if (data.receta && data.receta.detalles && data.receta.detalles.length > 0) {
                data.receta.detalles.forEach(det => {
                    addInsumoRow(det.id_insumo, det.cantidad);
                });
            } else {
                addInsumoRow('', '');
            }
        })
        .catch(err => {
            console.error('Error cargando receta:', err);
            document.getElementById('recetaInsumosRows').innerHTML = '<tr><td colspan="4" style="text-align:center; padding:15px; color:#f87171;">Error al cargar datos de la receta.</td></tr>';
        });
}

function closeRecetaModal() {
    document.getElementById('modalReceta').classList.remove('active');
}

function addInsumoRow(idInsumo = '', cantidad = '') {
    var container = document.getElementById('recetaInsumosRows');
    var rowIndex = container.children.length;

    var tr = document.createElement('tr');
    tr.id = 'receta_row_' + rowIndex;

    var optionsHtml = '<option value="">-- Seleccionar Insumo --</option>';
    availableInsumos.forEach(ins => {
        var selected = (String(ins.id_insumo) === String(idInsumo)) ? 'selected' : '';
        optionsHtml += `<option value="${ins.id_insumo}" data-unidad="${ins.unidad_medida}" ${selected}>${ins.nombre} (${ins.unidad_medida})</option>`;
    });

    tr.innerHTML = `
        <td style="padding:8px;">
            <select name="insumos[${rowIndex}][id_insumo]" class="form-control receta-insumo-select" required onchange="updateUnidadLabel(this, ${rowIndex})">
                ${optionsHtml}
            </select>
        </td>
        <td style="padding:8px; width:130px;">
            <input type="number" name="insumos[${rowIndex}][cantidad]" step="0.001" min="0.001" class="form-control receta-cant-input" value="${cantidad}" required placeholder="Ej: 0.250">
        </td>
        <td style="padding:8px; width:80px; color:var(--text-muted); font-size:0.85rem;" id="unidad_label_${rowIndex}">
            -
        </td>
        <td style="padding:8px; width:50px; text-align:center;">
            <button type="button" class="btn btn-sm" style="background:rgba(239,68,68,0.15); color:#f87171; border:1px solid rgba(239,68,68,0.3); padding:4px 8px;" onclick="removeInsumoRow(this)" title="Quitar insumo">✕</button>
        </td>
    `;

    container.appendChild(tr);

    var selectElem = tr.querySelector('.receta-insumo-select');
    updateUnidadLabel(selectElem, rowIndex);
}

function removeInsumoRow(btn) {
    var tr = btn.closest('tr');
    tr.remove();
}

function updateUnidadLabel(select, rowIndex) {
    var label = document.getElementById('unidad_label_' + rowIndex);
    if (!label) return;
    var selectedOption = select.options[select.selectedIndex];
    var unidad = selectedOption ? selectedOption.getAttribute('data-unidad') : '-';
    label.innerText = unidad || '-';
}

function validateRecetaSubmit(event) {
    var selects = document.querySelectorAll('#recetaInsumosRows .receta-insumo-select');
    var cants = document.querySelectorAll('#recetaInsumosRows .receta-cant-input');
    var seen = {};

    for (var i = 0; i < selects.length; i++) {
        var val = selects[i].value;
        var cant = parseFloat(cants[i].value);

        if (!val) {
            alert('Por favor selecciona un insumo en cada fila o elimina las filas vacías.');
            event.preventDefault();
            return false;
        }

        if (seen[val]) {
            alert('Error: Has seleccionado el mismo insumo más de una vez. No se permiten insumos repetidos en una receta.');
            selects[i].focus();
            event.preventDefault();
            return false;
        }
        seen[val] = true;

        if (isNaN(cant) || cant <= 0) {
            alert('Todas las cantidades de insumos deben ser mayores a 0.');
            cants[i].focus();
            event.preventDefault();
            return false;
        }
    }

    if (currentPedidosEnCurso > 0) {
        var ok = confirm('⚠️ ATENCIÓN: Hay ' + currentPedidosEnCurso + ' pedido(s) en curso (recibidos, aceptados o en preparación).\n\nLos cambios en esta receta modificarán los insumos que la cocina descontará al empacar dichos pedidos.\n\n¿Deseas guardar los cambios de todas formas?');
        if (!ok) {
            event.preventDefault();
            return false;
        }
    }

    return true;
}
</script>

<!-- Modal Configurar Receta de Producto -->
<div id="modalReceta" class="modal">
    <div class="modal-content" style="max-width: 680px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <div>
                <h3 style="font-size:1.3rem; margin:0;">🧪 Configurar Receta de Preparación</h3>
                <span style="font-size:0.85rem; color:#ea580c; font-weight:600;" id="recetaProdNombre"></span>
            </div>
            <button type="button" class="btn btn-secondary btn-sm" onclick="closeRecetaModal()">✕</button>
        </div>

        <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom:14px;">
            Define los insumos e ingredientes exactos que se descontarán automáticamente del inventario cuando la cocina empaque este producto.
        </p>

        <!-- Banner de Advertencia si hay pedidos en curso -->
        <div id="pedidosEnCursoNotice" style="display:none; background:rgba(239, 68, 68, 0.15); border:1px solid #ef4444; border-radius:10px; padding:12px 16px; margin-bottom:16px; color:#fca5a5; font-size:0.85rem;">
            <strong>⚠️ AVISO IMPORTANTE:</strong> Este producto cuenta actualmente con <span id="pedidosEnCursoCount" style="font-weight:800; text-decoration:underline;">0</span> pedido(s) en curso (pendientes de empacar). Los cambios en esta receta se aplicarán inmediatamente cuando la cocina prepare y empaque dichos pedidos.
        </div>

        <form method="POST" action="productos.php" onsubmit="return validateRecetaSubmit(event);">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            <input type="hidden" name="action" value="save_receta">
            <input type="hidden" name="id_producto" id="recetaIdProducto">

            <div class="form-group">
                <label>Nombre de la Receta *</label>
                <input type="text" name="nombre_receta" id="recetaNombre" class="form-control" required placeholder="Ej: Receta Santandereana Tradicional">
            </div>

            <div style="margin-top:16px; margin-bottom:8px; display:flex; justify-content:space-between; align-items:center;">
                <label style="font-weight:600; color:var(--text-muted); font-size:0.85rem; margin:0;">Insumos e Ingredientes Requeridos por Porción:</label>
                <button type="button" class="btn btn-secondary btn-sm" onclick="addInsumoRow()" style="font-size:0.78rem;">+ Añadir Insumo</button>
            </div>

            <div style="max-height:280px; overflow-y:auto; border:1px solid var(--border); border-radius:10px; background:rgba(0,0,0,0.2); margin-bottom:20px;">
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="background:var(--surface); border-bottom:1px solid var(--border); font-size:0.75rem; text-transform:uppercase; color:var(--text-muted);">
                            <th style="padding:10px; text-align:left;">Insumo</th>
                            <th style="padding:10px; text-align:left;">Cantidad</th>
                            <th style="padding:10px; text-align:left;">Unidad</th>
                            <th style="padding:10px; text-align:center;">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="recetaInsumosRows">
                        <!-- Filas dinámicas -->
                    </tbody>
                </table>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-secondary" onclick="closeRecetaModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Receta</button>
            </div>
        </form>
    </div>
</div>

