<?php
require_once '../modelo/conexion.php';

// Verificación de seguridad (Super Admin, Admin o permiso específico)
if (empty($_SESSION['id']) || (strcasecmp($_SESSION['rol'], 'Super Administrador') !== 0 && strcasecmp($_SESSION['rol'], 'Administrador') !== 0 && !isset($_SESSION['permisos_rutas']['seguimiento']))) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_contratacion = $_POST['tipo_contratacion'] ?? '';
    $area_usuaria = $_POST['area_usuaria'] ?? '';
    $fecha_requerimiento = $_POST['fecha_requerimiento'] ?: null;
    $objeto_contratacion = $_POST['objeto_contratacion'] ?? '';
    $fecha_orden = $_POST['fecha_orden'] ?: null;
    $plazo_dias = !empty($_POST['plazo_dias']) ? intval($_POST['plazo_dias']) : 0;
    $fecha_final = $_POST['fecha_final'] ?: null;
    $estado_id = !empty($_POST['estado']) ? intval($_POST['estado']) : null;
    $imp_comprometido = !empty($_POST['imp_comprometido']) ? floatval($_POST['imp_comprometido']) : 0.00;
    $imp_devengado = !empty($_POST['imp_devengado']) ? floatval($_POST['imp_devengado']) : 0.00;
    $imp_girado = !empty($_POST['imp_girado']) ? floatval($_POST['imp_girado']) : 0.00;
    $observaciones = $_POST['observaciones'] ?? '';

    // Mapeo tipo_contratacion a tipo_orden (BIENES -> OC, SERVICIOS -> OS)
    $tipo_orden = ($tipo_contratacion === 'BIENES') ? 'OC' : 'OS';
    
    // Año actual para el registro
    $anio = date('Y');

    try {
        if (!empty($_POST['id_registro'])) {
            $id = intval($_POST['id_registro']);
            $sql = "UPDATE seguimiento_menores_8uit SET 
                        tipo_orden = ?, unidad_solicitante = ?, fecha_requerimiento = ?, 
                        descripcion_servicio_bien = ?, fecha_emision = ?, plazo_ejecucion_dias = ?, 
                        fecha_vencimiento = ?, estado_id = ?, monto_comprometido = ?, 
                        monto_devengado = ?, monto_girado = ?, observaciones = ?
                    WHERE id = ?";
            
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("sssssisidddsi", 
                $tipo_orden, $area_usuaria, $fecha_requerimiento,
                $objeto_contratacion, $fecha_orden, $plazo_dias,
                $fecha_final, $estado_id, $imp_comprometido,
                $imp_devengado, $imp_girado, $observaciones, $id
            );
            $message = "Registro actualizado exitosamente.";
        } else {
            $sql = "INSERT INTO seguimiento_menores_8uit (
                        anio, tipo_orden, unidad_solicitante, fecha_requerimiento, 
                        descripcion_servicio_bien, fecha_emision, plazo_ejecucion_dias, 
                        fecha_vencimiento, estado_id, monto_comprometido, 
                        monto_devengado, monto_girado, observaciones, usuario_registro_id
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("isssssisidddsi", 
                $anio, $tipo_orden, $area_usuaria, $fecha_requerimiento,
                $objeto_contratacion, $fecha_orden, $plazo_dias,
                $fecha_final, $estado_id, $imp_comprometido,
                $imp_devengado, $imp_girado, $observaciones, $_SESSION['id']
            );
            $message = "Registro guardado exitosamente.";
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => $message]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta: ' . $stmt->error]);
        }
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
