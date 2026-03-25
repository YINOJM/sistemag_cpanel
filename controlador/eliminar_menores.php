<?php
require_once __DIR__ . '/../modelo/conexion.php';

// Verificación de seguridad (Super Admin, Admin o permiso específico)
if (empty($_SESSION['id']) || (strcasecmp($_SESSION['rol'], 'Super Administrador') !== 0 && strcasecmp($_SESSION['rol'], 'Administrador') !== 0 && !isset($_SESSION['permisos_rutas']['seguimiento']))) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
    $id = intval($_POST['id']);

    try {
        $sql = "DELETE FROM seguimiento_menores_8uit WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Registro eliminado correctamente.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar: ' . $stmt->error]);
        }
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Solicitud incompleta.']);
}
