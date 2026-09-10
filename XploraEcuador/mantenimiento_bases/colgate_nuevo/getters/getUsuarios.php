<?php
    header('Content-Type: text/html; charset=UTF-8');
	include_once($_SERVER['DOCUMENT_ROOT']."/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/includes/db_connect.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/includes/functions.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/includes/config.php");
	
	sec_session_start();

    // $codigo_pdv = $_POST['codigo_pdv'];
    // $query = "SELECT DISTINCT(user) FROM repositorio_locales_dtt WHERE pos_id='" . $codigo_pdv . "' AND activar='SI' ORDER BY user";
    $query = "SELECT id, usuario FROM repositorio_usuarios WHERE status=1 ORDER BY usuario";
	
    $contador = 0;
    $html = "<option value=''>Seleccione</option>";
    $tiene_submenu = false;
    if ($sql = $mysqli->prepare($query)) {
        $sql->execute();
        $sql->store_result();
        if ($sql->num_rows > 0) {
            $sql->bind_result($id, $user) or die($sql->error);
            while($sql->fetch()) {
                $user = str_replace('Ã‘', 'Ñ', $user);
                $html .= "<option value='".$id."'>".$user."</option>";
            }
        }
        $sql->close();
    }
    echo $html;
	
?>