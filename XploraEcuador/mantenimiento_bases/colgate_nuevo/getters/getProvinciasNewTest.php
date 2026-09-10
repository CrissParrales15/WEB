<?php
include_once "../includes/db_connect.php";
include_once "../includes/functions.php";
include_once "../includes/config.php";

sec_session_start();

$canal = $_GET["canal"];
$cadena = $_GET["cadena"];

// ========== FILTROS DINÁMICOS ==========
$concat_canal = "";
if ($canal != "TODOS" && !empty($canal) && $canal != "") {
    $concat_canal = " AND channel = '$canal' ";
}

$concat_cadena = "";
if ($cadena != "TODOS" && !empty($cadena) && $cadena != "") {
    $concat_cadena = " AND subchannel = '$cadena' ";
}

$query = "SELECT DISTINCT province FROM repositorio_locales_dtt2 
          WHERE province IS NOT NULL AND province != '' 
            AND province NOT LIKE '%PRUEBA%' 
            AND province NOT LIKE '%N/D%'
            $concat_canal
            $concat_cadena
          ORDER BY province";

$html = "<option value=''>Seleccione</option>";
$html .= "<option value='TODOS'>TODOS</option>";

if ($sql = $mysqli->prepare($query)) {
    $sql->execute();
    $sql->store_result();
    if ($sql->num_rows > 0) {
        $sql->bind_result($province) or die($sql->error);
        while ($sql->fetch()) {
            $html .= "<option value='".$province."'>".$province."</option>";
        }
    }
    $sql->close();
}
echo $html;
?>