<?php
// Asegúrate de que las rutas a includes/db_connect.php, etc. sean correctas
include_once "../includes/db_connect.php"; 
include_once "../includes/functions.php";
include_once "../includes/config.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Obtener y limpiar todos los 12 parámetros (excluyendo el id auto_increment)
    $pais = isset($_POST['pais']) ? trim($_POST['pais']) : '';
    $fabricante = isset($_POST['fabricante']) ? trim($_POST['fabricante']) : '';
    $categoria = isset($_POST['categoria']) ? trim($_POST['categoria']) : '';
    $subcategoria = isset($_POST['subcategoria']) ? trim($_POST['subcategoria']) : '';
    $abreviatura_subcategoria = isset($_POST['abreviatura_subcategoria']) ? trim($_POST['abreviatura_subcategoria']) : '';
    $marca = isset($_POST['marca']) ? trim($_POST['marca']) : '';
    $codigo_ean_pais = isset($_POST['codigo_ean_pais']) ? trim($_POST['codigo_ean_pais']) : '';
    $familia_segmento = isset($_POST['familia_segmento']) ? trim($_POST['familia_segmento']) : '';
    $descripcion_producto = isset($_POST['descripcion_producto']) ? trim($_POST['descripcion_producto']) : '';
    $propio_competencia = isset($_POST['propio_competencia']) ? trim($_POST['propio_competencia']) : '';
    $descripcion_producto_homologado = isset($_POST['descripcion_producto_homologado']) ? trim($_POST['descripcion_producto_homologado']) : '';
    $gramaje_tamanio = isset($_POST['gramaje_tamanio']) ? trim($_POST['gramaje_tamanio']) : '';
    
    // Aquí puedes añadir validación si todos los campos son obligatorios
    // (Opcional, depende de las reglas de tu negocio)

    $query = "INSERT INTO tb_productos (
        pais, fabricante, categoria, subcategoria, abreviatura_subcategoria, 
        marca, codigo_ean_pais, familia_segmento, descripcion_producto, 
        propio_competencia, descripcion_producto_homologado, gramaje_tamanio
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"; // 12 placeholders
    
    if ($stmt = $mysqli->prepare($query)) {
        // Tipos: 6 strings, 1 integer (codigo_ean_pais), 5 strings. Usaremos 's' para VARCHAR, 'i' para INT.
        // ssssss i sssss (6s + 1i + 5s = 12 total)
        $stmt->bind_param(
            "ssssssisssss", // 12 tipos de datos
            $pais, 
            $fabricante, 
            $categoria, 
            $subcategoria, 
            $abreviatura_subcategoria, 
            $marca, 
            $codigo_ean_pais, // Tipo INT
            $familia_segmento, 
            $descripcion_producto, 
            $propio_competencia, 
            $descripcion_producto_homologado, 
            $gramaje_tamanio
        );
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Producto insertado correctamente', 'id' => $stmt->insert_id]);
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