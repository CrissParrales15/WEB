<?php
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Guayaquil');

require_once '../Core/DataSource.php'; 
use Phppot\DataSource;

try {
    $db = new DataSource();
    
    $desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-m-01');
    $hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-d');
    
    $id_usuario = isset($_GET['id_usuario']) && $_GET['id_usuario'] !== 'all' ? $_GET['id_usuario'] : null;
    $region_filtro = isset($_GET['region']) && $_GET['region'] !== 'all' ? $_GET['region'] : null;

    $sql = "
        SELECT 
            r.id,
            r.id_pdv,
            u.id AS id_mercaderista,
            u.mercaderista,
            p.pos_name AS nombre_pdv,
            p.region AS zona,
            p.latitud,
            p.longitud,
            r.hora_inicio_visita,
            r.hora_fin_visita,
            p.tiempo_min_gestion,
            e.descripcion AS estado_desc,
            r.id_estado,
            TIMESTAMPDIFF(MINUTE, r.hora_inicio_visita, r.hora_fin_visita) AS tiempo_real_min,
            DATE(r.fecha_visita) AS fecha_real
        FROM rutero_pdv r
        LEFT JOIN repositorio_usuario u ON r.id_usuario = u.id
        LEFT JOIN repositorio_locales_dtt2 p ON r.id_pdv = p.id
        LEFT JOIN repositorio_estados e ON r.id_estado = e.id
        WHERE DATE(r.fecha_visita) BETWEEN ? AND ?
        AND r.habilitado = 1
        AND UPPER(u.user) NOT LIKE '%PRUEBA%'
        AND UPPER(u.mercaderista) NOT LIKE '%PRUEBA%'
    ";

    // Pasamos ambas fechas
    $params = [$desde, $hasta];
    $tipos_param = 'ss';


    if ($id_usuario) {
        $sql .= " AND r.id_usuario = ?";
        $params[] = $id_usuario;
        $tipos_param .= 'i'; // i de integer
    }
    
    // Filtro por Región
    if ($region_filtro) {
        $sql .= " AND UPPER(p.region) = UPPER(?)";
        $params[] = $region_filtro;
        $tipos_param .= 's';
    }


    $sql .= " ORDER BY DATE(r.fecha_visita) DESC, r.hora_inicio_visita DESC";

    $visitas = $db->select($sql, $tipos_param, $params);

    // --- PROCESAMIENTO DE DATOS ---
    $total_rutas = count($visitas);
    $total_visitados = 0;
    
    $detalle_tabla = [];
    $data_mapa = [];
    $lista_mercaderistas = []; 

    $rendimiento_macro = []; 

    foreach ($visitas as $row) {
        $es_visitado = ($row['id_estado'] == 3 || $row['id_estado'] == 6); 
        
        if ($es_visitado) {
            $total_visitados++;
        }

        if (!isset($lista_mercaderistas[$row['id_mercaderista']])) {
            $lista_mercaderistas[$row['id_mercaderista']] = $row['mercaderista'];
        }

        $tiempo_esperado_min = 0;
        if (!empty($row['tiempo_min_gestion'])) {
            $t = explode(':', $row['tiempo_min_gestion']);
            $tiempo_esperado_min = ($t[0] * 60) + $t[1];
        }

        $alerta_tiempo = ($row['tiempo_real_min'] !== null && $tiempo_esperado_min > 0) 
                         ? ($row['tiempo_real_min'] > $tiempo_esperado_min) : false;

        $status_final = $es_visitado ? ($alerta_tiempo ? 'Excedido' : 'OK') : 'No visitado';

        if ($id_usuario) {
            // VISTA MICRO: Llenamos la tabla con el detalle, AHORA INCLUYENDO LA FECHA
            $detalle_tabla[] = [
                'fecha_visita' => $row['fecha_real'], 
                'col1' => $row['nombre_pdv'], 
                'col2' => $row['zona'],
                'col3' => $row['hora_inicio_visita'] ?? '-',
                'col4' => $row['hora_fin_visita'] ?? '-',
                'col5' => ($row['tiempo_real_min'] ?? 0) . ' min',
                'estado' => $status_final
            ];
        } else {
            // VISTA MACRO: Agrupamos por mercaderista
            if (!isset($rendimiento_macro[$row['id_mercaderista']])) {
                $rendimiento_macro[$row['id_mercaderista']] = [
                    'nombre' => $row['mercaderista'],
                    'asignados' => 0,
                    'visitados' => 0
                ];
            }
            $rendimiento_macro[$row['id_mercaderista']]['asignados']++;
            if ($es_visitado) {
                $rendimiento_macro[$row['id_mercaderista']]['visitados']++;
            }
        }

        if ($row['latitud'] && $row['longitud']) {
            $data_mapa[] = [
                'id_pdv' => $row['id_pdv'],
                'fecha' => $row['fecha_real'], 
                'nombre' => $row['nombre_pdv'],
                'mercaderista' => $row['mercaderista'],
                'lat' => (float)$row['latitud'],
                'lng' => (float)$row['longitud'],
                'estado' => $status_final
            ];
        }
    }

      //ORDENADO MAYOR A MENOR POR EFECTIVIDAD --fix lady
    if (!$id_usuario) {
        $filas_macro = [];
 
        foreach ($rendimiento_macro as $id_merc => $data) {
            $porcentaje = $data['asignados'] > 0 ? round(($data['visitados'] / $data['asignados']) * 100) : 0;
            $estado_macro = 'OK';
            if ($porcentaje < 50) $estado_macro = 'No visitado';
            elseif ($porcentaje < 80) $estado_macro = 'Excedido';
 
            $filas_macro[] = [
                'id_mercaderista' => $id_merc,
                'col1' => $data['nombre'],
                'col2' => $data['asignados'] . ' Rutas',
                'col3' => $data['visitados'] . ' Visitados',
                'col4' => '-',
                'col5' => $porcentaje . '%',
                'estado' => $estado_macro,
                '_efectividad' => $porcentaje 
            ];
        }
 
        usort($filas_macro, function($a, $b) {
            return $b['_efectividad'] <=> $a['_efectividad'];
        });
 
        foreach ($filas_macro as $fila) {
            unset($fila['_efectividad']);
            $detalle_tabla[] = $fila;
        }
    }

    $porcentaje_cobertura = $total_rutas > 0 ? round(($total_visitados / $total_rutas) * 100) : 0;

    echo json_encode([
        "status" => "success",
        "tipo_vista" => $id_usuario ? "micro" : "macro",
        "kpi_resumen" => [
            "total_asignado" => $total_rutas,
            "total_visitado" => $total_visitados,
            "efectividad_porcentaje" => $porcentaje_cobertura
        ],
        "detalle_tabla" => $detalle_tabla,
        "mercaderistas_activos" => $lista_mercaderistas,
        "data_mapa" => $data_mapa
    ]);

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error al procesar el KPI",
        "detalle" => $e->getMessage()
    ]);
}