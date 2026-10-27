<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/EstadoPedido.php';
require_once __DIR__ . '/../controllers/auth/AuthMiddleware.php';

enforceSecureSession();

$currentUser = $_SESSION['user'] ?? null;
if (!$currentUser) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Sesión no iniciada.']);
    exit;
}

$userRole = (int)($currentUser['rol'] ?? $currentUser['id_rol'] ?? 0);
$userId = (int)($currentUser['id'] ?? $currentUser['id_usuario'] ?? 0);

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true) ?? $_POST;

$csrfToken = $data['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verifyCsrfToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF inválido o ausente.']);
    exit;
}

$idPedido = (int)($data['id_pedido'] ?? 0);
$nuevoEstado = (int)($data['nuevo_estado'] ?? $data['id_estado'] ?? 0);
$comentario = trim($data['comentario'] ?? $data['motivo'] ?? '');
$idDomiciliario = isset($data['id_domiciliario']) ? (int)$data['id_domiciliario'] : null;

if (($data['action'] ?? '') === 'contactar' || ($data['action'] ?? '') === 'contactar_cliente') {
    if ($idPedido <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de pedido no válido.']);
        exit;
    }
    $db = getDatabaseConnection();
    $detalle = trim($comentario ?: 'Se contactó al cliente respecto a la novedad. El pedido permanece bloqueado.');
    $db->prepare("INSERT INTO historial_pedido (id_pedido, id_estado, id_usuario, comentario, fecha) VALUES (?, ?, ?, ?, NOW())")
       ->execute([$idPedido, EstadoPedido::PROBLEMA, $userId, $detalle]);
    echo json_encode([
        'success' => true,
        'message' => 'Gestión de contacto registrada en el historial. El pedido sigue bloqueado.',
        'bloqueado' => true
    ]);
    exit;
}

if ($idPedido <= 0 || $nuevoEstado <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parámetros inválidos.']);
    exit;
}

// Cancelar exige motivo obligatorio
if ($nuevoEstado === EstadoPedido::CANCELADO && empty($comentario)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Debe proporcionar un motivo para cancelar el pedido.']);
    exit;
}

try {
    $db = getDatabaseConnection();
    
    // Obtener estado actual del pedido
    $stmtPed = $db->prepare("SELECT p.*, e.nombre as estado_nombre FROM pedido p LEFT JOIN estado_pedido e ON p.id_estado = e.id_estado WHERE p.id_pedido = ?");
    $stmtPed->execute([$idPedido]);
    $pedido = $stmtPed->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Pedido no encontrado.']);
        exit;
    }

    $estadoActual = (int)$pedido['id_estado'];

    // Si el pedido tiene novedad PENDIENTE (estado PROBLEMA):
    // Está bloqueado para el domiciliario
    if ($estadoActual === EstadoPedido::PROBLEMA) {
        if ($userRole !== 2) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'El pedido está bloqueado por una novedad pendiente de atención administrativa.']);
            exit;
        }
    }

    // MATRIZ ESTRICTA DE TRANSICIONES SEGÚN ROL
    $transicionValida = false;
    $mensajeError = "No tienes permiso para realizar esta transición de estado.";

    if ($userRole === 2) { // Admin
        if (($estadoActual === 1 || $estadoActual === 8) && ($nuevoEstado === 2 || $nuevoEstado === 10)) {
            $transicionValida = true;
        } elseif ($estadoActual === 4 && $idDomiciliario > 0) {
            // Asignar domiciliario a pedido Empacado
            $transicionValida = true;
        } elseif ($estadoActual === EstadoPedido::PROBLEMA && $nuevoEstado === EstadoPedido::EN_CAMINO) {
            // Reintentar entrega tras novedad
            $transicionValida = true;
        } elseif (in_array($nuevoEstado, [EstadoPedido::CANCELADO, EstadoPedido::RECHAZADO])) {
            // Cancelar o rechazar
            $transicionValida = true;
        }
    } elseif ($userRole === 3) { // Cocinera
        if ($estadoActual === 2 && $nuevoEstado === 3) { // Aceptado -> En preparación
            $transicionValida = true;
        } elseif ($estadoActual === 3 && $nuevoEstado === 4) { // En preparación -> Empacado
            $transicionValida = true;
        }
    } elseif ($userRole === 4) { // Domiciliario
        // Validar que el pedido esté asignado específicamente a este domiciliario
        $stmtCheckAsig = $db->prepare("SELECT id_domiciliario FROM asignacion_domicilio WHERE id_pedido = ?");
        $stmtCheckAsig->execute([$idPedido]);
        $asigDomi = $stmtCheckAsig->fetchColumn();
        if (!$asigDomi || (int)$asigDomi !== $userId) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso denegado: Este pedido no está asignado a tu usuario domiciliario.']);
            exit;
        }

        if ($estadoActual === 4 && $nuevoEstado === 5) { // Empacado -> En camino
            $transicionValida = true;
        } elseif ($estadoActual === 5 && $nuevoEstado === 6) { // En camino -> Entregado
            $transicionValida = true;
        } elseif ($estadoActual === 5 && $nuevoEstado === EstadoPedido::PROBLEMA) { // En camino -> Reportar Problema
            $transicionValida = true;
        }
    } elseif ($userRole === 1) { // Cliente
        if ((int)$pedido['id_cliente'] !== $userId) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso denegado: No puedes cancelar o modificar un pedido ajeno.']);
            exit;
        }
        if (in_array($estadoActual, [EstadoPedido::RECIBIDO, EstadoPedido::PENDIENTE_PAGO]) && $nuevoEstado === EstadoPedido::CANCELADO) {
            $transicionValida = true;
        }
    }

    if (!$transicionValida) {
        http_response_code(403);
        echo json_encode([
            'success' => false, 
            'message' => "Transición no permitida. El estado actual es '$pedido[estado_nombre]' y tu rol no puede cambiarlo a estado ID $nuevoEstado."
        ]);
        exit;
    }

    $db->beginTransaction();

    // Si Admin está asignando domiciliario
    if ($userRole === 2 && $idDomiciliario > 0) {
        // Asignar o actualizar domiciliario
        $stmtAsig = $db->prepare("INSERT INTO asignacion_domicilio (id_pedido, id_domiciliario, fecha_asignacion) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE id_domiciliario = VALUES(id_domiciliario), fecha_asignacion = NOW()");
        $stmtAsig->execute([$idPedido, $idDomiciliario]);
        
        $stmtDomName = $db->prepare("SELECT nombre FROM usuario WHERE id_usuario = ?");
        $stmtDomName->execute([$idDomiciliario]);
        $domNombre = $stmtDomName->fetchColumn() ?: 'Domiciliario';

        $comentario = $comentario ?: "Domiciliario '$domNombre' asignado por el administrador.";
    }

    // Actualizar estado del pedido si cambió
    if ($nuevoEstado !== $estadoActual) {
        $stmtUpd = $db->prepare("UPDATE pedido SET id_estado = ? WHERE id_pedido = ?");
        $stmtUpd->execute([$nuevoEstado, $idPedido]);

        // Si fue una verificación de pago por admin
        if ($userRole === 2 && ($estadoActual === 8 || $estadoActual === 1) && $nuevoEstado === 2) {
            $db->prepare("UPDATE pago SET estado = 'Verificado' WHERE id_pedido = ?")->execute([$idPedido]);
        } elseif ($userRole === 2 && $nuevoEstado === 10) {
            $db->prepare("UPDATE pago SET estado = 'Rechazado' WHERE id_pedido = ?")->execute([$idPedido]);
        }

        // Si se cancela o rechaza el pedido
        if ($nuevoEstado === EstadoPedido::CANCELADO || $nuevoEstado === EstadoPedido::RECHAZADO) {
            // Si el pago estaba verificado o aprobado, marcar devolución pendiente = 1
            $db->prepare("UPDATE pago SET pendiente_devolucion = 1 WHERE id_pedido = ? AND UPPER(estado) IN ('VERIFICADO', 'APROBADO')")->execute([$idPedido]);
            // Resolver novedad pendiente si existía
            $db->prepare("UPDATE novedad_entrega SET estado = 'ATENDIDA', atendida_por = ?, fecha_atencion = NOW() WHERE id_pedido = ? AND estado = 'PENDIENTE'")
               ->execute([$userId, $idPedido]);
        } elseif ($nuevoEstado === EstadoPedido::EN_CAMINO && $estadoActual === EstadoPedido::PROBLEMA) {
            // Reintentar entrega tras novedad
            $db->prepare("UPDATE novedad_entrega SET estado = 'ATENDIDA', atendida_por = ?, fecha_atencion = NOW() WHERE id_pedido = ? AND estado = 'PENDIENTE'")
               ->execute([$userId, $idPedido]);
        } elseif ($nuevoEstado === EstadoPedido::PROBLEMA) {
            // Reporte de novedad en entrega
            $tipoNov = trim($data['tipo_novedad'] ?? 'INCIDENTE EN RUTA');
            $descNov = trim($data['descripcion_novedad'] ?? ($comentario ?: 'Novedad reportada en entrega'));
            $db->prepare("INSERT INTO novedad_entrega (id_pedido, id_domiciliario, tipo_novedad, descripcion, estado, fecha) VALUES (?, ?, ?, ?, 'PENDIENTE', NOW())")
               ->execute([$idPedido, $userId, $tipoNov, $descNov]);
        }

        // Obtener nombre del nuevo estado
        $stmtEst = $db->prepare("SELECT nombre FROM estado_pedido WHERE id_estado = ?");
        $stmtEst->execute([$nuevoEstado]);
        $nombreNuevoEstado = $stmtEst->fetchColumn() ?: "Estado $nuevoEstado";

        // Registrar en historial_pedido
        $textoComentario = $comentario ?: "Estado cambiado a '$nombreNuevoEstado'";
        $stmtHist = $db->prepare("INSERT INTO historial_pedido (id_pedido, id_estado, id_usuario, comentario, fecha) VALUES (?, ?, ?, ?, NOW())");
        $stmtHist->execute([$idPedido, $nuevoEstado, $userId, $textoComentario]);

        // Crear notificación para el cliente
        $notifMsj = "Tu pedido #$idPedido ha cambiado de estado a: $nombreNuevoEstado.";
        $db->prepare("INSERT INTO notificacion (id_usuario, id_pedido, titulo, mensaje, fecha) VALUES (?, ?, 'Actualización de Pedido', ?, NOW())")
           ->execute([$pedido['id_cliente'], $idPedido, $notifMsj]);
    }

    $db->commit();

    // SEGUNDA TRANSACCIÓN / OPERACIÓN DE INVENTARIO DESACOPLADA (Regla 4 del prompt)
    // Para que la cocinera NUNCA quede bloqueada si hay un fallo de inventario
    require_once __DIR__ . '/../models/InventarioModel.php';
    $inventarioModel = new InventarioModel($db);
    $inventarioInfo = null;

    // 1. Al pasar a "Empacado" (Estado 4): descontar stock según receta
    if ($nuevoEstado === 4 && $estadoActual !== 4) {
        try {
            $inventarioInfo = $inventarioModel->descontarStockPedido($idPedido);
            if (!$inventarioInfo['success']) {
                // Registrar error en log y generar alerta crítica para el Admin, sin revertir el estado
                error_log("Fallo en descuento de stock para pedido #$idPedido: " . $inventarioInfo['message']);
                $db->prepare("
                    INSERT INTO notificacion (id_usuario, id_pedido, titulo, mensaje, leida, fecha)
                    SELECT u.id_usuario, ?, 'Error en Descuento de Inventario', ?, 0, NOW()
                    FROM usuario u WHERE u.id_rol = 2
                ")->execute([$idPedido, "⚠️ Error al descontar inventario del Pedido #$idPedido: " . $inventarioInfo['message']]);
            }
        } catch (Throwable $invEx) {
            error_log("Excepción al descontar stock pedido #$idPedido: " . $invEx->getMessage());
            $db->prepare("
                INSERT INTO notificacion (id_usuario, id_pedido, titulo, mensaje, leida, fecha)
                SELECT u.id_usuario, ?, 'Error Crítico en Inventario', ?, 0, NOW()
                FROM usuario u WHERE u.id_rol = 2
            ")->execute([$idPedido, "⚠️ Excepción en inventario Pedido #$idPedido: " . $invEx->getMessage()]);
        }
    }

    // 2. Al cancelar un pedido ya empacado: reintegrar si se envió la bandera
    if (($nuevoEstado === EstadoPedido::CANCELADO || $nuevoEstado === EstadoPedido::RECHAZADO) && !empty($data['reintegrar_stock'])) {
        try {
            $inventarioModel->reintegrarStockPedido($idPedido, $comentario ?: 'Cancelación de pedido', $userId);
        } catch (Throwable $reintEx) {
            error_log("Error al reintegrar stock en cancelación: " . $reintEx->getMessage());
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Estado del pedido actualizado correctamente.',
        'nuevo_estado' => $nuevoEstado,
        'inventario' => $inventarioInfo
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()]);
}
