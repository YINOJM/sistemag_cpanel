<?php
require_once '../modelo/conexion.php';
// Verificación de seguridad
if (empty($_SESSION['id'])) {
    header("Location: inicio.php");
    exit();
}

// Permitir el acceso si es Administrador, Super Administrador o tiene permisos
if ($_SESSION['rol'] !== 'Administrador' && $_SESSION['rol'] !== 'Super Administrador' && !isset($_SESSION['permisos_rutas']['seguimiento'])) {
    // Aquí puedes redirigir si el usuario no tiene los permisos suficientes.
}

require_once '../modelo/PermisosMiddleware.php';
$middleware = new PermisosMiddleware();
$puede_crear_seg_men = $middleware->verificarPermiso($_SESSION['id'], 'seguimiento', 'CREAR');
$puede_editar_seg_men = $middleware->verificarPermiso($_SESSION['id'], 'seguimiento', 'EDITAR');
$puede_eliminar_seg_men = $middleware->verificarPermiso($_SESSION['id'], 'seguimiento', 'ELIMINAR');
$puede_importar_seg_men = $middleware->verificarPermiso($_SESSION['id'], 'seguimiento', 'IMPORTAR');

$titulo_pagina = "Contrataciones Menores a 8 UIT";

// Parámetros de filtrado
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');
$estado_filtro = isset($_GET['estado']) ? (int)$_GET['estado'] : 0;
$search_query = isset($_GET['q']) ? $_GET['q'] : '';

// Parámetros de paginación
$por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$offset = ($pagina_actual - 1) * $por_pagina;

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= $titulo_pagina ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; background-color: #f4f6f9; }
    .page-content { transition: all 0.3s ease; }
    
    /* Botones Modernos */
    .btn-modern {
        border-radius: 8px;
        transition: all 0.2s ease;
        padding: 5px 15px;
        font-weight: 600;
        border: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
    }
    .btn-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        filter: brightness(1.1);
    }
    .btn-modern-primary { background-color: #0d6efd; color: white; }
    .btn-modern-success { background-color: #198754; color: white; }
    .btn-modern-warning { background-color: #ffc107; color: #111; }
    .btn-modern-danger { background-color: #dc3545; color: white; }
    .btn-modern-secondary { background-color: #6c757d; color: white; }
    .btn-modern-sky { background-color: #006699; color: white !important; }

    /* Estilo Cabecera Tabla (Original PAC) */
    .custom-dark-header th {
        background-color: #005666 !important; /* Azul petróleo más oscuro */
        color: white !important;
        border-bottom: 2px solid #003d4a !important;
        text-align: center !important;
        vertical-align: middle !important;
        font-size: 0.72rem !important;
        padding: 12px 5px !important;
        line-height: 1.2 !important;
        border: 1px solid rgba(255,255,255,0.1) !important;
        text-transform: uppercase;
        font-weight: 500; /* Menos que bold */
        letter-spacing: 0.3px;
    }

    .table-custom-compact td {
        padding: 4px 6px !important;
        font-size: 0.72rem !important;
        line-height: 1.2 !important;
        vertical-align: middle !important;
    }
    
    .table-bordered th, .table-bordered td {
        border-color: #dee2e6 !important;
    }
    .bg-light-sky { background-color: #e7f3ff !important; }
    
    .table-container {
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid #dee2e6;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }

    /* Botones de acción en tabla */
    .btn-table-action {
        width: 32px;
        height: 32px;
        padding: 0;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        border: none;
        margin: 0 2px;
    }
    .btn-table-action:hover {
        transform: scale(1.1);
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }
    .btn-table-edit { background-color: #e7f1ff; color: #0d6efd; }
    .btn-table-edit:hover { background-color: #0d6efd; color: white; }
    .btn-table-delete { background-color: #fff1f2; color: #dc3545; }
    .btn-table-delete:hover { background-color: #dc3545; color: white; }

    /* Tema Corporate Blue (Modal Registro) */
    .bg-sky { background-color: #006699 !important; color: white !important; }
    .text-sky { color: #006699 !important; }
    .border-sky { border-color: #006699 !important; }
    .btn-sky { 
        background: linear-gradient(135deg, #0db1dc 0%, #006699 100%) !important;
        color: white !important; 
        border: none !important;
        padding: 12px 30px !important;
        font-weight: 700 !important;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 15px rgba(0, 102, 153, 0.2);
    }
    .btn-sky:hover:not(:disabled) {
        transform: translateY(-2px) scale(1.02);
        box-shadow: 0 8px 25px rgba(0, 102, 153, 0.4);
        filter: brightness(1.1);
    }
    .btn-sky:active:not(:disabled) {
        transform: translateY(0) scale(0.98);
    }
    .btn-sky:disabled {
        background: #f0f5f9 !important;
        color: #b0c4de !important;
        border: 1px solid #d1e3f2 !important;
        cursor: not-allowed;
        box-shadow: none !important;
        transform: none !important;
    }

    /* Estilos Modal Importación Premium */
    .import-header {
        background: linear-gradient(135deg, #0084b0 0%, #006699 100%);
        border-radius: 15px 15px 0 0;
        padding: 25px;
        color: white;
    }
    
    /* TOOLTIP PERSONALIZADO (PREMIUM) */
    .tooltip-inner {
        background: linear-gradient(145deg, #ffffff 0%, #f4fcff 100%) !important; 
        color: #2c3e50 !important;
        border: 1px solid #cceeff !important;
        border-left: 5px solid #00607a !important;
        box-shadow: 0 8px 25px rgba(0, 96, 122, 0.25) !important; 
        font-size: 0.9rem !important;
        text-align: left;
        padding: 15px !important;
        max-width: 350px !important;
        opacity: 1 !important;
        border-radius: 6px !important;
    }
    .tooltip.show { opacity: 1 !important; } 
    .tooltip-arrow { display: none !important; }

    .import-dropzone {
        border: 2px dashed #006699;
        border-radius: 12px;
        background: #f8fbff;
        padding: 40px 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        margin: 20px 0;
    }
    .import-dropzone:hover, .import-dropzone.dragover {
        background: #ebf5ff;
        border-color: #0084b0;
        transform: scale(1.01);
    }
    .import-icon-cloud {
        font-size: 50px;
        color: #006699;
        margin-bottom: 15px;
    }
    .file-select-custom {
        max-width: 500px;
        margin: 0 auto;
        display: flex;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        overflow: hidden;
        background: white;
    }
    .file-select-btn {
        background: #f8f9fa;
        border-right: 1px solid #dee2e6;
        padding: 8px 15px;
        font-weight: 600;
        color: #444;
        cursor: pointer;
    }
    .file-select-name {
        padding: 8px 15px;
        color: #777;
        flex-grow: 1;
        text-align: left;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .btn-template-outline {
        border: 1.5px solid #0d6efd;
        color: #0d6efd;
        background: white;
        padding: 8px 25px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
    }
    .btn-template-outline:hover {
        background: #0d6efd;
        color: white;
    }
    /* Personalización Tooltip (Toolkit) */
    .tooltip {
        --bs-tooltip-bg: #1e293b;
        --bs-tooltip-max-width: 450px;
        --bs-tooltip-padding-x: 12px;
        --bs-tooltip-padding-y: 8px;
    }
    .tooltip-inner {
        text-align: left !important;
        font-size: 0.72rem;
        line-height: 1.4;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
  </style>
</head>
<body>
<?php
require_once 'layout/topbar.php';
require_once 'layout/sidebar.php';
?>

<div class="page-content" style="padding: 20px; padding-top: 80px;">
  
  <?php
  // Calcular métricas rápidas
  $sql_metricas = "SELECT 
                      COUNT(*) as total_procesos,
                      SUM(monto_comprometido) as total_comprometido,
                      SUM(monto_devengado) as total_devengado,
                      SUM(monto_girado) as total_girado
                   FROM seguimiento_menores_8uit WHERE anio = $anio";
  $res_metricas = $conexion->query($sql_metricas);
  $metricas = $res_metricas->fetch_assoc();
  
  $total_proc = $metricas['total_procesos'] ?? 0;
  $tot_comp = $metricas['total_comprometido'] ?? 0;
  $tot_dev = $metricas['total_devengado'] ?? 0;
  $tot_gir = $metricas['total_girado'] ?? 0;
  ?>
  
  <div class="page-head shadow-sm d-flex flex-column flex-xl-row justify-content-between align-items-start align-items-xl-center mb-4 gap-3">
    <div class="flex-shrink-0">
        <h4 class="mb-1 text-warning fw-bold">
            <i class="fa-solid fa-cart-shopping me-2"></i> Contrataciones Menores a 8 UIT
        </h4>
        <div class="subtitle">Módulo de Seguimiento de Órdenes de Compra/Servicio</div>
    </div>
    
    <!-- Indicadores Clave en Cabecera -->
    <div class="row g-2 flex-grow-1 w-100 mb-0 ms-xl-2">
        <div class="col-sm-6 col-lg-3">
            <div class="bg-light rounded shadow-sm border-start border-3 border-info px-3 py-2 d-flex flex-column justify-content-center h-100">
                <small class="text-secondary fw-bold text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Total Contratos</small>
                <div class="mb-0 text-info fw-bolder" style="font-size: 1.1rem;"><i class="fa-solid fa-list-check opacity-50 me-2"></i><?= number_format($total_proc) ?></div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="bg-light rounded shadow-sm border-start border-3 border-primary px-3 py-2 d-flex flex-column justify-content-center h-100">
                <small class="text-secondary fw-bold text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Imp. Comprometido</small>
                <div class="mb-0 text-primary fw-bolder" style="font-size: 1.1rem;"><span class="opacity-50 me-1" style="font-size: 0.85rem;">S/</span><?= number_format($tot_comp, 2) ?></div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="bg-light rounded shadow-sm border-start border-3 border-warning px-3 py-2 d-flex flex-column justify-content-center h-100">
                <small class="text-secondary fw-bold text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Imp. Devengado</small>
                <div class="mb-0 text-warning fw-bolder" style="font-size: 1.1rem;"><span class="opacity-50 me-1" style="font-size: 0.85rem;">S/</span><?= number_format($tot_dev, 2) ?></div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="bg-light rounded shadow-sm border-start border-3 border-success px-3 py-2 d-flex flex-column justify-content-center h-100">
                <small class="text-secondary fw-bold text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Imp. Girado</small>
                <div class="mb-0 text-success fw-bolder" style="font-size: 1.1rem;"><span class="opacity-50 me-1" style="font-size: 0.85rem;">S/</span><?= number_format($tot_gir, 2) ?></div>
            </div>
        </div>
    </div>
  </div>

    <div class="row">
      <div class="col-lg-12">
        <div class="card shadow-sm border-0 border-top border-warning border-4 rounded-3 mb-4">
          <div class="card-body p-4">
            

            <div class="d-flex justify-content-between align-items-center mb-0">
                <h5 class="card-title fw-bold mb-0 text-secondary">Listado de Contrataciones</h5>
                <div class="d-flex gap-2">
                    <?php if ($puede_importar_seg_men): ?>
                    <button type="button" class="btn btn-action px-3 shadow-sm rounded-3" id="btnAbrirImportar" style="background-color: #4a69bd; color: white; border: none; font-size: 0.75rem; font-weight: 600;">
                        <i class="fa-solid fa-file-import me-1"></i> IMPORTAR
                    </button>
                    <?php endif; ?>

                    <a href="../controlador/reporte_menores_excel.php?anio=<?= $anio ?>&estado=<?= $estado_filtro ?>&q=<?= urlencode($search_query) ?>" class="btn btn-action px-3 shadow-sm rounded-3" style="background-color: #218c74; color: white; border: none; font-size: 0.75rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center;">
                        <i class="fa-solid fa-file-excel me-1"></i> EXCEL
                    </a>
                    <a href="../controlador/reporte_menores_pdf.php?anio=<?= $anio ?>&estado=<?= $estado_filtro ?>&q=<?= urlencode($search_query) ?>" target="_blank" class="btn btn-action px-3 shadow-sm rounded-3" style="background-color: #b33939; color: white; border: none; font-size: 0.75rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center;">
                        <i class="fa-solid fa-file-pdf me-1"></i> PDF
                    </a>
                    <?php if ($puede_crear_seg_men): ?>
                    <button type="button" class="btn btn-action px-3 shadow-sm rounded-3" id="btnNuevoRegistro" style="background-color: #0c2461; color: white; border: none; font-size: 0.75rem; font-weight: 600;">
                        <i class="fa-solid fa-plus-circle me-1"></i> NUEVO REGISTRO
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Filtros básicos (placeholder) -->
            <div class="row g-2 mb-3">
                <div class="col-md-3">
                    <input type="text" id="searchInput" class="form-control form-control-sm" value="<?= htmlspecialchars($search_query) ?>" placeholder="Buscar por Nº OC/OS o descripción...">
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" id="filterEstado">
                        <option value="">Todos los Estados</option>
                        <option value="1" <?= $estado_filtro == 1 ? 'selected' : '' ?>>Pendiente</option>
                        <option value="2" <?= $estado_filtro == 2 ? 'selected' : '' ?>>En Ejecucion</option>
                        <option value="3" <?= $estado_filtro == 3 ? 'selected' : '' ?>>Entrego conforme</option>
                        <option value="4" <?= $estado_filtro == 4 ? 'selected' : '' ?>>No entrego conforme</option>
                        <option value="5" <?= $estado_filtro == 5 ? 'selected' : '' ?>>No entrego</option>
                        <option value="6" <?= $estado_filtro == 6 ? 'selected' : '' ?>>Anulado</option>
                        <option value="7" <?= $estado_filtro == 7 ? 'selected' : '' ?>>Culminado</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" id="btnBuscar" class="btn-modern btn-modern-secondary btn-sm"><i class="fa-solid fa-magnifying-glass me-1"></i> Buscar</button>
                </div>
            </div>

            <div class="table-container bg-white">
                <table class="table table-bordered table-hover align-middle mb-0 table-custom-compact" style="width: 100%;">
                    <thead class="custom-dark-header">
                        <tr>
                            <th style="width: 30px;">#</th>
                            <th style="width: 90px;">TIPO</th>
                            <th style="min-width: 300px;">OBJETO DE LA CONTRATACION</th>
                            <th style="width: 150px;">ÁREA USUARIA</th>
                            <th style="width: 85px;">F. REQ.</th>
                            <th style="width: 85px;">F. ORDEN</th>
                            <th style="width: 60px;">PLAZO</th>
                            <th style="width: 85px;">F. VENCE</th>
                            <th style="width: 95px;">IMP. COMPR.</th>
                            <th style="width: 95px;">IMP. DEV.</th>
                            <th style="width: 95px;">IMP. GIRADO</th>
                            <th style="width: 90px;">ESTADO</th>
                            <th style="width: 120px;">OBS.</th>
                            <th style="width: 70px;">ACC.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Construir WHERE dinámico
                        $where = "WHERE m.anio = $anio";
                        if ($estado_filtro > 0) {
                            $where .= " AND m.estado_id = $estado_filtro";
                        }
                        if (!empty($search_query)) {
                            $s = $conexion->real_escape_string($search_query);
                            $where .= " AND (m.descripcion_servicio_bien LIKE '%$s%' OR m.id LIKE '%$s%')";
                        }

                        $sql_count = "SELECT COUNT(*) as total FROM seguimiento_menores_8uit m $where";
                        $res_count = $conexion->query($sql_count);
                        $total_registros = $res_count->fetch_assoc()['total'];
                        $total_paginas = ceil($total_registros / $por_pagina);

                        $sql = "SELECT m.*, e.nombre as estado_nombre 
                                FROM seguimiento_menores_8uit m
                                LEFT JOIN seguimiento_estados_menores e ON m.estado_id = e.id
                                $where
                                ORDER BY m.id DESC
                                LIMIT $por_pagina OFFSET $offset";
                        $res = $conexion->query($sql);
                        $contador = $offset;

                        if ($res && $res->num_rows > 0):
                            while ($row = $res->fetch_assoc()):
                                $contador++;
                                // Lógica de semáforos para badges
                                $badgeClass = 'bg-secondary';
                                switch($row['estado_id']) {
                                    case 1: $badgeStyle = 'background-color: #fcefdc; color: #a16207; border: 1px solid #fbd38d;'; break; // Pendiente (Ámbar suave)
                                    case 2: $badgeStyle = 'background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;'; break; // En Ejecucion (Azul suave)
                                    case 3: case 7: $badgeStyle = 'background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0;'; break; // Exito (Verde suave)
                                    case 4: case 5: $badgeStyle = 'background-color: #fee2e2; color: #b91c1c; border: 1px solid #fecaca;'; break; // Error (Rojo suave)
                                    case 6: $badgeStyle = 'background-color: #f3f4f6; color: #374151; border: 1px solid #d1d5db;'; break; // Anulado (Gris suave)
                                    default: $badgeStyle = 'background-color: #f9fafb; color: #6b7280; border: 1px solid #e5e7eb;';
                                }
                        ?>
                        <tr>
                            <td class="text-center text-muted" style="font-size: 0.65rem;"><?= $contador ?></td>
                            <td class="text-center text-dark" style="font-size: 0.65rem;">
                                <?= $row['tipo_orden'] == 'OC' ? 'BIENES' : 'SERVICIOS' ?>
                            </td>
                            <td class="text-dark">
                                <div style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; max-width: 450px; cursor: help; word-wrap: break-word;" 
                                     data-bs-toggle="tooltip" 
                                     data-bs-placement="top" 
                                     title="<?= htmlspecialchars($row['descripcion_servicio_bien']) ?>">
                                     <?= htmlspecialchars($row['descripcion_servicio_bien']) ?>
                                </div>
                            </td>
                            <td class="text-dark">
                                <div style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; font-size: 0.68rem; color: #666; cursor: help;"
                                     data-bs-toggle="tooltip" 
                                     data-bs-placement="top" 
                                     title="<?= htmlspecialchars($row['unidad_solicitante']) ?>">
                                    <?= htmlspecialchars($row['unidad_solicitante']) ?>
                                </div>
                            </td>
                            <td class="text-center text-dark" style="font-size: 0.65rem;"><?= $row['fecha_requerimiento'] ? date('d/m/Y', strtotime($row['fecha_requerimiento'])) : '-' ?></td>
                            <td class="text-center text-dark" style="font-size: 0.65rem;"><?= $row['fecha_emision'] ? date('d/m/Y', strtotime($row['fecha_emision'])) : '-' ?></td>
                            <td class="text-center text-dark" style="font-size: 0.65rem;"><?= $row['plazo_ejecucion_dias'] ?></td>
                            <td class="text-center text-dark" style="font-size: 0.65rem;"><?= $row['fecha_vencimiento'] ? date('d/m/Y', strtotime($row['fecha_vencimiento'])) : '-' ?></td>
                            <td class="text-end text-dark"><?= number_format($row['monto_comprometido'], 2) ?></td>
                            <td class="text-end text-dark"><?= number_format($row['monto_devengado'], 2) ?></td>
                            <td class="text-end text-dark"><?= number_format($row['monto_girado'], 2) ?></td>
                            <td class="text-center">
                                <span class="badge w-100 fw-normal" style="font-size: 0.6rem; padding: 4px 2px; <?= $badgeStyle ?>">
                                    <?= strtoupper($row['estado_nombre'] ?? 'S/E') ?>
                                </span>
                            </td>
                            <td class="text-dark"><div class="text-truncate" style="max-width: 120px; font-size: 0.65rem;" title="<?= $row['observaciones'] ?>"><?= $row['observaciones'] ?></div></td>
                             <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <?php if ($puede_editar_seg_men): ?>
                                    <button class="btn-table-action btn-table-edit btn-editar" title="Editar"
                                            data-id="<?= $row['id'] ?>"
                                            data-tipo="<?= $row['tipo_orden'] == 'OC' ? 'BIENES' : 'SERVICIOS' ?>"
                                            data-objeto="<?= htmlspecialchars($row['descripcion_servicio_bien']) ?>"
                                            data-area="<?= htmlspecialchars($row['unidad_solicitante']) ?>"
                                            data-f-req="<?= $row['fecha_requerimiento'] ?>"
                                            data-f-orden="<?= $row['fecha_emision'] ?>"
                                            data-plazo="<?= $row['plazo_ejecucion_dias'] ?>"
                                            data-f-final="<?= $row['fecha_vencimiento'] ?>"
                                            data-estado="<?= $row['estado_id'] ?>"
                                            data-comprometido="<?= $row['monto_comprometido'] ?>"
                                            data-devengado="<?= $row['monto_devengado'] ?>"
                                            data-girado="<?= $row['monto_girado'] ?>"
                                            data-obs="<?= htmlspecialchars($row['observaciones']) ?>"
                                            >
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <?php else: ?>
                                    <button class="btn-table-action btn-table-edit" title="Sin permiso" style="opacity: 0.5; cursor: not-allowed;" disabled>
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <?php endif; ?>

                                    <?php if ($puede_eliminar_seg_men): ?>
                                    <button class="btn-table-action btn-table-delete btn-eliminar" title="Eliminar" data-id="<?= $row['id'] ?>">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                    <?php else: ?>
                                    <button class="btn-table-action btn-table-delete" title="Sin permiso" style="opacity: 0.5; cursor: not-allowed;" disabled>
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        else: 
                        ?>
                        <tr>
                            <td colspan="14" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-folder-open fa-3x mb-3 opacity-50"></i>
                                <h5>No hay registros actualmente</h5>
                                <p class="mb-0">Las órdenes registradas aparecerán aquí con sus respectivos semáforos.</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Controles de Paginación -->
            <?php if ($total_paginas > 1): ?>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted small">
                    Mostrando <?= $offset + 1 ?> a <?= min($offset + $por_pagina, $total_registros) ?> de <?= $total_registros ?> registros
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <!-- Botón Anterior -->
                        <li class="page-item <?= ($pagina_actual <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?anio=<?= $anio ?>&estado=<?= $estado_filtro ?>&q=<?= urlencode($search_query) ?>&pagina=<?= $pagina_actual - 1 ?>">Anterior</a>
                        </li>
                        
                        <!-- Números de Página -->
                        <?php 
                        $start_page = max(1, $pagina_actual - 4);
                        $end_page = min($total_paginas, $start_page + 9);
                        if ($end_page - $start_page < 9 && $start_page > 1) {
                            $start_page = max(1, $end_page - 9);
                        }
                        
                        if ($start_page > 1): ?>
                            <li class="page-item"><a class="page-link" href="?anio=<?= $anio ?>&estado=<?= $estado_filtro ?>&q=<?= urlencode($search_query) ?>&pagina=1">1</a></li>
                            <?php if ($start_page > 2): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="page-item <?= ($i == $pagina_actual) ? 'active' : '' ?>">
                                <a class="page-link" href="?anio=<?= $anio ?>&estado=<?= $estado_filtro ?>&q=<?= urlencode($search_query) ?>&pagina=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($end_page < $total_paginas): ?>
                            <?php if ($end_page < $total_paginas - 1): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                            <li class="page-item"><a class="page-link" href="?anio=<?= $anio ?>&estado=<?= $estado_filtro ?>&q=<?= urlencode($search_query) ?>&pagina=<?= $total_paginas ?>"><?= $total_paginas ?></a></li>
                        <?php endif; ?>

                        <!-- Botón Siguiente -->
                        <li class="page-item <?= ($pagina_actual >= $total_paginas) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?anio=<?= $anio ?>&estado=<?= $estado_filtro ?>&q=<?= urlencode($search_query) ?>&pagina=<?= $pagina_actual + 1 ?>">Siguiente</a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>

          </div>
        </div>
      </div>
    </div>
</div>

<div class="modal fade" id="modalNuevoMenores" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-sky py-3 border-bottom shadow-sm">
                <h5 class="modal-title fw-bold text-white"><i class="fa-solid fa-plus-circle me-2"></i> Nuevo Registro - Menores a 8 UIT</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3 px-3 bg-light">
                <form id="formNuevoMenores">
                    <input type="hidden" name="id_registro" id="id_registro">
                    
                    <div class="row g-3">
                        <!-- Columna Izquierda: Información y Plazos -->
                        <div class="col-md-7">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-white py-2 border-bottom border-light">
                                    <h6 class="mb-0 fw-bold text-secondary small"><i class="fa-solid fa-circle-info me-2 text-sky"></i> Datos de la Orden</h6>
                                </div>
                                <div class="card-body p-3">
                                    <div class="row g-3">
                                        <div class="col-md-5">
                                            <label class="form-label fw-600 small mb-1">Tipo de Contratación <span class="text-danger">*</span></label>
                                            <select name="tipo_contratacion" class="form-select" required>
                                                <option value="">Seleccione...</option>
                                                <option value="BIENES">BIENES</option>
                                                <option value="SERVICIOS">SERVICIOS</option>
                                            </select>
                                        </div>
                                        <div class="col-md-7">
                                            <label class="form-label fw-600 small mb-1">F. de Requerimiento</label>
                                            <input type="date" name="fecha_requerimiento" class="form-control">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-600 small mb-1">Área Usuaria</label>
                                            <input type="text" name="area_usuaria" class="form-control" placeholder="Unidad solicitante..." required>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-600 small mb-1">Objeto (Descripción) <span class="text-danger">*</span></label>
                                            <textarea name="objeto_contratacion" class="form-control" rows="2" placeholder="Detalle del bien o servicio..." required></textarea>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-600 small mb-1">F. de OC/OS</label>
                                            <input type="date" id="fecha_orden" name="fecha_orden" class="form-control">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-600 small mb-1">Plazo (Días)</label>
                                            <input type="number" id="plazo_dias" name="plazo_dias" class="form-control" placeholder="0" min="0">
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label fw-600 small mb-1">F. Final Ejecución</label>
                                            <input type="date" id="fecha_final" name="fecha_final" class="form-control">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Columna Derecha: Estados y Montos -->
                        <div class="col-md-5">
                            <div class="card border-0 shadow-sm mb-3">
                                <div class="card-header bg-white py-2 border-bottom border-light">
                                    <h6 class="mb-0 fw-bold text-secondary small"><i class="fa-solid fa-gears me-2 text-sky"></i> Estado y Control</h6>
                                </div>
                                <div class="card-body p-3">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label fw-600 small mb-1">Estado Actual</label>
                                            <select name="estado" class="form-select">
                                                <option value="1">Pendiente</option>
                                                <option value="2">En Ejecucion</option>
                                                <option value="3">Entrego conforme</option>
                                                <option value="4">No entrego conforme</option>
                                                <option value="5">No entrego</option>
                                                <option value="6">Anulado</option>
                                                <option value="7">Culminado</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white py-2 border-bottom border-light">
                                    <h6 class="mb-0 fw-bold text-secondary small"><i class="fa-solid fa-coins me-2 text-sky"></i> Importes (S/)</h6>
                                </div>
                                <div class="card-body p-3">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label fw-600 small mb-1">Imp. Comprometido</label>
                                            <div class="input-group">
                                                <span class="input-group-text">S/</span>
                                                <input type="number" name="imp_comprometido" class="form-control" step="0.01" value="0.00">
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-600 small mb-1">Imp. Devengado</label>
                                            <div class="input-group">
                                                <span class="input-group-text">S/</span>
                                                <input type="number" name="imp_devengado" class="form-control" step="0.01" value="0.00">
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-600 small mb-1">Imp. Girado</label>
                                            <div class="input-group">
                                                <span class="input-group-text">S/</span>
                                                <input type="number" name="imp_girado" class="form-control" step="0.01" value="0.00">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Fila Inferior: Observaciones -->
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-2">
                                    <label class="form-label fw-600 small mb-1 text-secondary">Observaciones Adicionales</label>
                                    <textarea name="observaciones" class="form-control" rows="1" placeholder="Opcional..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
            <div class="modal-footer bg-light border-top py-2">
                <button type="button" class="btn btn-secondary px-4 fw-bold shadow-sm" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" id="btnGuardarNuevoMenores" class="btn-modern btn-modern-sky px-4 fw-bold shadow-sm">
                    <i class="fa-solid fa-save me-1"></i> Guardar Registro
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Importación Premium -->
<div class="modal fade" id="modalImportarExcel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <!-- Header con Gradiente como en la imagen -->
            <div class="import-header d-flex align-items-center">
                <div class="me-3 flex-shrink-0">
                    <img src="../public/images/logo_regpol.png" style="width: 55px; height: 55px; object-fit: contain;">
                </div>
                <div class="flex-grow-1">
                    <h4 class="mb-0 fw-bold"><i class="fa-solid fa-file-excel me-2"></i> Importar Contrataciones desde Excel</h4>
                    <p class="mb-0 opacity-75 small text-white">Carga tu archivo Excel con la estructura de contrataciones menores a 8 UIT</p>
                </div>
                <button type="button" class="btn-close btn-close-white align-self-start" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4 text-center">
                <!-- Dropzone Interactivo -->
                <div class="import-dropzone" id="dropzoneImport">
                    <i class="fa-solid fa-cloud-arrow-up import-icon-cloud"></i>
                    <h5 class="fw-bold text-dark">Selecciona tu archivo Excel</h5>
                    <p class="text-muted small mb-4">Formatos permitidos: .xlsx, .xls</p>
                    
                    <div class="file-select-custom mb-2 shadow-sm" id="containerSelectFile">
                        <div class="file-select-btn">Seleccionar archivo</div>
                        <div class="file-select-name" id="fileNameDisplay">Sin archivos seleccionados</div>
                    </div>
                </div>
                <input type="file" id="inputExcelImport" style="display: none;" accept=".xlsx, .xls">

                <!-- Botón de Plantilla -->
                <div class="mt-4">
                    <a href="../controlador/reporte_menores_plantilla.php" class="btn-template-outline">
                        <i class="fa-solid fa-download me-2 text-primary"></i> Descargar Plantilla Excel
                    </a>
                </div>

                <div class="mt-4 text-muted small d-flex align-items-center justify-content-center">
                    <i class="fa-solid fa-lightbulb text-warning me-2 fs-5"></i> 
                    <span>Descarga la plantilla con los encabezados correctos y ejemplos</span>
                </div>
            </div>

            <div class="modal-footer border-0 pb-4 pt-0 d-flex justify-content-between px-4">
                <div>
                    <?php if ($_SESSION['rol'] === 'Super Administrador'): ?>
                    <button type="button" class="btn btn-outline-danger btn-sm fw-bold px-3 shadow-sm" id="btnLimpiarTodo" title="Eliminar todos los registros actuales">
                        <i class="fa-solid fa-trash-can me-1"></i> Limpiar BD
                    </button>
                    <?php endif; ?>
                </div>
                <div class="d-flex">
                    <button type="button" class="btn btn-light px-4 fw-bold rounded-3 me-2 text-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-sky px-5 fw-bold rounded-pill shadow" id="btnFinalizarImportacion" disabled>
                        <i class="fa-solid fa-circle-check me-2"></i> Subir e Importar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Estilo para campos con data (Reutilizado de PAC) -->
<style>
    .field-filled {
        background-color: #e7f3ff !important;
        border-color: #c2d9ef !important;
    }
    .field-filled-label {
        color: #006699 !important;
        font-weight: 600;
    }
    .small { font-size: 0.85rem; }
</style>



<script>
$(document).ready(function() {
    console.log("Sistema de Seguimiento de Menores Inicializado");

    // Inicializar Tooltips Premium
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Abrir Modal - Importar (Delegación directa)
    $(document).on('click', '#btnAbrirImportar', function(e) {
        e.preventDefault();
        $('#modalImportarExcel').modal('show');
    });

    // Abrir Modal - Nuevo (Delegación directa)
    $(document).on('click', '#btnNuevoRegistro', function(e) {
        e.preventDefault();
        $('#formNuevoMenores')[0].reset();
        $('#id_registro').val('');
        $('.modal-title').html('<i class="fa-solid fa-plus-circle me-2"></i> Nuevo Registro - Menores a 8 UIT');
        $('#modalNuevoMenores').modal('show');
        highlightFilledFields();
    });

    // Filtros
    $('#btnBuscar').click(function() {
        const query = $('#searchInput').val();
        const est = $('#filterEstado').val();
        const anio = '<?= $anio ?>';
        window.location.href = `seguimiento_menores.php?anio=${anio}&estado=${est}&q=${encodeURIComponent(query)}&pagina=1`;
    });

    $('#searchInput').keypress(function(e) {
        if(e.which == 13) $('#btnBuscar').click();
    });

    // Abrir Modal - Editar
    $(document).on('click', '.btn-editar', function() {
        const btn = $(this);
        $('#id_registro').val(btn.data('id'));
        $('select[name="tipo_contratacion"]').val(btn.data('tipo'));
        $('input[name="fecha_requerimiento"]').val(btn.data('f-req'));
        $('input[name="area_usuaria"]').val(btn.data('area'));
        $('textarea[name="objeto_contratacion"]').val(btn.data('objeto'));
        
        // Asignar y disparar cálculos
        const fOrden = btn.data('f-orden') || '';
        const fFinal = btn.data('f-final') || '';
        const plazo = btn.data('plazo') || 0;

        $('#fecha_orden').val(fOrden);
        $('#plazo_dias').val(plazo);
        $('#fecha_final').val(fFinal);
        
        $('select[name="estado"]').val(btn.data('estado'));
        $('input[name="imp_comprometido"]').val(btn.data('comprometido'));
        $('input[name="imp_devengado"]').val(btn.data('devengado'));
        $('input[name="imp_girado"]').val(btn.data('girado'));
        $('textarea[name="observaciones"]').val(btn.data('obs'));

        $('.modal-title').html('<i class="fa-solid fa-pen-to-square me-2"></i> Editar Registro - Menores a 8 UIT');
        $('#modalNuevoMenores').modal('show');
        
        // Forzar iluminación de campos
        highlightFilledFields();
        
        // Ejecutar recálculo de plazo SOLO si ambos existen para corregir el 0
        if (fOrden && fFinal) {
            recalculatePlazo();
        } else if (fOrden && plazo > 0) {
            updateFechaFinal();
        }
    });

    $('#dropzoneImport, #containerSelectFile').on('click', function(e) {
        e.stopPropagation();
        $('#inputExcelImport').trigger('click');
    });

    $('#inputExcelImport').on('change', function() {
        const file = this.files[0];
        if (file) {
            $('#fileNameDisplay').text(file.name).addClass('text-dark fw-bold');
            $('#btnFinalizarImportacion').prop('disabled', false).addClass('shadow-sm');
        } else {
            $('#fileNameDisplay').text('Sin archivos seleccionados').removeClass('text-dark fw-bold');
            $('#btnFinalizarImportacion').prop('disabled', true).removeClass('shadow-sm');
        }
    });

    // Drag & Drop
    const dropzone = document.getElementById('dropzoneImport');
    if (dropzone) {
        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.classList.add('dragover');
        });
        dropzone.addEventListener('dragleave', () => {
            dropzone.classList.remove('dragover');
        });
        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('dragover');
            const file = e.dataTransfer.files[0];
            if (file && (file.name.endsWith('.xlsx') || file.name.endsWith('.xls'))) {
                document.getElementById('inputExcelImport').files = e.dataTransfer.files;
                $('#fileNameDisplay').text(file.name).addClass('text-dark fw-bold');
                $('#btnFinalizarImportacion').prop('disabled', false);
            } else {
                Swal.fire('Error', 'Solo se permiten archivos Excel (.xlsx, .xls)', 'error');
            }
        });
    }

    $('#btnFinalizarImportacion').on('click', function() {
        const file = $('#inputExcelImport')[0].files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('archivo_excel', file);

        Swal.fire({
            title: '¿Iniciar importación?',
            text: "Se procesarán todos los registros del archivo.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#006699',
            confirmButtonText: 'Sí, importar ahora'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Importando...',
                    text: 'Esto puede demorar unos segundos.',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                $.ajax({
                    url: '../controlador/importar_menores.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('¡Éxito!', response.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Error crítico al procesar la importación.', 'error');
                    }
                });
            }
        });
    });

    // Eliminar Registro
    $(document).on('click', '.btn-eliminar', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción no se puede deshacer",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#006699',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../controlador/eliminar_menores.php',
                    type: 'POST',
                    data: { id: id },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Eliminado',
                                text: response.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    }
                });
            }
        });
    });

    // Funciones de cálculo exportadas para ser llamadas desde el botón editar
    let isUpdatingDates = false;

    function updateFechaFinal() {
        if (typeof isUpdatingDates === 'undefined') window.isUpdatingDates = false;
        if (isUpdatingDates) return;
        const fechaIni = $('#fecha_orden').val();
        const diasStr = $('#plazo_dias').val();
        
        if (fechaIni && (diasStr !== '' || diasStr === '0')) {
            isUpdatingDates = true;
            const dias = parseInt(diasStr);
            if (!isNaN(dias)) {
                let [y, m, d] = fechaIni.split('-');
                let date = new Date(y, m - 1, d);
                // Lógica Inclusiva: 1 día termina el mismo día. Si es 0 días, es lo mismo para evitar errores.
                const offset = dias > 0 ? dias - 1 : 0;
                date.setDate(date.getDate() + offset);
                
                let yyyy = date.getFullYear();
                let mm = String(date.getMonth() + 1).padStart(2, '0');
                let dd = String(date.getDate()).padStart(2, '0');
                $('#fecha_final').val(`${yyyy}-${mm}-${dd}`);
            }
            isUpdatingDates = false;
        }
        highlightFilledFields();
    }

    function recalculatePlazo() {
        if (isUpdatingDates) return;
        const fechaIni = $('#fecha_orden').val();
        const fechaFin = $('#fecha_final').val();
        
        if (fechaIni && fechaFin && fechaIni !== 'null' && fechaFin !== 'null') {
            isUpdatingDates = true;
            try {
                let [y1, m1, d1] = fechaIni.split('-');
                let [y2, m2, d2] = fechaFin.split('-');
                
                let utcIni = Date.UTC(y1, m1 - 1, d1);
                let utcFin = Date.UTC(y2, m2 - 1, d2);
                
                // Lógica Inclusiva: Diferencia de días + 1
                const msPerDay = 1000 * 60 * 60 * 24;
                let diffDays = Math.floor((utcFin - utcIni) / msPerDay) + 1;
                
                // Si la fecha final es menor a la inicial, forzamos 1 día
                if (diffDays < 1) diffDays = 1;
                
                $('#plazo_dias').val(diffDays);
            } catch (e) {
                console.error("Error en cálculo de plazo:", e);
            }
            isUpdatingDates = false;
        }
        highlightFilledFields();
    }

    $('#fecha_orden').on('keyup change input', function() {
        if ($('#fecha_final').val()) {
            recalculatePlazo();
        } else {
            updateFechaFinal();
        }
    });

    $('#plazo_dias').on('keyup change input', updateFechaFinal);
    $('#fecha_final').on('keyup change input', recalculatePlazo);

    // Resaltado de campos con data
    function highlightFilledFields() {
        $('#formNuevoMenores input, #formNuevoMenores select, #formNuevoMenores textarea').each(function() {
            const val = $(this).val();
            const isFilled = (val !== null && val !== "" && val !== "0" && val !== "0.00");
            
            if (isFilled && !$(this).prop('readonly')) {
                $(this).addClass('field-filled');
                $(this).closest('div').find('label').addClass('field-filled-label');
            } else {
                $(this).removeClass('field-filled');
                $(this).closest('div').find('label').removeClass('field-filled-label');
            }
        });
    }

    // Trigger resaltado en tiempo real
    $('#formNuevoMenores input, #formNuevoMenores select, #formNuevoMenores textarea').on('input change', function() {
        highlightFilledFields();
    });

    // Guardar (AJAX)
    $('#btnGuardarNuevoMenores').click(function() {
        const tipo = $('select[name="tipo_contratacion"]').val();
        const objeto = $('textarea[name="objeto_contratacion"]').val();

        if (!tipo || !objeto.trim()) {
            Swal.fire({
                icon: 'warning',
                title: 'Campos Obligatorios',
                text: 'Por favor complete el Tipo de Contratación y el Objeto antes de guardar.',
                confirmButtonColor: '#006699'
            });
            return;
        }

        const formData = $('#formNuevoMenores').serialize();

        $.ajax({
            url: '../controlador/guardar_menores.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message,
                        confirmButtonColor: '#006699'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de servidor',
                    text: 'No se pudo procesar la solicitud.',
                    confirmButtonColor: '#006699'
                });
            }
        });
    });

    // --- ELIMINACIÓN MASIVA ---
    $(document).on('click', '#btnLimpiarTodo', function() {
        $('#modalImportarExcel').modal('hide');
        Swal.fire({
            title: '¿Estás completamente seguro?',
            text: "Esta acción ELIMINARÁ PERMANENTEMENTE todos los registros de contrataciones menores. ¡Proceda con extrema precaución!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, ELIMINAR TODO',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Segunda confirmación por seguridad
                Swal.fire({
                    title: 'CONFIRMACIÓN FINAL',
                    text: 'Escribe "ELIMINAR" para confirmar el vaciado total:',
                    input: 'text',
                    inputPlaceholder: 'ELIMINAR',
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'VACIAR BASE DE DATOS',
                    cancelButtonText: 'Cancelar',
                    preConfirm: (value) => {
                        if (value !== 'ELIMINAR') {
                            Swal.showValidationMessage('Debes escribir "ELIMINAR" para continuar');
                        }
                    }
                }).then((secondResult) => {
                    if (secondResult.isConfirmed) {
                        Swal.fire({
                            title: 'Eliminando...',
                            text: 'Procesando limpieza total de registros.',
                            allowOutsideClick: false,
                            didOpen: () => { Swal.showLoading(); }
                        });

                        $.ajax({
                            url: '../controlador/eliminar_todo_menores.php',
                            type: 'POST',
                            dataType: 'json',
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire('¡Éxito!', response.message, 'success').then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire('Error', response.message, 'error').then(() => {
                                        $('#modalImportarExcel').modal('show');
                                    });
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('Error:', error);
                                console.error('Response:', xhr.responseText);
                                Swal.fire('Error', 'No se pudo vaciar la base de datos. Verifique permisos.', 'error').then(() => {
                                    $('#modalImportarExcel').modal('show');
                                });
                            }
                        });
                    } else {
                        $('#modalImportarExcel').modal('show');
                    }
                });
            } else {
                $('#modalImportarExcel').modal('show');
            }
        });
    });

});
</script>

<?php require_once 'layout/footer.php'; ?>
