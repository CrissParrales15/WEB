<?php
// getters/get_pdv_by_id.php

include_once "../includes/db_connect.php"; // Asegúrate de que esta ruta sea correcta

header('Content-Type: application/json');

$id = $_POST['id'] ?? null;

if (empty($id) || !is_numeric($id)) {
    echo json_encode(['status' => 'error', 'message' => 'ID de registro no válido.']);
    exit;
}

// 1. Consulta SQL para obtener todos los campos de un solo registro
$query = "SELECT 
    id, day, cp_code, customer_code, trade, retail_enviroment, re, 
    cp_format, customer_format_banner, target, distribuidor, customer, 
    pos, ruta, region, territory, province, city, zone, address, 
    supervisor, merchandiser, user, x, y, sob, visual_access, fecha_modificacion 
    FROM tb_pdv
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
    echo json_encode(['status' => 'error', 'message' => 'Error en la preparación de la consulta: ' . $mysqli->error]);
}

$mysqli->close();
?>