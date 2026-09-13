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
    .badge-primary {
        background: rgba(255, 75, 43, 0.2);
        color: var(--primary);
        border: 1px solid var(--primary);
    }
    .badge-success {
        background: rgba(46, 213, 115, 0.2);
        color: #2ed573;
        border: 1px solid #2ed573;
    }
    .badge-warning {
        background: rgba(255, 165, 2, 0.2);
        color: #ffa502;
        border: 1px solid #ffa502;
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
        max-height: 90vh;
        overflow-y: auto;
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
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h3>Gestión de Personal</h3>
        <button class="btn btn-primary" onclick="openModal('create')">+ Nuevo Empleado</button>
    </div>
    
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Rol</th>
                    <th>Detalles</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($empleados)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:30px;">No hay empleados registrados</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($empleados as $emp): ?>
                        <tr>
                            <td><strong>#<?= str_pad($emp['id_usuario'], 4, '0', STR_PAD_LEFT) ?></strong></td>
                            <td>
                                <div><?= htmlspecialchars($emp['nombre']) ?></div>
                                <div style="font-size:0.85rem;color:#888;"><?= htmlspecialchars($emp['telefono']) ?></div>
                            </td>
                            <td>
                                <?php if ($emp['id_rol'] == 3): ?>
                                    <span class="badge badge-warning">Cocinera</span>
                                <?php elseif ($emp['id_rol'] == 4): ?>
                                    <span class="badge badge-primary">Domiciliario</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($emp['id_rol'] == 3): ?>
                                    <div style="font-size:0.85rem;">Turno: <strong><?= htmlspecialchars($emp['turno'] ?? 'N/A') ?></strong></div>
                                <?php elseif ($emp['id_rol'] == 4): ?>
                                    <div style="font-size:0.85rem;">Vehículo: <strong><?= htmlspecialchars($emp['tipo_vehiculo'] ?? 'N/A') ?></strong></div>
                                    <div style="font-size:0.85rem;">Placa: <strong><?= htmlspecialchars($emp['placa'] ?? 'N/A') ?></strong></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $emp['estado_usuario'] === 'ACTIVO' ? 'badge-success' : 'badge-secondary' ?>">
                                    <?= htmlspecialchars($emp['estado_usuario']) ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-secondary btn-sm" onclick='openEditModal(<?= json_encode($emp) ?>)'>Editar</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Form -->
<div id="empleadoModal" class="modal">
    <div class="modal-content">
        <h3 id="modalTitle" style="margin-bottom: 20px;">Crear Empleado</h3>
        <form method="POST" action="empleados.php">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id_usuario" id="formId">
            
            <div class="form-group">
                <label>Nombre Completo</label>
                <input type="text" name="nombre" id="formNombre" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Correo Electrónico (Para iniciar sesión)</label>
                <input type="email" name="correo" id="formCorreo" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Teléfono</label>
                <input type="text" name="telefono" id="formTelefono" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Contraseña <small id="passHelp">(Obligatoria al crear)</small></label>
                <input type="password" name="password" id="formPassword" class="form-control">
            </div>
            <div class="form-group">
                <label>Estado del Usuario</label>
                <select name="estado" id="formEstadoUsuario" class="form-control">
                    <option value="ACTIVO">Activo</option>
                    <option value="INACTIVO">Inactivo</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Rol</label>
                <select name="id_rol" id="formRol" class="form-control" onchange="toggleRoleFields()" required>
                    <option value="">Seleccione un rol...</option>
                    <option value="3">Cocinera</option>
                    <option value="4">Domiciliario</option>
                </select>
            </div>

            <!-- Campos Cocinera -->
            <div id="camposCocinera" style="display:none; padding:15px; background:rgba(255,165,2,0.1); border-radius:8px; border:1px solid rgba(255,165,2,0.2); margin-bottom:15px;">
                <h4 style="color:#ffa502; margin-bottom:10px; font-size:0.9rem;">Atributos de Cocinera</h4>
                <div class="form-group">
                    <label>Turno</label>
                    <select name="turno" id="formTurno" class="form-control">
                        <option value="Mañana">Mañana</option>
                        <option value="Tarde">Tarde</option>
                        <option value="Noche">Noche</option>
                    </select>
                </div>
            </div>

            <!-- Campos Domiciliario -->
            <div id="camposDomiciliario" style="display:none; padding:15px; background:rgba(255,75,43,0.1); border-radius:8px; border:1px solid rgba(255,75,43,0.2); margin-bottom:15px;">
                <h4 style="color:var(--primary); margin-bottom:10px; font-size:0.9rem;">Atributos de Domiciliario</h4>
                <div class="form-group">
                    <label>Tipo de Vehículo</label>
                    <select name="tipo_vehiculo" id="formVehiculo" class="form-control">
                        <option value="Moto">Moto</option>
                        <option value="Bicicleta">Bicicleta</option>
                        <option value="Carro">Carro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Placa</label>
                    <input type="text" name="placa" id="formPlaca" class="form-control" placeholder="Ej: ABC-123">
                </div>
                <div class="form-group">
                    <label>Estado del Domiciliario</label>
                    <select name="estado_domiciliario" id="formEstadoDom" class="form-control">
                        <option value="ACTIVO">Disponible</option>
                        <option value="INACTIVO">No Disponible</option>
                    </select>
                </div>
            </div>
            
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Empleado</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleRoleFields() {
    const rol = document.getElementById('formRol').value;
    const isEdit = document.getElementById('formAction').value === 'update';
    
    document.getElementById('camposCocinera').style.display = (rol === '3') ? 'block' : 'none';
    document.getElementById('camposDomiciliario').style.display = (rol === '4') ? 'block' : 'none';
    
    // Disable role change if editing
    document.getElementById('formRol').disabled = isEdit;
    
    // Add hidden input to pass id_rol when disabled
    let hiddenRol = document.getElementById('hiddenIdRol');
    if (isEdit) {
        if (!hiddenRol) {
            hiddenRol = document.createElement('input');
            hiddenRol.type = 'hidden';
            hiddenRol.name = 'id_rol';
            hiddenRol.id = 'hiddenIdRol';
            document.querySelector('form').appendChild(hiddenRol);
        }
        hiddenRol.value = rol;
    } else {
        if (hiddenRol) hiddenRol.remove();
        document.getElementById('formRol').disabled = false;
    }
}

function openModal(mode) {
    document.getElementById('empleadoModal').classList.add('active');
    
    if (mode === 'create') {
        document.getElementById('modalTitle').innerText = 'Nuevo Empleado';
        document.getElementById('formAction').value = 'create';
        document.getElementById('formId').value = '';
        
        document.getElementById('formNombre').value = '';
        document.getElementById('formCorreo').value = '';
        document.getElementById('formCorreo').readOnly = false;
        document.getElementById('formTelefono').value = '';
        document.getElementById('formPassword').required = true;
        document.getElementById('passHelp').innerText = '(Obligatoria al crear)';
        
        document.getElementById('formEstadoUsuario').value = 'ACTIVO';
        document.getElementById('formRol').value = '';
        
        // Reset subfields
        document.getElementById('formTurno').value = 'Mañana';
        document.getElementById('formVehiculo').value = 'Moto';
        document.getElementById('formPlaca').value = '';
        document.getElementById('formEstadoDom').value = 'ACTIVO';
        
        toggleRoleFields();
    }
}

function openEditModal(emp) {
    openModal('edit');
    document.getElementById('modalTitle').innerText = 'Editar Empleado';
    document.getElementById('formAction').value = 'update';
    document.getElementById('formId').value = emp.id_usuario;
    
    document.getElementById('formNombre').value = emp.nombre;
    document.getElementById('formCorreo').value = emp.correo;
    document.getElementById('formCorreo').readOnly = true; // No permitir cambiar correo
    document.getElementById('formTelefono').value = emp.telefono;
    
    document.getElementById('formPassword').required = false;
    document.getElementById('passHelp').innerText = '(Dejar en blanco para no cambiar)';
    
    document.getElementById('formEstadoUsuario').value = emp.estado_usuario;
    document.getElementById('formRol').value = emp.id_rol;
    
    // Subfields
    if (emp.id_rol == 3) {
        document.getElementById('formTurno').value = emp.turno || 'Mañana';
    } else if (emp.id_rol == 4) {
        document.getElementById('formVehiculo').value = emp.tipo_vehiculo || 'Moto';
        document.getElementById('formPlaca').value = emp.placa || '';
        document.getElementById('formEstadoDom').value = emp.estado_domiciliario || 'ACTIVO';
    }
    
    toggleRoleFields();
}

function closeModal() {
    document.getElementById('empleadoModal').classList.remove('active');
}
</script>
