<?php
// views/admin/empleados.php
?>
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
    .table tr {
        transition: background 0.2s ease;
    }
    .table tr:hover {
        background: rgba(255, 255, 255, 0.02);
    }
    .badge {
        padding: 5px 10px;
        border-radius: 16px;
        font-size: 0.82rem;
        font-weight: 600;
        display: inline-block;
        white-space: nowrap;
        text-align: center;
    }
    .badge-primary {
        background: rgba(255, 75, 43, 0.15);
        color: #ff5722;
        border: 1px solid rgba(255, 75, 43, 0.3);
    }
    .badge-success {
        background: rgba(46, 213, 115, 0.15);
        color: #2ed573;
        border: 1px solid rgba(46, 213, 115, 0.3);
    }
    .badge-warning {
        background: rgba(255, 165, 2, 0.15);
        color: #ffa502;
        border: 1px solid rgba(255, 165, 2, 0.3);
    }
    .badge-secondary {
        background: rgba(255, 255, 255, 0.08);
        color: #aaa;
        border: 1px solid rgba(255, 255, 255, 0.15);
    }
    .btn {
        padding: 7px 14px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
        transition: background 0.2s, transform 0.1s;
        font-size: 0.88rem;
    }
    .btn-primary {
        background: var(--primary);
        color: white;
    }
    .btn-primary:hover {
        background: #e03e1e;
    }
    .btn-secondary {
        background: var(--border);
        color: var(--text-main);
    }
    .btn-secondary:hover {
        background: #3f3f46;
    }
    .btn-warning {
        background: rgba(255, 165, 2, 0.15);
        color: #ffa502;
        border: 1px solid rgba(255, 165, 2, 0.3);
    }
    .btn-warning:hover {
        background: rgba(255, 165, 2, 0.25);
    }
    .btn-danger {
        background: rgba(239, 68, 68, 0.15);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }
    .btn-danger:hover {
        background: rgba(239, 68, 68, 0.25);
    }
    .btn-sm {
        padding: 5px 10px;
        font-size: 0.8rem;
    }
    .btn:active { transform: scale(0.97); }

    .alert {
        padding: 14px 18px;
        border-radius: 10px;
        margin-bottom: 20px;
        font-size: 0.92rem;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .alert-success {
        background: rgba(46, 213, 115, 0.12);
        color: #2ed573;
        border: 1px solid rgba(46, 213, 115, 0.25);
    }
    .alert-error {
        background: rgba(239, 68, 68, 0.12);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.25);
    }
    .alert-highlight {
        background: rgba(255, 165, 2, 0.15);
        color: #ffbe3b;
        border: 1px dashed rgba(255, 165, 2, 0.5);
        padding: 16px 20px;
    }
    
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
        border: 1px solid var(--border);
        border-radius: 16px;
        width: 100%;
        max-width: 520px;
        padding: 28px;
        max-height: 90vh;
        overflow-y: auto;
    }
    .form-group {
        margin-bottom: 16px;
    }
    .form-group label {
        display: block;
        margin-bottom: 6px;
        color: #bbb;
        font-size: 0.88rem;
        font-weight: 500;
    }
    .form-control {
        width: 100%;
        padding: 11px 14px;
        background: rgba(255,255,255,0.05);
        border: 1px solid var(--border);
        border-radius: 8px;
        color: white;
        font-size: 0.92rem;
        outline: none;
    }
    .form-control:focus {
        border-color: var(--primary);
    }
    .form-control option {
        background-color: var(--dark);
        color: white;
    }
    .policy-hint {
        font-size: 0.8rem;
        color: #888;
        margin-top: 4px;
    }
    .actions-cell {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }
</style>

<div class="card" style="padding: 24px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
        <div>
            <h2 style="font-size:1.5rem; font-weight:700;">Gestión de Personal</h2>
            <p style="color:var(--text-muted); font-size:0.9rem;">Administra cocineras y domiciliarios, credenciales temporales y control de accesos.</p>
        </div>
        <button class="btn btn-primary" onclick="openModal('create')">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Nuevo Empleado
        </button>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <span><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"></circle><line x1="12" y1="8" x2="12" y2="12" stroke-width="2"></line><circle cx="12" cy="16" r="1" fill="currentColor"></circle></svg>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($tempPasswordInfo)): ?>
        <div class="alert alert-highlight">
            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
            <div>
                <strong>🔑 Contraseña Temporal Generada:</strong><br>
                Empleado: <strong><?= htmlspecialchars($tempPasswordInfo['correo']) ?></strong><br>
                Nueva contraseña: <code style="font-size:1.15rem; background:#000; padding:2px 8px; border-radius:6px; color:#4ade80; user-select:all;"><?= htmlspecialchars($tempPasswordInfo['password']) ?></code>
                <div style="font-size:0.82rem; margin-top:4px; color:#ffdd88;">Esta clave solo se muestra una vez. El empleado deberá cambiarla obligatoriamente en su primer inicio de sesión.</div>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Empleado</th>
                    <th>Rol</th>
                    <th>Actividad</th>
                    <th>Detalles Operativos</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($empleados)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:35px; color:var(--text-muted);">No hay empleados registrados en el sistema.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($empleados as $emp): ?>
                        <tr>
                            <td>
                                <div style="font-weight:600; color:white;"><?= htmlspecialchars($emp['nombre']) ?></div>
                                <div style="font-size:0.82rem; color:var(--text-muted);"><?= htmlspecialchars($emp['correo']) ?></div>
                                <div style="font-size:0.82rem; color:var(--text-muted);"><?= htmlspecialchars($emp['telefono'] ?? '') ?></div>
                            </td>
                            <td>
                                <?php if ((int)$emp['id_rol'] === 3): ?>
                                    <span class="badge badge-warning">Cocinera</span>
                                <?php elseif ((int)$emp['id_rol'] === 4): ?>
                                    <span class="badge badge-primary">Domiciliario</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($emp['esta_online'])): ?>
                                    <span class="badge badge-success" title="Activo en los últimos 2 minutos">🟢 En línea</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary" title="Sin actividad reciente">⚪ Desconectado</span>
                                <?php endif; ?>
                                <?php if (!empty($emp['ultima_actividad'])): ?>
                                    <div style="font-size:0.75rem; color:#777; margin-top:3px;"><?= htmlspecialchars(date('d/m H:i', strtotime($emp['ultima_actividad']))) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ((int)$emp['id_rol'] === 3): ?>
                                    <div style="font-size:0.85rem;">Turno: <strong><?= htmlspecialchars($emp['turno'] ?? 'N/A') ?></strong></div>
                                <?php elseif ((int)$emp['id_rol'] === 4): ?>
                                    <div style="font-size:0.85rem;">Vehículo: <strong><?= htmlspecialchars($emp['tipo_vehiculo'] ?? 'N/A') ?></strong></div>
                                    <div style="font-size:0.85rem;">Placa: <strong><?= htmlspecialchars($emp['placa'] ?? 'N/A') ?></strong></div>
                                    <?php if (!empty($emp['pedidos_activos'])): ?>
                                        <div style="margin-top:3px;">
                                            <span class="badge badge-warning" style="font-size:0.75rem;">📦 <?= (int)$emp['pedidos_activos'] ?> pedido(s) en curso</span>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $emp['estado_usuario'] === 'ACTIVO' ? 'badge-success' : 'badge-secondary' ?>">
                                    <?= htmlspecialchars($emp['estado_usuario']) ?>
                                </span>
                                <?php if (!empty($emp['debe_cambiar_password'])): ?>
                                    <div style="font-size:0.75rem; color:#f59e0b; margin-top:4px;" title="Debe actualizar su contraseña temporal">
                                        🔑 Clave temporal
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions-cell">
                                    <button class="btn btn-secondary btn-sm" onclick='openEditModal(<?= json_encode($emp) ?>)'>
                                        Editar
                                    </button>

                                    <!-- Restablecer contraseña -->
                                    <form method="POST" action="empleados.php" style="display:inline;" onsubmit="return confirm('¿Generar una nueva contraseña temporal de un solo uso para <?= htmlspecialchars($emp['nombre']) ?>?');">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken()) ?>">
                                        <input type="hidden" name="action" value="reset_password">
                                        <input type="hidden" name="id_usuario" value="<?= (int)$emp['id_usuario'] ?>">
                                        <button type="submit" class="btn btn-warning btn-sm" title="Generar clave temporal">
                                            Reset Clave
                                        </button>
                                    </form>

                                    <!-- Desactivar / Activar -->
                                    <form method="POST" action="empleados.php" style="display:inline;" onsubmit="return confirm('¿Seguro que deseas <?= $emp['estado_usuario'] === 'ACTIVO' ? 'DESACTIVAR' : 'ACTIVAR' ?> a <?= htmlspecialchars($emp['nombre']) ?>? <?= $emp['estado_usuario'] === 'ACTIVO' ? 'Su sesión se revocará inmediatamente.' : '' ?>');">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken()) ?>">
                                        <input type="hidden" name="action" value="toggle_estado">
                                        <input type="hidden" name="id_usuario" value="<?= (int)$emp['id_usuario'] ?>">
                                        <?php if ($emp['estado_usuario'] === 'ACTIVO'): ?>
                                            <button type="submit" class="btn btn-danger btn-sm" title="Desactivar y revocar sesión">
                                                Desactivar
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" class="btn btn-secondary btn-sm" style="color:#2ed573;" title="Activar cuenta">
                                                Activar
                                            </button>
                                        <?php endif; ?>
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

<!-- Modal Form -->
<div id="empleadoModal" class="modal">
    <div class="modal-content">
        <h3 id="modalTitle" style="margin-bottom: 18px; font-weight:700;">Crear Empleado</h3>
        <form method="POST" action="empleados.php" id="formEmpleado">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken()) ?>">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id_usuario" id="formId">
            
            <div class="form-group">
                <label for="formNombre">Nombre Completo</label>
                <input type="text" name="nombre" id="formNombre" class="form-control" required placeholder="Ej: María Gómez">
            </div>

            <div class="form-group">
                <label for="formCorreo">Correo Electrónico (Para inicio de sesión)</label>
                <input type="email" name="correo" id="formCorreo" class="form-control" required placeholder="ejemplo@chorifest.local">
            </div>

            <div class="form-group">
                <label for="formTelefono">Teléfono de Contacto</label>
                <input type="text" name="telefono" id="formTelefono" class="form-control" required placeholder="3001234567">
            </div>

            <div class="form-group">
                <label for="formPassword">Contraseña Inicial <small id="passHelp" style="color:var(--text-muted);">(Obligatoria al crear)</small></label>
                <input type="password" name="password" id="formPassword" class="form-control" minlength="8" placeholder="••••••••">
                <div class="policy-hint">Mínimo 8 caracteres. Evita contraseñas triviales (123456, admin, etc.). Se exigirá cambio en el primer inicio.</div>
            </div>

            <div class="form-group">
                <label for="formEstadoUsuario">Estado de la Cuenta</label>
                <select name="estado" id="formEstadoUsuario" class="form-control">
                    <option value="ACTIVO">Activo</option>
                    <option value="INACTIVO">Inactivo</option>
                </select>
            </div>
            
            <!-- Rol Fijo: Solo Cocinera o Domiciliario -->
            <div class="form-group">
                <label for="formRol">Rol Operativo</label>
                <select name="id_rol" id="formRol" class="form-control" onchange="toggleRoleFields()" required>
                    <option value="">Seleccione un rol...</option>
                    <option value="3">Cocinera</option>
                    <option value="4">Domiciliario</option>
                </select>
            </div>

            <!-- Campos Cocinera -->
            <div id="camposCocinera" style="display:none; padding:14px; background:rgba(255,165,2,0.08); border-radius:8px; border:1px solid rgba(255,165,2,0.25); margin-bottom:15px;">
                <h4 style="color:#ffa502; margin-bottom:10px; font-size:0.88rem;">Atributos de Cocinera</h4>
                <div class="form-group" style="margin-bottom:0;">
                    <label for="formTurno">Turno de Cocina</label>
                    <select name="turno" id="formTurno" class="form-control">
                        <option value="Mañana">Mañana (08:00 - 16:00)</option>
                        <option value="Tarde">Tarde (14:00 - 22:00)</option>
                        <option value="Noche">Noche (18:00 - Cierre)</option>
                    </select>
                </div>
            </div>

            <!-- Campos Domiciliario -->
            <div id="camposDomiciliario" style="display:none; padding:14px; background:rgba(255,75,43,0.08); border-radius:8px; border:1px solid rgba(255,75,43,0.25); margin-bottom:15px;">
                <h4 style="color:var(--primary); margin-bottom:10px; font-size:0.88rem;">Atributos de Domiciliario</h4>
                <div class="form-group">
                    <label for="formVehiculo">Tipo de Vehículo</label>
                    <select name="tipo_vehiculo" id="formVehiculo" class="form-control">
                        <option value="Moto">Moto</option>
                        <option value="Bicicleta">Bicicleta</option>
                        <option value="Carro">Carro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="formPlaca">Placa del Vehículo</label>
                    <input type="text" name="placa" id="formPlaca" class="form-control" placeholder="Ej: ABC-12D">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label for="formEstadoDom">Disponibilidad de Ruta</label>
                    <select name="estado_domiciliario" id="formEstadoDom" class="form-control">
                        <option value="ACTIVO">Disponible para Entregas</option>
                        <option value="INACTIVO">No Disponible / En Descanso</option>
                    </select>
                </div>
            </div>
            
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnGuardarEmpleado">Guardar Empleado</button>
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
    
    // In edit mode, prevent changing role
    document.getElementById('formRol').disabled = isEdit;
    
    let hiddenRol = document.getElementById('hiddenIdRol');
    if (isEdit) {
        if (!hiddenRol) {
            hiddenRol = document.createElement('input');
            hiddenRol.type = 'hidden';
            hiddenRol.name = 'id_rol';
            hiddenRol.id = 'hiddenIdRol';
            document.getElementById('formEmpleado').appendChild(hiddenRol);
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
    document.getElementById('formCorreo').readOnly = true;
    document.getElementById('formTelefono').value = emp.telefono;
    
    document.getElementById('formPassword').required = false;
    document.getElementById('passHelp').innerText = '(Dejar en blanco para conservar)';
    
    document.getElementById('formEstadoUsuario').value = emp.estado_usuario;
    document.getElementById('formRol').value = emp.id_rol;
    
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
