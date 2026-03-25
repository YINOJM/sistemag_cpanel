<?php
// controlador/cmn_anexo_validar.php
session_start();
require_once __DIR__ . '/../modelo/conexion.php';

header('Content-Type: application/json');

if (empty($_SESSION['id']) || $_SESSION['rol'] !== 'Super Administrador') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int) $_POST['id'];

    $sql = "UPDATE cmn_anexos_fase1 SET estado_revision = 1 WHERE id = $id";
    
    if ($conexion->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Documento validado exitosamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar la base de datos']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Solicitud inválida']);
}
?>
