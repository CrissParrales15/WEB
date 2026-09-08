<?php
ini_set('display_errors', 0);

try {
    header('Content-Type: application/json; charset=utf-8');
    date_default_timezone_set('America/Guayaquil');

    require_once '../core/DataSource.php';
    $db = new \Phppot\DataSource();
    
    $desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-m-01');
    $hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-d'); 
    
    $filtro_merca = isset($_GET['mercaderista']) && $_GET['mercaderista'] !== 'all' ? $_GET['mercaderista'] : null;
    $filtro_region = isset($_GET['region']) && $_GET['region'] !== 'all' ? $_GET['region'] : null; 

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

    $filtro_negocio = "
        AND u.status = 1 
        AND UPPER(u.user) NOT LIKE '%PRUEBA%'
        AND UPPER(u.mercaderista) NOT LIKE '%PRUEBA%'
        AND TRIM(UPPER(u.mercaderista)) NOT IN ($sql_in_excluidos)
        AND (UPPER(p.atendido) != 'SAUL GILER' OR p.atendido IS NULL)
        AND UPPER(p.subchannel) = 'TIENDAS INDUSTRIALES ASOCIADAS'
    ";

    // 0. VOLUMEN GLOBAL REAL (DEL RANGO)
    $sql_global = "
        SELECT COUNT(DISTINCT CONCAT(IFNULL(o.numero_orden, 'VACIO'), '|', o.codigo_pdv, '|', DATE(o.fecha_servidor))) AS volumen_total
        FROM insert_orden_pedido o
        JOIN repositorio_locales_dtt2 p ON o.codigo_pdv = p.pos_id 
        JOIN repositorio_usuario u ON (TRIM(UPPER(o.usuario)) = TRIM(UPPER(u.user)) OR TRIM(UPPER(o.usuario)) = TRIM(UPPER(u.mercaderista)))
        WHERE DATE(o.fecha_servidor) BETWEEN ? AND ?
        AND o.existe_orden = 'SI'
        $filtro_negocio
    ";
    
    $params_global = [$desde, $hasta];
    $tipos_global = 'ss';
    
    if ($filtro_region) {
        $sql_global .= " AND UPPER(p.region) = UPPER(?)";
        $params_global[] = $filtro_region;
        $tipos_global .= 's';
    }
    if ($filtro_merca) {
        $sql_global .= " AND TRIM(UPPER(u.mercaderista)) = UPPER(?)";
        $params_global[] = $filtro_merca;
        $tipos_global .= 's';
    }
    
    $res_global = $db->select($sql_global, $tipos_global, $params_global);
    $volumen_total_global = !empty($res_global) && isset($res_global[0]['volumen_total']) ? (int)$res_global[0]['volumen_total'] : 0;

    // 1. META GLOBAL DEL RANGO (SUMA DEL RUTERO EN ESE PERIODO)
    $sql_meta = "
        SELECT 
            u.id AS id_usuario,
            TRIM(UPPER(u.mercaderista)) AS mercaderista,
            m.meta AS meta_diaria
        FROM repositorio_metas_incentivos m
        JOIN repositorio_usuario u ON TRIM(UPPER(u.mercaderista)) = TRIM(UPPER(m.usuario))
        WHERE m.fecha_inicio <= ? AND m.fecha_fin >= ?
        AND u.status = 1
        AND UPPER(u.mercaderista) NOT LIKE '%PRUEBA%'
        AND TRIM(UPPER(u.mercaderista)) NOT IN ($sql_in_excluidos)
    ";

    $params_meta = [$desde, $hasta];
    $tipos_meta = 'ss';
    
    if ($filtro_region) {
        $sql_meta .= " AND UPPER(p.region) = UPPER(?)";
        $params_meta[] = $filtro_region;
        $tipos_meta .= 's';
    }
    if ($filtro_merca) {
        $sql_meta .= " AND TRIM(UPPER(u.mercaderista)) = UPPER(?)";
        $params_meta[] = $filtro_merca;
        $tipos_meta .= 's';
    }
    $sql_meta .= " GROUP BY u.id, TRIM(UPPER(u.mercaderista))";
    $metas = $db->select($sql_meta, $tipos_meta, $params_meta) ?: [];

    $sql_logros = "
        SELECT 
            u.id AS id_usuario,
            COUNT(DISTINCT CONCAT(o.codigo_pdv, '|', DATE(o.fecha_servidor))) AS pdvs_con_oc,
            COUNT(DISTINCT CONCAT(IFNULL(o.numero_orden, 'VACIO'), '|', o.codigo_pdv, '|', DATE(o.fecha_servidor))) AS total_ordenes,
            SUM(o.valor_factura) AS total_facturado   -- NUEVA COLUMNA
        FROM insert_orden_pedido o
        JOIN repositorio_locales_dtt2 p ON o.codigo_pdv = p.pos_id 
        JOIN repositorio_usuario u ON (TRIM(UPPER(o.usuario)) = TRIM(UPPER(u.user)) OR TRIM(UPPER(o.usuario)) = TRIM(UPPER(u.mercaderista)))
        WHERE DATE(o.fecha_servidor) BETWEEN ? AND ?
        AND o.existe_orden = 'SI'
        $filtro_negocio
    ";
    $params_logros = [$desde, $hasta];
    $tipos_logros = 'ss';
    
    if ($filtro_region) {
        $sql_logros .= " AND UPPER(p.region) = UPPER(?)";
        $params_logros[] = $filtro_region;
        $tipos_logros .= 's';
    }
    if ($filtro_merca) {
        $sql_logros .= " AND TRIM(UPPER(u.mercaderista)) = UPPER(?)";
        $params_logros[] = $filtro_merca;
        $tipos_logros .= 's';
    }
    $sql_logros .= " GROUP BY u.id";
    $logros = $db->select($sql_logros, $tipos_logros, $params_logros) ?: [];
    $monto_facturado_total = array_sum(array_column($logros, 'total_facturado')) ?? 0;

    // CRUZAR LOS DATOS (PHP)
    $resumen_rango = [];
    foreach ($metas as $m) {
        $resumen_rango[$m['id_usuario']] = [
            'id_usuario' => $m['id_usuario'],
            'mercaderista' => $m['mercaderista'],
            'meta_pdvs' => $m['meta_diaria'],
            'pdvs_exitosos' => 0,
            'total_ordenes' => 0,
            'total_facturado' => 0 
        ];
    }
    foreach ($logros as $l) {
        if (isset($resumen_rango[$l['id_usuario']])) {
            $resumen_rango[$l['id_usuario']]['pdvs_exitosos'] = $l['pdvs_con_oc'];
            $resumen_rango[$l['id_usuario']]['total_ordenes'] = $l['total_ordenes'];
            $resumen_rango[$l['id_usuario']]['total_facturado'] = $l['total_facturado'] ?? 0;
        }
    }

    if ($filtro_merca) {
        $sql_id = "SELECT id FROM repositorio_usuario WHERE TRIM(UPPER(mercaderista)) = UPPER(?) LIMIT 1";
        $res_id = $db->select($sql_id, 's', [$filtro_merca]);
        if (!empty($res_id)) {
            $id_usr = $res_id[0]['id'];
            if (!isset($resumen_rango[$id_usr])) {
                $resumen_rango[$id_usr] = [
                    'id_usuario' => $id_usr,
                    'mercaderista' => strtoupper($filtro_merca),
                    'meta_pdvs' => 0,
                    'pdvs_exitosos' => 0,
                    'total_ordenes' => 0
                ];
            }
        }
    }

    // ORDENAR POR EFECTIVIDAD DE CUMPLIMIENTO
    uasort($resumen_rango, function($a, $b) {
        $pct_a = $a['meta_pdvs'] > 0 ? ($a['pdvs_exitosos'] / $a['meta_pdvs']) : 0;
        $pct_b = $b['meta_pdvs'] > 0 ? ($b['pdvs_exitosos'] / $b['meta_pdvs']) : 0;
        return $pct_b <=> $pct_a;
    });

    $data_rango = array_values($resumen_rango);

    // 3. DETALLE FEED INFERIOR (Con Fecha Real)
    $sql_diario = "
        SELECT 
            DATE(o.fecha_servidor) AS fecha,
            o.hora, TRIM(UPPER(u.mercaderista)) AS mercaderista,
            o.codigo_pdv AS pos_id, o.punto_venta AS nombre_pdv,
            o.existe_orden, o.numero_orden
        FROM insert_orden_pedido o
        JOIN repositorio_locales_dtt2 p ON o.codigo_pdv = p.pos_id
        JOIN repositorio_usuario u ON (TRIM(UPPER(o.usuario)) = TRIM(UPPER(u.user)) OR TRIM(UPPER(o.usuario)) = TRIM(UPPER(u.mercaderista)))
        WHERE DATE(o.fecha_servidor) BETWEEN ? AND ?
        $filtro_negocio
    ";
    
    $params_diario = [$desde, $hasta];
    $tipos_diario = 'ss';
    
    if ($filtro_region) {
        $sql_diario .= " AND UPPER(p.region) = UPPER(?)";
        $params_diario[] = $filtro_region;
        $tipos_diario .= 's';
    }
    if ($filtro_merca) {
        $sql_diario .= " AND TRIM(UPPER(u.mercaderista)) = UPPER(?)";
        $params_diario[] = $filtro_merca;
        $tipos_diario .= 's';
    }
    
    $sql_diario .= " ORDER BY DATE(o.fecha_servidor) DESC, o.hora DESC";
    $diario = $db->select($sql_diario, $tipos_diario, $params_diario) ?: [];

    // =========================================================
    // COMBOBOX DINÁMICO 
    // =========================================================
    $lista_usuarios_temp = [];

    // Extraemos de las metas y logros (Resumen)
    foreach ($data_rango as $row) {
        $nombre_user = trim($row['mercaderista']);
        if (!empty($nombre_user) && !in_array($nombre_user, $lista_usuarios_temp)) {
            $lista_usuarios_temp[] = $nombre_user;
        }
    }

    // Extraemos del feed diario (Por si alguien reportó sin tener meta programada)
    foreach ($diario as $row) {
        $nombre_user = trim($row['mercaderista']);
        if (!empty($nombre_user) && !in_array($nombre_user, $lista_usuarios_temp)) {
            $lista_usuarios_temp[] = $nombre_user;
        }
    }

    // Ordenamos alfabéticamente
    sort($lista_usuarios_temp);
    $lista_usuarios = $lista_usuarios_temp;

    echo json_encode([
        "status" => "success",
        "rango" => "Desde $desde hasta $hasta",
        "volumen_total_global" => $volumen_total_global,
        "monto_facturado_total" => $monto_facturado_total,
        "usuarios_activos" => $lista_usuarios,
        "resumen_rango" => $data_rango,
        "detalle_diario" => $diario
    ]);

} catch (\Throwable $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>