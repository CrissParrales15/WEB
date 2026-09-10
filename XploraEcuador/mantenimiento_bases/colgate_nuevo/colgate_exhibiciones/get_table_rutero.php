<?php
	use Phppot\DataSource;
	require_once 'DataSource.php';
	$html = '';

	// $data = json_decode($_POST['data']);
	$fechaInicio = $_POST['fechaInicio'];
	$fechaFin = $_POST['fechaFin'];

	$db = new DataSource();
	$conn = $db->getConnection();

	$sqlSelect = "SELECT * FROM lvi_rutero WHERE fecha_visita BETWEEN '$fechaInicio' AND '$fechaFin' ORDER BY fecha_visita, pos_id";
	$result = $db->select($sqlSelect);
	if (!empty($result)) {
		$html = '<table id="rutero-table" class="table table-striped" style="width:100%">';
		$html .= '<thead class="thead-light">';
		$html .= '<tr>';
		$html .= '<th scope="col">ID</th>';
		$html .= '<th scope="col">id_usuario</th>';
		$html .= '<th scope="col">id_pdv</th>';
		$html .= '<th scope="col">habilitado</th>';
		$html .= '<th scope="col">FECHA VISITA</th>';
		$html .= '<th scope="col">DIA</th>';
		$html .= '<th scope="col">CODIGO PDV</th>';
		$html .= '<th scope="col">CANAL</th>';
		$html .= '<th scope="col">NOMBRE DEL PDV</th>';
		$html .= '<th scope="col">USER</th>';
		$html .= '<th scope="col">id_termometro</th>';
		$html .= '<th scope="col">TERMOMETRO</th>';
		$html .= '<th scope="col">ACCIONES</th>';
		$html .= '</tr>';
		$html .= '</thead>';
		$html .= '<tbody>';
			
		foreach ($result as $row) {
			$termometro = ($row['termometro']==1) ? 'SI' : 'NO';
			$html .= '<tr>';
			$html .= '<td>' . $row['id'] . '</td>';
			$html .= '<td>' . $row['id_usuario'] . '</td>';
			$html .= '<td>' . $row['id_pdv'] . '</td>';
			$html .= '<td>' . $row['habilitado'] . '</td>';
			$html .= '<td>' . $row['fecha_visita'] . '</td>';
			$html .= '<td>' . $row['nombre_dia_visita'] . '</td>';
			$html .= '<td>' . $row['pos_id'] . '</td>';
			$html .= '<td>' . $row['channel'] . '</td>';
			$html .= '<td>' . $row['pos_name'] . '</td>';
			$html .= '<td>' . $row['user'] . '</td>';
			$html .= '<td>' . $row['termometro'] . '</td>';
			$html .= '<td>' . $termometro . '</td>';
			$html .= '<td></td>';
			$html .= '</tr>';
		}
		$html .= '</tbody>';
        $html .= '</table>';
	}

	echo $html;