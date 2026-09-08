<?php
	include_once "../includes/db_connect.php";
	include_once "../includes/functions.php";
	include_once "../includes/config.php";

	$query = "SELECT id, 
	codigosku,
	modulo, 
	re,
	activo,
	eliminar FROM repositorio_productos_modulo_cadena";
	if ($sql = $mysqli->prepare($query)) {
		$sql->execute();
        $sql->store_result();
        if ($sql->num_rows > 0) {
			$html = '<table id="table" name="table" class="table table-striped" style="width:100%">';
			$html .= '<thead class="thead-light">';
			$html .= '<tr>';

			$html .= '<th scope="col">ID</th>';
			$html .= '<th scope="col">Codigo SKU</th>';
			$html .= '<th scope="col">Modulo</th>';
			$html .= '<th scope="col">RE</th>';
			$html .= '<th scope="col">ACTIVO</th>';
			$html .= '<th scope="col">ELIMINAR</th>';

			$html .= '</tr>';
			$html .= '</thead>';
			$html .= '<tbody>';

			$sql->bind_result(
				$id,
				$codigosku,
				$modulo,
				$re,
				$activo,
				$eliminar
			) or die($sql->error);

			while ($sql->fetch()) {
				$html .= '<tr>';

				$html .= '<td>' . $id . '</td>';
				$html .= '<td>' . $codigosku . '</td>';
				$html .= '<td>' . $modulo . '</td>';
				$html .= '<td>' . $re . '</td>';
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