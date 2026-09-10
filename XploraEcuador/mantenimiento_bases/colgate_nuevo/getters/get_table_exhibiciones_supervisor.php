<?php
	include_once "../includes/db_connect.php";
	include_once "../includes/functions.php";
	include_once "../includes/config.php";

	$query = "SELECT id, 
	cp_code,
	local,
	visual,
	canal, 
	supervisor, 
	exhibition_tool, 
	manufacturer,
	category, 
	subcategory, 
	zone_exhibition, 
	personalization,
	price_tag, 
	num_exhibitions, 
	observation, 
	photo, 
	date, 
	visual_access, 
	photo2,
	tipo_herramienta,
	convenio,
	clasificacion,
	campana,
	comentario,
	activo,
	eliminado FROM repositorio_exhibicion_supervisor2";
	if ($sql = $mysqli->prepare($query)) {
		$sql->execute();
        $sql->store_result();
        if ($sql->num_rows > 0) {
			$html = '<table id="table" name="table" class="table table-striped" style="width:100%">';
			$html .= '<thead class="thead-light">';
			$html .= '<tr>';

			$html .= '<th scope="col">ID</th>';
			$html .= '<th scope="col">Codigo CP</th>';
			$html .= '<th scope="col">Local</th>';
			$html .= '<th scope="col">Visual</th>';
			$html .= '<th scope="col">canal</th>';
			$html .= '<th scope="col">User</th>';
			$html .= '<th scope="col">Exhibition_tool</th>';
			$html .= '<th scope="col">Manufacturer</th>';
			$html .= '<th scope="col">Category</th>';
			$html .= '<th scope="col">Subcategory</th>';
			$html .= '<th scope="col">Zone_exhibition</th>';
			$html .= '<th scope="col">Personalization</th>';
			$html .= '<th scope="col">Price tag</th>';
			$html .= '<th scope="col">Numero Exhibiciones</th>';
			$html .= '<th scope="col">Observacion</th>';
			$html .= '<th scope="col">Foto</th>';
			$html .= '<th scope="col">Fecha</th>';
			$html .= '<th scope="col">Visual_access</th>';
			$html .= '<th scope="col">Photo2</th>';
			$html .= '<th scope="col">Tipo Herramienta</th>';
			$html .= '<th scope="col">Convenio</th>';
			$html .= '<th scope="col">Clasificacion</th>';
			$html .= '<th scope="col">Campana</th>';
			$html .= '<th scope="col">Comentario</th>';
			$html .= '<th scope="col">Activo</th>';
			$html .= '<th scope="col">Eliminado</th>';

			$html .= '</tr>';
			$html .= '</thead>';
			$html .= '<tbody>';

			$sql->bind_result(
				$id,
				$cp_code,
				$local,
				$visual,
				$canal,
				$supervisor,
				$exhibition_tool,
				$manufacturer,
				$category,
				$subcategory,
				$zone_exhibition,
				$personalization,
				$price_tag,
				$num_exhibitions,
				$observation,
				$photo,
				$date,
				$visual_access,
				$photo2,
				$tipo_herramienta,
				$convenio,
				$clasificacion,
				$campana,
				$comentario,
				$activo,
				$eliminado
			) or die($sql->error);

			while ($sql->fetch()) {
				$html .= '<tr>';

				$html .= '<td>' . $id . '</td>';
				$html .= '<td>' . $cp_code . '</td>';
				$html .= '<td>' . $local . '</td>';
				$html .= '<td>' . $visual . '</td>';
				$html .= '<td>' . $canal . '</td>';
				$html .= '<td>' . $supervisor . '</td>';
				$html .= '<td>' . $exhibition_tool . '</td>';
				$html .= '<td>' . $manufacturer . '</td>';
				$html .= '<td>' . $category . '</td>';
				$html .= '<td>' . $subcategory . '</td>';
				$html .= '<td>' . $zone_exhibition . '</td>';
				$html .= '<td>' . $personalization . '</td>';
				$html .= '<td>' . $price_tag . '</td>';
				$html .= '<td>' . $num_exhibitions . '</td>';
				$html .= '<td>' . $observation . '</td>';
				$html .= '<td>' . $photo . '</td>';
				$html .= '<td>' . $date . '</td>';
				$html .= '<td>' . $visual_access . '</td>';
				$html .= '<td>' . $photo2 . '</td>';
				$html .= '<td>' . $tipo_herramienta . '</td>';
				$html .= '<td>' . $convenio . '</td>';
				$html .= '<td>' . $clasificacion . '</td>';
				$html .= '<td>' . $campana . '</td>';
				$html .= '<td>' . $comentario . '</td>';
				$html .= '<td>' . $activo . '</td>';
				$html .= '<td>' . $eliminado . '</td>';

				$html .= '</tr>';
			}
			$html .= '</tbody>';
        	$html .= '</table>';
		}
		$sql->close();
	}
	echo $html;