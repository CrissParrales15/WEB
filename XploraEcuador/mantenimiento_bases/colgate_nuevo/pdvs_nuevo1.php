<?php

use Phppot\DataSource;

require_once 'DataSource.php';
$db = new DataSource();
$conn = $db->getConnection();

if ($conn instanceof mysqli) {
    $conn->set_charset("utf8mb4");
    $conn->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
} else {
    $conn->exec("SET NAMES utf8mb4");
}

// =====================================================================
// [DEBUG-IMPORT] Arnés de diagnóstico para la carga por lotes (AJAX).
// Captura incluso errores FATALES (memoria, tiempo agotado, mysqli en
// modo excepción, funciones no definidas, etc.) que un try/catch normal
// NO atrapa, y los devuelve como JSON para que se vean en la consola
// F12 del navegador. Cuando el import ya funcione, se puede QUITAR
// todo este bloque (busca "DEBUG-IMPORT" para encontrar los añadidos).
// =====================================================================
$__DEBUG_LOTE = isset($_POST['lote_data']);
if ($__DEBUG_LOTE) {
    @ini_set('display_errors', '0'); // no mezclar HTML de error con el JSON
    error_reporting(E_ALL);
    ob_start(); // capturamos cualquier warning/notice/HTML accidental

    register_shutdown_function(function () {
        $err = error_get_last();
        $buffer = (ob_get_level() > 0) ? ob_get_clean() : '';
        $fatales = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];

        if ($err && in_array($err['type'], $fatales, true)) {
            // Terminó por un error fatal: lo devolvemos como JSON legible
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=UTF-8');
                // [DEBUG-IMPORT] 200 a propósito: si mandamos 500, el
                // ErrorDocument de Apache descarta este JSON y muestra su
                // página "500". Con 200 el mensaje real SÍ llega al navegador.
                http_response_code(200);
            }
            echo json_encode([
                "status"        => "error",
                "tipo"          => "FATAL_PHP",
                "message"       => $err['message'],
                "archivo"       => $err['file'],
                "linea"         => $err['line'],
                "salida_previa" => mb_substr($buffer, 0, 800)
            ]);
        } else {
            // Sin fatal: reemitimos el JSON normal que ya se generó
            echo $buffer;
        }
    });
}
// ============ fin arnés DEBUG-IMPORT ============

// ---------------------------------------------------------------
// IMPORT MASIVO CON TABLA "STAGING" (carga segura, cancelable)
//
// Ya no existe el modo "solo actualizar/agregar": todo import REEMPLAZA
// la tabla completa. Para que esto sea seguro y cancelable sin dejar la
// tabla real a medias, el proceso funciona en 2 fases:
//
//  FASE 1 (por cada lote, mientras se sube el archivo):
//    Los datos se insertan en repositorio_locales_dtt2_staging (una
//    tabla IDÉNTICA a la real, pero vacía/de trabajo). La tabla REAL
//    (repositorio_locales_dtt2) no se toca en ningún momento aquí.
//    -> Requiere crear esta tabla una sola vez en la BD:
//       CREATE TABLE repositorio_locales_dtt2_staging
//       LIKE repositorio_locales_dtt2;
// 
//  FASE 2 (solo si el usuario NO cancela y todos los lotes subieron bien):
//    El JS llama a "confirmar_import", que hace un RENAME TABLE de 3
//    vías (swap atómico, prácticamente instantáneo sin importar el
//    tamaño): la tabla staging pasa a ser la real, y la real (antigua)
//    pasa a ser la nueva staging (se vaciará en el próximo import).
//
//  CANCELAR: el JS llama a "cancelar_import", que solo vacía la tabla
//    staging. La tabla real NUNCA se tocó, así que queda exactamente
//    como estaba antes de empezar. Nada que revertir.
// ---------------------------------------------------------------

$TABLA_REAL    = 'repositorio_locales_dtt2';
$TABLA_STAGING = 'repositorio_locales_prueba_staging';

if (isset($_POST['lote_data']) && !empty($_POST['lote_data'])) {

    header('Content-Type: application/json; charset=UTF-8');

    try {
        $lote_data = json_decode($_POST['lote_data'], true);

        if ($lote_data === null) {
            http_response_code(200); // [DEBUG-IMPORT] 200 para no gatillar ErrorDocument
            echo json_encode(["status" => "error", "tipo" => "JSON_INVALIDO", "message" => "JSON mal formado o vacío."]);
            exit;
        }

        $GLOBALS['__lote_error'] = null;               // [DEBUG-IMPORT]

        // El JS manda nuevo_import=1 SOLO en el primer lote de una carga
        // nueva. Vaciamos la tabla STAGING (no la real) y le damos un
        // AUTO_INCREMENT seguro (por encima del id máximo actual), para
        // que una fila sin id explícito no choque con ids ya usados.
        $vaciada = false;
        if (isset($_POST['nuevo_import']) && $_POST['nuevo_import'] == '1') {
            $conn->query("TRUNCATE TABLE `$TABLA_STAGING`");
            $r = $conn->query("SELECT COALESCE(MAX(id),0)+1 AS next_id FROM `$TABLA_REAL`");
            $nextId = $r ? (int)$r->fetch_assoc()['next_id'] : 1;
            $conn->query("ALTER TABLE `$TABLA_STAGING` AUTO_INCREMENT = $nextId");
            $vaciada = true;
        }

        $resultado = procesarLote($lote_data, $conn, $TABLA_STAGING);

        if ($resultado) {
            $st = $GLOBALS['__lote_stats'] ?? ['insertados'=>0,'actualizados'=>0,'validos'=>0];
            echo json_encode([
                "status"       => "success",
                "version"      => "v8-staging-cancelable", // [DEBUG-IMPORT] confirma archivo desplegado
                "message"      => "Lote procesado correctamente.",
                "filas"        => count($lote_data),     // [DEBUG-IMPORT]
                "insertados"   => $st['insertados'],      // [DEBUG-IMPORT]
                "actualizados" => $st['actualizados'],    // [DEBUG-IMPORT]
                "validos"      => $st['validos'],         // [DEBUG-IMPORT]
                "tabla_vaciada"=> $vaciada                // [DEBUG-IMPORT]
            ]);
        } else {
            http_response_code(200); // [DEBUG-IMPORT] 200 para que Apache NO oculte el mysql_error
            echo json_encode([
                "status"      => "error",
                "tipo"        => "DB_ERROR",             // [DEBUG-IMPORT]
                "message"     => "Error al insertar el lote en la BD.",
                "mysql_error" => $GLOBALS['__lote_error'] ?? $conn->error // [DEBUG-IMPORT]
            ]);
        }
    } catch (\Throwable $e) {
        http_response_code(200); // [DEBUG-IMPORT] 200 para no gatillar ErrorDocument
        echo json_encode([
            "status"  => "error",
            "tipo"    => "EXCEPCION_PHP",               // [DEBUG-IMPORT]
            "message" => $e->getMessage(),
            "archivo" => $e->getFile(),                 // [DEBUG-IMPORT]
            "linea"   => $e->getLine()                  // [DEBUG-IMPORT]
        ]);
    }
    exit;
}

// FASE 2: confirmar el import (swap atómico staging <-> real).
// Solo se llama cuando TODOS los lotes se subieron sin error y sin cancelar.
if (isset($_POST['confirmar_import'])) {
    header('Content-Type: application/json; charset=UTF-8');
    try {
        $r = $conn->query("SELECT COUNT(*) AS c FROM `$TABLA_STAGING`");
        $cnt = $r ? (int)$r->fetch_assoc()['c'] : 0;

        if ($cnt < 1) {
            echo json_encode(["status" => "error", "message" => "La tabla temporal está vacía; no hay nada que confirmar."]);
            exit;
        }

        $tmp = $TABLA_REAL . '_swap_tmp';
        $ok = $conn->query(
            "RENAME TABLE `$TABLA_REAL` TO `$tmp`, `$TABLA_STAGING` TO `$TABLA_REAL`, `$tmp` TO `$TABLA_STAGING`"
        );

        if ($ok) {
            echo json_encode(["status" => "success", "message" => "Importación confirmada.", "total" => $cnt]);
        } else {
            echo json_encode(["status" => "error", "message" => "Error al confirmar (swap de tablas): " . $conn->error]);
        }
    } catch (\Throwable $e) {
        echo json_encode(["status" => "error", "message" => "Excepción al confirmar: " . $e->getMessage()]);
    }
    exit;
}

// CANCELAR: solo vacía la tabla staging. La tabla real NUNCA se tocó.
if (isset($_POST['cancelar_import'])) {
    header('Content-Type: application/json; charset=UTF-8');
    try {
        $conn->query("TRUNCATE TABLE `$TABLA_STAGING`");
        echo json_encode(["status" => "success", "message" => "Carga cancelada. La tabla no fue modificada."]);
    } catch (\Throwable $e) {
        echo json_encode(["status" => "error", "message" => "Excepción al cancelar: " . $e->getMessage()]);
    }
    exit;
}

if (isset($_POST["delete_by_excel"])) {

    $fileName = $_FILES["file_delete"]["tmp_name"];
    $fileExtension = strtolower(pathinfo($_FILES["file_delete"]["name"], PATHINFO_EXTENSION));

    if ($_FILES["file_delete"]["size"] > 0) {

        $all_ids = array();

        if ($fileExtension === 'csv') {
            $file = fopen($fileName, "r");
            $bom = fread($file, 3);
            if ($bom !== "\xEF\xBB\xBF") { rewind($file); }
            $primeraFila = true;
            while (($column = fgetcsv($file, 27000, ";")) !== FALSE) {
                if ($primeraFila) { $primeraFila = false; continue; }
                $id_candidato = isset($column[0]) ? trim($column[0]) : '';
                if (is_numeric($id_candidato) && (int)$id_candidato > 0) {
                    $all_ids[] = (int)$id_candidato;
                }
            }
            fclose($file);

        } elseif ($fileExtension === 'xlsx' || $fileExtension === 'xls') {
            require_once 'SimpleXLSX.php';
            if ($xlsx = SimpleXLSX::parse($fileName)) {
                $rows = $xlsx->rows();
                $firstRow = true;
                foreach ($rows as $row) {
                    if ($firstRow) { $firstRow = false; continue; }
                    $id_candidato = isset($row[0]) ? trim($row[0]) : '';
                    if (is_numeric($id_candidato) && (int)$id_candidato > 0) {
                        $all_ids[] = (int)$id_candidato;
                    }
                }
            } else {
                $type = "error";
                $message = "Error al leer el archivo Excel: " . (SimpleXLSX::parseError() ?? 'Verifica la ruta de SimpleXLSX.php');
            }
        }

        if (!empty($all_ids) && !isset($type)) {
            $unique_ids = array_unique($all_ids);
            $delete_result = softDeleteByIds($unique_ids);
            if ($delete_result['success']) {
                $type = "success";
                $message = $delete_result['message'];
            } else {
                $type = "error";
                $message = "Error en Delete: " . $delete_result['message'];
            }
        } elseif (!isset($type)) {
            $type = "warning";
            $message = "Archivo leído, pero no se encontraron IDs válidos para eliminar.";
        }

    } else {
        $type = "error";
        $message = "El archivo subido está vacío.";
    }

    if (isset($type) && $type === "success") {
        header("Location: " . $_SERVER['PHP_SELF'] . "?delete_status=success&count=" . $delete_result['actualizados']);
        exit;
    }
}

if (isset($_GET['delete_status']) && $_GET['delete_status'] === 'success') {
    $count = isset($_GET['count']) ? (int)$_GET['count'] : 0;
    $type = "success";
    $message = "El borrado masivo se completó correctamente. Se eliminaron $count registro(s).";
}

if (isset($_GET['import_status']) && $_GET['import_status'] === 'success') {
    $type = "success";
    $message = "La importación se completó correctamente.";
}


// ---------------------------------------------------------------
// procesarLote: usa $conn directamente con prepared statements
// para respetar utf8mb4 y evitar corrupción de caracteres
//
// ORDEN DE COLUMNAS DEL ARCHIVO DEL CLIENTE (0-indexed). Son 29 columnas:
// 0 id, 1 pos_id, 2 sales_executive, 3 channel, 4 subchannel, 5 format,
// 6 pos_name_dpsm, 7 kam, 8 merchandising, 9 customer_owner,
// 10 pos_name, 11 dpsm, 12 region, 13 tipo, 14 province, 15 city,
// 16 zone, 17 address, 18 supervisor, 19 latitud, 20 longitud,
// 21 channel_segment, 22 visual, 23 coordinador, 24 foto, 25 status,
// 26 perimetro, 27 distancia, 28 activar
//
// ⚠️ CAMBIO IMPORTANTE (v2): el archivo del cliente SÍ trae 'id' como
// primera columna. Ahora el upsert se hace por 'id' (clave primaria),
// no por 'pos_id': si el 'id' del archivo ya existe en la tabla, se
// ACTUALIZA esa fila (incluido su pos_id, por si lo corrigieron); si
// el 'id' no existe o viene vacío, se INSERTA como registro nuevo
// (con ese id explícito, o autogenerado si vino vacío).
//
// 'pos_id' sigue teniendo índice UNIQUE en la tabla. Si el archivo
// trajera un pos_id que ya usa OTRO id distinto, esa fila puntual
// fallará con "Duplicate entry" (se reporta el error de esa fila,
// como antes); no debería pasar si el archivo del cliente es consistente.
//
// 'reabrev', 'color' y 'tiempo_visita' NO vienen en el archivo del
// cliente. En filas nuevas entran NULL; en filas existentes NO se
// tocan (se preservan), por eso no están en el ON DUPLICATE KEY UPDATE.
// 'activar' SÍ viene (última columna); si viene vacío, 'SI' por defecto.
// ---------------------------------------------------------------
function procesarLote($lote, $conn, $tabla = 'repositorio_locales_dtt2')
{
    // Upsert por 'id' (clave primaria) vía INSERT ... ON DUPLICATE KEY
    // UPDATE. Al listar 'id' en el INSERT (con su valor explícito del
    // archivo), un id ya existente dispara la rama UPDATE de esa misma
    // fila. pos_id también se actualiza aquí (antes no, porque el upsert
    // se hacía por pos_id y no tenía sentido reescribirlo sobre sí mismo).
    //
    // $tabla: nombre de la tabla destino (staging durante el import;
    // ver bloque de FASE 1/2 más arriba). Es un valor fijo controlado
    // por el propio código (no viene del usuario), así que interpolarlo
    // directamente en el SQL es seguro aquí.
    $sqlInsert = "INSERT INTO `$tabla`
    (id, pos_id, sales_executive, channel, subchannel, reabrev, format,
     pos_name_dpsm, kam, merchandising, customer_owner, pos_name, dpsm,
     region, tipo, province, city, zone, address, supervisor, latitud,
     longitud, channel_segment, visual, coordinador, foto, status,
     perimetro, distancia, activar, color, tiempo_visita)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ON DUPLICATE KEY UPDATE
        pos_id          = VALUES(pos_id),
        sales_executive = VALUES(sales_executive),
        channel         = VALUES(channel),
        subchannel      = VALUES(subchannel),
        format          = VALUES(format),
        pos_name_dpsm   = VALUES(pos_name_dpsm),
        kam             = VALUES(kam),
        merchandising   = VALUES(merchandising),
        customer_owner  = VALUES(customer_owner),
        pos_name        = VALUES(pos_name),
        dpsm            = VALUES(dpsm),
        region          = VALUES(region),
        tipo            = VALUES(tipo),
        province        = VALUES(province),
        city            = VALUES(city),
        zone            = VALUES(zone),
        address         = VALUES(address),
        supervisor      = VALUES(supervisor),
        latitud         = VALUES(latitud),
        longitud        = VALUES(longitud),
        channel_segment = VALUES(channel_segment),
        visual          = VALUES(visual),
        coordinador     = VALUES(coordinador),
        foto            = VALUES(foto),
        status          = VALUES(status),
        perimetro       = VALUES(perimetro),
        distancia       = VALUES(distancia),
        activar         = VALUES(activar)";

    $stmt = $conn->prepare($sqlInsert);
    if (!$stmt) {
        $GLOBALS['__lote_error'] = "prepare() falló: " . $conn->error; // [DEBUG-IMPORT]
        return false;
    }

    $id_registro = null;
    $pos_id = $sales_executive = $channel = $subchannel = $reabrev = $format = '';
    $pos_name_dpsm = $kam = $merchandising = $customer_owner = $pos_name = $dpsm = '';
    $region = $tipo = $province = $city = $zone = $address = $supervisor = $latitud = '';
    $longitud = $channel_segment = $visual = $coordinador = $foto = $status = '';
    $perimetro = $distancia = $activar = $color = $tiempo_visita = '';

    $stmt->bind_param(
        "isssssssssssssssssssssssssssssss", // i + 31 s = 32 (verificado)
        $id_registro, $pos_id, $sales_executive, $channel, $subchannel, $reabrev, $format,
        $pos_name_dpsm, $kam, $merchandising, $customer_owner, $pos_name, $dpsm,
        $region, $tipo, $province, $city, $zone, $address, $supervisor, $latitud,
        $longitud, $channel_segment, $visual, $coordinador, $foto, $status,
        $perimetro, $distancia, $activar, $color, $tiempo_visita
    );

    $registrosValidos = 0;
    $insertados = 0;   // [DEBUG-IMPORT] filas nuevas
    $actualizados = 0; // [DEBUG-IMPORT] filas que ya existían (por pos_id) y se actualizaron
    $huboError = false;

    $conn->begin_transaction();

    foreach ($lote as $fila) {

        $hayDato = false;
        for ($i = 1; $i <= 28; $i++) { // 0 es 'id'; se revisan las 28 columnas de datos
            if (isset($fila[$i]) && trim($fila[$i]) !== "" && strtoupper(trim($fila[$i])) !== "NULL") {
                $hayDato = true;
                break;
            }
        }
        if (!$hayDato) continue;

        // Helper: trim + recorte a 200 chars (todas las columnas son
        // VARCHAR(200)). Además, los marcadores de NULL exportados por
        // MySQL ('\N') y el texto 'NULL' se convierten a NULL real.
        $f = function($i) use ($fila) {
            $v = trim($fila[$i] ?? "");
            if ($v === '\\N' || strtoupper($v) === 'NULL') return null;
            return mb_substr($v, 0, 200);
        };

        // id: columna 0. Si viene vacío/no numérico -> NULL (autoincrement
        // genera uno nuevo, INSERT). Si viene con valor -> upsert por ese id.
        $id_raw = trim($fila[0] ?? "");
        $id_registro = (is_numeric($id_raw) && (int)$id_raw > 0) ? (int)$id_raw : null;

        $pos_id           = $f(1);
        // pos_id es UNIQUE: si viene vacío, mandamos NULL (NULL no colisiona
        // en un índice UNIQUE; '' sí colisionaría entre varias filas vacías).
        if ($pos_id === '') { $pos_id = null; }
        $sales_executive  = $f(2);
        $channel          = $f(3);
        $subchannel       = $f(4);
        $format           = $f(5);
        $pos_name_dpsm    = $f(6);
        $kam              = $f(7);
        $merchandising    = $f(8);
        $customer_owner   = $f(9);
        $pos_name         = $f(10);
        $dpsm             = $f(11);
        $region           = $f(12);
        $tipo             = $f(13);
        $province         = $f(14);
        $city             = $f(15);
        $zone             = $f(16);
        $address          = $f(17);
        $supervisor       = $f(18);
        $latitud          = $f(19);
        $longitud         = $f(20);
        $channel_segment  = $f(21);
        $visual           = $f(22);
        $coordinador      = $f(23);
        $foto             = $f(24);
        $status           = $f(25);
        $perimetro        = $f(26);
        $distancia        = $f(27);
        // activar VIENE del archivo (última columna). Si viene vacío/NULL, 'SI'.
        $activar          = $f(28);
        if ($activar === null || $activar === '') { $activar = 'SI'; }
        // reabrev, color y tiempo_visita NO vienen en el archivo del cliente:
        // en filas nuevas entran NULL; en filas existentes no se tocan (no
        // están en el ON DUPLICATE KEY UPDATE, así que se preservan).
        $reabrev          = null;
        $color            = null;
        $tiempo_visita    = null;

        if (!$stmt->execute()) {
            // [DEBUG-IMPORT] guardamos el error real y en qué fila ocurrió
            $GLOBALS['__lote_error'] = "execute() falló en fila #" . ($registrosValidos + 1)
                                     . " (id=" . ($id_registro ?? 'NULL') . ", pos_id=" . $pos_id . "): " . $stmt->error;
            $huboError = true;
            break;
        }

        // [DEBUG-IMPORT] affected_rows en ON DUPLICATE KEY UPDATE:
        //   1 = fila NUEVA insertada
        //   2 = fila EXISTENTE actualizada
        //   0 = fila existente sin cambios (valores idénticos)
        $aff = $stmt->affected_rows;
        if ($aff === 1)      $insertados++;
        else if ($aff >= 2)  $actualizados++;

        $registrosValidos++;
    }

    if ($registrosValidos === 0 || $huboError) {
        $conn->rollback();
        $stmt->close();
        return false;
    }

    $conn->commit();
    $stmt->close();
    // [DEBUG-IMPORT] exponemos el desglose para el JSON de respuesta
    $GLOBALS['__lote_stats'] = [
        'insertados'   => $insertados,
        'actualizados' => $actualizados,
        'validos'      => $registrosValidos
    ];
    return true;
}


function softDeleteByIds(array $ids) {
    global $conn;
    if (!isset($conn) || $conn->connect_error) {
        return ['success' => false, 'message' => 'Error de conexión a la base de datos.'];
    }

    $ids = array_filter($ids, function($id) {
        return is_numeric($id) && (int)$id > 0;
    });

    if (empty($ids)) {
        return ['success' => false, 'message' => 'IDs inválidos en el archivo.'];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    // ⚠️ Ajustado: soft-delete vía "activar" en vez de "status"
    $query = "UPDATE repositorio_locales_dtt2 SET activar = 'NO' WHERE id IN ($placeholders)";

    if ($stmt = $conn->prepare($query)) {
        $types = str_repeat('i', count($ids));
        $bind_params = array($types);
        foreach ($ids as &$id) {
            $bind_params[] = &$id;
        }
        call_user_func_array(array($stmt, 'bind_param'), $bind_params);

        if ($stmt->execute()) {
            $actualizados = $stmt->affected_rows;
            $stmt->close();
            return [
                'success'      => true,
                'message'      => "Delete exitoso. Se actualizaron $actualizados registro(s).",
                'actualizados' => $actualizados
            ];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Error al ejecutar: ' . $error];
        }
    } else {
        return ['success' => false, 'message' => 'Error al preparar: ' . $conn->error];
    }
}

?>

<!DOCTYPE html>
<html>

<head>

<link rel="stylesheet" href="/App/XploraEcuador/assets/css/bootstrap-5.1.3.min.css">

<link rel="stylesheet" href="style.css">
<script src="/App/XploraEcuador/assets/js/jquery-3.2.1.min.js"></script>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link rel="stylesheet" href="/App/XploraEcuador/assets/css/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
<link href="/App/XploraEcuador/assets/css/select2.min.css" rel="stylesheet" />

<style>
    .input-row {
        margin-top: 0px;
        margin-bottom: 20px;
    }

    .btn-submit {
        background: #333;
        border: #1d1d1d 1px solid;
        color: #f0f0f0;
        font-size: 0.9em;
        width: 100px;
        border-radius: 2px;
        cursor: pointer;
    }

    #response {
        padding: 10px;
        margin-bottom: 10px;
        border-radius: 2px;
        display: none;
    }

    .success {
        background: #c7efd9;
        border: #bbe2cd 1px solid;
    }

    #loading {
        display: none;
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(255,255,255,0.7);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }
</style>

</head>

<body>

<div id="loading"><div class="spinner-border" role="status"></div></div>

<div class="container-fluid mt-3">

    <?php if (isset($type) && isset($message)): ?>
        <div class="alert alert-<?php echo $type === 'success' ? 'success' : ($type === 'warning' ? 'warning' : 'danger'); ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <h3>Repositorio Locales DTT2 1</h3>

    <div class="row input-row">
        <div class="col-md-4">
            <label>Importar registros (CSV/XLSX)</label>
            <form method="POST" enctype="multipart/form-data" id="frmImport">
                <input type="file" name="file" id="file" accept=".csv,.xlsx,.xls">
                <button type="submit" id="submit" name="import" class="btn btn-primary btn-sm mt-1">Importar</button>
                <div id="progreso-contenedor-import" style="display:none;" class="mt-2">
                    <div class="progress">
                        <div id="barra-progreso-import" class="progress-bar" role="progressbar" style="width:0%">0%</div>
                    </div>
                    <small id="progreso-texto-import"></small>
                    <button type="button" id="btn-cancelar-import" class="btn btn-outline-danger btn-sm mt-1">Cancelar carga</button>
                </div>
            </form>
        </div>

        <div class="col-md-4">
            <label>Eliminar por lote (Excel/CSV con IDs en la primera columna)</label>
            <form method="POST" enctype="multipart/form-data" id="formDeleteExcel">
                <input type="file" name="file_delete" id="file_delete" class="form-control" accept=".csv,.xlsx,.xls" required>
                <button type="button" id="btnConfirmDeleteExcel" class="btn btn-danger btn-sm mt-1">Eliminar por Excel</button>
                <div id="progreso-contenedor" style="display:none;" class="mt-2">
                    <div class="progress">
                        <div id="barra-progreso" class="progress-bar" role="progressbar" style="width:0%">0%</div>
                    </div>
                    <small id="progreso-texto"></small>
                </div>
            </form>
        </div>
    </div>

    <button type="button" class="btn btn-success mb-3" data-toggle="modal" data-target="#modalAgregar">
        <i class="material-icons" style="font-size:16px;vertical-align:middle;">add</i> Agregar Registro
    </button>

    <button type="button" id="btnEliminarSeleccionados" class="btn btn-danger mb-3" style="display:none;">
        Eliminar seleccionados (<span id="contadorSeleccionados">0</span>)
    </button>

    <div class="table-responsive">
        <table id="table" class="table table-striped" style="width:100%">
            <thead class="thead-light">
                <tr>
                    <th scope="col"><input type="checkbox" id="selectAll"></th>
                    <th scope="col">ID</th>
                    <th scope="col">POS ID</th>
                    <th scope="col">SALES EXECUTIVE</th>
                    <th scope="col">CHANNEL</th>
                    <th scope="col">SUBCHANNEL</th>
                    <th scope="col">FORMAT</th>
                    <th scope="col">POS NAME DPSM</th>
                    <th scope="col">KAM</th>
                    <th scope="col">MERCHANDISING</th>
                    <th scope="col">CUSTOMER OWNER</th>
                    <th scope="col">POS NAME</th>
                    <th scope="col">DPSM</th>
                    <th scope="col">REGION</th>
                    <th scope="col">TIPO</th>
                    <th scope="col">PROVINCE</th>
                    <th scope="col">CITY</th>
                    <th scope="col">ZONE</th>
                    <th scope="col">ADDRESS</th>
                    <th scope="col">SUPERVISOR</th>
                    <th scope="col">LATITUD</th>
                    <th scope="col">LONGITUD</th>
                    <th scope="col">CHANNEL SEGMENT</th>
                    <th scope="col">VISUAL</th>
                    <th scope="col">COORDINADOR</th>
                    <th scope="col">FOTO</th>
                    <th scope="col">STATUS</th>
                    <th scope="col">PERIMETRO</th>
                    <th scope="col">DISTANCIA</th>
                    <th scope="col">ACTIVAR</th>
                    <th scope="col">ACCIONES</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- MODAL EDITAR -->
<div class="modal fade" id="modalEditar" tabindex="-1" role="dialog" aria-labelledby="modalEditarLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarLabel">Editar Registro Local DTT2</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <form id="formEditar">
                    <input type="hidden" id="edit_id" name="edit_id">
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <label>POS ID</label>
                            <input type="text" class="form-control bg-light" id="edit_pos_id" name="edit_pos_id" readonly title="El POS ID es el identificador único y no se puede editar">
                            <small class="text-muted">No editable (identificador único)</small>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Sales Executive</label>
                            <input type="text" class="form-control" id="edit_sales_executive" name="edit_sales_executive">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Channel</label>
                            <input type="text" class="form-control" id="edit_channel" name="edit_channel">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Subchannel</label>
                            <input type="text" class="form-control" id="edit_subchannel" name="edit_subchannel">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Reabrev</label>
                            <input type="text" class="form-control" id="edit_reabrev" name="edit_reabrev">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Format</label>
                            <input type="text" class="form-control" id="edit_format" name="edit_format">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>POS Name DPSM</label>
                            <input type="text" class="form-control" id="edit_pos_name_dpsm" name="edit_pos_name_dpsm">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>KAM</label>
                            <input type="text" class="form-control" id="edit_kam" name="edit_kam">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Merchandising</label>
                            <input type="text" class="form-control" id="edit_merchandising" name="edit_merchandising">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Customer Owner</label>
                            <input type="text" class="form-control" id="edit_customer_owner" name="edit_customer_owner">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>POS Name</label>
                            <input type="text" class="form-control" id="edit_pos_name" name="edit_pos_name">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>DPSM</label>
                            <input type="text" class="form-control" id="edit_dpsm" name="edit_dpsm">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Region</label>
                            <input type="text" class="form-control" id="edit_region" name="edit_region">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Tipo</label>
                            <input type="text" class="form-control" id="edit_tipo" name="edit_tipo">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Province</label>
                            <input type="text" class="form-control" id="edit_province" name="edit_province">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>City</label>
                            <input type="text" class="form-control" id="edit_city" name="edit_city">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Zone</label>
                            <input type="text" class="form-control" id="edit_zone" name="edit_zone">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Address</label>
                            <input type="text" class="form-control" id="edit_address" name="edit_address">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Supervisor</label>
                            <input type="text" class="form-control" id="edit_supervisor" name="edit_supervisor">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label>Latitud</label>
                            <input type="text" class="form-control" id="edit_latitud" name="edit_latitud">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label>Longitud</label>
                            <input type="text" class="form-control" id="edit_longitud" name="edit_longitud">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label>Channel Segment</label>
                            <input type="text" class="form-control" id="edit_channel_segment" name="edit_channel_segment">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label>Visual</label>
                            <input type="text" class="form-control" id="edit_visual" name="edit_visual">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Coordinador</label>
                            <input type="text" class="form-control" id="edit_coordinador" name="edit_coordinador">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Foto (URL)</label>
                            <input type="text" class="form-control" id="edit_foto" name="edit_foto">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Status</label>
                            <select class="form-control" id="edit_status" name="edit_status">
                                <option value="ABIERTO">ABIERTO</option>
                                <option value="CERRADO">CERRADO</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Perimetro</label>
                            <input type="text" class="form-control" id="edit_perimetro" name="edit_perimetro">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Distancia</label>
                            <input type="text" class="form-control" id="edit_distancia" name="edit_distancia">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Color</label>
                            <input type="text" class="form-control" id="edit_color" name="edit_color">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Tiempo Visita</label>
                            <input type="text" class="form-control" id="edit_tiempo_visita" name="edit_tiempo_visita">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" id="btnGuardarCambios" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL AGREGAR -->
<div class="modal fade" id="modalAgregar" tabindex="-1" role="dialog" aria-labelledby="modalAgregarLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAgregarLabel">Agregar Nuevo Registro Local DTT2</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <form id="formAgregar">
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <label>POS ID</label>
                            <input type="text" class="form-control" id="add_pos_id" name="add_pos_id">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Sales Executive</label>
                            <input type="text" class="form-control" id="add_sales_executive" name="add_sales_executive">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Channel</label>
                            <input type="text" class="form-control" id="add_channel" name="add_channel">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Subchannel</label>
                            <input type="text" class="form-control" id="add_subchannel" name="add_subchannel">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Reabrev</label>
                            <input type="text" class="form-control" id="add_reabrev" name="add_reabrev">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Format</label>
                            <input type="text" class="form-control" id="add_format" name="add_format">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>POS Name DPSM</label>
                            <input type="text" class="form-control" id="add_pos_name_dpsm" name="add_pos_name_dpsm">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>KAM</label>
                            <input type="text" class="form-control" id="add_kam" name="add_kam">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Merchandising</label>
                            <input type="text" class="form-control" id="add_merchandising" name="add_merchandising">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Customer Owner</label>
                            <input type="text" class="form-control" id="add_customer_owner" name="add_customer_owner">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>POS Name</label>
                            <input type="text" class="form-control" id="add_pos_name" name="add_pos_name">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>DPSM</label>
                            <input type="text" class="form-control" id="add_dpsm" name="add_dpsm">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Region</label>
                            <input type="text" class="form-control" id="add_region" name="add_region">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Tipo</label>
                            <input type="text" class="form-control" id="add_tipo" name="add_tipo">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Province</label>
                            <input type="text" class="form-control" id="add_province" name="add_province">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>City</label>
                            <input type="text" class="form-control" id="add_city" name="add_city">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Zone</label>
                            <input type="text" class="form-control" id="add_zone" name="add_zone">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Address</label>
                            <input type="text" class="form-control" id="add_address" name="add_address">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Supervisor</label>
                            <input type="text" class="form-control" id="add_supervisor" name="add_supervisor">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label>Latitud</label>
                            <input type="text" class="form-control" id="add_latitud" name="add_latitud">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label>Longitud</label>
                            <input type="text" class="form-control" id="add_longitud" name="add_longitud">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label>Channel Segment</label>
                            <input type="text" class="form-control" id="add_channel_segment" name="add_channel_segment">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label>Visual</label>
                            <input type="text" class="form-control" id="add_visual" name="add_visual">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Coordinador</label>
                            <input type="text" class="form-control" id="add_coordinador" name="add_coordinador">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Foto (URL)</label>
                            <input type="text" class="form-control" id="add_foto" name="add_foto">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Status</label>
                            <select class="form-control" id="add_status" name="add_status">
                                <option value="ABIERTO">ABIERTO</option>
                                <option value="CERRADO">CERRADO</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Perimetro</label>
                            <input type="text" class="form-control" id="add_perimetro" name="add_perimetro">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Distancia</label>
                            <input type="text" class="form-control" id="add_distancia" name="add_distancia">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Color</label>
                            <input type="text" class="form-control" id="add_color" name="add_color">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Tiempo Visita</label>
                            <input type="text" class="form-control" id="add_tiempo_visita" name="add_tiempo_visita">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" id="btnGuardarNuevo" class="btn btn-success">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script src="/App/XploraEcuador/assets/js/popper-1.12.9.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="/App/XploraEcuador/assets/js/bootstrap-4.0.0.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>

<!-- DATATABLE -->
<link rel="stylesheet" href="/App/XploraEcuador/assets/css/dataTables.bootstrap5.min.css">
<script src="/App/XploraEcuador/assets/js/jquery.dataTables.min.js"></script>
<script src="/App/XploraEcuador/assets/js/dataTables.bootstrap5.min.js"></script>
<script src="/App/XploraEcuador/assets/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/select/1.3.1/js/dataTables.select.min.js"></script>
<script src="/App/XploraEcuador/assets/js/jszip.min.js"></script>
<script src="/App/XploraEcuador/assets/js/pdfmake.min.js"></script>
<script src="/App/XploraEcuador/assets/js/vfs_fonts.js"></script>
<script src="/App/XploraEcuador/assets/js/buttons.html5.min.js"></script>
<script src="/App/XploraEcuador/assets/js/buttons.print.min.js"></script>
<script src="/App/XploraEcuador/assets/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.3.2/papaparse.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
    const BATCH_SIZE_IMPORT = 1500;
    const TARGET_URL_IMPORT = window.location.pathname; // se auto-postea al mismo archivo (igual que tb_pdv.php)

    $(document).ready(function() {
        var tableLocales = null;

        inicializarDataTablesLocales();

        $('#clear-filter-locales').on('click', function() {
            if (tableLocales) tableLocales.ajax.reload();
        });

        // =======================================================
        // IMPORT MASIVO POR LOTES (AJAX) -- mismo patrón que tb_pdv.php
        // Evita el 500/timeout de un solo POST gigante: el navegador
        // lee el archivo, lo trocea, y va mandando cada lote aparte.
        // Columnas esperadas por fila (30, sin id ni activar):
        // pos_id, sales_executive, channel, subchannel, reabrev, format,
        // pos_name_dpsm, kam, merchandising, customer_owner, pos_name, dpsm,
        // region, tipo, province, city, zone, address, supervisor, latitud,
        // longitud, channel_segment, visual, coordinador, foto, status,
        // perimetro, distancia, color, tiempo_visita
        // =======================================================
        // Bandera global de cancelación: el botón "Cancelar carga" la activa;
        // el bucle de envío la revisa entre lotes para detenerse.
        let importCancelado = false;
        let importAbortController = null;

        $('#btn-cancelar-import').on('click', function () {
            Swal.fire({
                icon: 'warning',
                title: '¿Cancelar la carga?',
                text: 'Se detendrá el envío. La tabla NO se modificará (queda tal como estaba antes de importar).',
                showCancelButton: true,
                confirmButtonText: 'Sí, cancelar',
                cancelButtonText: 'Seguir cargando',
                confirmButtonColor: '#d33'
            }).then((res) => {
                if (res.isConfirmed) {
                    importCancelado = true;
                    if (importAbortController) importAbortController.abort();
                }
            });
        });

        async function iniciarCargaPorLotesLocales(file, fileExtension) {
            importCancelado = false;
            const reader = new FileReader();
            // Ahora el archivo del cliente SÍ trae la columna 'id' al inicio
            // (29 columnas: id + los 28 campos). Se usa para hacer upsert
            // por 'id' en vez de por 'pos_id'. Si algún archivo viniera SIN
            // id (28 columnas), también se soporta: se inserta como nuevo.
            const COLUMNAS_ESPERADAS = 29;

            $("#submit").prop('disabled', true).text('Procesando...');
            $('#progreso-contenedor-import').show();
            $('#barra-progreso-import').css('width', '0%').text('0%');
            $('#progreso-texto-import').text('Leyendo archivo...');

            reader.onload = async function(e) {
                const data = e.target.result;
                let rows = [];
                let success = true;

                try {
                    if (fileExtension === 'csv') {
                        // El archivo se lee como bytes (ArrayBuffer) y detectamos
                        // la codificación: si NO es UTF-8 válido (típico de CSV
                        // exportado por Excel en Windows), lo decodificamos como
                        // Windows-1252 (Latin-1). Así la "Ñ" no se rompe en "�".
                        let texto;
                        try {
                            texto = new TextDecoder('utf-8', { fatal: true }).decode(data);
                        } catch (encErr) {
                            texto = new TextDecoder('windows-1252').decode(data);
                            console.warn('[IMPORT] El archivo no era UTF-8; se leyó como Windows-1252 (Latin-1).');
                        }
                        // PapaParse maneja comillas, delimitadores y saltos de
                        // línea dentro de campos correctamente.
                        const parsed = Papa.parse(texto, {
                            delimiter: ';',
                            skipEmptyLines: 'greedy',
                            quoteChar: '"'
                        });
                        if (parsed.errors && parsed.errors.length) {
                            console.warn('[IMPORT] Avisos de PapaParse (primeros 5):', parsed.errors.slice(0, 5));
                        }
                        // Normalizamos cada fila a exactamente 29 columnas
                        // (id, pos_id ... activar). El archivo del cliente
                        // trae 'id' primero. Si algún archivo viniera SIN esa
                        // columna (28 datos), le anteponemos una celda vacía
                        // para que 'id' quede vacío (= insertar como nuevo).
                        rows = parsed.data.map(row => {
                            let newRow = Array.isArray(row) ? [...row] : [];
                            if (newRow.length === COLUMNAS_ESPERADAS - 1) {
                                newRow = ['', ...newRow]; // no trae id -> se antepone vacío
                            }
                            while (newRow.length < COLUMNAS_ESPERADAS) newRow.push('');
                            return newRow.slice(0, COLUMNAS_ESPERADAS);
                        });

                    } else if (fileExtension === 'xlsx' || fileExtension === 'xls') {
                        if (typeof XLSX === 'undefined') {
                            Swal.fire('Error', 'La librería SheetJS (xlsx.full.min.js) es necesaria para archivos Excel.', 'error');
                            success = false;
                        } else {
                            const data_array = new Uint8Array(data);
                            const workbook = XLSX.read(data_array, { type: 'array' });
                            const sheetName = workbook.SheetNames[0];
                            rows = XLSX.utils.sheet_to_json(workbook.Sheets[sheetName], { header: 1 });

                            rows = rows.map(row => {
                                let newRow = Array.isArray(row) ? [...row] : [];
                                // Si el archivo NO trae 'id' (28 columnas), se
                                // antepone vacío para que quede sin id (nuevo).
                                if (newRow.length === COLUMNAS_ESPERADAS - 1) {
                                    newRow = ['', ...newRow];
                                }
                                while (newRow.length < COLUMNAS_ESPERADAS) {
                                    newRow.push(null);
                                }
                                return newRow.slice(0, COLUMNAS_ESPERADAS);
                            });

                            rows = rows.filter(row => row.some(cell => cell !== null && cell !== ''));
                        }
                    } else {
                        Swal.fire('Error', 'Formato de archivo no soportado.', 'error');
                        success = false;
                    }
                } catch (error) {
                    Swal.fire('Error', 'Error al parsear el archivo. Verifique el formato.', 'error');
                    console.error('Error de parseo:', error);
                    success = false;
                }

                if (!success || rows.length === 0) {
                    $("#loading").css("display", "none");
                    $("#submit").prop('disabled', false).text('Importar');
                    if (rows.length === 0 && success) {
                        Swal.fire('Atención', 'El archivo está vacío o solo contiene encabezados.', 'warning');
                    }
                    return;
                }

                rows.shift(); // quitar encabezado
                const totalRows = rows.length;
                let processedRows = 0;
                let totalInsertados = 0;   // [DEBUG-IMPORT]
                let totalActualizados = 0; // [DEBUG-IMPORT]

                if (totalRows === 0) {
                    $("#submit").prop('disabled', false).text('Importar');
                    Swal.fire('Atención', 'El archivo solo contiene encabezados. No hay datos para insertar.', 'warning');
                    return;
                }

                for (let i = 0; i < totalRows && success; i += BATCH_SIZE_IMPORT) {

                    // Si se pidió cancelar entre lotes, detenemos el envío aquí.
                    if (importCancelado) {
                        break;
                    }

                    const batch = rows.slice(i, i + BATCH_SIZE_IMPORT);
                    const batchIndex = Math.floor(i / BATCH_SIZE_IMPORT) + 1;
                    const totalBatches = Math.ceil(totalRows / BATCH_SIZE_IMPORT);

                    $('#progreso-texto-import').text(`Procesando lote ${batchIndex} / ${totalBatches} (${processedRows}/${totalRows} filas)...`);

                    try {
                        // ===== [DEBUG-IMPORT] instrumentación de red =====
                        // SOLO en el primer lote mandamos la señal de "import
                        // nuevo": el servidor vacía la tabla STAGING (no la
                        // real) y prepara su AUTO_INCREMENT.
                        const nuevo = (batchIndex === 1) ? '&nuevo_import=1' : '';
                        const bodyStr = `lote_data=${encodeURIComponent(JSON.stringify(batch))}${nuevo}`;
                        const bodyBytes = new Blob([bodyStr]).size;
                        console.group(`%c[LOTE ${batchIndex}/${totalBatches}]`, 'color:#2563eb;font-weight:bold');
                        console.log('Filas en el lote:', batch.length);
                        console.log('Tamaño del cuerpo POST (bytes):', bodyBytes.toLocaleString());
                        console.log('URL destino:', TARGET_URL_IMPORT);
                        console.log('Primera fila del lote:', batch[0]);

                        importAbortController = new AbortController();
                        const response = await fetch(TARGET_URL_IMPORT, {
                            method: 'POST',
                            signal: importAbortController.signal,
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                                'Cache-Control': 'no-cache'
                            },
                            body: bodyStr
                        });

                        // Leemos SIEMPRE como texto primero (nunca .json() a ciegas)
                        const rawText = await response.text();
                        const contentType = response.headers.get('content-type') || '(sin content-type)';
                        console.log('HTTP status:', response.status, response.statusText);
                        console.log('Content-Type:', contentType);
                        console.log('Longitud respuesta (chars):', rawText.length);
                        console.log('%cRESPUESTA CRUDA (primeros 1500 chars):', 'color:#b45309;font-weight:bold');
                        console.log(rawText.slice(0, 1500));

                        let result = null;
                        let parseOk = true;
                        try {
                            result = JSON.parse(rawText);
                        } catch (parseErr) {
                            parseOk = false;
                            console.error('%cLa respuesta NO es JSON. Es lo de arriba ^^^', 'color:#dc2626;font-weight:bold');
                            console.error('Detalle del parseo:', parseErr.message);
                        }
                        console.groupEnd();

                        if (!parseOk) {
                            success = false;
                            // Detectamos pistas típicas en el HTML devuelto
                            let pista = 'La respuesta fue HTML/texto, no JSON.';
                            const low = rawText.toLowerCase();
                            if (low.includes('mod_security') || low.includes('not acceptable') || low.includes('forbidden') || low.includes('406') || low.includes('403')) {
                                pista = 'Parece un BLOQUEO del servidor/WAF (mod_security). PHP no llegó a ejecutarse.';
                            } else if (low.includes('fatal error') || low.includes('parse error') || low.includes('uncaught')) {
                                pista = 'Parece un ERROR FATAL de PHP. Revisa el texto en consola (archivo y línea).';
                            } else if (low.includes('<!doctype') || low.includes('<html')) {
                                pista = 'El servidor devolvió la página completa: el bloque lote_data NO se ejecutó (¿$_POST vacío?).';
                            }
                            Swal.fire('Diagnóstico', `Lote ${batchIndex}: ${pista}\n\nAbre F12 → Console y copia el texto de "RESPUESTA CRUDA".`, 'error');
                        } else if (response.status !== 200 || result.status !== 'success') {
                            console.error(`Error en lote ${batchIndex}:`, result);
                            success = false;
                            const detalle = result.mysql_error || result.message || 'Error desconocido';
                            const extra = result.archivo ? `\n(${result.archivo}:${result.linea})` : '';
                            Swal.fire('Error del servidor', `Lote ${batchIndex} [${result.tipo || '?'}]: ${detalle}${extra}`, 'error');
                        } else {
                            processedRows += batch.length;
                            totalInsertados += (result.insertados || 0);    // [DEBUG-IMPORT]
                            totalActualizados += (result.actualizados || 0); // [DEBUG-IMPORT]
                            console.log(`%cLote OK -> nuevos: ${result.insertados}, actualizados: ${result.actualizados}`, 'color:#16a34a');
                            let porcentaje = Math.min(Math.round((processedRows / totalRows) * 100), 100);
                            $('#barra-progreso-import').css('width', porcentaje + '%').text(porcentaje + '%');
                            $('#progreso-texto-import').text(`Procesando: ${processedRows.toLocaleString()} de ${totalRows.toLocaleString()} filas`);
                        }
                        // ===== fin [DEBUG-IMPORT] =====
                    } catch (error) {
                        if (error.name === 'AbortError') {
                            // Cancelación intencional del usuario, no un error real.
                            console.log('%cLote abortado por cancelación del usuario.', 'color:#d97706');
                        } else {
                            success = false;
                            Swal.fire('Error', `Error de conexión al enviar el lote ${batchIndex}. Revise la consola (F12).`, 'error');
                            console.error('Error de Fetch/AJAX (nivel red):', error);
                        }
                    }
                }

                $("#submit").prop('disabled', false).text('Importar');

                if (importCancelado) {
                    $('#progreso-contenedor-import').hide();
                    $('#barra-progreso-import').css('width', '0%').text('0%');
                    $('#progreso-texto-import').text('');
                    // El usuario canceló: limpiamos la tabla staging en el
                    // servidor. La tabla real nunca se tocó.
                    try {
                        await fetch(TARGET_URL_IMPORT, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: 'cancelar_import=1'
                        });
                    } catch (e) { /* si esto falla no es grave: se limpia en el próximo import */ }
                    console.log('%c[IMPORT CANCELADO] La tabla no fue modificada.', 'color:#d97706;font-weight:bold');
                    Swal.fire('Carga cancelada', 'No se modificó la tabla. Puedes intentar de nuevo cuando quieras.', 'info');

                } else if (success) {
                    // Todos los lotes se subieron bien a staging. Confirmamos
                    // el import: swap atómico staging <-> tabla real.
                    $('#progreso-texto-import').text('Confirmando importación...');
                    try {
                        const confRes = await fetch(TARGET_URL_IMPORT, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: 'confirmar_import=1'
                        });
                        const confText = await confRes.text();
                        let confJson;
                        try { confJson = JSON.parse(confText); } catch (e) { confJson = null; }

                        $('#progreso-contenedor-import').hide();
                        $('#barra-progreso-import').css('width', '0%').text('0%');
                        $('#progreso-texto-import').text('');

                        if (confJson && confJson.status === 'success') {
                            console.log(`%c[IMPORT COMPLETO Y CONFIRMADO] Total nuevos: ${totalInsertados.toLocaleString()} | Total actualizados: ${totalActualizados.toLocaleString()} | Filas leídas: ${totalRows.toLocaleString()}`, 'color:#2563eb;font-weight:bold');
                            Swal.fire({
                                icon: 'success', title: '¡Importación completada!',
                                html: `Filas leídas del archivo: <b>${totalRows.toLocaleString()}</b><br>` +
                                      `Registros NUEVOS insertados: <b>${totalInsertados.toLocaleString()}</b><br>` +
                                      `Registros existentes ACTUALIZADOS: <b>${totalActualizados.toLocaleString()}</b>`,
                                confirmButtonText: 'Aceptar'
                            }).then(() => {
                                if (tableLocales) tableLocales.ajax.reload();
                            });
                        } else {
                            console.error('Error al confirmar el import:', confText);
                            Swal.fire('Error al confirmar', (confJson && confJson.message) || 'No se pudo aplicar la importación. La tabla real no fue modificada.', 'error');
                        }
                    } catch (e) {
                        $('#progreso-contenedor-import').hide();
                        $('#barra-progreso-import').css('width', '0%').text('0%');
                        $('#progreso-texto-import').text('');
                        console.error('Error de red al confirmar el import:', e);
                        Swal.fire('Error al confirmar', 'No se pudo contactar al servidor para confirmar. La tabla real no fue modificada.', 'error');
                    }

                } else {
                    $('#progreso-contenedor-import').hide();
                    $('#barra-progreso-import').css('width', '0%').text('0%');
                    $('#progreso-texto-import').text('');
                    // Error real durante la carga: limpiamos staging, la
                    // tabla real no se tocó en ningún momento.
                    try {
                        await fetch(TARGET_URL_IMPORT, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: 'cancelar_import=1'
                        });
                    } catch (e) { /* no crítico */ }
                    Swal.fire('Carga detenida', 'Ocurrió un error y se detuvo la carga. Revise la consola para más detalles. La tabla real no fue modificada.', 'warning');
                }
            };

            // Leemos SIEMPRE como ArrayBuffer (bytes). Para CSV decodificamos
            // el texto con detección de codificación; para Excel lo usa SheetJS.
            reader.readAsArrayBuffer(file);
        }

        $('#frmImport').on('submit', function(e) {
            e.preventDefault(); // clave: evita el POST tradicional de formulario (y el 413/500)

            const fileInput = $('#file')[0];
            if (fileInput.files.length === 0) {
                Swal.fire('Atención', 'Seleccione un archivo primero.', 'warning');
                return;
            }

            const file = fileInput.files[0];
            const fileExtension = file.name.split('.').pop().toLowerCase();

            if (fileExtension !== 'csv' && fileExtension !== 'xlsx' && fileExtension !== 'xls') {
                Swal.fire('Atención', 'Formato de archivo no soportado. Use CSV, XLSX o XLS.', 'warning');
                return;
            }

            // Todo import REEMPLAZA la tabla completa con el contenido del
            // archivo (ya no existe el modo "solo actualizar/agregar").
            // Como la carga usa una tabla temporal y confirma al final
            // (ver comentario en el bloque PHP), la tabla real solo cambia
            // si el proceso termina bien; se puede cancelar sin dejar nada a medias.
            Swal.fire({
                icon: 'warning',
                title: '¿Reemplazar todos los registros?',
                text: 'La tabla quedará idéntica al archivo que subas. Podrás cancelar mientras se está cargando, sin que se modifique nada.',
                showCancelButton: true,
                confirmButtonText: 'Sí, reemplazar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d33'
            }).then((res) => {
                if (res.isConfirmed) {
                    iniciarCargaPorLotesLocales(file, fileExtension);
                }
            });
        });

        // aqui se usan phps -- getters/get_table_pdvs_nuevo1.php
        function inicializarDataTablesLocales() {

            if (tableLocales) {
                tableLocales.destroy();
                tableLocales = null;
            }

            tableLocales = $('#table').DataTable({
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "getters/get_table_pdvs_nuevo1.php",
                    "type": "POST",
                    "dataSrc": function(json) {
                        $("#loading").css("display", "none");
                        return json.data;
                    },
                    "error": function(xhr, error, thrown) {
                        console.log("URL solicitada:", this.url);
                        console.log("Status:", xhr.status);
                        console.log("Response:", xhr.responseText);
                    }
                },

                "columns": [
                    { "data": 0,  "orderable": false }, // Checkbox
                    { "data": 1  }, // ID
                    { "data": 2  }, // POS ID
                    { "data": 3  }, // SALES EXECUTIVE
                    { "data": 4  }, // CHANNEL
                    { "data": 5  }, // SUBCHANNEL
                    { "data": 6  }, // FORMAT
                    { "data": 7  }, // POS NAME DPSM
                    { "data": 8  }, // KAM
                    { "data": 9  }, // MERCHANDISING
                    { "data": 10 }, // CUSTOMER OWNER
                    { "data": 11 }, // POS NAME
                    { "data": 12 }, // DPSM
                    { "data": 13 }, // REGION
                    { "data": 14 }, // TIPO
                    { "data": 15 }, // PROVINCE
                    { "data": 16 }, // CITY
                    { "data": 17 }, // ZONE
                    { "data": 18 }, // ADDRESS
                    { "data": 19 }, // SUPERVISOR
                    { "data": 20 }, // LATITUD
                    { "data": 21 }, // LONGITUD
                    { "data": 22 }, // CHANNEL SEGMENT
                    { "data": 23 }, // VISUAL
                    { "data": 24 }, // COORDINADOR
                    { "data": 25 }, // FOTO
                    { "data": 26 }, // STATUS
                    { "data": 27 }, // PERIMETRO
                    { "data": 28 }, // DISTANCIA
                    { "data": 29 }, // ACTIVAR
                    { "data": 30, "orderable": false } // ACCIONES
                ],

                "scrollX": true,
                "lengthMenu": [10, 25, 50, 75, 100],
                "responsive": true,
                "dom": 'lBfrtip',
                "buttons": [
                    {
                        text: 'Excel (Todos los registros)',
                        action: function(e, dt, node, config) {
                            exportarTodosLosRegistrosLocales();
                        }
                    },
                    'copy', 'pdf', 'print'
                ],
                "order": [[1, "asc"]],

                "initComplete": function(settings, json) {
                    console.log("DataTables Repositorio Locales DTT2 inicializado");

                    $('#table').off('change', '.select-row').on('change', '.select-row', function() {
                        actualizarContadorSeleccionados();
                        var total   = $('.select-row').length;
                        var checked = $('.select-row:checked').length;
                        $('#selectAll').prop('checked', total === checked && total > 0);
                    });

                    $('#selectAll').off('click').on('click', function() {
                        var isChecked = $(this).prop('checked');
                        tableLocales.rows({ page: 'current' }).nodes().to$().find('.select-row').prop('checked', isChecked);
                        actualizarContadorSeleccionados();
                    });

                    $('#table').off('click', '.btn-editar').on('click', '.btn-editar', function() {
                        const id_registro = $(this).data('id');
                        console.log(`Cargando registro con ID: ${id_registro}`);
                        $('#formEditar')[0].reset();

                        $.ajax({
                            url: 'getters/get_pdvs_nuevo1_by_id.php',
                            type: 'POST',
                            dataType: 'json',
                            data: { id: id_registro },
                            success: function(response) {
                                if (response.status === 'success') {
                                    const data = response.data;
                                    console.log('Datos recibidos:', data);

                                    $('#edit_id').val(data.id);
                                    $('#edit_pos_id').val(data.pos_id);
                                    $('#edit_sales_executive').val(data.sales_executive);
                                    $('#edit_channel').val(data.channel);
                                    $('#edit_subchannel').val(data.subchannel);
                                    $('#edit_reabrev').val(data.reabrev);
                                    $('#edit_format').val(data.format);
                                    $('#edit_pos_name_dpsm').val(data.pos_name_dpsm);
                                    $('#edit_kam').val(data.kam);
                                    $('#edit_merchandising').val(data.merchandising);
                                    $('#edit_customer_owner').val(data.customer_owner);
                                    $('#edit_pos_name').val(data.pos_name);
                                    $('#edit_dpsm').val(data.dpsm);
                                    $('#edit_region').val(data.region);
                                    $('#edit_tipo').val(data.tipo);
                                    $('#edit_province').val(data.province);
                                    $('#edit_city').val(data.city);
                                    $('#edit_zone').val(data.zone);
                                    $('#edit_address').val(data.address);
                                    $('#edit_supervisor').val(data.supervisor);
                                    $('#edit_latitud').val(data.latitud);
                                    $('#edit_longitud').val(data.longitud);
                                    $('#edit_channel_segment').val(data.channel_segment);
                                    $('#edit_visual').val(data.visual);
                                    $('#edit_coordinador').val(data.coordinador);
                                    $('#edit_foto').val(data.foto);
                                    $('#edit_status').val(data.status);
                                    $('#edit_perimetro').val(data.perimetro);
                                    $('#edit_distancia').val(data.distancia);
                                    $('#edit_color').val(data.color);
                                    $('#edit_tiempo_visita').val(data.tiempo_visita);

                                    $('#modalEditar').modal('show');
                                } else {
                                    Swal.fire({ icon: 'error', title: 'Error', text: 'Error al obtener datos: ' + response.message });
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error("Error AJAX:", error);
                                Swal.fire({ icon: 'error', title: 'Error', text: 'Error al obtener el registro.' });
                            }
                        });

                        return false;
                    });

                    actualizarContadorSeleccionados();
                }
            });
        }

        function exportarTodosLosRegistrosLocales() {
            $("#loading").css("display", "flex");

            const form = $('<form>', {
                method: 'POST',
                action: 'getters/export_pdvs_nuevo1_excel.php',
                target: '_blank'
            });

            const searchValue = tableLocales.search();
            if (searchValue) {
                form.append($('<input>', { type: 'hidden', name: 'search_value', value: searchValue }));
            }

            form.appendTo('body').submit().remove();
            setTimeout(function() { $("#loading").css("display", "none"); }, 1000);
        }

        function actualizarContadorSeleccionados() {
            var seleccionados = $('.select-row:checked').length;
            $('#contadorSeleccionados').text(seleccionados);
            if (seleccionados > 0) {
                $('#btnEliminarSeleccionados').show();
            } else {
                $('#btnEliminarSeleccionados').hide();
            }
        }

        // ELIMINAR SELECCIONADOS
        $('#btnEliminarSeleccionados').off('click').on('click', function() {
            var idsSeleccionados = [];
            $('.select-row:checked').each(function() {
                idsSeleccionados.push($(this).val());
            });

            if (idsSeleccionados.length === 0) {
                Swal.fire({ icon: 'warning', title: 'Atención', text: 'Por favor, seleccione al menos un registro para eliminar.' });
                return;
            }

            Swal.fire({
                title: '¿Está seguro?',
                html: 'Está a punto de eliminar <strong>' + idsSeleccionados.length + '</strong> registro(s).<br>Esta acción no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    eliminarPorLotesLocales(idsSeleccionados);
                }
            });
        });

        function eliminarPorLotesLocales(idsSeleccionados) {
            const BATCH_SIZE = 500;
            const totalIds = idsSeleccionados.length;
            let procesados = 0;
            let errores = 0;

            Swal.fire({
                title: 'Procesando...',
                html: `<p>Eliminando registros...</p><p id="progress-text">0 / ${totalIds}</p>`,
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => { Swal.showLoading(); }
            });

            const chunks = [];
            for (let i = 0; i < idsSeleccionados.length; i += BATCH_SIZE) {
                chunks.push(idsSeleccionados.slice(i, i + BATCH_SIZE));
            }

            function procesarSiguienteLote(index) {
                if (index >= chunks.length) {
                    Swal.close();
                    if (errores === 0) {
                        Swal.fire({
                            icon: 'success', title: '¡Eliminado!',
                            text: `Se eliminaron correctamente ${procesados} registro(s).`,
                            timer: 2000, showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'warning', title: 'Proceso Completado con Errores',
                            html: `Se eliminaron <strong>${procesados}</strong> registros.<br>Fallaron <strong>${errores}</strong> registros.`
                        });
                    }
                    tableLocales.ajax.reload();
                    return;
                }

                const loteActual = chunks[index];
                $.ajax({
                    type: "POST",
                    url: "actions/delete_pdvs_nuevo1.php",
                    data: { ids: loteActual },
                    dataType: 'json',
                    success: function(result) {
                        if (result.success) {
                            procesados += result.actualizados || loteActual.length;
                        } else {
                            errores += loteActual.length;
                        }
                        $('#progress-text').text(`${procesados + errores} / ${totalIds}`);
                        procesarSiguienteLote(index + 1);
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        errores += loteActual.length;
                        console.error(`Error en lote ${index + 1}:`, textStatus);
                        $('#progress-text').text(`${procesados + errores} / ${totalIds}`);
                        procesarSiguienteLote(index + 1);
                    }
                });
            }

            procesarSiguienteLote(0);
        }

        // GUARDAR NUEVO REGISTRO
        $('#btnGuardarNuevo').off('click').on('click', function() {
            var formData = {
                pos_id:            $('#add_pos_id').val(),
                sales_executive:   $('#add_sales_executive').val(),
                channel:           $('#add_channel').val(),
                subchannel:        $('#add_subchannel').val(),
                reabrev:           $('#add_reabrev').val(),
                format:            $('#add_format').val(),
                pos_name_dpsm:     $('#add_pos_name_dpsm').val(),
                kam:               $('#add_kam').val(),
                merchandising:     $('#add_merchandising').val(),
                customer_owner:    $('#add_customer_owner').val(),
                pos_name:          $('#add_pos_name').val(),
                dpsm:              $('#add_dpsm').val(),
                region:            $('#add_region').val(),
                tipo:              $('#add_tipo').val(),
                province:          $('#add_province').val(),
                city:              $('#add_city').val(),
                zone:              $('#add_zone').val(),
                address:           $('#add_address').val(),
                supervisor:        $('#add_supervisor').val(),
                latitud:           $('#add_latitud').val(),
                longitud:          $('#add_longitud').val(),
                channel_segment:   $('#add_channel_segment').val(),
                visual:            $('#add_visual').val(),
                coordinador:       $('#add_coordinador').val(),
                foto:              $('#add_foto').val(),
                status:            $('#add_status').val(),
                perimetro:         $('#add_perimetro').val(),
                distancia:         $('#add_distancia').val(),
                color:             $('#add_color').val(),
                tiempo_visita:     $('#add_tiempo_visita').val()
            };

            $("#loading").css("display", "flex");

            $.ajax({
                type: "POST",
                url: "actions/insert_pdvs_nuevo1.php",
                data: formData,
                dataType: 'json',
                success: function(result) {
                    $("#loading").css("display", "none");
                    if (result.success) {
                        Swal.fire({
                            icon: 'success', title: '¡Éxito!',
                            text: result.message || 'Registro agregado correctamente.',
                            timer: 2000, showConfirmButton: false
                        });
                        $('#modalAgregar').modal('hide');
                        $('#formAgregar')[0].reset();
                        tableLocales.ajax.reload();
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: result.message || 'Error al insertar el registro.' });
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    $("#loading").css("display", "none");
                    Swal.fire({ icon: 'error', title: 'Error de Conexión', text: 'No se pudo contactar al servidor: ' + textStatus });
                }
            });
        });

        // GUARDAR CAMBIOS (EDITAR)
        $('#btnGuardarCambios').off('click').on('click', function() {
            var formData = {
                id:                $('#edit_id').val(),
                pos_id:            $('#edit_pos_id').val(),
                sales_executive:   $('#edit_sales_executive').val(),
                channel:           $('#edit_channel').val(),
                subchannel:        $('#edit_subchannel').val(),
                reabrev:           $('#edit_reabrev').val(),
                format:            $('#edit_format').val(),
                pos_name_dpsm:     $('#edit_pos_name_dpsm').val(),
                kam:               $('#edit_kam').val(),
                merchandising:     $('#edit_merchandising').val(),
                customer_owner:    $('#edit_customer_owner').val(),
                pos_name:          $('#edit_pos_name').val(),
                dpsm:              $('#edit_dpsm').val(),
                region:            $('#edit_region').val(),
                tipo:              $('#edit_tipo').val(),
                province:          $('#edit_province').val(),
                city:              $('#edit_city').val(),
                zone:              $('#edit_zone').val(),
                address:           $('#edit_address').val(),
                supervisor:        $('#edit_supervisor').val(),
                latitud:           $('#edit_latitud').val(),
                longitud:          $('#edit_longitud').val(),
                channel_segment:   $('#edit_channel_segment').val(),
                visual:            $('#edit_visual').val(),
                coordinador:       $('#edit_coordinador').val(),
                foto:              $('#edit_foto').val(),
                status:            $('#edit_status').val(),
                perimetro:         $('#edit_perimetro').val(),
                distancia:         $('#edit_distancia').val(),
                color:             $('#edit_color').val(),
                tiempo_visita:     $('#edit_tiempo_visita').val()
            };

            $("#loading").css("display", "flex");

            $.ajax({
                type: "POST",
                url: "actions/update_pdvs_nuevo1.php",
                data: formData,
                dataType: 'json',
                success: function(result) {
                    $("#loading").css("display", "none");
                    if (result.success) {
                        Swal.fire({
                            icon: 'success', title: '¡Éxito!',
                            text: 'Registro actualizado correctamente',
                            timer: 2000, showConfirmButton: false
                        });
                        $('#modalEditar').modal('hide');
                        tableLocales.ajax.reload();
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: result.message || 'Error al actualizar el registro.' });
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    $("#loading").css("display", "none");
                    console.error('Error AJAX:', textStatus, errorThrown);

                    let errorMessage = 'Error en la conexión.';
                    if (textStatus === 'error' && errorThrown === 'Not Found') {
                        errorMessage = 'ERROR 404: Archivo no encontrado.';
                    } else if (jqXHR.responseText && jqXHR.responseText.includes('Fatal error')) {
                        errorMessage = 'ERROR 500: Error de PHP en el servidor.';
                    }

                    Swal.fire({
                        icon: 'error', title: 'Error de Conexión/Servidor',
                        html: errorMessage + '<br>Consulta la consola (F12) para más detalles.'
                    });
                }
            });
        });

        // CONFIRMACIÓN DELETE POR EXCEL
        $('#btnConfirmDeleteExcel').on('click', function(e) {
            e.preventDefault();

            var fileInput = $('#file_delete')[0];
            if (fileInput.files.length === 0) {
                Swal.fire('Error', 'Por favor, selecciona un archivo.', 'error');
                return;
            }

            var file = fileInput.files[0];
            var extension = file.name.split('.').pop().toLowerCase();

            Swal.fire({
                title: '¿Confirmar Eliminación?',
                text: "Se marcarán como eliminados los registros del archivo por lotes.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Sí, Eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#progreso-contenedor').show();
                    $('#btnConfirmDeleteExcel').prop('disabled', true);

                    if (extension === 'csv') {
                        procesarCSV(file);
                    } else {
                        procesarExcel(file);
                    }
                }
            });
        });

        function procesarCSV(file) {
            Papa.parse(file, {
                skipEmptyLines: true,
                complete: function(results) {
                    let ids = results.data.slice(1).map(row => row[0]).filter(id => id && !isNaN(id));
                    enviarLotesAsync(ids);
                }
            });
        }

        function procesarExcel(file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                var data = new Uint8Array(e.target.result);
                var workbook = XLSX.read(data, { type: 'array' });
                var sheet = workbook.Sheets[workbook.SheetNames[0]];
                var json = XLSX.utils.sheet_to_json(sheet, { header: 1 });
                let ids = json.slice(1).map(row => row[0]).filter(id => id && !isNaN(id));
                enviarLotesAsync(ids);
            };
            reader.readAsArrayBuffer(file);
        }

        async function enviarLotesAsync(ids) {
            const tamañoLote = 1000;
            const total = ids.length;
            let procesados = 0;

            for (let i = 0; i < total; i += tamañoLote) {
                let lote = ids.slice(i, i + tamañoLote);
                try {
                    let respuesta = await $.ajax({
                        type: "POST",
                        url: "actions/delete_pdvs_nuevo1.php",
                        data: { ids: lote },
                        dataType: 'json'
                    });

                    if (respuesta.success) {
                        procesados += lote.length;
                        let porcentaje = Math.min(Math.round((procesados / total) * 100), 100);
                        $('#barra-progreso').css('width', porcentaje + '%').text(porcentaje + '%');
                        $('#progreso-texto').text(`Procesando: ${procesados.toLocaleString()} de ${total.toLocaleString()} IDs`);
                    }
                } catch (error) {
                    console.error("Error en lote:", error);
                }
            }
            Swal.fire('¡Éxito!', `Se procesaron ${procesados.toLocaleString()} registros correctamente.`, 'success')
                .then(() => location.reload());
        }
    });
</script>
</body>

</html>