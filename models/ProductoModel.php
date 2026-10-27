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
        $stmt = $this->db->query("SELECT * FROM categoria WHERE estado = 'ACTIVA' ORDER BY id_categoria ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProductos(int $categoriaId = 0): array
    {
        if ($categoriaId > 0) {
            $stmt = $this->db->prepare("
                SELECT p.*, c.nombre as categoria_nombre,
                       (SELECT id_receta FROM receta WHERE id_producto = p.id_producto AND estado = 'ACTIVA' LIMIT 1) as id_receta
                FROM producto p
                LEFT JOIN categoria c ON p.id_categoria = c.id_categoria
                WHERE p.id_categoria = ?
                ORDER BY p.id_producto ASC
            ");
            $stmt->execute([$categoriaId]);
        } else {
            $stmt = $this->db->query("
                SELECT p.*, c.nombre as categoria_nombre,
                       (SELECT id_receta FROM receta WHERE id_producto = p.id_producto AND estado = 'ACTIVA' LIMIT 1) as id_receta
                FROM producto p
                LEFT JOIN categoria c ON p.id_categoria = c.id_categoria
                ORDER BY p.id_producto ASC
            ");
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProductoById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT p.*, c.nombre as categoria_nombre,
                   (SELECT id_receta FROM receta WHERE id_producto = p.id_producto AND estado = 'ACTIVA' LIMIT 1) as id_receta
            FROM producto p
            LEFT JOIN categoria c ON p.id_categoria = c.id_categoria
            WHERE p.id_producto = ?
        ");
        $stmt->execute([$id]);
        $prod = $stmt->fetch(PDO::FETCH_ASSOC);
        return $prod ?: null;
    }

    /**
     * Valida y sube una foto de producto de forma segura
     */
    public function procesarSubidaFoto(array $file): array
    {
        if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['success' => true, 'filename' => null];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Error al subir el archivo de imagen (código ' . $file['error'] . ').'];
        }

        // Tamaño máximo 2 MB (2 * 1024 * 1024)
        if ($file['size'] > 2097152) {
            return ['success' => false, 'message' => 'El tamaño de la imagen supera el límite máximo permitido de 2 MB.'];
        }

        // Validar MIME type real con finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/pjpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp'
        ];

        if (!array_key_exists($mime, $allowedMimes)) {
            return ['success' => false, 'message' => 'Tipo de imagen no permitido. Solo se admiten archivos JPG, PNG y WEBP.'];
        }

        // Validar dimensiones reales con getimagesize (rechaza explícitamente SVG y archivos corruptos)
        $imgInfo = @getimagesize($file['tmp_name']);
        if ($imgInfo === false) {
            return ['success' => false, 'message' => 'El archivo no es una imagen válida o está dañado.'];
        }

        $width = $imgInfo[0];
        $height = $imgInfo[1];
        if ($width <= 0 || $height <= 0 || $width > 3000 || $height > 3000) {
            return ['success' => false, 'message' => 'Las dimensiones de la imagen exceden el límite permitido (máximo 3000x3000px).'];
        }

        // Generar nombre de archivo aleatorio del servidor (nunca el original)
        $ext = $allowedMimes[$mime];
        $randomName = 'prod_' . bin2hex(random_bytes(16)) . '.' . $ext;

        $uploadDir = realpath(__DIR__ . '/../public/uploads/productos');
        if (!$uploadDir) {
            $rawDir = __DIR__ . '/../public/uploads/productos';
            if (!is_dir($rawDir)) {
                mkdir($rawDir, 0755, true);
            }
            $uploadDir = realpath($rawDir);
        }

        $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $randomName;

        $moved = is_uploaded_file($file['tmp_name']) 
            ? move_uploaded_file($file['tmp_name'], $targetPath) 
            : copy($file['tmp_name'], $targetPath);

        if (!$moved) {
            return ['success' => false, 'message' => 'No se pudo guardar la imagen en el servidor.'];
        }

        return ['success' => true, 'filename' => $randomName];
    }

    /**
     * Elimina de forma segura una imagen anterior usando basename y realpath
     */
    public function eliminarFotoSegura(?string $nombreArchivo): void
    {
        if (empty($nombreArchivo)) {
            return;
        }

        $safeFilename = basename($nombreArchivo);
        $uploadDir = realpath(__DIR__ . '/../public/uploads/productos');
        if (!$uploadDir) {
            return;
        }

        $fullPath = $uploadDir . DIRECTORY_SEPARATOR . $safeFilename;
        $realFullPath = realpath($fullPath);

        // Verificar que la ruta real exista y resida estrictamente dentro de uploadDir
        if ($realFullPath && strpos($realFullPath, $uploadDir) === 0 && is_file($realFullPath)) {
            @unlink($realFullPath);
        }
    }

    public function createProducto(array $data, ?array $file = null): array
    {
        $idCat = (int)($data['id_categoria'] ?? 1);
        $nombre = trim($data['nombre_producto'] ?? '');
        $descripcion = trim($data['descripcion'] ?? '');
        $precio = (float)($data['precio'] ?? 0);
        $disponible = isset($data['disponible']) ? (int)$data['disponible'] : 1;

        if (empty($nombre)) {
            return ['success' => false, 'message' => 'El nombre del producto es obligatorio.'];
        }
        if ($precio <= 0) {
            return ['success' => false, 'message' => 'El precio debe ser mayor a 0.'];
        }

        $nombreFoto = null;
        if ($file && isset($file['name']) && !empty($file['name'])) {
            $uploadRes = $this->procesarSubidaFoto($file);
            if (!$uploadRes['success']) {
                return $uploadRes;
            }
            $nombreFoto = $uploadRes['filename'];
        }

        $stmt = $this->db->prepare("
            INSERT INTO producto (id_categoria, nombre_producto, descripcion, imagen, precio, stock, stock_minimo, disponible, fecha_creacion, fecha_actualizacion) 
            VALUES (?, ?, ?, ?, ?, 50, 5, ?, NOW(), NOW())
        ");
        
        $ok = $stmt->execute([
            $idCat,
            $nombre,
            $descripcion ?: null,
            $nombreFoto,
            $precio,
            $disponible
        ]);

        if ($ok) {
            return ['success' => true, 'id_producto' => (int)$this->db->lastInsertId(), 'message' => 'Producto creado con éxito.'];
        }
        return ['success' => false, 'message' => 'Error al guardar el producto en la base de datos.'];
    }

    public function updateProducto(int $id, array $data, ?array $file = null): array
    {
        $current = $this->getProductoById($id);
        if (!$current) {
            return ['success' => false, 'message' => 'Producto no encontrado.'];
        }

        $idCat = (int)($data['id_categoria'] ?? $current['id_categoria']);
        $nombre = trim($data['nombre_producto'] ?? $current['nombre_producto']);
        $descripcion = trim($data['descripcion'] ?? $current['descripcion']);
        $precio = (float)($data['precio'] ?? $current['precio']);
        $disponible = isset($data['disponible']) ? (int)$data['disponible'] : (int)$current['disponible'];

        if (empty($nombre)) {
            return ['success' => false, 'message' => 'El nombre del producto es obligatorio.'];
        }
        if ($precio <= 0) {
            return ['success' => false, 'message' => 'El precio debe ser mayor a 0.'];
        }

        $nombreFoto = $current['imagen'];

        // Si se subió un nuevo archivo de imagen
        if ($file && isset($file['name']) && !empty($file['name'])) {
            $uploadRes = $this->procesarSubidaFoto($file);
            if (!$uploadRes['success']) {
                return $uploadRes;
            }
            // Borrar foto vieja si existía
            if (!empty($current['imagen'])) {
                $this->eliminarFotoSegura($current['imagen']);
            }
            $nombreFoto = $uploadRes['filename'];
        }

        $stmt = $this->db->prepare("
            UPDATE producto 
            SET id_categoria = ?, nombre_producto = ?, descripcion = ?, imagen = ?, precio = ?, disponible = ?, fecha_actualizacion = NOW() 
            WHERE id_producto = ?
        ");

        $ok = $stmt->execute([
            $idCat,
            $nombre,
            $descripcion ?: null,
            $nombreFoto,
            $precio,
            $disponible,
            $id
        ]);

        if ($ok) {
            return ['success' => true, 'message' => 'Producto actualizado con éxito.'];
        }
        return ['success' => false, 'message' => 'Error al actualizar el producto en la base de datos.'];
    }

    public function toggleDisponible(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE producto SET disponible = NOT disponible, fecha_actualizacion = NOW() WHERE id_producto = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Oculta o elimina el producto con protección de integridad:
     * Si tiene pedidos asociados en detalle_pedido, solo se desactiva (disponible = 0).
     * Si no tiene pedidos asociados, se puede eliminar de forma segura.
     */
    public function ocultarOEliminar(int $id): array
    {
        $prod = $this->getProductoById($id);
        if (!$prod) {
            return ['success' => false, 'message' => 'Producto no encontrado.'];
        }

        // Verificar si tiene pedidos asociados
        $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM detalle_pedido WHERE id_producto = ?");
        $stmtCount->execute([$id]);
        $hasOrders = (int)$stmtCount->fetchColumn() > 0;

        if ($hasOrders) {
            // Desactivar en lugar de borrar
            $stmtUpd = $this->db->prepare("UPDATE producto SET disponible = 0, fecha_actualizacion = NOW() WHERE id_producto = ?");
            $stmtUpd->execute([$id]);
            return [
                'success' => true,
                'action' => 'desactivado',
                'message' => 'El producto tiene pedidos históricos asociados, por lo que fue desactivado del menú para proteger la integridad del historial.'
            ];
        }

        // Si no tiene pedidos, borrar foto y eliminar
        if (!empty($prod['imagen'])) {
            $this->eliminarFotoSegura($prod['imagen']);
        }

        // Borrar receta asociada si existe
        $this->db->prepare("DELETE FROM receta_detalle WHERE id_receta IN (SELECT id_receta FROM receta WHERE id_producto = ?)")->execute([$id]);
        $this->db->prepare("DELETE FROM receta WHERE id_producto = ?")->execute([$id]);

        $stmtDel = $this->db->prepare("DELETE FROM producto WHERE id_producto = ?");
        $stmtDel->execute([$id]);

        return [
            'success' => true,
            'action' => 'eliminado',
            'message' => 'Producto eliminado correctamente.'
        ];
    }

    /**
     * Obtiene URL de imagen: primero revisa si tiene foto subida, si no, usa el fallback dinámico
     */
    public function getImagenUrl(string $nombre, int $id, ?string $imagen = null, int $width = 600): string
    {
        if (!empty($imagen)) {
            $safe = basename($imagen);
            $full = __DIR__ . '/../public/uploads/productos/' . $safe;
            if (file_exists($full)) {
                return baseUrl('/public/uploads/productos/' . $safe);
            }
        }

        $n = mb_strtolower($nombre, 'UTF-8');

        if (strpos($n, 'santandereano') !== false) {
            return baseUrl('/public/img/productos/chorizo_santandereano.jpg');
        } elseif (strpos($n, 'antioque') !== false || strpos($n, 'antioqueno') !== false) {
            return baseUrl('/public/img/productos/chorizo_antioqueno.jpg');
        } elseif (strpos($n, 'especial') !== false) {
            return baseUrl('/public/img/productos/chorizo_especial.jpg');
        } elseif (strpos($n, 'clasico') !== false || strpos($n, 'clásico') !== false) {
            return baseUrl('/public/img/productos/chorizo_clasico.jpg');
        } elseif (strpos($n, 'picante') !== false) {
            return baseUrl('/public/img/productos/chorizo_picante.jpg');
        } elseif (strpos($n, 'queso') !== false) {
            return baseUrl('/public/img/productos/chorizo_queso.jpg');
        } elseif (strpos($n, 'pareja') !== false) {
            return baseUrl('/public/img/productos/combo_pareja.jpg');
        } elseif (strpos($n, 'familiar') !== false) {
            return baseUrl('/public/img/productos/combo_familiar.jpg');
        } elseif (strpos($n, 'parrilla') !== false) {
            return baseUrl('/public/img/productos/combo_parrilla.jpg');
        } elseif (strpos($n, 'gaseosa') !== false) {
            return baseUrl('/public/img/productos/gaseosa.jpg');
        } elseif (strpos($n, 'jugo') !== false) {
            return baseUrl('/public/img/productos/jugo_natural.jpg');
        } elseif (strpos($n, 'limonada') !== false) {
            return baseUrl('/public/img/productos/limonada.jpg');
        } elseif (strpos($n, 'cerveza') !== false) {
            return baseUrl('/public/img/productos/cerveza.jpg');
        } elseif (strpos($n, 'chimichurri') !== false) {
            return baseUrl('/public/img/productos/chimichurri.jpg');
        } elseif (strpos($n, 'salsa') !== false) {
            return baseUrl('/public/img/productos/salsa_casa.jpg');
        } elseif (strpos($n, 'papa') !== false) {
            return baseUrl('/public/img/productos/papa_francesa.jpg');
        } elseif (strpos($n, 'premium') !== false) {
            return baseUrl('/public/img/productos/chorizo_especial.jpg');
        }

        return baseUrl('/public/img/productos/chorizo_clasico.jpg');
    }
}
