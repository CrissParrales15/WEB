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
$re = isset($_REQUEST['re']) ? $_REQUEST['re'] : '';
$rol = isset($_REQUEST['rol']) ? $_REQUEST['rol'] : 'USUARIO';

header('Content-Type: application/json');

if (empty($codigo) || empty($fecha)) {
    echo json_encode(['error' => 'Código y fecha son requeridos']);
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

// ========== 1. OBTENER MÓDULOS ACTIVOS POR RE Y ROL ==========
$modulosActivos = [];
$queryModulos = "SELECT modulo, nombre_modulo FROM repositorio_modulos_por_re WHERE re = ? AND rol = ? AND modulo IS NOT NULL
                 AND modulo NOT IN ('CONVENIOS_PENDIENTES', 'GESTION_VISITA', 'ALMUERZO')
  AND modulo != '' 
  AND nombre_modulo IS NOT NULL 
  AND nombre_modulo != '' ORDER BY id";

if ($stmtModulos = $mysqli->prepare($queryModulos)) {
    $stmtModulos->bind_param("ss", $re, $rol);
    $stmtModulos->execute();
    $resultModulos = $stmtModulos->get_result();
    
    while ($row = $resultModulos->fetch_assoc()) {
        $modulosActivos[$row['modulo']] = $row['nombre_modulo'];
    }
    
    $stmtModulos->close();
}

// Si no hay módulos activos, retornar vacío
if (empty($modulosActivos)) {
    echo json_encode([
        'error' => 'No hay módulos configurados para este RE',
        'modulos_activos' => [],
        'estados' => [],
        'fotos' => ['ENTRADA' => '', 'SALIDA' => '']
    ]);
    exit;
}

// ========== 2. OBTENER FOTOS ==========
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

// ========== 3. CONFIGURACIÓN DE MÓDULOS ==========
$moduloConfig = [
    'PRECIOS' => [
        'tabla' => 'insert_precios_new',
        'campo_usuario' => 'usuario',
        'campo_categoria' => 'categoria'
    ],
    'SURTIDO_CORRECTO' => [
        'tabla' => 'insert_presencia_new_3',
        'campo_usuario' => 'usuario',
        'campo_categoria' => 'categoria'
    ],
    'PRESENCIA_MINIMA' => [
        'tabla' => 'insert_presenciamin_new_2',
        'campo_usuario' => 'usuario',
        'campo_categoria' => 'categoria'
    ],
    'PROMOCION' => [
        'tabla' => 'insert_promocion',
        'campo_usuario' => 'usuario',
        'campo_categoria' => 'categoria'
    ],
    'VALIDACION_PROMOCION' => [
        'tabla' => 'insert_validacion_promocion',
        'campo_usuario' => 'usuario',
        'campo_categoria' => 'categoria'
    ],
    'PROMOCIONES_IA' => [
        'tabla' => 'insert_promocion_ia',
        'campo_usuario' => 'usuario',
        'campo_categoria' => 'categoria'
    ],
    'MATERIAL_POP' => [
        'tabla' => 'insert_material_pop',
        'campo_usuario' => 'usuario',
        'campo_categoria' => 'categoria'
    ],
    'SHARE' => [
        'tabla' => 'insert_shareshelf',
        'campo_usuario' => 'usuario',
        'campo_categoria' => 'categoria'
    ],
    'HERRAMIENTAS_EXHIBICION' => [
        'tabla' => 'insert_exhibicion_colgate',
        'campo_usuario' => 'nombre',
        'campo_categoria' => 'categoria'
    ],
    'ENCUESTA_APP' => [
        'tabla' => 'insert_evaluacion_encuesta',
        'campo_usuario' => 'usuario',
        'campo_categoria' => 'categoria'
    ]
];

// ========== 4. CONSTRUIR QUERY DINÁMICA DE CATEGORÍAS ==========
$unionParts = [];
$params = [];
$types = "";

foreach ($modulosActivos as $modulo => $nombre) {
    if (isset($moduloConfig[$modulo])) {
        $config = $moduloConfig[$modulo];
        $tabla = $config['tabla'];
        $campoUsuario = $config['campo_usuario'];
        $campoCategoria = $config['campo_categoria'];
        
        // CONVERTIR categoria a utf8mb4_general_ci para evitar problemas de collation
        $unionParts[] = "
            SELECT DISTINCT CONVERT($campoCategoria USING utf8mb4) COLLATE utf8mb4_general_ci AS categoria
            FROM $tabla
            WHERE codigo = ? AND fecha = ? AND $campoUsuario = ?
            AND $campoCategoria IS NOT NULL AND $campoCategoria != ''
        ";
        
        $params[] = $codigo;
        $params[] = $fecha_bd;
        $params[] = $usuario;
        $types .= "sss";
    }
}

$queryCategorias = "";
if (!empty($unionParts)) {
    $queryCategorias = "SELECT DISTINCT categoria FROM (" . implode(" UNION ", $unionParts) . ") AS todas_categorias ORDER BY categoria";
}

// ========== 5. OBTENER TODAS LAS CATEGORÍAS ==========
$todasCategorias = [];

if (!empty($queryCategorias)) {
    if ($stmt = $mysqli->prepare($queryCategorias)) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $todasCategorias[] = $row['categoria'];
        }
        
        $stmt->close();
    }
}
// ========== 6. OBTENER DATOS POR MÓDULO ==========
$datosModulos = [];
foreach ($modulosActivos as $modulo => $nombre) {
    $datosModulos[$modulo] = [];
}

foreach ($modulosActivos as $modulo => $nombre) {
    if (isset($moduloConfig[$modulo])) {
        $config = $moduloConfig[$modulo];
        $tabla = $config['tabla'];
        $campoUsuario = $config['campo_usuario'];
        $campoCategoria = $config['campo_categoria'];
        
        $query = "SELECT DISTINCT $campoCategoria AS categoria FROM $tabla WHERE codigo = ? AND fecha = ? AND $campoUsuario = ? AND $campoCategoria IS NOT NULL AND $campoCategoria != ''";
        
        if ($stmt = $mysqli->prepare($query)) {
            $stmt->bind_param("sss", $codigo, $fecha_bd, $usuario);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $datosModulos[$modulo][] = $row['categoria'];
            }
            
            $stmt->close();
        }
    }
}

// ========== 6.1 OBTENER SUBCATEGORÍAS DE LOS MÓDULOS RELEVADOS ==========
$subcategoriasPorCategoria = [];

// Construir query para obtener subcategorías de todas las tablas de módulos activos
$unionSubcategorias = [];
$paramsSub = [];
$typesSub = "";

foreach ($modulosActivos as $modulo => $nombre) {
    if (isset($moduloConfig[$modulo])) {
        $config = $moduloConfig[$modulo];
        $tabla = $config['tabla'];
        $campoUsuario = $config['campo_usuario'];
        
        // Verificar si la tabla tiene campo 'subcategoria'
        $checkColumn = "SHOW COLUMNS FROM $tabla LIKE 'subcategoria'";
        $hasSubcategoria = false;
        if ($checkStmt = $mysqli->prepare($checkColumn)) {
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            if ($checkResult->num_rows > 0) {
                $hasSubcategoria = true;
            }
            $checkStmt->close();
        }
        
        if ($hasSubcategoria) {
            // CONVERTIR categoría y subcategoria a utf8mb4_general_ci para evitar problemas de collation
            $unionSubcategorias[] = "
                SELECT DISTINCT 
                    CONVERT(categoria USING utf8mb4) COLLATE utf8mb4_general_ci AS categoria,
                    CONVERT(subcategoria USING utf8mb4) COLLATE utf8mb4_general_ci AS subcategoria
                FROM $tabla 
                WHERE codigo = ? AND fecha = ? AND $campoUsuario = ?
                AND categoria IS NOT NULL AND categoria != ''
                AND subcategoria IS NOT NULL AND subcategoria != ''
            ";
            $paramsSub[] = $codigo;
            $paramsSub[] = $fecha_bd;
            $paramsSub[] = $usuario;
            $typesSub .= "sss";
        }
    }
}

$querySubcategorias = "";
if (!empty($unionSubcategorias)) {
    $querySubcategorias = "SELECT DISTINCT categoria, subcategoria FROM (" . implode(" UNION ", $unionSubcategorias) . ") AS todas_subcategorias ORDER BY categoria, subcategoria";
}

// Log para depuración
error_log("=== QUERY SUBCATEGORIAS ===");
error_log($querySubcategorias);

if (!empty($querySubcategorias)) {
    if ($stmtSub = $mysqli->prepare($querySubcategorias)) {
        if (!empty($paramsSub)) {
            $stmtSub->bind_param($typesSub, ...$paramsSub);
        }
        $stmtSub->execute();
        $resultSub = $stmtSub->get_result();
        
        while ($row = $resultSub->fetch_assoc()) {
            $categoria = $row['categoria'];
            $subcategoria = $row['subcategoria'];
            
            if (!isset($subcategoriasPorCategoria[$categoria])) {
                $subcategoriasPorCategoria[$categoria] = [];
            }
            // Evitar duplicados
            if (!in_array($subcategoria, $subcategoriasPorCategoria[$categoria])) {
                $subcategoriasPorCategoria[$categoria][] = $subcategoria;
            }
        }
        
        $stmtSub->close();
    }
}

// Log para depuración
error_log("=== SUBCATEGORIAS ENCONTRADAS ===");
error_log(print_r($subcategoriasPorCategoria, true));

// ========== 7. CONSTRUIR RESPUESTA ==========
$respuesta = [
    'todas_categorias' => $todasCategorias,
    'modulos_activos' => $modulosActivos,
    'subcategorias' => $subcategoriasPorCategoria,
    'estados' => [],
    'fotos' => $fotos
];

foreach ($todasCategorias as $categoria) {
    $estado = [
        'categoria' => $categoria,
        'subcategoria' => 'N/A',  // Valor por defecto (compatibilidad)
        'subcategorias' => [],    // Array de subcategorías
        'tiene_subcategorias' => false
    ];
    
    // Verificar si la categoría tiene subcategorías
    if (isset($subcategoriasPorCategoria[$categoria]) && !empty($subcategoriasPorCategoria[$categoria])) {
        $estado['subcategorias'] = $subcategoriasPorCategoria[$categoria];
        $estado['tiene_subcategorias'] = true;
        // Para compatibilidad, también guardar como string (por si acaso)
        $estado['subcategoria'] = implode(', ', $subcategoriasPorCategoria[$categoria]);
    }
    
    // Verificar si la categoría está en cada módulo
    foreach ($modulosActivos as $modulo => $nombre) {
        $campoRespuesta = strtolower($modulo);
        $estado[$campoRespuesta] = in_array($categoria, $datosModulos[$modulo]);
    }
    
    $respuesta['estados'][] = $estado;
}

// Log para depuración
error_log("=== RESPUESTA FINAL ===");
error_log("Categorías: " . print_r($todasCategorias, true));
error_log("Estados: " . print_r($respuesta['estados'], true));

echo json_encode($respuesta);
?>