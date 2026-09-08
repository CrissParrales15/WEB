<?php
// Asegúrate de que las rutas a includes/db_connect.php, etc. sean correctas
include_once "../includes/db_connect.php"; 
include_once "../includes/functions.php";
include_once "../includes/config.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    // Obtener y limpiar todos los 7 parámetros de texto/fecha y 1 decimal
    $fecha = isset($_POST['fecha']) ? trim($_POST['fecha']) : '';
    $canal = isset($_POST['canal']) ? trim($_POST['canal']) : '';
    $retail_enviroment = isset($_POST['retail_enviroment']) ? trim($_POST['retail_enviroment']) : '';
    $cliente = isset($_POST['cliente']) ? trim($_POST['cliente']) : '';
    $visual_access = isset($_POST['visual_access']) ? trim($_POST['visual_access']) : '';
    $subcategory = isset($_POST['subcategory']) ? trim($_POST['subcategory']) : '';
    
    // El objetivo es un DECIMAL
    $new_objetivo = isset($_POST['new_objetivo']) ? floatval(str_replace(',', '.', trim($_POST['new_objetivo']))) : 0.0;

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID invalido']);
        exit;
    }
    
    $query = "UPDATE tb_objetivo_sos SET 
              fecha = ?, 
              canal = ?, 
              retail_enviroment = ?, 
              cliente = ?, 
              visual_access = ?, 
              subcategory = ?, 
              new_objetivo = ? 
              WHERE id = ?"; // 7 campos a actualizar + 1 WHERE id

    if ($stmt = $mysqli->prepare($query)) {
        // Tipos: 6s, 1d (new_objetivo) + 1i (para el ID) => ssssssdi
        $stmt->bind_param(
            "ssssssdi", // ¡8 tipos de datos!
            $fecha, 
            $canal, 
            $retail_enviroment, 
            $cliente, 
            $visual_access, 
            $subcategory, 
            $new_objetivo, // d
            $id // i
        );
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Registro actualizado correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se realizaron cambios o el registro no existe']);
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