<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/App/XploraEcuador/mantenimiento_bases/jaboneria_wilson/includes/db_connect.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/App/XploraEcuador/mantenimiento_bases/jaboneria_wilson/includes/functions.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/App/XploraEcuador/mantenimiento_bases/jaboneria_wilson/includes/config.php");
?>
<!DOCTYPE html>
<html>

<head>
    <?php
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 1 Jul 2000 05:00:00 GMT"); // Fecha en el pasado
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
    <link rel="stylesheet" href="/App/XploraEcuador/assets/css/bootstrap-3.3.7.min.css">
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
    <form method="post" action="">
        <div class="wrapper">
            <!-- Sidebar Holder -->
            <nav id="sidebar" class="active">
                <div class="sidebar-header">
                    <h3>Filtros</h3>
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
                            <label for="tipo">Tipo Notificacion</label>
                            <select class="form-control" id="tipo" name="tipo" required>
                                <option value="">Seleccione</option>
                                <option value="perímetro">Perímetro</option>
                                <option value="gps">GPS</option>
                                <!-- <option value="fecha/hora">Fecha y hora</option> -->
                                <!-- <option value="bateria">Batería</option> -->
                            </select>
                        </div>
                    </li>
                    <li class="active">
                        <div class="form-group" style="margin-left:5%;margin-right:5%;">
                            <label for="estado">Estado Notificacion</label>
                            <select class="form-control" id="estado" name="estado" required>
                                <option value="">Seleccione</option>
                                <option value="1">Leído</option>
                                <option value="0">No leído</option>
                            </select>
                        </div>
                    </li>
                </ul>
                <ul class="list-unstyled CTAs">
                    <li><a href="#" class="download" id="buscar" name="buscar">Buscar</a></li>
                </ul>
            </nav>

            <!-- Page Content Holder -->
            <div id="content" style="width:100%;">
                <nav class="navbar navbar-expand-lg navbar-light bg-light">
                    <div class="container-fluid">
                        <button type="button" id="sidebarCollapse" class="btn btn-info">
                            <i class="glyphicon glyphicon-plus"></i>
                            <span>Filtros</span>
                        </button>

                        <button type="button" id="marcar_leido" class="btn btn-success">
                            <span>Marcar todo como leído</span>
                        </button>
                    </div>
                </nav>
                <h2>Notificaciones</h2>
                <div class="row">
                    <div class="col-xl-12 col-lg-12 mb-4">
                        <div class="bg-white rounded-lg p-5 shadow" id="data-result" name="data-result"></div>
                    </div>
                </div>
            </div>
        </div>

        <script type="text/javascript">
            $(document).ready(function() {
                $('#sidebarCollapse').on('click', function() {
                    $('#sidebar').toggleClass('active');
                });
                $('#marcar_leido').on('click', function() {
                    updateAll();
                });
            });
        </script>

        <script>
            function updateRow(id_notificacion) {
                $.ajax({
                    type: "POST",
                    url: "updates/update_notificacion.php",
                    data: {
                        id_notificacion: id_notificacion
                    },
                    beforeSend: function() {
                        $("#loading").css("display", "block");
                    },
                    success: function(data_update) {
                        $("#loading").css("display", "none");
                        if (data_update==1) {
                            Swal.fire({
                                title: "Notificaciones",
                                text: "Notificación marcada como leida",
                                type: "success"
                            });
                        } else {
                            Swal.fire({
                                title: "Notificaciones",
                                text: "Error de actualización!",
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

            function updateAll() {
                $.ajax({
                    type: "POST",
                    url: "updates/update_all_notificacion.php",
                    data: null,
                    beforeSend: function() {
                        $("#loading").css("display", "block");
                    },
                    success: function(data_update) {
                        $("#loading").css("display", "none");
                        if (data_update==1) {
                            Swal.fire({
                                title: "Notificaciones",
                                text: "Todas las notificaciones fueron marcadas como leídas",
                                type: "success"
                            });
                        } else {
                            Swal.fire({
                                title: "Notificaciones",
                                text: "Error de actualización!",
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

            function getNotificaciones(fechaInicialB, fechaFinB, tipo, estado){
                $.ajax({
                    type: "POST",
                    url: "getters/get_table_notificaciones.php",
                    data: {
                        fechaInicio: fechaInicialB,
                        fechaFin: fechaFinB,
                        tipo: tipo,
                        estado: estado
                    },
                    beforeSend: function() {
                        $("#loading").css("display", "block");
                    },
                    success: function(data) {
                        $("#loading").css("display", "none");
                        if (data != "") {
                            $("#data-result").html(data);
                            var table = $('#notification-table').DataTable({
                                lengthMenu: [10, 25, 50, 75, 100],
                                responsive: true,
                                "dom": 'lBftpi',
                                buttons: [
                                    'copy', 'csv', 'excel', 'pdf', 'print'
                                ],
                                columnDefs: [{
                                    targets: -1,
                                    data: null,
                                    defaultContent: "<a href='#' class='modificar-notificacion btn btn-default'><i class='fa fa-edit'></i>Marcar como leído</a></li>"
                                }],
                                fnInfoCallback: function(settings, json) {
                                    $('#notification-table .modificar-notificacion').click(function() {
                                        var data_table = table.row($(this).parents()).data();
                                        var id_notificacion = data_table[0];
                                        var estado = data_table[4];
                                        //METODO DE ACTUALIZACION
                                        updateRow(id_notificacion);
                                        table.row($(this).parents('tr')).remove().draw(false);
                                    });
                                }
                            });
                        } else {
                            $("#data-result").html("");
                        }
                    },
                    error: function(XMLHttpRequest, textStatus, errorThrown) {
                        alert("Status: " + textStatus);
                        alert("Error: " + errorThrown);
                    }
                });
            }

            $(document).ready(function() {
                var fechaInicialB = '';
                var fechaFinB = '';
                var tipo = '';
                var estado = '0';

                getNotificaciones(fechaInicialB, fechaFinB, tipo, estado);

                $("#buscar").click(function() {
                    var fechaInicialB = $("#fechaInicio").val();
                    var fechaFinB = $("#fechaFin").val();
                    var tipo = $("#tipo").val();
                    var estado = $("#estado").val();

                    getNotificaciones(fechaInicialB, fechaFinB, tipo, estado);
                    console.log("FECHA: " + fechaInicialB);
                });
            });
        </script>
    </form>

    <script src="/App/XploraEcuador/assets/js/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="/App/XploraEcuador/assets/js/popper-1.12.9.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="/App/XploraEcuador/assets/js/bootstrap-4.0.0.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
    <script src="/App/XploraEcuador/assets/js/jquery-3.6.0.min.js"></script>

    <!-- DATATABLE -->
    <link rel="stylesheet" href="/App/XploraEcuador/assets/css/dataTables.bootstrap5.min.css">
    <script src="/App/XploraEcuador/assets/js/jquery-3.5.1.js"></script>
    <script src="/App/XploraEcuador/assets/js/jquery.dataTables.min.js"></script>
    <script src="/App/XploraEcuador/assets/js/dataTables.bootstrap5.min.js"></script>
    <script src="/App/XploraEcuador/assets/js/dataTables.buttons.min.js"></script>
    <script src="/App/XploraEcuador/assets/js/jszip.min.js"></script>
    <script src="/App/XploraEcuador/assets/js/pdfmake.min.js"></script>
    <script src="/App/XploraEcuador/assets/js/vfs_fonts.js"></script>
    <script src="/App/XploraEcuador/assets/js/buttons.html5.min.js"></script>
    <script src="/App/XploraEcuador/assets/js/buttons.print.min.js"></script>
    <script src="/App/XploraEcuador/assets/js/select2.min.js"></script>
</body>

</html>