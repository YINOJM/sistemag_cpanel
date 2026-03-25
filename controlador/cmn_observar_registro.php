<?php
// controlador/cmn_observar_registro.php
require_once __DIR__ . '/../modelo/conexion.php';

// Verificación de seguridad
if (empty($_SESSION['id']) || $_SESSION['rol'] !== 'Super Administrador') {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado']);
    exit();
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id > 0) {
    // Actualizar el estado a 1 (Observado)
    $stmt = $conexion->prepare("UPDATE cmn_responsables SET estado = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Registro marcado como OBSERVADO']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el estado']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
}
?>
