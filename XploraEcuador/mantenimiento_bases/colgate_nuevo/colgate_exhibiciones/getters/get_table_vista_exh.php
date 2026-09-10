<?php
	include_once "../includes/db_connect.php";
	include_once "../includes/functions.php";
	include_once "../includes/config.php";

	$query = "SELECT cp_code,supervisor,exhibition_tool,nherramienta,realizadas,eliminadas,pendientes FROM lvi_exhibiciones_colgate";
	if ($sql = $mysqli->prepare($query)) {
		$sql->execute();
        $sql->store_result();
        if ($sql->num_rows > 0) {
			$html = '<table id="table" name="table" class="table table-striped" style="width:100%">';
			$html .= '<thead class="thead-light">';
			$html .= '<tr>';

			$html .= '<th scope="col">Codigo</th>';
			$html .= '<th scope="col">Usuario</th>';
			$html .= '<th scope="col">Herramienta</th>';
			$html .= '<th scope="col">N. Herramienta</th>';
			$html .= '<th scope="col">Realizadas</th>';
			$html .= '<th scope="col">Eliminadas</th>';
			$html .= '<th scope="col">Pendientes</th>';

			$html .= '</tr>';
			$html .= '</thead>';
			$html .= '<tbody>';

			$sql->bind_result(
				$cp_code,
				$supervisor,
				$exhibition_tool,
				$nherramienta,
				$realizadas,
				$eliminadas,
				$pendientes
			) or die($sql->error);

			while ($sql->fetch()) {
				$html .= '<tr>';

				$html .= '<td>' . utf8_encode($cp_code) . '</td>';
				$html .= '<td>' . utf8_encode($supervisor) . '</td>';
				$html .= '<td>' . utf8_encode($exhibition_tool) . '</td>';
				$html .= '<td>' . utf8_encode($nherramienta) . '</td>';
				$html .= '<td>' . utf8_encode($realizadas) . '</td>';
				$html .= '<td>' . utf8_encode($eliminadas) . '</td>';
				$html .= '<td>' . utf8_encode($pendientes) . '</td>';

				$html .= '</tr>';
			}
			$html .= '</tbody>';
        	$html .= '</table>';
		}
		$sql->close();
	}
	echo $html;