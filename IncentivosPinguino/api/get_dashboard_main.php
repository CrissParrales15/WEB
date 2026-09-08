<?php
// Mantenemos la seguridad de no imprimir errores de PHP en el JSON
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Guayaquil');

require_once '../core/DataSource.php';
$db = new \Phppot\DataSource();

try {
    // NUEVA LÓGICA DE FECHAS (RANGO)
    $desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-m-01');
    $hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-d');
    $filtro_region = isset($_GET['region']) && $_GET['region'] !== 'all' ? $_GET['region'] : null;


    // =================================================================
    // CALCULAR DÍAS POR SEMANA DENTRO DEL RANGO (para gráfica incremental)
    // =================================================================
    $fecha_inicio = new DateTime($desde);
    $fecha_fin = new DateTime($hasta);
    $dias_por_semana = array_fill(0, 4, 0);
    $total_dias = 0;
    $current = clone $fecha_inicio;
    while ($current <= $fecha_fin) {
        $dia = (int)$current->format('d');
        $semana = ceil($dia / 7) - 1; // 0-3
        $dias_por_semana[$semana]++;
        $total_dias++;
        $current->modify('+1 day');
    }
    if ($total_dias == 0) { $total_dias = 1; $dias_por_semana[0] = 1; }
    
    $filtro_region = isset($_GET['region']) && $_GET['region'] !== 'all' ? $_GET['region'] : null;
    $filtro_merca = isset($_GET['mercaderista']) && $_GET['mercaderista'] !== 'all' ? $_GET['mercaderista'] : null;

    // DEFINIMOS LOS USUARIOS EXCLUIDOS DE KPI 3
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
    $sql_in_excluidos = "'" . implode("','", $excluidos_kpi3) . "'";

    // Lista de excluidos para KPI2 (nombres como aparecen en insert_evidencias.usuario)
    $excluidos_kpi2 = [
        'GERALDINE GONZALEZ',
        'EDWAR ZARABIA',
        'ALEX ALVAREZ',
        'ANDRES MUNOZ',
        'KATHERINE RODRIGUEZ',
        'VIVIANA QUINTEROS',
        'JANELLA ROCA',
        'YELITZA QUIMIZ'
    ];
    $sql_in_excluidos_kpi2 = "'" . implode("','", $excluidos_kpi2) . "'";

    $filtro_extra = "";
    
    // Ahora pasamos dos parámetros de fecha a las consultas (desde y hasta)
    $params = [$desde, $hasta];
    $tipos = 'ss';

    if ($filtro_region) {
        $filtro_extra .= " AND UPPER(p.region) = UPPER(?) ";
        $params[] = $filtro_region;
        $tipos .= 's';
    }
    if ($filtro_merca) {
        $filtro_extra .= " AND TRIM(UPPER(u.mercaderista)) = UPPER(?) ";
        $params[] = $filtro_merca;
        $tipos .= 's';
    }

    $condiciones_base = "
        AND u.status = 1 
        AND UPPER(u.mercaderista) NOT LIKE '%PRUEBA%'
    " . $filtro_extra;

    // Regla estricta KPI 3 (Filtra a las personas excluidas directamente en SQL)
    $condicion_kpi3 = " 
        AND (UPPER(p.atendido) != 'SAUL GILER' OR p.atendido IS NULL) 
        AND UPPER(p.subchannel) = 'TIENDAS INDUSTRIALES ASOCIADAS' 
        AND TRIM(UPPER(u.mercaderista)) NOT IN ($sql_in_excluidos)
    ";

    // =================================================================
    // 1. KPI 1: RUTERO (Filtro BETWEEN)
    // =================================================================
    $sql_kpi1 = "
        SELECT 
            TRIM(UPPER(u.mercaderista)) AS nombre,
            DATE(r.fecha_visita) AS dia,
            COUNT(r.id) AS rutas_asignadas,
            SUM(CASE WHEN r.id_estado IN (3,6) THEN 1 ELSE 0 END) AS rutas_visitadas
        FROM rutero_pdv r
        JOIN repositorio_usuario u ON r.id_usuario = u.id
        LEFT JOIN repositorio_locales_dtt2 p ON r.id_pdv = p.id
        WHERE DATE(r.fecha_visita) BETWEEN ? AND ? AND r.habilitado = 1
        $condiciones_base
        GROUP BY TRIM(UPPER(u.mercaderista)), DATE(r.fecha_visita)
    ";
    $data_kpi1 = $db->select($sql_kpi1, $tipos, $params) ?: [];


    $sql_kpi2 = "
    SELECT 
        TRIM(UPPER(u.mercaderista)) AS nombre,
        TRIM(UPPER(e.usuario)) AS usuario_evidencia,
        COUNT(e.id) AS total_evidencias,
        SUM(CASE WHEN e.validado = 1 THEN 1 ELSE 0 END) AS validadas_evidencias
    FROM insert_evidencias e
    JOIN repositorio_locales_dtt2 p ON e.codigo = p.pos_id
    JOIN repositorio_usuario u ON (TRIM(UPPER(e.usuario)) = TRIM(UPPER(u.user)) OR TRIM(UPPER(e.usuario)) = TRIM(UPPER(u.mercaderista)))
    WHERE STR_TO_DATE(e.fecha, '%d/%m/%Y') BETWEEN ? AND ?
      AND UPPER(e.usuario) NOT LIKE '%PRUEBA%'
      AND u.status = 1 
      AND UPPER(u.mercaderista) NOT LIKE '%PRUEBA%'
";

$params_kpi2 = [$desde, $hasta];
$tipos_kpi2 = 'ss';

// Agregar filtro de región (igual que en la sección KPI2)
if ($filtro_region) {
    $sql_kpi2 .= " AND UPPER(p.region) = UPPER(?) ";
    $params_kpi2[] = $filtro_region;
    $tipos_kpi2 .= 's';
}
// Agregar filtro de mercaderista (igual que en la sección KPI2)
if ($filtro_merca) {
    $sql_kpi2 .= " AND TRIM(UPPER(u.mercaderista)) = UPPER(?) ";
    $params_kpi2[] = $filtro_merca;
    $tipos_kpi2 .= 's';
}

$sql_kpi2 .= " GROUP BY TRIM(UPPER(u.mercaderista)), TRIM(UPPER(e.usuario)), e.codigo, e.foto_antes, e.foto_despues";

$data_kpi2_raw = $db->select($sql_kpi2, $tipos_kpi2, $params_kpi2) ?: [];


$data_kpi2 = [];
foreach ($data_kpi2_raw as $row) {
    $nombre = $row['nombre'];
    if (!isset($data_kpi2[$nombre])) {
        $data_kpi2[$nombre] = [
            'nombre' => $nombre,
            'usuario_evidencia' => $row['usuario_evidencia'],
            'total_evidencias' => 0,
            'validadas_evidencias' => 0
        ];
    }
    $data_kpi2[$nombre]['total_evidencias']++;
    if ($row['validadas_evidencias'] > 0) {
        $data_kpi2[$nombre]['validadas_evidencias']++;
    }
}
$data_kpi2 = array_values($data_kpi2);
    // =================================================================
    // 3. KPI 3: ÓRDENES OC (NUEVO CÁLCULO CON META MENSUAL)
    // =================================================================

    // 3.1 Obtener la meta mensual para cada usuario desde repositorio_metas_incentivos
    $sql_metas = "
        SELECT 
            TRIM(UPPER(u.mercaderista)) AS nombre,
            u.id AS id_usuario,
            m.meta AS meta_mensual
        FROM repositorio_metas_incentivos m
        JOIN repositorio_usuario u ON TRIM(UPPER(u.mercaderista)) = TRIM(UPPER(m.usuario))
        WHERE m.fecha_inicio <= ? AND m.fecha_fin >= ?
        AND u.status = 1
        AND UPPER(u.mercaderista) NOT LIKE '%PRUEBA%'
        AND TRIM(UPPER(u.mercaderista)) NOT IN ($sql_in_excluidos)
    ";

    $params_metas = [$desde, $hasta];
    $tipos_metas = 'ss';

    if ($filtro_merca) {
        $sql_metas .= " AND TRIM(UPPER(u.mercaderista)) = UPPER(?) ";
        $params_metas[] = $filtro_merca;
        $tipos_metas .= 's';
    }

    $metas_usuario = $db->select($sql_metas, $tipos_metas, $params_metas) ?: [];


    $sql_logradas = "
    SELECT 
        TRIM(UPPER(u.mercaderista)) AS nombre,
        COUNT(o.id) AS total_logradas
    FROM insert_orden_pedido o
    JOIN repositorio_locales_dtt2 p ON o.codigo_pdv = p.pos_id
    JOIN repositorio_usuario u ON (TRIM(UPPER(o.usuario)) = TRIM(UPPER(u.user)) OR TRIM(UPPER(o.usuario)) = TRIM(UPPER(u.mercaderista)))
    WHERE DATE(o.fecha_servidor) BETWEEN ? AND ?
      AND o.existe_orden = 'SI'
      AND u.status = 1
      AND UPPER(u.mercaderista) NOT LIKE '%PRUEBA%'
      AND TRIM(UPPER(u.mercaderista)) NOT IN ($sql_in_excluidos)
      AND (UPPER(p.atendido) != 'SAUL GILER' OR p.atendido IS NULL)
      AND UPPER(p.subchannel) = 'TIENDAS INDUSTRIALES ASOCIADAS'
";

$params_logradas = [$desde, $hasta];
$tipos_logradas = 'ss';

if ($filtro_region) {
    $sql_logradas .= " AND UPPER(p.region) = UPPER(?) ";
    $params_logradas[] = $filtro_region;
    $tipos_logradas .= 's';
}
if ($filtro_merca) {
    $sql_logradas .= " AND TRIM(UPPER(u.mercaderista)) = UPPER(?) ";
    $params_logradas[] = $filtro_merca;
    $tipos_logradas .= 's';
}
$sql_logradas .= " GROUP BY TRIM(UPPER(u.mercaderista))";


    $logradas = $db->select($sql_logradas, $tipos_logradas, $params_logradas) ?: [];

    // 3.3 Cruce de datos para calcular puntos KPI3
    $kpi3_puntos = [];
    foreach ($metas_usuario as $meta_row) {
        $nombre = $meta_row['nombre'];
        $meta_mensual = $meta_row['meta_mensual'];
        $logradas_total = 0;
        foreach ($logradas as $log) {
            if ($log['nombre'] === $nombre) {
                $logradas_total = $log['total_logradas'];
                break;
            }
        }
        // Calcular puntos: (logradas / meta) * 300, redondeado, máximo 300
        $puntos = ($meta_mensual > 0) ? round(($logradas_total / $meta_mensual) * 300) : 0;
        $kpi3_puntos[$nombre] = min(300, $puntos);
    }

    // =================================================================
    // 4. CRUZAR DATOS Y ASIGNAR METAS DINÁMICAS
    // =================================================================
    $ranking = [];
    
    $initUser = function($nombre) use (&$ranking, $excluidos_kpi3) {
        if (!isset($ranking[$nombre])) {
            $es_excluido = in_array(TRIM(strtoupper($nombre)), $excluidos_kpi3);
            
            $ranking[$nombre] = [
                'nombre' => $nombre, 'iniciales' => substr($nombre, 0, 2), 
                'meta_locales' => 0, 'estado' => '',
                
                'max_kpi1' => $es_excluido ? 500 : 400,
                'max_kpi2' => $es_excluido ? 500 : 300,
                'max_kpi3' => $es_excluido ? 0 : 300,
                'pts_diarios_kpi1' => $es_excluido ? 25 : 20, 

                'kpi1_pts_w1' => 0, 'kpi1_pts_w2' => 0, 'kpi1_pts_w3' => 0, 'kpi1_pts_w4' => 0,
                'kpi2_pdv_w1' => 0, 'kpi2_pdv_w2' => 0, 'kpi2_pdv_w3' => 0, 'kpi2_pdv_w4' => 0,
                'kpi3_pts_w1' => 0, 'kpi3_pts_w2' => 0, 'kpi3_pts_w3' => 0, 'kpi3_pts_w4' => 0
            ];
        }
    };

    // Inicializar usuarios que aparecen en KPI1, KPI2 y en KPI3 (metas)
    foreach ($data_kpi1 as $row) {
        $initUser($row['nombre']);
    }
    foreach ($data_kpi2 as $row) {
        $initUser($row['nombre']);
    }
    foreach ($metas_usuario as $meta_row) {
        $initUser($meta_row['nombre']);
    }
    // También incluir usuarios de logradas que no estén en metas (por si acaso)
    foreach ($logradas as $log) {
        $initUser($log['nombre']);
    }

    // Procesar KPI 1
    foreach ($data_kpi1 as $row) {
        $nombre = $row['nombre'];
        $ranking[$nombre]['meta_locales'] += $row['rutas_asignadas'];

        $dia_mes = (int)date('d', strtotime($row['dia']));
        $pct_dia = $row['rutas_asignadas'] > 0 ? ($row['rutas_visitadas'] / $row['rutas_asignadas']) * 100 : 0;
        
        if ($pct_dia >= 87) {
            $pts = $ranking[$nombre]['pts_diarios_kpi1']; 
            if ($dia_mes <= 7)  $ranking[$nombre]['kpi1_pts_w1'] += $pts;
            if ($dia_mes <= 14) $ranking[$nombre]['kpi1_pts_w2'] += $pts;
            if ($dia_mes <= 21) $ranking[$nombre]['kpi1_pts_w3'] += $pts;
            $ranking[$nombre]['kpi1_pts_w4'] += $pts; 
        }
    }

    // Asignar puntos KPI3 (proporcionales) a la semana 4 (y luego se copiará a kpi3_pts)
    foreach ($kpi3_puntos as $nombre => $puntos) {
        if (isset($ranking[$nombre])) {
            $ranking[$nombre]['kpi3_pts_w1'] = $puntos;
            $ranking[$nombre]['kpi3_pts_w2'] = $puntos;
            $ranking[$nombre]['kpi3_pts_w3'] = $puntos;
            $ranking[$nombre]['kpi3_pts_w4'] = $puntos;
        }
    }

    // Topes máximos dinámicos
    foreach ($ranking as &$user) {
        $user['kpi1_pts_w1'] = min($user['max_kpi1'], $user['kpi1_pts_w1']);
        $user['kpi1_pts_w2'] = min($user['max_kpi1'], $user['kpi1_pts_w2']);
        $user['kpi1_pts_w3'] = min($user['max_kpi1'], $user['kpi1_pts_w3']);
        $user['kpi1_pts_w4'] = min($user['max_kpi1'], $user['kpi1_pts_w4']);
        $user['kpi1_pts'] = $user['kpi1_pts_w4']; 

        $user['kpi3_pts_w1'] = min($user['max_kpi3'], $user['kpi3_pts_w1']);
        $user['kpi3_pts_w2'] = min($user['max_kpi3'], $user['kpi3_pts_w2']);
        $user['kpi3_pts_w3'] = min($user['max_kpi3'], $user['kpi3_pts_w3']);
        $user['kpi3_pts_w4'] = min($user['max_kpi3'], $user['kpi3_pts_w4']);
        $user['kpi3_pts'] = $user['kpi3_pts_w4']; 
    }

    // Procesar KPI 2 (nuevo cálculo con totales de evidencias)
    foreach ($data_kpi2 as $row) {
        $nombre = $row['nombre'];
        $usuario_evidencia = $row['usuario_evidencia'];
        $total_evidencias = $row['total_evidencias'];
        $validadas_evidencias = $row['validadas_evidencias'];
        
        $initUser($nombre);
        
        // Determinar si es excluido para KPI2
        $es_excluido_kpi2 = in_array($usuario_evidencia, $excluidos_kpi2);
        $max_kpi2 = $es_excluido_kpi2 ? 500 : 300;
        $ranking[$nombre]['max_kpi2'] = $max_kpi2;
        
        // Calcular puntos (regla de tres)
        $puntos = ($total_evidencias > 0) ? round(($validadas_evidencias / $total_evidencias) * $max_kpi2) : 0;
        
        // Asignar a las 4 semanas
        $ranking[$nombre]['kpi2_pdv_w1'] = $validadas_evidencias;
        $ranking[$nombre]['kpi2_pdv_w2'] = $validadas_evidencias;
        $ranking[$nombre]['kpi2_pdv_w3'] = $validadas_evidencias;
        $ranking[$nombre]['kpi2_pdv_w4'] = $validadas_evidencias;
        $ranking[$nombre]['kpi2_pts_w1'] = $puntos;
        $ranking[$nombre]['kpi2_pts_w2'] = $puntos;
        $ranking[$nombre]['kpi2_pts_w3'] = $puntos;
        $ranking[$nombre]['kpi2_pts_w4'] = $puntos;
    }


    // Calcular Puntos totales y Estados
    foreach ($ranking as $nombre => &$data) {

        $data['kpi2_pts'] = $data['kpi2_pts_w4'] ?? 0;
        
        $data['total_pts'] = $data['kpi1_pts'] + $data['kpi2_pts'] + $data['kpi3_pts'];
        if ($data['total_pts'] >= 900) $data['estado'] = 'EXCELENTE';
        elseif ($data['total_pts'] >= 750) $data['estado'] = 'BUENO';
        elseif ($data['total_pts'] >= 500) $data['estado'] = 'BÁSICO';
        else $data['estado'] = 'NO CUMPLE';
    }

    usort($ranking, function($a, $b) { return $b['total_pts'] <=> $a['total_pts']; });

    // =================================================================
    // 5. CÁLCULO DE EQUIPO DINÁMICO (SUMA METAS REALES)
    // =================================================================
    $max_team_1 = 0; $max_team_2 = 0; $max_team_3 = 0;
    
    foreach ($ranking as $u) {
        $max_team_1 += $u['max_kpi1'];
        $max_team_2 += $u['max_kpi2'];
        $max_team_3 += $u['max_kpi3'];
    }
    
    if ($max_team_1 == 0) $max_team_1 = 1;
    if ($max_team_2 == 0) $max_team_2 = 1;
    if ($max_team_3 == 0) $max_team_3 = 1;


    $total_usuarios = count($ranking);
    $usuarios_kpi3 = 0;
    foreach ($ranking as $u) {
        if ($u['max_kpi3'] > 0) $usuarios_kpi3++;
    }
    if ($usuarios_kpi3 == 0) $usuarios_kpi3 = 1;

    // =================================================================
    // RECALCULAR SEMANAS 1,2,3 PARA EL GRÁFICO (sin afectar w4)
    // =================================================================
    foreach ($ranking as &$user) {
        // KPI 1
        $total_pts_kpi1 = $user['kpi1_pts']; // valor final (w4)
        $acum_dias = 0;
        for ($w = 1; $w <= 3; $w++) { // solo semanas 1,2,3
            if ($dias_por_semana[$w-1] == 0) {
                $user["kpi1_pts_w$w"] = 0;
            } else {
                $acum_dias += $dias_por_semana[$w-1];
                $progreso = $acum_dias / $total_dias;
                $user["kpi1_pts_w$w"] = round($total_pts_kpi1 * $progreso);
            }
        }
        // w4 ya está asignado, no lo toques

        // KPI 2
        $total_pts_kpi2 = $user['kpi2_pts'];
        $acum_dias = 0;
        for ($w = 1; $w <= 3; $w++) {
            if ($dias_por_semana[$w-1] == 0) {
                $user["kpi2_pts_w$w"] = 0;
            } else {
                $acum_dias += $dias_por_semana[$w-1];
                $progreso = $acum_dias / $total_dias;
                $user["kpi2_pts_w$w"] = round($total_pts_kpi2 * $progreso);
            }
        }

        // KPI 3
        $total_pts_kpi3 = $user['kpi3_pts'];
        $acum_dias = 0;
        for ($w = 1; $w <= 3; $w++) {
            if ($dias_por_semana[$w-1] == 0) {
                $user["kpi3_pts_w$w"] = 0;
            } else {
                $acum_dias += $dias_por_semana[$w-1];
                $progreso = $acum_dias / $total_dias;
                $user["kpi3_pts_w$w"] = round($total_pts_kpi3 * $progreso);
            }
        }
    }
    unset($user); 



    // Construir array de semanas activas y sus etiquetas
    $semanas_activas = [];
    $etiquetas_semanas = [];
    $semana_nombres = ['Semana 1', 'Semana 2', 'Semana 3', 'Total Periodo'];
    foreach ($dias_por_semana as $idx => $dias) {
        if ($dias > 0) {
            $semanas_activas[] = $idx;
            $etiquetas_semanas[] = $semana_nombres[$idx];
        }
    }
    // Si no hay semanas activas (raro), poner al menos una
    if (empty($semanas_activas)) {
        $semanas_activas = [0];
        $etiquetas_semanas = ['Sin datos'];
    }



    $tendencia = ['kpi1' => [], 'kpi2' => [], 'kpi3' => []];
    for ($w = 0; $w < 4; $w++) {
        $w_real = $w + 1;
        if ($dias_por_semana[$w] > 0) {

            $suma1 = array_sum(array_column($ranking, "kpi1_pts_w$w_real"));
            $suma2 = array_sum(array_column($ranking, "kpi2_pts_w$w_real"));
            $suma3 = array_sum(array_column($ranking, "kpi3_pts_w$w_real"));

            $tendencia['kpi1'][] = round(($suma1 / $max_team_1) * 100);
            $tendencia['kpi2'][] = round(($suma2 / $max_team_2) * 100);
            $tendencia['kpi3'][] = round(($suma3 / $max_team_3) * 100);
        } else {
           
            $tendencia['kpi1'][] = null;
            $tendencia['kpi2'][] = null;
            $tendencia['kpi3'][] = null;
        }
    }

    $suma_pts_kpi1 = array_sum(array_column($ranking, 'kpi1_pts'));
    $suma_pts_kpi2 = array_sum(array_column($ranking, 'kpi2_pts'));
    $suma_pts_kpi3 = array_sum(array_column($ranking, 'kpi3_pts'));

    // Porcentajes reales de cumplimiento
    $pct_kpi1 = ($max_team_1 > 0) ? round(($suma_pts_kpi1 / $max_team_1) * 100) : 0;
    $pct_kpi2 = ($max_team_2 > 0) ? round(($suma_pts_kpi2 / $max_team_2) * 100) : 0;
    $pct_kpi3 = ($max_team_3 > 0) ? round(($suma_pts_kpi3 / $max_team_3) * 100) : 0;

    // Ponderación para el donut (escala 1000)
    $pts_kpi1 = round(($pct_kpi1 / 100) * 400);
    $pts_kpi2 = round(($pct_kpi2 / 100) * 300);
    $pts_kpi3 = round(($pct_kpi3 / 100) * 300);
    $total_pts = $pts_kpi1 + $pts_kpi2 + $pts_kpi3;

    $metas_equipo = [
        "kpi1" => $max_team_1,
        "kpi2" => $max_team_2,
        "kpi3" => $max_team_3
    ];

    $cantidad_usuarios = count($ranking);

    $promedio_equipo = [
        "kpi1" => $cantidad_usuarios > 0 ? round($suma_pts_kpi1 / $cantidad_usuarios) : 0,
        "kpi2" => $cantidad_usuarios > 0 ? round($suma_pts_kpi2 / $cantidad_usuarios) : 0,
        "kpi3" => $cantidad_usuarios > 0 ? round($suma_pts_kpi3 / $cantidad_usuarios) : 0
    ];

    $promedio_metas = [
        "kpi1" => $cantidad_usuarios > 0 ? round($max_team_1 / $cantidad_usuarios) : 0,
        "kpi2" => $cantidad_usuarios > 0 ? round($max_team_2 / $cantidad_usuarios) : 0,
        "kpi3" => $cantidad_usuarios > 0 ? round($max_team_3 / $cantidad_usuarios) : 0
    ];

    $promedio_ponderado = [
        "kpi1" => $max_team_1 > 0 ? round(($suma_pts_kpi1 / $max_team_1) * 400) : 0,
        "kpi2" => $max_team_2 > 0 ? round(($suma_pts_kpi2 / $max_team_2) * 300) : 0,
        "kpi3" => $max_team_3 > 0 ? round(($suma_pts_kpi3 / $max_team_3) * 300) : 0
    ];

    // Puntos reales totales y máximos reales
    $suma_total_real = $suma_pts_kpi1 + $suma_pts_kpi2 + $suma_pts_kpi3;
    $max_total_real = $max_team_1 + $max_team_2 + $max_team_3;
    $porcentaje_real = $max_total_real > 0 ? round(($suma_total_real / $max_total_real) * 100) : 0;


    $semanas_activas = 0;
    foreach ($dias_por_semana as $dias) {
        if ($dias > 0) $semanas_activas++;
    }

    // --- Respuesta JSON ---
    echo json_encode([
        "status" => "success",
        "usuarios_activos" => array_column($ranking, 'nombre'),
        "global" => [
            "total_pts" => $total_pts,
            "kpi1" => ["pts" => $pts_kpi1, "pct" => $pct_kpi1],
            "kpi2" => ["pts" => $pts_kpi2, "pct" => $pct_kpi2],
            "kpi3" => ["pts" => $pts_kpi3, "pct" => $pct_kpi3]
        ],
        "donut_real" => [
            "total_pts" => $suma_total_real,
            "max_pts" => $max_total_real,
            "kpi1" => $suma_pts_kpi1,
            "kpi2" => $suma_pts_kpi2,
            "kpi3" => $suma_pts_kpi3,
            "porcentaje" => $porcentaje_real
        ],
        "suma_real" => [
            "kpi1" => $suma_pts_kpi1,
            "kpi2" => $suma_pts_kpi2,
            "kpi3" => $suma_pts_kpi3
        ],
        "metas_equipo" => $metas_equipo,
        "promedio_equipo" => $promedio_equipo,
        "promedio_metas" => $promedio_metas,
        "tendencia_semanal" => $tendencia,
        "etiquetas_semanas" => $etiquetas_semanas, // NUEVO
        "semanas_activas" => $semanas_activas,
        "promedio_ponderado" => $promedio_ponderado,
        "ranking" => array_values($ranking)
    ]);

} catch (\Throwable $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>