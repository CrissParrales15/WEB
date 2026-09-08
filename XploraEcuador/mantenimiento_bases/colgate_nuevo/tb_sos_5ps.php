<?php

use Phppot\DataSource;

require_once 'DataSource.php';
$db = new DataSource();
$conn = $db->getConnection();

// ⭐ ASEGURAR UTF-8 en la conexión MySQL
if ($conn instanceof mysqli) {
    $conn->set_charset("utf8mb4");
} else {
    $conn->exec("SET NAMES utf8mb4");
}

// ⭐⭐⭐ NUEVO: PUNTO DE ENTRADA PARA PROCESAMIENTO AJAX POR LOTES ⭐⭐⭐
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

// ⭐ IMPORTACIÓN TRADICIONAL (Mantener para compatibilidad, pero no se usará para archivos grandes)
if (isset($_POST["import"])) {

    $fileName = $_FILES["file"]["tmp_name"];
    $fileExtension = strtolower(pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION));

    if ($_FILES["file"]["size"] > 0) {

        $tamano_lote = 500;
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

                // ⭐ Conversión UTF-8
                $column = array_map(function($value) {
                    if ($value === null || $value === "") return $value;
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

                    // ⭐ Asegurar UTF-8 también para Excel
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

// ⭐ BORRADO MASIVO POR EXCEL
if (isset($_POST["delete_by_excel"])) {
    $fileName = $_FILES["file_delete"]["tmp_name"];
    $fileExtension = strtolower(pathinfo($_FILES["file_delete"]["name"], PATHINFO_EXTENSION));
    
    $idsParaEliminar = array();
    $totalProcesados = 0;
    $exitoGlobal = true;
    $batch_size = 500;

    if ($_FILES["file_delete"]["size"] > 0) {
        if ($fileExtension === 'csv') {
            $file = fopen($fileName, "r");
            $bom = fread($file, 3);
            if ($bom != "\xEF\xBB\xBF") rewind($file);
            
            $primeraFila = true;
            while (($column = fgetcsv($file, 1000, ";")) !== FALSE) {
                if ($primeraFila) { $primeraFila = false; continue; }

                if (isset($column[0]) && is_numeric(trim($column[0]))) {
                    $idsParaEliminar[] = intval(trim($column[0]));
                }
                
                if (count($idsParaEliminar) >= $batch_size) {
                    $idsUnicos = array_unique($idsParaEliminar);
                    if (!softDeleteSos5ps($db, $idsUnicos)) { 
                        $exitoGlobal = false; 
                        break; 
                    }
                    $totalProcesados += count($idsUnicos);
                    $idsParaEliminar = array();
                }
            }
            fclose($file);
            
        } elseif ($fileExtension === 'xlsx' || $fileExtension === 'xls') {
            require_once 'SimpleXLSX.php';
            if ($xlsx = SimpleXLSX::parse($fileName)) {
                $rows = $xlsx->rows();
                $firstRow = true;
                
                foreach ($rows as $row) {
                    if ($firstRow) { $firstRow = false; continue; }
                    
                    if (isset($row[0]) && is_numeric(trim($row[0]))) {
                        $idsParaEliminar[] = intval(trim($row[0]));
                    }
                    
                    if (count($idsParaEliminar) >= $batch_size) {
                        $idsUnicos = array_unique($idsParaEliminar);
                        if (!softDeleteSos5ps($db, $idsUnicos)) { 
                            $exitoGlobal = false; 
                            break; 
                        }
                        $totalProcesados += count($idsUnicos);
                        $idsParaEliminar = array();
                    }
                }
            } else {
                $type = "error";
                $message = "Error al leer el archivo: " . SimpleXLSX::parseError();
                $exitoGlobal = false;
            }
        }

        // Procesar remanente
        if ($exitoGlobal && count($idsParaEliminar) > 0) {
            $idsUnicos = array_unique($idsParaEliminar);
            if (!softDeleteSos5ps($db, $idsUnicos)) {
                $exitoGlobal = false;
            } else {
                $totalProcesados += count($idsUnicos);
            }
        }

        if ($exitoGlobal && $totalProcesados > 0) {
            $type = "success";
            $message = "Borrado completado correctamente. Se procesaron $totalProcesados IDs únicos.";
        } elseif ($totalProcesados > 0) {
            $type = "warning";
            $message = "Proceso interrumpido. Se lograron procesar $totalProcesados IDs antes del error.";
        } else {
            $type = "error";
            $message = "Ocurrió un error durante el borrado masivo.";
        }
    }

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
    $message = "El borrado masivo se completó correctamente. Se actualizaron $count registros.";
} elseif (isset($_GET['delete_status']) && ($_GET['delete_status'] === 'error' || $_GET['delete_status'] === 'warning')) {
    $type = $_GET['delete_status'];
    $message = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : 'Ocurrió un error al intentar la eliminación masiva.';
}

// ========================================
// FUNCIONES AUXILIARES
// ========================================

function convertirFecha($valor) {
    if ($valor === null || $valor === "") return null;

    if (is_numeric($valor)) {
        $unix = ($valor - 25569) * 86400;
        return date("Y-m-d", $unix);
    }

    $valor = str_replace('/', '-', $valor);
    $ts = strtotime($valor);
    return ($ts) ? date("Y-m-d", $ts) : null;
}

function convertirHora($valor) {
    if ($valor === null || $valor === "") return null;

    if (is_numeric($valor)) {
        $segundos = $valor * 86400;
        return gmdate("H:i:s", $segundos);
    }

    return $valor;
}

function convertirFechaHora($valor) {
    if ($valor === null || $valor === "") return null;

    if (is_numeric($valor)) {
        $unix = ($valor - 25569) * 86400;
        return date("Y-m-d H:i:s", $unix);
    }

    $valor = str_replace('/', '-', $valor);
    $ts = strtotime($valor);
    return ($ts) ? date("Y-m-d H:i:s", $ts) : null;
}

function procesarLote($db, $lote, $conn)
{
    // ⭐ Asegurar UTF-8 antes de insertar
    if ($conn instanceof mysqli) {
        $conn->set_charset("utf8mb4");
    }
    
    $sqlInsert = "INSERT INTO tb_sos_5ps 
    (day, time, cp_code, manufacturer, subcategory, total_cms_hooks, cms_individual_hooks, sos, server_date) 
    VALUES ";

    $paramType = "";
    $paramArray = array();
    $registrosValidos = 0;

    foreach ($lote as $index => $fila) {
        // Validación: si toda la fila viene vacía, saltar
        $hayDato = false;
        for ($i = 0; $i <= 8; $i++) {
            if (isset($fila[$i]) && trim($fila[$i]) !== "" && strtoupper(trim($fila[$i])) !== "NULL") {
                $hayDato = true;
                break;
            }
        }
        if (!$hayDato) continue;

        $sqlInsert .= "(?, ?, ?, ?, ?, ?, ?, ?, ?),";
        $paramType .= "sssssiids"; 

        // 0 - DAY (Fecha)
        $day = convertirFecha(isset($fila[0]) ? trim($fila[0]) : null);
        $paramArray[] = $day;

        // 1 - TIME (HH:MI:SS)
        $time = convertirHora(isset($fila[1]) ? trim($fila[1]) : null);
        $paramArray[] = $time;

        // 2 - cp_code
        $paramArray[] = isset($fila[2]) ? trim($fila[2]) : "";

        // 3 - manufacturer
        $paramArray[] = isset($fila[3]) ? trim($fila[3]) : "";

        // 4 - subcategory
        $paramArray[] = isset($fila[4]) ? trim($fila[4]) : "";

        // 5 - total_cms_hooks (int)
        $paramArray[] = isset($fila[5]) ? intval(trim($fila[5])) : 0;

        // 6 - cms_individual_hooks (int)
        $paramArray[] = isset($fila[6]) ? intval(trim($fila[6])) : 0;

        // 7 - sos (float)
        $sos = isset($fila[7]) ? floatval(str_replace(",", ".", trim($fila[7]))) : 0.00;
        $paramArray[] = $sos;

        // 8 - server_date (DATETIME)
        $server_date = convertirFechaHora(isset($fila[8]) ? trim($fila[8]) : null);
        $paramArray[] = $server_date;

        $registrosValidos++;
    }

    if ($registrosValidos === 0) {
        return false;
    }

    $sqlInsert = rtrim($sqlInsert, ',');
    $insertId = $db->insertMultiple($sqlInsert, $paramType, $paramArray);

    return !empty($insertId) || $insertId !== false;
}

function softDeleteSos5ps($db, $ids)
{
    if (empty($ids)) {
        return false;
    }
    
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $paramType = str_repeat('i', count($ids));
    $query = "UPDATE tb_sos_5ps SET status = 0 WHERE id IN ($placeholders)";
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
            <h3>Gestión de SOS / 5Ps</h3>
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
        <form class="form-horizontal" id="formImportar" enctype="multipart/form-data">
            <div class="input-row">
                <label class="col-md-4 control-label">Seleccionar archivo</label> 
                <input type="file" name="file" id="file" accept=".csv,.xlsx,.xls">
                <button type="submit" id="submit" class="btn-submit">Import</button>
                <br />
                <small style="color: #666;">Formatos aceptados: CSV</small>
            </div>
        </form>
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Eliminación Masiva por ID (SOS 5Ps)</h5>
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
                                role="progressbar" style="width: 0%; font-weight: bold;">0%</div>
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

                <input type="date" class="form-control" id="date-start-sos">
                <div class="input-group-prepend">
                    <span class="input-group-text">Hasta:</span>
                </div>
                <input type="date" class="form-control" id="date-end-sos">

                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="button" id="clear-filter-sos" title="Limpiar Filtro">
                        <i class="material-icons" style="font-size: 1.2rem;">close</i>
                    </button>
                </div>
            </div>
        </div>

        <div class="col-xl-12 col-lg-12 mb-4">
            <button type="button" class="btn btn-success mb-3" data-toggle="modal" data-target="#modalAgregar">
                <i class="material-icons" style="font-size: 1.2rem;">add</i> Agregar Nuevo Registro SOS
            </button>
            <button type="button" class="btn btn-danger mb-3 ml-2" id="btnEliminarSeleccionados" style="display:none;">
                <i class="material-icons" style="font-size: 1.2rem;">delete</i> Eliminar Seleccionados (<span id="contadorSeleccionados">0</span>)
            </button>
            
            <!-- ⭐ TABLA DIRECTAMENTE EN EL HTML (No dentro de un div para cargar con AJAX) -->
            <div class="bg-white rounded-lg p-5 shadow">
                <table id="table" class="table table-striped" style="width:100%">
                    <thead class="thead-light">
                        <tr>
                            <th scope="col"><input type="checkbox" id="selectAll"></th>
                            <th scope="col">ID</th>
                            <th scope="col">DAY</th>
                            <th scope="col">TIME</th>
                            <th scope="col">CP_CODE</th>
                            <th scope="col">MANUFACTURER</th>
                            <th scope="col">SUBCATEGORY</th>
                            <th scope="col">TOTAL CMS HOOKS</th>
                            <th scope="col">CMS INDIVIDUAL HOOKS</th>
                            <th scope="col">SOS</th>
                            <th scope="col">SERVER DATE</th>
                            <th scope="col">FECHA MODIFICACIÓN</th>
                            <th scope="col">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- ⭐ El contenido se carga automáticamente vía AJAX Server-Side -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

    <div class="modal fade" id="modalEditar" tabindex="-1" role="dialog" aria-labelledby="modalEditarLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarLabel">Editar Registro SOS / 5Ps</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formEditar">
                        <input type="hidden" id="edit_id" name="edit_id">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_day">Day (Día)</label>
                                    <input type="date" class="form-control" id="edit_day" name="edit_day"> 
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_time">Time (Hora)</label>
                                    <input type="time" class="form-control" id="edit_time" name="edit_time" step="1"> 
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_cp_code">CP Code</label>
                                    <input type="text" class="form-control" id="edit_cp_code" name="edit_cp_code">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_manufacturer">Manufacturer (Fabricante)</label>
                                    <input type="text" class="form-control" id="edit_manufacturer" name="edit_manufacturer">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_subcategory">Subcategory (Subcategoría)</label>
                                    <input type="text" class="form-control" id="edit_subcategory" name="edit_subcategory">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_total_cms_hooks">Total CMS Hooks</label>
                                    <input type="number" class="form-control" id="edit_total_cms_hooks" name="edit_total_cms_hooks">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_cms_individual_hooks">CMS Individual Hooks</label>
                                    <input type="number" class="form-control" id="edit_cms_individual_hooks" name="edit_cms_individual_hooks">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_sos">SOS (15,2)</label>
                                    <input type="number" step="0.01" class="form-control" id="edit_sos" name="edit_sos"> 
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_server_date">Server Date (DATETIME)</label>
                            <input type="datetime-local" class="form-control" id="edit_server_date" name="edit_server_date" step="1">
                        </div>

                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnGuardarCambios">Guardar Cambios</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalAgregar" tabindex="-1" role="dialog" aria-labelledby="modalAgregarLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAgregarLabel">Agregar Nuevo Registro SOS / 5Ps</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formAgregar">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="add_day">Day (Día)</label>
                                    <input type="date" class="form-control" id="add_day" name="add_day" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="add_time">Time (Hora)</label>
                                    <input type="time" class="form-control" id="add_time" name="add_time" step="1" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="add_cp_code">CP Code</label>
                                    <input type="text" class="form-control" id="add_cp_code" name="add_cp_code" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="add_manufacturer">Manufacturer (Fabricante)</label>
                                    <input type="text" class="form-control" id="add_manufacturer" name="add_manufacturer" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="add_subcategory">Subcategory (Subcategoría)</label>
                                    <input type="text" class="form-control" id="add_subcategory" name="add_subcategory" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="add_total_cms_hooks">Total CMS Hooks</label>
                                    <input type="number" class="form-control" id="add_total_cms_hooks" name="add_total_cms_hooks" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="add_cms_individual_hooks">CMS Individual Hooks</label>
                                    <input type="number" class="form-control" id="add_cms_individual_hooks" name="add_cms_individual_hooks" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="add_sos">SOS (15,2)</label>
                                    <input type="number" step="0.01" class="form-control" id="add_sos" name="add_sos" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="add_server_date">Server Date (DATETIME)</label>
                            <input type="datetime-local" class="form-control" id="add_server_date" name="add_server_date" step="1" required>
                        </div>

                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="btnGuardarNuevo">Guardar Registro</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Solo una versión de jQuery -->
    <script src="/App/XploraEcuador/assets/js/popper-1.12.9.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="/App/XploraEcuador/assets/js/bootstrap-4.0.0.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>

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
        const BATCH_SIZE_SOS = 1000; 
        const TARGET_URL_SOS = 'tb_sos_5ps.php'; // Archivo PHP que procesa los lotes

        $(document).ready(function() {
            var tableSOS = null;

            // ⭐ INICIALIZAR DATATABLES CON SERVER-SIDE PROCESSING
            inicializarDataTablesSOS();

            // Aplicar filtros cuando cambien las fechas
            $('#date-start-sos, #date-end-sos').on('change input', function() {
                if (tableSOS) {
                    tableSOS.ajax.reload();
                }
            });

            // Limpiar filtros
            $('#clear-filter-sos').on('click', function() {
                $('#date-start-sos').val('');
                $('#date-end-sos').val('');
                
                if (tableSOS) {
                    tableSOS.ajax.reload();
                }
            });


            $('#formImportar').on('submit', function(e) {
                e.preventDefault(); // Prevenir submit tradicional
                
                const fileInput = $('#file')[0];
                if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Atención',
                        text: 'Por favor, seleccione un archivo para importar.'
                    });
                    return false;
                }

                const file = fileInput.files[0];
                const fileName = file.name;
                const fileExtension = fileName.split('.').pop().toLowerCase();

                // Validar extensión
                if (!['csv', 'xlsx', 'xls'].includes(fileExtension)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Formato Inválido',
                        text: 'Solo se permiten archivos CSV.'
                    });
                    return false;
                }

                console.log(`[INICIO] Procesando archivo: ${fileName} (${fileExtension})`);
                
                // Iniciar la carga por lotes
                iniciarCargaPorLotesSOS(file, fileExtension);
                
                return false;
            });

            // Inicializar DataTables con Server-Side
            function inicializarDataTablesSOS() {
                if (tableSOS) {
                    tableSOS.destroy();
                    tableSOS = null;
                }

                tableSOS = $('#table').DataTable({
                    "processing": true,
                    "serverSide": true,
                    "ajax": {
                        "url": "getters/get_table_tb_sos_5ps.php",
                        "type": "POST",
                        "data": function(d) {
                            d.fecha_inicio = $('#date-start-sos').val();
                            d.fecha_fin = $('#date-end-sos').val();
                        },
                        "dataSrc": function (json) {
                            $("#loading").css("display", "none");
                            return json.data;
                        },
                        "error": function(xhr, error, thrown) {
                            $("#loading").css("display", "none");
                            console.error("Error al cargar datos:", thrown);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Error al cargar los datos de la tabla.'
                            });
                        }
                    },
                    "columns": [
                        { "data": 0, "orderable": false },
                        { "data": 1 },
                        { "data": 2 },
                        { "data": 3 },
                        { "data": 4 },
                        { "data": 5 },
                        { "data": 6 },
                        { "data": 7 },
                        { "data": 8 },
                        { "data": 9 },
                        { "data": 10 },
                        { "data": 11 },
                        { "data": 12, "orderable": false }
                    ],
                    "scrollX": true,
                    "lengthMenu": [10, 25, 50, 75, 100],
                    "responsive": true,
                    "dom": 'lBfrtip',
                    "buttons": [
                        {
                            text: 'Excel (Todos los registros)',
                            action: function (e, dt, node, config) {
                                exportarTodosLosRegistrosSOS();
                            }
                        },
                        'copy', 'csv', 'pdf', 'print'
                    ],
                    "order": [[ 1, "desc" ]],
                    "initComplete": function(settings, json) {
                        console.log("DataTables inicializado correctamente");
                        
                        $('#table').off('change', '.select-row').on('change', '.select-row', function() {
                            actualizarContadorSeleccionados();
                            var total = $('.select-row').length;
                            var checked = $('.select-row:checked').length;
                            $('#selectAll').prop('checked', total === checked && total > 0);
                        });

                        $('#selectAll').off('click').on('click', function() {
                            var isChecked = $(this).prop('checked');
                            tableSOS.rows({ page: 'current' }).nodes().to$().find('.select-row').prop('checked', isChecked);
                            actualizarContadorSeleccionados();
                        });
                        
                        $('#table').off('click', '.btn-editar-sos').on('click', '.btn-editar-sos', function() {
                            const id_registro = $(this).data('id');
                            console.log(`Cargando registro con ID: ${id_registro}`);
                            $('#formEditar')[0].reset();
                            
                            $.ajax({
                                url: 'getters/get_sos_by_id.php',
                                type: 'POST',
                                dataType: 'json',
                                data: { id: id_registro },
                                success: function(response) {
                                    if (response.status === 'success') {
                                        const data = response.data;
                                        console.log('Datos recibidos:', data);
                                        
                                        $('#edit_id').val(data.id);
                                        $('#edit_day').val(data.day);
                                        $('#edit_time').val(data.time);
                                        $('#edit_cp_code').val(data.cp_code);
                                        $('#edit_manufacturer').val(data.manufacturer);
                                        $('#edit_subcategory').val(data.subcategory);
                                        $('#edit_total_cms_hooks').val(data.total_cms_hooks);
                                        $('#edit_cms_individual_hooks').val(data.cms_individual_hooks);
                                        $('#edit_sos').val(data.sos);
                                        
                                        let serverDateFormatted = data.server_date ? data.server_date.replace(' ', 'T') : '';
                                        $('#edit_server_date').val(serverDateFormatted);
                                        
                                        $('#modalEditar').modal('show');
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error',
                                            text: 'Error al obtener datos: ' + response.message
                                        });
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.error("Error AJAX al obtener datos:", error);
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: 'Error al obtener el registro.'
                                    });
                                }
                            });
                            
                            return false;
                        });
                        
                        actualizarContadorSeleccionados();
                    }
                });
            }

            async function iniciarCargaPorLotesSOS(file, fileExtension) {
                const reader = new FileReader();
                
                $("#submit").prop('disabled', true).text('Procesando...'); 
                $("#loading").css("display", "flex");
                $("#loading-text").text("Iniciando lectura del archivo...");
                
                console.log(`[DEBUG] Intentando leer archivo: ${file.name} (Ext: ${fileExtension})`);
                
                reader.onload = async function(e) {
                    $("#loading-text").text("Leyendo datos del archivo en el navegador...");
                    const data = e.target.result;
                    let rows = [];
                    let success = true;

                    console.log(`[DEBUG] Data leída del archivo: Tipo=${typeof data}, Longitud=${data.byteLength || data.length || 'N/A'}`);

                    try {
                        if (fileExtension === 'csv') {
                            rows = data.split('\n')
                                .map(line => line.split(';'))
                                .filter(line => line.join('').trim() !== '');
                            
                        } else if (fileExtension === 'xlsx' || fileExtension === 'xls') {
                            if (typeof XLSX === 'undefined') {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'La librería SheetJS (xlsx.full.min.js) es necesaria para archivos Excel.'
                                });
                                success = false;
                            } else {
                                try {
                                    const data_array = new Uint8Array(data);
                                    const workbook = XLSX.read(data_array, { type: 'array' });
                                    
                                    console.log("[DEBUG] Nombres de hojas detectadas:", workbook.SheetNames);
                                    const sheetName = workbook.SheetNames[0];
                                    console.log(`[DEBUG] Parseando la primera hoja: ${sheetName}`);
                                    
                                    rows = XLSX.utils.sheet_to_json(workbook.Sheets[sheetName], { header: 1 });
                                    console.log("[DEBUG] Filas parseadas inicialmente (XLSX):", rows);

                                    // Normalizar a 9 columnas (0-8)
                                    const COLUMNAS_ESPERADAS = 9; 
                                    rows = rows.map(row => {
                                        const newRow = Array.isArray(row) ? [...row] : []; 
                                        while (newRow.length < COLUMNAS_ESPERADAS) {
                                            newRow.push(null);
                                        }
                                        return newRow.slice(0, COLUMNAS_ESPERADAS);
                                    });

                                    rows = rows.filter(row => row.some(cell => cell !== null && cell !== '')); 
                                    console.log("[DEBUG] Filas después de normalización y filtro:", rows);
                                    
                                } catch (readError) {
                                    console.error("[ERROR XLSX] Falló la lectura del workbook:", readError);
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error al leer Excel',
                                        text: 'No se pudo procesar el archivo Excel. Verifique el formato.'
                                    });
                                    success = false;
                                }
                            }
                        }

                        if (!success) {
                            $("#loading").css("display", "none");
                            $("#submit").prop('disabled', false).text('Importar');
                            return;
                        }

                        // Remover encabezado
                        if (rows.length > 0) {
                            rows.shift();
                        }

                        const totalRows = rows.length;
                        console.log(`[INFO] Total de filas a procesar: ${totalRows}`);

                        if (totalRows === 0) {
                            $("#loading").css("display", "none");
                            $("#submit").prop('disabled', false).text('Importar');
                            Swal.fire({
                                icon: 'warning',
                                title: 'Archivo Vacío',
                                text: 'No se encontraron datos para importar.'
                            });
                            return;
                        }

                        // Dividir en lotes
                        let currentIndex = 0;
                        let errorCount = 0;
                        const totalBatches = Math.ceil(totalRows / BATCH_SIZE_SOS);

                        while (currentIndex < totalRows) {
                            const batch = rows.slice(currentIndex, currentIndex + BATCH_SIZE_SOS);
                            const batchNumber = Math.floor(currentIndex / BATCH_SIZE_SOS) + 1;
                            
                            $("#loading-text").text(`Procesando lote ${batchNumber} de ${totalBatches}...`);
                            console.log(`[AJAX] Enviando lote ${batchNumber}/${totalBatches} (${batch.length} filas)`);

                            try {
                                const response = await fetch(TARGET_URL_SOS, {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                    body: 'lote_data=' + encodeURIComponent(JSON.stringify(batch))
                                });

                                const result = await response.json();
                                
                                if (result.status !== 'success') {
                                    console.error(`[ERROR] Lote ${batchNumber} falló:`, result.message);
                                    errorCount++;
                                } else {
                                    console.log(`[OK] Lote ${batchNumber} procesado correctamente`);
                                }

                            } catch (error) {
                                console.error(`[ERROR] Error de red en lote ${batchNumber}:`, error);
                                errorCount++;
                            }

                            currentIndex += BATCH_SIZE_SOS;
                        }

                        // Finalizar
                        $("#loading").css("display", "none");
                        $("#submit").prop('disabled', false).text('Importar');

                        if (errorCount === 0) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Importación Exitosa!',
                                text: `Se procesaron correctamente ${totalRows} registros.`,
                                timer: 3000,
                                showConfirmButton: false
                            }).then(() => {
                                $('#modalImportar').modal('hide');
                                $('#formImportar')[0].reset();
                                tableSOS.ajax.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Importación Completada con Errores',
                                html: `Se procesaron los datos, pero <strong>${errorCount}</strong> lote(s) fallaron.<br>Revise la consola para más detalles.`
                            }).then(() => {
                                tableSOS.ajax.reload();
                            });
                        }

                    } catch (error) {
                        console.error("[ERROR CRÍTICO] Error al procesar el archivo:", error);
                        $("#loading").css("display", "none");
                        $("#submit").prop('disabled', false).text('Importar');
                        Swal.fire({
                            icon: 'error',
                            title: 'Error Crítico',
                            text: 'Ocurrió un error al procesar el archivo. Revise la consola.'
                        });
                    }
                };

                // Leer archivo según extensión
                if (fileExtension === 'csv') {
                    reader.readAsText(file, 'UTF-8');
                } else {
                    reader.readAsArrayBuffer(file);
                }
            }

            function exportarTodosLosRegistrosSOS() {
                $("#loading").css("display", "flex");
                
                const form = $('<form>', {
                    method: 'POST',
                    action: 'getters/export_sos_5ps_excel.php',
                    target: '_blank'
                });
                
                form.append($('<input>', {
                    type: 'hidden',
                    name: 'fecha_inicio',
                    value: $('#date-start-sos').val()
                }));
                
                form.append($('<input>', {
                    type: 'hidden',
                    name: 'fecha_fin',
                    value: $('#date-end-sos').val()
                }));
                
                const searchValue = tableSOS.search();
                if (searchValue) {
                    form.append($('<input>', {
                        type: 'hidden',
                        name: 'search_value',
                        value: searchValue
                    }));
                }
                
                form.appendTo('body').submit().remove();
                
                setTimeout(function() {
                    $("#loading").css("display", "none");
                }, 1000);
            }

            function actualizarContadorSeleccionados() {
                var seleccionados = $('.select-row:checked').length;
                $('#contadorSeleccionados').text(seleccionados);
                
                if (seleccionados > 0) {
                    $('#btnEliminarSeleccionados').show();
                } else {
                    $('#btnEliminarSeleccionados').hide();
                }
            }

            $('#btnEliminarSeleccionados').off('click').on('click', function() {
                var idsSeleccionados = [];
                $('.select-row:checked').each(function() {
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
                    html: 'Está a punto de eliminar <strong>' + idsSeleccionados.length + '</strong> registro(s) SOS/5Ps.<br>Esta acción no se puede deshacer.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, continuar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        eliminarPorLotesSOS(idsSeleccionados);
                    }
                });
            });

            function eliminarPorLotesSOS(idsSeleccionados) {
                const BATCH_SIZE = 500;
                const totalIds = idsSeleccionados.length;
                let procesados = 0;
                let errores = 0;
                
                Swal.fire({
                    title: 'Procesando...',
                    html: `<p>Eliminando registros...</p><p id="progress-text">0 / ${totalIds}</p>`,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                const chunks = [];
                for (let i = 0; i < idsSeleccionados.length; i += BATCH_SIZE) {
                    chunks.push(idsSeleccionados.slice(i, i + BATCH_SIZE));
                }
                
                function procesarSiguienteLote(index) {
                    if (index >= chunks.length) {
                        Swal.close();
                        
                        if (errores === 0) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Eliminado!',
                                text: `Se eliminaron correctamente ${procesados} registro(s).`,
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Proceso Completado con Errores',
                                html: `Se eliminaron <strong>${procesados}</strong> registros.<br>Fallaron <strong>${errores}</strong> registros.`
                            });
                        }
                        
                        tableSOS.ajax.reload();
                        return;
                    }
                    
                    const loteActual = chunks[index];
                    
                    $.ajax({
                        type: "POST",
                        url: "actions/delete_tb_sos_5ps.php",
                        data: { ids: loteActual },
                        dataType: 'json',
                        success: function(result) {
                            if (result.success) {
                                procesados += result.actualizados || loteActual.length;
                            } else {
                                errores += loteActual.length;
                            }
                            
                            $('#progress-text').text(`${procesados + errores} / ${totalIds}`);
                            procesarSiguienteLote(index + 1);
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            errores += loteActual.length;
                            console.error(`Error en lote ${index + 1}:`, textStatus);
                            
                            $('#progress-text').text(`${procesados + errores} / ${totalIds}`);
                            procesarSiguienteLote(index + 1);
                        }
                    });
                }
                
                procesarSiguienteLote(0);
            }

            $('#btnGuardarNuevo').off('click').on('click', function() {
                var formData = {
                    day: $('#add_day').val(),
                    time: $('#add_time').val(),
                    cp_code: $('#add_cp_code').val(),
                    manufacturer: $('#add_manufacturer').val(),
                    subcategory: $('#add_subcategory').val(),
                    total_cms_hooks: $('#add_total_cms_hooks').val(),
                    cms_individual_hooks: $('#add_cms_individual_hooks').val(),
                    sos: $('#add_sos').val(),
                    server_date: $('#add_server_date').val() 
                };

                if (formData.server_date) {
                    formData.server_date = formData.server_date.replace('T', ' ');
                }

                if (Object.values(formData).some(x => x === null || x === '')) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Atención',
                        text: 'Por favor, complete todos los campos requeridos.'
                    });
                    return;
                }

                $("#loading").css("display", "flex");
                
                $.ajax({
                    type: "POST",
                    url: "actions/insert_sos_5ps.php",
                    data: formData,
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
                            $('#formAgregar')[0].reset();
                            tableSOS.ajax.reload();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: result.message || 'Error al insertar el registro.'
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
            });

            $('#btnGuardarCambios').off('click').on('click', function() {
                var formData = {
                    id: $('#edit_id').val(),
                    day: $('#edit_day').val(),
                    time: $('#edit_time').val(),
                    cp_code: $('#edit_cp_code').val(),
                    manufacturer: $('#edit_manufacturer').val(),
                    subcategory: $('#edit_subcategory').val(),
                    total_cms_hooks: $('#edit_total_cms_hooks').val(),
                    cms_individual_hooks: $('#edit_cms_individual_hooks').val(),
                    sos: $('#edit_sos').val(),
                    server_date: $('#edit_server_date').val()
                };
                
                if (formData.server_date) {
                    formData.server_date = formData.server_date.replace('T', ' ');
                }
                
                console.log('Datos de edición a enviar:', formData);
                
                $("#loading").css("display", "flex");
                
                $.ajax({
                    type: "POST",
                    url: "actions/update_tb_sos_5ps.php",
                    data: formData,
                    dataType: 'json',
                    success: function(result) {
                        $("#loading").css("display", "none");
                        
                        if (result.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Actualizado!',
                                text: result.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            $('#modalEditar').modal('hide');
                            tableSOS.ajax.reload();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: result.message || 'Error al actualizar el registro.'
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

                        // Llamar a las funciones según extensión
                        if (extension === 'csv') {
                            procesarCSVDeleteSOS(file);
                        } else if (extension === 'xlsx' || extension === 'xls') {
                            procesarExcelDeleteSOS(file);
                        } else {
                            Swal.fire('Error', 'Formato no soportado. Use CSV', 'error');
                            $('#progreso-contenedor').hide();
                            $('#btnConfirmDeleteExcel').prop('disabled', false);
                        }
                    }
                });
            });

            async function enviarIdsEnLotesSOS(ids) {
                const tamañoLote = 500;
                const total = ids.length;
                let procesados = 0;

                for (let i = 0; i < total; i += tamañoLote) {
                    let lote = ids.slice(i, i + tamañoLote);
                    
                    try {
                        let respuesta = await $.ajax({
                            type: "POST",
                            url: "actions/delete_tb_sos_5ps.php", 
                            data: { ids: lote },
                            dataType: 'json'
                        });

                        if (respuesta.success) {
                            procesados += lote.length;
                            let porcentaje = Math.round((procesados / total) * 100);
                            
                            // Actualizar barra de progreso
                            $('#barra-progreso').css('width', porcentaje + '%').text(porcentaje + '%');
                            $('#progreso-texto').text(`Procesando: ${porcentaje}% (${procesados.toLocaleString()} de ${total.toLocaleString()})`);
                        } else {
                            console.error("Error en respuesta del servidor:", respuesta);
                        }
                    } catch (error) {
                        console.error("Error en lote:", error);
                    }
                }

                // Finalización
                $('#progreso-contenedor').hide();
                $('#btnConfirmDeleteExcel').prop('disabled', false);
                
                Swal.fire({
                    icon: 'success',
                    title: '¡Eliminación Completada!',
                    text: `Se procesaron exitosamente ${procesados.toLocaleString()} registros.`,
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    $('#frmExcelDelete')[0].reset(); // Limpiar formulario
                    tableSOS.ajax.reload(); // Recargar tabla
                });
            }

            function procesarCSVDeleteSOS(file) {
                // Verificar si PapaParse está disponible
                if (typeof Papa === 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'La librería PapaParse no está cargada. Necesaria para procesar CSV.'
                    });
                    $('#progreso-contenedor').hide();
                    $('#btnConfirmDeleteExcel').prop('disabled', false);
                    return;
                }
                
                Papa.parse(file, {
                    skipEmptyLines: true,
                    complete: function(results) {
                        let ids = results.data.slice(1) // Saltar encabezado
                                    .map(row => row[0])
                                    .filter(id => id && !isNaN(id));
                        
                        if (ids.length === 0) {
                            Swal.fire('Error', 'No se encontraron IDs válidos en el archivo.', 'error');
                            $('#progreso-contenedor').hide();
                            $('#btnConfirmDeleteExcel').prop('disabled', false);
                            return;
                        }
                        
                        console.log(`[CSV] Se encontraron ${ids.length} IDs para eliminar`);
                        enviarIdsEnLotesSOS(ids);
                    },
                    error: function(error) {
                        console.error("Error al parsear CSV:", error);
                        Swal.fire('Error', 'Error al leer el archivo CSV.', 'error');
                        $('#progreso-contenedor').hide();
                        $('#btnConfirmDeleteExcel').prop('disabled', false);
                    }
                });
            }

            function procesarExcelDeleteSOS(file) {
                // Verificar si SheetJS está disponible
                if (typeof XLSX === 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'La librería SheetJS (XLSX) no está cargada. Necesaria para procesar Excel.'
                    });
                    $('#progreso-contenedor').hide();
                    $('#btnConfirmDeleteExcel').prop('disabled', false);
                    return;
                }
                
                var reader = new FileReader();
                reader.onload = function(e) {
                    try {
                        var data = new Uint8Array(e.target.result);
                        var workbook = XLSX.read(data, { 
                            type: 'array',
                            sheets: [0] 
                        });
                        
                        var worksheet = workbook.Sheets[workbook.SheetNames[0]];
                        var json = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
                        
                        let ids = json.slice(1) // Saltar encabezado
                                    .map(row => row[0])
                                    .filter(id => id && !isNaN(id));
                        
                        if (ids.length === 0) {
                            Swal.fire('Error', 'No se encontraron IDs válidos en el archivo Excel.', 'error');
                            $('#progreso-contenedor').hide();
                            $('#btnConfirmDeleteExcel').prop('disabled', false);
                            return;
                        }
                        
                        console.log(`[EXCEL] Se encontraron ${ids.length} IDs para eliminar`);
                        enviarIdsEnLotesSOS(ids);
                        
                    } catch (error) {
                        console.error("Error al procesar Excel:", error);
                        Swal.fire('Error', 'Error al leer el archivo Excel.', 'error');
                        $('#progreso-contenedor').hide();
                        $('#btnConfirmDeleteExcel').prop('disabled', false);
                    }
                };
                
                reader.onerror = function(error) {
                    console.error("Error al leer archivo:", error);
                    Swal.fire('Error', 'Error al leer el archivo.', 'error');
                    $('#progreso-contenedor').hide();
                    $('#btnConfirmDeleteExcel').prop('disabled', false);
                };
                
                reader.readAsArrayBuffer(file);
            }
        });

    </script>
</body>

</html>