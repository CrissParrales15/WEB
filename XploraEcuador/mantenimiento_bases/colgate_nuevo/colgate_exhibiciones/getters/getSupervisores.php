<?php
	include_once '../includes/db_connect.php';
	include_once '../includes/functions.php';
	include_once '../includes/config.php';
	
	sec_session_start();
	
    $query = "SELECT supervisor FROM repositorio_supervisor WHERE habilitado=1 AND rol='SUPERVISOR' ORDER BY supervisor";
	
    $html = "<option value=''>Seleccione </option>";
    if ($sql = $mysqli->prepare($query)) {
        $sql->execute();
        $sql->store_result();
        if ($sql->num_rows > 0) {

            if($sql->num_rows > 1){
                $html .= "<option value='.'>Todos</option>";
            }

            $sql->bind_result($supervisor) or die($sql->error);
            while($sql->fetch()) {
                $html .= "<option value='".$supervisor."'>".$supervisor."</option>";
            }
        }
        $sql->close();
    }
    echo $html;
	
?>