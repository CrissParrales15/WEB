<?php
// actions/get_table_pdv.php (SERVER-SIDE PROCESSING)

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

$cols = [
    'id', 'day', 'cp_code', 'customer_code', 'trade', 'retail_enviroment', 're', 
    'cp_format', 'customer_format_banner', 'target', 'distribuidor', 'customer', 
    'pos', 'ruta', 'region', 'territory', 'province', 'city', 'zone', 'address', 
    'supervisor', 'merchandiser', 'user', 'x', 'y', 'sob', 'visual_access', 
    'fecha_modificacion'
];

$base_query = "FROM tb_pdv WHERE status = 1";
$params = [];
$param_types = '';

if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $base_query .= " AND day BETWEEN ? AND ?";
    $param_types .= 'ss';
    $params[] = $fecha_inicio;
    $params[] = $fecha_fin;
} else if (!empty($fecha_inicio)) {
    $base_query .= " AND day >= ?";
    $param_types .= 's';
    $params[] = $fecha_inicio;
} else if (!empty($fecha_fin)) {
    $base_query .= " AND day <= ?";
    $param_types .= 's';
    $params[] = $fecha_fin;
}

if (!empty($search_value)) {
    $base_query .= " AND (pos LIKE ? OR customer LIKE ? OR cp_code LIKE ? OR city LIKE ?)";
    $param_types .= 'ssss';
    $term = "%$search_value%";
    $params[] = $term; $params[] = $term; $params[] = $term; $params[] = $term;
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
    $data_array[] = [
        '<input type="checkbox" class="select-row" value="' . $row['id'] . '">',
        $row['id'],
        $row['day'],
        $row['cp_code'],
        $row['customer_code'],
        $row['trade'],
        $row['retail_enviroment'],
        $row['re'],
        $row['cp_format'],
        $row['customer_format_banner'],
        $row['target'],
        $row['distribuidor'],
        $row['customer'],
        $row['pos'],
        $row['ruta'],
        $row['region'],
        $row['territory'],
        $row['province'],
        $row['city'],
        $row['zone'],
        $row['address'],
        $row['supervisor'],
        $row['merchandiser'],
        $row['user'],
        $row['x'],
        $row['y'],
        $row['sob'],
        $row['visual_access'],
        $row['fecha_modificacion'],
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