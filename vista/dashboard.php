<?php include __DIR__ . '/layout/topbar.php'; ?>
<?php include __DIR__ . '/layout/sidebar.php'; ?>

<style>
    /* === vista/dashboard.php === */
    /* === TEMA OSCURO GLOBAL === */
    body {
        background: #0f0f0f;
        color: #e4e4e4;
        font-family: 'Inter', sans-serif;
    }

    /* === ANIMACIÓN GENERAL === */
    .fade-in {
        animation: fadeIn .5s ease-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            translate: 0 5px;
        }

        to {
            opacity: 1;
            translate: 0;
        }
    }

    /* === TARJETAS KPI — DISEÑO PREMIUM === */
    .kpi-card {
        border-radius: 16px;
        padding: 20px;
        height: 115px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(6px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        transition: .25s ease;
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }

    /* HOVER EFECTO */
    .kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, .35);
    }

    /* ICONO */
    .kpi-card .icon {
        font-size: 28px;
        opacity: .85;
    }

    /* TEXTO PEQUEÑO */
    .kpi-card small {
        font-size: 13px;
        opacity: .75;
    }

    /* VALOR NUMÉRICO */
    .kpi-card .value {
        font-size: 28px;
        font-weight: 700;
        margin-top: 2px;
        color: #fff;
    }

    /* === BORDES NEÓN SUTILES === */
    .kpi-blue {
        border-left: 4px solid #2196f3;
        box-shadow: inset 0 0 10px #2196f366;
    }

    .kpi-green {
        border-left: 4px solid #4caf50;
        box-shadow: inset 0 0 10px #4caf5066;
    }

    .kpi-purple {
        border-left: 4px solid #ab47bc;
        box-shadow: inset 0 0 10px #ab47bc66;
    }

    .kpi-yellow {
        border-left: 4px solid #fbc02d;
        box-shadow: inset 0 0 10px #fbc02d66;
    }

    .kpi-red {
        border-left: 4px solid #e53935;
        box-shadow: inset 0 0 10px #e5393566;
    }

    .kpi-cyan {
        border-left: 4px solid #00bcd4;
        box-shadow: inset 0 0 10px #00bcd466;
    }

    .kpi-orange {
        border-left: 4px solid #ff9800;
        box-shadow: inset 0 0 10px #ff980066;
    }

    .kpi-pink {
        border-left: 4px solid #e91e63;
        box-shadow: inset 0 0 10px #e91e6366;
    }

    /* === TITULOS === */
    .section-title {
        font-size: 19px;
        font-weight: 600;
        margin-top: 25px;
        margin-bottom: 5px;
    }

    /* === CONTENEDOR DE GRÁFICOS === */
    .chart-box {
        background: #151515;
        border-radius: 14px;
        padding: 14px;
        height: 260px;
        border: 1px solid rgba(255, 255, 255, 0.07);
        box-shadow: 0 2px 10px rgba(0, 0, 0, .25);
        transition: .25s ease;
    }

    .chart-box:hover {
        transform: translateY(-2px);
    }

    /* Etiquetas dentro del box */
    .chart-box small {
        color: #dcdcdc;
        font-size: 12px;
    }

    /* FONDO DEL CANVAS */
    canvas {
        background: transparent !important;
    }

    /* === AJUSTE DE TAMAÑO DE GRÁFICOS === */
    .chart-box {
        height: 300px !important;
        /* Altura homogénea */
        position: relative;
    }

    .chart-box canvas {
        max-height: 200px !important;
        /* Limita gráficos circulares */
        margin: auto !important;
    }
</style>

<!-- ESTILOS CONDICIONALES PARA MODO CLARO/OSCURO -->
<style>
    <?php if ($metrics): ?>
        /* === MODO OSCURO (SOLO PARA DASHBOARD ACTIVO) === */
        body {
            background: #0f0f0f;
            color: #e4e4e4;
            font-family: 'Inter', sans-serif;
        }

        .section-title {
            color: #fff;
        }

    <?php else: ?>
        /* === MODO CLARO (PARA ESTADO SIN PERMISOS) === */
        body {
            background: #f4f6f9;
            /* Color estandar del sistema */
            color: #333;
            font-family: 'Inter', sans-serif;
        }

        main.page-content {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 80vh;
        }

        /* Ajustar textos para fondo claro */
        .text-white {
            color: #333 !important;
        }

        .text-light {
            color: #555 !important;
        }

        .text-muted {
            color: #6c757d !important;
        }

    <?php endif; ?>
</style>

<div id="layoutSidenav">
    <div id="layoutSidenav_content">
        <main class="page-content p-4 fade-in">

            <?php if ($metrics): ?>
                <!-- HEADER -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold mb-1 text-white">📊 Dashboard — Visión General</h2>
                        <small class="text-light opacity-75">
                            Resumen del año <?= htmlspecialchars($anio) ?>
                        </small>
                    </div>

                    <!-- SELECTOR DE AÑO -->
                    <form action="" method="GET" class="d-flex align-items-center">
                        <label for="anio" class="text-white me-2 fw-semibold">Año:</label>
                        <select name="anio" id="anio" class="form-select form-select-sm"
                            style="width: auto; background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.2);"
                            onchange="this.form.submit()">
                            <?php foreach ($anios as $a): ?>
                                <option value="<?= $a ?>" <?= $a == $anio ? 'selected' : '' ?> class="text-dark">
                                    <?= $a ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>

                <!-- === KPI CARDS UNIFICADOS === -->
                <!-- === KPI CARDS UNIFICADOS (SCROLLABLE ROW) === -->
                <!-- Usamos d-flex y overflow-auto para garantizar una sola fila con scroll si es necesario -->
                <div class="d-flex flex-nowrap gap-3 mb-4 overflow-auto pb-2"
                    style="scrollbar-width: thin; -ms-overflow-style: none;">
                    <!-- Scroll oculto en mozilla/IE si se quiere -->

                    <!-- 1. Procedimientos -->
                    <div style="min-width: 200px; flex: 1;">
                        <div class="kpi-card kpi-blue h-100">
                            <div class="icon"><i class="bi bi-files"></i></div>
                            <small>Procedimientos</small>
                            <div class="value"><?= $metrics["total_procedimientos"] ?></div>
                        </div>
                    </div>

                    <!-- 2. Total PAC -->
                    <div style="min-width: 200px; flex: 1;">
                        <div class="kpi-card kpi-green h-100">
                            <div class="icon"><i class="bi bi-cash-coin"></i></div>
                            <small>Total PAC (S/)</small>
                            <div class="value"><?= number_format($metrics["total_cuantias"], 2) ?></div>
                        </div>
                    </div>

                    <!-- 2.1 10% PAC -->
                    <div style="min-width: 200px; flex: 1;">
                        <div class="kpi-card kpi-pink h-100">
                            <div class="icon"><i class="bi bi-pie-chart"></i></div>
                            <small>10% del PAC</small>
                            <div class="value"><?= number_format($metrics["total_cuantias"] * 0.10, 2) ?></div>
                        </div>
                    </div>



                    <!-- 4. Rutinarios -->
                    <div style="min-width: 200px; flex: 1;">
                        <div class="kpi-card kpi-cyan h-100">
                            <div class="icon"><i class="bi bi-clipboard-check"></i></div>
                            <small>Rutinarios</small>
                            <div class="value"><?= $metrics["total_rutinarios"] ?></div>
                        </div>
                    </div>

                    <!-- 5. Operacionales -->
                    <div style="min-width: 200px; flex: 1;">
                        <div class="kpi-card kpi-orange h-100">
                            <div class="icon"><i class="bi bi-gear-wide-connected"></i></div>
                            <small>Operacionales</small>
                            <div class="value"><?= $metrics["total_operacionales"] ?></div>
                        </div>
                    </div>

                    <!-- 6. Críticos -->
                    <div style="min-width: 200px; flex: 1;">
                        <div class="kpi-card kpi-yellow h-100">
                            <div class="icon"><i class="bi bi-exclamation-triangle"></i></div>
                            <small>Críticos</small>
                            <div class="value"><?= $metrics["total_criticos"] ?></div>
                        </div>
                    </div>

                    <!-- 7. Estratégicos -->
                    <div style="min-width: 200px; flex: 1;">
                        <div class="kpi-card kpi-red h-100">
                            <div class="icon"><i class="bi bi-bullseye"></i></div>
                            <small>Estratégicos</small>
                            <div class="value"><?= $metrics["total_estrategicos"] ?></div>
                        </div>
                    </div>

                </div>

                <!-- (Sección de Cumpleaños Removida del Flujo Principal) -->

                <h4 class="section-title">📈 Análisis de Comportamiento</h4>

                <!-- LOS SCRIPTS DEL MODAL DE CUMPLEAÑOS SE AGREGARÁN AL FINAL DEL TUVO -->
                <?php if (!empty($cumpleanieros)): ?>
                    <!-- MODAL PREMIUM DE CELEBRACIÓN -->
                    <div class="modal fade" id="modalCelebracion" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 overflow-hidden" style="background: rgba(20, 20, 25, 0.95); backdrop-filter: blur(20px); box-shadow: 0 0 50px rgba(0,0,0,0.8); border-radius: 24px;">
                                
                                <!-- Header con Gradiente Dorado -->
                                <div class="position-relative p-4 text-center" style="background: linear-gradient(135deg, #FFD700 0%, #B8860B 100%);">
                                    <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1); opacity: 0.8;"></button>
                                    
                                    <div class="mb-2">
                                        <i class="fa-solid fa-cake-candles fa-3x text-white drop-shadow-md animate-bounce"></i>
                                    </div>
                                    <h3 class="fw-bold text-white mb-0" style="text-shadow: 0 2px 4px rgba(0,0,0,0.3);">¡Festejos Próximos!</h3>
                                    <small class="text-white opacity-90 fw-semibold">Cumpleañeros de los próximos 10 días</small>
                                </div>

                                <!-- Cuerpo del Modal -->
                                <div class="modal-body p-0">
                                    <div class="list-group list-group-flush" style="max-height: 350px; overflow-y: auto;">
                                        <?php foreach ($cumpleanieros as $cumple): ?>
                                            <?php 
                                                $meses = ['', 'Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
                                                $dia = date('d', strtotime($cumple['proximo_cumpleanos']));
                                                $mes = $meses[(int)date('m', strtotime($cumple['proximo_cumpleanos']))];
                                                $hoy = date('Y-m-d');
                                                $esHoy = ($cumple['proximo_cumpleanos'] == $hoy);
                                                
                                                // Estilos PRO por estado
                                                $bgItem = $esHoy ? 'background: linear-gradient(90deg, rgba(255, 215, 0, 0.1), transparent); border-left: 4px solid #FFD700;' : 'border-left: 4px solid #444;';
                                                $txtDate = $esHoy ? 'text-warning fw-bold' : 'text-secondary';
                                                $icon = $esHoy ? '<i class="fa-solid fa-crown text-warning me-2"></i>' : '<i class="fa-regular fa-calendar text-muted me-2"></i>';
                                                $badge = $esHoy ? '<span class="badge bg-warning text-dark fw-bold shadow-sm animate-pulse">¡HOY!</span>' : '<span class="badge bg-secondary opacity-50">Próximamente</span>';
                                            ?>
                                            <div class="list-group-item border-0 p-3 d-flex align-items-center gap-3" style="background: transparent; border-bottom: 1px solid rgba(255,255,255,0.05); <?= $bgItem ?>">
                                                
                                                <!-- Avatar / Fecha -->
                                                <div class="d-flex flex-column align-items-center justify-content-center rounded-3 bg-dark border border-secondary border-opacity-25 shadow-sm" 
                                                     style="width: 60px; height: 60px; min-width: 60px;">
                                                    <span class="h4 mb-0 fw-bold text-white lh-1"><?= $dia ?></span>
                                                    <span class="text-uppercase fw-bold <?= $esHoy ? 'text-warning' : 'text-secondary' ?>" style="font-size: 11px; letter-spacing: 0.5px;"><?= strtoupper($mes) ?></span>
                                                </div>

                                                <!-- Info -->
                                                <div class="flex-grow-1 overflow-hidden">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <h6 class="mb-0 text-white text-truncate fw-bold">
                                                            <?= htmlspecialchars($cumple['apellidos']) ?>
                                                        </h6>
                                                        <?= $badge ?>
                                                    </div>
                                                    <div class="small text-muted text-truncate">
                                                        <?= $icon ?>
                                                        <?= htmlspecialchars($cumple['nombre_grado']) ?> - <?= htmlspecialchars($cumple['nombre_subunidad']) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <!-- Footer -->
                                <div class="modal-footer border-0 justify-content-center p-3" style="background: #111;">
                                    <button type="button" class="btn btn-outline-light rounded-pill px-4 btn-sm" data-bs-dismiss="modal">
                                        Entendido, cerrar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ESTILOS Y SCRIPTS ESPECÍFICOS DEL EFECTO PRO -->
                    <style>
                        /* Animación de Rebote Suave */
                        @keyframes bounce-subtle {
                            0%, 100% { transform: translateY(0); }
                            50% { transform: translateY(-5px); }
                        }
                        .animate-bounce { animation: bounce-subtle 2s infinite ease-in-out; }
                        
                        /* Animación de Pulso */
                        @keyframes pulse-glow {
                            0% { box-shadow: 0 0 0 0 rgba(255, 215, 0, 0.4); }
                            70% { box-shadow: 0 0 0 10px rgba(255, 215, 0, 0); }
                            100% { box-shadow: 0 0 0 0 rgba(255, 215, 0, 0); }
                        }
                        .animate-pulse { animation: pulse-glow 2s infinite; }

                        /* Confetti SVG (Simple CSS implementation) */
                        .confetti-container {
                            position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 9999;
                        }
                    </style>

                    <script>
                        document.addEventListener("DOMContentLoaded", function() {
                            
                            // 1. Calcular los días restantes para el cumpleaños más cercano
                            let minDays = 999;
                            <?php 
                                $minD = 999;
                                foreach($cumpleanieros as $c) {
                                    $fHoy = new DateTime();
                                    $fCum = new DateTime($c['proximo_cumpleanos']);
                                    $diff = $fHoy->diff($fCum);
                                    // Si es hoy dif->days es 0 pero puede variar por horas, usar logica de STR
                                    $dias = (int)$diff->format("%r%a");
                                    // Si es negativo (ya pasó hoy pero el query lo trajo?) query filtra >= hoy. 0 es hoy.
                                    if($dias < $minD) $minD = $dias;
                                }
                                echo "minDays = $minD;";
                            ?>

                            // LOGICA INTELIGENTE DE ALERTAS
                            const hoyStr = '<?php echo date("Ymd"); ?>';
                            const lastSeenDate = localStorage.getItem('birthday_last_seen_date');
                            const lastSeenDaysRemaining = localStorage.getItem('birthday_last_seen_days_val'); // Para saber si ya avisamos en el rango de 10

                            let debeMostrar = false;

                            // CASO 1: Estamos en zona roja (5 días o menos) -> MOSTRAR TODOS LOS DÍAS
                            if (minDays <= 5) {
                                // Si no lo ha visto HOY, mostrar
                                if (lastSeenDate !== hoyStr) {
                                    debeMostrar = true;
                                }
                            } 
                            // CASO 2: Estamos en zona de aviso previo (10 a 6 días)
                            else if (minDays <= 10) {
                                // Mostrar SOLO SI no se ha mostrado ya para este rango de "aviso temprano" recientemente
                                // O simplificando: Mostrar solo una vez cuando entramos en este rango.
                                // Si el último visto fue hace más de 48 horas, recordamos.
                                // Pero el usuario dijo "primer alert a los 10". Vamos a hacer que avise cada 3 días en este rango.
                                
                                const lastSeenTime = parseInt(localStorage.getItem('birthday_last_seen_timestamp') || 0);
                                const nowTime = new Date().getTime();
                                const hoursSinceLast = (nowTime - lastSeenTime) / (1000 * 60 * 60);

                                if (hoursSinceLast > 72) { // Cada 3 días recordatorio suave
                                    debeMostrar = true;
                                }
                            }

                            if (debeMostrar) {
                                // Pequeño delay para dejar cargar el dashboard primero
                                setTimeout(() => {
                                    const modalEl = document.getElementById('modalCelebracion');
                                    if(modalEl) {
                                        var myModal = new bootstrap.Modal(modalEl);
                                        myModal.show();
                                        lanzarConfeti();
                                        
                                        // Guardar marca de tiempo
                                        localStorage.setItem('birthday_last_seen_date', hoyStr);
                                        localStorage.setItem('birthday_last_seen_timestamp', new Date().getTime());
                                    }
                                }, 1500);
                            }
                        });


                        function lanzarConfeti() {
                            // Usaremos una librería ligera vía CDN si es posible, o una implementación simple
                            // Para asegurar efecto PRO sin dependencias pesadas, inyectamos librería ligera: canvas-confetti
                            var script = document.createElement('script');
                            script.src = "https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js";
                            script.onload = function() {
                                var duration = 3 * 1000;
                                var animationEnd = Date.now() + duration;
                                var defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 10600 };

                                function randomInRange(min, max) { return Math.random() * (max - min) + min; }

                                var interval = setInterval(function() {
                                    var timeLeft = animationEnd - Date.now();
                                    if (timeLeft <= 0) { return clearInterval(interval); }
                                    var particleCount = 50 * (timeLeft / duration);
                                    
                                    // Disparar desde dos esquinas
                                    confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 } }));
                                    confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 } }));
                                }, 250);
                            };
                            document.head.appendChild(script);
                        }
                    </script>
                <?php endif; ?>

                <!-- === GRID SUPERIOR (LINE CHART + TOP 5) === -->
                <div class="row g-3">

                    <!-- Columna Izquierda: Gráfico de Líneas (Procedimientos por Mes) -->
                    <div class="col-md-8">
                        <div class="chart-box" style="height: 340px;">
                            <small>Procedimientos por Mes</small>
                            <canvas id="chartMes"></canvas>
                        </div>
                    </div>

                    <!-- Columna Derecha: Top 5 Mayores Cuantías -->
                    <div class="col-md-4">
                        <div class="chart-box" style="height: 340px; overflow-y: auto;">
                            <small class="mb-2 d-block">🏆 Top 5 Mayores Inversiones</small>
                            <table class="table table-sm table-dark table-borderless" style="font-size: 12px;">
                                <thead>
                                    <tr class="text-secondary">
                                        <th>Descripción</th>
                                        <th class="text-end">Monto (S/)</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($metrics["top_5"])): ?>
                                        <?php foreach ($metrics["top_5"] as $top): ?>
                                            <tr>
                                                <td class="text-truncate" style="max-width: 120px;"
                                                    title="<?= htmlspecialchars($top['descripcion']) ?>">
                                                    <?= htmlspecialchars($top['descripcion']) ?>
                                                </td>
                                                <td class="text-end text-info fw-bold">
                                                    <?= number_format($top['cuantia'], 2) ?>
                                                </td>
                                                <td class="text-end">
                                                    <?php
                                                    $badgeCls = match ($top['resultado_segmentacion']) {
                                                        'Crítico' => 'text-warning',
                                                        'Estratégico' => 'text-danger',
                                                        default => 'text-success'
                                                    };
                                                    ?>
                                                    <i class="bi bi-circle-fill <?= $badgeCls ?>" style="font-size: 8px;"></i>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">Sin datos</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <!-- === GRID INFERIOR (3 COLUMNAS) === -->
                <div class="row g-3 mt-1">

                    <div class="col-md-4">
                        <div class="chart-box">
                            <small>Resultados por Categoría</small>
                            <canvas id="chartResultados"></canvas>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="chart-box">
                            <small>Riesgo (Alto/Bajo)</small>
                            <canvas id="chartRiesgo"></canvas>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="chart-box">
                            <small>Cuantía (Alta/Baja)</small>
                            <canvas id="chartCuantia"></canvas>
                        </div>
                    </div>

                </div>

            <?php else: ?>
                <!-- WELCOME STATE (No Dashboard Permissions) -->
                <div class="text-center fade-in bg-white rounded-4 shadow-sm p-5 border0"
                    style="max-width: 650px; width: 100%;">
                    <div class="mb-4 d-inline-block text-primary bg-primary bg-opacity-10 p-4 rounded-circle">
                        <i class="fa-solid fa-layer-group fa-4x"></i>
                    </div>
                    <h2 class="fw-light text-secondary mb-0">Bienvenido,</h2>
                    <h1 class="fw-normal text-dark mb-3">
                        <?= htmlspecialchars(ucwords(strtolower(($_SESSION['nombre'] ?? '') . ' ' . ($_SESSION['apellido'] ?? '')))) ?>
                    </h1>
                    <h5 class="text-secondary fw-normal mb-5">Sistema Integrado de Gestión - UE009</h5>

                    <div class="card bg-light border-0 p-3 text-start">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 text-primary">
                                <i class="fa-solid fa-circle-info fa-2x"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="fw-bold mb-1 text-dark">¿Cómo comenzar?</h6>
                                <p class="mb-0 text-muted small">
                                    Utiliza el <strong class="text-primary">menú lateral</strong> para acceder a las
                                    herramientas y módulos que han sido habilitados para tu perfil de usuario.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php include __DIR__ . '/layout/footer.php'; ?>
    </div>
</div>

<!-- === CHART JS === -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    let porMes = <?= json_encode($metrics["por_mes"]) ?>;
    let resultados = <?= json_encode($metrics["por_resultado"]) ?>;
    let riesgo = <?= json_encode($metrics["por_riesgo"]) ?>;
    let cuantia = <?= json_encode($metrics["por_cuantia"]) ?>;

    /* --- UTIL: Crear Gradiente --- */
    function getGradient(ctx, colorStart, colorEnd) {
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, colorStart);
        gradient.addColorStop(1, colorEnd);
        return gradient;
    }

    /* ESTILO GENERAL */
    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { labels: { color: "#ccc", font: { size: 10 } } }
        }
    };

    /* === 1. LINE CHART (Procedimientos) === */
    const ctxMes = document.getElementById("chartMes").getContext("2d");
    const gradMes = getGradient(ctxMes, "rgba(66, 165, 245, 0.5)", "rgba(66, 165, 245, 0.0)");

    new Chart(ctxMes, {
        type: "line",
        data: {
            labels: porMes.map(r => r.mes),
            datasets: [{
                label: "Procedimientos",
                data: porMes.map(r => r.total),
                borderColor: "#42a5f5",
                backgroundColor: gradMes,
                borderWidth: 2,
                pointBackgroundColor: "#fff",
                pointBorderColor: "#42a5f5",
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            ...commonOptions,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { color: "#aaa", stepSize: 1 },
                    grid: { color: "rgba(255,255,255,0.05)" }
                },
                x: {
                    ticks: { color: "#aaa" },
                    grid: { display: false }
                }
            }
        }
    });


    /* === 2. BAR CHART (Resultados) === */
    const ctxRes = document.getElementById("chartResultados").getContext("2d");

    // Preparar datos ordenados
    const categorias = ["Rutinario", "Operacional", "Crítico", "Estratégico"];
    const colores = {
        "rutinario": ["#66bb6a", "#43a047"],
        "operacional": ["#42a5f5", "#1e88e5"],
        "critico": ["#ffee58", "#fdd835"],
        "estrategico": ["#ef5350", "#e53935"]
    };

    let dataMap = {};
    resultados.forEach(r => dataMap[r.resultado.trim()] = r.total);

    const dataValues = categorias.map(c => dataMap[c] || dataMap[c.toUpperCase()] || 0);
    const bgColors = categorias.map(c => {
        let key = c.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        return colores[key] ? colores[key][1] : "#ccc";
    });

    new Chart(ctxRes, {
        type: "bar",
        data: {
            labels: categorias,
            datasets: [{
                label: "Cantidad",
                data: dataValues,
                backgroundColor: bgColors,
                borderRadius: 4,
                barPercentage: 0.6
            }]
        },
        options: {
            ...commonOptions,
            plugins: {
                legend: { display: false } // <--- Ocultar leyenda redundante
            },
            scales: {
                y: { display: false },
                x: { ticks: { color: "#aaa" }, grid: { display: false } }
            }
        }
    });

    /* === 3. PIE CHART (Riesgo) === */
    new Chart(document.getElementById("chartRiesgo"), {
        type: "pie",
        data: {
            labels: riesgo.map(r => r.riesgo),
            datasets: [{
                data: riesgo.map(r => r.total),
                backgroundColor: ["#66bb6a", "#ef5350"],
                borderWidth: 0
            }]
        },
        options: commonOptions
    });

    /* === 4. DOUGHNUT (Cuantía) === */
    new Chart(document.getElementById("chartCuantia"), {
        type: "doughnut",
        data: {
            labels: cuantia.map(r => r.categoria),
            datasets: [{
                data: cuantia.map(r => r.total),
                backgroundColor: ["#ab47bc", "#26c6da"],
                borderWidth: 0
            }]
        },
        options: {
            ...commonOptions,
            cutout: "60%"
        }
    });
</script>