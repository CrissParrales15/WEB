<?php
	include_once "../includes/db_connect.php";
	include_once "../includes/functions.php";
	include_once "../includes/config.php";

	$query = "SELECT id, supervisor, tipo, pass, status FROM repositorio_supervisores";
	if ($sql = $mysqli->prepare($query)) {
		$sql->execute();
        $sql->store_result();
        if ($sql->num_rows > 0) {
			$html = '<table id="table" name="table" class="table table-striped" style="width:100%">';
			$html .= '<thead class="thead-light">';
			$html .= '<tr>';

			$html .= '<th scope="col">ID</th>';
			$html .= '<th scope="col">SUPERVISOR</th>';
			$html .= '<th scope="col">TIPO</th>';
			$html .= '<th scope="col">PASS</th>';
			$html .= '<th scope="col">STATUS</th>'
			$html .= '</tr>';
			$html .= '</thead>';
			$html .= '<tbody>';

			$sql->bind_result($id, $supervisor, $tipo, $pass, $status) or die($sql->error);

			while ($sql->fetch()) {
				$html .= '<tr>';
				$html .= '<td>' . $id . '</td>';
				$html .= '<td>' . $supervisor . '</td>';
				$html .= '<td>' . $tipo . '</td>';
				$html .= '<td>' . $pass . '</td>';
				$html .= '<td>' . $status . '</td>';
				$html .= '</tr>';
			}
			$html .= '</tbody>';
        	$html .= '</table>';
		}
		$sql->close();
	}
	echo $html;