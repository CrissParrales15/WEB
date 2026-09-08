<?php
use Phppot\DataSource;

require_once 'DataSource.php';
$db = new DataSource();
$conn = $db->getConnection();

if (isset($_POST["import"])) {
    
    $fileName = $_FILES["file"]["tmp_name"];
    
    if ($_FILES["file"]["size"] > 0) {
        
        $file = fopen($fileName, "r");
		
		$sqlTruncate = "TRUNCATE table repositorio_locales_ventas;";
        $db->truncate($sqlTruncate);
		
		while (($column = fgetcsv($file, 10000, ";")) !== FALSE) {
            
            $id = "";
            if (isset($column[0])) {
                $id = mysqli_real_escape_string($conn, $column[0]);
            }
            $channel = "";
			if (isset($column[1])) {
				$channel = mysqli_real_escape_string($conn, $column[1]);
			}

			$subchannel = "";
			if (isset($column[2])) {
				$subchannel = mysqli_real_escape_string($conn, $column[2]);
			}

			$channel_segment = "";
			if (isset($column[3])) {
				$channel_segment = mysqli_real_escape_string($conn, $column[3]);
			}

			$format = "";
			if (isset($column[4])) {
				$format = mysqli_real_escape_string($conn, $column[4]);
			}

			$customer_owner = "";
			if (isset($column[5])) {
				$customer_owner = mysqli_real_escape_string($conn, $column[5]);
			}

			$pos_id = "";
			if (isset($column[6])) {
				$pos_id = mysqli_real_escape_string($conn, $column[6]);
			}

			$pos_name = "";
			if (isset($column[7])) {
				$pos_name = mysqli_real_escape_string($conn, $column[7]);
			}

			$pos_name_dpsm = "";
			if (isset($column[8])) {
				$pos_name_dpsm = mysqli_real_escape_string($conn, $column[8]);
			}

			$zone = "";
			if (isset($column[9])) {
				$zone = mysqli_real_escape_string($conn, $column[9]);
			}

			$region = "";
			if (isset($column[10])) {
				$region = mysqli_real_escape_string($conn, $column[10]);
			}

			$province = "";
			if (isset($column[11])) {
				$province = mysqli_real_escape_string($conn, $column[11]);
			}

			$city = "";
			if (isset($column[12])) {
				$city = mysqli_real_escape_string($conn, $column[12]);
			}

			$address = "";
			if (isset($column[13])) {
				$address = mysqli_real_escape_string($conn, $column[13]);
			}

			$kam = "";
			if (isset($column[14])) {
				$kam = mysqli_real_escape_string($conn, $column[14]);
			}

			$sales_executive = "";
			if (isset($column[15])) {
				$sales_executive = mysqli_real_escape_string($conn, $column[15]);
			}

			$merchandising = "";
			if (isset($column[16])) {
				$merchandising = mysqli_real_escape_string($conn, $column[16]);
			}

			$supervisor = "";
			if (isset($column[17])) {
				$supervisor = mysqli_real_escape_string($conn, $column[17]);
			}

			$mercaderista = "";
			if (isset($column[18])) {
				$mercaderista = mysqli_real_escape_string($conn, $column[18]);
			}

			$user = "";
			if (isset($column[19])) {
				$user = mysqli_real_escape_string($conn, $column[19]);
			}

			$dpsm = "";
			if (isset($column[20])) {
				$dpsm = mysqli_real_escape_string($conn, $column[20]);
			}

			$status = "";
			if (isset($column[21])) {
				$status = mysqli_real_escape_string($conn, $column[21]);
			}

			$tipo = "";
			if (isset($column[22])) {
				$tipo = mysqli_real_escape_string($conn, $column[22]);
			}

			$latitud = "";
			if (isset($column[23])) {
				$latitud = mysqli_real_escape_string($conn, $column[23]);
			}

			$longitud = "";
			if (isset($column[24])) {
				$longitud = mysqli_real_escape_string($conn, $column[24]);
			}

			$foto = "";
			if (isset($column[25])) {
				$foto = mysqli_real_escape_string($conn, $column[25]);
			}
			
			$segmentacion = "";
			if (isset($column[26])) {
				$segmentacion = mysqli_real_escape_string($conn, $column[26]);
			}
			
			$compras = "";
			if (isset($column[27])) {
				$compras = mysqli_real_escape_string($conn, $column[27]);
			}

			$activar = "";
			if (isset($column[28])) {
				$activar = mysqli_real_escape_string($conn, $column[28]);
			}
            
			$pass = "";
			if (isset($column[29])) {
				$pass = mysqli_real_escape_string($conn, $column[29]);
			}
            
			$numero_controller = "";
			if (isset($column[30])) {
				$numero_controller = mysqli_real_escape_string($conn, $column[30]);
			}

			$distancia = "";
			if (isset($column[31])) {
				$distancia = mysqli_real_escape_string($conn, $column[31]);
			}

			$perimetro = "";
			if (isset($column[32])) {
				$perimetro = mysqli_real_escape_string($conn, $column[32]);
			}
            
            $sqlInsert = "INSERT INTO repositorio_locales_ventas (id,channel,subchannel,channel_segment,format,customer_owner,pos_id,pos_name,pos_name_dpsm,zone,region,province,city,address,kam,sales_executive,merchandising,supervisor,mercaderista,user,dpsm,status,tipo,latitud,longitud,foto,segmentacion,compras,activar,pass,numero_controller,distancia,perimetro)
                   values (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $paramType = "issssssssssssssssssssssssssssssss";
            $paramArray = array(
                $id,
				$channel,
				$subchannel,
				$channel_segment,
				$format,
				$customer_owner,
				$pos_id,
				$pos_name,
				$pos_name_dpsm,
				$zone,
				$region,
				$province,
				$city,
				$address,
				$kam,
				$sales_executive,
				$merchandising,
				$supervisor,
				$mercaderista,
				$user,
				$dpsm,
				$status,
				$tipo,
				$latitud,
				$longitud,
				$foto,
				$segmentacion,
				$compras,
				$activar,
				$pass,
				$numero_controller,
				$distancia,
				$perimetro
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
            $sqlSelect = "SELECT * FROM repositorio_locales_ventas";
            $result = $db->select($sqlSelect);
            if (!empty($result)) {
                ?>
            <table id='userTable'>
            <thead>
                <tr>
                    <th>ID</th>
					<th>CANAL</th>
					<th>CADENA/CLIENTE</th>
					<th>FORMATO</th>
					<th>CODIGO PYDACO</th>
					<th>VENDEDOR</th>
					<th>POS_ID</th>
					<th>LOCAL</th>
					<th>AGENCIA DESPACHO PYDACO</th>
					<th>ZONA</th>
					<th>REGION</th>
					<th>PROVINCIA</th>
					<th>CIUDAD</th>
					<th>DIRECCION</th>
					<th>SEGMENTACION</th>
					<th>SUPERVISOR ALICORP</th>
					<th>MERCHANDISING</th>
					<th>SUPERVISOR LUCKY</th>
					<th>MERCADERISTA</th>
					<th>USUARIO</th>
					<th>RAZON SOCIAL</th>
					<th>STATUS</th>
					<th>TIPO</th>
					<th>LATITUD</th>
					<th>LONGITUD</th>
					<th>FOTO</th>
					<th>SEGMENTACION2</th>
					<th>COMPRAS</th>
					<th>ACTIVAR</th>
					<th>PASSWORD</th>
					<th>NUMERO CONTROLLER</th>
					<th>DISTANCIA</th>
					<th>PERIMETRO</th>
                </tr>
            </thead>
		<?php
                
                foreach ($result as $row) {
                    ?>
                    
                <tbody>
                <tr>
                    <td><?php  echo $row['id']; ?></td>
					<td><?php  echo $row['channel']; ?></td>
					<td><?php  echo $row['subchannel']; ?></td>
					<td><?php  echo $row['channel_segment']; ?></td>
					<td><?php  echo $row['format']; ?></td>
					<td><?php  echo $row['customer_owner']; ?></td>
					<td><?php  echo $row['pos_id']; ?></td>
					<td><?php  echo $row['pos_name']; ?></td>
					<td><?php  echo $row['pos_name_dpsm']; ?></td>
					<td><?php  echo $row['zone']; ?></td>
					<td><?php  echo $row['region']; ?></td>
					<td><?php  echo $row['province']; ?></td>
					<td><?php  echo $row['city']; ?></td>
					<td><?php  echo $row['address']; ?></td>
					<td><?php  echo $row['kam']; ?></td>
					<td><?php  echo $row['sales_executive']; ?></td>
					<td><?php  echo $row['merchandising']; ?></td>
					<td><?php  echo $row['supervisor']; ?></td>
					<td><?php  echo $row['mercaderista']; ?></td>
					<td><?php  echo $row['user']; ?></td>
					<td><?php  echo $row['dpsm']; ?></td>
					<td><?php  echo $row['status']; ?></td>
					<td><?php  echo $row['tipo']; ?></td>
					<td><?php  echo $row['latitud']; ?></td>
					<td><?php  echo $row['longitud']; ?></td>
					<td><?php  echo $row['foto']; ?></td>
					<td><?php  echo $row['segmentacion']; ?></td>
					<td><?php  echo $row['compras']; ?></td>
					<td><?php  echo $row['activar']; ?></td>
					<td><?php  echo $row['pass']; ?></td>
					<td><?php  echo $row['numero_controller']; ?></td>
					<td><?php  echo $row['distancia']; ?></td>
					<td><?php  echo $row['perimetro']; ?></td>
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