<?php
include_once "../includes/db_connect.php";
include_once "../includes/functions.php";
include_once "../includes/config.php";

header('Content-Type: application/json');

// Habilitar logs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$usuario = isset($_POST['usuario']) ? $_POST['usuario'] : '';
$gestor_id = isset($_POST['gestor_id']) ? $_POST['gestor_id'] : '';
$fecha_inicio = isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : '';
$fecha_fin = isset($_POST['fecha_fin']) ? $_POST['fecha_fin'] : '';

// Log para depuración
error_log("=== getPuntosVentaPorGestor.php ===");
error_log("usuario: " . $usuario);
error_log("gestor_id: " . $gestor_id);
error_log("fecha_inicio: " . $fecha_inicio);
error_log("fecha_fin: " . $fecha_fin);

if (empty($usuario) || empty($gestor_id) || empty($fecha_inicio) || empty($fecha_fin)) {
    error_log("ERROR: Parámetros incompletos");
    echo json_encode([]);
    exit;
}

// Buscar el ID del supervisor por su nombre de usuario (rol 1 = supervisor)
$queryUsuario = "SELECT id FROM repositorio_usuarios WHERE usuario = ? AND id_rol IN (/*1,*/4) AND status = 1";
$supervisor_id = null;

if ($stmtUser = $mysqli->prepare($queryUsuario)) {
    $stmtUser->bind_param("s", $usuario);
    $stmtUser->execute();
    $resultUser = $stmtUser->get_result();
    if ($rowUser = $resultUser->fetch_assoc()) {
        $supervisor_id = $rowUser['id'];
        error_log("Supervisor ID encontrado: " . $supervisor_id);
    } else {
        error_log("No se encontró supervisor con usuario: " . $usuario);
        echo json_encode([]);
        $stmtUser->close();
        exit;
    }
    $stmtUser->close();
}

// MODIFICADO: Agregar subchannel a la consulta
$query = "
    SELECT 
        r.id AS id_rutero,
        rl.pos_id AS codigo,
        rl.pos_name AS nombre,
        rl.subchannel AS subchannel, -- NUEVO: Campo RE
        r.fecha_visita AS fecha,
        r.hora_inicio_visita AS h_ini,
        r.hora_fin_visita AS h_fin,
        r.distancia,
        re.descripcion AS estado,
        r.tipo_relevo,
        rl.address AS direccion,
        rl.latitud AS lat,
        rl.longitud AS lon,
        ru.mercaderista AS gestor_nombre,
        ru.usuario AS gestor_usuario
    FROM rutero_pdv r
    INNER JOIN repositorio_locales_dtt2 rl ON r.id_pdv = rl.id
    INNER JOIN repositorio_usuarios ru ON r.id_usuario = ru.id
    INNER JOIN repositorio_estados re ON r.id_estado = re.id
    /*WHERE r.id_supervisor = ?*/
    WHERE /*(
			CASE 
				WHEN r.id_supervisor != rl.supervisor THEN rl.supervisor
				ELSE r.id_supervisor
			END
		) = ?*/
        ru.id_supervisor=?
    AND r.id_usuario = ?
    AND r.fecha_visita BETWEEN ? AND ?
    AND r.status = 1
    AND r.habilitado = 1
    ORDER BY r.fecha_visita, rl.pos_name
";

error_log("Query: " . $query);

$puntos = [];

if ($stmt = $mysqli->prepare($query)) {
    $stmt->bind_param("iiss", $supervisor_id, $gestor_id, $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $result = $stmt->get_result();
    
    error_log("Número de registros encontrados: " . $result->num_rows);
    
    while ($row = $result->fetch_assoc()) {
        error_log("PDV encontrado: " . $row['nombre'] . " - Fecha: " . $row['fecha'] . " - RE: " . ($row['subchannel'] ?? 'N/A'));
        
        // MODIFICADO: Agregar subchannel a la respuesta
        $puntos[] = [
            'id' => $row['id_rutero'],
            'codigo' => $row['codigo'],
            'nombre' => $row['nombre'],
            'subchannel' => $row['subchannel'] ?? '', // NUEVO: RE del PDV
            'fecha' => $row['fecha'],
            'h_ini' => $row['h_ini'],
            'h_fin' => $row['h_fin'],
            'distancia' => $row['distancia'],
            'estado' => $row['estado'],
            'tipo_relevo' => $row['tipo_relevo'],
            'direccion' => $row['direccion'],
            'lat' => $row['lat'],
            'lon' => $row['lon'],
            'usuario' => $usuario,
            'gestor_nombre' => $row['gestor_nombre'],
            'gestor_usuario' => $row['gestor_usuario']
        ];
    }
    $stmt->close();
} else {
    error_log("ERROR en prepare: " . $mysqli->error);
}

error_log("Total PDVs: " . count($puntos));
error_log("=== FIN getPuntosVentaPorGestor.php ===");

echo json_encode($puntos);
?>