<?php
require_once __DIR__ . "/../modelo/conexion.php";

if (empty($_SESSION['id']) || $_SESSION['rol'] !== 'Super Administrador') {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit();
}

$estado = isset($_POST['estado']) ? $_POST['estado'] : '0';

// Asegurar que solo sea 0 o 1
$estado = ($estado === '1') ? '1' : '0';

$stmt = $conexion->prepare("UPDATE cmn_config SET valor = ? WHERE clave = 'mantenimiento'");
$stmt->bind_param("s", $estado);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Estado del formulario actualizado correctamente']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error al actualizar base de datos']);
}
