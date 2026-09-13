<?php
class ProductoModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getCategorias(): array
    {
        $stmt = $this->db->query("SELECT * FROM categoria WHERE estado = 'ACTIVA'");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProductos(int $categoriaId = 0): array
    {
        if ($categoriaId > 0) {
            $stmt = $this->db->prepare("SELECT * FROM producto WHERE id_categoria = ?");
            $stmt->execute([$categoriaId]);
        } else {
            $stmt = $this->db->query("SELECT * FROM producto");
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProductoById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM producto WHERE id_producto = ?");
        $stmt->execute([$id]);
        $prod = $stmt->fetch(PDO::FETCH_ASSOC);
        return $prod ?: null;
    }

    public function createProducto(array $data): bool
    {
        $stmt = $this->db->prepare("INSERT INTO producto (id_categoria, nombre_producto, descripcion, precio, stock, stock_minimo, disponible, fecha_creacion, fecha_actualizacion) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        
        $stock = (int)($data['stock'] ?? 0);
        $disponible = ($stock <= 0) ? 0 : ($data['disponible'] ?? 1);

        return $stmt->execute([
            $data['id_categoria'] ?? 1,
            $data['nombre_producto'],
            $data['descripcion'],
            $data['precio'],
            $stock,
            (int)($data['stock_minimo'] ?? 5),
            $disponible
        ]);
    }

    public function updateProducto(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("UPDATE producto SET id_categoria = ?, nombre_producto = ?, descripcion = ?, precio = ?, stock = ?, stock_minimo = ?, disponible = ?, fecha_actualizacion = NOW() WHERE id_producto = ?");
        
        $stock = (int)($data['stock'] ?? 0);
        $disponible = ($stock <= 0) ? 0 : ($data['disponible'] ?? 1);

        return $stmt->execute([
            $data['id_categoria'] ?? 1,
            $data['nombre_producto'],
            $data['descripcion'],
            $data['precio'],
            $stock,
            (int)($data['stock_minimo'] ?? 5),
            $disponible,
            $id
        ]);
    }

    public function toggleDisponible(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE producto SET disponible = NOT disponible, fecha_actualizacion = NOW() WHERE id_producto = ?");
        return $stmt->execute([$id]);
    }

    // Helper para asignar imágenes dinámicas según el nombre
    public function getImagenUrl(string $nombre, int $id, int $width = 600): string
    {
        $n = mb_strtolower($nombre, 'UTF-8');

        if (strpos($n, 'santandereano') !== false) {
            return '/public/img/productos/chorizo_santandereano.jpg';
        } elseif (strpos($n, 'antioqueño') !== false || strpos($n, 'antioqueno') !== false) {
            return '/public/img/productos/chorizo_antioqueno.jpg';
        } elseif (strpos($n, 'especial') !== false) {
            return '/public/img/productos/chorizo_especial.jpg';
        } elseif (strpos($n, 'combo pareja') !== false) {
            return '/public/img/productos/combo_pareja.jpg';
        } elseif (strpos($n, 'combo familiar') !== false) {
            return '/public/img/productos/combo_familiar.jpg';
        } elseif (strpos($n, 'gaseosa') !== false) {
            return '/public/img/productos/gaseosa.jpg';
        } elseif (strpos($n, 'jugo') !== false) {
            return '/public/img/productos/jugo_natural.jpg';
        } elseif (strpos($n, 'salsa') !== false) {
            return '/public/img/productos/salsa_casa.jpg';
        } elseif (strpos($n, 'papa') !== false) {
            return '/public/img/productos/papa_francesa.jpg';
        }

        $height = intval($width * (2/3)); // Relación de aspecto 3:2

        if (strpos($n, 'hamburguesa') !== false || strpos($n, 'burger') !== false) {
            $keyword = 'burger,food';
        } elseif (strpos($n, 'chorizo') !== false || strpos($n, 'picada') !== false || strpos($n, 'chori') !== false || strpos($n, 'carne') !== false) {
            $keyword = 'sausage,grill';
        } elseif (strpos($n, 'perro') !== false || strpos($n, 'caliente') !== false || strpos($n, 'hotdog') !== false) {
            $keyword = 'hotdog,food';
        } elseif (strpos($n, 'papa') !== false || strpos($n, 'frita') !== false || strpos($n, 'salchipapa') !== false) {
            $keyword = 'fries,food';
        } elseif (strpos($n, 'bebida') !== false || strpos($n, 'gaseosa') !== false || strpos($n, 'jugo') !== false || strpos($n, 'coca') !== false) {
            $keyword = 'drink,beverage';
        } elseif (strpos($n, 'pollo') !== false || strpos($n, 'alitas') !== false) {
            $keyword = 'chicken,food';
        } elseif (strpos($n, 'combo') !== false) {
            $keyword = 'fastfood';
        } else {
            $keyword = 'bbq,meat';
        }
        
        // Usar loremflickr para buscar fotos reales según la palabra clave. 
        // Se añade ?lock=ID para que la foto no cambie constantemente cada vez que se recarga.
        return "https://loremflickr.com/{$width}/{$height}/{$keyword}?lock={$id}";
    }
}
