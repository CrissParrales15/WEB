<?php
// export_pdv_excel.php
include_once "../includes/db_connect.php";

// Aumentar límites para reportes grandes
set_time_limit(0);
ini_set('memory_limit', '512M');

$mysqli->set_charset("utf8mb4");

// --- RECOGIDA DE PARÁMETROS ---
$search_value = $_POST['search_value'] ?? '';
$fecha_inicio = $_POST['fecha_inicio'] ?? null;
$fecha_fin = $_POST['fecha_fin'] ?? null;

// Definir columnas (Encabezados para el Excel)
$cols = [
    'ID', 'DAY', 'CP_CODE', 'CUSTOMER_CODE', 'TRADE', 'RETAIL_ENVIRONMENT', 'RE',
    'CP_FORMAT', 'CUSTOMER_FORMAT_BANNER', 'TARGET', 'DISTRIBUIDOR', 'CUSTOMER',
    'POS', 'RUTA', 'REGION', 'TERRITORY', 'PROVINCE', 'CITY', 'ZONE', 'ADDRESS',
    'SUPERVISOR', 'MERCHANDISER', 'USER', 'X', 'Y', 'SOB', 'VISUAL_ACCESS',
    'FECHA_MODIFICACION'
];

$base_query = "FROM tb_pdv WHERE status = 1";
$params = [];
$param_types = '';

// FILTROS DE FECHA
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

// FILTRO DE BÚSQUEDA GLOBAL
if (!empty($search_value)) {
    $base_query .= " AND (pos LIKE ? OR customer LIKE ? OR route LIKE ? OR cp_code LIKE ?)";
    $param_types .= 'ssss';
    $term = "%$search_value%";
    $params[] = $term; $params[] = $term; $params[] = $term; $params[] = $term;
}

// Consulta exacta (usa los mismos campos que $cols pero en minúsculas de la DB)
$sql_data = "SELECT id, day, cp_code, customer_code, trade, retail_enviroment, re, 
                    cp_format, customer_format_banner, target, distribuidor, customer, 
                    pos, ruta, region, territory, province, city, zone, address, 
                    supervisor, merchandiser, user, x, y, sob, visual_access, 
                    fecha_modificacion " . $base_query;

// --- GENERADOR XLSX ---

// 1. Crear archivo temporal para los datos
$tmpSheet = tempnam(sys_get_temp_dir(), 'sheet_pdv');
$fd = fopen($tmpSheet, 'w');

// Cabecera XML de la hoja
fwrite($fd, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheetData>');

// Fila 1: Encabezados
fwrite($fd, '<row r="1">');
foreach ($cols as $v) {
    fwrite($fd, '<c t="inlineStr"><is><t>' . htmlspecialchars($v) . '</t></is></c>');
}
fwrite($fd, '</row>');

// Filas de datos
if ($stmt = $mysqli->prepare($sql_data)) {
    if (!empty($param_types)) {
        $stmt->bind_param($param_types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $rowIdx = 2;
    
    while ($row = $result->fetch_row()) {
        fwrite($fd, '<row r="' . $rowIdx . '">');
        foreach ($row as $val) {
            // Si es numérico (y no es un ID muy largo que deba ser texto)
            if (is_numeric($val) && strlen($val) < 12) {
                fwrite($fd, '<c><v>' . $val . '</v></c>');
            } else {
                fwrite($fd, '<c t="inlineStr"><is><t>' . htmlspecialchars($val ?? '') . '</t></is></c>');
            }
        }
        fwrite($fd, '</row>');
        $rowIdx++;
    }
    $stmt->close();
}
fwrite($fd, '</sheetData></worksheet>');
fclose($fd);

// 2. Empaquetar en ZIP con estructura XLSX
$zipFile = tempnam(sys_get_temp_dir(), 'xlsx_pdv');
$zip = new ZipArchive();
$zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

// Estructura mínima necesaria
$zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');

$zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="PDV_Report" sheetId="1" r:id="rId1"/></sheets></workbook>');

$zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');

$zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');

// Añadir la hoja de datos
$zip->addFile($tmpSheet, 'xl/worksheets/sheet1.xml');
$zip->close();

// 3. Descarga forzada como XLSX
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="Reporte_PDV_' . date('Ymd_His') . '.xlsx"');
header('Content-Length: ' . filesize($zipFile));
header('Cache-Control: max-age=0');

readfile($zipFile);

// Limpiar archivos temporales
unlink($tmpSheet);
unlink($zipFile);
$mysqli->close();
exit;
?>