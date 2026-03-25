<?php
// topbar.php

// Iniciar configuración de entorno y sesión (si no se hizo antes)
require_once __DIR__ . '/../../modelo/conexion.php';

// El timeout ahora es de 4 horas (definido en modelo/sesion_config.php)

// Redirigir si el usuario no está logueado
// Redirigir si el usuario no está logueado
$login_path = (defined('BASE_URL') ? BASE_URL : '/sistem_job/') . "vista/login/login.php";
if (!isset($_SESSION['id'])) {
    header("Location: " . $login_path);
    exit();
}

// Revisar inactividad
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > INACTIVITY_LIMIT)) {
    session_unset();
    session_destroy();

    // Redirigir de forma segura
    $timeout_path = $login_path . "?timeout=1";
    if (!headers_sent()) {
        header("Location: " . $timeout_path);
        exit();
    } else {
        echo "<script>window.location.href='" . $timeout_path . "';</script>";
        exit();
    }
}

// Actualizar último tiempo de actividad
// Actualizar último tiempo de actividad
$_SESSION['last_activity'] = time();

// ==============================
// AUTO-CARGA DE PERMISOS (Real-time)
// ==============================
// Esto asegura que si el admin cambia permisos, el usuario lo vea al instante al recargar la página
require_once __DIR__ . '/../../controlador/autocargar_permisos.php';
if (isset($_SESSION['id'])) {
    recargarPermisosUsuario($_SESSION['id']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo_pagina ?? 'Sistema SIG' ?></title>

<!-- Estilos específicos del Topbar (Mantener aquí o mover a head global) -->
<!-- Se mantienen aquí temporalmente para no romper estilos en otras páginas -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?= (defined('BASE_URL') ? BASE_URL : '/sistem_job/') ?>public/css/estilos.css?v=<?= time() ?>">

<!-- jQuery & PNotify (Loaded here to support inline scripts in body) -->
<script src="<?= (defined('BASE_URL') ? BASE_URL : '/sistem_job/') ?>public/app/publico/js/lib/jquery/jquery.min.js"></script>
<link href="<?= (defined('BASE_URL') ? BASE_URL : '/sistem_job/') ?>public/pnotify/css/pnotify.css" rel="stylesheet">
<link href="<?= (defined('BASE_URL') ? BASE_URL : '/sistem_job/') ?>public/pnotify/css/pnotify.buttons.css" rel="stylesheet">
<script src="<?= (defined('BASE_URL') ? BASE_URL : '/sistem_job/') ?>public/pnotify/js/pnotify.js"></script>
<script src="<?= (defined('BASE_URL') ? BASE_URL : '/sistem_job/') ?>public/pnotify/js/pnotify.buttons.js"></script>

</head>
<body>
<header class="topbar d-flex justify-content-between align-items-center px-3 shadow-sm">
    <div class="d-flex align-items-center">
        <button id="toggleSidebar" class="btn btn-outline-light btn-sm me-2" title="Menú">
            <i class="fa-solid fa-bars"></i>
        </button>
        <h6 class="mb-0 text-white fw-semibold d-flex align-items-center gap-2" style="color: #fff !important;">
            Sistema Integrado de Gestión
            <span class="badge"
                style="background: rgba(255, 255, 255, 0.95); color: #006db3; font-size: 0.75rem; padding: 4px 10px; letter-spacing: 1.5px; font-weight: 700; border: 2px solid #00d4ff; box-shadow: 0 2px 6px rgba(0, 212, 255, 0.3);">SIG</span>
        </h6>
    </div>

    <div class="dropdown">
        <button class="btn btn-outline-light dropdown-toggle d-flex align-items-center gap-2 py-1"
            data-bs-toggle="dropdown" style="border: none; background: rgba(255, 255, 255, 0.1);">
            <div class="d-none d-md-flex align-items-center me-2">
                <i class="fa-solid fa-user-circle fa-2x text-white-50"></i>
            </div>
            <div class="text-start lh-1">
                <div class="fw-bold mb-1" style="font-size: 0.85rem; letter-spacing: 0.5px;">
                    <?= htmlspecialchars($_SESSION["dni"] ?? "") ?> -
                    <?= htmlspecialchars($_SESSION["nombre"] ?? "Usuario") ?>
                </div>
                <?php
                // Preparar variables de ubicación para uso limpio
                $region = $_SESSION["nombre_region"] ?? '';
                $division = $_SESSION["nombre_division"] ?? '';
                $subunidad = $_SESSION["nombre_subunidad"] ?? $_SESSION["nombre_oficina"] ?? 'SIN UNIDAD ASIGNADA';
                ?>

                    <div class="d-flex text-start align-items-start gap-2 mt-1">
                        <i class="fas fa-location-dot mt-1" style="font-size: 0.7rem; color: #ffc107;"></i>
                        <div class="d-flex flex-column text-white" style="font-size: 0.7rem; line-height: 1.2;">
                            <!-- FILA 2: SUBUNIDAD (Ubicación exacta) -->
                            <span class="fw-normal text-wrap" style="max-width: 450px;">
                                <?= htmlspecialchars($subunidad) ?>
                            </span>

                            <!-- FILA 3: JERARQUÍA (Contexto) -->
                            <?php if (!empty($region)): ?>
                                    <span style="font-size: 0.65rem; color: rgba(255,255,255,0.7);">
                                        <?= htmlspecialchars($division) ?> <span class="mx-1">|</span> <?= htmlspecialchars($region) ?>
                                    </span>
                            <?php endif; ?>
                        </div>
                    </div>
            </div>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="min-width: 250px;">
            <li class="p-3 bg-light rounded-top">
                <div class="text-muted small fw-bold mb-1">USUARIO CONECTADO</div>
                <div class="text-primary fw-bold" style="font-size: 0.9rem;">
                    <?= htmlspecialchars(($_SESSION["nombre"] ?? "Usuario") . " " . ($_SESSION["apellido"] ?? "")) ?>
                </div>
                <div class="text-muted small">
                    <?= htmlspecialchars($_SESSION["rol"] ?? "Personal") ?>
                </div>
            </li>
            <li>
                <hr class="dropdown-divider m-0">
            </li>
            <li><a class="dropdown-item py-2" href="<?= (defined('BASE_URL') ? BASE_URL : '/sistem_job/') ?>vista/perfil.php"><i
                        class="fa-solid fa-user me-2 text-secondary"></i>Mi Perfil</a></li>
            <li><a class="dropdown-item py-2" href="<?= (defined('BASE_URL') ? BASE_URL : '/sistem_job/') ?>vista/cambiarClave.php"><i
                        class="fa-solid fa-lock me-2 text-secondary"></i>Seguridad</a></li>
            <li>
                <hr class="dropdown-divider m-0">
            </li>
            <li><a class="dropdown-item py-2 text-danger fw-bold"
                    href="<?= (defined('BASE_URL') ? BASE_URL : '/sistem_job/') ?>controlador/controlador_cerrar_sesion.php">
                    <i class="fa-solid fa-right-from-bracket me-2"></i>Cerrar Sesión
                </a></li>
        </ul>
    </div>
</header>

<!-- Bootstrap JS MOVED TO FOOTER.PHP -->