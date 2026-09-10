<?php
include_once "../includes/db_connect.php";
include_once "../includes/functions.php";
include_once "../includes/config.php";

$columns = array(
    "id",
    "pos_id",
    "sales_executive",
    "channel",
    "subchannel",
    "format",
    "pos_name_dpsm",
    "kam",
    "merchandising",
    "customer_owner",
    "pos_name",
    "dpsm",
    "region",
    "tipo",
    "province",
    "city",
    "zone",
    "address",
    "supervisor",
    "latitud",
    "longitud",
    "channel_segment",
    "visual",
    "coordinador",
    "foto",
    "status",
    "perimetro",
    "distancia",
    "activar"
);

$table = "repositorio_locales_dtt2";

$draw = isset($_POST["draw"]) ? intval($_POST["draw"]) : 0;
$start = isset($_POST["start"]) ? intval($_POST["start"]) : 0;
$length = isset($_POST["length"]) ? intval($_POST["length"]) : 25;

if ($length < 1 || $length > 500) {
    $length = 25;
}

$search = "";
if (isset($_POST["search"]["value"])) {
    $search = trim($_POST["search"]["value"]);
}

$orderColumnIndex = 0;
$orderDir = "ASC";

if (isset($_POST["order"][0]["column"])) {
    $orderColumnIndex = intval($_POST["order"][0]["column"]);
}

if (isset($_POST["order"][0]["dir"]) && strtolower($_POST["order"][0]["dir"]) === "desc") {
    $orderDir = "DESC";
}

$orderColumn = isset($columns[$orderColumnIndex]) ? $columns[$orderColumnIndex] : "id";

function bindParams($stmt, $types, &$params)
{
    if ($types === "") {
        return;
    }

    $bind = array();
    $bind[] = $types;

    foreach ($params as $key => $value) {
        $bind[] = &$params[$key];
    }

    call_user_func_array(array($stmt, "bind_param"), $bind);
}

function getCount($mysqli, $query, $types = "", $params = array())
{
    $total = 0;

    if ($stmt = $mysqli->prepare($query)) {
        bindParams($stmt, $types, $params);
        $stmt->execute();
        $stmt->bind_result($total);
        $stmt->fetch();
        $stmt->close();
    }

    return intval($total);
}

$totalRecords = getCount($mysqli, "SELECT COUNT(*) FROM $table");

$where = "";
$types = "";
$params = array();

if ($search !== "") {
    $likeParts = array();

    foreach ($columns as $column) {
        $likeParts[] = "$column LIKE ?";
        $params[] = "%" . $search . "%";
        $types .= "s";
    }

    $where = " WHERE " . implode(" OR ", $likeParts);
}

$totalFiltered = $totalRecords;

if ($where !== "") {
    $totalFiltered = getCount($mysqli, "SELECT COUNT(*) FROM $table $where", $types, $params);
}

$selectColumns = implode(",", $columns);

$query = "
    SELECT $selectColumns
    FROM $table
    $where
    ORDER BY $orderColumn $orderDir
    LIMIT ?, ?
";

$paramsData = $params;
$typesData = $types . "ii";
$paramsData[] = $start;
$paramsData[] = $length;

$data = array();

if ($stmt = $mysqli->prepare($query)) {
    bindParams($stmt, $typesData, $paramsData);
    $stmt->execute();

    $stmt->bind_result(
        $id,
        $pos_id,
        $sales_executive,
        $channel,
        $subchannel,
        $format,
        $pos_name_dpsm,
        $kam,
        $merchandising,
        $customer_owner,
        $pos_name,
        $dpsm,
        $region,
        $tipo,
        $province,
        $city,
        $zone,
        $address,
        $supervisor,
        $latitud,
        $longitud,
        $channel_segment,
        $visual,
        $coordinador,
        $foto,
        $status,
        $perimetro,
        $distancia,
        $activar
    );

    while ($stmt->fetch()) {
        $data[] = array(
            $id,
            $pos_id,
            $sales_executive,
            $channel,
            $subchannel,
            $format,
            $pos_name_dpsm,
            $kam,
            $merchandising,
            $customer_owner,
            $pos_name,
            $dpsm,
            $region,
            $tipo,
            $province,
            $city,
            $zone,
            $address,
            $supervisor,
            $latitud,
            $longitud,
            $channel_segment,
            $visual,
            $coordinador,
            $foto,
            $status,
            $perimetro,
            $distancia,
            $activar
        );
    }

    $stmt->close();
} 

header("Content-Type: application/json; charset=utf-8");

echo json_encode(array(
    "draw" => $draw,
    "recordsTotal" => $totalRecords,
    "recordsFiltered" => $totalFiltered,
    "data" => $data
));