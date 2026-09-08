<?php
use Phppot\DataSource;

require_once 'DataSource.php';
$db = new DataSource();
$conn = $db->getConnection();

if (isset($_POST["import"])) {
    
    $fileName = $_FILES["file"]["tmp_name"];
    
    if ($_FILES["file"]["size"] > 0) {
        
        $file = fopen($fileName, "r");
		
		$sqlTruncate = "TRUNCATE table epson_puntos_actuales;";
        $db->truncate($sqlTruncate);
		
		while (($column = fgetcsv($file, 10000, ";")) !== FALSE) {
            
            $id_pdv = "";
            if (isset($column[0])) {
                $id_pdv = mysqli_real_escape_string($conn, $column[0]);
            }
            $codigo = "";
			if (isset($column[1])) {
				$codigo = mysqli_real_escape_string($conn, $column[1]);
			}

			$tipo = "";
			if (isset($column[2])) {
				$tipo = mysqli_real_escape_string($conn, $column[2]);
			}

			$ciudad = "";
			if (isset($column[3])) {
				$ciudad = mysqli_real_escape_string($conn, $column[3]);
			}

			$provincia = "";
			if (isset($column[4])) {
				$provincia = mysqli_real_escape_string($conn, $column[4]);
			}

			$establecimiento = "";
			if (isset($column[5])) {
				$establecimiento = mysqli_real_escape_string($conn, $column[5]);
			}

			$categorizacion = "";
			if (isset($column[6])) {
				$categorizacion = mysqli_real_escape_string($conn, $column[6]);
			}

			$ruc = "";
			if (isset($column[7])) {
				$ruc = mysqli_real_escape_string($conn, $column[7]);
			}

			$telefono = "";
			if (isset($column[8])) {
				$telefono = mysqli_real_escape_string($conn, $column[8]);
			}

			$direccion = "";
			if (isset($column[9])) {
				$direccion = mysqli_real_escape_string($conn, $column[9]);
			}

			$correo = "";
			if (isset($column[10])) {
				$correo = mysqli_real_escape_string($conn, $column[10]);
			}

			$usuario = "";
			if (isset($column[11])) {
				$usuario = mysqli_real_escape_string($conn, $column[11]);
			}

			$supervisor = "";
			if (isset($column[12])) {
				$supervisor = mysqli_real_escape_string($conn, $column[12]);
			}

			$status = "";
			if (isset($column[13])) {
				$status = mysqli_real_escape_string($conn, $column[13]);
			}
			
            $sqlInsert = "INSERT INTO epson_puntos_actuales (id_pdv, codigo, tipo, ciudad, provincia, establecimiento, categorizacion, ruc, telefono, direccion, correo, usuario, supervisor, status)
                   values (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $paramType = "isssssssssssss";
            $paramArray = array(
                $id_pdv,
				$codigo,
				$tipo,
				$ciudad,
				$provincia,
				$establecimiento,
				$categorizacion,
				$ruc,
				$telefono,
				$direccion,
				$correo,
				$usuario,
				$supervisor,
				$status
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
<!DOCTYPE html>
<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
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
    <h2>Base PDV</h2>

    <div id="response"
        class="<?php if(!empty($type)) { echo $type . " display-block"; } ?>">
        <?php if(!empty($message)) { echo $message; } ?>
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
            $sqlSelect = "SELECT * FROM epson_puntos_actuales";
            $result = $db->select($sqlSelect);
            if (!empty($result)) {
                ?>
            <table id='userTable'>
            <thead>
                <tr>
                    <th>ID</th>
					<th>CODIGO</th>
					<th>TIPO</th>
					<th>CIUDAD</th>
					<th>PROVINCIA</th>
					<th>ESTABLECIMIENTO</th>
					<th>CATEGORIZACION</th>
					<th>RUC</th>
					<th>TELEFONO</th>
					<th>DIRECCION</th>
					<th>CORREO</th>
					<th>USUARIO</th>
					<th>SUPERVISOR</th>
					<th>STATUS</th>
                </tr>
            </thead>
		<?php
                
                foreach ($result as $row) {
                    ?>
                    
                <tbody>
                <tr>
                    <td><?php  echo $row['id_pdv']; ?></td>
					<td><?php  echo $row['codigo']; ?></td>
					<td><?php  echo $row['tipo']; ?></td>
					<td><?php  echo $row['ciudad']; ?></td>
					<td><?php  echo $row['provincia']; ?></td>
					<td><?php  echo $row['establecimiento']; ?></td>
					<td><?php  echo $row['categorizacion']; ?></td>
					<td><?php  echo $row['ruc']; ?></td>
					<td><?php  echo $row['telefono']; ?></td>
					<td><?php  echo $row['direccion']; ?></td>
					<td><?php  echo $row['correo']; ?></td>
					<td><?php  echo $row['usuario']; ?></td>
					<td><?php  echo $row['supervisor']; ?></td>
					<td><?php  echo $row['status']; ?></td>
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