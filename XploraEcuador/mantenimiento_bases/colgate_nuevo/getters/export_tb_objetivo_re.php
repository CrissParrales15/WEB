<?php
include_once "../includes/db_connect.php";

set_time_limit(0);
ini_set('memory_limit', '512M');

$mysqli->set_charset("utf8mb4");

$search_value = $_POST['search_value'] ?? '';
$fecha_inicio = $_POST['fecha_inicio'] ?? null;
$fecha_fin = $_POST['fecha_fin'] ?? null;

$cols = ['ID', 'FECHA', 'CÓDIGO PDV', 'OBJETIVO'];

$base_query = "FROM tb_objetivo_re WHERE status = 1";
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
    $base_query .= " AND (codigo_pdv LIKE ? OR objetivo LIKE ?)";
    $param_types .= 'ss';
    $term = "%$search_value%";
    $params[] = $term; 
    $params[] = $term;
}

$sql_data = "SELECT id, fecha, codigo_pdv, objetivo " . $base_query;

$tmpSheet = tempnam(sys_get_temp_dir(), 'sheet_objetivo');
$fd = fopen($tmpSheet, 'w');

fwrite($fd, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheetData>');

fwrite($fd, '<row r="1">');
foreach ($cols as $v) {
    fwrite($fd, '<c t="inlineStr"><is><t>' . htmlspecialchars($v) . '</t></is></c>');
}
fwrite($fd, '</row>');

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

$zipFile = tempnam(sys_get_temp_dir(), 'xlsx_objetivo');
$zip = new ZipArchive();
$zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

$zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');

$zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Objetivos_RE" sheetId="1" r:id="rId1"/></sheets></workbook>');

$zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');

$zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');

$zip->addFile($tmpSheet, 'xl/worksheets/sheet1.xml');
$zip->close();

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="Reporte_Objetivos_RE_' . date('Ymd_His') . '.xlsx"');
header('Content-Length: ' . filesize($zipFile));
header('Cache-Control: max-age=0');

readfile($zipFile);

unlink($tmpSheet);
unlink($zipFile);
$mysqli->close();
exit;
?>