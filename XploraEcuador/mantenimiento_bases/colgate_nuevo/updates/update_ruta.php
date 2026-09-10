<?php
    include_once($_SERVER['DOCUMENT_ROOT']."/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/includes/db_connect.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/includes/functions.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/includes/config.php");
		
	sec_session_start();

    $usuario = $_SESSION['username'];

    $id = $_POST["id"];
    $fecha = $_POST["fecha"];
    $codigo = $_POST["codigo"];
    $usuario_pdv = str_replace('Ã‘', 'Ñ', $_POST["usuario"]);
    $habilitado = $_POST["habilitado"];

    $query = "UPDATE rutero_pdv r 
            JOIN repositorio_locales_dtt2 rl 
            ON r.id_pdv = rl.id 
            SET 
            r.id_pdv=?, 
            r.id_usuario=?, 
            r.id_supervisor = rl.supervisor, 
            r.fecha_visita=?, 
            r.habilitado=?, 
            r.usuario_edicion=? 
            WHERE r.id=?";
	
    if ($sql = $mysqli->prepare($query)) {
		$sql->bind_param('ssssss', $codigo, $usuario_pdv, $fecha, $habilitado, $usuario, $id);
        if ($sql->execute()) {
			echo "1";
		} else {
			echo "2";
		}        
        $sql->close();
    }
?>