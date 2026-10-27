<?php
/**
 * Modelo de Reportes y Liquidaciones - Chorifest
 * Fuente única de verdad para ventas, métricas financieras y operativas.
 */

require_once __DIR__ . '/EstadoPedido.php';

class ReporteModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * FUENTE ÚNICA DE VENTAS
     * Reglas de negocio:
     * 1. Pedido en estado ENTREGADO (id_estado = 6).
     * 2. Fecha de venta = fecha de entrega tomada de historial_pedido (MIN(fecha) con id_estado = 6).
     *    Si no existe historial por ser pedido legado, respaldo a p.fecha.
     * 3. Filtro por rango estricto: fecha >= ? AND fecha <= ? (sin DATE() en columnas).
     * 4. Pedidos cancelados o rechazados NUNCA suman.
     *
     * @param string $fechaInicio Formato 'Y-m-d H:i:s'
     * @param string $fechaFin    Formato 'Y-m-d H:i:s'
     * @return array
     */
    public function ventasPorRango(string $fechaInicio, string $fechaFin): array
    {
        $sql = "
            SELECT 
                p.id_pedido,
                p.id_cliente,
                COALESCE(u.nombre, 'Cliente Mostrador') AS cliente_nombre,
                p.id_direccion,
                p.id_zona,
                p.nombre_zona,
                p.id_estado,
                p.subtotal,
                p.costo_domicilio,
                p.total,
                p.fecha AS fecha_creacion,
                COALESCE(h.fecha_entrega, p.fecha) AS fecha_venta,
                COALESCE(pg.metodo, 'EFECTIVO') AS metodo_pago,
                pg.estado AS estado_pago,
                COALESCE(pg.pendiente_devolucion, 0) AS pendiente_devolucion
            FROM pedido p
            INNER JOIN (
                SELECT id_pedido, MIN(fecha) AS fecha_entrega
                FROM historial_pedido
                WHERE id_estado = :estado_entregado
                GROUP BY id_pedido
            ) h ON p.id_pedido = h.id_pedido
            LEFT JOIN pago pg ON p.id_pedido = pg.id_pedido
            LEFT JOIN usuario u ON p.id_cliente = u.id_usuario
            WHERE p.id_estado = :estado_entregado_ped
              AND COALESCE(h.fecha_entrega, p.fecha) >= :fecha_inicio
              AND COALESCE(h.fecha_entrega, p.fecha) <= :fecha_fin
            ORDER BY fecha_venta ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':estado_entregado'     => EstadoPedido::ENTREGADO,
            ':estado_entregado_ped' => EstadoPedido::ENTREGADO,
            ':fecha_inicio'         => $fechaInicio,
            ':fecha_fin'            => $fechaFin
        ]);

        $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalVentas = 0.0;
        $totalSubtotal = 0.0;
        $totalDomicilios = 0.0;
        $ventasEfectivo = 0.0;
        $ventasTransferencia = 0.0;
        $pedidosEfectivo = 0;
        $pedidosTransferencia = 0;
        $porDia = [];

        foreach ($pedidos as $p) {
            $monto = (float)$p['total'];
            $totalVentas += $monto;
            $totalSubtotal += (float)($p['subtotal'] ?? 0);
            $totalDomicilios += (float)($p['costo_domicilio'] ?? 0);

            $metodo = strtoupper(trim($p['metodo_pago'] ?? 'EFECTIVO'));
            if ($metodo === 'EFECTIVO') {
                $ventasEfectivo += $monto;
                $pedidosEfectivo++;
            } else {
                $ventasTransferencia += $monto;
                $pedidosTransferencia++;
            }

            $dia = substr($p['fecha_venta'], 0, 10);
            if (!isset($porDia[$dia])) {
                $porDia[$dia] = [
                    'fecha' => $dia,
                    'total' => 0.0,
                    'pedidos' => 0,
                    'efectivo' => 0.0,
                    'transferencia' => 0.0
                ];
            }
            $porDia[$dia]['total'] += $monto;
            $porDia[$dia]['pedidos']++;
            if ($metodo === 'EFECTIVO') {
                $porDia[$dia]['efectivo'] += $monto;
            } else {
                $porDia[$dia]['transferencia'] += $monto;
            }
        }

        $cantidadPedidos = count($pedidos);
        $ticketPromedio = $cantidadPedidos > 0 ? round($totalVentas / $cantidadPedidos, 2) : 0.0;

        return [
            'resumen' => [
                'total_ventas'          => round($totalVentas, 2),
                'subtotal_productos'    => round($totalSubtotal, 2),
                'domicilios_historicos' => round($totalDomicilios, 2),
                'cantidad_pedidos'      => $cantidadPedidos,
                'ticket_promedio'       => $ticketPromedio,
                'ventas_efectivo'       => round($ventasEfectivo, 2),
                'ventas_transferencia'  => round($ventasTransferencia, 2),
                'pedidos_efectivo'      => $pedidosEfectivo,
                'pedidos_transferencia' => $pedidosTransferencia
            ],
            'pedidos' => $pedidos,
            'por_dia' => array_values($porDia)
        ];
    }

    /**
     * Ventas de un día específico (00:00:00 a 23:59:59)
     */
    public function getVentasDia(string $fechaYmd): array
    {
        return $this->ventasPorRango("$fechaYmd 00:00:00", "$fechaYmd 23:59:59");
    }

    /**
     * Pagos pendientes de devolución (Cancelados/Rechazados con pago registrado)
     */
    public function getPagosPendientesDevolucion(): array
    {
        $sql = "
            SELECT 
                p.id_pedido,
                p.id_cliente,
                COALESCE(u.nombre, 'Cliente') AS cliente_nombre,
                u.telefono AS cliente_telefono,
                p.total,
                p.fecha AS fecha_pedido,
                e.nombre AS estado_pedido,
                pg.id_pago,
                pg.metodo,
                pg.comprobante,
                pg.referencia,
                pg.fecha_pago
            FROM pago pg
            INNER JOIN pedido p ON pg.id_pedido = p.id_pedido
            LEFT JOIN usuario u ON p.id_cliente = u.id_usuario
            LEFT JOIN estado_pedido e ON p.id_estado = e.id_estado
            WHERE pg.pendiente_devolucion = 1
              AND p.id_estado IN (:cancelado, :rechazado)
            ORDER BY p.fecha DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':cancelado' => EstadoPedido::CANCELADO,
            ':rechazado' => EstadoPedido::RECHAZADO
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * TOP PRODUCTOS VENDIDOS
     */
    public function topProductos(string $fechaInicio, string $fechaFin, int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));
        $sql = "
            SELECT 
                dp.id_producto,
                COALESCE(dp.nombre_producto, pr.nombre_producto) AS nombre_producto,
                COALESCE(cat.nombre, 'General') AS categoria,
                SUM(dp.cantidad) AS total_unidades,
                SUM(dp.subtotal) AS total_recaudado
            FROM detalle_pedido dp
            INNER JOIN pedido p ON dp.id_pedido = p.id_pedido
            INNER JOIN (
                SELECT id_pedido, MIN(fecha) AS fecha_entrega
                FROM historial_pedido
                WHERE id_estado = :estado_entregado
                GROUP BY id_pedido
            ) h ON p.id_pedido = h.id_pedido
            LEFT JOIN producto pr ON dp.id_producto = pr.id_producto
            LEFT JOIN categoria cat ON pr.id_categoria = cat.id_categoria
            WHERE p.id_estado = :estado_entregado_ped
              AND COALESCE(h.fecha_entrega, p.fecha) >= :fecha_inicio
              AND COALESCE(h.fecha_entrega, p.fecha) <= :fecha_fin
            GROUP BY dp.id_producto, COALESCE(dp.nombre_producto, pr.nombre_producto), COALESCE(cat.nombre, 'General')
            ORDER BY total_unidades DESC
            LIMIT $limit
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':estado_entregado'     => EstadoPedido::ENTREGADO,
            ':estado_entregado_ped' => EstadoPedido::ENTREGADO,
            ':fecha_inicio'         => $fechaInicio,
            ':fecha_fin'            => $fechaFin
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * CONSUMO DE INSUMOS
     */
    public function consumoInsumos(string $fechaInicio, string $fechaFin): array
    {
        $sql = "
            SELECT 
                m.id_insumo,
                i.nombre AS nombre_insumo,
                i.unidad_medida,
                COALESCE(inv.cantidad, 0.000) AS stock_actual,
                i.stock_minimo,
                SUM(CASE WHEN m.tipo = 'SALIDA_RECETA' THEN m.cantidad ELSE 0 END) AS salidas_receta,
                SUM(CASE WHEN m.tipo = 'SALIDA' THEN m.cantidad ELSE 0 END) AS salidas_manuales,
                SUM(CASE WHEN m.tipo = 'FALTANTE' THEN m.cantidad ELSE 0 END) AS faltantes,
                SUM(CASE WHEN m.tipo = 'ENTRADA' THEN m.cantidad ELSE 0 END) AS entradas,
                SUM(CASE WHEN m.tipo = 'REINTEGRO_CANCELACION' THEN m.cantidad ELSE 0 END) AS reintegros,
                COUNT(m.id_movimiento) AS total_movimientos
            FROM movimiento_inventario m
            INNER JOIN insumo i ON m.id_insumo = i.id_insumo
            LEFT JOIN inventario_insumo inv ON i.id_insumo = inv.id_insumo
            WHERE m.fecha >= :fecha_inicio
              AND m.fecha <= :fecha_fin
            GROUP BY m.id_insumo, i.nombre, i.unidad_medida, COALESCE(inv.cantidad, 0.000), i.stock_minimo
            ORDER BY salidas_receta DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':fecha_inicio' => $fechaInicio,
            ':fecha_fin'    => $fechaFin
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * REPORTE DE CLIENTES (Frecuencia, gasto total, ticket promedio)
     * Por protección de datos personales: NO expone teléfonos ni direcciones completas.
     */
    public function reporteClientes(string $fechaInicio, string $fechaFin, int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        $sql = "
            SELECT 
                p.id_cliente,
                COALESCE(u.nombre, 'Cliente') AS cliente_nombre,
                COUNT(p.id_pedido) AS total_pedidos,
                SUM(p.total) AS total_comprado,
                ROUND(AVG(p.total), 2) AS ticket_promedio,
                MAX(COALESCE(h.fecha_entrega, p.fecha)) AS ultima_compra
            FROM pedido p
            INNER JOIN (
                SELECT id_pedido, MIN(fecha) AS fecha_entrega
                FROM historial_pedido
                WHERE id_estado = :estado_entregado
                GROUP BY id_pedido
            ) h ON p.id_pedido = h.id_pedido
            LEFT JOIN usuario u ON p.id_cliente = u.id_usuario
            WHERE p.id_estado = :estado_entregado_ped
              AND COALESCE(h.fecha_entrega, p.fecha) >= :fecha_inicio
              AND COALESCE(h.fecha_entrega, p.fecha) <= :fecha_fin
            GROUP BY p.id_cliente, u.nombre
            ORDER BY total_comprado DESC
            LIMIT $limit
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':estado_entregado'     => EstadoPedido::ENTREGADO,
            ':estado_entregado_ped' => EstadoPedido::ENTREGADO,
            ':fecha_inicio'         => $fechaInicio,
            ':fecha_fin'            => $fechaFin
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * REPORTE DE CALIFICACIONES Y SATISFACCIÓN
     * Promedio, distribución 1-5, comentarios con filtro por fecha y puntaje.
     */
    public function calificaciones(string $fechaInicio, string $fechaFin, ?int $puntaje = null): array
    {
        // 1. Resumen general (promedio y total)
        $sqlSummary = "
            SELECT 
                COUNT(*) AS total_calificaciones,
                COALESCE(AVG(puntuacion), 0.0) AS promedio
            FROM calificacion
            WHERE fecha >= :fecha_inicio AND fecha <= :fecha_fin
        ";
        $stmtSum = $this->db->prepare($sqlSummary);
        $stmtSum->execute([':fecha_inicio' => $fechaInicio, ':fecha_fin' => $fechaFin]);
        $summary = $stmtSum->fetch(PDO::FETCH_ASSOC);

        $totalCalificaciones = (int)($summary['total_calificaciones'] ?? 0);
        $promedio = round((float)($summary['promedio'] ?? 0.0), 2);

        // 2. Distribución de 1 a 5 estrellas
        $sqlDist = "
            SELECT puntuacion, COUNT(*) AS cantidad
            FROM calificacion
            WHERE fecha >= :fecha_inicio AND fecha <= :fecha_fin
            GROUP BY puntuacion
        ";
        $stmtDist = $this->db->prepare($sqlDist);
        $stmtDist->execute([':fecha_inicio' => $fechaInicio, ':fecha_fin' => $fechaFin]);
        $distRows = $stmtDist->fetchAll(PDO::FETCH_KEY_PAIR);

        $distribucion = [];
        for ($s = 5; $s >= 1; $s--) {
            $cant = (int)($distRows[$s] ?? 0);
            $pct = $totalCalificaciones > 0 ? round(($cant / $totalCalificaciones) * 100, 1) : 0.0;
            $distribucion[$s] = [
                'puntuacion' => $s,
                'cantidad'   => $cant,
                'porcentaje' => $pct
            ];
        }

        // 3. Comentarios con filtros
        $sqlComentarios = "
            SELECT 
                c.id_calificacion,
                c.id_pedido,
                c.id_cliente,
                COALESCE(u.nombre, 'Cliente') AS cliente_nombre,
                c.puntuacion,
                c.comentario,
                c.fecha
            FROM calificacion c
            LEFT JOIN usuario u ON c.id_cliente = u.id_usuario
            WHERE c.fecha >= :fecha_inicio 
              AND c.fecha <= :fecha_fin
        ";

        $params = [
            ':fecha_inicio' => $fechaInicio,
            ':fecha_fin'    => $fechaFin
        ];

        if ($puntaje !== null && $puntaje >= 1 && $puntaje <= 5) {
            $sqlComentarios .= " AND c.puntuacion = :puntaje";
            $params[':puntaje'] = $puntaje;
        }

        $sqlComentarios .= " ORDER BY c.fecha DESC";

        $stmtCom = $this->db->prepare($sqlComentarios);
        $stmtCom->execute($params);
        $comentarios = $stmtCom->fetchAll(PDO::FETCH_ASSOC);

        return [
            'resumen' => [
                'total_calificaciones' => $totalCalificaciones,
                'promedio'             => $promedio,
                'distribucion'         => $distribucion
            ],
            'comentarios' => $comentarios
        ];
    }

    /**
     * REPORTE DE VENTAS POR CATEGORÍA
     * Unidades vendidas, monto recaudado y porcentaje de ventas por categoría
     */
    public function ventasPorCategoria(string $fechaInicio, string $fechaFin): array
    {
        $sql = "
            SELECT 
                COALESCE(cat.id_categoria, 0) AS id_categoria,
                COALESCE(cat.nombre, 'General') AS categoria_nombre,
                SUM(dp.cantidad) AS total_unidades,
                SUM(dp.subtotal) AS total_recaudado,
                COUNT(DISTINCT p.id_pedido) AS total_pedidos
            FROM detalle_pedido dp
            INNER JOIN pedido p ON dp.id_pedido = p.id_pedido
            INNER JOIN (
                SELECT id_pedido, MIN(fecha) AS fecha_entrega
                FROM historial_pedido
                WHERE id_estado = :estado_entregado
                GROUP BY id_pedido
            ) h ON p.id_pedido = h.id_pedido
            LEFT JOIN producto pr ON dp.id_producto = pr.id_producto
            LEFT JOIN categoria cat ON pr.id_categoria = cat.id_categoria
            WHERE p.id_estado = :estado_entregado_ped
              AND COALESCE(h.fecha_entrega, p.fecha) >= :fecha_inicio
              AND COALESCE(h.fecha_entrega, p.fecha) <= :fecha_fin
            GROUP BY COALESCE(cat.id_categoria, 0), COALESCE(cat.nombre, 'General')
            ORDER BY total_recaudado DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':estado_entregado'     => EstadoPedido::ENTREGADO,
            ':estado_entregado_ped' => EstadoPedido::ENTREGADO,
            ':fecha_inicio'         => $fechaInicio,
            ':fecha_fin'            => $fechaFin
        ]);

        $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $granTotalRecaudado = 0.0;
        $granTotalUnidades = 0;
        foreach ($categorias as $cat) {
            $granTotalRecaudado += (float)$cat['total_recaudado'];
            $granTotalUnidades += (int)$cat['total_unidades'];
        }

        foreach ($categorias as &$cat) {
            $cat['porcentaje_ventas'] = $granTotalRecaudado > 0 
                ? round(((float)$cat['total_recaudado'] / $granTotalRecaudado) * 100, 1) 
                : 0.0;
        }
        unset($cat);

        return [
            'resumen' => [
                'total_recaudado'  => round($granTotalRecaudado, 2),
                'total_unidades'   => $granTotalUnidades,
                'total_categorias' => count($categorias)
            ],
            'categorias' => $categorias
        ];
    }

    /**
     * REPORTE Y RENDIMIENTO DE DOMICILIARIOS
     */
    public function reporteDomiciliarios(string $fechaInicio, string $fechaFin): array
    {
        $sql = "
            SELECT 
                ad.id_domiciliario,
                u.nombre AS domiciliario_nombre,
                u.telefono AS domiciliario_telefono,
                d.tipo_vehiculo,
                d.placa,
                COUNT(p.id_pedido) AS total_entregas,
                COALESCE(SUM(CASE WHEN LOWER(pg.metodo) = 'efectivo' THEN p.total ELSE 0 END), 0) AS efectivo_cobrado,
                COALESCE(SUM(CASE WHEN LOWER(pg.metodo) != 'efectivo' THEN p.total ELSE 0 END), 0) AS digital_cobrado,
                COALESCE(SUM(p.total), 0) AS total_recaudado,
                COALESCE(AVG(cal.puntuacion), 0) AS calificacion_promedio,
                COUNT(cal.id_calificacion) AS total_calificaciones
            FROM asignacion_domicilio ad
            INNER JOIN pedido p ON ad.id_pedido = p.id_pedido
            INNER JOIN (
                SELECT id_pedido, MIN(fecha) AS fecha_entrega
                FROM historial_pedido
                WHERE id_estado = :estado_entregado
                GROUP BY id_pedido
            ) h ON p.id_pedido = h.id_pedido
            INNER JOIN usuario u ON ad.id_domiciliario = u.id_usuario
            LEFT JOIN domiciliario d ON u.id_usuario = d.id_usuario
            LEFT JOIN pago pg ON p.id_pedido = pg.id_pedido
            LEFT JOIN calificacion cal ON p.id_pedido = cal.id_pedido
            WHERE p.id_estado = :estado_entregado_ped
              AND COALESCE(h.fecha_entrega, p.fecha) >= :fecha_inicio
              AND COALESCE(h.fecha_entrega, p.fecha) <= :fecha_fin
            GROUP BY ad.id_domiciliario, u.nombre, u.telefono, d.tipo_vehiculo, d.placa
            ORDER BY total_entregas DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':estado_entregado'     => EstadoPedido::ENTREGADO,
            ':estado_entregado_ped' => EstadoPedido::ENTREGADO,
            ':fecha_inicio'         => $fechaInicio,
            ':fecha_fin'            => $fechaFin
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * PEDIDOS PENDIENTES DE LIQUIDACIÓN DE UN DOMICILIARIO (Efectivo entregado y no liquidado)
     */
    public function getPedidosPendientesLiquidacion(int $idDomiciliario): array
    {
        $sql = "
            SELECT 
                p.id_pedido,
                p.total,
                h.fecha_entrega,
                pg.metodo,
                u.nombre AS cliente_nombre,
                dir.direccion
            FROM asignacion_domicilio ad
            INNER JOIN pedido p ON ad.id_pedido = p.id_pedido
            INNER JOIN (
                SELECT id_pedido, MIN(fecha) AS fecha_entrega
                FROM historial_pedido
                WHERE id_estado = :estado_entregado
                GROUP BY id_pedido
            ) h ON p.id_pedido = h.id_pedido
            LEFT JOIN pago pg ON p.id_pedido = pg.id_pedido
            LEFT JOIN usuario u ON p.id_cliente = u.id_usuario
            LEFT JOIN direccion dir ON p.id_direccion = dir.id_direccion
            LEFT JOIN turno_liquidacion_pedido tlp ON p.id_pedido = tlp.id_pedido
            WHERE ad.id_domiciliario = :id_domi
              AND p.id_estado = :estado_entregado_ped
              AND LOWER(COALESCE(pg.metodo, 'efectivo')) = 'efectivo'
              AND tlp.id_pedido IS NULL
            ORDER BY h.fecha_entrega ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':estado_entregado'     => EstadoPedido::ENTREGADO,
            ':estado_entregado_ped' => EstadoPedido::ENTREGADO,
            ':id_domi'              => $idDomiciliario
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * LIQUIDAR TURNO DE DOMICILIARIO (Transaccional)
     */
    public function liquidarTurno(int $idDomiciliario, int $idAdmin, string $fechaCorte, ?string $observaciones = null): array
    {
        $pendientes = $this->getPedidosPendientesLiquidacion($idDomiciliario);
        if (empty($pendientes)) {
            return ['success' => false, 'message' => 'No hay pedidos en efectivo pendientes de liquidación para este domiciliario.'];
        }

        $totalEfectivo = 0.0;
        foreach ($pendientes as $ped) {
            $totalEfectivo += (float)$ped['total'];
        }

        try {
            $this->db->beginTransaction();

            // Insertar cabecera de liquidación
            $stmtLiq = $this->db->prepare("
                INSERT INTO turno_liquidacion 
                (id_domiciliario, liquidado_por, fecha_corte, total_efectivo, observaciones)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmtLiq->execute([$idDomiciliario, $idAdmin, $fechaCorte, $totalEfectivo, $observaciones]);
            $idLiquidacion = (int)$this->db->lastInsertId();

            // Asociar pedidos liquidados
            $stmtDet = $this->db->prepare("
                INSERT INTO turno_liquidacion_pedido (id_liquidacion, id_pedido, monto_liquidado)
                VALUES (?, ?, ?)
            ");

            foreach ($pendientes as $ped) {
                $stmtDet->execute([$idLiquidacion, $ped['id_pedido'], $ped['total']]);
            }

            $this->db->commit();

            return [
                'success'         => true,
                'id_liquidacion'  => $idLiquidacion,
                'total_efectivo'  => $totalEfectivo,
                'total_pedidos'   => count($pendientes),
                'message'         => 'Turno liquidado exitosamente.'
            ];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => 'Error al liquidar turno: ' . $e->getMessage()];
        }
    }

    /**
     * HISTORIAL DE LIQUIDACIONES
     */
    public function getHistorialLiquidaciones(?int $idDomiciliario = null): array
    {
        $where = $idDomiciliario ? "WHERE tl.id_domiciliario = :id_domi" : "";
        $sql = "
            SELECT 
                tl.*,
                ud.nombre AS domiciliario_nombre,
                ua.nombre AS admin_nombre,
                uan.nombre AS anulador_nombre,
                COUNT(tlp.id_pedido) AS total_pedidos_liquidados
            FROM turno_liquidacion tl
            INNER JOIN usuario ud ON tl.id_domiciliario = ud.id_usuario
            INNER JOIN usuario ua ON tl.liquidado_por = ua.id_usuario
            LEFT JOIN usuario uan ON tl.anulado_por = uan.id_usuario
            LEFT JOIN turno_liquidacion_pedido tlp ON tl.id_liquidacion = tlp.id_liquidacion
            $where
            GROUP BY tl.id_liquidacion
            ORDER BY tl.fecha_liquidacion DESC
        ";

        $stmt = $this->db->prepare($sql);
        if ($idDomiciliario) {
            $stmt->execute([':id_domi' => $idDomiciliario]);
        } else {
            $stmt->execute();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
