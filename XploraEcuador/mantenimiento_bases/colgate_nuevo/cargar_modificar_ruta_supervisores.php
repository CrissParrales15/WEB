<?php

use Phppot\DataSource;

require_once 'DataSource.php';
$db = new DataSource();
$conn = $db->getConnection();

$uploaded = false;

$usuario = isset($_SESSION['username']) ? $_SESSION['username'] : '';

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

				$id_pdv = "";
				if (isset($column[1])) {
					$id_pdv = mysqli_real_escape_string($conn, $column[1]);
				}

				$punto_apoyo = "";
				if (isset($column[2])) {
					$punto_apoyo = mysqli_real_escape_string($conn, $column[2]);
				}

				$id_supervisor = "";
				if (isset($column[3])) {
					$id_supervisor = mysqli_real_escape_string($conn, $column[3]);
				}

				$fecha_visita = "";
				if (isset($column[4])) {
					$fecha_visita = mysqli_real_escape_string($conn, $column[4]);
				}

				$status = "";
				if (isset($column[5])) {
					$status = mysqli_real_escape_string($conn, $column[5]);
				}

				$habilitado = "";
				if (isset($column[6])) {
					$habilitado = mysqli_real_escape_string($conn, $column[6]);
				}

				$sqlInsert = "UPDATE rutero_pdv_supervisores SET id_pdv=?, punto_apoyo=?, id_supervisor=?, fecha_visita=?, status=?, habilitado=?, usuario_edicion=? WHERE id=?";
				$paramType = "ssssssss";
				$paramArray = array( 
					$id_pdv, 
					$punto_apoyo, 
					$id_supervisor, 
					$fecha_visita, 
					$status, 
					$habilitado, 
					$usuario,
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
