<?php

$dv =  isset($_GET['dv']) ? $_GET['dv'] : '';
$sprv =  isset($_GET['sprv']) ? $_GET['sprv'] : '';
$merc = isset($_GET['merc']) ? $_GET['merc'] : '';
$fecha = isset($_GET['fecha']) ? $_GET['fecha'] : '';
$horaI = isset($_GET['horaI']) ? $_GET['horaI'] : '';
$horaF = isset($_GET['horaF']) ? $_GET['horaF'] : '';

//consulta supervisor 
function consultarSupervisor()
{

  require_once 'conexion.php';

  $result = $conn->query("SELECT supervisor FROM pdv_semvra GROUP BY supervisor");
  while ($r = $result->fetch_assoc()) {
    $data[] = $r;
  }

  return json_encode($data);

  $result->close();
  $conn->close();
}

//consulta mercaderista segun supervisor

function consultarMercaderista($sprv)
{

  require_once 'conexion.php';


  $result = $conn->query("SELECT mercaderista FROM pdv_semvra WHERE supervisor = '$sprv' GROUP BY mercaderista");
  while ($r = $result->fetch_assoc()) {
    $dato[] = $r;
  }

  return json_encode($dato);

  $result->close();
  $conn->close();
}


function consultarPdv($merc, $sprv)
{

  require_once 'conexion.php';

  $merc = isset($_GET['merc']) ? $_GET['merc'] : '';
  $sprv = isset($_GET['sprv']) ? $_GET['sprv'] : '';
  $data = [];

  if ($merc == 'all') {
    $result = $conn->query("SELECT pos_name,pos_id,supervisor,address,latitud,longitud,foto,mercaderista,city FROM pdv_semvra WHERE supervisor='$sprv'");
    while ($rows = $result->fetch_assoc()) {
      $data[] = $rows;
    }
    return json_encode($data);
  } else {
    $result = $conn->query("SELECT pos_name,pos_id,supervisor,address,latitud,longitud,foto,mercaderista,city FROM pdv_semvra WHERE mercaderista='$merc'");
    while ($rows = $result->fetch_assoc()) {
      $data[] = $rows;
    }
    return json_encode($data);
  }

  $result->close();
  $conn->close();
}

//consulta final, con supervisor, mercaderista y fecha     
function consultarRastreo($merc, $sprv, $fecha, $horaI, $horaF)
{
  require_once 'conexion.php';

  $merc = isset($_GET['merc']) ? $_GET['merc'] : '';
  $sprv = isset($_GET['sprv']) ? $_GET['sprv'] : '';
  $data = [];


  if ($merc == 'all') {
    $result = $conn->query("SELECT * FROM vilasecarastreomuestras 
    WHERE STR_TO_DATE(fecha, '%d/%m/%Y') = '$fecha' 
    AND hora BETWEEN '$horaI' AND '$horaF' 
    AND latitude != 'NULL' 
    AND longitude != 'NULL' 
    AND usuario = ANY (
      SELECT mercaderista FROM pdv_semvra 
      WHERE supervisor = '$sprv')
    GROUP BY latitude,longitude;");
    
    while ($rows = $result->fetch_assoc()) {
      $data[] = $rows;
    }
    if ($data == NULL) {
      $data = 'cero registros';
    }
    return json_encode($data);
    } 

    else {
    $result = $conn->query("SELECT * from vilasecarastreomuestras 
    WHERE usuario='$merc' 
    AND STR_TO_DATE(fecha, '%d/%m/%Y') = '$fecha' 
    AND hora BETWEEN '$horaI' 
    AND '$horaF' AND latitude != 'NULL' 
    AND longitude != 'NULL' GROUP BY latitude,longitude");

    $data = [];

    while ($rows = $result->fetch_assoc()) {
      $data[] = $rows;
    }
    if ($data == NULL) {
      $data = 'cero registros';
    }

    return json_encode($data);
  }

  $result->close();
  $conn->close();
}

switch ($dv) {
  case  "consultarSupervisor":
    echo consultarSupervisor();
    break;
  case "consultarMercaderista":
    echo consultarMercaderista($sprv);
    break;
  case "consultarPdv":
    echo consultarPdv($merc, $sprv);
    break;
  case "consultarRastreo":
    echo consultarRastreo($merc, $sprv, $fecha, $horaI, $horaF);
    break;
}
