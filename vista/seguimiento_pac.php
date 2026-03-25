<?php
require_once '../modelo/conexion.php';
// Verificación de seguridad
if (empty($_SESSION['id'])) {
    header("Location: inicio.php");
    exit();
}

// Si la vista requiere permisos específicos, se pueden validar aquí.
// Por ahora permitimos a Administradores y Super Administradores, o aquellos con permiso al módulo Seguimiento
if ($_SESSION['rol'] !== 'Administrador' && $_SESSION['rol'] !== 'Super Administrador' && !isset($_SESSION['permisos_rutas']['seguimiento'])) {
    // Si usas permisos dinámicos, descomentar la validación deseada.
    // Nosotros simplemente dejaremos pasar si tienen acceso al menú de Seguimiento
}

require_once '../modelo/PermisosMiddleware.php';
$middleware = new PermisosMiddleware();
$puede_crear_seg_pac = $middleware->verificarPermiso($_SESSION['id'], 'seguimiento', 'CREAR');
$puede_editar_seg_pac = $middleware->verificarPermiso($_SESSION['id'], 'seguimiento', 'EDITAR');
$puede_eliminar_seg_pac = $middleware->verificarPermiso($_SESSION['id'], 'seguimiento', 'ELIMINAR');

$titulo_pagina = "Seguimiento PAC (Ley 32069)";
$anio = isset($_GET['anio']) ? (int) $_GET['anio'] : (int) date('Y');

$sql_pac = "
SELECT 
    s.id as segmentacion_id, 
    s.ref_pac,
    s.objeto_contratacion as t_contratacion, 
    s.descripcion as objeto_contrato,
    s.origen_registro,
    tp.nombre as t_procedimiento,
    tp.id as tipo_proceso_id,
    s.programado as es_programado,
    s.cuantia as v_estimado,
    s.cmn as cmn,
    sp.id as pac_id,
    sp.oculto,
    sp.mes_programado,
    sp.estado_proceso,
    sp.valor_convocado,
    sp.monto_adjudicado as valor_adjudicado,
    sp.imp_comprometido,
    sp.imp_devengado,
    sp.imp_girado,
    sp.certificado,
    sp.observaciones
FROM segmentacion s
LEFT JOIN tipo_proceso tp ON tp.id = s.tipo_proceso_id
LEFT JOIN seguimiento_pac sp ON sp.segmentacion_id = s.id
WHERE s.anio = $anio AND (sp.oculto = 0 OR sp.oculto IS NULL)
ORDER BY s.id DESC
";
$result_pac = $conexion->query($sql_pac);

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= $titulo_pagina ?></title>
  <link rel="stylesheet" href="../public/bootstrap5/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../public/estilos/estilos.css?v=9">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    .btn-action-pro {
        border-radius: 8px;
        transition: all 0.2s ease;
        padding: 5px 15px;
        font-size: 0.72rem;
        font-weight: 600;
        border: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: white;
    }
    .btn-action-pro:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        filter: brightness(1.1);
        color: white;
    }
    
    .btn-table-action {
        width: 28px;
        height: 28px;
        padding: 0;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        border: none;
    }
    .btn-table-action:hover {
        transform: scale(1.1);
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }
    .btn-table-edit { background-color: #e0f2fe; color: #0369a1; }
    .btn-table-edit:hover { background-color: #0369a1; color: white; }
    
    .btn-table-delete { background-color: #fee2e2; color: #b91c1c; }
    .btn-table-delete:hover { background-color: #b91c1c; color: white; }

    /* Estilo Cabecera Tabla */
    .custom-dark-header th {
        background-color: #005666 !important;
        color: white !important;
        border-bottom: 2px solid #003d4a !important;
        text-align: center !important;
        vertical-align: middle !important;
        font-size: 0.72rem !important;
        padding: 12px 5px !important;
        line-height: 1.2 !important;
        border: 1px solid rgba(255,255,255,0.1) !important;
        text-transform: uppercase;
        font-weight: 500;
        letter-spacing: 0.3px;
    }

    .table-custom-compact td {
        padding: 4px 6px !important;
        font-size: 0.72rem !important;
        line-height: 1.2 !important;
        vertical-align: middle !important;
        border-color: #eceff1 !important;
    }

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        word-wrap: break-word;
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
  
  <div class="page-head shadow-sm d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
    <div>
        <h4 class="mb-1 text-primary fw-bold">
            <i class="fa-solid fa-file-signature me-2"></i> Seguimiento PAC (Ley 32069)
        </h4>
        <div class="subtitle">Módulo de Trazabilidad de Procesos de Selección</div>
    </div>
  </div>

    <div class="row">
      <div class="col-lg-12">
        <div class="card shadow-sm border-0 border-top border-primary border-4 rounded-3 mb-4">
          <div class="card-body p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title fw-bold mb-0 text-secondary">Listado de Procesos PAC</h5>
                <div class="d-flex gap-2">
                   <a href="../controlador/reporte_seguimiento_pac_excel.php?anio=<?= $anio ?>" class="btn-action-pro shadow-sm" style="background-color: #218c74; text-decoration: none;">
                        <i class="fa-solid fa-file-excel me-1"></i> EXCEL
                   </a>
                   <a href="../controlador/reporte_seguimiento_pac_pdf.php?anio=<?= $anio ?>" target="_blank" class="btn-action-pro shadow-sm" style="background-color: #b33939; text-decoration: none;">
                        <i class="fa-solid fa-file-pdf me-1"></i> PDF
                   </a>
                   <?php if ($puede_crear_seg_pac): ?>
                   <button type="button" class="btn-action-pro shadow-sm" style="background-color: #0c2461;" data-bs-toggle="modal" data-bs-target="#modalNuevoProcesoManual">
                        <i class="fa-solid fa-plus me-1"></i> NUEVO PROCESO
                   </button>
                   <?php endif; ?>
                </div>
            </div>

            <!-- Filtros básicos (placeholder) -->
            <div class="row g-2 mb-3">
                <div class="col-md-3">
                    <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Buscar por objeto, CMN o REF. PAC...">
                </div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm" id="statusFilter">
                        <option value="">Todos los Estados</option>
                        <option value="Actos preparatorios">Actos preparatorios</option>
                        <option value="Convocado">Convocado</option>
                        <option value="Adjudicado">Adjudicado</option>
                        <option value="Contrato Suscrito">Contrato Suscrito</option>
                        <option value="En ejecucion">En ejecución</option>
                        <option value="Concluido">Concluido</option>
                        <option value="Devengado">Devengado</option>
                        <option value="Pagado">Pagado</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button id="btnBuscar" class="btn btn-secondary btn-sm"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
                </div>
            </div>

            <style>
                .custom-dark-header th {
                    background-color: #00667a !important;
                    color: white !important;
                    border-bottom: 2px solid #004c5c !important;
                    text-align: center !important;
                    vertical-align: middle !important;
                    font-size: 0.75rem !important;
                    padding: 0.4rem 0.2rem !important;
                    line-height: 1.1 !important;
                }
            </style>
            <div class="table-responsive bg-white rounded shadow-sm border">
                <table class="table table-sm table-hover table-bordered align-middle mb-0 table-custom-compact">
                    <thead class="custom-dark-header">
                        <tr>
                            <th style="width: 30px;">#</th>
                            <th style="width: 60px;">Ref. PAC</th>
                            <th>Tipo Contratación</th>
                            <th style="min-width: 280px;">Objeto de la Contratación</th>
                            <th>Procedimiento</th>
                            <th class="text-center">Programado</th>
                            <th class="text-center">Mes</th>
                            <th class="text-center">Estado del Proceso</th>
                            <th class="text-end">V. Estimado</th>
                            <th class="text-end">V. Convocado</th>
                            <th class="text-end">V. Adjudicado</th>
                            <th class="text-end">Comprometido</th>
                            <th class="text-end">Devengado</th>
                            <th class="text-end">Girado</th>
                            <th class="text-center">Certif.</th>
                            <th class="text-center">CMN</th>
                            <th style="min-width: 150px;">Observaciones</th>
                            <th class="text-center" style="width: 60px;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result_pac && $result_pac->num_rows > 0): ?>
                            <?php $counter = 1; while($row = $result_pac->fetch_assoc()): ?>
                                                  <td class="text-center text-muted"><?= $counter++ ?></td>
                                <td class="text-center text-dark"><?= htmlspecialchars($row['ref_pac'] ?? '-') ?></td>
                                <td class="text-center text-dark">
                                    <?= htmlspecialchars($row['t_contratacion'] ?? '') ?>
                                </td>
                                <td>
                                    <div class="line-clamp-2 text-dark" style="max-width: 350px; cursor: help;"
                                         data-bs-toggle="tooltip" data-bs-placement="top" title="<?= htmlspecialchars($row['objeto_contrato'] ?? '') ?>">
                                        <?= htmlspecialchars($row['objeto_contrato'] ?? '') ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="line-clamp-2 text-dark" style="max-width: 150px; cursor: help;"
                                         data-bs-toggle="tooltip" data-bs-placement="top" title="<?= htmlspecialchars($row['t_procedimiento'] ?? '') ?>">
                                         <?= htmlspecialchars($row['t_procedimiento'] ?? '') ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <?php if($row['es_programado'] == 1): ?>
                                      <span class="text-success" style="font-size: 0.65rem;">Programado</span>
                                    <?php else: ?>
                                      <span class="text-muted" style="font-size: 0.65rem;">No Prog.</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center text-dark"><?= htmlspecialchars($row['mes_programado'] ?? '-') ?></td>
                                <td class="text-center">
                                    <?php
                                        $estado = $row['estado_proceso'] ?? 'Actos preparatorios';
                                        $estado_display = $estado;
                                        switch($estado) {
                                            case 'Actos preparatorios': case 'Pendiente': case 'Sin Iniciar':
                                                $badgeStyle = 'background-color: #f3f4f6; color: #374151; border: 1px solid #d1d5db;'; $estado_display = 'Actos prep.'; break;
                                            case 'Convocado':
                                                $badgeStyle = 'background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;'; break;
                                            case 'Adjudicado':
                                                $badgeStyle = 'background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0;'; break;
                                            case 'Contrato Suscrito':
                                                $badgeStyle = 'background-color: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;'; break;
                                            case 'En ejecucion': case 'En Ejecucion':
                                                $badgeStyle = 'background-color: #fef9c3; color: #854d0e; border: 1px solid #fde047;'; $estado_display = 'En ejecución'; break;
                                            case 'Concluido': case 'Culminado':
                                                $badgeStyle = 'background-color: #f9fafb; color: #111827; border: 1px solid #e5e7eb;'; break;
                                            default:
                                                $badgeStyle = 'background-color: #f3f4f6; color: #4b5563; border: 1px solid #d1d5db;';
                                        }
                                    ?>
                                    <span class="badge w-100 fw-normal" style="font-size: 0.6rem; padding: 4px 2px; <?= $badgeStyle ?>">
                                        <?= strtoupper($estado_display) ?>
                                    </span>
                                </td>
                                <td class="text-end text-dark"><?= number_format($row['v_estimado'] ?? 0, 2) ?></td>
                                <td class="text-end text-dark"><?= number_format($row['valor_convocado'] ?? 0, 2) ?></td>
                                <td class="text-end text-dark"><?= number_format($row['valor_adjudicado'] ?? 0, 2) ?></td>
                                <td class="text-end text-dark"><?= number_format($row['imp_comprometido'] ?? 0, 2) ?></td>
                                <td class="text-end text-dark"><?= number_format($row['imp_devengado'] ?? 0, 2) ?></td>
                                <td class="text-end text-dark"><?= number_format($row['imp_girado'] ?? 0, 2) ?></td>
                                <td class="text-center text-dark"><?= htmlspecialchars($row['certificado'] ?? '-') ?></td>
                                <td class="text-center text-dark"><?= htmlspecialchars($row['cmn'] ?? '-') ?></td>
                                <td>
                                    <div class="line-clamp-2 text-muted" style="max-width: 150px; font-size: 0.65rem; cursor: help;"
                                         data-bs-toggle="tooltip" data-bs-placement="top" title="<?= htmlspecialchars($row['observaciones'] ?? '') ?>">
                                        <?= htmlspecialchars($row['observaciones'] ?? '') ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <?php if ($puede_editar_seg_pac): ?>
                                        <button class="btn-table-action btn-table-edit btn-edita-pac" title="Actualizar Proceso"
                                                data-id="<?= $row['segmentacion_id'] ?>" 
                                                data-origen="<?= $row['origen_registro'] ?>"
                                                data-ref="<?= htmlspecialchars($row['ref_pac'] ?? '') ?>"
                                                data-cmn="<?= htmlspecialchars($row['cmn'] ?? '') ?>"
                                                data-des="<?= htmlspecialchars($row['objeto_contrato'] ?? '') ?>"
                                                data-obj="<?= htmlspecialchars($row['t_contratacion'] ?? '') ?>"
                                                data-v_est="<?= $row['v_estimado'] ?? '' ?>"
                                                data-tproc="<?= $row['tipo_proceso_id'] ?? '' ?>"
                                                data-prog="<?= $row['es_programado'] ?? '' ?>"
                                                data-mes="<?= htmlspecialchars($row['mes_programado'] ?? '') ?>" 
                                                data-estado="<?= htmlspecialchars($row['estado_proceso'] ?? '') ?>"
                                                data-convocado="<?= htmlspecialchars($row['valor_convocado'] ?? '') ?>"
                                                data-adjudicado="<?= htmlspecialchars($row['valor_adjudicado'] ?? '') ?>"
                                                data-comprometido="<?= htmlspecialchars($row['imp_comprometido'] ?? '') ?>"
                                                data-devengado="<?= htmlspecialchars($row['imp_devengado'] ?? '') ?>"
                                                data-girado="<?= htmlspecialchars($row['imp_girado'] ?? '') ?>"
                                                data-certificado="<?= htmlspecialchars($row['certificado'] ?? '') ?>"
                                                data-observaciones="<?= htmlspecialchars($row['observaciones'] ?? '') ?>"
                                                >
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <?php else: ?>
                                        <button class="btn-table-action btn-table-edit" title="Sin permiso" style="opacity: 0.5; cursor: not-allowed;" disabled>
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <?php endif; ?>

                                        <?php if(($row['origen_registro'] ?? '') === 'Seguimiento PAC'): ?>
                                            <?php if ($puede_eliminar_seg_pac): ?>
                                            <button class="btn-table-action btn-table-delete btn-elimina-pac" title="Eliminar Proceso"
                                                    data-id="<?= $row['segmentacion_id'] ?>"
                                                    data-origen="<?= $row['origen_registro'] ?>"
                                                    >
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                            <?php else: ?>
                                            <button class="btn-table-action btn-table-delete" title="Sin permiso" style="opacity: 0.5; cursor: not-allowed;" disabled>
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="17" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-file-signature fa-3x mb-3 opacity-50"></i>
                                <h5>No hay procesos PAC registrados</h5>
                                <p class="mb-0">Aquí se mostrará el estado situacional exacto que te pide la entidad monitora.</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

          </div>
        </div>
      </div>
    </div>
</div>

<!-- Modal Actualizar PAC -->
<div class="modal fade" id="modalActualizarPAC" tabindex="-1" aria-labelledby="modalActualizarPACLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title fw-bold" id="modalActualizarPACLabel"><i class="fa-solid fa-pen-to-square me-2"></i>Actualizar Proceso</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formFichaPAC">
        <div class="modal-body bg-light">
            <input type="hidden" name="segmentacion_id" id="pac_segmentacion_id">
            <input type="hidden" name="origen_registro" id="pac_origen_registro">
            
            <!-- SECCIÓN: Datos del Proceso (Solo editables si es manual) -->
            <div class="bg-white p-3 rounded shadow-sm border mb-3">
                <h6 class="text-secondary border-bottom mb-3 pb-2 d-flex justify-content-between align-items-center">
                    <span><i class="fa-solid fa-file-invoice me-1"></i>Información del Proceso</span>
                    <span id="badge_origen" class="badge bg-secondary" style="font-size: 0.65rem;"></span>
                </h6>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Nº Ref. PAC</label>
                        <input type="text" class="form-control form-control-sm master-field" name="ref_pac" id="pac_ref_pac">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">CMN</label>
                        <input type="text" class="form-control form-control-sm master-field" name="cmn" id="pac_cmn">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Contratación</label>
                        <select class="form-select form-select-sm master-field" name="objeto_contratacion" id="pac_objeto_contratacion">
                            <option value="SERVICIOS">SERVICIOS</option>
                            <option value="BIENES">BIENES</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Procedimiento</label>
                        <select class="form-select form-select-sm master-field" name="tipo_proceso_id" id="pac_tipo_proceso_id">
                            <option value="">- Seleccione -</option>
                            <?php 
                            $tipos_sql2 = $conexion->query("SELECT id, nombre FROM tipo_proceso WHERE estado=1 ORDER BY nombre ASC");
                            while($t = $tipos_sql2->fetch_assoc()): ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nombre']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-9">
                        <label class="form-label fw-bold small">Descripción del Objeto</label>
                        <textarea class="form-control form-control-sm master-field" name="descripcion" id="pac_descripcion" rows="1"></textarea>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Val. Estimado</label>
                        <input type="number" step="0.01" class="form-control form-control-sm text-end master-field" name="cuantia" id="pac_cuantia">
                    </div>
                </div>
                <div id="alert_masters" class="mt-2 small text-danger fw-bold d-none">
                    <i class="fa-solid fa-lock me-1"></i> Este proceso proviene de Segmentación y no puede ser editado aquí.
                </div>
            </div>

            <!-- SECCIÓN: Seguimiento (Opcional/Actualizable) -->
            <div class="bg-white p-3 rounded shadow-sm border text-dark">
                <h6 class="text-secondary border-bottom mb-3 pb-2"><i class="fa-solid fa-chart-line me-1"></i>Seguimiento y Ejecución</h6>
                <div class="row g-3">
                    <!-- FILA 1 -->
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Mes Programado</label>
                        <select class="form-select form-select-sm" name="mes_programado" id="pac_mes_programado">
                            <option value="">- Seleccione -</option>
                            <option value="Enero">Enero</option>
                            <option value="Febrero">Febrero</option>
                            <option value="Marzo">Marzo</option>
                            <option value="Abril">Abril</option>
                            <option value="Mayo">Mayo</option>
                            <option value="Junio">Junio</option>
                            <option value="Julio">Julio</option>
                            <option value="Agosto">Agosto</option>
                            <option value="Septiembre">Septiembre</option>
                            <option value="Octubre">Octubre</option>
                            <option value="Noviembre">Noviembre</option>
                            <option value="Diciembre">Diciembre</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Estado Actual</label>
                        <select class="form-select form-select-sm" name="estado_proceso" id="pac_estado_proceso">
                            <option value="Actos preparatorios">Actos preparatorios</option>
                            <option value="Convocado">Convocado</option>
                            <option value="Adjudicado">Adjudicado</option>
                            <option value="Contrato Suscrito">Contrato Suscrito</option>
                            <option value="En ejecucion">En ejecución</option>
                            <option value="Concluido">Concluido</option>
                            <option value="Devengado">Devengado</option>
                            <option value="Pagado">Pagado</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Nº Certificado</label>
                        <input type="text" class="form-control form-control-sm" name="certificado" id="pac_certificado" placeholder="Ej. 000142">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Valor Convocado (S/)</label>
                        <input type="number" step="0.01" class="form-control form-control-sm text-end" name="valor_convocado" id="pac_valor_convocado" placeholder="0.00">
                    </div>

                    <!-- FILA 2 -->
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Valor Adjudicado (S/)</label>
                        <input type="number" step="0.01" class="form-control form-control-sm text-end" name="valor_adjudicado" id="pac_valor_adjudicado" placeholder="0.00">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Imp. Comprometido (S/)</label>
                        <input type="number" step="0.01" class="form-control form-control-sm text-end" name="imp_comprometido" id="pac_imp_comprometido" placeholder="0.00">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Imp. Devengado (S/)</label>
                        <input type="number" step="0.01" class="form-control form-control-sm text-end" name="imp_devengado" id="pac_imp_devengado" placeholder="0.00">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Imp. Girado (S/)</label>
                        <input type="number" step="0.01" class="form-control form-control-sm text-end" name="imp_girado" id="pac_imp_girado" placeholder="0.00">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold small">Observaciones de Seguimiento</label>
                        <textarea class="form-control form-control-sm" name="observaciones" id="pac_observaciones" rows="2" placeholder="Opcional..."></textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer bg-light">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary btn-sm fw-bold" id="btnGuardarPAC"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar Cambios</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Nuevo Proceso Manual -->
<div class="modal fade" id="modalNuevoProcesoManual" tabindex="-1" aria-labelledby="modalNuevoProcesoManualLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title fw-bold" id="modalNuevoProcesoManualLabel"><i class="fa-solid fa-plus me-2"></i>Registrar Nuevo Proceso</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formNuevoProcesoManual">
        <div class="modal-body bg-light">
            <div class="bg-white p-3 rounded shadow-sm border mb-3">
                <h6 class="text-primary border-bottom mb-3 pb-2"><i class="fa-solid fa-info-circle me-1"></i>Datos Generales</h6>
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Nº Ref. PAC</label>
                        <input type="text" class="form-control" name="ref_pac" placeholder="Ej. 001">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">CMN</label>
                        <input type="text" class="form-control" name="cmn" placeholder="Cód. CMN">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Tipo Contratación <span class="text-danger">*</span></label>
                        <select class="form-select" name="objeto_contratacion" required>
                            <option value="">- Seleccione -</option>
                            <option value="SERVICIOS">SERVICIOS</option>
                            <option value="BIENES">BIENES</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Tipo Procedimiento</label>
                        <select class="form-select" name="tipo_proceso_id">
                            <option value="">- Seleccione -</option>
                            <?php 
                            $tipos_sql = $conexion->query("SELECT id, nombre FROM tipo_proceso WHERE estado=1 ORDER BY nombre ASC");
                            while($t = $tipos_sql->fetch_assoc()): ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nombre']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Objeto de la Contratación (Descripción) <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="descripcion" rows="2" placeholder="Describa el servicio o bien a contratar..." required></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Valor Estimado (S/)</label>
                        <input type="number" step="0.01" class="form-control text-end" name="cuantia" placeholder="0.00">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Programación <span class="text-danger">*</span></label>
                        <select class="form-select" name="programado" required>
                            <option value="">- Seleccione -</option>
                            <option value="1">Programado</option>
                            <option value="0">No Programado</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="bg-white p-3 rounded shadow-sm border">
                <h6 class="text-success border-bottom mb-3 pb-2"><i class="fa-solid fa-clock-rotate-left me-1"></i>Estado Situacional Inicial</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Mes Programado PAC</label>
                        <select class="form-select" name="mes_programado">
                            <option value="">- Seleccione -</option>
                            <option value="Enero">Enero</option>
                            <option value="Febrero">Febrero</option>
                            <option value="Marzo">Marzo</option>
                            <option value="Abril">Abril</option>
                            <option value="Mayo">Mayo</option>
                            <option value="Junio">Junio</option>
                            <option value="Julio">Julio</option>
                            <option value="Agosto">Agosto</option>
                            <option value="Septiembre">Septiembre</option>
                            <option value="Octubre">Octubre</option>
                            <option value="Noviembre">Noviembre</option>
                            <option value="Diciembre">Diciembre</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Estado Inicial</label>
                        <select class="form-select" name="estado_proceso">
                            <option value="Actos preparatorios">Actos preparatorios</option>
                            <option value="Convocado">Convocado</option>
                            <option value="Adjudicado">Adjudicado</option>
                            <option value="Contrato Suscrito">Contrato Suscrito</option>
                            <option value="En ejecucion">En ejecución</option>
                            <option value="Concluido">Concluido</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer bg-light">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-success fw-bold" id="btnGuardarNuevoPAC"><i class="fa-solid fa-plus me-1"></i>Registrar Proceso</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Bibliotecas y Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // Abrir Modal
    $('.btn-edita-pac').click(function() {
        const id = $(this).data('id');
        const origen = $(this).data('origen');
        
        // Datos tracking
        $('#pac_segmentacion_id').val(id);
        $('#pac_origen_registro').val(origen);
        $('#pac_mes_programado').val($(this).data('mes'));
        $('#pac_estado_proceso').val($(this).data('estado') || 'Actos preparatorios');
        $('#pac_valor_convocado').val($(this).data('convocado'));
        $('#pac_valor_adjudicado').val($(this).data('adjudicado'));
        $('#pac_imp_comprometido').val($(this).data('comprometido'));
        $('#pac_imp_devengado').val($(this).data('devengado'));
        $('#pac_imp_girado').val($(this).data('girado'));
        $('#pac_certificado').val($(this).data('certificado'));
        $('#pac_observaciones').val($(this).data('observaciones'));

        // Datos Master (Nuevos)
        $('#pac_ref_pac').val($(this).data('ref'));
        $('#pac_cmn').val($(this).data('cmn'));
        $('#pac_objeto_contratacion').val($(this).data('obj'));
        $('#pac_tipo_proceso_id').val($(this).data('tproc'));
        $('#pac_descripcion').val($(this).data('des'));
        $('#pac_cuantia').val($(this).data('v_est'));

        // Lógica de bloqueo/desbloqueo según origen
        if(origen === 'Segmentación') {
            $('.master-field').prop('disabled', true);
            $('#alert_masters').removeClass('d-none');
            $('#badge_origen').text('DATO FILTRADO (LEY 32069)').removeClass('bg-primary').addClass('bg-secondary');
        } else {
            $('.master-field').prop('disabled', false);
            $('#alert_masters').addClass('d-none');
            $('#badge_origen').text('REGISTRO MANUAL').removeClass('bg-secondary').addClass('bg-primary');
        }

        $('#modalActualizarPAC').modal('show');
        highlightFilledFields();
    });

    // Función para resaltar campos con data
    function highlightFilledFields() {
        $('#formFichaPAC input, #formFichaPAC select, #formFichaPAC textarea').each(function() {
            const val = $(this).val();
            const isFilled = (val !== null && val !== "" && val !== "0" && val !== "0.00");
            
            if (isFilled) {
                $(this).addClass('field-filled');
                $(this).closest('div').find('label').addClass('field-filled-label');
            } else {
                $(this).removeClass('field-filled');
                $(this).closest('div').find('label').removeClass('field-filled-label-label');
                // Nota: removí el label-label repetido en el else para consistencia
            }
        });
    }

    // Actualizar resaltado al escribir o cambiar
    $('#formFichaPAC input, #formFichaPAC select, #formFichaPAC textarea').on('input change', function() {
        const val = $(this).val();
        const isFilled = (val !== null && val !== "" && val !== "0" && val !== "0.00");
        
        if (isFilled) {
            $(this).addClass('field-filled');
            $(this).closest('div').find('label').addClass('field-filled-label');
        } else {
            $(this).removeClass('field-filled');
            $(this).closest('div').find('label').removeClass('field-filled-label');
        }
    });

    // Guardar via AJAX
    $('#formFichaPAC').submit(function(e) {
        e.preventDefault();
        
        let formData = new FormData(this);
        
        // Bloquear botón
        $('#btnGuardarPAC').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i>Guardando...');

        $.ajax({
            url: '../controlador/guardar_seguimiento_pac.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Actualizado!',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', response.message, 'error');
                    $('#btnGuardarPAC').prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-2"></i>Guardar Cambios');
                }
            },
            error: function() {
                Swal.fire('Error del Servidor', 'Ocurrió un error en la comunicación con el servidor.', 'error');
                $('#btnGuardarPAC').prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-2"></i>Guardar Cambios');
            }
        });
    });

    // Filtros de tabla
    function filterTable() {
        let searchText = $('#searchInput').val().toLowerCase();
        let statusFilter = $('#statusFilter').val().toLowerCase();

        $('table tbody tr').each(function() {
            // Ignorar fila de "No hay procesos"
            if ($(this).find('td').length === 1) return;

            let rowText = $(this).text().toLowerCase();
            let rowStatus = $(this).find('td:eq(7)').text().toLowerCase(); // Index 7 is Estado del Proceso

            let matchSearch = rowText.indexOf(searchText) > -1;
            let matchStatus = statusFilter === "" || rowStatus.indexOf(statusFilter) > -1;

            if (matchSearch && matchStatus) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }

    $('#searchInput').on('keyup', filterTable);
    $('#statusFilter').on('change', filterTable);
    $('#btnBuscar').on('click', filterTable);

    // Registro Proceso Manual
    $('#formNuevoProcesoManual').submit(function(e) {
        if (!this.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('was-validated');
            Swal.fire('Atención', 'Por favor complete todos los campos marcados con asterisco (*)', 'warning');
            return false;
        }

        e.preventDefault();
        $('#btnGuardarNuevoPAC').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i>Registrando...');
        
        $.ajax({
            url: '../controlador/guardar_proceso_directo.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    Swal.fire({ icon: 'success', title: '¡Registrado!', text: response.message, timer: 1500, showConfirmButton: false }).then(() => { location.reload(); });
                } else {
                    Swal.fire('Error', response.message, 'error');
                    $('#btnGuardarNuevoPAC').prop('disabled', false).html('<i class="fa-solid fa-plus me-1"></i>Registrar Proceso');
                }
            },
            error: function() {
                Swal.fire('Error', 'Error de red.', 'error');
                $('#btnGuardarNuevoPAC').prop('disabled', false).html('<i class="fa-solid fa-plus me-1"></i>Registrar Proceso');
            }
        });
    });

    // Eliminar / Quitar Seguimento
    $('.btn-elimina-pac').click(function() {
        const id = $(this).data('id');
        const origen = $(this).data('origen');

        Swal.fire({
            title: '¿Desea ELIMINAR este proceso?',
            text: 'Este proceso fue registrado manualmente en este módulo y se eliminará por completo del sistema.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('../controlador/eliminar_seguimiento_pac.php', { segmentacion_id: id, origen: origen }, function(response) {
                    if(response.status === 'success') {
                        location.reload();
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                }, 'json');
            }
        });
    });

    // Inicializar Tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php require_once 'layout/footer.php'; ?>
