<?php
// cmn_eliminar_registro.php
require_once __DIR__ . '/../modelo/conexion.php';

header('Content-Type: application/json');

// Verificación de seguridad: Super Administrador o Administrador
if (empty($_SESSION['id']) || (strcasecmp($_SESSION['rol'], 'Super Administrador') !== 0 && strcasecmp($_SESSION['rol'], 'Administrador') !== 0)) {
    echo json_encode(["status" => "error", "message" => "Acceso denegado. No tiene permisos necesarios."]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    
    // 1. Obtener el nombre del archivo PDF antes de borrar para eliminarlo físicamente
    $stmt_file = $conexion->prepare("SELECT archivo_pdf FROM cmn_responsables WHERE id = ?");
    $stmt_file->bind_param("i", $id);
    $stmt_file->execute();
    $res_file = $stmt_file->get_result();
    $datos_archivo = $res_file->fetch_assoc();
    
    if ($datos_archivo) {
        $nombre_archivo = $datos_archivo['archivo_pdf'];
        
        // 2. Eliminar de la base de datos
        $stmt_del = $conexion->prepare("DELETE FROM cmn_responsables WHERE id = ?");
        $stmt_del->bind_param("i", $id);
        
        if ($stmt_del->execute()) {
            // 3. Eliminar el archivo físico si existe
            if (!empty($nombre_archivo)) {
                $ruta_archivo = "../uploads/cmn_2026/" . $nombre_archivo;
                if (file_exists($ruta_archivo)) {
                    unlink($ruta_archivo);
                }
            }
            echo json_encode(["success" => true, "message" => "Registro eliminado correctamente."]);
        } else {
            echo json_encode(["success" => false, "message" => "Error al eliminar de la base de datos: " . $conexion->error]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Registro no encontrado."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "ID no proporcionado o método no válido."]);
}
exit();
?>
