<?php
// eliminar_todo_menores.php
declare(strict_types=1);

require_once __DIR__ . '/../modelo/conexion.php';

session_start();

// Seguridad: Solo Super Administrador
if (empty($_SESSION['id']) || $_SESSION['rol'] !== 'Super Administrador') {
    echo json_encode(['success' => false, 'message' => 'No tienes permisos para realizar esta acción.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

try {
    // Definir el año actual para limpiar solo los registros del año en curso
    // O limpiar todo si el usuario así lo requiere. En este caso limpiaremos todo el seguimiento de menores.
    $sql = "DELETE FROM seguimiento_menores_8uit";
    
    if ($conexion->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Todos los registros han sido eliminados correctamente.']);
    } else {
        throw new Exception($conexion->error);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al eliminar registros: ' . $e->getMessage()]);
}
