<?php

use Phppot\DataSource;

require_once 'DataSource.php';
$db = new DataSource();
$conn = $db->getConnection();

$uploaded = false;


if (!empty($_FILES["file_modificar_ruta"]["name"])) {

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
	if (!empty($_FILES['file_modificar_ruta']['name']) && in_array($_FILES['file_modificar_ruta']['type'], $fileMimes)) {

		$fileName = $_FILES["file_modificar_ruta"]["tmp_name"];

		if ($_FILES["file_modificar_ruta"]["size"] > 0) {

			$file = fopen($fileName, "r");

			$contador = 0;

			while (($column = fgetcsv($file, 27000, ";")) !== FALSE) {
				$id_rutero = "";
				if (isset($column[0])) {
					$id_rutero = mysqli_real_escape_string($conn, $column[0]);
					if ($contador==0) {
						$id_rutero = removeBomUtf8($id_rutero);
					}
				}

				$id_usuario = "";
				if (isset($column[1])) {
					$id_usuario = mysqli_real_escape_string($conn, $column[1]);
				}

				$id_pdv = "";
				if (isset($column[2])) {
					$id_pdv = mysqli_real_escape_string($conn, $column[2]);
				}

				$fecha_visita = "";
				if (isset($column[3])) {
					$fecha_visita = mysqli_real_escape_string($conn, $column[3]);
				}

				$termometro = "";
				if (isset($column[4])) {
					$termometro = mysqli_real_escape_string($conn, $column[4]);
				}

				$status = "";
				if (isset($column[5])) {
					$status = mysqli_real_escape_string($conn, $column[5]);
				}

				$habilitado = "";
				if (isset($column[6])) {
					$habilitado = mysqli_real_escape_string($conn, $column[6]);
				}

				$sqlInsert = "UPDATE rutero_pdv SET id_usuario=?, id_pdv=?, id_supervisor=(SELECT supervisor FROM repositorio_locales_dtt2 WHERE id=?), termometro=?, fecha_visita=?, status=?, habilitado=? WHERE id=?";
				$paramType = "ssssssss";
				$paramArray = array(
					$id_usuario, 
					$id_pdv, 
					$id_pdv, 
					$termometro, 
					$fecha_visita, 
					$status, 
					$habilitado, 
					$id_rutero
				);

				if ($db->update($sqlInsert, $paramType, $paramArray)) {
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
