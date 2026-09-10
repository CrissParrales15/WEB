<?php
// actions/update_pdv.php

include_once "../includes/db_connect.php"; 
include_once "../includes/functions.php";
include_once "../includes/config.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // CAMBIO CLAVE: Esperar 'edit_id'
    $id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : 0;
    
    // CAMBIO CLAVE: Esperar 'edit_day', 'edit_cp_code', etc.
    $day = isset($_POST['edit_day']) ? trim($_POST['edit_day']) : '';
    $cp_code = isset($_POST['edit_cp_code']) ? trim($_POST['edit_cp_code']) : '';
    $customer_code = isset($_POST['edit_customer_code']) ? trim($_POST['edit_customer_code']) : '';
    $trade = isset($_POST['edit_trade']) ? trim($_POST['edit_trade']) : '';
    $retail_enviroment = isset($_POST['edit_retail_enviroment']) ? trim($_POST['edit_retail_enviroment']) : '';
    $re = isset($_POST['edit_re']) ? trim($_POST['edit_re']) : '';
    $cp_format = isset($_POST['edit_cp_format']) ? trim($_POST['edit_cp_format']) : '';
    $customer_format_banner = isset($_POST['edit_customer_format_banner']) ? trim($_POST['edit_customer_format_banner']) : '';
    $target = isset($_POST['edit_target']) ? trim($_POST['edit_target']) : '';
    $distribuidor = isset($_POST['edit_distribuidor']) ? trim($_POST['edit_distribuidor']) : '';
    $customer = isset($_POST['edit_customer']) ? trim($_POST['edit_customer']) : '';
    $pos = isset($_POST['edit_pos']) ? trim($_POST['edit_pos']) : '';
    $ruta = isset($_POST['edit_ruta']) ? trim($_POST['edit_ruta']) : '';
    $region = isset($_POST['edit_region']) ? trim($_POST['edit_region']) : '';
    $territory = isset($_POST['edit_territory']) ? trim($_POST['edit_territory']) : '';
    $province = isset($_POST['edit_province']) ? trim($_POST['edit_province']) : '';
    $city = isset($_POST['edit_city']) ? trim($_POST['edit_city']) : '';
    $zone = isset($_POST['edit_zone']) ? trim($_POST['edit_zone']) : '';
    $address = isset($_POST['edit_address']) ? trim($_POST['edit_address']) : '';
    $supervisor = isset($_POST['edit_supervisor']) ? trim($_POST['edit_supervisor']) : '';
    $merchandiser = isset($_POST['edit_merchandiser']) ? trim($_POST['edit_merchandiser']) : '';
    $user = isset($_POST['edit_user']) ? trim($_POST['edit_user']) : '';
    $x = isset($_POST['edit_x']) ? trim($_POST['edit_x']) : ''; // Coordenada X (string/float)
    $y = isset($_POST['edit_y']) ? trim($_POST['edit_y']) : ''; // Coordenada Y (string/float)
    $sob = isset($_POST['edit_sob']) ? floatval($_POST['edit_sob']) : 0.0; // Decimal/Double
    $visual_access = isset($_POST['edit_visual_access']) ? trim($_POST['edit_visual_access']) : '';

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID invalido']);
        exit;
    }
    
    $query = "UPDATE tb_pdv SET 
        day = ?, cp_code = ?, customer_code = ?, trade = ?, retail_enviroment = ?, 
        re = ?, cp_format = ?, customer_format_banner = ?, target = ?, 
        distribuidor = ?, customer = ?, pos = ?, ruta = ?, region = ?, 
        territory = ?, province = ?, city = ?, zone = ?, address = ?, 
        supervisor = ?, merchandiser = ?, user = ?, x = ?, y = ?, 
        sob = ?, visual_access = ? 
        WHERE id = ?"; // 26 campos a actualizar + 1 WHERE id
    
    if ($stmt = $mysqli->prepare($query)) {
        // Cadena de tipos: 24s, 1d, 1s, 1i (27 total)
        $stmt->bind_param(
            "ssssssssssssssssssssssssdsi", // ¡27 caracteres!
            $day, 
            $cp_code, 
            $customer_code, 
            $trade, 
            $retail_enviroment, 
            $re, 
            $cp_format, 
            $customer_format_banner, 
            $target, 
            $distribuidor, 
            $customer, 
            $pos, 
            $ruta, 
            $region, 
            $territory, 
            $province, 
            $city, 
            $zone, 
            $address, 
            $supervisor, 
            $merchandiser, 
            $user, 
            $x, 
            $y, 
            $sob, // Tipo 'd' (decimal/double)
            $visual_access,
            $id // ID al final
        );
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Registro PDV actualizado correctamente']);
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