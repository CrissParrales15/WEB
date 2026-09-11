<?php



  $op =  isset($_GET['op']) ? $_GET['op'] : '';
 


   function obtenerSupervisor(){ 
    
    require_once '../conection/conexion.php';

    $result = $conn->query("SELECT id, usuario FROM repositorio_usuarios WHERE id_rol = 4 AND STATUS = 1 AND ACTIVO = 1 
                            AND usuario NOT REGEXP 'PRUEBA|LUCKY|TEST' ORDER BY usuario ASC;");
    
    while($rows = $result->fetch_assoc()){
        $data[] = $rows;
    }

    $result->close();
    $conn->close();
    
    return json_encode($data);


   }


  //  function mercaderistaPorSup(){

  //   require_once '../conection/conexion.php';

  //   $value =  isset($_GET['value']) ? $_GET['value'] : '';
    
  //   // $result = $conn->query("SELECT pos_name,supervisor,user AS mercaderista,longitud,latitud,foto from repositorio_locales_dtt WHERE supervisor='$value' AND supervisor NOT LIKE '%PRUEBA%' AND activar = 'SI' GROUP BY(user) ORDER BY user;");
  //   $result = $conn->query("SELECT 
  //                             rl2.pos_name, 
  //                             rl2.supervisor,
  //                             ru.usuario AS mercaderista,
  //                             rl2.longitud,
  //                             rl2.latitud,
  //                             rl2.foto
  //                           FROM repositorio_usuarios ru
  //                             INNER JOIN rutero_pdv r ON r.id_usuario = ru.id
  //                             INNER JOIN repositorio_locales_dtt2 rl2 ON r.id_pdv = rl2.id
  //                           WHERE usuario='$value' AND supervisor NOT LIKE '%PRUEBA%' AND activar = 'SI' GROUP BY(usuario) ORDER BY usuario;");
    
    

  //   while($rows = $result->fetch_assoc()){
  //       $data[] = $rows;
  //   }

  //   $result->close();
    
  //   return json_encode($data);

  //  }
//   function mercaderistaPorSup(){
//     require_once '../conection/conexion.php';

//     $value = isset($_GET['value']) ? $_GET['value'] : '';
    
//     $sql = "SELECT 
//               rl2.pos_name, 
//               rl2.supervisor,
//               ru.usuario AS mercaderista,  -- Esto devuelve el NOMBRE del mercaderista
//               rl2.longitud,
//               rl2.latitud,
//               rl2.foto
//             FROM repositorio_usuarios ru
//               INNER JOIN rutero_pdv r ON r.id_usuario = ru.id
//               INNER JOIN repositorio_locales_dtt2 rl2 ON r.id_pdv = rl2.id
//             WHERE rl2.supervisor = '$value' 
//               AND rl2.activar = 'SI' 
//               AND ru.usuario NOT LIKE '%PRUEBA%'
//             GROUP BY ru.usuario 
//             ORDER BY ru.usuario";
    
//     $data = array();
    
//     if ($result = $conn->query($sql)) {
//         while($rows = $result->fetch_assoc()){
//             $data[] = $rows;
//         }
//         $result->close();
//     }
    
//     $conn->close();
    
//     return json_encode($data);
// }

function mercaderistaPorSup() {
    require_once '../conection/conexion.php';

    $value = isset($_GET['value']) ? $_GET['value'] : ''; // ID del supervisor (ej: 173)
    
    if (empty($value)) {
        $conn->close();
        return json_encode([]);
    }
    
    // DIRECTAMENTE FILTRAR POR EL ID DEL SUPERVISOR
    // Porque en repositorio_locales_dtt2.supervisor se guarda el ID numérico
    $sql = "SELECT DISTINCT 
                ru.id, 
                ru.usuario, 
                CONCAT(ru.nombre, ' ', ru.apellido) as nombre_completo,
                ru.mercaderista
            FROM repositorio_usuarios ru
            INNER JOIN rutero_pdv r ON r.id_usuario = ru.id
            INNER JOIN repositorio_locales_dtt2 rl2 ON r.id_pdv = rl2.id
            /*WHERE rl2.supervisor = '$value'*/  
            WHERE ru.id_supervisor = '$value'  
            AND rl2.activar = 'SI' 
            AND ru.id_rol = 2
            AND ru.status = 1
            AND ru.activo = 1
            AND ru.mercaderista IS NOT NULL
            AND ru.mercaderista != ''
            AND ru.usuario NOT LIKE '%PRUEBA%'
            ORDER BY ru.usuario";
    
    $data = [];
    
    if ($result = $conn->query($sql)) {
        while ($rows = $result->fetch_assoc()) {
            $data[] = $rows;
        }
        $result->close();
    }
    
    $conn->close();
    
    return json_encode($data);
}

// function obtenerRegistros(){
//     require_once '../conection/conexion.php';

//     $supervisorID = $_POST['usuario'];
//     $mercaderista = $_POST['mercaderista'];
//     $fecha = $_POST['fecha'];
//     $newDate = date("d/m/Y", strtotime($fecha));

//     $data = array();

//     // Debug: Verificar qué valores estamos recibiendo
//     error_log("=== DEBUG obtenerRegistros ===");
//     error_log("supervisorID: " . $supervisorID);
//     error_log("mercaderista: " . $mercaderista);
//     error_log("fecha: " . $fecha);
//     error_log("newDate: " . $newDate);

//     // Primero obtenemos el nombre del supervisor basado en su ID
//     $querySupervisor = "SELECT usuario FROM repositorio_usuarios WHERE id = '$supervisorID'";
//     error_log("Query Supervisor: " . $querySupervisor);
    
//     $resultSupervisor = $conn->query($querySupervisor);
    
//     if (!$resultSupervisor) {
//         error_log("Error en query supervisor: " . $conn->error);
//         return json_encode(['error' => 'Error al obtener supervisor: ' . $conn->error]);
//     }
    
//     if ($resultSupervisor->num_rows == 0) {
//         error_log("Supervisor no encontrado con ID: " . $supervisorID);
//         return json_encode(['error' => 'Supervisor no encontrado']);
//     }
    
//     $rowSupervisor = $resultSupervisor->fetch_assoc();
//     $supervisorNombre = $rowSupervisor['usuario'];
//     $resultSupervisor->close();
    
//     error_log("supervisorNombre: " . $supervisorNombre);

//     if($mercaderista == 'TODOS')
//     {
//         $sql = "SELECT vr.id_pdv AS pos_id, 
//                        vr.channel, 
//                        vr.customer_owner, 
//                        vr.nombre_pdv AS pos_name, 
//                        vr.pos_name_dpsm, 
//                        vr.region, 
//                        vr.province AS province, 
//                        vr.city AS city, 
//                        vr.direccion AS address, 
//                        vr.supervisor, 
//                        vr.nombre AS mercaderista, 
//                        vr.latitude_pdv AS latitud, 
//                        vr.longitude_pdv AS longitud, 
//                        vr.foto, 
//                        s.activar, 
//                        vr.tipo, 
//                        vr.causal, 
//                        vr.latitude, 
//                        vr.foto AS FotoMe, 
//                        vr.longitude, 
//                        vr.fecha, 
//                        vr.hora, 
//                        s.distancia  
//                 FROM repositorio_locales_dtt2 s 
//                 INNER JOIN insert_registro vr ON s.pos_id = vr.id_pdv 
//                 WHERE vr.supervisor = '$supervisorNombre'
//                   AND s.activar = 'SI' 
//                   AND vr.fecha = '$newDate' 
//                 GROUP BY vr.latitude, vr.longitude";
        
//         error_log("SQL TODOS: " . $sql);
        
//         $result = $conn->query($sql);
        
//         if (!$result) {
//             error_log("Error en SQL TODOS: " . $conn->error);
//             return json_encode(['error' => 'Error en consulta: ' . $conn->error, 'sql' => $sql]);
//         }
        
//         if ($result->num_rows > 0) {
//             while($rows = $result->fetch_assoc()){
//                 $data[] = $rows;
//             }
//         }
        
//         $result->close();
        
//         error_log("Registros encontrados (TODOS): " . count($data));
        
//         return json_encode($data);
//     }
//     else
//     {
//         $sql = "SELECT vr.id_pdv AS pos_id, 
//                        vr.channel, 
//                        vr.customer_owner, 
//                        vr.nombre_pdv AS pos_name, 
//                        vr.pos_name_dpsm, 
//                        vr.region, 
//                        vr.province AS province, 
//                        vr.city AS city,
//                        vr.direccion AS address, 
//                        vr.supervisor, 
//                        vr.nombre AS mercaderista, 
//                        vr.latitude_pdv AS latitud, 
//                        vr.longitude_pdv AS longitud, 
//                        vr.foto, 
//                        s.activar, 
//                        vr.tipo, 
//                        vr.causal, 
//                        vr.latitude, 
//                        vr.foto as FotoMe, 
//                        vr.longitude, 
//                        vr.fecha, 
//                        vr.hora, 
//                        s.distancia  
//                 FROM repositorio_locales_dtt2 s 
//                 INNER JOIN insert_registro vr ON s.pos_id = vr.id_pdv
//                 WHERE vr.supervisor = '$supervisorNombre'
//                   AND vr.nombre = '$mercaderista'
//                   AND s.activar = 'SI' 
//                   AND vr.fecha = '$newDate'  
//                 GROUP BY vr.latitude, vr.longitude";
        
//         error_log("SQL MERCADERISTA: " . $sql);
        
//         $result = $conn->query($sql);
        
//         if (!$result) {
//             error_log("Error en SQL MERCADERISTA: " . $conn->error);
//             return json_encode(['error' => 'Error en consulta: ' . $conn->error, 'sql' => $sql]);
//         }
        
//         if ($result->num_rows > 0) {
//             while($rows = $result->fetch_assoc()){
//                 $data[] = $rows;
//             }
//         }
        
//         $result->close();
        
//         error_log("Registros encontrados (MERCADERISTA): " . count($data));
        
//         return json_encode($data);
//     }
// }

function obtenerRegistros(){
    require_once '../conection/conexion.php';

    $supervisorID = (int) $_POST['usuario']; // ID del supervisor (repositorio_usuarios.id)
    $mercaderistaId = $_POST['mercaderista']; // 'TODOS' o ID (repositorio_usuarios.id) del mercaderista elegido
    $fecha = $_POST['fecha'];
    $newDate = date("d/m/Y", strtotime($fecha));

    $data = array();

    // Hasta ~13/08/2026 el campo insert_registro.supervisor se grababa mal (bug de la app),
    // por eso se cambió a resolver el supervisor vía repositorio_usuarios.id_supervisor.
    // Desde esa fecha vr.supervisor SÍ es confiable y además refleja relevos/apoyos del día
    // (mercaderistas que ese día reportaron a un supervisor distinto al que tienen asignado
    // hoy en repositorio_usuarios). Por eso combinamos ambos criterios con OR, acotando
    // vr.supervisor a partir de la fecha del fix para no arrastrar el dato histórico roto.
    $fechaCorteSupervisor = '2026-08-13';

    $querySupervisor = "SELECT usuario FROM repositorio_usuarios WHERE id = '$supervisorID' AND id_rol = 4";
    $resultSupervisor = $conn->query($querySupervisor);

    if (!$resultSupervisor || $resultSupervisor->num_rows == 0) {
        return json_encode(['error' => 'Supervisor no encontrado']);
    }

    $supervisorNombre = $resultSupervisor->fetch_assoc()['usuario'];
    $resultSupervisor->close();

    $filtroSupervisor = "(
        ru.id_supervisor = '$supervisorID'
        OR (
            STR_TO_DATE(vr.fecha, '%d/%m/%Y') >= '$fechaCorteSupervisor'
            AND vr.supervisor = '$supervisorNombre'
        )
    )";

    if($mercaderistaId == 'TODOS')
    {
        $sql = "SELECT vr.id_pdv AS pos_id, 
                       vr.channel, 
                       vr.customer_owner, 
                       s.pos_name AS pos_name, 
                       vr.pos_name_dpsm, 
                       vr.region, 
                       vr.province AS province, 
                       vr.city AS city, 
                       vr.direccion AS address, 
                       vr.supervisor, 
                       vr.nombre AS mercaderista, 
                       vr.latitude_pdv AS latitud, 
                       vr.longitude_pdv AS longitud, 
                       s.foto,
                       s.activar, 
                       vr.tipo, 
                       vr.causal, 
                       vr.latitude, 
                       vr.foto AS FotoMe, 
                       vr.longitude, 
                       vr.fecha, 
                       vr.hora, 
                       s.distancia  
                FROM repositorio_locales_dtt2 s
                INNER JOIN insert_registro vr ON s.pos_id = vr.id_pdv
                INNER JOIN repositorio_usuarios ru
                    ON ru.usuario = vr.nombre
                WHERE $filtroSupervisor
                  AND s.activar = 'SI'
                  AND vr.fecha = '$newDate'
                GROUP BY vr.latitude, vr.longitude";
        
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            while($rows = $result->fetch_assoc()){
                $data[] = $rows;
            }
        }
        
        if ($result) {
            $result->close();
        }
        
        return json_encode($data);
    }
    else
    {
        // $mercaderistaId es repositorio_usuarios.id (no el nombre): hay mercaderistas con
        // nombre+apellido duplicado (ej. "JENNIFFER ALBAN" existe en 2 cuentas distintas,
        // id 116 y 196) y el nombre por sí solo no alcanza para distinguirlas.
        //
        // OJO - limitación de datos: insert_registro.nombre solo guarda "Nombre Apellido"
        // (no el usuario ni el id de quien hizo el check-in/out), así que aunque aquí
        // resolvamos el id correcto, si existen 2 cuentas con el mismo nombre+apellido sus
        // registros seguirán sin poder separarse entre sí — eso solo se resuelve si la app
        // graba el usuario o el id en insert_registro.
        $mercaderistaId = (int) $mercaderistaId;

        $queryMercaderista = "SELECT CONCAT(nombre, ' ', apellido) as nombre_completo
                              FROM repositorio_usuarios
                              WHERE id = '$mercaderistaId' AND id_rol = 2";

        $resultMercaderista = $conn->query($queryMercaderista);

        if (!$resultMercaderista || $resultMercaderista->num_rows == 0) {
            return json_encode(['error' => 'Mercaderista no encontrado']);
        }

        $rowMercaderista = $resultMercaderista->fetch_assoc();
        $mercaderistaNombreCompleto = $rowMercaderista['nombre_completo'];
        $resultMercaderista->close();

        $sql = "SELECT vr.id_pdv AS pos_id, 
                       vr.channel, 
                       vr.customer_owner, 
                       s.pos_name AS pos_name, 
                       vr.pos_name_dpsm, 
                       vr.region, 
                       vr.province AS province, 
                       vr.city AS city,
                       vr.direccion AS address, 
                       vr.supervisor, 
                       vr.nombre AS mercaderista, 
                       vr.latitude_pdv AS latitud, 
                       vr.longitude_pdv AS longitud, 
                       s.foto, 
                       s.activar, 
                       vr.tipo, 
                       vr.causal, 
                       vr.latitude, 
                       vr.foto as FotoMe, 
                       vr.longitude, 
                       vr.fecha, 
                       vr.hora, 
                       s.distancia  
                FROM repositorio_locales_dtt2 s 
                INNER JOIN insert_registro vr ON s.pos_id = vr.id_pdv
                /*WHERE vr.supervisor = '$supervisorNombreCompleto'*/
                INNER JOIN repositorio_usuarios ru
                    ON ru.usuario = vr.nombre
                WHERE ru.id_supervisor = '$supervisorID'
                  AND vr.nombre = '$mercaderistaNombreCompleto'
                  AND s.activar = 'SI' 
                  AND vr.fecha = '$newDate'  
                GROUP BY vr.latitude, vr.longitude";
        
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            while($rows = $result->fetch_assoc()){
                $data[] = $rows;
            }
        }
        
        if ($result) {
            $result->close();
        }
        
        return json_encode($data);
    }
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