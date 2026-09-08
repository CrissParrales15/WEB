<?php
	include_once "../includes/db_connect.php";
	include_once "../includes/functions.php";
	include_once "../includes/config.php";

	$query = "SELECT id, canal, retail, reabrev, formatocp, formato, target, distribuidor, activo, eliminar FROM repositorio_retail";
	if ($sql = $mysqli->prepare($query)) {
		$sql->execute();
        $sql->store_result();
        if ($sql->num_rows > 0) {
			$html = '<table id="table" name="table" class="table table-striped" style="width:100%">';
			$html .= '<thead class="thead-light">';
			$html .= '<tr>';

			$html .= '<th scope="col">ID</th>';
			$html .= '<th scope="col">Canal</th>';
			$html .= '<th scope="col">Retail</th>';
			$html .= '<th scope="col">Reabrev</th>';
			$html .= '<th scope="col">Formato CP</th>';
			$html .= '<th scope="col">Formato</th>'; 
			$html .= '<th scope="col">Target</th>'; 
			$html .= '<th scope="col">Distribuidor</th>';
			$html .= '<th scope="col">Activo</th>';
			$html .= '<th scope="col">Eliminar</th>';

			$html .= '</tr>';
			$html .= '</thead>';
			$html .= '<tbody>';

			$sql->bind_result(
				$id,
				$canal,
				$retail,
				$reabrev,
				$formatocp,
				$formato,
				$target,
				$distribuidor,
				$activo,
				$eliminar
			) or die($sql->error);

			while ($sql->fetch()) {
				$html .= '<tr>';

				$html .= '<td>' . $id . '</td>';
				$html .= '<td>' . $canal . '</td>';
				$html .= '<td>' . $retail . '</td>';
				$html .= '<td>' . $reabrev . '</td>';
				$html .= '<td>' . $formatocp . '</td>';
				$html .= '<td>' . $formato . '</td>';
				$html .= '<td>' . $target . '</td>';
				$html .= '<td>' . $distribuidor . '</td>';
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