<?php
// Asegúrate de que las rutas a includes/db_connect.php, etc. sean correctas
include_once "../includes/db_connect.php"; 
include_once "../includes/functions.php";
include_once "../includes/config.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Obtener y limpiar los 9 parámetros (excluyendo el id auto_increment)
    $day = isset($_POST['day']) ? trim($_POST['day']) : '';
    $time = isset($_POST['time']) ? trim($_POST['time']) : '';
    $cp_code = isset($_POST['cp_code']) ? trim($_POST['cp_code']) : '';
    $manufacturer = isset($_POST['manufacturer']) ? trim($_POST['manufacturer']) : '';
    $subcategory = isset($_POST['subcategory']) ? trim($_POST['subcategory']) : '';
    $total_cms_hooks = isset($_POST['total_cms_hooks']) ? $_POST['total_cms_hooks'] : 0; // INT
    $cms_individual_hooks = isset($_POST['cms_individual_hooks']) ? $_POST['cms_individual_hooks'] : 0; // INT
    $sos = isset($_POST['sos']) ? $_POST['sos'] : 0.00; // DECIMAL (15,2)
    $server_date = isset($_POST['server_date']) ? trim($_POST['server_date']) : ''; // DATETIME

    // Preparar el SOS para la base de datos (si es necesario un formato específico)
    // Para MySQL/mysqli, generalmente se espera un string o float
    $sos = (float) str_replace(',', '.', $sos); // Asegura formato con punto decimal

    $query = "INSERT INTO tb_sos_5ps (
        day, time, cp_code, manufacturer, subcategory, total_cms_hooks, 
        cms_individual_hooks, sos, server_date
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"; // 9 placeholders
    
    if ($stmt = $mysqli->prepare($query)) {
        // Tipos de datos: 
        // day (s - DATE), time (s - TIME), cp_code (s - VARCHAR), 
        // manufacturer (s - VARCHAR), subcategory (s - VARCHAR), 
        // total_cms_hooks (i - INT), cms_individual_hooks (i - INT), 
        // sos (d - DECIMAL/FLOAT), server_date (s - DATETIME)
        $stmt->bind_param(
            "sssssiids", // s, s, s, s, s, i, i, d, s (9 total)
            $day, 
            $time, 
            $cp_code, 
            $manufacturer, 
            $subcategory, 
            $total_cms_hooks, 
            $cms_individual_hooks, 
            $sos, 
            $server_date
        );
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Registro SOS_5PS insertado correctamente', 'id' => $stmt->insert_id]);
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