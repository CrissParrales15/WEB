<?php
	include_once "../includes/db_connect.php";
	include_once "../includes/functions.php";
	include_once "../includes/config.php";

	$query = "SELECT id,pos_id,sales_executive,channel,subchannel,format,pos_name_dpsm,kam,merchandising,customer_owner,pos_name,dpsm,region,tipo, province,city,zone,address,supervisor,latitud,longitud,channel_segment,visual,coordinador,foto,status,perimetro,distancia,activar FROM repositorio_locales_dtt2 limit 1000";
	if ($sql = $mysqli->prepare($query)) {
		$sql->execute();
        $sql->store_result();
        if ($sql->num_rows > 0) {
			$html = '<table id="table" name="table" class="table table-striped" style="width:100%">';
			$html .= '<thead class="thead-light">';
			$html .= '<tr>';

			$html .= '<th scope="col">ID</th>'; 
			$html .= '<th scope="col">CODIGO CP</th>';
			$html .= '<th scope="col">COD CLIENTE</th>';
			$html .= '<th scope="col">CANAL</th>';
			$html .= '<th scope="col">RE</th>';
			$html .= '<th scope="col">FORMATO CP</th>';
			$html .= '<th scope="col">FORMATO CLIENTE</th>';
			$html .= '<th scope="col">DISTRIBUIDOR</th>';
			$html .= '<th scope="col">TARGET</th>';
			$html .= '<th scope="col">NOMBRE COMERCIAL/CLIENTE</th>';
			$html .= '<th scope="col">LOCAL</th>';
			$html .= '<th scope="col">RUTA</th>';
			$html .= '<th scope="col">REGION</th>';
			$html .= '<th scope="col">TIPO</th>';
			$html .= '<th scope="col">PROVINCIA</th>';
			$html .= '<th scope="col">CIUDAD</th>';
			$html .= '<th scope="col">ZONA</th>';
			$html .= '<th scope="col">DIRECCION</th>';
			$html .= '<th scope="col">SUPERVISOR</th>';
			$html .= '<th scope="col">X</th>';
			$html .= '<th scope="col">Y</th>';
			$html .= '<th scope="col">CHANNEL SEGMENT</th>';
			$html .= '<th scope="col">VISUAL ACCESS</th>';
			$html .= '<th scope="col">COORDINADOR</th>';
			$html .= '<th scope="col">FOTO</th>';
			$html .= '<th scope="col">STATUS</th>';
			$html .= '<th scope="col">PERIMETRO</th>';
			$html .= '<th scope="col">DISTANCIA</th>';
			$html .= '<th scope="col">ACTIVAR</th>';
			
			$html .= '</tr>';
			$html .= '</thead>';
			$html .= '<tbody>';

			$sql->bind_result(
				$id,
				$pos_id,
				$sales_executive,
				$channel,
				$subchannel,
				$format,
				$pos_name_dpsm,
				$kam,
				$merchandising,
				$customer_owner,
				$pos_name,
				$dpsm,
				$region,
				$tipo,
				$province,
				$city,
				$zone,
				$address,
				$supervisor,
				$latitud,
				$longitud,
				$channel_segment,
				$visual,
				$coordinador,
				$foto,
				$status,
				$perimetro,
				$distancia,
				$activar) or die($sql->error);

			while ($sql->fetch()) {
				$html .= '<tr>';
				$html .= '<td>' . $id . '</td>';
				$html .= '<td>' . $pos_id . '</td>';
				$html .= '<td>' . $sales_executive . '</td>';
				$html .= '<td>' . $channel . '</td>';
				$html .= '<td>' . $subchannel . '</td>';
				$html .= '<td>' . $format . '</td>';
				$html .= '<td>' . $pos_name_dpsm . '</td>';
				$html .= '<td>' . $kam . '</td>';
				$html .= '<td>' . $merchandising . '</td>';
				$html .= '<td>' . $customer_owner . '</td>';
				$html .= '<td>' . $pos_name . '</td>';
				$html .= '<td>' . $dpsm . '</td>';
				$html .= '<td>' . $region . '</td>';
				$html .= '<td>' . $tipo . '</td>';
				$html .= '<td>' . $province . '</td>';
				$html .= '<td>' . $city . '</td>';
				$html .= '<td>' . $zone . '</td>';
				$html .= '<td>' . $address . '</td>';
				$html .= '<td>' . $supervisor . '</td>';
				$html .= '<td>' . $latitud . '</td>';
				$html .= '<td>' . $longitud . '</td>';
				$html .= '<td>' . $channel_segment . '</td>';
				$html .= '<td>' . $visual . '</td>';
				$html .= '<td>' . $coordinador . '</td>';
				$html .= '<td>' . $foto . '</td>';
				$html .= '<td>' . $status . '</td>';
				$html .= '<td>' . $perimetro . '</td>';
				$html .= '<td>' . $distancia . '</td>';
				$html .= '<td>' . $activar . '</td>';
				$html .= '</tr>';
			}
			$html .= '</tbody>';
        	$html .= '</table>';
		}
		$sql->close();
	}
	echo $html;