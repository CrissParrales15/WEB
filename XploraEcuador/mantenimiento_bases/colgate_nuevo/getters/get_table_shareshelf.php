<?php
	include_once "../includes/db_connect.php";
	include_once "../includes/functions.php";
	include_once "../includes/config.php";

	$query = "SELECT id, re, fabricante, categoria, subcategoria, activo, eliminar FROM repositorio_share_shelf";
	if ($sql = $mysqli->prepare($query)) {
		$sql->execute();
        $sql->store_result();
        if ($sql->num_rows > 0) {
			$html = '<table id="table" name="table" class="table table-striped" style="width:100%">';
			$html .= '<thead class="thead-light">';
			$html .= '<tr>';

			$html .= '<th scope="col">ID</th>';
			$html .= '<th scope="col">RE</th>';
			$html .= '<th scope="col">Fabricante</th>';
			$html .= '<th scope="col">Categoria</th>';
			$html .= '<th scope="col">Subcategoria</th>';
			$html .= '<th scope="col">Activo</th>';
			$html .= '<th scope="col">eliminar</th>';

			$html .= '</tr>';
			$html .= '</thead>';
			$html .= '<tbody>';

			$sql->bind_result(
				$id,
				$re,
				$fabricante,
				$categoria,
				$subcategoria,
				$activo,
				$eliminar
			) or die($sql->error);

			while ($sql->fetch()) {
				$html .= '<tr>';

				$html .= '<td>' . $id . '</td>';
				$html .= '<td>' . $re . '</td>';
				$html .= '<td>' . $fabricante . '</td>';
				$html .= '<td>' . $categoria . '</td>';
				$html .= '<td>' . $subcategoria . '</td>';
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