<?php
include_once "../includes/db_connect.php";
include_once "../includes/functions.php";

header('Content-Type: application/json');

$re = isset($_REQUEST['re']) ? $_REQUEST['re'] : '';
$rol = isset($_REQUEST['rol']) ? $_REQUEST['rol'] : 'USUARIO';

if (empty($re)) {
    echo json_encode([]);
    exit;
}

$query = "SELECT modulo, nombre_modulo 
          FROM repositorio_modulos_por_re 
          WHERE re = ? AND rol = ? 
          AND modulo NOT IN ('CONVENIOS_PENDIENTES', 'GESTION_VISITA', 'ALMUERZO')
          ORDER BY id";

$modulos = [];

if ($stmt = $mysqli->prepare($query)) {
    $stmt->bind_param("ss", $re, $rol);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $modulos[] = [
            'modulo' => $row['modulo'],
            'nombre_modulo' => $row['nombre_modulo']
        ];
    }
    
    $stmt->close();
}

echo json_encode($modulos);
?>