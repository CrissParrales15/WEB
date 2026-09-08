<?php
use Phppot\DataSource;

require_once 'DataSource.php';
$db = new DataSource();
$conn = $db->getConnection();

if (isset($_POST["import"])) {

    $fileName = $_FILES["file"]["tmp_name"];

    if ($_FILES["file"]["size"] > 0) {

        $file = fopen($fileName, "r");

        // Para activar TRUNCATE, descomentar las dos líneas siguientes:
        // $sqlTruncate = "TRUNCATE table repositorio_tipo_actividad;";
        // $db->truncate($sqlTruncate);

        $insertados = 0;
        $errores    = 0;

        while (($column = fgetcsv($file, 10000, ";")) !== FALSE) {

            $tipo = "";
            if (isset($column[0])) {
                $tipo = mysqli_real_escape_string($conn, $column[0]);
            }

            $actividad = "";
            if (isset($column[1])) {
                $actividad = mysqli_real_escape_string($conn, $column[1]);
            }

            $status = "";
            if (isset($column[2])) {
                $status = mysqli_real_escape_string($conn, $column[2]);
            }

            $sqlInsert  = "INSERT INTO repositorio_tipo_actividad (tipo, actividad, status) VALUES (?, ?, ?)";
            $paramType  = "sss";
            $paramArray = array($tipo, $actividad, $status);
            $insertId   = $db->insert($sqlInsert, $paramType, $paramArray);

            if (!empty($insertId)) {
                $insertados++;
            } else {
                $errores++;
            }
        }

        if ($errores === 0) {
            $type    = "success";
            $message = "CSV importado correctamente — $insertados registros cargados.";
        } else {
            $type    = "warning";
            $message = "$insertados registros importados, $errores con error.";
        }
    } else {
        $type    = "error";
        $message = "El archivo está vacío.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Base Tipo Actividad</title>

    <!-- Bootstrap -->
    <link rel="stylesheet" href="/App/XploraEcuador/assets/css/bootstrap-5.1.3.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="/App/XploraEcuador/assets/css/dataTables.bootstrap5.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="/App/XploraEcuador/assets/css/sweetalert2.min.css">
    <!-- Select2 -->
    <link href="/App/XploraEcuador/assets/css/select2.min.css" rel="stylesheet">
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <style>
        .input-row      { margin-top: 0; margin-bottom: 20px; }

        .btn-submit {
            background: #333;
            border: #1d1d1d 1px solid;
            color: #f0f0f0;
            font-size: 0.9em;
            width: 110px;
            border-radius: 2px;
            cursor: pointer;
            padding: 5px 10px;
        }

        #response {
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 2px;
            display: none;
        }

        .success { background: #c7efd9; border: #bbe2cd 1px solid; }
        .warning { background: #fff3cd; border: #ffc107 1px solid; }
        .error   { background: #fbcfcf; border: #f3c6c7 1px solid; }

        div#response.display-block { display: block; }

        /* Toggle switch */
        .form-switch .form-check-input {
            width: 2.5em;
            cursor: pointer;
        }
    </style>

    <script src="/App/XploraEcuador/assets/js/jquery-3.2.1.min.js"></script>
    <script>
        $(document).ready(function () {
            $("#frmCSVImport").on("submit", function () {
                $("#response").attr("class", "");
                $("#response").html("");
                var fileType = ".csv";
                var regex = new RegExp("([a-zA-Z0-9\\s_\\.\\-:])+(" + fileType + ")$");
                if (!regex.test($("#file").val().toLowerCase())) {
                    $("#response").addClass("error display-block");
                    $("#response").html("Archivo inválido. Solo se permiten archivos <b>.csv</b>.");
                    return false;
                }
                return true;
            });
        });
    </script>
</head>

<body>
<div id="content" style="width:100%;">

    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <h3>Base Tipo Actividad</h3>
        </div>
    </nav>

    <!-- Mensaje de resultado de importación -->
    <div id="response" class="<?php if (!empty($type)) { echo $type . ' display-block'; } ?>">
        <?php if (!empty($message)) { echo $message; } ?>
    </div>

    <!-- Formulario de carga CSV -->
    <div class="row px-3 pt-3">
        <form class="form-horizontal" action="" method="post"
              name="frmCSVImport" id="frmCSVImport" enctype="multipart/form-data">
            <div class="input-row">
                <label class="col-md-4 control-label">Seleccionar archivo CSV</label>
                <input type="file" name="file" id="file" accept=".csv">
                <button type="submit" id="submit" name="import" class="btn-submit">Importar</button>
                <br>
                <small class="text-muted">
                    Formato esperado (separado por <code>;</code>):
                    <strong>tipo ; actividad ; status</strong>
                    — el <code>id</code> es autoincremental y no debe incluirse.
                </small>
            </div>
        </form>
    </div>

    <!-- Tabla de resultados -->
    <div class="row px-3">
        <div class="col-xl-12 col-lg-12 mb-4">
            <div class="bg-white rounded-lg p-4 shadow" id="data-result"></div>
        </div>
    </div>

</div>

<!-- JS: jQuery, Bootstrap, DataTables, SweetAlert2, Select2 -->
<script src="/App/XploraEcuador/assets/js/jquery-3.6.0.min.js"></script>
<script src="/App/XploraEcuador/assets/js/bootstrap-4.0.0.min.js"
        integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl"
        crossorigin="anonymous"></script>
<script src="/App/XploraEcuador/assets/js/jquery.dataTables.min.js"></script>
<script src="/App/XploraEcuador/assets/js/dataTables.bootstrap5.min.js"></script>
<script src="/App/XploraEcuador/assets/js/dataTables.buttons.min.js"></script>
<script src="/App/XploraEcuador/assets/js/jszip.min.js"></script>
<script src="/App/XploraEcuador/assets/js/pdfmake.min.js"></script>
<script src="/App/XploraEcuador/assets/js/vfs_fonts.js"></script>
<script src="/App/XploraEcuador/assets/js/buttons.html5.min.js"></script>
<script src="/App/XploraEcuador/assets/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/select/1.3.1/js/dataTables.select.min.js"></script>
<script src="/App/XploraEcuador/assets/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>

<script>
$(document).ready(function () {

    // ── Cargar tabla ──────────────────────────────────────────────
    $.ajax({
        type: "POST",
        url: "getters/get_table_tipoactividad.php",
        data: null,
        success: function (data) {
            if (data !== "") {
                $("#data-result").html(data);
                $('#table-tipoactividad').DataTable({
                    scrollX: true,
                    lengthMenu: [10, 25, 50, 75, 100],
                    responsive: true,
                    dom: 'lBftpi',
                    buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
                    // No ordenar por la columna "Activar" (última)
                    columnDefs: [{ orderable: false, targets: -1 }]
                });
            }
        },
        error: function (xhr, textStatus, errorThrown) {
            $("#data-result").html(
                '<p class="text-danger">Error al cargar la tabla: ' + errorThrown + '</p>'
            );
        }
    });

    // ── Toggle de status (delegado, porque el HTML llega por AJAX) ─
    $(document).on("change", ".toggle-status", function () {
        var $toggle  = $(this);
        var id       = $toggle.data("id");
        var newStatus = $toggle.is(":checked") ? 1 : 0;

        $.ajax({
            type: "POST",
            url: "update_status_tipoactividad.php",
            data: { id: id, status: newStatus },
            success: function (response) {
                var res = JSON.parse(response);
                if (res.success) {
                    // Actualizar etiqueta visual junto al toggle
                    var label = $toggle.closest("label").find(".status-label");
                    if (newStatus === 1) {
                        label.text("Activo").removeClass("text-secondary").addClass("text-success");
                    } else {
                        label.text("Inactivo").removeClass("text-success").addClass("text-secondary");
                    }
                } else {
                    // Revertir si hubo error
                    $toggle.prop("checked", !$toggle.is(":checked"));
                    alert("Error al actualizar el estado.");
                }
            },
            error: function () {
                $toggle.prop("checked", !$toggle.is(":checked"));
                alert("Error de conexión al actualizar el estado.");
            }
        });
    });

});
</script>
</body>
</html>