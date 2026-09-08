<?php

$dv    = isset($_GET['dv']) ? $_GET['dv'] : '';
$sprv  = isset($_GET['sprv']) ? $_GET['sprv'] : '';
$merc  = isset($_GET['merc']) ? $_GET['merc'] : '';
$fecha = isset($_GET['fecha']) ? $_GET['fecha'] : '';
$horaI = isset($_GET['horaI']) ? $_GET['horaI'] : '';
$horaF = isset($_GET['horaF']) ? $_GET['horaF'] : '';

// Solo señala si la coordenada se ve fuera del rango lógico de Ecuador.
// No filtra nada, solo agrega un flag informativo.
function coordenadaValida($lat, $lng)
{
    if (!is_numeric($lat) || !is_numeric($lng)) return false;
    $lat = (float)$lat;
    $lng = (float)$lng;
    return ($lat >= -5 && $lat <= 2 && $lng >= -92 && $lng <= -75);
}

//consulta supervisor
function consultarSupervisor()
{
    require_once 'conexion.php';

    $data = [];
    $result = $conn->query("SELECT DISTINCT supervisor FROM lvi_ruta_semanal_supervisores_v2 
        WHERE supervisor IS NOT NULL AND supervisor != '' 
        AND supervisor NOT LIKE '%PRUEBA%' 
        AND supervisor NOT LIKE '%TEST%'
        /*AND rol = 'Supervisor Lucky'*/
        ORDER BY supervisor ASC");

    while ($r = $result->fetch_assoc()) {
        $data[] = $r;
    }

    $result->close();
    $conn->close();

    return json_encode($data);
}

//consulta mercaderista segun supervisor
function consultarMercaderista($sprv)
{
    require_once 'conexion.php';

    $data = [];
    $stmt = $conn->prepare("SELECT DISTINCT mercaderista FROM lvi_ruta_semanal_v2 
        WHERE supervisor = ? 
        AND mercaderista IS NOT NULL AND mercaderista != ''
        AND mercaderista NOT LIKE '%PRUEBA%'
        AND mercaderista NOT LIKE '%TEST%'
        ORDER BY mercaderista ASC");
    $stmt->bind_param("s", $sprv);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($r = $result->fetch_assoc()) {
        $data[] = $r;
    }

    $stmt->close();
    $conn->close();

    return json_encode($data);
}

//consulta puntos de venta
function consultarPdv($merc, $sprv, $fecha)
{
    require_once 'conexion.php';

    $data = [];

    if ($merc == 'all') {
        $stmt = $conn->prepare("SELECT pos_name, pos_id, supervisor, address, latitud, longitud, foto, 
            mercaderista, city, distancia 
            FROM lvi_ruta_semanal_v2 
            WHERE supervisor = ?
            AND fecha_visita = ? 
            AND mercaderista NOT LIKE '%PRUEBA%'
            AND mercaderista NOT LIKE '%TEST%'");
        $stmt->bind_param("ss", $sprv, $fecha);
    } else {
        $stmt = $conn->prepare("SELECT pos_name, pos_id, supervisor, address, latitud, longitud, foto, 
            mercaderista, city, distancia 
            FROM lvi_ruta_semanal_v2 
            WHERE mercaderista = ?
            AND fecha_visita = ?
            AND mercaderista NOT LIKE '%PRUEBA%'
            AND mercaderista NOT LIKE '%TEST%'");
        $stmt->bind_param("ss", $merc, $fecha);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    while ($r = $result->fetch_assoc()) {
        // Solo se señala, no se filtra ni se modifica el dato original
        $r['coord_valida'] = coordenadaValida($r['latitud'], $r['longitud']);
        $data[] = $r;
    }

    $stmt->close();
    $conn->close();

    return json_encode($data);
}

//consulta final de rastreo GPS, con supervisor, mercaderista y fecha
//OJO: esta tabla vive en otra base de datos (luckyec_appgeosupervision), por eso usa $conn2
function consultarRastreo($merc, $sprv, $fecha, $horaI, $horaF)
{
    require_once 'conexion.php';

    error_log("=== consultarRastreo === merc=$merc sprv=$sprv fecha=$fecha horaI=$horaI horaF=$horaF");

    $data = [];

    if ($merc == 'all') {
        // Primero obtenemos los mercaderistas del supervisor desde la base 5pgo
        $mercaderistas = [];
        $stmt = $conn->prepare("SELECT DISTINCT mercaderista FROM lvi_ruta_semanal_v2 
            WHERE supervisor = ?
            AND mercaderista NOT LIKE '%PRUEBA%'
            AND mercaderista NOT LIKE '%TEST%'");
        $stmt->bind_param("s", $sprv);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($r = $result->fetch_assoc()) {
            $mercaderistas[] = $r['mercaderista'];
        }
        $stmt->close();

        error_log("Mercaderistas encontrados para supervisor '$sprv': " . json_encode($mercaderistas));

        if (count($mercaderistas) === 0) {
            $conn->close();
            $conn2->close();
            return json_encode('cero registros');
        }

        // Armamos un IN (?, ?, ?...) dinámico y seguro contra el segundo conn
        $placeholders = implode(',', array_fill(0, count($mercaderistas), '?'));
        $types = str_repeat('s', count($mercaderistas));

        $sql = "SELECT * FROM insert_gps_rastreo4 
            WHERE STR_TO_DATE(fecha, '%d/%m/%Y') = ? 
            AND hora BETWEEN ? AND ? 
            AND latitud != 'GPS DESACTIVADO' 
            AND longitud != 'GPS DESACTIVADO' 
            AND mercaderista IN ($placeholders)
            GROUP BY latitud, longitud
            ORDER BY mercaderista ASC, hora ASC";

        $stmt2 = $conn2->prepare($sql);

        // bind_param necesita los parámetros por referencia
        $allParams = array_merge([$fecha, $horaI, $horaF], $mercaderistas);
        $allTypes = "sss" . $types;
        $stmt2->bind_param($allTypes, ...$allParams);
        $stmt2->execute();
        $result2 = $stmt2->get_result();

        while ($rows = $result2->fetch_assoc()) {
            $rows['coord_valida'] = coordenadaValida($rows['latitud'], $rows['longitud']);
            $data[] = $rows;
        }

        error_log("consultarRastreo (all): filas encontradas = " . count($data));

        $stmt2->close();
        $conn->close();
    } else {
        $stmt2 = $conn2->prepare("SELECT * FROM insert_gps_rastreo4 
            WHERE mercaderista = ? 
            AND STR_TO_DATE(fecha, '%d/%m/%Y') = ? 
            AND hora BETWEEN ? AND ? 
            AND latitud != 'GPS DESACTIVADO' 
            AND longitud != 'GPS DESACTIVADO' 
            GROUP BY latitud, longitud 
            ORDER BY mercaderista ASC, hora ASC");
        $stmt2->bind_param("ssss", $merc, $fecha, $horaI, $horaF);
        $stmt2->execute();
        $result2 = $stmt2->get_result();

        while ($rows = $result2->fetch_assoc()) {
            $rows['coord_valida'] = coordenadaValida($rows['latitud'], $rows['longitud']);
            $data[] = $rows;
        }

        error_log("consultarRastreo (uno): filas encontradas = " . count($data));

        $stmt2->close();
        $conn->close();
    }

    $conn2->close();

    if (count($data) === 0) {
        $data = 'cero registros';
    }

    return json_encode($data);
}

function obtenerColores()
{
    require_once 'conexion.php';

    $data = [];
    $result = $conn->query("SELECT mercaderista AS user, color FROM repositorio_usuarios 
        WHERE mercaderista IS NOT NULL AND mercaderista != '' GROUP BY mercaderista, color;");

    while ($r = $result->fetch_assoc()) {
        $data[] = $r;
    }

    $conn->close();

    return json_encode($data);
}

switch ($dv) {
    case "consultarSupervisor":
        echo consultarSupervisor();
        break;
    case "consultarMercaderista":
        echo consultarMercaderista($sprv);
        break;
    case "consultarPdv":
        echo consultarPdv($merc, $sprv, $fecha);
        break;
    case "consultarRastreo":
        echo consultarRastreo($merc, $sprv, $fecha, $horaI, $horaF);
        break;
    case "obtenerColores":
        echo obtenerColores();
        break;
} 