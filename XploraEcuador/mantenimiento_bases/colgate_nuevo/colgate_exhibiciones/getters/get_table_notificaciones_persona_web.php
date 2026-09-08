<?php
	include_once "../includes/db_connect.php";
	include_once "../includes/functions.php";
	include_once "../includes/config.php";

	$supervisor = $_POST['supervisor'];
	$fechaInicio = $_POST['fechaInicio'];
	$fechaFin = $_POST['fechaFin'];


//	$query = "SELECT id, codigo, mercaderista, nombre_pdv, cadena, supervisor, fecha_visita, hora_llegada_min, hora_llegada, hora_llegada_max, hora_inicio_visita, hora_salida_min, hora_salida, hora_salida_max, hora_fin_visita, observacion_entrada, observacion_salida, leido FROM lvi_notificaciones_visitas_calificadas WHERE supervisor REGEXP ? AND  fecha_visita BETWEEN ? AND ?";
    $query = "		SELECT l.id, l.codigo, l.mercaderista, l.nombre_pdv, l.cadena, l.supervisor, l.fecha_visita, 
					l.hora_llegada_min, l.hora_llegada, l.hora_llegada_max, l.hora_inicio_visita, l.hora_salida_min, 
					l.hora_salida,l.hora_salida_max, l.hora_fin_visita, l.observacion_entrada, l.observacion_salida, l.leido 
					FROM lvi_notificaciones_visitas_calificadas l 
					INNER JOIN repositorio_supervisor rs ON l.supervisor = rs.supervisor
					WHERE l.supervisor REGEXP ? AND rs.habilitado = 1
					AND l.fecha_visita BETWEEN ? AND ?";
	if ($sql = $mysqli->prepare($query)) {
		$sql->bind_param('sss',$supervisor, $fechaInicio, $fechaFin);
		$sql->execute();
        $sql->store_result();
        if ($sql->num_rows > 0) {
			$html = '<table id="table" name="table" class="table table-striped" style="width:100%">';
			$html .= '<thead class="thead-light">';
			$html .= '<tr>';

			$html .= '<th>id</th>';
			$html .= '<th scope="col">Codigo</th>';
			$html .= '<th scope="col">Auditor</th>';
			$html .= '<th scope="col">Nombre PDV</th>';
			$html .= '<th scope="col">Cadena</th>';
			$html .= '<th scope="col">Coordinador</th>';
			$html .= '<th scope="col">Fecha</th>';
			$html .= '<th scope="col">Hora Llegada Minima</th>';
			$html .= '<th scope="col">Hora Llegada</th>';
			$html .= '<th scope="col">Hora Llegada Maxima</th>';
			$html .= '<th scope="col">Hora Inicio Visita</th>';
			$html .= '<th scope="col">Hora Salida Minima</th>';
			$html .= '<th scope="col">Hora Salida</th>';
			$html .= '<th scope="col">Hora Salida Maxima</th>';
			$html .= '<th scope="col">Hora Fin Visita</th>';
			$html .= '<th scope="col">Observacion Entrada</th>';
			$html .= '<th scope="col">Observacion Salida</th>';
			$html .= '<th scope="col">Estado</th>';
			$html .= '<th scope="col">Opciones</th>';
			
			$html .= '</tr>';
			$html .= '</thead>';
			$html .= '<tbody>';

			$sql->bind_result(
				$id,
				$codigo,
				$mercaderista,
				$nombre_pdv,
				$cadena,
				$supervisor,
				$fecha,
				$hora_llegada_min,
				$hora_llegada,
				$hora_llegada_max,
				$hora_inicio_visita,
				$hora_salida_min,
				$hora_salida,
				$hora_salida_max,
				$hora_fin_visita,
				$observacion_entrada,
				$observacion_salida,
				$leido) or die($sql->error);

			while ($sql->fetch()) {
				$est = "No leido";
				if ($leido==1) {
					$est = "Leido";
				}

				$html .= '<tr>';
				$html .= '<td>' . utf8_encode($id) . '</td>';
				$html .= '<td>' . utf8_encode($codigo) . '</td>';
				$html .= '<td>' . utf8_encode($mercaderista) . '</td>';
				$html .= '<td>' . utf8_encode($nombre_pdv) . '</td>';
				$html .= '<td>' . utf8_encode($cadena) . '</td>';
				$html .= '<td>' . utf8_encode($supervisor) . '</td>';
				$html .= '<td>' . utf8_encode($fecha) . '</td>';
				$html .= '<td>' . utf8_encode($hora_llegada_min) . '</td>';
				$html .= '<td>' . utf8_encode($hora_llegada) . '</td>';
				$html .= '<td>' . utf8_encode($hora_llegada_max) . '</td>';
				$html .= '<td>' . utf8_encode($hora_inicio_visita) . '</td>';
				$html .= '<td>' . utf8_encode($hora_salida_min) . '</td>';
				$html .= '<td>' . utf8_encode($hora_salida) . '</td>';
				$html .= '<td>' . utf8_encode($hora_salida_max) . '</td>';
				$html .= '<td>' . utf8_encode($hora_fin_visita) . '</td>';
				$html .= '<td>' . utf8_encode($observacion_entrada) . '</td>';
				$html .= '<td>' . utf8_encode($observacion_salida) . '</td>';
				$html .= '<td>' . utf8_encode($est) . '</td>';
				$html .= '<td></td>';
				$html .= '</tr>';
			}
			$html .= '</tbody>';
        	$html .= '</table>';
		}
		$sql->close();
	}
	echo $html;