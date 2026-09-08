<?php

use Phppot\DataSource;

require_once 'DataSource.php';
$db = new DataSource();
$conn = $db->getConnection();

$uploaded = false;


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

			while (($column = fgetcsv($file, 27000, ";")) !== FALSE) {
				$id_usuario = "";
				if (isset($column[0])) {
					$id_usuario = mysqli_real_escape_string($conn, $column[0]);
					if ($contador==0) {
						$id_usuario = removeBomUtf8($id_usuario);
					}
				}

				$id_pdv = "";
				if (isset($column[1])) {
					$id_pdv = mysqli_real_escape_string($conn, $column[1]);
				}

				$fecha_visita = "";
				if (isset($column[2])) {
					$fecha_visita = mysqli_real_escape_string($conn, $column[2]);
				}

				$termometro = "";
				if (isset($column[3])) {
					$termometro = mysqli_real_escape_string($conn, $column[3]);
				}

				$status = "";
				if (isset($column[4])) {
					$status = mysqli_real_escape_string($conn, $column[4]);
				}

				$habilitado = "";
				if (isset($column[5])) {
					$habilitado = mysqli_real_escape_string($conn, $column[5]);
				}

				$sqlInsert = "INSERT INTO rutero_pdv(id_usuario, id_pdv, id_supervisor, termometro, fecha_visita, status, habilitado) 
							SELECT ?,?,supervisor,?,?,?,? FROM repositorio_locales_dtt2 WHERE id=?";
				$paramType = "sssssss";
				$paramArray = array(
					$id_usuario, 
					$id_pdv, 
					$termometro, 
					$fecha_visita, 
					$status, 
					$habilitado, 
					$id_pdv
				);
				$insertId = $db->insert($sqlInsert, $paramType, $paramArray);

				if (!empty($insertId)) {
					$uploaded = true;
				} else {
					$uploaded = false;
				}
				$contador++;
			}
			if ($uploaded) {
				echo "1";
			} else {
				echo "2";
			}
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
