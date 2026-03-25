<?php
// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Conexión a la BD
require_once "../modelo/conexion.php";

// Controlador (procesa el POST)
require_once "../controlador/controlador_enviar_recuperacion.php";

// Mensaje flash
$flash_msg = '';
if (isset($_SESSION['recuperar_msg'])) {
    $flash_msg = $_SESSION['recuperar_msg'];
    unset($_SESSION['recuperar_msg']);
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña</title>

    <link href="https://fonts.googleapis.com/css?family=Poppins:400,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="login/css/bootstrap.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #36d1dc, #5b86e5);
        }

        .login-box {
            background: rgba(255, 255, 255, 0.95);
            width: 420px;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
            text-align: center;
        }

        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, #36d1dc, #5b86e5);
            color: #fff;
            padding: 12px;
            border-radius: 12px;
            border: none;
            margin-top: 15px;
            transition: 0.3s;
        }

        .btn-login:hover {
            opacity: 0.9;
        }

        .input-group {
            background: #f9f9f9;
            border-radius: 12px;
            border: 1px solid #ddd;
            margin-bottom: 20px;
            align-items: center;
            padding-left: 10px;
        }

        .input-group-text {
            background: transparent;
            border: none;
        }

        .form-control {
            border: none;
            background: #f9f9f9;
            height: 50px;
        }
    </style>
</head>

<body>

    <div class="login-box">
        <h3>Recuperar Contraseña</h3>
        <p class="text-muted">
            Ingrese su correo electrónico para recibir un enlace de restablecimiento.
        </p>

        <!-- ALERT -->
        <?php if (!empty($flash_msg)): ?>
            <?php echo $flash_msg; ?>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-envelope"></i>
                </span>
                <input type="email" name="correo" class="form-control" placeholder="Correo electrónico" required>
            </div>

            <button type="submit" name="btnrecuperar" class="btn btn-login">
                Enviar enlace
            </button>

            <div class="mt-3">
                <a href="login/login.php" style="text-decoration: none; color: #666;">
                    Volver al Login
                </a>
            </div>
        </form>
    </div>

</body>

</html>