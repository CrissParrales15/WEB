<?php
use Phppot\DataSource;

require_once 'DataSource.php';
$db = new DataSource();
$conn = $db->getConnection();

if (isset($_POST["import"])) {
    
    $fileName = $_FILES["file"]["tmp_name"];
    
    if ($_FILES["file"]["size"] > 0) {
        
        $file = fopen($fileName, "r");
		
		$sqlTruncate = "TRUNCATE table repositorio_locales_dtt2;";
        $db->truncate($sqlTruncate);
		
		while (($column = fgetcsv($file, 10000, ";")) !== FALSE) {
            
            $id = "";
            if (isset($column[0])) {
                $id = mysqli_real_escape_string($conn, $column[0]);
            }
            $channel = "";
			if (isset($column[1])) {
				$channel = mysqli_real_escape_string($conn, $column[1]);
			}

			$subchannel = "";
			if (isset($column[2])) {
				$subchannel = mysqli_real_escape_string($conn, $column[2]);
			}

			$channel_segment = "";
			if (isset($column[3])) {
				$channel_segment = mysqli_real_escape_string($conn, $column[3]);
			}

			$format = "";
			if (isset($column[4])) {
				$format = mysqli_real_escape_string($conn, $column[4]);
			}

			$customer_owner = "";
			if (isset($column[5])) {
				$customer_owner = mysqli_real_escape_string($conn, $column[5]);
			}

			$pos_id = "";
			if (isset($column[6])) {
				$pos_id = mysqli_real_escape_string($conn, $column[6]);
			}

			$pos_name = "";
			if (isset($column[7])) {
				$pos_name = mysqli_real_escape_string($conn, $column[7]);
			}

			$pos_name_dpsm = "";
			if (isset($column[8])) {
				$pos_name_dpsm = mysqli_real_escape_string($conn, $column[8]);
			}

			$zone = "";
			if (isset($column[9])) {
				$zone = mysqli_real_escape_string($conn, $column[9]);
			}

			$region = "";
			if (isset($column[10])) {
				$region = mysqli_real_escape_string($conn, $column[10]);
			}

			$province = "";
			if (isset($column[11])) {
				$province = mysqli_real_escape_string($conn, $column[11]);
			}

			$city = "";
			if (isset($column[12])) {
				$city = mysqli_real_escape_string($conn, $column[12]);
			}

			$address = "";
			if (isset($column[13])) {
				$address = mysqli_real_escape_string($conn, $column[13]);
			}

			$kam = "";
			if (isset($column[14])) {
				$kam = mysqli_real_escape_string($conn, $column[14]);
			}

			$sales_executive = "";
			if (isset($column[15])) {
				$sales_executive = mysqli_real_escape_string($conn, $column[15]);
			}

			$merchandising = "";
			if (isset($column[16])) {
				$merchandising = mysqli_real_escape_string($conn, $column[16]);
			}

			$supervisor = "";
			if (isset($column[17])) {
				$supervisor = mysqli_real_escape_string($conn, $column[17]);
			}

			$dpsm = "";
			if (isset($column[18])) {
				$dpsm = mysqli_real_escape_string($conn, $column[18]);
			}

			$status = "";
			if (isset($column[19])) {
				$status = mysqli_real_escape_string($conn, $column[19]);
			}

			$tipo = "";
			if (isset($column[20])) {
				$tipo = mysqli_real_escape_string($conn, $column[20]);
			}

			$latitud = "";
			if (isset($column[21])) {
				$latitud = mysqli_real_escape_string($conn, $column[21]);
			}

			$longitud = "";
			if (isset($column[22])) {
				$longitud = mysqli_real_escape_string($conn, $column[22]);
			}

			$foto = "";
			if (isset($column[23])) {
				$foto = mysqli_real_escape_string($conn, $column[23]);
			}
			
			$segmentacion = "";
			if (isset($column[24])) {
				$segmentacion = mysqli_real_escape_string($conn, $column[24]);
			}
			
			$compras = "";
			if (isset($column[25])) {
				$compras = mysqli_real_escape_string($conn, $column[25]);
			}

			$activar = "";
			if (isset($column[26])) {
				$activar = mysqli_real_escape_string($conn, $column[26]);
			}
            
			$numero_controller = "";
			if (isset($column[27])) {
				$numero_controller = mysqli_real_escape_string($conn, $column[27]);
			}

			$distancia = "";
			if (isset($column[28])) {
				$distancia = mysqli_real_escape_string($conn, $column[28]);
			}

			$perimetro = "";
			if (isset($column[29])) {
				$perimetro = mysqli_real_escape_string($conn, $column[29]);
			}
            
            $sqlInsert = "INSERT INTO repositorio_locales_dtt2 (id,channel,subchannel,channel_segment,format,customer_owner,pos_id,pos_name,pos_name_dpsm,zone,region,province,city,address,kam,sales_executive,merchandising,supervisor,dpsm,status,tipo,latitud,longitud,foto,segmentacion,compras,activar,numero_controller,distancia,perimetro)
                   values (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $paramType = "isssssssssssssssssssssssssssss";
            $paramArray = array(
                $id,
				$channel,
				$subchannel,
				$channel_segment,
				$format,
				$customer_owner,
				$pos_id,
				$pos_name,
				$pos_name_dpsm,
				$zone,
				$region,
				$province,
				$city,
				$address,
				$kam,
				$sales_executive,
				$merchandising,
				$supervisor,
				$dpsm,
				$status,
				$tipo,
				$latitud,
				$longitud,
				$foto,
				$segmentacion,
				$compras,
				$activar,
				$numero_controller,
				$distancia,
				$perimetro
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
$(document).ready(function() {
    $("#frmCSVImport").on("submit", function () {

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
    <script src="https://editor.datatables.net/extensions/Editor/js/dataTables.editor.min.js"></script>
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
					// $("#loading").css("display", "block");
				},
				success: function(data) {
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
					alert("Status: " + textStatus);
					alert("Error: " + errorThrown);
				}
			});
		});
	</script>
</body>

</html>