<?php
ini_set('memory_limit', '1024M');
set_time_limit(600);

include_once "../includes/db_connect.php"; 
include_once "../includes/functions.php";
include_once "../includes/config.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $ids = isset($_POST['ids']) ? $_POST['ids'] : [];
    
    if (empty($ids) || !is_array($ids)) {
        echo json_encode(['success' => false, 'message' => 'No se recibieron IDs.']);
        exit;
    }
    
    $ids = array_filter($ids, function($id) {
        return is_numeric($id) && $id > 0;
    });
    
    if (empty($ids)) {
        echo json_encode(['success' => false, 'message' => 'IDs no válidos.']);
        exit;
    }

    $lotes = array_chunk($ids, 5000);
    $totalActualizados = 0;

    $mysqli->begin_transaction();

    try {
        foreach ($lotes as $lote) {
            $placeholders = implode(',', array_fill(0, count($lote), '?'));
            $query = "UPDATE tb_objetivo_re SET status = 0 WHERE id IN ($placeholders) AND status = 1";
            
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

        $mysqli->commit();

        if ($totalActualizados > 0) {
            echo json_encode([
                'success' => true, 
                'message' => "Se marcaron $totalActualizados registro(s) como eliminados.",
                'actualizados' => $totalActualizados
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'No se realizaron cambios.'
            ]);
        }

    } catch (Exception $e) {
        $mysqli->rollback();
        echo json_encode([
            'success' => false, 
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}

$mysqli->close();
?>