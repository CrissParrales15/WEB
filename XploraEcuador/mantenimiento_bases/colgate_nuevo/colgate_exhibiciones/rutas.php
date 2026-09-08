<?php
// require $_SERVER["DOCUMENT_ROOT"] . '/App/XploraEcuador/includes/db_connect.php';
// require $_SERVER["DOCUMENT_ROOT"] . '/App/XploraEcuador/includes/functions.php';

// sec_session_start();

// if (login_check($mysqli) == true) {

    include_once "includes/db_connect.php";
	include_once "includes/functions.php";
	include_once "includes/config.php";
?>
    <!DOCTYPE html>
    <html>

    <head>
        <?php
        header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
        header("Expires: Sat, 1 Jul 2000 05:00:00 GMT"); // Fecha en el pasado
        header('Content-Type: text/html; charset=UTF-8');
        ?>
        <meta http-equiv="cache-control" content="no-cache, must-revalidate, post-check=0, pre-check=0" />
        <meta http-equiv="cache-control" content="max-age=0" />
        <meta http-equiv="expires" content="0" />
        <meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
        <meta http-equiv="pragma" content="no-cache" />
        <meta charset="utf-8">
        <meta http-equiv="Content-Type" content="text/html; charset=gb18030">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
        <meta name="HandheldFriendly" content="true" />
        <title>Inicio seguro: Reportes Xplora</title>
        <!-- Bootstrap CSS CDN -->
        <link rel="stylesheet" href="/App/XploraEcuador/assets/css/bootstrap-5.1.3.min.css">
        <!-- Our Custom CSS -->
        <link rel="stylesheet" href="style.css">
        <script src="/App/XploraEcuador/assets/js/jquery-3.2.1.min.js"></script>
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
        <link rel="stylesheet" href="/App/XploraEcuador/assets/css/sweetalert2.min.css">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
        <link href="/App/XploraEcuador/assets/css/select2.min.css" rel="stylesheet" />
    </head>

    <body>
        <div id="loading" style=" display: none;position: fixed; width: 100%; height: 100%; top: 0px; left: 0px; z-index: 999999; overflow: auto; background-color: rgba(0, 0, 0, 0.498039);">
            <img style="position: absolute; top: 50% !important; left: 50% !important;" src="/CER/assets/dist/img/loader_1.gif">
        </div>
        <form method="post" action="" name="frmCSVImport" id="frmCSVImport"  enctype="multipart/form-data">
            <div class="wrapper">
                <!-- Sidebar Holder -->
                <nav id="sidebar" class="active">
                    <div class="sidebar-header">
                        <h3>Nueva Ruta</h3>
                    </div>

                    <ul class="list-unstyled components">
                        <li class="active">
                            <div class="form-group" style="margin-left:5%;margin-right:5%;">
                                <label for="fechaInicio">Fecha Inicio</label>
                                <input type="date" class="form-control" id="fechaInicio" name="fechaInicio" required>
                            </div>
                        </li>
                        <li class="active">
                            <div class="form-group" style="margin-left:5%;margin-right:5%;">
                                <label for="fechaFin">Fecha Fin</label>
                                <input type="date" class="form-control" id="fechaFin" name="fechaFin" required>
                            </div>
                        </li>
                        <li class="active">
                            <div class="form-group" style="margin-left:5%;margin-right:5%;">
                                <label for="modulo">Canales</label>
                                <select class="form-control js-example-basic-single" id="canales" name="canales" required></select>
                            </div>
                        </li>
                        <li class="active">
                            <div class="form-group" style="margin-left:5%;margin-right:5%;">
                                <label for="modulo">Código PDV</label>
                                <select class="form-control js-example-basic-single" id="codigos_pdv" name="codigos_pdv" required>
                                    <option value="">Seleccione</option>
                                </select>
                            </div>
                        </li>
                        <li class="active">
                            <div class="form-group" style="margin-left:5%;margin-right:5%;">
                                <input class="form-check-input me-1" type="checkbox" aria-label="..." id="termometro" name="termometro">Termómetro
                            </div>
                        </li>
                        <li class="active">
                            <div class="form-group" style="margin-left:5%;margin-right:5%;">
                                <label for="modulo">Usuario</label>
                                <select class="form-control js-example-basic-single" id="usuarios" name="usuarios" required>
                                    <option value="">Seleccione</option>
                                </select>
                            </div>
                        </li>
                        <li class="active" style="color:black;">
                            <div class="form-group" style="margin-left:5%;margin-right:5%;">
                                <label for="days" style="color:white;">Días</label>
                                <ul class="list-group days">
                                    <li class="list-group-item">
                                        <input class="form-check-input me-1" type="checkbox" value="1" aria-label="..." id="lunes" name="lunes">
                                        Lunes
                                    </li>
                                    <li class="list-group-item">
                                        <input class="form-check-input me-1" type="checkbox" value="2" aria-label="..." id="martes" name="martes">
                                        Martes
                                    </li>
                                    <li class="list-group-item">
                                        <input class="form-check-input me-1" type="checkbox" value="3" aria-label="..." id="miercoles" name="miercoles">
                                        Miércoles
                                    </li>
                                    <li class="list-group-item">
                                        <input class="form-check-input me-1" type="checkbox" value="4" aria-label="..." id="jueves" name="jueves">
                                        Jueves
                                    </li>
                                    <li class="list-group-item">
                                        <input class="form-check-input me-1" type="checkbox" value="5" aria-label="..." id="viernes" name="viernes">
                                        Viernes
                                    </li>
                                    <li class="list-group-item">
                                        <input class="form-check-input me-1" type="checkbox" value="6" aria-label="..." id="sabado" name="sabado">
                                        Sábado
                                    </li>
                                    <li class="list-group-item">
                                        <input class="form-check-input me-1" type="checkbox" value="7" aria-label="..." id="domingo" name="domingo">
                                        Domingo
                                    </li>
                                </ul>
                            </div>
                        </li>
                    </ul>
                    <ul class="list-unstyled CTAs">
                        <li><a href="#" class="download" id="save" name="save">Guardar</a></li>
                    </ul>
                </nav>

                <!-- Page Content Holder -->
                <div id="content" style="width:100%;">
                    <nav class="navbar navbar-expand-lg navbar-light bg-light">
                        <div class="container-fluid">
                            <button type="button" id="sidebarCollapse" class="btn btn-info">
                                <i class="glyphicon glyphicon-plus"></i>
                                <span>Nueva ruta</span>
                            </button>
                            <!-- <button class="btn btn-dark d-inline-block d-lg-none ml-auto" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                                    <i class="fas fa-align-justify"></i>
                                </button> -->

                            <?php
                            /*if (isset($_GET['aksi'])) {
                                        if (strip_tags($_GET["aksi"],ENT_QUOTES) === 'read'){
                                            $nik = $mysqli->real_escape_string(strip_tags($_GET["nik"],ENT_QUOTES));
                                            $sql = $mysqli->prepare("UPDATE insert_notificaciones SET estado=1 WHERE id=?");
                                            $sql->bind_param('i', $nik);
                                            $sql->execute();

                                            echo '<script type="text/JavaScript"> window.history.pushState("", "Rutero", "rutero.php"); </script>';
                                            // header('Location: rutero.php');
                                            // die;
                                        }
                                    }*/
                            ?>

                            <!-- <ul class="nav navbar-nav navbar-right">
                                    <li class="dropdown" id="notificaciones" name="notificaciones"> -->
                            <!-- <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Notificacion (<b>2</b>)</a>
                                        <ul class="dropdown-menu notify-drop">
                                            <div class="notify-drop-title">
                                                <div class="row">
                                                    <div class="col-md-6 col-sm-6 col-xs-6">Notificaciones (<b>2</b>)</div>
                                                    <div class="col-md-6 col-sm-6 col-xs-6 text-right"><a href="" class="rIcon allRead" data-tooltip="tooltip" data-placement="bottom" title="tümü okundu."><i class="fa fa-dot-circle-o"></i></a></div>
                                                </div>
                                            </div> -->
                            <!-- end notify title -->
                            <!-- notify content -->
                            <!-- <div class="drop-content"> -->
                            <!-- <li>
                                                    <div class="col-md-3 col-sm-3 col-xs-3">
                                                        <div class="notify-img"><img src="http://placehold.it/45x45" alt=""></div>
                                                    </div>
                                                    <div class="col-md-9 col-sm-9 col-xs-9 pd-l0"><a href="" class="rIcon"><span class="material-icons">email</span></a>
                                                        <h6>Pepito Perez</h6>
                                                        <p>Salió del Punto de Venta XYZ1234 sin marcar la salida.</p>
                                                        <p class="time">22-06-2022 11:49:00</p>
                                                    </div>
                                                </li>-->
                            <!-- </div>
                                            <div class="notify-drop-footer text-center">
                                                <a href=""><span class="material-icons">visibility</span>Mostrar todo</a>
                                            </div>
                                        </ul> -->
                            <!-- </li>
                                </ul> -->
                        </div>
                    </nav>


                    <!-- <button type="button" id="sidebarCollapse" class="btn btn-info navbar-btn">
                            <i class="glyphicon glyphicon-plus"></i>
                            <span>Nueva ruta</span>
                        </button> -->

                    <!-- <div class="dropdown">
                            <a id="dLabel" role="button" data-toggle="dropdown" data-target="#" >
                                <span><i class="material-icons">notifications</i></span>
                            </a>

                            <ul class="dropdown-menu notifications" role="menu" aria-labelledby="dLabel">

                                <div class="notification-heading">
                                    <h4 class="menu-title">Notifications</h4>
                                    <h4 class="menu-title pull-right">View all<i class="glyphicon glyphicon-circle-arrow-right"></i></h4>
                                </div>
                                <li class="divider"></li>
                                <div class="notifications-wrapper">
                                    <a class="content" href="#">

                                        <div class="notification-item">
                                            <h4 class="item-title">Evaluation Deadline 1 · day ago</h4>
                                            <p class="item-info">Marketing 101, Video Assignment</p>
                                        </div>

                                    </a>
                                    <a class="content" href="#">
                                        <div class="notification-item">
                                            <h4 class="item-title">Evaluation Deadline 1 · day ago</h4>
                                            <p class="item-info">Marketing 101, Video Assignment</p>
                                        </div>
                                    </a>
                                    <a class="content" href="#">
                                        <div class="notification-item">
                                            <h4 class="item-title">Evaluation Deadline 1 • day ago</h4>
                                            <p class="item-info">Marketing 101, Video Assignment</p>
                                        </div>
                                    </a>
                                    <a class="content" href="#">
                                        <div class="notification-item">
                                            <h4 class="item-title">Evaluation Deadline 1 • day ago</h4>
                                            <p class="item-info">Marketing 101, Video Assignment</p>
                                        </div>

                                    </a>
                                    <a class="content" href="#">
                                        <div class="notification-item">
                                            <h4 class="item-title">Evaluation Deadline 1 • day ago</h4>
                                            <p class="item-info">Marketing 101, Video Assignment</p>
                                        </div>
                                    </a>
                                    <a class="content" href="#">
                                        <div class="notification-item">
                                            <h4 class="item-title">Evaluation Deadline 1 • day ago</h4>
                                            <p class="item-info">Marketing 101, Video Assignment</p>
                                        </div>
                                    </a>

                                </div>
                                <li class="divider"></li>
                                <div class="notification-footer">
                                    <h4 class="menu-title">View all<i class="glyphicon glyphicon-circle-arrow-right"></i></h4>
                                </div>
                            </ul>
                        </div> -->

                    <h3>Consultar rutas</h3>

                    <div class="row">
                        <div class="col-xl-12 col-lg-12 mb-12">
                            <div class="bg-white rounded-lg p-5 shadow">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="fechaInicioFiltro">Fecha Inicio</label>
                                        <input type="date" class="form-control" id="fechaInicioFiltro" name="fechaInicioFiltro" required>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="fechaFinFiltro">Fecha Fin</label>
                                        <input type="date" class="form-control" id="fechaFinFiltro" name="fechaFinFiltro" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xl-12 col-lg-12 mb-12">
                            <div class="bg-white rounded-lg p-5 shadow">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <button type="button" id="search" name="search" value="search" class="btn btn-primary">Buscar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xl-12 col-lg-12 mb-4">
                            <div class="bg-white rounded-lg p-5 shadow" id="data-result" name="data-result"></div>
                        </div>
                    </div>

                    <div class="row" style="text-align: center; margin-bottom:1%;">
                        <div class="col-xl-6 col-lg-6 mb-6">
                            <h2>Rutero</h2>
                        </div>
                        <div class="col-xl-6 col-lg-6 mb-6">
                            <a class="btn btn-primary" href="formatos.zip" role="button">Descargar formato</a>
                            <!-- <button type="button" class="btn btn-info">Descargar formato</button> -->
                        </div>
                    </div>

                    <div class="row" style="text-align: center;">
                        <div class="col-xl-12 col-lg-12 mb-12">
                            <div class="bg-white rounded-lg p-5 shadow">
                                <div class="card">
                                    <h5 class="card-header">Cargar Ruta Nueva</h5>
                                    <div class="card-body">
                                        <h5 class="card-title">Escoger archivo CSV</h5>
                                        <input type="file" name="file_nueva_ruta" id="file_nueva_ruta" accept=".csv">
                                    </div>
                                    <div class="card-footer text-muted">
                                        <button type="submit" id="import_nueva_ruta" name="import_nueva_ruta" value="import_nueva_ruta" class="btn btn-primary btn-submit form-control-file">Importar Rutas</button>
                                    </div>
                                </div>
                                <br>
                                <div class="card">
                                    <h5 class="card-header">Modificar Ruta</h5>
                                    <div class="card-body">
                                        <h5 class="card-title">Escoger archivo CSV</h5>
                                        <input type="file" name="file_modificar_ruta" id="file_modificar_ruta" accept=".csv">
                                    </div>
                                    <div class="card-footer text-muted">
                                        <button type="submit" id="import_modificar_ruta" name="import_modificar_ruta" value="import_modificar_ruta" class="btn btn-primary btn-submit form-control-file">Importar Rutas</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header text-center">
                            <h4 class="modal-title w-100 font-weight-bold">Editar Ruta</h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body mx-3">
                            <div class="md-form mb-2" style="display:none;">
                                <input type="text" class="form-control" id="idEdit" name="idEdit">
                            </div>
                            
                            <div class="md-form mb-2">
                                <label data-error="wrong" data-success="right" for="fechaEdit">Fecha</label>
                                <input type="date" class="form-control" id="fechaEdit" name="fechaEdit">
                            </div>

                            <div class="md-form mb-2">
                                <label data-error="wrong" data-success="right" for="codigoEdit">Codigo PDV</label>
                                <select class="form-control js-example-basic-single" id="codigoEdit" name="codigoEdit"></select>
                            </div>

                            <div class="md-form mb-2">
                                <label data-error="wrong" data-success="right" for="usuarioEdit">Usuario</label>
                                <select class="form-control js-example-basic-single" id="usuarioEdit" name="usuarioEdit"></select>
                            </div>

                            <div class="md-form mb-2">
                                <label data-error="wrong" data-success="right" for="termometroEdit">Termómetro</label>
                                <select class="form-control js-example-basic-single" id="termometroEdit" name="termometroEdit">
                                    <option value="1">Si</option>
                                    <option value="0">No</option>
                                </select>
                            </div>

                            <div class="md-form mb-2">
                                <label data-error="wrong" data-success="right" for="estadoEdit">Estado</label>
                                <select class="form-control js-example-basic-single" id="estadoEdit" name="estadoEdit">
                                    <option value="1">Habilitado</option>
                                    <option value="0">Deshabilitado</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer d-flex justify-content-center">
                            <button type="button" id="save-edit" name="save-edit" value="save-edit" class="btn btn-primary">Guardar</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- jQuery CDN -->
            <!-- <script src="https://code.jquery.com/jquery-1.12.0.min.js"></script> -->
            <!-- Bootstrap Js CDN -->
            <!-- <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script> -->

            <script>
                $(document).ready(function() {
                    $("#frmCSVImport button[type = 'submit']").click(function(e) {
                        buttonClicked = $(this).attr("id");
                        console.log("ENTRA 2: " + buttonClicked);
                        if (buttonClicked == "import_nueva_ruta") {
                            var file_data = $('#file_nueva_ruta').prop('files')[0];
                            var form_data = new FormData();
                            form_data.append('file_nueva_ruta', file_data);
                            buttonClicked = "";
                            $.ajax({
                                url: "cargar_nueva_ruta.php",
                                method: "POST",
                                data: form_data,
                                contentType: false, // The content type used when sending data to the server.  
                                cache: false, // To unable request pages to be cached  
                                processData: false, // To send DOMDocument or non processed data file it is set to false  
                                beforeSend: function() {
                                    $("#loading").css("display", "block");
                                },
                                success: function(data) {
                                    $("#loading").css("display", "none");
                                    var id = parseInt(data);
                                    switch (id) {
                                        case 1:
                                            Swal.fire({
                                                title: "Rutas",
                                                text: "La tabla fue actualizada",
                                                type: "success"
                                            });
                                            $('#frmCSVImport')[0].reset();
                                            break;
                                        case 2:
                                            Swal.fire({
                                                title: "Rutas",
                                                text: "Error de actualización!",
                                                type: "error"
                                            });
                                            break;
                                    }
                                },
                                error: function(XMLHttpRequest, textStatus, errorThrown) {
                                    alert("Status: " + textStatus);
                                    alert("Error: " + errorThrown);
                                    $("#loading").css("display", "none");
                                }
                            });
                        } else if (buttonClicked == "import_modificar_ruta") {
                            var file_data = $('#file_modificar_ruta').prop('files')[0];
                            var form_data = new FormData();
                            form_data.append('file_modificar_ruta', file_data);
                            buttonClicked = "";
                            $.ajax({
                                url: "cargar_modificar_ruta.php",
                                method: "POST",
                                data: form_data,
                                contentType: false, // The content type used when sending data to the server.  
                                cache: false, // To unable request pages to be cached  
                                processData: false, // To send DOMDocument or non processed data file it is set to false  
                                beforeSend: function() {
                                    $("#loading").css("display", "block");
                                },
                                success: function(data) {
                                    $("#loading").css("display", "none");
                                    var id = parseInt(data);
                                    switch (id) {
                                        case 1:
                                            Swal.fire({
                                                title: "Rutas",
                                                text: "La tabla fue actualizada",
                                                type: "success"
                                            });
                                            $('#frmCSVImport')[0].reset();
                                            break;
                                        case 2:
                                            Swal.fire({
                                                title: "Rutas",
                                                text: "Error de actualización!",
                                                type: "error"
                                            });
                                            break;
                                    }
                                },
                                error: function(XMLHttpRequest, textStatus, errorThrown) {
                                    alert("Status: " + textStatus);
                                    alert("Error: " + errorThrown);
                                    $("#loading").css("display", "none");
                                }
                            });
                        }
                    });
                });
            </script>

            <script src="/App/XploraEcuador/assets/js/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
            <script src="/App/XploraEcuador/assets/js/popper-1.12.9.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
            <script src="/App/XploraEcuador/assets/js/jquery-3.6.0.min.js"></script>

            <!-- DATATABLE -->
            <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.3/css/bootstrap.min.css"> -->
            <link rel="stylesheet" href="/App/XploraEcuador/assets/css/dataTables.bootstrap5.min.css">
            <script src="/App/XploraEcuador/assets/js/jquery-3.5.1.js"></script>
            <script src="/App/XploraEcuador/assets/js/bootstrap-4.0.0.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
            <script src="/App/XploraEcuador/assets/js/jquery.dataTables.min.js"></script>
            <script src="/App/XploraEcuador/assets/js/dataTables.bootstrap5.min.js"></script>
            <script src="/App/XploraEcuador/assets/js/dataTables.buttons.min.js"></script>
            <script src="https://cdn.datatables.net/select/1.3.1/js/dataTables.select.min.js"></script>
            <script src="https://editor.datatables.net/extensions/Editor/js/dataTables.editor.min.js"></script>
            <script src="/App/XploraEcuador/assets/js/jszip.min.js"></script>
            <script src="/App/XploraEcuador/assets/js/pdfmake.min.js"></script>
            <script src="/App/XploraEcuador/assets/js/vfs_fonts.js"></script>
            <script src="/App/XploraEcuador/assets/js/buttons.html5.min.js"></script>
            <script src="/App/XploraEcuador/assets/js/buttons.print.min.js"></script>
            <script src="/App/XploraEcuador/assets/js/select2.min.js"></script>

            <script type="text/javascript">
                $(document).ready(function() {
                    $('#sidebarCollapse').on('click', function() {
                        $('#sidebar').toggleClass('active');
                        $('.js-example-basic-single').select2();
                    });
                });
            </script>

            <script>
                function deleteRow(id) {
                    $.ajax({
                        type: "POST",
                        url: "updates/delete_ruta.php",
                        data: {
                            id: id
                        },
                        beforeSend: function() {
                            $("#loading").css("display", "block");
                        },
                        success: function(data_update) {
                            $("#loading").css("display", "none");
                            if (data_update == 1) {
                                Swal.fire({
                                    title: "Rutero",
                                    text: "Ruta eliminada",
                                    type: "success"
                                });
                            } else {
                                Swal.fire({
                                    title: "Rutero",
                                    text: "Error de eliminación!",
                                    type: "error"
                                });
                            }
                        },
                        error: function(XMLHttpRequest, textStatus, errorThrown) {
                            alert("Status: " + textStatus);
                            alert("Error: " + errorThrown);
                        }
                    });
                }

                function getCanales() {
                    $.ajax({
                        type: "POST",
                        url: "getters/getCanales.php",
                        data: null,
                        beforeSend: function() {
                            $("#loading").css("display", "block");
                        },
                        success: function(data) {
                            $("#canales").html(data);
                            $("#loading").css("display", "none");
                        }
                    });
                }

                function getCodigosPDV(canal, tipo) {
                    formdata = "&canal=" + canal + "&tipo=" + tipo;
                    $.ajax({
                        type: "POST",
                        url: "getters/getCodigosPDV.php",
                        data: formdata,
                        beforeSend: function() {
                            $("#loading").css("display", "block");
                        },
                        success: function(data) {
                            console.log("TIPO: " + tipo);
                            if (tipo == "new") {
                                $("#codigos_pdv").html(data);
                            } else {
                                $("#codigoEdit").html(data);
                            }
                            $("#loading").css("display", "none");
                        }
                    });
                }

                function getUsuarios() {
                    $.ajax({
                        type: "POST",
                        url: "getters/getUsuarios.php",
                        data: null,
                        beforeSend: function() {
                            $("#loading").css("display", "block");
                        },
                        success: function(data) {
                            $("#usuarios").html(data);
                            $("#usuarioEdit").html(data);
                            $("#loading").css("display", "none");
                        }
                    });
                }

                function getNotificaciones() {
                    $.ajax({
                        type: "POST",
                        url: "getters/getNotificaciones.php",
                        data: null,
                        beforeSend: function() {
                            // $("#loading").css("display", "block");
                        },
                        success: function(data) {
                            $("#notificaciones").html(data);
                            // $("#loading").css("display", "none");
                        }
                    });
                }

                function buscarRuta() {
                    if ($("#fechaInicioFiltro").val() && $("#fechaFinFiltro").val()) {
                        var fechaInicialB = $("#fechaInicioFiltro").val();
                        var fechaFinB = $("#fechaFinFiltro").val();

                        $.ajax({
                            type: "POST",
                            url: "get_table_rutero.php",
                            data: {
                                fechaInicio: fechaInicialB,
                                fechaFin: fechaFinB
                            },
                            beforeSend: function() {
                                $("#loading").css("display", "block");
                            },
                            success: function(data) {
                                $("#loading").css("display", "none");
                                if (data != "") {
                                    $("#data-result").html(data);
                                    var table = $('#rutero-table').DataTable({
                                        lengthMenu: [10, 25, 50, 75, 100],
                                        responsive: true,
                                        "dom": 'lBftpi',
                                        buttons: [
                                            'copy', 'csv', 'excel', 'pdf', 'print'
                                        ],
                                        columnDefs: [{
                                            targets: -1,
                                            data: null,
                                            defaultContent: "<div class='btn-group'><a href='#' class='edit-ruta btn btn-info'><span><i class='material-icons'>edit</i></span></a><a href='#' class='delete-ruta btn btn-danger'><span><i class='material-icons'>delete</i></span></a></div>"
                                        },
                                        {
                                            targets: [1],
                                            visible: false,
                                            searchable: false
                                        },
                                        {
                                            targets: [2],
                                            visible: false,
                                            searchable: false
                                        },
                                        {
                                            targets: [3],
                                            visible: false,
                                            searchable: false
                                        },
                                        {
                                            targets: [10],
                                            visible: false,
                                            searchable: false
                                        }],
                                        fnInfoCallback: function(settings, json) {
                                            $('#rutero-table .delete-ruta').click(function() {
                                                var data_table = table.row($(this).parents()).data();
                                                var id = data_table[0];
                                                var estado = data_table[4];
                                                console.log("ID: " + id);
                                                //METODO DE ACTUALIZACION
                                                deleteRow(id);
                                                table.row($(this).parents('tr')).remove().draw(false);
                                            });
                                            $('#rutero-table .edit-ruta').click(function() {
                                                var data_table = table.row($(this).parents()).data();
                                                $('#idEdit').val(data_table[0]);
                                                $('#fechaEdit').val(data_table[4]);
                                                $('#codigoEdit').val(data_table[2]);
                                                $('#usuarioEdit').val(data_table[1]);
                                                $('#estadoEdit').val(data_table[3]);
                                                $('#termometroEdit').val(data_table[10]);
                                                $('#myModal').modal('show');
                                            });
                                        }
                                    });
                                }
                            },
                            error: function(XMLHttpRequest, textStatus, errorThrown) {
                                alert("Status: " + textStatus);
                                alert("Error: " + errorThrown);
                            }
                        });
                    } else {
                        /*Swal.fire({
                            icon: 'info',
                            title: 'Búsqueda',
                            text: 'Debe seleccionar las fechas inicio y fin para realizar la búsqueda'
                        });*/
                        Swal.fire({
                            title: 'Búsqueda',
                            text: 'Debe seleccionar las fechas inicio y fin para realizar la búsqueda'
                        });
                    }
                }

                function saveRutero(fecha_inicio, fecha_fin, id_pdv, id_usuario, days, termometro) {
                    formdata = "&fecha_inicio=" + fecha_inicio + 
                            "&fecha_fin=" + fecha_fin + 
                            "&id_pdv=" + id_pdv + 
                            "&id_usuario=" + id_usuario + 
                            "&days=" + days + 
                            "&termometro=" + termometro;

                    $.ajax({
                        type: "POST",
                        url: "rutero_pdvs.php",
                        data: formdata,
                        beforeSend: function() {
                            $("#loading").css("display", "block");
                        },
                        success: function(data) {
                            console.log("DATA: " + data);
                            $("#loading").css("display", "none");
                            if (data.includes('success')) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Rutero',
                                    text: 'Ruta asignada correctamente'
                                });
                                resetFormulario();
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Rutero',
                                    text: 'No se pudo asignar la ruta'
                                });
                            }
                            
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            $("#loading").css("display", "none");
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: 'Ha sucedido un error, intentelo más tarde'
                            });
                        }
                    });
                }

                function editRuta(id, fecha, codigo, usuario, habilitado, termometro) {
                    formdata =  "&id=" + id + 
                                "&fecha=" + fecha + 
                                "&codigo=" + codigo + 
                                "&usuario=" + usuario + 
                                "&habilitado=" + habilitado + 
                                "&termometro=" + termometro;

                    $.ajax({
                        type: "POST",
                        url: "updates/update_ruta.php",
                        data: formdata,
                        beforeSend: function() {
                            $("#loading").css("display", "block");
                        },
                        success: function(data) {
                            console.log("EDIT DATA: " + data);
                            $("#loading").css("display", "none");
                            if (data=="1") {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Rutero',
                                    text: 'Ruta editada correctamente'
                                });
                                $('#myModal').modal('hide');
                                buscarRuta();
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            $("#loading").css("display", "none");
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: 'Ha sucedido un error, intentelo más tarde'
                            });
                        }
                    });
                }

                function resetFormulario() {
                    $('#fechaInicio').val("");
                    $('#fechaFin').val("");
                    $('#codigos_pdv').val("");
                    $('#usuarios').val("");
                    // $('#usuarios option').remove();
                    $("#lunes").prop("checked", false);
                    $("#martes").prop("checked", false);
                    $("#miercoles").prop("checked", false);
                    $("#jueves").prop("checked", false);
                    $("#viernes").prop("checked", false);
                    $("#sabado").prop("checked", false);
                    $("#domingo").prop("checked", false);
                    $("#termometro").prop("checked", false);
                }

                function esFormularioValido(fecha_inicio, fecha_fin, id_pdv, id_usuario, days) {
                    if (!fecha_inicio) {
                        Swal.fire({
                            // icon: 'info',
                            title: 'Nueva ruta',
                            text: 'Debe seleccionar la fecha de inicio'
                        });
                        return false;
                    }
                    if (!fecha_fin) {
                        Swal.fire({
                            // icon: 'info',
                            title: 'Nueva ruta',
                            text: 'Debe seleccionar la fecha de finalización'
                        });
                        return false;
                    }
                    if (!id_pdv) {
                        Swal.fire({
                            // icon: 'info',
                            title: 'Nueva ruta',
                            text: 'Debe seleccionar el código del PDV'
                        });
                        return false;
                    }
                    if (!id_usuario) {
                        Swal.fire({
                            // icon: 'info',
                            title: 'Nueva ruta',
                            text: 'Debe seleccionar el usuario'
                        });
                        return false;
                    }
                    if (days.length === 0) {
                        Swal.fire({
                            // icon: 'info',
                            title: 'Nueva ruta',
                            text: 'Debe seleccionar los días para completar el rutero'
                        });
                        return false;
                    }
                    return true;
                }

                $(document).ready(function() {
                    $('#myModal').modal('hide');
                    getCanales();
                    getCodigosPDV("", "edit");
                    // getNotificaciones();
                    getUsuarios();
                    // setInterval(getNotificaciones, 10000);

                    $("#canales").change(function() {
                        $("#canales option:selected").each(function() {
                            canal = $(this).val();
                            getCodigosPDV(canal, "new");
                        });
                    });

                    // $("#codigos_pdv").change(function() {
                    //     $("#codigos_pdv option:selected").each(function() {
                    //         getUsuarios();
                    //     });
                    // });

                    $("#save").click(function() {
                        var fecha_inicio = $('#fechaInicio').val();
                        var fecha_fin = $('#fechaFin').val();
                        var id_pdv = $('#codigos_pdv').val();
                        var id_usuario = $('#usuarios').val();
                        var termometro = 0;

                        if( $('#termometro').is(':checked') ){
                            termometro = 1;
                        }

                        var days = [];

                        $("ul.days input[type=checkbox]").each(function() {
                            var line = "";
                            if ($(this).is(":checked")) {
                                var day_selected = $(this).val().toString(2);
                                days.push(day_selected);
                            }
                        });

                        if (esFormularioValido(fecha_inicio, fecha_fin, id_pdv, id_usuario, days)) {
                            saveRutero(fecha_inicio, fecha_fin, id_pdv, id_usuario, days, termometro);
                        }
                    });

                    $("button").click(function(event) {
                        if ($(this).attr("value") == "search") {
                            buscarRuta();
                        } else if ($(this).attr("value") == "save-edit") {
                            var id = $('#idEdit').val();
                            var fecha = $('#fechaEdit').val();
                            var codigo = $('#codigoEdit').val();
                            var usuario = $('#usuarioEdit').val();
                            var habilitado = $('#estadoEdit').val();
                            var termometro = $('#termometroEdit').val();

                            editRuta(id, fecha, codigo, usuario, habilitado, termometro);
                        }
                        event.preventDefault();
                    });
                });
            </script>
        </form>
    </body>

    </html>
<?php
// } else {
//     header("Location: /App/XploraEcuador/includes/logout.php");
// }
?>