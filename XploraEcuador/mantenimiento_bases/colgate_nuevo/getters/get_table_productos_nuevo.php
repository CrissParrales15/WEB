<?php
// getters/get_table_productos_nuevo.php
include_once "../includes/db_connect.php";
include_once "../includes/functions.php";
include_once "../includes/config.php";

header('Content-Type: application/json; charset=UTF-8');

$mysqli->set_charset("utf8mb4");

$draw = $_POST['draw'] ?? 1;
$start = $_POST['start'] ?? 0;
$length = $_POST['length'] ?? 10;
$search_value = $_POST['search']['value'] ?? '';

$cols = [
    'id', 'pais', 'fabricante', 'categoria', 'subcategoria', 
    'abreviatura_subcategoria', 'marca', 'codigo_ean_pais', 
    'familia_segmento', 'descripcion_producto', 'propio_competencia', 
    'descripcion_producto_homologado', 'gramaje_tamanio', 'fecha_modificacion'
];

$base_query = "FROM tb_productos WHERE status = 1";
$params = [];
$param_types = '';

if (!empty($_POST['subcategoria_filtro'])) {
    if (is_array($_POST['subcategoria_filtro'])) {
        $placeholders = implode(',', array_fill(0, count($_POST['subcategoria_filtro']), '?'));
        $base_query .= " AND subcategoria IN ($placeholders)";
        foreach ($_POST['subcategoria_filtro'] as $sub) {
            $param_types .= 's';
            $params[] = $sub;
        }
    } else {
        $base_query .= " AND subcategoria = ?";
        $param_types .= 's';
        $params[] = $_POST['subcategoria_filtro'];
    }
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
}

// 4. Ordenamiento
$order_col_index = $_POST['order'][0]['column'] ?? 1; // Columna ID por defecto
$order_direction = $_POST['order'][0]['dir'] ?? 'desc';
$order_column = $cols[$order_col_index - 1] ?? 'id'; // -1 porque la col 0 es el checkbox
$order_sql = " ORDER BY $order_column $order_direction";

// 5. Consulta de Datos Final con Paginación
$sql_data = "SELECT " . implode(', ', $cols) . " " . $base_query . $order_sql . " LIMIT ? OFFSET ?";
$param_types_data = $param_types . 'ii';
$params_data = array_merge($params, [(int)$length, (int)$start]);

$data_array = [];

if ($stmt = $mysqli->prepare($sql_data)) {
    $stmt->bind_param($param_types_data, ...$params_data);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Armamos la fila tal como la espera el JS
        $row_data = [
            '<input type="checkbox" class="select-row" value="' . $row['id'] . '">',
            $row['id'],
            $row['pais'],
            $row['fabricante'],
            $row['categoria'],
            $row['subcategoria'],
            $row['abreviatura_subcategoria'],
            $row['marca'],
            $row['codigo_ean_pais'],
            $row['familia_segmento'],
            $row['descripcion_producto'],
            $row['propio_competencia'],
            $row['descripcion_producto_homologado'],
            $row['gramaje_tamanio'],
            $row['fecha_modificacion'],
            '<button class="btn btn-sm btn-primary btn-editar" 
                data-id="'.htmlspecialchars($row['id'], ENT_QUOTES).'" 
                data-pais="'.htmlspecialchars($row['pais'], ENT_QUOTES).'" 
                data-fabricante="'.htmlspecialchars($row['fabricante'], ENT_QUOTES).'" 
                data-categoria="'.htmlspecialchars($row['categoria'], ENT_QUOTES).'" 
                data-subcategoria="'.htmlspecialchars($row['subcategoria'], ENT_QUOTES).'" 
                data-abreviatura="'.htmlspecialchars($row['abreviatura_subcategoria'], ENT_QUOTES).'" 
                data-marca="'.htmlspecialchars($row['marca'], ENT_QUOTES).'" 
                data-ean="'.htmlspecialchars($row['codigo_ean_pais'], ENT_QUOTES).'" 
                data-familia="'.htmlspecialchars($row['familia_segmento'], ENT_QUOTES).'" 
                data-descripcion="'.htmlspecialchars($row['descripcion_producto'], ENT_QUOTES).'" 
                data-propio="'.htmlspecialchars($row['propio_competencia'], ENT_QUOTES).'" 
                data-homologado="'.htmlspecialchars($row['descripcion_producto_homologado'], ENT_QUOTES).'" 
                data-gramaje="'.htmlspecialchars($row['gramaje_tamanio'], ENT_QUOTES).'">
                <i class="material-icons" style="font-size:16px; vertical-align: middle;">edit</i> Editar
            </button>'
        ];
        $data_array[] = $row_data;
    }
    $stmt->close();
}

// 6. Respuesta JSON final
echo json_encode([
    "draw" => intval($draw),
    "recordsTotal" => $total_filtered_records, // En un status=1 real podrías hacer otro count sin filtros
    "recordsFiltered" => $total_filtered_records,
    "data" => $data_array
]);

$mysqli->close();
?>