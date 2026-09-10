<?php
/* ============================================================
   MODO DEBUG  —  quítalo (o pon $DEBUG = false) cuando funcione
   ------------------------------------------------------------
   Muestra errores de PHP en pantalla en vez de dejar la página
   en blanco, y devuelve toda la info al navegador (consola F12).
   ============================================================ */
$DEBUG = true;
if ($DEBUG) {
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
}

use Phppot\DataSource;

/* Recolectamos info de diagnóstico para mandarla a la consola */
$debug = [];
$debug['php_version']         = PHP_VERSION;
$debug['upload_max_filesize'] = ini_get('upload_max_filesize');
$debug['post_max_size']       = ini_get('post_max_size');
$debug['memory_limit']        = ini_get('memory_limit');
$debug['max_execution_time']  = ini_get('max_execution_time');
$debug['post_import_set']     = isset($_POST['import']);
$debug['files_keys']          = isset($_FILES) ? array_keys($_FILES) : [];

/* ¿Es una petición AJAX? Si sí, respondemos JSON y NO pintamos el HTML. */
$isAjax = (
	isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
	strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
);

/* Traduce el código de error de subida a algo legible */
function uploadErrorText($code) {
	switch ($code) {
		case UPLOAD_ERR_OK:         return "OK (0)";
		case UPLOAD_ERR_INI_SIZE:   return "El archivo supera upload_max_filesize del php.ini (1)";
		case UPLOAD_ERR_FORM_SIZE:  return "El archivo supera MAX_FILE_SIZE del formulario (2)";
		case UPLOAD_ERR_PARTIAL:    return "El archivo se subió solo parcialmente (3)";
		case UPLOAD_ERR_NO_FILE:    return "No se subió ningún archivo (4)";
		case UPLOAD_ERR_NO_TMP_DIR: return "Falta la carpeta temporal del servidor (6)";
		case UPLOAD_ERR_CANT_WRITE: return "No se pudo escribir el archivo en disco (7)";
		case UPLOAD_ERR_EXTENSION:  return "Una extensión de PHP detuvo la subida (8)";
		default:                    return "Código de error desconocido ($code)";
	}
}

$type    = "";
$message = "";

if (isset($_POST["import"])) {

	/* ---------- 1) Revisar el archivo ANTES de tocar la BD ---------- */
	if (!isset($_FILES["file"])) {
		$type    = "error";
		$message = "No llegó el campo 'file'. Casi seguro el archivo supera post_max_size (" . $debug['post_max_size'] . ").";
		$debug['step_failed'] = "no_file_field";
	} else {
		$f = $_FILES["file"];
		$debug['file_name']       = $f["name"];
		$debug['file_size_bytes'] = $f["size"];
		$debug['file_size_mb']    = round($f["size"] / 1048576, 2);
		$debug['file_tmp_name']   = $f["tmp_name"];
		$debug['file_error_code'] = $f["error"];
		$debug['file_error_text'] = uploadErrorText($f["error"]);

		if ($f["error"] !== UPLOAD_ERR_OK) {
			$type    = "error";
			$message = "Error de subida: " . $debug['file_error_text'];
			$debug['step_failed'] = "upload_error";
		} elseif ($f["size"] <= 0) {
			$type    = "error";
			$message = "El archivo llegó pero pesa 0 bytes.";
			$debug['step_failed'] = "zero_size";
		} else {

			/* ---------- 2) Cargar DataSource / conexión con guardas ---------- */
			try {
				if (!file_exists('DataSource.php')) {
					throw new Exception("No se encontró DataSource.php en " . __DIR__);
				}
				require_once 'DataSource.php';
				$debug['datasource_loaded'] = true;

				$db   = new DataSource();
				$conn = $db->getConnection();
				if (!$conn) {
					throw new Exception("getConnection() no devolvió conexión");
				}
				$debug['db_connected'] = true;
				$debug['db_charset']   = @mysqli_character_set_name($conn);

				/* Asegura UTF-8 en la conexión (importante por la columna 'campaña') */
				@mysqli_set_charset($conn, "utf8mb4");
				$debug['db_charset_after'] = @mysqli_character_set_name($conn);

				/* ---------- 3) Importar ---------- */
				set_time_limit(300);
				ini_set('memory_limit', '256M');

				$file = fopen($f["tmp_name"], "r");
				if (!$file) {
					throw new Exception("No se pudo abrir el archivo temporal");
				}

				$sqlTruncate = "TRUNCATE table repositorio_ppts_exh;";
				$db->truncate($sqlTruncate);
				$debug['truncate_ok'] = true;

				$sqlInsert = "INSERT INTO repositorio_ppts_exh
					(`year`,`month`,`day`,`cp_code`,`trade`,`retail_environment`,`pos`,`exhibition_tool`,`manufacturer`,`category`,`subcategory`,`n_convenio`,`tipo_herramienta`,`clasificacion`,`campaña`,`photo_url`,`customer`,`city`,`server_date`,`status`)
					VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
				$stmt = mysqli_prepare($conn, $sqlInsert);
				if (!$stmt) {
					throw new Exception("Falló prepare(): " . mysqli_error($conn));
				}
				$debug['prepare_ok'] = true;

				$insertCount = 0;
				$rowNum      = 0;
				$rowErrors   = [];

				mysqli_begin_transaction($conn);
				$firstRow = true;

				while (($column = fgetcsv($file, 0, ";")) !== FALSE) {
					if (isset($column[0])) {
						$column[0] = preg_replace('/^\xEF\xBB\xBF/', '', $column[0]);
					}
					if ($firstRow) { $firstRow = false; continue; }
					$rowNum++;

					$year               = isset($column[0])  ? $column[0]  : "";
					$month              = isset($column[1])  ? $column[1]  : "";
					$day                = isset($column[2])  ? $column[2]  : "";
					$cp_code            = isset($column[3])  ? $column[3]  : "";
					$trade              = isset($column[4])  ? $column[4]  : "";
					$retail_environment = isset($column[5])  ? $column[5]  : "";
					$pos                = isset($column[6])  ? $column[6]  : "";
					$exhibition_tool    = isset($column[7])  ? $column[7]  : "";
					$manufacturer       = isset($column[8])  ? $column[8]  : "";
					$category           = isset($column[9])  ? $column[9]  : "";
					$subcategory        = isset($column[10]) ? $column[10] : "";
					$n_convenio         = isset($column[11]) ? $column[11] : "";
					$tipo_herramienta   = isset($column[12]) ? $column[12] : "";
					$clasificacion      = isset($column[13]) ? $column[13] : "";
					$campana            = isset($column[14]) ? $column[14] : "";
					$photo_url          = isset($column[15]) ? $column[15] : "";
					$customer           = isset($column[16]) ? $column[16] : "";
					$city               = isset($column[17]) ? $column[17] : "";

					$server_date = isset($column[18]) ? trim($column[18]) : "";
					$dt = ($server_date !== "") ? DateTime::createFromFormat('j/n/Y G:i', $server_date) : false;
					if ($dt === false && $server_date !== "") {
						$dt = DateTime::createFromFormat('Y-m-d H:i:s', $server_date);
					}
					$server_date = ($dt !== false) ? $dt->format('Y-m-d H:i:s') : date('Y-m-d H:i:s');

					// El CSV ya no trae columna status. Se fija en 0 para todos los
					// registros importados (coincide con el filtro actual de la vista).
					$status = 0;

					mysqli_stmt_bind_param(
						$stmt, "sssssssssssssssssssi",
						$year, $month, $day, $cp_code,
						$trade, $retail_environment, $pos, $exhibition_tool, $manufacturer,
						$category, $subcategory, $n_convenio, $tipo_herramienta, $clasificacion,
						$campana, $photo_url, $customer, $city, $server_date, $status
					);

					if (!mysqli_stmt_execute($stmt)) {
						/* Guardamos los primeros 10 errores de fila para no saturar */
						if (count($rowErrors) < 10) {
							$rowErrors[] = "Fila $rowNum: " . mysqli_stmt_error($stmt);
						}
						throw new Exception("Falló INSERT en la fila $rowNum: " . mysqli_stmt_error($stmt));
					}
					$insertCount++;
				}

				mysqli_commit($conn);
				mysqli_stmt_close($stmt);
				fclose($file);

				$debug['rows_read']     = $rowNum;
				$debug['rows_inserted'] = $insertCount;
				$debug['row_errors']    = $rowErrors;

				$type    = "success";
				$message = "CSV importado: " . $insertCount . " filas cargadas correctamente";

			} catch (Throwable $e) {
				if (isset($conn) && $conn) { @mysqli_rollback($conn); }
				if (isset($stmt) && $stmt) { @mysqli_stmt_close($stmt); }
				if (isset($file) && $file) { @fclose($file); }
				$type    = "error";
				$message = "Importación falló: " . $e->getMessage();
				$debug['exception']       = $e->getMessage();
				$debug['exception_line']  = $e->getLine();
				$debug['exception_file']  = $e->getFile();
			}
		}
	}

	/* ---------- Si es AJAX: responder JSON y terminar (sin HTML) ---------- */
	if ($isAjax) {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode([
			'type'    => $type,
			'message' => $message,
			'debug'   => $debug,
		]);
		exit;
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

			// ============ IMPORT VIA AJAX + LOGS EN CONSOLA (F12) ============
			$("#frmCSVImport").on("submit", function(e) {
				e.preventDefault();               // no recargamos la página
				console.clear();
				console.log("%c[IMPORT] === Inicio de importación ===", "color:#2563eb;font-weight:bold");

				var fileInput = $("#file")[0];
				var file = fileInput.files[0];

				// Validación de extensión
				var fileType = ".csv";
				var regex = new RegExp("([a-zA-Z0-9\s_\\.\-:])+(" + fileType + ")$");
				if (!file) {
					console.error("[IMPORT] No seleccionaste ningún archivo.");
					$("#response").attr("class", "error display-block").html("Selecciona un archivo primero.");
					return false;
				}
				console.log("[IMPORT] Archivo seleccionado:", file.name);
				console.log("[IMPORT] Tamaño:", (file.size / 1048576).toFixed(2), "MB", "(" + file.size + " bytes)");
				console.log("[IMPORT] Tipo MIME:", file.type);

				if (!regex.test(file.name.toLowerCase())) {
					console.error("[IMPORT] Extensión inválida. Debe ser .csv");
					$("#response").attr("class", "error display-block")
						.html("Archivo inválido. Sube: <b>" + fileType + "</b>");
					return false;
				}

				$("#loading").css("display", "block");

				var formData = new FormData();
				formData.append("file", file);
				formData.append("import", "1");

				console.log("[IMPORT] Enviando al servidor...");
				var t0 = performance.now();

				$.ajax({
					type: "POST",
					url: "",                        // misma página
					data: formData,
					processData: false,
					contentType: false,
					headers: { "X-Requested-With": "XMLHttpRequest" },
					dataType: "text",               // recibimos texto para poder inspeccionar aunque NO sea JSON
					success: function(raw) {
						$("#loading").css("display", "none");
						var ms = (performance.now() - t0).toFixed(0);
						console.log("[IMPORT] Respuesta recibida en " + ms + " ms");

						var res;
						try {
							res = JSON.parse(raw);
						} catch (err) {
							// Si no es JSON, casi siempre es un error fatal de PHP en HTML
							console.error("[IMPORT] La respuesta NO es JSON. Probable error fatal de PHP:");
							console.error(raw);
							$("#response").attr("class", "error display-block")
								.html("Error del servidor (ver consola F12). Respuesta cruda abajo.");
							return;
						}

						console.log("[IMPORT] type:", res.type);
						console.log("[IMPORT] message:", res.message);
						console.log("%c[IMPORT] === DEBUG del servidor ===", "color:#16a34a;font-weight:bold");
						console.table(res.debug);           // tabla legible en la consola
						console.log("[IMPORT] debug completo:", res.debug);

						if (res.debug && res.debug.row_errors && res.debug.row_errors.length) {
							console.warn("[IMPORT] Errores por fila:", res.debug.row_errors);
						}

						$("#response").attr("class", res.type + " display-block").html(res.message);

						// Si se importó bien, recargamos la tabla
						if (res.type === "success") {
							cargarTabla();
						}
					},
					error: function(xhr, textStatus, errorThrown) {
						$("#loading").css("display", "none");
						console.error("[IMPORT] Falló la petición AJAX");
						console.error("[IMPORT] status:", xhr.status, textStatus);
						console.error("[IMPORT] errorThrown:", errorThrown);
						console.error("[IMPORT] responseText:", xhr.responseText);
						$("#response").attr("class", "error display-block")
							.html("Error de red/servidor (" + xhr.status + "). Ver consola F12.");
					}
				});

				return false;
			});
		});
	</script>
</head>

<body>
	<div id="loading" style=" display: none;position: fixed; width: 100%; height: 100%; top: 0px; left: 0px; z-index: 999999; overflow: auto; background-color: rgba(0, 0, 0, 0.498039);">
        <img style="position: absolute; top: 50% !important; left: 50% !important;" src="/App/XploraEcuador/assets/img/loader_1.gif">
    </div>
	<!-- Page Content Holder -->
	<div id="content" style="width:100%;">
		<nav class="navbar navbar-expand-lg navbar-light bg-light">
			<div class="container-fluid">
				<h3>Base Repositorio PPTS Exhibiciones</h3>
			</div>
		</nav>

		<div id="response"
			class="<?php if (!empty($type)) {
						echo $type . " display-block";
					} ?>">
			<?php
			if (!empty($message)) {
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
		// Carga (o recarga) la tabla de datos
		function cargarTabla() {
			console.log("[TABLA] Cargando datos desde getters/get_ppts_exh.php ...");
			$.ajax({
				type: "POST",
				url: "getters/get_ppts_exh.php",
				data: null,
				beforeSend: function() {
					$("#loading").css("display", "block");
				},
				success: function(data) {
					$("#loading").css("display", "none");
					if (data != "") {
						// Si ya existía una DataTable, la destruimos antes de recrearla
						if ($.fn.dataTable.isDataTable('#table')) {
							$('#table').DataTable().destroy();
						}
						$("#data-result").html(data);
						$('#table').DataTable({
							"scrollX": true,
							lengthMenu: [10, 25, 50, 75, 100],
							responsive: true,
							"dom": 'lBftpi',
							buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
						});
						console.log("[TABLA] Datos cargados.");
					} else {
						console.warn("[TABLA] get_ppts_exh.php devolvió vacío (¿tabla sin filas?).");
					}
				},
				error: function(XMLHttpRequest, textStatus, errorThrown) {
					$("#loading").css("display", "none");
					console.error("[TABLA] Error cargando tabla:", textStatus, errorThrown);
					console.error("[TABLA] responseText:", XMLHttpRequest.responseText);
				}
			});
		}

		$(document).ready(function() {
			cargarTabla();
		});
	</script>
</body>

</html>