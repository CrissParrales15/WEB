<?php

use Phppot\DataSource;

require_once 'DataSource.php';
$db = new DataSource();
$conn = $db->getConnection();

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


if (isset($_GET['import_status']) && $_GET['import_status'] === 'success') {
    $type = "success";
    $message = "La importación se completó correctamente.";
}

if (isset($_POST["delete_by_excel"])) {

    $fileName = $_FILES["file_delete"]["tmp_name"];
    $fileExtension = strtolower(pathinfo($_FILES["file_delete"]["name"], PATHINFO_EXTENSION));
    
    $idsParaEliminar = array();

    if ($_FILES["file_delete"]["size"] > 0) {
        
        if ($fileExtension === 'csv') {
            $file = fopen($fileName, "r");
            $primeraFila = true;

            while (($column = fgetcsv($file, 1000, ";")) !== FALSE) {
                if ($primeraFila) {
                    $primeraFila = false;
                    continue;
                }
                
                //el ID está en la primera columna (debería)
                if (isset($column[0]) && is_numeric(trim($column[0]))) {
                    $idsParaEliminar[] = intval(trim($column[0]));
                }
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
                    
                    if (isset($row[0]) && is_numeric(trim($row[0]))) {
                        $idsParaEliminar[] = intval(trim($row[0]));
                    }
                }
            } else {
                $type = "error";
                $message = "Error al leer el archivo Excel: " . SimpleXLSX::parseError();
            }
        }
        
        if (!empty($idsParaEliminar)) {
            $idsUnicos = array_unique($idsParaEliminar);
            $batch_size = 500;
            $exitoGlobal = true;
            
            foreach (array_chunk($idsUnicos, $batch_size) as $batch) {
                if (!softDeleteProductosByIds($db, $batch)) {
                    $exitoGlobal = false;
                    break;
                }
            }

            if ($exitoGlobal) {
                $type = "success";
                $message = "Borrado completado correctamente. Se procesaron " . count($idsUnicos) . " IDs únicos.";
            } else {
                $type = "error";
                $message = "Ocurrió un error durante el borrado masivo.";
            }
        } else {
            $type = "warning";
            $message = "No se encontraron IDs de producto válidos para eliminar en el archivo.";
        }
    } else {
        $type = "error";
        $message = "El archivo está vacío o no se subió correctamente.";
    }

    if (isset($type) && $type === "success") {
        header("Location: " . $_SERVER['PHP_SELF'] . "?delete_status=success&count=" . (count($idsUnicos) ?? 0));
        exit;
    } else if (isset($type)) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?delete_status={$type}&message=" . urlencode($message));
        exit;
    }
}

if (isset($_GET['delete_status']) && $_GET['delete_status'] === 'success') {
    $count = isset($_GET['count']) ? (int)$_GET['count'] : 0;
    $type = "success";
    $message = "El borrado masivo se completó correctamente. Se eliminaron $count registros.";
} elseif (isset($_GET['delete_status']) && ($_GET['delete_status'] === 'error' || $_GET['delete_status'] === 'warning')) {
    $type = $_GET['delete_status'];
    $message = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : 'Ocurrió un error al intentar la eliminación masiva.';
}



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
    $sqlInsert = "INSERT INTO tb_productos (pais, fabricante, categoria, subcategoria, abreviatura_subcategoria, marca, codigo_ean_pais, familia_segmento, descripcion_producto, propio_competencia, descripcion_producto_homologado, gramaje_tamanio) VALUES ";
    $paramType = "";
    $paramArray = array();
    $registrosValidos = 0;

    foreach ($lote as $index => $fila) {
        $hayDato = false;
        for ($i = 0; $i <= 11; $i++) {
            if (isset($fila[$i]) && trim($fila[$i]) !== "" && strtoupper(trim($fila[$i])) !== "NULL") { 
                $hayDato = true; 
                break; 
            }
        }
        
        if (!$hayDato) {
            continue;
        }

        $sqlInsert .= "(?,?,?,?,?,?,?,?,?,?,?,?),";
        $paramType .= "ssssssisssss";

        $paramArray[] = (isset($fila[0]) && trim($fila[0]) !== '') ? trim(mysqli_real_escape_string($conn, $fila[0])) : ""; // pais
        $paramArray[] = (isset($fila[1]) && trim($fila[1]) !== '') ? trim(mysqli_real_escape_string($conn, $fila[1])) : ""; // fabricante
        $paramArray[] = (isset($fila[2]) && trim($fila[2]) !== '') ? trim(mysqli_real_escape_string($conn, $fila[2])) : ""; // categoria
        $paramArray[] = (isset($fila[3]) && trim($fila[3]) !== '') ? trim(mysqli_real_escape_string($conn, $fila[3])) : ""; // subcategoria
        $paramArray[] = (isset($fila[4]) && trim($fila[4]) !== '') ? trim(mysqli_real_escape_string($conn, $fila[4])) : ""; // abreviatura_subcategoria
        $paramArray[] = (isset($fila[5]) && trim($fila[5]) !== '') ? trim(mysqli_real_escape_string($conn, $fila[5])) : ""; // marca
        $paramArray[] = (isset($fila[6]) && trim($fila[6]) !== '') ? intval(trim($fila[6])) : 0; // codigo_ean_pais (INT)
        $paramArray[] = (isset($fila[7]) && trim($fila[7]) !== '') ? trim(mysqli_real_escape_string($conn, $fila[7])) : ""; // familia_segmento
        $paramArray[] = (isset($fila[8]) && trim($fila[8]) !== '') ? trim(mysqli_real_escape_string($conn, $fila[8])) : ""; // descripcion_producto
        $paramArray[] = (isset($fila[9]) && trim($fila[9]) !== '') ? trim(mysqli_real_escape_string($conn, $fila[9])) : ""; // propio_competencia
        $paramArray[] = (isset($fila[10]) && trim($fila[10]) !== '') ? trim(mysqli_real_escape_string($conn, $fila[10])) : ""; // descripcion_producto_homologado
        $paramArray[] = (isset($fila[11]) && trim($fila[11]) !== '') ? trim(mysqli_real_escape_string($conn, $fila[11])) : ""; // gramaje_tamanio
        
        $registrosValidos++;
    }

    if ($registrosValidos === 0) {
        return false;
    }

    // Eliminar la coma final
    $sqlInsert = rtrim($sqlInsert, ',');
    
    $insertId = $db->insertMultiple($sqlInsert, $paramType, $paramArray);

    if (!empty($insertId)) {
        return true;
    } else {
        return false;
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
            $("#response").html("Archivo inválido. Sube archivos: <b>CSV o Excel (.xlsx, .xls)</b>");
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
                    <label class="col-md-4 control-label">Seleccionar archivo CSV o Excel</label> 
                    <input type="file" name="file" id="file" accept=".csv,.xlsx,.xls">
                    <button type="submit" id="submit" name="import" class="btn-submit">Import</button>
                    <br />
                    <small style="color: #666;">Formatos aceptados: CSV (separado por ;), Excel (.xlsx, .xls)</small>
                </div>
            </form>
        </div>

        <div class="row mt-4">
            <form class="form-horizontal" action="" method="post" name="frmExcelDelete" id="frmExcelDelete" enctype="multipart/form-data">
                <div class="input-row">
                    <label class="col-md-4 control-label">Eliminación Masiva por ID</label> 
                    <input type="file" name="file_delete" id="file_delete" accept=".csv,.xlsx,.xls" required>
                    
                    <input type="hidden" name="delete_by_excel" value="1"> 
                    
                    <button type="button" id="btnConfirmDeleteExcel" class="btn btn-danger">Eliminar por ID</button>
                    <br />
                    <small style="color: #666;">*Archivo requerido:* Debe contener la columna con los IDs de los registros a eliminar.</small>
                </div>
            </form>
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

        // ⬅️ AQUÍ Select2 devuelve un ARRAY automáticamente
        let subcategoriaSeleccionada = $("#filtro_subcategoria").val();  
        // Ejemplo: ["SNACK", "GOLOSINAS"]

        $.ajax({
            type: "POST",
            url: "getters/get_table_productos_nuevo.php",
            data: {
                subcategoria_filtro: subcategoriaSeleccionada   // 🔥 ENVÍA ARRAY
            },
            beforeSend: function() {
                $("#loading").css("display", "flex");
            },
            success: function(data) {
                $("#loading").css("display", "none");

                if (data != "") {
                    $("#data-result").html(data);

                    if ($.fn.DataTable.isDataTable('#table')) {
                        $('#table').DataTable().destroy();
                    }

                    $('#table').DataTable({
                        "scrollX": true,
                        lengthMenu: [10, 25, 50, 75, 100],
                        responsive: true,
                        "dom": 'lBftpi',
                        buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
                    });

                    // --------------------------
                    // EVENTOS
                    // --------------------------

                    $('#selectAll').off().on('click', function() {
                        var isChecked = $(this).prop('checked');
                        $('.select-row').prop('checked', isChecked);
                        actualizarContadorSeleccionados();
                    });

                    $('.select-row').off().on('change', function() {
                        actualizarContadorSeleccionados();

                        var total = $('.select-row').length;
                        var seleccionados = $('.select-row:checked').length;
                        $('#selectAll').prop('checked', total === seleccionados);
                    });

                    $('.btn-editar').off().on('click', function() {

                        $('#edit_id').val($(this).data('id'));
                        $('#edit_pais').val($(this).data('pais'));
                        $('#edit_fabricante').val($(this).data('fabricante'));
                        $('#edit_categoria').val($(this).data('categoria'));
                        $('#edit_subcategoria').val($(this).data('subcategoria'));
                        $('#edit_abreviatura').val($(this).data('abreviatura'));
                        $('#edit_marca').val($(this).data('marca'));
                        $('#edit_ean').val($(this).data('ean'));
                        $('#edit_familia').val($(this).data('familia'));
                        $('#edit_descripcion').val($(this).data('descripcion'));
                        $('#edit_propio').val($(this).data('propio'));
                        $('#edit_homologado').val($(this).data('homologado'));
                        $('#edit_gramaje').val($(this).data('gramaje'));

                        $('#modalEditar').modal('show');
                    });

                } else {
                    $("#data-result").html(
                        '<div class="alert alert-info">No hay datos para mostrar. Sube un archivo para comenzar.</div>'
                    );
                }
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                $("#loading").css("display", "none");
                alert("Status: " + textStatus);
                alert("Error: " + errorThrown);
            }
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
                Swal.fire('Error', 'Por favor, selecciona un archivo CSV o Excel con IDs para borrar.', 'error');
                return;
            }
            Swal.fire({
                title: '¿Confirmar Eliminación Masiva de Productos?',
                text: "Esta acción borrará todos los productos cuyo ID se encuentre en el archivo. ¿Desea continuar?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, Proceder con la Eliminación',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#loading').css("display", "flex"); 
                    $('#frmExcelDelete').submit();
                }
            });
        });
        
    </script>
</body>

</html>