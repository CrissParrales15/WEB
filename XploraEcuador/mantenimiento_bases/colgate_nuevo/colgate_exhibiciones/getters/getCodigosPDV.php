<?php
	include_once '../includes/db_connect.php';
	include_once '../includes/functions.php';
	include_once '../includes/config.php';
	
	sec_session_start();

    $canal = $_POST['canal'];
    $tipo = $_POST['tipo'];

    if ($tipo === "new") {
        $add_query = " channel='" . $canal . "' AND ";
    }
     
    $query = "SELECT id, pos_id, pos_name FROM repositorio_locales_dtt2 WHERE " . $add_query . " activar='SI' GROUP BY pos_id ORDER BY pos_id";
	
    $contador = 0;
    $html = "<option value=''>Seleccione</option>";
    $tiene_submenu = false;
    if ($sql = $mysqli->prepare($query)) {
        $sql->execute();
        $sql->store_result();
        if ($sql->num_rows > 0) {
            $sql->bind_result($id, $pos_id, $pos_name) or die($sql->error);
            while($sql->fetch()) {
                $html .= "<option value='".$id."'>".$pos_id." - " . $pos_name . "</option>";
            }
        }
        $sql->close();
    }
    echo $html;
	
?>