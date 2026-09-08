<?php
include_once "../includes/db_connect.php";

$query = "SELECT DISTINCT subcategoria FROM tb_productos WHERE status = 1 ORDER BY subcategoria ASC";
$result = $mysqli->query($query);

$subs = [];
while($row = $result->fetch_assoc()){
    $subs[] = $row['subcategoria'];
}

echo json_encode($subs);
