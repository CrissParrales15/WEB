<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include_once($_SERVER['DOCUMENT_ROOT']."/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/includes/db_connect.php");
include_once($_SERVER['DOCUMENT_ROOT']."/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/includes/functions.php");
include_once($_SERVER['DOCUMENT_ROOT']."/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/includes/config.php");

$parametros = $_POST['parametros'];

$fechaInicio = $parametros[0];
$fechaFin = $parametros[1];
// $cuenta = $parametros[2];  // <-- COMENTADO: NO SE USA CUENTA
$canal = isset($parametros[2]) ? $parametros[2] : '';
$cadena = isset($parametros[3]) ? $parametros[3] : '';
$provincia = isset($parametros[4]) ? $parametros[4] : '';

// ========== CONSTRUIR FILTROS ==========
$concat_canal_sql = "";
if (!empty($canal) && $canal != "TODOS" && $canal != "Seleccione" && $canal != "") {
    $concat_canal_sql = " AND rl.channel = '$canal' ";
}

$concat_cadena_sql = "";
if (!empty($cadena) && $cadena != "TODOS" && $cadena != "Seleccione" && $cadena != "") {
    $concat_cadena_sql = " AND rl.subchannel = '$cadena' ";
}

$concat_provincia_sql = "";
if (!empty($provincia) && $provincia != "TODOS" && $provincia != "Seleccione" && $provincia != "") {
    $concat_provincia_sql = " AND rl.province = '$provincia' ";
}

// ========== QUERY SUPERVISORES ==========
$query_supervisor = "SELECT rs.id AS id_supervisor, 
                        rs.usuario AS nombre_supervisor 
                        FROM rutero_pdv r 
                        INNER JOIN repositorio_locales_dtt2 rl ON r.id_pdv = rl.id 
                        INNER JOIN repositorio_usuarios rs ON r.id_supervisor = rs.id 
                        WHERE (r.fecha_visita BETWEEN ? AND ?) 
                        AND r.status = 1 
                        AND r.habilitado = 1 
                        AND rs.id_rol = 4 
                        AND rs.activo = 1
                        $concat_canal_sql
                        $concat_cadena_sql
                        $concat_provincia_sql
                        GROUP BY rs.id
                        ORDER BY nombre_supervisor";  

$supervisores = array();       
$contador_orden = 1;

if ($sql_supervisor = $mysqli->prepare($query_supervisor)) {
    $sql_supervisor->bind_param('ss', $fechaInicio, $fechaFin);
    $sql_supervisor->execute();
    $sql_supervisor->store_result();
    
    if ($sql_supervisor->num_rows > 0) {
        $sql_supervisor->bind_result(
            $id_supervisor,
            $nombre_supervisor
        ) or die($sql_supervisor->error);

        while ($sql_supervisor->fetch()) {
            $contador_supervisor = 0;
            $efectuados_supervisor = 0;

            $supervisor = new Supervisor();

			$gestores = array();
            $supervisor->id = $id_supervisor;
            $supervisor->nombre = $nombre_supervisor;

            // ========== QUERY GESTORES ==========
            $query = "SELECT 
            ru.id AS id_gestor, 
            ru.mercaderista AS nombre_gestor, 
            ru.usuario AS usuario,
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
                AND ru.id_supervisor = ?
                $concat_canal_sql
                $concat_cadena_sql
                $concat_provincia_sql
            ORDER BY nombre_gestor";

            if ($sql = $mysqli->prepare($query)) {
                $sql->bind_param('ssi', $fechaInicio, $fechaFin, $id_supervisor);
                $sql->execute();
                $sql->store_result();
                
                if ($sql->num_rows > 0) {
                    $sql->bind_result(
                        $id_gestor,
                        $nombre_gestor,
                        $usuario,
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
                        $foto_no_visita
                    ) or die($sql->error);

                    $pdvs = array();
                    $gestores = array();
                    $id_gestor_ingresado = 0;
                    $contador = 1;
                    $efectuados = 0;

                    while ($sql->fetch()) {
                        $minutes = 0;
                        
                        if ($id_gestor != $id_gestor_ingresado) {
                            $pdvs = array();
                            $contador = 1;
                            $efectuados = 0;
                            $id_gestor_ingresado = $id_gestor;
                        }

                        // ========== CALCULAR DURACION ==========
                        if (!empty($h_ini) && !empty($h_fin)) {
                            $dateTimeObject1 = date_create($h_ini);
                            $dateTimeObject2 = date_create($h_fin);
                            $difference = date_diff($dateTimeObject1, $dateTimeObject2);
                            $minutes = ($difference->days * 24 * 60) + ($difference->h * 60) + $difference->i;
                        }

                        // ========== FORMATEAR FECHA ==========
                        $fecha_formateada = $fecha;
                        if (!empty($fecha)) {
                            $dateObj = DateTime::createFromFormat('Y-m-d', $fecha);
                            if ($dateObj) {
                                $fecha_formateada = $dateObj->format('d/m/Y');
                            }
                        }

                        // ========== CREAR PDV ==========
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

                        if ($estado == 'ATENDIDO') {
                            $efectuados++;
                            $efectuados_supervisor++;
                        }

                        // ========== CREAR/ACTUALIZAR GESTOR ==========
                        if (!$gestores) {
                            $gestor = new Gestor();
                            $gestor->id = $id_gestor;
                            $gestor->nombre = $nombre_gestor;
                            $gestor->usuario = $usuario;
                            $gestor->cant_visitas = $contador;
                            $gestor->efectividad = round(($efectuados / $contador) * 100, 2);
                            $gestor->pdv = $pdvs;
                            array_push($gestores, $gestor);
                        } else {
                            $gestor = end($gestores);
                            if ($id_gestor != $gestor->id) {
                                $gestor = new Gestor();
                                $gestor->id = $id_gestor;
                                $gestor->nombre = $nombre_gestor;
                                $gestor->usuario = $usuario;
                                $gestor->cant_visitas = $contador;
                                $gestor->efectividad = round(($efectuados / $contador) * 100, 2);
                                $gestor->pdv = $pdvs;
                                array_push($gestores, $gestor);
                            } else {
                                $gestor->cant_visitas = $contador;
                                $gestor->efectividad = round(($efectuados / $contador) * 100, 2);
                                $gestor->pdv = $pdvs;
                            }
                        }
                        $contador++;
                        $contador_orden++;
                        $contador_supervisor++;
                    }
                }
                $sql->close();
            }

            // ========== ASIGNAR GESTORES AL SUPERVISOR ==========
            if ($efectuados_supervisor != 0 && $contador_supervisor != 0) {
				$supervisor->efectividad = round(($efectuados_supervisor / $contador_supervisor) * 100, 2);
			} else {
				$supervisor->efectividad = 0;
			}
			$supervisor->gestor = isset($gestores) ? $gestores : array();

            array_push($supervisores, $supervisor);
        }
    }
    $sql_supervisor->close();
}

// ========== CLASES ==========
class Supervisor {
    public $id;
    public $nombre = "";
    public $efectividad = 0;
    public $gestor = array();
}

class Gestor {
    public $id;
    public $nombre = "";
    public $usuario = "";
    public $cant_visitas = 0;
    public $efectividad = 0;
    public $pdv = array();
}

class Pdv {
    public $id;
    public $ordenTotal;
    public $codigo;
    public $subchannel;
    public $nombre = "";
    public $visual = "";
    public $fecha = "";
    public $h_ini = "";
    public $h_fin = "";
    public $duracion = 0;
    public $distancia = 0;
    public $estado = "";
    public $tipo_relevo = "";
    public $direccion = "";
    public $lon = "";
    public $lat = "";
    public $foto_no_visita = "";
}

// ========== ENVIAR RESPUESTA ==========
$batch = json_encode($supervisores);
echo $batch;
?>