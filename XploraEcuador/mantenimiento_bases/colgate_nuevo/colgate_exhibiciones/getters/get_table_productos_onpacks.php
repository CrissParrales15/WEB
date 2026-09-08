<?php
	include_once "../includes/db_connect.php";
	include_once "../includes/functions.php";
	include_once "../includes/config.php";

	$query = "SELECT id, sector, categoria, subcategoria, segmento, presentacion, variante1, variante2, contenido, sku, marca, elaborado, activar, dolar, fabricante, historico, pvp, cadenas, locales, foto, plataforma, pvs FROM repositorio_productos_onpacks";
	if ($sql = $mysqli->prepare($query)) {
		$sql->execute();
        $sql->store_result();
        if ($sql->num_rows > 0) {
			$html = '<table id="table" name="table" class="table table-striped" style="width:100%">';
			$html .= '<thead class="thead-light">';
			$html .= '<tr>';

			$html .= '<th scope="col">ID</th>';
			$html .= '<th scope="col">Categoria</th>';
			$html .= '<th scope="col">Subcategoria</th>';
			$html .= '<th scope="col">Subcategoria2</th>';
			$html .= '<th scope="col">Segmento</th>';
			$html .= '<th scope="col">Presentacion</th>';
			$html .= '<th scope="col">Variante 1</th>';
			$html .= '<th scope="col">Variante 2</th>';
			$html .= '<th scope="col">Contenido</th>';
			$html .= '<th scope="col">NEW DESCRIPTION SKU</th>';
			$html .= '<th scope="col">Marca</th>';
			$html .= '<th scope="col">Elaborado/Fabricado</th>';
			$html .= '<th scope="col">Activa</th>';
			$html .= '<th scope="col">PVP</th>';
			$html .= '<th scope="col">Importado/Distribuido</th>';
			$html .= '<th scope="col">HISTORICO</th>';
			$html .= '<th scope="col">SIN DÓLAR</th>';
			$html .= '<th scope="col">MALLA</th>';
			$html .= '<th scope="col">N/A</th>';
			$html .= '<th scope="col">Foto</th>';
			$html .= '<th scope="col">Plataforma</th>';
			$html .= '<th scope="col">PVS</th>';
			
			$html .= '</tr>';
			$html .= '</thead>';
			$html .= '<tbody>';

			$sql->bind_result(
				$id,
				$sector,
				$categoria,
				$subcategoria,
				$segmento,
				$presentacion,
				$variante1,
				$variante2,
				$contenido,
				$sku,
				$marca,
				$elaborado,
				$activar,
				$dolar,
				$fabricante,
				$historico,
				$pvp,
				$cadenas,
				$locales,
				$foto,
				$plataforma,
				$pvs) or die($sql->error);

			while ($sql->fetch()) {
				$html .= '<tr>';
				$html .= '<td>' . utf8_encode($id) . '</td>';
				$html .= '<td>' . utf8_encode($sector) . '</td>';
				$html .= '<td>' . utf8_encode($categoria) . '</td>';
				$html .= '<td>' . utf8_encode($subcategoria) . '</td>';
				$html .= '<td>' . utf8_encode($segmento) . '</td>';
				$html .= '<td>' . utf8_encode($presentacion) . '</td>';
				$html .= '<td>' . utf8_encode($variante1) . '</td>';
				$html .= '<td>' . utf8_encode($variante2) . '</td>';
				$html .= '<td>' . utf8_encode($contenido) . '</td>';
				$html .= '<td>' . utf8_encode($sku) . '</td>';
				$html .= '<td>' . utf8_encode($marca) . '</td>';
				$html .= '<td>' . utf8_encode($elaborado) . '</td>';
				$html .= '<td>' . utf8_encode($activar) . '</td>';
				$html .= '<td>' . utf8_encode($dolar) . '</td>';
				$html .= '<td>' . utf8_encode($fabricante) . '</td>';
				$html .= '<td>' . utf8_encode($historico) . '</td>';
				$html .= '<td>' . utf8_encode($pvp) . '</td>';
				$html .= '<td>' . utf8_encode($cadenas) . '</td>';
				$html .= '<td>' . utf8_encode($locales) . '</td>';
				$html .= '<td>' . utf8_encode($foto) . '</td>';
				$html .= '<td>' . utf8_encode($plataforma) . '</td>';  
				$html .= '<td>' . utf8_encode($pvs) . '</td>';  
				$html .= '</tr>';
			}
			$html .= '</tbody>';
        	$html .= '</table>';
		}
		$sql->close();
	}
	echo $html;