<?php
    include_once("includes/db_connect.php");
    $title_zona = $_POST["zona"];
    $title_canal = $_POST["canal"];
    $title_nombre = $_POST["nombre"];
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">
<html>

<head>
    <!-- Compiled with Bootstrap Email version: 1.1.3 -->
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style type="text/css">
        body,
        table,
        td {
            font-family: Helvetica, Arial, sans-serif !important
        }

        .ExternalClass {
            width: 100%
        }

        .ExternalClass,
        .ExternalClass p,
        .ExternalClass span,
        .ExternalClass font,
        .ExternalClass td,
        .ExternalClass div {
            line-height: 150%
        }

        a {
            text-decoration: none
        }

        * {
            color: inherit
        }

        a[x-apple-data-detectors],
        u+#body a,
        #MessageViewBody a {
            color: inherit;
            text-decoration: none;
            font-size: inherit;
            font-family: inherit;
            font-weight: inherit;
            line-height: inherit
        }

        img {
            -ms-interpolation-mode: bicubic
        }

        table:not([class^=s-]) {
            font-family: Helvetica, Arial, sans-serif;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
            border-spacing: 0px;
            border-collapse: collapse
        }

        table:not([class^=s-]) td {
            border-spacing: 0px;
            border-collapse: collapse
        }

        @media screen and (max-width: 600px) {

            .w-full,
            .w-full>tbody>tr>td {
                width: 100% !important
            }

            *[class*=s-lg-]>tbody>tr>td {
                font-size: 0 !important;
                line-height: 0 !important;
                height: 0 !important
            }

            .s-10>tbody>tr>td {
                font-size: 40px !important;
                line-height: 40px !important;
                height: 40px !important
            }
        }
    </style>
</head>

<body class="bg-light" style="outline: 0; width: 100%; min-width: 100%; height: 100%; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; font-family: Helvetica, Arial, sans-serif; line-height: 24px; font-weight: normal; font-size: 16px; -moz-box-sizing: border-box; -webkit-box-sizing: border-box; box-sizing: border-box; color: #000000; margin: 0; padding: 0; border-width: 0;" bgcolor="#f7fafc">
    <table class="bg-light body" valign="top" role="presentation" border="0" cellpadding="0" cellspacing="0" style="outline: 0; width: 100%; min-width: 100%; height: 100%; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; font-family: Helvetica, Arial, sans-serif; line-height: 24px; font-weight: normal; font-size: 16px; -moz-box-sizing: border-box; -webkit-box-sizing: border-box; box-sizing: border-box; color: #000000; margin: 0; padding: 0; border-width: 0;" bgcolor="#f7fafc">
        <tbody>
            <tr>
                <td valign="top" style="line-height: 24px; font-size: 16px; margin: 0;" align="left" bgcolor="#f7fafc">
                    <table class="container" role="presentation" border="0" cellpadding="0" cellspacing="0" style="width: 100%;">
                        <tbody>
                            <tr>
                                <td align="center" style="line-height: 24px; font-size: 16px; margin: 0; padding: 0 16px;">
                                    <!--[if (gte mso 9)|(IE)]>
                      <table align="center" role="presentation">
                        <tbody>
                          <tr>
                            <td width="600">
                    <![endif]-->
                                    <table align="center" role="presentation" border="0" cellpadding="0" cellspacing="0" style="width: 100%; max-width: 1200px; margin: 0 auto;">
                                        <tbody>
                                            <tr>
                                                <td style="line-height: 24px; font-size: 16px; margin: 0;" align="left">
                                                    <!-- CUERPO MENSAJE-->
                                                    <table class="s-3 w-full" role="presentation" border="0" cellpadding="0" cellspacing="0" style="width: 100%;" width="100%">
                                                        <tbody>
                                                            <tr>
                                                                <td style="line-height: 12px; font-size: 12px; width: 100%; height: 12px; margin: 0;" align="left" width="100%" height="12">
                                                                    &#160;
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>

                                                    <p class="text-gray-700" style="line-height: 24px; font-size: 18px; width: 100%; margin: 0;" align="left">Estimado/a <b><?= $title_nombre; ?></b></p>
                                                    <table class="s-3 w-full" role="presentation" border="0" cellpadding="0" cellspacing="0" style="width: 100%;" width="100%">
                                                        <tbody>
                                                            <tr>
                                                                <td style="line-height: 12px; font-size: 12px; width: 100%; height: 12px; margin: 0;" align="left" width="100%" height="12">
                                                                    &#160;
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                    <p class="text-gray-700" style="line-height: 24px; font-size: 18px; width: 100%; margin: 0;" align="left">Se notifica el estado del reporte del día de hoy de la cantidad de PDV’s registrados para Quiebres y Sugeridos y la cantidad de Ventas registradas para Impulso, según el detalle del SKU realizados por Cadena/Distribuidor (<?=$title_zona; ?>):</p>
                                                    <table class="s-3 w-full" role="presentation" border="0" cellpadding="0" cellspacing="0" style="width: 100%;" width="100%">
                                                        <tbody>
                                                            <tr>
                                                                <td style="line-height: 12px; font-size: 12px; width: 100%; height: 12px; margin: 0;" align="left" width="100%" height="12">
                                                                    &#160;
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                    <!-- FIN CUERPO MENSAJE-->

                                                    <table class="s-10 w-full" role="presentation" border="0" cellpadding="0" cellspacing="0" style="width: 100%;" width="100%">
                                                        <tbody>
                                                            <tr>
                                                                <td style="line-height: 40px; font-size: 40px; width: 100%; height: 40px; margin: 0;" align="left" width="100%" height="40">
                                                                    &#160;
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>