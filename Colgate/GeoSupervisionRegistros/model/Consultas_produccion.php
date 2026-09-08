<?php



  $op =  isset($_GET['op']) ? $_GET['op'] : '';
 


   function obtenerSupervisor(){
    
    require_once '../conection/conexion.php';

    $result = $conn->query("SELECT supervisor FROM repositorio_locales_dtt WHERE activar ='SI' AND supervisor NOT LIKE '%PRUEBA%' GROUP BY(supervisor)");
    
    while($rows = $result->fetch_assoc()){
        $data[] = $rows;
    }

    $result->close();
    $conn->close();
    
    return json_encode($data);


   }


   function mercaderistaPorSup(){

    require_once '../conection/conexion.php';

    $value =  isset($_GET['value']) ? $_GET['value'] : '';
    
    $result = $conn->query("SELECT pos_name,supervisor,mercaderista,longitud,latitud,foto from repositorio_locales_dtt WHERE supervisor='$value' AND supervisor NOT LIKE '%PRUEBA%' AND activar = 'SI' GROUP BY(mercaderista);");
    
    

    while($rows = $result->fetch_assoc()){
        $data[] = $rows;
    }

    $result->close();
    
    return json_encode($data);

   }



   function obtenerRegistros(){

    require_once '../conection/conexion.php';

    $supervisor = $_POST['supervisor'];
    $mercaderista = $_POST['mercaderista'];
    $fecha = $_POST['fecha'];
    $newDate = date("d/m/Y", strtotime($fecha));
    
    $result = $conn->query(" SELECT vr.id_pdv AS pos_id, vr.channel, vr.customer_owner, vr.pos_name, vr.pos_name_dpsm, vr.region, 
    vr.provincia AS province, vr.ciudad AS city, vr.direccion AS address, vr.supervisor, vr.mercaderista, vr.lat_pdv AS latitud, 
    vr.lng_pdv AS longitud, vr.foto, s.activar, vr.tipo, vr.causal, vr.version, vr.latitude, vr.longitude, vr.fecha,vr.hora, 
    vr.fechaservidor from repositorio_locales_dtt s inner join  insert_registro vr on s.pos_id =  vr.id_pdv
    WHERE vr.supervisor = '$supervisor' AND vr.mercaderista='$mercaderista' AND vr.fecha='$newDate' GROUP BY vr.latitude,vr.longitude;");
    
    

    while($rows = $result->fetch_assoc()){
        $data[] = $rows;
    }

    $result->close();
    
    return json_encode($data);
   }



   switch ($op) {
    case 'obtenerSupervisor': echo obtenerSupervisor();
      break;
    case 'mercaderistaPorSup': echo mercaderistaPorSup();
      break;
    case 'obtenerRegistros': echo obtenerRegistros();
      break;
  }

  


?>