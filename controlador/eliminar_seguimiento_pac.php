<?php
require_once '../modelo/conexion.php';
header('Content-Type: application/json');

if (empty($_SESSION['id']) || (strcasecmp($_SESSION['rol'], 'Super Administrador') !== 0 && strcasecmp($_SESSION['rol'], 'Administrador') !== 0 && !isset($_SESSION['permisos_rutas']['seguimiento']))) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado.']);
    exit();
}

$segmentacion_id = (int)($_POST['segmentacion_id'] ?? 0);
$origen          = $_POST['origen'] ?? '';

if ($segmentacion_id === 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
    exit();
}

try {
    $conexion->begin_transaction();

    if ($origen === 'Seguimiento PAC') {
        // Manual Entry: Delete everything
        $stmt1 = $conexion->prepare("DELETE FROM seguimiento_pac WHERE segmentacion_id = ?");
        $stmt1->bind_param("i", $segmentacion_id);
        $stmt1->execute();

        $stmt2 = $conexion->prepare("DELETE FROM segmentacion WHERE id = ?");
        $stmt2->bind_param("i", $segmentacion_id);
        $stmt2->execute();
        
        $msg = "El proceso y su seguimiento han sido eliminados por completo.";
    } else {
        // Segmented Entry: Just hide it
        // Check if seguimiento_pac record exists
        $check = $conexion->prepare("SELECT id FROM seguimiento_pac WHERE segmentacion_id = ?");
        $check->bind_param("i", $segmentacion_id);
        $check->execute();
        $res = $check->get_result();

        if ($res->num_rows > 0) {
            $stmt = $conexion->prepare("UPDATE seguimiento_pac SET oculto = 1 WHERE segmentacion_id = ?");
            $stmt->bind_param("i", $segmentacion_id);
            $stmt->execute();
        } else {
            // If doesn't exist, insert as hidden
            $stmt = $conexion->prepare("INSERT INTO seguimiento_pac (segmentacion_id, oculto) VALUES (?, 1)");
            $stmt->bind_param("i", $segmentacion_id);
            $stmt->execute();
        }
        $msg = "El proceso ha sido quitado de la lista de seguimiento (permanece en segmentación).";
    }

    $conexion->commit();
    echo json_encode(['status' => 'success', 'message' => $msg]);

} catch (Exception $e) {
    $conexion->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
