<?php
// getters/get_justificaciones_by_id.php

include_once "../includes/db_connect.php"; // Asegúrate de que esta ruta sea correcta

header('Content-Type: application/json; charset=utf-8');

// Forzar UTF-8 para evitar problemas con caracteres especiales
$mysqli->set_charset("utf8mb4");

$id = $_POST['id'] ?? null;

if (empty($id) || !is_numeric($id)) {
    echo json_encode(['status' => 'error', 'message' => 'ID de registro no válido.']);
    exit;
}

// 1. Consulta SQL para obtener todos los campos de un solo registro
$query = "SELECT 
    id, fecha, codigo_pdv, id_usuario, xplorer, 
    hora_entrada, hora_salida, tipo_justificacion, observacion, status
    FROM tb_justificaciones
    WHERE id = ?";

// No filtramos por status para poder editar incluso registros inactivos
// Si quieres solo activos, descomenta: AND status = 1

if ($stmt = $mysqli->prepare($query)) {
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $data = $result->fetch_assoc();
        
        // Formatear horas para mostrar solo HH:MM (sin segundos)
        if (!empty($data['hora_entrada'])) {
            $data['hora_entrada'] = substr($data['hora_entrada'], 0, 5);
        }
        if (!empty($data['hora_salida'])) {
            $data['hora_salida'] = substr($data['hora_salida'], 0, 5);
        }
        
        echo json_encode([
            'status' => 'success', 
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Registro no encontrado.'
        ]);
    }
    
    $stmt->close();
} else {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Error en la preparación de la consulta: ' . $mysqli->error
    ]);
}

$mysqli->close();
?>