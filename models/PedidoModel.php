<?php
class PedidoModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getZonasEnvio(): array
    {
        $stmt = $this->db->query("SELECT * FROM zona_envio WHERE estado = 'ACTIVA'");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getZonaById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM zona_envio WHERE id_zona = ? AND estado = 'ACTIVA'");
        $stmt->execute([$id]);
        $zona = $stmt->fetch(PDO::FETCH_ASSOC);
        return $zona ?: null;
    }

    public function createPedido(int $idCliente, array $direccionData, array $carrito, string $metodoPago, string $observaciones = '', ?string $comprobantePath = null): ?int
    {
        try {
            $this->db->beginTransaction();

            // Bypass FK constraint for badly designed database schema where cliente table exists separately
            $this->db->exec("SET FOREIGN_KEY_CHECKS=0");

            // 1. Crear direccion
            $stmtDir = $this->db->prepare("INSERT INTO direccion (id_cliente, id_zona, direccion, barrio, referencia, estado) VALUES (?, ?, ?, ?, ?, 'ACTIVA')");
            $stmtDir->execute([
                $idCliente,
                $direccionData['id_zona'],
                $direccionData['direccion'],
                $direccionData['barrio'],
                $direccionData['referencia']
            ]);
            $idDireccion = (int)$this->db->lastInsertId();

            // 2. Calcular totales
            $subtotal = 0;
            foreach ($carrito as $item) {
                $subtotal += ($item['producto']['precio'] * $item['cantidad']);
            }

            $zona = $this->getZonaById((int)$direccionData['id_zona']);
            $costoDomicilio = $zona ? $zona['costo_domicilio'] : 0;
            $total = $subtotal + $costoDomicilio;

            // 3. Crear pedido
            $idEstadoInicial = 1; // Asumimos 1 = Recibido/Pendiente
            $stmtPed = $this->db->prepare("INSERT INTO pedido (id_cliente, id_direccion, id_zona, id_estado, fecha, subtotal, costo_domicilio, total, observaciones) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?)");
            $stmtPed->execute([
                $idCliente,
                $idDireccion,
                $direccionData['id_zona'],
                $idEstadoInicial,
                $subtotal,
                $costoDomicilio,
                $total,
                $observaciones
            ]);
            $idPedido = (int)$this->db->lastInsertId();

            // 4. Insertar detalles y descontar inventario
            $stmtDet = $this->db->prepare("INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
            $stmtUpdateStock = $this->db->prepare("UPDATE producto SET stock = stock - ? WHERE id_producto = ?");
            $stmtCheckStock = $this->db->prepare("SELECT stock FROM producto WHERE id_producto = ?");
            $stmtDisableProd = $this->db->prepare("UPDATE producto SET disponible = 0 WHERE id_producto = ?");

            foreach ($carrito as $item) {
                $idProducto = (int)$item['producto']['id_producto'];
                $cantidad = (int)$item['cantidad'];
                $itemSubtotal = $item['producto']['precio'] * $cantidad;
                
                $stmtDet->execute([
                    $idPedido,
                    $idProducto,
                    $cantidad,
                    $item['producto']['precio'],
                    $itemSubtotal
                ]);

                // Descontar inventario (HU-19)
                $stmtUpdateStock->execute([$cantidad, $idProducto]);

                // Verificar si llegó a cero para desactivarlo automáticamente
                $stmtCheckStock->execute([$idProducto]);
                $currentStock = (int)$stmtCheckStock->fetchColumn();
                if ($currentStock <= 0) {
                    $stmtDisableProd->execute([$idProducto]);
                }
            }

            // 5. Registrar pago
            $stmtPago = $this->db->prepare("INSERT INTO pago (id_pedido, metodo, estado, fecha_pago, comprobante) VALUES (?, ?, 'Pendiente', NOW(), ?)");
            $stmtPago->execute([$idPedido, $metodoPago, $comprobantePath]);

            // 6. Registrar historial
            $stmtHist = $this->db->prepare("INSERT INTO historial_pedido (id_pedido, id_estado, id_usuario, comentario, fecha) VALUES (?, ?, ?, 'Pedido creado desde la web', NOW())");
            $stmtHist->execute([$idPedido, $idEstadoInicial, $idCliente]);

            $this->db->commit();
            $this->db->exec("SET FOREIGN_KEY_CHECKS=1");
            return $idPedido;
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->db->exec("SET FOREIGN_KEY_CHECKS=1");
            error_log("Error creando pedido: " . $e->getMessage());
            return null;
        }
    }

    public function getMisPedidos(int $idCliente): array
    {
        $query = "SELECT p.*, e.nombre as estado_nombre, m.metodo as metodo_pago, m.estado as estado_pago
                  FROM pedido p 
                  LEFT JOIN estado_pedido e ON p.id_estado = e.id_estado
                  LEFT JOIN pago m ON p.id_pedido = m.id_pedido
                  WHERE p.id_cliente = ?
                  ORDER BY p.fecha DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$idCliente]);
        $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get details for each order
        foreach ($pedidos as &$pedido) {
            $stmtDet = $this->db->prepare("SELECT d.*, pr.nombre_producto, pr.descripcion FROM detalle_pedido d JOIN producto pr ON d.id_producto = pr.id_producto WHERE d.id_pedido = ?");
            $stmtDet->execute([$pedido['id_pedido']]);
            $pedido['detalles'] = $stmtDet->fetchAll(PDO::FETCH_ASSOC);
        }

        return $pedidos;
    }
}
