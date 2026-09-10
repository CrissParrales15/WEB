<?php
// actions/insert_pdv.php

include_once "../includes/db_connect.php"; 
include_once "../includes/functions.php";
include_once "../includes/config.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 26 campos a obtener - CAMBIO CLAVE AQUÍ: Usar el prefijo 'add_'
    $day = isset($_POST['add_day']) ? trim($_POST['add_day']) : '';
    $cp_code = isset($_POST['add_cp_code']) ? trim($_POST['add_cp_code']) : '';
    $customer_code = isset($_POST['add_customer_code']) ? trim($_POST['add_customer_code']) : '';
    $trade = isset($_POST['add_trade']) ? trim($_POST['add_trade']) : '';
    $retail_enviroment = isset($_POST['add_retail_enviroment']) ? trim($_POST['add_retail_enviroment']) : '';
    $re = isset($_POST['add_re']) ? trim($_POST['add_re']) : '';
    $cp_format = isset($_POST['add_cp_format']) ? trim($_POST['add_cp_format']) : '';
    $customer_format_banner = isset($_POST['add_customer_format_banner']) ? trim($_POST['add_customer_format_banner']) : '';
    $target = isset($_POST['add_target']) ? trim($_POST['add_target']) : '';
    $distribuidor = isset($_POST['add_distribuidor']) ? trim($_POST['add_distribuidor']) : '';
    $customer = isset($_POST['add_customer']) ? trim($_POST['add_customer']) : '';
    $pos = isset($_POST['add_pos']) ? trim($_POST['add_pos']) : '';
    $ruta = isset($_POST['add_ruta']) ? trim($_POST['add_ruta']) : '';
    $region = isset($_POST['add_region']) ? trim($_POST['add_region']) : '';
    $territory = isset($_POST['add_territory']) ? trim($_POST['add_territory']) : '';
    $province = isset($_POST['add_province']) ? trim($_POST['add_province']) : '';
    $city = isset($_POST['add_city']) ? trim($_POST['add_city']) : '';
    $zone = isset($_POST['add_zone']) ? trim($_POST['add_zone']) : '';
    $address = isset($_POST['add_address']) ? trim($_POST['add_address']) : '';
    $supervisor = isset($_POST['add_supervisor']) ? trim($_POST['add_supervisor']) : '';
    $merchandiser = isset($_POST['add_merchandiser']) ? trim($_POST['add_merchandiser']) : '';
    $user = isset($_POST['add_user']) ? trim($_POST['add_user']) : '';
    $x = isset($_POST['add_x']) ? trim($_POST['add_x']) : ''; // Coordenada X (string/float)
    $y = isset($_POST['add_y']) ? trim($_POST['add_y']) : ''; // Coordenada Y (string/float)
    $sob = isset($_POST['add_sob']) ? floatval($_POST['add_sob']) : 0.0; // Decimal/Double
    $visual_access = isset($_POST['add_visual_access']) ? trim($_POST['add_visual_access']) : '';
    
    
    $query = "INSERT INTO tb_pdv (
        day, cp_code, customer_code, trade, retail_enviroment, re, cp_format, 
        customer_format_banner, target, distribuidor, customer, pos, ruta, region, 
        territory, province, city, zone, address, supervisor, merchandiser, user, 
        x, y, sob, visual_access
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
    )"; // 26 placeholders
    
    if ($stmt = $mysqli->prepare($query)) {
        // Cadena de tipos: 24s, 1d, 1s (sob es double/decimal)
        $stmt->bind_param(
            "ssssssssssssssssssssssssds", 
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
            $visual_access
        );
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'PDV insertado correctamente', 'id' => $stmt->insert_id]);
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