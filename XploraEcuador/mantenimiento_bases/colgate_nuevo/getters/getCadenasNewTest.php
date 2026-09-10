<?php
include_once "../includes/db_connect.php";
include_once "../includes/functions.php";
include_once "../includes/config.php";

sec_session_start();

$canal = $_GET["canal"];

// ========== CONSTRUIR QUERY SEGÚN CANAL ==========
if ($canal != "TODOS" && !empty($canal) && $canal != "") {
    // Filtrar por canal específico
    $query = "SELECT DISTINCT subchannel FROM repositorio_locales_dtt2 
              WHERE channel = ? 
                AND subchannel IS NOT NULL AND subchannel != '' 
                AND subchannel NOT LIKE '%PRUEBA%' 
                AND subchannel NOT LIKE '%LUCKY%' 
                AND subchannel NOT LIKE '%IMPULSO%' 
                AND subchannel NOT LIKE '%NO CADENA%' 
                AND subchannel NOT LIKE '%NA%' 
              ORDER BY subchannel";
    $usarBind = true;
} else {
    // "TODOS" o vacío → traer todas las cadenas sin filtrar por canal
    $query = "SELECT DISTINCT subchannel FROM repositorio_locales_dtt2 
              WHERE subchannel IS NOT NULL AND subchannel != '' 
                AND subchannel NOT LIKE '%PRUEBA%' 
                AND subchannel NOT LIKE '%LUCKY%' 
                AND subchannel NOT LIKE '%IMPULSO%' 
                AND subchannel NOT LIKE '%NO CADENA%' 
                AND subchannel NOT LIKE '%NA%' 
              ORDER BY subchannel";
    $usarBind = false;
}

$html = "<option value=''>Seleccione</option>";
$html .= "<option value='TODOS'>TODOS</option>";

if ($sql = $mysqli->prepare($query)) {
    if ($usarBind) {
        $sql->bind_param('s', $canal);
    }
    $sql->execute();
    $sql->store_result();
    if ($sql->num_rows > 0) {
        $sql->bind_result($subchannel) or die($sql->error);
        while ($sql->fetch()) {
            $html .= "<option value='".$subchannel."'>".$subchannel."</option>";
        }
    }
    $sql->close();
}
echo $html;
?>