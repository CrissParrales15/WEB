<?php
	include_once '../includes/db_connect.php';
	include_once '../includes/functions.php';
	include_once '../includes/config.php';
		
    $query = "SELECT COUNT(*) AS total FROM lvi_notificaciones WHERE estado=0";
	
    $contador = 0;

    $html = "<a href='#' id='alertasxp' name='alertasxp' class='dropdown-toggle' role='button'><span class='material-icons'>notifications</span>(<b>CONTADOR_NOTIFICACIONES</b>)</a>";

    if ($sql = $mysqli->prepare($query)) {
        $sql->execute();
        $sql->store_result();
        if ($sql->num_rows > 0) {
            $sql->bind_result($total) or die($sql->error);
            while ($sql->fetch()) { 
                $html = str_replace("CONTADOR_NOTIFICACIONES", $total, $html);
            }
        }
        $sql->close();
    }
    echo $html;
	
?>