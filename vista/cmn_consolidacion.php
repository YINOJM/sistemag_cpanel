<?php
require_once __DIR__ . '/../modelo/conexion.php';

// Verificación de seguridad
if (empty($_SESSION['id']) || !userCan('cmn')) {
    header("Location: " . (defined('BASE_URL') ? BASE_URL : '/sistemag_cpanel/') . "vista/inicio.php");
    exit();
}

$titulo_pagina = "CMN - Fase de Consolidación";
$anio_actual = defined('ANIO_CMN') ? ANIO_CMN : (int)date('Y');
$anio = isset($_GET['anio']) ? (int) $_GET['anio'] : $anio_actual;

// 1. Estadísticas Sincronizadas
$sql_stats = "SELECT 
    (SELECT COUNT(*) FROM cmn_responsables WHERE anio_proceso = $anio AND archivo_pdf IS NOT NULL) as identificados,
    (SELECT COUNT(*) FROM cmn_anexos_fase3 a INNER JOIN cmn_responsables r ON a.dni_responsable = r.dni WHERE r.anio_proceso = $anio) as recibidos,
    (SELECT SUM(a.monto_total) FROM cmn_anexos_fase3 a INNER JOIN cmn_responsables r ON a.dni_responsable = r.dni WHERE r.anio_proceso = $anio) as monto_total";
    
$res_stats = $conexion->query($sql_stats);
if (!$res_stats) {
    die("Error en consulta de estadísticas: " . $conexion->error);
}
$row_stats = $res_stats->fetch_assoc();

// 1.1 Estado del formulario
$res_config = $conexion->query("SELECT valor FROM cmn_config WHERE clave = 'mantenimiento_fase3'");
$config_mantenimiento = $res_config->fetch_assoc();
$esta_cerrado = ($config_mantenimiento && $config_mantenimiento['valor'] === '1');

// 2. Definir Filtros Dinámicos
$where_filtros = " AND r.anio_proceso = $anio ";
if (!empty($_GET['region'])) {
    $where_filtros .= " AND r.region_policial = '" . $conexion->real_escape_string($_GET['region']) . "' ";
}
if (!empty($_GET['divopus'])) {
    $where_filtros .= " AND r.divpol_divopus = '" . $conexion->real_escape_string($_GET['divopus']) . "' ";
}
if (!empty($_GET['sub_unidad'])) {
    $where_filtros .= " AND r.sub_unidad_especifica LIKE '%" . $conexion->real_escape_string($_GET['sub_unidad']) . "%' ";
}

// 3. Consulta de registros
$sql_tabla = "SELECT 
                r.dni, r.grado, CONCAT(r.apellidos, ' ', r.nombres) as nombres_completos, r.cip, 
                r.sub_unidad_especifica as sub_unidad, r.region_policial, r.divpol_divopus as div_frente, r.celular,
                a.id as anexo_id, a.fecha_subida, a.archivo_pdf as anexo_pdf, a.monto_total, a.estado_revision
              FROM cmn_responsables r 
              LEFT JOIN cmn_anexos_fase3 a ON r.dni = a.dni_responsable 
              WHERE r.archivo_pdf IS NOT NULL $where_filtros 
              ORDER BY r.sub_unidad_especifica ASC";

$res_tabla = $conexion->query($sql_tabla);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= $titulo_pagina ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        :root {
            --primary-color: #d32f2f;      
            --secondary-color: #b71c1c;    
            --bg-body: #f4f7f6;
            --card-radius: 12px;
            --mef-orange: #f59e0b;
        }

        body { 
            font-family: 'Outfit', sans-serif; 
            background-color: var(--bg-body); 
            color: #333;
        }

        .page-content { 
            padding-top: 85px; 
            padding-left: 10px;    
            padding-right: 10px;   
            padding-bottom: 30px;
            transition: all 0.3s ease; 
        }

        /* Glass Panel Styling */
        .glass-panel {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: var(--card-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.05);
        }

        /* Filter Card Styling */
        .filter-card {
            background: #fff;
            border-radius: var(--card-radius);
            padding: 1.2rem;
            border-left: 5px solid var(--primary-color);
        }

        .filter-label {
            font-size: 0.72rem;
            font-weight: 700;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
            display: block;
        }

        /* Stats Card Styling */
        .stat-card {
            background: white;
            border-radius: var(--card-radius);
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1.2rem;
            border: 1px solid rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }

        .stat-card:hover { transform: translateY(-5px); }

        .stat-icon {
            width: 55px;
            height: 55px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            background: rgba(211, 47, 47, 0.1);
            color: var(--primary-color);
        }

        /* Table Aesthetics */
        .table-custom { border-collapse: separate; border-spacing: 0 8px; width: 100% !important; }
        .table-custom thead th {
            background: #f8fafc;
            color: #475569;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            padding: 12px 15px;
            border: none;
        }
        .table-custom tbody tr {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            transition: all 0.2s;
        }
        .table-custom tbody tr:hover { background: #fdfdfd; transform: scale(1.002); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .table-custom td { padding: 15px; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9; }
        .table-custom td:first-child { border-left: 1px solid #f1f5f9; border-top-left-radius: 10px; border-bottom-left-radius: 10px; }
        .table-custom td:last-child { border-right: 1px solid #f1f5f9; border-top-right-radius: 10px; border-bottom-right-radius: 10px; }

        .btn-modern-primary { background: linear-gradient(135deg, #d32f2f 0%, #b71c1c 100%); color: white; border: none; padding: 8px 16px; border-radius: 10px; font-weight: 600; text-decoration: none; }
        .btn-modern-outline { background: white; color: #555; border: 1px solid #e0e0e0; padding: 8px 16px; border-radius: 10px; font-weight: 600; text-decoration: none; }

        /* Status & Action Buttons for Table */
        .btn-status { border: none; border-radius: 4px; padding: 4px 8px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: white; display: inline-flex; align-items: center; gap: 4px; }
        .bg-validado { background-color: #28a745; box-shadow: 0 2px 4px rgba(40,167,69,0.3); }
        .bg-recepcionado { background-color: #007bff; box-shadow: 0 2px 4px rgba(0,123,255,0.3); }
        .bg-pendiente { background-color: #6c757d; color: white; opacity: 0.8; }

        .btn-pdf-box { width: 34px; height: 34px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; border: none; color: white; transition: all 0.2s; }
        .btn-pdf-box:hover { transform: scale(1.05); }
        /* Header and Buttons */
        .btn-header-add {
            background: linear-gradient(135deg, #0d2a4a 0%, #008eb0 100%) !important;
            color: white !important;
            border: none !important;
            border-radius: 6px !important;
            padding: 8px 18px !important;
            font-weight: 600 !important;
            font-size: 0.8rem !important;
            box-shadow: 0 4px 10px rgba(0, 142, 176, 0.25) !important;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            height: 36px;
        }
        .btn-header-add:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0, 142, 176, 0.4) !important; filter: brightness(1.1); }

        .btn-copy-link {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
            color: white !important;
            border: none !important;
            border-radius: 6px !important;
            padding: 8px 18px !important;
            font-weight: 600 !important;
            font-size: 0.8rem !important;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 123, 255, 0.25) !important;
            display: inline-flex;
            align-items: center;
            height: 36px;
        }
        .btn-copy-link:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0, 123, 255, 0.35) !important; filter: brightness(1.1); }

        .btn-export-pro {
            min-width: 100px;
            height: 36px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 15px;
            gap: 8px;
            font-weight: 600;
            font-size: 0.75rem;
            transition: all 0.3s;
            border: none;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            color: white !important;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .btn-export-pro:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0,0,0,0.15); filter: brightness(1.1); }
        .bg-excel { background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%) !important; color: white !important; }
        .bg-pdf { background: linear-gradient(135deg, #dc3545 0%, #bd2130 100%) !important; color: white !important; }

        .btn-status-open {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
            border: none !important;
            color: white !important;
        }
        .btn-status-closed {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
            border: none !important;
            color: white !important;
        }

        .btn-pdf-green { background-color: #28a745; }
        .btn-pdf-blue { background-color: #007bff; }
        .btn-pdf-red { background-color: #e63946; }

        .btn-whatsapp-custom:hover { background-color: #128c7e; transform: translateY(-2px); color: white; }

        .btn-action {
            width: 32px; height: 32px;
            border-radius: 8px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 0.9rem;
            border: none; cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-action:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.1); filter: brightness(0.95); }
        .btn-obs { background: #fff3e0 !important; color: #ef6c00 !important; }
        .btn-obs:hover { background: #ffe0b2 !important; }
    </style>
</head>
<body>
    <?php include './layout/topbar.php'; ?>
    <?php include './layout/sidebar.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
            
            <!-- Encabezado Unificado -->
            <div class="row align-items-center mb-3">
                <div class="col-md-7">
                    <h2 class="fw-semibold mb-0" style="color: #1e293b; font-size: 1.7rem; letter-spacing: -0.5px;">
                        <i class="fa-solid fa-box-check me-2" style="color: #dc2626; font-size: 1.5rem;"></i> (iii) Fase de Consolidación y Aprobación
                        <span class="badge rounded-pill bg-danger animate__animated animate__pulse animate__infinite ms-2" style="font-size: 0.6rem; vertical-align: middle; background: #dc3545 !important;">
                            <i class="fa-solid fa-satellite-dish me-1"></i> EN VIVO
                        </span>
                    </h2>
                    <p class="text-muted small mb-0">Consolidación final y cierre del Cuadro Multianual de Necesidades.</p>
                </div>
                <div class="col-md-5 text-md-end d-flex gap-2 justify-content-md-end mt-2 mt-md-0">
                    <button class="btn btn-copy-link btn-sm px-3" onclick="copyPublicLink()">
                        <i class="fa-solid fa-link me-2"></i> Enlace Público
                    </button>
                    <a href="cmn_consolidacion_subir.php" target="_blank" class="btn btn-header-add btn-sm px-3">
                        <i class="fa-solid fa-plus me-2"></i> Nuevo Registro
                    </a>
                    <div class="d-flex gap-2 border-start ps-3 ms-2 align-items-center">
                        <button class="btn btn-export-pro bg-excel" title="Exportar a Excel" onclick="exportarExcel(3)">
                            <i class="fa-solid fa-file-excel"></i> EXCEL
                        </button>
                        <button class="btn btn-export-pro bg-pdf" title="Exportar a PDF" onclick="exportarPDF(3)">
                            <i class="fa-solid fa-file-pdf"></i> REPORTE PDF
                        </button>
                    </div>
                </div>
            </div>

            <!-- KPIs Unificados Estilo Identificación -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="glass-panel text-center py-2 border-0 shadow-sm" style="background: white; border-radius: 10px;">
                        <p class="text-muted small fw-bold mb-0 text-uppercase" style="font-size: 0.65rem;">Total Usuarios Padrón</p>
                        <h2 class="fw-bold mb-0 text-primary" style="font-size: 2rem;"><?= $row_stats['identificados'] ?? 0 ?></h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-panel text-center py-2 border-0 shadow-sm" style="background: white; border-radius: 10px;">
                        <p class="text-muted small fw-bold mb-0 text-uppercase" style="font-size: 0.65rem;">Subunidades con CMN Final</p>
                        <h2 class="fw-bold mb-0 text-success" style="font-size: 2rem;"><?= $row_stats['recibidos'] ?? 0 ?></h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-panel text-center py-1 border-0 shadow-sm" style="background: white; border-radius: 10px;">
                        <p class="text-muted small fw-bold mb-1 text-uppercase" style="font-size: 0.65rem;">Estado de la Fase</p>
                        <div class="d-grid px-2">
                            <button id="btnToggleFase" 
                                class="btn fw-bold py-1 rounded-pill shadow-sm <?= $esta_cerrado ? 'btn-status-closed' : 'btn-status-open' ?>" 
                                style="font-size: 0.75rem;"
                                onclick="toggleFormStatus(<?= $esta_cerrado ? 0 : 1 ?>)">
                                <?php if($esta_cerrado): ?>
                                    <i class="fa-solid fa-circle-xmark me-2"></i> CERRADO (PLAZO VENCIDO)
                                <?php else: ?>
                                    <i class="fa-solid fa-circle-check me-2"></i> ABIERTO (EN PROCESO)
                                <?php endif; ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="filter-card border-0 shadow-sm mb-4">
                <form method="GET" class="row g-2 align-items-end" id="formFiltro">
                    <div class="col-md-2">
                        <label class="filter-label">Periodo</label>
                        <select name="anio" class="form-select form-select-sm rounded-2 border-light shadow-none bg-light" onchange="this.form.submit()">
                            <?php for($y = $anio_actual; $y >= 2025; $y--): ?>
                                <option value="<?= $y ?>" <?= $anio == $y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="filter-label">Región Policial</label>
                        <select name="region" class="form-select form-select-sm rounded-2 shadow-none" id="filterRegion" onchange="this.form.submit()">
                            <option value="">-- Seleccione Región --</option>
                            <?php 
                            $q_reg = "SELECT DISTINCT region_policial FROM cmn_responsables WHERE anio_proceso = $anio ORDER BY region_policial";
                            $res_reg = $conexion->query($q_reg);
                            while($r_reg = $res_reg->fetch_assoc()): ?>
                                <option value="<?= $r_reg['region_policial'] ?>" <?= ($_GET['region'] ?? '') == $r_reg['region_policial'] ? 'selected' : '' ?>><?= $r_reg['region_policial'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="filter-label">División / Frente</label>
                        <select name="divopus" class="form-select form-select-sm rounded-2 shadow-none" id="filterDivopus" onchange="this.form.submit()" <?= empty($_GET['region']) ? 'disabled' : '' ?>>
                            <option value="">-- Todas las Divisiones --</option>
                            <?php if(!empty($_GET['region'])):
                                $q_div = "SELECT DISTINCT divpol_divopus as divopus FROM cmn_responsables WHERE anio_proceso = $anio AND region_policial = '".$conexion->real_escape_string($_GET['region'])."' ORDER BY divopus";
                                $res_div = $conexion->query($q_div);
                                while($r_div = $res_div->fetch_assoc()): ?>
                                    <option value="<?= $r_div['divopus'] ?>" <?= ($_GET['divopus'] ?? '') == $r_div['divopus'] ? 'selected' : '' ?>><?= $r_div['divopus'] ?></option>
                                <?php endwhile; endif; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="filter-label">Buscar Sub Unidad</label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="sub_unidad" class="form-control" placeholder="Nombre de unidad..." value="<?= $_GET['sub_unidad'] ?? '' ?>">
                            <button class="btn btn-danger fw-bold" type="submit">FILTRAR</button>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <a href="cmn_consolidacion.php" class="btn btn-light btn-sm w-100 border"><i class="fa-solid fa-sync"></i></a>
                    </div>
                </form>
            </div>

            <div class="glass-panel">
                <div class="table-responsive">
                    <table class="table table-custom table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="text-center" style="width:3%">#</th>
                                <th style="width:7%; white-space:nowrap">GRADO</th>
                                <th style="width:18%">APELLIDOS Y NOMBRES</th>
                                <th class="text-center" style="width:7%; white-space:nowrap">DNI</th>
                                <th class="text-center" style="width:6%; white-space:nowrap">CIP</th>
                                <th style="width:10%; white-space:nowrap">REGIÓN POLICIAL</th>
                                <th style="width:12%; white-space:nowrap">DIVOPUS / FRENTE</th>
                                <th style="width:16%">SUB UNIDAD / ÁREA</th>
                                <th class="text-center" style="width:11%">REGISTRO</th>
                                <th class="text-center" style="width:7%">PDF</th>
                                <th class="text-center" style="width:7%">ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $n = 1;
                            if ($res_tabla && $res_tabla->num_rows > 0):
                                while ($row = $res_tabla->fetch_assoc()): 
                                    $tiene_anexo = !empty($row['anexo_pdf']);
                                    $estado_rev = (int)($row['estado_revision'] ?? 0);
                            ?>
                            <tr>
                                <td class="text-center text-muted small"><?= $n++ ?></td>
                                <td class="small text-uppercase"><?= $row['grado'] ?? '-' ?></td>
                                <td><div class="" style="color: #2c3e50; font-size:0.85rem"><?= $row['nombres_completos'] ?></div></td>
                                <td class="text-center"><span class="badge bg-light text-dark border fw-normal" style="font-size:0.75rem"><?= $row['dni'] ?></span></td>
                                <td class="text-center small"><?= $row['cip'] ?? '-' ?></td>
                                <td><small class="text-uppercase" style="font-size:0.65rem"><?= $row['region_policial'] ?></small></td>
                                <td><small class="text-uppercase" style="font-size:0.65rem"><?= $row['div_frente'] ?></small></td>
                                <td><div class="small" style="font-size:0.75rem"><?= $row['sub_unidad'] ?></div></td>
                                
                                <td class="text-center">
                                    <?php if ($tiene_anexo): ?>
                                        <div class="small mb-1" style="font-size:0.75rem; color:#444"><?= date('d/m/Y', strtotime($row['fecha_subida'])) ?></div>
                                        <?php if ($estado_rev === 1): ?>
                                            <span class="btn-status bg-validado">✓ VALIDADO</span>
                                        <?php else: ?>
                                            <span class="btn-status bg-recepcionado">↓ RECEPCIONADO</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-secondary opacity-50 rounded-pill px-3" style="font-size:0.65rem">PENDIENTE</span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-center">
                                    <?php if ($tiene_anexo): ?>
                                         <div class="d-flex justify-content-center gap-1">
                                             <button class="btn-action <?= $estado_rev === 1 ? 'btn-pdf-green' : 'btn-pdf-blue' ?>" 
                                                 title="<?= $estado_rev === 1 ? 'Validado' : 'Ver y Validar' ?>"
                                                 onclick="verPdfModal('../<?= $row['anexo_pdf'] ?>', '<?= addslashes($row['sub_unidad']) ?>', '<?= $row['anexo_id'] ?>', <?= $estado_rev ?>)">
                                                 <i class="fa-solid <?= $estado_rev === 1 ? 'fa-check-double' : 'fa-eye' ?>"></i>
                                             </button>
                                             <a href="../<?= $row['anexo_pdf'] ?>" target="_blank" class="btn-action" style="background:rgba(220,38,38,0.1);color:#dc2626" title="Abrir Original">
                                                 <i class="fa-solid fa-file-pdf"></i>
                                             </a>
                                         </div>
                                     <?php else: ?> --- <?php endif; ?>
                                 </td>

                                 <td class="text-center">
                                     <div class="d-flex justify-content-center gap-1">
                                         <button class="btn-action btn-obs"
                                             title="Observar por WhatsApp"
                                             onclick="observarWhatsApp('<?= $row['celular'] ?>', '<?= addslashes($row['nombres_completos']) ?>', '<?= $row['anexo_id'] ?>', '<?= addslashes($row['grado']) ?>', 3)">
                                             <i class="fa-brands fa-whatsapp"></i>
                                         </button>
                                     </div>
                                 </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr><td colspan="12" class="text-center py-5 text-muted">No se encontraron registros activos para este periodo.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!-- PAGINACIÓN MODERNA -->
                <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pt-2 border-top gap-2" id="paginationBar">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small">Mostrar</span>
                        <select id="rowsPerPageSelect" class="form-select form-select-sm" style="width:auto;border-radius:8px;border:1.5px solid #dee2e6;font-size:0.85rem;">
                            <option value="15">15</option>
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span class="text-muted small">por página</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span id="paginationInfo" class="text-muted small fw-semibold"></span>
                        <div class="d-flex gap-1" id="paginationControls"></div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function copyPublicLink() {
            const link = "<?= FULL_BASE_URL . 'vista/cmn_consolidacion_subir.php' ?>";

            navigator.clipboard.writeText(link).then(() => {
                Swal.fire({
                    icon: 'success',
                    title: '¡Enlace Copiado!',
                    text: 'El enlace para Fase de Consolidación y Aprobación ya está en tu portapapeles.',
                    timer: 1500,
                    showConfirmButton: false
                });
            });
        }

        // ======================================================
        // MOTOR DE PAGINACIÓN MODERNO (Client-side)
        // ======================================================
        const tbody         = document.querySelector('.table tbody');
        const rowsSelect    = document.getElementById('rowsPerPageSelect');
        const paginInfo     = document.getElementById('paginationInfo');
        const paginControls = document.getElementById('paginationControls');

        let currentPage  = 1;
        let rowsPerPage  = 25;

        function applyPagination() {
            const allRows   = Array.from(tbody.querySelectorAll('tr'));
            const visible   = allRows.filter(r => r.getAttribute('data-hidden') !== 'filter');
            const total     = visible.length;
            const totalPages = Math.max(1, Math.ceil(total / rowsPerPage));
            
            if (currentPage > totalPages) currentPage = totalPages;

            const start = (currentPage - 1) * rowsPerPage;
            const end   = start + rowsPerPage;

            visible.forEach((row, i) => {
                row.style.display = (i >= start && i < end) ? '' : 'none';
            });
            // Ocultar las que el buscador filtró
            allRows.filter(r => r.getAttribute('data-hidden') === 'filter').forEach(r => r.style.display = 'none');

            const from = total === 0 ? 0 : start + 1;
            const to   = Math.min(end, total);
            paginInfo.textContent = `Mostrando ${from}–${to} de ${total}`;

            renderPaginControls(totalPages);
        }

        function renderPaginControls(totalPages) {
            paginControls.innerHTML = '';
            const btnStyle = (active) => `
                display:inline-flex;align-items:center;justify-content:center;
                width:32px;height:32px;border-radius:8px;border:1.5px solid;
                font-size:0.8rem;font-weight:600;cursor:pointer;transition:all .2s;
                background:${active ? '#d32f2f' : 'white'};
                color:${active ? 'white' : '#555'};
                border-color:${active ? '#d32f2f' : '#dee2e6'};`;

            const mkBtn = (label, page, active = false, disabled = false) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.style.cssText = btnStyle(active);
                btn.innerHTML = label;
                btn.disabled = disabled;
                if (disabled) btn.style.opacity = '0.4';
                btn.addEventListener('click', () => { currentPage = page; applyPagination(); });
                return btn;
            };

            if (totalPages <= 1) return;

            // Simple pagination: Prev, Current, Next
            paginControls.appendChild(mkBtn('<i class="fa-solid fa-angle-left"></i>', currentPage - 1, false, currentPage === 1));
            
            for(let p=1; p<=totalPages; p++) {
                if (p === 1 || p === totalPages || (p >= currentPage - 1 && p <= currentPage + 1)) {
                    paginControls.appendChild(mkBtn(p, p, p === currentPage));
                } else if (p === currentPage - 2 || p === currentPage + 2) {
                    const dots = document.createElement('span');
                    dots.innerHTML = '&hellip;';
                    dots.className = 'px-1';
                    paginControls.appendChild(dots);
                }
            }

            paginControls.appendChild(mkBtn('<i class="fa-solid fa-angle-right"></i>', currentPage + 1, false, currentPage === totalPages));
        }

        rowsSelect.addEventListener('change', function() {
            rowsPerPage = parseInt(this.value);
            currentPage = 1;
            applyPagination();
        });

        // Búsqueda Integrada con Paginación
        const inputBusca = document.querySelector('input[name="sub_unidad"]');
        if (inputBusca) {
            inputBusca.addEventListener('keyup', function() {
                const q = this.value.toLowerCase().trim();
                const allRows = Array.from(tbody.querySelectorAll('tr'));
                allRows.forEach(row => {
                    const match = row.innerText.toLowerCase().includes(q);
                    row.setAttribute('data-hidden', match ? '' : 'filter');
                });
                currentPage = 1;
                applyPagination();
            });
        }

        function toggleFormStatus(newState) {
            const statusText = newState === 1 ? 'CERRAR la recepción del CMN Consolidado y Aprobado' : 'ABRIR la recepción del CMN Consolidado y Aprobado';
            Swal.fire({
                title: '¿Confirmar cambio?',
                text: `Vas a ${statusText}.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: newState === 1 ? '#dc3545' : '#198754'
            }).then((result) => {
                if (result.isConfirmed) {
                    const fd = new FormData();
                    fd.append('estado', newState);
                    fd.append('fase', 3);
                    fetch('../controlador/cmn_toggle_form.php', { method: 'POST', body: fd })
                        .then(r => r.json())
                        .then(data => {
                            if (data.status === 'success') location.reload();
                        });
                }
            });
        }

        function exportarExcel(fase) {
            window.location.href = `../controlador/cmn_export_excel_fases.php?fase=${fase}&anio=<?= $anio ?>`;
        }

        function exportarPDF(fase) {
            window.open(`../controlador/cmn_generar_reporte_pdf_fases.php?fase=${fase}&anio=<?= $anio ?>`, '_blank');
        }

        function verPdfModal(url, titulo, id, estado) {
            const modal = new bootstrap.Modal(document.getElementById('modalPdfView'));
            document.getElementById('pdfViewTitle').innerText = titulo;
            document.getElementById('pdfIframe').src = url;
            document.getElementById('current_anexo_id').value = id;
            
            const btnVal = document.getElementById('btnValidarAnexo');
            if (estado === 1) {
                btnVal.style.display = 'none';
            } else {
                btnVal.style.display = 'block';
            }
            modal.show();
        }

        function validarAnexo() {
            const id = document.getElementById('current_anexo_id').value;
            Swal.fire({
                title: '¿Confirmar Validación?',
                text: "Se marcará el documento de la Fase de Consolidación como conforme.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754'
            }).then((result) => {
                if (result.isConfirmed) {
                    const fd = new FormData();
                    fd.append('id', id);
                    fd.append('fase', 3);
                    fetch('../controlador/cmn_anexo_validar.php', { method: 'POST', body: fd })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) location.reload();
                        });
                }
            });
        }

        function observarWhatsApp(celular, nombre, id, grado, fase) {
            if (!celular || celular === '---' || celular === '') {
                Swal.fire('Error', 'No hay celular registrado.', 'error');
                return;
            }
            const faseTxt = 'CMN Final - Fase de Consolidación';
            const m = `*-- OBSERVACIÓN CMN --*\nEstimado/a *${grado} ${nombre}*,\n\nSe ha observado su despacho del *${faseTxt}*. Por favor revise y vuelva a subir su archivo corregido.`;
            
            Swal.fire({
                title: 'Observar vía WA',
                text: "¿Deseas marcar como observado y abrir chat?",
                icon: 'warning',
                showCancelButton: true
            }).then((result) => {
                if (result.isConfirmed && id) {
                    const fd = new FormData();
                    fd.append('id', id);
                    fd.append('fase', 3);
                    fetch('../controlador/cmn_anexo_observar.php', { method: 'POST', body: fd })
                        .then(r => r.json())
                        .then(() => {
                            window.open("https://web.whatsapp.com/send?phone=51" + celular + "&text=" + encodeURIComponent(m));
                            setTimeout(() => location.reload(), 2000);
                        });
                }
            });
        }

        function copyPublicLink() {
            const url = "<?= FULL_BASE_URL ?>vista/cmn_consolidacion_subir.php";
            navigator.clipboard.writeText(url).then(() => {
                Swal.fire({ icon: 'success', title: '¡Enlace Copiado!', text: 'El enlace público CMN Final ya está en su portapapeles.', timer: 1500, showConfirmButton: false });
            });
        }

        document.addEventListener('DOMContentLoaded', applyPagination);
    </script>

    <!-- Modal para Previsualizar PDF -->
    <div class="modal fade" id="modalPdfView" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content" style="border-radius: 15px; overflow: hidden; height: 90vh;">
                <div class="modal-header bg-dark text-white px-4 py-2 d-flex justify-content-between align-items-center">
                    <h6 class="modal-title fw-bold" id="pdfViewTitle" style="font-size: 0.9rem;">Previsualización de Documento</h6>
                    <div class="d-flex gap-2">
                        <button class="btn btn-success btn-sm fw-bold px-3" id="btnValidarAnexo" onclick="validarAnexo()">
                            <i class="fa-solid fa-check-circle me-1"></i> VALIDAR CONFORME
                        </button>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body p-0 bg-secondary">
                    <input type="hidden" id="current_anexo_id">
                    <iframe id="pdfIframe" src="" width="100%" height="100%" frameborder="0"></iframe>
                </div>
            </div>
        </div>
    </div>
    <?php require('./layout/footer.php'); ?>
</body>
</html>