<?php
session_start();
require_once __DIR__ . '/../modelo/conexion.php';

if (isset($_POST["btnmodificar"])) {

    $id = (int)$_POST["txtid"];
    $claveActual = $_POST["txtclaveactual"];
    $claveNueva  = $_POST["txtclavenueva"];

    if ($id > 0 && !empty($claveActual) && !empty($claveNueva)) {

        $rs = $conexion->query("SELECT password FROM usuario WHERE id_usuario = $id");
        $row = $rs ? $rs->fetch_object() : null;

        if ($row) {

            $hashGuardado = $row->password;
            $claveActualMD5 = md5($claveActual);

            // Detectar si el hash guardado es bcrypt o md5
            $esBcrypt = str_starts_with($hashGuardado, '$2y$') || str_starts_with($hashGuardado, '$2a$');

            // Validación segura
            $valida = $esBcrypt ? password_verify($claveActual, $hashGuardado) : ($hashGuardado === $claveActualMD5);

            if ($valida) {

                // Siempre guardar la nueva contraseña en bcrypt (seguro)
                $nuevoHash = password_hash($claveNueva, PASSWORD_BCRYPT);

                $ok = $conexion->query("UPDATE usuario SET password='$nuevoHash' WHERE id_usuario=$id");

                $_SESSION['flash'] = $ok
                    ? ["tipo" => "success", "msg" => "La contraseña fue actualizada correctamente."]
                    : ["tipo" => "error", "msg" => "Error al actualizar la contraseña."];

            } else {
                $_SESSION['flash'] = ["tipo" => "warning", "msg" => "La contraseña actual es incorrecta."];
            }

        }

    } else {
        $_SESSION['flash'] = ["tipo" => "info", "msg" => "Complete todos los campos."];
    }

    header("Location: ../vista/cambiarClave.php");
    exit();
}
