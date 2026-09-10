<?php

use Phppot\DataSource;

require_once 'DataSource.php';
$db = new DataSource();
$conn = $db->getConnection();

$nombres = $_GET['nombres'];
$total_nombres = explode(',', $nombres);

$ids = $_GET['ids'];
$total_materiales = explode(',', $ids);


for ($i = 0; $i <  count($total_materiales); $i++) {
    $id = $total_materiales[$i];
    $sqlDelete = "DELETE FROM repositorio_materiales WHERE id = $id";
    $insertId = $db->delete($sqlDelete);
}


if(!empty($insertId)){

    echo json_encode('Registro eliminado exitosamente.');

}else{

echo json_encode('No se pudo eliminar el registro.');

}


?>   

