<?php
require_once __DIR__ . '/conexion/DataSource.php';

use Phppot\DataSource;

$database = new DataSource();
$conn = $database->getConnection();

header("Content-Type: application/json");

try {
    $esAdicional = isset($_GET['es_adicional']) && $_GET['es_adicional'] === 'true';

    $whereTipo = $esAdicional
        ? "AND rpo.historico = 'Adicional'"
        : "AND (rpo.historico IS NULL OR rpo.historico <> 'Adicional')";

    $currentMonth = date("Y-m");

    $query = "
        SELECT 
            ip.usuario AS gestor,
            ip.pos_name AS pdv,
            ip.sku_codesec AS tactico,
            ip.estado,
            ip.cantidad AS cantidad_armada,
            ip.motivo_reporte AS motivo,
            STR_TO_DATE(ip.fecha, '%d/%m/%Y') AS fecha_formateada
        FROM insert_packs ip
        LEFT JOIN repositorio_productos_onpacks rpo 
            ON ip.sku_codesec = rpo.sku AND rpo.activar = 'SI'
        WHERE DATE_FORMAT(STR_TO_DATE(ip.fecha, '%d/%m/%Y'), '%Y-%m') = '$currentMonth'
        AND ip.estado IN (1, 2, 3)
        $whereTipo
    ";

    $registros = $database->select($query);

    $validaciones = [];
    $reportes = [];
    $correjidos = [];

    foreach ($registros as $row) {
        $item = [
            "tactico" => $row["tactico"],
            "gestor" => $row["gestor"],
            "pdv" => $row["pdv"],
            "fecha_creacion" => $row["fecha_formateada"] ?? "Sin fecha"
        ];

        if ($row["estado"] == 2) {
            $item["cantidad_armada"] = $row["cantidad_armada"];
            $validaciones[] = $item;
        } elseif ($row["estado"] == 3) {
            $item["motivo"] = $row["motivo"] ?? "Sin motivo";
            $reportes[] = $item;
        } elseif ($row["estado"] == 1) {
            $item["motivo"] = $row["motivo"] ?? "Sin motivo";
            $correjidos[] = $item;
        }
    }

    $response = [
        "success" => true,
        "validaciones" => $validaciones,
        "reportes" => $reportes,
        "correjidos" => $correjidos
    ];

    echo json_encode($response, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error al obtener los datos",
        "details" => $e->getMessage()
    ]);
}
