<?php
include_once "../includes/db_connect.php";
include_once "../includes/functions.php";
include_once "../includes/config.php";

$html = "";

$query = "SELECT id, `year`, `month`, `day`, cp_code, trade, retail_environment, pos, exhibition_tool, manufacturer, category, subcategory, n_convenio, tipo_herramienta, clasificacion, campaña, photo_url, customer, city, server_date FROM repositorio_ppts_exh";
if ($sql = $mysqli->prepare($query)) {
	$sql->execute();
	$sql->store_result();
	if ($sql->num_rows > 0) {
		$html = '<table id="table" name="table" class="table table-striped" style="width:100%">';
		$html .= '<thead class="thead-light">';
		$html .= '<tr>';

		$html .= '<th scope="col">ID</th>';
		$html .= '<th scope="col">Year</th>';
		$html .= '<th scope="col">Month</th>';
		$html .= '<th scope="col">Day</th>';
		$html .= '<th scope="col">CP Code</th>'; 
		$html .= '<th scope="col">Trade</th>';
		$html .= '<th scope="col">Retail Environment</th>';
		$html .= '<th scope="col">POS</th>';
		$html .= '<th scope="col">Exhibition Tool</th>';
		$html .= '<th scope="col">Manufacturer</th>';
		$html .= '<th scope="col">Category</th>';
		$html .= '<th scope="col">Subcategory</th>';
		$html .= '<th scope="col">N Convenio</th>';
		$html .= '<th scope="col">Tipo Herramienta</th>';
		$html .= '<th scope="col">Clasificacion</th>';
		$html .= '<th scope="col">Campaña</th>';
		$html .= '<th scope="col">Photo</th>';
		$html .= '<th scope="col">Customer</th>';
		$html .= '<th scope="col">City</th>';
		$html .= '<th scope="col">Server Date</th>';

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
			$campana,
			$photo_url,
			$customer,
			$city,
			$server_date
		) or die($sql->error);

		while ($sql->fetch()) {
			$html .= '<tr>';
			$html .= '<td>' . utf8_encode($id) . '</td>';
			$html .= '<td>' . utf8_encode($year) . '</td>';
			$html .= '<td>' . utf8_encode($month) . '</td>';
			$html .= '<td>' . utf8_encode($day) . '</td>';
			$html .= '<td>' . utf8_encode($cp_code) . '</td>';
			$html .= '<td>' . utf8_encode($trade) . '</td>';
			$html .= '<td>' . utf8_encode($retail_environment) . '</td>';
			$html .= '<td>' . utf8_encode($pos) . '</td>';
			$html .= '<td>' . utf8_encode($exhibition_tool) . '</td>';
			$html .= '<td>' . utf8_encode($manufacturer) . '</td>';
			$html .= '<td>' . utf8_encode($category) . '</td>';
			$html .= '<td>' . utf8_encode($subcategory) . '</td>';
			$html .= '<td>' . utf8_encode($n_convenio) . '</td>';
			$html .= '<td>' . utf8_encode($tipo_herramienta) . '</td>';
			$html .= '<td>' . utf8_encode($clasificacion) . '</td>';
			$html .= '<td>' . utf8_encode($campana) . '</td>';
			if (!empty($photo_url)) {
				$html .= '<td><a href="' . htmlspecialchars($photo_url) . '" target="_blank">Ver foto</a></td>';
			} else {
				$html .= '<td></td>';
			}
			$html .= '<td>' . utf8_encode($customer) . '</td>';
			$html .= '<td>' . utf8_encode($city) . '</td>';
			$html .= '<td>' . utf8_encode($server_date) . '</td>';
			$html .= '</tr>';
		}
		$html .= '</tbody>';
		$html .= '</table>';
	}
	$sql->close();
}
echo $html;