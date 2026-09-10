<?php
	include_once '../includes/db_connect.php';
	include_once '../includes/functions.php';
	include_once '../includes/config.php';
	
	sec_session_start();
	
    $query = "SELECT DISTINCT channel FROM repositorio_locales_dtt2 WHERE activar='SI' ORDER BY channel";
	
    $contador = 0;
    $html = "<option value=''>Seleccione</option>";
    $tiene_submenu = false;
    if ($sql = $mysqli->prepare($query)) {
        $sql->execute();
        $sql->store_result();
        if ($sql->num_rows > 0) {
            $sql->bind_result($channel) or die($sql->error);
            while($sql->fetch()) {
                $html .= "<option value='".$channel."'>".$channel."</option>";
            }
        }
        $sql->close();
    }
    echo $html;
	
?>