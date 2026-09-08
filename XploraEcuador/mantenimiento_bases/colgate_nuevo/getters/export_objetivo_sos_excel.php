<?php
// getters/export_objetivo_sos_excel.php
include_once "../includes/db_connect.php";

set_time_limit(0);
ini_set('memory_limit', '512M');

$mysqli->set_charset("utf8mb4");

// ---------------- DATOS ----------------
$search_value = $_POST['search_value'] ?? '';
$fecha_inicio = $_POST['fecha_inicio'] ?? null;
$fecha_fin    = $_POST['fecha_fin'] ?? null;

$cols = [
    'id', 'fecha', 'canal', 'retail_enviroment', 'cliente',
    'visual_access', 'subcategory', 'new_objetivo', 'fecha_modificacion'
];

$base_query = "FROM tb_objetivo_sos WHERE status = 1";
$params = [];
$param_types = '';

// Filtros fecha
if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $base_query .= " AND fecha BETWEEN ? AND ?";
    $param_types .= 'ss';
    $params[] = $fecha_inicio;
    $params[] = $fecha_fin;
} elseif (!empty($fecha_inicio)) {
    $base_query .= " AND fecha >= ?";
    $param_types .= 's';
    $params[] = $fecha_inicio;
} elseif (!empty($fecha_fin)) {
    $base_query .= " AND fecha <= ?";
    $param_types .= 's';
    $params[] = $fecha_fin;
}

// Búsqueda global
if (!empty($search_value)) {
    $base_query .= " AND (";
    $likes = [];
    foreach ($cols as $c) {
        $likes[] = "$c LIKE ?";
        $param_types .= 's';
        $params[] = "%$search_value%";
    }
    $base_query .= implode(' OR ', $likes) . ")";
}

$sql = "SELECT " . implode(', ', $cols) . " $base_query";

// ---------------- CREAR SHEET XML ----------------
$tmpSheet = tempnam(sys_get_temp_dir(), 'sheet');
$fd = fopen($tmpSheet, 'w');

fwrite($fd,
'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
'<sheetData>'
);

// Encabezados
fwrite($fd, '<row r="1">');
foreach ($cols as $col) {
    fwrite($fd, '<c t="inlineStr"><is><t>' . htmlspecialchars($col) . '</t></is></c>');
}
fwrite($fd, '</row>');

// Datos
$stmt = $mysqli->prepare($sql);
if (!empty($param_types)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$rowNum = 2;
while ($row = $result->fetch_assoc()) {
    fwrite($fd, '<row r="' . $rowNum . '">');
    foreach ($cols as $c) {
        $val = $row[$c];
        if (is_numeric($val) && strlen((string)$val) < 11) {
            fwrite($fd, '<c><v>' . $val . '</v></c>');
        } else {
            fwrite($fd, '<c t="inlineStr"><is><t>' . htmlspecialchars($val ?? '') . '</t></is></c>');
        }
    }
    fwrite($fd, '</row>');
    $rowNum++;
}

fwrite($fd, '</sheetData></worksheet>');
fclose($fd);
$stmt->close();

// ---------------- ZIP XLSX ----------------
$tmpZip = tempnam(sys_get_temp_dir(), 'xlsx');
$zip = new ZipArchive();
$zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);

$zip->addFromString('_rels/.rels',
'<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1"
    Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument"
    Target="xl/workbook.xml"/>
</Relationships>');

$zip->addFromString('xl/workbook.xml',
'<?xml version="1.0" encoding="UTF-8"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
 xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
 <sheets>
  <sheet name="Objetivo SOS" sheetId="1" r:id="rId1"/>
 </sheets>
</workbook>');

$zip->addFromString('xl/_rels/workbook.xml.rels',
'<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
 <Relationship Id="rId1"
  Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet"
  Target="worksheets/sheet1.xml"/>
</Relationships>');

$zip->addFromString('[Content_Types].xml',
'<?xml version="1.0" encoding="UTF-8"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
 <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
 <Default Extension="xml" ContentType="application/xml"/>
 <Override PartName="/xl/workbook.xml"
  ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
 <Override PartName="/xl/worksheets/sheet1.xml"
  ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
</Types>');

$zip->addFile($tmpSheet, 'xl/worksheets/sheet1.xml');
$zip->close();

// ---------------- DESCARGA ----------------
$filename = 'objetivo_sos_export_' . date('Ymd_His') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($tmpZip));
header('Cache-Control: max-age=0');

readfile($tmpZip);

// Limpieza
unlink($tmpSheet);
unlink($tmpZip);
$mysqli->close();
exit;
