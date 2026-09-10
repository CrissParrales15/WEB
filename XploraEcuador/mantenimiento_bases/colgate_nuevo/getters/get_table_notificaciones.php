<?php
	include_once($_SERVER['DOCUMENT_ROOT']."/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/includes/db_connect.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/includes/functions.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/includes/config.php");
	
	$estado = $_POST['estado'];
	$inicial = false;
	$hoy = date("Y-m-d");

	if (!empty($_POST["fechaInicio"]) && 
		!empty($_POST["fechaFin"]) && 
		!empty($_POST["tipo"])) {
		
		$fechaInicio = $_POST['fechaInicio'];
		$fechaFin = $_POST['fechaFin'];
		$tipo = $_POST['tipo'];

		$query = "SELECT id, supervisor, usuario, descripcion, estado, fecha, hora 
			FROM lvi_notificaciones 
			WHERE (STR_TO_DATE(fecha, '%d/%m/%Y') BETWEEN ? AND ?) 
			AND descripcion REGEXP ? 
			AND estado=?";
	} else {
		$inicial = true;
		$query = "SELECT id, supervisor, usuario, descripcion, estado, fecha, hora 
			FROM lvi_notificaciones 
			WHERE (STR_TO_DATE(fecha, '%d/%m/%Y')=?) 
			AND estado=?";
	}
	
    $contador = 0;

	$adicional = ($inicial) ? " para el día de hoy":" en el período seleccionado";
	$html = "<p>No hay registros a mostrar " . $adicional . "</p>";

    if ($sql = $mysqli->prepare($query)) {
		if (!$inicial) {
			$sql->bind_param('sssi', $fechaInicio, $fechaFin, $tipo, $estado);
		} else {
			$sql->bind_param('si', $hoy, $estado);
		}
        $sql->execute();
        $sql->store_result();
        if ($sql->num_rows > 0) {
			$html = '<table id="notification-table" class="table table-striped" style="width:100%">';
			$html .= '<thead class="thead-light">';
			$html .= '<tr>';
			$html .= '<th scope="col">ID</th>';
			$html .= '<th scope="col">SUPERVISOR</th>';
			$html .= '<th scope="col">MERCADERISTA</th>';
			$html .= '<th scope="col">DESCRIPCION</th>';
			$html .= '<th scope="col">ESTADO</th>';
			$html .= '<th scope="col">FECHA Y HORA</th>';
			$html .= '<th scope="col">OPCIONES</th>';
			$html .= '</tr>';
			$html .= '</thead>';
			$html .= '<tbody>';

            $sql->bind_result($id, $supervisor, $usuario, $descripcion, $estado, $fecha, $hora) or die($sql->error);
            while ($sql->fetch()) {
				$est = "No leído";
				if ($estado==1) {
					$est = "Leído";
				}

                $html .= '<tr>';
				$html .= '<td>' . $id . '</td>';
				$html .= '<td>' . $supervisor . '</td>';
				$html .= '<td>' . $usuario . '</td>';
				$html .= '<td>' . $descripcion . '</td>';
				$html .= '<td>' . $est . '</td>';
				$html .= '<td>' . $fecha . ' ' . $hora . '</td>';
				$html .= '<td></td>';
				$html .= '</tr>';
            }
			$html .= '</tbody>';
        	$html .= '</table>';
        }
        $sql->close();
    }
    echo $html;