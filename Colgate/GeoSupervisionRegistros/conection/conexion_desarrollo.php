<?php 

$conn = new mysqli("localhost", "root", "", "base");

if ($conn->connect_error) {
    die("ERROR: No se puede conectar al servidor: " . $conn->connect_error);
}



?>