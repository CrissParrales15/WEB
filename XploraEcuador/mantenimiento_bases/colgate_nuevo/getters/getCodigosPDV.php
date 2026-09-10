<?php
	include_once($_SERVER['DOCUMENT_ROOT']."/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/includes/db_connect.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/includes/functions.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/includes/config.php");
	
	sec_session_start();
	
    $query = "SELECT id, pos_id, pos_name FROM repositorio_locales_dtt2 WHERE activar='SI' ORDER BY pos_id";
	
    $contador = 0;
    $html = "<option value=''>Seleccione</option>";
    $tiene_submenu = false;
    if ($sql = $mysqli->prepare($query)) {
        $sql->execute();
        $sql->store_result();
        if ($sql->num_rows > 0) {
            $sql->bind_result($id_pdv, $pos_id, $pos_name) or die($sql->error);
            while($sql->fetch()) {
                $html .= "<option value='".$id_pdv."'>".$pos_id." - " . $pos_name . "</option>";
            }
        }
        $sql->close();
    }
    echo $html;
	
?>