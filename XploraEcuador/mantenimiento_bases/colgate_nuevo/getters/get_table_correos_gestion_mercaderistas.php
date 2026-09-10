<?php
	include_once "../includes/db_connect.php";
	include_once "../includes/functions.php";
	include_once "../includes/config.php";
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
	$query = "SELECT id, cargo, nombre, canal, zona, correo,correos_cc,correos_cco FROM alertas_correo_gestion_mercaderistas";

    if ($sql = $mysqli->prepare($query)) {
		$sql->execute();
        $sql->store_result();
        if ($sql->num_rows > 0) {
			$html = '<table id="table" name="table" class="table table-striped" style="width:100%">';
			$html .= '<thead class="thead-light">';
			$html .= '<tr>';

			$html .= '<th scope="col">ID</th>';
			$html .= '<th scope="col">CARGO</th>';
			$html .= '<th scope="col">NOMBRE</th>';
			$html .= '<th scope="col">CANAL</th>';
			$html .= '<th scope="col">ZONA</th>';
			$html .= '<th scope="col">Correos</th>';
			$html .= '<th scope="col">Correos_cc</th>';
			$html .= '<th scope="col">Correos_cco</th>';
			
			$html .= '</tr>';
			$html .= '</thead>';
			$html .= '<tbody>';

			$sql->bind_result(
				$id,
                $cargo,
                $nombre,
                $canal,
                $zona,   
                $correo,   
                $correos_cc,
				$correos_cco) or die($sql->error);

			while ($sql->fetch()) {
				$html .= '<tr>';
				$html .= '<td>' . $id . '</td>';
				$html .= '<td>' . $cargo . '</td>';
				$html .= '<td>' . $nombre . '</td>';
				$html .= '<td>' . $canal . '</td>';
				$html .= '<td>' . $zona . '</td>';
                $html .= '<td>' . $correo . '</td>';
                $html .= '<td>' . $correos_cc . '</td>'; 
                $html .= '<td>' . $correos_cco . '</td>';
				$html .= '</tr>';
			}
			$html .= '</tbody>';
        	$html .= '</table>';
		}
		$sql->close();
	}
	echo $html;