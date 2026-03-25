<?php
session_start();
require_once "../modelo/conexion.php";

// Seguridad: Solo administradores pueden guardar permisos
if (empty($_SESSION['rol']) || ($_SESSION['rol'] !== 'Administrador' && $_SESSION['rol'] !== 'Super Administrador')) {
    header("Location: ../vista/inicio.php");
    exit();
}

if (isset($_POST['btnGuardarPermisos'])) {
    $id_usuario = (int)$_POST['id_usuario'];
    
    // 1. Eliminar permisos anteriores
    $stmt = $conexion->prepare("DELETE FROM permisos WHERE id_usuario = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    
    // 2. Insertar nuevos permisos
    if (!empty($_POST['permisos'])) {
        $stmtInsert = $conexion->prepare("INSERT INTO permisos (id_usuario, modulo, accion) VALUES (?, ?, ?)");
        
        foreach ($_POST['permisos'] as $modulo => $acciones) {
            foreach ($acciones as $accion => $valor) {
                // $valor es 1, pero solo necesitamos confirmar que está checkeado
                if ($valor == 1) {
                    $stmtInsert->bind_param("iss", $id_usuario, $modulo, $accion);
                    $stmtInsert->execute();
                }
            }
        }
    }
    
    // Redirigir con éxito (puedes añadir lógica de alertas aquí si tienes un sistema de notificaciones en sesión)
    echo "<script>
            alert('Permisos actualizados correctamente.');
            window.location.href = '../vista/usuario.php';
          </script>";
} else {
    header("Location: ../vista/usuario.php");
}
?>
