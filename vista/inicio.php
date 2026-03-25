<?php
// Cargar configuración de entorno y sesión (4 horas)
require_once "../modelo/conexion.php";

// Evitar Caché (Back Button Security)

if (empty($_SESSION['nombre']) && empty($_SESSION['apellido'])) {
    header('Location: ' . (defined('BASE_URL') ? BASE_URL : '/sistem_job/') . 'vista/login/login.php');
    exit;
}

$mostrarBienvenida = false;
if (isset($_SESSION['primera_visita']) && $_SESSION['primera_visita'] === true) {
    $mostrarBienvenida = true;
    $_SESSION['primera_visita'] = false; // Marcar como ya visitado
}

/* ============================
   FESTEJOS DE CUMPLEAÑOS (LÓGICA)
============================ */
$cumpleanieros = [];
// Permisos: Admin, Super Admin, Oficina Personal, o permiso explícito RRHH/PERSONAL
$tieneAccesoRRHH = (
    ($_SESSION['rol'] ?? '') === 'Administrador' ||
    ($_SESSION['rol'] ?? '') === 'Super Administrador' ||
    ($_SESSION['rol'] ?? '') === 'Oficina Personal' ||
    isset($_SESSION['permisos']['RECURSOS HUMANOS']) ||
    isset($_SESSION['permisos']['PERSONAL'])
);

if ($tieneAccesoRRHH) {
    if (!class_exists('PersonalModelo')) {
        require_once "../modelo/PersonalModelo.php";
    }
    $personalModel = new PersonalModelo();
    // Buscamos cumpleaños en los próximos 10 días
    $cumpleanieros = $personalModel->obtenerCumpleanieros(10);

    // --- INTEGRACIÓN LOCADORES ---
    if (!class_exists('LocadorModelo')) {
        require_once "../modelo/LocadorModelo.php";
    }
    $locadorModel = new LocadorModelo();
    $cumplesLocadores = $locadorModel->obtenerCumpleanieros(10);

    // Fusionar y Adaptar Locadores al formato de Personal
    foreach ($cumplesLocadores as $loc) {
        $cumpleanieros[] = [
            'id_personal' => 'L-' . $loc['id'], // ID ficticio para diferenciar
            'apellidos' => $loc['apellidos'], // Usamos el campo aliased
            'nombre_grado' => $loc['nombre_grado'] ?? 'LOCADOR',
            'nombre_subunidad' => $loc['nombre_subunidad'] ?? 'Servicios No Personales',
            'proximo_cumpleanos' => $loc['proximo_cumpleanos'],
            'sexo' => $loc['sexo'] ?? 'M',
            'tipo' => 'LOCADOR'
        ];
    }

    // Reordenar por fecha de cumpleaños (merge desordena)
    usort($cumpleanieros, function ($a, $b) {
        return strtotime($a['proximo_cumpleanos']) - strtotime($b['proximo_cumpleanos']);
    });
}

// ============================
// SALUDO PERSONAL DE CUMPLEAÑOS (AUTO-FELICITACIÓN)
// ============================
$miSaludo = null;
if (!empty($_SESSION['dni'])) {
    if (!class_exists('LocadorModelo')) {
        require_once "../modelo/LocadorModelo.php";
    }
    $locModelPropio = new LocadorModelo();
    $miDatos = $locModelPropio->buscarPorDni($_SESSION['dni']);

    if ($miDatos && !empty($miDatos['fecha_nacimiento'])) {
        $nac = $miDatos['fecha_nacimiento'];
        $mesDia = date('m-d', strtotime($nac));
        $hoyY = date('Y');
        $hoy = date('Y-m-d');

        // Cumpleaños este año
        $cumpleEste = $hoyY . '-' . $mesDia;
        // Cumpleaños año pasado (para cubrir primeros días de Enero si el cumple fue Dic)
        $cumplePasado = ($hoyY - 1) . '-' . $mesDia;

        // Diferencia en días
        $diff1 = (strtotime($hoy) - strtotime($cumpleEste)) / 86400; // Días que pasaron desde el cumple actual
        $diff2 = (strtotime($hoy) - strtotime($cumplePasado)) / 86400;

        // Rango válido: Desde el día (0) hasta 3 días después (3)
        $diasPasados = -1;

        if ($diff1 >= 0 && $diff1 <= 3) {
            $diasPasados = $diff1;
        } elseif ($diff2 >= 0 && $diff2 <= 3) {
            $diasPasados = $diff2;
        }

        if ($diasPasados !== -1) {
            $miSaludo = [
                'nombre' => explode(' ', trim($miDatos['nombres_apellidos']))[0], // Primer nombre/apellido
                'es_hoy' => ($diasPasados == 0),
                'dias_pasados' => $diasPasados,
                'sexo' => $miDatos['sexo'] ?? 'M'
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Inicio - Sistema Integrado de Gestión</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
          .page-content {
            padding: 15px 30px;
            padding-top: 65px;
        }

        .welcome-card {
            background: linear-gradient(135deg, #006db3 0%, #00a8cc 100%);
            border-radius: 20px;
            padding: 0;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            max-width: 900px;
            margin: 0 auto;
        }

        .welcome-header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 15px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .welcome-icon {
            font-size: 30px;
            margin-bottom: 5px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        .welcome-title {
            color: white;
            font-size: 22px;
            font-weight: 700;
            margin: 5px 0;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .welcome-name {
            color: #fff;
            font-size: 18px;
            font-weight: 600;
            margin: 5px 0;
        }

        .welcome-role {
            margin-top: 10px;
        }

        .role-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .role-super {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(245, 87, 108, 0.4);
        }

        .role-admin {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: #333;
            box-shadow: 0 4px 15px rgba(250, 112, 154, 0.4);
        }

        .welcome-body {
            background: white;
            padding: 20px 30px;
        }

        .system-title {
            color: #2c3e50;
            font-size: 20px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 5px;
        }

        .system-subtitle {
            color: #7f8c8d;
            font-size: 14px;
            text-align: center;
            margin-bottom: 15px;
        }

        .intro-text {
            color: #34495e;
            font-size: 15px;
            text-align: center;
            margin-bottom: 15px;
        }

        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }

        .module-item {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 10px 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .module-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .module-item i {
            font-size: 20px;
            color: #006db3;
        }

        .module-item span {
            color: #2c3e50;
            font-weight: 600;
            font-size: 12px;
        }

        .footer-text {
            background: #ecf0f1;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            color: #34495e;
            font-size: 15px;
        }

        .footer-text i {
            color: #006db3;
            margin-right: 8px;
        }

        .footer-text strong {
            color: #2c3e50;
        }

    </style>
</head>

<body>
    <!-- Cargamos el topbar -->
    <?php require('./layout/topbar.php'); ?>
    <!-- Cargamos el sidebar -->
    <?php require('./layout/sidebar.php'); ?>

    <!-- Contenido principal -->
    <div class="page-content">
        <div class="container-fluid">
            <!-- Portal de Inicio Permanente -->
            <div class="welcome-card mb-5">
                <div class="welcome-header">
                    <div class="welcome-icon">
                        <?php
                        $rol = $_SESSION['rol'] ?? 'Usuario';
                        if ($rol === 'Super Administrador') {
                            echo '👑';
                        } elseif ($rol === 'Administrador') {
                            echo '⚡';
                        } else {
                            echo '👤';
                        }
                        ?>
                    </div>
                    <h2 class="welcome-title">¡Bienvenido al Sistema!</h2>
                    <h3 class="welcome-name">
                        <?= htmlspecialchars(ucwords(strtolower(($_SESSION['nombre'] ?? '') . ' ' . ($_SESSION['apellido'] ?? '')))) ?>
                    </h3>
                    <?php if ($rol === 'Super Administrador' || $rol === 'Administrador'): ?>
                        <div class="welcome-role">
                            <span class="role-badge <?= $rol === 'Super Administrador' ? 'role-super' : 'role-admin' ?>">
                                <?= htmlspecialchars($rol) ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="welcome-body">
                    <h4 class="system-title d-flex align-items-center justify-content-center gap-2">
                        Sistema Integrado de Gestión
                        <span class="badge"
                            style="background: rgba(255, 255, 255, 0.95); color: #006db3; font-size: 0.8rem; padding: 5px 12px; letter-spacing: 1.5px; font-weight: 700; border: 2px solid #00d4ff; box-shadow: 0 2px 6px rgba(0, 212, 255, 0.3);">SIG</span>
                    </h4>
                    <p class="system-subtitle">Unidad Ejecutora 009 - DIRTEPOL LIMA</p>

                    <div class="modules-info">
                        <p class="intro-text">
                            Accesos rápidos a los módulos del sistema:
                        </p>

                        <div class="modules-grid">
                            <?php
                            
                            // 1. Obtener todos los módulos activos ordenados
                            $resCards = $conexion->query("SELECT * FROM modulos WHERE activo = 1 ORDER BY orden ASC");
                            while ($m = $resCards->fetch_assoc()):
                                // 2. FILTRAR MÓDULOS DE RELLENO Y CARPETAS FANTASMA QUE ARRUINAN LA PANTALLA
                                $slugFilter = strtolower($m['slug'] ?? '');
                                $nombreFilter = mb_strtolower(trim($m['nombre']), 'UTF-8');
                                
                                if (!userCan($m['slug']) || 
                                    $slugFilter === 'acerca' || 
                                    strpos($slugFilter, 'acerca') !== false ||
                                    in_array($nombreFilter, ['módulos auxiliares', 'modulos auxiliares', 'utilitarios', 'configuración', 'configuracion'])) {
                                    continue;
                                }

                                // 3. FORZAR LOS ENLACES CORRECTOS (Evita enlaces # rotos en CPanel)
                                $raw_enlace = $m['enlace'] ?? '#';
                                
                                if ($slugFilter === 'inicio') {
                                    $raw_enlace = 'vista/inicio.php';
                                } elseif ($slugFilter === 'dashboard') {
                                    $raw_enlace = 'vista/dashboard.php';
                                } elseif ($slugFilter === 'cmn') {
                                    $raw_enlace = 'vista/cmn_clasificacion.php';
                                } elseif ($slugFilter === 'seguimiento') {
                                    $raw_enlace = 'vista/seguimiento_pac.php';
                                } elseif ($slugFilter === 'rendiciones') {
                                    $raw_enlace = 'vista/rendiciones.php';
                                } elseif ($slugFilter === 'repositorio') {
                                    $raw_enlace = 'vista/repositorio.php';
                                }

                                // 4. GENERAR ENLACE ABSOLUTO Y SEGURO
                                $href = $raw_enlace;
                                if (!empty($href) && $href !== '#' && strpos($href, 'javascript') === false) {
                                    $href = str_replace(['../', './'], '', $href);
                                    $href = ltrim($href, '/');
                                    // Dinámico para que funcione en local o cpanel sin romper el link
                                    $base_url_grid = defined('BASE_URL') ? BASE_URL : '/';
                                    if ($base_url_grid !== '/' && substr($base_url_grid, -1) !== '/') $base_url_grid .= '/';
                                    $href = $base_url_grid . $href;
                                }

                                // 5. ESTILOS ESPECIALES PARA ALGUNOS MÓDULOS (CMN Y RENDICIONES)
                                $cardStyle = "";
                                $iconStyle = "";
                                $textStyle = "";
                                
                                if ($m['slug'] === 'cmn') {
                                    $cardStyle = 'style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border: 1px solid #fde68a;"';
                                    $iconStyle = 'style="color: #d97706;"';
                                    $textStyle = 'style="color: #92400e;"';
                                } elseif ($m['slug'] === 'rendiciones') {
                                    $cardStyle = 'style="background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%); border: 1px solid #7dd3fc;"';
                                    $iconStyle = 'style="color: #0369a1;"';
                                    $textStyle = 'style="color: #075985;"';
                                }
                            ?>
                                <a href="<?= htmlspecialchars($href) ?>" class="text-decoration-none">
                                    <div class="module-item" <?= $cardStyle ?>>
                                        <i class="fa-solid <?= htmlspecialchars($m['icono']) ?>" <?= $iconStyle ?>></i>
                                        <span <?= $textStyle ?>><?= htmlspecialchars($m['nombre']) ?></span>
                                    </div>
                                </a>

                            <?php endwhile; ?>
                        </div>

                        <p class="footer-text">
                            <i class="fa-solid fa-circle-info"></i>
                            Selecciona una opción para comenzar a trabajar.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Fin del contenido principal -->

    <!-- Footer -->
    <?php require('./layout/footer.php'); ?>

    <!-- === MODAL DE CUMPLEAÑOS === -->
    <?php if (!empty($cumpleanieros)): ?>
        <?php
        // Lógica para determinar el Título y Subtítulo Dinámicos
        $hayCumpleHoy = false;
        $minDiasRestantes = 999;
        $cumplesHoyCount = 0;
        $ultimoSexoHoy = 'M'; // Por defecto masculino
    
        foreach ($cumpleanieros as $c) {
            $fechaHoy = new DateTime();
            $fechaCumple = new DateTime($c['proximo_cumpleanos']);
            $diff = $fechaHoy->diff($fechaCumple);
            $dias = (int) $diff->format("%r%a"); // %r permite ver negativos si los hubiera, %a es absoluto total días
    
            if ($dias == 0) {
                $hayCumpleHoy = true;
                $cumplesHoyCount++;
                // Capturamos el sexo (si hay varios, quedará el del último, pero en plural usamos "colegas")
                $ultimoSexoHoy = $c['sexo'] ?? 'M';
            }
            if ($dias >= 0 && $dias < $minDiasRestantes) {
                $minDiasRestantes = $dias;
            }
        }

        if ($hayCumpleHoy) {
            // NIVEL 1: CUMPLEAÑOS HOY
            $modalTitle = "¡HOY ES UN DÍA ESPECIAL!";
            if ($cumplesHoyCount > 1) {
                $modalSubtitle = "Hoy tenemos <strong>$cumplesHoyCount</strong> colegas de fiesta. ¡Es una celebración múltiple!";
            } else {
                $termino = ($ultimoSexoHoy === 'F') ? 'una compañera' : 'un compañero';
                $modalSubtitle = "Hoy celebramos el día de $termino. ¡No olvides saludar!";
            }
            $headerIcon = "fa-cake-candles";
        } elseif ($minDiasRestantes <= 5) {
            // NIVEL 2: CUENTA REGRESIVA (1 a 5 días)
            $modalTitle = "¡La fecha se acerca!";
            $modalSubtitle = "Faltan pocos días para celebrar. Ve preparando el saludo.";
            $headerIcon = "fa-hourglass-half";
        } else {
            // NIVEL 3: AVISO PREVIO (6 a 10 días)
            $modalTitle = "¡Próximas Celebraciones!";
            $modalSubtitle = "Atento a los cumpleaños que se acercan.";
            $headerIcon = "fa-calendar-day";
        }
        ?>
        <div class="modal fade" id="modalCelebracion" tabindex="-1" aria-hidden="true" style="z-index: 10600;">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 overflow-hidden"
                    style="background: rgba(20, 20, 25, 0.95); backdrop-filter: blur(20px); box-shadow: 0 0 50px rgba(0,0,0,0.8); border-radius: 24px;">

                    <!-- Header con Gradiente Dorado -->
                    <div class="position-relative p-4 text-center"
                        style="background: linear-gradient(135deg, #FFD700 0%, #B8860B 100%);">
                        <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3"
                            data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1); opacity: 0.8;"></button>

                        <div class="mb-2">
                            <i class="fa-solid <?= $headerIcon ?> fa-3x text-white drop-shadow-md animate-bounce"></i>
                        </div>
                        <h3 class="fw-bold text-white mb-0" style="text-shadow: 0 2px 4px rgba(0,0,0,0.3);">
                            <?= $modalTitle ?>
                        </h3>
                        <small class="text-white opacity-90 fw-semibold"><?= $modalSubtitle ?></small>
                    </div>

                    <!-- Cuerpo del Modal -->
                    <div class="modal-body p-0">
                        <div class="list-group list-group-flush" style="max-height: 350px; overflow-y: auto;">
                            <?php foreach ($cumpleanieros as $cumple): ?>
                                <?php
                                $meses = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                                $dia = date('d', strtotime($cumple['proximo_cumpleanos']));
                                $mes = $meses[(int) date('m', strtotime($cumple['proximo_cumpleanos']))];
                                $hoy = date('Y-m-d');
                                $esHoy = ($cumple['proximo_cumpleanos'] == $hoy);

                                // Determinar colores según Tipo y Sexo
                                $tipo = $cumple['tipo'] ?? 'PERSONAL';
                                $sexo = $cumple['sexo'] ?? 'M';

                                // Colores Base (Por defecto Personal/Gold)
                                $borderColor = '#FFD700'; // Dorado
                                $gradient = 'linear-gradient(90deg, rgba(255, 215, 0, 0.1), transparent)';
                                $textColor = 'text-warning';

                                // Diferenciación por Tipo (Locador = Plata/Cyan)
                                if ($tipo === 'LOCADOR') {
                                    $borderColor = '#00CED1'; // DarkTurquoise
                                    $gradient = 'linear-gradient(90deg, rgba(0, 206, 209, 0.1), transparent)';
                                    $textColor = 'text-info';
                                }

                                // Diferenciación por Sexo (Femenino = Rosa/Magenta)
                                if ($sexo === 'F') {
                                    // Usamos un color rosa vibrante pero elegante (HotPink/DeepPink)
                                    $textColor = 'text-white';
                                    $borderColor = '#FF1493'; // DeepPink
                                    $gradient = 'linear-gradient(90deg, rgba(255, 20, 147, 0.2), transparent)';
                                    // Badge custom
                                    $badgeColor = 'bg-pink-custom text-white'; // Se definirá style inline si bootstrap no tiene pink
                                }

                                // Aplicar estilos
                                $bgItem = $esHoy ? "background: $gradient; border-left: 4px solid $borderColor;" : 'border-left: 4px solid #444;';

                                // Badge y Icono
                                $badgeStyle = "";
                                if ($sexo === 'F') {
                                    $badgeColor = "bg-danger"; // Fallback class
                                    $badgeStyle = "background-color: #FF1493 !important; box-shadow: 0 2px 5px rgba(255, 20, 147, 0.4);";
                                } elseif ($tipo === 'LOCADOR') {
                                    $badgeColor = 'bg-info text-white';
                                } else {
                                    $badgeColor = 'bg-warning text-dark';
                                }

                                $badge = $esHoy ? "<span class=\"badge $badgeColor fw-bold shadow-sm animate-pulse\" style=\"$badgeStyle\">¡HOY!</span>" : '<span class="badge bg-secondary opacity-50">Próximamente</span>';

                                $iconClass = ($sexo === 'F') ? 'fa-person-dress' : 'fa-crown';
                                // Icono color: si es F, usamos el rosa directo style color
                                $iconStyle = ($sexo === 'F') ? 'color: #FF69B4;' : ''; // HotPink para el icono
                                $icon = $esHoy ? "<i class=\"fa-solid $iconClass me-2\" style=\"$iconStyle\" class=\"$textColor\"></i>" : '<i class="fa-regular fa-calendar text-muted me-2"></i>';

                                // Color del cuadro de fecha
                                $dateBoxColor = $esHoy ? "color: $borderColor !important;" : 'text-secondary';
                                ?>
                                <div class="list-group-item border-0 p-3 d-flex align-items-center gap-3"
                                    style="background: transparent; border-bottom: 1px solid rgba(255,255,255,0.05); <?= $bgItem ?>">
                                    <div class="d-flex flex-column align-items-center justify-content-center rounded-3 bg-dark border border-secondary border-opacity-25 shadow-sm"
                                        style="width: 60px; height: 60px; min-width: 60px;">
                                        <span class="h4 mb-0 fw-bold lh-1" style="<?= $dateBoxColor ?>"><?= $dia ?></span>
                                        <span class="text-uppercase fw-bold"
                                            style="font-size: 11px; letter-spacing: 0.5px; <?= $dateBoxColor ?>"><?= strtoupper($mes) ?></span>
                                    </div>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <h6 class="mb-0 text-white text-truncate fw-bold">
                                                <?= htmlspecialchars($cumple['apellidos'] ?? '') ?>
                                                <?php if ($tipo === 'LOCADOR'): ?>
                                                    <span class="badge bg-dark border border-secondary text-secondary ms-1"
                                                        style="font-size: 0.6rem;">LOC</span>
                                                <?php endif; ?>
                                            </h6>
                                            <?= $badge ?>
                                        </div>
                                        <div class="small text-muted text-truncate">
                                            <?= $icon ?>
                                            <?= htmlspecialchars($cumple['nombre_grado'] ?? '') ?> -
                                            <?= htmlspecialchars($cumple['nombre_subunidad'] ?? '') ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            @keyframes bounce-subtle {

                0%,
                100% {
                    transform: translateY(0);
                }

                50% {
                    transform: translateY(-5px);
                }
            }

            .animate-bounce {
                animation: bounce-subtle 2s infinite ease-in-out;
            }

            @keyframes pulse-glow {
                0% {
                    box-shadow: 0 0 0 0 rgba(255, 215, 0, 0.4);
                }

                70% {
                    box-shadow: 0 0 0 10px rgba(255, 215, 0, 0);
                }

                100% {
                    box-shadow: 0 0 0 0 rgba(255, 215, 0, 0);
                }
            }

            .animate-pulse {
                animation: pulse-glow 2s infinite;
            }

            .confetti-container {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                pointer-events: none;
                z-index: 10700;
            }
        </style>

        <script>
            document.addEventListener("DOMContentLoaded", function () {
                // Cálculo de alerta inteligente
                let minDays = 999;
                <?php
                $minD = 999;
                foreach ($cumpleanieros as $c) {
                    $diff = (new DateTime())->diff(new DateTime($c['proximo_cumpleanos']));
                    $dias = (int) $diff->format("%r%a");
                    if ($dias < $minD && $dias >= 0)
                        $minD = $dias;
                }
                echo "minDays = $minD;";
                ?>

                // LOGICA INTELIGENTE DE ALERTAS
                // Usamos la variable de sesión 'primera_visita' que el sistema ya gestiona para saber si acabas de entrar.
                const isFirstVisit = <?php echo (!empty($mostrarBienvenida) && $mostrarBienvenida) ? 'true' : 'false'; ?>;

                // Claves de almacenamiento (Solo para control de intervalos largos)
                const keyDistantGlobal = 'birthday_last_seen_timestamp';
                const lastSeenTime = parseInt(localStorage.getItem(keyDistantGlobal) || 0);

                let debeMostrar = false;

                // CASO 1: URGENTE (<= 5 días)
                // Mostrar SIEMPRE que sea la "Primera Visita" tras el Login.
                if (minDays <= 5) {
                    if (isFirstVisit) {
                        debeMostrar = true;
                    }
                }
                // CASO 2: PREVIO (10 a 6 días)
                // Mostrar si es Primera Visita Y han pasado 3 días desde el último aviso
                else if (minDays <= 10) {
                    const hoursSinceLast = (new Date().getTime() - lastSeenTime) / (1000 * 60 * 60);
                    if (isFirstVisit && hoursSinceLast > 72) {
                        debeMostrar = true;
                    }
                }

                if (debeMostrar) {
                    setTimeout(() => {
                        const modalEl = document.getElementById('modalCelebracion');
                        if (modalEl) {
                            var myModal = new bootstrap.Modal(modalEl);
                            myModal.show();

                            // Cargar script de confeti dinámicamente
                            var script = document.createElement('script');
                            script.src = "https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js";
                            script.onload = function () {
                                var duration = 3000;
                                var end = Date.now() + duration;
                                (function frame() {
                                    confetti({ particleCount: 5, angle: 60, spread: 55, origin: { x: 0 } });
                                    confetti({ particleCount: 5, angle: 120, spread: 55, origin: { x: 1 } });
                                    if (Date.now() < end) requestAnimationFrame(frame);
                                }());
                            };
                            document.head.appendChild(script);

                            // Marcar timestamp global solo si es lejano, para el control de 3 dias
                            if (minDays > 5) {
                                localStorage.setItem(keyDistantGlobal, new Date().getTime());
                            }
                        }
                    }, 1000);
                }
            });
        </script>
    <?php endif; ?>
    <!-- === MODAL AUTO-FELICITACIÓN === -->
    <?php if ($miSaludo): ?>
        <div class="modal fade" id="modalMiCumple" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg overflow-hidden"
                    style="background: linear-gradient(135deg, #FFEFBA 0%, #FFFFFF 100%);">

                    <div class="modal-body text-center p-5 position-relative">
                        <!-- Decoración Confetti CSS (Opcional si falla librería) -->
                        <div class="position-absolute top-0 start-0 w-100 h-100"
                            style="pointer-events: none; overflow: hidden; z-index: 0;">
                            <!-- Aquí se renderizará el canvas-confetti -->
                        </div>

                        <div class="position-relative" style="z-index: 2;">
                            <!-- Icono -->
                            <div class="mb-4">
                                <?php if ($miSaludo['es_hoy']): ?>
                                    <i class="fa-solid fa-cake-candles fa-4x text-warning animate-bounce drop-shadow-md"></i>
                                <?php else: ?>
                                    <i class="fa-solid fa-gift fa-4x text-success animate-bounce drop-shadow-md"></i>
                                <?php endif; ?>
                            </div>

                            <!-- Título -->
                            <h2 class="fw-bold mb-3" style="color: #d35400;">
                                <?php if ($miSaludo['es_hoy']): ?>
                                    ¡Feliz Cumpleaños, <?= htmlspecialchars($miSaludo['nombre']) ?>!
                                <?php else: ?>
                                    ¡Un poco tarde, pero... Felicidades!
                                <?php endif; ?>
                            </h2>

                            <!-- Mensaje -->
                            <p class="lead text-dark mb-4 fw-normal">
                                <?php if ($miSaludo['es_hoy']): ?>
                                    Hoy es tu día especial. Todo el equipo te desea un año lleno de éxitos, salud y alegría.
                                    <br>
                                    <strong>¡Que lo pases genial!</strong>
                                <?php else: ?>
                                    Esperamos que hayas pasado un cumpleaños increíble el día
                                    <?= ($miSaludo['dias_pasados'] == 1) ? 'ayer' : 'hace unos días' ?>.
                                    <br>¡Te deseamos lo mejor en este nuevo año de vida!
                                <?php endif; ?>
                            </p>

                            <!-- Botón -->
                            <button type="button"
                                class="btn btn-warning btn-lg fw-bold rounded-pill px-5 shadow-sm text-white"
                                data-bs-dismiss="modal" style="background: #f39c12; border: none;">
                                ¡Muchas Gracias! <i class="fa-solid fa-heart ms-2"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Script para Auto-Felicitaçao -->
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var myModal = new bootstrap.Modal(document.getElementById('modalMiCumple'));
                var anioActual = new Date().getFullYear();
                var storageKey = 'saludo_cumple_' + anioActual + '_<?= $_SESSION['dni'] ?>';

                // Verificar si ya se mostró este año
                if (!localStorage.getItem(storageKey)) {

                    // Mostrar Modal
                    setTimeout(() => {
                        myModal.show();

                        // Lanzar Confetti
                        var duration = 3 * 1000;
                        var animationEnd = Date.now() + duration;
                        var defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 9999 };

                        function random(min, max) {
                            return Math.random() * (max - min) + min;
                        }

                        var interval = setInterval(function () {
                            var timeLeft = animationEnd - Date.now();

                            if (timeLeft <= 0) {
                                return clearInterval(interval);
                            }

                            var particleCount = 50 * (timeLeft / duration);
                            // since particles fall down, start a bit higher than random
                            confetti(Object.assign({}, defaults, { particleCount, origin: { x: random(0.1, 0.3), y: Math.random() - 0.2 } }));
                            confetti(Object.assign({}, defaults, { particleCount, origin: { x: random(0.7, 0.9), y: Math.random() - 0.2 } }));
                        }, 250);

                        // Marcar como visto
                        localStorage.setItem(storageKey, 'true');

                    }, 1000); // Pequeño delay para UX
                }
            });
        </script>
    <?php endif; ?>
</body>

</html>