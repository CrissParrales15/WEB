<?php
use Phppot\DataSource;

require_once '../DataSource.php';
$db = new DataSource();
$conn = $db->getConnection();

if (isset($_POST["import"])) {

    $fileName = $_FILES["file"]["tmp_name"];

    if ($_FILES["file"]["size"] > 0) {

        $file = fopen($fileName, "r");
		
		
		$sqlTruncate2 = "TRUNCATE table alertas_correo_gestion_supervisores;";
        $db->truncate($sqlTruncate2);

		while (($column = fgetcsv($file, 10000, ";")) !== FALSE) {
            
            $id = "";
            if (isset($column[0])) {
                $id = mysqli_real_escape_string($conn, $column[0]);
            }


			$cargo = "";
			if (isset($column[1])) {
				$cargo = mysqli_real_escape_string($conn, $column[1]);
			}

			$nombre = "";
			if (isset($column[2])) {
				$nombre = mysqli_real_escape_string($conn, $column[2]);
			}
			
            $canal = "";
			if (isset($column[3])) {
				$canal = mysqli_real_escape_string($conn, $column[3]);
			}
            
            $zona = "";
			if (isset($column[4])) {
				$zona = mysqli_real_escape_string($conn, $column[4]);
			}
            
            $correo = "";
			if (isset($column[5])) { 
                $correo = mysqli_real_escape_string($conn, $column[5]);
			}
            
            $correos_cc = "";
			if (isset($column[6])) { 
                $correos_cc = mysqli_real_escape_string($conn, $column[6]);
			}

            $correos_cco = "";
			if (isset($column[7])) {
				$correos_cco = mysqli_real_escape_string($conn, $column[7]);  
			}
	
            $sqlInsert = "INSERT INTO alertas_correo_gestion_supervisores (id, cargo, nombre, canal, zona, correo,correos_cc,correos_cco) VALUES  (?,?,?,?,?,?,?,?)";
            $paramType = "isssssss";    
            $paramArray = array(
                $id,
                $cargo,
                $nombre,
                $canal,
                $zona,
                $correo,
                $correos_cc,
                $correos_cco
            );
            $insertId = $db->insert($sqlInsert, $paramType, $paramArray);
            
            if (! empty($insertId)) {
                $type = "success";
                $message = "CSV Data Imported into the Database";
            } else {
                $type = "error";
                $message = "Problem in Importing CSV Data";
            }
        }


    }
}
?>
<html>

<head>
    <!-- Bootstrap CSS CDN -->
    <link rel="stylesheet" href="/App/XploraEcuador/assets/css/bootstrap-5.1.3.min.css">
    <!-- Our Custom CSS -->
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
        $(document).ready(function () {
            $("#frmCSVImport").on("submit", function () {
                $("#loading").css("display", "block");
                $("#response").attr("class", "");
                $("#response").html("");
                var fileType = ".csv";
                var regex = new RegExp("([a-zA-Z0-9\s_\\.\-:])+(" + fileType + ")$");
                if (!regex.test($("#file").val().toLowerCase())) {
                    $("#response").addClass("error");
                    $("#response").addClass("display-block");
                    $("#response").html("Invalid File. Upload : <b>" + fileType + "</b> Files.");
                    return false;
                }
                return true;
            });
        });
    </script>
</head>

<body> 

    <div id="loading"
        style=" display: block;position: fixed; width: 100%; height: 100%; top: 0px; left: 0px; z-index: 999999; overflow: auto; background-color: rgba(0, 0, 0, 0.498039);">
        <img style="position: absolute; top: 50% !important; left: 47% !important;"
            src="/CER/assets/dist/img/loader_1.gif">
    </div>
    <!-- Page Content Holder -->
    <div id="content" style="width:100%;">
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container-fluid">
                <h3>Base Correos Gestion del día Mercaderistas</h3>
            </div> 
        </nav>

        <div id="response" class="<?php if (!empty($type)) {
            echo $type . " display-block";
        } ?>">
            <?php
            if (!empty($message)) {
                echo $message;
            }
            ?>
        </div>

        <div class="row">
            <form class="form-horizontal" action="" method="post" name="frmCSVImport" id="frmCSVImport"
                enctype="multipart/form-data">
                <div class="input-row">
                    <label class="col-md-4 control-label">Choose CSV File</label> <input type="file" name="file"
                        id="file" accept=".csv">
                    <button type="submit" id="submit" name="import" class="btn-submit">Import</button>
                    <br />
                </div>
            </form>
        </div>

        <div class="row">
            <div class="col-xl-12 col-lg-12 mb-4">
                <div class="bg-white rounded-lg p-5 shadow" id="data-result" name="data-result"></div>
            </div>
        </div>
    </div>

    <script src="/App/XploraEcuador/assets/js/jquery-3.2.1.slim.min.js"
        integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN"
        crossorigin="anonymous"></script>
    <script src="/App/XploraEcuador/assets/js/popper-1.12.9.min.js"
        integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q"
        crossorigin="anonymous"></script>
    <script src="/App/XploraEcuador/assets/js/jquery-3.6.0.min.js"></script>

    <!-- DATATABLE -->
    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.3/css/bootstrap.min.css"> -->
    <link rel="stylesheet" href="/App/XploraEcuador/assets/css/dataTables.bootstrap5.min.css">
    <script src="/App/XploraEcuador/assets/js/jquery-3.5.1.js"></script>
    <script src="/App/XploraEcuador/assets/js/bootstrap-4.0.0.min.js"
        integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl"
        crossorigin="anonymous"></script>
    <script src="/App/XploraEcuador/assets/js/jquery.dataTables.min.js"></script>
    <script src="/App/XploraEcuador/assets/js/dataTables.bootstrap5.min.js"></script>
    <script src="/App/XploraEcuador/assets/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/select/1.3.1/js/dataTables.select.min.js"></script>
    <!-- <script src="https://editor.datatables.net/extensions/Editor/js/dataTables.editor.min.js"></script> -->  
    <script src="/App/XploraEcuador/assets/js/jszip.min.js"></script>
    <script src="/App/XploraEcuador/assets/js/pdfmake.min.js"></script>
    <script src="/App/XploraEcuador/assets/js/vfs_fonts.js"></script>
    <script src="/App/XploraEcuador/assets/js/buttons.html5.min.js"></script>
    <script src="/App/XploraEcuador/assets/js/buttons.print.min.js"></script>
    <script src="/App/XploraEcuador/assets/js/select2.min.js"></script>

    <script>
        $(document).ready(function () {
            $.ajax({
                type: "POST",
                url: "../getters/get_table_correos_gestion_supervisores.php",
                data: null, 
                beforeSend: function () {
                    $("#loading").css("display", "block");
                },
                success: function (data) {
                    $("#loading").css("display", "none");
                    /*Swal.fire({
                        title: "Datos Cargados Correctamente",
                        icon: "success"
                    });*/
                    if (data != "") {
                        $("#data-result").html(data);
                        $('#table').DataTable({
                            "scrollX": true,
                            lengthMenu: [10, 25, 50, 75, 100],
                            responsive: true,
                            "dom": 'lBftpi',
                            buttons: [
                                'copy', 'csv', 'excel', 'pdf', 'print'
                            ],
                        });
                    }
                },
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    alert("Status: " + textStatus);
                    alert("Error: " + errorThrown);
                    $("#loading").css("display", "none");
                }
            });
        });
    </script>
</body>

</html>