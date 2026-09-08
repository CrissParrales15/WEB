<?php
include_once "../includes/db_connect.php";

header('Content-Type: application/json; charset=UTF-8');

$mysqli->set_charset("utf8mb4");

$id = $_POST['id'] ?? null;

if (empty($id) || !is_numeric($id)) {
    echo json_encode(['status' => 'error', 'message' => 'ID no válido.']);
    exit;
}

$query = "SELECT 
    id, day, time, cp_code, manufacturer, subcategory,
    total_cms_hooks, cms_individual_hooks, sos, server_date
    FROM tb_sos_5ps
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