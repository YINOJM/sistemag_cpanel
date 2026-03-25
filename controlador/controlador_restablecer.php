<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Conexión
require_once __DIR__ . '/../modelo/conexion.php';

if (isset($_POST['btnrestablecer'])) {

    $token = $_GET['token'] ?? '';
    $nueva_password = $_POST['nueva_password'] ?? '';
    $confirmar_password = $_POST['confirmar_password'] ?? '';

    // Validaciones
    if (empty($token)) {
        $_SESSION['restablecer_msg'] = "
        <div class='alert alert-danger'>
            Token inválido o expirado.
        </div>";
        header("Location: ../vista/restablecer.php?token=$token");
        exit;
    }

    if (empty($nueva_password) || empty($confirmar_password)) {
        $_SESSION['restablecer_msg'] = "
        <div class='alert alert-warning'>
            Debe completar todos los campos.
        </div>";
        header("Location: ../vista/restablecer.php?token=$token");
        exit;
    }

    if ($nueva_password !== $confirmar_password) {
        $_SESSION['restablecer_msg'] = "
        <div class='alert alert-warning'>
            Las contraseñas no coinciden.
        </div>";
        header("Location: ../vista/restablecer.php?token=$token");
        exit;
    }

    if (strlen($nueva_password) < 6) {
        $_SESSION['restablecer_msg'] = "
        <div class='alert alert-warning'>
            La contraseña debe tener al menos 6 caracteres.
        </div>";
        header("Location: ../vista/restablecer.php?token=$token");
        exit;
    }

    // Verificar token en la base de datos
    $sql = $conexion->query("
        SELECT id, nombre 
        FROM usuario 
        WHERE token_password = '$token' 
        AND token_password_expiry > NOW()
        LIMIT 1
    ");

    if ($sql && $sql->num_rows > 0) {

        $user = $sql->fetch_object();

        // Encriptar nueva contraseña
        $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);

        // Actualizar contraseña y limpiar token
        $update = $conexion->query("
            UPDATE usuario 
            SET password = '$password_hash',
                token_password = NULL,
                token_password_expiry = NULL
            WHERE id = {$user->id}
        ");

        if ($update) {
            $_SESSION['login_msg'] = "
            <div class='alert alert-success'>
                ✓ Contraseña actualizada correctamente. Puede iniciar sesión.
            </div>";
            header("Location: ../vista/login/login.php");
            exit;
        } else {
            $_SESSION['restablecer_msg'] = "
            <div class='alert alert-danger'>
                Error al actualizar la contraseña. Intente nuevamente.
            </div>";
            header("Location: ../vista/restablecer.php?token=$token");
            exit;
        }

    } else {
        $_SESSION['restablecer_msg'] = "
        <div class='alert alert-danger'>
            El enlace de recuperación ha expirado o es inválido.
        </div>";
        header("Location: ../vista/restablecer.php?token=$token");
        exit;
    }
}
?>