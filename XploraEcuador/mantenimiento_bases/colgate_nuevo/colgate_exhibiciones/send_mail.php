<?php
    include_once 'includes/db_connect.php';
	use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    
    require $_SERVER["DOCUMENT_ROOT"].'/App/XploraEcuador/assets/pluginsV5/vendor/autoload.php';

    if ($sql = $mysqli->prepare("SELECT nombre, correo, GROUP_CONCAT(zona SEPARATOR '|') AS zona, GROUP_CONCAT(canal SEPARATOR '|') AS canal, correos_cc, correos_cco FROM luckyec_salica.lvi_correos WHERE correo IS NOT NULL GROUP BY 1 ORDER BY correo;")){
        $sql->execute();    // Ejecuta la consulta preparada.
        $sql->store_result();
        if ($sql->num_rows > 0) {
            $sql->bind_result($nombre, $correo, $zona, $canal, $correos_cc, $correos_cco) or die($sql->error);
            while ($sql->fetch()) {
                $url_path = 'https://webecuador.azurewebsites.net/App/XploraEcuador/mantenimiento_bases/salica/body_mail.php';
                $data = array(
                    'zona' => $zona, 
                    'canal' => $canal, 
                    'nombre' => $nombre);
                $options = array(
                    'http' => array(
                    'method' => 'POST',
                    'content' => http_build_query($data))
                );
                $stream = stream_context_create($options);
                $mailContent = file_get_contents($url_path, false, $stream);

                $subject = "Alerta Campos Xplora Ecuador";
                $Subject = "=?ISO-8859-1?B?".base64_encode($subject)."=?=";

                // $ruta = "https://webecuador.azurewebsites.net/App/XploraEcuador/test3.php";
                // $ruta .= "?correo=" . $correo;
                // $ruta .= "&kam=" . $kam;
                // $mailContent = file_get_contents(var_dump($ruta));// LLAMADO A PHP
                
                $mail = new PHPMailer(); // create a new object
                $mail->IsSMTP(); // enable SMTP
                $mail->SMTPDebug = 0; // debugging: 1 = errors and messages, 2 = messages only
                $mail->SMTPAuth = true; // authentication enabled
                $mail->SMTPSecure = 'tls'; // secure transfer enabled REQUIRED for Gmail
                $mail->Host = "smtp.office365.com";
                $mail->Port = 587; // or 587
                // $mail->IsHTML(true);
                $mail->Username='alertascampos@booombtl.com.ec';
                $mail->Password='Booom062022';
                
                $mail->SetFrom("alertascampos@booombtl.com.ec", "Alerta Campos Xplora Ecuador");
                $mail->AddAddress($correo, $nombre);

                if ($correos_cc != null) {
                    $emailCC = explode(',', $correos_cc);
                    for ($i = 0; $i < count($emailCC); $i++) {
                        $mail->AddCC($emailCC[$i]);
                    }
                }

                if ($correos_cco != null) {
                    $emailCCO = explode(',', $correos_cco);
                    for ($j = 0; $j < count($emailCCO); $j++) {
                        $mail->AddBCC($emailCCO[$j]);
                    }
                }

                $mail->CharSet = 'UTF-8';
                $mail->Subject = $subject;
                $mail->Body    = $mailContent;
                $mail->IsHTML(true);

                if ($correo != '' || $correo != null) {
                    if(!$mail->Send()) {
                        $sessData['estado']['type'] = 'error';
                        // $sessData['estado']['msg'] = 'Error de envio.';
                        echo '1';
                    } else {
                        $sessData['estado']['type'] = 'success';
                        // $sessData['estado']['msg'] = 'Por favor revise su correo electrónico, hemos enviado un enlace de restablecimiento de contraseña a su correo electrónico registrado.';
                        echo '2'.'|'.$correo;
                    }
                } else {
                    echo '3';
                }
            }
        } else {
            //NO HAY REGISTROS
        }
    }
?>