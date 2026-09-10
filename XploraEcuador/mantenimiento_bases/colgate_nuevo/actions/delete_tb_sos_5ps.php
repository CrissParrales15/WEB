<?php
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
    
    // Filtra y valida que los IDs sean números positivos
    $ids = array_filter($ids, function($id) {
        return is_numeric($id) && $id > 0;
    });
    
    if (empty($ids)) {
        echo json_encode(['success' => false, 'message' => 'IDs inválidos.']);
        exit;
    }
    
    // Crea la lista de placeholders (?) para la cláusula IN
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    // 🎯 CAMBIO CLAVE: Cambiar DELETE a UPDATE y establecer 'estado' = 0
    $query = "UPDATE tb_sos_5ps SET status = 0 WHERE id IN ($placeholders)"; 
    
    if ($stmt = $mysqli->prepare($query)) {
        // Tipos: 'i' por cada ID
        $types = str_repeat('i', count($ids));
        
        // El operador '...' desempaqueta el array $ids como argumentos de la función
        // Esto funciona con PHP 5.6+
        $stmt->bind_param($types, ...$ids);
        
        if ($stmt->execute()) {
            $actualizados = $stmt->affected_rows; // Renombrado a 'actualizados'
            
            if ($actualizados > 0) {
                echo json_encode([
                    'success' => true, 
                    // Mensaje actualizado
                    'message' => "Se han marcado $actualizados registro(s) como eliminados (Estado 0) correctamente.", 
                    'actualizados' => $actualizados
                ]);
            } else {
                echo json_encode([
                    'success' => false, 
                    'message' => 'No se encontraron registros activos con esos IDs para marcar como eliminados.'
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