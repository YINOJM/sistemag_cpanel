<?php
require_once '../modelo/conexion.php';
header('Content-Type: application/json');

if (empty($_SESSION['id']) || (strcasecmp($_SESSION['rol'], 'Super Administrador') !== 0 && strcasecmp($_SESSION['rol'], 'Administrador') !== 0 && !isset($_SESSION['permisos_rutas']['seguimiento']))) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado.']);
    exit();
}

$ref_pac            = $_POST['ref_pac'] ?? '';
$cmn                = $_POST['cmn'] ?? '';
$objeto_contratacion = $_POST['objeto_contratacion'] ?? 'SERVICIOS';
$tipo_proceso_id    = (int)($_POST['tipo_proceso_id'] ?? 0);
$descripcion        = $_POST['descripcion'] ?? '';
$cuantia            = (float)($_POST['cuantia'] ?? 0);
$programado         = (int)($_POST['programado'] ?? 1);
$mes_programado     = $_POST['mes_programado'] ?? '';
$estado_proceso     = $_POST['estado_proceso'] ?? 'Actos preparatorios';
$anio               = (int)date('Y');
$usuario_id         = $_SESSION['id'];

if (empty($descripcion)) {
    echo json_encode(['status' => 'error', 'message' => 'El objeto de la contratación (Descripción) es obligatorio.']);
    exit();
}

try {
    $conexion->begin_transaction();

    // 1. Insert into segmentacion
    $stmt1 = $conexion->prepare("
        INSERT INTO segmentacion (ref_pac, cmn, objeto_contratacion, tipo_proceso_id, descripcion, cuantia, programado, anio, origen_registro) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Seguimiento PAC')
    ");
    $stmt1->bind_param("sssisdii", $ref_pac, $cmn, $objeto_contratacion, $tipo_proceso_id, $descripcion, $cuantia, $programado, $anio);
    
    if (!$stmt1->execute()) {
        throw new Exception("Error al insertar en segmentación: " . $stmt1->error);
    }
    
    $segmentacion_id = $stmt1->insert_id;

    // 2. Insert into seguimiento_pac
    $stmt2 = $conexion->prepare("
        INSERT INTO seguimiento_pac (segmentacion_id, mes_programado, estado_proceso, usuario_registro_id) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt2->bind_param("issi", $segmentacion_id, $mes_programado, $estado_proceso, $usuario_id);
    
    if (!$stmt2->execute()) {
        throw new Exception("Error al insertar en seguimiento_pac: " . $stmt2->error);
    }

    $conexion->commit();
    echo json_encode(['status' => 'success', 'message' => 'Proceso registrado correctamente.']);

} catch (Exception $e) {
    $conexion->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
