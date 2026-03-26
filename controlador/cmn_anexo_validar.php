<?php
// controlador/cmn_anexo_validar.php
session_start();
require_once __DIR__ . '/../modelo/conexion.php';

header('Content-Type: application/json');

if (empty($_SESSION['id']) || !userCan('cmn')) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int) $_POST['id'];
    $fase = isset($_POST['fase']) ? (int)$_POST['fase'] : 1;
    
    $tabla = "cmn_anexos_fase1";
    if ($fase === 2) $tabla = "cmn_anexos_fase2";
    if ($fase === 3) $tabla = "cmn_anexos_fase3";

    $sql = "UPDATE $tabla SET estado_revision = 1, fecha_revision = NOW() WHERE id = $id";
    
    if ($conexion->query($sql)) {
        echo json_encode(['success' => true, 'message' => "Documento de Fase $fase validado exitosamente"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar la base de datos']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Solicitud inválida']);
}
?>
