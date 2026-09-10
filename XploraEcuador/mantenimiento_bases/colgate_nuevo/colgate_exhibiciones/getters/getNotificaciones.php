<?php
	include_once '../includes/db_connect.php';
	include_once '../includes/functions.php';
	include_once '../includes/config.php';
		
    $query = "SELECT id, usuario, descripcion, fecha, hora FROM lvi_notificaciones /*WHERE estado=0*/";
	
    $contador = 0;

    $html = "<a href='#' class='dropdown-toggle' data-toggle='dropdown' role='button' aria-haspopup='true' aria-expanded='false'><span class='material-icons'>notifications</span>(<b>CONTADOR_NOTIFICACIONES</b>)</a>";
    $html .= "<ul class='dropdown-menu notify-drop'>";
    $html .= "<div class='notify-drop-title'>";
    $html .= "<div class='row'>";
    $html .= "<div class='col-md-6 col-sm-6 col-xs-6'>Notificaciones (<b>CONTADOR_NOTIFICACIONES</b>)</div>";
    $html .= "<div class='col-md-6 col-sm-6 col-xs-6 text-right'><a href='' class='rIcon allRead' data-tooltip='tooltip' data-placement='bottom' title='Todo leído.'><i class='fa fa-dot-circle-o'></i></a></div>";
    $html .= "</div>";
    $html .= "</div>";
    $html .= "<div class='drop-content'>";

    if ($sql = $mysqli->prepare($query)) {
        $sql->execute();
        $sql->store_result();
        if ($sql->num_rows > 0) {
            $sql->bind_result($id, $usuario, $descripcion, $fecha, $hora) or die($sql->error);
            while($sql->fetch()) {
                $html .= "<li>";
                $html .= "<div class='col-md-3 col-sm-3 col-xs-3'>";
                $html .= "<div class='notify-img'><img src='http://placehold.it/45x45' alt=''></div>";
                $html .= "</div>";
                // $html .= "<div class='col-md-9 col-sm-9 col-xs-9 pd-l0'><a href='index.php?aksi=read&nik=$id' class='rIcon'><span class='material-icons'>email</span></a>";
                $html .= "<div class='col-md-9 col-sm-9 col-xs-9 pd-l0'><a href='#' class='rIcon'><span class='material-icons'>email</span></a>";
                $html .= "<h6>" . $usuario . "</h6>";
                $html .= "<p>" . $descripcion . "</p>";
                $html .= "<p class='time'>" . $fecha . " " . $hora . "</p>";
                $html .= "</div>";
                $html .= "</li>";
                $contador++;
            }
        }
        $sql->close();
    }
    $html .= "</div>";
    // $html .= "<div class='notify-drop-footer text-center'>";
    // $html .= "<a href='#' id='showAll' name='showAll'><span><i class='material-icons'>visibility</i></span>Mostrar todo</a>";
    // $html .= "</div>";
    $html .= "</ul>";
    echo str_replace("CONTADOR_NOTIFICACIONES", $contador, $html);
	
?>