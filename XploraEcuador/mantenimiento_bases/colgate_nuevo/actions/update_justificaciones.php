<?php
// actions/update_justificaciones.php

include_once "../includes/db_connect.php"; 
include_once "../includes/functions.php";
include_once "../includes/config.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ID del registro a actualizar
    $id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : 0;
    
    // Campos de tb_justificaciones
    $fecha = isset($_POST['edit_fecha']) ? trim($_POST['edit_fecha']) : '';
    $codigo_pdv = isset($_POST['edit_codigo_pdv']) ? trim($_POST['edit_codigo_pdv']) : '';
    $id_usuario = isset($_POST['edit_id_usuario']) ? intval($_POST['edit_id_usuario']) : 0;
    $xplorer = isset($_POST['edit_xplorer']) ? trim($_POST['edit_xplorer']) : '';
    $hora_entrada = isset($_POST['edit_hora_entrada']) ? trim($_POST['edit_hora_entrada']) : '';
    $hora_salida = isset($_POST['edit_hora_salida']) ? trim($_POST['edit_hora_salida']) : '';
    $tipo_justificacion = isset($_POST['edit_tipo_justificacion']) ? trim($_POST['edit_tipo_justificacion']) : '';
    $observacion = isset($_POST['edit_observacion']) ? trim($_POST['edit_observacion']) : '';
    $status = isset($_POST['edit_status']) ? intval($_POST['edit_status']) : 1;

    // Validación básica
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        exit;
    }

    if (empty($fecha)) {
        echo json_encode(['success' => false, 'message' => 'La fecha es obligatoria']);
        exit;
    }

    // Query de actualización
    $query = "UPDATE tb_justificaciones SET 
        fecha = ?,
        codigo_pdv = ?,
        id_usuario = ?,
        xplorer = ?,
        hora_entrada = ?,
        hora_salida = ?,
        tipo_justificacion = ?,
        observacion = ?,
        status = ?
        WHERE id = ?";
    
    if ($stmt = $mysqli->prepare($query)) {
        // Tipos: s=string, i=integer
        // 8 strings + 2 integers = 10 parámetros
        $stmt->bind_param(
            "ssisssssii",  // fecha(s), codigo_pdv(s), id_usuario(i), xplorer(s), hora_entrada(s), hora_salida(s), tipo_justificacion(s), observacion(s), status(i), id(i)
            $fecha,
            $codigo_pdv,
            $id_usuario,
            $xplorer,
            $hora_entrada,
            $hora_salida,
            $tipo_justificacion,
            $observacion,
            $status,
            $id
        );
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Justificación actualizada correctamente'
                ]);
            } else {
                echo json_encode([
                    'success' => false, 
                    'message' => 'No se realizaron cambios o el registro no existe'
                ]);
            }
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