<?php
	include_once "../includes/db_connect.php";
	include_once "../includes/functions.php";
	include_once "../includes/config.php";
	
    $query = "UPDATE insert_notificaciones SET estado=1 WHERE estado=0";
	
    if ($sql = $mysqli->prepare($query)) {
        if ($sql->execute()) {
			echo "1";
		} else {
			echo "2";
		}        
        $sql->close();
    }