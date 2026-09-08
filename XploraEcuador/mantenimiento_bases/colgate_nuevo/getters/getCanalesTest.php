<?php
include_once "../includes/db_connect.php";
include_once "../includes/functions.php";
include_once "../includes/config.php";

sec_session_start();

// ========== SIN FILTRO ==========
$query = "SELECT DISTINCT channel FROM repositorio_locales_dtt2 
          WHERE channel IS NOT NULL AND channel != '' 
            AND channel NOT LIKE '%PRUEBA%' 
          ORDER BY channel";

$html = "<option value=''>Seleccione</option>";
$html .= "<option value='TODOS'>TODOS</option>";

if ($sql = $mysqli->prepare($query)) {
    $sql->execute();
    $sql->store_result();
    if ($sql->num_rows > 0) {
        $sql->bind_result($channel) or die($sql->error);
        while ($sql->fetch()) {
            $html .= "<option value='".$channel."'>".$channel."</option>";
        }
    }
    $sql->close();
}
echo $html;
?>