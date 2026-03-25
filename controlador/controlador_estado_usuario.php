<?php
require_once "../modelo/conexion.php";

// Verificar permisos de administrador
if (!isset($_SESSION['rol']) || ($_SESSION['rol'] !== 'Administrador' && $_SESSION['rol'] !== 'Super Administrador')) {
    echo json_encode(['status' => 'error', 'msg' => 'Acceso denegado']);
    exit;
}

if (!empty($_GET['id'])) {
    $id = $_GET['id'];
    
    // Evitar que el Super Administrador se desactive a sí mismo o sea desactivado
    $check = $conexion->query("SELECT rol FROM usuario WHERE id_usuario=$id");
    if ($check && $check->fetch_object()->rol === 'Super Administrador') {
        echo json_encode(['status' => 'error', 'msg' => 'No se puede desactivar al Super Administrador']);
        exit;
    }

    // Toggle estado
    $sql = "UPDATE usuario SET estado = IF(estado='Activo', 'Inactivo', 'Activo') WHERE id_usuario=?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // Obtener el nuevo estado para devolverlo
        $res = $conexion->query("SELECT estado FROM usuario WHERE id_usuario=$id");
        $nuevo_estado = $res->fetch_object()->estado;
        
        // --- AUDITORIA ---
        $idEjecutor = $_SESSION['id'] ?? 0;
        $accion = "ESTADO";
        $detalle = "Cambio de estado a: " . $nuevo_estado;
        $stmtLog = $conexion->prepare("INSERT INTO log_actividad (id_ejecutor, id_afectado, accion, detalle) VALUES (?, ?, ?, ?)");
        $stmtLog->bind_param("iiss", $idEjecutor, $id, $accion, $detalle);
        $stmtLog->execute();
        // -----------------

        echo json_encode(['status' => 'success', 'msg' => 'Estado actualizado correctamente', 'nuevo_estado' => $nuevo_estado]);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Error al actualizar base de datos']);
    }
} else {
    echo json_encode(['status' => 'error', 'msg' => 'ID no proporcionado']);
}
?>
