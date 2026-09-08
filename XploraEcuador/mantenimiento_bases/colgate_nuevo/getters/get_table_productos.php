<?php
	include_once "../includes/db_connect.php";
	include_once "../includes/functions.php";
	include_once "../includes/config.php";

	$query = "SELECT id, 
	codigosku,
	categoria, 
	subcategoria, 
	segmento, 
	forma,
	fabricante, 
	marca, 
	variante, 
	ean,
	cod_sap, 
	sku, 
	sku_sugerido, 
	consumer_unit, 
	tamano, 
	nuevo, 
	backup, 
	activar FROM repositorio_productos";
	if ($sql = $mysqli->prepare($query)) {
		$sql->execute();
        $sql->store_result();
        if ($sql->num_rows > 0) {
			$html = '<table id="table" name="table" class="table table-striped" style="width:100%">';
			$html .= '<thead class="thead-light">';
			$html .= '<tr>';

			$html .= '<th scope="col">ID</th>';
			$html .= '<th scope="col">Codigo SKU</th>';
			$html .= '<th scope="col">Categoria</th>';
			$html .= '<th scope="col">Subcategoria</th>';
			$html .= '<th scope="col">Segmento</th>';
			$html .= '<th scope="col">Forma</th>';
			$html .= '<th scope="col">Fabricante</th>';
			$html .= '<th scope="col">Marca</th>';
			$html .= '<th scope="col">Variante</th>';
			$html .= '<th scope="col">EAN</th>';
			$html .= '<th scope="col">Codigo SAP</th>';
			$html .= '<th scope="col">Sku</th>';
			$html .= '<th scope="col">Sku Sugerido</th>';
			$html .= '<th scope="col">Consumer Unit</th>';
			$html .= '<th scope="col">Tamaño</th>';
			$html .= '<th scope="col">Nuevo</th>';
			$html .= '<th scope="col">Backup</th>';
			$html .= '<th scope="col">Activar</th>';

			$html .= '</tr>';
			$html .= '</thead>';
			$html .= '<tbody>';

			$sql->bind_result(
				$id,
				$codigosku,
				$categoria,
				$subcategoria,
				$segmento,
				$forma,
				$fabricante,
				$marca,
				$variante,
				$ean,
				$cod_sap,
				$sku,
				$sku_sugerido,
				$consumer_unit,
				$tamano,
				$nuevo,
				$backup,
				$activar
			) or die($sql->error);

			while ($sql->fetch()) {
				$html .= '<tr>';

				$html .= '<td>' . $id . '</td>';
				$html .= '<td>' . $codigosku . '</td>';
				$html .= '<td>' . $categoria . '</td>';
				$html .= '<td>' . $subcategoria . '</td>';
				$html .= '<td>' . $segmento . '</td>';
				$html .= '<td>' . $forma . '</td>';
				$html .= '<td>' . $fabricante . '</td>';
				$html .= '<td>' . $marca . '</td>';
				$html .= '<td>' . $variante . '</td>';
				$html .= '<td>' . $ean . '</td>';
				$html .= '<td>' . $cod_sap . '</td>';
				$html .= '<td>' . $sku . '</td>';
				$html .= '<td>' . $sku_sugerido . '</td>';
				$html .= '<td>' . $consumer_unit . '</td>';
				$html .= '<td>' . $tamano . '</td>';
				$html .= '<td>' . $nuevo . '</td>';
				$html .= '<td>' . $backup . '</td>';
				$html .= '<td>' . $activar . '</td>';

				$html .= '</tr>';
			}
			$html .= '</tbody>';
        	$html .= '</table>';
		}
		$sql->close();
	}
	echo $html;