<?php
class IngredienteModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getIngredientes(): array
    {
        $stmt = $this->db->query("SELECT * FROM ingrediente");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getIngredienteById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM ingrediente WHERE id_ingrediente = ?");
        $stmt->execute([$id]);
        $ing = $stmt->fetch(PDO::FETCH_ASSOC);
        return $ing ?: null;
    }

    public function createIngrediente(string $nombre): bool
    {
        $stmt = $this->db->prepare("INSERT INTO ingrediente (nombre, estado) VALUES (?, 'disponible')");
        return $stmt->execute([$nombre]);
    }

    public function updateIngrediente(int $id, string $nombre): bool
    {
        $stmt = $this->db->prepare("UPDATE ingrediente SET nombre = ? WHERE id_ingrediente = ?");
        return $stmt->execute([$nombre, $id]);
    }

    public function toggleEstado(int $id): bool
    {
        // Obtener el estado actual
        $ing = $this->getIngredienteById($id);
        if (!$ing) return false;

        $nuevoEstado = ($ing['estado'] === 'disponible') ? 'agotado' : 'disponible';
        
        // Actualizar el estado
        $stmt = $this->db->prepare("UPDATE ingrediente SET estado = ? WHERE id_ingrediente = ?");
        $success = $stmt->execute([$nuevoEstado, $id]);

        // Si se agotó, desactivar los productos que lo contienen
        if ($success && $nuevoEstado === 'agotado') {
            $this->desactivarProductosConIngrediente($id);
        }

        return $success;
    }

    private function desactivarProductosConIngrediente(int $idIngrediente)
    {
        $query = "UPDATE producto p 
                  JOIN producto_ingrediente pi ON p.id_producto = pi.id_producto 
                  SET p.disponible = 0 
                  WHERE pi.id_ingrediente = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$idIngrediente]);
    }

    public function deleteIngrediente(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM ingrediente WHERE id_ingrediente = ?");
        return $stmt->execute([$id]);
    }

    // Funciones para la relación con productos
    public function getIngredientesPorProducto(int $idProducto): array
    {
        $query = "SELECT i.* FROM ingrediente i
                  JOIN producto_ingrediente pi ON i.id_ingrediente = pi.id_ingrediente
                  WHERE pi.id_producto = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$idProducto]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function setIngredientesProducto(int $idProducto, array $ingredientesIds): bool
    {
        try {
            $this->db->beginTransaction();

            // Eliminar relaciones existentes
            $stmtDel = $this->db->prepare("DELETE FROM producto_ingrediente WHERE id_producto = ?");
            $stmtDel->execute([$idProducto]);

            // Insertar nuevas relaciones
            if (!empty($ingredientesIds)) {
                $stmtIns = $this->db->prepare("INSERT INTO producto_ingrediente (id_producto, id_ingrediente) VALUES (?, ?)");
                foreach ($ingredientesIds as $idIng) {
                    $stmtIns->execute([$idProducto, $idIng]);
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}
