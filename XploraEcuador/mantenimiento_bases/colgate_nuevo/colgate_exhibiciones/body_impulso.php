<?php
$cont_sub = 0;
$cont = 0;

$query = "";
$sql_sub = "";
$resultado_sub = "";
$sql = "";
$resultado = "";
$mensaje = "";

$query = "SELECT sku_code, channel, ";

$sql_sub = $mysqli->prepare("SELECT channel, customer_owner FROM luckyec_salica.reporte_impulso WHERE customer_owner!='-' AND zone REGEXP ? AND channel REGEXP ? GROUP BY customer_owner");
$sql_sub->bind_param('ss', $title_zona, $title_canal);
$sql_sub->execute();
$resultado_sub = $sql_sub->get_result();
if (mysqli_num_rows($resultado_sub) > 0) {
  foreach ($resultado_sub as $fila_sub) {
    $caracteres = array(".", " ", "&");
    $reemplazos = array("", "_", "");
    $column_name = str_replace($caracteres, $reemplazos, $fila_sub['customer_owner']);

    if ($cont_sub == 0) {
      $query .= " SUM(CASE WHEN customer_owner='" . $fila_sub['customer_owner'] . "' THEN cumplimiento ELSE '' END) AS " . $column_name;
    } else {
      $query .= ", SUM(CASE WHEN customer_owner='" . $fila_sub['customer_owner'] . "' THEN cumplimiento ELSE '' END) AS " . $column_name;
    }
    $cont_sub++;
  }
  $query .= " FROM luckyec_salica.reporte_impulso WHERE zone REGEXP ? AND channel REGEXP ? GROUP BY sku_code, channel ORDER BY sku_code, channel ASC";

  $sql = $mysqli->prepare($query);
  $sql->bind_param('ss', $title_zona, $title_canal);
  $sql->execute();
  $resultado = $sql->get_result(); ?>

  <table class="card" role="presentation" border="0" cellpadding="0" cellspacing="0" style="border-radius: 6px; border-collapse: separate !important; width: 100%; overflow: hidden; border: 1px solid #e2e8f0;" bgcolor="#ffffff">
    <tbody>
      <tr>
        <td style="line-height: 24px; font-size: 13px; width: 100%; margin: 0;" align="left" bgcolor="#ffffff">
          <table class="card-body" role="presentation" border="0" cellpadding="0" cellspacing="0" style="width: 100%;">
            <tbody>
              <tr>
                <td style="line-height: 24px; font-size: 13px; width: 100%; margin: 0; padding: 20px;" align="left">
                  <?php
                  $cont = 1;
                  $contador = $fila['contador'];
                  $semana = $fila['semana'];
                  $division = $fila['division'];
                  $cuenta = $fila['cuenta'];

                  $mensaje .= "<h1 class='h3' style='padding-top: 0; padding-bottom: 0; font-weight: 500; vertical-align: baseline; font-size: 28px; line-height: 33.6px; margin: 0;' align='left'><b>IMPULSO</b></h1>
                              <table class='s-2 w-full' role='presentation' border='0' cellpadding='0' cellspacing='0' style='width: 100%;' width='100%'>
                              <tbody>
                                  <tr>
                                  <td style='line-height: 8px; font-size: 8px; width: 100%; height: 8px; margin: 0;' align='left' width='100%' height='8'>
                                      &#160;
                                  </td>
                                  </tr>
                              </tbody>
                              </table>";

                  $mensaje .= "<table class='table table-striped thead-default table-bordered' border='0' cellpadding='0' cellspacing='0' style='width: 100%; max-width: 100%; border: 1px solid #e2e8f0;'><thead><tr>";
                  $mensaje .= "<th style='line-height: 24px; font-size: 13px; margin: 0; padding: 12px; background-color:#0d6efd; color:white; border-color: #e2e8f0; border-style: solid; border-width: 1px 1px 2px;' align='left' valign='top'>Sku</th>";

                  foreach ($resultado_sub as $fila_sub) {
                    $column_name = str_replace($caracteres, $reemplazos, $fila_sub['customer_owner']);
                    $mensaje .= "<th style='width: 10%; line-height: 24px; font-size: 13px; margin: 0; padding: 5px; background-color:#0d6efd; color:white; border-color: #e2e8f0; border-style: solid; border-width: 1px 1px 2px;' align='left' valign='top'>" . $fila_sub['customer_owner'] . "</th>";
                  }
                  $mensaje .= "</tr></thead><tbody>";
                  $mensaje .= $mensaje_data;

                  foreach ($resultado as $fila) {
                    $bg = "";
                    if ($cont % 2 == 0) {
                      $bg = "bgcolor='#f2f2f2'";
                    }
                    $mensaje .= "<tr " . $bg . ">";
                    $mensaje .= "<td style='line-height: 24px; font-size: 13px; margin: 0; padding: 12px; border: 1px solid #e2e8f0;' align='left' valign='top'>" . $fila['sku_code'] . "</td>";
                    foreach ($resultado_sub as $fila_sub) {
                      $column_name = str_replace($caracteres, $reemplazos, $fila_sub['customer_owner']);
                      $mensaje .= "<td style='width: 10%; line-height: 24px; font-size: 13px; margin: 0; padding: 12px; border: 1px solid #e2e8f0;' align='center' valign='top'>" . $fila[$column_name] . "</td>";
                    }
                    $mensaje .= "</tr>";
                    $cont++;
                  }

                  $mensaje .= "</tbody></table><br>";

                  echo $mensaje;
                  ?>

                </td>
              </tr>
            </tbody>
          </table>
        </td>
      </tr>
    </tbody>
  </table>
<?php 
} else { ?>
  <p class="text-gray-700" style="line-height: 24px; font-size: 15px; width: 100%; margin: 0;" align="left">.:No hay datos que mostrar para el reporte <b>Impulso</b>:.</p>
  <table class="s-3 w-full" role="presentation" border="0" cellpadding="0" cellspacing="0" style="width: 100%;" width="100%">
      <tbody>
          <tr>
              <td style="line-height: 12px; font-size: 12px; width: 100%; height: 12px; margin: 0;" align="left" width="100%" height="12">
                  &#160;
              </td>
          </tr>
      </tbody>
  </table>
<?php 
} 
?>