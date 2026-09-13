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
        // Obtener pedidos pendientes (1), confirmados (2), en preparación (3) y listos (4)
        $query = "SELECT p.*, c.nombre as cliente_nombre, e.nombre as estado_nombre 
                  FROM pedido p 
                  JOIN usuario c ON p.id_cliente = c.id_usuario
                  LEFT JOIN estado_pedido e ON p.id_estado = e.id_estado
                  WHERE p.id_estado IN (2, 3, 4)
                  ORDER BY p.fecha ASC";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDetallesPedido(int $idPedido): array
    {
        $query = "SELECT d.*, pr.nombre_producto 
                  FROM detalle_pedido d
                  JOIN producto pr ON d.id_producto = pr.id_producto
                  WHERE d.id_pedido = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$idPedido]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updatePedidoStatus(int $idPedido, int $idEstado): bool
    {
        $stmt = $this->db->prepare("UPDATE pedido SET id_estado = ? WHERE id_pedido = ?");
        return $stmt->execute([$idEstado, $idPedido]);
    }

    public function getRutasDomicilio(): array
    {
        // Domiciliario ve pedidos Listos para despachar (4), En camino (5), y Demorados (11)
        $query = "SELECT p.*, c.nombre as cliente_nombre, c.telefono as cliente_telefono, 
                         dir.direccion, dir.referencia as direccion_detalles, dir.barrio, z.nombre as zona_nombre,
                         e.nombre as estado_nombre, m.metodo as metodo_pago, m.estado as estado_pago
                  FROM pedido p 
                  JOIN usuario c ON p.id_cliente = c.id_usuario
                  JOIN direccion dir ON p.id_direccion = dir.id_direccion
                  LEFT JOIN zona_envio z ON dir.id_zona = z.id_zona
                  LEFT JOIN estado_pedido e ON p.id_estado = e.id_estado
                  LEFT JOIN pago m ON p.id_pedido = m.id_pedido
                  WHERE p.id_estado IN (4, 5, 11)
                  ORDER BY p.fecha ASC";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
