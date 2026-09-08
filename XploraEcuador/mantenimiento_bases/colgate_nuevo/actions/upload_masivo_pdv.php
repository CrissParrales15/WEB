<?php
include_once "../includes/db_connect.php";
include_once "../includes/config.php";

header("Content-Type: application/json");

if (!isset($_FILES["file"])) {
    echo json_encode(["status" => "error", "message" => "No se subió ningún archivo."]);
    exit;
}

$archivoTMP = $_FILES["file"]["tmp_name"];
$nombreOriginal = $_FILES["file"]["name"];
$extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

if ($extension !== "csv") {
    echo json_encode([
        "status" => "error",
        "message" => "El archivo debe ser CSV para una carga masiva rápida."
    ]);
    exit;
}

$destino = __DIR__ . "/temp_import.csv";
move_uploaded_file($archivoTMP, $destino);

mysqli_set_local_infile_default($conn);

$query = "
    LOAD DATA LOCAL INFILE '$destino'
    INTO TABLE tb_pdv
    FIELDS TERMINATED BY ';'
    ENCLOSED BY '\"'
    LINES TERMINATED BY '\\n'
    IGNORE 1 ROWS
    (
        day, cp_code, customer_code, trade, retail_enviroment, re, 
        cp_format, customer_format_banner, target, distribuidor, 
        customer, pos, ruta, region, territory, province, 
        city, zone, address, supervisor, merchandiser, user, 
        x, y, sob, visual_access
    )
";

if (mysqli_query($conn, $query)) {
    echo json_encode([
        "status" => "success",
        "message" => "Archivo cargado correctamente (carga masiva ultra rápida)."
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Error en MySQL: " . mysqli_error($conn)
    ]);
}

unlink($destino);
