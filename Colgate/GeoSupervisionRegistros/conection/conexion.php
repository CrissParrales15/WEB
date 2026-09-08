<?php 

$conn = new mysqli("mysqlecuadorsf.mysql.database.azure.com", "xplora_mysql", "XpL0r@Ec8Ad0R..", "luckyec_5pgo");

if ($conn->connect_error) {
    die("ERROR: No se puede conectar al servidor: " . $conn->connect_error);
}



?>