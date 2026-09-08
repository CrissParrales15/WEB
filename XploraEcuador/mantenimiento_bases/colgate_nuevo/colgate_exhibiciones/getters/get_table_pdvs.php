<?php
	include_once "../includes/db_connect.php";
	include_once "../includes/functions.php";
	include_once "../includes/config.php";

	$query = "SELECT id, channel, subchannel, channel_segment, format, customer_owner, pos_id, pos_name, pos_name_dpsm, zone, region, province, city, address, kam, sales_executive, merchandising, supervisor, dpsm, status, tipo, latitud, longitud, foto, segmentacion, compras, activar, numero_controller, distancia, perimetro FROM repositorio_locales_dtt2";
	if ($sql = $mysqli->prepare($query)) {
		$sql->execute();
        $sql->store_result();
        if ($sql->num_rows > 0) {
			$html = '<table id="table" name="table" class="table table-striped" style="width:100%">';
			$html .= '<thead class="thead-light">';
			$html .= '<tr>';

			$html .= '<th scope="col">ID</th>';
			$html .= '<th scope="col">CANAL</th>';
			$html .= '<th scope="col">CADENA/CLIENTE</th>';
			$html .= '<th scope="col">FORMATO</th>';
			$html .= '<th scope="col">CODIGO PYDACO</th>';
			$html .= '<th scope="col">VENDEDOR</th>';
			$html .= '<th scope="col">POS_ID</th>';
			$html .= '<th scope="col">LOCAL</th>';
			$html .= '<th scope="col">AGENCIA DESPACHO PYDACO</th>';
			$html .= '<th scope="col">ZONA</th>';
			$html .= '<th scope="col">REGION</th>';
			$html .= '<th scope="col">PROVINCIA</th>';
			$html .= '<th scope="col">CIUDAD</th>';
			$html .= '<th scope="col">DIRECCION</th>';
			$html .= '<th scope="col">SEGMENTACION</th>';
			$html .= '<th scope="col">SUPERVISOR ALICORP</th>';
			$html .= '<th scope="col">MERCHANDISING</th>';
			$html .= '<th scope="col">SUPERVISOR LUCKY</th>';
			$html .= '<th scope="col">RAZON SOCIAL</th>';
			$html .= '<th scope="col">STATUS</th>';
			$html .= '<th scope="col">TIPO</th>';
			$html .= '<th scope="col">LATITUD</th>';
			$html .= '<th scope="col">LONGITUD</th>';
			$html .= '<th scope="col">FOTO</th>';
			$html .= '<th scope="col">SEGMENTACION2</th>';
			$html .= '<th scope="col">COMPRAS</th>';
			$html .= '<th scope="col">ACTIVAR</th>';
			$html .= '<th scope="col">NUMERO CONTROLLER</th>';
			$html .= '<th scope="col">DISTANCIA</th>';
			$html .= '<th scope="col">PERIMETRO</th>';
			
			$html .= '</tr>';
			$html .= '</thead>';
			$html .= '<tbody>';

			$sql->bind_result(
				$id, 
				$channel, 
				$subchannel, 
				$channel_segment, 
				$format, 
				$customer_owner, 
				$pos_id, 
				$pos_name, 
				$pos_name_dpsm, 
				$zone, 
				$region, 
				$province, 
				$city, 
				$address, 
				$kam, 
				$sales_executive, 
				$merchandising, 
				$supervisor, 
				$dpsm, 
				$status, 
				$tipo, 
				$latitud, 
				$longitud, 
				$foto, 
				$segmentacion, 
				$compras, 
				$activar, 
				$numero_controller, 
				$distancia, 
				$perimetro) or die($sql->error);

			while ($sql->fetch()) {
				$html .= '<tr>';
				$html .= '<td>' . utf8_encode($id) . '</td>';
				$html .= '<td>' . utf8_encode($channel) . '</td>';
				$html .= '<td>' . utf8_encode($subchannel) . '</td>';
				$html .= '<td>' . utf8_encode($channel_segment) . '</td>';
				$html .= '<td>' . utf8_encode($format) . '</td>';
				$html .= '<td>' . utf8_encode($customer_owner) . '</td>';
				$html .= '<td>' . utf8_encode($pos_id) . '</td>';
				$html .= '<td>' . utf8_encode($pos_name) . '</td>';
				$html .= '<td>' . utf8_encode($pos_name_dpsm) . '</td>';
				$html .= '<td>' . utf8_encode($zone) . '</td>';
				$html .= '<td>' . utf8_encode($region) . '</td>';
				$html .= '<td>' . utf8_encode($province) . '</td>';
				$html .= '<td>' . utf8_encode($city) . '</td>';
				$html .= '<td>' . utf8_encode($address) . '</td>';
				$html .= '<td>' . utf8_encode($kam) . '</td>';
				$html .= '<td>' . utf8_encode($sales_executive) . '</td>';
				$html .= '<td>' . utf8_encode($merchandising) . '</td>';
				$html .= '<td>' . utf8_encode($supervisor) . '</td>';
				$html .= '<td>' . utf8_encode($dpsm) . '</td>';
				$html .= '<td>' . utf8_encode($status) . '</td>';
				$html .= '<td>' . utf8_encode($tipo) . '</td>';
				$html .= '<td>' . utf8_encode($latitud) . '</td>';
				$html .= '<td>' . utf8_encode($longitud) . '</td>';
				$html .= '<td>' . utf8_encode($foto) . '</td>';
				$html .= '<td>' . utf8_encode($segmentacion) . '</td>';
				$html .= '<td>' . utf8_encode($compras) . '</td>';
				$html .= '<td>' . utf8_encode($activar) . '</td>';
				$html .= '<td>' . utf8_encode($numero_controller) . '</td>';
				$html .= '<td>' . utf8_encode($distancia) . '</td>';
				$html .= '<td>' . utf8_encode($perimetro) . '</td>';
				$html .= '</tr>';
			}
			$html .= '</tbody>';
        	$html .= '</table>';
		}
		$sql->close();
	}
	echo $html;