<!-- ========== footer.php ========== -->

<!-- ========== Footer Visual ========== -->
<style>
    /* Footer Fijo o al final del contenido */
    .site-footer {
        background-color: #03526e;
        /* Color corporativo/teal oscuro */
        color: #fff;
        padding: 10px 0;
        margin-top: auto;
        font-size: 0.85rem;
        width: 100%;
    }

    /* Asegurar que el body o wrapper principal use flex column para empujar el footer */
    body {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    .page-content {
        flex: 1;
        /* Esto hace que el contenido empuje el footer al fondo */
    }
</style>

<footer class="site-footer mt-auto">
    <div class="container-fluid text-center">
        <small>© <?= date('Y') ?> <strong>Omar Jara Mendoza</strong>. Todos los derechos reservados.</small>
    </div>
</footer>

<!-- ========== Dependencias y Scripts ========== -->
<script src="<?= (defined('BASE_URL') ? BASE_URL : '/sistem_job/') ?>public/app/publico/js/plugins.js"></script>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Parche de compatibilidad para Bootstrap 5 con jQuery (Evita error de tooltips) -->
<script>
    if (typeof bootstrap !== 'undefined' && typeof jQuery !== 'undefined' && !jQuery.fn.tooltip) {
        jQuery.fn.tooltip = function(options) {
            return this.each(function() { new bootstrap.Tooltip(this, options); });
        };
        jQuery.fn.popover = function(options) {
            return this.each(function() { new bootstrap.Popover(this, options); });
        };
    }
</script>

<script src="<?= (defined('BASE_URL') ? BASE_URL : '/sistem_job/') ?>public/app/publico/js/app.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<!-- ========== Script del sidebar y topbar ========== -->

<!-- ========== Script del sidebar y topbar ========== -->
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const sidebar = document.getElementById("sidebar");
        const toggleBtn = document.getElementById("toggleSidebar");
        const pageContent = document.querySelector(".page-content");
        const submenus = document.querySelectorAll(".submenu-toggle");

        if (pageContent) {
            // Removed manual style setting to rely on CSS classes
            // pageContent.style.marginTop = "70px"; - handled by padding-top
            // pageContent.style.marginLeft = "230px"; - handled by CSS class
        }

        toggleBtn?.addEventListener("click", () => {
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle("open");
            } else {
                sidebar.classList.toggle("collapsed");
            }
        });

        const current = location.pathname.split("/").pop();
        document.querySelectorAll(".sidebar a[href]").forEach(a => {
            if (a.getAttribute("href") === current) a.classList.add("active-link");
        });
    });

    // ============================================
    // Script de Inactividad (Session Timeout)
    // ============================================
    (function () {
        // TIEMPOS EN MILISEGUNDOS
        // Configuración 4 HORAS (Sincronizado con PHP)
        const LOGOUT_TIME = <?= (defined('INACTIVITY_LIMIT') ? INACTIVITY_LIMIT : 14400) ?> * 1000; 
        const WARNING_TIME = LOGOUT_TIME - (2 * 60 * 1000); // 2 minutos antes del cierre

        let warningTimer;
        let logoutTimer;

        function resetTimers() {
            clearTimeout(warningTimer);
            clearTimeout(logoutTimer);

            warningTimer = setTimeout(showWarning, WARNING_TIME);
            logoutTimer = setTimeout(logoutUser, LOGOUT_TIME);
        }

        function showWarning() {
            let timeLeft = (LOGOUT_TIME - WARNING_TIME) / 1000;

            Swal.fire({
                title: '¿Sigues ahí?',
                text: `Tu sesión se cerrará pronto por inactividad.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, continuar trabajando',
                cancelButtonText: 'No, salir',
                timer: (LOGOUT_TIME - WARNING_TIME),
                timerProgressBar: true,
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    renovarSesion();
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    logoutUser();
                } else if (result.dismiss === Swal.DismissReason.timer) {
                    logoutUser();
                }
            });
        }

        function logoutUser() {
            const base = "<?= (defined('BASE_URL') ? BASE_URL : '/sistem_job/') ?>";
            window.location.href = base + 'controlador/logout.php?reason=timeout';
        }

        function renovarSesion() {
            const base = "<?= (defined('BASE_URL') ? BASE_URL : '/sistem_job/') ?>";
            fetch(base + 'controlador/renovar_sesion.php')
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'ok') {
                        resetTimers();
                    } else {
                        logoutUser();
                    }
                })
                .catch(err => console.error(err));
        }

        // Eventos para detectar actividad
        ['click', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(evt => {
            document.addEventListener(evt, () => {
                // Solo reseteamos si NO hay una alerta visible
                // Si la alerta está visible, el usuario DEBE interactuar con ella
                if (!Swal.isVisible()) {
                    resetTimers();
                }
            });
        });

        // Iniciar timers al cargar
        resetTimers();
    })();
</script>


</body>

</html>