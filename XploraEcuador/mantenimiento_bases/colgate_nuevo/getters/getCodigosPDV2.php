<?php
include_once($_SERVER['DOCUMENT_ROOT']."/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/includes/db_connect.php");
include_once($_SERVER['DOCUMENT_ROOT']."/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/includes/functions.php");
include_once($_SERVER['DOCUMENT_ROOT']."/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/includes/config.php");

sec_session_start();

$search = isset($_GET['q']) ? $_GET['q'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 1000;
$offset = ($page - 1) * $limit;

$query = "SELECT id, pos_id, pos_name FROM repositorio_locales_dtt2 
          WHERE activar='SI' AND (pos_id LIKE ? OR pos_name LIKE ?) 
          ORDER BY pos_id LIMIT ? OFFSET ?";

$searchTerm = "%$search%";
$items = [];

if ($sql = $mysqli->prepare($query)) {
    $sql->bind_param("ssii", $searchTerm, $searchTerm, $limit, $offset);
    $sql->execute();
    $result = $sql->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $items[] = [
            'id' => $row['id'],
            'text' => $row['pos_id'] . ' - ' . $row['pos_name']
        ];
    }
    $sql->close();
}

// Contar total
$countQuery = "SELECT COUNT(*) as total FROM repositorio_locales_dtt2 
               WHERE activar='SI' AND (pos_id LIKE ? OR pos_name LIKE ?)";
$countStmt = $mysqli->prepare($countQuery);
$countStmt->bind_param("ss", $searchTerm, $searchTerm);
$countStmt->execute();
$countResult = $countStmt->get_result();
$total = $countResult->fetch_assoc()['total'];
$countStmt->close();

// IMPORTANTE: Forzar que la respuesta sea JSON
header('Content-Type: application/json');
echo json_encode([
    'results' => $items,
    'pagination' => [
        'more' => ($page * $limit) < $total
    ]
]);
?>