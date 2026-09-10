<?php
require_once __DIR__ . '/conexion/DataSource.php';
use Phppot\DataSource;

$database = new DataSource();
header('Content-Type: application/json');

ob_clean();

try {
    $modo = isset($_GET['es_adicional']) ? strtolower(trim($_GET['es_adicional'])) : 'false';
    $es_adicional = ($modo === 'true');
    $es_todos = ($modo === 'todos');
    $mes = isset($_GET['mes']) ? intval($_GET['mes']) : date('n');
    $anio = isset($_GET['anio']) ? intval($_GET['anio']) : date('Y');

    if ($es_adicional) {
        $condicion_observaciones = "LIKE '%Adicional%'";
        $condicion_tipo_tactico  = "= 'Adicional'";
    } elseif ($es_todos) {
        // TODOS: no filtra por tipo (trae normales + adicionales)
        $condicion_observaciones = "LIKE '%'";
        $condicion_tipo_tactico  = "IS NOT NULL";
    } else {
        $condicion_observaciones = "NOT LIKE '%Adicional%'";
        $condicion_tipo_tactico  = "!= 'Adicional'";
    }

    function executeQuery($db, $query) {
        $result = $db->select($query);
        if ($result === false) {
            throw new Exception("Error ejecutando consulta.");
        }
        return $result;
    }

    $query_regional = "
        SELECT
            a.region,
            a.cantidad_recursos,
            a.cantidad_distribuida,
            COALESCE(b.total_armada, 0) AS cantidad_armada,
            (a.cantidad_distribuida - COALESCE(b.total_armada, 0)) AS faltantes,
            CASE WHEN a.cantidad_distribuida = 0 THEN 0
                 ELSE ROUND((COALESCE(b.total_armada, 0) / a.cantidad_distribuida) * 100, 2)
            END AS avance
        FROM (
            SELECT rd.regional AS region,
                   COUNT(DISTINCT rd.mercaderista) AS cantidad_recursos,
                   SUM(rd.cantidad_asignada) AS cantidad_distribuida
            FROM repositorio_distributivo rd
            WHERE MONTH(rd.fecha_asignacion) = $mes
              AND YEAR(rd.fecha_asignacion) = $anio
              AND (rd.observaciones IS NULL OR rd.observaciones $condicion_observaciones)
            GROUP BY rd.regional
        ) a
        LEFT JOIN (
            SELECT rd.regional AS region,
                   SUM(vt_sub.cantidad_armada) AS total_armada
            FROM (
                SELECT gestor, SUM(cantidad_armada) AS cantidad_armada
                FROM validacion_tacticos
                WHERE mes_reporte = $mes AND anio_reporte = $anio AND tipo_tactico $condicion_tipo_tactico
                GROUP BY gestor
            ) vt_sub
            JOIN (
                SELECT DISTINCT regional, mercaderista
                FROM repositorio_distributivo
                WHERE MONTH(fecha_asignacion) = $mes AND YEAR(fecha_asignacion) = $anio
                  AND (observaciones IS NULL OR observaciones $condicion_observaciones)
            ) rd ON vt_sub.gestor = rd.mercaderista
            GROUP BY rd.regional
        ) b ON a.region = b.region;
      ";

    $query_ejecutivo = "
        
      SELECT 
        rd.jefatura,
        rd.ejecutivo,
        COUNT(DISTINCT rd.mercaderista) AS cantidad_recursos,
        SUM(DISTINCT IFNULL(vt.total_armado,0)) AS cantidad_armada, -- <= SUM DISTINCT para no duplicar!
        SUM(rd.cantidad_asignada) AS cantidad_distribuida
      FROM 
        repositorio_distributivo rd
      LEFT JOIN (
        SELECT 
          gestor,
          SUM(cantidad_armada) AS total_armado
        FROM 
          validacion_tacticos
        WHERE 
          mes_reporte = $mes
          AND anio_reporte = $anio
          AND tipo_tactico $condicion_tipo_tactico
        GROUP BY gestor
      ) vt ON vt.gestor = rd.mercaderista
      WHERE 
        MONTH(rd.fecha_asignacion) = $mes
        AND YEAR(rd.fecha_asignacion) = $anio
        AND (rd.observaciones IS NULL OR rd.observaciones $condicion_observaciones)
        AND rd.mercaderista NOT IN ('LUCKY UIO', 'LUCKY GYE', 'PRUEBA GYE')
      GROUP BY 
        rd.jefatura, rd.ejecutivo;

          ";


    // Se agrupa SOLO por mercaderista: un mercaderista puede tener filas de
    // distributivo bajo mas de un supervisor y agrupar por (supervisor, mercaderista)
    // repetia su total en cada fila (doble conteo). El armado se toma de un
    // subquery pre-agrupado por gestor (LEFT JOIN) para no repetirlo, y directo
    // de validacion_tacticos -sin join a onpacks- para igualar el criterio de
    // regional y ejecutivo. El supervisor mostrado es MIN() (determinista).
    $query_mercaderista = "
        SELECT
            MIN(rd.supervisor) AS supervisor,
            rd.mercaderista,
            GROUP_CONCAT(DISTINCT rd.distribuidor SEPARATOR ' | ') AS distribuidor,
            SUM(rd.cantidad_asignada) AS cantidad_distribuida,
            MAX(COALESCE(va.total_armado, 0)) AS cantidad_armada
        FROM repositorio_distributivo rd
        LEFT JOIN (
            SELECT gestor, SUM(cantidad_armada) AS total_armado
            FROM validacion_tacticos
            WHERE mes_reporte = $mes
              AND anio_reporte = $anio
              AND tipo_tactico $condicion_tipo_tactico
            GROUP BY gestor
        ) va ON va.gestor = rd.mercaderista
        WHERE YEAR(rd.fecha_asignacion) = $anio
          AND MONTH(rd.fecha_asignacion) = $mes
          AND rd.mercaderista NOT IN ('LUCKY UIO', 'LUCKY GYE', 'PRUEBA GYE')
          AND (rd.observaciones IS NULL OR rd.observaciones $condicion_observaciones)
        GROUP BY rd.mercaderista
        ORDER BY MIN(rd.supervisor), rd.mercaderista;
    ";

    $data_regional = executeQuery($database, $query_regional);
    $data_ejecutivo = executeQuery($database, $query_ejecutivo);
    $data_mercaderista = executeQuery($database, $query_mercaderista);

    echo json_encode([
        "success" => true,
        "data" => [
            "regional" => $data_regional,
            "ejecutivo" => $data_ejecutivo,
            "mercaderista" => $data_mercaderista
        ]
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage(),
        "line" => $e->getLine(),
        "file" => $e->getFile()
    ]);
}
