<?php
// actions/update_locales_dtt2.php

include_once "../includes/db_connect.php";
include_once "../includes/functions.php";
include_once "../includes/config.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        exit;
    }

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
    // NOTA: "activar" NO se actualiza aquí a propósito. Es un campo de
    // visibilidad/soft-delete, no un dato editable del local. Se controla
    // exclusivamente desde el switch en el listado (actions/toggle_activar_locales_dtt2.php)
    // o desde eliminar/eliminar por Excel.
    $color            = isset($_POST['color'])            ? trim($_POST['color'])            : '';
    $tiempo_visita    = isset($_POST['tiempo_visita'])    ? trim($_POST['tiempo_visita'])    : '';

    // pos_id es UNIQUE: no permitir cambiarlo a uno que ya use OTRO registro.
    // (En la UI el POS ID es de solo lectura, pero validamos igual por seguridad.)
    if ($pos_id !== '') {
        if ($chk = $mysqli->prepare("SELECT id FROM repositorio_locales_dtt2 WHERE pos_id = ? AND id != ? LIMIT 1")) {
            $chk->bind_param("si", $pos_id, $id);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows > 0) {
                $chk->close();
                echo json_encode(['success' => false, 'message' => 'Otro registro ya usa el POS ID "' . $pos_id . '". El POS ID debe ser único.']);
                $mysqli->close();
                exit;
            }
            $chk->close();
        }
    }

    // 30 columnas SET (sin "activar") + 1 WHERE id = 31 placeholders totales
    $query = "UPDATE repositorio_locales_dtt2 SET
        pos_id = ?, sales_executive = ?, channel = ?, subchannel = ?, reabrev = ?,
        format = ?, pos_name_dpsm = ?, kam = ?, merchandising = ?, customer_owner = ?,
        pos_name = ?, dpsm = ?, region = ?, tipo = ?, province = ?, city = ?, zone = ?,
        address = ?, supervisor = ?, latitud = ?, longitud = ?, channel_segment = ?,
        visual = ?, coordinador = ?, foto = ?, status = ?, perimetro = ?, distancia = ?,
        color = ?, tiempo_visita = ?
        WHERE id = ?";

    if ($stmt = $mysqli->prepare($query)) {
        $stmt->bind_param(
            "ssssssssssssssssssssssssssssssi", // 30 s + 1 i (id) = 31 -- verificado
            $pos_id, $sales_executive, $channel, $subchannel, $reabrev,
            $format, $pos_name_dpsm, $kam, $merchandising, $customer_owner,
            $pos_name, $dpsm, $region, $tipo, $province, $city, $zone,
            $address, $supervisor, $latitud, $longitud, $channel_segment,
            $visual, $coordinador, $foto, $status, $perimetro, $distancia,
            $color, $tiempo_visita,
            $id
        );

        try {
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    echo json_encode(['success' => true, 'message' => 'Registro actualizado correctamente']);
                } else {
                    // affected_rows = 0 significa que se guardó sin cambios
                    // (los valores eran idénticos). Eso NO es un error.
                    echo json_encode(['success' => true, 'message' => 'Guardado. No hubo cambios respecto a los datos actuales.']);
                }
            } else {
                if ($stmt->errno === 1062 || $mysqli->errno === 1062) {
                    echo json_encode(['success' => false, 'message' => 'Otro registro ya usa ese POS ID. El POS ID debe ser único.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta: ' . $stmt->error]);
                }
            }
        } catch (\mysqli_sql_exception $e) {
            if ($e->getCode() === 1062) {
                echo json_encode(['success' => false, 'message' => 'Otro registro ya usa ese POS ID. El POS ID debe ser único.']);
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