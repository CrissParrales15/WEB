<?php
// getters/get_repositorio_ppts_by_id.php

include_once "../includes/db_connect.php";

header('Content-Type: application/json; charset=UTF-8');

$mysqli->set_charset("utf8mb4");

$id = $_POST['id'] ?? null;

if (empty($id) || !is_numeric($id)) {
    echo json_encode(['status' => 'error', 'message' => 'ID no válido.']);
    exit;
}

$query = "SELECT 
    id, day, time, cp_code, trade, retail_environment, cp_format,
    customer_format_banner, distribuidor, customer, pos, province, city,
    zone, ejecutivo, category, subcategory, segment, form, manufacturer,
    brand, product, size, validation, activity, type_of_promotion,
    descuento, price_talker, mechanics, sale_price, observation,
    photo_url, period, inicio_de_promocion, fin_de_promocion,
    agotar_stock, fuente
    FROM repositorio_ppts
    WHERE id = ? AND status = 1";

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