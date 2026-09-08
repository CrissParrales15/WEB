<?php

use Phppot\DataSource;

require_once 'DataSource.php';

$db = new DataSource();
$data_array = array();

$sqlSelect = "SELECT * FROM informativo_pdv";
$result = $db->select($sqlSelect);

if (!empty($result)) {

    array_walk_recursive($result, function(&$item, $key){
        if(!mb_detect_encoding($item, 'utf-8', true)){
                $item = utf8_encode($item);
        }
    });

    foreach ($result as $key => $value) {

        $data_array[] = array(
            $value['pos_id'],
            $value['local'],
            $value['precios'],
            $value['sos'],
            $value['onpacks'],
            $value['invent_sugerido'],
            $value['antes_despues'],
            $value['promociones'],
            $value['avance_general'],
        );
    }
}

$new_array  = array("data" => $data_array);

if (!empty($new_array)) {
    echo json_encode($new_array);
} else{
    echo json_encode('NO DATA');
}
