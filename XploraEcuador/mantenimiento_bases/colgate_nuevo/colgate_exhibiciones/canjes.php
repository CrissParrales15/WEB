<?php
use Phppot\DataSource;

require_once 'DataSource.php';
$db = new DataSource();
$conn = $db->getConnection();

if (isset($_POST["import"])) {
    
    $fileName = $_FILES["file"]["tmp_name"];
    
    if ($_FILES["file"]["size"] > 0) {
        
        $file = fopen($fileName, "r");

		$sqlTruncate = "TRUNCATE table repositorio_rotacion;";
        $db->truncate($sqlTruncate);
		
		while (($column = fgetcsv($file, 10000, ";")) !== FALSE) {
			$id = "";
			if (isset($column[0])) {
				$id = mysqli_real_escape_string($conn, $column[0]);
			}

			$categoria = "";
			if (isset($column[1])) {
				$categoria = mysqli_real_escape_string($conn, $column[1]);
			}

			$subcategoria = "";
			if (isset($column[2])) {
				$subcategoria = mysqli_real_escape_string($conn, $column[2]);
			}

			$marca = "";
			if (isset($column[3])) {
				$marca = mysqli_real_escape_string($conn, $column[3]);
			}

			$producto = "";
			if (isset($column[4])) {
				$producto = mysqli_real_escape_string($conn, $column[4]);
			}

			$promocional = "";
			if (isset($column[5])) {
				$promocional = mysqli_real_escape_string($conn, $column[5]);
			}

			$mecanica = "";
			if (isset($column[6])) {
				$mecanica = mysqli_real_escape_string($conn, $column[6]);
			}

			$peso = "";
			if (isset($column[7])) {
				$peso = mysqli_real_escape_string($conn, $column[7]);
			}

			$tipo = "";
			if (isset($column[8])) {
				$tipo = mysqli_real_escape_string($conn, $column[8]);
			}

			$mecanica2 = "";
			if (isset($column[9])) {
				$mecanica2 = mysqli_real_escape_string($conn, $column[9]);
			}

			$region = "";
			if (isset($column[10])) {
				$region = mysqli_real_escape_string($conn, $column[10]);
			}

			$plataforma = "";
			if (isset($column[11])) {
				$plataforma = mysqli_real_escape_string($conn, $column[11]);
			}
			
            $sqlInsert = "INSERT INTO repositorio_rotacion (id, categoria, subcategoria, marca, producto, promocional, mecanica, peso, tipo, mecanica2, region, plataforma) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
            $paramType = "sssssssssss";
            $paramArray = array(
				$id,
                $categoria,
				$subcategoria,
				$marca,
				$producto, 
				$promocional, 
				$mecanica, 
				$peso, 
				$tipo, 
				$mecanica2, 
				$region,
				$plataforma
            );
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
<script src="jquery-3.2.1.min.js"></script>

<style>
	body {
		font-family: Arial;
		width: 550px;
	}

	.outer-scontainer {
		/*background: #F0F0F0;*/
		/*border: #e0dfdf 1px solid;*/
		padding: 20px;
		border-radius: 2px;
	}

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

	.outer-scontainer table {
		border-collapse: collapse;
		width: 100%;
	}

	.outer-scontainer th {
		border: 1px solid #dddddd;
		padding: 8px;
		text-align: left;
	}

	.outer-scontainer td {
		border: 1px solid #dddddd;
		padding: 8px;
		text-align: left;
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
    <h2>Base Canjes</h2>

    <div id="response"
        class="<?php if(!empty($type)) { echo $type . " display-block"; } ?>">
        <?php 
			if(!empty($message)) {
				echo $message;
			} 
		?>
        </div>
    <div class="outer-scontainer">
        <div class="row">
            <form class="form-horizontal" action="" method="post" name="frmCSVImport" id="frmCSVImport" enctype="multipart/form-data">
                <div class="input-row">
                    <label class="col-md-4 control-label">Choose CSV File</label> <input type="file" name="file" id="file" accept=".csv">
                    <button type="submit" id="submit" name="import" class="btn-submit">Import</button>
                    <br />
                </div>
            </form>
        </div>
		<?php
            $sqlSelect = "SELECT * FROM repositorio_rotacion";
            $result = $db->select($sqlSelect);
            if (!empty($result)) {
                ?>
            <table id='userTable'>
            <thead>
                <tr>
                    <th>ID</th>
					<th>CATEGORIA</th>
					<th>SUBCATEGORIA</th>
					<th>MARCA</th>
					<th>PRODUCTO</th>
					<th>PROMOCIONAL</th>
					<th>DESCRIPCION</th>
					<th>PESO</th>
					<th>TIPO</th>
					<th>MECANICA</th>
					<th>REGION</th>
					<th>PLATAFORMA</th>
                </tr>
            </thead>
		<?php
                
                foreach ($result as $row) {
                    ?>
                    
                <tbody>
                <tr>
                    <td><?php  echo utf8_encode($row['id']); ?></td>
					<td><?php  echo utf8_encode($row['categoria']); ?></td>
					<td><?php  echo utf8_encode($row['subcategoria']); ?></td>
					<td><?php  echo utf8_encode($row['marca']); ?></td>
					<td><?php  echo utf8_encode($row['producto']); ?></td>
					<td><?php  echo utf8_encode($row['promocional']); ?></td>
					<td><?php  echo utf8_encode($row['mecanica']); ?></td>
					<td><?php  echo utf8_encode($row['peso']); ?></td>
					<td><?php  echo utf8_encode($row['tipo']); ?></td>
					<td><?php  echo utf8_encode($row['mecanica2']); ?></td>
					<td><?php  echo utf8_encode($row['region']); ?></td>
					<td><?php  echo utf8_encode($row['plataforma']); ?></td>
                </tr>
                    <?php
                }
                ?>
                </tbody>
        </table>
        <?php } ?>
    </div>

</body>

</html>