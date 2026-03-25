<?php
// controlador/cmn_marcar_envio.php

// Esto ya inicia la sesión limpiamente por dentro sin crear conflictos
require_once '../modelo/conexion.php';

// Validamos solo que el usuario esté logueado en el sistema
if (empty($_SESSION['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado']);
    exit();
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
// Estado 1 = correo copiado/enviado, 0 = no enviado
$estado = isset($_POST['estado']) ? (int)$_POST['estado'] : 0; 

if ($id > 0) {
    // Intentamos actualizar
    $stmt = $conexion->prepare("UPDATE cmn_responsables SET acceso_enviado = ? WHERE id = ?");
    
    // Si falla, es seguro que la columna no existe, la creamos al vuelo:
    if (!$stmt) {
        $conexion->query("ALTER TABLE cmn_responsables ADD COLUMN acceso_enviado TINYINT(1) DEFAULT 0");
        $stmt = $conexion->prepare("UPDATE cmn_responsables SET acceso_enviado = ? WHERE id = ?");
    }

    if ($stmt) {
        $stmt->bind_param("ii", $estado, $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Guardado permanentemente']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo guardar']);
        }
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
}
