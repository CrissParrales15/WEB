<?php

	include_once($_SERVER['DOCUMENT_ROOT']."/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/includes/db_connect.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/includes/functions.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/includes/config.php");

	$parametros = $_POST['parametros'];

	$fechaInicio = $parametros[0];
	$fechaFin = $parametros[1];

	$query_supervisor = "SELECT rs.id AS id_supervisor, 
						rs.usuario AS nombre_supervisor 
						FROM rutero_pdv r 
						INNER JOIN repositorio_locales_dtt2 rl ON r.id_pdv=rl.id 
						/*INNER JOIN repositorio_usuarios rs ON rl.supervisor=rs.id*/
						INNER JOIN repositorio_usuarios rs ON r.id_supervisor = rs.id 
						WHERE (r.fecha_visita BETWEEN ? AND ?) AND r.status=1 AND r.habilitado=1 
						AND rs.id_rol = 4 and rs.activo = 1
						
						GROUP BY rs.id
						ORDER BY nombre_supervisor;";
	
	$supervisores = array();
	$contador_orden = 1;

	if ($sql_supervisor = $mysqli->prepare($query_supervisor)) {
		$sql_supervisor->bind_param('ss', $fechaInicio, $fechaFin);
		$sql_supervisor->execute();
		$sql_supervisor->store_result();
		if ($sql_supervisor->num_rows > 0) {
			$sql_supervisor->bind_result(
				$id_supervisor,
				$nombre_supervisor) or die($sql_supervisor->error);

			while($sql_supervisor->fetch()) {
				$supervisor = new Supervisor();
				$supervisor->id = $id_supervisor;
				$supervisor->nombre = $nombre_supervisor;
				 
				// $query = "SELECT 
				// 		ru.id AS id_gestor, 
				// 		ru.mercaderista AS nombre_gestor, 
				// 		r.id AS id_rutero, 
				// 		rl.pos_name AS nombre, 
				// 		rl.visual AS visual, 
				// 		rl.pos_id AS codigo,	
				// 		rl.subchannel AS subchannel,
				// 		r.fecha_visita AS fecha, 
				// 		r.hora_inicio_visita AS h_ini,      
				// 		r.hora_fin_visita AS h_fin, 
				// 		r.distancia AS distancia, 
				// 		re.descripcion AS estado,     
				// 		r.tipo_relevo AS tipo_relevo, 
				// 		rl.address AS direccion, 
				// 		rl.latitud AS lat,  
				// 		rl.longitud AS lon, 
				// 		'' AS foto_no_visita       
				// 		FROM rutero_pdv r 
				// 		INNER JOIN repositorio_locales_dtt2 rl ON r.id_pdv=rl.id 
				// 		INNER JOIN repositorio_usuarios ru ON r.id_usuario=ru.id 
				// 		INNER JOIN repositorio_estados re ON r.id_estado=re.id 
				// 		/*WHERE rl.supervisor=? AND (r.fecha_visita BETWEEN ? AND ?) AND r.status=1 AND r.habilitado=1 AND ru.id_rol = 2*/     
				// 		WHERE r.id_supervisor=? AND (r.fecha_visita BETWEEN ? AND ?) AND r.status=1 AND r.habilitado=1 AND ru.id_rol = 2     
				// 		ORDER BY nombre_gestor";

				$query = "
							SELECT 
								ru.id AS id_gestor, 
								ru.mercaderista AS nombre_gestor, 
								r.id AS id_rutero, 
								rl.pos_name AS nombre, 
								rl.visual AS visual, 
								rl.pos_id AS codigo,	
								rl.subchannel AS subchannel,
								r.fecha_visita AS fecha, 
								r.hora_inicio_visita AS h_ini,      
								r.hora_fin_visita AS h_fin, 
								r.distancia AS distancia, 
								re.descripcion AS estado,     
								r.tipo_relevo AS tipo_relevo, 
								rl.address AS direccion, 
								rl.latitud AS lat,  
								rl.longitud AS lon,
								'' AS foto_no_visita
							FROM rutero_pdv r 
							INNER JOIN repositorio_locales_dtt2 rl ON r.id_pdv = rl.id 
							INNER JOIN repositorio_usuarios ru ON r.id_usuario = ru.id 
							INNER JOIN repositorio_estados re ON r.id_estado = re.id 
							WHERE (r.fecha_visita BETWEEN ? AND ?) 
								AND r.status = 1 
								AND r.habilitado = 1 
								AND ru.id_rol = 2
								AND ru.id NOT IN (101,105,103,4)
								AND r.id_estado NOT IN (4)
								
								AND /*(
									CASE 
										WHEN r.id_supervisor != rl.supervisor THEN rl.supervisor
										ELSE r.id_supervisor
									END
								) = ?*/
								 ru.id_supervisor=?
							ORDER BY nombre_gestor"; 
 
				if ($sql = $mysqli->prepare($query)) {
					// $sql->bind_param('iss', $id_supervisor, $fechaInicio, $fechaFin);
					$sql->bind_param('ssi', $fechaInicio, $fechaFin, $id_supervisor);
					$sql->execute();
					$sql->store_result();
					if ($sql->num_rows > 0) {
						$sql->bind_result(
							$id_gestor,
							$nombre_gestor,
							$id_rutero,
							$nombre,
							$visual,
							$codigo,
							$subchannel,
							$fecha,
							$h_ini,
							$h_fin,
							$distancia,
							$estado,
							$tipo_relevo,
							$direccion,
							$lat,
							$lon,
							$foto_no_visita) or die($sql->error);

						$pdvs = array();
						$gestores = array();

						$id_gestor_ingresado;

						$contador = 1;

						while($sql->fetch()) {
							$minutes = "";
							if ($id_gestor != $id_gestor_ingresado) {
								$pdvs = array();
								$contador = 1;
							} 
							/*DURACION*/
							if (!empty($h_ini) && !empty($h_fin)) {
								$dateTimeObject1 = date_create($h_ini); 
								$dateTimeObject2 = date_create($h_fin); 							
								$difference = date_diff($dateTimeObject1, $dateTimeObject2); 
								$minutes = $difference->days * 24 * 60;
								$minutes += $difference->h * 60;
								$minutes += $difference->i;
							}

							$fecha_formateada = $fecha; // Valor por defecto
							if (!empty($fecha)) {
								$dateObj = DateTime::createFromFormat('Y-m-d', $fecha);
								if ($dateObj) {
									$fecha_formateada = $dateObj->format('d/m/Y');
								}
							}

							/*NUEVO PDV*/
							$pdv = new Pdv();
							$pdv->id = $contador;
							$pdv->ordenTotal = $contador_orden;
							$pdv->codigo = $codigo;
							$pdv->subchannel = $subchannel;
							$pdv->nombre = $nombre;
							$pdv->visual = $visual;
							$pdv->fecha = $fecha;
							$pdv->h_ini = $h_ini;
							$pdv->h_fin = $h_fin;
							$pdv->duracion = $minutes;
							$pdv->distancia = $distancia;
							$pdv->estado = $estado;
							$pdv->tipo_relevo = $tipo_relevo;
							$pdv->direccion = $direccion;
							$pdv->lat = $lat;
							$pdv->lon = $lon;
							$pdv->foto_no_visita = $foto_no_visita;
							array_push($pdvs, $pdv);

							if (!$gestores) {
								// echo "1) NO EXISTE: " . $id_gestor . " " . $nombre_gestor . "</br></br>";
								$gestor = new Gestor();
								$gestor->id = $id_gestor;
								$gestor->nombre = $nombre_gestor;
								// $gestor->visual = $visual;
								$gestor->cant_visitas = $contador;
								$gestor->pdv = $pdvs;
								array_push($gestores, $gestor);
								$id_gestor_ingresado = $id_gestor;
							} else {
								if ($id_gestor != $id_gestor_ingresado) {
									// echo "</br>1) NO EXISTE: " . $id_gestor . " " . $nombre_gestor . "</br></br>";
									$id_gestor_ingresado = $id_gestor;
									$gestor = new Gestor();
									$gestor->id = $id_gestor;
									$gestor->nombre = $nombre_gestor;
									// $gestor->visual = $visual;
									$gestor->cant_visitas = $contador;
									$gestor->pdv = $pdvs;
									array_push($gestores, $gestor);
									// $contador = 1;
								} else {
									$gestor->cant_visitas = $contador;
									$gestor->pdv = $pdvs;
									// echo "</br>1) YA EXISTE: " . $id_gestor . " " . $nombre_gestor . "</br></br>";
								}
							}
							$contador++;
							$contador_orden++;
						}
					}
					$sql->close();
				}

				$supervisor->gestor = $gestores;
				array_push($supervisores, $supervisor);
			}
		}
		$sql_supervisor->close();
	}

	// $supervisor = new Supervisor();
	// $supervisor->id = 1;
	// $supervisor->nombre = "SUPERVISOR 1";
    // $supervisor->gestor = $gestores;

    $batch = json_encode($supervisores);

	echo $batch;

	class Supervisor {
		public $id;
		public $nombre = "";
		public $gestor = "";
	}

	class Gestor {
		public $id;
		public $nombre = "";
		public $cant_visitas;
		public $pdv = "";
	}

	class Pdv{
		public $id;
		public $ordenTotal;
		public $codigo;
		public $subchannel;
		public $nombre = "";
		public $visual = "";
		public $fecha = "";
		public $h_ini = "";
		public $h_fin = "";
		public $duracion = "";
		public $distancia = "";
		public $estado = "";
		public $tipo_relevo = "";
		public $direccion = "";
		public $lon = "";
		public $lat = "";
		public $foto_no_visita = "";
	}


	// $array = array("TAG1", "TAG2", "TAG3");
    // $objects = array();
    // for($i=0; $i<count($array); $i++) {
	// 	$myObj = new stdClass();
	// 	$myObj->name = $array[$i];
	// 	$objects[] = $myObj;
    // }
    // $obj = new stdClass();
    // $obj -> create = array($objects);
    // $batch = json_encode($obj);


	// // $pdvs = array();

	// // $pdv1 = new Pdv();
	// // $pdv1->id = 1;
	// // $pdv1->ordenTotal = 1;
	// // $pdv1->nombre = "PDV 1";
	// // array_push($pdvs, $pdv1);

	// // $pdv2 = new Pdv();
	// // $pdv2->id = 2;
	// // $pdv2->ordenTotal = 2;
	// // $pdv2->nombre = "PDV 2";
	// // array_push($pdvs, $pdv2);

	// $gestores = array();
	// for($i=0; $i<count($pdvs); $i++) {
	// 	$gestor = new Gestor();
	// 	$gestor->id = 1;
	// 	$gestor->nombre = "GESTOR 1: ".$i;
	// 	$gestor->pdv = $pdvs[$i];
	// 	$gestores[] = $gestor;
    // }

	// // $gestores = array();
	// // $gestor = new Gestor();
	// // $gestor->id = 1;
	// // $gestor->nombre = "GESTOR 1";
    // // // $gestor->pdv = array($pdvs);
    // // $gestor->pdv = $pdvs;
	// // array_push($gestores, $gestor);

	// // $supervisor = new Supervisor();
	// // $supervisor->id = 1;
	// // $supervisor->nombre = "SUPERVISOR 1";
    // // $supervisor->gestor = $gestores;

    // // $batch = json_encode($supervisor);

	// // echo $batch;
















	// $pdvs = array();

	// $pdv1 = new Pdv();
	// $pdv1->id = 1;
	// $pdv1->ordenTotal = 1;
	// $pdv1->nombre = "PDV 1";
	// array_push($pdvs, $pdv1);

	// $pdv2 = new Pdv();
	// $pdv2->id = 2;
	// $pdv2->ordenTotal = 2;
	// $pdv2->nombre = "PDV 2";
	// array_push($pdvs, $pdv2);

	// $gestores = array();
	// for($i=0; $i<count($pdvs); $i++) {
	// 	$gestor = new Gestor();
	// 	$gestor->id = 1;
	// 	$gestor->nombre = "GESTOR 1: ".$i;
	// 	$gestor->pdv = $pdvs[$i];
	// 	$gestores[] = $gestor;
    // }
	// $obj = new Gestor();
	// $obj->id = 1;
	// $obj->nombre = "GESTOR 1";
    // $obj->pdv = array($gestores);
    // $batch = json_encode($obj);

	// echo $batch;

?>