<?php
require_once __DIR__ . '/../config/database.php';

class InventarioModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Obtiene todos los insumos con stock actual y estado de alerta
     */
    public function getInsumos(): array
    {
        $query = "
            SELECT i.id_insumo, i.nombre, i.unidad_medida, i.stock_minimo, i.estado,
                   COALESCE(inv.cantidad, 0.000) as stock_actual,
                   inv.fecha_actualizacion,
                   CASE WHEN COALESCE(inv.cantidad, 0) <= i.stock_minimo THEN 1 ELSE 0 END as alerta_stock,
                   (SELECT COUNT(DISTINCT r.id_producto) 
                    FROM receta_detalle rd 
                    JOIN receta r ON rd.id_receta = r.id_receta 
                    WHERE rd.id_insumo = i.id_insumo) as total_productos_receta
            FROM insumo i
            LEFT JOIN inventario_insumo inv ON i.id_insumo = inv.id_insumo
            WHERE i.estado = 'ACTIVO'
            ORDER BY i.nombre ASC
        ";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un insumo por ID
     */
    public function getInsumoById(int $idInsumo): ?array
    {
        $query = "
            SELECT i.id_insumo, i.nombre, i.unidad_medida, i.stock_minimo, i.estado,
                   COALESCE(inv.cantidad, 0.000) as stock_actual
            FROM insumo i
            LEFT JOIN inventario_insumo inv ON i.id_insumo = inv.id_insumo
            WHERE i.id_insumo = ?
        ";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$idInsumo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Registra una entrada de stock (compra o reposición) con motivo obligatorio
     */
    public function registrarEntrada(int $idInsumo, float $cantidad, string $motivo, int $idUsuario = 0): array
    {
        if ($cantidad <= 0) {
            return ['success' => false, 'message' => 'La cantidad debe ser mayor a 0.'];
        }
        $motivo = trim($motivo);
        if (empty($motivo)) {
            return ['success' => false, 'message' => 'El motivo de la entrada es obligatorio.'];
        }

        try {
            $this->db->beginTransaction();

            // Bloqueo y actualización atómica
            $stmtLock = $this->db->prepare("SELECT cantidad FROM inventario_insumo WHERE id_insumo = ? FOR UPDATE");
            $stmtLock->execute([$idInsumo]);
            $current = $stmtLock->fetchColumn();

            if ($current === false) {
                // Si no existía registro en inventario_insumo, crearlo
                $stmtIns = $this->db->prepare("INSERT INTO inventario_insumo (id_insumo, cantidad, fecha_actualizacion) VALUES (?, ?, NOW())");
                $stmtIns->execute([$idInsumo, $cantidad]);
            } else {
                $stmtUpd = $this->db->prepare("UPDATE inventario_insumo SET cantidad = cantidad + ?, fecha_actualizacion = NOW() WHERE id_insumo = ?");
                $stmtUpd->execute([$cantidad, $idInsumo]);
            }

            // Registrar movimiento
            $stmtMov = $this->db->prepare("
                INSERT INTO movimiento_inventario (id_insumo, id_usuario, tipo, cantidad, motivo, fecha) 
                VALUES (?, ?, 'ENTRADA', ?, ?, NOW())
            ");
            $stmtMov->execute([$idInsumo, $idUsuario ?: null, $cantidad, $motivo]);

            $this->db->commit();
            return ['success' => true, 'message' => 'Entrada registrada con éxito.'];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => 'Error al registrar entrada: ' . $e->getMessage()];
        }
    }

    /**
     * Registra un ajuste de stock (fijando un nuevo stock) con motivo obligatorio
     * No se permite stock negativo.
     */
    public function registrarAjuste(int $idInsumo, float $nuevoStock, string $motivo, int $idUsuario = 0): array
    {
        if ($nuevoStock < 0) {
            return ['success' => false, 'message' => 'No se permite stock negativo.'];
        }
        $motivo = trim($motivo);
        if (empty($motivo)) {
            return ['success' => false, 'message' => 'El motivo del ajuste es obligatorio.'];
        }

        try {
            $this->db->beginTransaction();

            $stmtLock = $this->db->prepare("SELECT cantidad FROM inventario_insumo WHERE id_insumo = ? FOR UPDATE");
            $stmtLock->execute([$idInsumo]);
            $current = $stmtLock->fetchColumn();
            $stockActual = ($current !== false) ? (float)$current : 0.0;

            $diferencia = $nuevoStock - $stockActual;

            if ($current === false) {
                $stmtIns = $this->db->prepare("INSERT INTO inventario_insumo (id_insumo, cantidad, fecha_actualizacion) VALUES (?, ?, NOW())");
                $stmtIns->execute([$idInsumo, $nuevoStock]);
            } else {
                $stmtUpd = $this->db->prepare("UPDATE inventario_insumo SET cantidad = ?, fecha_actualizacion = NOW() WHERE id_insumo = ?");
                $stmtUpd->execute([$nuevoStock, $idInsumo]);
            }

            // Registrar movimiento con la diferencia
            $stmtMov = $this->db->prepare("
                INSERT INTO movimiento_inventario (id_insumo, id_usuario, tipo, cantidad, motivo, fecha) 
                VALUES (?, ?, 'AJUSTE', ?, ?, NOW())
            ");
            $detalleMotivo = "$motivo (Ajuste de " . number_format($stockActual, 3) . " a " . number_format($nuevoStock, 3) . ")";
            $stmtMov->execute([$idInsumo, $idUsuario ?: null, $diferencia, $detalleMotivo]);

            $this->db->commit();
            return ['success' => true, 'message' => 'Ajuste de inventario registrado con éxito.'];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => 'Error al registrar ajuste: ' . $e->getMessage()];
        }
    }

    /**
     * Obtiene el historial de movimientos de inventario
     */
    public function getMovimientos(int $idInsumo = 0, int $limit = 100): array
    {
        $params = [];
        $where = "WHERE 1=1";
        if ($idInsumo > 0) {
            $where .= " AND m.id_insumo = ?";
            $params[] = $idInsumo;
        }

        $query = "
            SELECT m.*, i.nombre as insumo_nombre, i.unidad_medida,
                   u.nombre as usuario_nombre
            FROM movimiento_inventario m
            LEFT JOIN insumo i ON m.id_insumo = i.id_insumo
            LEFT JOIN usuario u ON m.id_usuario = u.id_usuario
            $where
            ORDER BY m.fecha DESC, m.id_movimiento DESC
            LIMIT " . (int)$limit;

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Descuenta el stock de un pedido al pasar a "Empacado".
     * - Idempotente (si stock_descontado = 1, no vuelve a descontar).
     * - Ordena insumos por id_insumo ASC para prevenir deadlocks.
     * - Reintenta 1 vez ante deadlock (error 1213).
     * - Si no alcanza el stock: descuenta hasta 0, registra FALTANTE y alerta al admin.
     */
    public function descontarStockPedido(int $idPedido): array
    {
        $maxRetries = 2;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $attempt++;
            try {
                return $this->ejecutarDescuentoPedido($idPedido);
            } catch (PDOException $e) {
                // Código MySQL 1213 = Deadlock found when trying to get lock
                if ($e->getCode() == '40001' || strpos($e->getMessage(), '1213') !== false) {
                    if ($this->db->inTransaction()) {
                        $this->db->rollBack();
                    }
                    if ($attempt < $maxRetries) {
                        usleep(100000); // 100ms antes de reintentar
                        continue;
                    }
                }
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                return ['success' => false, 'message' => 'Error de concurrencia: ' . $e->getMessage()];
            } catch (Exception $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
        }

        return ['success' => false, 'message' => 'No fue posible completar el descuento tras reintentos.'];
    }

    private function ejecutarDescuentoPedido(int $idPedido): array
    {
        $this->db->beginTransaction();

        // 1. Verificar idempotencia
        $stmtPed = $this->db->prepare("SELECT id_pedido, id_estado, stock_descontado FROM pedido WHERE id_pedido = ? FOR UPDATE");
        $stmtPed->execute([$idPedido]);
        $ped = $stmtPed->fetch(PDO::FETCH_ASSOC);

        if (!$ped) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Pedido no encontrado.'];
        }

        if ((int)$ped['stock_descontado'] === 1) {
            $this->db->rollBack();
            return ['success' => true, 'message' => 'El stock de este pedido ya fue descontado previamente.', 'ya_descontado' => true];
        }

        // 2. Obtener productos del pedido y sus recetas
        $stmtItems = $this->db->prepare("SELECT id_producto, cantidad FROM detalle_pedido WHERE id_pedido = ?");
        $stmtItems->execute([$idPedido]);
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        // Calcular insumos totales requeridos por el pedido
        $insumosRequeridos = []; // [id_insumo => ['nombre' => ..., 'cantidad' => ...]]

        foreach ($items as $item) {
            $idProd = (int)$item['id_producto'];
            $cantItem = (int)$item['cantidad'];

            $stmtRec = $this->db->prepare("
                SELECT rd.id_insumo, i.nombre, rd.cantidad as cant_unitaria
                FROM receta r
                JOIN receta_detalle rd ON r.id_receta = rd.id_receta
                JOIN insumo i ON rd.id_insumo = i.id_insumo
                WHERE r.id_producto = ? AND r.estado = 'ACTIVA'
            ");
            $stmtRec->execute([$idProd]);
            $ingredientesReceta = $stmtRec->fetchAll(PDO::FETCH_ASSOC);

            foreach ($ingredientesReceta as $ing) {
                $idIns = (int)$ing['id_insumo'];
                $totalIng = (float)$ing['cant_unitaria'] * $cantItem;

                if (!isset($insumosRequeridos[$idIns])) {
                    $insumosRequeridos[$idIns] = [
                        'id_insumo' => $idIns,
                        'nombre' => $ing['nombre'],
                        'cantidad' => 0.0
                    ];
                }
                $insumosRequeridos[$idIns]['cantidad'] += $totalIng;
            }
        }

        // Si el pedido no tiene recetas (ej. solo bebidas en lata sin receta), marcar stock_descontado = 1
        if (empty($insumosRequeridos)) {
            $this->db->prepare("UPDATE pedido SET stock_descontado = 1 WHERE id_pedido = ?")->execute([$idPedido]);
            $this->db->commit();
            return ['success' => true, 'message' => 'Pedido sin recetas: marcado descontado sin alterar insumos.', 'sin_receta' => true];
        }

        // 3. ORDENAR INSUMOS POR id_insumo ASC PARA EVITAR DEADLOCKS (Regla 2 del prompt)
        ksort($insumosRequeridos);

        $huboFaltante = false;
        $detallesFaltante = [];

        $stmtLock = $this->db->prepare("SELECT cantidad FROM inventario_insumo WHERE id_insumo = ? FOR UPDATE");
        $stmtUpdStock = $this->db->prepare("UPDATE inventario_insumo SET cantidad = ?, fecha_actualizacion = NOW() WHERE id_insumo = ?");
        $stmtMovSalida = $this->db->prepare("
            INSERT INTO movimiento_inventario (id_insumo, id_pedido, tipo, cantidad, motivo, fecha) 
            VALUES (?, ?, 'SALIDA_RECETA', ?, ?, NOW())
        ");
        $stmtMovFaltante = $this->db->prepare("
            INSERT INTO movimiento_inventario (id_insumo, id_pedido, tipo, cantidad, motivo, fecha) 
            VALUES (?, ?, 'FALTANTE', ?, ?, NOW())
        ");

        foreach ($insumosRequeridos as $idIns => $req) {
            $stmtLock->execute([$idIns]);
            $currentVal = $stmtLock->fetchColumn();
            $stockActual = ($currentVal !== false) ? (float)$currentVal : 0.0;
            $cantRequerida = (float)$req['cantidad'];

            if ($stockActual >= $cantRequerida) {
                // Descuento normal completo
                $nuevoStock = $stockActual - $cantRequerida;
                $stmtUpdStock->execute([$nuevoStock, $idIns]);

                $motivo = "Consumo por receta en Pedido #$idPedido";
                $stmtMovSalida->execute([$idIns, $idPedido, $cantRequerida, $motivo]);
            } else {
                // Stock insuficiente: descuenta hasta 0 y registra FALTANTE (Regla del prompt)
                $huboFaltante = true;
                $salidaPosible = $stockActual > 0 ? $stockActual : 0.0;
                $faltante = $cantRequerida - $salidaPosible;

                $stmtUpdStock->execute([0.0, $idIns]);

                if ($salidaPosible > 0) {
                    $motivoSalida = "Consumo parcial (stock agotado) en Pedido #$idPedido";
                    $stmtMovSalida->execute([$idIns, $idPedido, $salidaPosible, $motivoSalida]);
                }

                $motivoFaltante = "Faltante en preparación del Pedido #$idPedido ({$req['nombre']})";
                $stmtMovFaltante->execute([$idIns, $idPedido, $faltante, $motivoFaltante]);
                $detallesFaltante[] = "{$req['nombre']}: faltaron " . number_format($faltante, 3);
            }
        }

        // Marcar el pedido como descontado
        $stmtDone = $this->db->prepare("UPDATE pedido SET stock_descontado = 1 WHERE id_pedido = ?");
        $stmtDone->execute([$idPedido]);

        // Si hubo faltante, generar alerta crítica para el Admin
        if ($huboFaltante) {
            $msgAlerta = "⚠️ ALERTA CRÍTICA: Stock insuficiente en preparación del Pedido #$idPedido. " . implode(", ", $detallesFaltante);
            $stmtAlert = $this->db->prepare("
                INSERT INTO notificacion (id_usuario, id_pedido, titulo, mensaje, leida, fecha)
                SELECT u.id_usuario, ?, 'Stock Insuficiente en Cocina', ?, 0, NOW()
                FROM usuario u WHERE u.id_rol = 2
            ");
            $stmtAlert->execute([$idPedido, $msgAlerta]);
        }

        $this->db->commit();
        return [
            'success' => true,
            'message' => $huboFaltante ? 'Descuento completado con faltantes registrados.' : 'Descuento de stock completado exitosamente.',
            'hubo_faltante' => $huboFaltante,
            'detalles_faltante' => $detallesFaltante
        ];
    }

    /**
     * Reintegra el stock de un pedido cancelado empacado.
     * - Guarda: stock_descontado = 1 && stock_reintegrado = 0
     * - Suma ÚNICAMENTE los movimientos 'SALIDA_RECETA' de ese pedido (ignora FALTANTE).
     * - Asigna stock_reintegrado = 1 (nunca se reintegra dos veces).
     */
    public function reintegrarStockPedido(int $idPedido, string $motivo, int $idUsuario = 0): array
    {
        $motivo = trim($motivo);
        if (empty($motivo)) {
            $motivo = "Reintegro por cancelación de Pedido #$idPedido";
        }

        try {
            $this->db->beginTransaction();

            // 1. Verificar guardia de reintegro único
            $stmtPed = $this->db->prepare("SELECT id_pedido, stock_descontado, stock_reintegrado FROM pedido WHERE id_pedido = ? FOR UPDATE");
            $stmtPed->execute([$idPedido]);
            $ped = $stmtPed->fetch(PDO::FETCH_ASSOC);

            if (!$ped) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Pedido no encontrado.'];
            }

            if ((int)$ped['stock_descontado'] !== 1) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Este pedido nunca fue descontado de inventario. No hay insumos que reintegrar.'];
            }

            if ((int)$ped['stock_reintegrado'] === 1) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'El stock de este pedido ya fue reintegrado previamente.'];
            }

            // 2. Obtener la suma exacta de lo que SALIÓ del inventario (SALIDA_RECETA, no FALTANTE)
            $stmtSalidas = $this->db->prepare("
                SELECT id_insumo, SUM(cantidad) as total_salida
                FROM movimiento_inventario
                WHERE id_pedido = ? AND tipo = 'SALIDA_RECETA'
                GROUP BY id_insumo
                ORDER BY id_insumo ASC
            ");
            $stmtSalidas->execute([$idPedido]);
            $salidas = $stmtSalidas->fetchAll(PDO::FETCH_ASSOC);

            if (empty($salidas)) {
                // No hubo salidas físicas
                $this->db->prepare("UPDATE pedido SET stock_reintegrado = 1 WHERE id_pedido = ?")->execute([$idPedido]);
                $this->db->commit();
                return ['success' => true, 'message' => 'No hubo salidas físicas registradas. Pedido marcado como reintegrado.'];
            }

            $stmtUpdStock = $this->db->prepare("UPDATE inventario_insumo SET cantidad = cantidad + ?, fecha_actualizacion = NOW() WHERE id_insumo = ?");
            $stmtMovReintegro = $this->db->prepare("
                INSERT INTO movimiento_inventario (id_insumo, id_pedido, id_usuario, tipo, cantidad, motivo, fecha) 
                VALUES (?, ?, ?, 'REINTEGRO_CANCELACION', ?, ?, NOW())
            ");

            foreach ($salidas as $salida) {
                $idIns = (int)$salida['id_insumo'];
                $cantReingreso = (float)$salida['total_salida'];

                if ($cantReingreso > 0) {
                    $stmtUpdStock->execute([$cantReingreso, $idIns]);
                    $stmtMovReintegro->execute([$idIns, $idPedido, $idUsuario ?: null, $cantReingreso, $motivo]);
                }
            }

            // Marcar guardia de reintegro en pedido
            $this->db->prepare("UPDATE pedido SET stock_reintegrado = 1 WHERE id_pedido = ?")->execute([$idPedido]);

            $this->db->commit();
            return ['success' => true, 'message' => 'Insumos reintegrados al inventario exitosamente.'];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => 'Error al reintegrar stock: ' . $e->getMessage()];
        }
    }

    /**
     * Retorna el estado de disponibilidad por stock de recetas para todos los productos,
     * calculando la cantidad máxima disponible por porciones de insumos.
     */
    public function getProductosStockStatus(): array
    {
        $query = "
            SELECT p.id_producto, p.nombre_producto, p.disponible as disponible_admin,
                   r.id_receta,
                   rd.id_insumo, i.nombre as insumo_nombre,
                   rd.cantidad as cantidad_unitaria,
                   COALESCE(inv.cantidad, 0.0) as stock_actual
            FROM producto p
            LEFT JOIN receta r ON p.id_producto = r.id_producto AND r.estado = 'ACTIVA'
            LEFT JOIN receta_detalle rd ON r.id_receta = rd.id_receta
            LEFT JOIN insumo i ON rd.id_insumo = i.id_insumo
            LEFT JOIN inventario_insumo inv ON rd.id_insumo = inv.id_insumo
            ORDER BY p.id_producto ASC
        ";
        $stmt = $this->db->query($query);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $statusMap = [];

        foreach ($rows as $r) {
            $idProd = (int)$r['id_producto'];

            if (!isset($statusMap[$idProd])) {
                $statusMap[$idProd] = [
                    'id_producto' => $idProd,
                    'nombre_producto' => $r['nombre_producto'],
                    'disponible_admin' => (int)$r['disponible_admin'] === 1,
                    'tiene_receta' => false,
                    'insumos_insuficientes' => [],
                    'disponible_por_stock' => true,
                    'cantidad_maxima' => null, // null = sin receta (sin límite de receta)
                    '_min_porciones' => PHP_INT_MAX
                ];
            }

            if (!empty($r['id_insumo']) && !empty($r['id_receta'])) {
                $statusMap[$idProd]['tiene_receta'] = true;
                $stockActual = (float)$r['stock_actual'];
                $cantReq = (float)$r['cantidad_unitaria'];

                if ($cantReq > 0) {
                    $porcionesInsumo = (int)floor($stockActual / $cantReq);
                    if ($porcionesInsumo < $statusMap[$idProd]['_min_porciones']) {
                        $statusMap[$idProd]['_min_porciones'] = $porcionesInsumo;
                    }
                }

                if ($stockActual < $cantReq) {
                    $statusMap[$idProd]['disponible_por_stock'] = false;
                    $statusMap[$idProd]['insumos_insuficientes'][] = [
                        'insumo' => $r['insumo_nombre'],
                        'stock' => $stockActual,
                        'requerido' => $cantReq
                    ];
                }
            }
        }

        // Finalizar cálculo de cantidad máxima y visibilidad
        foreach ($statusMap as &$p) {
            if ($p['tiene_receta']) {
                $p['cantidad_maxima'] = max(0, $p['_min_porciones']);
                if ($p['cantidad_maxima'] <= 0) {
                    $p['disponible_por_stock'] = false;
                }
            } else {
                $p['cantidad_maxima'] = null; // Productos sin receta no tienen límite
            }
            unset($p['_min_porciones']);

            $p['visible_cliente'] = $p['disponible_admin'] && $p['disponible_por_stock'];
        }

        return $statusMap;
    }

    /**
     * Calcula la cantidad máxima de un producto según el mínimo de
     * floor(stock del insumo / cantidad de la receta) entre sus insumos.
     * Retorna null si el producto no tiene receta (sin límite).
     */
    public function getCantidadMaximaDisponible(int $idProducto): ?int
    {
        $stmt = $this->db->prepare("
            SELECT rd.id_insumo, rd.cantidad as cant_unitaria, COALESCE(inv.cantidad, 0.0) as stock_actual
            FROM receta r
            JOIN receta_detalle rd ON r.id_receta = rd.id_receta
            LEFT JOIN inventario_insumo inv ON rd.id_insumo = inv.id_insumo
            WHERE r.id_producto = ? AND r.estado = 'ACTIVA'
        ");
        $stmt->execute([$idProducto]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            return null; // Sin receta no tiene límite
        }

        $minPorciones = PHP_INT_MAX;
        foreach ($rows as $r) {
            $cantReq = (float)$r['cant_unitaria'];
            $stock = (float)$r['stock_actual'];
            if ($cantReq > 0) {
                $p = (int)floor($stock / $cantReq);
                if ($p < $minPorciones) {
                    $minPorciones = $p;
                }
            }
        }

        return max(0, $minPorciones);
    }

    /**
     * Valida el stock global de insumos para el carrito completo sumando el consumo
     * de todos los productos y combos incluidos en el carrito.
     * Retorna ['valido' => bool, 'faltantes' => array, 'mensaje' => string]
     */
    public function verificarStockCarrito(array $carrito): array
    {
        if (empty($carrito)) {
            return ['valido' => true, 'faltantes' => [], 'mensaje' => ''];
        }

        $insumosRequeridos = [];

        foreach ($carrito as $item) {
            $idProd = (int)($item['producto']['id_producto'] ?? ($item['id_producto'] ?? 0));
            $cantItem = (int)($item['cantidad'] ?? 0);

            if ($idProd <= 0 || $cantItem <= 0) {
                continue;
            }

            $stmtRec = $this->db->prepare("
                SELECT rd.id_insumo, i.nombre, i.unidad_medida, rd.cantidad as cant_unitaria, COALESCE(inv.cantidad, 0.0) as stock_actual
                FROM receta r
                JOIN receta_detalle rd ON r.id_receta = rd.id_receta
                JOIN insumo i ON rd.id_insumo = i.id_insumo
                LEFT JOIN inventario_insumo inv ON rd.id_insumo = inv.id_insumo
                WHERE r.id_producto = ? AND r.estado = 'ACTIVA'
            ");
            $stmtRec->execute([$idProd]);
            $ingredientes = $stmtRec->fetchAll(PDO::FETCH_ASSOC);

            foreach ($ingredientes as $ing) {
                $idIns = (int)$ing['id_insumo'];
                $totalReq = (float)$ing['cant_unitaria'] * $cantItem;

                if (!isset($insumosRequeridos[$idIns])) {
                    $insumosRequeridos[$idIns] = [
                        'id_insumo' => $idIns,
                        'nombre' => $ing['nombre'],
                        'unidad_medida' => $ing['unidad_medida'],
                        'stock_actual' => (float)$ing['stock_actual'],
                        'cantidad_requerida' => 0.0
                    ];
                }
                $insumosRequeridos[$idIns]['cantidad_requerida'] += $totalReq;
            }
        }

        $faltantes = [];
        $textos = [];

        foreach ($insumosRequeridos as $idIns => $req) {
            // Margen de tolerancia para números flotantes
            if ($req['stock_actual'] < ($req['cantidad_requerida'] - 0.0001)) {
                $dif = $req['cantidad_requerida'] - $req['stock_actual'];
                $faltantes[] = [
                    'id_insumo' => $idIns,
                    'nombre' => $req['nombre'],
                    'unidad_medida' => $req['unidad_medida'],
                    'requerido' => $req['cantidad_requerida'],
                    'disponible' => $req['stock_actual'],
                    'faltante' => $dif
                ];
                $textos[] = "{$req['nombre']} (requiere " . number_format($req['cantidad_requerida'], 3) . " {$req['unidad_medida']}, disponible " . number_format($req['stock_actual'], 3) . " {$req['unidad_medida']})";
            }
        }

        $valido = empty($faltantes);
        $mensaje = $valido ? '' : "Stock insuficiente para procesar el pedido con la combinación de productos solicitada. Insumos con déficit: " . implode('; ', $textos) . ". Por favor reduce las cantidades en tu carrito.";

        return [
            'valido' => $valido,
            'faltantes' => $faltantes,
            'mensaje' => $mensaje
        ];
    }

    /**
     * Verifica si el stock de inventario alcanza para preparar todos los productos de un pedido.
     * Utilizado para advertir al Administrador antes de aceptar el pedido.
     */
    public function verificarStockParaPedido(int $idPedido): array
    {
        $stmtItems = $this->db->prepare("SELECT id_producto, nombre_producto, cantidad FROM detalle_pedido WHERE id_pedido = ?");
        $stmtItems->execute([$idPedido]);
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        $insumosRequeridos = [];

        foreach ($items as $item) {
            $idProd = (int)$item['id_producto'];
            $cantItem = (int)$item['cantidad'];

            $stmtRec = $this->db->prepare("
                SELECT rd.id_insumo, i.nombre, i.unidad_medida, rd.cantidad as cant_unitaria, COALESCE(inv.cantidad, 0.0) as stock_actual
                FROM receta r
                JOIN receta_detalle rd ON r.id_receta = rd.id_receta
                JOIN insumo i ON rd.id_insumo = i.id_insumo
                LEFT JOIN inventario_insumo inv ON rd.id_insumo = inv.id_insumo
                WHERE r.id_producto = ? AND r.estado = 'ACTIVA'
            ");
            $stmtRec->execute([$idProd]);
            $ingredientes = $stmtRec->fetchAll(PDO::FETCH_ASSOC);

            foreach ($ingredientes as $ing) {
                $idIns = (int)$ing['id_insumo'];
                $totalReq = (float)$ing['cant_unitaria'] * $cantItem;

                if (!isset($insumosRequeridos[$idIns])) {
                    $insumosRequeridos[$idIns] = [
                        'id_insumo' => $idIns,
                        'nombre' => $ing['nombre'],
                        'unidad_medida' => $ing['unidad_medida'],
                        'stock_actual' => (float)$ing['stock_actual'],
                        'cantidad_requerida' => 0.0
                    ];
                }
                $insumosRequeridos[$idIns]['cantidad_requerida'] += $totalReq;
            }
        }

        $faltantes = [];
        $textos = [];

        foreach ($insumosRequeridos as $idIns => $req) {
            if ($req['stock_actual'] < $req['cantidad_requerida']) {
                $dif = $req['cantidad_requerida'] - $req['stock_actual'];
                $faltantes[] = [
                    'id_insumo' => $idIns,
                    'nombre' => $req['nombre'],
                    'unidad_medida' => $req['unidad_medida'],
                    'requerido' => $req['cantidad_requerida'],
                    'disponible' => $req['stock_actual'],
                    'faltante' => $dif
                ];
                $textos[] = "{$req['nombre']} (requiere " . number_format($req['cantidad_requerida'], 3) . " {$req['unidad_medida']}, hay " . number_format($req['stock_actual'], 3) . " {$req['unidad_medida']})";
            }
        }

        return [
            'suficiente' => empty($faltantes),
            'faltantes' => $faltantes,
            'detalle_texto' => !empty($textos) ? implode(', ', $textos) : ''
        ];
    }

    /**
     * Obtiene la receta de un producto junto con sus detalles de insumos
     */
    public function getRecetaByProducto(int $idProducto): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM receta WHERE id_producto = ? ORDER BY id_receta DESC LIMIT 1");
        $stmt->execute([$idProducto]);
        $receta = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$receta) {
            return null;
        }

        $stmtDet = $this->db->prepare("
            SELECT rd.id_insumo, i.nombre as insumo_nombre, i.unidad_medida, rd.cantidad,
                   COALESCE(inv.cantidad, 0.0) as stock_actual
            FROM receta_detalle rd
            JOIN insumo i ON rd.id_insumo = i.id_insumo
            LEFT JOIN inventario_insumo inv ON i.id_insumo = inv.id_insumo
            WHERE rd.id_receta = ?
            ORDER BY rd.id_insumo ASC
        ");
        $stmtDet->execute([$receta['id_receta']]);
        $receta['detalles'] = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

        return $receta;
    }

    /**
     * Retorna el número de pedidos en curso que contienen este producto
     * (Estados 1: Recibido, 8: Pendiente Verificación, 2: Aceptado, 3: En preparación)
     */
    public function getPedidosEnCursoByProducto(int $idProducto): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(DISTINCT p.id_pedido)
            FROM pedido p
            JOIN detalle_pedido dp ON p.id_pedido = dp.id_pedido
            WHERE dp.id_producto = ? AND p.id_estado IN (1, 8, 2, 3)
        ");
        $stmt->execute([$idProducto]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Guarda o actualiza la receta de un producto con sus insumos y cantidades.
     * Valida que las cantidades sean > 0 y que no haya insumos repetidos.
     */
    public function guardarReceta(int $idProducto, string $nombreReceta, array $insumos): array
    {
        $nombreReceta = trim($nombreReceta);
        if (empty($nombreReceta)) {
            return ['success' => false, 'message' => 'El nombre de la receta es obligatorio.'];
        }

        // Validar insumos y cantidades
        $insumosVistos = [];
        $insumosValidos = [];

        foreach ($insumos as $item) {
            $idInsumo = (int)($item['id_insumo'] ?? 0);
            $cantidad = (float)($item['cantidad'] ?? 0);

            if ($idInsumo <= 0) continue;

            if ($cantidad <= 0) {
                return ['success' => false, 'message' => 'Todas las cantidades de insumos deben ser mayores a 0.'];
            }

            if (isset($insumosVistos[$idInsumo])) {
                return ['success' => false, 'message' => 'No se permiten insumos repetidos en la misma receta.'];
            }

            $insumosVistos[$idInsumo] = true;
            $insumosValidos[] = [
                'id_insumo' => $idInsumo,
                'cantidad' => $cantidad
            ];
        }

        try {
            $this->db->beginTransaction();

            // Buscar si ya existe receta para el producto
            $stmtR = $this->db->prepare("SELECT id_receta FROM receta WHERE id_producto = ? LIMIT 1");
            $stmtR->execute([$idProducto]);
            $idReceta = $stmtR->fetchColumn();

            if ($idReceta) {
                $idReceta = (int)$idReceta;
                $stmtUpd = $this->db->prepare("UPDATE receta SET nombre = ?, estado = 'ACTIVA' WHERE id_receta = ?");
                $stmtUpd->execute([$nombreReceta, $idReceta]);

                // Eliminar detalles previos
                $this->db->prepare("DELETE FROM receta_detalle WHERE id_receta = ?")->execute([$idReceta]);
            } else {
                $stmtIns = $this->db->prepare("INSERT INTO receta (id_producto, nombre, estado) VALUES (?, ?, 'ACTIVA')");
                $stmtIns->execute([$idProducto, $nombreReceta]);
                $idReceta = (int)$this->db->lastInsertId();
            }

            // Insertar nuevos detalles
            if (!empty($insumosValidos)) {
                $stmtDet = $this->db->prepare("INSERT INTO receta_detalle (id_receta, id_insumo, cantidad) VALUES (?, ?, ?)");
                foreach ($insumosValidos as $iv) {
                    $stmtDet->execute([$idReceta, $iv['id_insumo'], $iv['cantidad']]);
                }
            } else {
                // Si se guardó sin insumos, desactivar receta para que no descuente
                $this->db->prepare("UPDATE receta SET estado = 'INACTIVA' WHERE id_receta = ?")->execute([$idReceta]);
            }

            $pedidosEnCurso = $this->getPedidosEnCursoByProducto($idProducto);

            $this->db->commit();
            return [
                'success' => true,
                'message' => 'Receta guardada exitosamente.',
                'id_receta' => $idReceta,
                'pedidos_en_curso' => $pedidosEnCurso
            ];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => 'Error al guardar la receta: ' . $e->getMessage()];
        }
    }

    /**
     * Obtiene alertas de stock bajo umbral mínimo para el Dashboard
     */
    public function getAlertasStockMinimo(): array
    {
        $query = "
            SELECT i.id_insumo, i.nombre, i.unidad_medida, i.stock_minimo,
                   COALESCE(inv.cantidad, 0.000) as stock_actual
            FROM insumo i
            LEFT JOIN inventario_insumo inv ON i.id_insumo = inv.id_insumo
            WHERE i.estado = 'ACTIVO' 
              AND COALESCE(inv.cantidad, 0.0) <= i.stock_minimo
            ORDER BY (COALESCE(inv.cantidad, 0.0) / NULLIF(i.stock_minimo, 0)) ASC
        ";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

