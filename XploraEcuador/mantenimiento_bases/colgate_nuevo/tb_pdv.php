<?php

use Phppot\DataSource;

require_once 'DataSource.php';
$db = new DataSource();
$conn = $db->getConnection();

// ⭐ ASEGURAR UTF-8 en la conexión MySQL
if ($conn instanceof mysqli) {
    $conn->set_charset("utf8mb4");
} else {
    // Si usas PDO
    $conn->exec("SET NAMES utf8mb4");
}

// NUEVO: PUNTO DE ENTRADA PARA PROCESAMIENTO AJAX POR LOTES
if (isset($_POST['lote_data']) && !empty($_POST['lote_data'])) {
    
    $lote_data = json_decode($_POST['lote_data'], true);
    
    header('Content-Type: application/json; charset=UTF-8');
    
    if ($lote_data === null) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "JSON mal formado o vacío."]);
        exit;
    }
    
    $resultado = procesarLote($db, $lote_data, $conn);
    
    if ($resultado) {
        echo json_encode(["status" => "success", "message" => "Lote procesado correctamente."]);
    } else {
        http_response_code(500); 
        echo json_encode(["status" => "error", "message" => "Error al insertar el lote en la base de datos."]);
    }
    exit;
}

if (isset($_POST["import"])) {

    $fileName = $_FILES["file"]["tmp_name"];
    $fileExtension = strtolower(pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION));

    if ($_FILES["file"]["size"] > 0) {

        $tamano_lote = 1000;
        $contador = 0;
        $lote = array();

        if ($fileExtension === 'csv') {
            $file = fopen($fileName, "r");
            
            // Manejar BOM UTF-8
            $bom = fread($file, 3);
            if ($bom != "\xEF\xBB\xBF") {
                rewind($file);
            }
            
            $primeraFila = true;
            $totalFilas = 0;

            while (($column = fgetcsv($file, 27000, ";")) !== FALSE) {
                $totalFilas++;

                if ($primeraFila) {
                    $primeraFila = false;
                    continue;
                }

                // ⭐ SOLUCIÓN MEJORADA PARA ACENTOS (Rumiñahui)
                $column = array_map(function($value) {
                    if ($value === null || $value === "") return $value;
                    
                    // Si el valor NO es UTF-8 válido, asumimos que viene de Excel/Windows Latino
                    if (!mb_check_encoding($value, 'UTF-8')) {
                        return mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
                    }
                    return $value;
                }, $column);

                $lote[] = $column;
                $contador++;

                if ($contador == $tamano_lote) {
                    $resultado = procesarLote($db, $lote, $conn); 
                    $contador = 0;
                    $lote = array();
                }
            }

            if (count($lote) > 0) {
                $resultado = procesarLote($db, $lote, $conn);
                $contador = 0;
                $lote = array();
            }

            fclose($file);

        } elseif ($fileExtension === 'xlsx' || $fileExtension === 'xls') {
            require_once 'SimpleXLSX.php';

            if ($xlsx = SimpleXLSX::parse($fileName)) {
                $rows = $xlsx->rows();
                $firstRow = true;

                foreach ($rows as $row) {
                    if ($firstRow) {
                        $firstRow = false;
                        continue;
                    }

                    // ⭐ Asegurar UTF-8 también para Excel (SimpleXLSX suele manejarlo, pero esto es doble capa de seguridad)
                    $row = array_map(function($value) {
                        if (is_string($value) && $value !== "") {
                            if (!mb_check_encoding($value, 'UTF-8')) {
                                return mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
                            }
                        }
                        return $value;
                    }, $row);

                    $lote[] = $row;
                    $contador++;

                    if ($contador == $tamano_lote) {
                        $resultado = procesarLote($db, $lote, $conn); 
                        $contador = 0;
                        $lote = array();
                    }
                }

                if (count($lote) > 0) {
                    $resultado = procesarLote($db, $lote, $conn); 
                    $contador = 0;
                    $lote = array();
                }

            } else {
                $type = "error";
                $message = "Error al leer el archivo: " . SimpleXLSX::parseError();
            }
        }

        $type = "success";
        $message = "Proceso completado."; 
    }

    if (isset($type) && $type === "success") {
        header("Location: " . $_SERVER['PHP_SELF'] . "?import_status=success");
        exit;
    }
}

if (isset($_GET['import_status']) && $_GET['import_status'] === 'success') {
    $type = "success";
    $message = "La importación se completó correctamente.";
}


if (isset($_POST["delete_by_excel"])) {

    $fileName = $_FILES["file_delete"]["tmp_name"];
    $fileExtension = strtolower(pathinfo($_FILES["file_delete"]["name"], PATHINFO_EXTENSION));
    
    $idsParaEliminar = array();
    $totalProcesados = 0;
    $exitoGlobal = true;
    
    // ⭐ TAMAÑO DEL LOTE - Procesar cada X IDs
    $batch_size = 1000;

    if ($_FILES["file_delete"]["size"] > 0) {
        
        if ($fileExtension === 'csv') {
            $file = fopen($fileName, "r");
            
            // Detectar BOM UTF-8
            $bom = fread($file, 3);
            if ($bom != "\xEF\xBB\xBF") {
                rewind($file);
            }
            
            $primeraFila = true;

            // ⭐ PROCESAR POR LOTES EN LUGAR DE ALMACENAR TODO
            while (($column = fgetcsv($file, 1000, ";")) !== FALSE) {
                if ($primeraFila) {
                    $primeraFila = false;
                    continue;
                }

                // Agregar ID al lote temporal
                if (isset($column[0]) && is_numeric(trim($column[0]))) {
                    $idsParaEliminar[] = intval(trim($column[0]));
                }
                
                // ⭐ Cuando el lote alcance el tamaño, procesarlo inmediatamente
                if (count($idsParaEliminar) >= $batch_size) {
                    $idsUnicos = array_unique($idsParaEliminar);
                    
                    if (!softDeletePdvByIds($db, $idsUnicos)) {
                        $exitoGlobal = false;
                        break;
                    }
                    
                    $totalProcesados += count($idsUnicos);
                    
                    // Limpiar el array para el siguiente lote
                    $idsParaEliminar = array();
                }
            }
            
            fclose($file);
            
            // ⭐ Procesar el último lote si quedaron IDs pendientes
            if ($exitoGlobal && count($idsParaEliminar) > 0) {
                $idsUnicos = array_unique($idsParaEliminar);
                
                if (!softDeletePdvByIds($db, $idsUnicos)) {
                    $exitoGlobal = false;
                } else {
                    $totalProcesados += count($idsUnicos);
                }
            }

        } elseif ($fileExtension === 'xlsx' || $fileExtension === 'xls') {
            require_once 'SimpleXLSX.php';

            if ($xlsx = SimpleXLSX::parse($fileName)) {
                $rows = $xlsx->rows();
                $firstRow = true;

                // ⭐ PROCESAR POR LOTES TAMBIÉN PARA EXCEL
                foreach ($rows as $row) {
                    if ($firstRow) {
                        $firstRow = false;
                        continue;
                    }
                    
                    if (isset($row[0]) && is_numeric(trim($row[0]))) {
                        $idsParaEliminar[] = intval(trim($row[0]));
                    }
                    
                    // ⭐ Procesar cuando alcance el tamaño del lote
                    if (count($idsParaEliminar) >= $batch_size) {
                        $idsUnicos = array_unique($idsParaEliminar);
                        
                        if (!softDeletePdvByIds($db, $idsUnicos)) {
                            $exitoGlobal = false;
                            break;
                        }
                        
                        $totalProcesados += count($idsUnicos);
                        $idsParaEliminar = array();
                    }
                }
                
                // ⭐ Procesar último lote pendiente
                if ($exitoGlobal && count($idsParaEliminar) > 0) {
                    $idsUnicos = array_unique($idsParaEliminar);
                    
                    if (!softDeletePdvByIds($db, $idsUnicos)) {
                        $exitoGlobal = false;
                    } else {
                        $totalProcesados += count($idsUnicos);
                    }
                }
                
            } else {
                $type = "error";
                $message = "Error al leer el archivo: " . SimpleXLSX::parseError();
                $exitoGlobal = false;
            }
        }

        // ⭐ MENSAJES ACTUALIZADOS
        if ($exitoGlobal && $totalProcesados > 0) {
            $type = "success";
            $message = "Borrado completado correctamente. Se procesaron $totalProcesados IDs únicos.";
        } elseif ($totalProcesados > 0) {
            $type = "warning";
            $message = "Proceso interrumpido. Se lograron procesar $totalProcesados IDs antes del error.";
        } elseif ($totalProcesados === 0 && $exitoGlobal) {
            $type = "warning";
            $message = "No se encontraron IDs de PDV válidos para eliminar en el archivo.";
        } else {
            $type = "error";
            $message = "Ocurrió un error durante el borrado masivo.";
        }
        
    } else {
        $type = "error";
        $message = "El archivo está vacío o no se subió correctamente.";
    }
    
    // ⭐ REDIRECCIÓN CON TOTAL PROCESADO
    if (isset($type) && $type === "success") {
        header("Location: " . $_SERVER['PHP_SELF'] . "?delete_status=success&count=" . $totalProcesados);
        exit;
    } else if (isset($type)) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?delete_status={$type}&message=" . urlencode($message));
        exit;
    }
}

if (isset($_GET['delete_status']) && $_GET['delete_status'] === 'success') {
    $count = isset($_GET['count']) ? (int)$_GET['count'] : 0;
    $type = "success";
    $message = "El borrado Masivo se completó correctamente. Se actualizaron $count registros.";
} elseif (isset($_GET['delete_status']) && ($_GET['delete_status'] === 'error' || $_GET['delete_status'] === 'warning')) {
    $type = $_GET['delete_status'];
    $message = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : 'Ocurrió un error al intentar la eliminación masiva.';
}

function convertirFechaUniversal($valor) {
    if ($valor === null || trim($valor) === "") return null;

    if (is_numeric($valor)) {
        $unix = ($valor - 25569) * 86400;
        return date("Y-m-d", $unix);
    }

    $valor = str_replace('/', '-', trim($valor));
    $ts = strtotime($valor);

    return ($ts) ? date("Y-m-d", $ts) : null;
}


function convertirDecimal($valor) {
    if ($valor === null || trim($valor) === "") return 0.0;
    $valor = str_replace(',', '.', $valor);
    return floatval($valor);
}


function procesarLote($db, $lote, $conn)
{
    // ⭐ Asegurar UTF-8 antes de insertar
    if ($conn instanceof mysqli) {
        $conn->set_charset("utf8mb4");
    }
    
    $sqlInsert = "INSERT INTO tb_pdv (
        day, cp_code, customer_code, trade, retail_enviroment, re, cp_format, 
        customer_format_banner, target, distribuidor, customer, pos, ruta, region, 
        territory, province, city, zone, address, supervisor, merchandiser, user, 
        x, y, sob, visual_access
    ) VALUES ";
    
    $paramType = "";
    $paramArray = array();
    $registrosValidos = 0;

    foreach ($lote as $index => $fila) {
        $hayDato = false;

        for ($i = 0; $i <= 25; $i++) {
            if (isset($fila[$i]) && trim($fila[$fila[25] ? $i : 0]) !== "" && strtoupper(trim($fila[$i])) !== "NULL") { 
                $hayDato = true; 
                break; 
            }
        }
        
        if (!$hayDato) {
            continue;
        }

        $sqlInsert .= "(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?),";
        
        $paramType .= "ssssssssssssssssssssssssds";

        // 0. day (Fecha)
        $paramArray[] = convertirFechaUniversal($fila[0]);

        // 1 a 21. Cadenas de texto
        for ($i = 1; $i <= 21; $i++) {
            $paramArray[] = isset($fila[$i]) ? trim($fila[$i]) : null;
        }

        // 22 y 23. Más texto (x, y)
        $paramArray[] = isset($fila[22]) ? trim($fila[22]) : null;
        $paramArray[] = isset($fila[23]) ? trim($fila[23]) : null;

        // 24. sob (Decimal/Double)
        $paramArray[] = convertirDecimal($fila[24]);

        // 25. visual_access (String)
        $paramArray[] = isset($fila[25]) ? trim($fila[25]) : null;

        $registrosValidos++;
    }

    if ($registrosValidos === 0) {
        return false;
    }

    $sqlInsert = rtrim($sqlInsert, ',');

    $insertId = $db->insertMultiple($sqlInsert, $paramType, $paramArray);

    return !empty($insertId) || $insertId !== false;
}


function softDeletePdvByIds($db, $ids)
{
    if (empty($ids)) {
        return false;
    }

    // Convertir el array de IDs en una lista de placeholders para la consulta
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    $paramType = str_repeat('i', count($ids));
    
    $query = "UPDATE tb_pdv SET status = 0 WHERE id IN ($placeholders)";
    
    $updateResult = $db->execute($query, $paramType, $ids);
    return $updateResult !== false;
}
?>

<!DOCTYPE html>
<html>

<head>

<link rel="stylesheet" href="/App/XploraEcuador/assets/css/bootstrap-5.1.3.min.css">

<link rel="stylesheet" href="style.css">
<script src="/App/XploraEcuador/assets/js/jquery-3.2.1.min.js"></script>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link rel="stylesheet" href="/App/XploraEcuador/assets/css/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
<link href="/App/XploraEcuador/assets/css/select2.min.css" rel="stylesheet" />

<style>
    .input-row {
        margin-top: 0px;
        margin-bottom: 20px;
    }

    .btn-submit {
        background: #333;
        border: #1d1d1d 1px solid;
        color: #f0f0f0;
        font-size: 0.9em;
        width: 100px;
        border-radius: 2px;
        cursor: pointer;
    }

    #response {
        padding: 10px;
        margin-bottom: 10px;
        border-radius: 2px;
        display: none;
    }

    .success {
        background: #c7efd9;
        border: #bbe2cd 1px solid;
    }

    .error {
        background: #fbcfcf;
        border: #f3c6c7 1px solid;
    }

    div#response.display-block {
        display: block;
    }
</style>
<script type="text/javascript">
$(document).ready(function() {
    $("#frmImport").on("submit", function () {
        $("#loading").css("display", "flex");
        $("#response").attr("class", "");
        $("#response").html("");
        var fileType = ".csv|.xlsx|.xls";
        var regex = new RegExp("([a-zA-Z0-9\\s_\\.-:])+(" + fileType + ")$");
        if (!regex.test($("#file").val().toLowerCase())) {
            $("#response").addClass("error");
            $("#response").addClass("display-block");
            $("#response").html("Archivo inválido. Sube archivos: <b>CSV</b>");
            $("#loading").css("display", "none");
            return false;
        }
        return true;
    });
});
</script>
</head>

<body>  

<div id="loading" class="loading-img"
        style=" display: flex;position: fixed; width: 100%; height: 100%; top: 0px; left: 0px; z-index: 999999; overflow: auto; background-color: rgba(0, 0, 0, 0.498039);">
        <img style="position: absolute; top: 50% !important; left: 47% !important;" src="loader_1.gif">
    </div>

    <div id="content" style="width:100%;">
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container-fluid">
                <h3>Gestión de Puntos de Venta (PDV)</h3>
            </div>
        </nav>

        <div id="response"
            class="<?php if(!empty($type)) { echo $type . " display-block"; } ?>">
            <?php 
                if(!empty($message)) {
                    echo $message;
                } 
            ?>
        </div>

        <div class="row">
            <form class="form-horizontal" action="" method="post" name="frmImport" id="frmImport" enctype="multipart/form-data">
                <div class="input-row">
                    <label class="col-md-4 control-label">Seleccionar archivo CSV</label> 
                    <input type="file" name="file" id="file" accept=".csv">
                    <button type="submit" id="submit" name="import" class="btn-submit">Import</button>
                    <br />
                    <small style="color: #666;">Formatos aceptados: CSV (separado por ;)</small>
                </div>
            </form>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">Eliminación Masiva por ID (Puntos de Venta)</h5>
                    </div>
                    <div class="card-body">
                        <form class="form-horizontal" id="frmExcelDelete" enctype="multipart/form-data">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <label class="control-label font-weight-bold">Seleccionar archivo (CSV)</label>
                                    <input type="file" name="file_delete" id="file_delete" class="form-control" accept=".csv" required>
                                    <small class="text-muted d-block mt-1">
                                        * El archivo debe tener el <b>ID</b> en la primera columna (Columna A).
                                    </small>
                                </div>
                                <div class="col-md-6 text-right">
                                    <button type="button" id="btnConfirmDeleteExcel" class="btn btn-danger btn-lg">
                                        <i class="fas fa-trash-alt"></i> Proceder con la Eliminación
                                    </button>
                                </div>
                            </div>
                        </form>

                        <div id="progreso-contenedor" style="display:none; margin-top: 20px;">
                            <hr>
                            <p id="progreso-texto" class="font-weight-bold text-danger mb-2">Iniciando proceso...</p>
                            <div class="progress" style="height: 30px;">
                                <div id="barra-progreso" class="progress-bar progress-bar-striped progress-bar-animated bg-danger" 
                                    role="progressbar" style="width: 0%; font-weight: bold; line-height: 30px;">0%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        

        <div class="row">

        <div class="col-xl-6 col-lg-6 mb-4">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">Desde:</span>
                </div>
                <input type="date" class="form-control" id="date-start-pdv">
                
                <div class="input-group-prepend">
                    <span class="input-group-text">Hasta:</span>
                </div>
                <input type="date" class="form-control" id="date-end-pdv">

                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="button" id="clear-filter-pdv" title="Limpiar Filtro">
                        <i class="material-icons" style="font-size: 1.2rem;">close</i>
                    </button>
                </div>
            </div>
        </div>



            <div class="col-xl-12 col-lg-12 mb-4">
                <button type="button" class="btn btn-success mb-3" data-toggle="modal" data-target="#modalAgregar">
                    <i class="material-icons" style="font-size: 1.2rem;">add</i> Agregar Nuevo PDV
                </button>
                <button type="button" class="btn btn-danger mb-3 ml-2" id="btnEliminarSeleccionados" style="display:none;">
                    <i class="material-icons" style="font-size: 1.2rem;">delete</i> Eliminar Seleccionados (<span id="contadorSeleccionados">0</span>)
                </button>
                <div class="bg-white rounded-lg p-5 shadow" id="data-result" name="data-result"></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditar" tabindex="-1" role="dialog" aria-labelledby="modalEditarLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarLabel">Editar PDV</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formEditar">
                        <input type="hidden" id="edit_id" name="edit_id">
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_day">Día</label>
                                    <input type="date" class="form-control" id="edit_day" name="edit_day">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_cp_code">CP Code</label>
                                    <input type="text" class="form-control" id="edit_cp_code" name="edit_cp_code">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_customer_code">Customer Code</label>
                                    <input type="text" class="form-control" id="edit_customer_code" name="edit_customer_code">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_trade">Trade</label>
                                    <input type="text" class="form-control" id="edit_trade" name="edit_trade">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_retail_enviroment">Retail Enviroment</label>
                                    <input type="text" class="form-control" id="edit_retail_enviroment" name="edit_retail_enviroment">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_re">RE</label>
                                    <input type="text" class="form-control" id="edit_re" name="edit_re">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_cp_format">CP Format</label>
                                    <input type="text" class="form-control" id="edit_cp_format" name="edit_cp_format">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_customer_format_banner">Customer Format Banner</label>
                                    <input type="text" class="form-control" id="edit_customer_format_banner" name="edit_customer_format_banner">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_target">Target</label>
                                    <input type="text" class="form-control" id="edit_target" name="edit_target">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_distribuidor">Distribuidor</label>
                                    <input type="text" class="form-control" id="edit_distribuidor" name="edit_distribuidor">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_customer">Customer</label>
                                    <input type="text" class="form-control" id="edit_customer" name="edit_customer">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_pos">POS</label>
                                    <input type="text" class="form-control" id="edit_pos" name="edit_pos">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_ruta">Ruta</label>
                                    <input type="text" class="form-control" id="edit_ruta" name="edit_ruta">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_region">Región</label>
                                    <input type="text" class="form-control" id="edit_region" name="edit_region">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_territory">Territory</label>
                                    <input type="text" class="form-control" id="edit_territory" name="edit_territory">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_province">Province</label>
                                    <input type="text" class="form-control" id="edit_province" name="edit_province">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_city">City</label>
                                    <input type="text" class="form-control" id="edit_city" name="edit_city">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_zone">Zone</label>
                                    <input type="text" class="form-control" id="edit_zone" name="edit_zone">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="edit_address">Address</label>
                                    <textarea class="form-control" id="edit_address" name="edit_address" rows="2"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_supervisor">Supervisor</label>
                                    <input type="text" class="form-control" id="edit_supervisor" name="edit_supervisor">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_merchandiser">Merchandiser</label>
                                    <input type="text" class="form-control" id="edit_merchandiser" name="edit_merchandiser">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_user">User</label>
                                    <input type="text" class="form-control" id="edit_user" name="edit_user">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_x">Coordenada X</label>
                                    <input type="text" class="form-control" id="edit_x" name="edit_x">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_y">Coordenada Y</label>
                                    <input type="text" class="form-control" id="edit_y" name="edit_y">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_sob">SOB (Decimal)</label>
                                    <input type="number" step="0.01" class="form-control" id="edit_sob" name="edit_sob">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="edit_visual_access">Visual Access</label>
                                    <input type="text" class="form-control" id="edit_visual_access" name="edit_visual_access">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnGuardarCambiosPDV">Guardar Cambios</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalAgregar" tabindex="-1" role="dialog" aria-labelledby="modalAgregarLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAgregarLabel">Agregar Nuevo PDV</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formAgregar">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="add_day">Día</label>
                                    <input type="date" class="form-control" id="add_day" name="add_day" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="add_cp_code">CP Code</label>
                                    <input type="text" class="form-control" id="add_cp_code" name="add_cp_code">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="add_customer_code">Customer Code</label>
                                    <input type="text" class="form-control" id="add_customer_code" name="add_customer_code">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="add_trade">Trade</label>
                                    <input type="text" class="form-control" id="add_trade" name="add_trade">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="add_retail_enviroment">Retail Enviroment</label>
                                    <input type="text" class="form-control" id="add_retail_enviroment" name="add_retail_enviroment">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="add_re">RE</label>
                                    <input type="text" class="form-control" id="add_re" name="add_re">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="add_cp_format">CP Format</label>
                                    <input type="text" class="form-control" id="add_cp_format" name="add_cp_format">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="add_customer_format_banner">Customer Format Banner</label>
                                    <input type="text" class="form-control" id="add_customer_format_banner" name="add_customer_format_banner">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="add_target">Target</label>
                                    <input type="text" class="form-control" id="add_target" name="add_target">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="add_distribuidor">Distribuidor</label>
                                    <input type="text" class="form-control" id="add_distribuidor" name="add_distribuidor">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="add_customer">Customer</label>
                                    <input type="text" class="form-control" id="add_customer" name="add_customer">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="add_pos">POS</label>
                                    <input type="text" class="form-control" id="add_pos" name="add_pos">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="add_ruta">Ruta</label>
                                    <input type="text" class="form-control" id="add_ruta" name="add_ruta">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="add_region">Región</label>
                                    <input type="text" class="form-control" id="add_region" name="add_region">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="add_territory">Territory</label>
                                    <input type="text" class="form-control" id="add_territory" name="add_territory">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="add_province">Province</label>
                                    <input type="text" class="form-control" id="add_province" name="add_province">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="add_city">City</label>
                                    <input type="text" class="form-control" id="add_city" name="add_city">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="add_zone">Zone</label>
                                    <input type="text" class="form-control" id="add_zone" name="add_zone">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="add_address">Address</label>
                                    <textarea class="form-control" id="add_address" name="add_address" rows="2"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="add_supervisor">Supervisor</label>
                                    <input type="text" class="form-control" id="add_supervisor" name="add_supervisor">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="add_merchandiser">Merchandiser</label>
                                    <input type="text" class="form-control" id="add_merchandiser" name="add_merchandiser">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="add_user">User</label>
                                    <input type="text" class="form-control" id="add_user" name="add_user">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="add_x">Coordenada X</label>
                                    <input type="text" class="form-control" id="add_x" name="add_x">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="add_y">Coordenada Y</label>
                                    <input type="text" class="form-control" id="add_y" name="add_y">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="add_sob">SOB (Decimal)</label>
                                    <input type="number" step="0.01" class="form-control" id="add_sob" name="add_sob">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="add_visual_access">Visual Access</label>
                                    <input type="text" class="form-control" id="add_visual_access" name="add_visual_access">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="btnGuardarNuevoPDV">Guardar PDV</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Solo una versión de jQuery -->
    <script src="/App/XploraEcuador/assets/js/popper-1.12.9.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="/App/XploraEcuador/assets/js/bootstrap-4.0.0.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <!-- DATATABLE -->
    <link rel="stylesheet" href="/App/XploraEcuador/assets/css/dataTables.bootstrap5.min.css">
    <script src="/App/XploraEcuador/assets/js/jquery.dataTables.min.js"></script>
    <script src="/App/XploraEcuador/assets/js/dataTables.bootstrap5.min.js"></script>
    <script src="/App/XploraEcuador/assets/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/select/1.3.1/js/dataTables.select.min.js"></script>
    <script src="/App/XploraEcuador/assets/js/jszip.min.js"></script>
    <script src="/App/XploraEcuador/assets/js/pdfmake.min.js"></script>
    <script src="/App/XploraEcuador/assets/js/vfs_fonts.js"></script>
    <script src="/App/XploraEcuador/assets/js/buttons.html5.min.js"></script>
    <script src="/App/XploraEcuador/assets/js/buttons.print.min.js"></script>
    <script src="/App/XploraEcuador/assets/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.3.2/papaparse.min.js"></script>

    <script>
        var tablePDV = null;

            // Tamaño del lote para enviar al servidor (1000 filas por solicitud)
            const BATCH_SIZE = 1000;
            // Asegúrate de que este sea el archivo que contiene la lógica para manejar 'lote_data'
            const TARGET_URL = 'tb_pdv.php';
            const columnsDefinition = [
            { title: '<input type="checkbox" id="selectAll">', data: 0, orderable: false, searchable: false }, // 0: Checkbox
            { title: 'ID', data: 1 }, // 1: ID
            { title: 'DÍA', data: 2 }, // 2: day
            { title: 'CP CODE', data: 3 }, // 3: cp_code
            { title: 'CUSTOMER CODE', data: 4 }, // 4: customer_code
            { title: 'TRADE', data: 5 }, // 5: trade
            { title: 'RETAIL ENVIROMENT', data: 6 }, // 6: retail_enviroment
            { title: 'RE', data: 7 }, // 7: re
            { title: 'CP FORMAT', data: 8 }, // 8: cp_format
            { title: 'CUSTOMER FORMAT BANNER', data: 9 }, // 9: customer_format_banner
            { title: 'TARGET', data: 10 }, // 10: target
            { title: 'DISTRIBUIDOR', data: 11 }, // 11: distribuidor
            { title: 'CUSTOMER', data: 12 }, // 12: customer
            { title: 'POS', data: 13 }, // 13: pos
            { title: 'RUTA', data: 14 }, // 14: ruta
            { title: 'REGION', data: 15 }, // 15: region
            { title: 'TERRITORY', data: 16 }, // 16: territory
            { title: 'PROVINCE', data: 17 }, // 17: province
            { title: 'CITY', data: 18 }, // 18: city
            { title: 'ZONE', data: 19 }, // 19: zone
            { title: 'ADDRESS', data: 20 }, // 20: address
            { title: 'SUPERVISOR', data: 21 }, // 21: supervisor
            { title: 'MERCHANDISER', data: 22 }, // 22: merchandiser
            { title: 'USER', data: 23 }, // 23: user
            { title: 'COORD. X', data: 24 }, // 24: x
            { title: 'COORD. Y', data: 25 }, // 25: y
            { title: 'SOB', data: 26 }, // 26: sob
            { title: 'VISUAL ACCESS', data: 27 }, // 27: visual_access
            { title: 'FECHA MODIFICACIÓN', data: 28 }, // 28: fecha_modificacion
            { title: 'ACCIONES', data: 29, orderable: false, searchable: false } // 29: Acciones (Botón)
        ];

            // =======================================================
            // 1. CARGA DE TABLA DATATABLES (Server-Side)
            // =======================================================
            function cargarTablaPDV() {
                if (!tablePDV) {
                    $("#data-result").html('<table id="table_pdv" name="table_pdv" class="table table-striped" style="width:100%"><thead></thead><tbody></tbody></table>');
                    $("#loading").css("display", "flex");
                    // *** CAMBIO: PASAR LA DEFINICIÓN DE COLUMNAS ***
                    inicializarDataTablesYEventosPDV(columnsDefinition); 
                } else {
                    // Recargamos la data con los nuevos filtros (sin resetear la paginación)
                    tablePDV.ajax.reload(null, false); 
                }
            }

            // FUNCIÓN DE INICIALIZACIÓN (ACEPTA LA DEFINICIÓN DE COLUMNAS)
            function inicializarDataTablesYEventosPDV(columns) {
                // Si la tabla ya está inicializada, no hagas nada.
                if (tablePDV) {
                    return; 
                }
                
                // Asigna la instancia de DataTables a la variable global tablePDV
                tablePDV = $('#table_pdv').DataTable({
                    // *** Configuraciones Críticas para Server-Side ***
                    "processing": true,
                    "serverSide": true, // ¡CLAVE!
                    "ajax": {
                        // Revisa que la URL sea correcta en tu entorno
                        "url": "getters/get_table_tb_pdv.php", 
                        "type": "POST",
                        "data": function(d) {
                            // Envía los filtros de fecha como parámetros adicionales de DataTables
                            // Revisa que los IDs de los campos de fecha sean correctos
                            d.fecha_inicio = $('#date-start-pdv').val(); 
                            d.fecha_fin = $('#date-end-pdv').val();
                        },
                        "dataSrc": function (json) {
                            // Ocultar el loading manual de la tabla después de la carga
                            $("#loading").css("display", "none");
                            return json.data;
                        },
                        "error": function(xhr, error, thrown) {
                            $("#loading").css("display", "none");
                            console.error("Error al cargar datos:", thrown);
                            alert("Error al cargar los datos de la tabla. Verifique el log del servidor.");
                        }
                    },
                    // *** CONFIGURACIÓN CORREGIDA: Usar la definición pasada ***
                    "columns": columns, // <--- ESTO SOLUCIONA QUE NO SE DIBUJARAN LOS ENCABEZADOS
                    
                    "scrollX": true,
                    lengthMenu: [10, 25, 50, 75, 100],
                    responsive: true,
                    "dom": 'lBfrtip',
                    buttons: [
                        {
                            text: 'Excel',
                            action: function (e, dt, node, config) {
                                exportarTodosLosRegistros();
                            }
                        },
                        'copy', 'csv', 'pdf', 'print'
                    ],
                    "order": [[ 1, "asc" ]], // Ordenar por ID por defecto
                    
                    // Asocia los eventos después de que DataTables haya dibujado la tabla
                    "initComplete": function(settings, json) {
                        // Re-bindear eventos de DataTables (delegados)
                        
                        // Evento Checkbox por Fila
                        $('#table_pdv').off('change', '.select-row').on('change', '.select-row', function() {
                            actualizarContadorSeleccionados();
                        });

                        // Evento Checkbox Principal (Seleccionar Todo)
                        $('#selectAll').off('click').on('click', function() {
                            var isChecked = $(this).prop('checked');
                            // Solo seleccionar/deseleccionar los de la PÁGINA actual
                            tablePDV.rows({ page: 'current' }).nodes().to$().find('.select-row').prop('checked', isChecked);
                            actualizarContadorSeleccionados();
                        });
                        
                        // Botón de edición - LÓGICA AJAX AGREGADA
                        $('#table_pdv').off('click', '.btn-editar-pdv').on('click', '.btn-editar-pdv', function() {
                        const id_registro = $(this).data('id');
                        
                        // Opcional: limpiar el formulario antes de cargar nuevos datos (buena práctica)
                        $('#formEditar')[0].reset(); 
                        
                        console.log(`Intentando cargar registro con ID: ${id_registro}`); 

                        $.ajax({
                            url: 'getters/get_pdv_by_id.php', 
                            type: 'POST',
                            dataType: 'json',
                            data: { id: id_registro },
                            success: function(response) {
                                if (response.status === 'success') {
                                    const data = response.data;
                                    
                                    console.log('Datos recibidos del servidor:', data);
                                    
                                    // 1. LLENADO DEL FORMULARIO - USA GUION BAJO "_"
                                    for (const key in data) {
                                        if (data.hasOwnProperty(key)) {
                                            // CAMBIO CLAVE AQUÍ: Usamos `edit_` + key (guion bajo)
                                            const field_id = `#edit_${key}`; 
                                            const value = data[key];
                                            
                                            const $field = $(field_id);
                                            $field.val(value);
                                            
                                            if ($field.length === 0) {
                                                // Esta advertencia desaparecerá cuando todos los IDs coincidan
                                                console.warn(`[ADVERTENCIA] Campo no encontrado en el modal para la columna: ${key}. ID esperado: ${field_id}`);
                                            } else {
                                                // Este mensaje confirmará que cada campo se llena
                                                console.log(`Campo ${field_id} llenado correctamente.`);
                                            }
                                        }
                                    }
                                    
                                    // 2. Mostrar el modal después de llenarlo
                                    $('#modalEditar').modal('show');
                                    
                                } else {
                                    alert('Error al obtener datos: ' + response.message);
                                    console.error('Error del servidor:', response.message);
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error("Error AJAX al obtener datos:", error, status, xhr.responseText);
                                alert("Ocurrió un error de red o de servidor al obtener el registro. Revise la Consola.");
                            }
                        });
                        
                        return false; 
                    });
                        
                        actualizarContadorSeleccionados();
                    }
                });
            }

            // Función para exportar TODOS los registros
            function exportarTodosLosRegistros() {
                // Mostrar loading
                $("#loading").css("display", "flex");
                
                // Crear un formulario temporal para enviar los filtros
                const form = $('<form>', {
                    method: 'POST',
                    action: 'getters/export_tb_pdv.php',
                    target: '_blank'
                });
                
                // Agregar los mismos filtros que usa DataTables
                form.append($('<input>', {
                    type: 'hidden',
                    name: 'fecha_inicio',
                    value: $('#date-start-pdv').val()
                }));
                
                form.append($('<input>', {
                    type: 'hidden',
                    name: 'fecha_fin',
                    value: $('#date-end-pdv').val()
                }));
                
                // Agregar búsqueda si existe
                const searchValue = tablePDV.search();
                if (searchValue) {
                    form.append($('<input>', {
                        type: 'hidden',
                        name: 'search_value',
                        value: searchValue
                    }));
                }
                
                // Enviar el formulario
                form.appendTo('body').submit().remove();
                
                // Ocultar loading después de un momento
                setTimeout(function() {
                    $("#loading").css("display", "none");
                }, 1000);
            }


            // Función para actualizar el contador de seleccionados
            function actualizarContadorSeleccionados() {
                var seleccionados = $('.select-row:checked').length;
                $('#contadorSeleccionados').text(seleccionados);

                if (seleccionados > 0) {
                    $('#btnEliminarSeleccionados').show();
                } else {
                    $('#btnEliminarSeleccionados').hide();
                }
            }

            // =======================================================
            // 2. FUNCIÓN DE CARGA POR LOTES (Anti-413)
            // =======================================================
            async function iniciarCargaPorLotes(file, fileExtension) {
                const reader = new FileReader();
                
                // --- Estado Inicial ---
                $("#submit").prop('disabled', true).text('Procesando...'); 
                $("#loading").css("display", "flex").text("Iniciando lectura del archivo...");
                console.log(`[DEBUG] Intentando leer archivo: ${file.name} (Ext: ${fileExtension})`);
                
                reader.onload = async function(e) {
                    $("#loading").text("Leyendo datos del archivo en el navegador...");
                    const data = e.target.result;
                    let rows = [];
                    let success = true;

                    // DEBUG: Muestra el tipo de dato que se leyó. Para XLSX debe ser ArrayBuffer.
                    console.log(`[DEBUG] Data leída del archivo: Tipo=${typeof data}, Longitud=${data.byteLength || data.length || 'N/A'}`);

                    try {
                        if (fileExtension === 'csv') {
                            rows = data.split('\n')
                                .map(line => line.split(';'))
                                .filter(line => line.join('').trim() !== '');
                            
                        } else if (fileExtension === 'xlsx' || fileExtension === 'xls') {
                            
                            if (typeof XLSX === 'undefined') {
                                alert("Error: La librería SheetJS (xlsx.full.min.js) es necesaria para archivos Excel.");
                                success = false;
                            } else {
                                try {
                                    const data_array = new Uint8Array(data);
                                    const workbook = XLSX.read(data_array, { type: 'array' });
                                    
                                    // DEBUG: Muestra los nombres de las hojas y la hoja seleccionada
                                    console.log("[DEBUG] Nombres de hojas detectadas:", workbook.SheetNames);
                                    const sheetName = workbook.SheetNames[0];
                                    console.log(`[DEBUG] Parseando la primera hoja: ${sheetName}`);
                                    
                                    rows = XLSX.utils.sheet_to_json(workbook.Sheets[sheetName], { header: 1 });
                                    
                                    // DEBUG: Muestra el resultado de la lectura ANTES de cualquier manipulación
                                    console.log("[DEBUG] Filas parseadas inicialmente (XLSX):", rows);

                                    const COLUMNAS_ESPERADAS = 26; 
                                    
                                    rows = rows.map(row => {
                                        const newRow = Array.isArray(row) ? [...row] : []; 
                                        
                                        while (newRow.length < COLUMNAS_ESPERADAS) {
                                            newRow.push(null);
                                        }
                                        return newRow.slice(0, COLUMNAS_ESPERADAS);
                                    });

                                    rows = rows.filter(row => row.some(cell => cell !== null && cell !== '')); 
                                    
                                    // DEBUG: Muestra el resultado DESPUÉS de normalización y filtro
                                    console.log("[DEBUG] Filas después de normalización y filtro:", rows);
                                    
                                } catch (readError) {
                                    console.error("[ERROR XLSX] Falló la lectura del workbook o la conversión a JSON:", readError);
                                    success = false;
                                }
                            }
                        } else {
                            alert("Formato de archivo no soportado.");
                            success = false;
                        }
                    } catch (error) {
                        alert("Error al parsear el archivo. Asegúrese del formato correcto.");
                        console.error("Error de parseo general:", error);
                        success = false;
                    }

                    if (!success || rows.length === 0) {
                        $("#loading").css("display", "none");
                        $("#submit").prop('disabled', false).text('Import');
                        if (rows.length === 0 && success) { 
                            console.log("[DEBUG] Flujo detenido: El resultado final de 'rows' está vacío.");
                            alert("El archivo está vacío o solo contiene encabezados.");
                        }
                        return;
                    }

                    rows.shift(); 
                    const totalRows = rows.length;
                    let processedRows = 0;

                    if (totalRows === 0) {
                        $("#loading").css("display", "none");
                        $("#submit").prop('disabled', false).text('Import');
                        console.log("[DEBUG] Flujo detenido: Solo quedaba el encabezado.");
                        alert("El archivo solo contiene encabezados. No hay datos para insertar.");
                        return;
                    }
                    
                    // DEBUG: Muestra el número total de filas a procesar
                    console.log(`[DEBUG] Filas de datos listas para AJAX: ${totalRows}`);
                    
                    for (let i = 0; i < totalRows && success; i += BATCH_SIZE) {
                        const batch = rows.slice(i, i + BATCH_SIZE);
                        const batchIndex = Math.floor(i / BATCH_SIZE) + 1;
                        const totalBatches = Math.ceil(totalRows / BATCH_SIZE);
                        
                        $("#loading").text(`Procesando lote ${batchIndex} / ${totalBatches} (${processedRows}/${totalRows} filas)...`);
                        
                        try {
                            // DEBUG: Muestra el contenido del primer lote que se envía
                            if (i === 0) {
                                console.log("[DEBUG] Contenido del primer lote a enviar:", batch);
                            }
                            
                            const response = await fetch(TARGET_URL, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                    'Cache-Control': 'no-cache'
                                },
                                body: `lote_data=${encodeURIComponent(JSON.stringify(batch))}`
                            });

                            const result = await response.json();
                            
                            // DEBUG: Muestra la respuesta del servidor (PHP)
                            if (response.status !== 200 || result.status !== 'success') {
                                console.error(`[ERROR AJAX] Respuesta del lote ${batchIndex}:`, result);
                                success = false;
                                alert(`Error al procesar el lote ${batchIndex}: ${result.message || 'Error desconocido del servidor'}`);
                            } else {
                                processedRows += batch.length;
                            }
                        } catch (error) {
                            success = false;
                            alert(`Error de conexión al enviar el lote ${batchIndex}. Revise la consola.`);
                            console.error("Error de Fetch/AJAX:", error);
                        }
                    }

                    $("#loading").css("display", "none");
                    $("#submit").prop('disabled', false).text('Import');

                    if (success) {
                        alert(`Carga por lotes completada. ${totalRows} registros insertados.`);
                        cargarTablaPDV();
                    } else {
                        alert("Carga cancelada debido a un error. Revise la consola para detalles.");
                    }
                };
                
                if (fileExtension === 'xlsx' || fileExtension === 'xls') {
                    reader.readAsArrayBuffer(file);
                } else {
                    reader.readAsText(file);
                }
            }


            // =======================================================
            // 3. EVENTOS PRINCIPALES DE DOCUMENT.READY (Fusionado)
            // =======================================================
            $(document).ready(function() {
                // Carga inicial de la tabla
                cargarTablaPDV();

                // Filtros de fecha (recarga la tabla al cambiar)
                $('#date-start-pdv, #date-end-pdv').on('change input', function() {
                    if (tablePDV) {
                        tablePDV.ajax.reload();
                    } else {
                        cargarTablaPDV();
                    }
                });

                // botón limpiar
                $('#clear-filter-pdv').on('click', function() {
                    $('#date-start-pdv').val('').trigger('input').trigger('change');
                    $('#date-end-pdv').val('').trigger('input').trigger('change');
                    if (tablePDV) tablePDV.ajax.reload();
                });
                
                // --- EVENTO DE SUBIDA DE ARCHIVO POR LOTES (Anti-413) ---
                // CORRECCIÓN 1: Usar el ID correcto del formulario: #frmImport
                $('#frmImport').on('submit', function(e) {
                    e.preventDefault(); // ¡CLAVE! Detiene el envío de formulario HTML tradicional (413)
                    
                    const fileInput = $('#file')[0]; 
                    if (fileInput.files.length === 0) {
                        alert("Seleccione un archivo primero.");
                        return;
                    }

                    const file = fileInput.files[0];
                    const fileName = file.name;
                    const fileExtension = fileName.split('.').pop().toLowerCase();

                    if (fileExtension === 'csv' || fileExtension === 'xlsx' || fileExtension === 'xls') {
                        iniciarCargaPorLotes(file, fileExtension);
                    } else {
                        alert("Formato de archivo no soportado. Use CSV, XLSX, o XLS.");
                    }
                });

                // --- ELIMINAR REGISTROS SELECCIONADOS (Se mantiene tu lógica) ---
                $('#btnEliminarSeleccionados').on('click', function() {
                    var idsSeleccionados = [];
                    $('.select-row:checked').each(function() {
                        // RECUERDA: En Server-Side, el `value` de este checkbox
                        // debe contener el ID del registro para que la eliminación funcione.
                        idsSeleccionados.push($(this).val());
                    });

                    if (idsSeleccionados.length === 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Atención',
                            text: 'Por favor, seleccione al menos un registro para eliminar.'
                        });
                        return;
                    }

                    Swal.fire({
                        title: '¿Está seguro?',
                        html: 'Está a punto de eliminar <strong>' + idsSeleccionados.length + '</strong> registro(s) PDV.<br>Esta acción no se puede deshacer.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $("#loading").css("display", "flex");

                            $.ajax({
                                type: "POST",
                                url: "actions/delete_pdv.php", 
                                data: { ids: idsSeleccionados },
                                dataType: 'json',
                                success: function(result) {
                                    $("#loading").css("display", "none");

                                    if (result.success) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: '¡Eliminado!',
                                            text: result.message,
                                            timer: 2000,
                                            showConfirmButton: false
                                        });
                                        cargarTablaPDV();
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error',
                                            text: result.message || 'Error al eliminar los registros PDV.'
                                        });
                                    }
                                },
                                error: function(jqXHR, textStatus, errorThrown) {
                                    $("#loading").css("display", "none");
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error de Conexión',
                                        text: 'No se pudo contactar al servidor: ' + textStatus
                                    });
                                }
                            });
                        }
                    });
                });


                // --- GUARDAR NUEVO PDV (Se mantiene tu lógica) ---
                $('#btnGuardarNuevoPDV').on('click', function() {
                    
                    // 1. Obtener los datos usando .serialize()
                    const formData = $('#formAgregar').serialize(); 
                    
                    // Si necesitas validar campos ANTES de enviar:
                    const customer_code = $('#add_customer_code').val(); // Reemplaza 'add_customer_code' con el ID real de tu campo de nuevo registro
                    const address = $('#add_address').val();             // Reemplaza 'add_address' con el ID real de tu campo de nuevo registro
                    
                    // 2. Validación de campos obligatorios (usando los IDs del formulario de Agregar)
                    if (customer_code.trim() === '' || address.trim() === '') {
                        Swal.fire({ 
                            icon: 'warning', 
                            title: 'Atención', 
                            text: 'Por favor, complete los campos Código de Cliente y Dirección.' 
                        });
                        return;
                    }
                    
                    // Deshabilitar botón y mostrar loading
                    $(this).prop('disabled', true).text('Guardando...');
                    $("#loading").css("display", "flex");
                    
                    $.ajax({
                        type: "POST",
                        url: "actions/insert_pdv.php", 
                        data: formData, // Se envía la cadena serializada
                        dataType: 'json',
                        success: function(result) {
                            $("#loading").css("display", "none");
                            if (result.success) {
                                Swal.fire({ 
                                    icon: 'success', 
                                    title: '¡Éxito!', 
                                    text: result.message, 
                                    timer: 2000, 
                                    showConfirmButton: false 
                                });
                                $('#modalAgregar').modal('hide');
                                $('#formAgregar')[0].reset(); // Limpiar el formulario
                                
                                // Recargar tabla (o la función que usas para DataTables)
                                if (typeof tablePDV !== 'undefined') {
                                    tablePDV.ajax.reload(null, false);
                                } else if (typeof cargarTablaPDV === 'function') {
                                    cargarTablaPDV();
                                }

                            } else {
                                Swal.fire({ 
                                    icon: 'error', 
                                    title: 'Error', 
                                    text: result.message || 'Error desconocido al insertar el registro PDV.' 
                                });
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            $("#loading").css("display", "none");
                            Swal.fire({ 
                                icon: 'error', 
                                title: 'Error de Conexión', 
                                text: 'No se pudo contactar al servidor: ' + textStatus + ' - ' + errorThrown + ' Consulta la consola (F12).' 
                            });
                            $(this).prop('disabled', false).text('Guardar'); // Volver a habilitar en caso de error de red
                        },
                        complete: function() {
                            // Volver a habilitar el botón y restaurar el texto
                            $('#btnGuardarNuevoPDV').prop('disabled', false).text('Guardar'); 
                        }
                    });
                });

                // --- GUARDAR CAMBIOS PDV (Se mantiene tu lógica) ---
                $('#btnGuardarCambiosPDV').off('click').on('click', function() {
                    
                    const urlDestino = "actions/update_pdv.php";
                    
                    // 1. OBTENER LOS DATOS: Serializa el formulario para incluir TODOS los campos (name="edit_...")
                    const formData = $('#formEditar').serialize();
                    
                    // 2. VALIDACIÓN LIGERA (opcional, si el ID es el único crítico en JS)
                    const id = $('#edit_id').val();
                    if (!id || id <= 0) {
                        Swal.fire({ icon: 'warning', title: 'Atención', text: 'Error en la identificación del registro (ID no presente).' });
                        return;
                    }

                    // Deshabilitar botón y mostrar loading
                    $(this).prop('disabled', true).text('Guardando...');
                    $("#loading").css("display", "flex");
                    
                    $.ajax({
                        type: "POST",
                        url: urlDestino, 
                        data: formData, // Se envía la cadena serializada
                        dataType: 'json',
                        success: function(result) {
                            $("#loading").css("display", "none");
                            if (result.success) {
                                Swal.fire({ icon: 'success', title: '¡Éxito!', text: 'Registro PDV actualizado correctamente', timer: 2000, showConfirmButton: false });
                                $('#modalEditar').modal('hide');
                                // Recargar tabla (o la función que usas para DataTables)
                                if (typeof tablePDV !== 'undefined') {
                                    tablePDV.ajax.reload(null, false);
                                } else if (typeof cargarTablaPDV === 'function') {
                                    cargarTablaPDV();
                                }
                            } else {
                                // Muestra el mensaje de error del PHP (ej: "ID invalido" o "Error al ejecutar la consulta")
                                Swal.fire({ icon: 'error', title: 'Error', text: result.message || 'Error al actualizar el registro PDV.' });
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            $("#loading").css("display", "none");
                            let errorMessage = 'Error en la conexión. ';
                            if (textStatus === 'error' && errorThrown === 'Not Found') {
                                errorMessage = 'ERROR 404: Archivo ' + urlDestino + ' no encontrado.';
                            } else if (jqXHR.responseText && jqXHR.responseText.includes('Fatal error')) {
                                errorMessage = 'ERROR 500: Fallo de PHP en el servidor (Fatal Error).';
                            } else {
                                errorMessage += 'Detalles: ' + errorThrown;
                                console.log(jqXHR.responseText); // Deja el log de la respuesta completa del servidor
                            }
                            Swal.fire({ icon: 'error', title: 'Error de Conexión/Servidor', html: errorMessage + '<br>Consulta la consola (F12) para más detalles técnicos.' });
                        },
                        complete: function() {
                            // Volver a habilitar el botón y restaurar el texto
                            $('#btnGuardarCambiosPDV').prop('disabled', false).text('Guardar Cambios');
                        }
                    });
                });
            });
        $('#btnConfirmDeleteExcel').on('click', function(e) {
                e.preventDefault();
                
                var fileInput = $('#file_delete')[0];
                if (fileInput.files.length === 0) {
                    Swal.fire('Error', 'Por favor, selecciona un archivo.', 'error');
                    return;
                }

                var file = fileInput.files[0];
                var extension = file.name.split('.').pop().toLowerCase();

                Swal.fire({
                    title: '¿Confirmar Eliminación Masiva?',
                    text: "Esta acción marcará como eliminados los registros del archivo.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Sí, Proceder',
                    cancelButtonText: 'Cancelar'
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        
                        // Validación de tamaño
                        if (file.size > 10 * 1024 * 1024 && extension !== 'csv') { 
                            const res = await Swal.fire({
                                title: 'Archivo muy grande',
                                text: "Para archivos con muchos registros, recomendamos usar .CSV.",
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: 'Intentar de todos modos',
                                cancelButtonText: 'Cancelar'
                            });
                            if(!res.isConfirmed) return;
                        }

                        // Mostrar barra y desactivar botón
                        $('#progreso-contenedor').show();
                        $('#btnConfirmDeleteExcel').prop('disabled', true);

                        // Llamar a las funciones que ya tienes
                        if (extension === 'csv') {
                            procesarCSVDelete(file);
                        } else {
                            procesarExcelDelete(file);
                        }
                    }
                });
            });

            // 1. LA FUNCIÓN QUE ENVÍA LOS DATOS (Mantenemos tu nombre enviarIdsEnLotes)
            async function enviarIdsEnLotes(ids) {
                const tamañoLote = 1000;
                const total = ids.length;
                let procesados = 0;

                for (let i = 0; i < total; i += tamañoLote) {
                    let lote = ids.slice(i, i + tamañoLote);
                    
                    try {
                        let respuesta = await $.ajax({
                            type: "POST",
                            url: "actions/delete_pdv.php",
                            data: { ids: lote },
                            dataType: 'json'
                        });

                        if (respuesta.success) {
                            procesados += lote.length;
                            let porcentaje = Math.round((procesados / total) * 100);
                            
                            // Actualizamos la barra con el diseño que te gustó
                            $('#barra-progreso').css('width', porcentaje + '%').text(porcentaje + '%');
                            $('#progreso-texto').text(`Procesando: ${porcentaje}% (${procesados.toLocaleString()} de ${total.toLocaleString()})`);
                        }
                    } catch (error) {
                        console.error("Error en lote:", error);
                    }
                }

                // Finalización
                Swal.fire({
                    icon: 'success',
                    title: '¡Eliminación Completada!',
                    text: `Se procesaron exitosamente ${procesados.toLocaleString()} registros.`,
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    location.reload();
                });
            }

            // 2. LECTOR CSV (Corregido para llamar a enviarIdsEnLotes)
            function procesarCSVDelete(file) {
                Papa.parse(file, {
                    skipEmptyLines: true,
                    complete: function(results) {
                        let ids = results.data.slice(1)
                                    .map(row => row[0])
                                    .filter(id => id && !isNaN(id));
                        enviarIdsEnLotes(ids); // <-- Ahora llama al nombre correcto
                    }
                });
            }

            // 3. LECTOR EXCEL (Mantenemos tu nombre procesarExcelDelete)
            function procesarExcelDelete(file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var data = new Uint8Array(e.target.result);
                    var workbook = XLSX.read(data, { 
                        type: 'array',
                        sheets: [0] 
                    });
                    
                    var worksheet = workbook.Sheets[workbook.SheetNames[0]];
                    var json = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
                    
                    let ids = json.slice(1) 
                                .map(row => row[0])
                                .filter(id => id && !isNaN(id));
                    
                    enviarIdsEnLotes(ids); // <-- Ahora llama al nombre correcto
                };
                reader.readAsArrayBuffer(file);
            }
    </script>
</body>

</html>