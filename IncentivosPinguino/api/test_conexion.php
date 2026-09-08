<?php
// 1. Configurar las cabeceras para indicar que la respuesta es JSON
header('Content-Type: application/json; charset=utf-8');

// Opcional: Permitir peticiones desde otros orígenes si luego separas el frontend
// header('Access-Control-Allow-Origin: *'); 

// 2. Importar la clase DataSource
// Subimos un nivel en los directorios (..) para entrar a core/
require_once '../core/DataSource.php';

use Phppot\DataSource;

try {
    // 3. Instanciar la clase (esto dispara el constructor y la función getConnection)
    $db = new DataSource();
    
    // 4. Ejecutar una consulta inofensiva para confirmar que todo opera bien
    // "SELECT 1" es el estándar en SQL para probar conectividad
    $resultado = $db->select("SELECT 1 AS test_val");
    
    // 5. Validar el resultado
    if (!empty($resultado) && $resultado[0]['test_val'] == 1) {
        // HTTP 200 OK
        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "message" => "Conexión a la base de datos establecida correctamente.",
            "data" => $resultado
        ]);
    } else {
        // HTTP 500 Internal Server Error
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Conexión exitosa, pero la consulta de prueba falló."
        ]);
    }

} catch (\Exception $e) {
    // Si la base de datos está caída o las credenciales de Config.php están mal,
    // el bloque catch atrapará la excepción para que la página no "explote" con un error de PHP.
    
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Fallo de conexión o de ejecución.",
        "detalle" => $e->getMessage() // Útil para depurar en desarrollo
    ]);
}