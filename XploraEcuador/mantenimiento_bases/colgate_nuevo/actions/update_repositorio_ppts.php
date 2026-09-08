<?php
// actions/update_repositorio_ppts.php

include_once "../includes/db_connect.php";
include_once "../includes/functions.php";
include_once "../includes/config.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        exit;
    }

    $day                    = isset($_POST['day'])                    ? trim($_POST['day'])                    : '';
    $time                   = isset($_POST['time'])                   ? trim($_POST['time'])                   : '';
    $cp_code                = isset($_POST['cp_code'])                ? trim($_POST['cp_code'])                : '';
    $trade                  = isset($_POST['trade'])                  ? trim($_POST['trade'])                  : '';
    $retail_environment     = isset($_POST['retail_environment'])     ? trim($_POST['retail_environment'])     : '';
    $cp_format              = isset($_POST['cp_format'])              ? trim($_POST['cp_format'])              : '';
    $customer_format_banner = isset($_POST['customer_format_banner']) ? trim($_POST['customer_format_banner']) : '';
    $distribuidor           = isset($_POST['distribuidor'])           ? trim($_POST['distribuidor'])           : '';
    $customer               = isset($_POST['customer'])               ? trim($_POST['customer'])               : '';
    $pos                    = isset($_POST['pos'])                    ? trim($_POST['pos'])                    : '';
    $province               = isset($_POST['province'])               ? trim($_POST['province'])               : '';
    $city                   = isset($_POST['city'])                   ? trim($_POST['city'])                   : '';
    $zone                   = isset($_POST['zone'])                   ? trim($_POST['zone'])                   : '';
    $ejecutivo              = isset($_POST['ejecutivo'])              ? trim($_POST['ejecutivo'])              : '';
    $category               = isset($_POST['category'])              ? trim($_POST['category'])               : '';
    $subcategory            = isset($_POST['subcategory'])            ? trim($_POST['subcategory'])            : '';
    $segment                = isset($_POST['segment'])                ? trim($_POST['segment'])                : '';
    $form                   = isset($_POST['form'])                   ? trim($_POST['form'])                   : '';
    $manufacturer           = isset($_POST['manufacturer'])           ? trim($_POST['manufacturer'])           : '';
    $brand                  = isset($_POST['brand'])                  ? trim($_POST['brand'])                  : '';
    $product                = isset($_POST['product'])                ? trim($_POST['product'])                : '';
    $size                   = isset($_POST['size'])                   ? trim($_POST['size'])                   : '';
    $validation             = isset($_POST['validation'])             ? trim($_POST['validation'])             : '';
    $activity               = isset($_POST['activity'])               ? trim($_POST['activity'])               : '';
    $type_of_promotion      = isset($_POST['type_of_promotion'])      ? trim($_POST['type_of_promotion'])      : '';
    $descuento              = isset($_POST['descuento'])              ? trim($_POST['descuento'])              : '';
    $price_talker           = isset($_POST['price_talker'])           ? trim($_POST['price_talker'])           : '';
    $mechanics              = isset($_POST['mechanics'])              ? trim($_POST['mechanics'])              : '';
    $sale_price             = isset($_POST['sale_price'])             ? trim($_POST['sale_price'])             : '';
    $observation            = isset($_POST['observation'])            ? trim($_POST['observation'])            : '';
    $photo_url              = isset($_POST['photo_url'])              ? trim($_POST['photo_url'])              : '';
    $period                 = isset($_POST['period'])                 ? trim($_POST['period'])                 : '';
    $inicio_de_promocion    = isset($_POST['inicio_de_promocion'])    ? trim($_POST['inicio_de_promocion'])    : '';
    $fin_de_promocion       = isset($_POST['fin_de_promocion'])       ? trim($_POST['fin_de_promocion'])       : '';
    $agotar_stock           = isset($_POST['agotar_stock'])           ? trim($_POST['agotar_stock'])           : '';
    $fuente                 = isset($_POST['fuente'])                 ? trim($_POST['fuente'])                 : '';
    $tipo                   = isset($_POST['tipo'])                   ? trim($_POST['tipo'])                   : '';

    $query = "UPDATE repositorio_ppts SET
        day = ?, time = ?, cp_code = ?, trade = ?, retail_environment = ?,
        cp_format = ?, customer_format_banner = ?, distribuidor = ?, customer = ?,
        pos = ?, province = ?, city = ?, zone = ?, ejecutivo = ?, category = ?,
        subcategory = ?, segment = ?, form = ?, manufacturer = ?, brand = ?,
        product = ?, size = ?, validation = ?, activity = ?, type_of_promotion = ?,
        descuento = ?, price_talker = ?, mechanics = ?, sale_price = ?,
        observation = ?, photo_url = ?, period = ?, inicio_de_promocion = ?,
        fin_de_promocion = ?, agotar_stock = ?, fuente = ?, tipo = ?
        WHERE id = ?"; // 36 campos + 1 WHERE = 37 placeholders

    if ($stmt = $mysqli->prepare($query)) {
        $stmt->bind_param(
            "sssssssssssssssssssssssssssssssssssssi", // 36 s + 1 i (id)
            $day, $time, $cp_code, $trade, $retail_environment,
            $cp_format, $customer_format_banner, $distribuidor, $customer,
            $pos, $province, $city, $zone, $ejecutivo, $category,
            $subcategory, $segment, $form, $manufacturer, $brand,
            $product, $size, $validation, $activity, $type_of_promotion,
            $descuento, $price_talker, $mechanics, $sale_price,
            $observation, $photo_url, $period, $inicio_de_promocion,
            $fin_de_promocion, $agotar_stock, $fuente, $tipo,
            $id
        );

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Registro actualizado correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se realizaron cambios o el registro no existe']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta: ' . $stmt->error]);
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al preparar la consulta: ' . $mysqli->error]);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}

$mysqli->close();
?>