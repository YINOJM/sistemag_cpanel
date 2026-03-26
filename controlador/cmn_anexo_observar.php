<?php
// controlador/cmn_anexo_observar.php
session_start();
require_once __DIR__ . '/../modelo/conexion.php';

header('Content-Type: application/json');

if (empty($_SESSION['id']) || !userCan('cmn')) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$id = (int)trim($_POST['id'] ?? 0);
$fase = isset($_POST['fase']) ? (int)$_POST['fase'] : 1;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de Anexo no válido.']);
    exit;
}

$tabla = "cmn_anexos_fase1";
if ($fase === 2) $tabla = "cmn_anexos_fase2";
if ($fase === 3) $tabla = "cmn_anexos_fase3";

// Solo se puede observar si está en estado RECEPCIONADO (0) — no si ya está VALIDADO (1)
$stmt = $conexion->prepare("SELECT id, estado_revision FROM $tabla WHERE id = ? LIMIT 1");
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

// Marcar como OBSERVADO (2)
$stmt2 = $conexion->prepare("UPDATE $tabla SET estado_revision = 2, fecha_revision = NOW() WHERE id = ?");
$stmt2->bind_param("i", $id);
$ok = $stmt2->execute();
$stmt2->close();

if ($ok) {
    echo json_encode(['success' => true, 'message' => 'Documento marcado como OBSERVADO. El logístico podrá re-subir su Anexo corregido.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado: ' . $conexion->error]);
}
?>
