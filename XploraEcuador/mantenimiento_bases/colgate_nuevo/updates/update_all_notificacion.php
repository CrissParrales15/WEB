<?php
	include_once($_SERVER['DOCUMENT_ROOT']."/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/includes/db_connect.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/includes/functions.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/includes/config.php");
	
    $query = "UPDATE insert_notificaciones SET estado=1 WHERE estado=0";
	
    if ($sql = $mysqli->prepare($query)) {
        if ($sql->execute()) {
			echo "1";
		} else {
			echo "2";
		}        
        $sql->close();
    }