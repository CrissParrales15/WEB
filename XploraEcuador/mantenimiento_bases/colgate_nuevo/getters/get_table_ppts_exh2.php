<?php
	include_once "../includes/db_connect.php";
	include_once "../includes/functions.php";
	include_once "../includes/config.php";

	$query = "SELECT id, 
	year,
	month,
	day,
	cp_code,
	trade,
	retail_environment,
	pos,
	exhibition_tool,
	manufacturer,
	category,
	subcategory,
	n_convenio,
	tipo_herramienta,
	clasificacion,
	campaña,
	photo_url,
	customer,
	city,
	server_date,
	status FROM repositorio_ppts_exh ORDER BY id DESC";
	
	if ($sql = $mysqli->prepare($query)) {
		$sql->execute();
        $sql->store_result();
        if ($sql->num_rows > 0) {
			$html = '<div class="table-responsive">';
			$html .= '<table id="table" name="table" class="table table-striped table-bordered" style="width:100%">';
			$html .= '<thead class="thead-light">';
			$html .= '<tr>';
			
			$html .= '<th scope="col">ID</th>';
			$html .= '<th scope="col">Año</th>';
			$html .= '<th scope="col">Mes</th>';
			$html .= '<th scope="col">Día</th>';
			$html .= '<th scope="col">Código CP</th>';
			$html .= '<th scope="col">Trade</th>';
			$html .= '<th scope="col">Ambiente Retail</th>';
			$html .= '<th scope="col">Punto de Venta</th>';
			$html .= '<th scope="col">Herramienta Exhibición</th>';
			$html .= '<th scope="col">Fabricante</th>';
			$html .= '<th scope="col">Categoría</th>';
			$html .= '<th scope="col">Subcategoría</th>';
			$html .= '<th scope="col">N° Convenio</th>';
			$html .= '<th scope="col">Tipo Herramienta</th>';
			$html .= '<th scope="col">Tipo</th>';
			$html .= '<th scope="col">Campaña</th>';
			$html .= '<th scope="col">Foto</th>';
			$html .= '<th scope="col">Cliente</th>';
			$html .= '<th scope="col">Ciudad</th>';
			$html .= '<th scope="col">Fecha Servidor</th>';
			$html .= '<th scope="col">Estado</th>';
			
			$html .= '</tr>';
			$html .= '</thead>';
			$html .= '<tbody>';

			$sql->bind_result(
				$id,
				$year,
				$month,
				$day,
				$cp_code,
				$trade,
				$retail_environment,
				$pos,
				$exhibition_tool,
				$manufacturer,
				$category,
				$subcategory,
				$n_convenio,
				$tipo_herramienta,
				$clasificacion,
				$campaña,
				$photo_url,
				$customer,
				$city,
				$server_date,
				$status
			) or die($sql->error);

			while ($sql->fetch()) {
				$html .= '<tr>';
				
				$html .= '<td>' . htmlspecialchars($id) . '</td>';
				$html .= '<td>' . htmlspecialchars($year) . '</td>';
				$html .= '<td>' . htmlspecialchars($month) . '</td>';
				$html .= '<td>' . htmlspecialchars($day) . '</td>';
				$html .= '<td>' . htmlspecialchars($cp_code) . '</td>';
				$html .= '<td>' . htmlspecialchars($trade) . '</td>';
				$html .= '<td>' . htmlspecialchars($retail_environment) . '</td>';
				$html .= '<td>' . htmlspecialchars($pos) . '</td>';
				$html .= '<td>' . htmlspecialchars($exhibition_tool) . '</td>';
				$html .= '<td>' . htmlspecialchars($manufacturer) . '</td>';
				$html .= '<td>' . htmlspecialchars($category) . '</td>';
				$html .= '<td>' . htmlspecialchars($subcategory) . '</td>';
				$html .= '<td>' . htmlspecialchars($n_convenio) . '</td>';
				$html .= '<td>' . htmlspecialchars($tipo_herramienta) . '</td>';
				$html .= '<td>' . htmlspecialchars($clasificacion) . '</td>';
				$html .= '<td>' . htmlspecialchars($campaña) . '</td>';
				$html .= '<td>';
				// Mostrar miniatura de la foto si existe
				if (!empty($photo_url) && $photo_url != '-') {
					$html .= '<a href="' . htmlspecialchars($photo_url) . '" target="_blank">';
					$html .= '<img src="' . htmlspecialchars($photo_url) . '" alt="Foto" style="width:50px; height:50px; object-fit:cover; border-radius:4px;">';
					$html .= '</a>';
				} else {
					$html .= '<span class="text-muted">-</span>';
				}
				$html .= '</td>';
				$html .= '<td>' . htmlspecialchars($customer) . '</td>';
				$html .= '<td>' . htmlspecialchars($city) . '</td>';
				$html .= '<td>' . htmlspecialchars($server_date) . '</td>';
				$html .= '<td>';
				// Badge para el estado
				if ($status == '1' || $status == 1) {
					$html .= '<span class="badge bg-success">Activo</span>';
				} elseif ($status == '0' || $status == 0) {
					$html .= '<span class="badge bg-danger">Inactivo</span>';
				} else {
					$html .= '<span class="badge bg-secondary">' . htmlspecialchars($status) . '</span>';
				}
				$html .= '</td>';
				
				$html .= '</tr>';
			}
			$html .= '</tbody>';
        	$html .= '</table>';
			$html .= '</div>';
			
			// Agregar contador de registros
			$html .= '<div class="mt-3">';
			$html .= '<span class="badge bg-info">Total registros: ' . $sql->num_rows . '</span>';
			$html .= '</div>';
		} else {
			$html = '<div class="alert alert-warning">No hay registros en la tabla repositorio_ppts_exh</div>';
		}
		$sql->close();
	} else {
		$html = '<div class="alert alert-danger">Error en la consulta: ' . $mysqli->error . '</div>';
	}
	echo $html;
?>