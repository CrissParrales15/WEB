<?php
$conn = new mysqli("localhost", "root", "", "coordenadas");

  if ($conn->connect_error) {
    die("ERROR: No se puede conectar al servidor: " . $conn->connect_error);
  }
