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

// ⭐ IMPORTACIÓN TRADICIONAL (Mantener para compatibilidad)
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
                    if (!softDeleteProductosByIds($db, $idsUnicos)) { 
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
                        if (!softDeleteProductosByIds($db, $idsUnicos)) { 
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
            if (!softDeleteProductosByIds($db, $idsUnicos)) {
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

function softDeleteProductosByIds($db, $ids)
{
    if (empty($ids)) {
        return false;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $paramType = str_repeat('i', count($ids));
    $query = "UPDATE tb_productos SET status = 0 WHERE id IN ($placeholders)";
    $updateResult = $db->execute($query, $paramType, $ids);
    return $updateResult !== false;
}

function procesarLote($db, $lote, $conn)
{
    // ⭐ Asegurar UTF-8 antes de insertar
    if ($conn instanceof mysqli) {
        $conn->set_charset("utf8mb4");
    }
    
    $sqlInsert = "INSERT INTO tb_productos (
        pais, fabricante, categoria, subcategoria, abreviatura_subcategoria, marca, 
        codigo_ean_pais, familia_segmento, descripcion_producto, propio_competencia, 
        descripcion_producto_homologado, gramaje_tamanio
    ) VALUES ";

    $paramType = "";
    $paramArray = array();
    $registrosValidos = 0;

    foreach ($lote as $index => $fila) {
        // Validación: si toda la fila viene vacía, saltar
        $hayDato = false;
        for ($i = 0; $i <= 11; $i++) {
            if (isset($fila[$i]) && trim($fila[$i]) !== "" && strtoupper(trim($fila[$i])) !== "NULL") {
                $hayDato = true;
                break;
            }
        }
        if (!$hayDato) continue;

        $sqlInsert .= "(?,?,?,?,?,?,?,?,?,?,?,?),";
        $paramType .= "ssssssisssss"; 

        // 0 - pais (string)
        $paramArray[] = isset($fila[0]) && trim($fila[0]) !== '' ? trim($fila[0]) : "";

        // 1 - fabricante (string)
        $paramArray[] = isset($fila[1]) && trim($fila[1]) !== '' ? trim($fila[1]) : "";

        // 2 - categoria (string)
        $paramArray[] = isset($fila[2]) && trim($fila[2]) !== '' ? trim($fila[2]) : "";

        // 3 - subcategoria (string)
        $paramArray[] = isset($fila[3]) && trim($fila[3]) !== '' ? trim($fila[3]) : "";

        // 4 - abreviatura_subcategoria (string)
        $paramArray[] = isset($fila[4]) && trim($fila[4]) !== '' ? trim($fila[4]) : "";

        // 5 - marca (string)
        $paramArray[] = isset($fila[5]) && trim($fila[5]) !== '' ? trim($fila[5]) : "";

        // 6 - codigo_ean_pais (int) ⚠️ IMPORTANTE
        $paramArray[] = isset($fila[6]) && trim($fila[6]) !== '' ? intval(trim($fila[6])) : 0;

        // 7 - familia_segmento (string)
        $paramArray[] = isset($fila[7]) && trim($fila[7]) !== '' ? trim($fila[7]) : "";

        // 8 - descripcion_producto (string)
        $paramArray[] = isset($fila[8]) && trim($fila[8]) !== '' ? trim($fila[8]) : "";

        // 9 - propio_competencia (string)
        $paramArray[] = isset($fila[9]) && trim($fila[9]) !== '' ? trim($fila[9]) : "";

        // 10 - descripcion_producto_homologado (string)
        $paramArray[] = isset($fila[10]) && trim($fila[10]) !== '' ? trim($fila[10]) : "";

        // 11 - gramaje_tamanio (string)
        $paramArray[] = isset($fila[11]) && trim($fila[11]) !== '' ? trim($fila[11]) : "";

        $registrosValidos++;
    }

    if ($registrosValidos === 0) {
        return false;
    }

    $sqlInsert = rtrim($sqlInsert, ',');
    $insertId = $db->insertMultiple($sqlInsert, $paramType, $paramArray);

    return !empty($insertId) || $insertId !== false;
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
                <h3>Gestión de Productos</h3>
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
                    <strong><small style="color: #666;">La plataforma acepta encabezados - No es necesaria la columna id (se genera el id de forma automática)</small></strong>
                </div>
            </form>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">   
                        <h5 class="mb-0"><i class="material-icons" style="vertical-align: middle;">delete_sweep</i> Eliminación Masiva de Productos por ID</h5>
                    </div>
                    <div class="card-body">
                        <form class="form-horizontal" id="frmExcelDelete" enctype="multipart/form-data">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <label class="font-weight-bold">Seleccionar archivo (CSV)</label>
                                    <input type="file" name="file_delete" id="file_delete" class="form-control" accept=".csv" required>
                                    <small class="text-muted d-block mt-1">
                                        * El archivo debe tener el <b>ID del Producto</b> en la primera columna.
                                    </small>
                                </div>
                                <div class="col-md-6 text-right">
                                    <button type="button" id="btnConfirmDeleteExcel" class="btn btn-danger btn-lg">
                                        <i class="material-icons" style="vertical-align: middle;">check_circle</i> Proceder con la Eliminación
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
            <div class="col-xl-3 col-lg-4 mb-3">
                <label for="filtro_subcategoria">Filtrar por Subcategoría:</label>
                <select id="filtro_subcategoria" class="form-control" multiple>
                </select>
            </div>


            <div class="col-xl-12 col-lg-12 mb-4">
                <button type="button" class="btn btn-success mb-3" data-toggle="modal" data-target="#modalAgregar">
                    <i class="material-icons" style="font-size: 1.2rem;">add</i> Agregar Nuevo Producto
                </button>
                <button type="button" class="btn btn-danger mb-3 ml-2" id="btnEliminarSeleccionados" style="display:none;">
                    <i class="material-icons" style="font-size: 1.2rem;">delete</i> Eliminar Seleccionados (<span id="contadorSeleccionados">0</span>)
                </button>
                <div class="bg-white rounded-lg p-5 shadow" id="data-result" name="data-result"></div>
            </div>
        </div>
    </div>

    <!-- Modal para editar registro -->
    <div class="modal fade" id="modalEditar" tabindex="-1" role="dialog" aria-labelledby="modalEditarLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarLabel">Editar Producto</h5>
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
                                    <label for="edit_pais">País</label>
                                    <input type="text" class="form-control" id="edit_pais" name="edit_pais">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_fabricante">Fabricante</label>
                                    <input type="text" class="form-control" id="edit_fabricante" name="edit_fabricante">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_categoria">Categoría</label>
                                    <input type="text" class="form-control" id="edit_categoria" name="edit_categoria">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_subcategoria">Subcategoría</label>
                                    <input type="text" class="form-control" id="edit_subcategoria" name="edit_subcategoria">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_abreviatura">Abreviatura Subcategoría</label>
                                    <input type="text" class="form-control" id="edit_abreviatura" name="edit_abreviatura">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_marca">Marca</label>
                                    <input type="text" class="form-control" id="edit_marca" name="edit_marca">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_ean">Código EAN País</label>
                                    <input type="number" class="form-control" id="edit_ean" name="edit_ean">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_familia">Familia Segmento</label>
                                    <input type="text" class="form-control" id="edit_familia" name="edit_familia">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_descripcion">Descripción Producto</label>
                            <textarea class="form-control" id="edit_descripcion" name="edit_descripcion" rows="2"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_propio">Propio Competencia</label>
                            <input type="text" class="form-control" id="edit_propio" name="edit_propio">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_homologado">Descripción Producto Homologado</label>
                            <textarea class="form-control" id="edit_homologado" name="edit_homologado" rows="2"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_gramaje">Gramaje/Tamaño</label>
                            <input type="text" class="form-control" id="edit_gramaje" name="edit_gramaje">
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

    <!-- Modal para agregar nuevo registro -->
    <div class="modal fade" id="modalAgregar" tabindex="-1" role="dialog" aria-labelledby="modalAgregarLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAgregarLabel">Agregar Nuevo Producto</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formAgregar">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="add_pais">País</label>
                                    <input type="text" class="form-control" id="add_pais" name="add_pais" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="add_fabricante">Fabricante</label>
                                    <input type="text" class="form-control" id="add_fabricante" name="add_fabricante" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="add_categoria">Categoría</label>
                                    <input type="text" class="form-control" id="add_categoria" name="add_categoria" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="add_subcategoria">Subcategoría</label>
                                    <input type="text" class="form-control" id="add_subcategoria" name="add_subcategoria" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="add_abreviatura">Abreviatura Subcategoría</label>
                                    <input type="text" class="form-control" id="add_abreviatura" name="add_abreviatura" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="add_marca">Marca</label>
                                    <input type="text" class="form-control" id="add_marca" name="add_marca" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="add_ean">Código EAN País</label>
                                    <input type="number" class="form-control" id="add_ean" name="add_ean" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="add_familia">Familia Segmento</label>
                                    <input type="text" class="form-control" id="add_familia" name="add_familia" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="add_descripcion">Descripción Producto</label>
                            <textarea class="form-control" id="add_descripcion" name="add_descripcion" rows="2" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="add_propio">Propio Competencia</label>
                            <input type="text" class="form-control" id="add_propio" name="add_propio" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="add_homologado">Descripción Producto Homologado</label>
                            <textarea class="form-control" id="add_homologado" name="add_homologado" rows="2" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="add_gramaje">Gramaje/Tamaño</label>
                            <input type="text" class="form-control" id="add_gramaje" name="add_gramaje" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="btnGuardarNuevo">Guardar Producto</button>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.3.0/papaparse.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>

<script>
        $(document).ready(function() {

            cargarSubcategorias();
            cargarTabla();

            // 🔥 Inicializar Select2 cuando ya existan las opciones
            setTimeout(() => {
                $("#filtro_subcategoria").select2({
                    placeholder: "Seleccionar subcategorías",
                    allowClear: true,
                    width: '100%'
                });
            }, 300);

            // 🔥 Recargar tabla cuando cambie la subcategoría
            $("#filtro_subcategoria").on("change", function () {
                cargarTabla();
            });

        });

        function cargarTabla() {
            let subcategoriaSeleccionada = $("#filtro_subcategoria").val(); 

            if ($.fn.DataTable.isDataTable('#table')) {
                $('#table').DataTable().destroy();
                $("#data-result").empty();
            }

            // Inyectamos la estructura necesaria
            $("#data-result").html(`
                <table id="table" class="table table-striped table-bordered" style="width:100%">
                    <thead class="thead-light">
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>ID</th>
                            <th>PAÍS</th>
                            <th>FABRICANTE</th>
                            <th>CATEGORÍA</th>
                            <th>SUBCATEGORÍA</th>
                            <th>ABREVIATURA</th>
                            <th>MARCA</th>
                            <th>EAN</th>
                            <th>FAMILIA</th>
                            <th>DESCRIPCIÓN</th>
                            <th>PROPIO/COMP.</th>
                            <th>HOMOLOGADO</th>
                            <th>GRAMAJE</th>
                            <th>MODIFICADO</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            `);

            $('#table').DataTable({
                "processing": true,
                "serverSide": true,
                "scrollX": true,
                "ajax": {
                    "url": "getters/get_table_productos_nuevo.php",
                    "type": "POST",
                    "data": function(d) {
                        d.subcategoria_filtro = subcategoriaSeleccionada;
                    },
                    "dataSrc": function(json) {
                        $("#loading").hide(); // Cerramos el loading al recibir datos
                        return json.data;
                    }
                },
                "columns": [
                    { "data": 0, "orderable": false }, { "data": 1 }, { "data": 2 }, { "data": 3 },
                    { "data": 4 }, { "data": 5 }, { "data": 6 }, { "data": 7 },
                    { "data": 8 }, { "data": 9 }, { "data": 10 }, { "data": 11 },
                    { "data": 12 }, { "data": 13 }, { "data": 14 }, { "data": 15, "orderable": false }
                ],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
                },
                "dom": 'lBftpi',
                "buttons": [
                    'copy', 
                    'csv', 
                    {
                        "extend": 'excel',
                        "text": 'Excel',
                        "action": function ( e, dt, node, config ) {
                            // Personalizamos la acción para que descargue TODO lo filtrado
                            let search_value = dt.search();
                            let sub_filtro = $("#filtro_subcategoria").val();

                            let form = $('<form>', {
                                action: 'getters/export_productos_excel.php',
                                method: 'POST'
                            }).append($('<input>', {
                                type: 'hidden',
                                name: 'search_value',
                                value: search_value
                            })).append($('<input>', {
                                type: 'hidden',
                                name: 'subcategoria_filtro',
                                value: JSON.stringify(sub_filtro)
                            }));

                            $('body').append(form);
                            form.submit();
                            form.remove();
                        }
                    },
                    'pdf', 
                    'print'
                ],
                "drawCallback": function() {
                    vincularEventosTabla();
                }
            });
        }

        // Nueva función para separar los eventos y que no se pierdan al paginar
        function vincularEventosTabla() {
            $('#selectAll').off().on('click', function() {
                $('.select-row').prop('checked', $(this).prop('checked'));
                actualizarContadorSeleccionados();
            });

            $(document).on('change', '.select-row', function() {
                actualizarContadorSeleccionados();
            });

            // Delegación de eventos para el botón editar (importante en Server-Side)
            $('#table').off('click', '.btn-editar').on('click', '.btn-editar', function() {
                const btn = $(this);
                $('#edit_id').val(btn.data('id'));
                $('#edit_pais').val(btn.data('pais'));
                $('#edit_fabricante').val(btn.data('fabricante'));
                $('#edit_categoria').val(btn.data('categoria'));
                $('#edit_subcategoria').val(btn.data('subcategoria'));
                $('#edit_abreviatura').val(btn.data('abreviatura'));
                $('#edit_marca').val(btn.data('marca'));
                $('#edit_ean').val(btn.data('ean'));
                $('#edit_familia').val(btn.data('familia'));
                $('#edit_descripcion').val(btn.data('descripcion'));
                $('#edit_propio').val(btn.data('propio'));
                $('#edit_homologado').val(btn.data('homologado'));
                $('#edit_gramaje').val(btn.data('gramaje'));
                $('#modalEditar').modal('show');
            });
        }

        function cargarSubcategorias() {
            $.ajax({
                url: "getters/get_subcategorias_productos.php",
                type: "POST",
                success: function(respuesta) {
                    let data = JSON.parse(respuesta);
                    let select = $("#filtro_subcategoria");

                    data.forEach(sub => {
                        select.append(`<option value="${sub}">${sub}</option>`);
                    });
                }
            });
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
        
        // Eliminar registros seleccionados
        $('#btnEliminarSeleccionados').on('click', function() {
            var idsSeleccionados = [];
            $('.select-row:checked').each(function() {
                idsSeleccionados.push($(this).val());
            });
            
            console.log('IDs seleccionados para eliminar:', idsSeleccionados);
            
            if (idsSeleccionados.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: 'Por favor, seleccione al menos un registro para eliminar.'
                });
                return;
            }
            
            // Confirmación con SweetAlert
            Swal.fire({
                title: '¿Está seguro?',
                html: 'Está a punto de eliminar <strong>' + idsSeleccionados.length + '</strong> registro(s).<br>Esta acción no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, continuar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $("#loading").css("display", "flex");
                    
                    $.ajax({
                        type: "POST",
                        url: "actions/delete_productos.php",
                        data: { ids: idsSeleccionados },
                        dataType: 'json',
                        success: function(result) {
                            $("#loading").css("display", "none");
                            console.log('Respuesta del servidor (eliminar):', result);
                            
                            if (result.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: '¡Eliminado!',
                                    text: result.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                cargarTabla();
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
                            console.error('Error AJAX (eliminar):', jqXHR.responseText);
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

        // Guardar nuevo registro
        $('#btnGuardarNuevo').on('click', function() {
            var formData = {
                pais: $('#add_pais').val(),
                fabricante: $('#add_fabricante').val(),
                categoria: $('#add_categoria').val(),
                subcategoria: $('#add_subcategoria').val(),
                abreviatura_subcategoria: $('#add_abreviatura').val(),
                marca: $('#add_marca').val(),
                codigo_ean_pais: $('#add_ean').val(),
                familia_segmento: $('#add_familia').val(),
                descripcion_producto: $('#add_descripcion').val(),
                propio_competencia: $('#add_propio').val(),
                descripcion_producto_homologado: $('#add_homologado').val(),
                gramaje_tamanio: $('#add_gramaje').val()
            };
            
            if (Object.values(formData).some(x => x === null || x === '')) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: 'Por favor, complete todos los campos requeridos.',
                });
                return;
            }

            $("#loading").css("display", "flex");
            
            $.ajax({
                type: "POST",
                url: "actions/insert_productos.php",
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
                        cargarTabla();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: result.message || 'Error desconocido al insertar el registro.'
                        });
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    $("#loading").css("display", "none");
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de Conexión',
                        text: 'No se pudo contactar al servidor: ' + textStatus + ' - ' + errorThrown
                    });
                }
            });
        });

        // Guardar cambios del modal de edición
        $('#btnGuardarCambios').on('click', function() {
            var formData = {
                id: $('#edit_id').val(),
                pais: $('#edit_pais').val(),
                fabricante: $('#edit_fabricante').val(),
                categoria: $('#edit_categoria').val(),
                subcategoria: $('#edit_subcategoria').val(),
                abreviatura_subcategoria: $('#edit_abreviatura').val(),
                marca: $('#edit_marca').val(),
                codigo_ean_pais: $('#edit_ean').val(),
                familia_segmento: $('#edit_familia').val(),
                descripcion_producto: $('#edit_descripcion').val(),
                propio_competencia: $('#edit_propio').val(),
                descripcion_producto_homologado: $('#edit_homologado').val(),
                gramaje_tamanio: $('#edit_gramaje').val()
            };
            
            console.log('DEBUG: Datos de edición a enviar:', formData);
            const urlDestino = "actions/update_productos.php";
            console.log('DEBUG: URL de destino del AJAX:', urlDestino);
            
            $("#loading").css("display", "flex");
            
            $.ajax({
                type: "POST",
                url: urlDestino,
                data: formData,
                dataType: 'json',
                success: function(result) {
                    $("#loading").css("display", "none");
                    console.log('DEBUG: Respuesta SUCCESS (JSON):', result);
                    
                    if (result.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: 'Registro actualizado correctamente',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        $('#modalEditar').modal('hide');
                        cargarTabla();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: result.message || 'Error al actualizar el registro. Mensaje no especificado.'
                        });
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    $("#loading").css("display", "none");
                    console.error('DEBUG: Error AJAX (textStatus):', textStatus);
                    console.error('DEBUG: Error AJAX (errorThrown):', errorThrown);
                    console.error('DEBUG: Respuesta del Servidor (HTML/Error 404, 500):', jqXHR.responseText);
                    
                    let errorMessage = 'Error en la conexión. ';
                    if (textStatus === 'error' && errorThrown === 'Not Found') {
                        errorMessage = 'ERROR 404: Archivo ' + urlDestino + ' no encontrado. Revisa la ruta en la consola (pestaña Network).';
                    } else if (jqXHR.responseText && jqXHR.responseText.includes('Fatal error')) {
                        errorMessage = 'ERROR 500: Fallo de PHP en el servidor (Fatal Error).';
                    } else {
                        errorMessage += 'Detalles: ' + errorThrown;
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de Conexión/Servidor',
                        html: errorMessage + '<br>Consulta la consola (F12) para más detalles técnicos.',
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
                text: "Esta acción marcará como eliminados (status=0) los productos del archivo.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Sí, Eliminar',
                cancelButtonText: 'Cancelar'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    $('#progreso-contenedor').show();
                    $('#btnConfirmDeleteExcel').prop('disabled', true);

                    if (extension === 'csv') {
                        procesarCSVDelete(file);
                    } else if (extension === 'xlsx' || extension === 'xls') {
                        procesarExcelDelete(file);
                    } else {
                        Swal.fire('Error', 'Formato no soportado.', 'error');
                        $('#progreso-contenedor').hide();
                        $('#btnConfirmDeleteExcel').prop('disabled', false);
                    }
                }
            });
        });

        async function enviarIdsEnLotesProductos(ids) {
            const tamañoLote = 500;
            const total = ids.length;
            let procesados = 0;

            for (let i = 0; i < total; i += tamañoLote) {
                let lote = ids.slice(i, i + tamañoLote);
                
                try {
                    let respuesta = await $.ajax({
                        type: "POST",
                        url: "actions/delete_productos.php", // Tu archivo de acción
                        data: { ids: lote },
                        dataType: 'json'
                    });

                    if (respuesta.success) {
                        procesados += lote.length;
                        let porcentaje = Math.round((procesados / total) * 100);
                        $('#barra-progreso').css('width', porcentaje + '%').text(porcentaje + '%');
                        $('#progreso-texto').text(`Procesando: ${porcentaje}% (${procesados} de ${total})`);
                    }
                } catch (error) {
                    console.error("Error en lote:", error);
                }
            }

            Swal.fire('¡Completado!', `Se procesaron ${procesados} productos.`, 'success').then(() => {
                location.reload(); // Recargar para ver los cambios
            });
        }

        // 1. MEJORA EN PROCESAR CSV
        function procesarCSVDelete(file) {
            console.log("Iniciando lectura de CSV...");
            Papa.parse(file, {
                skipEmptyLines: true,
                dynamicTyping: true, // Convierte números automáticamente
                header: false,      // Leemos por índice de columna
                complete: function(results) {
                    console.log("Datos capturados:", results.data);
                    
                    // Filtramos los IDs: 
                    // - Saltamos la primera fila (encabezado) con slice(1)
                    // - Tomamos la columna 0
                    // - Filtramos que sea un número válido
                    let ids = results.data.slice(1)
                        .map(row => row[0])
                        .filter(id => id !== null && id !== undefined && id !== "" && !isNaN(id));

                    console.log("IDs finales a procesar:", ids);

                    if (ids.length === 0) {
                        Swal.fire('Error', 'No se encontraron IDs válidos en la primera columna del CSV. Asegúrate de que el ID esté en la Columna A.', 'error');
                        $('#progreso-contenedor').hide();
                        $('#btnConfirmDeleteExcel').prop('disabled', false);
                        return;
                    }
                    enviarIdsEnLotesProductos(ids);
                },
                error: function(err) {
                    console.error("Error al parsear CSV:", err);
                    Swal.fire('Error', 'No se pudo leer el archivo CSV.', 'error');
                }
            });
        }

        // 2. MEJORA EN PROCESAR EXCEL
        function procesarExcelDelete(file) {
            console.log("Iniciando lectura de Excel...");
            var reader = new FileReader();
            reader.onload = function(e) {
                try {
                    var data = new Uint8Array(e.target.result);
                    var workbook = XLSX.read(data, { type: 'array' });
                    var firstSheetName = workbook.SheetNames[0];
                    var worksheet = workbook.Sheets[firstSheetName];
                    
                    // Convertimos a JSON (array de arrays)
                    var json = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
                    console.log("Filas del Excel:", json);

                    // Filtrado igual al del CSV
                    let ids = json.slice(1)
                        .map(row => row[0])
                        .filter(id => id !== null && id !== undefined && id !== "" && !isNaN(id));

                    console.log("IDs extraídos del Excel:", ids);

                    if (ids.length === 0) {
                        Swal.fire('Error', 'El Excel parece estar vacío o no tiene IDs en la primera columna.', 'error');
                        $('#progreso-contenedor').hide();
                        $('#btnConfirmDeleteExcel').prop('disabled', false);
                        return;
                    }
                    enviarIdsEnLotesProductos(ids);
                } catch (err) {
                    console.error("Error procesando Excel:", err);
                    Swal.fire('Error', 'El archivo Excel tiene un formato inválido.', 'error');
                }
            };
            reader.readAsArrayBuffer(file);
        }

        // Función para el botón de exportar (fuera del DataTable)
        $('#btnExportarExcelPersonalizado').on('click', function() {
            let search_value = $('#table').DataTable().search();
            let subcategoria_filtro = $("#filtro_subcategoria").val();

            // Crear un formulario temporal para hacer el POST y descargar el archivo
            let form = $('<form>', {
                action: 'getters/export_productos_excel.php',
                method: 'POST'
            }).append($('<input>', {
                type: 'hidden',
                name: 'search_value',
                value: search_value
            })).append($('<input>', {
                type: 'hidden',
                name: 'subcategoria_filtro',
                value: JSON.stringify(subcategoria_filtro)
            }));

            $('body').append(form);
            form.submit();
            form.remove();
        });
                
    </script>
</body>

</html>