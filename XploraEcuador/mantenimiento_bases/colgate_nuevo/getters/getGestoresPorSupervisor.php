<?php
include_once "../includes/db_connect.php";
include_once "../includes/functions.php";
include_once "../includes/config.php";

header('Content-Type: application/json');

// Habilitar logs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 🔥 RECIBIR TODOS LOS PARÁMETROS
$usuario = isset($_POST['usuario']) ? $_POST['usuario'] : '';
$fecha_inicio = isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : '';
$fecha_fin = isset($_POST['fecha_fin']) ? $_POST['fecha_fin'] : '';

// Log para depuración
error_log("=== getGestoresPorSupervisor.php ===");
error_log("usuario recibido: " . $usuario);
error_log("fecha_inicio: " . $fecha_inicio);
error_log("fecha_fin: " . $fecha_fin);

if (empty($usuario)) {
    error_log("ERROR: usuario vacío");
    echo json_encode([]);
    exit;
}

// Buscar el ID del supervisor por su nombre de usuario (rol 4 = supervisor)
$queryUsuario = "SELECT id FROM repositorio_usuarios WHERE usuario = ? AND id_rol = 4 AND status = 1";
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
    }
    $stmtUser->close();
}

if (!$supervisor_id) {
    echo json_encode([]);
    exit;
}

// Query para obtener mercaderistas con rutas en el rango de fechas
$query = "
    SELECT DISTINCT 
        ru.id, 
        ru.mercaderista AS nombre, 
        ru.usuario
    FROM repositorio_usuarios ru
    INNER JOIN rutero_pdv r ON r.id_usuario = ru.id
    INNER JOIN repositorio_locales_dtt2 rl ON r.id_pdv = rl.id
    /*WHERE r.id_supervisor = ?*/
    WHERE /*(
			CASE 
				WHEN r.id_supervisor != rl.supervisor THEN rl.supervisor
				ELSE r.id_supervisor
			END
		) = ?*/
        ru.id_supervisor=?
    AND r.status = 1
    AND r.habilitado = 1
    AND ru.mercaderista IS NOT NULL
    AND ru.mercaderista != ''
    AND ru.id_rol = 2
";

// Agregar filtro de fechas si están presentes
if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $query .= " AND r.fecha_visita BETWEEN ? AND ?";
}

$query .= " ORDER BY ru.mercaderista";

error_log("Query: " . $query);

$gestores = [];

if ($stmt = $mysqli->prepare($query)) {
    if (!empty($fecha_inicio) && !empty($fecha_fin)) {
        $stmt->bind_param("iss", $supervisor_id, $fecha_inicio, $fecha_fin);
        error_log("Bind params: supervisor_id=$supervisor_id, fecha_inicio=$fecha_inicio, fecha_fin=$fecha_fin");
    } else {
        $stmt->bind_param("i", $supervisor_id);
        error_log("Bind params: supervisor_id=$supervisor_id (sin fechas)");
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    error_log("Número de gestores encontrados: " . $result->num_rows);
    
    while ($row = $result->fetch_assoc()) {
        $gestores[] = [
            'id' => $row['id'],
            'nombre' => $row['nombre'],
            'usuario' => $row['usuario']
        ];
        error_log("Gestor encontrado: " . $row['nombre'] . " (ID: " . $row['id'] . ")");
    }
    
    $stmt->close();
} else {
    error_log("ERROR en prepare: " . $mysqli->error);
}

error_log("Total gestores a enviar: " . count($gestores));
error_log("=== FIN getGestoresPorSupervisor.php ===");

echo json_encode($gestores);
?>