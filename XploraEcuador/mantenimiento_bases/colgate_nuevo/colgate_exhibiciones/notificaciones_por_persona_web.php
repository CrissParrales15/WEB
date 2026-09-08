<?php
use Phppot\DataSource;

require_once 'DataSource.php';
$db = new DataSource();
$conn = $db->getConnection();

if (isset($_POST["import"])) {
    
    $fileName = $_FILES["file"]["tmp_name"];
    
    if ($_FILES["file"]["size"] > 0) {
        
        $file = fopen($fileName, "r");
		
		$sqlTruncate = "TRUNCATE table lvi_estados_visitas_calificadas;";
        $db->truncate($sqlTruncate);
		
		while (($column = fgetcsv($file, 10000, ";")) !== FALSE) {
            
            $codigo = "";
            if (isset($column[0])) {
                $codigo = mysqli_real_escape_string($conn, $column[0]);
            }

			$mercaderista = "";
			if (isset($column[1])) {
				$mercaderista = mysqli_real_escape_string($conn, $column[1]);
			}

            $nombre_pdv = "";
			if (isset($column[2])) {
				$nombre_pdv = mysqli_real_escape_string($conn, $column[2]);
			}

            $cadena = "";
			if (isset($column[3])) {
				$cadena = mysqli_real_escape_string($conn, $column[3]);
			}

            $supervisor = "";
			if (isset($column[4])) {
				$supervisor = mysqli_real_escape_string($conn, $column[4]);
			}

            $hora_llegada_min = "";
			if (isset($column[5])) {
				$hora_llegada_min = mysqli_real_escape_string($conn, $column[5]);
			}

            $hora_llegada = "";
			if (isset($column[6])) {
				$hora_llegada = mysqli_real_escape_string($conn, $column[6]);
			}

            $hora_llegada_max = "";
			if (isset($column[7])) {
				$hora_llegada_max = mysqli_real_escape_string($conn, $column[7]);
			}

            $hora_inicio_visita = "";
			if (isset($column[8])) {
				$hora_inicio_visita = mysqli_real_escape_string($conn, $column[8]);
			}

            $hora_salida_min = "";
			if (isset($column[9])) {
				$hora_salida_min = mysqli_real_escape_string($conn, $column[9]);
			}

            $hora_salida = "";
			if (isset($column[10])) {
				$hora_salida = mysqli_real_escape_string($conn, $column[10]);
			}

            $hora_salida_max = "";
			if (isset($column[11])) {
				$hora_salida_max = mysqli_real_escape_string($conn, $column[11]);
			}

            $hora_fin_visita = "";
			if (isset($column[12])) {
				$hora_fin_visita = mysqli_real_escape_string($conn, $column[12]);
			}

            $observacion_entrada = "";
			if (isset($column[13])) {
				$observacion_entrada = mysqli_real_escape_string($conn, $column[13]);
			}

            $observacion_salida = "";
			if (isset($column[14])) {
				$observacion_salida = mysqli_real_escape_string($conn, $column[14]);
			}


            $sqlInsert = "INSERT INTO lvi_estados_visitas_calificadas (codigo,mercaderista,nombre_pdv,cadena,supervisor,hora_llegada_min,hora_llegada,hora_llegada_max,hora_inicio_visita,hora_salida_min,hora_salida,hora_salida_max,hora_fin_visita,observacion_entrada,observacion_salida) VALUES  (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $paramType = "issssssssssssss";
            $paramArray = array(
                $codigo,
				$mercaderista,
				$nombre_pdv,
				$cadena,
				$supervisor,
				$hora_llegada_min,
				$hora_llegada,
				$hora_llegada_max,
				$hora_inicio_visita,
				$hora_salida_min,
				$hora_salida,
				$hora_salida_max,
				$hora_fin_visita,
				$observacion_entrada,
				$observacion_salida
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
	<link rel="stylesheet" href="/App/XploraEcuador/assets/css/bootstrap-3.3.7.min.css">
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
				<h3>Notificaciones</h3>
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

		<!--<div class="row">
			<form class="form-horizontal" action="" method="post" name="frmCSVImport" id="frmCSVImport" enctype="multipart/form-data">
				<div class="input-row">
					<label class="col-md-4 control-label">Choose CSV File</label> <input type="file" name="file" id="file" accept=".csv">
					<button type="submit" id="submit" name="import" class="btn-submit">Import</button>
					<br />
				</div>
			</form>
		</div>-->


		<div class="row">		
			<div class="col-xl-12 col-lg-12 mb-12">
			<div class="col-sm-6">
						<div class="form-group">
                           <label for="supervisor">Coordinador / Supervisor</label>
                            <select class="form-control" id="selectSupervisor" name="supervisor" required>
                                <option value="Seleccione">Seleccione </option>
                            </select>
						</div>
				</div>
			</div>
		</div>	

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
	</div>

	<script>
		function getParameterByName(name) {
			name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
			var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
			results = regex.exec(location.search);
			return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
		}

		function updateRow(id_notificacion) {
			console.log("ID NOTIFICACION" + id_notificacion);

			return new Promise(function(resolve, reject) {
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
						if (data_update == 1) {
							Swal.fire({
								title: "Notificaciones",
								text: "Notificación marcada como leida",
								type: "success"
							});
							resolve(true);
						} else {
							Swal.fire({
								title: "Notificaciones",
								text: "Error de actualización!",
								type: "error"
							});
							resolve(false);
						}
					},
					error: function(XMLHttpRequest, textStatus, errorThrown) {
						alert("Status: " + textStatus);
						alert("Error: " + errorThrown);
						reject(errorThrown);
					}
				});
			});
		}


		function buscarRuta() {
			if ($("#fechaInicioFiltro").val() && $("#fechaFinFiltro").val() && $("#selectSupervisor").val()) {
				var fechaInicialB = $("#fechaInicioFiltro").val();
				var fechaFinB = $("#fechaFinFiltro").val();
				var supervisor = $("#selectSupervisor").val();
					
				$.ajax({
					type: "POST",
					url: "getters/get_table_notificaciones_persona_web.php",
					data: {
						fechaInicio: fechaInicialB,
						fechaFin: fechaFinB,
						supervisor: supervisor
					},
					beforeSend: function() {
						// $("#loading").css("display", "block");
						Swal.fire({
						title: 'Cargando..',
    					allowEscapeKey: false,
    					allowOutsideClick: false,
						didOpen: () => {
      						swal.showLoading();
    					}		
            		});
					},
					success: function(data) {
						if (data != "") {

							swal.close();

							$("#data-result").html(data);
							var table = $('#table').DataTable({
								scrollX: true,
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
								},
								{
								targets: [0],
								visible: false,
								searchable: false
								}],
								fnInfoCallback: function(settings, json) {
									$('#table .modificar-notificacion').click(function() {
										var data_table = table.row($(this).parents()).data();
										var id_notificacion = data_table[0];
										var estado = data_table[14];
										//METODO DE ACTUALIZACION

										var $this = $(this); // Almacenar la referencia a $(this) fuera del callback then

										updateRow(id_notificacion)
											.then(function(result) {
												console.log(result);
												$this.parents('tr').find('td:eq(14)').text('Leido');
											})
											.catch(function(error) {
												// Manejar errores aquí
												console.error(error);
											});
									});
								}
							});
						}else{
							Swal.fire({
								title:"Error",
  								text: "No se encontraron registros.",
  								icon: "error"
							})
						}
					},
					error: function(XMLHttpRequest, textStatus, errorThrown) {
						alert("Status: " + textStatus);
						alert("Error: " + errorThrown);
					}
				});
			} else {
				Swal.fire({
					title: 'Búsqueda',
					//text: 'Debe seleccionar las fechas inicio y fin para realizar la búsqueda'
					text: 'Debe escoger los campos para realizar la búsqueda'
				});

			}
		}

		function getSupervisores() {
 
                $.ajax({
                    type: "GET",
                    url: "getters/getSupervisores.php",
                    beforeSend: function() {},
                    success: function(data) {
                        $("#selectSupervisor").html(data);
                    }
                });
            }


		$(document).ready(function() {
			getSupervisores();
			$("button").click(function(event) {
				if ($(this).attr("value") == "search") {
					console.log("BUSCAR RUTA");
					buscarRuta();
				}
				event.preventDefault();
			});
		});
	</script>

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