<?php
	include_once "../includes/db_connect.php";
	include_once "../includes/functions.php";
	include_once "../includes/config.php";
		
	$id = $_POST['id'];

    $query = "UPDATE rutero_pdv SET status=0, habilitado=0 WHERE id=?";
	
    if ($sql = $mysqli->prepare($query)) {
		$sql->bind_param('i', $id);
        if ($sql->execute()) {
			echo "1";
		} else {
			echo "2";
		}        
        $sql->close();
    }