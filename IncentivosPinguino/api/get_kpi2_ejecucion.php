<?php
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Guayaquil');

require_once '../core/DataSource.php';
use Phppot\DataSource;

try {
    $db = new DataSource();
    
    // Filtros por Rango
    $desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-m-01');
    $hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-d');
    $filtro_region = isset($_GET['region']) && $_GET['region'] !== 'all' ? $_GET['region'] : null;

    $mercaderista = isset($_GET['mercaderista']) && $_GET['mercaderista'] !== 'all' ? $_GET['mercaderista'] : null;

    $url_base_img = "https://luckyecuadorweb.blob.core.windows.net/app/AppPinguino/Inserts/";


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
    $sql_in_excluidos = "'" . implode("','", $excluidos_kpi2) . "'";

   
    $sql_cliente = "
    SELECT 
        MAX(e.id) AS id,
        e.codigo AS pos_id,
        MAX(p.pos_name) AS nombre_pdv,
        e.usuario AS mercaderista,
        MAX(e.categoria) AS categoria,
        MAX(e.comentario) AS comentario,
        e.foto_antes,
        e.foto_despues,
        MAX(e.hora) AS hora,
        MAX(e.fecha) AS fecha_trabajo,
        SUM(CASE WHEN e.validado = 1 THEN 1 ELSE 0 END) AS validadas_evidencias,  -- cambio aquí
        CASE 
            WHEN TRIM(UPPER(e.usuario)) IN ($sql_in_excluidos) THEN 500 
            ELSE 300 
        END AS max_kpi2
    FROM insert_evidencias e
    JOIN repositorio_locales_dtt2 p ON e.codigo = p.pos_id
    JOIN repositorio_usuario u ON (TRIM(UPPER(e.usuario)) = TRIM(UPPER(u.user)) OR TRIM(UPPER(e.usuario)) = TRIM(UPPER(u.mercaderista)))
    WHERE STR_TO_DATE(e.fecha, '%d/%m/%Y') BETWEEN ? AND ?
      AND UPPER(e.usuario) NOT LIKE '%PRUEBA%'
      AND u.status = 1 
      AND UPPER(u.mercaderista) NOT LIKE '%PRUEBA%'
";

// Parámetros y filtros
$params_cli = [$desde, $hasta];
$tipos_cli = 'ss';

if ($filtro_region) {
    $sql_cliente .= " AND UPPER(p.region) = UPPER(?) ";
    $params_cli[] = $filtro_region;
    $tipos_cli .= 's';
}
// if ($mercaderista) {
//     $sql_cliente .= " AND TRIM(UPPER(u.mercaderista)) = UPPER(?) ";
//     $params_cli[] = $mercaderista;
//     $tipos_cli .= 's';
// }


if ($mercaderista) {
    $sql_cliente .= " AND TRIM(UPPER(e.usuario)) = UPPER(?) ";
    $params_cli[] = $mercaderista;
    $tipos_cli .= 's';
}

$sql_cliente .= " GROUP BY TRIM(UPPER(u.mercaderista)), TRIM(UPPER(e.usuario)), e.codigo, e.foto_antes, e.foto_despues";
$data_cliente = $db->select($sql_cliente, $tipos_cli, $params_cli) ?: [];


    $sql_auditor = "
        SELECT 
            MAX(e.id) AS id,
            e.codigo AS pos_id,
            MAX(p.pos_name) AS nombre_pdv,
            MAX(p.region) AS zona,
            e.usuario AS mercaderista,
            MAX(e.categoria) AS categoria,
            MAX(e.subcategoria) AS subcategoria,
            MAX(e.comentario) AS comentario,
            e.foto_antes,
            e.foto_despues,
            MAX(e.hora) AS hora,
            MAX(e.fecha) AS fecha_trabajo,
            SUM(CASE WHEN e.validado = 1 THEN 1 ELSE 0 END) AS validadas_evidencias
        FROM insert_evidencias e
        LEFT JOIN repositorio_locales_dtt2 p ON e.codigo = p.pos_id
        JOIN repositorio_usuario u ON (TRIM(UPPER(e.usuario)) = TRIM(UPPER(u.user)) OR TRIM(UPPER(e.usuario)) = TRIM(UPPER(u.mercaderista)))
        WHERE DATE(e.fechaservidor) BETWEEN ? AND ?
        AND UPPER(e.usuario) NOT LIKE '%PRUEBA%'
        AND u.status = 1 
        AND UPPER(u.mercaderista) NOT LIKE '%PRUEBA%'
    ";
    
    $params_aud = [$desde, $hasta]; 
    $tipos_aud = 'ss';
    
    // if ($mercaderista) {
    //     $sql_auditor .= " AND e.usuario = ?";
    //     $params_aud[] = $mercaderista;
    //     $tipos_aud .= 's';
    // }

    if ($mercaderista) {
        $sql_auditor .= " AND TRIM(UPPER(e.usuario)) = UPPER(?) ";
        $params_aud[] = $mercaderista;
        $tipos_aud .= 's';
    }
    
    // Agrupamos para matar clones y ordenamos por hora real
    $sql_auditor .= " GROUP BY e.usuario, e.codigo, e.foto_antes, e.foto_despues ORDER BY MAX(e.fecha) DESC, MAX(e.hora) DESC";

    $data_auditor = $db->select($sql_auditor, $tipos_aud, $params_aud) ?: [];

    // =================================================================
    // 3. FORMATEO DE RESPUESTAS
    // =================================================================
    $formato_auditor = [];
    $lista_usuarios = [];
    
    foreach ($data_auditor as $row) {
        $nombre_user = trim($row['mercaderista']);
        if (!empty($nombre_user) && !in_array($nombre_user, $lista_usuarios)) {
            $lista_usuarios[] = $nombre_user;
        }

        $formato_auditor[] = [
            'id_evidencia' => $row['id'],
            'pdv' => $row['pos_id'] . ' - ' . ($row['nombre_pdv'] ?? 'PDV Desconocido'),
            'zona' => $row['zona'] ?? '-',
            'mercaderista' => $nombre_user,
            'categoria' => $row['categoria'] . ' / ' . $row['subcategoria'],
            'comentario' => $row['comentario'],
            'hora' => $row['hora'],
            'fecha_trabajo' => $row['fecha_trabajo'],
            'url_antes' => $url_base_img . $row['foto_antes'],
            'url_despues' => $url_base_img . $row['foto_despues'],
            'validado' => ($row['validadas_evidencias'] == 1) ? 1 : 0
        ];
    }

    $formato_cliente = [];
    foreach ($data_cliente as $row) {
        $nombre_user_cli = trim($row['mercaderista']);
        if (!empty($nombre_user_cli) && !in_array($nombre_user_cli, $lista_usuarios)) {
            $lista_usuarios[] = $nombre_user_cli;
        }


        $formato_cliente[] = [
            'id_evidencia' => $row['id'],
            'pdv' => $row['pos_id'] . ' - ' . ($row['nombre_pdv'] ?? 'PDV Desconocido'),
            'mercaderista' => $nombre_user_cli,
            'categoria' => $row['categoria'] ?? 'Sin Categoría',
            'comentario' => $row['comentario'] ?? '',
            'hora' => $row['hora'] ?? '',
            'fecha_trabajo' => $row['fecha_trabajo'] ?? '',
            'url_antes' => $url_base_img . $row['foto_antes'],
            'url_despues' => $url_base_img . $row['foto_despues'],
            'validado' => $row['validadas_evidencias'] > 0 ? 1 : 0,
            'max_kpi2' => (int)$row['max_kpi2']  // <-- NUEVO
        ];
    }

    echo json_encode([
        "status" => "success",
        "rango" => "Desde $desde hasta $hasta",
        "usuarios_activos" => $lista_usuarios,
        "evidencias_cliente" => $formato_cliente,
        "evidencias_auditoria" => $formato_auditor
    ]);

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error al procesar KPI 2",
        "detalle" => $e->getMessage()
    ]);
}