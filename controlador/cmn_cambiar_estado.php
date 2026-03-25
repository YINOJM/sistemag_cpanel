<?php
// controlador/cmn_cambiar_estado.php
require_once __DIR__ . '/../modelo/conexion.php';

header('Content-Type: application/json');

// Verificación de seguridad
if (empty($_SESSION['id']) || (strcasecmp($_SESSION['rol'], 'Super Administrador') !== 0 && strcasecmp($_SESSION['rol'], 'Administrador') !== 0)) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado']);
    exit();
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$estado = isset($_POST['estado']) ? (int)$_POST['estado'] : 0;

if ($id > 0) {
    // 0 = Registrado/Pendiente, 1 = Observado, 2 = Validado
    $stmt = $conexion->prepare("UPDATE cmn_responsables SET estado = ? WHERE id = ?");
    $stmt->bind_param("ii", $estado, $id);
    
    if ($stmt->execute()) {
        $msgs = [
            0 => 'Registro restablecido a pendiente',
            1 => 'Registro marcado como OBSERVADO',
            2 => 'Registro VALIDADO correctamente'
        ];
        echo json_encode(['status' => 'success', 'message' => $msgs[$estado] ?? 'Estado actualizado']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el estado: ' . $conexion->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Datos inválidos']);
}
?>
