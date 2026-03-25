<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_POST["btnrestablecer"])) {
    if (!empty($_POST["token"]) and !empty($_POST["password"]) and !empty($_POST["password_confirm"])) {
        $token = $_POST["token"];
        $password = $_POST["password"];
        $password_confirm = $_POST["password_confirm"];

        if ($password !== $password_confirm) {
            $_SESSION['reset_msg'] = "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> Las contraseñas no coinciden.</div>";
            header("Location: ../vista/restablecer.php?token=" . urlencode($token));
            exit;
        }

        // Verificar longitud mínima
        if (strlen($password) < 6) {
            $_SESSION['reset_msg'] = "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> La contraseña debe tener al menos 6 caracteres.</div>";
            header("Location: ../vista/restablecer.php?token=" . urlencode($token));
            exit;
        }

        // Verificar token y expiración
        $fecha_actual = date("Y-m-d H:i:s");
        $sql = $conexion->query("SELECT * FROM usuario WHERE token_password = '$token' AND token_password_expiry > '$fecha_actual'");

        if ($sql->num_rows > 0) {
            // Token válido - usar password_hash() en lugar de MD5
            $nuevo_pass = password_hash($password, PASSWORD_DEFAULT);
            $update = $conexion->query("UPDATE usuario SET password = '$nuevo_pass', token_password = NULL, token_password_expiry = NULL WHERE token_password = '$token'");

            if ($update) {
                $_SESSION['login_msg'] = "<div class='alert alert-success'><i class='fas fa-check-circle'></i> ✓ Contraseña actualizada correctamente. Ya puedes iniciar sesión.</div>";
                header("Location: ../vista/login/login.php");
                exit;
            } else {
                $_SESSION['reset_msg'] = "<div class='alert alert-danger'><i class='fas fa-times-circle'></i> Error al actualizar la contraseña. Intente nuevamente.</div>";
                header("Location: ../vista/restablecer.php?token=" . urlencode($token));
                exit;
            }

        } else {
            $_SESSION['reset_msg'] = "<div class='alert alert-danger'><i class='fas fa-times-circle'></i> El enlace es inválido o ha expirado.</div>";
            header("Location: ../vista/restablecer.php?token=" . urlencode($token));
            exit;
        }

    } else {
        $_SESSION['reset_msg'] = "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> Por favor rellene todos los campos.</div>";
        header("Location: ../vista/restablecer.php?token=" . urlencode($_POST['token'] ?? ''));
        exit;
    }
}
?>