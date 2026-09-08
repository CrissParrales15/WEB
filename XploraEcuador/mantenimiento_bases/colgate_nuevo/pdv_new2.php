<?php
use Phppot\DataSource;

require_once 'DataSource.php';
$db = new DataSource();
$conn = $db->getConnection();

function randomColor()
{
    $hexa = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F');
    $num = count($hexa);
    $color = '';
    for ($i = 0; $i < 6; $i++) {
        $index = rand(0, $num - 1);
        $color .= $hexa[$index];
    }
    return $color;
}

function validarCoordenadas($latitud = "0", $longitud = "0") {
	if ($latitud !== "" && $longitud !== "") {
		$latitud = str_replace(',', '.', $latitud);
		$longitud = str_replace(',', '.', $longitud);

		$latitud = validarYCorregirCoordenada($latitud);
		$longitud = validarYCorregirCoordenada($longitud);
	}
    return [$latitud, $longitud];
}

function validarYCorregirCoordenada($coordenada) {
    $coordenada = str_replace(',', '.', $coordenada);

    if (strpos($coordenada, '-') === 0) {
        $coordenada = '-' . preg_replace('/[^0-9.]/', '', substr($coordenada, 1));
    } else {
        $coordenada = preg_replace('/[^0-9.]/', '', $coordenada);
    }

    $partes = explode('.', $coordenada);
    if (count($partes) > 2) {
        $coordenada = $partes[0] . '.' . implode('', array_slice($partes, 1));
    }

    return $coordenada;
}

if (isset($_POST["import"])) {

    $fileName = $_FILES["file"]["tmp_name"];

    if ($_FILES["file"]["size"] > 0) {

        $file = fopen($fileName, "r");


        $tamano_lote = 500;//cantidad minima de registros

        $contador = 0;
        $lote = array();

		$sqlTruncate1 = "SET FOREIGN_KEY_CHECKS = 0;";
        $db->truncate($sqlTruncate1);

        $sqlTruncate2 = "TRUNCATE table repositorio_locales_dtt2;";
        $db->truncate($sqlTruncate2);

        while (($column = fgetcsv($file, 27000, ";")) !== FALSE) {


            $lote[] = $column;

            $contador++;

            //si ya hay 1000 registros tomados del csv se envia a la funcion para que se isnerten en la BD
            if ($contador == $tamano_lote || feof($file)) {

                procesarLote($db, $lote, $conn);

                //una vez insertdo los 1000 se reinicia contador y array para volver a llenar con los siguientes 1000 registros
                $contador = 0;
                $lote = array();
            }
        }

        //para los registros sobrantes caundo quedan menos de 1000
        if (count($lote) > 0) {
            procesarLote($db, $lote, $conn);

            $contador = 0;
            $lote = array();
        }



    }
}

function procesarLote($db, $lote, $conn)
{


    $sqlInsert = "INSERT INTO repositorio_locales_dtt2 (id,pos_id,sales_executive,channel,subchannel,format,pos_name_dpsm,kam,merchandising,customer_owner,pos_name,dpsm,region,tipo, province,city,zone,address,supervisor,latitud,longitud,channel_segment,visual,coordinador,foto,status,perimetro,distancia,activar,color,tiempo_visita) VALUES ";

    $paramType = "";
    $paramArray = array();

    foreach ($lote as $fila) {
        list($latitudValida, $longitudValida) = validarCoordenadas($fila[19], $fila[20]);

        if ($latitudValida !== "" && $longitudValida !== "") {
            $replace = array("#");
            $new_pos_name = str_replace($replace, "", $fila[10]);

            $sqlInsert .= "(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?),";
            $paramType .= "issssssssssssssssssssssssssssss";

            // Construir el array de parámetros
            $paramArray[] = (isset($fila[0])) ? trim(mysqli_real_escape_string($conn, $fila[0])) : ""; // id
            $paramArray[] = (isset($fila[1])) ? trim(mysqli_real_escape_string($conn, $fila[1])) : ""; // pos_id
            $paramArray[] = (isset($fila[2])) ? trim(mysqli_real_escape_string($conn, $fila[2])) : ""; // sales_executive
            $paramArray[] = (isset($fila[3])) ? trim(mysqli_real_escape_string($conn, $fila[3])) : ""; // channel
            $paramArray[] = (isset($fila[4])) ? trim(mysqli_real_escape_string($conn, $fila[4])) : ""; // subchannel
            $paramArray[] = (isset($fila[5])) ? trim(mysqli_real_escape_string($conn, $fila[5])) : ""; // format
            $paramArray[] = (isset($fila[6])) ? trim(mysqli_real_escape_string($conn, $fila[6])) : ""; // pos_name_dpsm
            $paramArray[] = (isset($fila[7])) ? trim(mysqli_real_escape_string($conn, $fila[7])) : ""; // kam
            $paramArray[] = (isset($fila[8])) ? trim(mysqli_real_escape_string($conn, $fila[8])) : ""; // merchandising
            $paramArray[] = (isset($fila[9])) ? trim(mysqli_real_escape_string($conn, $fila[9])) : ""; // customer_owner
            $paramArray[] = (isset($fila[10])) ? trim(mysqli_real_escape_string($conn, $new_pos_name)) : ""; // pos_name
            $paramArray[] = (isset($fila[11])) ? trim(mysqli_real_escape_string($conn, $fila[11])) : ""; // dpsm
            $paramArray[] = (isset($fila[12])) ? trim(mysqli_real_escape_string($conn, $fila[12])) : ""; // region
            $paramArray[] = (isset($fila[13])) ? trim(mysqli_real_escape_string($conn, $fila[13])) : ""; // tipo
            $paramArray[] = (isset($fila[14])) ? trim(mysqli_real_escape_string($conn, $fila[14])) : ""; // province
            $paramArray[] = (isset($fila[15])) ? trim(mysqli_real_escape_string($conn, $fila[15])) : ""; // city
            $paramArray[] = (isset($fila[16])) ? trim(mysqli_real_escape_string($conn, $fila[16])) : ""; // zone
            $paramArray[] = (isset($fila[17])) ? trim(mysqli_real_escape_string($conn, $fila[17])) : ""; // address
            $paramArray[] = (isset($fila[18])) ? trim(mysqli_real_escape_string($conn, $fila[18])) : ""; // supervisor
            $paramArray[] = (isset($fila[19])) ? trim(mysqli_real_escape_string($conn, $latitudValida)) : ""; // latitud
            $paramArray[] = (isset($fila[20])) ? trim(mysqli_real_escape_string($conn, $longitudValida)) : ""; // longitud
            $paramArray[] = (isset($fila[21])) ? trim(mysqli_real_escape_string($conn, $fila[21])) : ""; // channel_segment
            $paramArray[] = (isset($fila[22])) ? trim(mysqli_real_escape_string($conn, $fila[22])) : ""; // visual
            $paramArray[] = (isset($fila[23])) ? trim(mysqli_real_escape_string($conn, $fila[23])) : ""; // coordinador
            $paramArray[] = (isset($fila[24])) ? trim(mysqli_real_escape_string($conn, $fila[24])) : ""; // foto
            $paramArray[] = (isset($fila[25])) ? trim(mysqli_real_escape_string($conn, $fila[25])) : ""; // status
            $paramArray[] = (isset($fila[26])) ? trim(mysqli_real_escape_string($conn, $fila[26])) : ""; // perimetro
            $paramArray[] = (isset($fila[27])) ? trim(mysqli_real_escape_string($conn, $fila[27])) : ""; // distancia
            $paramArray[] = (isset($fila[28])) ? trim(mysqli_real_escape_string($conn, $fila[28])) : ""; // activar
            $paramArray[] = randomColor(); // color
            $paramArray[] = (isset($fila[29])) ? trim(mysqli_real_escape_string($conn, $fila[29])) : ""; // tiempo_visita
        }
    }

    // Eliminar la coma adicional al final de la sentencia SQL
    $sqlInsert = rtrim($sqlInsert, ',');

    //echo json_encode($paramArray);
    // Ejecutar la inserción
    $insertId = $db->insertMultiple($sqlInsert, $paramType, $paramArray);

    if (!empty($insertId)) {
        $type = "success";
        $message = "CSV Data Imported into the Database";
    } else {
        $type = "error";
        $message = "Problem in Importing CSV Data";
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
$(document).ready(function() {
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

    <div id="loading" class="loading-img"
        style=" display: flex;position: fixed; width: 100%; height: 100%; top: 0px; left: 0px; z-index: 999999; overflow: auto; background-color: rgba(0, 0, 0, 0.498039);">
        <img style="position: absolute; top: 50% !important; left: 47% !important;" src="loader_1.gif">
    </div>

    <!-- Page Content Holder -->
	<div id="content" style="width:100%;">
		<nav class="navbar navbar-expand-lg navbar-light bg-light">
			<div class="container-fluid">
				<h3>Base PDV</h3>
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
			<form class="form-horizontal" action="" method="post" name="frmCSVImport" id="frmCSVImport" enctype="multipart/form-data">
				<div class="input-row">
					<label class="col-md-4 control-label">Choose CSV File</label> <input type="file" name="file" id="file" accept=".csv">
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
    <!-- <script src="https://editor.datatables.net/extensions/Editor/js/dataTables.editor.min.js"></script> -->  
    <script src="/App/XploraEcuador/assets/js/jszip.min.js"></script>
    <script src="/App/XploraEcuador/assets/js/pdfmake.min.js"></script>
    <script src="/App/XploraEcuador/assets/js/vfs_fonts.js"></script>
    <script src="/App/XploraEcuador/assets/js/buttons.html5.min.js"></script>
    <script src="/App/XploraEcuador/assets/js/buttons.print.min.js"></script>
    <script src="/App/XploraEcuador/assets/js/select2.min.js"></script>

	<script>
		$(document).ready(function() {
			$.ajax({
				type: "POST",
				url: "getters/get_table_pdvs.php",
				data: null,
				beforeSend: function() {
					$("#loading").css("display", "block");
				},
				success: function(data) {
                    $("#loading").css("display", "none");
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
				error: function(XMLHttpRequest, textStatus, errorThrown) {
                    $("#loading").css("display", "none");
					alert("Status: " + textStatus);
					alert("Error: " + errorThrown);
				}
			});
		});
	</script>
</body>

</html>