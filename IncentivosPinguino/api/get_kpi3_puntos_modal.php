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

    $filtro_negocio = "
        AND u.status = 1 
        AND UPPER(u.mercaderista) NOT LIKE '%PRUEBA%' 
        AND TRIM(UPPER(u.mercaderista)) NOT IN ('GONZALEZ QUIMI GERALDINE ALEXANDRA', 'ZARABIA MUNOZ EDWAR ISRAEL', 'ALVAREZ RUIZ ALEX GIOVANNI', 'JOSE ANDRES MUNOZ HERNANDEZ', 'KATHERINE GABRIELA RODRIGUEZ GAMBOA', 'VIVIANA LUCIA QUINTEROS ROBOLLEDO', 'JANELLA LILIBETH ROCA PAZMINO', 'YELITZA VALERIA QUIMIZ MENDOZA')
        AND (UPPER(p.atendido) != 'SAUL GILER' OR p.atendido IS NULL)
        AND UPPER(p.subchannel) = 'TIENDAS INDUSTRIALES ASOCIADAS'
    ";

    // 1. Obtener la META DIARIA (Rutero TIA) de todo el mes
    $sql_rutero = "
        SELECT 
            DATE(r.fecha_visita) as fecha_dia,
            COUNT(DISTINCT r.id_pdv) as total_asignados
        FROM rutero_pdv r
        JOIN repositorio_locales_dtt2 p ON r.id_pdv = p.id
        WHERE r.id_usuario = ? 
        AND DATE_FORMAT(r.fecha_visita, '%Y-%m') = DATE_FORMAT(?, '%Y-%m')
        AND r.habilitado = 1
        $filtro_negocio
        GROUP BY DATE(r.fecha_visita)
        ORDER BY fecha_dia ASC
    ";
    $dias_rutero = $db->select($sql_rutero, 'is', [$id_usuario, $fecha_referencia]) ?: [];

    // 2. Obtener LOGROS DIARIOS (Órdenes efectivas TIA) de todo el mes
    $sql_oc = "
        SELECT 
            DATE(o.fecha_servidor) as fecha_dia,
            COUNT(DISTINCT o.codigo_pdv) as total_visitados
        FROM insert_orden_pedido o
        JOIN repositorio_locales_dtt2 p ON o.codigo_pdv = p.pos_id
        JOIN repositorio_usuario u ON (TRIM(UPPER(o.usuario)) = TRIM(UPPER(u.user)) OR TRIM(UPPER(o.usuario)) = TRIM(UPPER(u.mercaderista)))
        WHERE u.id = ? 
        AND DATE_FORMAT(o.fecha_servidor, '%Y-%m') = DATE_FORMAT(?, '%Y-%m')
        AND o.existe_orden = 'SI'
        $filtro_negocio
        GROUP BY DATE(o.fecha_servidor)
    ";
    $logros_oc = $db->select($sql_oc, 'is', [$id_usuario, $fecha_referencia]) ?: [];

    // Indexar logros por fecha para cruzar fácil
    $logros_por_fecha = [];
    foreach ($logros_oc as $logro) {
        $logros_por_fecha[$logro['fecha_dia']] = $logro['total_visitados'];
    }

    $puntos_acumulados = 0;
    $meta_puntos = 300; 
    $desglose_diario = [];

    // 3. Procesar día por día
    foreach ($dias_rutero as $dia) {
        $fecha = $dia['fecha_dia'];
        $asignados = $dia['total_asignados'];
        $visitados = isset($logros_por_fecha[$fecha]) ? $logros_por_fecha[$fecha] : 0;
        
        $porcentaje_dia = $asignados > 0 ? round(($visitados / $asignados) * 100) : 0;
        
        // REGLA DE NEGOCIO KPI 3: Si supera (o iguala) el 70%, gana 15 puntos.
        $puntos_ganados = 0;
        if ($porcentaje_dia >= 70) {
            $puntos_ganados = 15;
            $puntos_acumulados += 15;
        }

        $desglose_diario[] = [
            'fecha' => $fecha,
            'asignados' => $asignados,
            'visitados' => $visitados,
            'efectividad' => $porcentaje_dia,
            'puntos' => $puntos_ganados
        ];
    }

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
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}