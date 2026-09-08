<?php
	include_once "../includes/db_connect.php";
	include_once "../includes/functions.php";
	include_once "../includes/config.php";

	$query = "SELECT id,id_rol,usuario,password,nombre,apellido,mercaderista,cedula,telefono,device_id,color,status,activo,id_supervisor FROM repositorio_usuarios";
	if ($sql = $mysqli->prepare($query)) {
		$sql->execute();
        $sql->store_result();
        if ($sql->num_rows > 0) {
			$html = '<table id="table" name="table" class="table table-striped" style="width:100%">';
			$html .= '<thead class="thead-light">';
			$html .= '<tr>';

			$html .= '<th scope="col">ID</th>';
			$html .= '<th scope="col">ID ROL</th>';
			$html .= '<th scope="col">USUARIO</th>';
			$html .= '<th scope="col">PASSWORD</th>';
			$html .= '<th scope="col">NOMBRE</th>';
			$html .= '<th scope="col">APELLIDO</th>';
			$html .= '<th scope="col">NOMBRE COMPLETO</th>';
			$html .= '<th scope="col">CEDULA</th>';
			$html .= '<th scope="col">TELEFONO</th>';
			$html .= '<th scope="col">DEVICE ID</th>';
			$html .= '<th scope="col">COLOR</th>';
			$html .= '<th scope="col">STATUS</th>';
			$html .= '<th scope="col">ACTIVO</th>';
			$html .= '<th scope="col">ID SUPERVISOR</th>';

			$html .= '</tr>';
			$html .= '</thead>';
			$html .= '<tbody>';

			$sql->bind_result($id, $id_rol, $usuario, $password, $nombre, $apellido, $mercaderista, $cedula, $telefono, $device_id, $color, $status, $activo, $id_supervisor) or die($sql->error);

			while ($sql->fetch()) {
				$html .= '<tr>';
				$html .= '<td>' . $id . '</td>';
				$html .= '<td>' . $id_rol . '</td>';
				$html .= '<td>' . $usuario . '</td>';
				$html .= '<td>' . $password . '</td>';
				$html .= '<td>' . $nombre . '</td>';
				$html .= '<td>' . $apellido . '</td>';
				$html .= '<td>' . $mercaderista . '</td>';
				$html .= '<td>' . $cedula . '</td>';
				$html .= '<td>' . $telefono . '</td>';
				$html .= '<td>' . $device_id . '</td>';
				$html .= '<td>' . $color . '</td>';
				$html .= '<td>' . $status . '</td>';
				$html .= '<td>' . $activo . '</td>';
				$html .= '<td>' . $id_supervisor . '</td>';
				$html .= '</tr>';
			}
			$html .= '</tbody>';
        	$html .= '</table>';
		}
		$sql->close();
	}
	echo $html;