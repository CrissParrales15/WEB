<?php
// actions/delete_repositorio_ppts.php

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

    $ids = array_filter($ids, function($id) {
        return is_numeric($id) && $id > 0;
    });

    if (empty($ids)) {
        echo json_encode(['success' => false, 'message' => 'IDs inválidos.']);
        exit;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $query = "UPDATE repositorio_ppts SET status = 0 WHERE id IN ($placeholders)";

    if ($stmt = $mysqli->prepare($query)) {
        $types = str_repeat('i', count($ids));
        $stmt->bind_param($types, ...$ids);

        if ($stmt->execute()) {
            $actualizados = $stmt->affected_rows;

            if ($actualizados > 0) {
                echo json_encode([
                    'success'      => true,
                    'message'      => "Se han marcado $actualizados registro(s) como eliminados correctamente.",
                    'actualizados' => $actualizados
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'No se encontraron registros activos con esos IDs.'
                ]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al ejecutar la actualización: ' . $stmt->error]);
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