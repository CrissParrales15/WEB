<?php
	include_once "../includes/db_connect.php";
	include_once "../includes/functions.php";
	include_once "../includes/config.php";

	$query = "SELECT id, causal, modificar_horas FROM repositorio_causales_efectividad";
	if ($sql = $mysqli->prepare($query)) {
		$sql->execute();
        $sql->store_result();
        if ($sql->num_rows > 0) {
			$html = '<table id="table" name="table" class="table table-striped" style="width:100%">';
			$html .= '<thead class="thead-light">';
			$html .= '<tr>';

			$html .= '<th scope="col">ID</th>';
			$html .= '<th scope="col">Causal</th>';
			$html .= '<th scope="col">Modificar Horas</th>';
			$html .= '</tr>';
			$html .= '</thead>';
			$html .= '<tbody>';

			$sql->bind_result(
					$id,
					$causal,
					$modificar_horas
				) or die($sql->error);

			while ($sql->fetch()) {
				$html .= '<tr>';
				$html .= '<td>' . utf8_encode($id) . '</td>';
				$html .= '<td>' . utf8_encode($causal) . '</td>';
				$html .= '<td>' . utf8_encode($modificar_horas) . '</td>';
				$html .= '</tr>';
			}
			$html .= '</tbody>';
        	$html .= '</table>';
		}
		$sql->close();
	}
	echo $html;