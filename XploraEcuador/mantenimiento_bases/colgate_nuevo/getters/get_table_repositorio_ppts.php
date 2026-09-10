<?php
// getters/get_ppts_table.php (SERVER-SIDE PROCESSING)

include_once "../includes/db_connect.php";

header('Content-Type: application/json; charset=UTF-8');

$mysqli->set_charset("utf8mb4");

$draw = $_POST['draw'] ?? 1;
$start = $_POST['start'] ?? 0;
$length = $_POST['length'] ?? 10;
$search_value = $_POST['search']['value'] ?? '';

$cols = [
    'id', 'day', 'time', 'cp_code', 'trade', 'retail_environment',
    'cp_format', 'customer_format_banner', 'distribuidor', 'customer',
    'pos', 'province', 'city', 'zone', 'ejecutivo', 'category',
    'subcategory', 'segment', 'form', 'manufacturer', 'brand', 'product',
    'size', 'validation', 'activity', 'type_of_promotion', 'descuento',
    'price_talker', 'mechanics', 'sale_price', 'observation', 'photo_url',
    'period', 'inicio_de_promocion', 'fin_de_promocion', 'agotar_stock',
    'fuente', 'tipo', 'server_date'
];

$base_query = "FROM repositorio_ppts WHERE status = 1";
$params = [];
$param_types = '';

$fecha_inicio = $_POST['fecha_inicio'] ?? null;
$fecha_fin = $_POST['fecha_fin'] ?? null;

if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $base_query .= " AND DATE(day) BETWEEN ? AND ?";
    $param_types .= 'ss';
    $params[] = $fecha_inicio;
    $params[] = $fecha_fin;
} else if (!empty($fecha_inicio)) {
    $base_query .= " AND DATE(day) >= ?";
    $param_types .= 's';
    $params[] = $fecha_inicio;
} else if (!empty($fecha_fin)) {
    $base_query .= " AND DATE(day) <= ?";
    $param_types .= 's';
    $params[] = $fecha_fin;
}

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

$order_col_index = $_POST['order'][0]['column'] ?? 1;
$order_direction = $_POST['order'][0]['dir'] ?? 'asc';
$order_column = $cols[$order_col_index - 1] ?? 'id';
$order_sql = " ORDER BY $order_column $order_direction";

$sql_data = "SELECT " . implode(', ', $cols) . " " . $base_query . $order_sql;

$is_export = ($length == -1 || $length == 0);

$param_types_data = $param_types;
$params_data = $params;

if (!$is_export) {
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
            $row['day'],
            $row['time'],
            $row['cp_code'],
            $row['trade'],
            $row['retail_environment'],
            $row['cp_format'],
            $row['customer_format_banner'],
            $row['distribuidor'],
            $row['customer'],
            $row['pos'],
            $row['province'],
            $row['city'],
            $row['zone'],
            $row['ejecutivo'],
            $row['category'],
            $row['subcategory'],
            $row['segment'],
            $row['form'],
            $row['manufacturer'],
            $row['brand'],
            $row['product'],
            $row['size'],
            $row['validation'],
            $row['activity'],
            $row['type_of_promotion'],
            $row['descuento'],
            $row['price_talker'],
            $row['mechanics'],
            $row['sale_price'],
            $row['observation'],
            !empty($row['photo_url']) ? '<a href="' . htmlspecialchars($row['photo_url']) . '" target="_blank">Ver foto</a>' : '',
            $row['period'],
            $row['inicio_de_promocion'],
            $row['fin_de_promocion'],
            $row['agotar_stock'],
            $row['fuente'],
            $row['tipo'], 
            $row['server_date'],
            '<button class="btn btn-sm btn-primary btn-editar" data-id="' . htmlspecialchars($row['id'], ENT_QUOTES) . '">
                <i class="material-icons" style="font-size: 16px; vertical-align: middle;">edit</i> Editar
            </button>'
        ];
        $data_array[] = $row_data;
    }
    $stmt->close();
}

$output = [
    "draw" => intval($draw),
    "recordsTotal" => $total_filtered_records,
    "recordsFiltered" => $total_filtered_records,
    "data" => $data_array
];

echo json_encode($output);

$mysqli->close();
?>