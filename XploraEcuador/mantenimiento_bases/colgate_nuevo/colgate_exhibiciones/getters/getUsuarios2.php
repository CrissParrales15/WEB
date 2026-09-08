<?php
	include_once '../includes/db_connect.php';
	include_once '../includes/functions.php';
	include_once '../includes/config.php';
	
	sec_session_start();

    $codigo_pdv = $_POST['codigo_pdv'];
	
    $query = "SELECT DISTINCT(user) FROM repositorio_locales_dtt WHERE pos_id='" . $codigo_pdv . "' AND activar='SI' ORDER BY user";
	
    $contador = 0;
    $html = "<option value=''>Seleccione</option>";
    $tiene_submenu = false;
    if ($sql = $mysqli->prepare($query)) {
        $sql->execute();
        $sql->store_result();
        if ($sql->num_rows > 0) {
            $sql->bind_result($user) or die($sql->error);
            while($sql->fetch()) {
                $html .= "<option value='".$user."'>".$user."</option>";
            }
        }
        $sql->close();
    }
    echo $html;
	
?>