<?php
use Phppot\DataSource;

require_once 'DataSource.php';
$db = new DataSource();
$conn = $db->getConnection();

if (isset($_POST["import"])) {
    
    $fileName = $_FILES["file"]["tmp_name"];
    
    if ($_FILES["file"]["size"] > 0) {
        
        $file = fopen($fileName, "r");
		
		$sqlTruncate = "TRUNCATE table repositorio_productos_ventas;";
        $db->truncate($sqlTruncate);
		
		while (($column = fgetcsv($file, 10000, ";")) !== FALSE) {
            
            $id = "";
            if (isset($column[0])) {
                $id = mysqli_real_escape_string($conn, $column[0]);
            }
            $sector = "";
			if (isset($column[1])) {
				$sector = mysqli_real_escape_string($conn, $column[1]);
			}

			$categoria = "";
			if (isset($column[2])) {
				$categoria = mysqli_real_escape_string($conn, $column[2]);
			}

			$subcategoria = "";
			if (isset($column[3])) {
				$subcategoria = mysqli_real_escape_string($conn, $column[3]);
			}

			$segmento = "";
			if (isset($column[4])) {
				$segmento = mysqli_real_escape_string($conn, $column[4]);
			}

			$presentacion = "";
			if (isset($column[5])) {
				$presentacion = mysqli_real_escape_string($conn, $column[5]);
			}

			$variante1 = "";
			if (isset($column[6])) {
				$variante1 = mysqli_real_escape_string($conn, $column[6]);
			}

			$variante2 = "";
			if (isset($column[7])) {
				$variante2 = mysqli_real_escape_string($conn, $column[7]);
			}

			$contenido = "";
			if (isset($column[8])) {
				$contenido = mysqli_real_escape_string($conn, $column[8]);
			}

			$sku = "";
			if (isset($column[9])) {
				$sku = mysqli_real_escape_string($conn, $column[9]);
			}

			$marca = "";
			if (isset($column[10])) {
				$marca = mysqli_real_escape_string($conn, $column[10]);
			}

			$elaborado = "";
			if (isset($column[11])) {
				$elaborado = mysqli_real_escape_string($conn, $column[11]);
			}

			$activar = "";
			if (isset($column[12])) {
				$activar = mysqli_real_escape_string($conn, $column[12]);
			}

			$dolar = "";
			if (isset($column[13])) {
				$dolar = mysqli_real_escape_string($conn, $column[13]);
			}

			$fabricante = "";
			if (isset($column[14])) {
				$fabricante = mysqli_real_escape_string($conn, $column[14]);
			}

			$historico = "";
			if (isset($column[15])) {
				$historico = mysqli_real_escape_string($conn, $column[15]);
			}

			$pvp = "";
			if (isset($column[16])) {
				$pvp = mysqli_real_escape_string($conn, $column[16]);
			}

			$cadenas = "";
			if (isset($column[17])) {
				$cadenas = mysqli_real_escape_string($conn, $column[17]);
			}

			$locales = "";
			if (isset($column[18])) {
				$locales = mysqli_real_escape_string($conn, $column[18]);
			}
			
			$foto = "";
			if (isset($column[19])) {
				$foto = mysqli_real_escape_string($conn, $column[19]);
			}
            
            $sqlInsert = "INSERT INTO repositorio_productos_ventas (id,sector,categoria,subcategoria,segmento,presentacion,variante1,variante2,contenido,sku,marca,elaborado,activar,dolar,fabricante,historico,pvp,cadenas,locales,foto) VALUES  (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $paramType = "isssssssssssssssssss";
            $paramArray = array(
                $id,
				$sector,
				$categoria,
				$subcategoria,
				$segmento,
				$presentacion,
				$variante1,
				$variante2,
				$contenido,
				$sku,
				$marca,
				$elaborado,
				$activar,
				$dolar,
				$fabricante,
				$historico,
				$pvp,
				$cadenas,
				$locales,
				$foto
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
    <h2>Base Productos</h2>

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
            $sqlSelect = "SELECT * FROM repositorio_productos_ventas";
            $result = $db->select($sqlSelect);
            if (!empty($result)) {
                ?>
            <table id='userTable'>
            <thead>
                <tr>
                    <th>ID</th>
					<th>Categoria</th>
					<th>Subcategoria</th>
					<th>Subcategoria2</th>
					<th>Segmento</th>
					<th>Presentacion</th>
					<th>Variante 1</th>
					<th>Variante 2</th>
					<th>Contenido</th>
					<th>NEW DESCRIPTION SKU</th>
					<th>Marca</th>
					<th>Elaborado/Fabricado</th>
					<th>Activa</th>
					<th>PVP</th>
					<th>Importado/Distribuido</th>
					<th>HISTORICO</th>
					<th>SIN DÓLAR</th>
					<th>CODIGO PDV</th>
					<th>N/A</th>
					<th>FOTO</th>
                </tr>
            </thead>
		<?php
                
                foreach ($result as $row) {
                    ?>
                    
                <tbody>
                <tr>
                    <td><?php  echo $row['id']; ?></td>
					<td><?php  echo $row['sector']; ?></td>
					<td><?php  echo $row['categoria']; ?></td>
					<td><?php  echo $row['subcategoria']; ?></td>
					<td><?php  echo $row['segmento']; ?></td>
					<td><?php  echo $row['presentacion']; ?></td>
					<td><?php  echo $row['variante1']; ?></td>
					<td><?php  echo $row['variante2']; ?></td>
					<td><?php  echo $row['contenido']; ?></td>
					<td><?php  echo $row['sku']; ?></td>
					<td><?php  echo $row['marca']; ?></td>
					<td><?php  echo $row['elaborado']; ?></td>
					<td><?php  echo $row['activar']; ?></td>
					<td><?php  echo $row['dolar']; ?></td>
					<td><?php  echo $row['fabricante']; ?></td>
					<td><?php  echo $row['historico']; ?></td>
					<td><?php  echo $row['pvp']; ?></td>
					<td><?php  echo $row['cadenas']; ?></td>
					<td><?php  echo $row['locales']; ?></td>
					<td><?php  echo $row['foto']; ?></td>
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