<?php
// Asegúrate de que las rutas a includes/db_connect.php, etc. sean correctas
include_once "../includes/db_connect.php"; 
include_once "../includes/functions.php";
include_once "../includes/config.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    // Obtener y limpiar todos los 12 parámetros
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

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID invalido']);
        exit;
    }
    
    $query = "UPDATE tb_productos SET 
              pais = ?, 
              fabricante = ?, 
              categoria = ?, 
              subcategoria = ?, 
              abreviatura_subcategoria = ?, 
              marca = ?, 
              codigo_ean_pais = ?, 
              familia_segmento = ?, 
              descripcion_producto = ?, 
              propio_competencia = ?, 
              descripcion_producto_homologado = ?, 
              gramaje_tamanio = ? 
              WHERE id = ?"; // 12 campos a actualizar + 1 WHERE id
    
    if ($stmt = $mysqli->prepare($query)) {
        // Tipos: 6s, 1i, 5s + 1i (para el ID) => ssssssississ
        $stmt->bind_param(
        "ssssssisssssi", // ¡13 caracteres!
        $pais, 
        $fabricante, 
        $categoria, 
        $subcategoria, 
        $abreviatura_subcategoria, 
        $marca, 
        $codigo_ean_pais, // i
        $familia_segmento, 
        $descripcion_producto, 
        $propio_competencia, 
        $descripcion_producto_homologado, 
        $gramaje_tamanio,
        $id // i
    );
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Registro actualizado correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se realizaron cambios o el registro no existe']);
            }
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