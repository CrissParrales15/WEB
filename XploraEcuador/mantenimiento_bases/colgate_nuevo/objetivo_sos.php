<?php

use Phppot\DataSource;

require_once 'DataSource.php';
$db = new DataSource();
$conn = $db->getConnection();

if ($conn instanceof mysqli) {
    $conn->set_charset("utf8mb4");
} else {
    $conn->exec("SET NAMES utf8mb4");
}

if (isset($_POST["import"])) {

    $fileName = $_FILES["file"]["tmp_name"];
    $fileExtension = strtolower(pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION));

    if ($_FILES["file"]["size"] > 0) {

        $tamano_lote = 500;
        $contador = 0;
        $lote = array();

        if ($fileExtension === 'csv') {
            $file = fopen($fileName, "r");
            $primeraFila = true;
            $totalFilas = 0;

            while (($column = fgetcsv($file, 27000, ";")) !== FALSE) {
                $totalFilas++;

                if ($primeraFila) {
                    $primeraFila = false;
                    continue;
                }

                $lote[] = $column;
                $contador++;

                if ($contador == $tamano_lote || feof($file)) {
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
                $totalFilas = 0;

                foreach ($rows as $row) {
                    $totalFilas++;

                    if ($firstRow) {
                        $firstRow = false;
                        continue;
                    }

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
                $message = "Error al leer el archivo Excel: " . SimpleXLSX::parseError();
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

if (isset($_POST["delete_by_excel"])) {
    
    $fileName = $_FILES["file_delete"]["tmp_name"]; 
    $fileExtension = strtolower(pathinfo($_FILES["file_delete"]["name"], PATHINFO_EXTENSION));

    if ($_FILES["file_delete"]["size"] > 0) {
        
        $all_ids = array(); 

        if ($fileExtension === 'csv') {
            $file = fopen($fileName, "r");
            $primeraFila = true;

            while (($column = fgetcsv($file, 27000, ";")) !== FALSE) {
                if ($primeraFila) {
                    $primeraFila = false;
                    continue; 
                }
                
                $id_candidato = isset($column[0]) ? trim($column[0]) : '';
                if (is_numeric($id_candidato) && (int)$id_candidato > 0) {
                     $all_ids[] = (int)$id_candidato;
                }
            }
            fclose($file);

        } elseif ($fileExtension === 'xlsx' || $fileExtension === 'xls') {
            
            require_once 'SimpleXLSX.php'; //revisar

            if ($xlsx = SimpleXLSX::parse($fileName)) {
                $rows = $xlsx->rows();
                $firstRow = true;

                foreach ($rows as $row) {
                    if ($firstRow) {
                        $firstRow = false;
                        continue; 
                    }
                    
                    $id_candidato = isset($row[0]) ? trim($row[0]) : '';
                    if (is_numeric($id_candidato) && (int)$id_candidato > 0) {
                        $all_ids[] = (int)$id_candidato;
                    }
                }
            } else {
                $type = "error";
                $message = "Error al leer el archivo Excel: " . (SimpleXLSX::parseError() ?? 'Verifica la ruta de SimpleXLSX.php');
            }
        }
        
        if (!empty($all_ids) && !isset($type)) {
            $unique_ids = array_unique($all_ids);
            
            $delete_result = softDeleteByIds($unique_ids); 
            
            if ($delete_result['success']) {
                $type = "success";
                $message = $delete_result['message']; 
            } else {
                $type = "error";
                $message = "Error en Delete: " . $delete_result['message']; 
            }
            
        } elseif (!isset($type)) {
            $type = "warning";
            $message = "Archivo leído, pero no se encontraron IDs válidos para eliminar.";
        }
        
    } else {
        $type = "error";
        $message = "El archivo subido está vacío.";
    }

    if (isset($type) && $type === "success") {
        header("Location: " . $_SERVER['PHP_SELF'] . "?delete_status=success&count=" . $delete_result['actualizados']);
        exit;
    }
}

if (isset($_GET['delete_status']) && $_GET['delete_status'] === 'success') {
    $count = isset($_GET['count']) ? (int)$_GET['count'] : 0;
    $type = "success";
    $message = "El borrado masivo se completó correctamente. Se eliminaron $count registro(s).";
}


if (isset($_GET['import_status']) && $_GET['import_status'] === 'success') {
    $type = "success";
    $message = "La importación se completó correctamente.";
}


function procesarLote($db, $lote, $conn)
{
    $sqlInsert = "INSERT INTO tb_objetivo_sos 
    (fecha, canal, retail_enviroment, cliente, visual_access, subcategory, new_objetivo) 
    VALUES ";
    
    $paramType = "";
    $paramArray = array();
    $registrosValidos = 0;

    foreach ($lote as $index => $fila) {

        $hayDato = false;
        for ($i = 0; $i <= 6; $i++) {
            if (isset($fila[$i]) && trim($fila[$i]) !== "" && strtoupper(trim($fila[$i])) !== "NULL") { 
                $hayDato = true; 
                break; 
            }
        }
        if (!$hayDato) continue;

        $sqlInsert .= "(?,?,?,?,?,?,?),";
        $paramType .= "ssssssd";

        $fechaOriginal = trim($fila[0] ?? "");
        $fechaConvertida = "";

        if (is_numeric($fechaOriginal)) {
            $unix_time = ($fechaOriginal - 25569) * 86400;
            $fechaConvertida = gmdate("Y-m-d", $unix_time);
        } else {
            $partes = explode('/', $fechaOriginal);
            if (count($partes) == 3) {
                $fechaConvertida = $partes[2] . '-' . str_pad($partes[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($partes[0], 2, '0', STR_PAD_LEFT);
            } else {
                $timestamp = strtotime($fechaOriginal);
                $fechaConvertida = $timestamp ? date("Y-m-d", $timestamp) : null;
            }
        }

        $paramArray[] = $fechaConvertida;
        $paramArray[] = trim(mysqli_real_escape_string($conn, $fila[1] ?? ""));
        $paramArray[] = trim(mysqli_real_escape_string($conn, $fila[2] ?? ""));
        $paramArray[] = trim(mysqli_real_escape_string($conn, $fila[3] ?? ""));
        $paramArray[] = trim(mysqli_real_escape_string($conn, $fila[4] ?? ""));
        $paramArray[] = trim(mysqli_real_escape_string($conn, $fila[5] ?? ""));

        $new_obj = trim(mysqli_real_escape_string($conn, $fila[6] ?? ""));

        // Reemplazar coma -> punto
        $new_obj = str_replace(",", ".", $new_obj);

        // Convertir a decimal
        $new_obj = ($new_obj !== "") ? floatval($new_obj) : 0.00;

        $paramArray[] = $new_obj;

        $registrosValidos++;
    }

    if ($registrosValidos === 0) return false;

    $sqlInsert = rtrim($sqlInsert, ',');
    $insertId = $db->insertMultiple($sqlInsert, $paramType, $paramArray);

    return !empty($insertId);
}


function softDeleteByIds(array $ids) {
    global $conn;
    if (!isset($conn) || $conn->connect_error) {
        return ['success' => false, 'message' => 'Error de configuración: La conexión a la base de datos ($conn) no está disponible o ha fallado.'];
    }
    
    if (empty($ids)) {
        return ['success' => false, 'message' => 'No hay IDs para procesar.'];
    }
    
    $ids = array_filter($ids, function($id) {
        return is_numeric($id) && (int)$id > 0;
    });

    if (empty($ids)) {
         return ['success' => false, 'message' => 'IDs inválidos en el archivo.'];
    }
    
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $query = "UPDATE tb_objetivo_sos SET status = 0 WHERE id IN ($placeholders)"; 
    
    if ($stmt = $conn->prepare($query)) {
        $types = str_repeat('i', count($ids));
        
        $bind_params = array($types);
        foreach ($ids as &$id) { 
            $bind_params[] = &$id;
        }
        
        call_user_func_array(array($stmt, 'bind_param'), $bind_params);
        
        if ($stmt->execute()) {
            $actualizados = $stmt->affected_rows;
            $stmt->close();
            return [
                'success' => true, 
                'message' => "Delete exitoso. Se actualizaron $actualizados registro(s).",
                'actualizados' => $actualizados
            ];
        } else {
            $error_message = $stmt->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Error al ejecutar la actualización: ' . $error_message];
        }
    } else {
        return ['success' => false, 'message' => 'Error al preparar la consulta: ' . $conn->error];
    }
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
                <h3>Gestión de Objetivos SOS</h3>
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

        <div class="row mb-4">
            <form class="form-horizontal" action="" method="post" name="frmImport" id="frmImport" enctype="multipart/form-data">
                <div class="input-row">
                    <label class="col-md-4 control-label">Seleccionar archivo CSV (IMPORTAR)</label> 
                    <input type="file" name="file" id="file" accept=".csv">
                    <button type="submit" id="submit" name="import" class="btn-submit">Importar</button>
                    <br />
                    <small style="color: #666;">Formatos aceptados: CSV (separado por ;)</small>
                </div>
            </form>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">Eliminación Masiva por ID (Objetivo SOS)</h5>
                    </div>
                    <div class="card-body">
                        <form class="form-horizontal" id="frmExcelDelete">
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
                            <i class="material-icons" style="font-size: 1.2rem; vertical-align: middle;">close</i>
                        </button>
                    </div>
                </div>
            </div>


            <div class="col-xl-8 col-lg-6 mb-4 text-right"> 
                <button type="button" class="btn btn-success mb-3" data-toggle="modal" data-target="#modalAgregar">
                    <i class="material-icons" style="font-size: 1.2rem;">add</i> Agregar Nuevo Objetivo SOS
                </button>
                <button type="button" class="btn btn-danger mb-3 ml-2" id="btnEliminarSeleccionados" style="display:none;">
                    <i class="material-icons" style="font-size: 1.2rem;">delete</i> Eliminar Seleccionados (<span id="contadorSeleccionados">0</span>)
                </button>
            </div>

            <div class="col-xl-12 col-lg-12 mb-4">
                <!-- ⭐ CAMBIO: Tabla directa en HTML, no cargada por AJAX -->
                <div class="bg-white rounded-lg p-5 shadow">
                    <table id="table" class="table table-striped" style="width:100%">
                        <thead class="thead-light">
                            <tr>
                                <th scope="col"><input type="checkbox" id="selectAll"></th>
                                <th scope="col">ID</th>
                                <th scope="col">FECHA</th>
                                <th scope="col">CANAL</th>
                                <th scope="col">RETAIL ENVIROMENT</th>
                                <th scope="col">CLIENTE</th>
                                <th scope="col">VISUAL ACCESS</th>
                                <th scope="col">SUBCATEGORY</th>
                                <th scope="col">NUEVO OBJETIVO</th>
                                <th scope="col">FECHA MODIFICACIÓN</th>
                                <th scope="col">ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- ⭐ El contenido se carga automáticamente vía Server-Side -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        </div>


    <div class="modal fade" id="modalEditar" tabindex="-1" role="dialog" aria-labelledby="modalEditarLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document"> <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarLabel">Editar Objetivo SOS</h5>
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
                                    <label for="edit_fecha">Fecha</label>
                                    <input type="date" class="form-control" id="edit_fecha" name="edit_fecha" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_canal">Canal</label>
                                    <input type="text" class="form-control" id="edit_canal" name="edit_canal">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_retail_enviroment">Retail Enviroment</label>
                                    <input type="text" class="form-control" id="edit_retail_enviroment" name="edit_retail_enviroment">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_cliente">Cliente</label>
                                    <input type="text" class="form-control" id="edit_cliente" name="edit_cliente">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_visual_access">Visual Access</label>
                                    <input type="text" class="form-control" id="edit_visual_access" name="edit_visual_access">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_subcategory">Subcategory</label>
                                    <input type="text" class="form-control" id="edit_subcategory" name="edit_subcategory">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_new_objetivo">Nuevo Objetivo</label>
                            <input type="text" class="form-control" id="edit_new_objetivo" name="edit_new_objetivo" placeholder="Ej: 0.00 o 15.5">
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
        <div class="modal-dialog modal-lg" role="document"> <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAgregarLabel">Agregar Nuevo Objetivo SOS</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formAgregar">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="add_fecha">Fecha</label>
                                    <input type="date" class="form-control" id="add_fecha" name="add_fecha" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="add_canal">Canal</label>
                                    <input type="text" class="form-control" id="add_canal" name="add_canal" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="add_retail_enviroment">Retail Enviroment</label>
                                    <input type="text" class="form-control" id="add_retail_enviroment" name="add_retail_enviroment" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="add_cliente">Cliente</label>
                                    <input type="text" class="form-control" id="add_cliente" name="add_cliente" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="add_visual_access">Visual Access</label>
                                    <input type="text" class="form-control" id="add_visual_access" name="add_visual_access" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="add_subcategory">Subcategory</label>
                                    <input type="text" class="form-control" id="add_subcategory" name="add_subcategory" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="add_new_objetivo">Nuevo Objetivo</label>
                            <input type="text" class="form-control" id="add_new_objetivo" name="add_new_objetivo" placeholder="Ej: 0.00 o 15.5" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="btnGuardarNuevo">Guardar Objetivo</button>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.3.2/papaparse.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <script>

        $(document).ready(function() {
            // Variable global para la tabla
            var tableObjetivo = null;

            // ⭐ INICIALIZAR DATATABLES CON SERVER-SIDE PROCESSING
            inicializarDataTablesObjetivo();

            // Aplicar filtros cuando cambien las fechas
            $('#date-start-objetivo, #date-end-objetivo').on('change input', function() {
                if (tableObjetivo) {
                    tableObjetivo.ajax.reload();
                }
            });

            // Limpiar filtros
            $('#clear-filter-objetivo').on('click', function() {
                $('#date-start-objetivo').val('');
                $('#date-end-objetivo').val('');
                
                if (tableObjetivo) {
                    tableObjetivo.ajax.reload();
                }
            });

            // ⭐ FUNCIÓN PRINCIPAL: Inicializar DataTables con Server-Side
            function inicializarDataTablesObjetivo() {
                if (tableObjetivo) {
                    tableObjetivo.destroy();
                    tableObjetivo = null;
                }

                tableObjetivo = $('#table').DataTable({
                    "processing": true,
                    "serverSide": true,
                    "ajax": {
                        "url": "getters/get_table_objetivo_sos.php",
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
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Error al cargar los datos de la tabla.'
                            });
                        }
                    },
                    
                    "columns": [
                        { "data": 0, "orderable": false }, // Checkbox
                        { "data": 1 }, // ID
                        { "data": 2 }, // FECHA
                        { "data": 3 }, // CANAL
                        { "data": 4 }, // RETAIL ENVIROMENT
                        { "data": 5 }, // CLIENTE
                        { "data": 6 }, // VISUAL ACCESS
                        { "data": 7 }, // SUBCATEGORY
                        { "data": 8 }, // NEW OBJETIVO
                        { "data": 9 }, // FECHA MODIFICACIÓN
                        { "data": 10, "orderable": false } // ACCIONES
                    ],
                    
                    "scrollX": true,
                    "lengthMenu": [10, 25, 50, 75, 100],
                    "responsive": true,
                    "dom": 'lBfrtip',
                    "buttons": [
                        {
                            text: 'Excel (Todos los registros)',
                            action: function (e, dt, node, config) {
                                exportarTodosLosRegistrosObjetivo();
                            }
                        },
                        'copy', 'csv', 'pdf', 'print'
                    ],
                    "order": [[ 1, "asc" ]],
                    
                    "initComplete": function(settings, json) {
                        console.log("DataTables Objetivo SOS inicializado");
                        
                        // Evento Checkbox Individual
                        $('#table').off('change', '.select-row').on('change', '.select-row', function() {
                            actualizarContadorSeleccionados();
                            
                            var total = $('.select-row').length;
                            var checked = $('.select-row:checked').length;
                            $('#selectAll').prop('checked', total === checked && total > 0);
                        });

                        // Evento Checkbox Principal
                        $('#selectAll').off('click').on('click', function() {
                            var isChecked = $(this).prop('checked');
                            tableObjetivo.rows({ page: 'current' }).nodes().to$().find('.select-row').prop('checked', isChecked);
                            actualizarContadorSeleccionados();
                        });
                        
                        // ⭐ Botón de edición - Cargar datos por AJAX
                        $('#table').off('click', '.btn-editar').on('click', '.btn-editar', function() {
                            const id_registro = $(this).data('id');
                            
                            console.log(`Cargando registro con ID: ${id_registro}`);
                            
                            $('#formEditar')[0].reset();
                            
                            $.ajax({
                                url: 'getters/get_objetivo_sos_by_id.php',
                                type: 'POST',
                                dataType: 'json',
                                data: { id: id_registro },
                                success: function(response) {
                                    if (response.status === 'success') {
                                        const data = response.data;
                                        
                                        console.log('Datos recibidos:', data);
                                        
                                        // Llenar formulario
                                        $('#edit_id').val(data.id);
                                        $('#edit_fecha').val(data.fecha);
                                        $('#edit_canal').val(data.canal);
                                        $('#edit_retail_enviroment').val(data.retail_enviroment);
                                        $('#edit_cliente').val(data.cliente);
                                        $('#edit_visual_access').val(data.visual_access);
                                        $('#edit_subcategory').val(data.subcategory);
                                        $('#edit_new_objetivo').val(data.new_objetivo);
                                        
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
                                    console.error("Error AJAX:", error);
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

            // ⭐ FUNCIÓN: Exportar todos los registros
            function exportarTodosLosRegistrosObjetivo() {
                $("#loading").css("display", "flex");
                
                const form = $('<form>', {
                    method: 'POST',
                    action: 'getters/export_objetivo_sos_excel.php',
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

            // ⭐ FUNCIÓN: Actualizar contador
            function actualizarContadorSeleccionados() {
                var seleccionados = $('.select-row:checked').length;
                $('#contadorSeleccionados').text(seleccionados);
                
                if (seleccionados > 0) {
                    $('#btnEliminarSeleccionados').show();
                } else {
                    $('#btnEliminarSeleccionados').hide();
                }
            }

            // ⭐ ELIMINAR SELECCIONADOS (CON PROCESAMIENTO POR LOTES)
            $('#btnEliminarSeleccionados').off('click').on('click', function() {
                var idsSeleccionados = [];
                $('.select-row:checked').each(function() {
                    idsSeleccionados.push($(this).val());
                });
                
                if (idsSeleccionados.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Atención',
                        text: 'Por favor, seleccione al menos un Objetivo SOS para eliminar.'
                    });
                    return;
                }
                
                Swal.fire({
                    title: '¿Está seguro?',
                    html: 'Está a punto de eliminar <strong>' + idsSeleccionados.length + '</strong> Objetivo(s) SOS.<br>Esta acción no se puede deshacer.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        eliminarPorLotesObjetivo(idsSeleccionados);
                    }
                });
            });

            // ⭐ FUNCIÓN: Eliminar por lotes (evita error 413)
            function eliminarPorLotesObjetivo(idsSeleccionados) {
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
                        
                        tableObjetivo.ajax.reload();
                        return;
                    }
                    
                    const loteActual = chunks[index];
                    
                    $.ajax({
                        type: "POST",
                        url: "actions/delete_objetivo_sos.php",
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

            // ⭐ GUARDAR NUEVO REGISTRO
            $('#btnGuardarNuevo').off('click').on('click', function() {
                var formData = {
                    fecha: $('#add_fecha').val(),
                    canal: $('#add_canal').val(),
                    retail_enviroment: $('#add_retail_enviroment').val(),
                    cliente: $('#add_cliente').val(),
                    visual_access: $('#add_visual_access').val(),
                    subcategory: $('#add_subcategory').val(),
                    new_objetivo: $('#add_new_objetivo').val()
                };

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
                    url: "actions/insert_objetivo_sos.php",
                    data: formData,
                    dataType: 'json',
                    success: function(result) {
                        $("#loading").css("display", "none");
                        
                        if (result.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Éxito!',
                                text: result.message || 'Objetivo SOS agregado correctamente.',
                                timer: 2000,
                                showConfirmButton: false
                            });
                            $('#modalAgregar').modal('hide');
                            $('#formAgregar')[0].reset();
                            tableObjetivo.ajax.reload(); // ⭐ Recargar tabla
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: result.message || 'Error al insertar el Objetivo SOS.'
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

            // ⭐ GUARDAR CAMBIOS (EDITAR)
            $('#btnGuardarCambios').off('click').on('click', function() {
                var formData = {
                    id: $('#edit_id').val(),
                    fecha: $('#edit_fecha').val(),
                    canal: $('#edit_canal').val(),
                    retail_enviroment: $('#edit_retail_enviroment').val(),
                    cliente: $('#edit_cliente').val(),
                    visual_access: $('#edit_visual_access').val(),
                    subcategory: $('#edit_subcategory').val(),
                    new_objetivo: $('#edit_new_objetivo').val()
                };

                if (Object.values(formData).some(x => x === null || x === '' && x !== formData.id)) {
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
                    url: "actions/update_objetivo_sos.php",
                    data: formData,
                    dataType: 'json',
                    success: function(result) {
                        $("#loading").css("display", "none");
                        
                        if (result.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Éxito!',
                                text: 'Objetivo SOS actualizado correctamente',
                                timer: 2000,
                                showConfirmButton: false
                            });
                            $('#modalEditar').modal('hide');
                            tableObjetivo.ajax.reload(); // ⭐ Recargar tabla
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: result.message || 'Error al actualizar el Objetivo SOS.'
                            });
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        $("#loading").css("display", "none");
                        
                        console.error('Error AJAX:', textStatus, errorThrown);
                        console.error('Respuesta del servidor:', jqXHR.responseText);
                        
                        let errorMessage = 'Error en la conexión.';
                        if (textStatus === 'error' && errorThrown === 'Not Found') {
                            errorMessage = 'ERROR 404: Archivo no encontrado.';
                        } else if (jqXHR.responseText && jqXHR.responseText.includes('Fatal error')) {
                            errorMessage = 'ERROR 500: Error de PHP en el servidor.';
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de Conexión/Servidor',
                            html: errorMessage + '<br>Consulta la consola (F12) para más detalles.'
                        });
                    }
                });
            });

            // ⭐ CONFIRMACIÓN DELETE POR EXCEL
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
                    title: '¿Confirmar Eliminación?',
                    text: "Se marcarán como eliminados los registros del archivo por lotes.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Sí, Eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#progreso-contenedor').show();
                        $('#btnConfirmDeleteExcel').prop('disabled', true);
                        
                        // Leemos el archivo según su extensión
                        if (extension === 'csv') {
                            procesarCSV(file);
                        } else {
                            procesarExcel(file);
                        }
                    }
                });
            });

            function procesarCSV(file) {
                Papa.parse(file, {
                    skipEmptyLines: true,
                    complete: function(results) {
                        let ids = results.data.slice(1).map(row => row[0]).filter(id => id && !isNaN(id));
                        enviarLotesAsync(ids);
                    }
                });
            }

            function procesarExcel(file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var data = new Uint8Array(e.target.result);
                    var workbook = XLSX.read(data, { type: 'array' });
                    var sheet = workbook.Sheets[workbook.SheetNames[0]];
                    var json = XLSX.utils.sheet_to_json(sheet, { header: 1 });
                    
                    let ids = json.slice(1).map(row => row[0]).filter(id => id && !isNaN(id));
                    enviarLotesAsync(ids);
                };
                reader.readAsArrayBuffer(file);
            }

            async function enviarLotesAsync(ids) {
                const tamañoLote = 1000;
                const total = ids.length;
                let procesados = 0;

                for (let i = 0; i < total; i += tamañoLote) {
                    let lote = ids.slice(i, i + tamañoLote);
                    
                    try {
                        let respuesta = await $.ajax({
                            type: "POST",
                            url: "actions/delete_objetivo_sos.php", // Tu archivo PHP de destino
                            data: { ids: lote },
                            dataType: 'json'
                        });

                        if (respuesta.success) {
                            procesados += lote.length;
                            let porcentaje = Math.min(Math.round((procesados / total) * 100), 100);
                            $('#barra-progreso').css('width', porcentaje + '%').text(porcentaje + '%');
                            $('#progreso-texto').text(`Procesando: ${procesados.toLocaleString()} de ${total.toLocaleString()} IDs`);
                        }
                    } catch (error) {
                        console.error("Error en lote:", error);
                    }
                }

                Swal.fire('¡Éxito!', `Se procesaron ${procesados.toLocaleString()} registros correctamente.`, 'success')
                    .then(() => location.reload());
            }
        });
    </script>
</body>

</html>  