<?php
// controlador/controlador_config_pac.php
require_once '../modelo/conexion.php'; // Debe definir $conn o $conexion y el alias $conn

// Acepta: accion = guardar | eliminar | recalcular
$accion = isset($_POST['accion']) ? $_POST['accion'] : (isset($_GET['accion']) ? $_GET['accion'] : '');
$anio   = isset($_POST['anio']) ? (int)$_POST['anio'] : (isset($_GET['anio']) ? (int)$_GET['anio'] : 0);

if ($accion === 'guardar') {
    $total_pac = isset($_POST['total_pac']) ? (float)$_POST['total_pac'] : 0;
    $modo      = 'manual';

    // UPSERT por anio (requiere UNIQUE KEY en anio)
    $sql = "INSERT INTO config_pac (anio, total_pac, updated_at, modo)
            VALUES (?, ?, NOW(), ?)
            ON DUPLICATE KEY UPDATE
                total_pac = VALUES(total_pac),
                updated_at = NOW(),
                modo = VALUES(modo)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ids', $anio, $total_pac, $modo);
    $ok = $stmt->execute();
    $stmt->close();

    header('Location: ../vista/config_pac.php?msg=' . ($ok ? 'guardado' : 'error'));
    exit;
}

if ($accion === 'eliminar') {
    if ($anio > 0) {
        $stmt = $conn->prepare("DELETE FROM config_pac WHERE anio = ?");
        $stmt->bind_param('i', $anio);
        $ok = $stmt->execute();
        $stmt->close();

        header('Location: ../vista/config_pac.php?msg=' . ($ok ? 'eliminado' : 'error'));
        exit;
    }
    header('Location: ../vista/config_pac.php?msg=error');
    exit;
}

if ($accion === 'recalcular') {
    if ($anio > 0) {
        // Recalcular porcentaje y cuantía_categoria para ese año
        $sql = "
        UPDATE segmentacion s
        JOIN config_pac cp ON cp.anio = s.anio
        SET s.porcentaje = CASE WHEN cp.total_pac > 0 THEN ROUND((s.cuantia / cp.total_pac) * 100, 0) ELSE 0 END,
            s.cuantia_categoria = CASE 
                    WHEN cp.total_pac > 0 AND ROUND((s.cuantia / cp.total_pac) * 100, 0) > 10 THEN 'Alta'
                    ELSE 'Baja'
                END
        WHERE s.anio = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $anio);
        $ok = $stmt->execute();
        $stmt->close();

        header('Location: ../vista/config_pac.php?msg=' . ($ok ? 'recalculado' : 'error'));
        exit;
    }
    header('Location: ../vista/config_pac.php?msg=error');
    exit;
}

header('Location: ../vista/config_pac.php');
