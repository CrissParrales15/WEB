<?php
use Phppot\DataSource;

require_once 'DataSource.php';
$db = new DataSource();
$conn = $db->getConnection();

if (isset($_POST["import"])) {
    
    $fileName = $_FILES["file"]["tmp_name"];
    
    if ($_FILES["file"]["size"] > 0) {
        
        $file = fopen($fileName, "r"); 
		
		$sqlTruncate = "TRUNCATE table repositorio_ppts_exh;";
        $db->truncate($sqlTruncate);
		
		// Variables para el batch insert
		$batchSize = 1000; // Número de registros por lote
		$batchData = array();
		$rowCount = 0;
		$totalRows = 0;
		
		while (($column = fgetcsv($file, 10000, ";")) !== FALSE) {
            
            $id = "";
            if (isset($column[0])) {
                $id = mysqli_real_escape_string($conn, $column[0]);
            }
            $year = "";
			if (isset($column[1])) {
				$year = mysqli_real_escape_string($conn, $column[1]);
			}

			$month = "";
			if (isset($column[2])) {
				$month = mysqli_real_escape_string($conn, $column[2]);
			}

			$day = "";
			if (isset($column[3])) {
				$day = mysqli_real_escape_string($conn, $column[3]);
			}

			$cp_code = "";
			if (isset($column[4])) {
				$cp_code = mysqli_real_escape_string($conn, $column[4]);
			}

			$trade = "";
			if (isset($column[5])) {
				$trade = mysqli_real_escape_string($conn, $column[5]);
			}
			
			$retail_environment = "";
			if (isset($column[6])) {
				$retail_environment = mysqli_real_escape_string($conn, $column[6]);
			}
			
			$pos = "";
			if (isset($column[7])) {
				$pos = mysqli_real_escape_string($conn, $column[7]);
			}
			
			$exhibition_tool = "";
			if (isset($column[8])) {
				$exhibition_tool = mysqli_real_escape_string($conn, $column[8]);
			}
			
			$manufacturer = "";
			if (isset($column[9])) {
				$manufacturer = mysqli_real_escape_string($conn, $column[9]);
			}
			
			$category = "";
			if (isset($column[10])) {
				$category = mysqli_real_escape_string($conn, $column[10]);
			}
			
			$subcategory = "";
			if (isset($column[11])) {
				$subcategory = mysqli_real_escape_string($conn, $column[11]);
			}
			
			$n_convenio = "";
			if (isset($column[12])) {
				$n_convenio = mysqli_real_escape_string($conn, $column[12]);
			}
			
			$tipo_herramienta = "";
			if (isset($column[13])) {
				$tipo_herramienta = mysqli_real_escape_string($conn, $column[13]);
			}
			
			$clasificacion = "";
			if (isset($column[14])) {
				$clasificacion = mysqli_real_escape_string($conn, $column[14]);
			}
			
			$campaña = "";
			if (isset($column[15])) {
				$campaña = mysqli_real_escape_string($conn, $column[15]);
			}
			
			$photo_url = "";
			if (isset($column[16])) {
				$photo_url = mysqli_real_escape_string($conn, $column[16]);
			}
			
			$customer = "";
			if (isset($column[17])) {
				$customer = mysqli_real_escape_string($conn, $column[17]);
			}
			
			$city = "";
			if (isset($column[18])) {
				$city = mysqli_real_escape_string($conn, $column[18]);
			}
			
			$server_date = "";
			if (isset($column[19])) {
				$server_date = mysqli_real_escape_string($conn, $column[19]);
			}
			
			$status = "";
			if (isset($column[20])) {
				$status = mysqli_real_escape_string($conn, $column[20]);
			}
			
			// Agregar al batch
			$batchData[] = array(
				$id,
				$year,
				$month,
				$day,
				$cp_code,
				$trade,
				$retail_environment,
				$pos,
				$exhibition_tool,
				$manufacturer,
				$category,
				$subcategory,
				$n_convenio,
				$tipo_herramienta,
				$clasificacion,
				$campaña,
				$photo_url,
				$customer,
				$city,
				$server_date,
				$status
			);
			
			$rowCount++;
			$totalRows++;
			
			// Si alcanzamos el tamaño del batch, insertar
			if ($rowCount >= $batchSize) {
				insertBatch($conn, $batchData);
				$batchData = array();
				$rowCount = 0;
			}
		}
		
		// Insertar los registros restantes (menos de 1000)
		if (!empty($batchData)) {
			insertBatch($conn, $batchData);
		}
		
		fclose($file);
		
		$type = "success";
		$message = "CSV Data Imported into the Database. Total: $totalRows registros";
	}
}

// Función para insertar un lote de registros
function insertBatch($conn, $batchData) {
	if (empty($batchData)) {
		return;
	}
	
	// Construir la consulta SQL con múltiples VALUES
	$sql = "INSERT INTO repositorio_ppts_exh 
			(id, year, month, day, cp_code, trade, retail_environment, pos, exhibition_tool, manufacturer, category, subcategory, n_convenio, tipo_herramienta, clasificacion, campaña, photo_url, customer, city, server_date, status) VALUES ";
	
	$values = array();
	$params = array();
	$types = "";
	
	foreach ($batchData as $row) {
		$values[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		$types .= "issssssssssssssssssss";
		$params[] = $row[0];
		$params[] = $row[1];
		$params[] = $row[2];
		$params[] = $row[3];
		$params[] = $row[4];
		$params[] = $row[5];
		$params[] = $row[6];
		$params[] = $row[7];
		$params[] = $row[8];
		$params[] = $row[9];
		$params[] = $row[10];
		$params[] = $row[11];
		$params[] = $row[12];
		$params[] = $row[13];
		$params[] = $row[14];
		$params[] = $row[15];
		$params[] = $row[16];
		$params[] = $row[17];
		$params[] = $row[18];
		$params[] = $row[19];
		$params[] = $row[20];
	}
	
	$sql .= implode(", ", $values);
	
	// Preparar y ejecutar la consulta
	$stmt = $conn->prepare($sql);
	if ($stmt) {
		// Bind parameters
		$stmt->bind_param($types, ...$params);
		$stmt->execute();
		$stmt->close();
	}
}
?>
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
        
        // MOSTRAR MENSAJE DE CARGANDO
        Swal.fire({
            title: 'Cargando...',
            text: 'Por favor espera mientras se procesa el archivo',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

	    $("#response").attr("class", "");
        $("#response").html("");
        var fileType = ".csv";
        var regex = new RegExp("([a-zA-Z0-9\s_\\.\-:])+(" + fileType + ")$");
        if (!regex.test($("#file").val().toLowerCase())) {
        	    $("#response").addClass("error");
        	    $("#response").addClass("display-block");
            $("#response").html("Invalid File. Upload : <b>" + fileType + "</b> Files.");
            // CERRAR MENSAJE DE CARGANDO SI HAY ERROR
            Swal.close();
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
				<h3>Base Repositorio PPTS Exhibiciones</h3>
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
    <link rel="stylesheet" href="/App/XploraEcuador/assets/css/dataTables.bootstrap5.min.css">
    <script src="/App/XploraEcuador/assets/js/jquery-3.5.1.js"></script>
    <script src="/App/XploraEcuador/assets/js/bootstrap-4.0.0.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
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
			$.ajax({
				type: "POST",
				url: "getters/get_table_ppts_exh2.php",
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