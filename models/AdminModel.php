<?php
require_once __DIR__ . '/EstadoPedido.php';

class AdminModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    const RETRASO_MINUTOS = 30;

    public function getDashboardStats(): array
    {
        $stats = [
            'pedidos_hoy' => 0,
            'pedidos_ayer' => 0,
            'pedidos_pct' => null,
            
            'ventas_hoy' => 0.0,
            'ventas_efectivo_hoy' => 0.0,
            'ventas_transferencia_hoy' => 0.0,
            'ventas_ayer' => 0.0,
            'ventas_pct' => null,
            
            'por_atender' => 0,
            'en_curso' => 0
        ];

        // 1. Pedidos creados hoy vs ayer en zona horaria Colombia (usando rangos sin DATE())
        $hoyInicio = date('Y-m-d 00:00:00');
        $hoyFin    = date('Y-m-d 23:59:59');
        $ayerInicio = date('Y-m-d 00:00:00', strtotime('-1 day'));
        $ayerFin    = date('Y-m-d 23:59:59', strtotime('-1 day'));

        $stmtHoy = $this->db->prepare("SELECT COUNT(*) FROM pedido WHERE fecha >= ? AND fecha <= ?");
        $stmtHoy->execute([$hoyInicio, $hoyFin]);
        $stats['pedidos_hoy'] = (int)$stmtHoy->fetchColumn();

        $stmtAyer = $this->db->prepare("SELECT COUNT(*) FROM pedido WHERE fecha >= ? AND fecha <= ?");
        $stmtAyer->execute([$ayerInicio, $ayerFin]);
        $stats['pedidos_ayer'] = (int)$stmtAyer->fetchColumn();

        if ($stats['pedidos_ayer'] > 0) {
            $diffP = $stats['pedidos_hoy'] - $stats['pedidos_ayer'];
            $stats['pedidos_pct'] = round(($diffP / $stats['pedidos_ayer']) * 100, 1);
        }

        // 2. Ventas hoy y ayer (SOLO pedidos entregados, fechados al momento de entrega)
        // FUENTE ÚNICA DE VERDAD: ReporteModel::ventasPorRango
        require_once __DIR__ . '/ReporteModel.php';
        $reporteModel = new ReporteModel($this->db);

        $vh = $reporteModel->ventasPorRango($hoyInicio, $hoyFin);
        $stats['ventas_hoy'] = (float)$vh['resumen']['total_ventas'];
        $stats['ventas_efectivo_hoy'] = (float)$vh['resumen']['ventas_efectivo'];
        $stats['ventas_transferencia_hoy'] = (float)$vh['resumen']['ventas_transferencia'];

        $va = $reporteModel->ventasPorRango($ayerInicio, $ayerFin);
        $stats['ventas_ayer'] = (float)$va['resumen']['total_ventas'];

        if ($stats['ventas_ayer'] > 0) {
            $diffV = $stats['ventas_hoy'] - $stats['ventas_ayer'];
            $stats['ventas_pct'] = round(($diffV / $stats['ventas_ayer']) * 100, 1);
        }

        // 3. Por atender: Recibido o Pendiente de verificación
        $stmtAtender = $this->db->query("SELECT COUNT(*) FROM pedido WHERE id_estado IN (" . EstadoPedido::RECIBIDO . ", " . EstadoPedido::PENDIENTE_VERIFICACION . ")");
        $stats['por_atender'] = (int)$stmtAtender->fetchColumn();

        // 4. En curso: Aceptado, En preparación, Empacado, En camino
        $stmtCurso = $this->db->query("SELECT COUNT(*) FROM pedido WHERE id_estado IN (" . EstadoPedido::ACEPTADO . ", " . EstadoPedido::EN_PREPARACION . ", " . EstadoPedido::EMPACADO . ", " . EstadoPedido::EN_CAMINO . ")");
        $stats['en_curso'] = (int)$stmtCurso->fetchColumn();

        return $stats;
    }

    public function getColaPedidos(): array
    {
        $query = "SELECT p.*, c.nombre as cliente_nombre, c.telefono as cliente_telefono, c.correo as cliente_correo,
                         e.nombre as estado_nombre, m.metodo as metodo_pago, m.estado as estado_pago, m.comprobante, m.referencia as ref_pago,
                         d.direccion, d.barrio, d.referencia as direccion_referencia,
                         z.nombre as zona_nombre
                  FROM pedido p 
                  LEFT JOIN usuario c ON p.id_cliente = c.id_usuario
                  LEFT JOIN estado_pedido e ON p.id_estado = e.id_estado
                  LEFT JOIN pago m ON p.id_pedido = m.id_pedido
                  LEFT JOIN direccion d ON p.id_direccion = d.id_direccion
                  LEFT JOIN zona_envio z ON p.id_zona = z.id_zona
                  WHERE p.id_estado IN (" . EstadoPedido::RECIBIDO . ", " . EstadoPedido::PENDIENTE_VERIFICACION . ")
                  ORDER BY p.fecha ASC";
        $stmt = $this->db->query($query);
        $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($pedidos as &$ped) {
            $stmtDet = $this->db->prepare("
                SELECT dp.*, COALESCE(dp.nombre_producto, pr.nombre_producto) AS nombre_producto 
                FROM detalle_pedido dp 
                JOIN producto pr ON dp.id_producto = pr.id_producto 
                WHERE dp.id_pedido = ?
            ");
            $stmtDet->execute([$ped['id_pedido']]);
            $ped['detalles'] = $stmtDet->fetchAll(PDO::FETCH_ASSOC);
        }

        return $pedidos;
    }

    public function getAlertasOperativas(): array
    {
        $alertas = [
            'stock_bajo' => [],
            'pedidos_retrasados' => [],
            'pagos_por_revisar' => [],
            'novedades' => []
        ];

        // 1. Stock bajo de insumos (inventario_insumo.cantidad <= insumo.stock_minimo)
        try {
            $stmtIng = $this->db->query("
                SELECT i.id_insumo as id_ingrediente, i.nombre, 
                       COALESCE(inv.cantidad, 0.0) as stock_actual, 
                       i.stock_minimo, i.unidad_medida 
                FROM insumo i
                LEFT JOIN inventario_insumo inv ON i.id_insumo = inv.id_insumo
                WHERE i.estado = 'ACTIVO' AND COALESCE(inv.cantidad, 0.0) <= i.stock_minimo
                ORDER BY (COALESCE(inv.cantidad, 0.0) / NULLIF(i.stock_minimo, 0)) ASC
            ");
            $alertas['stock_bajo'] = $stmtIng->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $alertas['stock_bajo'] = [];
        }

        // 2. Pedidos retrasados
        $retrasoMin = self::RETRASO_MINUTOS;
        $queryRetrasados = "
            SELECT p.id_pedido, p.fecha, p.total, p.id_estado, e.nombre as estado_nombre, c.nombre as cliente_nombre,
                   TIMESTAMPDIFF(MINUTE, p.fecha, NOW()) as minutos_transcurridos
            FROM pedido p
            LEFT JOIN usuario c ON p.id_cliente = c.id_usuario
            LEFT JOIN estado_pedido e ON p.id_estado = e.id_estado
            WHERE p.id_estado IN (" . EstadoPedido::ACEPTADO . ", " . EstadoPedido::EN_PREPARACION . ", " . EstadoPedido::EMPACADO . ", " . EstadoPedido::EN_CAMINO . ")
              AND TIMESTAMPDIFF(MINUTE, p.fecha, NOW()) > {$retrasoMin}
            ORDER BY p.fecha ASC
        ";
        $stmtRet = $this->db->query($queryRetrasados);
        $alertas['pedidos_retrasados'] = $stmtRet->fetchAll(PDO::FETCH_ASSOC);

        // 3. Pagos por revisar (PAGO RECHAZADO - Problema de pago financiero)
        $queryPagos = "
            SELECT p.id_pedido, p.fecha, p.id_estado, p.total, e.nombre as estado_nombre, c.nombre as cliente_nombre, p.observaciones,
                   COALESCE(m.metodo, 'Transferencia') as metodo_pago, m.comprobante
            FROM pedido p
            LEFT JOIN usuario c ON p.id_cliente = c.id_usuario
            LEFT JOIN estado_pedido e ON p.id_estado = e.id_estado
            LEFT JOIN pago m ON p.id_pedido = m.id_pedido
            WHERE p.id_estado = " . EstadoPedido::PAGO_RECHAZADO . "
            ORDER BY p.fecha DESC LIMIT 10
        ";
        $stmtPagos = $this->db->query($queryPagos);
        $alertas['pagos_por_revisar'] = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);

        // 4. Novedades de entregas en ruta (DEMORADO, PROBLEMA - Incidentes logísticos)
        $queryNov = "
            SELECT p.id_pedido, p.fecha, p.id_estado, e.nombre as estado_nombre, c.nombre as cliente_nombre, p.observaciones
            FROM pedido p
            LEFT JOIN usuario c ON p.id_cliente = c.id_usuario
            LEFT JOIN estado_pedido e ON p.id_estado = e.id_estado
            WHERE p.id_estado IN (" . EstadoPedido::DEMORADO . ", " . EstadoPedido::PROBLEMA . ")
            ORDER BY p.fecha DESC LIMIT 10
        ";
        $stmtNov = $this->db->query($queryNov);
        $alertas['novedades'] = $stmtNov->fetchAll(PDO::FETCH_ASSOC);

        return $alertas;
    }

    public function getSalesChartData(): array
    {
        require_once __DIR__ . '/ReporteModel.php';
        $reporteModel = new ReporteModel($this->db);

        $dias = [];
        for ($i = 6; $i >= 0; $i--) {
            $fecha = date('Y-m-d', strtotime("-$i days"));
            $dias[$fecha] = ['efectivo' => 0.0, 'transferencia' => 0.0];
        }

        $inicio = date('Y-m-d 00:00:00', strtotime("-6 days"));
        $fin    = date('Y-m-d 23:59:59');

        $ventasData = $reporteModel->ventasPorRango($inicio, $fin);

        foreach ($ventasData['pedidos'] as $p) {
            $dia = substr($p['fecha_venta'], 0, 10);
            if (isset($dias[$dia])) {
                $met = strtoupper(trim($p['metodo_pago'] ?? 'EFECTIVO'));
                if ($met === 'EFECTIVO') {
                    $dias[$dia]['efectivo'] += (float)$p['total'];
                } else {
                    $dias[$dia]['transferencia'] += (float)$p['total'];
                }
            }
        }

        $chartData = [
            'labels' => [],
            'efectivo' => [],
            'transferencia' => []
        ];

        foreach ($dias as $fecha => $data) {
            $chartData['labels'][] = date('d/m', strtotime($fecha));
            $chartData['efectivo'][] = round($data['efectivo'], 2);
            $chartData['transferencia'][] = round($data['transferencia'], 2);
        }

        return $chartData;
    }

    public function getTopProductos7Dias(): array
    {
        require_once __DIR__ . '/ReporteModel.php';
        $reporteModel = new ReporteModel($this->db);
        $inicio = date('Y-m-d 00:00:00', strtotime("-6 days"));
        $fin    = date('Y-m-d 23:59:59');
        return $reporteModel->topProductos($inicio, $fin, 5);
    }

    public function getDomiciliariosEnCamino(): array
    {
        $query = "
            SELECT u.id_usuario, u.nombre, u.telefono,
                   udom.latitud, udom.longitud, udom.ultima_actualizacion,
                   p.id_pedido, p.total, d.direccion, d.barrio
            FROM pedido p
            JOIN asignacion_domicilio ad ON p.id_pedido = ad.id_pedido
            JOIN usuario u ON ad.id_domiciliario = u.id_usuario
            LEFT JOIN ubicacion_domiciliario udom ON u.id_usuario = udom.id_domiciliario
            LEFT JOIN direccion d ON p.id_direccion = d.id_direccion
            WHERE p.id_estado = " . EstadoPedido::EN_CAMINO . "
              AND udom.latitud IS NOT NULL
        ";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updatePedidoEstadoWithAudit(int $idPedido, int $nuevoEstado, int $idAdmin, string $motivo = ''): bool
    {
        // Validar transiciones permitidas desde el panel Admin
        // Recibido (1) o Pendiente de verificación (8) -> Aceptado (2) o Rechazado (10)
        $stmtCur = $this->db->prepare("SELECT id_estado FROM pedido WHERE id_pedido = ?");
        $stmtCur->execute([$idPedido]);
        $actual = (int)$stmtCur->fetchColumn();

        if ($actual === 0) {
            return false;
        }

        // Regla de seguridad: transiciones validas en servidor
        $esValido = false;
        if (($actual === EstadoPedido::RECIBIDO || $actual === EstadoPedido::PENDIENTE_VERIFICACION) && ($nuevoEstado === EstadoPedido::ACEPTADO || $nuevoEstado === EstadoPedido::RECHAZADO)) {
            $esValido = true;
        } elseif ($nuevoEstado === EstadoPedido::CANCELADO || $nuevoEstado === EstadoPedido::RECHAZADO) {
            // Cancelar / Rechazar con motivo siempre permitido por admin
            $esValido = true;
        }

        if (!$esValido) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            // Actualizar pedido
            $stmtUp = $this->db->prepare("UPDATE pedido SET id_estado = ? WHERE id_pedido = ?");
            $stmtUp->execute([$nuevoEstado, $idPedido]);

            // Obtener nombre del nuevo estado
            $stmtNom = $this->db->prepare("SELECT nombre FROM estado_pedido WHERE id_estado = ?");
            $stmtNom->execute([$nuevoEstado]);
            $nombreEstado = $stmtNom->fetchColumn() ?: "Estado $nuevoEstado";

            $txtComentario = $motivo ? "Motivo: $motivo" : "Acción ejecutada por Admin (Estado: $nombreEstado)";
            
            // Insertar auditoría en historial_pedido
            $stmtAudit = $this->db->prepare("INSERT INTO historial_pedido (id_pedido, id_estado, id_usuario, comentario, fecha) VALUES (?, ?, ?, ?, NOW())");
            $stmtAudit->execute([$idPedido, $nuevoEstado, $idAdmin, $txtComentario]);

            // Si es aceptado, asegurar pago
            if ($nuevoEstado === EstadoPedido::ACEPTADO) {
                $stmtPago = $this->db->prepare("UPDATE pago SET estado = 'PENDIENTE' WHERE id_pedido = ? AND estado = 'PENDIENTE'");
                $stmtPago->execute([$idPedido]);
            }

            // Si se cancela o rechaza un pedido pagado/verificado, marcar pendiente_devolucion = 1
            if ($nuevoEstado === EstadoPedido::CANCELADO || $nuevoEstado === EstadoPedido::RECHAZADO) {
                $stmtDev = $this->db->prepare("UPDATE pago SET pendiente_devolucion = 1 WHERE id_pedido = ? AND UPPER(estado) IN ('VERIFICADO', 'APROBADO')");
                $stmtDev->execute([$idPedido]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }

    public function verificarPagoWithAudit(int $idPedido, int $idAdmin): bool
    {
        try {
            $this->db->beginTransaction();

            // Marcar pago como VERIFICADO
            $stmtPago = $this->db->prepare("UPDATE pago SET estado = 'VERIFICADO', verificado_por = ?, fecha_verificacion = NOW() WHERE id_pedido = ?");
            $stmtPago->execute([$idAdmin, $idPedido]);

            // Pasar pedido a Aceptado
            $stmtPed = $this->db->prepare("UPDATE pedido SET id_estado = ? WHERE id_pedido = ?");
            $stmtPed->execute([EstadoPedido::ACEPTADO, $idPedido]);

            // Registrar auditoria
            $comentario = "Pago de transferencia verificado correctamente por el administrador.";
            $stmtAudit = $this->db->prepare("INSERT INTO historial_pedido (id_pedido, id_estado, id_usuario, comentario, fecha) VALUES (?, ?, ?, ?, NOW())");
            $stmtAudit->execute([$idPedido, EstadoPedido::ACEPTADO, $idAdmin, $comentario]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }

    public function getPedidos(): array
    {
        $query = "SELECT p.*, c.nombre as cliente_nombre, c.telefono as cliente_telefono, 
                         e.nombre as estado_nombre, m.metodo as metodo_pago, m.estado as estado_pago, m.comprobante,
                         ad.id_domiciliario, udom.nombre as domiciliario_nombre
                  FROM pedido p 
                  LEFT JOIN usuario c ON p.id_cliente = c.id_usuario
                  LEFT JOIN estado_pedido e ON p.id_estado = e.id_estado
                  LEFT JOIN pago m ON p.id_pedido = m.id_pedido
                  LEFT JOIN asignacion_domicilio ad ON p.id_pedido = ad.id_pedido
                  LEFT JOIN usuario udom ON ad.id_domiciliario = udom.id_usuario
                  ORDER BY p.fecha DESC LIMIT 50";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDomiciliarios(): array
    {
        $query = "SELECT id_usuario, nombre, telefono FROM usuario WHERE id_rol = 4 AND estado = 'ACTIVO' ORDER BY nombre ASC";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function asignarDomiciliario(int $idPedido, int $idDomiciliario, int $idAdmin): bool
    {
        try {
            $this->db->beginTransaction();

            $stmtAsig = $this->db->prepare("INSERT INTO asignacion_domicilio (id_pedido, id_domiciliario, fecha_asignacion, estado) 
                VALUES (?, ?, NOW(), 'ASIGNADO') 
                ON DUPLICATE KEY UPDATE id_domiciliario = VALUES(id_domiciliario), fecha_asignacion = NOW()");
            $stmtAsig->execute([$idPedido, $idDomiciliario]);

            $stmtDomName = $this->db->prepare("SELECT nombre FROM usuario WHERE id_usuario = ?");
            $stmtDomName->execute([$idDomiciliario]);
            $domNombre = $stmtDomName->fetchColumn() ?: 'Domiciliario';

            $comentario = "Domiciliario '$domNombre' asignado por administrador.";
            $stmtHist = $this->db->prepare("INSERT INTO historial_pedido (id_pedido, id_estado, id_usuario, comentario, fecha) 
                SELECT ?, id_estado, ?, ?, NOW() FROM pedido WHERE id_pedido = ?");
            $stmtHist->execute([$idPedido, $idAdmin, $comentario, $idPedido]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function reasignarDomiciliario(int $idPedido, int $idDomiciliarioNuevo, int $idAdmin, ?int $idDomiciliarioActual = null): array
    {
        try {
            $this->db->beginTransaction();

            // 1. Consultar asignación activa actual
            $stmtAct = $this->db->prepare("SELECT ad.id_domiciliario, u.nombre 
                                           FROM asignacion_domicilio ad 
                                           JOIN usuario u ON ad.id_domiciliario = u.id_usuario 
                                           WHERE ad.id_pedido = ? AND ad.fecha_salida IS NULL 
                                           FOR UPDATE");
            $stmtAct->execute([$idPedido]);
            $asigActual = $stmtAct->fetch(PDO::FETCH_ASSOC);

            $idAnterior = $asigActual ? (int)$asigActual['id_domiciliario'] : 0;
            $nombreAnterior = $asigActual ? $asigActual['nombre'] : 'Sin domiciliario previo';

            // Si se pasa idDomiciliarioActual pero no coincide con la base de datos (concurrencia/cambio previo)
            if ($idDomiciliarioActual !== null && $idDomiciliarioActual > 0 && $idAnterior !== $idDomiciliarioActual) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'el pedido cambió, recarga'];
            }

            if ($idAnterior > 0) {
                // UPDATE condicional y atómico
                $stmtUp = $this->db->prepare("UPDATE asignacion_domicilio 
                                               SET id_domiciliario = ?, fecha_asignacion = NOW() 
                                               WHERE id_pedido = ? AND id_domiciliario = ? AND fecha_salida IS NULL");
                $stmtUp->execute([$idDomiciliarioNuevo, $idPedido, $idAnterior]);

                if ($stmtUp->rowCount() === 0) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => 'el pedido cambió, recarga'];
                }
            } else {
                // Asignar por primera vez
                $stmtIns = $this->db->prepare("INSERT INTO asignacion_domicilio (id_pedido, id_domiciliario, fecha_asignacion) 
                    VALUES (?, ?, NOW()) 
                    ON DUPLICATE KEY UPDATE id_domiciliario = VALUES(id_domiciliario), fecha_asignacion = NOW()");
                $stmtIns->execute([$idPedido, $idDomiciliarioNuevo]);
            }

            // Obtener nombre del nuevo domiciliario y del admin
            $stmtNuevo = $this->db->prepare("SELECT nombre FROM usuario WHERE id_usuario = ?");
            $stmtNuevo->execute([$idDomiciliarioNuevo]);
            $nombreNuevo = $stmtNuevo->fetchColumn() ?: 'Domiciliario #' . $idDomiciliarioNuevo;

            $stmtAdmin = $this->db->prepare("SELECT nombre FROM usuario WHERE id_usuario = ?");
            $stmtAdmin->execute([$idAdmin]);
            $nombreAdmin = $stmtAdmin->fetchColumn() ?: 'Admin #' . $idAdmin;

            // 3. Registrar auditoría completa en historial_pedido con domiciliario anterior, nuevo y admin
            $comentario = "Reasignación de domiciliario: Anterior '$nombreAnterior' (ID: $idAnterior), Nuevo '$nombreNuevo' (ID: $idDomiciliarioNuevo) realizada por $nombreAdmin (ID: $idAdmin)";
            $stmtHist = $this->db->prepare("INSERT INTO historial_pedido (id_pedido, id_estado, id_usuario, comentario, fecha) 
                                           SELECT ?, id_estado, ?, ?, NOW() FROM pedido WHERE id_pedido = ?");
            $stmtHist->execute([$idPedido, $idAdmin, $comentario, $idPedido]);

            $this->db->commit();
            return ['success' => true, 'message' => 'Pedido reasignado correctamente.'];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => 'Error al reasignar: ' . $e->getMessage()];
        }
    }

    public function getEstadosPedido(): array
    {
        $stmt = $this->db->query("SELECT * FROM estado_pedido");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updatePedidoStatus(int $idPedido, int $idEstado): bool
    {
        $stmt = $this->db->prepare("UPDATE pedido SET id_estado = ? WHERE id_pedido = ?");
        return $stmt->execute([$idEstado, $idPedido]);
    }

    public function getZonasEnvio(): array
    {
        $stmt = $this->db->query("SELECT * FROM zona_envio");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createZona(array $data): bool
    {
        $stmt = $this->db->prepare("INSERT INTO zona_envio (nombre, costo_domicilio, tiempo_estimado_min, estado) VALUES (?, ?, ?, 'ACTIVA')");
        return $stmt->execute([
            $data['nombre'],
            $data['costo_domicilio'],
            $data['tiempo_estimado_min'] ?? 30
        ]);
    }

    public function updateZona(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("UPDATE zona_envio SET nombre = ?, costo_domicilio = ?, tiempo_estimado_min = ?, estado = ? WHERE id_zona = ?");
        return $stmt->execute([
            $data['nombre'],
            $data['costo_domicilio'],
            $data['tiempo_estimado_min'] ?? 30,
            $data['estado'],
            $id
        ]);
    }

    public function getEmpleados(): array
    {
        // 3 = Cocinera, 4 = Domiciliario
        $query = "SELECT u.id_usuario, u.nombre, u.correo, u.telefono, u.id_rol, u.estado as estado_usuario,
                         u.debe_cambiar_password, u.ultima_actividad,
                         (TIMESTAMPDIFF(SECOND, u.ultima_actividad, NOW()) <= 120) AS esta_online,
                         c.turno, 
                         d.tipo_vehiculo, d.placa, d.estado as estado_domiciliario,
                         (
                             SELECT COUNT(*) 
                             FROM asignacion_domicilio ad 
                             JOIN pedido p ON ad.id_pedido = p.id_pedido 
                             WHERE ad.id_domiciliario = u.id_usuario 
                               AND p.id_estado IN (" . EstadoPedido::EMPACADO . ", " . EstadoPedido::EN_CAMINO . ", " . EstadoPedido::DEMORADO . ", " . EstadoPedido::PROBLEMA . ")
                         ) AS pedidos_activos
                  FROM usuario u 
                  LEFT JOIN cocinera c ON u.id_usuario = c.id_usuario
                  LEFT JOIN domiciliario d ON u.id_usuario = d.id_usuario
                  WHERE u.id_rol IN (3, 4)
                  ORDER BY u.id_usuario DESC";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createEmpleado(array $data, int $adminId = 0): array
    {
        $rol = (int)($data['id_rol'] ?? 0);
        // Regla estricta: Servidor asigna y valida roles fijos (solo Cocinera 3 o Domiciliario 4)
        if ($rol !== 3 && $rol !== 4) {
            return ['success' => false, 'message' => 'Rol inválido. Solo se permite crear Cocinera o Domiciliario.'];
        }

        $email = strtolower(trim($data['correo'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Correo electrónico no válido.'];
        }

        // Correo único
        $stmtChk = $this->db->prepare("SELECT id_usuario FROM usuario WHERE correo = ?");
        $stmtChk->execute([$email]);
        if ($stmtChk->fetch()) {
            return ['success' => false, 'message' => 'El correo electrónico ya se encuentra registrado.'];
        }

        // Política de contraseñas mínima
        $password = $data['password'] ?? '';
        $trivialList = ['123456', '12345678', '123456789', 'password', 'contraseña', 'admin123', 'admin', 'qwerty', '111111', '123123', 'chorifest', 'chorifest123'];
        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'La contraseña temporal debe tener al menos 8 caracteres.'];
        }
        if (in_array(strtolower($password), $trivialList)) {
            return ['success' => false, 'message' => 'La contraseña temporal es demasiado trivial o predecible.'];
        }

        try {
            $this->db->beginTransaction();
            
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            // Toda cuenta de empleado creada por admin exige cambio de contraseña en su primer inicio
            $stmt = $this->db->prepare('INSERT INTO usuario (nombre, correo, password_hash, telefono, id_rol, estado, debe_cambiar_password) VALUES (?, ?, ?, ?, ?, ?, 1)');
            $stmt->execute([
                trim($data['nombre'] ?? ''), 
                $email, 
                $hashedPassword, 
                trim($data['telefono'] ?? ''), 
                $rol, 
                $data['estado'] ?? 'ACTIVO'
            ]);
            
            $userId = (int)$this->db->lastInsertId();
            
            if ($rol === 3) { // Cocinera
                $stmtCocinera = $this->db->prepare('INSERT INTO cocinera (id_usuario, turno) VALUES (?, ?)');
                $stmtCocinera->execute([$userId, trim($data['turno'] ?? 'Mañana')]);
            } elseif ($rol === 4) { // Domiciliario
                $stmtDom = $this->db->prepare('INSERT INTO domiciliario (id_usuario, tipo_vehiculo, placa, estado) VALUES (?, ?, ?, ?)');
                $stmtDom->execute([$userId, trim($data['tipo_vehiculo'] ?? 'Moto'), trim($data['placa'] ?? ''), 'ACTIVO']);
            }

            // Auditoría
            $rolNombre = ($rol === 3) ? 'Cocinera' : 'Domiciliario';
            $stmtAudit = $this->db->prepare("INSERT INTO auditoria (id_usuario, accion, entidad, id_registro, descripcion, fecha) VALUES (?, 'CREAR_EMPLEADO', 'usuario', ?, ?, NOW())");
            $stmtAudit->execute([$adminId, $userId, "Creación de empleado $rolNombre ($email)"]);
            
            $this->db->commit();
            return ['success' => true, 'message' => "Empleado creado exitosamente con cambio de clave obligatorio.", 'id_usuario' => $userId];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Error al crear empleado: ' . $e->getMessage()];
        }
    }

    public function updateEmpleado(int $id, array $data, int $adminId = 0): array
    {
        // Validar que el empleado exista
        $stmtEmp = $this->db->prepare("SELECT id_usuario, id_rol, estado, correo FROM usuario WHERE id_usuario = ?");
        $stmtEmp->execute([$id]);
        $empActual = $stmtEmp->fetch(PDO::FETCH_ASSOC);

        if (!$empActual) {
            return ['success' => false, 'message' => 'El empleado especificado no existe.'];
        }

        $nuevoEstado = $data['estado'] ?? $empActual['estado'];

        // Impedir desactivar a uno mismo
        if ($nuevoEstado === 'INACTIVO' && $id === $adminId) {
            return ['success' => false, 'message' => 'No puedes desactivar tu propia cuenta de usuario.'];
        }

        // Impedir desactivar domiciliario con pedidos activos
        if ($nuevoEstado === 'INACTIVO' && (int)$empActual['id_rol'] === 4) {
            $stmtAct = $this->db->prepare("
                SELECT COUNT(*) 
                FROM asignacion_domicilio ad 
                JOIN pedido p ON ad.id_pedido = p.id_pedido 
                WHERE ad.id_domiciliario = ? 
                  AND p.id_estado IN (" . EstadoPedido::EMPACADO . ", " . EstadoPedido::EN_CAMINO . ", " . EstadoPedido::DEMORADO . ", " . EstadoPedido::PROBLEMA . ")
            ");
            $stmtAct->execute([$id]);
            $pedidosActivos = (int)$stmtAct->fetchColumn();

            if ($pedidosActivos > 0) {
                return ['success' => false, 'message' => "No se puede desactivar al domiciliario porque tiene {$pedidosActivos} pedido(s) activo(s) en curso."];
            }
        }

        try {
            $this->db->beginTransaction();
            
            // Actualizar usuario base
            $stmt = $this->db->prepare('UPDATE usuario SET nombre = ?, telefono = ?, estado = ? WHERE id_usuario = ?');
            $stmt->execute([
                trim($data['nombre'] ?? ''),
                trim($data['telefono'] ?? ''),
                $nuevoEstado,
                $id
            ]);
            
            // Si se proporciona contraseña, validar política y forzar cambio
            if (!empty($data['password'])) {
                $password = $data['password'];
                $trivialList = ['123456', '12345678', '123456789', 'password', 'contraseña', 'admin123', 'admin', 'qwerty', '111111', '123123', 'chorifest', 'chorifest123'];
                if (strlen($password) < 8) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => 'La contraseña debe tener al menos 8 caracteres.'];
                }
                if (in_array(strtolower($password), $trivialList)) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => 'La contraseña es demasiado trivial.'];
                }

                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmtPass = $this->db->prepare('UPDATE usuario SET password_hash = ?, debe_cambiar_password = 1 WHERE id_usuario = ?');
                $stmtPass->execute([$hashedPassword, $id]);
            }
            
            // Actualizar datos de rol específico
            $rol = (int)$empActual['id_rol'];
            if ($rol === 3) {
                $stmtCocinera = $this->db->prepare('UPDATE cocinera SET turno = ? WHERE id_usuario = ?');
                $stmtCocinera->execute([trim($data['turno'] ?? 'Mañana'), $id]);
            } elseif ($rol === 4) {
                $stmtDom = $this->db->prepare('UPDATE domiciliario SET tipo_vehiculo = ?, placa = ?, estado = ? WHERE id_usuario = ?');
                $stmtDom->execute([trim($data['tipo_vehiculo'] ?? 'Moto'), trim($data['placa'] ?? ''), $data['estado_domiciliario'] ?? 'ACTIVO', $id]);
            }

            // Auditoría
            $stmtAudit = $this->db->prepare("INSERT INTO auditoria (id_usuario, accion, entidad, id_registro, descripcion, fecha) VALUES (?, 'EDITAR_EMPLEADO', 'usuario', ?, ?, NOW())");
            $stmtAudit->execute([$adminId, $id, "Edición de empleado {$empActual['correo']} (Estado: $nuevoEstado)"]);
            
            $this->db->commit();
            return ['success' => true, 'message' => 'Empleado actualizado exitosamente.'];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Error al actualizar empleado: ' . $e->getMessage()];
        }
    }

    public function resetPasswordEmpleado(int $id, int $adminId): array
    {
        $stmt = $this->db->prepare("SELECT id_usuario, correo, id_rol FROM usuario WHERE id_usuario = ? AND id_rol IN (3, 4)");
        $stmt->execute([$id]);
        $emp = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$emp) {
            return ['success' => false, 'message' => 'Empleado no encontrado.'];
        }

        // Generar clave temporal segura de un solo uso que cumple la política
        $tempPassword = 'Chori#' . bin2hex(random_bytes(3)) . '!';
        $hashed = password_hash($tempPassword, PASSWORD_DEFAULT);

        try {
            $stmtUpd = $this->db->prepare("UPDATE usuario SET password_hash = ?, debe_cambiar_password = 1 WHERE id_usuario = ?");
            $stmtUpd->execute([$hashed, $id]);

            // Auditoría
            $stmtAudit = $this->db->prepare("INSERT INTO auditoria (id_usuario, accion, entidad, id_registro, descripcion, fecha) VALUES (?, 'RESET_PASSWORD_EMPLEADO', 'usuario', ?, ?, NOW())");
            $stmtAudit->execute([$adminId, $id, "Generación de clave temporal para empleado {$emp['correo']}"]);

            return [
                'success' => true, 
                'message' => 'Contraseña temporal generada con éxito.', 
                'temp_password' => $tempPassword,
                'empleado_correo' => $emp['correo']
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al restablecer contraseña: ' . $e->getMessage()];
        }
    }

    public function toggleEstadoEmpleado(int $id, int $adminId): array
    {
        if ($id === $adminId) {
            return ['success' => false, 'message' => 'No puedes cambiar el estado de tu propia cuenta.'];
        }

        $stmt = $this->db->prepare("SELECT id_usuario, correo, id_rol, estado FROM usuario WHERE id_usuario = ? AND id_rol IN (3, 4)");
        $stmt->execute([$id]);
        $emp = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$emp) {
            return ['success' => false, 'message' => 'Empleado no encontrado.'];
        }

        $nuevoEstado = ($emp['estado'] === 'ACTIVO') ? 'INACTIVO' : 'ACTIVO';

        // Si se intenta desactivar a un domiciliario, verificar pedidos activos
        if ($nuevoEstado === 'INACTIVO' && (int)$emp['id_rol'] === 4) {
            $stmtAct = $this->db->prepare("
                SELECT COUNT(*) 
                FROM asignacion_domicilio ad 
                JOIN pedido p ON ad.id_pedido = p.id_pedido 
                WHERE ad.id_domiciliario = ? 
                  AND p.id_estado IN (" . EstadoPedido::EMPACADO . ", " . EstadoPedido::EN_CAMINO . ", " . EstadoPedido::DEMORADO . ", " . EstadoPedido::PROBLEMA . ")
            ");
            $stmtAct->execute([$id]);
            $pedidosActivos = (int)$stmtAct->fetchColumn();

            if ($pedidosActivos > 0) {
                return ['success' => false, 'message' => "No se puede desactivar al domiciliario mientras tenga {$pedidosActivos} pedido(s) activo(s) en curso."];
            }
        }

        try {
            $stmtUpd = $this->db->prepare("UPDATE usuario SET estado = ? WHERE id_usuario = ?");
            $stmtUpd->execute([$nuevoEstado, $id]);

            $accionAudit = ($nuevoEstado === 'INACTIVO') ? 'DESACTIVAR_EMPLEADO' : 'ACTIVAR_EMPLEADO';
            $stmtAudit = $this->db->prepare("INSERT INTO auditoria (id_usuario, accion, entidad, id_registro, descripcion, fecha) VALUES (?, ?, 'usuario', ?, ?, NOW())");
            $stmtAudit->execute([$adminId, $accionAudit, $id, "Cambio de estado a $nuevoEstado para {$emp['correo']}"]);

            return [
                'success' => true, 
                'message' => ($nuevoEstado === 'ACTIVO') ? 'Empleado activado con éxito.' : 'Empleado desactivado con éxito. Su sesión activa será revocada de inmediato.',
                'nuevo_estado' => $nuevoEstado
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al cambiar estado: ' . $e->getMessage()];
        }
    }

    public function updatePagoStatus(int $idPedido, string $estado, int $idAdmin): bool
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("UPDATE pago SET estado = ? WHERE id_pedido = ?");
            $stmt->execute([$estado, $idPedido]);

            $comentario = "Pago marcado como " . $estado . " por administrador.";
            $stmtHist = $this->db->prepare("INSERT INTO historial_pedido (id_pedido, id_estado, id_usuario, comentario, fecha) SELECT ?, id_estado, ?, ?, NOW() FROM pedido WHERE id_pedido = ?");
            $stmtHist->execute([$idPedido, $idAdmin, $comentario, $idPedido]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function getDetallePedidoCompleto(int $idPedido): ?array
    {
        // Obtener el pedido base
        $query = "SELECT p.*, c.nombre as cliente_nombre, c.telefono as cliente_telefono, c.correo as cliente_correo,
                         e.nombre as estado_nombre, m.metodo as metodo_pago, m.estado as estado_pago, m.fecha_pago, m.comprobante,
                         d.direccion, d.barrio, d.referencia, z.nombre as zona_nombre
                  FROM pedido p 
                  LEFT JOIN usuario c ON p.id_cliente = c.id_usuario
                  LEFT JOIN estado_pedido e ON p.id_estado = e.id_estado
                  LEFT JOIN pago m ON p.id_pedido = m.id_pedido
                  LEFT JOIN direccion d ON p.id_direccion = d.id_direccion
                  LEFT JOIN zona_envio z ON p.id_zona = z.id_zona
                  WHERE p.id_pedido = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$idPedido]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pedido) {
            return null;
        }

        // Obtener los detalles (productos)
        $stmtDet = $this->db->prepare("
            SELECT dp.*, COALESCE(dp.nombre_producto, pr.nombre_producto) AS nombre_producto 
            FROM detalle_pedido dp 
            JOIN producto pr ON dp.id_producto = pr.id_producto 
            WHERE dp.id_pedido = ?
        ");
        $stmtDet->execute([$idPedido]);
        $pedido['productos'] = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

        return $pedido;
    }
}
