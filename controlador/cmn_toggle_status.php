<?php
// cmn_toggle_status.php
require_once __DIR__ . '/../modelo/conexion.php';

header('Content-Type: application/json');

if (empty($_SESSION['id']) || $_SESSION['rol'] !== 'Super Administrador') {
    echo json_encode(["success" => false, "message" => "No autorizado"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevo_estado = ($_POST['estado'] === '1') ? '1' : '0';
    $stmt = $conexion->prepare("UPDATE cmn_config SET valor = ? WHERE clave = 'mantenimiento'");
    $stmt->bind_param("s", $nuevo_estado);
    
    if ($stmt->execute()) {
        $msg = ($nuevo_estado === '1') ? "Formulario DESACTIVADO correctamente." : "Formulario ACTIVADO correctamente.";
        echo json_encode(["success" => true, "message" => $msg]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al actualizar"]);
    }
}
?>
