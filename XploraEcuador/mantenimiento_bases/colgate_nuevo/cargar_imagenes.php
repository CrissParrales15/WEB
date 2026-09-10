<?php

require_once '../../includes/upload_azure.php';

use Phppot\DataSource;

require_once 'DataSource.php';
$db = new DataSource();
$conn = $db->getConnection();
//$cuenta = $_GET['cuenta'];
$tamanio = 52000;

$container = 'app/App5pGo/imagenes';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $v = count($_FILES['documento']['name']);
    $v2 = 0;
    foreach ($_FILES['documento']['tmp_name'] as $i => $tmp_name) {
        $v2++;
        if ($_FILES['documento']['name'][$i]) {
            if (
                $_FILES['documento']['type'][$i] == 'image/jpeg' ||
                $_FILES['documento']['type'][$i] == 'image/png'
            ) {
                try {
                    if ($_FILES['documento']['size'][$i] < ($tamanio * 1024)) {
                        date_default_timezone_set("America/Guayaquil");
                        $tipo_archivo = $_FILES['documento']['type'][$i];
                        $temporal = $_FILES['documento']['tmp_name'][$i];
                        $fileName = $_FILES['documento']['name'][$i];
                        $file_name = str_replace(' ', '_', $fileName);
                        $fecha_subida = date("d-m-Y");
                        $directorio = "../imagenes/";

                        if (!file_exists($directorio)) {
                            mkdir($directorio, 0777);
                        }

                        $dir = opendir($directorio);
                        $ruta = $directorio . $file_name;

                        $sqlInsert = "INSERT INTO repositorio_materiales (nombre, tipo, ruta_archivo,cuenta, estado)
                                values (?,?,?,?,?) ON DUPLICATE KEY UPDATE tipo = VALUES(tipo) 
                                , ruta_archivo = VALUES(ruta_archivo) , fecha_subida = VALUES(fecha_subida) , cuenta = VALUES(cuenta) , estado = VALUES(estado)";
                        $paramType = "sssss";
                        $paramArray = array(
                            $fileName,
                            $tipo_archivo,
                            $ruta,
                            'colgate',
                            '1'
                        );
                        $insertId = $db->insert($sqlInsert, $paramType, $paramArray);

                        if (uploadBlobSample($blobClient, $container, $tipo_archivo, $file_name, $temporal)) {
                            // if (move_uploaded_file($temporal, $ruta)) {
                            /*                         echo "<div class='alert alert-success alert-dismissible fade show' style='margin:10px' role='alert'>
                        El documento $file_name se ha guardado correctamente.
                        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                        </div>"; */
                            /*unset($_POST);
                        header("Location: ".$_SERVER['PHP_SELF']);
                        exit; */
                            if ($v == $v2) {
                                if ($v == 1) {
                                    //echo '<script>alert("al archivo se ha cargado correctamente");</script>';
                                    echo "<script> var result = alert('la imagen se ha cargado correctamente'); 
                                if ( result ) {
                                    document.location = 'cargar_imagenes.php'
                                } else {
                                    document.location = 'cargar_imagenes.php'
                                }</script>";
                                } else {
                                    //echo '<script>alert("los archivos se han cargado correctamente");</script>'; 
                                    echo "<script> var result = alert('las imagenes se han cargado correctamente'); 
                                if ( result ) {
                                    document.location = 'cargar_imagenes.php'
                                } else {
                                    document.location = 'cargar_imagenes.php'
                                }</script>";
                                }
                            }
                        } else {
                            /*  echo '<div class="alert alert-danger alert-dismissible fade show" style="margin:10px" role="alert">
                        Error al subir el documento peso superior al permitido !.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>'; */
                            echo '<script>alert("Error al subir el documento peso superior al permitido !.");</script>';
                        }

                        closedir($dir);
                    } else {
                        /*                     echo '<div class="alert alert-danger alert-dismissible fade show" style="margin:10px" role="alert">
                    El documento tiene un peso mayor a 25Mb
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>'; */
                        echo '<script>alert("El documento tiene un peso mayor a 50Mb");</script>';
                    }

                    //  throw new Exception("¡Ha ocurrido un error!");

                    //   echo '<script>alert("¡Ha ocurrido un error!");</script>';

                } catch (Exception $e) {
                    // Manejo de la excepción
                    echo "Excepción capturada: " . $e->getMessage();
                }
            } else {
                /*                 echo '<div class="alert alert-danger alert-dismissible fade show" style="margin:10px" role="alert">
                Solo se admiten documentos PDF o Mp4
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>'; */
                //echo '' .$_FILES['documento']['type'][$i];
                echo '<script>alert("Solo se admiten imagenes");</script>';
            }
        } else {
            /*  echo '<div class="alert alert-danger alert-dismissible fade show" style="margin:10px" role="alert">
            No existen documentos
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>'; */
            echo '<script>alert("No existen documentos");</script>';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="estilos/estilos.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css">
    <!-- Boostrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.2/font/bootstrap-icons.css">

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>

    <title>Carga de Imagenes</title>
</head>

<body>

    <div class="btn-bar">    
 
        <h1 class="">Imágenes<span style="color:#8A21EA"><?php echo $cuenta ?></span></h1>

        <div class="botones">
            <button type="button" id="btnExportar" class="btn white-btn">
                <span class="material-symbols-outlined">file_download</span>
                <div>Descargar Formato</div>
            </button>
            <button type="button" id="mostrarModal" class="btn lilac-btn" onclick="mostrarModal()">
                <span class="material-symbols-outlined">file_upload</span>
                <div>Cargar Archivos</div>
            </button>
            <button type="button" class="btn btn-danger" id="btn_eliminar">Eliminar Seleccionado(s)</button>
<!--             <div class="item">
                <button type="button" class="btn2 btn-orange" id="regresar">Regresar</button>
            </div> -->
        </div>
    </div>

    <section class="detalles">
        <div class="modal__container">
            <form class="form-horizontal" action="" method="post" name="frmCSVImport" id="frmCSVImport"
                enctype="multipart/form-data">
                <div class="input-row">
                    <h2 class="modal__title">Cargar Archivos</h2>
                    <div id="response2"></div>
                    <form action="" method="post" enctype="multipart/form-data">
                        <input accept="image/png , image/jpeg" type="file" name="documento[]" id="file" multiple>
                        <div class="btn-bar botones" style="margin-top: 2%;">
                            <button type="submit" id="submit" name="import" class="btn ok-btn">Subir</button>
                            <button type="button" class="modal_close btn cancel-btn"
                                onclick="cerrarModal()">Cerrar</button>
                        </div>
                    </form>
                </div>
            </form>
        </div>
    </section>


    <div class="container">
        <div class="start">
            <div class="outer-scontainer">
                <table id='tb_imagenes' class="table" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th></th>
                            <th>NOMBRE</th>
                            <th>FORMATO</th>
                            <th>FECHA DE SUBIDA</th>
                            <th>ENLACE</th>
                            <th>ENLACE</th>
                            <th><input class="form-check-input" type="checkbox" value="" id="checkBoxAll">Marcar Todos
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>


    <script>

        $(document).on("click", "#btn_visibility", function () {
            var fila = $(this).closest("tr");
            var id = fila.find('td:eq(0)').text();
            var nombre = fila.find('td:eq(2)').text();
            var estado = fila.find('td:eq(5)').text();

            Swal.fire({
                title: '¿Estás seguro de cambiar el estado de ' + nombre + '?',
                text: "",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#37C976',
                cancelButtonColor: '#FE4F4F',
                confirmButtonText: 'Cambiar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('../../update_estado.php?id=' + id + '&estado=' + estado)
                        .then((res) => {
                            if (!res.ok) {
                                throw new Error("Error en la respuesta");
                            }
                            return res.json();
                        })
                        .then((datos) => {
                            var elemento = fila.find('td:eq(5)').children();
                            if (datos == 'VISIBLE') {
                                elemento.toggleClass('btn-success');
                                elemento.toggleClass('btn-danger');
                                elemento.text('VISIBLE');

                            } else if (datos == 'NO VISIBLE') {
                                elemento.toggleClass('btn-danger');
                                elemento.toggleClass('btn-success');
                                elemento.text('NO VISIBLE');

                            } else if (datos == 'ERROR') {
                                alert('No se pudo cambiar el estado');
                            }
                        });
                }
            })
        })


        $("#frmCSVImport").on("submit", function () {

            $("#response2").attr("class", "");
            $("#response2").html("");

            if ($("#file").val() == "") {
                $("#response2").addClass("error");
                $("#response2").addClass("display-block");
                $("#response2").fadeIn(300);
                $("#response2").html("No hay imagenes elegidas");
                $("#response2").fadeOut(6000);
                console.log('d: ' + $("#file").val());
                return false;
            }
            return true;
        });


        $("#submit").on("click", function () {
            if ($("#file").val() != "") {
                $("#submit").addClass("hidden");
                $("#loading-btn").removeClass("hidden");
            }
        });


        let $checkBoxAll = document.getElementById("checkBoxAll");

        $checkBoxAll.addEventListener("click", () => {

            let $checkBoxItems = document.querySelectorAll(".checkBox");

            if ($checkBoxAll.checked) {
                $checkBoxItems.forEach(element => element.checked = true);
            } else {
                $checkBoxItems.forEach(element => element.checked = false);
            }

        })


        $(document).on("click", "#btn_eliminar", function () {
            const arr_ids = [];
            const arr_nombres = [];
            const checkBoxSelecionados = [];
            const idsSelecionados = [];
            const nombresSelecionados = [];
            let parametro = new URLSearchParams(document.location.search);
            let cuenta = parametro.get("cuenta");

            let $checkBoxItems = document.querySelectorAll(".checkBox");
            $checkBoxItems = Array.from($checkBoxItems);

            let $ids = document.querySelectorAll('tr > td:first-child').forEach(element => {
                arr_ids.push(element.innerText);
            });

            let $nombres = document.querySelectorAll('tr > td:nth-child(3)').forEach(element => {
                arr_nombres.push(element.innerText);
            });

            for (let i = 0; i < $checkBoxItems.length; i++) {

                if ($checkBoxItems[i].checked) {
                    idsSelecionados.push(arr_ids[i]);
                    nombresSelecionados.push(arr_nombres[i]);
                }
            }

            if (idsSelecionados.length == 0) {

                Swal.fire('Atención!', 'Debe marcar al menos un registro.', 'warning');

            } else {

                Swal.fire({
                    title: '¿Estás seguro que desea eliminar los registros seleccionados?',
                    text: "",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#37C976',
                    cancelButtonColor: '#FE4F4F',
                    confirmButtonText: 'Eliminar'
                }).then((result) => {
                    if (result.isConfirmed) {

                        fetch('delete_material.php?ids=' + idsSelecionados + '&nombres=' + nombresSelecionados)
                            .then((res) => {
                                if (!res.ok) {
                                    throw new Error("Error en la respuesta");
                                }
                                return res.json();
                            })
                            .then((datos) => {

                                $('#tb_imagenes').DataTable().ajax.reload();

                                Swal.fire("Exito!", `${datos}`, "success");
                                $checkBoxAll.checked = false;

                            });

                    }
                })

            }
        })

        $(document).on("click", "#mostrarContraseña", function () {
            let inputContraseña = document.getElementById('contraseña');

            if (inputContraseña.type === 'password') {
                inputContraseña.type = "text";
            } else {
                inputContraseña.type = "password";
            }
        })

        $('#regresar').on('click', function () {
            //window.history.back();
            //window.location.replace('index.php?cuenta=' + cuenta);
            window.location.replace('../index.php');
        });
    </script>
    <script src="js/main.js"></script> 
</body>

</html>