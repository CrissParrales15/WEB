<?php

use Phppot\DataSource;

require_once 'DataSource.php';
$db = new DataSource();
$conn = $db->getConnection();

if ($conn instanceof mysqli) {
    $conn->set_charset("utf8mb4");
    $conn->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
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

            // Eliminar BOM si existe
            $bom = fread($file, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($file);
            }

            $primeraFila = true;

            while (($column = fgetcsv($file, 27000, ";")) !== FALSE) {
                if ($primeraFila) { $primeraFila = false; continue; }

                $column = array_map(function($cell) {
                    if ($cell === null) return '';
                    if (!mb_detect_encoding($cell, 'UTF-8', true)) {
                        return mb_convert_encoding($cell, 'UTF-8', 'Windows-1252');
                    }
                    return $cell;
                }, $column);

                $lote[] = $column;
                $contador++;
                if ($contador == $tamano_lote || feof($file)) {
                    $resultado = procesarLote($lote, $conn); // <-- solo $conn
                    $contador = 0; $lote = array();
                }
            }
            if (count($lote) > 0) {
                $resultado = procesarLote($lote, $conn);
            }
            fclose($file);

        } elseif ($fileExtension === 'xlsx' || $fileExtension === 'xls') {
            require_once 'SimpleXLSX.php';
            if ($xlsx = SimpleXLSX::parse($fileName)) {
                $rows = $xlsx->rows();
                $firstRow = true;
                foreach ($rows as $row) {
                    if ($firstRow) { $firstRow = false; continue; }
                    $lote[] = $row;
                    $contador++;
                    if ($contador == $tamano_lote) {
                        $resultado = procesarLote($lote, $conn);
                        $contador = 0; $lote = array();
                    }
                }
                if (count($lote) > 0) {
                    $resultado = procesarLote($lote, $conn);
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
            $bom = fread($file, 3);
            if ($bom !== "\xEF\xBB\xBF") { rewind($file); }
            $primeraFila = true;
            while (($column = fgetcsv($file, 27000, ";")) !== FALSE) {
                if ($primeraFila) { $primeraFila = false; continue; }
                $id_candidato = isset($column[0]) ? trim($column[0]) : '';
                if (is_numeric($id_candidato) && (int)$id_candidato > 0) {
                    $all_ids[] = (int)$id_candidato;
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


// ---------------------------------------------------------------
// procesarLote: usa $conn directamente con prepared statements
// para respetar utf8mb4 y evitar corrupción de caracteres
// ---------------------------------------------------------------
function procesarLote($lote, $conn)
{
    $sqlInsert = "INSERT INTO repositorio_ppts 
    (day, time, cp_code, trade, retail_environment, cp_format,
     customer_format_banner, distribuidor, customer, pos, province, city,
     zone, ejecutivo, category, subcategory, segment, form, manufacturer,
     brand, product, size, validation, activity, type_of_promotion,
     descuento, price_talker, mechanics, sale_price, observation,
     photo_url, period, inicio_de_promocion, fin_de_promocion,
     agotar_stock, fuente, tipo) 
    VALUES ";

    $paramType  = "";
    $paramArray = array();
    $registrosValidos = 0;

    foreach ($lote as $fila) {

        $hayDato = false;
        for ($i = 0; $i <= 36; $i++) {
            if (isset($fila[$i]) && trim($fila[$i]) !== "" && strtoupper(trim($fila[$i])) !== "NULL") {
                $hayDato = true;
                break;
            }
        }
        if (!$hayDato) continue;

        $sqlInsert .= "(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?),";
        $paramType .= "sssssssssssssssssssssssssssssssssssss"; // 37 s

        // Helper: solo trim, sin escape manual (prepared statement lo maneja)
        $f = function($i) use ($fila) {
            return trim($fila[$i] ?? "");
        };

        // day (índice 0) — convertir fecha
        $dayOriginal = $f(0);
        $dayConvertido = "";
        if (is_numeric($dayOriginal)) {
            $dayConvertido = gmdate("Y-m-d", ($dayOriginal - 25569) * 86400);
        } else {
            $partes = explode('/', $dayOriginal);
            if (count($partes) == 3) {
                $dayConvertido = $partes[2] . '-' . str_pad($partes[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($partes[0], 2, '0', STR_PAD_LEFT);
            } else {
                $ts = strtotime($dayOriginal);
                $dayConvertido = $ts ? date("Y-m-d", $ts) : null;
            }
        }

        // inicio_de_promocion (índice 32)
        $inicioOriginal = $f(32);
        $inicioConvertido = "";
        if (is_numeric($inicioOriginal)) {
            $inicioConvertido = gmdate("Y-m-d", ($inicioOriginal - 25569) * 86400);
        } else {
            $partes = explode('/', $inicioOriginal);
            if (count($partes) == 3) {
                $inicioConvertido = $partes[2] . '-' . str_pad($partes[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($partes[0], 2, '0', STR_PAD_LEFT);
            } else {
                $ts = strtotime($inicioOriginal);
                $inicioConvertido = $ts ? date("Y-m-d", $ts) : null;
            }
        }

        // fin_de_promocion (índice 33)
        $finOriginal = $f(33);
        $finConvertido = "";
        if (is_numeric($finOriginal)) {
            $finConvertido = gmdate("Y-m-d", ($finOriginal - 25569) * 86400);
        } else {
            $partes = explode('/', $finOriginal);
            if (count($partes) == 3) {
                $finConvertido = $partes[2] . '-' . str_pad($partes[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($partes[0], 2, '0', STR_PAD_LEFT);
            } else {
                $ts = strtotime($finOriginal);
                $finConvertido = $ts ? date("Y-m-d", $ts) : null;
            }
        }

        $paramArray[] = $dayConvertido;  // day
        $paramArray[] = $f(1);           // time
        $paramArray[] = $f(2);           // cp_code
        $paramArray[] = $f(3);           // trade
        $paramArray[] = $f(4);           // retail_environment
        $paramArray[] = $f(5);           // cp_format
        $paramArray[] = $f(6);           // customer_format_banner
        $paramArray[] = $f(7);           // distribuidor
        $paramArray[] = $f(8);           // customer
        $paramArray[] = $f(9);           // pos
        $paramArray[] = $f(10);          // province
        $paramArray[] = $f(11);          // city
        $paramArray[] = $f(12);          // zone
        $paramArray[] = $f(13);          // ejecutivo
        $paramArray[] = $f(14);          // category
        $paramArray[] = $f(15);          // subcategory
        $paramArray[] = $f(16);          // segment
        $paramArray[] = $f(17);          // form
        $paramArray[] = $f(18);          // manufacturer
        $paramArray[] = $f(19);          // brand
        $paramArray[] = $f(20);          // product
        $paramArray[] = $f(21);          // size
        $paramArray[] = $f(22);          // validation
        $paramArray[] = $f(23);          // activity
        $paramArray[] = $f(24);          // type_of_promotion
        $paramArray[] = $f(25);          // descuento
        $paramArray[] = $f(26);          // price_talker
        $paramArray[] = $f(27);          // mechanics
        $paramArray[] = $f(28);          // sale_price
        $paramArray[] = $f(29);          // observation
        $paramArray[] = $f(30);          // photo_url
        $paramArray[] = $f(31);          // period
        $paramArray[] = $inicioConvertido; // inicio_de_promocion
        $paramArray[] = $finConvertido;    // fin_de_promocion
        $paramArray[] = $f(34);          // agotar_stock
        $paramArray[] = $f(35);          // fuente
        $paramArray[] = $f(36); 

        $registrosValidos++;
    }

    if ($registrosValidos === 0) return false;

    $sqlInsert = rtrim($sqlInsert, ',');

    // Prepared statement directo con $conn (charset utf8mb4 ya configurado)
    if ($stmt = $conn->prepare($sqlInsert)) {
        $stmt->bind_param($paramType, ...$paramArray);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    return false;
}


function softDeleteByIds(array $ids) {
    global $conn;
    if (!isset($conn) || $conn->connect_error) {
        return ['success' => false, 'message' => 'Error de conexión a la base de datos.'];
    }

    $ids = array_filter($ids, function($id) {
        return is_numeric($id) && (int)$id > 0;
    });

    if (empty($ids)) {
        return ['success' => false, 'message' => 'IDs inválidos en el archivo.'];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $query = "UPDATE repositorio_ppts SET status = 0 WHERE id IN ($placeholders)";

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
                'success'      => true,
                'message'      => "Delete exitoso. Se actualizaron $actualizados registro(s).",
                'actualizados' => $actualizados
            ];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Error al ejecutar: ' . $error];
        }
    } else {
        return ['success' => false, 'message' => 'Error al preparar: ' . $conn->error];
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
        style="display: flex;position: fixed; width: 100%; height: 100%; top: 0px; left: 0px; z-index: 999999; overflow: auto; background-color: rgba(0, 0, 0, 0.498039);">
        <img style="position: absolute; top: 50% !important; left: 47% !important;" src="loader_1.gif">
    </div>

    <div id="content" style="width:100%;">
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container-fluid">
                <h3>Gestión de PPTs</h3> 
            </div>
        </nav>

        <div id="response" class="<?php if(!empty($type)) { echo $type . " display-block"; } ?>">
            <?php if(!empty($message)) { echo $message; } ?>
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
                        <h5 class="mb-0">Eliminación Masiva por ID (Repositorio PPTs)</h5>
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
                    <input type="date" class="form-control" id="date-start-ppts">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Hasta:</span>
                    </div>
                    <input type="date" class="form-control" id="date-end-ppts">
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button" id="clear-filter-ppts" title="Limpiar Filtro">
                            <i class="material-icons" style="font-size: 1.2rem; vertical-align: middle;">close</i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-xl-8 col-lg-6 mb-4 text-right">
                <button type="button" class="btn btn-success mb-3" data-toggle="modal" data-target="#modalAgregar">
                    <i class="material-icons" style="font-size: 1.2rem;">add</i> Agregar Nuevo Registro PPT
                </button>
                <button type="button" class="btn btn-danger mb-3 ml-2" id="btnEliminarSeleccionados" style="display:none;">
                    <i class="material-icons" style="font-size: 1.2rem;">delete</i> Eliminar Seleccionados (<span id="contadorSeleccionados">0</span>)
                </button>
            </div>

            <div class="col-xl-12 col-lg-12 mb-4">
                <div class="bg-white rounded-lg p-5 shadow">
                    <table id="table" class="table table-striped" style="width:100%">
                        <thead class="thead-light">
                            <tr>
                                <th scope="col"><input type="checkbox" id="selectAll"></th>
                                <th scope="col">ID</th>
                                <th scope="col">DAY</th>
                                <th scope="col">TIME</th>
                                <th scope="col">CP CODE</th>
                                <th scope="col">TRADE</th>
                                <th scope="col">RETAIL ENVIRONMENT</th>
                                <th scope="col">CP FORMAT</th>
                                <th scope="col">CUSTOMER FORMAT BANNER</th>
                                <th scope="col">DISTRIBUIDOR</th>
                                <th scope="col">CUSTOMER</th>
                                <th scope="col">POS</th>
                                <th scope="col">PROVINCE</th>
                                <th scope="col">CITY</th>
                                <th scope="col">ZONE</th>
                                <th scope="col">EJECUTIVO</th>
                                <th scope="col">CATEGORY</th>
                                <th scope="col">SUBCATEGORY</th>
                                <th scope="col">SEGMENT</th>
                                <th scope="col">FORM</th>
                                <th scope="col">MANUFACTURER</th>
                                <th scope="col">BRAND</th>
                                <th scope="col">PRODUCT</th>
                                <th scope="col">SIZE</th>
                                <th scope="col">VALIDATION</th>
                                <th scope="col">ACTIVITY</th>
                                <th scope="col">TYPE OF PROMOTION</th>
                                <th scope="col">DESCUENTO</th>
                                <th scope="col">PRICE TALKER</th>
                                <th scope="col">MECHANICS</th>
                                <th scope="col">SALE PRICE</th>
                                <th scope="col">OBSERVATION</th>
                                <th scope="col">PHOTO URL</th>
                                <th scope="col">PERIOD</th>
                                <th scope="col">INICIO PROMOCIÓN</th>
                                <th scope="col">FIN PROMOCIÓN</th>
                                <th scope="col">AGOTAR STOCK</th>
                                <th scope="col">FUENTE</th>
                                <th scope="col">TIPO</th>
                                <th scope="col">FECHA MODIFICACIÓN</th>
                                <th scope="col">ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- El contenido se carga automáticamente vía Server-Side -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>


    <!-- ===================== MODAL EDITAR ===================== -->
    <div class="modal fade" id="modalEditar" tabindex="-1" role="dialog" aria-labelledby="modalEditarLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarLabel">Editar Registro PPT</h5>
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
                                    <label>Day</label>
                                    <input type="date" class="form-control" id="edit_day" name="edit_day">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Time</label>
                                    <input type="text" class="form-control" id="edit_time" name="edit_time" placeholder="HH:MM:SS">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>CP Code</label>
                                    <input type="text" class="form-control" id="edit_cp_code" name="edit_cp_code">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Trade</label>
                                    <input type="text" class="form-control" id="edit_trade" name="edit_trade">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Retail Environment</label>
                                    <input type="text" class="form-control" id="edit_retail_environment" name="edit_retail_environment">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>CP Format</label>
                                    <input type="text" class="form-control" id="edit_cp_format" name="edit_cp_format">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Customer Format Banner</label>
                                    <input type="text" class="form-control" id="edit_customer_format_banner" name="edit_customer_format_banner">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Distribuidor</label>
                                    <input type="text" class="form-control" id="edit_distribuidor" name="edit_distribuidor">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Customer</label>
                                    <input type="text" class="form-control" id="edit_customer" name="edit_customer">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>POS</label>
                                    <input type="text" class="form-control" id="edit_pos" name="edit_pos">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Province</label>
                                    <input type="text" class="form-control" id="edit_province" name="edit_province">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>City</label>
                                    <input type="text" class="form-control" id="edit_city" name="edit_city">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Zone</label>
                                    <input type="text" class="form-control" id="edit_zone" name="edit_zone">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Ejecutivo</label>
                                    <input type="text" class="form-control" id="edit_ejecutivo" name="edit_ejecutivo">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Category</label>
                                    <input type="text" class="form-control" id="edit_category" name="edit_category">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Subcategory</label>
                                    <input type="text" class="form-control" id="edit_subcategory" name="edit_subcategory">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Segment</label>
                                    <input type="text" class="form-control" id="edit_segment" name="edit_segment">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Form</label>
                                    <input type="text" class="form-control" id="edit_form" name="edit_form">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Manufacturer</label>
                                    <input type="text" class="form-control" id="edit_manufacturer" name="edit_manufacturer">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Brand</label>
                                    <input type="text" class="form-control" id="edit_brand" name="edit_brand">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Product</label>
                                    <input type="text" class="form-control" id="edit_product" name="edit_product">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Size</label>
                                    <input type="text" class="form-control" id="edit_size" name="edit_size">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Validation</label>
                                    <input type="text" class="form-control" id="edit_validation" name="edit_validation">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Activity</label>
                                    <input type="text" class="form-control" id="edit_activity" name="edit_activity">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Type of Promotion</label>
                                    <input type="text" class="form-control" id="edit_type_of_promotion" name="edit_type_of_promotion">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Descuento</label>
                                    <input type="text" class="form-control" id="edit_descuento" name="edit_descuento">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Price Talker</label>
                                    <input type="text" class="form-control" id="edit_price_talker" name="edit_price_talker">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Mechanics</label>
                                    <input type="text" class="form-control" id="edit_mechanics" name="edit_mechanics">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Sale Price</label>
                                    <input type="text" class="form-control" id="edit_sale_price" name="edit_sale_price">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Observation</label>
                                    <input type="text" class="form-control" id="edit_observation" name="edit_observation">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Photo URL</label>
                                    <input type="text" class="form-control" id="edit_photo_url" name="edit_photo_url">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Period</label>
                                    <input type="text" class="form-control" id="edit_period" name="edit_period">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Inicio de Promoción</label>
                                    <input type="date" class="form-control" id="edit_inicio_de_promocion" name="edit_inicio_de_promocion">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Fin de Promoción</label>
                                    <input type="date" class="form-control" id="edit_fin_de_promocion" name="edit_fin_de_promocion">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Agotar Stock</label>
                                    <input type="text" class="form-control" id="edit_agotar_stock" name="edit_agotar_stock">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Fuente</label>
                                    <input type="text" class="form-control" id="edit_fuente" name="edit_fuente">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Tipo</label>
                                    <input type="text" class="form-control" id="edit_tipo" name="edit_tipo">
                                </div>
                            </div>
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


    <!-- ===================== MODAL AGREGAR ===================== -->
    <div class="modal fade" id="modalAgregar" tabindex="-1" role="dialog" aria-labelledby="modalAgregarLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAgregarLabel">Agregar Nuevo Registro PPT</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formAgregar">

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Day</label>
                                    <input type="date" class="form-control" id="add_day" name="add_day" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Time</label>
                                    <input type="text" class="form-control" id="add_time" name="add_time" placeholder="HH:MM:SS">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>CP Code</label>
                                    <input type="text" class="form-control" id="add_cp_code" name="add_cp_code">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Trade</label>
                                    <input type="text" class="form-control" id="add_trade" name="add_trade">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Retail Environment</label>
                                    <input type="text" class="form-control" id="add_retail_environment" name="add_retail_environment">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>CP Format</label>
                                    <input type="text" class="form-control" id="add_cp_format" name="add_cp_format">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Customer Format Banner</label>
                                    <input type="text" class="form-control" id="add_customer_format_banner" name="add_customer_format_banner">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Distribuidor</label>
                                    <input type="text" class="form-control" id="add_distribuidor" name="add_distribuidor">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Customer</label>
                                    <input type="text" class="form-control" id="add_customer" name="add_customer">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>POS</label>
                                    <input type="text" class="form-control" id="add_pos" name="add_pos">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Province</label>
                                    <input type="text" class="form-control" id="add_province" name="add_province">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>City</label>
                                    <input type="text" class="form-control" id="add_city" name="add_city">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Zone</label>
                                    <input type="text" class="form-control" id="add_zone" name="add_zone">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Ejecutivo</label>
                                    <input type="text" class="form-control" id="add_ejecutivo" name="add_ejecutivo">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Category</label>
                                    <input type="text" class="form-control" id="add_category" name="add_category">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Subcategory</label>
                                    <input type="text" class="form-control" id="add_subcategory" name="add_subcategory">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Segment</label>
                                    <input type="text" class="form-control" id="add_segment" name="add_segment">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Form</label>
                                    <input type="text" class="form-control" id="add_form" name="add_form">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Manufacturer</label>
                                    <input type="text" class="form-control" id="add_manufacturer" name="add_manufacturer">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Brand</label>
                                    <input type="text" class="form-control" id="add_brand" name="add_brand">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Product</label>
                                    <input type="text" class="form-control" id="add_product" name="add_product">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Size</label>
                                    <input type="text" class="form-control" id="add_size" name="add_size">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Validation</label>
                                    <input type="text" class="form-control" id="add_validation" name="add_validation">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Activity</label>
                                    <input type="text" class="form-control" id="add_activity" name="add_activity">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Type of Promotion</label>
                                    <input type="text" class="form-control" id="add_type_of_promotion" name="add_type_of_promotion">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Descuento</label>
                                    <input type="text" class="form-control" id="add_descuento" name="add_descuento">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Price Talker</label>
                                    <input type="text" class="form-control" id="add_price_talker" name="add_price_talker">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Mechanics</label>
                                    <input type="text" class="form-control" id="add_mechanics" name="add_mechanics">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Sale Price</label>
                                    <input type="text" class="form-control" id="add_sale_price" name="add_sale_price">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Observation</label>
                                    <input type="text" class="form-control" id="add_observation" name="add_observation">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Photo URL</label>
                                    <input type="text" class="form-control" id="add_photo_url" name="add_photo_url">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Period</label>
                                    <input type="text" class="form-control" id="add_period" name="add_period">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Inicio de Promoción</label>
                                    <input type="date" class="form-control" id="add_inicio_de_promocion" name="add_inicio_de_promocion">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Fin de Promoción</label>
                                    <input type="date" class="form-control" id="add_fin_de_promocion" name="add_fin_de_promocion">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Agotar Stock</label>
                                    <input type="text" class="form-control" id="add_agotar_stock" name="add_agotar_stock">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Fuente</label>
                                    <input type="text" class="form-control" id="add_fuente" name="add_fuente">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Tipo</label>
                                    <input type="text" class="form-control" id="add_tipo" name="add_tipo">
                                </div>
                            </div>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.3.2/papaparse.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    
    <script>
        $(document).ready(function() {
            var tablePpts = null;

            inicializarDataTablesPpts();

            $('#date-start-ppts, #date-end-ppts').on('change input', function() {
                if (tablePpts) tablePpts.ajax.reload();
            });

            $('#clear-filter-ppts').on('click', function() {
                $('#date-start-ppts').val('');
                $('#date-end-ppts').val('');
                if (tablePpts) tablePpts.ajax.reload();
            });

            function inicializarDataTablesPpts() {
                console.warn('📍 window.location.href:', window.location.href);
                console.warn('📍 URL getter que se usará:', window.location.href.replace(/[^/]*$/, '') + 'getters/get_table_repositorio_ppts.php');

                if (tablePpts) {
                    tablePpts.destroy();
                    tablePpts = null;
                }

                tablePpts = $('#table').DataTable({
                    "processing": true,
                    "serverSide": true,
                    "ajax": {
                        "url": "getters/get_table_repositorio_ppts.php",
                        "type": "POST",
                        "data": function(d) {
                            d.fecha_inicio = $('#date-start-ppts').val();
                            d.fecha_fin    = $('#date-end-ppts').val();
                        },
                        "dataSrc": function(json) {
                            $("#loading").css("display", "none");
                            return json.data;
                        },
                        "error": function(xhr, error, thrown) {
                            console.log("URL solicitada:", this.url);
                            console.log("Status:", xhr.status);
                            console.log("Response:", xhr.responseText);
                        }
                    },

                    "columns": [
                        { "data": 0,  "orderable": false }, // Checkbox
                        { "data": 1  }, // ID
                        { "data": 2  }, // DAY
                        { "data": 3  }, // TIME
                        { "data": 4  }, // CP CODE
                        { "data": 5  }, // TRADE
                        { "data": 6  }, // RETAIL ENVIRONMENT
                        { "data": 7  }, // CP FORMAT
                        { "data": 8  }, // CUSTOMER FORMAT BANNER
                        { "data": 9  }, // DISTRIBUIDOR
                        { "data": 10 }, // CUSTOMER
                        { "data": 11 }, // POS
                        { "data": 12 }, // PROVINCE
                        { "data": 13 }, // CITY
                        { "data": 14 }, // ZONE
                        { "data": 15 }, // EJECUTIVO
                        { "data": 16 }, // CATEGORY
                        { "data": 17 }, // SUBCATEGORY
                        { "data": 18 }, // SEGMENT
                        { "data": 19 }, // FORM
                        { "data": 20 }, // MANUFACTURER
                        { "data": 21 }, // BRAND
                        { "data": 22 }, // PRODUCT
                        { "data": 23 }, // SIZE
                        { "data": 24 }, // VALIDATION
                        { "data": 25 }, // ACTIVITY
                        { "data": 26 }, // TYPE OF PROMOTION
                        { "data": 27 }, // DESCUENTO
                        { "data": 28 }, // PRICE TALKER
                        { "data": 29 }, // MECHANICS
                        { "data": 30 }, // SALE PRICE
                        { "data": 31 }, // OBSERVATION
                        { "data": 32 }, // PHOTO URL
                        { "data": 33 }, // PERIOD
                        { "data": 34 }, // INICIO PROMOCIÓN
                        { "data": 35 }, // FIN PROMOCIÓN
                        { "data": 36 }, // AGOTAR STOCK
                        { "data": 37 }, // FUENTE
                        { "data": 38 },
                        { "data": 39 }, // FECHA MODIFICACIÓN
                        { "data": 40, "orderable": false } // ACCIONES
                    ],

                    "scrollX": true,
                    "lengthMenu": [10, 25, 50, 75, 100],
                    "responsive": true,
                    "dom": 'lBfrtip',
                    "buttons": [
                        {
                            text: 'Excel (Todos los registros)',
                            action: function(e, dt, node, config) {
                                exportarTodosLosRegistrosPpts();
                            }
                        },
                        'copy', 'pdf', 'print'
                    ],
                    "order": [[1, "asc"]],

                    "initComplete": function(settings, json) {
                        console.log("DataTables Repositorio PPTs inicializado");

                        $('#table').off('change', '.select-row').on('change', '.select-row', function() {
                            actualizarContadorSeleccionados();
                            var total   = $('.select-row').length;
                            var checked = $('.select-row:checked').length;
                            $('#selectAll').prop('checked', total === checked && total > 0);
                        });

                        $('#selectAll').off('click').on('click', function() {
                            var isChecked = $(this).prop('checked');
                            tablePpts.rows({ page: 'current' }).nodes().to$().find('.select-row').prop('checked', isChecked);
                            actualizarContadorSeleccionados();
                        });

                        $('#table').off('click', '.btn-editar').on('click', '.btn-editar', function() {
                            const id_registro = $(this).data('id');
                            console.log(`Cargando registro con ID: ${id_registro}`);
                            $('#formEditar')[0].reset();

                            $.ajax({
                                url: 'getters/get_repositorio_ppts_by_id.php',
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
                                        $('#edit_trade').val(data.trade);
                                        $('#edit_retail_environment').val(data.retail_environment);
                                        $('#edit_cp_format').val(data.cp_format);
                                        $('#edit_customer_format_banner').val(data.customer_format_banner);
                                        $('#edit_distribuidor').val(data.distribuidor);
                                        $('#edit_customer').val(data.customer);
                                        $('#edit_pos').val(data.pos);
                                        $('#edit_province').val(data.province);
                                        $('#edit_city').val(data.city);
                                        $('#edit_zone').val(data.zone);
                                        $('#edit_ejecutivo').val(data.ejecutivo);
                                        $('#edit_category').val(data.category);
                                        $('#edit_subcategory').val(data.subcategory);
                                        $('#edit_segment').val(data.segment);
                                        $('#edit_form').val(data.form);
                                        $('#edit_manufacturer').val(data.manufacturer);
                                        $('#edit_brand').val(data.brand);
                                        $('#edit_product').val(data.product);
                                        $('#edit_size').val(data.size);
                                        $('#edit_validation').val(data.validation);
                                        $('#edit_activity').val(data.activity);
                                        $('#edit_type_of_promotion').val(data.type_of_promotion);
                                        $('#edit_descuento').val(data.descuento);
                                        $('#edit_price_talker').val(data.price_talker);
                                        $('#edit_mechanics').val(data.mechanics);
                                        $('#edit_sale_price').val(data.sale_price);
                                        $('#edit_observation').val(data.observation);
                                        $('#edit_photo_url').val(data.photo_url);
                                        $('#edit_period').val(data.period);
                                        $('#edit_inicio_de_promocion').val(data.inicio_de_promocion);
                                        $('#edit_fin_de_promocion').val(data.fin_de_promocion);
                                        $('#edit_agotar_stock').val(data.agotar_stock);
                                        $('#edit_fuente').val(data.fuente);
                                        $('#edit_tipo').val(data.tipo);

                                        $('#modalEditar').modal('show');
                                    } else {
                                        Swal.fire({ icon: 'error', title: 'Error', text: 'Error al obtener datos: ' + response.message });
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.error("Error AJAX:", error);
                                    Swal.fire({ icon: 'error', title: 'Error', text: 'Error al obtener el registro.' });
                                }
                            });

                            return false;
                        });

                        actualizarContadorSeleccionados();
                    }
                });
            }

            function exportarTodosLosRegistrosPpts() {
                $("#loading").css("display", "flex");

                const form = $('<form>', {
                    method: 'POST',
                    action: 'getters/export_repositorio_ppts_excel.php',
                    target: '_blank'
                });

                form.append($('<input>', { type: 'hidden', name: 'fecha_inicio', value: $('#date-start-ppts').val() }));
                form.append($('<input>', { type: 'hidden', name: 'fecha_fin',    value: $('#date-end-ppts').val() }));

                const searchValue = tablePpts.search();
                if (searchValue) {
                    form.append($('<input>', { type: 'hidden', name: 'search_value', value: searchValue }));
                }

                form.appendTo('body').submit().remove();
                setTimeout(function() { $("#loading").css("display", "none"); }, 1000);
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

            // ELIMINAR SELECCIONADOS
            $('#btnEliminarSeleccionados').off('click').on('click', function() {
                var idsSeleccionados = [];
                $('.select-row:checked').each(function() {
                    idsSeleccionados.push($(this).val());
                });

                if (idsSeleccionados.length === 0) {
                    Swal.fire({ icon: 'warning', title: 'Atención', text: 'Por favor, seleccione al menos un registro para eliminar.' });
                    return;
                }

                Swal.fire({
                    title: '¿Está seguro?',
                    html: 'Está a punto de eliminar <strong>' + idsSeleccionados.length + '</strong> registro(s).<br>Esta acción no se puede deshacer.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        eliminarPorLotesPpts(idsSeleccionados);
                    }
                });
            });

            function eliminarPorLotesPpts(idsSeleccionados) {
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
                    didOpen: () => { Swal.showLoading(); }
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
                                icon: 'success', title: '¡Eliminado!',
                                text: `Se eliminaron correctamente ${procesados} registro(s).`,
                                timer: 2000, showConfirmButton: false
                            });
                        } else {
                            Swal.fire({
                                icon: 'warning', title: 'Proceso Completado con Errores',
                                html: `Se eliminaron <strong>${procesados}</strong> registros.<br>Fallaron <strong>${errores}</strong> registros.`
                            });
                        }
                        tablePpts.ajax.reload();
                        return;
                    }

                    const loteActual = chunks[index];
                    $.ajax({
                        type: "POST",
                        url: "actions/delete_repositorio_ppts.php",
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

            // GUARDAR NUEVO REGISTRO
            $('#btnGuardarNuevo').off('click').on('click', function() {
                var formData = {
                    day:                    $('#add_day').val(),
                    time:                   $('#add_time').val(),
                    cp_code:                $('#add_cp_code').val(),
                    trade:                  $('#add_trade').val(),
                    retail_environment:     $('#add_retail_environment').val(),
                    cp_format:              $('#add_cp_format').val(),
                    customer_format_banner: $('#add_customer_format_banner').val(),
                    distribuidor:           $('#add_distribuidor').val(),
                    customer:               $('#add_customer').val(),
                    pos:                    $('#add_pos').val(),
                    province:               $('#add_province').val(),
                    city:                   $('#add_city').val(),
                    zone:                   $('#add_zone').val(),
                    ejecutivo:              $('#add_ejecutivo').val(),
                    category:               $('#add_category').val(),
                    subcategory:            $('#add_subcategory').val(),
                    segment:                $('#add_segment').val(),
                    form:                   $('#add_form').val(),
                    manufacturer:           $('#add_manufacturer').val(),
                    brand:                  $('#add_brand').val(),
                    product:                $('#add_product').val(),
                    size:                   $('#add_size').val(),
                    validation:             $('#add_validation').val(),
                    activity:               $('#add_activity').val(),
                    type_of_promotion:      $('#add_type_of_promotion').val(),
                    descuento:              $('#add_descuento').val(),
                    price_talker:           $('#add_price_talker').val(),
                    mechanics:              $('#add_mechanics').val(),
                    sale_price:             $('#add_sale_price').val(),
                    observation:            $('#add_observation').val(),
                    photo_url:              $('#add_photo_url').val(),
                    period:                 $('#add_period').val(),
                    inicio_de_promocion:    $('#add_inicio_de_promocion').val(),
                    fin_de_promocion:       $('#add_fin_de_promocion').val(),
                    agotar_stock:           $('#add_agotar_stock').val(),
                    fuente:                 $('#add_fuente').val(),
                    tipo:                   $('#add_tipo').val() // o $('#edit_tipo').val()
                };

                if (Object.values(formData).some(x => x === null || x === '')) {
                    Swal.fire({ icon: 'warning', title: 'Atención', text: 'Por favor, complete todos los campos requeridos.' });
                    return;
                }

                $("#loading").css("display", "flex");

                $.ajax({
                    type: "POST",
                    url: "actions/insert_repositorio_ppts.php",
                    data: formData,
                    dataType: 'json',
                    success: function(result) {
                        $("#loading").css("display", "none");
                        if (result.success) {
                            Swal.fire({
                                icon: 'success', title: '¡Éxito!',
                                text: result.message || 'Registro agregado correctamente.',
                                timer: 2000, showConfirmButton: false
                            });
                            $('#modalAgregar').modal('hide');
                            $('#formAgregar')[0].reset();
                            tablePpts.ajax.reload();
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: result.message || 'Error al insertar el registro.' });
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        $("#loading").css("display", "none");
                        Swal.fire({ icon: 'error', title: 'Error de Conexión', text: 'No se pudo contactar al servidor: ' + textStatus });
                    }
                });
            });

            // GUARDAR CAMBIOS (EDITAR)
            $('#btnGuardarCambios').off('click').on('click', function() {
                var formData = {
                    id:                     $('#edit_id').val(),
                    day:                    $('#edit_day').val(),
                    time:                   $('#edit_time').val(),
                    cp_code:                $('#edit_cp_code').val(),
                    trade:                  $('#edit_trade').val(),
                    retail_environment:     $('#edit_retail_environment').val(),
                    cp_format:              $('#edit_cp_format').val(),
                    customer_format_banner: $('#edit_customer_format_banner').val(),
                    distribuidor:           $('#edit_distribuidor').val(),
                    customer:               $('#edit_customer').val(),
                    pos:                    $('#edit_pos').val(),
                    province:               $('#edit_province').val(),
                    city:                   $('#edit_city').val(),
                    zone:                   $('#edit_zone').val(),
                    ejecutivo:              $('#edit_ejecutivo').val(),
                    category:               $('#edit_category').val(),
                    subcategory:            $('#edit_subcategory').val(),
                    segment:                $('#edit_segment').val(),
                    form:                   $('#edit_form').val(),
                    manufacturer:           $('#edit_manufacturer').val(),
                    brand:                  $('#edit_brand').val(),
                    product:                $('#edit_product').val(),
                    size:                   $('#edit_size').val(),
                    validation:             $('#edit_validation').val(),
                    activity:               $('#edit_activity').val(),
                    type_of_promotion:      $('#edit_type_of_promotion').val(),
                    descuento:              $('#edit_descuento').val(),
                    price_talker:           $('#edit_price_talker').val(),
                    mechanics:              $('#edit_mechanics').val(),
                    sale_price:             $('#edit_sale_price').val(),
                    observation:            $('#edit_observation').val(),
                    photo_url:              $('#edit_photo_url').val(),
                    period:                 $('#edit_period').val(),
                    inicio_de_promocion:    $('#edit_inicio_de_promocion').val(),
                    fin_de_promocion:       $('#edit_fin_de_promocion').val(),
                    agotar_stock:           $('#edit_agotar_stock').val(),
                    fuente:                 $('#edit_fuente').val(),
                    tipo:                   $('#edit_tipo').val() // o $('#edit_tipo').val()
                };

                $("#loading").css("display", "flex");

                $.ajax({
                    type: "POST",
                    url: "actions/update_repositorio_ppts.php",
                    data: formData,
                    dataType: 'json',
                    success: function(result) {
                        $("#loading").css("display", "none");
                        if (result.success) {
                            Swal.fire({
                                icon: 'success', title: '¡Éxito!',
                                text: 'Registro actualizado correctamente',
                                timer: 2000, showConfirmButton: false
                            });
                            $('#modalEditar').modal('hide');
                            tablePpts.ajax.reload();
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: result.message || 'Error al actualizar el registro.' });
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        $("#loading").css("display", "none");
                        console.error('Error AJAX:', textStatus, errorThrown);

                        let errorMessage = 'Error en la conexión.';
                        if (textStatus === 'error' && errorThrown === 'Not Found') {
                            errorMessage = 'ERROR 404: Archivo no encontrado.';
                        } else if (jqXHR.responseText && jqXHR.responseText.includes('Fatal error')) {
                            errorMessage = 'ERROR 500: Error de PHP en el servidor.';
                        }

                        Swal.fire({
                            icon: 'error', title: 'Error de Conexión/Servidor',
                            html: errorMessage + '<br>Consulta la consola (F12) para más detalles.'
                        });
                    }
                });
            });

            // CONFIRMACIÓN DELETE POR EXCEL
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
                            url: "actions/delete_repositorio_ppts.php",
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