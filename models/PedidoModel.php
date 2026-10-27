<?php
class PedidoModel
{
    /**
     * Regla de Negocio Centralizada: Envío Gratis en Chorifest.
     * Si en el futuro se vuelve a cobrar, basta con cambiar esta constante a true.
     */
    public const COBRAR_DOMICILIO = false;

    private PDO $db;
    protected ?string $lastError = null;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
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

            // 2. Calcular subtotal del carrito
            $subtotal = 0;
            foreach ($carrito as $item) {
                $subtotal += ($item['producto']['precio'] * $item['cantidad']);
            }

            // Validación estricta del servidor: Punto en polígono y tarifa
            require_once __DIR__ . '/ZonaModel.php';
            $zonaModel = new ZonaModel($this->db);
            $lat = !empty($direccionData['latitud']) ? (float)$direccionData['latitud'] : null;
            $lng = !empty($direccionData['longitud']) ? (float)$direccionData['longitud'] : null;

            $zona = null;
            if ($lat !== null && $lng !== null && $lat != 0 && $lng != 0) {
                $zona = $zonaModel->findMatchingZone($lat, $lng);
                if (!$zona) {
                    throw new Exception("La ubicación seleccionada se encuentra fuera de nuestra zona de cobertura.");
                }
            } elseif (!empty($direccionData['id_zona'])) {
                $zona = $zonaModel->getZonaById((int)$direccionData['id_zona']);
            }

            if (!$zona) {
                throw new Exception("No se pudo determinar una zona de entrega válida para la dirección.");
            }

            $idZona = (int)$zona['id_zona'];
            $nombreZona = $zona['nombre'];
            // Regla de Negocio: Domicilio Gratis centralizado
            // Se ignora cualquier valor enviado desde el cliente/navegador y se recalcula en servidor.
            $costoDomicilio = self::COBRAR_DOMICILIO ? (float)($zona['costo_domicilio'] ?? 0) : 0.00;
            $montoMinimo = (float)($zona['monto_minimo'] ?? 0);

            // Validar monto mínimo en el SERVIDOR
            if ($subtotal < $montoMinimo) {
                throw new Exception("El pedido no cumple con el monto mínimo de compra para la zona '{$nombreZona}' ($" . number_format($montoMinimo, 2) . "). Tu subtotal actual es $" . number_format($subtotal, 2) . ".");
            }

            // Recalcular total en el SERVIDOR (NUNCA confiar en lo enviado por el navegador)
            $total = $subtotal + $costoDomicilio;

            // 1. Crear direccion con latitud y longitud
            $stmtDir = $this->db->prepare("INSERT INTO direccion (id_cliente, id_zona, direccion, barrio, referencia, latitud, longitud, estado) VALUES (?, ?, ?, ?, ?, ?, ?, 'ACTIVA')");
            $stmtDir->execute([
                $idCliente,
                $idZona,
                $direccionData['direccion'],
                $direccionData['barrio'] ?? '',
                $direccionData['referencia'] ?? '',
                $lat,
                $lng
            ]);
            $idDireccion = (int)$this->db->lastInsertId();

            // 3. Determinar estado inicial según método de pago (Regla 1 del prompt)
            // Efectivo -> Recibido (1)
            // Transferencia / Nequi -> Pendiente de verificación (8)
            $esTransferencia = (strpos(strtolower($metodoPago), 'transferencia') !== false || strpos(strtolower($metodoPago), 'nequi') !== false);
            $idEstadoInicial = $esTransferencia ? 8 : 1;

            $stmtPed = $this->db->prepare("INSERT INTO pedido (id_cliente, id_direccion, id_zona, nombre_zona, id_estado, fecha, subtotal, costo_domicilio, total, observaciones) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)");
            $stmtPed->execute([
                $idCliente,
                $idDireccion,
                $idZona,
                $nombreZona,
                $idEstadoInicial,
                $subtotal,
                $costoDomicilio,
                $total,
                $observaciones
            ]);
            $idPedido = (int)$this->db->lastInsertId();

            require_once __DIR__ . '/InventarioModel.php';
            $invModel = new InventarioModel($this->db);
            foreach ($carrito as $item) {
                $idProducto = (int)$item['producto']['id_producto'];
                $cantidad = (int)$item['cantidad'];
                $maxDisp = $invModel->getCantidadMaximaDisponible($idProducto);
                if ($maxDisp !== null && $cantidad > $maxDisp) {
                    $nombreProd = $item['producto']['nombre_producto'] ?? 'Producto';
                    throw new Exception("Stock insuficiente para '{$nombreProd}'. Cantidad solicitada: {$cantidad}, máxima disponible: {$maxDisp}.");
                }
            }

            // Validación agregada de todo el carrito (consumo combinado de insumos)
            $valCarrito = $invModel->verificarStockCarrito($carrito);
            if (!$valCarrito['valido']) {
                throw new Exception($valCarrito['mensaje']);
            }

            // 4. Insertar detalles del pedido (sin reservar stock mientras está pendiente)
            $stmtDet = $this->db->prepare("INSERT INTO detalle_pedido (id_pedido, id_producto, nombre_producto, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?, ?)");

            foreach ($carrito as $item) {
                $idProducto = (int)$item['producto']['id_producto'];
                $cantidad = (int)$item['cantidad'];
                $itemSubtotal = $item['producto']['precio'] * $cantidad;
                $nombreProd = $item['producto']['nombre_producto'] ?? 'Producto';
                
                $stmtDet->execute([
                    $idPedido,
                    $idProducto,
                    $nombreProd,
                    $cantidad,
                    $item['producto']['precio'],
                    $itemSubtotal
                ]);
            }

            // 5. Registrar pago
            $stmtPago = $this->db->prepare("INSERT INTO pago (id_pedido, metodo, estado, fecha_pago, comprobante) VALUES (?, ?, 'Pendiente', NOW(), ?)");
            $stmtPago->execute([$idPedido, $metodoPago, $comprobantePath]);

            // 6. Registrar historial con timestamp exacto
            $comentarioInicial = $esTransferencia ? 'Pedido realizado con Transferencia (Pendiente de verificación)' : 'Pedido realizado en Efectivo (Recibido)';
            $stmtHist = $this->db->prepare("INSERT INTO historial_pedido (id_pedido, id_estado, id_usuario, comentario, fecha) VALUES (?, ?, ?, ?, NOW())");
            $stmtHist->execute([$idPedido, $idEstadoInicial, $idCliente, $comentarioInicial]);

            // 7. Notificar a los administradores
            $admins = $this->db->query("SELECT id_usuario FROM usuario WHERE id_rol = 2")->fetchAll(PDO::FETCH_COLUMN);
            $stmtNotif = $this->db->prepare("INSERT INTO notificacion (id_usuario, titulo, mensaje) VALUES (?, 'Nuevo Pedido Recibido', ?)");
            $msjAdmin = "Se ha recibido el nuevo pedido #$idPedido por valor de $" . number_format($total, 0, ',', '.');
            foreach ($admins as $adminId) {
                $stmtNotif->execute([$adminId, $msjAdmin]);
            }

            $this->db->commit();
            $this->db->exec("SET FOREIGN_KEY_CHECKS=1");
            return $idPedido;
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->db->exec("SET FOREIGN_KEY_CHECKS=1");
            $this->lastError = $e->getMessage();
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
            $stmtDet = $this->db->prepare("SELECT d.*, COALESCE(d.nombre_producto, pr.nombre_producto) AS nombre_producto, pr.descripcion FROM detalle_pedido d JOIN producto pr ON d.id_producto = pr.id_producto WHERE d.id_pedido = ?");
            $stmtDet->execute([$pedido['id_pedido']]);
            $pedido['detalles'] = $stmtDet->fetchAll(PDO::FETCH_ASSOC);
        }

        return $pedidos;
    }
}
