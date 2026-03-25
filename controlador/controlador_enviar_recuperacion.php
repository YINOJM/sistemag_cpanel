<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cargar PHPMailer manualmente
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Conexión
require_once __DIR__ . '/../modelo/conexion.php';

if (isset($_POST['btnrecuperar'])) {

    // Mensaje directo y claro
    $mensaje_ok = "
    <div class='alert alert-success'>
        <i class='fas fa-check-circle'></i> Se ha enviado un enlace de recuperación a tu correo electrónico.
        <br><strong>Por favor, revisa tu bandeja de entrada.</strong>
    </div>";

    if (!empty($_POST['correo'])) {

        $email = trim($_POST['correo']);

        // Buscar usuario por correo
        $sql = $conexion->query(
            "SELECT nombre, correo FROM usuario WHERE correo = '$email' LIMIT 1"
        );

        if ($sql && $sql->num_rows > 0) {

            $user = $sql->fetch_object();

            // Verificar que el usuario tenga correo configurado
            if (empty($user->correo)) {
                $_SESSION['recovery_msg'] = "Este usuario no tiene un correo electrónico configurado. Por favor, contacta al administrador.";
                $_SESSION['recovery_type'] = "danger";
                header("Location: recuperar.php");
                exit();
            }

            // Token y expiración
            $token = bin2hex(random_bytes(32));
            $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $conexion->query("
                UPDATE usuario 
                SET token_password = '$token',
                    token_password_expiry = '$expira'
                WHERE correo = '$email'
            ");

            try {
                $mail = new PHPMailer(true);

                // SMTP Gmail
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'omaryinoj@gmail.com';
                $mail->Password = 'qqifurmddtnrnwag'; // contraseña app
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port = 465;

                $mail->setFrom('omaryinoj@gmail.com', 'Sistema Job');
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->CharSet = 'UTF-8';
                $mail->Subject = 'Recuperación de contraseña';

                $base_full = defined('FULL_BASE_URL') ? FULL_BASE_URL : "http://{$_SERVER['HTTP_HOST']}/sistem_job/";
                $link = $base_full . "vista/restablecer.php?token=$token";

                $mail->Body = "
                    <h2>Recuperación de contraseña</h2>
                    <p>Hola {$user->nombre},</p>
                    <p>Haz clic en el siguiente enlace:</p>
                    <p><a href='$link'>$link</a></p>
                    <p>Este enlace expirará en 1 hora.</p>
                ";

                $mail->send();

            } catch (Exception $e) {
                // No revelar errores SMTP
            }
        }

        $_SESSION['recuperar_msg'] = $mensaje_ok;

    } else {
        $_SESSION['recuperar_msg'] = "
        <div class='alert alert-warning'>
            Debe ingresar un correo electrónico.
        </div>";
    }

    // REDIRECCIÓN CORRECTA
    header("Location: ../vista/recuperar.php");
    exit;
}
