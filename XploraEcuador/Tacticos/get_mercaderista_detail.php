<?php
require_once __DIR__ . '/conexion/DataSource.php';
use Phppot\DataSource;

$database = new DataSource();
$conn = $database->getConnection();

header("Content-Type: application/json");

try {
    $mes = isset($_GET['mes']) ? intval($_GET['mes']) : date('n');
    $anio = isset($_GET['anio']) ? intval($_GET['anio']) : date('Y');
    $modo = isset($_GET['es_adicional']) ? strtolower(trim($_GET['es_adicional'])) : 'false';
    $es_adicional = ($modo === 'true');
    $es_todos = ($modo === 'todos');
    $jefatura = $_GET['jefatura'] ?? '';
    $ejecutivo = $_GET['ejecutivo'] ?? '';

    if (!$jefatura || !$ejecutivo) {
        throw new Exception("Faltan parámetros requeridos.");
    }

    // Criterios de tipo, alineados con get_avances.php (tabla de ejecutivo)
    if ($es_adicional) {
        $cond_observaciones = "LIKE '%Adicional%'";
        $cond_tipo_tactico  = "= 'Adicional'";
    } elseif ($es_todos) {
        $cond_observaciones = "LIKE '%'";
        $cond_tipo_tactico  = "IS NOT NULL";
    } else {
        $cond_observaciones = "NOT LIKE '%Adicional%'";
        $cond_tipo_tactico  = "!= 'Adicional'";
    }

    // Mismo origen y criterio que la fila de ejecutivo en get_avances.php:
    // se parte de repositorio_distributivo filtrado por jefatura/ejecutivo, y el
    // armado sale directo de validacion_tacticos (por mes_reporte/anio_reporte/
    // tipo_tactico, sin join a onpacks ni filtro por fecha_creacion),
    // pre-agrupado por gestor para no repetirlo al hacer el JOIN.
    $sql = "
        SELECT
            rd.mercaderista,
            GROUP_CONCAT(DISTINCT rd.distribuidor SEPARATOR ' | ') AS distribuidor,
            SUM(rd.cantidad_asignada)         AS cantidad_distribuida,
            MAX(COALESCE(va.total_armado, 0)) AS cantidad_armada
        FROM repositorio_distributivo rd
        LEFT JOIN (
            SELECT gestor, SUM(cantidad_armada) AS total_armado
            FROM validacion_tacticos
            WHERE mes_reporte = ?
              AND anio_reporte = ?
              AND tipo_tactico $cond_tipo_tactico
            GROUP BY gestor
        ) va ON va.gestor = rd.mercaderista
        WHERE MONTH(rd.fecha_asignacion) = ?
          AND YEAR(rd.fecha_asignacion) = ?
          AND rd.jefatura = ?
          AND rd.ejecutivo = ?
          AND rd.mercaderista NOT IN ('LUCKY UIO', 'LUCKY GYE', 'PRUEBA GYE')
          AND (rd.observaciones IS NULL OR rd.observaciones $cond_observaciones)
        GROUP BY rd.mercaderista
        ORDER BY rd.mercaderista
    ";

    $types  = 'iiiiss';
    $params = [$mes, $anio, $mes, $anio, $jefatura, $ejecutivo];

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $response = [];
    while ($row = $result->fetch_assoc()) {
        $faltantes = max($row['cantidad_distribuida'] - $row['cantidad_armada'], 0);
        $avance = ($row['cantidad_distribuida'] > 0)
            ? round(($row['cantidad_armada'] / $row['cantidad_distribuida']) * 100, 2)
            : 0;

        $response[] = [
            "mercaderista" => $row['mercaderista'],
            "distribuidor" => $row['distribuidor'],
            "cantidad_distribuida" => intval($row['cantidad_distribuida']),
            "cantidad_armada" => intval($row['cantidad_armada']),
            "faltantes" => $faltantes,
            "avance" => $avance
        ];
    }

    echo json_encode(["success" => true, "data" => $response], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
