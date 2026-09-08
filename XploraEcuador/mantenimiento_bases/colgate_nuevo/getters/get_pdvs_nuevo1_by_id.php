<?php
// getters/get_locales_dtt2_by_id.php

include_once "../includes/db_connect.php";

header('Content-Type: application/json; charset=UTF-8');

$mysqli->set_charset("utf8mb4");

$id = $_POST['id'] ?? null;

if (empty($id) || !is_numeric($id)) {
    echo json_encode(['status' => 'error', 'message' => 'ID no válido.']);
    exit;
}

$query = "SELECT 
    id, pos_id, sales_executive, channel, subchannel, reabrev, format,
    pos_name_dpsm, kam, merchandising, customer_owner, pos_name, dpsm,
    region, tipo, province, city, zone, address, supervisor, latitud,
    longitud, channel_segment, visual, coordinador, foto, status,
    perimetro, distancia, activar, color, tiempo_visita
    FROM repositorio_locales_dtt2
    WHERE id = ? AND activar = 'SI'";

if ($stmt = $mysqli->prepare($query)) {
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $data = $result->fetch_assoc();
        echo json_encode(['status' => 'success', 'data' => $data]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Registro no encontrado.']);
    }

    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error en la consulta: ' . $mysqli->error]);
}

$mysqli->close();
?>