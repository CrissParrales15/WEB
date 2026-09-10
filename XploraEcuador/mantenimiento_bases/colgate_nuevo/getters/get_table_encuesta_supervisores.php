<?php
	include_once "../includes/db_connect.php";
	include_once "../includes/functions.php";
	include_once "../includes/config.php";

	$query = "SELECT id, link, activo, eliminado FROM repositorio_encuesta_supervisores";
	if ($sql = $mysqli->prepare($query)) {
		$sql->execute();
        $sql->store_result();
        if ($sql->num_rows > 0) {
			$html = '<table id="table" name="table" class="table table-striped" style="width:100%">';
			$html .= '<thead class="thead-light">';
			$html .= '<tr>';

			$html .= '<th scope="col">ID</th>';
			$html .= '<th scope="col">LINK</th>';
			$html .= '<th scope="col">Activo</th>';
			$html .= '<th scope="col">Eliminado</th>';

			$html .= '</tr>';
			$html .= '</thead>';
			$html .= '<tbody>';

			$sql->bind_result(
				$id,
				$link,
				$activo,
				$eliminado
			) or die($sql->error);

			while ($sql->fetch()) {
				$html .= '<tr>';

				$html .= '<td>' . $id . '</td>';
				$html .= '<td>' . $link . '</td>';
				$html .= '<td>' . $activo . '</td>';
				$html .= '<td>' . $eliminado . '</td>';

				$html .= '</tr>';
			}
			$html .= '</tbody>';
        	$html .= '</table>';
		}
		$sql->close();
	}
	echo $html;