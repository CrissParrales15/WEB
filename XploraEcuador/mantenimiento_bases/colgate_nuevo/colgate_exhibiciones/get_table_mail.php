<?php
	use Phppot\DataSource;

	require_once 'DataSource.php';

	$html = '';

	$db = new DataSource();
	$conn = $db->getConnection();

	$sqlSelect = "SELECT * FROM alertas_correos";
	$result = $db->select($sqlSelect);
	if (!empty($result)) {
		$html = '<table id="mail-table" class="table table-striped">';
		$html .= '<thead class="thead-light">';
		$html .= '<tr>';
		$html .= '<th scope="col">CARGO</th>';
		$html .= '<th scope="col">NOMBRE</th>';
		$html .= '<th scope="col">CANAL</th>';
		$html .= '<th scope="col">ZONA</th>';
		$html .= '<th scope="col">CORREO</th>';
		$html .= '<th scope="col">CORREO COPIA</th>';
		$html .= '<th scope="col">CORREO COPIA OCULTA</th>';
		$html .= '</tr>';
		$html .= '</thead>';
		$html .= '<tbody>';
			
		foreach ($result as $row) {
			$html .= '<tr>';
			$html .= '<td>' . $row['cargo'] . '</td>';
			$html .= '<td>' . $row['nombre'] . '</td>';
			$html .= '<td>' . $row['canal'] . '</td>';
			$html .= '<td>' . $row['zona'] . '</td>';
			$html .= '<td>' . $row['correo'] . '</td>';
			$html .= '<td>' . $row['correos_cc'] . '</td>';
			$html .= '<td>' . $row['correos_cco'] . '</td>';
			$html .= '</tr>';
		}
		$html .= '</tbody>';
        $html .= '</table>';
	}

	echo $html;