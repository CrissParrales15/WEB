<?php

use Phppot\DataSource;

require_once 'DataSource.php';
$db = new DataSource();
$conn = $db->getConnection();

$uploaded = false;

$usuario = isset($_SESSION['username']) ? $_SESSION['username'] : '';

if (!empty($_FILES["file_nueva_ruta"]["name"])) {

	// Allowed mime types
	$fileMimes = array(
		'text/x-comma-separated-values',
		'text/comma-separated-values',
		'application/octet-stream',
		'application/vnd.ms-excel',
		'application/x-csv',
		'text/x-csv',
		'text/csv',
		'application/csv',
		'application/excel',
		'application/vnd.msexcel',
		'text/plain'
	);

	// Validate whether selected file is a CSV file
	if (!empty($_FILES['file_nueva_ruta']['name']) && in_array($_FILES['file_nueva_ruta']['type'], $fileMimes)) {

		$fileName = $_FILES["file_nueva_ruta"]["tmp_name"];

		if ($_FILES["file_nueva_ruta"]["size"] > 0) {
            
            $file = fopen($fileName, "r");
            $contador = 0;
            $registros = 0;


            $file = fopen($fileName, "r");
            $firstLine = fgets($file); // Leer la primera línea
            $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ','; // Determinar el delimitador
            rewind($file); // Regresar al inicio del archivo

            // echo "FIRSTLINE: " . $firstLine .  "\n";
            // echo "DELIMITER: " . $delimiter .  "\n";

			while (($column = fgetcsv($file, 27000, $delimiter)) !== FALSE) {
				$id_pdv = "";
				if (isset($column[0])) {
					$id_pdv = mysqli_real_escape_string($conn, $column[0]);
					if ($contador == 0) {
						$id_pdv = removeBomUtf8($id_pdv);
					}
				}

				$punto_apoyo = "";
				if (isset($column[1])) {
					$punto_apoyo = mysqli_real_escape_string($conn, $column[1]);
				}

				$id_supervisor = "";
				if (isset($column[2])) {
					$id_supervisor = mysqli_real_escape_string($conn, $column[2]);
				}

				$fecha_visita = "";
				if (isset($column[3])) {
					// if (preg_match("/^\d{1,2}\/\d{1,2}\/\d{4}$/", $column[4])) {
					if (preg_match("/^\d{2}\/\d{2}\/\d{4}$/", $column[3])) {
						$fecha_visita = DateTime::createFromFormat('d/m/Y', $column[3])->format('Y-m-d');
					} else {
						$fecha_visita = $column[3];
					}
					$fecha_visita = mysqli_real_escape_string($conn, $fecha_visita);
				}

				$status = "";
				if (isset($column[4])) {
					$status = mysqli_real_escape_string($conn, $column[4]);
				}

				$habilitado = "";
				if (isset($column[5])) {
					$habilitado = mysqli_real_escape_string($conn, $column[5]);
				}

				if (
					$id_pdv != "" &&
					$punto_apoyo != "" &&
					$id_supervisor != "" &&
					$fecha_visita != "" &&
					$status != "" &&
					$habilitado != ""
				) {
                    // echo 
                    //     $id_usuario . " - " . 
                    //     $id_pdv . " - " . 
                    //     $punto_apoyo . " - " . 
                    //     $fecha_visita . " - " . 
                    //     $status . " - " . 
                    //     $habilitado . "\n";

					$sqlInsert = "INSERT INTO rutero_pdv_supervisores(id_pdv, punto_apoyo, id_supervisor, fecha_visita, status, habilitado, usuario_creacion) VALUES (?,?,?,?,?,?,?)";
					$paramType = "sssssss";
					$paramArray = array(
						$id_pdv,
						$punto_apoyo,
						$id_supervisor,
						$fecha_visita,
						$status,
						$habilitado,
						$usuario
					);
					$insertId = $db->insert($sqlInsert, $paramType, $paramArray);
					$registros++;
				}
				$contador++;
			}
			if ($registros > 0) {
				echo "1";
			} else {
				echo "2";
			}

			// echo $contador;
				
			fclose($file);
		}
	}
}

function removeBomUtf8($s)
{
	if (substr($s, 0, 3) == chr(hexdec('EF')) . chr(hexdec('BB')) . chr(hexdec('BF'))) {
		return substr($s, 3);
	} else {
		return $s;
	}
}
