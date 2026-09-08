<?php
use Phppot\DataSource;

require_once 'DataSource.php';
$db = new DataSource();
$conn = $db->getConnection();

if (isset($_POST["import"])) {
    
    $fileName = $_FILES["file"]["tmp_name"];
    
    if ($_FILES["file"]["size"] > 0) {
        
        $file = fopen($fileName, "r");

		$sqlTruncate = "TRUNCATE table repositorio_preguntas;";
        $db->truncate($sqlTruncate);
		
		while (($column = fgetcsv($file, 10000, ";")) !== FALSE) {
			$id = "";
			if (isset($column[0])) {
				$id = mysqli_real_escape_string($conn, $column[0]);
			}

			$question = "";
			if (isset($column[1])) {
				$question = mysqli_real_escape_string($conn, $column[1]);
			}

			$answer = "";
			if (isset($column[2])) {
				$answer = mysqli_real_escape_string($conn, $column[2]);
			}

			$opta = "";
			if (isset($column[3])) {
				$opta = mysqli_real_escape_string($conn, $column[3]);
			}

			$optb = "";
			if (isset($column[4])) {
				$optb = mysqli_real_escape_string($conn, $column[4]);
			}

			$optc = "";
			if (isset($column[5])) {
				$optc = mysqli_real_escape_string($conn, $column[5]);
			}

			$canal = "";
			if (isset($column[6])) {
				$canal = mysqli_real_escape_string($conn, $column[6]);
			}

			$tiempo = "";
			if (isset($column[7])) {
				$tiempo = mysqli_real_escape_string($conn, $column[7]);
			}

			$test_id = "";
			if (isset($column[8])) {
				$test_id = mysqli_real_escape_string($conn, $column[8]);
			}
			
            $sqlInsert = "INSERT INTO repositorio_preguntas(id, question, answer, opta, optb, optc, canal, tiempo, test_id) VALUES (?,?,?,?,?,?,?,?,?)";
            $paramType = "sssssssss";
            $paramArray = array(
				$id,
                $question,
				$answer,
				$opta,
				$optb,
				$optc,
				$canal,
				$tiempo,
				$test_id
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
        if (!regex.question($("#file").val().toLowerCase())) {
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
    <h2>Base question</h2>

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
            $sqlSelect = "SELECT * FROM repositorio_preguntas";
            $result = $db->select($sqlSelect);
            if (!empty($result)) {
                ?>
            <table id='userTable'>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>QUESTION</th>
					<th>ANSWER</th>
					<th>OPCION A</th>
					<th>OPCION B</th>
					<th>OPCION C</th>
					<th>CANAL</th>
					<th>TIEMPO</th>
					<th>TEST ID</th>
                </tr>
            </thead>
		<?php
                
                foreach ($result as $row) {
                    ?>
                    
                <tbody>
                <tr>
                    <td><?php  echo $row['id']; ?></td>
                    <td><?php  echo utf8_encode($row['question']); ?></td>
					<td><?php  echo utf8_encode($row['answer']); ?></td>
					<td><?php  echo utf8_encode($row['opta']); ?></td>
					<td><?php  echo utf8_encode($row['optb']); ?></td>
					<td><?php  echo utf8_encode($row['optc']); ?></td>
					<td><?php  echo utf8_encode($row['canal']); ?></td>
					<td><?php  echo $row['tiempo']; ?></td>
					<td><?php  echo $row['test_id']; ?></td>
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