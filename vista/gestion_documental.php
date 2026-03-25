<?php
// vista/gestion_documental.php
declare(strict_types=1);

// 1. Cargar conexión y sesión (Centralizado)
require_once __DIR__ . '/../modelo/conexion.php';

if (empty($_SESSION['id'])) {
    header("Location: " . (defined('BASE_URL') ? BASE_URL : '/sistem_job/') . "vista/login/login.php");
    exit();
}

// 2. Permisos (Explicit Sync)
require_once __DIR__ . '/../controlador/autocargar_permisos.php';
if (isset($conn) && $conn instanceof mysqli) {
    recargarPermisosUsuario($_SESSION['id'], $conn);
} elseif (isset($conexion) && $conexion instanceof mysqli) {
    recargarPermisosUsuario($_SESSION['id'], $conexion);
}

// 2. Seguridad Estricta (Bloqueo VER)
if ($_SESSION['rol'] !== 'Administrador' && $_SESSION['rol'] !== 'Super Administrador' && !isset($_SESSION['permisos']['GESTION_DOCUMENTAL']['VER'])) {
    // Si no tiene permiso VER, expulsar
    echo '<!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Acceso Denegado</title>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body style="background-color: #f4f6f9; font-family: sans-serif;">
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    title: "Acceso Denegado",
                    text: "No tienes permisos para visualizar este módulo.",
                    icon: "error",
                    confirmButtonColor: "#3085d6",
                    confirmButtonText: "Volver al Dashboard",
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = "../controlador/dashboard.php";
                    }
                });
            });
        </script>
    </body>
    </html>';
    exit();
}

// 1. Incluir Topbar (Trae <head>, CSS global, jQuery, y apertura de <body>)
// Nota: topbar.php abre <html> y <body>, y cierra al final. 
// Browsers modernos manejarán el contenido extra, pero idealmente deberíamos limpiar esto.
include 'layout/topbar.php';

// 2. Incluir Sidebar
include 'layout/sidebar.php';

// Determinar permisos para JS y botones
$is_super = ($_SESSION['rol'] === 'Super Administrador');
$pCrear = ($is_super || isset($_SESSION['permisos']['GESTION_DOCUMENTAL']['CREAR'])) ? 'true' : 'false';
$pEditar = ($is_super || isset($_SESSION['permisos']['GESTION_DOCUMENTAL']['EDITAR'])) ? 'true' : 'false';
$pEliminar = ($is_super || isset($_SESSION['permisos']['GESTION_DOCUMENTAL']['ELIMINAR'])) ? 'true' : 'false';
$pImportar = ($is_super || isset($_SESSION['permisos']['GESTION_DOCUMENTAL']['IMPORTAR'])) ? 'true' : 'false';

// Siempre mostraremos las acciones para poder pintar los botones de "no permitido"
$mostrarAcciones = 'true';
?>
<script>
    const PERMISO_EDITAR = <?= $pEditar ?>;
    const PERMISO_ELIMINAR = <?= $pEliminar ?>;
    const MOSTRAR_ACCIONES = <?= $mostrarAcciones ?>;
</script>
<?php // Re-open PHP if needed, but the file continues as HTML mixed
?>

<style>
    /* Estilos adicionales para gestión documental */
    .site-footer {
        background-color: #006080;
        /* Mismo color que el sidebar */
        color: #fff;
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        padding: 5px 0;
        /* Más delgado */
        font-size: 0.8rem;
        text-align: center;
        z-index: 1000;
        /* Debajo del sidebar (1020) si se solapan */
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* Ajuste para que el footer fijo no tape el contenido final */
    body {
        padding-bottom: 40px;
    }
</style>

<!-- Contenido Principal -->
<!-- Usamos 'page-content' con padding-top para compensar topbar fijo (aprox 70px) -->
<div class="page-content" style="padding-top: 80px; padding-left: 20px; padding-right: 20px;">
    <div class="container-fluid">
        <!-- Estilos Específicos para esta Barra (Fail-safe) -->
        <style>
            .sis-nav-container {
                background-color: #00779e !important;
                /* Revertido al Teal original */
                color: #ffffff !important;
                border-radius: 8px;
                padding: 15px;
                margin-bottom: 20px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }

            .sis-title {
                margin: 0;
                font-weight: 700;
                color: #ffffff !important;
                font-size: 1.25rem;
            }

            .sis-btn-nav {
                text-decoration: none;
                font-weight: 600;
                padding: 6px 15px;
                border-radius: 5px;
                transition: all 0.2s;
                font-size: 0.9rem;
            }

            /* Botón Nuevo Documento - VERDE BRILLANTE LLAMATIVO */
            .sis-btn-new {
                background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
                color: #ffffff !important;
                border: 2px solid #ffffff;
                box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
                font-weight: 700 !important;
                font-size: 1rem !important;
                padding: 8px 20px !important;
                animation: pulse-green 2s infinite;
            }

            @keyframes pulse-green {

                0%,
                100% {
                    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
                }

                50% {
                    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.6);
                }
            }

            .sis-btn-new:hover {
                background: linear-gradient(135deg, #059669 0%, #047857 100%) !important;
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(16, 185, 129, 0.6);
            }

            /* Botón Activo (Bandeja) */
            .sis-btn-active {
                background-color: rgba(255, 255, 255, 0.25) !important;
                color: #ffffff !important;
                border: 1px solid rgba(255, 255, 255, 0.5);
            }

            /* Botones Inactivos */
            .sis-btn-link {
                color: rgba(255, 255, 255, 0.8) !important;
                border: 1px solid transparent;
            }

            .sis-btn-link:hover {
                background-color: rgba(255, 255, 255, 0.1);
                color: #ffffff !important;
            }

            /* Estilos Custom para los Tabs (Filtrar intensidad) */
            .nav-pills .nav-link.active {
                background-color: #00779e !important;
                /* Mismo Teal de la marca */
                color: #fff !important;
            }

            .nav-pills .nav-link {
                color: #555;
                /* Gris suave para inactivos */
                font-weight: 600;
            }

            .nav-pills .nav-link:hover {
                color: #00779e;
                background-color: #e6f7fa;
            }

            /* Estilos para botones de acción */
            .edit-doc-btn:hover {
                background-color: #2563eb !important;
                transform: translateY(-1px);
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }

            .delete-doc-btn:hover {
                background-color: #dc2626 !important;
                transform: translateY(-1px);
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }

            /* Mejorar el campo de búsqueda de DataTables */
            /* Sobrescribir el grid de Bootstrap que usa DataTables */
            .dataTables_wrapper .row:first-child {
                display: block !important;
            }

            .dataTables_wrapper .row:first-child>div {
                width: 100% !important;
                max-width: 100% !important;
                flex: none !important;
            }

            .dataTables_wrapper .dataTables_length {
                float: left !important;
                display: inline-block !important;
                margin-right: 30px !important;
                width: auto !important;
                vertical-align: middle !important;
            }

            .dataTables_wrapper .dataTables_filter {
                float: left !important;
                display: inline-block !important;
                text-align: left !important;
                width: auto !important;
                vertical-align: middle !important;
            }

            .dataTables_wrapper .dataTables_filter label {
                font-weight: 600 !important;
                color: #333 !important;
                font-size: 14px !important;
                display: flex !important;
                align-items: center !important;
                gap: 10px !important;
                margin-bottom: 0 !important;
                white-space: nowrap !important;
            }

            .dataTables_wrapper .dataTables_filter input {
                width: 280px !important;
                padding: 8px 15px !important;
                border: 1px solid #dee2e6 !important;
                border-radius: 6px !important;
                font-size: 14px !important;
                margin-left: 0 !important;
                transition: all 0.3s ease !important;
            }

            .dataTables_wrapper .dataTables_filter input:focus {
                outline: none !important;
                border-color: #86b7fe !important;
                box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
            }

            .dataTables_wrapper .dataTables_length label {
                font-weight: 600 !important;
                color: #333 !important;
                font-size: 14px !important;
                margin-bottom: 0 !important;
                white-space: nowrap !important;
                display: flex !important;
                align-items: center !important;
                gap: 5px !important;
            }

            /* Asegurar que ambos controles estén en la misma línea */
            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter {
                margin-bottom: 15px !important;
            }

            /* Limpiar floats */
            .dataTables_wrapper .row:first-child::after {
                content: "";
                display: table;
                clear: both;
            }

            /* Estilo profesional para el encabezado de la tabla */
            table.dataTable thead th {
                background-color: #3f454d !important;
                color: white !important;
                font-weight: 600 !important;
                text-transform: uppercase !important;
                font-size: 0.65rem !important;
                letter-spacing: 0.4px !important;
                padding: 6px 3px !important;
                border-bottom: 2px solid #23272b !important;
                vertical-align: middle !important;
                text-align: center !important;
                position: sticky !important;
                top: 0 !important;
                z-index: 10 !important;
            }

            /* Hover effect en el encabezado */
            table.dataTable thead th:hover {
                background-color: #5a6268 !important;
            }

            /* Estilo para las filas de la tabla */
            table.dataTable tbody tr {
                transition: background-color 0.2s ease;
            }

            table.dataTable tbody tr:hover {
                background-color: #f8f9fa !important;
            }

            /* Bordes de las celdas - Más compacto para mostrar 10 registros */
            table.dataTable td {
                border-bottom: 1px solid #e9ecef !important;
                vertical-align: middle !important;
                text-align: center !important;
                padding: 3px 2px !important;
                font-size: 0.72rem !important;
                line-height: 1.2 !important;
                color: #1e293b !important; /* Texto oscuro */
                font-weight: normal !important; /* Quitamos la negrita de todo */
            }

            /* Negrita SOLO para la primera columna N° DOCUMENTO */
            table.dataTable td:first-child {
                font-weight: 600 !important;
                color: #000 !important;
            }

            /* Reducir padding del encabezado también */
            table.dataTable thead th {
                padding: 6px 4px !important;
            }

            /* Fix para permitir sticky header */
            .table-responsive {
                overflow-x: auto !important;
                overflow-y: visible !important;
            }

            /* Estilos personalizados para tooltips - Diseño profesional celeste */
            .tooltip {
                z-index: 9999 !important;
            }

            .tooltip-inner {
                background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%) !important;
                color: #0c4a6e !important;
                text-align: justify !important;
                max-width: 450px !important;
                padding: 12px 16px !important;
                font-size: 0.875rem !important;
                line-height: 1.6 !important;
                border: 1px solid #0ea5e9 !important;
                border-radius: 8px !important;
                box-shadow: 0 4px 12px rgba(14, 165, 233, 0.25) !important;
                font-weight: 500 !important;
            }

            .tooltip-arrow::before {
                border-top-color: #0ea5e9 !important;
                border-bottom-color: #0ea5e9 !important;
            }

            /* Justificar texto en columna ASUNTO */
            table.dataTable td:nth-child(6) div {
                text-align: justify !important;
            }
        </style>

        <!-- Barra de Navegación Personalizada -->
        <div class="d-flex align-items-center justify-content-between sis-nav-container">
            <div class="d-flex align-items-center">
                <h4 class="sis-title me-4"><i class="fa-solid fa-file-contract me-2"></i>SisDocumentos</h4>

                <div class="d-flex gap-2">
                    <?php if ($pCrear === 'true'): ?>
                    <!-- Botón Nuevo -->
                    <a href="documento_nuevo.php" class="sis-btn-nav sis-btn-new">
                        <i class="fa-solid fa-circle-plus me-1"></i> Nuevo Documento
                    </a>
                    <?php endif; ?>

                    <!-- Botón Bandeja (Activo) -->
                    <a href="gestion_documental.php" class="sis-btn-nav sis-btn-active">
                        <i class="fa-solid fa-list me-1"></i> Bandeja
                    </a>

                    <?php if ($pImportar === 'true'): ?>
                    <!-- Botón Importar (Nuevo) -->
                    <a href="importar_datos.php" class="sis-btn-nav sis-btn-link" title="Importación Masiva">
                        <i class="fa-solid fa-file-import me-1"></i> Importar
                    </a>
                    <?php endif; ?>

                    <!-- Botón Reportes (Dropdown) -->
                    <div class="dropdown d-inline-block">
                        <a class="sis-btn-nav sis-btn-link dropdown-toggle" href="#" role="button" id="btnReportes"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-chart-line me-1"></i> Reportes
                        </a>
                        <ul class="dropdown-menu shadow" aria-labelledby="btnReportes">
                            <li><a class="dropdown-item" href="#" onclick="descargarReporte('excel')"><i
                                        class="fa-solid fa-file-excel text-success me-2"></i> Reporte Excel</a></li>
                            <li><a class="dropdown-item" href="#" onclick="descargarReporte('pdf')"><i
                                        class="fa-solid fa-file-pdf text-danger me-2"></i> Reporte PDF</a></li>
                        </ul>
                    </div>

                    <!-- Botón Tutorial PDF -->
                    <a href="../controlador/generar_manual_documento_pdf.php" target="_blank" class="sis-btn-nav sis-btn-link" 
                       style="background: transparent; border: none; color: #ffffff !important; transition: all 0.3s ease;"
                       onmouseover="this.style.textShadow='0 0 8px rgba(255,255,255,0.5)';"
                       onmouseout="this.style.textShadow='none';"
                       title="Ver Manual de Usuario de Gestión Documental">
                        <i class="fas fa-book-open me-1 text-warning"></i> Tutorial
                    </a>
                </div>
            </div>

            <!-- Selector de Año y Rango de Fechas -->
            <div class="d-flex align-items-center gap-2">
                <!-- Filtro Fechas -->
                <div class="d-flex align-items-center bg-white rounded px-2 py-1 shadow-sm" style="gap: 5px;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-transparent border-0 pe-1 text-muted"><i class="fa-solid fa-calendar-day"></i></span>
                        <input type="date" class="form-control form-control-sm border-0 ps-0" id="fechaInicio" title="Fecha Inicio" style="max-width: 110px;">
                    </div>
                    <span class="text-muted fw-bold">-</span>
                    <div class="input-group input-group-sm">
                        <input type="date" class="form-control form-control-sm border-0" id="fechaFin" title="Fecha Fin" style="max-width: 110px;">
                    </div>
                    <button class="btn btn-sm btn-primary px-2" onclick="cargarTabla()" title="Aplicar Filtro">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary px-2 border-0" onclick="limpiarFechas()" title="Limpiar">
                        <i class="fa-solid fa-eraser"></i>
                    </button>
                    
                    <div class="vr mx-1"></div>
                    
                    <button class="btn btn-sm btn-success px-2" onclick="descargarReporte('excel')" title="Descargar Excel Filtrado">
                        <i class="fa-solid fa-file-excel"></i>
                    </button>
                    <button class="btn btn-sm btn-danger px-2" onclick="descargarReporte('pdf')" title="Descargar PDF Filtrado">
                        <i class="fa-solid fa-file-pdf"></i>
                    </button>
                </div>

                <div class="border-start mx-2 h-50 border-white-50"></div>

                <label class="form-label mb-0 fw-bold text-white" style="font-size: 0.9rem;">Año:</label>
                <select class="form-select form-select-sm" id="filtroAnio" onchange="cargarTabla()"
                    style="width: 80px;">
                    <?php for ($y = date('Y') + 1; $y >= 2024; $y--): ?>
                        <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>


        <style>
            /* Colores específicos para los Tabs */
            .nav-pills .nav-link {
                border-bottom: 3px solid transparent;
                border-radius: 0;
            }

            .nav-link.tab-oficio.active,
            .nav-link.tab-oficio:hover {
                border-bottom-color: #28a745 !important;
                color: #28a745 !important;
                background: transparent !important;
            }

            .nav-link.tab-informe.active,
            .nav-link.tab-informe:hover {
                border-bottom-color: #a71d2a !important;
                color: #a71d2a !important;
                background: transparent !important;
            }

            .nav-link.tab-orden.active,
            .nav-link.tab-orden:hover {
                border-bottom-color: #ff00ff !important;
                color: #ff00ff !important;
                background: transparent !important;
            }

            .nav-link.tab-memo.active,
            .nav-link.tab-memo:hover {
                border-bottom-color: #007bff !important;
                color: #007bff !important;
                background: transparent !important;
            }

            /* Reducir espacios verticales para mostrar más registros */
            .sis-nav-container {
                margin-bottom: 5px !important;
                padding: 8px 15px !important;
            }

            .nav-pills {
                margin-bottom: 5px !important;
                padding-top: 0 !important;
            }

            .nav-pills .nav-link {
                padding: 8px 16px !important;
            }

            .card {
                margin-bottom: 0 !important;
            }

            .card-body {
                padding: 10px !important;
            }

            .nav-pills .nav-link.custom-tab.active {
                background-color: transparent !important;
            }
        </style>

        <!-- Filtros Rápidos (Tipo Excel Tabs) -->
        <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" onclick="cambiarFiltroTipo('')" data-bs-toggle="pill">TODOS</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link custom-tab tab-oficio fw-bold" onclick="cambiarFiltroTipo('OFICIO')"
                    data-bs-toggle="pill">OFICIOS</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link custom-tab tab-informe fw-bold" onclick="cambiarFiltroTipo('INFORME')"
                    data-bs-toggle="pill">INFORMES</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link custom-tab tab-orden fw-bold" onclick="cambiarFiltroTipo('ORDEN TELEFONICA')"
                    data-bs-toggle="pill">ORDEN TELEFÓNICA</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link custom-tab tab-memo fw-bold" onclick="cambiarFiltroTipo('MEMORANDUM')"
                    data-bs-toggle="pill">MEMORANDUM</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold" onclick="cambiarFiltroTipo('OTROS')"
                    data-bs-toggle="pill">OTROS</button>
            </li>
        </ul>

        <!-- Tabla -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tablaDocumentos"
                        class="table table-hover table-striped table-bordered align-middle text-center"
                        style="width:100%; font-size: 0.9rem;">
                        <thead class="bg-dark text-white">
                            <tr>
                                <th style="width: 150px;">N° Documento</th>
                                <th>HT / Ref</th>
                                <th style="width: 90px;">Fecha</th>
                                <th>Clasif. (Se Solicita)</th>
                                <th>Destino</th>
                                <th style="width: 30%;">Asunto</th>
                                <th>Formulado Por</th>
                                <th>Obs.</th>

                                <?php if ($mostrarAcciones === 'true'): ?>
                                    <th style="width: 100px;">Acciones</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($pCrear === 'true'): ?>
<!-- Botón Flotante (FAB) para Nuevo Documento -->
<a href="documento_nuevo.php" id="fabNuevoDoc" class="fab-button" title="Nuevo Documento (Ctrl+N)">
    <i class="fa-solid fa-plus"></i>
</a>
<?php endif; ?>

<style>
    /* Botón Flotante (FAB) - Verde Brillante */
    .fab-button {
        position: fixed;
        bottom: 80px;
        right: 30px;
        width: 65px;
        height: 65px;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 24px;
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.5);
        text-decoration: none;
        transition: all 0.3s ease;
        z-index: 1000;
        animation: fab-pulse 2s infinite, fab-bounce 3s ease-in-out infinite;
    }

    @keyframes fab-pulse {

        0%,
        100% {
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.5);
        }

        50% {
            box-shadow: 0 8px 30px rgba(16, 185, 129, 0.8);
        }
    }

    @keyframes fab-bounce {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-10px);
        }
    }

    .fab-button:hover {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        transform: scale(1.1) rotate(90deg);
        box-shadow: 0 8px 30px rgba(16, 185, 129, 0.8);
    }

    .fab-button i {
        transition: transform 0.3s ease;
    }

    .fab-button:hover i {
        transform: rotate(-90deg);
    }
</style>

<script>
    // Atajo de teclado Ctrl+N para Nuevo Documento
    document.addEventListener('keydown', function (e) {
        if (e.ctrlKey && e.key === 'n') {
            e.preventDefault();
            window.location.href = 'documento_nuevo.php';
        }
    });
</script>


<!-- Scripts Extras -->
<!-- Bootstrap JS se carga en topbar.php para evitar duplicados -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>



<script>
    let tabla;
    let currentTipo = '';

    $(document).ready(function () {
        cargarTabla();
    });

    function cambiarFiltroTipo(tipo) {
        currentTipo = tipo;
        cargarTabla();
    }

    function limpiarFechas() {
        $('#fechaInicio').val('');
        $('#fechaFin').val('');
        cargarTabla();
    }

    function cargarTabla() {
        const anio = document.getElementById('filtroAnio').value;
        const fInicio = document.getElementById('fechaInicio').value;
        const fFin = document.getElementById('fechaFin').value;
        
        // Construimos URL dinámica
        let url = `../controlador/DocumentoControlador.php?op=listar_v2&anio=${anio}&tipo=${currentTipo}&_t=${new Date().getTime()}`;
        if(fInicio && fFin) {
            url += `&fecha_inicio=${fInicio}&fecha_fin=${fFin}`;
            
            // Feedback
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'info',
                title: 'Filtrando por fechas...',
                showConfirmButton: false,
                timer: 1500
            });
        }

        if ($.fn.DataTable.isDataTable('#tablaDocumentos')) {
            $('#tablaDocumentos').DataTable().destroy();
        }

        tabla = $('#tablaDocumentos').DataTable({

            "ajax": url,
            "columns": (function () {
                const cols = [
                    {
                        "data": "num_completo",
                        "type": "num", // Forzar tratamiento numérico
                        "render": function (data, type, row) {
                            // Para ordenar, usamos estrictamente el correlativo numérico
                            if (type === 'sort' || type === 'type') {
                                return parseInt(row.num_correlativo);
                            }

                            // Para mostrar (display), formateamos el HTML
                            const match = data.match(/^(.+?)\s+N°\s+(.+)$/);
                            if (match) {
                                const tipo = match[1]; // "OFICIO", "INFORME", etc.
                                const numero = match[2]; // "1", "20250511-B", etc.

                                return `
                                    <div class="d-flex align-items-center justify-content-between gap-2">
                                        <span style="white-space: nowrap; color: #000;">
                                            ${tipo} N° <strong>${numero}</strong>
                                        </span>
                                        <button class="btn btn-sm copy-number-btn" 
                                                data-numero="${numero}" 
                                                title="Copiar número: ${numero}"
                                                style="padding: 4px 8px; font-size: 0.75rem; background-color: #f7f7f8; border: 1px solid #e5e5e5; color: #6b6b6b; border-radius: 6px;">
                                            <i class="fa-solid fa-copy"></i>
                                        </button>
                                    </div>
                                `;
                            }
                            return data;
                        }
                    },
                    { "data": "ht", "defaultContent": "-" },
                    {
                        "data": "created_at",
                        "render": function (data) {
                            if (!data) return '';
                            // Convertir formato YYYY-MM-DD a DD-MM-YYYY
                            const fecha = data.substring(0, 10);
                            const partes = fecha.split('-');
                            return `<span style="white-space: nowrap;">${partes[2]}-${partes[1]}-${partes[0]}</span>`;
                        }
                    },
                    {
                        "data": "se_solicita",
                        "render": function (data) {
                            return data || '-';
                        }
                    },
                    { "data": "nombre_destino" },
                    {
                        "data": "asunto",
                        "className": "text-start",
                        "width": "360px",
                        "render": function (data) {
                            if (!data) return '-';
                            data = data.toUpperCase(); // Forzar mayúsculas para mantener consistencia
                            // Mostrar con line-clamp de 2 líneas y tooltip Bootstrap
                            return `<div 
                                data-bs-toggle="tooltip" 
                                data-bs-placement="top" 
                                data-bs-html="true"
                                title="${data}"
                                style="
                                    display: -webkit-box;
                                    -webkit-line-clamp: 2;
                                    -webkit-box-orient: vertical;
                                    overflow: hidden;
                                    text-overflow: ellipsis;
                                    cursor: pointer;
                                    line-height: 1.4;
                                    max-height: 2.8em;
                                    width: 360px;
                                ">${data}</div>`;
                        }
                    },

                    { "data": "usuario_formulador" },
                    { 
                        "data": "observaciones",
                        "width": "180px",
                        "render": function (data) {
                            if (!data) return '-';
                            data = data.toUpperCase(); // Forzar mayúsculas
                            return `<div 
                                data-bs-toggle="tooltip" 
                                data-bs-placement="top" 
                                data-bs-html="true"
                                title="${data}"
                                style="
                                    display: -webkit-box;
                                    -webkit-line-clamp: 2;
                                    -webkit-box-orient: vertical;
                                    overflow: hidden;
                                    text-overflow: ellipsis;
                                    cursor: pointer;
                                    line-height: 1.4;
                                    max-height: 2.8em;
                                    width: 180px;
                                ">${data}</div>`;
                        }
                    }
                ];

                if (MOSTRAR_ACCIONES) {
                    cols.push({
                        "data": null,
                        "render": function (data, type, row) {
                            let botones = '<div class="d-flex gap-2 justify-content-center">';

                            if (PERMISO_EDITAR) {
                                botones += `
                                    <button class="btn btn-sm edit-doc-btn" 
                                            data-id="${row.id_documento}" 
                                            title="Editar"
                                            style="background-color: #3b82f6; border: none; color: white; padding: 1px 5px; font-size: 11px; border-radius: 4px; transition: all 0.2s;">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                `;
                            } else {
                                botones += `
                                    <button class="btn btn-sm"
                                            title="Sin permiso"
                                            disabled
                                            style="background-color: #3b82f6; opacity: 0.5; cursor: not-allowed; border: none; color: white; padding: 1px 5px; font-size: 11px; border-radius: 4px; transition: all 0.2s;">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                `;
                            }

                            if (PERMISO_ELIMINAR) {
                                botones += `
                                    <button class="btn btn-sm delete-doc-btn" 
                                            data-id="${row.id_documento}"
                                            data-numero="${row.num_completo}" 
                                            title="Eliminar"
                                            style="background-color: #ef4444; border: none; color: white; padding: 1px 5px; font-size: 11px; border-radius: 4px; transition: all 0.2s;">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                `;
                            } else {
                                botones += `
                                    <button class="btn btn-sm"
                                            title="Sin permiso"
                                            disabled
                                            style="background-color: #ef4444; opacity: 0.5; cursor: not-allowed; border: none; color: white; padding: 1px 5px; font-size: 11px; border-radius: 4px; transition: all 0.2s;">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                `;
                            }

                            botones += '</div>';
                            return botones;
                        }
                    });
                }
                return cols;
            })(),
            "autoWidth": false, // Evitar que Datatables recaloque los anchos
            "order": [[0, "desc"]], // Ordenar por N° Documento por defecto (Para mantener secuencia visual)
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json",
                "search": "Buscar:",
                "searchPlaceholder": "Escriba para buscar...",
                "lengthMenu": "Mostrar _MENU_"
            },
            "drawCallback": function () {
                // Inicializar tooltips de Bootstrap después de cada redibujado
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl, {
                        template: '<div class="tooltip" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner" style="text-align: justify; max-width: 400px;"></div></div>'
                    });
                });
            },
            "pageLength": 10
        });
    }

    // Event delegation para los botones de copiar
    $(document).on('click', '.copy-number-btn', function (e) {
        e.preventDefault();
        const numero = $(this).data('numero');
        const btn = $(this);

        // Función interna para feedback visual
        const mostrarExito = () => {
            const iconOriginal = btn.html();
            btn.html('<i class="fa-solid fa-check"></i>');
            btn.css({
                'background-color': '#10a37f',
                'border-color': '#10a37f',
                'color': '#ffffff'
            });

            setTimeout(() => {
                btn.html(iconOriginal);
                btn.css({
                    'background-color': '#f7f7f8',
                    'border-color': '#e5e5e5',
                    'color': '#6b6b6b'
                });
            }, 2000);
        };

        // Lógica de copiado compatible con HTTP
        if (navigator.clipboard && window.isSecureContext) {
            // Método moderno (HTTPS / Localhost)
            navigator.clipboard.writeText(numero).then(mostrarExito).catch(err => {
                console.error('Error clipboard API:', err);
                copiarFallback(numero);
            });
        } else {
            // Método fallback (HTTP)
            copiarFallback(numero);
        }

        function copiarFallback(texto) {
            let textArea = document.createElement("textarea");
            textArea.value = texto;

            // Asegurar que no sea visible pero sea parte del DOM
            textArea.style.top = "0";
            textArea.style.left = "0";
            textArea.style.position = "fixed";
            textArea.style.opacity = "0"; // Make it invisible
            textArea.style.pointerEvents = "none"; // Prevent interaction

            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    mostrarExito();
                } else {
                    console.error('Fallback copy failed: document.execCommand("copy") returned false');
                    alert('No se pudo copiar el número');
                }
            } catch (err) {
                console.error('Fallback copy failed', err);
                alert('No se pudo copiar el número');
            }

            document.body.removeChild(textArea);
        }
    });

    // Event handler para botón de editar
    $(document).on('click', '.edit-doc-btn', function (e) {
        e.preventDefault();
        const id = $(this).data('id');

        // Redirigir a página de edición (crear después)
        window.location.href = `documento_editar.php?id=${id}`;
    });

    // Event handler para botón de eliminar
    $(document).on('click', '.delete-doc-btn', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        const numero = $(this).data('numero');

        Swal.fire({
            title: '¿Eliminar documento?',
            html: `Se eliminará el documento: <br><strong>${numero}</strong>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Llamar al controlador para eliminar
                $.ajax({
                    url: '../controlador/DocumentoControlador.php?op=eliminar',
                    method: 'POST',
                    data: { id: id },
                    dataType: 'json',
                    success: function (response) {
                        if (response.status) {
                            Swal.fire('Eliminado', response.msg, 'success');
                            cargarTabla(); // Recargar tabla
                        } else {
                            Swal.fire('Error', response.msg, 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
                    }
                });
            }
        });
    });

    // Función para descargar reportes
    function descargarReporte(formato) {
        const anio = document.getElementById('filtroAnio').value;
        const fInicio = document.getElementById('fechaInicio').value;
        const fFin = document.getElementById('fechaFin').value;
        const tipo = currentTipo || ''; // Si es vacío, descarga todos

        let url = '';
        if (formato === 'excel') {
            url = `../controlador/reporte_documento_excel.php?anio=${anio}&tipo=${tipo}`;
        } else if (formato === 'pdf') {
            url = `../controlador/reporte_documento_pdf.php?anio=${anio}&tipo=${tipo}`;
        }

        // Agregar filtro de fechas si existe
        if (fInicio && fFin) {
            url += `&fecha_inicio=${fInicio}&fecha_fin=${fFin}`;
        }

        if (url) {
            window.open(url, '_blank');
        }
    }
</script>

<?php include 'layout/footer.php'; ?>