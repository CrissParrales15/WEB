<?php
// actions/delete_productos.php

// Asegúrate de que las rutas a includes/db_connect.php, etc. sean correctas
include_once "../includes/db_connect.php"; 
include_once "../includes/functions.php";
include_once "../includes/config.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $ids = isset($_POST['ids']) ? $_POST['ids'] : [];
    
    if (empty($ids) || !is_array($ids)) {
        echo json_encode(['success' => false, 'message' => 'No se recibieron IDs para la operación.']);
        exit;
    }
    
    // Filtramos para asegurar que sean IDs válidos
    $ids = array_filter($ids, function($id) {
        return is_numeric($id) && $id > 0;
    });
    
    if (empty($ids)) {
        echo json_encode(['success' => false, 'message' => 'IDs inválidos.']);
        exit;
    }
    
    // Creamos placeholders (?, ?, ?, ...)
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    // 🎯 CORRECCIÓN: Cambiamos DELETE por UPDATE para Soft Delete, estableciendo 'status' a 0
    $query = "UPDATE tb_productos SET status = 0 WHERE id IN ($placeholders)"; 
    
    if ($stmt = $mysqli->prepare($query)) {
        // Creamos la cadena de tipos dinámicamente (ej: 'iiii')
        $types = str_repeat('i', count($ids));
        
        // El operador ...$ids desempaqueta el array de IDs en argumentos separados
        $stmt->bind_param($types, ...$ids);
        
        if ($stmt->execute()) {
            $actualizados = $stmt->affected_rows; // Renombrado de $eliminados a $actualizados
            
            if ($actualizados > 0) {
                echo json_encode([
                    'success' => true, 
                    // Mensaje actualizado para reflejar Soft Delete
                    'message' => "Se han marcado $actualizados registro(s) de producto como eliminados (status 0) correctamente.",
                    'actualizados' => $actualizados
                ]);
            } else {
                echo json_encode([
                    'success' => false, 
                    'message' => 'No se encontraron registros de producto activos con esos IDs para marcar como eliminados.'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Error al ejecutar la actualización: ' . $stmt->error
            ]);
        }
        
        $stmt->close();
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Error al preparar la consulta de eliminación suave: ' . $mysqli->error
        ]);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}

$mysqli->close();
?>