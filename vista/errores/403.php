<?php
require_once __DIR__ . '/../../modelo/conexion.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Acceso Denegado | Sistema Integrado de Gestión</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .error-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 60px 40px;
            max-width: 600px;
            text-align: center;
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .error-icon {
            font-size: 120px;
            color: #dc3545;
            margin-bottom: 30px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }
        }

        .error-code {
            font-size: 80px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 20px;
            line-height: 1;
        }

        .error-title {
            font-size: 32px;
            font-weight: 600;
            color: #34495e;
            margin-bottom: 20px;
        }

        .error-message {
            font-size: 18px;
            color: #7f8c8d;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .error-details {
            background: #f8f9fa;
            border-left: 4px solid #dc3545;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: left;
        }

        .error-details h5 {
            color: #dc3545;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .error-details ul {
            margin: 0;
            padding-left: 20px;
        }

        .error-details li {
            color: #495057;
            margin-bottom: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 15px 40px;
            font-size: 18px;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .btn-secondary {
            background: #6c757d;
            border: none;
            padding: 15px 40px;
            font-size: 18px;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
            margin-left: 10px;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .contact-info {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #e9ecef;
        }

        .contact-info p {
            color: #6c757d;
            font-size: 14px;
            margin: 0;
        }

        .contact-info a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .contact-info a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="error-container">
        <!-- Icono de Error -->
        <div class="error-icon">
            <i class="fas fa-shield-alt"></i>
        </div>

        <!-- Código de Error -->
        <div class="error-code">403</div>

        <!-- Título -->
        <h1 class="error-title">Acceso Denegado</h1>

        <!-- Mensaje -->
        <p class="error-message">
            Lo sentimos, no tienes permisos para acceder a este recurso.
        </p>

        <!-- Detalles del Error -->
        <div class="error-details">
            <h5><i class="fas fa-info-circle"></i> ¿Por qué veo este mensaje?</h5>
            <ul>
                <li>No tienes los permisos necesarios para acceder a este módulo</li>
                <li>Tu rol de usuario no incluye este recurso</li>
                <li>Es posible que necesites permisos adicionales de un administrador</li>
            </ul>
        </div>

        <!-- Botones de Acción -->
        <div class="d-flex justify-content-center flex-wrap gap-2">
            <a href="<?= isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : (defined('BASE_URL') ? BASE_URL : '/sistem_job/') . 'vista/inicio.php' ?>"
                class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            <a href="<?= (defined('BASE_URL') ? BASE_URL : '/sistem_job/') ?>vista/inicio.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Ir al Inicio
            </a>
        </div>

        <!-- Información de Contacto -->
        <div class="contact-info">
            <p>
                Si crees que esto es un error, contacta al administrador del sistema.
            </p>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>