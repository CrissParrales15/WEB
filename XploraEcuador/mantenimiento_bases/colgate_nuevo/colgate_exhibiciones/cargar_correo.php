<?php

use Phppot\DataSource;

require_once 'DataSource.php';
$db = new DataSource();
$conn = $db->getConnection();

$uploaded = false;


if (!empty($_FILES["file_mail"]["name"])) {

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
	if (!empty($_FILES['file_mail']['name']) && in_array($_FILES['file_mail']['type'], $fileMimes)) {

		$fileName = $_FILES["file_mail"]["tmp_name"];

		if ($_FILES["file_mail"]["size"] > 0) {

			$file = fopen($fileName, "r");

			$sqlTruncate = "TRUNCATE table alertas_correos;";
			$db->truncate($sqlTruncate);
			$id = 0;

			while (($column = fgetcsv($file, 27000, ";")) !== FALSE) {

				$cargo = "";
				if (isset($column[0])) {
					$cargo = mysqli_real_escape_string($conn, $column[0]);
					if ($id==0) {
						$cargo = removeBomUtf8($cargo);
					}
				}

				$nombre = "";
				if (isset($column[1])) {
					$nombre = mysqli_real_escape_string($conn, $column[1]);
				}

				$canal = "";
				if (isset($column[2])) {
					$canal = mysqli_real_escape_string($conn, $column[2]);
				}

				$zona = "";
				if (isset($column[3])) {
					$zona = mysqli_real_escape_string($conn, $column[3]);
				}

				$correo = "";
				if (isset($column[4])) {
					$correo = mysqli_real_escape_string($conn, $column[4]);
				}

				$correos_cc = "";
				if (isset($column[5])) {
					$correos_cc = mysqli_real_escape_string($conn, $column[5]);
				}

				$correos_cco = "";
				if (isset($column[6])) {
					$correos_cco = mysqli_real_escape_string($conn, $column[6]);
				}

				$sqlInsert = "INSERT INTO alertas_correos(
								cargo, 
								nombre, 
								canal, 
								zona, 
								correo, 
								correos_cc, 
								correos_cco)
							VALUES (?,?,?,?,?,?,?)";
				$paramType = "sssssss";
				$paramArray = array(
					$cargo, 
					$nombre, 
					$canal, 
					$zona, 
					$correo, 
					$correos_cc, 
					$correos_cco
				);
				$insertId = $db->insert($sqlInsert, $paramType, $paramArray);

				if (!empty($insertId)) {
					$uploaded = true;
				} else {
					$uploaded = false;
				}
				$id++;
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
