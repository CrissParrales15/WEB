<?php

use Phppot\DataSource;

require_once '../DataSource.php';
$db = new DataSource();
$conn = $db->getConnection();
$data_array = array();
//$cuenta = $_POST['cuenta'];


$sqlSelect = "SELECT * FROM repositorio_materiales where tipo like '%image%'";
$paramType = "s";
$paramArray = [];
$result = $db->select($sqlSelect,$paramType,$paramArray);
$url = 'https://luckyecuadorweb.blob.core.windows.net/app/App5pGo';  /*'https://webecuador.azurewebsites.net/App/AppTomebambaEvaluaciones/Site/data'*/;

if (!empty($result)) {
    foreach ($result as $key => $value) {

        $img_tipo = '<img src="images/image3.png" style="width: 25px;">';

        if($value['estado'] == 1){
            $estado = '<button id="btn_visibility" class="btn btn-success">VISIBLE</button>';
        } else {
            $estado = '<button id="btn_visibility" class="btn btn-danger">NO VISIBLE</button>';
        }

        $btn_copiar = '<button id="btn_copiar" class="btn btn-success ">Copiar Enlace</button>';

        $btn_eliminar = '<button id="btn_eliminar" class="btn btn-danger "><i class="bi bi-trash"></i></button>';

        $checkBox = "<input class='form-check-input checkBox' type='checkbox' value='' id='checkbox'>";
    
        $nombre_archivo = '<a class="link" href="'. $url . substr($value["ruta_archivo"],2).'">'. $value['nombre'] .'</a>';
        $tipo = explode('/',$value['tipo']) ;
        $formato = $tipo[1];

        $data_array[] = array(
            $value['id'],
            $img_tipo,
            $nombre_archivo,
            $formato,
            $value['fecha_subida'],
            $url . substr($value["ruta_archivo"],2),
            $btn_copiar,
            //'<div class="copy-icon"><span id="copy-quote"><img src="https://i.imgur.com/zHgKDpN.png" alt="" /></span></div>',
            $checkBox
        );
    }
}
$new_array  = array("data" => $data_array);

// $new_array = array_map('utf8_encode_recursive', $new_array);

// function utf8_encode_recursive($value) {
//     if (is_array($value)) {
//         return array_map('utf8_encode_recursive', $value);
//     } else {
//         return utf8_encode($value);
//     }
// }

if (!empty($new_array)) {
    echo json_encode($new_array);
} else{
    echo json_encode('NO DATA');
}