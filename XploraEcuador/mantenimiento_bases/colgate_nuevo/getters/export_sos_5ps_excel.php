<?php
// getters/export_sos_5ps_excel.php
include_once "../includes/db_connect.php";

// Aumentar límites para evitar cortes
set_time_limit(0);
ini_set('memory_limit', '512M'); // Estrictamente necesario pero eficiente

$mysqli->set_charset("utf8mb4");

// --- RECOGIDA DE DATOS ---
$search_value = $_POST['search_value'] ?? '';
$fecha_inicio = $_POST['fecha_inicio'] ?? null;
$fecha_fin = $_POST['fecha_fin'] ?? null;

$cols = ['ID', 'DAY', 'TIME', 'CP_CODE', 'MANUFACTURER', 'SUBCATEGORY', 'TOTAL_HOOKS', 'IND_HOOKS', 'SOS', 'SERVER_DATE', 'MODIFICADO'];

$base_query = "FROM tb_sos_5ps WHERE status = 1";
$params = [];
$param_types = '';

// (Tu lógica de filtros se mantiene igual)
if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $base_query .= " AND DATE(day) BETWEEN ? AND ?";
    $param_types .= 'ss'; $params[] = $fecha_inicio; $params[] = $fecha_fin;
}
if (!empty($search_value)) {
    $base_query .= " AND (cp_code LIKE ? OR manufacturer LIKE ?)";
    $param_types .= 'ss';
    $term = "%$search_value%"; $params[] = $term; $params[] = $term;
}

$sql_data = "SELECT id, day, time, cp_code, manufacturer, subcategory, total_cms_hooks, cms_individual_hooks, sos, server_date, fecha_modificacion " . $base_query;

// --- GENERADOR XLSX AUTÓNOMO ---

// 1. Crear archivo temporal para la hoja de datos
$tmpSheet = tempnam(sys_get_temp_dir(), 'sheet');
$fd = fopen($tmpSheet, 'w');

// Cabecera obligatoria de la hoja
fwrite($fd, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheetData>');

// Fila 1: Encabezados
fwrite($fd, '<row r="1">');
foreach ($cols as $k => $v) {
    fwrite($fd, '<c t="inlineStr"><is><t>' . htmlspecialchars($v) . '</t></is></c>');
}
fwrite($fd, '</row>');

// Filas de datos desde la DB
if ($stmt = $mysqli->prepare($sql_data)) {
    if (!empty($param_types)) $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $rowIdx = 2;
    
    while ($row = $result->fetch_row()) {
        fwrite($fd, '<row r="' . $rowIdx . '">');
        foreach ($row as $val) {
            if (is_numeric($val) && (strlen($val) < 11)) { // Evitar números que parecen IDs largos
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

// 2. Crear el ZIP (XLSX) con la estructura completa requerida
$zipFile = tempnam(sys_get_temp_dir(), 'xlsx');
$zip = new ZipArchive();
$zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

// Archivos de estructura (Sin estos, Excel da error de "Recuperar contenido")
$zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');

$zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Reporte" sheetId="1" r:id="rId1"/></sheets></workbook>');

$zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');

$zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');

// Añadimos el archivo de datos que creamos antes
$zip->addFile($tmpSheet, 'xl/worksheets/sheet1.xml');
$zip->close();

// 3. Descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="Reporte_SOS_' . date('Ymd_His') . '.xlsx"');
header('Content-Length: ' . filesize($zipFile));
header('Cache-Control: max-age=0');

readfile($zipFile);

// Limpiar
unlink($tmpSheet);
unlink($zipFile);
$mysqli->close();
exit;