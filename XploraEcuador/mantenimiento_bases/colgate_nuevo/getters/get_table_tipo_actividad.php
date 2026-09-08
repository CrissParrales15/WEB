<?php
	include_once "../includes/db_connect.php";
	include_once "../includes/functions.php";
	include_once "../includes/config.php";

	$query = "SELECT id, re, tipo_actividad, activo, eliminar FROM repositorio_tipo_actividad";
	if ($sql = $mysqli->prepare($query)) {
		$sql->execute();
        $sql->store_result();
        if ($sql->num_rows > 0) {
			$html = '<table id="table" name="table" class="table table-striped" style="width:100%">';
			$html .= '<thead class="thead-light">';
			$html .= '<tr>';

			$html .= '<th scope="col">ID</th>';
			$html .= '<th scope="col">re</th>';
			$html .= '<th scope="col">Tipo Actividad</th>';
			$html .= '<th scope="col">Activo</th>';
			$html .= '<th scope="col">Eliminar</th>';

			$html .= '</tr>';
			$html .= '</thead>';
			$html .= '<tbody>';

			$sql->bind_result(
				$id,
				$re,
				$tipo_actividad,
				$activo,
				$eliminar
			) or die($sql->error);

			while ($sql->fetch()) {
				$html .= '<tr>';

				$html .= '<td>' . $id . '</td>';
				$html .= '<td>' . $re . '</td>';
				$html .= '<td>' . $tipo_actividad . '</td>';
				$html .= '<td>' . $activo . '</td>';
				$html .= '<td>' . $eliminar . '</td>';

				$html .= '</tr>';
			}
			$html .= '</tbody>';
        	$html .= '</table>';
		}
		$sql->close();
	}
	echo $html;