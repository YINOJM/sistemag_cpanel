<?php
// Este controlador asume que YA se llamó a session_start() y YA está incluido ../modelo/conexion.php
// Devuelve un flag por GET para mostrar SweetAlert sin duplicados.

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnmodificar'])) {

    $id       = isset($_POST['txtid']) ? (int) $_POST['txtid'] : 0;
    $nombre   = trim($_POST['txtnombre']   ?? '');
    $apellido = trim($_POST['txtapellido'] ?? '');
    $usuario  = trim($_POST['txtusuario']  ?? '');

    if ($id > 0 && $nombre !== '' && $apellido !== '' && $usuario !== '') {

        $stmt = $conexion->prepare(
            "UPDATE usuario SET nombre = ?, apellido = ?, usuario = ? WHERE id_usuario = ?"
        );

        if ($stmt) {
            $stmt->bind_param("sssi", $nombre, $apellido, $usuario, $id);

            if ($stmt->execute()) {
                // Actualiza la sesión para que el topbar muestre los nuevos datos
                $_SESSION['nombre']   = $nombre;
                $_SESSION['apellido'] = $apellido;
                $_SESSION['usuario']  = $usuario;

                // Redirección con flag de éxito (evita reenvío y pantallas en blanco)
                header("Location: perfil.php?ok=1");
                exit;
            } else {
                $err = urlencode($stmt->error);
                header("Location: perfil.php?error=1&d=$err");
                exit;
            }
        } else {
            $err = urlencode($conexion->error);
            header("Location: perfil.php?error=1&d=$err");
            exit;
        }
    } else {
        header("Location: perfil.php?vacio=1");
        exit;
    }
}
