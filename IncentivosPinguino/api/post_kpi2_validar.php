<?php
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Guayaquil');

require_once '../core/DataSource.php';
use Phppot\DataSource;

try {
    // Recibir los datos enviados por JS en formato JSON
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!isset($data['id_evidencia'])) {
        throw new \Exception("Falta el ID de la evidencia.");
    }

    $id_evidencia = $data['id_evidencia'];
    // Si validado es 1 lo guardamos como 1, si es 0 lo guardamos como NULL
    $estado_validado = (isset($data['validado']) && $data['validado'] == 1) ? 1 : null;

    $db = new DataSource();
    
    $sql = "UPDATE insert_evidencias SET validado = ? WHERE id = ?";
    // 'ii' significa que pasamos un Integer y un Integer (o null)
    $tipos = "ii"; 
    $params = [$estado_validado, $id_evidencia];

    $db->execute($sql, $tipos, $params);

    echo json_encode([
        "status" => "success",
        "message" => "Validación actualizada"
    ]);

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error al guardar validación",
        "detalle" => $e->getMessage()
    ]);
}