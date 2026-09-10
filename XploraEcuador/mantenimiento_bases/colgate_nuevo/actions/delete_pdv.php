<?php
// actions/delete_pdv.php

// 1. Aumentar límites para soportar archivos masivos
ini_set('memory_limit', '1024M'); // 1GB de RAM para procesar el Excel grande
set_time_limit(600);             // 10 minutos máximo de ejecución

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
    
    // Filtramos para asegurar que sean IDs válidos y numéricos
    $ids = array_filter($ids, function($id) {
        return is_numeric($id) && $id > 0;
    });
    
    if (empty($ids)) {
        echo json_encode(['success' => false, 'message' => 'Los IDs proporcionados no son válidos.']);
        exit;
    }

    // --- MEJORA CLAVE: PROCESAMIENTO POR LOTES ---
    // Dividimos los 650,000 IDs en paquetes de 5,000 para no saturar SQL
    $lotes = array_chunk($ids, 5000);
    $totalActualizados = 0;
    $huboError = false;
    $mensajeError = "";

    // Iniciamos una transacción para mayor velocidad y seguridad
    $mysqli->begin_transaction();

    try {
        foreach ($lotes as $lote) {
            // Creamos placeholders (?, ?, ...) para el lote actual
            $placeholders = implode(',', array_fill(0, count($lote), '?'));
            $query = "UPDATE tb_pdv SET status = 0 WHERE id IN ($placeholders) AND status = 1";
            
            if ($stmt = $mysqli->prepare($query)) {
                $types = str_repeat('i', count($lote));
                $stmt->bind_param($types, ...$lote);
                
                if ($stmt->execute()) {
                    $totalActualizados += $stmt->affected_rows;
                } else {
                    throw new Exception($stmt->error);
                }
                $stmt->close();
            } else {
                throw new Exception($mysqli->error);
            }
        }

        // Si todo salió bien, guardamos cambios
        $mysqli->commit();

        if ($totalActualizados > 0) {
            echo json_encode([
                'success' => true, 
                'message' => "Operación masiva exitosa. Se han marcado $totalActualizados registro(s) como eliminados.",
                'actualizados' => $totalActualizados
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'No se realizaron cambios. Es posible que los registros ya estuvieran eliminados o los IDs no existan.'
            ]);
        }

    } catch (Exception $e) {
        // Si algo falla, revertimos todo para no dejar la base de datos inconsistente
        $mysqli->rollback();
        echo json_encode([
            'success' => false, 
            'message' => 'Error durante el procesamiento masivo: ' . $e->getMessage()
        ]);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}

$mysqli->close();
?>