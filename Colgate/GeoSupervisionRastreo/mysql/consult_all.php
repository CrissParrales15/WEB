<?php

$dv    = isset($_GET['dv']) ? $_GET['dv'] : '';
$sprv  = isset($_GET['sprv']) ? $_GET['sprv'] : '';
$merc  = isset($_GET['merc']) ? $_GET['merc'] : '';
$fecha = isset($_GET['fecha']) ? $_GET['fecha'] : '';

// El <input type="time"> manda "HH:MM" (sin segundos), pero insert_gps_rastreo4.hora
// guarda "HH:MM:SS". Completamos los segundos para que el BETWEEN cubra el minuto
// límite entero (antes, p.ej. horaF=17:00 dejaba fuera los puntos de 17:00:01 a 17:00:59).
$horaI = isset($_GET['horaI']) && $_GET['horaI'] !== '' ? $_GET['horaI'] . ':00' : '';
$horaF = isset($_GET['horaF']) && $_GET['horaF'] !== '' ? $_GET['horaF'] . ':59' : '';

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
    $result = $conn->query("SELECT DISTINCT supervisor FROM /*lvi_ruta_semanal_supervisores_v2*/ lvi_ruta_semanal_v2 
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

    // Ventana de dedup: colapsa lecturas repetidas en la misma coordenada si caen dentro
    // de este margen de segundos (mitiga el bug de duplicados de 5pGoAppv2 mientras se
    // despliega el fix a los celulares). Ver comentario en la query de abajo.
    $ventanaDedupSegundos = 5;

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

        // Dedup por VENTANA DE TIEMPO (no por segundo exacto): el bug de la app (ver
        // 5pGoAppv2/LocationService.java) sigue activo en los celulares que no tengan la
        // versión corregida instalada, y produce lecturas duplicadas en la MISMA coordenada
        // pero con 1-2 segundos de diferencia en "hora" (confirmado con datos reales), así
        // que un GROUP BY exacto por hora no las atrapa. Con LAG() descartamos una fila si
        // repite la coordenada de la lectura inmediatamente anterior de ese mismo mercaderista
        // dentro de $ventanaDedupSegundos segundos; una revisita real al mismo punto minutos
        // después queda fuera de la ventana y se conserva.
        $sql = "SELECT id, mercaderista, latitud, longitud, fecha, hora, fecha_servidor FROM (
                SELECT t.*,
                    LAG(latitud)  OVER (PARTITION BY mercaderista ORDER BY hora) AS prev_lat,
                    LAG(longitud) OVER (PARTITION BY mercaderista ORDER BY hora) AS prev_lng,
                    LAG(hora)     OVER (PARTITION BY mercaderista ORDER BY hora) AS prev_hora
                FROM insert_gps_rastreo4 t
                WHERE STR_TO_DATE(fecha, '%d/%m/%Y') = ?
                AND hora BETWEEN ? AND ?
                AND latitud != 'GPS DESACTIVADO'
                AND longitud != 'GPS DESACTIVADO'
                AND mercaderista IN ($placeholders)
            ) w
            WHERE prev_lat IS NULL
               OR latitud <> prev_lat
               OR longitud <> prev_lng
               OR TIME_TO_SEC(TIMEDIFF(hora, prev_hora)) > $ventanaDedupSegundos
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
        // Mismo criterio de dedup por ventana de tiempo que en la rama 'all' (ver comentario arriba).
        $stmt2 = $conn2->prepare("SELECT id, mercaderista, latitud, longitud, fecha, hora, fecha_servidor FROM (
                SELECT t.*,
                    LAG(latitud)  OVER (PARTITION BY mercaderista ORDER BY hora) AS prev_lat,
                    LAG(longitud) OVER (PARTITION BY mercaderista ORDER BY hora) AS prev_lng,
                    LAG(hora)     OVER (PARTITION BY mercaderista ORDER BY hora) AS prev_hora
                FROM insert_gps_rastreo4 t
                WHERE mercaderista = ?
                AND STR_TO_DATE(fecha, '%d/%m/%Y') = ?
                AND hora BETWEEN ? AND ?
                AND latitud != 'GPS DESACTIVADO'
                AND longitud != 'GPS DESACTIVADO'
            ) w
            WHERE prev_lat IS NULL
               OR latitud <> prev_lat
               OR longitud <> prev_lng
               OR TIME_TO_SEC(TIMEDIFF(hora, prev_hora)) > $ventanaDedupSegundos
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