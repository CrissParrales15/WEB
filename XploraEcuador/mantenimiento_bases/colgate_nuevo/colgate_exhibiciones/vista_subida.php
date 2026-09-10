<?php

use Phppot\DataSource;

require_once 'DataSource.php';
$db = new DataSource();
$conn = $db->getConnection();

if (isset($_POST["import"])) {

	$fileName = $_FILES["file"]["tmp_name"];

	if ($_FILES["file"]["size"] > 0) {

		$file = fopen($fileName, "r");

		$sqlTruncate = "TRUNCATE table informativo_pdv;";
		$db->truncate($sqlTruncate);

		while (($column = fgetcsv($file, 10000, ";")) !== FALSE) {
			
			$pos_id = "";
			if (isset($column[0])) {
				$pos_id = mysqli_real_escape_string($conn, $column[0]);
			}
			$local = "";
			if (isset($column[1])) {
				$local = mysqli_real_escape_string($conn, $column[1]);
			}
			$precios = "";
			if (isset($column[2])) {
				$precios = mysqli_real_escape_string($conn, $column[2]);
			}
			$sos = "";
			if (isset($column[3])) {
				$sos = mysqli_real_escape_string($conn, $column[3]);
			}
			$onpacks = "";
			if (isset($column[4])) {
				$onpacks = mysqli_real_escape_string($conn, $column[4]);
			}
			$inv_sug = "";
			if (isset($column[5])) {
				$inv_sug = mysqli_real_escape_string($conn, $column[5]);
			}
			$antes_despues = "";
			if (isset($column[6])) {
				$antes_despues = mysqli_real_escape_string($conn, $column[6]);
			}
			$promo = "";
			if (isset($column[7])) {
				$promo = mysqli_real_escape_string($conn, $column[7]);
			}
			$avances = "";
			if (isset($column[8])) {
				$avances = mysqli_real_escape_string($conn, $column[8]);
			}

			$sqlInsert = "INSERT INTO informativo_pdv (pos_id, local, precios, sos, onpacks, invent_sugerido, antes_despues, promociones, avance_general)
			values (?,?,?,?,?,?,?,?,?)";
			$paramType = "sssssssss";
			$paramArray = array($pos_id, $local, $precios, $sos, $onpacks, $inv_sug, $antes_despues, $promo, $avances);
			$insertId = $db->insert($sqlInsert, $paramType, $paramArray);

			if (!empty($insertId)) {
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
<!DOCTYPE html>
<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
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

	<title>Informativo PDV</title>
	<script type="text/javascript">
		$(document).ready(function() {
			let element = document.getElementById("response");
			$("#frmCSVImport").on("submit", function() {

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

			if (element.classList.contains("success")) {
				Swal.fire({
					icon: 'success',
					title: 'Subido correctamente',
					showConfirmButton: false,
					timer: 1500
				})
			} else if (element.classList.contains("error")) {
				Swal.fire({
					icon: 'warning',
					title: 'Ocurrió un error',
					showConfirmButton: false,
					timer: 1500
				})
			}

			$(document).on('click', '#mostrarModal', function() {
				Swal.fire({
					html: $('.modal__container').html(),
					showConfirmButton: false,
					showCloseButton: true,
				})
			})

			$('#userTable').DataTable({
				ajax: 'get_registros.php',
				buttons: [{
					extend: 'excel',
					filename: 'informativo_pdv',
					}],
				dom: 'Bfrtip',
				initComplete: function() {
					var $buttons = $('.dt-buttons').hide();
					$('#btnExportar').on('click', function() {
						var btnClass = ".buttons-excel";
						if (btnClass) $buttons.find(btnClass).click();
					});
					$('#userTable_filter').addClass('sss');
					$('#userTable_filter input[type="search"]').addClass('input-search');
				}
			});

		});
	</script>
</head>

<body>
	<div class="btn-bar">
		<h1 class="">INFORMATIVO PDV</h1>
		<div class="botones">
			<button type="button" id="btnExportar" class="btn white-btn">
				<span class="material-symbols-outlined">file_download</span>
				<div>Descargar Formato</div>
			</button>
			<button type="button" id="mostrarModal" class="btn lilac-btn">
				<span class="material-symbols-outlined">file_upload</span>
				<div>Cargar CSV</div>
			</button>
		</div>
	</div>

	<div id="response" class="<?php if (!empty($type)) {
									echo $type;
								} ?>">

	</div>

	<section class="detalles">
		<div class="modal__container" style="display:none;">
			<form class="form-horizontal" action="" method="post" name="frmCSVImport" id="frmCSVImport" enctype="multipart/form-data">
				<div class="input-row">
					<h2 class="modal__title">Cargar Excel</h2>
					<input type="file" name="file" id="file" accept=".csv">
					<div class="botones" style="justify-content: center;">
						<button type="submit" id="submit" name="import" class="btn ok-btn">Subir</button>
					</div>
				</div>
			</form>
		</div>
	</section>

	<div class="container">
		<div class="outer-scontainer">
			<?php
			
			?>
			<table id='userTable'>
				<thead>
					<tr>
					<th>POS ID</th>
					<th>LOCAL</th>
					<th>PRECIOS</th>
					<th>SOS</th>
					<th>ONPACKS</th>
					<th>INVENTARIO +SUGERIDO</th>
					<th>ANTES Y DESPUES</th>
					<th>PROMOCIONES</th>
					<th>AVANCE GENERAL</th>
					</tr>
				</thead>

				<tbody>
					<tr>

					</tr>

				</tbody>
			</table>
			<?php
			?>
		</div>
	</div>

</body>

</html>