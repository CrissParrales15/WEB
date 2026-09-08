<?php
	include_once "../includes/db_connect.php";
	include_once "../includes/functions.php";
	include_once "../includes/config.php";

	$query = "SELECT id, cadena, actividad, subcategoria, cod_sku, producto, descuento FROM repositorio_validaciones_promociones";
	if ($sql = $mysqli->prepare($query)) {
		$sql->execute();
        $sql->store_result();
        if ($sql->num_rows > 0) {
			$html = '<table id="table" name="table" class="table table-striped" style="width:100%">';
			$html .= '<thead class="thead-light">';
			$html .= '<tr>';

			$html .= '<th scope="col">ID</th>';
			$html .= '<th scope="col">Cadena</th>';
			$html .= '<th scope="col">Actividad</th>';
			$html .= '<th scope="col">Subcategoria</th>';
			$html .= '<th scope="col">Cod SKU</th>';
			$html .= '<th scope="col">Producto</th>';
			$html .= '<th scope="col">Descuento</th>';

			$html .= '</tr>';
			$html .= '</thead>';
			$html .= '<tbody>';

			$sql->bind_result(
				$id,
				$cadena,
				$actividad,
				$subcategoria,
				$cod_sku,
				$producto,
				$descuento
			) or die($sql->error);

			while ($sql->fetch()) {
				$html .= '<tr>';

				$html .= '<td>' . $id . '</td>';
				$html .= '<td>' . $cadena . '</td>';
				$html .= '<td>' . $actividad . '</td>';
				$html .= '<td>' . $subcategoria . '</td>';
				$html .= '<td>' . $cod_sku . '</td>';
				$html .= '<td>' . $producto . '</td>';
				$html .= '<td>' . $descuento . '</td>';
				
				$html .= '</tr>';
			}
			$html .= '</tbody>';
        	$html .= '</table>';
		}
		$sql->close();
	}
	echo $html;