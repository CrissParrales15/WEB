<?php
	include_once "../includes/db_connect.php";
	include_once "../includes/functions.php";
	include_once "../includes/config.php";

	$query = "SELECT nombre_encuesta, descripcion, categoria, re, pregunta, tipo_pregunta, opc_a, opc_b, opc_c, opc_d, opc_e, foto, tipo_campo, habilitado FROM repositorio_evaluacion_encuestas";
	if ($sql = $mysqli->prepare($query)) {
		$sql->execute();
        $sql->store_result();
        if ($sql->num_rows > 0) {
			$html = '<table id="table" name="table" class="table table-striped" style="width:100%">';
			$html .= '<thead class="thead-light">';
			$html .= '<tr>';

			$html .= '<th scope="col">Encuesta</th>';
			$html .= '<th scope="col">Descripcion</th>';
			$html .= '<th scope="col">Categoria</th>';
			$html .= '<th scope="col">RE</th>';
			$html .= '<th scope="col">Pregunta</th>';
			$html .= '<th scope="col">Tipo Pregunta</th>';
			$html .= '<th scope="col">Opc_a</th>';
			$html .= '<th scope="col">Opc_b</th>';
			$html .= '<th scope="col">Opc_c</th>';
			$html .= '<th scope="col">Opc_d</th>';
			$html .= '<th scope="col">Opc_e</th>';
			$html .= '<th scope="col">Foto</th>';
			$html .= '<th scope="col">Tipo Campo</th>';
			$html .= '<th scope="col">Habilitado</th>';

			$html .= '</tr>';
			$html .= '</thead>';
			$html .= '<tbody>';

			$sql->bind_result(
				$nombre_encuesta,
				$descripcion,
				$categoria,
				$re,
				$pregunta,
				$tipo_pregunta,
				$opc_a,
				$opc_b,
				$opc_c,
				$opc_d,
				$opc_e,
				$foto,
				$tipo_campo,
				$habilitado

			) or die($sql->error);
//id, nombre_encuesta, descripcion, categoria, re, pregunta, tipo_pregunta, puntaje_por_pregunta, habilitado
			while ($sql->fetch()) {
				$html .= '<tr>';

				$html .= '<td>' . $nombre_encuesta . '</td>';
				$html .= '<td>' . $descripcion . '</td>';
				$html .= '<td>' . $categoria . '</td>';
				$html .= '<td>' . $re . '</td>';
				$html .= '<td>' . $pregunta . '</td>';
				$html .= '<td>' . $tipo_pregunta . '</td>';
				$html .= '<td>' . $opc_a . '</td>';
				$html .= '<td>' . $opc_b . '</td>';
				$html .= '<td>' . $opc_c . '</td>';
				$html .= '<td>' . $opc_d . '</td>';
				$html .= '<td>' . $opc_e . '</td>';
				$html .= '<td>' . $foto . '</td>';
				$html .= '<td>' . $tipo_campo. '</td>';
				$html .= '<td>' . $habilitado . '</td>';

				$html .= '</tr>';
			}
			$html .= '</tbody>';
        	$html .= '</table>';
		}
		$sql->close();
	}
	echo $html;