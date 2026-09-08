<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../core/DataSource.php';
$db = new \Phppot\DataSource();

try {
    // Recibir datos vía POST
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if (empty($username) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Por favor, completa todos los campos.']);
        exit;
    }

    // Buscar en la base de datos (Status 1 = Activo)
    $sql = "SELECT id, user, mercaderista, color FROM repositorio_usuario WHERE UPPER(user) = UPPER(?) AND pass = ? AND status = 1 LIMIT 1";
    $result = $db->select($sql, 'ss', [$username, $password]);

    if (!empty($result)) {
        $usuario = $result[0];
        
       // --- LÓGICA DE ROLES MEJORADA ---
        $rol = 'CALIFICADOR'; // Por defecto, todos los de tu equipo pueden calificar
        $nombreUser = strtoupper($usuario['user']);

        // Si el nombre de usuario CONTIENE la palabra 'CLIENTE', le limitamos el acceso
        if (strpos($nombreUser, 'CLIENTE') !== false) {
            $rol = 'CLIENTE';
        }

        // Guardar datos en la sesión
        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['username'] = $usuario['user'];
        $_SESSION['nombre_completo'] = $usuario['mercaderista'];
        $_SESSION['user_color'] = $usuario['color'];
        $_SESSION['user_rol'] = $rol;

        echo json_encode([
            'status' => 'success', 
            'message' => 'Bienvenido', 
            'rol' => $rol
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Usuario o contraseña incorrectos.']);
    }

} catch (\Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error de servidor: ' . $e->getMessage()]);
}
?>