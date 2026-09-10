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
    
    $batch_size = 1000;

    if ($_FILES["file_delete"]["size"] > 0) {
        
        if ($fileExtension === 'csv') {
            $file = fopen($fileName, "r");
            
            $bom = fread($file, 3);
            if ($bom != "\xEF\xBB\xBF") {
                rewind($file);
            }
            
            $primeraFila = true;

            while (($column = fgetcsv($file, 1000, ";")) !== FALSE) {
                if ($primeraFila) {
                    $primeraFila = false;
                    continue;
                }

                if (isset($column[0]) && is_numeric(trim($column[0]))) {
                    $idsParaEliminar[] = intval(trim($column[0]));
                }
                
                if (count($idsParaEliminar) >= $batch_size) {
                    $idsUnicos = array_unique($idsParaEliminar);
                    
                    if (!softDeleteObjetivoReByIds($db, $idsUnicos)) {
                        $exitoGlobal = false;
                        break;
                    }
                    
                    $totalProcesados += count($idsUnicos);
                    $idsParaEliminar = array();
                }
            }
            
            fclose($file);
            
            if ($exitoGlobal && count($idsParaEliminar) > 0) {
                $idsUnicos = array_unique($idsParaEliminar);
                
                if (!softDeleteObjetivoReByIds($db, $idsUnicos)) {
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

                foreach ($rows as $row) {
                    if ($firstRow) {
                        $firstRow = false;
                        continue;
                    }
                    
                    if (isset($row[0]) && is_numeric(trim($row[0]))) {
                        $idsParaEliminar[] = intval(trim($row[0]));
                    }
                    
                    if (count($idsParaEliminar) >= $batch_size) {
                        $idsUnicos = array_unique($idsParaEliminar);
                        
                        if (!softDeleteObjetivoReByIds($db, $idsUnicos)) {
                            $exitoGlobal = false;
                            break;
                        }
                        
                        $totalProcesados += count($idsUnicos);
                        $idsParaEliminar = array();
                    }
                }
                
                if ($exitoGlobal && count($idsParaEliminar) > 0) {
                    $idsUnicos = array_unique($idsParaEliminar);
                    
                    if (!softDeleteObjetivoReByIds($db, $idsUnicos)) {
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

        if ($exitoGlobal && $totalProcesados > 0) {
            $type = "success";
            $message = "Borrado completado correctamente. Se procesaron $totalProcesados IDs únicos.";
        } elseif ($totalProcesados > 0) {
            $type = "warning";
            $message = "Proceso interrumpido. Se lograron procesar $totalProcesados IDs antes del error.";
        } elseif ($totalProcesados === 0 && $exitoGlobal) {
            $type = "warning";
            $message = "No se encontraron IDs de Objetivo RE válidos para eliminar en el archivo.";
        } else {
            $type = "error";
            $message = "Ocurrió un error durante el borrado masivo.";
        }
        
    } else {
        $type = "error";
        $message = "El archivo está vacío o no se subió correctamente.";
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

function procesarLote($db, $lote, $conn)
{
    if ($conn instanceof mysqli) {
        $conn->set_charset("utf8mb4");
    }
    
    $sqlInsert = "INSERT INTO tb_objetivo_re (
        fecha, codigo_pdv, objetivo, status
    ) VALUES ";
    
    $paramType = "";
    $paramArray = array();
    $registrosValidos = 0;

    foreach ($lote as $index => $fila) {
        $hayDato = false;

        for ($i = 0; $i <= 2; $i++) {
            if (isset($fila[$i]) && trim($fila[$i]) !== "" && strtoupper(trim($fila[$i])) !== "NULL") { 
                $hayDato = true; 
                break; 
            }
        }
        
        if (!$hayDato) {
            continue;
        }

        $sqlInsert .= "(?,?,?,?),";
        $paramType .= "ssii"; // fecha(s), codigo_pdv(s), objetivo(i), status(i)

        $paramArray[] = convertirFechaUniversal($fila[0]);  // fecha
        $paramArray[] = isset($fila[1]) ? trim($fila[1]) : null;  // codigo_pdv
        $paramArray[] = isset($fila[2]) ? intval(trim($fila[2])) : 0;  // objetivo
        $paramArray[] = 1;  // status = 1 (activo por defecto)

        $registrosValidos++;
    }

    if ($registrosValidos === 0) {
        return false;
    }

    $sqlInsert = rtrim($sqlInsert, ',');

    $insertId = $db->insertMultiple($sqlInsert, $paramType, $paramArray);

    return !empty($insertId) || $insertId !== false;
}

function softDeleteObjetivoReByIds($db, $ids)
{
    if (empty($ids)) {
        return false;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $paramType = str_repeat('i', count($ids));
    
    $query = "UPDATE tb_objetivo_re SET status = 0 WHERE id IN ($placeholders)";
    
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
        style="display: flex;position: fixed; width: 100%; height: 100%; top: 0px; left: 0px; z-index: 999999; overflow: auto; background-color: rgba(0, 0, 0, 0.498039);">
        <img style="position: absolute; top: 50% !important; left: 47% !important;" src="loader_1.gif">
</div>

<div id="content" style="width:100%;">
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <h3>Gestión de Objetivos RE</h3>
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
                <br />
                    <strong><small style="color: #f21a1a; font-size: 20px;">Encabezados permitidos. El ID se genera automáticamente(Quitarlo del csv).</small></strong>
            </div>
        </form>
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Eliminación Masiva por ID (Objetivos RE)</h5>
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
                <input type="date" class="form-control" id="date-start-objetivo">
                
                <div class="input-group-prepend">
                    <span class="input-group-text">Hasta:</span>
                </div>
                <input type="date" class="form-control" id="date-end-objetivo">

                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="button" id="clear-filter-objetivo" title="Limpiar Filtro">
                        <i class="material-icons" style="font-size: 1.2rem;">close</i>
                    </button>
                </div>
            </div>
        </div>

        <div class="col-xl-12 col-lg-12 mb-4">
            <button type="button" class="btn btn-success mb-3" data-toggle="modal" data-target="#modalAgregar">
                <i class="material-icons" style="font-size: 1.2rem;">add</i> Agregar Nuevo Objetivo RE
            </button>
            <button type="button" class="btn btn-danger mb-3 ml-2" id="btnEliminarSeleccionados" style="display:none;">
                <i class="material-icons" style="font-size: 1.2rem;">delete</i> Eliminar Seleccionados (<span id="contadorSeleccionados">0</span>)
            </button>
            <div class="bg-white rounded-lg p-5 shadow" id="data-result" name="data-result"></div>
        </div>
    </div>
</div>

<!-- MODAL EDITAR -->
<div class="modal fade" id="modalEditar" tabindex="-1" role="dialog" aria-labelledby="modalEditarLabel" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarLabel">Editar Objetivo RE</h5>
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
                                <label for="edit_fecha">Fecha *</label>
                                <input type="date" class="form-control" id="edit_fecha" name="edit_fecha" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_codigo_pdv">Código PDV *</label>
                                <input type="text" class="form-control" id="edit_codigo_pdv" name="edit_codigo_pdv" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_objetivo">Objetivo *</label>
                                <input type="number" class="form-control" id="edit_objetivo" name="edit_objetivo" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_status">Estado</label>
                                <select class="form-control" id="edit_status" name="edit_status">
                                    <option value="1">Activo</option>
                                    <option value="0">Inactivo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarCambiosObjetivo">Guardar Cambios</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL AGREGAR -->
<div class="modal fade" id="modalAgregar" tabindex="-1" role="dialog" aria-labelledby="modalAgregarLabel" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAgregarLabel">Agregar Nuevo Objetivo RE</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formAgregar">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="add_fecha">Fecha *</label>
                                <input type="date" class="form-control" id="add_fecha" name="add_fecha" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="add_codigo_pdv">Código PDV *</label>
                                <input type="text" class="form-control" id="add_codigo_pdv" name="add_codigo_pdv" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="add_objetivo">Objetivo *</label>
                                <input type="number" class="form-control" id="add_objetivo" name="add_objetivo" required>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btnGuardarNuevoObjetivo">Guardar Objetivo</button>
            </div>
        </div>
    </div>
</div>

<!-- SCRIPTS -->
<script src="/App/XploraEcuador/assets/js/popper-1.12.9.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="/App/XploraEcuador/assets/js/bootstrap-4.0.0.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

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
<script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.3.2/papaparse.min.js"></script>

<script>
    var tableObjetivo = null;
    const BATCH_SIZE = 1000;
    const TARGET_URL = 'tb_objetivo_re.php';
    
    const columnsDefinition = [
        { title: '<input type="checkbox" id="selectAll">', data: 0, orderable: false, searchable: false },
        { title: 'ID', data: 1 },
        { title: 'FECHA', data: 2 },
        { title: 'CÓDIGO PDV', data: 3 },
        { title: 'OBJETIVO', data: 4 },
        { title: 'STATUS', data: 5 },
        { title: 'ACCIONES', data: 6, orderable: false, searchable: false }
    ];

    function cargarTablaObjetivo() {
        if (!tableObjetivo) {
            $("#data-result").html('<table id="table_objetivo" name="table_objetivo" class="table table-striped" style="width:100%"><thead></thead><tbody></tbody></table>');
            $("#loading").css("display", "flex");
            inicializarDataTablesYEventosObjetivo(columnsDefinition); 
        } else {
            tableObjetivo.ajax.reload(null, false); 
        }
    }

    function inicializarDataTablesYEventosObjetivo(columns) {
        if (tableObjetivo) {
            return; 
        }
        
        tableObjetivo = $('#table_objetivo').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": "getters/get_table_tb_objetivo_re.php", 
                "type": "POST",
                "data": function(d) {
                    d.fecha_inicio = $('#date-start-objetivo').val(); 
                    d.fecha_fin = $('#date-end-objetivo').val();
                },
                "dataSrc": function (json) {
                    $("#loading").css("display", "none");
                    return json.data;
                },
                "error": function(xhr, error, thrown) {
                    $("#loading").css("display", "none");
                    console.error("Error al cargar datos:", thrown);
                    alert("Error al cargar los datos de la tabla.");
                }
            },
            "columns": columns,
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
            "order": [[ 1, "asc" ]],
            "initComplete": function(settings, json) {
                $('#table_objetivo').off('change', '.select-row').on('change', '.select-row', function() {
                    actualizarContadorSeleccionados();
                });

                $('#selectAll').off('click').on('click', function() {
                    var isChecked = $(this).prop('checked');
                    tableObjetivo.rows({ page: 'current' }).nodes().to$().find('.select-row').prop('checked', isChecked);
                    actualizarContadorSeleccionados();
                });
                
                $('#table_objetivo').off('click', '.btn-editar-objetivo').on('click', '.btn-editar-objetivo', function() {
                    const id_registro = $(this).data('id');
                    $('#formEditar')[0].reset(); 
                    
                    $.ajax({
                        url: 'getters/get_objetivo_re_by_id.php', 
                        type: 'POST',
                        dataType: 'json',
                        data: { id: id_registro },
                        success: function(response) {
                            if (response.status === 'success') {
                                const data = response.data;
                                
                                for (const key in data) {
                                    if (data.hasOwnProperty(key)) {
                                        const field_id = `#edit_${key}`; 
                                        const value = data[key];
                                        const $field = $(field_id);
                                        $field.val(value);
                                    }
                                }
                                
                                $('#modalEditar').modal('show');
                            } else {
                                alert('Error al obtener datos: ' + response.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error("Error AJAX:", error);
                            alert("Ocurrió un error al obtener el registro.");
                        }
                    });
                    
                    return false; 
                });
                
                actualizarContadorSeleccionados();
            }
        });
    }

    function exportarTodosLosRegistros() {
        $("#loading").css("display", "flex");
        
        const form = $('<form>', {
            method: 'POST',
            action: 'getters/export_tb_objetivo_re.php',
            target: '_blank'
        });
        
        form.append($('<input>', {
            type: 'hidden',
            name: 'fecha_inicio',
            value: $('#date-start-objetivo').val()
        }));
        
        form.append($('<input>', {
            type: 'hidden',
            name: 'fecha_fin',
            value: $('#date-end-objetivo').val()
        }));
        
        const searchValue = tableObjetivo.search();
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

    async function iniciarCargaPorLotes(file, fileExtension) {
        const reader = new FileReader();
        
        $("#submit").prop('disabled', true).text('Procesando...'); 
        $("#loading").css("display", "flex").text("Iniciando lectura del archivo...");
        
        reader.onload = async function(e) {
            $("#loading").text("Leyendo datos del archivo...");
            const data = e.target.result;
            let rows = [];
            let success = true;

            try {
                if (fileExtension === 'csv') {
                    rows = data.split('\n')
                        .map(line => line.split(';'))
                        .filter(line => line.join('').trim() !== '');
                } else if (fileExtension === 'xlsx' || fileExtension === 'xls') {
                    if (typeof XLSX === 'undefined') {
                        alert("Error: La librería SheetJS es necesaria para archivos Excel.");
                        success = false;
                    } else {
                        const data_array = new Uint8Array(data);
                        const workbook = XLSX.read(data_array, { type: 'array' });
                        const sheetName = workbook.SheetNames[0];
                        rows = XLSX.utils.sheet_to_json(workbook.Sheets[sheetName], { header: 1 });

                        const COLUMNAS_ESPERADAS = 3;
                        rows = rows.map(row => {
                            const newRow = Array.isArray(row) ? [...row] : []; 
                            while (newRow.length < COLUMNAS_ESPERADAS) {
                                newRow.push(null);
                            }
                            return newRow.slice(0, COLUMNAS_ESPERADAS);
                        });

                        rows = rows.filter(row => row.some(cell => cell !== null && cell !== ''));
                    }
                } else {
                    alert("Formato de archivo no soportado.");
                    success = false;
                }
            } catch (error) {
                alert("Error al parsear el archivo.");
                success = false;
            }

            if (!success || rows.length === 0) {
                $("#loading").css("display", "none");
                $("#submit").prop('disabled', false).text('Import');
                if (rows.length === 0 && success) {
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
                alert("El archivo solo contiene encabezados.");
                return;
            }
            
            for (let i = 0; i < totalRows && success; i += BATCH_SIZE) {
                const batch = rows.slice(i, i + BATCH_SIZE);
                const batchIndex = Math.floor(i / BATCH_SIZE) + 1;
                const totalBatches = Math.ceil(totalRows / BATCH_SIZE);
                
                $("#loading").text(`Procesando lote ${batchIndex} / ${totalBatches} (${processedRows}/${totalRows} filas)...`);
                
                try {
                    const response = await fetch(TARGET_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'Cache-Control': 'no-cache'
                        },
                        body: `lote_data=${encodeURIComponent(JSON.stringify(batch))}`
                    });

                    const result = await response.json();
                    
                    if (response.status !== 200 || result.status !== 'success') {
                        success = false;
                        alert(`Error al procesar el lote ${batchIndex}: ${result.message || 'Error desconocido'}`);
                    } else {
                        processedRows += batch.length;
                    }
                } catch (error) {
                    success = false;
                    alert(`Error de conexión al enviar el lote ${batchIndex}.`);
                }
            }

            $("#loading").css("display", "none");
            $("#submit").prop('disabled', false).text('Import');

            if (success) {
                alert(`Carga por lotes completada. ${totalRows} registros insertados.`);
                cargarTablaObjetivo();
            } else {
                alert("Carga cancelada debido a un error.");
            }
        };
        
        if (fileExtension === 'xlsx' || fileExtension === 'xls') {
            reader.readAsArrayBuffer(file);
        } else {
            reader.readAsText(file);
        }
    }

    $(document).ready(function() {
        cargarTablaObjetivo();

        $('#date-start-objetivo, #date-end-objetivo').on('change input', function() {
            if (tableObjetivo) {
                tableObjetivo.ajax.reload();
            } else {
                cargarTablaObjetivo();
            }
        });

        $('#clear-filter-objetivo').on('click', function() {
            $('#date-start-objetivo').val('').trigger('input').trigger('change');
            $('#date-end-objetivo').val('').trigger('input').trigger('change');
            if (tableObjetivo) tableObjetivo.ajax.reload();
        });
        
        $('#frmImport').on('submit', function(e) {
            e.preventDefault();
            
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
                alert("Formato de archivo no soportado.");
            }
        });

        $('#btnEliminarSeleccionados').on('click', function() {
            var idsSeleccionados = [];
            $('.select-row:checked').each(function() {
                idsSeleccionados.push($(this).val());
            });

            if (idsSeleccionados.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: 'Seleccione al menos un registro para eliminar.'
                });
                return;
            }

            Swal.fire({
                title: '¿Está seguro?',
                html: 'Está a punto de eliminar <strong>' + idsSeleccionados.length + '</strong> registro(s).',
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
                        url: "actions/delete_objetivo_re.php", 
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
                                cargarTablaObjetivo();
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: result.message || 'Error al eliminar los registros.'
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

        $('#btnGuardarNuevoObjetivo').on('click', function() {
            const formData = $('#formAgregar').serialize(); 
            
            const fecha = $('#add_fecha').val();
            const codigo_pdv = $('#add_codigo_pdv').val();
            const objetivo = $('#add_objetivo').val();
            
            if (fecha.trim() === '' || codigo_pdv.trim() === '' || objetivo.trim() === '') {
                Swal.fire({ 
                    icon: 'warning', 
                    title: 'Atención', 
                    text: 'Complete todos los campos obligatorios.' 
                });
                return;
            }
            
            $(this).prop('disabled', true).text('Guardando...');
            $("#loading").css("display", "flex");
            
            $.ajax({
                type: "POST",
                url: "actions/insert_objetivo_re.php", 
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
                        
                        if (typeof tableObjetivo !== 'undefined') {
                            tableObjetivo.ajax.reload(null, false);
                        } else {
                            cargarTablaObjetivo();
                        }
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
                        text: 'No se pudo contactar al servidor.' 
                    });
                },
                complete: function() {
                    $('#btnGuardarNuevoObjetivo').prop('disabled', false).text('Guardar Objetivo'); 
                }
            });
        });

        $('#btnGuardarCambiosObjetivo').off('click').on('click', function() {
            const urlDestino = "actions/update_objetivo_re.php";
            const formData = $('#formEditar').serialize();
            
            const id = $('#edit_id').val();
            if (!id || id <= 0) {
                Swal.fire({ icon: 'warning', title: 'Atención', text: 'Error en la identificación del registro.' });
                return;
            }

            $(this).prop('disabled', true).text('Guardando...');
            $("#loading").css("display", "flex");
            
            $.ajax({
                type: "POST",
                url: urlDestino, 
                data: formData,
                dataType: 'json',
                success: function(result) {
                    $("#loading").css("display", "none");
                    if (result.success) {
                        Swal.fire({ icon: 'success', title: '¡Éxito!', text: 'Registro actualizado correctamente', timer: 2000, showConfirmButton: false });
                        $('#modalEditar').modal('hide');
                        if (typeof tableObjetivo !== 'undefined') {
                            tableObjetivo.ajax.reload(null, false);
                        } else {
                            cargarTablaObjetivo();
                        }
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: result.message || 'Error al actualizar.' });
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    $("#loading").css("display", "none");
                    Swal.fire({ icon: 'error', title: 'Error de Conexión', text: 'No se pudo contactar al servidor.' });
                },
                complete: function() {
                    $('#btnGuardarCambiosObjetivo').prop('disabled', false).text('Guardar Cambios');
                }
            });
        });
    });

    $('#btnConfirmDeleteExcel').on('click', function(e) {
        e.preventDefault();
        
        var fileInput = $('#file_delete')[0];
        if (fileInput.files.length === 0) {
            Swal.fire('Error', 'Selecciona un archivo.', 'error');
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
                $('#progreso-contenedor').show();
                $('#btnConfirmDeleteExcel').prop('disabled', true);

                if (extension === 'csv') {
                    procesarCSVDelete(file);
                } else {
                    procesarExcelDelete(file);
                }
            }
        });
    });

    async function enviarIdsEnLotes(ids) {
        const tamañoLote = 1000;
        const total = ids.length;
        let procesados = 0;

        for (let i = 0; i < total; i += tamañoLote) {
            let lote = ids.slice(i, i + tamañoLote);
            
            try {
                let respuesta = await $.ajax({
                    type: "POST",
                    url: "actions/delete_objetivo_re.php",
                    data: { ids: lote },
                    dataType: 'json'
                });

                if (respuesta.success) {
                    procesados += lote.length;
                    let porcentaje = Math.round((procesados / total) * 100);
                    $('#barra-progreso').css('width', porcentaje + '%').text(porcentaje + '%');
                    $('#progreso-texto').text(`Procesando: ${porcentaje}% (${procesados.toLocaleString()} de ${total.toLocaleString()})`);
                }
            } catch (error) {
                console.error("Error en lote:", error);
            }
        }

        Swal.fire({
            icon: 'success',
            title: '¡Eliminación Completada!',
            text: `Se procesaron ${procesados.toLocaleString()} registros.`,
            confirmButtonText: 'Aceptar'
        }).then(() => {
            location.reload();
        });
    }

    function procesarCSVDelete(file) {
        Papa.parse(file, {
            skipEmptyLines: true,
            complete: function(results) {
                let ids = results.data.slice(1)
                            .map(row => row[0])
                            .filter(id => id && !isNaN(id));
                enviarIdsEnLotes(ids);
            }
        });
    }

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
            
            enviarIdsEnLotes(ids);
        };
        reader.readAsArrayBuffer(file);
    }
</script>
</body>
</html>