<?php
// 1. CARGAR CONEXIÓN Y ENTORNO PRIMERO (Incluye configuración de sesión de 4h)
include "../../modelo/conexion.php";

// Redirigir si ya está logueado
if (isset($_SESSION["id"])) {
    header("Location: " . (defined('BASE_URL') ? BASE_URL : '/sistem_job/') . "vista/inicio.php");
    exit();
}

// Recuperar alerta de sesión si existe
$alert = "";
if (!empty($_SESSION['alert_login'])) {
    $alert = $_SESSION['alert_login'];
    unset($_SESSION['alert_login']); // Limpiar para no repetir la alerta
}

include "../../controlador/login.php";
?>


<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de Sesión</title>

    <!-- Importamos pesos 300, 400, 600, 700 para que el navegador no los "finja" -->
    <link href="https://fonts.googleapis.com/css?family=Poppins:300,400,600,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            height: 100vh;
            display: flex;
            flex-direction: column; 
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #36d1dc, #5b86e5);
            gap: 20px; 
        }

        /* Estilos del Título del Sistema */
        .system-branding {
            text-align: center;
            color: #fff;
            z-index: 10;
            margin-bottom: 20px;
            /* Animación de flotación suave e infinita */
            animation: float 6s ease-in-out infinite;
        }
        
        .system-branding h1 {
            font-size: 2.8rem;
            font-weight: 700; /* Ahora usa el peso real 700 */
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 3px; /* Un poco más de espaciado para elegancia */
            /* Sombra más definida para mejor lectura sobre degradados */
            text-shadow: 0 2px 10px rgba(0,0,0,0.3); 
            /* Entrada inicial */
            animation: fadeInDown 1s ease-out;
            
            /* Flex para alinear verticalmente el badge con el texto */
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px; /* Separación elegante */
            flex-wrap: wrap; /* Por si en móviles se rompe */
        }

        /* 
        .sig-badge-container eliminado al volver a ponerlo en línea 
        */

        .sig-badge {
            display: inline-block;
            background: #fff;
            color: #006db3; /* Azul institucional */
            font-size: 0.9rem; /* Un poco más pequeño para no competir con el H1 */
            padding: 4px 10px;
            border-radius: 6px;
            border: 1px solid rgba(255,255,255,0.4);
            letter-spacing: 1px;
            font-weight: 800;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            /* Ajuste óptico vertical */
            margin-top: 4px; 
            vertical-align: middle;
        }

        .system-branding p {
            font-size: 1.1rem;
            font-weight: 300; /* Ahora usa el peso real 300 */
            margin-top: 5px;
            opacity: 0.95;
            letter-spacing: 1.5px;
            text-shadow: 0 1px 5px rgba(0,0,0,0.2);
            /* Entrada inicial */
            animation: fadeInDown 1s ease-out;
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }

        .login-box {
            position: relative;
            background: rgba(255, 255, 255, 0.85);
            /* Transparencia */
            backdrop-filter: blur(12px);
            /* Efecto vidrio esmerilado */
            -webkit-backdrop-filter: blur(12px);
            /* Para Safari */
            width: 380px; /* Reducido de 420px */
            padding: 70px 30px 30px; /* Reducido de 100px 40px 40px 40px */
            border-radius: 15px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
            /* Sombra más suave */
            text-align: center;
            overflow: hidden;
            transition: transform 0.2s ease;
            /* transición suave para hover */
            z-index: 10;
            /* Asegurar que esté encima de la animación */
        }

        /* Responsive adjustments */
        @media (max-width: 500px) {
            .system-branding h1 { font-size: 1.8rem; }
            .login-box { width: 90%; }
        }

        .login-box:hover {
            transform: translateY(-7px);
            /* levanta el formulario al pasar el mouse */
        }


        /* Barra superior */
        .login-box .top-bar {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 70px;
            background: linear-gradient(135deg, #36d1dc, #5b86e5);

        }

        /* Logo dentro del círculo */
        .login-box .logo-circle {
            width: 100px; /* Reducido de 120px */
            height: 100px; /* Reducido de 120px */
            background: #fff;
            border-radius: 50%;
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            /* IMPORTANTE: Recorta lo que sobresalga del círculo */
        }

        .login-box .logo-circle img {
            width: 70%; /* Ligeramente más pequeño dentro del círculo */
            height: 70%;
            object-fit: contain;
            border-radius: 0;
            margin-top: 5px; /* Ajuste visual para que se vea centrado con la sombra de la imagen */
        }

        /* Título */
        .login-box h2 {
            margin: 60px 0 20px 0; /* Ajustado el margin-top de 70px a 60px */
            color: #006db3; /* Azul institucional en lugar de negro */
            font-weight: 700; /* Matching weight with main title */
            letter-spacing: 1px; /* Estilo similar al título principal */
            font-size: 1.6rem; /* Ligeramente reducido de 1.8rem */
        }

        /* Input Group */
        .input-group {
            display: flex;
            align-items: center;
            border: 1px solid #ddd;
            border-radius: 12px;
            background: #f9f9f9;
            margin-bottom: 20px;
            position: relative;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .input-group:focus-within {
            border-color: #667eea;
            box-shadow: 0 0 6px rgba(102, 126, 234, 0.5);
        }

        /* Input */
        .form-control {
            border: none;
            flex: 1;
            height: 50px;
            padding-left: 12px;
            padding-right: 45px;
            font-size: 1.05rem;
            background: transparent;
        }

        /* Iconos */
        .input-group-text {
            background: transparent;
            border: none;
            color: #999;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 15px;
        }

        /* Ojo de mostrar contraseña */
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            font-size: 1.2rem;
        }

        /* Botón */
        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, #36d1dc, #5b86e5);

            color: #fff;
            padding: 12px;
            font-size: 1rem;
            border-radius: 12px;
            border: none;
            transition: background 0.3s, box-shadow 0.3s;
            margin-top: 15px;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, rgb(90, 170, 216), rgb(52, 115, 187));
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
            color: #fff;
            /* Texto blanco al pasar mouse */
        }

        /* Olvidó contraseña */
        .login-box a {
            font-size: 0.85rem;
            color: #888;
            text-decoration: none;
        }

        .login-box a:hover {
            color: #667eea;
        }
    </style>
    <style>
        /* ... estilos previos ... */

        /* Canvas para la animación de fondo */
        #bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            /* Al frente del fondo */
            pointer-events: none;
            /* Para que no bloquee clicks */
        }
    </style>
</head>

<body>

    <!-- Canvas para partículas -->
    <canvas id="bg-animation"></canvas>

    <!-- Título Flotante SUGERIDO -->
    <div class="system-branding">
        <h1>SISTEMA INTEGRADO DE GESTIÓN <span class="sig-badge">SIG</span></h1>
        <p>Plataforma de Servicios Administrativos</p>
    </div>

    <div class="login-box">
        <div class="top-bar"></div>
        <div class="logo-circle">
            <img src="../../public/images/card_user.png" alt="Logo">
        </div>

        <h2>BIENVENIDO</h2>
        <?php if (!empty($alert)): ?>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Acceso Denegado',
                    text: '<?= htmlspecialchars($alert, ENT_QUOTES) ?>',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Entendido'
                });
            </script>
        <?php endif; ?>


        <form method="POST" action="">
            <!-- Usuario -->
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-user"></i></span>
                <input type="text" name="usuario" class="form-control" placeholder="Usuario" required autocomplete="off"
                    spellcheck="false">
            </div>

            <!-- Contraseña -->
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" id="password" name="password" class="form-control" placeholder="Contraseña"
                    required autocomplete="new-password" spellcheck="false">
                <i class="fas fa-eye toggle-password" id="togglePassword"></i>
            </div>

            <!-- Olvidó contraseña y Registro -->
            <div class="d-flex justify-content-between mb-3 px-1">
                <a href="../recuperar.php">¿Olvidó su contraseña?</a>
                <a href="auto_registro.php" class="fw-bold text-primary">¿No tiene cuenta? Regístrese aquí</a>
            </div>

            <input type="submit" name="btningresar" class="btn btn-login" value="INICIAR SESIÓN">
        </form>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', () => {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            togglePassword.classList.toggle('fa-eye-slash');
        });
    </script>
    <script>
        // ==========================================
        // ANIMACIÓN DE PARTÍCULAS + MOUSE
        // ==========================================
        (function () {
            const canvas = document.getElementById('bg-animation');
            const ctx = canvas.getContext('2d');

            let width, height;
            let particles = [];

            // Mouse tracking
            let mouse = { x: null, y: null };

            // Configuración
            const PARTICLE_COUNT = 60;
            const CONNECT_DISTANCE = 150;
            const MOUSE_DISTANCE = 200; // Radio de atracción del mouse

            function resize() {
                width = canvas.width = window.innerWidth;
                height = canvas.height = window.innerHeight;
            }

            // Event Listeners del Mouse
            window.addEventListener('mousemove', function (e) {
                mouse.x = e.x;
                mouse.y = e.y;
            });

            window.addEventListener('mouseout', function () {
                mouse.x = null;
                mouse.y = null;
            });

            class Particle {
                constructor() {
                    this.x = Math.random() * width;
                    this.y = Math.random() * height;
                    this.vx = (Math.random() - 0.5) * 1.5;
                    this.vy = (Math.random() - 0.5) * 1.5;
                    this.size = Math.random() * 2 + 1;
                }

                update() {
                    this.x += this.vx;
                    this.y += this.vy;

                    if (this.x < 0 || this.x > width) this.vx *= -1;
                    if (this.y < 0 || this.y > height) this.vy *= -1;
                }

                draw() {
                    ctx.beginPath();
                    ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                    ctx.fillStyle = 'rgba(255, 255, 255, 0.7)';
                    ctx.fill();
                }
            }

            function init() {
                resize();
                particles = [];
                for (let i = 0; i < PARTICLE_COUNT; i++) {
                    particles.push(new Particle());
                }
                loop();
            }

            function loop() {
                ctx.clearRect(0, 0, width, height);

                ctx.lineWidth = 0.5;
                for (let i = 0; i < particles.length; i++) {
                    const p1 = particles[i];

                    // 1. Conexiones entre partículas
                    for (let j = i + 1; j < particles.length; j++) {
                        const p2 = particles[j];
                        const dx = p1.x - p2.x;
                        const dy = p1.y - p2.y;
                        const dist = Math.sqrt(dx * dx + dy * dy);

                        if (dist < CONNECT_DISTANCE) {
                            ctx.beginPath();
                            const alpha = 1 - (dist / CONNECT_DISTANCE);
                            ctx.strokeStyle = `rgba(255, 255, 255, ${alpha * 0.5})`;
                            ctx.moveTo(p1.x, p1.y);
                            ctx.lineTo(p2.x, p2.y);
                            ctx.stroke();
                        }
                    }

                    // 2. Conexiones con el MOUSE
                    if (mouse.x != null) {
                        const dx = p1.x - mouse.x;
                        const dy = p1.y - mouse.y;
                        const dist = Math.sqrt(dx * dx + dy * dy);

                        if (dist < MOUSE_DISTANCE) {
                            ctx.beginPath();
                            const alpha = 1 - (dist / MOUSE_DISTANCE);
                            ctx.strokeStyle = `rgba(255, 255, 255, ${alpha * 0.8})`;
                            ctx.moveTo(p1.x, p1.y);
                            ctx.lineTo(mouse.x, mouse.y);
                            ctx.stroke();
                        }
                    }
                }

                particles.forEach(p => {
                    p.update();
                    p.draw();
                });

                requestAnimationFrame(loop);
            }

            window.addEventListener('resize', resize);
            init();
        })();
    </script>
</body>

</html>