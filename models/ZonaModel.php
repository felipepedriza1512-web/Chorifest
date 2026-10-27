<?php
/**
 * Modelo para gestión de Zonas de Cobertura y Geolocalización
 */
class ZonaModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Obtener zonas de envío (todas o solo activas)
     */
    public function getZonas(bool $onlyActivas = false): array
    {
        $sql = "SELECT * FROM zona_envio";
        if ($onlyActivas) {
            $sql .= " WHERE estado = 'ACTIVA'";
        }
        $sql .= " ORDER BY nombre ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener zona por ID
     */
    public function getZonaById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM zona_envio WHERE id_zona = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Crear una nueva zona con polígono GeoJSON
     */
    public function createZona(array $data, int $adminId): int
    {
        $costoDomicilio = 0.00; // Por política de Domicilio Gratis, las nuevas zonas se crean con costo 0
        $stmt = $this->db->prepare("
            INSERT INTO zona_envio (nombre, costo_domicilio, monto_minimo, tiempo_estimado_min, estado, poligono_geojson)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            trim($data['nombre']),
            $costoDomicilio,
            (float)($data['monto_minimo'] ?? 0),
            (int)($data['tiempo_estimado_min'] ?? 30),
            ($data['estado'] ?? 'ACTIVA') === 'INACTIVA' ? 'INACTIVA' : 'ACTIVA',
            $data['poligono_geojson'] ?: null
        ]);
        $newId = (int)$this->db->lastInsertId();

        // Registrar auditoría
        $stmtAudit = $this->db->prepare("
            INSERT INTO auditoria (id_usuario, accion, entidad, id_registro, descripcion, fecha)
            VALUES (?, 'CREAR', 'zona_envio', ?, ?, NOW())
        ");
        $stmtAudit->execute([
            $adminId,
            $newId,
            "Zona '{$data['nombre']}' creada con monto mínimo $" . number_format((float)$data['monto_minimo'], 2)
        ]);

        return $newId;
    }

    /**
     * Actualizar zona existente (NO modifica costo_domicilio para conservar datos históricos)
     */
    public function updateZona(int $id, array $data, int $adminId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE zona_envio
            SET nombre = ?, monto_minimo = ?, tiempo_estimado_min = ?, estado = ?, poligono_geojson = ?
            WHERE id_zona = ?
        ");
        $success = $stmt->execute([
            trim($data['nombre']),
            (float)($data['monto_minimo'] ?? 0),
            (int)($data['tiempo_estimado_min'] ?? 30),
            ($data['estado'] ?? 'ACTIVA') === 'INACTIVA' ? 'INACTIVA' : 'ACTIVA',
            $data['poligono_geojson'] ?: null,
            $id
        ]);

        // Registrar auditoría
        $stmtAudit = $this->db->prepare("
            INSERT INTO auditoria (id_usuario, accion, entidad, id_registro, descripcion, fecha)
            VALUES (?, 'ACTUALIZAR', 'zona_envio', ?, ?, NOW())
        ");
        $stmtAudit->execute([
            $adminId,
            $id,
            "Zona #$id ('{$data['nombre']}') actualizada. Mínimo: $" . number_format((float)$data['monto_minimo'], 2) . ", Estado: {$data['estado']}"
        ]);

        return $success;
    }

    /**
     * Algoritmo Ray-Casting para determinar si un punto (lat, lng) cae dentro de un anillo de polígono.
     * En GeoJSON las coordenadas se representan como [lng, lat].
     */
    public static function pointInRing(float $lat, float $lng, array $ring): bool
    {
        $inside = false;
        $n = count($ring);
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $xi = (float)$ring[$i][0]; // Lng
            $yi = (float)$ring[$i][1]; // Lat
            $xj = (float)$ring[$j][0]; // Lng
            $yj = (float)$ring[$j][1]; // Lat

            $intersect = (($yi > $lat) !== ($yj > $lat))
                && ($lng < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi);
            if ($intersect) {
                $inside = !$inside;
            }
        }
        return $inside;
    }

    /**
     * Valida si un punto cae dentro de un polígono GeoJSON (Polygon o MultiPolygon)
     */
    public static function pointInGeoJson(float $lat, float $lng, $geojson): bool
    {
        if (is_string($geojson)) {
            $geojson = json_decode($geojson, true);
        }
        if (!is_array($geojson)) {
            return false;
        }

        // Si es FeatureCollection
        if (($geojson['type'] ?? '') === 'FeatureCollection' && !empty($geojson['features'])) {
            foreach ($geojson['features'] as $f) {
                if (self::pointInGeoJson($lat, $lng, $f)) {
                    return true;
                }
            }
            return false;
        }

        // Si es Feature
        if (($geojson['type'] ?? '') === 'Feature' && !empty($geojson['geometry'])) {
            return self::pointInGeoJson($lat, $lng, $geojson['geometry']);
        }

        $type = $geojson['type'] ?? '';
        $coords = $geojson['coordinates'] ?? [];

        if ($type === 'Polygon' && !empty($coords)) {
            // Anillo exterior es coords[0]
            if (self::pointInRing($lat, $lng, $coords[0])) {
                // Verificar que no caiga en agujeros interiores (anillos 1+)
                for ($h = 1; $h < count($coords); $h++) {
                    if (self::pointInRing($lat, $lng, $coords[$h])) {
                        return false; // Está dentro de un agujero
                    }
                }
                return true;
            }
            return false;
        } elseif ($type === 'MultiPolygon' && !empty($coords)) {
            foreach ($coords as $poly) {
                if (!empty($poly) && self::pointInRing($lat, $lng, $poly[0])) {
                    $inHole = false;
                    for ($h = 1; $h < count($poly); $h++) {
                        if (self::pointInRing($lat, $lng, $poly[$h])) {
                            $inHole = true;
                            break;
                        }
                    }
                    if (!$inHole) return true;
                }
            }
            return false;
        }

        return false;
    }

    /**
     * Encuentra la mejor zona para una ubicación dada (lat, lng).
     * REGLA DE SOLAPAMIENTO (DOMICILIO GRATIS):
     * Si varias zonas activas contienen el punto:
     * 1. Gana la zona con MENOR monto mínimo (monto_minimo ASC).
     * 2. Si empatan en monto mínimo, la zona con MENOR ID (id_zona ASC).
     */
    public function findMatchingZone(float $lat, float $lng): ?array
    {
        $zonas = $this->getZonas(true); // Solo activas
        $matching = [];

        foreach ($zonas as $z) {
            if (empty($z['poligono_geojson'])) {
                continue;
            }
            if (self::pointInGeoJson($lat, $lng, $z['poligono_geojson'])) {
                $matching[] = $z;
            }
        }

        if (empty($matching)) {
            return null;
        }

        // Ordenar según regla de desempate
        usort($matching, function ($a, $b) {
            // 1. Menor monto mínimo
            $diffMin = (float)$a['monto_minimo'] <=> (float)$b['monto_minimo'];
            if ($diffMin !== 0) return $diffMin;

            // 2. Menor ID de zona
            return (int)$a['id_zona'] <=> (int)$b['id_zona'];
        });

        return $matching[0];
    }

    /**
     * Alias de findMatchingZone
     */
    public function determinarZonaPorPunto(float $lat, float $lng): ?array
    {
        return $this->findMatchingZone($lat, $lng);
    }
}
