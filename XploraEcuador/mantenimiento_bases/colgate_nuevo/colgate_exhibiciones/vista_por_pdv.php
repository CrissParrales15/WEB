<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
    <!-- EXPORTAR -->
    <script src="https://cdn.datatables.net/buttons/2.3.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.2/js/buttons.print.min.js"></script>

    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.2/css/buttons.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.2/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="main.css">

    <script type="text/javascript">
        $(document).ready(function() {
            let element = document.getElementById("response");
            $("#frmCSVImport").on("submit", function() {

                $("#response").attr("class", "");
                $("#response").html("");
                var fileType = ".csv";
                var regex = new RegExp("([a-zA-Z0-9\s\\.\-:])+(" + fileType + ")$");
                if (!regex.test($("#file").val().toLowerCase())) {
                    $("#response").addClass("error");
                    $("#response").addClass("display-block");
                    $("#response").html("Invalid File. Upload : <b>" + fileType + "</b> Files.");
                    return false;
                }
                return true;
            });

            $(document).on('click', '#mostrarModal', function() {
                Swal.fire({
                    html: $('.modal__container').html(),
                    showConfirmButton: false,
                    showCloseButton: true,
                })
            })

            if (screen.width >= 950) {
                $('#userTable').DataTable({
                    dom: 'rtip',
                });
            } else {
                $('#userTable').DataTable({
                    dom: 'rtip',
                    scrollX: true,
                    responsive: true
                });

            }


        });
    </script>

    <style>
        .title {
            font-size: medium;
            padding: 5px;
            border-radius: 8px;
        }

        .body {
            z-index: 1;
            padding: 10px;
            font-size: large;
            border-radius: 8px;
            position: relative;
            background-color: #F3F3F5;
        }

        .color2 {
            background: #000F8F !important;
            color: var(--blanco);
        }

        h2 {
            color: #000F8F;
        }

        #avance_general {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            background-color: #61F043;
            z-index: -1;
        }
    </style>
</head>

<body>
    <?php

    use Phppot\DataSource;

    require_once 'DataSource.php';
    $db = new DataSource();
    $conn = $db->getConnection();
    $pdv = $_GET['codigo_pdv'];
    $local = $_GET['local'];

    ?>
    <div class="">
        <h1>AVANCE DE RELEVO POR PDV</h1>
        <h2><?php echo strtoupper($local) ?></h2>
    </div>

    <div class="container">
        <div class="outer-scontainer">
            <?php
            $sqlSelect = "SELECT * FROM informativo_pdv WHERE pos_id = ?";
            $paramType = 's';
            $paramArray = array($pdv);
            $result = $db->select($sqlSelect, $paramType, $paramArray);

            if (!empty($result)) {
                $general = $result[0]['avance_general']

            ?>

                <div id="tabla_avances">

                    <div class="title color2"> PRECIOS </div>
                    <div id="precios_body" class="body">
                        <?php echo $result[0]['precios']; ?>
                    </div>

                    <div class="title color2"> SOS </div>
                    <div id="precios_body" class="body">
                        <?php echo $result[0]['sos']; ?>
                    </div>

                    <div class="title color2"> ONPACKS </div>
                    <div id="precios_body" class="body">
                        <?php echo $result[0]['onpacks']; ?>
                    </div>

                    <div class="title color2"> INVENTARIO + SUGERIDO </div>
                    <div id="precios_body" class="body">
                        <?php echo $result[0]['invent_sugerido']; ?>
                    </div>

                    <div class="title color2"> ANTES Y DESPUES </div>
                    <div id="precios_body" class="body">
                        <?php echo $result[0]['antes_despues']; ?>
                    </div>

                    <div class="title color2"> PROMOCIONES </div>
                    <div id="precios_body" class="body">
                        <?php echo $result[0]['promociones']; ?>
                    </div>

                    <div class="title color1"> AVANCE GENERAL </div>
                    <div class="body">
                        <?php echo $general; ?>
                        <div id="avance_general" style="width:<?php echo $general ?>" class="body"></div>
                    </div>

                </div>

            <?php
            } else {
                echo 'NO HAY REGISTROS PARA ESTE PDV';
            }
            ?>
        </div>
    </div>

</body>

</html>