<?php
require_once '../modelo/conexion.php';
header('Content-Type: application/json');

if (empty($_SESSION['id']) || (strcasecmp($_SESSION['rol'], 'Super Administrador') !== 0 && strcasecmp($_SESSION['rol'], 'Administrador') !== 0 && !isset($_SESSION['permisos_rutas']['seguimiento']))) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado.']);
    exit();
}

$segmentacion_id  = isset($_POST['segmentacion_id']) ? (int)$_POST['segmentacion_id'] : 0;
$mes_programado   = $_POST['mes_programado'] ?? '';
$estado_proceso   = $_POST['estado_proceso'] ?? 'Actos preparatorios';
$valor_convocado  = !empty($_POST['valor_convocado']) ? (float)$_POST['valor_convocado'] : 0.00;
$valor_adjudicado = !empty($_POST['valor_adjudicado']) ? (float)$_POST['valor_adjudicado'] : 0.00;

$imp_comprometido = !empty($_POST['imp_comprometido']) ? (float)$_POST['imp_comprometido'] : 0.00;
$imp_devengado    = !empty($_POST['imp_devengado']) ? (float)$_POST['imp_devengado'] : 0.00;
$imp_girado       = !empty($_POST['imp_girado']) ? (float)$_POST['imp_girado'] : 0.00;

$certificado      = $_POST['certificado'] ?? '';
$observaciones    = $_POST['observaciones'] ?? '';
$usuario_id       = $_SESSION['id'];

// Master fields for manual records
$origen_registro    = $_POST['origen_registro'] ?? '';
$ref_pac            = $_POST['ref_pac'] ?? '';
$cmn                = $_POST['cmn'] ?? '';
$objeto_contratacion = $_POST['objeto_contratacion'] ?? '';
$tipo_proceso_id_post = (int)($_POST['tipo_proceso_id'] ?? 0);
$descripcion        = $_POST['descripcion'] ?? '';
$cuantia            = (float)($_POST['cuantia'] ?? 0);

if ($segmentacion_id === 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID de proceso inválido.']);
    exit();
}

try {
    $conexion->begin_transaction();

    // 1. If manual record, update master data in segmentacion
    if ($origen_registro === 'Seguimiento PAC') {
        $stmt_s = $conexion->prepare("
            UPDATE segmentacion 
            SET ref_pac = ?, cmn = ?, objeto_contratacion = ?, tipo_proceso_id = ?, descripcion = ?, cuantia = ? 
            WHERE id = ?
        ");
        $stmt_s->bind_param("sssisdi", $ref_pac, $cmn, $objeto_contratacion, $tipo_proceso_id_post, $descripcion, $cuantia, $segmentacion_id);
        $stmt_s->execute();
    }
    // Check if record exists
    $check_stmt = $conexion->prepare("SELECT id FROM seguimiento_pac WHERE segmentacion_id = ?");
    $check_stmt->bind_param("i", $segmentacion_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    if ($result->num_rows > 0) {
        $update_stmt = $conexion->prepare("
            UPDATE seguimiento_pac 
            SET mes_programado = ?, estado_proceso = ?, valor_convocado = ?, monto_adjudicado = ?, imp_comprometido = ?, imp_devengado = ?, imp_girado = ?, certificado = ?, observaciones = ? 
            WHERE segmentacion_id = ?
        ");
        $update_stmt->bind_param("ssdddddssi", $mes_programado, $estado_proceso, $valor_convocado, $valor_adjudicado, $imp_comprometido, $imp_devengado, $imp_girado, $certificado, $observaciones, $segmentacion_id);
        $update_stmt->execute();
    } else {
        $insert_stmt = $conexion->prepare("
            INSERT INTO seguimiento_pac (segmentacion_id, mes_programado, estado_proceso, valor_convocado, monto_adjudicado, imp_comprometido, imp_devengado, imp_girado, certificado, observaciones, usuario_registro_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insert_stmt->bind_param("issdddddssi", $segmentacion_id, $mes_programado, $estado_proceso, $valor_convocado, $valor_adjudicado, $imp_comprometido, $imp_devengado, $imp_girado, $certificado, $observaciones, $usuario_id);
        $insert_stmt->execute();
    }

    $conexion->commit();
    echo json_encode(['status' => 'success', 'message' => 'Proceso actualizado correctamente.']);

} catch (Exception $e) {
    if($conexion->connect_errno == 0 && $conexion->ping()) {
        $conexion->rollback();
    }
    echo json_encode(['status' => 'error', 'message' => 'Error de servidor: ' . $e->getMessage()]);
}
