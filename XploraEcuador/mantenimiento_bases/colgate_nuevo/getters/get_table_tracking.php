<?php
	include_once "../includes/db_connect.php";
	include_once "../includes/functions.php";
	include_once "../includes/config.php";

	$query = "SELECT id, customer, mecanica, categoria, descripcion, precio_promocion, vigencia, material_pop, activo, eliminar FROM repositorio_tracking";
	if ($sql = $mysqli->prepare($query)) {
		$sql->execute();
        $sql->store_result();
        if ($sql->num_rows > 0) {
			$html = '<table id="table" name="table" class="table table-striped" style="width:100%">';
			$html .= '<thead class="thead-light">';
			$html .= '<tr>';

			$html .= '<th scope="col">ID</th>';
			$html .= '<th scope="col">Customer</th>';
			$html .= '<th scope="col">Mecanica</th>';
			$html .= '<th scope="col">Categoria</th>';
			$html .= '<th scope="col">Descripcion</th>';
			$html .= '<th scope="col">Precio Promocion</th>';
			$html .= '<th scope="col">Vigencia</th>';
			$html .= '<th scope="col">Material POP</th>';
			$html .= '<th scope="col">Activo</th>';
			$html .= '<th scope="col">Eliminar</th>';

			$html .= '</tr>';
			$html .= '</thead>';
			$html .= '<tbody>';

			$sql->bind_result(
				$id,
				$customer,
				$mecanica,
				$categoria,
				$descripcion,
				$precio_promocion,
				$vigencia,
				$material_pop,
				$activo,
				$eliminar
			) or die($sql->error);

			while ($sql->fetch()) {
				$html .= '<tr>';

				$html .= '<td>' . $id . '</td>';
				$html .= '<td>' . $customer . '</td>';
				$html .= '<td>' . $mecanica . '</td>';
				$html .= '<td>' . $categoria . '</td>';
				$html .= '<td>' . $descripcion . '</td>';
				$html .= '<td>' . $precio_promocion . '</td>';
				$html .= '<td>' . $vigencia . '</td>';
				$html .= '<td>' . $material_pop . '</td>';
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