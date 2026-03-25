<?php
session_start();
include "../modelo/conexion.php";

$token = $_GET["token"] ?? '';
$mensaje = "";
$tipo_mensaje = ""; // success, warning, danger
$mostrar_formulario = true;

// PROCESAR FORMULARIO
if (isset($_POST["btnrestablecer"])) {
    $token_post = $_POST["token"] ?? '';
    $password = $_POST["password"] ?? '';
    $password_confirm = $_POST["password_confirm"] ?? '';

    // Validar que las contraseñas coincidan
    if ($password !== $password_confirm) {
        $mensaje = "Las contraseñas no coinciden.";
        $tipo_mensaje = "warning";
    }
    // Validar longitud mínima
    elseif (strlen($password) < 6) {
        $mensaje = "La contraseña debe tener al menos 6 caracteres.";
        $tipo_mensaje = "warning";
    }
    // Procesar cambio de contraseña
    else {
        $fecha_actual = date("Y-m-d H:i:s");

        // Buscar token válido
        $sql = $conexion->query("SELECT * FROM usuario WHERE token_password = '$token_post' AND token_password_expiry > '$fecha_actual'");

        if ($sql && $sql->num_rows > 0) {
            // Token válido - actualizar contraseña
            $nuevo_pass = password_hash($password, PASSWORD_DEFAULT);
            $update = $conexion->query("UPDATE usuario SET password = '$nuevo_pass', token_password = NULL, token_password_expiry = NULL WHERE token_password = '$token_post'");

            if ($update && $conexion->affected_rows > 0) {
                $mensaje = "¡Contraseña actualizada correctamente! Ya puedes iniciar sesión con tu nueva contraseña.";
                $tipo_mensaje = "success";
                $mostrar_formulario = false;
            } else {
                $mensaje = "Error al actualizar la contraseña. Por favor, intenta de nuevo.";
                $tipo_mensaje = "danger";
            }
        } else {
            // Token inválido o expirado
            $mensaje = "El enlace es inválido o ha expirado. Los enlaces de recuperación solo son válidos por 1 hora. Por favor, solicita un nuevo enlace.";
            $tipo_mensaje = "danger";
            $mostrar_formulario = false;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña</title>
    <link href="https://fonts.googleapis.com/css?family=Poppins:400,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #36d1dc, #5b86e5);
        }

        .container {
            background: white;
            width: 90%;
            max-width: 450px;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert i {
            font-size: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .input-wrapper {
            display: flex;
            align-items: center;
            background: #f5f5f5;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 0 15px;
            transition: all 0.3s;
        }

        .input-wrapper:focus-within {
            background: white;
            border-color: #5b86e5;
        }

        .input-wrapper i {
            color: #5b86e5;
            margin-right: 10px;
        }

        input[type="password"] {
            flex: 1;
            border: none;
            background: transparent;
            padding: 15px 0;
            font-size: 14px;
            outline: none;
            font-family: 'Poppins', sans-serif;
        }

        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
        }

        .btn-primary {
            background: linear-gradient(135deg, #36d1dc, #5b86e5);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(91, 134, 229, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            margin-top: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #218838, #1aa179);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }

        .btn-success i {
            font-size: 18px;
        }

        .link {
            display: block;
            text-align: center;

        }

        .link:hover {
            text-decoration: underline;
        }

        .password-hint {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-left: 3px solid #5b86e5;
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-size: 12px;
            color: #495057;
            display: flex;
            gap: 10px;
            text-align: left;
            line-height: 1.6;
        }

        .password-hint i {
            color: #5b86e5;
            margin-top: 2px;
            font-size: 14px;
        }

        .password-hint strong {
            color: #333;
            font-size: 13px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Nueva Contraseña</h2>
        <p class="subtitle">Ingrese su nueva contraseña</p>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                <i
                    class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : ($tipo_mensaje === 'warning' ? 'exclamation-triangle' : 'times-circle'); ?>"></i>
                <div><?php echo $mensaje; ?></div>
            </div>
        <?php endif; ?>

        <?php if ($mostrar_formulario): ?>
            <form method="POST" action="">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" placeholder="Nueva Contraseña" required
                            minlength="6" maxlength="50">
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password_confirm" id="password_confirm"
                            placeholder="Confirmar Contraseña" required minlength="6" maxlength="50">
                    </div>
                </div>

                <div class="password-hint">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>La contraseña debe tener:</strong><br>
                        • Entre 6 y 50 caracteres<br>
                        • Letras (a-z, A-Z), números (0-9)<br>
                        • Símbolos permitidos: @ # $ % & * - _ + = ! ?
                    </div>
                </div>

                <button type="submit" name="btnrestablecer" class="btn btn-primary">
                    Guardar Contraseña
                </button>
            </form>
        <?php else: ?>
            <?php if ($tipo_mensaje === 'success'): ?>
                <a href="login/login.php" class="btn btn-success">
                    <i class="fas fa-sign-in-alt"></i>
                    Iniciar Sesión
                </a>
            <?php else: ?>
                <a href="recuperar.php" class="btn btn-primary">Solicitar Nuevo Enlace</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>

</html>