<?php
// 1. CARGAR CONEXIÓN Y ENTORNO (Centralizado)
require_once __DIR__ . '/../modelo/conexion.php';

// Evitar Caché
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (empty($_SESSION['nombre']) && empty($_SESSION['apellido'])) {
    header('Location: ' . (defined('BASE_URL') ? BASE_URL : '/sistem_job/') . 'vista/login/login.php');
    exit;
}

// Verificar permisos - Permitir acceso si tiene permiso VER o es Administrador/Super Administrador
$tieneAcceso = false;

if (isset($_SESSION['rol'])) {
    if ($_SESSION['rol'] === 'Administrador' || $_SESSION['rol'] === 'Super Administrador') {
        $tieneAcceso = true;
    } elseif (isset($_SESSION['permisos']['UNIDADES_POLICIALES']['VER']) && $_SESSION['permisos']['UNIDADES_POLICIALES']['VER']) {
        $tieneAcceso = true;
    }
}

if (!$tieneAcceso) {
    header('Location: ../vista/inicio.php');
    exit;
}


?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unidades Policiales - SIG</title>

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
        rel="stylesheet" />

    <style>
        .page-content {
            padding: 15px;
            padding-top: 85px;
        }

        .module-header {
            background: linear-gradient(135deg, #006db3 0%, #00a8cc 100%);
            color: white;
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 12px;
            box-shadow: 0 4px 15px rgba(0, 109, 179, 0.25);
        }

        .module-header h2 {
            margin: 0;
            font-weight: 700;
            font-size: 20px;
        }

        .module-header p {
            margin: 2px 0 0 0;
            opacity: 0.95;
            font-size: 13px;
        }

        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 10px 15px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            height: 100%;
        }

        .stats-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.1);
        }

        .stats-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            border-radius: 4px 0 0 4px;
        }

        .stats-card.card-blue::after { background: #3b82f6; }
        .stats-card.card-indigo::after { background: #6366f1; }
        .stats-card.card-teal::after { background: #0ea5e9; }
        .stats-card.card-emerald::after { background: #10b981; }
        .stats-card.card-orange::after { background: #f59e0b; }

        .stats-card .icon-wrapper {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-right: 12px;
        }

        .card-blue .icon-wrapper { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .card-indigo .icon-wrapper { background: rgba(99, 102, 241, 0.1); color: #6366f1; }
        .card-teal .icon-wrapper { background: rgba(14, 165, 233, 0.1); color: #0ea5e9; }
        .card-emerald .icon-wrapper { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .card-orange .icon-wrapper { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }

        .stats-info {
            flex: 1;
        }

        .stats-card .number {
            font-size: 22px;
            font-weight: 800;
            color: #1e293b;
            margin: 0;
            line-height: 1;
        }

        .stats-card .label {
            color: #64748b;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 2px;
        }

        .content-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }

        .nav-tabs .nav-link {
            color: #6c757d;
            font-weight: 600;
            border: none;
            border-bottom: 3px solid transparent;
            padding: 8px 15px;
            font-size: 13px;
        }

        .nav-tabs .nav-link:hover {
            color: #006db3;
            border-bottom-color: #00a8cc;
        }

        .nav-tabs .nav-link.active {
            color: #006db3;
            background: transparent;
            border-bottom-color: #006db3;
        }

        .btn-primary {
            background: linear-gradient(135deg, #006db3 0%, #00a8cc 100%);
            border: none;
            padding: 10px 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 109, 179, 0.3);
        }

        .badge-tipo {
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            display: inline-block;
            white-space: nowrap;
            text-transform: uppercase;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .badge-comisaria {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .badge-jefatura {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .badge-departamento {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .badge-default {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            color: #333;
        }

        .search-box {
            position: relative;
            margin-bottom: 20px;
        }

        .search-box input {
            padding-left: 45px;
            border-radius: 25px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            border-color: #006db3;
            box-shadow: 0 0 0 0.2rem rgba(0, 109, 179, 0.15);
        }

        .search-box i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        table.dataTable thead th {
            background: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
            border-bottom: 2px solid #006db3;
            padding: 10px 8px !important;
            font-size: 0.75rem !important;
            text-transform: uppercase;
        }

        .table th, .table td {
            padding: 8px 10px !important;
            font-size: 0.78rem !important;
            vertical-align: middle !important;
        }

        .action-buttons .btn {
            padding: 5px 12px;
            margin: 0 2px;
            font-size: 13px;
        }

        .action-buttons {
            display: flex;
            flex-wrap: nowrap;
            gap: 4px;
            justify-content: center;
            white-space: nowrap;
        }

        .modal-header {
            background: linear-gradient(135deg, #006db3 0%, #00a8cc 100%);
            color: white;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }

        .form-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #006db3;
            box-shadow: 0 0 0 0.2rem rgba(0, 109, 179, 0.15);
        }

        .breadcrumb-item {
            color: #6c757d;
        }

        .breadcrumb-item.active {
            color: #006db3;
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
    </style>
</head>

<body>
    <!-- Topbar -->
    <?php require('./layout/topbar.php'); ?>

    <!-- Sidebar -->
    <?php require('./layout/sidebar.php'); ?>

    <!-- Contenido Principal -->
    <div class="page-content">
        <div class="container-fluid">

            <!-- Header del Módulo -->
            <div class="module-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <img src="../public/images/escudo.png" alt="Escudo PNP"
                            style="height: 48px; margin-right: 15px; filter: drop-shadow(0 2px 8px rgba(0,0,0,0.2));">
                        <div>
                            <h2 class="mb-1"><i class="fas fa-shield-alt me-2"></i>Unidades Policiales PNP</h2>
                            <p class="mb-0">Gestión Integral de Regiones, Divisiones y Unidades Policiales</p>
                        </div>
                    </div>
                    <div class="d-flex gap-1">
                        <a href="../controlador/reporte_unidades_excel.php" target="_blank" class="btn btn-success btn-sm"
                            title="Descargar Reporte Excel">
                            <i class="fas fa-file-excel"></i>
                        </a>
                        <a href="../controlador/reporte_unidades_pdf.php" target="_blank" class="btn btn-danger btn-sm"
                            title="Descargar Reporte PDF">
                            <i class="fas fa-file-pdf"></i>
                        </a>
                        <?php if ($_SESSION['rol'] === 'Super Administrador' || isset($_SESSION['permisos']['UNIDADES_POLICIALES']['IMPORTAR'])): ?>
                            <a href="importar_unidades_policiales.php" class="btn btn-success btn-sm">
                                <i class="fas fa-file-upload"></i> Importar
                            </a>
                            <button class="btn btn-danger btn-sm" onclick="limpiarTodosDatos()"
                                title="Limpiar todos los datos">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        <?php endif; ?>
                        
                        <!-- Botón Tutorial PDF -->
                        <a href="../controlador/generar_manual_unidades_pdf.php" target="_blank"
                            class="btn px-3 fw-bold"
                            style="background: transparent; border: none; color: #ffffff !important; transition: all 0.3s ease;"
                            onmouseover="this.style.textShadow='0 0 8px rgba(255,255,255,0.5)';"
                            onmouseout="this.style.textShadow='none';"
                            title="Ver Manual de Gestión de Unidades Policiales">
                            <i class="fas fa-book-open text-warning me-1"></i> Tutorial
                        </a>

                    </div>
                </div>
            </div>

            <!-- Estadísticas Rápidas -->
            <div class="row g-2 mb-2 row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5" id="estadisticasContainer">
                <div class="col">
                    <div class="stats-card card-blue">
                        <div class="icon-wrapper"><i class="fas fa-map-marked-alt"></i></div>
                        <div class="stats-info">
                            <p class="number" id="totalRegiones">0</p>
                            <p class="label">Regiones</p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="stats-card card-indigo">
                        <div class="icon-wrapper"><i class="fas fa-sitemap"></i></div>
                        <div class="stats-info">
                            <p class="number" id="totalDivisiones">0</p>
                            <p class="label">Divisiones</p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="stats-card card-teal">
                        <div class="icon-wrapper"><i class="fas fa-location-dot"></i></div>
                        <div class="stats-info">
                            <p class="number" id="totalDistritos">0</p>
                            <p class="label">Distritos</p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="stats-card card-emerald">
                        <div class="icon-wrapper"><i class="fas fa-building-shield"></i></div>
                        <div class="stats-info">
                            <p class="number" id="totalComisarias">0</p>
                            <p class="label">Comisarías PNP</p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="stats-card card-orange">
                        <div class="icon-wrapper"><i class="fas fa-building-user"></i></div>
                        <div class="stats-info">
                            <p class="number" id="totalOtros">0</p>
                            <p class="label">Otras Unidades</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pestañas de Navegación -->
            <div class="content-card">
                <ul class="nav nav-tabs mb-2" id="unidadesTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="jerarquia-tab" data-bs-toggle="tab"
                            data-bs-target="#jerarquia" type="button">
                            <i class="fas fa-sitemap me-2"></i>Vista Jerárquica
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="regiones-tab" data-bs-toggle="tab" data-bs-target="#regiones"
                            type="button">
                            <i class="fas fa-map-marked-alt me-2"></i>Regiones
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="divisiones-tab" data-bs-toggle="tab" data-bs-target="#divisiones"
                            type="button">
                            <i class="fas fa-building me-2"></i>Divisiones
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="distritos-tab" data-bs-toggle="tab" data-bs-target="#distritos"
                            type="button">
                            <i class="fas fa-map-marker-alt me-2"></i>Distritos
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="subunidades-tab" data-bs-toggle="tab" data-bs-target="#subunidades"
                            type="button">
                            <i class="fas fa-building-shield me-2"></i>Unidades / Sub-Unidades
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="unidadesTabContent">

                    <!-- Tab: Vista Jerárquica -->
                    <div class="tab-pane fade show active" id="jerarquia" role="tabpanel">
                        <div class="row g-2 mb-2">
                            <div class="col-md-4">
                                <label class="form-label small text-muted">Filtrar por Región</label>
                                <select class="form-select" id="filtroRegion">
                                    <option value="">Todas las Regiones</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted">Filtrar por División / DIVPOL</label>
                                <select class="form-select" id="filtroDivision">
                                    <option value="">Todas las Divisiones</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted">Búsqueda Rápida</label>
                                <div class="search-box mb-0">
                                    <i class="fas fa-search"></i>
                                    <input type="text" class="form-control" id="busquedaGlobal"
                                        placeholder="Escriba nombre o distrito...">
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="tablaJerarquia" class="table table-hover table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Departamento</th>
                                        <th>Provincia</th>
                                        <th>Distrito</th>
                                        <th>Región</th>
                                        <th>División</th>
                                        <th>Unidad / Sub-Unidad</th>
                                        <th>Tipo</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab: Regiones -->
                    <div class="tab-pane fade" id="regiones" role="tabpanel">
                        <?php if ($_SESSION['rol'] === 'Super Administrador' || isset($_SESSION['permisos']['UNIDADES_POLICIALES']['CREAR'])): ?>
                            <div class="mb-3">
                                <button class="btn btn-primary" onclick="abrirModalRegion()">
                                    <i class="fas fa-plus me-2"></i>Nueva Región
                                </button>
                            </div>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table id="tablaRegiones" class="table table-hover" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Descripción</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab: Divisiones -->
                    <div class="tab-pane fade" id="divisiones" role="tabpanel">
                        <?php if ($_SESSION['rol'] === 'Super Administrador' || isset($_SESSION['permisos']['UNIDADES_POLICIALES']['CREAR'])): ?>
                            <div class="mb-3">
                                <button class="btn btn-primary" onclick="abrirModalDivision()">
                                    <i class="fas fa-plus me-2"></i>Nueva División
                                </button>
                            </div>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table id="tablaDivisiones" class="table table-hover" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Región</th>
                                        <th>División</th>
                                        <th>Unidades</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab: Distritos -->
                    <div class="tab-pane fade" id="distritos" role="tabpanel">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Vista de Distritos:</strong> Muestra todos los distritos únicos donde hay comisarías
                            registradas.
                        </div>

                        <div class="table-responsive">
                            <table id="tablaDistritos" class="table table-hover" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Departamento</th>
                                        <th>Provincia</th>
                                        <th>Distrito</th>
                                        <th>Total Unidades</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab: Sub-Unidades -->
                    <div class="tab-pane fade" id="subunidades" role="tabpanel">
                        <?php if ($_SESSION['rol'] === 'Super Administrador' || isset($_SESSION['permisos']['UNIDADES_POLICIALES']['CREAR'])): ?>
                            <div class="mb-3">
                                <button class="btn btn-primary" onclick="abrirModalSubUnidad()">
                                    <i class="fas fa-plus me-2"></i>Nueva Unidad
                                </button>
                            </div>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table id="tablaSubUnidades" class="table table-hover" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>División</th>
                                        <th>Distrito</th>
                                        <th>Nombre</th>
                                        <th>Tipo</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>

    <!-- MODALES -->
    <?php include 'modales/modal_region.php'; ?>
    <?php include 'modales/modal_division.php'; ?>
    <?php include 'modales/modal_subunidad.php'; ?>
    <?php include 'modales/modal_detalle_subunidad.php'; ?>

    <!-- Footer -->
    <?php require('./layout/footer.php'); ?>

    <!-- Modal Detalle Comisarías -->
    <div class="modal fade" id="modalDetalleComisarias" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tituloModalDetalle">Listado de Unidades Policiales</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" width="100%">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th>Nombre Sub-Unidad</th>
                                    <th>Tipo</th>
                                    <th>Ubicación (Distrito)</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody id="tablaDetalleBody">
                                <!-- Se llena dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

    <!-- SweetAlert2 (Loaded via footer.php) -->

    <script>
        const USER_ROLE = '<?php echo $_SESSION['rol'] ?? ""; ?>';
        const USER_PERMISSIONS = {
            VER: <?php echo (isset($_SESSION['permisos']['UNIDADES_POLICIALES']['VER']) || $_SESSION['rol'] === 'Super Administrador' || $_SESSION['rol'] === 'Administrador') ? 'true' : 'false'; ?>,
            CREAR: <?php echo (isset($_SESSION['permisos']['UNIDADES_POLICIALES']['CREAR']) || $_SESSION['rol'] === 'Super Administrador') ? 'true' : 'false'; ?>,
            EDITAR: <?php echo (isset($_SESSION['permisos']['UNIDADES_POLICIALES']['EDITAR']) || $_SESSION['rol'] === 'Super Administrador') ? 'true' : 'false'; ?>,
            ELIMINAR: <?php echo (isset($_SESSION['permisos']['UNIDADES_POLICIALES']['ELIMINAR']) || $_SESSION['rol'] === 'Super Administrador') ? 'true' : 'false'; ?>,
            EXPORTAR: <?php echo (isset($_SESSION['permisos']['UNIDADES_POLICIALES']['EXPORTAR']) || $_SESSION['rol'] === 'Super Administrador' || $_SESSION['rol'] === 'Administrador') ? 'true' : 'false'; ?>,
            IMPORTAR: <?php echo (isset($_SESSION['permisos']['UNIDADES_POLICIALES']['IMPORTAR']) || $_SESSION['rol'] === 'Super Administrador') ? 'true' : 'false'; ?>
        };
    </script>
    <script src="../public/js/unidades_policiales.js?v=<?php echo time(); ?>"></script>
</body>

</html>