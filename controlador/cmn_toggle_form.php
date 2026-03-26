<?php
require_once __DIR__ . "/../modelo/conexion.php";

if (empty($_SESSION['id']) || $_SESSION['rol'] !== 'Super Administrador') {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit();
}

$estado = isset($_POST['estado']) ? $_POST['estado'] : '0';
$estado = ($estado === '1') ? '1' : '0';

$fase = isset($_POST['fase']) ? (int)$_POST['fase'] : 1;

// Mapear fase a la clave correcta en cmn_config
$claves = [
    1 => 'mantenimiento',
    2 => 'mantenimiento_fase2',
    3 => 'mantenimiento_fase3',
];

$clave = $claves[$fase] ?? 'mantenimiento';

$stmt = $conexion->prepare("UPDATE cmn_config SET valor = ? WHERE clave = ?");
$stmt->bind_param("ss", $estado, $clave);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => "Estado de la fase $fase actualizado correctamente"]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error al actualizar base de datos: ' . $stmt->error]);
}
