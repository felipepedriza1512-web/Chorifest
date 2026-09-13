<?php
class AdminModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getDashboardStats(): array
    {
        $stats = [
            'pedidos_hoy' => 0,
            'ventas_hoy' => 0,
            'pedidos_pendientes' => 0,
            'productos_activos' => 0
        ];

        // Pedidos y ventas de hoy
        $stmt = $this->db->query("SELECT COUNT(*) as count, SUM(total) as total FROM pedido WHERE DATE(fecha) = CURDATE() AND id_estado NOT IN (7, 10)"); // 7=Cancelado, 10=Rechazado
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $stats['pedidos_hoy'] = (int)$row['count'];
            $stats['ventas_hoy'] = (float)$row['total'];
        }

        // Pedidos pendientes (Pendiente, Confirmado, En preparacion)
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM pedido WHERE id_estado IN (1, 2, 3)"); 
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $stats['pedidos_pendientes'] = (int)$row['count'];
        }

        // Productos activos
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM producto WHERE disponible = 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $stats['productos_activos'] = (int)$row['count'];
        }

        return $stats;
    }

    public function getSalesChartData(): array
    {
        $query = "
            SELECT 
                DATE(fecha) as fecha_corta, 
                COUNT(*) as cantidad_pedidos, 
                SUM(total) as total_ventas 
            FROM pedido 
            WHERE fecha >= DATE(NOW()) - INTERVAL 6 DAY 
              AND id_estado NOT IN (7, 10) 
            GROUP BY DATE(fecha) 
            ORDER BY DATE(fecha) ASC
        ";
        $stmt = $this->db->query($query);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $chartData = [
            'labels' => [],
            'pedidos' => [],
            'ventas' => []
        ];

        // Initialize last 7 days with zeros
        $dias = [];
        for ($i = 6; $i >= 0; $i--) {
            $fecha = date('Y-m-d', strtotime("-$i days"));
            $dias[$fecha] = ['pedidos' => 0, 'ventas' => 0];
        }

        foreach ($results as $row) {
            $fecha = $row['fecha_corta'];
            if (isset($dias[$fecha])) {
                $dias[$fecha]['pedidos'] = (int)$row['cantidad_pedidos'];
                $dias[$fecha]['ventas'] = (float)$row['total_ventas'];
            }
        }

        // Format for Chart.js
        foreach ($dias as $fecha => $data) {
            $chartData['labels'][] = date('d M', strtotime($fecha));
            $chartData['pedidos'][] = $data['pedidos'];
            $chartData['ventas'][] = $data['ventas'];
        }

        return $chartData;
    }

    public function getPedidos(): array
    {
        $query = "SELECT p.*, c.nombre as cliente_nombre, c.telefono as cliente_telefono, 
                         e.nombre as estado_nombre, m.metodo as metodo_pago, m.estado as estado_pago, m.comprobante
                  FROM pedido p 
                  LEFT JOIN usuario c ON p.id_cliente = c.id_usuario
                  LEFT JOIN estado_pedido e ON p.id_estado = e.id_estado
                  LEFT JOIN pago m ON p.id_pedido = m.id_pedido
                  ORDER BY p.fecha DESC LIMIT 50";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                         c.turno, 
                         d.tipo_vehiculo, d.placa, d.estado as estado_domiciliario
                  FROM usuario u 
                  LEFT JOIN cocinera c ON u.id_usuario = c.id_usuario
                  LEFT JOIN domiciliario d ON u.id_usuario = d.id_usuario
                  WHERE u.id_rol IN (3, 4)
                  ORDER BY u.id_usuario DESC";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createEmpleado(array $data): bool
    {
        try {
            $this->db->beginTransaction();
            
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt = $this->db->prepare('INSERT INTO usuario (nombre, correo, password_hash, telefono, id_rol, estado) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $data['nombre'], 
                strtolower(trim($data['correo'])), 
                $hashedPassword, 
                $data['telefono'], 
                $data['id_rol'], 
                $data['estado'] ?? 'ACTIVO'
            ]);
            
            $userId = $this->db->lastInsertId();
            
            if ($data['id_rol'] == 3) { // Cocinera
                $stmtCocinera = $this->db->prepare('INSERT INTO cocinera (id_usuario, turno) VALUES (?, ?)');
                $stmtCocinera->execute([$userId, $data['turno'] ?? '']);
            } elseif ($data['id_rol'] == 4) { // Domiciliario
                $stmtDom = $this->db->prepare('INSERT INTO domiciliario (id_usuario, tipo_vehiculo, placa, estado) VALUES (?, ?, ?, ?)');
                $stmtDom->execute([$userId, $data['tipo_vehiculo'] ?? '', $data['placa'] ?? '', 'ACTIVO']);
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function updateEmpleado(int $id, array $data): bool
    {
        try {
            $this->db->beginTransaction();
            
            // Update base user data
            $stmt = $this->db->prepare('UPDATE usuario SET nombre = ?, telefono = ?, estado = ? WHERE id_usuario = ?');
            $stmt->execute([
                $data['nombre'],
                $data['telefono'],
                $data['estado'],
                $id
            ]);
            
            // If password is provided, update it
            if (!empty($data['password'])) {
                $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
                $stmtPass = $this->db->prepare('UPDATE usuario SET password_hash = ? WHERE id_usuario = ?');
                $stmtPass->execute([$hashedPassword, $id]);
            }
            
            // Update role specific data
            if ($data['id_rol'] == 3) {
                $stmtCocinera = $this->db->prepare('UPDATE cocinera SET turno = ? WHERE id_usuario = ?');
                $stmtCocinera->execute([$data['turno'] ?? '', $id]);
            } elseif ($data['id_rol'] == 4) {
                $stmtDom = $this->db->prepare('UPDATE domiciliario SET tipo_vehiculo = ?, placa = ?, estado = ? WHERE id_usuario = ?');
                $stmtDom->execute([$data['tipo_vehiculo'] ?? '', $data['placa'] ?? '', $data['estado_domiciliario'] ?? 'ACTIVO', $id]);
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
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
            SELECT dp.*, pr.nombre_producto 
            FROM detalle_pedido dp 
            JOIN producto pr ON dp.id_producto = pr.id_producto 
            WHERE dp.id_pedido = ?
        ");
        $stmtDet->execute([$idPedido]);
        $pedido['productos'] = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

        return $pedido;
    }
}
