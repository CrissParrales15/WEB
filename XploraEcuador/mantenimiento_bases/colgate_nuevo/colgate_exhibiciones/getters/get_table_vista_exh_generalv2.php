<?php
	include_once "../includes/db_connect.php";
	include_once "../includes/functions.php";
	include_once "../includes/config.php";

	$supervisor = isset($_GET['supervisor']) ? trim($_GET['supervisor']) : '';
	$html = '';

	if ($supervisor === '') {
		echo $html;
		exit;
	}

	$monthCondition = "MONTH(rp.fecha_visita) = MONTH(CURDATE()) AND YEAR(rp.fecha_visita) = YEAR(CURDATE())";

	$query = "
		SELECT cp_code, local, exhibition_tool, nherramienta, realizadas, eliminadas, pendientes
		FROM lvi_exhibiciones_colgate
		WHERE cp_code IN (
			SELECT DISTINCT rld.pos_id
			FROM repositorio_locales_dtt2 rld
			INNER JOIN rutero_pdv rp ON rp.id_pdv = rld.pos_id
			INNER JOIN repositorio_usuarios ru ON ru.id = rp.id_usuario
			WHERE ru.usuario = ?
			AND {$monthCondition}
		)
	";

	if ($sql = $mysqli->prepare($query)) {
		$sql->bind_param('s', $supervisor);
		$sql->execute();
		$sql->store_result();

		if ($sql->num_rows > 0) {
			$html = '<table id="table" name="table" class="table table-striped" style="width:100%">';
			$html .= '<thead class="thead-light">';
			$html .= '<tr>';
			$html .= '<th scope="col">Codigo</th>';
			$html .= '<th scope="col">Local</th>';
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
				$local,
				$exhibition_tool,
				$nherramienta,
				$realizadas,
				$eliminadas,
				$pendientes
			) or die($sql->error);

			while ($sql->fetch()) {
				$rowClass = $pendientes == 0 ? 'green-row' : 'red-row';
				$html .= '<tr class="' . $rowClass . '">';
				$html .= '<td>' . utf8_encode($cp_code) . '</td>';
				$html .= '<td>' . utf8_encode($local) . '</td>';
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