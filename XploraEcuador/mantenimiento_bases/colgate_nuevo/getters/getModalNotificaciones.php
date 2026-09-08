<?php
	include_once($_SERVER['DOCUMENT_ROOT']."/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/includes/db_connect.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/includes/functions.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/includes/config.php");
		
    $query = "SELECT id, usuario, descripcion, fecha, hora FROM insert_notificaciones WHERE estado=0 GROUP BY usuario, descripcion, fecha, hora ORDER BY fecha, hora DESC";
	
    $contador = 0;

    $html = '<table id="notification-table" class="table table-striped">';
    $html .= '<thead class="thead-light">';
    $html .= '<tr>';
    $html .= '<th scope="col">USUARIO</th>';
    $html .= '<th scope="col">DESCRIPCION</th>';
    $html .= '<th scope="col">FECHA Y HORA</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';

    if ($sql = $mysqli->prepare($query)) {
        $sql->execute();
        $sql->store_result();
        if ($sql->num_rows > 0) {
            $sql->bind_result($id, $usuario, $descripcion, $fecha, $hora) or die($sql->error);
            while($sql->fetch()) {
                $html .= "<tr>";
                $html .= "<td>" . $usuario . "</td>";
                $html .= "<td>" . $descripcion . "</td>";
                $html .= "<td>" . $fecha . " " . $hora . "</td>";
                $html .= "</tr>";                
            }
        }
        $sql->close();
    }
    $html .= '</tbody>';
    $html .= '</table>';

    echo $html;
	
?>