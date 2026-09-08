<?php
// Asegúrate de que las rutas a includes/db_connect.php, etc. sean correctas
include_once "../includes/db_connect.php"; 
include_once "../includes/functions.php";
include_once "../includes/config.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    // Obtener y limpiar los 9 parámetros
    $day = isset($_POST['day']) ? trim($_POST['day']) : '';
    $time = isset($_POST['time']) ? trim($_POST['time']) : '';
    $cp_code = isset($_POST['cp_code']) ? trim($_POST['cp_code']) : '';
    $manufacturer = isset($_POST['manufacturer']) ? trim($_POST['manufacturer']) : '';
    $subcategory = isset($_POST['subcategory']) ? trim($_POST['subcategory']) : '';
    $total_cms_hooks = isset($_POST['total_cms_hooks']) ? $_POST['total_cms_hooks'] : 0; // INT
    $cms_individual_hooks = isset($_POST['cms_individual_hooks']) ? $_POST['cms_individual_hooks'] : 0; // INT
    $sos = isset($_POST['sos']) ? $_POST['sos'] : 0.00; // DECIMAL (15,2)
    $server_date = isset($_POST['server_date']) ? trim($_POST['server_date']) : ''; // DATETIME

    // Preparar el SOS para la base de datos
    $sos = (float) str_replace(',', '.', $sos);

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID invalido']);
        exit;
    }
    
    $query = "UPDATE tb_sos_5ps SET 
              day = ?, 
              time = ?, 
              cp_code = ?, 
              manufacturer = ?, 
              subcategory = ?, 
              total_cms_hooks = ?, 
              cms_individual_hooks = ?, 
              sos = ?, 
              server_date = ? 
              WHERE id = ?"; // 9 campos a actualizar + 1 WHERE id
    
    if ($stmt = $mysqli->prepare($query)) {
        // Tipos: 5s, 2i, 1d, 1s + 1i (para el ID) => sssssiidsi
        $stmt->bind_param(
        "sssssiidsi", // ¡10 caracteres!
        $day, 
        $time, 
        $cp_code, 
        $manufacturer, 
        $subcategory, 
        $total_cms_hooks, // i
        $cms_individual_hooks, // i
        $sos, // d
        $server_date, // s
        $id // i
    );
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Registro SOS_5PS actualizado correctamente']);
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