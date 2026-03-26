<?php
// vista/cmn_listado.php
require_once __DIR__ . '/../modelo/conexion.php';

// Verificación de seguridad PNP UE-009
if (empty($_SESSION['id']) || (function_exists('userCan') ? !userCan('cmn') : $_SESSION['rol'] !== 'Super Administrador')) {
  header("Location: " . (defined('BASE_URL') ? BASE_URL : '/') . "vista/inicio.php");
    exit();
}

$anio_actual = defined('ANIO_CMN') ? ANIO_CMN : (int)date('Y');
$titulo_pagina = "Gestión de Responsables CMN " . $anio_actual;

// Parámetros de filtrado
$anio = isset($_GET['anio']) ? (int) $_GET['anio'] : $anio_actual;
$search_query = isset($_GET['q']) ? $_GET['q'] : '';
$region_f = isset($_GET['region']) ? $_GET['region'] : '';
$divopus_f = isset($_GET['divopus']) ? $_GET['divopus'] : '';
$subunidad_f = isset($_GET['subunidad']) ? $_GET['subunidad'] : '';

// Parámetros de paginación
$por_pagina = 30; 
$pagina_actual = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$offset = ($pagina_actual - 1) * $por_pagina;

// Construcción dinámica de la consulta (Prepared Statements)
$params = [];
$types = "";
$where = "WHERE anio_proceso = ? AND archivo_pdf IS NOT NULL";
$params[] = $anio;
$types .= "i";

if (!empty($search_query)) {
    $q_val = "%" . trim($search_query) . "%";
    $where .= " AND (apellidos LIKE ? OR nombres LIKE ? OR dni LIKE ? OR cip LIKE ? OR sub_unidad_especifica LIKE ? OR divpol_divopus LIKE ? OR region_policial LIKE ?)";
    for ($j = 0; $j < 7; $j++) { $params[] = $q_val; $types .= "s"; }
}

if (!empty($region_f)) { $where .= " AND TRIM(region_policial) = ?"; $params[] = trim($region_f); $types .= "s"; }
if (!empty($divopus_f)) { $where .= " AND TRIM(divpol_divopus) = ?"; $params[] = trim($divopus_f); $types .= "s"; }
if (!empty($subunidad_f)) { $where .= " AND TRIM(sub_unidad_especifica) = ?"; $params[] = trim($subunidad_f); $types .= "s"; }

// Conteo total para paginación
$sql_count = "SELECT COUNT(*) as total FROM cmn_responsables $where";
$stmt_count = $conexion->prepare($sql_count);
if ($stmt_count && !empty($params)) $stmt_count->bind_param($types, ...$params);
if ($stmt_count) {
    $stmt_count->execute();
    $total_registros = $stmt_count->get_result()->fetch_assoc()['total'] ?? 0;
} else {
    $total_registros = 0;
}
$total_paginas = ceil($total_registros / $por_pagina);

// Obtener registros
$sql = "SELECT * FROM cmn_responsables $where ORDER BY fecha_registro DESC LIMIT ? OFFSET ?";
$stmt_data = $conexion->prepare($sql);
if ($stmt_data) {
    $types_data = $types . "ii";
    $params_data = array_merge($params, [$por_pagina, $offset]);
    $stmt_data->bind_param($types_data, ...$params_data);
    $stmt_data->execute();
    $result = $stmt_data->get_result();
}

// Estado del formulario
$res_config = $conexion->query("SELECT valor FROM cmn_config WHERE clave = 'mantenimiento'");
$config_mantenimiento = $res_config->fetch_assoc();
$esta_cerrado = ($config_mantenimiento && $config_mantenimiento['valor'] === '1');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= $titulo_pagina ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --pnp-blue: #003666; --pnp-cyan: #008eb0; --indigo-soft: #f0f3ff; --teal-soft: #f0fdfa; --rose-soft: #fff1f2; --emerald-soft: #f0fdf4; }
        body { font-family: 'Outfit', sans-serif; background-color: #f4f7f6; color: #1a202c; }
        .page-content { padding-top: 160px; padding-left: 20px; padding-right: 20px; transition: all 0.3s ease; }
        
        .premium-header { background: #ffffff; border-radius: 16px; padding: 1.2rem 2rem; margin-bottom: 2rem; border: 1px solid rgba(0,0,0,0.06); display: flex; align-items: center; justify-content: space-between; box-shadow: 0 10px 30px rgba(0,0,0,0.04); position: relative; overflow: hidden; }
        .premium-header::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 6px; background: linear-gradient(to bottom, var(--pnp-cyan), var(--pnp-blue)); }

        .btn-modern { padding: 8px 16px; border-radius: 10px; font-weight: 600; font-size: 0.8rem; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; border: none; cursor: pointer; text-decoration: none; }
        .btn-modern:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .btn-modern-primary { background: linear-gradient(135deg, #008eb0 0%, #005073 100%); color: white; }
        .btn-modern-outline { background: white; color: #555; border: 1px solid #e2e8f0; }
        .btn-modern-success { background: #16a34a; color: white; }
        .btn-modern-danger { background: #dc2626; color: white; }
        .btn-modern-info { background: #0ea5e9; color: white; }

        .card-stats { 
            background: #ffffff; 
            border-radius: 12px; 
            padding: 10px 15px; 
            display: flex; 
            flex-direction: column;
            align-items: center; 
            justify-content: center;
            gap: 2px; 
            border: 1px solid rgba(0,0,0,0.06); 
            transition: all 0.3s ease; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.02); 
            height: 80px; 
            text-align: center;
        }
        .card-stats:hover { transform: translateY(-2px); box-shadow: 0 8px 15px rgba(0,0,0,0.08); }
        
        .phase-container { text-align: center; width: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; }
        .phase-label { font-size: 0.6rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 4px; }
        
        .btn-phase {
            width: 100%;
            border-radius: 50px;
            padding: 6px 15px;
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            color: white !important;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        .btn-phase:hover { transform: scale(1.02); }
        .btn-phase-open { background: #22c55e; animation: pulse-green 2s infinite; }
        .btn-phase-closed { background: #ef4444; }

        @keyframes pulse-green {
            0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4); }
            70% { box-shadow: 0 0 0 8px rgba(34, 197, 94, 0); }
            100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }

        .stats-data { display: flex; flex-direction: column; width: 100%; align-items: center; }
        .stats-label { font-size: 0.62rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0px; }
        .stats-number { font-size: 1.8rem; font-weight: 800; color: #2563eb; line-height: 1; }

        .table-container { background: white; border-radius: 15px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); overflow: hidden; }
       .custom-dark-header th { background-color: var(--pnp-blue) !important; color: white !important; font-size: 0.72rem; text-transform: uppercase; padding: 12px 10px !important; text-align: center; white-space: nowrap; border-right: 1px solid rgba(255,255,255,0.1); border-left: 1px solid rgba(255,255,255,0.1); }

        .table td { vertical-align: middle; padding: 10px 8px; font-size: 0.82rem; border-color: #f1f5f9; }
        
        .row-enviado { background-color: rgba(25, 135, 84, 0.04) !important; }
        .row-observado { background-color: rgba(220, 38, 38, 0.06) !important; }
        .row-validado { background-color: rgba(22, 163, 74, 0.04) !important; }

        .badge-obs { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; font-size: 0.65rem; padding: 2px 6px; border-radius: 4px; font-weight: 700; }
        .badge-val { background: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0; font-size: 0.65rem; padding: 2px 6px; border-radius: 4px; font-weight: 700; }

        .btn-copy-mail { color: #3b82f6; background: none; border: none; padding: 4px; border-radius: 6px; }
        .btn-copy-mail.enviado { color: #16a34a; }

        .btn-action { width: 32px; height: 32px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; border: none; transition: 0.2s; }
        .btn-pdf { background: #fffbeb; color: #d97706; }
        .btn-validado { background: #f0fdf4; color: #16a34a; }
        .btn-cargo { background: #eff6ff; color: #2563eb; }
        .btn-edit { background: #f5f3ff; color: #7c3aed; }
        .btn-obs { background: #fff7ed; color: #ea580c; }
        .btn-delete { background: #fef2f2; color: #dc2626; }
    </style>
</head>
<body>
    <?php require('./layout/topbar.php'); ?>
    <?php require('./layout/sidebar.php'); ?>

    <div class="page-content">
        <div class="container-fluid">
            <!-- Header -->
            <div class="premium-header">
                <div>
                    <h4 class="fw-bold mb-1" style="color: var(--pnp-blue);"><i class="fa-solid fa-users-gear me-2"></i> <?= $titulo_pagina ?></h4>
                    <p class="text-muted small mb-0">UE 009 - Gestión Administrativa CMN</p>
                </div>
                <div class="d-flex gap-2">
                    <button onclick="copiarLinkPublico()" class="btn-modern btn-modern-outline">Link Público</button>
                    <a href="../cmn.php" target="_blank" class="btn-modern btn-modern-primary">Nuevo</a>
                    <a href="cmn_cumplimiento.php" class="btn-modern btn-modern-info" style="color:white;">Cumplimiento</a>
                <a href="../controlador/reporte_cmn_excel.php?<?= http_build_query($_GET) ?>" class="btn-modern btn-modern-success">Excel</a>

                 <a href="../controlador/cmn_generar_reporte_pdf.php?<?= http_build_query($_GET) ?>" target="_blank" class="btn-modern btn-modern-danger">Reporte PDF</a>

                </div>
            </div>

            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card-stats shadow-sm">
                        <div class="stats-data">
                            <span class="stats-label">Total Usuarios Identificados</span>
                            <span class="stats-number"><?= number_format($total_registros) ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card-stats shadow-sm">
                        <div class="stats-data">
                            <span class="stats-label">Subunidades con Anexo N° 01</span>
                            <span class="stats-number">
                                <?php
                                $st_sub = $conexion->prepare("SELECT COUNT(DISTINCT sub_unidad_especifica) as t FROM cmn_responsables WHERE anio_proceso = ? AND archivo_pdf IS NOT NULL");
                                $st_sub->bind_param("i", $anio); $st_sub->execute();
                                echo $st_sub->get_result()->fetch_assoc()['t'];
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card-stats shadow-sm" style="background: #f8fafc;">
                        <div class="phase-container">
                            <span class="phase-label">ESTADO DE LA FASE</span>
                            <button id="btnToggleCmn" 
                                class="btn-phase <?= $esta_cerrado ? 'btn-phase-closed' : 'btn-phase-open' ?>"
                                onclick="toggleFormStatus(<?= $esta_cerrado ? 0 : 1 ?>)">
                                <?php if($esta_cerrado): ?>
                                    <i class="fa-solid fa-circle-xmark"></i> CERRADO (PLAZO VENCIDO)
                                <?php else: ?>
                                    <i class="fa-solid fa-circle-check"></i> ABIERTO (EN PROCESO)
                                <?php endif; ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card border-0 shadow-sm rounded-4 mb-3">
                <div class="card-body p-3">
                    <form action="" method="GET" class="row g-2 align-items-center" id="formFiltros">
                        <div class="col-md-3">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                                <input type="text" name="q" id="inputSearch" class="form-control" placeholder="Buscar..." value="<?= htmlspecialchars($search_query) ?>" autocomplete="off">
                                <button class="btn btn-primary" type="submit">Buscar</button>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select name="region" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">Regiones</option>
                                <?php
                                $res_reg = $conexion->query("SELECT DISTINCT region_policial FROM cmn_responsables WHERE region_policial != '' AND anio_proceso = $anio ORDER BY region_policial");
                                while($r = $res_reg->fetch_assoc()): ?>
                                    <option value="<?= $r['region_policial'] ?>" <?= $region_f == $r['region_policial'] ? 'selected' : '' ?>><?= $r['region_policial'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="divopus" class="form-select form-select-sm" onchange="this.form.submit()" <?= empty($region_f) ? 'disabled' : '' ?>>
                                <option value="">Divopus</option>
                                <?php
                                if(!empty($region_f)) {
                                    $res_div = $conexion->query("SELECT DISTINCT divpol_divopus FROM cmn_responsables WHERE region_policial = '" . $conexion->real_escape_string($region_f) . "' AND anio_proceso = $anio ORDER BY divpol_divopus");
                                    while($r = $res_div->fetch_assoc()): ?>
                                        <option value="<?= $r['divpol_divopus'] ?>" <?= $divopus_f == $r['divpol_divopus'] ? 'selected' : '' ?>><?= $r['divpol_divopus'] ?></option>
                                    <?php endwhile;
                                } ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="subunidad" class="form-select form-select-sm" onchange="this.form.submit()" <?= empty($divopus_f) ? 'disabled' : '' ?>>
                                <option value="">Subunidad</option>
                                <?php
                                if(!empty($divopus_f)) {
                                    $res_sub = $conexion->query("SELECT DISTINCT sub_unidad_especifica FROM cmn_responsables WHERE divpol_divopus = '" . $conexion->real_escape_string($divopus_f) . "' AND anio_proceso = $anio ORDER BY sub_unidad_especifica");
                                    while($r = $res_sub->fetch_assoc()): ?>
                                        <option value="<?= $r['sub_unidad_especifica'] ?>" <?= $subunidad_f == $r['sub_unidad_especifica'] ? 'selected' : '' ?>><?= $r['sub_unidad_especifica'] ?></option>
                                    <?php endwhile;
                                } ?>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <select name="anio" class="form-select form-select-sm" onchange="this.form.submit()">
                                <?php for($y = $anio_actual; $y >= 2025; $y--): ?>
                                    <option value="<?= $y ?>" <?= $anio == $y ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <a href="cmn_listado.php" class="btn btn-outline-danger btn-sm w-100"><i class="fa-solid fa-eraser"></i></a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabla -->
            <div class="table-container mb-4">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered mb-0">

                                   <thead class="custom-dark-header">
                            <tr>
                                <th>#</th>
                                <th>GRADO</th>
                                <th>APELLIDOS Y NOMBRES</th>
                                <th>DNI</th>
                                <th>CIP</th>
                                <th class="col-correo">CORREO</th>
                                <th>CELULAR</th>
                                <th style="min-width: 150px;">REGIÓN / DIVOPUS</th>
                                <th class="col-sub-unidad">SUB UNIDAD</th>
                                <th>REGISTRO</th>
                                <th class="text-center">PDF</th>
                                <th class="text-center">ACCIONES</th>
                            </tr>
                        </thead>

                                                 <tbody>
                            <?php if (isset($result)): ?>
                                <?php $i = $offset + 1; while ($row = $result->fetch_assoc()): 
                                    $est = (int)($row['estado'] ?? 0); 
                                    $is_env = (int)($row['acceso_enviado'] ?? 0); 
                                    $clase_row = '';
                                    if ($est === 1) $clase_row = 'row-observado';
                                    elseif ($est === 2) $clase_row = 'row-validado';
                                    elseif ($is_env) $clase_row = 'row-enviado';
                                ?>
                                    <tr id="row-<?= $row['id'] ?>" class="<?= $clase_row ?>">
                                        
                                        <td class="text-center text-muted align-middle"><?= $i++ ?></td>
                                        
                                        <td class="text-center align-middle" style="white-space: nowrap;">
                                            <?= htmlspecialchars($row['grado']) ?>
                                        </td>
                                        
                                        <td class="align-middle" style="min-width: 200px;">
                                            <?= htmlspecialchars($row['apellidos'] . ' ' . $row['nombres']) ?>
                                        </td>
                                        
                                        <!-- Celda 4: DNI -->
                                        <td class="text-center align-middle"><?= $row['dni'] ?></td>
                                        
                                        <!-- Celda 5: CIP -->
                                        <td class="text-center align-middle"><?= $row['cip'] ?></td>
                                        
                                        <td class="col-correo align-middle">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="small"><?= $row['correo'] ?></span>
                                                <button type="button" class="btn-copy-mail <?= $is_env ? 'enviado' : '' ?>" title="Copiar y marcar envío" onclick="copiarYMarcarEnvio('<?= $row['correo'] ?>', <?= $row['id'] ?>, this)">
                                                    <i class="<?= $is_env ? 'fa-solid fa-check-circle' : 'fa-regular fa-copy' ?>"></i>
                                                </button>
                                            </div>
                                        </td>
                                        
                                        <td class="text-center small align-middle"><?= $row['celular'] ?></td>
                                        
                                        <!-- Ubicación con texto normal -->
                                        <td class="align-middle" style="min-width: 160px;">
                                            <div style="font-size: 0.72rem; color: #475569;"><?= $row['region_policial'] ?></div>
                                            <div style="font-size: 0.68rem; color: #64748b; margin-top: 2px;"><i class="fa-solid fa-building text-black-50 me-1"></i><?= $row['divpol_divopus'] ?></div>
                                        </td>
                                        
                                        <td class="col-sub-unidad text-uppercase align-middle" style="font-size: 0.75rem;" title="<?= htmlspecialchars($row['sub_unidad_especifica']) ?>">
                                            <?= htmlspecialchars($row['sub_unidad_especifica']) ?>
                                        </td>
                                        
                                        <!-- Fechas y Badges -->
                                        <td class="text-center align-middle">
                                            <div class="small mb-1"><?= date('d/m/Y', strtotime($row['fecha_registro'])) ?></div>
                                            <?php if ($est === 1): ?>
                                                <span class="badge-obs shadow-sm">OBSERVADO</span>
                                            <?php elseif ($est === 2): ?>
                                                <span class="badge-val shadow-sm">VALIDADO</span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <!-- Botones PDF -->
                                        <td class="text-center align-middle">
                                            <div class="d-flex justify-content-center gap-1">
                                                <button type="button" class="btn-action <?= $est === 2 ? 'btn-validado' : 'btn-pdf' ?>" onclick="verPdfModal('<?= $row['id'] ?>', '../uploads/cmn_<?= $anio ?>/<?= $row['archivo_pdf'] ?>', '<?= addslashes($row['apellidos']) ?>', <?= $est ?>)" title="Ver Solicitud PDF"><i class="fa-solid <?= $est === 2 ? 'fa-check-double' : 'fa-eye' ?>"></i></button>
                                                <a href="../controlador/cmn_generar_cargo_pdf.php?dni=<?= $row['dni'] ?>" target="_blank" class="btn-action btn-cargo" title="Descargar Cargo"><i class="fa-solid fa-file-invoice"></i></a>
                                            </div>
                                        </td>
                                        
                                        <!-- Botones Acción -->
                                        <td class="text-center align-middle">
                                            <div class="d-flex justify-content-center gap-1">
                                                <button type="button" class="btn-action btn-edit" title="Editar Información" onclick='abrirModalEditar(<?= json_encode($row) ?>)'><i class="fa-solid fa-pen-to-square"></i></button>
                                                <button type="button" class="btn-action btn-obs" title="Observar por WhatsApp" onclick="observarRegistroCmn(<?= $row['id'] ?>, '<?= addslashes($row['grado']) ?>', '<?= addslashes($row['apellidos']) ?>', '<?= $row['celular'] ?>')"><i class="fa-brands fa-whatsapp"></i></button>
                                                <button type="button" class="btn-action btn-delete" title="Eliminar Registro" onclick="confirmarEliminarCmn(<?= $row['id'] ?>)"><i class="fa-solid fa-trash-can"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>


                    </table>
                </div>
            </div>

            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
                <div class="d-flex justify-content-between align-items-center mb-5" id="paginationContainer">
                    <span class="text-muted small">Registros: <?= $total_registros ?></span>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?= ($pagina_actual <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina_actual - 1])) ?>">Ant.</a>
                            </li>
                            <?php for ($p = max(1, $pagina_actual - 2); $p <= min($total_paginas, $pagina_actual + 2); $p++): ?>
                                <li class="page-item <?= ($p == $pagina_actual) ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $p])) ?>"><?= $p ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($pagina_actual >= $total_paginas) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina_actual + 1])) ?>">Sig.</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modales -->
    <div class="modal fade" id="modalPdfView" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content" style="height:90vh; border-radius: 15px; overflow: hidden;"><div class="modal-header bg-dark text-white py-1px px-4 d-flex align-items-center"><h6 class="modal-title flex-grow-1" id="pdfViewTitle">Revisión</h6><button class="btn btn-success btn-sm me-3" id="btnValidarPdf" onclick="validarDesdeModal()">Validar</button><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body p-0"><iframe id="pdfIframe" src="" width="100%" height="100%" frameborder="0"></iframe></div></div></div>
    </div>
    
    <div class="modal fade" id="modalEditarCmn" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <form id="formEditarCmn">
                    <div class="modal-header bg-light">
                        <h5><i class="fa-solid fa-user-pen me-2"></i>Editar Registro de CMN</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit_id">
                        
                        <h6 class="text-primary fw-bold mb-3 border-bottom pb-2">Datos de Usuario</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6"><label class="small fw-bold">Apellidos</label><input type="text" class="form-control" name="apellidos" id="edit_apellidos" required></div>
                            <div class="col-md-6"><label class="small fw-bold">Nombres</label><input type="text" class="form-control" name="nombres" id="edit_nombres" required></div>
                            <div class="col-md-4">
                                <label class="small fw-bold">Grado</label>
                                <select class="form-select" name="grado" id="edit_grado" required></select>
                            </div>
                            <div class="col-md-4"><label class="small fw-bold">CIP</label><input type="text" class="form-control" name="cip" id="edit_cip" maxlength="8"></div>
                            <div class="col-md-4"><label class="small fw-bold">DNI</label><input type="text" class="form-control bg-light" id="edit_dni" readonly></div>
                            <div class="col-md-6"><label class="small fw-bold">Correo Electrónico</label><input type="email" class="form-control" name="correo" id="edit_correo" required></div>
                            <div class="col-md-6"><label class="small fw-bold">Celular / WhatsApp</label><input type="text" class="form-control" name="celular" id="edit_celular" required></div>
                        </div>

                        <h6 class="text-primary fw-bold mb-3 border-bottom pb-2">Ubicación y Cargo</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="small fw-bold">Región Policial</label>
                                <select class="form-select" name="region_policial" id="edit_region" onchange="loadDivisionesEdit(this.value)"></select>
                            </div>
                            <div class="col-md-6">
                                <label class="small fw-bold">División / DIVOPUS</label>
                                <select class="form-select" name="divpol_divopus" id="edit_division" onchange="loadSubunidadesEdit(this.value)"></select>
                            </div>
                            <div class="col-12">
                                <label class="small fw-bold">Subunidad Específica</label>
                                <select class="form-select" name="sub_unidad_especifica" id="edit_sub_unidad"></select>
                            </div>
                            <div class="col-12"><label class="small fw-bold">Cargo</label><input type="text" class="form-control" name="cargo" id="edit_cargo"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-2"></i>Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let currentReviewId = null;
        function copiarYMarcarEnvio(email, id, btn) {
            // Revisamos si ya estaba en verde (si ya había sido enviado)
            let yaEnviado = btn.classList.contains('enviado');
            
            if (yaEnviado) {
                // Si ya está verde, le preguntamos si quiere Desmarcarlo o solo copiarlo de nuevo
                Swal.fire({
                    title: '¿Desmarcar envío?',
                    text: "Este usuario ya está marcado como enviado. ¿Deseas quitarle el check verde?",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, quitar marca',
                    cancelButtonText: 'No, solo copiar correo'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Magia para desmarcar visualmente
                        const icon = btn.querySelector('i'); 
                        icon.className = 'fa-regular fa-copy'; 
                        btn.classList.remove('enviado'); 
                        document.getElementById('row-' + id).classList.remove('row-enviado');
                        
                        // Enviamos estado=0 a la base de datos para borrar el check permanentemente
                        const fd = new FormData(); fd.append('id', id); fd.append('estado', 0);
                        fetch('../controlador/cmn_marcar_envio.php', { method: 'POST', body: fd });
                        
                        Swal.fire({ icon: 'success', title: 'Marca quitada', timer: 1000, showConfirmButton: false });
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        // Si dijo que "solo copiar", le copiamos el correo normal
                        navigator.clipboard.writeText(email).then(() => {
                            Swal.fire({ icon: 'success', title: 'Correo copiado', timer: 800, showConfirmButton: false });
                        });
                    }
                });
            } else {
                // Si estaba normal (azul), lo copiamos al instante y lo marcamos de verde
                navigator.clipboard.writeText(email).then(() => {
                    const icon = btn.querySelector('i'); 
                    icon.className = 'fa-solid fa-check-circle'; 
                    btn.classList.add('enviado'); 
                    document.getElementById('row-' + id).classList.add('row-enviado');
                    
                    // Enviamos estado=1 a la base de datos
                    const fd = new FormData(); fd.append('id', id); fd.append('estado', 1);
                    fetch('../controlador/cmn_marcar_envio.php', { method: 'POST', body: fd });
                });
            }
        }

        function verPdfModal(id, url, titulo, estado) { currentReviewId = id; document.getElementById('pdfIframe').src = url; document.getElementById('pdfViewTitle').innerText = 'Revisión: ' + titulo; document.getElementById('btnValidarPdf').style.display = (estado === 2) ? 'none' : 'block'; new bootstrap.Modal(document.getElementById('modalPdfView')).show(); }
        function validarDesdeModal() { 
            const fd = new URLSearchParams(); fd.append('id', currentReviewId); fd.append('estado', 2);
            fetch('../controlador/cmn_cambiar_estado.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: fd })
            .then(res => res.json()).then(data => { if(data.status==='success') location.reload(); }); 
        }
        // Grados policiales
        const GRADOS_PNP = ["MAYOR PNP", "MAYOR S. PNP", "ALFÉREZ PNP", "CAPITÁN PNP", "CAPITÁN S. PNP", "TENIENTE PNP", "SS PNP", "SS S. PNP", "SB PNP", "SB S. PNP", "ST1 PNP", "ST1 S. PNP", "ST2 PNP", "ST2 S. PNP", "ST3 PNP", "ST3 S. PNP", "S1 PNP", "S1 S. PNP", "S2 PNP", "S2 S. PNP", "S3 PNP", "S3 S. PNP", "EC AC", "EC PB", "EC TA", "EMPLEADO CIVIL", "CAS PNP", "LOCADOR"];

        function loadGradosList(selected) {
            const sel = document.getElementById('edit_grado');
            sel.innerHTML = '<option value="">Seleccione Grado...</option>';
            GRADOS_PNP.forEach(g => {
                const opt = document.createElement('option');
                opt.value = g; opt.textContent = g;
                if(g === selected) opt.selected = true;
                sel.appendChild(opt);
            });
        }

        // Carga de unidades para el modal Editar
        function loadRegionesEdit(selectedRegion, subData) {
            fetch('../controlador/cmn_get_unidades_ajax.php?type=regiones')
            .then(r => r.json()).then(data => {
                const sel = document.getElementById('edit_region');
                sel.innerHTML = '<option value="">Seleccione Región...</option>';
                data.forEach(r => {
                    sel.innerHTML += `<option value="${r}" ${r === selectedRegion ? 'selected' : ''}>${r}</option>`;
                });
                if(selectedRegion) loadDivisionesEdit(selectedRegion, subData);
            });
        }

        function loadDivisionesEdit(region, subData = null) {
            if(!region) { document.getElementById('edit_division').innerHTML = '<option value="">Seleccione Región primero...</option>'; return; }
            fetch(`../controlador/cmn_get_unidades_ajax.php?type=divisiones&region=${encodeURIComponent(region)}`)
            .then(r => r.json()).then(data => {
                const sel = document.getElementById('edit_division');
                sel.innerHTML = '<option value="">Seleccione División...</option>';
                data.forEach(d => {
                    sel.innerHTML += `<option value="${d}" ${subData && d === subData.divpol_divopus ? 'selected' : ''}>${d}</option>`;
                });
                if(subData && subData.divpol_divopus) loadSubunidadesEdit(subData.divpol_divopus, subData.sub_unidad_especifica);
            });
        }

        function loadSubunidadesEdit(division, selectedSub = null) {
            if(!division) { document.getElementById('edit_sub_unidad').innerHTML = '<option value="">Seleccione División primero...</option>'; return; }
            const region = document.getElementById('edit_region').value;
            fetch(`../controlador/cmn_get_unidades_ajax.php?type=subunidades&region=${encodeURIComponent(region)}&division=${encodeURIComponent(division)}`)
            .then(r => r.json()).then(data => {
                const sel = document.getElementById('edit_sub_unidad');
                sel.innerHTML = '<option value="">Seleccione Subunidad...</option>';
                data.forEach(s => {
                    sel.innerHTML += `<option value="${s.sub_unidad}" ${s.sub_unidad === selectedSub ? 'selected' : ''}>${s.sub_unidad}</option>`;
                });
            });
        }

        function abrirModalEditar(data) { 
            document.getElementById('edit_id').value = data.id; 
            document.getElementById('edit_apellidos').value = data.apellidos; 
            document.getElementById('edit_nombres').value = data.nombres; 
            
            loadGradosList(data.grado);
            
            document.getElementById('edit_cip').value = data.cip; 
            document.getElementById('edit_dni').value = data.dni; 
            document.getElementById('edit_correo').value = data.correo || ''; 
            document.getElementById('edit_celular').value = data.celular || ''; 
            document.getElementById('edit_cargo').value = data.cargo; 
            
            // Cargar selectores de dependencia
            loadRegionesEdit(data.region_policial, data);

            new bootstrap.Modal(document.getElementById('modalEditarCmn')).show(); 
        }

        document.getElementById('formEditarCmn').addEventListener('submit', function(e) { 
            e.preventDefault(); 
            // Mostramos alerta de que está guardando ya que esto debe guardar en BDD también
            Swal.fire({title: 'Guardando...', allowOutsideClick: false, didOpen: () => Swal.showLoading()});
            
            fetch('../controlador/cmn_registro_update.php', { method: 'POST', body: new FormData(this) })
            .then(res => res.json()).then(data => { 
                if(data.status==='success') {
                    Swal.fire({icon: 'success', title: 'Registro Actualizado', showConfirmButton: false, timer: 1500})
                    .then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message || 'Error al actualizar', 'error');
                }
            }).catch(() => Swal.fire('Error', 'Hubo un problema de red', 'error'));
        });
     function observarRegistroCmn(id, grado, apellidos, celular) {
    const nombre = grado + " " + apellidos;
    // Usamos texto plano en lugar del emoji directo para evitar errores de cPanel
   const m = "🚨 *OBSERVACION CMN* 🚨\n\nEstimado/a *" + nombre.toUpperCase() + "*,\n\nSe le informa que hemos recibido su documento de la *Fase de Identificación*; sin embargo, se ha observado que el archivo presenta inconsistencias como *firmas y/o sellos faltantes* (Responsable Logístico y Jefe de Unidad) o errores en el llenado del documento.\n\nPor favor, sírvase revisar detalladamente su archivo y volver a cargarlo con todos los requisitos completos. Quedamos atentos a la nueva versión.";

    Swal.fire({
        title: 'Observación por WhatsApp',
        html: `<div class="text-start bg-light p-3 rounded border shadow-sm" style="font-size: 0.8rem; color: #555; white-space: pre-wrap;">${m.replace(/\*/g, '')}</div>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#25D366',
        confirmButtonText: 'SÍ, ENVIAR AHORA',
        cancelButtonText: 'CANCELAR'
    }).then(r => {
        if (r.isConfirmed) {
            const fd = new URLSearchParams(); fd.append('id', id); fd.append('estado', 1);
            fetch('../controlador/cmn_cambiar_estado.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: fd }).then(() => {
                const num = celular.replace(/[^0-9]/g, '');
                const phone = (num.length === 9) ? '51' + num : num;
                
                // USAMOS ESTE ENLACE QUE ES EL QUE MEJOR FUNCIONA EN ESCRITORIO
                const url = "https://web.whatsapp.com/send?phone=" + phone + "&text=" + encodeURIComponent(m);
                window.open(url, '_blank');
                location.reload();
            });
        }
    });
}

        function confirmarEliminarCmn(id) {
            Swal.fire({ title: '¿Eliminar?', icon: 'error', showCancelButton: true }).then(r => {
                if (r.isConfirmed) {
                    const fd = new URLSearchParams(); fd.append('id', id);
                    fetch('../controlador/cmn_eliminar_registro.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: fd }).then(() => location.reload());
                }
            });
        }
        function copiarLinkPublico() {
            const link = window.location.origin + "/cmn.php";
            navigator.clipboard.writeText(link).then(() => { Swal.fire({ icon: 'success', title: 'Copiado', timer: 1000, showConfirmButton: false }); });
        }
        function toggleFormStatus(newState) {
            const fd = new FormData(); fd.append('estado', newState);
            fetch('../controlador/cmn_toggle_form.php', { method: 'POST', body: fd }).then(() => location.reload());
        }
        // Debounce search
        let debounceTimer;
        document.getElementById('inputSearch').addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const formData = new FormData(document.getElementById('formFiltros'));
                const params = new URLSearchParams(formData).toString();
                fetch('cmn_listado.php?' + params)
                    .then(r => r.text()).then(html => {
                        const doc = new DOMParser().parseFromString(html, 'text/html');
                        // Actualizar cuerpo de la tabla
                        const newTbody = doc.querySelector('table tbody');
                        if(newTbody) document.querySelector('table tbody').innerHTML = newTbody.innerHTML;

                        // Actualizar paginación
                        const newPag = doc.getElementById('paginationContainer');
                        const oldPag = document.getElementById('paginationContainer');
                        if(newPag && oldPag) {
                            oldPag.innerHTML = newPag.innerHTML;
                        } else if(newPag) {
                            document.querySelector('.container-fluid').appendChild(newPag);
                        } else if(oldPag) {
                            oldPag.remove();
                        }
                    });
            }, 400);
        });
    </script>
    <?php require('./layout/footer.php'); ?>
</body>
</html>