<?php
// controlador/cmn_registro_update.php
require_once __DIR__ . '/../modelo/conexion.php';

header('Content-Type: application/json');

// Verificación de seguridad
if (empty($_SESSION['id']) || (strcasecmp($_SESSION['rol'], 'Super Administrador') !== 0 && strcasecmp($_SESSION['rol'], 'Administrador') !== 0)) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado']);
    exit();
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID de registro no válido']);
    exit();
}

// Obtener datos del POST
$grado = $_POST['grado'] ?? '';
$cip = $_POST['cip'] ?? '';
$apellidos = $_POST['apellidos'] ?? '';
$nombres = $_POST['nombres'] ?? '';
$correo = $_POST['correo'] ?? '';
$celular = $_POST['celular'] ?? '';
$cargo = $_POST['cargo'] ?? '';

if (empty($grado) || empty($apellidos) || empty($nombres) || empty($celular)) {
    echo json_encode(['status' => 'error', 'message' => 'Complete todos los campos obligatorios']);
    exit();
}

// Actualizar en la base de datos
$stmt = $conexion->prepare("UPDATE cmn_responsables SET 
    grado = ?, 
    cip = ?, 
    apellidos = ?, 
    nombres = ?, 
    correo = ?, 
    celular = ?, 
    cargo = ? 
    WHERE id = ?");

$stmt->bind_param("sssssssi", $grado, $cip, $apellidos, $nombres, $correo, $celular, $cargo, $id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Registro actualizado correctamente']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error al actualizar: ' . $conexion->error]);
}
?>
