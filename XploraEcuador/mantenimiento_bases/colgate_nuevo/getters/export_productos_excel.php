<?php
// getters/export_productos_excel.php
include_once "../includes/db_connect.php";

// Aumentar límites para evitar cortes en reportes grandes
set_time_limit(0);
ini_set('memory_limit', '512M');

$mysqli->set_charset("utf8mb4");

// --- RECOGIDA DE DATOS Y FILTROS ---
$search_value = $_POST['search_value'] ?? '';
// En el JS enviamos esto con JSON.stringify, lo decodificamos:
$subcategoria_filtro = isset($_POST['subcategoria_filtro']) ? json_decode($_POST['subcategoria_filtro'], true) : null;

// Columnas para el Excel
$cols_headers = [
    'ID', 'PAÍS', 'FABRICANTE', 'CATEGORÍA', 'SUBCATEGORÍA', 
    'ABREVIATURA', 'MARCA', 'EAN', 'FAMILIA/SEGMENTO', 
    'DESCRIPCIÓN', 'PROPIO/COMP.', 'HOMOLOGADO', 'GRAMAJE', 'MODIFICADO'
];

// Columnas de la base de datos (mismo orden que el SELECT)
$db_cols = [
    'id', 'pais', 'fabricante', 'categoria', 'subcategoria', 
    'abreviatura_subcategoria', 'marca', 'codigo_ean_pais', 
    'familia_segmento', 'descripcion_producto', 'propio_competencia', 
    'descripcion_producto_homologado', 'gramaje_tamanio', 'fecha_modificacion'
];

// --- CONSTRUCCIÓN DE LA QUERY ---
$base_query = " FROM tb_productos WHERE status = 1";
$params = [];
$param_types = '';

// Filtro Subcategoría (Multiple)
if (!empty($subcategoria_filtro)) {
    if (is_array($subcategoria_filtro)) {
        $placeholders = implode(',', array_fill(0, count($subcategoria_filtro), '?'));
        $base_query .= " AND subcategoria IN ($placeholders)";
        foreach ($subcategoria_filtro as $sub) {
            $param_types .= 's';
            $params[] = $sub;
        }
    } else {
        $base_query .= " AND subcategoria = ?";
        $param_types .= 's';
        $params[] = $subcategoria_filtro;
    }
}

// Filtro Búsqueda Global (DataTables search)
if (!empty($search_value)) {
    $base_query .= " AND (";
    $search_conditions = [];
    foreach ($db_cols as $col) {
        $search_conditions[] = "$col LIKE ?";
        $param_types .= 's';
        $params[] = "%$search_value%";
    }
    $base_query .= implode(' OR ', $search_conditions) . ")";
}

$sql_data = "SELECT " . implode(', ', $db_cols) . $base_query . " ORDER BY id DESC";

// --- GENERADOR XLSX AUTÓNOMO (Tu método que funciona) ---

$tmpSheet = tempnam(sys_get_temp_dir(), 'sheet');
$fd = fopen($tmpSheet, 'w');

// Cabecera del XML
fwrite($fd, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheetData>');

// Fila 1: Encabezados
fwrite($fd, '<row r="1">');
foreach ($cols_headers as $v) {
    fwrite($fd, '<c t="inlineStr"><is><t>' . htmlspecialchars($v) . '</t></is></c>');
}
fwrite($fd, '</row>');

// Datos de la DB
if ($stmt = $mysqli->prepare($sql_data)) {
    if (!empty($param_types)) $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $rowIdx = 2;
    
    while ($row = $result->fetch_row()) {
        fwrite($fd, '<row r="' . $rowIdx . '">');
        foreach ($row as $val) {
            // Si es numérico y no es un EAN/ID largo, se guarda como número
            if (is_numeric($val) && (strlen($val) < 11)) {
                fwrite($fd, '<c><v>' . $val . '</v></c>');
            } else {
                // Para EANs y textos, usamos inlineStr para evitar errores de formato
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

// --- EMPAQUETADO ZIP (XLSX) ---
$zipFile = tempnam(sys_get_temp_dir(), 'xlsx');
$zip = new ZipArchive();
$zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

$zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
$zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Productos" sheetId="1" r:id="rId1"/></sheets></workbook>');
$zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
$zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');

$zip->addFile($tmpSheet, 'xl/worksheets/sheet1.xml');
$zip->close();

// --- DESCARGA ---
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="Reporte_Productos_' . date('Ymd_His') . '.xlsx"');
header('Content-Length: ' . filesize($zipFile));
header('Cache-Control: max-age=0');

readfile($zipFile);

// Limpiar archivos temporales
unlink($tmpSheet);
unlink($zipFile);
$mysqli->close();
exit;