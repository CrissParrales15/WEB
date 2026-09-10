<?php
	include_once '../includes/db_connect.php';
	include_once '../includes/functions.php';
	include_once '../includes/config.php';
	
	sec_session_start();
	
	$fecha_inicio = $_POST['fecha_inicio'];
	$fecha_fin = $_POST['fecha_fin'];
	$reporte = $_POST['reporte'];
	$canal = $_POST['canal'];
	$ciudad = $_POST['ciudad'];
	$supervisor = $_POST['supervisor'];
	$mercaderista = $_POST['mercaderista'];

    $img_url = "https://webecuador.azurewebsites.net/App/AppAlicorp/Inserts/";

    $query =    "SELECT id, fecha, hora, mercaderista, foto FROM " . $reporte . 
                " WHERE (STR_TO_DATE(fecha, '%d/%m/%Y') BETWEEN '" . $fecha_inicio . "' AND '" . $fecha_fin . "') 
                AND channel='" . $canal . "' 
                AND city LIKE '%" . $ciudad . "%' 
                AND supervisor LIKE '%" . $supervisor . "%' 
                AND mercaderista LIKE '%" . $mercaderista . "%' 
                ORDER BY fecha, hora";
	
    $contador = 0;
    $tiene_submenu = false;
    if ($sql = $mysqli->prepare($query)) {
        $sql->execute();
        $sql->store_result();
        if ($sql->num_rows > 0) {
            $sql->bind_result($id, $fecha, $hora, $mercaderista, $foto) or die($sql->error);
            while($sql->fetch()) {
                if ($contador == 0) {
                    $html .= "<tr>";
                }
                if ($contador == 4) {
                    $html .= "</tr><tr>";
                    $contador = 0;
                }

                $html .= "<td id='" . $id . "' name='" . $id . "' class='text-center' style='padding:1%;'>";
                $html .= "<div class='card text-center'>";
                $html .= "<div class='card-header'>";
                $html .= "<input type='checkbox' class='sub-col-7 dont-get-data' data-billaccount='emailField' data-subtype='21' id='" . $id . "' value='" . $img_url.$foto . "'/>";
                $html .= "</div>";
                $html .= "<div class='card-body'>";
                $html .= "<img style='width:100%;' class='img-responsive' src='" . $img_url.$foto . "'/>";
                $html .= "<p class='card-text'><b>Fecha:</b> " . $fecha . " " . $hora . "</p>";
                $html .= "<p class='card-text'><b>Mercaderista:</b> " . $mercaderista . "</p>";
                $html .= "</div>";
                $html .= "<div class='card-footer text-muted'>";
                $html .= "";
                $html .= "</div>";
                $html .= "</div>";
                $html .= "</td>";
                $contador++;
            }
            if (substr($html, -5, 0) != "</tr>") {
                $html .= "</tr>";
            }
        }
        $sql->close();
    }
    echo $html;
	
?>