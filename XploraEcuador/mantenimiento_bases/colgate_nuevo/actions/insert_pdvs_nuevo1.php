<?php
// actions/insert_locales_dtt2.php

include_once "../includes/db_connect.php";
include_once "../includes/functions.php";
include_once "../includes/config.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $pos_id          = isset($_POST['pos_id'])          ? trim($_POST['pos_id'])          : '';
    $sales_executive  = isset($_POST['sales_executive']) ? trim($_POST['sales_executive'])  : '';
    $channel          = isset($_POST['channel'])         ? trim($_POST['channel'])          : '';
    $subchannel       = isset($_POST['subchannel'])       ? trim($_POST['subchannel'])       : '';
    $reabrev          = isset($_POST['reabrev'])          ? trim($_POST['reabrev'])          : '';
    $format           = isset($_POST['format'])           ? trim($_POST['format'])           : '';
    $pos_name_dpsm    = isset($_POST['pos_name_dpsm'])    ? trim($_POST['pos_name_dpsm'])    : '';
    $kam              = isset($_POST['kam'])              ? trim($_POST['kam'])              : '';
    $merchandising    = isset($_POST['merchandising'])    ? trim($_POST['merchandising'])    : '';
    $customer_owner   = isset($_POST['customer_owner'])   ? trim($_POST['customer_owner'])   : '';
    $pos_name         = isset($_POST['pos_name'])         ? trim($_POST['pos_name'])         : '';
    $dpsm             = isset($_POST['dpsm'])             ? trim($_POST['dpsm'])             : '';
    $region           = isset($_POST['region'])           ? trim($_POST['region'])           : '';
    $tipo             = isset($_POST['tipo'])             ? trim($_POST['tipo'])             : '';
    $province         = isset($_POST['province'])         ? trim($_POST['province'])         : '';
    $city             = isset($_POST['city'])             ? trim($_POST['city'])             : '';
    $zone             = isset($_POST['zone'])             ? trim($_POST['zone'])             : '';
    $address          = isset($_POST['address'])          ? trim($_POST['address'])          : '';
    $supervisor       = isset($_POST['supervisor'])       ? trim($_POST['supervisor'])       : '';
    $latitud          = isset($_POST['latitud'])          ? trim($_POST['latitud'])          : '';
    $longitud         = isset($_POST['longitud'])         ? trim($_POST['longitud'])         : '';
    $channel_segment  = isset($_POST['channel_segment'])  ? trim($_POST['channel_segment'])  : '';
    $visual           = isset($_POST['visual'])           ? trim($_POST['visual'])           : '';
    $coordinador      = isset($_POST['coordinador'])      ? trim($_POST['coordinador'])      : '';
    $foto             = isset($_POST['foto'])             ? trim($_POST['foto'])             : '';
    $status           = isset($_POST['status'])           ? trim($_POST['status'])           : '';
    $perimetro        = isset($_POST['perimetro'])        ? trim($_POST['perimetro'])        : '';
    $distancia        = isset($_POST['distancia'])        ? trim($_POST['distancia'])        : '';
    // "activar" no viene del formulario: todo registro nuevo se crea visible (SI) por defecto.
    $activar          = 'SI';
    $color            = isset($_POST['color'])            ? trim($_POST['color'])            : '';
    $tiempo_visita    = isset($_POST['tiempo_visita'])    ? trim($_POST['tiempo_visita'])    : '';

    // pos_id es UNIQUE: validar que no exista ya antes de insertar,
    // para dar un mensaje claro en vez del error técnico "Duplicate entry".
    if ($pos_id !== '') {
        if ($chk = $mysqli->prepare("SELECT id FROM repositorio_locales_dtt2 WHERE pos_id = ? LIMIT 1")) {
            $chk->bind_param("s", $pos_id);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows > 0) {
                $chk->close();
                echo json_encode(['success' => false, 'message' => 'Ya existe un registro con el POS ID "' . $pos_id . '". El POS ID debe ser único.']);
                $mysqli->close();
                exit;
            }
            $chk->close();
        }
    }

    // 31 columnas de datos -> 31 placeholders '?' -> 31 caracteres 's' en bind_param
    $query = "INSERT INTO repositorio_locales_dtt2 (
        pos_id, sales_executive, channel, subchannel, reabrev, format,
        pos_name_dpsm, kam, merchandising, customer_owner, pos_name, dpsm,
        region, tipo, province, city, zone, address, supervisor, latitud,
        longitud, channel_segment, visual, coordinador, foto, status,
        perimetro, distancia, activar, color, tiempo_visita
    ) VALUES (
        ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?
    )";

    if ($stmt = $mysqli->prepare($query)) {
        $stmt->bind_param(
            "sssssssssssssssssssssssssssssss", // 31 s -- verificado: cuenta las 's' y compáralas con las 31 columnas de arriba
            $pos_id, $sales_executive, $channel, $subchannel, $reabrev, $format,
            $pos_name_dpsm, $kam, $merchandising, $customer_owner, $pos_name, $dpsm,
            $region, $tipo, $province, $city, $zone, $address, $supervisor, $latitud,
            $longitud, $channel_segment, $visual, $coordinador, $foto, $status,
            $perimetro, $distancia, $activar, $color, $tiempo_visita
        );

        try {
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Registro insertado correctamente', 'id' => $stmt->insert_id]);
            } else {
                if ($stmt->errno === 1062 || $mysqli->errno === 1062) {
                    echo json_encode(['success' => false, 'message' => 'Ya existe un registro con ese POS ID. El POS ID debe ser único.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta: ' . $stmt->error]);
                }
            }
        } catch (\mysqli_sql_exception $e) {
            // Modo excepción: el duplicado (código 1062) llega aquí
            if ($e->getCode() === 1062) {
                echo json_encode(['success' => false, 'message' => 'Ya existe un registro con ese POS ID. El POS ID debe ser único.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta: ' . $e->getMessage()]);
            }
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al preparar la consulta: ' . $mysqli->error]);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}

$mysqli->close();
?>