<?php
// getters/get_table_pdvs_nuevo1.php (SERVER-SIDE PROCESSING)

include_once "../includes/db_connect.php";

header('Content-Type: application/json; charset=UTF-8');

$mysqli->set_charset("utf8mb4");

$draw = $_POST['draw'] ?? 1;
$start = $_POST['start'] ?? 0;
$length = $_POST['length'] ?? 10;
$search_value = $_POST['search']['value'] ?? '';

// El listado muestra EXACTAMENTE las columnas del archivo del cliente:
//  - SIN 'reabrev', 'color' ni 'tiempo_visita' (no se manejan en este módulo).
//  - CON 'activar' como última columna de datos.
// Se sigue filtrando WHERE activar = 'SI' (solo se listan los visibles),
// por lo que esa columna siempre mostrará "SI"; se incluye para que el
// listado calce con el formato del archivo del cliente.
$cols = [
    'id', 'pos_id', 'sales_executive', 'channel', 'subchannel',
    'format', 'pos_name_dpsm', 'kam', 'merchandising', 'customer_owner',
    'pos_name', 'dpsm', 'region', 'tipo', 'province', 'city', 'zone',
    'address', 'supervisor', 'latitud', 'longitud', 'channel_segment',
    'visual', 'coordinador', 'foto', 'status', 'perimetro', 'distancia',
    'activar'
];

$base_query = "FROM repositorio_locales_dtt2 WHERE activar = 'SI'";
$params = [];
$param_types = '';

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
        // El orden de este array DEBE calzar con la config "columns" (data:N)
        // del DataTable en pdvs_nuevo1.php. Índices 0..30.
        $row_data = [
            '<input type="checkbox" class="select-row" value="' . htmlspecialchars($row['id']) . '">', // 0
            $row['id'],              // 1
            $row['pos_id'],          // 2
            $row['sales_executive'], // 3
            $row['channel'],         // 4
            $row['subchannel'],      // 5
            $row['format'],          // 6
            $row['pos_name_dpsm'],   // 7
            $row['kam'],             // 8
            $row['merchandising'],   // 9
            $row['customer_owner'],  // 10
            $row['pos_name'],        // 11
            $row['dpsm'],            // 12
            $row['region'],          // 13
            $row['tipo'],            // 14
            $row['province'],        // 15
            $row['city'],            // 16
            $row['zone'],            // 17
            $row['address'],         // 18
            $row['supervisor'],      // 19
            $row['latitud'],         // 20
            $row['longitud'],        // 21
            $row['channel_segment'], // 22
            $row['visual'],          // 23
            $row['coordinador'],     // 24
            !empty($row['foto']) ? '<a href="' . htmlspecialchars($row['foto']) . '" target="_blank">Ver foto</a>' : '', // 25
            $row['status'],          // 26
            $row['perimetro'],       // 27
            $row['distancia'],       // 28
            $row['activar'],         // 29
            '<button class="btn btn-sm btn-primary btn-editar" data-id="' . htmlspecialchars($row['id'], ENT_QUOTES) . '">
                <i class="material-icons" style="font-size: 16px; vertical-align: middle;">edit</i> Editar
            </button>'               // 30
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