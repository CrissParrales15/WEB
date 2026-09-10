<?php
// actions/insert_justificaciones.php

include_once "../includes/db_connect.php"; 
include_once "../includes/functions.php";
include_once "../includes/config.php";

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Campos de tb_justificaciones - Usando prefijo 'add_'
    $fecha = isset($_POST['add_fecha']) ? trim($_POST['add_fecha']) : '';
    $codigo_pdv = isset($_POST['add_codigo_pdv']) ? trim($_POST['add_codigo_pdv']) : '';
    $id_usuario = isset($_POST['add_id_usuario']) ? intval($_POST['add_id_usuario']) : 0;
    $xplorer = isset($_POST['add_xplorer']) ? trim($_POST['add_xplorer']) : '';
    $hora_entrada = isset($_POST['add_hora_entrada']) ? trim($_POST['add_hora_entrada']) : '';
    $hora_salida = isset($_POST['add_hora_salida']) ? trim($_POST['add_hora_salida']) : '';
    $tipo_justificacion = isset($_POST['add_tipo_justificacion']) ? trim($_POST['add_tipo_justificacion']) : '';
    $observacion = isset($_POST['add_observacion']) ? trim($_POST['add_observacion']) : '';
    
    $status = 1;

    // Validación básica
    if (empty($fecha)) {
        echo json_encode(['success' => false, 'message' => 'La fecha es obligatoria']);
        exit;
    }
    
    if (empty($codigo_pdv)) {
        echo json_encode(['success' => false, 'message' => 'El código PDV es obligatorio']);
        exit;
    }
    
    if ($id_usuario <= 0) {
        echo json_encode(['success' => false, 'message' => 'El ID de usuario es obligatorio y debe ser mayor a 0']);
        exit;
    }
    
    // Query de inserción
    $query = "INSERT INTO tb_justificaciones (
        fecha, codigo_pdv, id_usuario, xplorer, 
        hora_entrada, hora_salida, tipo_justificacion, observacion, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    if ($stmt = $mysqli->prepare($query)) {
        // Tipos: s=string, i=integer
        // 7 strings + 1 integer = 8 parámetros
        $stmt->bind_param(
            "ssisssssi",  // fecha(s), codigo_pdv(s), id_usuario(i), xplorer(s), hora_entrada(s), hora_salida(s), tipo_justificacion(s), observacion(s)
            $fecha,
            $codigo_pdv,
            $id_usuario,
            $xplorer,
            $hora_entrada,
            $hora_salida,
            $tipo_justificacion,
            $observacion,
            $status
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'message' => 'Justificación insertada correctamente', 
                'id' => $stmt->insert_id
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Error al ejecutar la consulta: ' . $stmt->error
            ]);
        }
        
        $stmt->close();
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Error al preparar la consulta: ' . $mysqli->error
        ]);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}

$mysqli->close();
?>