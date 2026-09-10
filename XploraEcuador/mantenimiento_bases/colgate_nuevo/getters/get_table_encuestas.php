<?php
	include_once "../includes/db_connect.php";
	include_once "../includes/functions.php";
	include_once "../includes/config.php";

	$query = "SELECT id, re, link FROM repositorio_encuestas";
	if ($sql = $mysqli->prepare($query)) {
		$sql->execute();
        $sql->store_result();
        if ($sql->num_rows > 0) {
			$html = '<table id="table" name="table" class="table table-striped" style="width:100%">';
			$html .= '<thead class="thead-light">';
			$html .= '<tr>';

			$html .= '<th scope="col">ID</th>';
			$html .= '<th scope="col">RE</th>';
			$html .= '<th scope="col">Link</th>';

			$html .= '</tr>';
			$html .= '</thead>';
			$html .= '<tbody>';

			$sql->bind_result(
				$id,
				$re,
				$link
			) or die($sql->error);

			while ($sql->fetch()) {
				$html .= '<tr>';

				$html .= '<td>' . $id . '</td>';
				$html .= '<td>' . $re . '</td>';
				$html .= '<td>' . $link . '</td>';

				$html .= '</tr>';
			}
			$html .= '</tbody>';
        	$html .= '</table>';
		}
		$sql->close();
	}
	echo $html;