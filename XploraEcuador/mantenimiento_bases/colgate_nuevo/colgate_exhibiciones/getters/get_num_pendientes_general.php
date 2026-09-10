<?php
header('Content-Type: text/plain');
include_once "../includes/db_connect.php";

// Obtener parámetros de la solicitud
$supervisor = $_GET['supervisor'];

// Preparar y ejecutar la consulta
$query = "SELECT COALESCE(SUM(pendientes), 0) as numPendientes FROM lvi_exhibiciones_colgate WHERE supervisor = ?";
if ($stmt = $mysqli->prepare($query)) {
    $stmt->bind_param('s', $supervisor);
    $stmt->execute();
    $stmt->bind_result($numPendientes);
    $stmt->fetch();

    // Enviar respuesta en formato de texto plano
    echo $numPendientes;
    $stmt->close();
} else {
    echo 'Error al preparar la consulta.';
}

$mysqli->close();
?>