<?php
include_once "../includes/db_connect.php";
include_once "../includes/functions.php";
include_once "../includes/config.php";

header('Content-Type: application/json');

$usuario = isset($_GET['usuario']) ? $_GET['usuario'] : '';

if (empty($usuario)) {
    echo json_encode(['error' => 'Usuario no especificado']);
    exit;
}

// Buscar el supervisor en la tabla repositorio_usuarios
$query = "
    SELECT id, CONCAT(nombre, ' ', apellido) AS nombre_completo, mercaderista, usuario
    FROM repositorio_usuarios
    WHERE usuario = ?
    AND id_rol IN (/*1,*/4)
    AND status = 1
    AND activo = 1
";

$response = [
    'success' => false,
    'supervisor_id' => null,
    'nombre' => null,
    'usuario' => $usuario
];

if ($stmt = $mysqli->prepare($query)) {
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $response['success'] = true;
        $response['supervisor_id'] = $row['id'];
        $response['nombre'] = $row['nombre_completo'] ?: $row['mercaderista'];
        $response['usuario'] = $row['usuario'];
    }
    $stmt->close();
}

echo json_encode($response);
?>