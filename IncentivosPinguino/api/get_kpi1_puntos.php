<?php
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Guayaquil');

require_once '../core/DataSource.php';
use Phppot\DataSource;

try {
    $db = new DataSource();
    
    if (!isset($_GET['id_usuario']) || !isset($_GET['fecha'])) {
        throw new \Exception("Faltan parámetros obligatorios.");
    }

    $id_usuario = $_GET['id_usuario'];
    $fecha_referencia = $_GET['fecha']; 


    if (isset($_GET['desde']) && isset($_GET['hasta'])) {
        $desde = $_GET['desde'];
        $hasta = $_GET['hasta'];
    } else {
        $desde = date('Y-m-01', strtotime($fecha_referencia));
        $hasta = date('Y-m-t', strtotime($fecha_referencia));
}

    $excluidos_kpi3 = [
        'GONZALEZ QUIMI GERALDINE ALEXANDRA',
        'ZARABIA MUNOZ EDWAR ISRAEL',
        'ALVAREZ RUIZ ALEX GIOVANNI',
        'JOSE ANDRES MUNOZ HERNANDEZ',
        'KATHERINE GABRIELA RODRIGUEZ GAMBOA',
        'VIVIANA LUCIA QUINTEROS ROBOLLEDO',
        'JANELLA LILIBETH ROCA PAZMINO',
        'YELITZA VALERIA QUIMIZ MENDOZA'
    ];

    $sql_usuario = "SELECT TRIM(UPPER(mercaderista)) AS mercaderista FROM repositorio_usuario WHERE id = ? LIMIT 1";
    $usuario = $db->select($sql_usuario, 'i', [$id_usuario]);
    $es_excluido = !empty($usuario)
        && in_array($usuario[0]['mercaderista'], $excluidos_kpi3, true);
    $puntos_por_dia = $es_excluido ? 25 : 20;
    $meta_puntos = $es_excluido ? 500 : 400;


    $sql = "
        SELECT 
            DATE(r.fecha_visita) as fecha_dia,
            COUNT(r.id) as total_asignados,
            SUM(CASE WHEN r.id_estado IN (3,6) THEN 1 ELSE 0 END) as total_visitados
        FROM rutero_pdv r
        JOIN repositorio_usuario u ON r.id_usuario = u.id
        LEFT JOIN repositorio_locales_dtt2 p ON r.id_pdv = p.id
        WHERE r.id_usuario = ? 
        /*AND DATE_FORMAT(r.fecha_visita, '%Y-%m') = DATE_FORMAT(?, '%Y-%m')*/
        AND DATE(r.fecha_visita) BETWEEN ? AND ?
        AND r.habilitado = 1
        AND u.status = 1
        AND UPPER(u.mercaderista) NOT LIKE '%PRUEBA%'
        GROUP BY DATE(r.fecha_visita)
        ORDER BY fecha_dia ASC
    ";


    $dias_rutero = $db->select($sql, 'iss', [$id_usuario, $desde, $hasta]);

    $puntos_acumulados = 0;
    $desglose_diario = [];

    foreach ($dias_rutero as $dia) {
        $porcentaje_dia = $dia['total_asignados'] > 0 
            ? round(($dia['total_visitados'] / $dia['total_asignados']) * 100) 
            : 0;
        

        $puntos_ganados = 0;
        if ($porcentaje_dia >= 87) {
            $puntos_ganados = $puntos_por_dia;
            $puntos_acumulados += $puntos_por_dia;
        }

        $desglose_diario[] = [
            'fecha' => $dia['fecha_dia'],
            'asignados' => $dia['total_asignados'],
            'visitados' => $dia['total_visitados'],
            'efectividad' => $porcentaje_dia,
            'puntos' => $puntos_ganados
        ];
    }

    $puntos_acumulados = min($meta_puntos, $puntos_acumulados);
    $porcentaje_meta = round(($puntos_acumulados / $meta_puntos) * 100);

    if ($porcentaje_meta > 100) $porcentaje_meta = 100; 

    echo json_encode([
        "status" => "success",
        "mes_consulta" => date('Y-m', strtotime($fecha_referencia)),
        "resumen_mensual" => [
            "puntos_actuales" => $puntos_acumulados,
            "meta_puntos" => $meta_puntos,
            "porcentaje_meta" => $porcentaje_meta
        ],
        "desglose" => $desglose_diario
    ]);

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error al calcular puntos mensuales",
        "detalle" => $e->getMessage()
    ]);
}