<?php
// getters/get_table_tb_justificaciones.php (SERVER-SIDE PROCESSING)

include_once "../includes/db_connect.php";

// 1. CLAVE: Forzar la cabecera con charset UTF-8
header('Content-Type: application/json; charset=utf-8');

// 2. CLAVE: Forzar a la base de datos a entregar los datos en UTF-8
// Esto evita que "Rumiñahui" se convierta en "RumiÃ±ahui" al salir de la DB
$mysqli->set_charset("utf8mb4");

// Parámetros de DataTables
$draw = $_POST['draw'] ?? 1;
$start = $_POST['start'] ?? 0;
$length = $_POST['length'] ?? 10; 
$search_value = $_POST['search']['value'] ?? '';

// Filtros de fecha desde el JS
$fecha_inicio = $_POST['fecha_inicio'] ?? null;
$fecha_fin = $_POST['fecha_fin'] ?? null;

// Columnas de tb_justificaciones
$cols = [
    'id', 'fecha', 'codigo_pdv', 'id_usuario', 'xplorer', 
    'hora_entrada', 'hora_salida', 'tipo_justificacion', 'observacion', 'status'
];

$base_query = "FROM tb_justificaciones WHERE status = 1";
$params = [];
$param_types = '';

if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $base_query .= " AND fecha BETWEEN ? AND ?";
    $param_types .= 'ss';
    $params[] = $fecha_inicio;
    $params[] = $fecha_fin;
} else if (!empty($fecha_inicio)) {
    $base_query .= " AND fecha >= ?";
    $param_types .= 's';
    $params[] = $fecha_inicio;
} else if (!empty($fecha_fin)) {
    $base_query .= " AND fecha <= ?";
    $param_types .= 's';
    $params[] = $fecha_fin;
}

if (!empty($search_value)) {
    $base_query .= " AND (codigo_pdv LIKE ? OR xplorer LIKE ? OR tipo_justificacion LIKE ? OR observacion LIKE ?)";
    $param_types .= 'ssss';
    $term = "%$search_value%";
    $params[] = $term; 
    $params[] = $term; 
    $params[] = $term; 
    $params[] = $term;
}

// Conteo Total
$sql_count = "SELECT COUNT(id) " . $base_query;
$stmt_c = $mysqli->prepare($sql_count);
if (!empty($param_types)) {
    $stmt_c->bind_param($param_types, ...$params);
}
$stmt_c->execute();
$total_filtered_records = $stmt_c->get_result()->fetch_row()[0];
$stmt_c->close();

// Ordenamiento
$order_col_index = $_POST['order'][0]['column'] ?? 1; 
$order_direction = $_POST['order'][0]['dir'] ?? 'asc';
// Ajuste: restamos 1 porque la columna 0 es el checkbox
$order_column = $cols[$order_col_index - 1] ?? 'id'; 
$order_sql = " ORDER BY $order_column $order_direction";

// Consulta de Datos
$sql_data = "SELECT " . implode(', ', $cols) . " " . $base_query . $order_sql . " LIMIT ? OFFSET ?";
$param_types_data = $param_types . "ii";
$params_data = array_merge($params, [(int)$length, (int)$start]);

$data_array = [];
$stmt = $mysqli->prepare($sql_data);
if (!empty($param_types_data)) {
    $stmt->bind_param($param_types_data, ...$params_data);
}
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    // Formatear horas para mostrar solo HH:MM (sin segundos)
    $hora_entrada = !empty($row['hora_entrada']) ? substr($row['hora_entrada'], 0, 5) : '';
    $hora_salida = !empty($row['hora_salida']) ? substr($row['hora_salida'], 0, 5) : '';
    
    // Traducir status
    $status_text = $row['status'] == 1 ? 'Activo' : 'Inactivo';
    $status_class = $row['status'] == 1 ? 'badge bg-success' : 'badge bg-danger';
    
    $data_array[] = [
        '<input type="checkbox" class="select-row" value="' . $row['id'] . '">',
        $row['id'],
        $row['fecha'],
        $row['codigo_pdv'],
        $row['id_usuario'],
        $row['xplorer'],
        $hora_entrada,
        $hora_salida,
        $row['tipo_justificacion'],
        $row['observacion'],
        '<span class="' . $status_class . '">' . $status_text . '</span>',
        '<button class="btn btn-sm btn-primary btn-editar-pdv" data-id="' . $row['id'] . '"><i class="material-icons" style="font-size: 16px;">edit</i> Editar</button>'
    ];
}

// 3. CLAVE: Usar JSON_UNESCAPED_UNICODE para que no transforme la ñ en códigos hexadecimales
echo json_encode([
    "draw" => intval($draw),
    "recordsTotal" => $total_filtered_records, 
    "recordsFiltered" => $total_filtered_records,
    "data" => $data_array
], JSON_UNESCAPED_UNICODE);

$mysqli->close();
?>