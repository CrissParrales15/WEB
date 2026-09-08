<?php
include_once "../includes/db_connect.php";
include_once "../includes/functions.php";
include_once "../includes/config.php";

ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

$codigo = isset($_REQUEST['codigo']) ? $_REQUEST['codigo'] : '';
$usuario = isset($_REQUEST['usuario']) ? $_REQUEST['usuario'] : '';
$fecha = isset($_REQUEST['fecha']) ? $_REQUEST['fecha'] : '';

header('Content-Type: application/json');

/**
    *========= MODULO==========NOMBRE MODULO==================
    * PRECIOS	PRECIOS (insert_precios_new)
    * SURTIDO_CORRECTO	SURTIDO CORRECTO (insert_presencia_new_3)
    *PRESENCIA_MINIMA	PRESENCIA MINIMA (insert_presenciamin_new_2)
    *PROMOCION	PROMOCION (insert_promocion)
    *VALIDACION_PROMOCION	VALIDACION PROMOCION (insert_validacion_promocion)
    *PROMOCIONES_IA	PROMOCIONES_IA (insert_promocion_ia)
    *MATERIAL_POP	MATERIAL POP (insert_material_pop)
    *SHARE	SHARE (insert_shareshelf)
    *HERRAMIENTAS_EXHIBICION	HERRAM. CP Y COMPETENCIA PRE CARGADAS
    *HERRAMIENTAS_EXHIBICION	EXH. PROMO, ADICIONAL CP/NUEVAS HERRAM. COMP.
    *ALMUERZO	ALMUERZO
    *ENCUESTA_APP	ENCUESTA (insert_evaluacion_encuesta)
 * 
 */

if (empty($codigo) || empty($fecha)) {
    echo json_encode([]);
    exit;
}

// Convertir fecha al formato de la BD (DD/MM/YYYY)
$fecha_mysql = DateTime::createFromFormat('Y-m-d', $fecha);
if ($fecha_mysql) {
    $fecha_bd = $fecha_mysql->format('d/m/Y');
} else {
    $fecha_bd = $fecha;
}

$baseFotoUrl = "https://luckyecuadorweb.blob.core.windows.net/app/CtaColgate/App5pgo/Inserts/";

// Obtener fotos de entrada y salida
$queryFotos = "
    SELECT tipo, foto 
    FROM insert_registro 
    WHERE id_pdv = ? 
    AND fecha = ?
    AND (nombre = ? OR usuario = ?)
    AND tipo IN ('ENTRADA', 'SALIDA')
    ORDER BY tipo
";

$fotos = ['ENTRADA' => '', 'SALIDA' => '']; 

if ($stmtFotos = $mysqli->prepare($queryFotos)) {
    $stmtFotos->bind_param("ssss", $codigo, $fecha_bd, $usuario, $usuario);
    $stmtFotos->execute();
    $resultFotos = $stmtFotos->get_result();
    
    while ($row = $resultFotos->fetch_assoc()) {
        $tipo = $row['tipo'];
        $fotoNombre = $row['foto'];
        
        if (!empty($fotoNombre)) {
            $fotos[$tipo] = $baseFotoUrl . $fotoNombre;
        }
    }
    
    $stmtFotos->close();
}

// Obtener todas las categorías únicas de todos los módulos
$queryCategorias = "
    SELECT DISTINCT categoria 
    FROM (
        -- PRECIOS
        SELECT CONVERT(categoria USING utf8mb3) COLLATE utf8mb3_general_ci AS categoria
        FROM insert_precios_new
        WHERE codigo = ? AND fecha = ? AND usuario = ?
        UNION
        -- SURTIDO_CORRECTO / PRESENCIA_MINIMA
        SELECT CONVERT(categoria USING utf8mb3) COLLATE utf8mb3_general_ci
        FROM insert_presencia_new_3 
        WHERE codigo = ? AND fecha = ? AND usuario = ?
        UNION
        -- SHARE
        SELECT categoria COLLATE utf8mb3_general_ci
        FROM insert_shareshelf
        WHERE codigo = ? AND fecha = ? AND usuario = ?
        UNION
        -- PROMOCION
        SELECT CONVERT(categoria USING utf8mb3) COLLATE utf8mb3_general_ci
        FROM insert_promocion
        WHERE codigo = ? AND fecha = ? AND usuario = ?
        UNION
        -- VALIDACION_PROMOCION (NUEVO)
        SELECT CONVERT(categoria USING utf8mb3) COLLATE utf8mb3_general_ci
        FROM insert_validacion_promocion
        WHERE codigo = ? AND fecha = ? AND usuario = ?
        UNION
        -- PROMOCIONES_IA (NUEVO)
        SELECT CONVERT(categoria USING utf8mb3) COLLATE utf8mb3_general_ci
        FROM insert_promocion_ia
        WHERE codigo = ? AND fecha = ? AND usuario = ?
        UNION
        -- MATERIAL_POP (NUEVO)
        SELECT CONVERT(categoria USING utf8mb3) COLLATE utf8mb3_general_ci
        FROM insert_material_pop
        WHERE codigo = ? AND fecha = ? AND usuario = ?
        UNION
        -- HERRAMIENTAS_EXHIBICION (NUEVO)
        SELECT categoria COLLATE utf8mb3_general_ci
        FROM insert_exhibicion_colgate
        WHERE codigo = ? AND fecha = ? AND nombre = ?
        UNION
        -- ALMUERZO (NUEVO)
        /*SELECT CONVERT(categoria USING utf8mb3) COLLATE utf8mb3_general_ci
        FROM insert_almuerzo
        WHERE codigo = ? AND fecha = ? AND usuario = ?
        UNION*/
        -- ENCUESTA_APP (NUEVO)
        SELECT CONVERT(categoria USING utf8mb3) COLLATE utf8mb3_general_ci
        FROM insert_evaluacion_encuesta
        WHERE codigo = ? AND fecha = ? AND usuario = ?
        AND categoria IS NOT NULL AND categoria != ''
    ) AS todas_categorias
    ORDER BY categoria
";

$todasCategorias = [];
$categoriasPrecios = [];
$categoriasPresenciaMin = [];
$categoriasShareShelf = [];
$categoriasPromociones = [];
$categoriasValidacionPromocion = []; // NUEVO
$categoriasPromocionesIA = []; // NUEVO
$categoriasMaterialPOP = []; // NUEVO
$categoriasExhibiciones = [];
// $categoriasAlmuerzo = []; // NUEVO
$categoriasEncuesta = []; // NUEVO

// Todas las categorías únicas
if ($stmt = $mysqli->prepare($queryCategorias)) {
    $stmt->bind_param("sssssssssssssssssssssssssss", 
        $codigo, $fecha_bd, $usuario, // precios
        $codigo, $fecha_bd, $usuario, // presencia
        $codigo, $fecha_bd, $usuario, // share
        $codigo, $fecha_bd, $usuario, // promocion
        $codigo, $fecha_bd, $usuario, // validacion_promocion
        $codigo, $fecha_bd, $usuario, // promociones_ia
        $codigo, $fecha_bd, $usuario, // material_pop
        $codigo, $fecha_bd, $usuario, // exhibiciones
        // $codigo, $fecha_bd, $usuario, // almuerzo
        $codigo, $fecha_bd, $usuario  // encuesta
    );
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $todasCategorias[] = $row['categoria'];
    }
    
    $stmt->close();
}

// Obtener categorías por módulo
// 1. PRECIOS
$queryPrecios = "SELECT DISTINCT categoria FROM insert_precios_new WHERE codigo = ? AND fecha = ? AND usuario = ?";
if ($stmtEv = $mysqli->prepare($queryPrecios)) {
    $stmtEv->bind_param("sss", $codigo, $fecha_bd, $usuario);
    $stmtEv->execute();
    $resEv = $stmtEv->get_result();
    while ($row = $resEv->fetch_assoc()) {
        $categoriasPrecios[] = $row['categoria'];
    }
    $stmtEv->close();
}

// 2. SURTIDO_CORRECTO / PRESENCIA_MINIMA
$queryPresenciaMin = "SELECT DISTINCT categoria FROM insert_presencia_new_3 WHERE codigo = ? AND fecha = ? AND usuario = ?";
if ($stmtEv = $mysqli->prepare($queryPresenciaMin)) {
    $stmtEv->bind_param("sss", $codigo, $fecha_bd, $usuario);
    $stmtEv->execute();
    $resEv = $stmtEv->get_result();
    while ($row = $resEv->fetch_assoc()) {
        $categoriasPresenciaMin[] = $row['categoria'];
    }
    $stmtEv->close();
}

// 3. SHARE
$queryShareShelf = "SELECT DISTINCT categoria FROM insert_shareshelf WHERE codigo = ? AND fecha = ? AND usuario = ?";
if ($stmtEv = $mysqli->prepare($queryShareShelf)) {
    $stmtEv->bind_param("sss", $codigo, $fecha_bd, $usuario);
    $stmtEv->execute();
    $resEv = $stmtEv->get_result();
    while ($row = $resEv->fetch_assoc()) {
        $categoriasShareShelf[] = $row['categoria'];
    }
    $stmtEv->close();
}

// 4. PROMOCION
$queryPromociones = "SELECT DISTINCT categoria FROM insert_promocion WHERE codigo = ? AND fecha = ? AND usuario = ?";
if ($stmtEv = $mysqli->prepare($queryPromociones)) {
    $stmtEv->bind_param("sss", $codigo, $fecha_bd, $usuario);
    $stmtEv->execute();
    $resEv = $stmtEv->get_result();
    while ($row = $resEv->fetch_assoc()) {
        $categoriasPromociones[] = $row['categoria'];
    }
    $stmtEv->close();
}

// 5. VALIDACION_PROMOCION (NUEVO)
$queryValidacionPromocion = "SELECT DISTINCT categoria FROM insert_validacion_promocion WHERE codigo = ? AND fecha = ? AND usuario = ?";
if ($stmtEv = $mysqli->prepare($queryValidacionPromocion)) {
    $stmtEv->bind_param("sss", $codigo, $fecha_bd, $usuario);
    $stmtEv->execute();
    $resEv = $stmtEv->get_result();
    while ($row = $resEv->fetch_assoc()) {
        $categoriasValidacionPromocion[] = $row['categoria'];
    }
    $stmtEv->close();
}

// 6. PROMOCIONES_IA (NUEVO)
$queryPromocionesIA = "SELECT DISTINCT categoria FROM insert_promocion_ia WHERE codigo = ? AND fecha = ? AND usuario = ?";
if ($stmtEv = $mysqli->prepare($queryPromocionesIA)) {
    $stmtEv->bind_param("sss", $codigo, $fecha_bd, $usuario);
    $stmtEv->execute();
    $resEv = $stmtEv->get_result();
    while ($row = $resEv->fetch_assoc()) {
        $categoriasPromocionesIA[] = $row['categoria'];
    }
    $stmtEv->close();
}

// 7. MATERIAL_POP (NUEVO)
$queryMaterialPOP = "SELECT DISTINCT categoria FROM insert_material_pop WHERE codigo = ? AND fecha = ? AND usuario = ?";
if ($stmtEv = $mysqli->prepare($queryMaterialPOP)) {
    $stmtEv->bind_param("sss", $codigo, $fecha_bd, $usuario);
    $stmtEv->execute();
    $resEv = $stmtEv->get_result();
    while ($row = $resEv->fetch_assoc()) {
        $categoriasMaterialPOP[] = $row['categoria'];
    }
    $stmtEv->close();
}

// 8. EXHIBICIONES
$queryExhibiciones = "SELECT DISTINCT categoria FROM insert_exhibicion_colgate WHERE codigo = ? AND fecha = ? AND nombre = ?";
if ($stmtEx = $mysqli->prepare($queryExhibiciones)) {
    $stmtEx->bind_param("sss", $codigo, $fecha_bd, $usuario);
    $stmtEx->execute();
    $resEx = $stmtEx->get_result();
    while ($row = $resEx->fetch_assoc()) {
        $categoriasExhibiciones[] = $row['categoria'];
    }
    $stmtEx->close();
}

// 9. ALMUERZO (NUEVO)
// $queryAlmuerzo = "SELECT DISTINCT categoria FROM insert_almuerzo WHERE codigo = ? AND fecha = ? AND usuario = ?";
// if ($stmtEv = $mysqli->prepare($queryAlmuerzo)) {
//     $stmtEv->bind_param("sss", $codigo, $fecha_bd, $usuario);
//     $stmtEv->execute();
//     $resEv = $stmtEv->get_result();
//     while ($row = $resEv->fetch_assoc()) {
//         $categoriasAlmuerzo[] = $row['categoria'];
//     }
//     $stmtEv->close();
// }

// 10. ENCUESTA_APP (NUEVO)
$queryEncuesta = "SELECT DISTINCT categoria FROM insert_evaluacion_encuesta WHERE codigo = ? AND fecha = ? AND usuario = ?";
if ($stmtEv = $mysqli->prepare($queryEncuesta)) {
    $stmtEv->bind_param("sss", $codigo, $fecha_bd, $usuario);
    $stmtEv->execute();
    $resEv = $stmtEv->get_result();
    while ($row = $resEv->fetch_assoc()) {
        $categoriasEncuesta[] = $row['categoria'];
    }
    $stmtEv->close();
}

// Respuesta con estado por módulo
$respuesta = [
    'todas_categorias' => $todasCategorias,
    'estados' => [],
    'fotos' => $fotos
];

foreach ($todasCategorias as $categoria) {
    $prioridad = '';
    $queryPrioridad = "
        SELECT DISTINCT subcategoria 
        FROM repositorio_productos
        WHERE categoria = ? 
        AND activar = 'SI'
        ORDER BY subcategoria ASC 
        LIMIT 1
    ";

    if ($stmtPrioridad = $mysqli->prepare($queryPrioridad)) {
        $stmtPrioridad->bind_param("s", $categoria);
        $stmtPrioridad->execute();
        $resultPrioridad = $stmtPrioridad->get_result();
        
        if ($rowPrioridad = $resultPrioridad->fetch_assoc()) {
            $prioridad = $rowPrioridad['subcategoria'];
        }
        
        $stmtPrioridad->close();
    }

    $respuesta['estados'][] = [
        'categoria' => $categoria,
        'tipo_cat' => $prioridad,
        // Módulos originales
        'precio' => in_array($categoria, $categoriasPrecios),
        'presencia' => in_array($categoria, $categoriasPresenciaMin),
        'shareshelf' => in_array($categoria, $categoriasShareShelf),
        'promocion' => in_array($categoria, $categoriasPromociones),
        'exhibicion' => in_array($categoria, $categoriasExhibiciones),
        // NUEVOS MÓDULOS
        'validacion_promocion' => in_array($categoria, $categoriasValidacionPromocion),
        'promociones_ia' => in_array($categoria, $categoriasPromocionesIA),
        'material_pop' => in_array($categoria, $categoriasMaterialPOP),
        // 'almuerzo' => in_array($categoria, $categoriasAlmuerzo),
        'encuesta' => in_array($categoria, $categoriasEncuesta)
    ];
}

echo json_encode($respuesta);

?>