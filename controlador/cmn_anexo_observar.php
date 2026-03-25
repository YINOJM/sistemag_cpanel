<?php
// controlador/cmn_anexo_observar.php
// Marca el Anexo N°01 como OBSERVADO (estado_revision = 2)
// Esto habilita al logístico para volver a subir su documento corregido.
session_start();
require_once __DIR__ . '/../modelo/conexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$id = (int)trim($_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de Anexo no válido.']);
    exit;
}

// Solo se puede observar si está en estado RECEPCIONADO (0) — no si ya está VALIDADO (1)
$stmt = $conexion->prepare("SELECT id, estado_revision FROM cmn_anexos_fase1 WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$anexo = $res->fetch_assoc();
$stmt->close();

if (!$anexo) {
    echo json_encode(['success' => false, 'message' => 'Registro no encontrado.']);
    exit;
}

if ((int)$anexo['estado_revision'] === 1) {
    echo json_encode(['success' => false, 'message' => 'No se puede observar un documento ya VALIDADO.']);
    exit;
}

// Marcar como OBSERVADO (estado_revision = 2) y registrar fecha de observación
$stmt2 = $conexion->prepare("UPDATE cmn_anexos_fase1 SET estado_revision = 2, fecha_revision = NOW() WHERE id = ?");
$stmt2->bind_param("i", $id);
$ok = $stmt2->execute();
$stmt2->close();

if ($ok) {
    echo json_encode(['success' => true, 'message' => 'Documento marcado como OBSERVADO. El logístico podrá re-subir su Anexo corregido.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado: ' . $conexion->error]);
}
?>
