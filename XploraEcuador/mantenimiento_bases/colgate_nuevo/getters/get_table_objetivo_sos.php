<?php
// getters/get_table_tb_objetivo_sos.php (SERVER-SIDE PROCESSING)

include_once "../includes/db_connect.php";

header('Content-Type: application/json; charset=UTF-8');

// ⭐ ASEGURAR UTF-8
$mysqli->set_charset("utf8mb4");

// Parámetros de DataTables
$draw = $_POST['draw'] ?? 1;
$start = $_POST['start'] ?? 0;
$length = $_POST['length'] ?? 10;
$search_value = $_POST['search']['value'] ?? '';

// Definir las columnas
$cols = [
    'id', 'fecha', 'canal', 'retail_enviroment', 'cliente', 
    'visual_access', 'subcategory', 'new_objetivo', 'fecha_modificacion'
];

$base_query = "FROM tb_objetivo_sos WHERE status = 1";
$params = [];
$param_types = '';

// FILTROS DE FECHA
$fecha_inicio = $_POST['fecha_inicio'] ?? null;
$fecha_fin = $_POST['fecha_fin'] ?? null;

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

// FILTRO DE BÚSQUEDA GLOBAL
if (!empty($search_value)) {
    $base_query .= " AND (";
    $search_conditions = [];
    foreach ($cols as $col) {
        $search_conditions[] = "$col LIKE ?";
        $param_types .= 's';
        $params[] = "%$search_value%";
    }
    $base_query .= implode(' OR ', $search_conditions) . ")";
}

// Conteo Total
$sql_count = "SELECT COUNT(id) " . $base_query;

if ($stmt = $mysqli->prepare($sql_count)) {
    if (!empty($param_types)) {
        $stmt->bind_param($param_types, ...$params);
    }
    $stmt->execute();
    $result_count = $stmt->get_result();
    $row_count = $result_count->fetch_row();
    $total_filtered_records = $row_count[0];
    $stmt->close();
} else {
    echo json_encode(['draw' => intval($draw), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []]);
    exit;
}

// Ordenamiento
$order_col_index = $_POST['order'][0]['column'] ?? 1;
$order_direction = $_POST['order'][0]['dir'] ?? 'asc';
$order_column = $cols[$order_col_index - 1] ?? 'id';
$order_sql = " ORDER BY $order_column $order_direction";

// Consulta de Datos con Paginación
$sql_data = "SELECT " . implode(', ', $cols) . " " . $base_query . $order_sql;

// ⭐ Verificar si es exportación (length = -1)
$is_export = ($length == -1 || $length == 0);

$param_types_data = $param_types;
$params_data = $params;

if (!$is_export) {
    // Aplicar LIMIT y OFFSET solo para paginación normal
    $sql_data .= " LIMIT ? OFFSET ?";
    $param_types_data .= 'ii';
    $params_data[] = (int)$length;
    $params_data[] = (int)$start;
}

$data_array = [];

if ($stmt = $mysqli->prepare($sql_data)) {
    if (!empty($param_types_data)) {
        $stmt->bind_param($param_types_data, ...$params_data);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $row_data = [
            '<input type="checkbox" class="select-row" value="' . htmlspecialchars($row['id']) . '">',
            $row['id'],
            $row['fecha'],
            $row['canal'],
            $row['retail_enviroment'],
            $row['cliente'],
            $row['visual_access'],
            $row['subcategory'],
            number_format($row['new_objetivo'], 2, ',', '.'),
            $row['fecha_modificacion'],
            '<button class="btn btn-sm btn-primary btn-editar" data-id="' . htmlspecialchars($row['id'], ENT_QUOTES) . '">
                <i class="material-icons" style="font-size: 16px; vertical-align: middle;">edit</i> Editar
            </button>'
        ];
        $data_array[] = $row_data;
    }
    $stmt->close();
}

// Respuesta JSON
$output = [
    "draw" => intval($draw),
    "recordsTotal" => $total_filtered_records,
    "recordsFiltered" => $total_filtered_records,
    "data" => $data_array
];

echo json_encode($output);

$mysqli->close();
?>