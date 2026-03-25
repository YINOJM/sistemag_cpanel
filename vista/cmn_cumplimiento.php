<?php
// vista/cmn_cumplimiento.php
require_once __DIR__ . '/../modelo/conexion.php';

// Verificación de seguridad: Dinámica por permisos
if (!userCan('SEGUIMIENTO')) {
    header("Location: " . (defined('BASE_URL') ? BASE_URL : '/sistem_job/') . "vista/inicio.php");
    exit();
}

$titulo_pagina = "Seguimiento de Cumplimiento CMN " . ANIO_CMN;

// Parámetros de filtrado
$filtro_region = isset($_GET['region']) ? $_GET['region'] : '';
$filtro_division = isset($_GET['division']) ? $_GET['division'] : '';
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : ''; // 'cumplio' o 'pendiente'
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : ANIO_CMN;
$pagina = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($pagina < 1) $pagina = 1;
$por_pagina = 50;
$offset = ($pagina - 1) * $por_pagina;

// Obtener todas las regiones para el filtro
$regiones = [];
$res_reg = $conexion->query("SELECT DISTINCT nombre_region FROM regiones_policiales WHERE estado = 1 ORDER BY nombre_region");
while ($r = $res_reg->fetch_assoc()) $regiones[] = $r['nombre_region'];

// Obtener todas las divisiones para el filtro (si hay región seleccionada)
$divisiones = [];
if ($filtro_region) {
    $stmt_div = $conexion->prepare("SELECT d.nombre_division FROM divisiones_policiales d INNER JOIN regiones_policiales r ON d.id_region = r.id_region WHERE r.nombre_region = ? AND d.estado = 1 ORDER BY d.nombre_division");
    $stmt_div->bind_param("s", $filtro_region);
    $stmt_div->execute();
    $res_div = $stmt_div->get_result();
    while ($d = $res_div->fetch_assoc()) $divisiones[] = $d['nombre_division'];
}

// Lógica de coincidencia ULTRA-ESTRICTA (Nombre + Región + División)
// Esto evita que "JEFATURA" de Huacho se confunda con "JEFATURA" de Huaral o Lima.
$sql_match_cond = "
    REPLACE(TRIM(UPPER(s.nombre_subunidad)), ' ', '') COLLATE utf8mb4_spanish_ci = REPLACE(TRIM(UPPER(c.sub_unidad_especifica)), ' ', '') COLLATE utf8mb4_spanish_ci
    AND REPLACE(TRIM(UPPER(r.nombre_region)), ' ', '') COLLATE utf8mb4_spanish_ci = REPLACE(TRIM(UPPER(c.region_policial)), ' ', '') COLLATE utf8mb4_spanish_ci
    AND REPLACE(TRIM(UPPER(d.nombre_division)), ' ', '') COLLATE utf8mb4_spanish_ci = REPLACE(TRIM(UPPER(c.divpol_divopus)), ' ', '') COLLATE utf8mb4_spanish_ci
";

$subquery_cumplimiento = "(SELECT COUNT(*) FROM cmn_responsables c 
    WHERE ($sql_match_cond)
    AND c.anio_proceso = $anio 
    AND c.archivo_pdf IS NOT NULL)";

// Subquery para obtener datos del responsable que cumplió
$subquery_responsable_cmn = "(SELECT CONCAT(c.grado, '|', c.apellidos, '|', c.nombres, '|', c.dni, '|', c.celular, '|', c.correo, '|', c.fecha_registro) 
    FROM cmn_responsables c 
    WHERE ($sql_match_cond)
    AND c.anio_proceso = $anio 
    AND c.archivo_pdf IS NOT NULL 
    ORDER BY c.fecha_registro DESC LIMIT 1)";

// Base de la consulta (FROM y WHERE comunes)
$sql_base = "FROM sub_unidades_policiales s
            INNER JOIN divisiones_policiales d ON s.id_division = d.id_division
            INNER JOIN regiones_policiales r ON d.id_region = r.id_region
            WHERE s.estado = 1";

if ($filtro_region) {
    $sql_base .= " AND r.nombre_region = '" . $conexion->real_escape_string($filtro_region) . "'";
}
if ($filtro_division) {
    $sql_base .= " AND d.nombre_division = '" . $conexion->real_escape_string($filtro_division) . "'";
}

// Clonamos sql_base para el conteo con filtro de estado (para paginación)
$sql_base_filtrado = $sql_base;
if ($filtro_estado == 'cumplio') {
    $sql_base_filtrado .= " AND $subquery_cumplimiento > 0";
} elseif ($filtro_estado == 'pendiente') {
    $sql_base_filtrado .= " AND $subquery_cumplimiento = 0";
}

// Estadísticas globales (sin paginación de lista, pero respeta filtros de región/división)
$sql_stats = "SELECT 
                COUNT(*) as total_unidades,
                SUM(CASE WHEN $subquery_cumplimiento > 0 THEN 1 ELSE 0 END) as total_cumplieron
              $sql_base";
$res_stats = $conexion->query($sql_stats);
if (!$res_stats) die("Error en estadísticas: " . $conexion->error);
$row_stats = $res_stats->fetch_assoc();

$stats = [
    'total' => (int)$row_stats['total_unidades'],
    'cumplieron' => (int)$row_stats['total_cumplieron'],
    'pendientes' => (int)$row_stats['total_unidades'] - (int)$row_stats['total_cumplieron']
];

$porcentaje = ($stats['total'] > 0) ? round(($stats['cumplieron'] / $stats['total']) * 100, 1) : 0;

// Consulta de datos con paginación
$sql_data = "SELECT 
                r.nombre_region, 
                d.nombre_division, 
                s.nombre_subunidad,
                s.telefono,
                s.email,
                s.responsable as jefe_unidad,
                $subquery_cumplimiento as registros_encontrados,
                $subquery_responsable_cmn as datos_responsable_cmn
              $sql_base_filtrado";

// Contar total para paginación (con todos los filtros activos)
$res_count_pagi = $conexion->query("SELECT COUNT(*) as total_pagi $sql_base_filtrado");
if (!$res_count_pagi) die("Error en paginación: " . $conexion->error);
$row_count_pagi = $res_count_pagi->fetch_assoc();
$total_registros_filtrados = (int)$row_count_pagi['total_pagi'];
$total_paginas = ceil($total_registros_filtrados / $por_pagina);

$sql_data .= " ORDER BY r.nombre_region, d.nombre_division, s.nombre_subunidad";
$sql_data .= " LIMIT $offset, $por_pagina";

$result = $conexion->query($sql_data);
if (!$result) die("Error en datos: " . $conexion->error);
$unidades_data = [];
while ($row = $result->fetch_assoc()) {
    $row['cumplio'] = ($row['registros_encontrados'] > 0);
    
    // Parsear datos del responsable CMN si existen
    if ($row['datos_responsable_cmn']) {
        $parts = explode('|', $row['datos_responsable_cmn']);
        $row['resp_cmn'] = [
            'grado' => $parts[0] ?? '',
            'apellidos' => $parts[1] ?? '',
            'nombres' => $parts[2] ?? '',
            'dni' => $parts[3] ?? '',
            'celular' => $parts[4] ?? '',
            'correo' => $parts[5] ?? '',
            'fecha' => $parts[6] ?? ''
        ];
    } else {
        $row['resp_cmn'] = null;
    }
    
    $unidades_data[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo_pagina ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #003666;
            --secondary: #006699;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --bg-body: #f8fafc;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-body);
            color: #334155;
        }

        .page-content {
            padding-top: 100px;
            padding-left: 20px;
            padding-right: 20px;
            transition: all 0.3s ease;
        }

        .card-stat {
            border: none;
            border-radius: 32px; /* Super redondeado Pro */
            box-shadow: 0 10px 30px -10px rgba(0,0,0,0.08);
            transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            overflow: hidden;
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .card-stat:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: 0 25px 50px -15px rgba(0,0,0,0.15);
            background: #fff;
            border-color: rgba(255,255,255,1);
        }

        .stat-icon {
            width: 54px;
            height: 54px;
            border-radius: 20px; /* Squircle softer */
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            transition: all 0.4s ease;
            box-shadow: 0 8px 15px -5px rgba(0,0,0,0.1);
        }

        .card-stat:hover .stat-icon {
            transform: scale(1.15) rotate(10deg);
            box-shadow: 0 12px 20px -5px rgba(0,0,0,0.2);
        }

        .progress {
            height: 10px;
            border-radius: 20px;
            background-color: rgba(255, 255, 255, 0.2);
        }

        .progress-bar {
            border-radius: 10px;
            background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%);
        }

        .table-container {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 40px;
        }

        .table thead th {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            border: none;
            padding: 12px 15px;
        }

        .table tbody td {
            padding: 12px 15px;
            vertical-align: middle;
            font-size: 0.85rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .status-cumplio {
            background-color: #dcfce7;
            color: #166534;
        }

        .status-pendiente {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .filter-section {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            margin-bottom: 25px;
        }

        .pagination .page-link {
            border: none;
            color: #64748b;
            border-radius: 8px;
            margin: 0 3px;
            font-weight: 500;
        }

        .pagination .page-item.active .page-link {
            background-color: var(--primary);
            color: white;
            box-shadow: 0 4px 10px rgba(0,54,102,0.2);
        }

        .btn-call {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: #f1f5f9;
            color: #64748b;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-call:hover {
            background-color: var(--primary);
            color: white;
        }

        .btn-whatsapp {
            background-color: #e8fff3;
            color: #128c7e;
        }

        .btn-whatsapp:hover {
            background-color: #128c7e;
            color: white;
        }

        /* Estilos para impresión */
        @media print {
            .page-content { padding-top: 0 !important; }
            #sidebar, .navbar, .filter-section, .btn-primary, .btn-outline-secondary, .pagination-container, .btn-call {
                display: none !important;
            }
            body { background: white; }
            .table-container { box-shadow: none; border: 1px solid #ddd; }
            .card-stat { border: 1px solid #eee; box-shadow: none; }
            .navbar-brand, .sidebar { display: none !important; }
            .page-content { margin-left: 0 !important; }
            .btn-info { display: none !important; }
        }
    </style>
</head>
<body>
    <?php require('./layout/topbar.php'); ?>
    <?php require('./layout/sidebar.php'); ?>

    <div class="page-content">
        <div class="container-fluid">
            
            <!-- Breadcrumb & Title -->
            <div class="d-flex justify-content-between align-items-end mb-4">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1">
                            <li class="breadcrumb-item"><a href="cmn_listado.php">CMN <?= ANIO_CMN ?></a></li>
                            <li class="breadcrumb-item active">Seguimiento</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold text-dark m-0">
                        <i class="fa-solid fa-chart-line text-primary me-2"></i>Seguimiento de Cumplimiento
                    </h2>
                </div>
                <div class="text-end d-print-none">
                    <?php
                        // Construir URL de reporte con filtros actuales
                        $report_params = http_build_query([
                            'region' => $filtro_region,
                            'division' => $filtro_division,
                            'estado' => $filtro_estado,
                            'anio' => $anio
                        ]);
                    ?>
                    <a href="../controlador/cmn_reporte_cumplimiento_pdf.php?<?= $report_params ?>" target="_blank" class="btn btn-outline-dark btn-sm px-3 py-2 rounded-pill fw-bold">
                        <i class="fa-solid fa-file-pdf me-2 text-danger"></i>Imprimir Reporte
                    </a>
                    <a href="cmn_listado.php" class="btn btn-primary btn-sm ms-2 px-3 py-2 rounded-pill fw-bold">
                        <i class="fa-solid fa-arrow-left me-2"></i>Volver al Listado
                    </a>
                </div>
            </div>

            <!-- Stats Row -->
            <div class="row g-3 mb-4">
                <!-- Total -->
                <div class="col-md-3">
                    <div class="card card-stat p-3 h-100">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                                <i class="fa-solid fa-building"></i>
                            </div>
                            <div>
                                <span class="text-muted small fw-bold d-block uppercase" style="font-size: 10px; letter-spacing: 1px;">TOTAL UNIDADES</span>
                                <h3 class="fw-bold mb-0"><?= number_format($stats['total']) ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Cumplieron -->
                <div class="col-md-3">
                    <div class="card card-stat p-3 h-100 border-bottom border-success border-4">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                                <i class="fa-solid fa-check-double"></i>
                            </div>
                            <div>
                                <span class="text-success small fw-bold d-block uppercase" style="font-size: 10px; letter-spacing: 1px;">CUMPLIERON</span>
                                <h3 class="fw-bold mb-0 text-success"><?= number_format($stats['cumplieron']) ?></h3>
                                <span class="text-muted" style="font-size: 11px;"><?= $porcentaje ?>% de avance</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Pendientes -->
                <div class="col-md-3">
                    <div class="card card-stat p-3 h-100 border-bottom border-danger border-4">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-danger bg-opacity-10 text-danger me-3">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                            </div>
                            <div>
                                <span class="text-danger small fw-bold d-block uppercase" style="font-size: 10px; letter-spacing: 1px;">PENDIENTES</span>
                                <h3 class="fw-bold mb-0 text-danger"><?= number_format($stats['pendientes']) ?></h3>
                                <span class="text-muted" style="font-size: 11px;">Faltan completar</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Avance -->
                <div class="col-md-3">
                    <div class="card card-stat p-3 h-100" style="background: linear-gradient(135deg, #003666 0%, #006699 100%); color: white;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-bold opacity-75" style="font-size: 10px; letter-spacing: 1px;">AVANCE GENERAL</span>
                            <span class="fw-bold fs-4"><?= $porcentaje ?>%</span>
                        </div>
                        <div class="progress border-0 bg-white bg-opacity-20 mb-2" style="height: 6px;">
                            <div class="progress-bar bg-white" style="width: <?= $porcentaje ?>%;"></div>
                        </div>
                        <div class="d-flex justify-content-between opacity-75" style="font-size: 10px;">
                            <span>Meta: 100%</span>
                            <span>Proyectado</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filter-section d-print-none">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small">Región Policial</label>
                        <select name="region" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Todas las Regiones</option>
                            <?php foreach ($regiones as $r): ?>
                                <option value="<?= $r ?>" <?= $filtro_region == $r ? 'selected' : '' ?>><?= $r ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($filtro_region): ?>
                    <div class="col-md-3">
                        <label class="form-label small">División / DIVOPUS</label>
                        <select name="division" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Todas las Divisiones</option>
                            <?php foreach ($divisiones as $d): ?>
                                <option value="<?= $d ?>" <?= $filtro_division == $d ? 'selected' : '' ?>><?= $d ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-3">
                        <label class="form-label small">Estado</label>
                        <select name="estado" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Todos los Estados</option>
                            <option value="cumplio" <?= $filtro_estado == 'cumplio' ? 'selected' : '' ?>>Solo Cumplieron</option>
                            <option value="pendiente" <?= $filtro_estado == 'pendiente' ? 'selected' : '' ?>>Solo Pendientes</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <a href="cmn_cumplimiento.php" class="btn btn-outline-secondary btn-sm w-100">
                            <i class="fa-solid fa-filter-circle-xmark me-2"></i>Limpiar
                        </a>
                    </div>
                </form>
            </div>

            <!-- Table -->
            <div class="table-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="m-0"><i class="fa-solid fa-list-check me-2 text-primary"></i>Listado de Unidades</h5>
                    <span class="badge bg-light text-dark shadow-sm border">Mostrando <?= count($unidades_data) ?> de <?= $total_registros_filtrados ?> registros</span>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="20%">Región</th>
                                <th width="20%">División / DIVOPUS</th>
                                <th width="25%">Sub-Unidad (Comisaría/Unidad)</th>
                                <th width="15%" class="text-center">Estado</th>
                                <th width="15%" class="text-center d-print-none">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($unidades_data)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        No se encontraron unidades con los filtros seleccionados.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($unidades_data as $index => $row): ?>
                                    <tr>
                                        <td class="text-muted small fw-bold"><?= $offset + $index + 1 ?></td>
                                        <td class="small"><?= $row['nombre_region'] ?></td>
                                        <td class="small"><?= $row['nombre_division'] ?></td>
                                        <td>
                                            <?php if ($row['cumplio']): ?>
                                                <div class="fw-bold text-dark" style="cursor: pointer;" 
                                                     onclick="verDetalleResponsable(<?= htmlspecialchars(json_encode($row['resp_cmn'])) ?>, '<?= htmlspecialchars($row['nombre_subunidad']) ?>')"
                                                     title="Ver responsable logístico">
                                                    <?= htmlspecialchars($row['nombre_subunidad']) ?>
                                                    <i class="fa-solid fa-circle-info ms-1 small text-primary"></i>
                                                </div>
                                            <?php else: ?>
                                                <div><?= htmlspecialchars($row['nombre_subunidad']) ?></div>
                                            <?php endif; ?>

                                            <div class="text-muted x-small" style="font-size: 0.75rem;">
                                                <i class="fa-solid fa-user-tie me-1"></i> <?= $row['jefe_unidad'] ?: 'Jefe: Sin asignar' ?>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($row['cumplio']): ?>
                                                <span class="status-badge status-cumplio">
                                                    <i class="fa-solid fa-check"></i> CUMPLIÓ
                                                </span>
                                            <?php else: ?>
                                                <span class="status-badge status-pendiente">
                                                    <i class="fa-solid fa-clock"></i> PENDIENTE
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center d-print-none">
                                            <div class="d-flex justify-content-center gap-2">
                                                <?php 
                                                    // Priorizar el celular del responsable logístico si ya cumplió
                                                    $tel_contacto = ($row['resp_cmn'] && $row['resp_cmn']['celular']) ? $row['resp_cmn']['celular'] : $row['telefono'];
                                                    
                                                    if ($tel_contacto): 
                                                        $tel = preg_replace('/[^0-9]/', '', $tel_contacto);
                                                        if (strlen($tel) >= 9): 
                                                            $mensaje = $row['cumplio'] 
                                                                ? "Hola, le escribimos del Sistema Integrado de Gestión - UE009. Confirmamos la recepción del registro CMN de la unidad " . $row['nombre_subunidad'] . ". Muchas gracias."
                                                                : "Hola, le escribimos del Sistema Integrado de Gestión - UE009. Su unidad " . $row['nombre_subunidad'] . " se encuentra PENDIENTE de remitir su responsable CMN 2026. Por favor cumplir a la brevedad.";
                                                    ?>
                                                        <a href="https://wa.me/51<?= substr($tel, -9) ?>?text=<?= urlencode($mensaje) ?>" 
                                                           target="_blank" class="btn-call btn-whatsapp" title="Enviar WhatsApp a <?= $tel_contacto ?>">
                                                            <i class="fa-brands fa-whatsapp fa-lg"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted x-small" title="No hay celular registrado">Sin contacto</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_paginas > 1): ?>
                <div class="d-flex justify-content-center mt-4 pagination-container d-print-none">
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <li class="page-item <?= ($pagina <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?p=<?= $pagina - 1 ?>&region=<?= urlencode($filtro_region) ?>&division=<?= urlencode($filtro_division) ?>&estado=<?= urlencode($filtro_estado) ?>">
                                    <i class="fa-solid fa-chevron-left"></i>
                                </a>
                            </li>
                            
                            <?php
                            $rango = 2;
                            for ($i = 1; $i <= $total_paginas; $i++):
                                if ($i == 1 || $i == $total_paginas || ($i >= $pagina - $rango && $i <= $pagina + $rango)):
                            ?>
                                <li class="page-item <?= ($i == $pagina) ? 'active' : '' ?>">
                                    <a class="page-link" href="?p=<?= $i ?>&region=<?= urlencode($filtro_region) ?>&division=<?= urlencode($filtro_division) ?>&estado=<?= urlencode($filtro_estado) ?>"><?= $i ?></a>
                                </li>
                            <?php elseif ($i == $pagina - $rango - 1 || $i == $pagina + $rango + 1): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; endfor; ?>

                            <li class="page-item <?= ($pagina >= $total_paginas) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?p=<?= $pagina + 1 ?>&region=<?= urlencode($filtro_region) ?>&division=<?= urlencode($filtro_division) ?>&estado=<?= urlencode($filtro_estado) ?>">
                                    <i class="fa-solid fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Detalle Responsable -->
    <div class="modal fade" id="modalResponsable" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header bg-primary text-white border-0 py-3" style="border-radius: 20px 20px 0 0;">
                    <h5 class="modal-title fw-bold">Detalle del Responsable Logístico</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="text-center mb-4">
                        <div class="bg-primary bg-opacity-10 d-inline-flex p-3 rounded-circle mb-3">
                            <i class="fa-solid fa-user-check fa-3x text-primary"></i>
                        </div>
                        <h5 id="modalRespNombre" class="fw-bold mb-0">NOMBRE COMPLETO</h5>
                        <p id="modalRespGrado" class="text-muted small">GRADO PNP / CARGO</p>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="small text-muted d-block">DNI</label>
                            <span id="modalRespDni" class="fw-bold">-</span>
                        </div>
                        <div class="col-6">
                            <label class="small text-muted d-block">Celular</label>
                            <span id="modalRespCelular" class="fw-bold">-</span>
                        </div>
                        <div class="col-12">
                            <label class="small text-muted d-block">Correo Electrónico</label>
                            <span id="modalRespCorreo" class="fw-bold">-</span>
                        </div>
                        <div class="col-12">
                            <label class="small text-muted d-block">Fecha de Cumplimiento</label>
                            <span id="modalRespFecha" class="fw-bold">-</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-3">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function verDetalleResponsable(data, unidad) {
        if (!data) return;
        
        // Limpiar para evitar mostrar datos antiguos si algo falla
        document.getElementById('modalRespNombre').innerText = 'Cargando...';
        document.getElementById('modalRespGrado').innerText = '';
        document.getElementById('modalRespDni').innerText = '';
        document.getElementById('modalRespCelular').innerText = '';
        document.getElementById('modalRespCorreo').innerText = '';
        document.getElementById('modalRespFecha').innerText = '';

        // Asignar nuevos datos
        document.getElementById('modalRespNombre').innerText = (data.apellidos || '') + ', ' + (data.nombres || '');
        document.getElementById('modalRespGrado').innerText = data.grado || 'PERSONAL PNP';
        document.getElementById('modalRespDni').innerText = data.dni || '-';
        document.getElementById('modalRespCelular').innerText = data.celular || 'No registrado';
        document.getElementById('modalRespCorreo').innerText = data.correo || 'No registrado';
        document.getElementById('modalRespFecha').innerText = data.fecha || '-';
        
        const modalEl = document.getElementById('modalResponsable');
        let modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (!modalInstance) {
            modalInstance = new bootstrap.Modal(modalEl);
        }
        modalInstance.show();
    }
    </script>
</body>
</html>
