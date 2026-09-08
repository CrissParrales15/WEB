<?php
// En getters/get_table_objetivo_sos.php

// Asegúrate de que las rutas a db_connect, functions y config sean correctas
include_once "../includes/db_connect.php";
include_once "../includes/functions.php";
include_once "../includes/config.php";

$fecha_inicio = $_POST['fecha_inicio'] ?? null;
$fecha_fin    = $_POST['fecha_fin'] ?? null;

$query = "SELECT
    id, fecha, canal, retail_enviroment, cliente, visual_access, subcategory, new_objetivo, fecha_modificacion
    FROM tb_objetivo_sos
    WHERE status = 1";

$tipos_bind = ''; 
$parametros_bind = []; 

// FILTRO RANGO FECHAS
if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $query .= " AND fecha BETWEEN ? AND ?";
    $tipos_bind .= 'ss';
    $parametros_bind[] = $fecha_inicio;
    $parametros_bind[] = $fecha_fin;
}

$query .= " ORDER BY id ASC";


if ($sql = $mysqli->prepare($query)) {

    if (!empty($tipos_bind)) {
        array_unshift($parametros_bind, $tipos_bind);
        call_user_func_array([$sql, 'bind_param'], $parametros_bind);
    }
    
    $sql->execute();
    $sql->store_result();
    $html = '';

    if ($sql->num_rows > 0) {
        $html .= '<table id="table" name="table" class="table table-striped" style="width:100%">';
        $html .= '<thead class="thead-light">';
        $html .= '<tr>';

        $html .= '<th scope="col"><input type="checkbox" id="selectAll"></th>'; 
        $html .= '<th scope="col">ID</th>';
        $html .= '<th scope="col">FECHA</th>';
        $html .= '<th scope="col">CANAL</th>';
        $html .= '<th scope="col">RETAIL ENVIROMENT</th>';
        $html .= '<th scope="col">CLIENTE</th>';
        $html .= '<th scope="col">VISUAL ACCESS</th>';
        $html .= '<th scope="col">SUBCATEGORY</th>';
        $html .= '<th scope="col">NUEVO OBJETIVO</th>';
        $html .= '<th scope="col">FECHA MODIFICACIÓN</th>';
        $html .= '<th scope="col">ACCIONES</th>';
        
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        // Bind de resultados (9 campos)
        $sql->bind_result(
            $id,
            $fecha,
            $canal,
            $retail_enviroment,
            $cliente,
            $visual_access,
            $subcategory,
            $new_objetivo,
            $fecha_modificacion
        ) or die($sql->error);

        while ($sql->fetch()) {
            // Filas de tabla
            $html .= '<tr>';
            $html .= '<td><input type="checkbox" class="select-row" value="' . $id . '"></td>'; 
            $html .= '<td>' . $id . '</td>';
            $html .= '<td>' . $fecha . '</td>';
            $html .= '<td>' . $canal . '</td>';
            $html .= '<td>' . $retail_enviroment . '</td>';
            $html .= '<td>' . $cliente . '</td>';
            $html .= '<td>' . $visual_access . '</td>';
            $html .= '<td>' . $subcategory . '</td>';
            // Formateo simple para el decimal, puedes ajustarlo si es necesario
            $html .= '<td>' . number_format($new_objetivo, 2, ',', '.') . '</td>'; 
            $html .= '<td>' . $fecha_modificacion . '</td>';
            
            // Botón de edición con data-atributos
            $html .= '<td>
                <button class="btn btn-sm btn-primary btn-editar" 
                    data-id="' . htmlspecialchars($id, ENT_QUOTES) . '" 
                    data-fecha="' . htmlspecialchars($fecha, ENT_QUOTES) . '" 
                    data-canal="' . htmlspecialchars($canal, ENT_QUOTES) . '" 
                    data-retail_enviroment="' . htmlspecialchars($retail_enviroment, ENT_QUOTES) . '" 
                    data-cliente="' . htmlspecialchars($cliente, ENT_QUOTES) . '" 
                    data-visual_access="' . htmlspecialchars($visual_access, ENT_QUOTES) . '" 
                    data-subcategory="' . htmlspecialchars($subcategory, ENT_QUOTES) . '" 
                    data-new_objetivo="' . htmlspecialchars($new_objetivo, ENT_QUOTES) . '">
                    <i class="material-icons" style="font-size: 16px; vertical-align: middle;">edit</i> Editar
                </button>
            </td>';
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        $html .= '</table>';
    } else {
        $html .= '<p class="alert alert-warning">No se encontraron registros.</p>';
    }
    $sql->close();
}
echo $html;
?>