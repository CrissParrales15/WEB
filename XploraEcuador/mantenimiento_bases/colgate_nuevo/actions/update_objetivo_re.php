<?php
include_once "../includes/db_connect.php"; 
include_once "../includes/functions.php";
include_once "../includes/config.php";

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : 0;
    $fecha = isset($_POST['edit_fecha']) ? trim($_POST['edit_fecha']) : '';
    $codigo_pdv = isset($_POST['edit_codigo_pdv']) ? trim($_POST['edit_codigo_pdv']) : '';
    $objetivo = isset($_POST['edit_objetivo']) ? intval($_POST['edit_objetivo']) : 0;
    $status = isset($_POST['edit_status']) ? intval($_POST['edit_status']) : 1;

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        exit;
    }

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

    $query = "UPDATE tb_objetivo_re SET fecha = ?, codigo_pdv = ?, objetivo = ?, status = ? WHERE id = ?";
    
    if ($stmt = $mysqli->prepare($query)) {
        $stmt->bind_param("ssiii", $fecha, $codigo_pdv, $objetivo, $status, $id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Objetivo RE actualizado correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se realizaron cambios']);
            }
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