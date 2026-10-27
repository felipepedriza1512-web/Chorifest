<?php
class StaffModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getPedidosCocina(): array
    {
        // Cocinera ve solo pedidos Aceptados (2) y En preparación (3) - (Regla 1 del prompt)
        $query = "SELECT p.*, c.nombre as cliente_nombre, e.nombre as estado_nombre 
                  FROM pedido p 
                  JOIN usuario c ON p.id_cliente = c.id_usuario
                  LEFT JOIN estado_pedido e ON p.id_estado = e.id_estado
                  WHERE p.id_estado IN (2, 3)
                  ORDER BY p.fecha ASC";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDetallesPedido(int $idPedido): array
    {
        $query = "SELECT d.*, COALESCE(d.nombre_producto, pr.nombre_producto) AS nombre_producto 
                  FROM detalle_pedido d
                  JOIN producto pr ON d.id_producto = pr.id_producto
                  WHERE d.id_pedido = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$idPedido]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updatePedidoStatus(int $idPedido, int $idEstado, int $idUsuario = 0, string $comentario = ''): bool
    {
        try {
            // Si es un domiciliario, verificar que el pedido esté asignado específicamente a él
            if ($idUsuario > 0) {
                $stmtCheckRol = $this->db->prepare("SELECT id_rol FROM usuario WHERE id_usuario = ?");
                $stmtCheckRol->execute([$idUsuario]);
                $rol = (int)$stmtCheckRol->fetchColumn();
                if ($rol === 4) { // Domiciliario
                    $stmtCheckAsig = $this->db->prepare("SELECT 1 FROM asignacion_domicilio WHERE id_pedido = ? AND id_domiciliario = ?");
                    $stmtCheckAsig->execute([$idPedido, $idUsuario]);
                    if (!$stmtCheckAsig->fetch()) {
                        return false;
                    }
                }
            }

            $this->db->beginTransaction();

            $stmt = $this->db->prepare("UPDATE pedido SET id_estado = ? WHERE id_pedido = ?");
            $stmt->execute([$idEstado, $idPedido]);

            // Obtener nombre del nuevo estado
            $stmtEst = $this->db->prepare("SELECT nombre FROM estado_pedido WHERE id_estado = ?");
            $stmtEst->execute([$idEstado]);
            $nombreEstado = $stmtEst->fetchColumn() ?: "Estado $idEstado";

            $txtComentario = $comentario ?: "Estado actualizado a '$nombreEstado'";
            $stmtHist = $this->db->prepare("INSERT INTO historial_pedido (id_pedido, id_estado, id_usuario, comentario, fecha) VALUES (?, ?, ?, ?, NOW())");
            $stmtHist->execute([$idPedido, $idEstado, $idUsuario ?: null, $txtComentario]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }

    public function getRutasDomicilio(int $idDomiciliario = 0): array
    {
        // Domiciliario ve estrictamente solo sus pedidos asignados en estado Empacado (4) o En camino (5)
        $query = "SELECT p.*, c.nombre as cliente_nombre, c.telefono as cliente_telefono, 
                         dir.direccion, dir.referencia as direccion_detalles, dir.barrio, dir.latitud as cliente_lat, dir.longitud as cliente_lng,
                         z.nombre as zona_nombre,
                         e.nombre as estado_nombre, m.metodo as metodo_pago, m.estado as estado_pago,
                         ad.id_domiciliario
                  FROM pedido p 
                  JOIN usuario c ON p.id_cliente = c.id_usuario
                  JOIN direccion dir ON p.id_direccion = dir.id_direccion
                  LEFT JOIN zona_envio z ON dir.id_zona = z.id_zona
                  LEFT JOIN estado_pedido e ON p.id_estado = e.id_estado
                  LEFT JOIN pago m ON p.id_pedido = m.id_pedido
                  LEFT JOIN asignacion_domicilio ad ON p.id_pedido = ad.id_pedido
                  WHERE p.id_estado IN (4, 5)
                    AND ad.id_domiciliario = ?
                  ORDER BY p.fecha ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$idDomiciliario]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
