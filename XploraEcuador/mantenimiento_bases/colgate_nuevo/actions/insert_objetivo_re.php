<?php
include_once "../includes/db_connect.php"; 
include_once "../includes/functions.php";
include_once "../includes/config.php";

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $fecha = isset($_POST['add_fecha']) ? trim($_POST['add_fecha']) : '';
    $codigo_pdv = isset($_POST['add_codigo_pdv']) ? trim($_POST['add_codigo_pdv']) : '';
    $objetivo = isset($_POST['add_objetivo']) ? intval($_POST['add_objetivo']) : 0;
    $status = 1;
    
    if (empty($fecha)) {
        echo json_encode(['success' => false, 'message' => 'La fecha es obligatoria']);
        exit;
    }
    
    if (empty($codigo_pdv)) {
        echo json_encode(['success' => false, 'message' => 'El código PDV es obligatorio']);
        exit;
    }
    
    if ($objetivo <= 0) {
        echo json_encode(['success' => false, 'message' => 'El objetivo debe ser mayor a 0']);
        exit;
    }
    
    $query = "INSERT INTO tb_objetivo_re (fecha, codigo_pdv, objetivo, status) VALUES (?, ?, ?, ?)";
    
    if ($stmt = $mysqli->prepare($query)) {
        $stmt->bind_param("ssii", $fecha, $codigo_pdv, $objetivo, $status);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Objetivo RE insertado correctamente', 'id' => $stmt->insert_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta: ' . $stmt->error]);
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