<?php
    use Phppot\DataSource;

    require_once 'DataSource.php';
    require $_SERVER["DOCUMENT_ROOT"].'/App/XploraEcuador/includes/functions.php';

    $db = new DataSource();
    $conn = $db->getConnection();
    sec_session_start();

    $usuario = $_SESSION['username'];

    $fecha_inicio = $_POST["fecha_inicio"];
    $fecha_fin = $_POST["fecha_fin"];
    $id_pdv = $_POST["id_pdv"];
    $id_usuario = $_POST["id_usuario"];
    $termometro = $_POST["termometro"];

    $number_days = explode (",", $_POST["days"]);

    // $usuario = "jherrera";
    $id_estado = "1";
    $status = "1";
    $habilitado = "1";

    // $number_days = array("1", "5");

    $startDate = new DateTime($fecha_inicio);
    $endDate = new DateTime($fecha_fin);
    // $endDate->add(new DateInterval('P1D'));
    $endDate->modify('+1 day');

    function isMonday($date) {
        return $date->format('N') === '1';
    }

    function isTuesday($date) {
        return $date->format('N') === '2';
    }

    function isWednesday($date) {
        return $date->format('N') === '3';
    }

    function isThrusday($date) {
        return $date->format('N') === '4';
    }

    function isFriday($date) {
        return $date->format('N') === '5';
    }

    function isSaturday($date) {
        return $date->format('N') === '6';
    }

    function isSunday($date) {
        return $date->format('N') === '7';
    }

    function getDays($start, $end, $number_days) {
        $days = [];

        $datePeriod = new DatePeriod($start, new DateInterval('P1D'), $end);
        foreach ($number_days as $number_day) {
            foreach ($datePeriod as $date) {
                switch ($number_day) {
                    case 1:
                        if (isMonday($date)) $days[] = $date;
                        break;
                    case 2:
                        if (isTuesday($date)) $days[] = $date;
                        break;
                    case 3:
                        if (isWednesday($date)) $days[] = $date;
                        break;
                    case 4:
                        if (isThrusday($date)) $days[] = $date;
                        break;
                    case 5:
                        if (isFriday($date)) $days[] = $date;
                        break;
                    case 6:
                        if (isSaturday($date)) $days[] = $date;
                        break;
                    case 7:
                        if (isSunday($date)) $days[] = $date;
                        break;
                }
            }
        }
        return json_encode($days);
    }

    // var_dump(getMondays($startDate, $endDate));
    $data = json_decode(getDays($startDate, $endDate, $number_days));
    $contador = 0;

    foreach ($data as $object) {
        $new_date_format = date('Y-m-d', strtotime($object->date));
        // echo $new_date_format . "<br>";

        $sqlInsert = "INSERT INTO rutero_pdv (id_pdv, id_usuario, id_supervisor, termometro, fecha_visita, id_estado, status, habilitado, usuario_creacion) 
                    SELECT ?,?,supervisor,?,?,?,?,?,? FROM repositorio_locales_dtt2 WHERE id=?";
        $paramType = "sssssssss";
        $paramArray = array(
            $id_pdv,
            $id_usuario,
            $termometro,
            $new_date_format,
            $id_estado,
            $status,
            $habilitado,
            $usuario,
            $id_pdv
        );
        $insertId = $db->insert($sqlInsert, $paramType, $paramArray);
        
        if (!empty($insertId)) {
            $type = "success";
        } else {
            $type = "error";
        }
        $contador++;
    }

    echo "TYPE: " . $type . " ";
    echo 
        "FECHA INICIO: " . $fecha_inicio . " - " . 
        "FECHA FIN: " . $fecha_fin . " - " . 
        "ID PDV: " . $id_pdv . " - " . 
        "ID USUARIO: " . $id_usuario . " - " . 
        "ID ESTADO: " . $id_estado . " - " . 
        "TERMOMETRO: " . $termometro . " - " . 
        "DIAS: " . $number_days . " - " . 
        "TYPE: " . $type . " - " . 
        "CONTADOR: " . $contador;
?>