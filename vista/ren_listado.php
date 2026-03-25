<?php
// vista/ren_listado.php
require_once __DIR__ . '/../modelo/conexion.php';

if (empty($_SESSION['id']) || (!userCan('rendiciones') && !in_array($_SESSION['rol'], ['Super Administrador', 'Administrador']))) {
    header("Location: " . (defined('BASE_URL') ? BASE_URL : '/sistem_job/') . "vista/inicio.php");
    exit();
}

$titulo_pagina = "Gestión de Rendiciones de Viáticos";
include 'layout/topbar.php';
include 'layout/sidebar.php';
?>

<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    body {
        font-family: 'Outfit', sans-serif;
        background-color: #f4f7f6;
    }


    /* Estilo Premium de Tablas */
    .table-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        /* Quitamos overflow para que el dropdown no se corte */
    }

    .table thead th {
        background: #003666;
        color: white !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.65rem;
        padding: 8px 10px !important;
        vertical-align: middle;
    }

    .table tbody td {
        padding: 10px 12px;
        font-size: 0.78rem;
        border: 1px solid #f1f3f5 !important;
        vertical-align: middle;
    }

    /* Stats Cards Slim */
    .card-stat {
        background: white;
        border-radius: 10px;
        border: none;
        padding: 12px 20px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        display: flex;
        align-items: center;
        gap: 15px;
        transition: transform 0.3s;
    }

    .card-stat:hover {
        transform: translateY(-3px);
    }

    .icon-stat {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .stat-content h5 {
        font-size: 0.75rem;
        margin-bottom: 2px;
    }

    .stat-content h2 {
        font-size: 1.5rem;
        line-height: 1;
    }

    /* Badges */
    .badge-p {
        background: #E8F4FD;
        color: #2E86C1;
        border: 1px solid #AED6F1;
        font-weight: 600;
        padding: 5px 12px;
        border-radius: 20px;
    }

    .badge-r {
        background: #E9F7EF;
        color: #1E8449;
        border: 1px solid #A9DFBF;
        font-weight: 600;
        padding: 5px 12px;
        border-radius: 20px;
    }

    .badge-o {
        background: #FEF5E7;
        color: #D68910;
        border: 1px solid #FAD7A0;
        font-weight: 600;
        padding: 5px 12px;
        border-radius: 20px;
    }

    .btn-action {
        width: 28px;
        height: 28px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        font-size: 0.8rem;
        transition: all 0.2s;
        border: 1px solid transparent;
        margin: 0;
    }

    .btn-action:hover {
        transform: translateY(-2px) scale(1.1);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .btn-done {
        background: #e8f5e9;
        color: #2e7d32;
        border-color: #a5d6a7;
    }

    .btn-done:hover {
        background: #2e7d32;
        color: white !important;
        box-shadow: 0 4px 10px rgba(46, 125, 50, 0.3);
    }

    .btn-obs {
        background: #fff3e0;
        color: #ef6c00;
        border-color: #ffccbc;
    }

    .btn-obs:hover {
        background: #ef6c00;
        color: white !important;
        box-shadow: 0 4px 10px rgba(239, 108, 0, 0.3);
    }

    .btn-pend {
        background: #e1f5fe;
        color: #0288d1;
        border-color: #b3e5fc;
    }

    .btn-pend:hover {
        background: #0288d1;
        color: white !important;
        box-shadow: 0 4px 10px rgba(2, 136, 209, 0.3);
    }

    .btn-del {
        background: #ffebee;
        color: #d32f2f;
        border-color: #ffcdd2;
    }

    .btn-del:hover {
        background: #d32f2f;
        color: white !important;
        box-shadow: 0 4px 10px rgba(211, 47, 47, 0.3);
    }

    .btn-edit {
        background: #f3e5f5;
        color: #8e24aa;
        border-color: #e1bee7;
    }

    .btn-edit:hover {
        background: #8e24aa;
        color: white !important;
        box-shadow: 0 4px 10px rgba(142, 36, 170, 0.3);
    }

    .btn-notify {
        background: #fff5f5;
        color: #e53935;
        border-color: #ffcdd2;
    }

    .btn-notify:hover {
        background: #e53935;
        color: white !important;
        box-shadow: 0 4px 10px rgba(229, 57, 53, 0.3);
    }

    /* Botones de Lote con Viday Estilo Premium */
    .btn-lote-pdf {
        background: linear-gradient(135deg, #FF512F 0%, #DD2476 100%);
        color: white !important;
        border: none;
        box-shadow: 0 3px 6px rgba(221, 36, 118, 0.3);
        transition: all 0.3s;
    }

    .btn-lote-pdf:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 12px rgba(221, 36, 118, 0.4);
        filter: brightness(1.1);
    }
    .btn-lote-pdf, .btn-lote-del {
        margin-left: 8px !important;
        border-radius: 8px !important;
    }

    .btn-lote-del {
        background: linear-gradient(135deg, #4b6cb7 0%, #182848 100%);
        color: white !important;
        border: none;
        box-shadow: 0 3px 6px rgba(24, 40, 72, 0.3);
        transition: all 0.3s;
    }

    .btn-lote-del:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 12px rgba(24, 40, 72, 0.4);
        filter: brightness(1.1);
    }

    /* Resaltado de campos con data */
    .field-filled {
        background-color: #e3f2fd !important;
        border-color: #bbdefb !important;
        color: #333 !important;
    }

    /* Ajuste de altura de los labels */
    .modal-body label.form-label {
        margin-bottom: 0.25rem !important;
    }

    /* Aumentar altura de los campos (celdas) */
    .modal-body .form-control, 
    .modal-body .form-select {
        padding-top: 0.6rem !important;
        padding-bottom: 0.6rem !important;
        height: auto !important;
    }
</style>

<div class="page-content">
    <div class="container-fluid">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i>Rendiciones de Viáticos</h4>
                <p class="text-muted small mb-0">Gestión contable de comisiones de servicio - Año <span id="displayAnio"><?= ANIO_FISCAL ?></span></p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary shadow-sm fw-bold px-4 rounded-pill" data-bs-toggle="modal"
                    data-bs-target="#modalNuevo">
                    <i class="fa-solid fa-plus me-2"></i> Nueva Rendición
                </button>
                <button class="btn btn-success shadow-sm fw-bold px-4 rounded-pill" data-bs-toggle="modal"
                    data-bs-target="#modalImportar">
                    <i class="fa-solid fa-file-import me-2"></i> Importar
                </button>
                <button class="btn btn-info shadow-sm fw-bold px-4 rounded-pill text-white" onclick="exportarExcel()">
                    <i class="fa-solid fa-file-excel me-2"></i> Exportar
                </button>
                <button class="btn btn-danger shadow-sm fw-bold px-4 rounded-pill" onclick="generarReporteGeneral()">
                    <i class="fa-solid fa-file-pdf me-2"></i> Reporte PDF
                </button>
                <button class="btn btn-warning shadow-sm fw-bold px-4 rounded-pill" onclick="abrirConfiguracion()">
                    <i class="fa-solid fa-gear me-2"></i> Configurar
                </button>
            </div>
        </div>

        <!-- Stats Section Compacta -->
        <div class="card border-0 shadow-sm mb-3 overflow-hidden" style="border-radius: 15px;">
            <div class="card-body p-0">
                <div class="row g-0 align-items-center">
                    <!-- Total Card -->
                    <div class="col-md-2 p-3 border-end text-center bg-light bg-opacity-50">
                        <small class="text-muted fw-bold text-uppercase d-block mb-1" style="font-size: 0.65rem; letter-spacing: 1px;">Total Comisiones</small>
                        <div class="d-flex align-items-center justify-content-center gap-2">
                            <i class="fa-solid fa-users text-primary opacity-50"></i>
                            <h3 class="fw-bold m-0 text-dark" id="statTotal" style="letter-spacing: -1px;">-</h3>
                        </div>
                    </div>
                    
                    <!-- Estados Grid -->
                    <div class="col-md-7 px-4">
                        <div class="row text-center">
                            <div class="col-4 border-end py-3">
                                <small class="text-muted d-block mb-1" style="font-size: 0.65rem; font-weight: 700;">PENDIENTES</small>
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <span class="badge rounded-pill bg-warning bg-opacity-10 text-warning px-2 py-1" id="pctPendientes" style="font-size: 0.7rem;">0%</span>
                                    <h4 class="fw-bold m-0 text-warning" id="statPendientes">-</h4>
                                </div>
                            </div>
                            <div class="col-4 border-end py-3">
                                <small class="text-muted d-block mb-1" style="font-size: 0.65rem; font-weight: 700;">RENDIDOS</small>
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <span class="badge rounded-pill bg-success bg-opacity-10 text-success px-2 py-1" id="pctRendidos" style="font-size: 0.7rem;">0%</span>
                                    <h4 class="fw-bold m-0 text-success" id="statRendidos">-</h4>
                                </div>
                            </div>
                            <div class="col-4 py-3">
                                <small class="text-muted d-block mb-1" style="font-size: 0.65rem; font-weight: 700;">OBSERVADOS</small>
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <span class="badge rounded-pill px-2 py-1" id="pctObservados" style="font-size: 0.7rem; background: rgba(255, 100, 0, 0.1); color: #ff6400;">0%</span>
                                    <h4 class="fw-bold m-0" id="statObservados" style="color: #ff6400;">-</h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Chart Compacto -->
                    <div class="col-md-3 p-2 bg-light bg-opacity-25 border-start d-flex align-items-center justify-content-center" style="min-height: 100px;">
                        <div style="width: 80px; height: 80px; flex-shrink: 0;">
                            <canvas id="chartEstados"></canvas>
                        </div>
                        <div class="ms-2 lh-1">
                            <h6 class="m-0 fw-bold text-muted" style="font-size: 0.62rem; letter-spacing: 0.5px;">ESTADO<br>ACTUAL</h6>
                            <span class="text-success" style="font-size: 0.55rem; font-weight: 600;"><i class="fa-solid fa-sync fa-spin-hover"></i> ONLINE</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Filters -->
        <div class="table-container p-3">
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i
                                class="fa-solid fa-search text-muted"></i></span>
                        <input type="text" id="customSearch" class="form-control border-start-0"
                            placeholder="Buscar...">
                    </div>
                </div>
                <div class="col-md-2">
                    <select id="filterAnio" class="form-select">
                        <option value="<?= ANIO_FISCAL ?>">Año <?= ANIO_FISCAL ?></option>
                        <option value="<?= ANIO_FISCAL - 1 ?>">Año <?= ANIO_FISCAL - 1 ?></option>
                        <option value="<?= ANIO_FISCAL - 2 ?>">Año <?= ANIO_FISCAL - 2 ?></option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="filterEstado" class="form-select">
                        <option value="">-- Todos los Estados --</option>
                        <option value="Pendiente">Pendiente</option>
                        <option value="Rendido">Rendido</option>
                        <option value="Observado">Observado</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="input-group">
                        <select id="filterGrupo" class="form-select">
                            <option value="">-- Todos los Lotes --</option>
                        </select>
                        <button class="btn btn-lote-pdf" type="button" onclick="notificarGrupo()" title="Notificar a TODO el Lote filtrado">
                            <i class="fa-solid fa-file-pdf"></i>
                        </button>
                        <button class="btn btn-lote-del" type="button" onclick="eliminarLote()" title="ELIMINAR TODO ESTE LOTE">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Main Table -->
            <table id="tablaRendiciones" class="table table-hover table-bordered w-100">
                <thead>
                    <tr>
                        <th class="text-center">N°</th>
                        <th class="text-center">DNI</th>
                        <th class="text-center">CIP</th>
                        <th class="text-center">GRADO</th>
                        <th>PERSONAL</th>
                        <th>LUGAR COMISIÓN</th>
                        <th>REGIÓN/DIVISIÓN</th>
                        <th>UNIDAD</th>
                        <th>FECHA INI/FIN</th>
                        <th class="text-center">TOTAL DEP. S/</th>
                        <th class="text-center">ESTADO</th>
                        <th>LOTE/GRUPO</th>
                        <th class="text-center">HT/REF</th>
                        <th class="text-center">ACCIONES</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Registro Individual -->
<div class="modal fade" id="modalNuevo" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header text-white p-3"
                style="background-color: #003666; border-bottom: 2px solid #0056a3;">
                <div class="d-flex align-items-center">
                    <img src="../public/images/escudo.png" alt="PNP" style="width: 35px; height: auto;" class="me-3">
                    <div>
                        <h5 class="modal-title fw-bold m-0">Registro Individual de Rendición</h5>
                        <small class="opacity-75">Gestión manual de viáticos para el personal policial</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <form id="formNuevo">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white fw-bold"><i class="fa-solid fa-id-card text-primary me-2"></i>
                            Datos del Comisionado</div>
                        <div class="card-body row g-3">
                            <div class="col-md-2">
                                <label class="form-label fw-bold" style="font-size: 0.75rem;">DNI del Efectivo</label>
                                <div class="input-group">
                                    <input type="text" name="dni" id="dni_search" class="form-control" placeholder="DNI"
                                        maxlength="8" autocomplete="off" style="font-size: 0.85rem;">
                                    <button class="btn btn-outline-primary" type="button" onclick="buscarPorDni()"><i
                                            class="fa-solid fa-search"></i></button>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold" style="font-size: 0.75rem;">CIP</label>
                                <input type="text" name="cip" id="modal_cip" class="form-control" placeholder="CIP"
                                    autocomplete="off" style="font-size: 0.85rem;">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold" style="font-size: 0.75rem;">Grado</label>
                                <select name="grado" id="modal_grado_select" class="form-select" style="font-size: 0.85rem;">
                                    <option value="">-- Seleccionar --</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-bold" style="font-size: 0.75rem;">Apellidos y Nombres</label>
                                <input type="text" name="nombres" id="hidden_nombres" class="form-control"
                                    placeholder="Nombre Completo" autocomplete="off" style="font-size: 0.85rem;">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold" style="font-size: 0.75rem;">Región</label>
                                <select name="region_cache" id="modal_region" class="form-select form-select-sm" style="font-size: 0.85rem;">
                                    <option value="">-- Seleccionar --</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold" style="font-size: 0.75rem;">División / Divopus</label>
                                <select name="division_cache" id="modal_division" class="form-select form-select-sm" style="font-size: 0.85rem;">
                                    <option value="">-- Seleccionar --</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold" style="font-size: 0.75rem;">Unidad Actual</label>
                                <select name="unidad_nombre" id="modal_subunidad" class="form-select form-select-sm" style="font-size: 0.85rem;">
                                    <option value="">-- Seleccionar --</option>
                                </select>
                                <input type="hidden" name="id_subunidad" id="hidden_id_subunidad">
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white fw-bold"><i
                                class="fa-solid fa-map-location-dot text-primary me-2"></i> Información de la Comisión
                        </div>
                        <div class="card-body row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold" style="font-size: 0.75rem;">Lugar de Comisión</label>
                                <input type="text" name="lugar" class="form-control" placeholder="Ej: TRUJILLO"
                                    required style="font-size: 0.85rem;">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold" style="font-size: 0.75rem;">Fecha Inicio</label>
                                <input type="date" name="fecha_ini" class="form-control" required style="font-size: 0.85rem;">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold" style="font-size: 0.75rem;">Fecha Retorno</label>
                                <input type="date" name="fecha_ret" class="form-control" required style="font-size: 0.85rem;">
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white fw-bold"><i class="fa-solid fa-coins text-primary me-2"></i>
                            Datos Financieros</div>
                        <div class="card-body row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold" style="font-size: 0.75rem;">Expediente SIAF</label>
                                <input type="text" name="siaf" class="form-control" placeholder="Opcional" style="font-size: 0.85rem;">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold" style="font-size: 0.75rem;">Total Depositado (S/)</label>
                                <input type="number" step="0.01" name="total" class="form-control fw-bold text-success"
                                    required style="font-size: 0.85rem;">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold" style="font-size: 0.75rem;">N° Liquidación</label>
                                <input type="text" name="liq" class="form-control" style="font-size: 0.85rem;">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold text-primary" style="font-size: 0.75rem;">HT / REFERENCIA</label>
                                <input type="text" name="ht_ref" class="form-control border-primary" placeholder="Hoja de Trámite" style="font-size: 0.85rem;">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer p-4 border-0">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-bold border"
                    data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm"
                    onclick="guardarRegistroIndividual()">
                    <i class="fa-solid fa-save me-2"></i> Registrar Rendición
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Importar Excel (DISEÑO PREMIUM) -->
<div class="modal fade" id="modalImportar" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white p-4" style="background-color: #006691;">
                <div class="d-flex align-items-center">
                    <img src="../public/images/escudo.png" style="width: 40px; height: auto;" class="me-3">
                    <div>
                        <h5 class="modal-title fw-bold m-0"><i class="fa-solid fa-file-excel me-2"></i> Importar
                            Rendiciones</h5>
                        <small class="opacity-75">Sube tu archivo Excel de viáticos</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-5">
                <form id="formImportar">
                    <div id="dropZone"
                        class="drop-zone-custom d-flex flex-column align-items-center justify-content-center p-5 mb-4"
                        style="border: 2px dashed #009CD9; border-radius: 12px; background: #F4FBFE; cursor: pointer; transition: 0.3s; height: 260px; position: relative;">
                        <div class="text-center" id="dropZoneContent">
                            <i class="fa-solid fa-cloud-arrow-up text-primary mb-3" style="font-size: 3rem;"></i>
                            <h5 class="fw-bold">Selecciona o Arrastra tu Excel</h5>
                            <div class="mt-4 shadow-sm d-inline-flex border rounded-pill bg-white overflow-hidden">
                                <div class="px-4 py-2 bg-light border-end fw-bold">Archivo</div>
                                <div id="fileNameDisplay" class="px-4 py-2 text-muted" style="min-width: 250px;">Ninguno
                                    seleccionado</div>
                            </div>
                            <input type="file" name="archivo" id="fileInput"
                                class="position-absolute opacity-0 w-100 h-100 top-0 start-0" style="cursor: pointer;"
                                accept=".xlsx, .xls">
                        </div>
                    </div>

                    <!-- Campo opcional para nombre de remesa -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small"><i class="fa-solid fa-tag me-1"></i> NOMBRE DE LA REMESA / LOTE (OPCIONAL)</label>
                        <input type="text" name="nombre_lote" id="nombre_lote" class="form-control rounded-pill border-primary shadow-sm" placeholder="Ej: REMESA TRUJILLO MARZO">
                        <div class="form-text small italic">Si lo dejas vacío, el sistema le pondrá uno automático: <b>LOTE_DDMMYYYYv01</b></div>
                    </div>
                </form>
                <div id="progressContainer" class="mt-4" style="display: none;">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="small fw-bold text-primary" id="progressStatus">Subiendo archivo...</span>
                        <span class="small fw-bold text-primary" id="progressPercent">0%</span>
                    </div>
                    <div class="progress" style="height: 10px; border-radius: 5px; background-color: #e9ecef;">
                        <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" 
                            style="width: 0%; background-color: #006691;"></div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <button type="button" class="btn btn-outline-primary rounded-pill px-4 fw-bold shadow-sm"
                        onclick="window.location.href='../controlador/descargar_plantilla_viaticos.php'">
                        <i class="fa-solid fa-download me-2"></i> Descargar Plantilla
                    </button>
                </div>
            </div>
            <div class="modal-footer p-4 border-0 bg-light d-flex justify-content-between">
                <button type="button" class="btn btn-outline-danger shadow-sm fw-bold" onclick="confirmarLimpieza()">
                    <i class="fa-solid fa-trash-can me-2"></i> Limpiar BD
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light rounded-pill px-4 border"
                        data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" id="btnProcesar" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm"
                        disabled onclick="procesarImportacion()">
                        <i class="fa-solid fa-circle-check me-2"></i> Importar Ahora
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .drop-zone-custom:hover {
        background-color: #E8F7FE !important;
        border-color: #0084B5 !important;
    }

    .drop-zone-custom.dragover {
        background-color: #D1EFFF !important;
        transform: scale(1.005);
    }
</style>

</div>

<!-- Modal Editar -->
<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header text-white p-3" style="background-color: #6f42c1;">
                <div class="d-flex align-items-center">
                    <i class="fa-solid fa-pen-to-square fs-4 me-3"></i>
                    <div>
                        <h5 class="modal-title fw-bold m-0" style="font-size: 1.1rem;">Editar Rendición</h5>
                        <small class="opacity-75" style="font-size: 0.75rem;">Actualiza la unidad o los montos</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <form id="formEditar">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label fw-bold" style="font-size: 0.75rem;">DNI</label>
                            <input type="text" name="dni" id="edit_dni" class="form-control" style="font-size: 0.85rem;">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold" style="font-size: 0.75rem;">CIP</label>
                            <input type="text" name="cip" id="edit_cip" class="form-control" style="font-size: 0.85rem;">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold" style="font-size: 0.75rem;">Grado</label>
                            <select name="grado" id="edit_grado" class="form-select" style="font-size: 0.85rem;">
                                <option value="">-- Seleccionar --</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-bold" style="font-size: 0.75rem;">Apellidos y Nombres</label>
                            <input type="text" name="apellidos_nombres" id="edit_nombres" class="form-control" style="font-size: 0.85rem;">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold" style="font-size: 0.75rem;">Lugar de Comisión</label>
                            <input type="text" name="lugar_comision" id="edit_lugar" class="form-control" style="font-size: 0.85rem;">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold" style="font-size: 0.75rem;">Región Policial</label>
                            <select id="edit_region" class="form-select border-primary shadow-sm" style="font-size: 0.85rem;"></select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold" style="font-size: 0.75rem;">División / Divopus</label>
                            <select id="edit_division" class="form-select border-primary shadow-sm" style="font-size: 0.85rem;">
                                <option value="">-- Seleccionar --</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold" style="font-size: 0.75rem;">Unidad PNP</label>
                            <select name="id_subunidad" id="edit_subunidad" class="form-select border-primary shadow-sm" required style="font-size: 0.85rem;">
                                <option value="">-- Seleccionar --</option>
                            </select>
                        </div>

                        <!-- CAMPOS ADICIONALES -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold" style="font-size: 0.75rem;">Cuenta de Ahorros</label>
                            <input type="text" name="cuenta_ahorros" id="edit_cuenta" class="form-control border-primary shadow-sm" style="font-size: 0.85rem;">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold" style="font-size: 0.75rem;">Fecha Inicio</label>
                            <input type="date" name="fecha_inicio" id="edit_fecha_ini" class="form-control border-primary shadow-sm" required style="font-size: 0.85rem;">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold" style="font-size: 0.75rem;">Fecha Retorno</label>
                            <input type="date" name="fecha_retorno" id="edit_fecha_ret" class="form-control border-primary shadow-sm" required style="font-size: 0.85rem;">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold" style="font-size: 0.75rem;">N° Liquidación (LIQ.)</label>
                            <input type="text" name="nro_liquidacion" id="edit_liq" class="form-control shadow-sm" style="font-size: 0.85rem;">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold" style="font-size: 0.75rem;">IGV</label>
                            <input type="number" step="0.01" name="igv" id="edit_igv" class="form-control shadow-sm" style="font-size: 0.85rem;">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold" style="font-size: 0.75rem;">Días</label>
                            <input type="number" name="dias" id="edit_dias" class="form-control shadow-sm" style="font-size: 0.85rem;">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold" style="font-size: 0.75rem;">SIAF / Expediente</label>
                            <input type="text" name="siaf_expediente" id="edit_siaf" class="form-control shadow-sm" style="font-size: 0.85rem;">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold" style="font-size: 0.75rem;">1er Depósito (S/)</label>
                            <input type="number" step="0.01" name="primer_deposito" id="edit_deposito1" class="form-control text-success border-success shadow-sm" style="font-size: 0.85rem;">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold" style="font-size: 0.75rem;">Pasajes (S/)</label>
                            <input type="number" step="0.01" name="pasajes" id="edit_pasajes" class="form-control text-success border-success shadow-sm" style="font-size: 0.85rem;">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-primary" style="font-size: 0.75rem;">HT / REFERENCIA</label>
                            <input type="text" name="ht_ref" id="edit_ht_ref" class="form-control border-primary shadow-sm" style="font-size: 0.85rem;">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-dark" style="font-size: 0.75rem;">Total Depósitos (S/)</label>
                            <input type="number" step="0.01" name="total_depositado" id="edit_total" class="form-control fw-bold border-dark shadow-sm" required style="font-size: 0.85rem;">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer p-4 border-0">
                <button type="button" class="btn btn-light rounded-pill px-4 border"
                    data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn text-white rounded-pill px-5 fw-bold shadow-sm"
                    style="background-color: #6f42c1;" onclick="guardarEdicion()">
                    <i class="fa-solid fa-save me-2"></i> Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Configuración de Plantilla -->
<div class="modal fade" id="modalConfigurar" tabindex="-1">
    <div class="modal-dialog modal-lg border-0">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px; overflow: hidden;">
            <div class="modal-header bg-warning text-dark border-0 p-4">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-gear me-2"></i> CONFIGURAR PLANTILLA DE NOTIFICACIÓN</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-warning py-2 small mb-4 border-0 shadow-sm" style="background-color: #fff9db; color: #856404;">
                    <i class="fa-solid fa-circle-info me-2"></i> Puedes usar etiquetas dinámicas: 
                    <span class="badge bg-white text-dark border ms-1">ID</span>
                    <span class="badge bg-white text-dark border ms-1">{{ANIO_FISCAL}}</span>
                    <span class="badge bg-white text-dark border ms-1">{{LUGAR}}</span>
                    <span class="badge bg-white text-dark border ms-1">{{FECHAS}}</span>
                    <span class="badge bg-white text-dark border ms-1">{{MONTO_LETRAS}}</span>
                    <span class="badge bg-white text-dark border ms-1">{{MONTO_NUM}}</span>
                    <span class="badge bg-white text-dark border ms-1">{{SIAF}}</span>
                </div>
                
                <form id="formConfig" class="row g-3">
                    <input type="hidden" name="clave" value="plantilla_notificacion">
                    
                    <div class="col-12">
                        <label class="form-label fw-bold text-muted small text-uppercase">Cabecera Institucional</label>
                        <textarea class="form-control border-light shadow-sm" name="header" id="conf_header" rows="3" style="font-size: 0.85rem; background: #f8f9fa;"></textarea>
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label fw-bold text-muted small text-uppercase">Párrafo de Introducción (Antes de los puntos)</label>
                        <textarea class="form-control border-light shadow-sm" name="intro" id="conf_intro" rows="3" style="font-size: 0.85rem;"></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold text-muted small text-uppercase">Punto 1: Incumplimiento del Administrado</label>
                        <textarea class="form-control border-light shadow-sm" name="item1" id="conf_item1" rows="3" style="font-size: 0.85rem;"></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold text-muted small text-uppercase">Punto 2: Directiva Legal (COMGEN-PNP)</label>
                        <textarea class="form-control border-light shadow-sm" name="item2" id="conf_item2" rows="3" style="font-size: 0.85rem; font-style: italic; color: #555;"></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold text-muted small text-uppercase">Punto 3: Plazo Fatal de 72 Horas</label>
                        <textarea class="form-control border-light shadow-sm" name="item3" id="conf_item3" rows="3" style="font-size: 0.85rem;"></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold text-muted small text-uppercase">Párrafo de Cierre (Leyes y Sanciones)</label>
                        <textarea class="form-control border-light shadow-sm" name="outro" id="conf_outro" rows="4" style="font-size: 0.85rem;"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer p-4 border-0 bg-light">
                <button type="button" class="btn btn-outline-secondary px-4 rounded-pill fw-bold" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-warning px-5 rounded-pill fw-bold shadow-sm" onclick="guardarConfig()">
                    <i class="fa-solid fa-cloud-arrow-up me-2"></i> Guardar Cambios en Plantilla
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<script>
    let tabla;

    $(document).ready(function () {
        initTable();
        cargarStats();
        cargarGrados();
        cargarRegiones();
        cargarGruposFilter();

        $('#filterEstado').on('change', function () { tabla.ajax.reload(); });
        $('#filterGrupo').on('change', function () { tabla.ajax.reload(); });
        $('#filterAnio').on('change', function () {
            const anio = $(this).val();
            $('#displayAnio').text(anio);
            cargarGruposFilter();
            tabla.ajax.reload();
            cargarStats();
        });
        $('#customSearch').on('keyup', function () { tabla.search(this.value).draw(); });

        // Resaltar campos automáticamente al escribir o cambiar
        $(document).on('input change', '#modalEditar input, #modalEditar select, #modalNuevo input, #modalNuevo select', function() {
            highlightField($(this));
        });

        // Al abrir modales, refrescar estilos de campos
        $('.modal').on('shown.bs.modal', function() {
            refreshModalFields('#' + $(this).attr('id'));
        });

        $('#modal_region').on('change', function () { cargarDivisiones($(this).val()); });
        $('#modal_division').on('change', function () { cargarUnidades($(this).val()); });
        $('#modal_subunidad').on('change', function () {
            const selectedOpt = $(this).find('option:selected');
            $('#hidden_id_subunidad').val($(this).val()); // El valor ahora es el ID
            // El campo 'unidad' (para el cache) debe recibir el texto (nombre)
            if ($(this).val()) $('input[name="unidad"]').val(selectedOpt.text());
        });

        // Búsqueda automática al completar 8 dígitos
        $('#dni_search').on('input', function () {
            if (this.value.length === 8) {
                buscarPorDni();
            }
        });

        // Eventos Cascada para Editar (Mover aquí dentro)
        $('#edit_region').on('change', function () {
            $('#edit_division').html('<option value="">Cargando...</option>');
            $('#edit_subunidad').html('<option value="">-- Seleccionar --</option>');
            cargarDivisiones($(this).val(), '#edit_division');
        });

        $('#edit_division').on('change', function () {
            $('#edit_subunidad').html('<option value="">Cargando...</option>');
            cargarUnidades($(this).val(), '#edit_subunidad');
        });
    });

    function cargarRegiones(target = '#modal_region', callback) {
        $.get('../controlador/RendicionesControlador.php?op=get_regiones', function (data) {
            const regs = (typeof data === 'string') ? JSON.parse(data) : data;
            let html = '<option value="">-- Región --</option>';
            regs.forEach(r => { html += `<option value="${r.nombre}">${r.nombre}</option>`; });
            $(target).html(html);
            if (callback) callback();
        });
    }

    function cargarDivisiones(region, target = '#modal_division', callback) {
        if (typeof target === 'function') { callback = target; target = '#modal_division'; }
        if (!region) {
            $(target).html('<option value="">-- Seleccionar --</option>');
            return;
        }
        $(target).html('<option value="">Cargando...</option>');
        $.get('../controlador/RendicionesControlador.php?op=get_divisiones&region=' + encodeURIComponent(region), function (data) {
            const divs = (typeof data === 'string') ? JSON.parse(data) : data;
            let options = '<option value="">-- Seleccionar --</option>';
            divs.forEach(d => options += `<option value="${d.nombre}">${d.nombre}</option>`);
            $(target).html(options);
            if (callback) callback();
        });
    }

    function cargarUnidades(division, target = '#modal_subunidad', callback) {
        if (typeof target === 'function') { callback = target; target = '#modal_subunidad'; }
        if (!division) {
            $(target).html('<option value="">-- Seleccionar --</option>');
            return;
        }
        $(target).html('<option value="">Cargando...</option>');
        $.get('../controlador/RendicionesControlador.php?op=get_unidades&division=' + encodeURIComponent(division), function (data) {
            const unis = (typeof data === 'string') ? JSON.parse(data) : data;
            let options = '<option value="">-- Seleccionar --</option>';
            unis.forEach(u => options += `<option value="${u.id}">${u.nombre}</option>`);
            $(target).html(options);
            if (callback) callback();
        });
    }

    function cargarGrados() {
        $.get('../controlador/RendicionesControlador.php?op=listar_grados', function (data) {
            const grados = JSON.parse(data);
            let options = '<option value="">-- Seleccionar --</option>';
            grados.forEach(g => {
                options += `<option value="${g.nombre}">${g.nombre}</option>`;
            });
            $('#modal_grado_select').html(options);
            $('#edit_grado').html(options);
        });
    }

    function cargarGruposFilter() {
        const anio = $('#filterAnio').val() || <?= ANIO_FISCAL ?>;
        $.get('../controlador/RendicionesControlador.php?op=get_grupos&anio=' + anio, function (data) {
            const grupos = JSON.parse(data);
            let html = '<option value="">-- Todos los Lotes --</option>';
            grupos.forEach(g => { html += `<option value="${g}">${g}</option>`; });
            $('#filterGrupo').html(html);
        });
    }

    function initTable() {
        tabla = $('#tablaRendiciones').DataTable({
            responsive: true,
            processing: true,
            serverSide: true,
            ajax: {
                url: '../controlador/RendicionesControlador.php?op=listar',
                data: function (d) {
                    d.estado = $('#filterEstado').val();
                    d.grupo = $('#filterGrupo').val();
                    d.anio = $('#filterAnio').val();
                },
                error: function (xhr, error, thrown) {
                    console.error("Error DataTables:", xhr.responseText);
                    Swal.fire('Error', 'No se pudo cargar el listado. Verifique la consola.', 'error');
                }
            },
            columns: [
                { data: 'nro', className: 'text-center', responsivePriority: 1 },
                { data: 'dni', responsivePriority: 1, className: 'text-center' },
                { data: 'cip', responsivePriority: 2, className: 'text-dark small' },
                { data: 'grado', responsivePriority: 1, className: 'text-center' },
                { data: 'personal', responsivePriority: 1 },
                { data: 'lugar' },
                { data: null, render: data => `<small class='text-dark'>${data.region}</small><br><span class='badge bg-light text-dark border'>${data.division}</span>` },
                { data: 'unidad' },
                { data: null, render: data => `${formatDate(data.fecha_inicio)}<br>${formatDate(data.fecha_retorno)}` },
                { data: 'total', className: 'text-center text-success' },
                {
                    data: 'estado', className: 'text-center', render: function (val) {
                        if (val === 'Pendiente') return '<span class="badge-p">Pendiente</span>';
                        if (val === 'Rendido') return '<span class="badge-r">Rendido</span>';
                        return '<span class="badge-o">Observado</span>';
                    }
                },
                { data: 'grupo', render: val => `<small class='text-dark'>${val || '-'}</small>` },
                { data: 'ht_ref', className: 'text-center', responsivePriority: 1, render: val => `<small class='text-dark'>${val || '-'}</small>` },
                {
                    data: null, 
                    className: 'text-center', 
                    responsivePriority: 1,
                    render: function (row) {
                        return `
                        <div class="d-flex justify-content-center gap-1 flex-nowrap">
                            <button class="btn btn-action btn-notify" onclick="notificarRendicion(${row.id})" title="Generar Notificación PDF">
                                <i class="fa-solid fa-file-pdf"></i>
                            </button>
                            <button class="btn btn-action btn-done" onclick="cambiarEstado(${row.id}, 'Rendido')" title="Marcar como Rendido">
                                <i class="fa-solid fa-check"></i>
                            </button>
                            <button class="btn btn-action btn-obs" onclick="cambiarEstado(${row.id}, 'Observado')" title="Marcar con Observación">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            </button>
                            <button class="btn btn-action btn-edit" onclick="editarRendicion(${row.id})" title="Editar Registro">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <button class="btn btn-action btn-pend" onclick="cambiarEstado(${row.id}, 'Pendiente')" title="Volver a Pendiente">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                            </button>
                            <button class="btn btn-action btn-del text-danger" onclick="eliminarRendicion(${row.id})" title="Eliminar Registro">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    `;
                    }
                }
            ],
            language: { url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
            dom: 'rtp', // Ocultamos el buscador nativo de DT
            pageLength: 10
        });
    }

    let chartEstados = null;
    function initChart(pend, rend, obs) {
        const ctx = document.getElementById('chartEstados').getContext('2d');
        if (chartEstados) chartEstados.destroy();
        
        chartEstados = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Pendientes', 'Rendidos', 'Observados'],
                datasets: [{
                    data: [pend, rend, obs],
                    backgroundColor: ['#fbc02d', '#2e7d32', '#ff6400'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                cutout: '70%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) label += ': ';
                                if (context.parsed !== null) label += context.parsed;
                                return label;
                            }
                        }
                    }
                },
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    function cargarStats() {
        const anio = $('#filterAnio').val() || <?= ANIO_FISCAL ?>;
        $.get('../controlador/RendicionesControlador.php?op=stats&anio=' + anio, function (data) {
            try {
                const res = (typeof data === 'string') ? JSON.parse(data) : data;
                const total = parseInt(res.total) || 0;
                const pend = parseInt(res.pendientes) || 0;
                const rend = parseInt(res.rendidos) || 0;
                const obs = parseInt(res.observados) || 0;

                $('#statTotal').text(total);
                $('#statPendientes').text(pend);
                $('#statRendidos').text(rend);
                $('#statObservados').text(obs);

                // Calcular porcentajes
                if (total > 0) {
                    $('#pctPendientes').text(Math.round((pend / total) * 100) + '%');
                    $('#pctRendidos').text(Math.round((rend / total) * 100) + '%');
                    $('#pctObservados').text(Math.round((obs / total) * 100) + '%');
                } else {
                    $('#pctPendientes').text('0%');
                    $('#pctRendidos').text('0%');
                    $('#pctObservados').text('0%');
                }

                initChart(pend, rend, obs);
            } catch (e) {
                console.error("Error parseando stats:", data);
            }
        });
    }

    function procesarImportacion() {
        let formData = new FormData($('#formImportar')[0]);
        formData.append('anio', $('#filterAnio').val());
        $('#btnProcesar').html('<span class="spinner-border spinner-border-sm me-2"></span> Procesando...').prop('disabled', true);
        
        // Mostrar barra de progreso
        $('#progressContainer').fadeIn();
        $('#progressBar').css('width', '0%').removeClass('bg-success').addClass('bg-primary');
        $('#progressPercent').text('0%');
        $('#progressStatus').text('Iniciando subida...');

        $.ajax({
            url: '../controlador/ren_importar_excel.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = Math.round((evt.loaded / evt.total) * 100);
                        $('#progressBar').css('width', percentComplete + '%');
                        $('#progressPercent').text(percentComplete + '%');
                        if(percentComplete === 100) {
                            $('#progressStatus').text('Archivo subido. Procesando registros en BD...');
                        } else {
                            $('#progressStatus').text('Subiendo archivo...');
                        }
                    }
                }, false);
                return xhr;
            },
            success: function (response) {
                try {
                    const res = (typeof response === 'string') ? JSON.parse(response) : response;
                    if (res.status) {
                        $('#progressBar').removeClass('bg-primary').addClass('bg-success').css('width', '100%');
                        $('#progressPercent').text('100%');
                        $('#progressStatus').text('¡Proceso completado!');
                        
                        setTimeout(() => {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Importación Correcta!',
                                text: res.msg,
                                confirmButtonColor: '#006691'
                            });
                            $('#modalImportar').modal('hide');
                            $('#formImportar')[0].reset();
                            $('#fileNameDisplay').text('Sin archivos seleccionados');
                            $('#btnProcesar').prop('disabled', true);
                            $('#progressContainer').hide();
                            tabla.ajax.reload();
                            cargarStats();
                            cargarGruposFilter();
                        }, 600);
                    } else {
                        Swal.fire('Error de Importación', res.msg, 'error');
                        $('#progressContainer').hide();
                    }
                } catch (e) {
                    Swal.fire('Error Crítico', 'La respuesta del servidor no es válida.', 'error');
                    console.error(response);
                    $('#progressContainer').hide();
                }
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
                Swal.fire('Error', 'No se pudo conectar con el servidor: ' + error, 'error');
                $('#progressContainer').hide();
            },
            complete: function() {
                $('#btnProcesar').html('<i class="fa-solid fa-circle-check me-2"></i> Importar Ahora').prop('disabled', false);
            }
        });
    }

    // Lógica para el DropZone y Input de Archivos
    $(document).on('change', '#fileInput', function () {
        if (this.files && this.files[0]) {
            $('#fileNameDisplay').text(this.files[0].name).addClass('text-primary fw-bold');
            $('#btnProcesar').prop('disabled', false);
        } else {
            $('#fileNameDisplay').text('Sin archivos seleccionados').removeClass('text-primary fw-bold');
            $('#btnProcesar').prop('disabled', true);
        }
    });

    // Drag and Drop Effects
    const dropZone = document.getElementById('dropZone');
    if (dropZone) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });
        function preventDefaults(e) { e.preventDefault(); e.stopPropagation(); }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => dropZone.classList.add('dragover'), false);
        });
        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => dropZone.classList.remove('dragover'), false);
        });

        dropZone.addEventListener('drop', function (e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            document.getElementById('fileInput').files = files;
            // Trigger change manually
            $('#fileInput').trigger('change');
        }, false);
    }

    function confirmarLimpieza() {
        Swal.fire({
            title: '¿VACIAR TODA LA TABLA?',
            text: "Esta acción eliminará absolutamente TODOS los registros del sistema. Es irreversible.",
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Paso de seguridad extra
                Swal.fire({
                    title: 'CONFIRMACIÓN FINAL',
                    text: 'Escriba ELIMINAR para borrar TODA la base de datos de rendiciones:',
                    input: 'text',
                    inputPlaceholder: 'ELIMINAR',
                    showCancelButton: true,
                    confirmButtonText: 'BORRAR TODO',
                    confirmButtonColor: '#000',
                    preConfirm: (val) => {
                        if (val !== 'ELIMINAR') {
                            Swal.showValidationMessage('Debe escribir la palabra exacta');
                            return false;
                        }
                        return true;
                    }
                }).then((confirm) => {
                    if (confirm.isConfirmed) {
                        $.post('../controlador/RendicionesControlador.php?op=limpiar_todo', function (resp) {
                            Swal.fire('¡Vaciado!', 'La tabla de rendiciones ha sido limpiada.', 'success');
                            tabla.ajax.reload();
                            cargarStats();
                            cargarGruposFilter();
                        });
                    }
                });
            }
        });
    }

    function cambiarEstado(id, estado) {
        $.post('../controlador/RendicionesControlador.php?op=cambiar_estado', { id, estado }, function (resp) {
            tabla.ajax.reload(null, false);
            cargarStats();
        });
    }

    function eliminarRendicion(id) {
        Swal.fire({
            title: '¿Eliminar registro?',
            text: "Esta acción no se puede deshacer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('../controlador/RendicionesControlador.php?op=eliminar', { id }, function (resp) {
                    tabla.ajax.reload(null, false);
                    cargarStats();
                });
            }
        });
    }

    function notificarRendicion(id) {
        window.open('../controlador/ren_generar_notificacion_pdf.php?id=' + id, '_blank');
    }

    function notificarGrupo() {
        const grupo = $('#filterGrupo').val();
        if (!grupo) {
            Swal.fire('Atención', 'Para notificar por lote, primero seleccione un Lote en el filtro.', 'info');
            return;
        }

        Swal.fire({
            title: '¿Generar Notificaciones Masivas?',
            text: `Se generará un solo PDF con las notificaciones PENDIENTES del lote: ${grupo}`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sí, generar PDF',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.open('../controlador/ren_generar_notificacion_grupal_pdf.php?grupo=' + encodeURIComponent(grupo), '_blank');
            }
        });
    }

    function generarReporteGeneral() {
        const q = $('#customSearch').val();
        const estado = $('#filterEstado').val();
        const grupo = $('#filterGrupo').val();
        const anio = $('#filterAnio').val();
        
        const params = new URLSearchParams({
            q: q,
            estado: estado,
            grupo: grupo,
            anio: anio
        });

        window.open('../controlador/ren_generar_reporte_listado_pdf.php?' + params.toString(), '_blank');
    }

    function exportarExcel() {
        const q = $('#customSearch').val();
        const estado = $('#filterEstado').val();
        const grupo = $('#filterGrupo').val();
        const anio = $('#filterAnio').val();
        
        const params = new URLSearchParams({
            q: q,
            estado: estado,
            grupo: grupo,
            anio: anio
        });

        window.location.href = '../controlador/ren_exportar_excel.php?' + params.toString();
    }

    function eliminarLote() {
        const grupo = $('#filterGrupo').val();
        if (!grupo) {
            Swal.fire('Atención', 'Para eliminar un lote completo, primero selecciónelo en el filtro.', 'warning');
            return;
        }

        // Paso 1: Confirmación inicial
        Swal.fire({
            title: '¿ELIMINAR LOTE COMPLETO?',
            text: `Esta acción borrará permanentemente TODA la data del lote: ${grupo}. ¿Desea continuar?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Paso 2: Doble seguridad (Escribir palabra clave)
                Swal.fire({
                    title: 'SEGURIDAD DE ELIMINACIÓN',
                    text: `Para confirmar la eliminación del lote "${grupo}", escriba la palabra ELIMINAR en mayúsculas:`,
                    input: 'text',
                    inputPlaceholder: 'Escriba ELIMINAR aquí',
                    showCancelButton: true,
                    confirmButtonText: 'ELIMINAR AHORA',
                    confirmButtonColor: '#000',
                    cancelButtonText: 'Arrepentirse',
                    showLoaderOnConfirm: true,
                    preConfirm: (inputValue) => {
                        if (inputValue !== 'ELIMINAR') {
                            Swal.showValidationMessage('Debe escribir ELIMINAR exactamente para proceder');
                            return false;
                        }
                        return true;
                    }
                }).then((confirmResult) => {
                    if (confirmResult.isConfirmed) {
                        $.post('../controlador/RendicionesControlador.php?op=eliminar_lote', { grupo }, function(data) {
                            try {
                                const res = (typeof data === 'string') ? JSON.parse(data) : data;
                                if (res.status === 'success') {
                                    Swal.fire('Eliminado', 'El lote ha sido borrado satisfactoriamente.', 'success');
                                    cargarGruposFilter();
                                    tabla.ajax.reload();
                                    cargarStats();
                                } else {
                                    Swal.fire('Error', res.message, 'error');
                                }
                            } catch (e) {
                                Swal.fire('Error', 'Fallo al procesar respuesta del servidor.', 'error');
                            }
                        });
                    }
                });
            }
        });
    }

    function formatDate(dateStr) {
        if (!dateStr || dateStr === '0000-00-00') return '-';
        const parts = dateStr.split('-');
        if (parts.length !== 3) return dateStr;
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }

    function buscarPorDni() {
        const dni = $('#dni_search').val();
        if (dni.length < 8) {
            Swal.fire('Atención', 'El DNI debe tener 8 dígitos', 'warning');
            return;
        }

        $.get('../controlador/RendicionesControlador.php?op=buscar_personal&dni=' + dni, function (data) {
            try {
                const res = (typeof data === 'string') ? JSON.parse(data) : data;
                if (res.status) {
                    $('#modal_cip').val(res.cip);
                    $('#modal_grado_select').val(res.grado);
                    $('#hidden_nombres').val(res.nombres);
                    $('#hidden_id_subunidad').val(res.id_subunidad);

                    // Poblar jerarquía dinámicamente
                    if (res.region) {
                        $('#modal_region').val(res.region);
                        cargarDivisiones(res.region, function () {
                            $('#modal_division').val(res.division);
                            cargarUnidades(res.division, function () {
                                $('#modal_subunidad').val(res.unidad).trigger('change');
                            });
                        });
                    }

                    Swal.fire({ icon: 'success', title: 'Personal Encontrado', text: res.nombres, timer: 1500, showConfirmButton: false });
                } else {
                    Swal.fire({
                        icon: 'info',
                        title: 'Efectivo No Encontrado',
                        text: 'El DNI no está en el padrón de Lima Centro. Puede ingresar los datos manualmente para esta rendición.',
                        confirmButtonText: 'Entendido'
                    });
                    // Limpiamos solo campos de resultados previos, pero dejamos el DNI
                    $('#modal_cip').val('').focus();
                    $('#hidden_nombres').val('');
                    $('#modal_region').val('').trigger('change');
                }
            } catch (e) {
                console.error("Error búsqueda DNI:", data);
                Swal.fire('Error de Sistema', 'No se pudo buscar el DNI. <br><small>' + data + '</small>', 'error');
            }
        });
    }

    function guardarRegistroIndividual() {
        if (!$('#dni_search').val() || !$('#hidden_nombres').val()) {
            Swal.fire('Error', 'Debe completar el DNI y los Apellidos/Nombres del efectivo.', 'error');
            return;
        }

        let formData = $('#formNuevo').serialize();
        formData += '&anio=' + $('#filterAnio').val();

        $.post('../controlador/RendicionesControlador.php?op=registrar_manual', formData, function (data) {
            try {
                const res = (typeof data === 'string') ? JSON.parse(data) : data;
                if (res.status === 'success') {
                    Swal.fire('¡Registrado!', 'La rendición ha sido guardada correctamente.', 'success');
                    $('#modalNuevo').modal('hide');
                    $('#formNuevo')[0].reset();
                    tabla.ajax.reload();
                    cargarStats();
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            } catch (e) {
                console.error("Error respuesta:", data);
                Swal.fire('Error de Sistema', 'El servidor respondió algo inesperado: <br><pre class="text-start small" style="max-height:200px; overflow:auto;">' + data + '</pre>', 'error');
            }
        });
    }
    function editarRendicion(id) {
        // Resetear selects para evitar basura de registros anteriores
        $('#edit_region').html('<option value="">Cargando...</option>');
        $('#edit_division').html('<option value="">-- Seleccionar --</option>');
        $('#edit_subunidad').html('<option value="">-- Seleccionar --</option>');

        $.post('../controlador/RendicionesControlador.php?op=mostrar', { id }, function (data) {
            try {
                const res = (typeof data === 'string') ? JSON.parse(data) : data;
                $('#edit_id').val(res.id);
                $('#edit_dni').val(res.dni);
                $('#edit_cip').val(res.cip);
                $('#edit_grado').val(res.grado);
                $('#edit_nombres').val(res.apellidos_nombres);
                $('#edit_lugar').val(res.lugar_comision);

                // Campos adicionales
                $('#edit_cuenta').val(res.cuenta_ahorros);
                $('#edit_fecha_ini').val(res.fecha_inicio);
                $('#edit_fecha_ret').val(res.fecha_retorno);
                $('#edit_liq').val(res.nro_liquidacion);
                $('#edit_igv').val(res.igv);
                $('#edit_dias').val(res.dias);
                $('#edit_siaf').val(res.siaf_expediente);
                $('#edit_ht_ref').val(res.ht_ref);
                $('#edit_deposito1').val(res.primer_deposito);
                $('#edit_pasajes').val(res.pasajes);
                $('#edit_total').val(res.total_depositado);

                // Cascada de unidades para edición
                cargarRegiones('#edit_region', function () {
                    if (res.region_cache) {
                        $('#edit_region').val(res.region_cache);
                        cargarDivisiones(res.region_cache, '#edit_division', function () {
                            if (res.division_cache) {
                                $('#edit_division').val(res.division_cache);
                                cargarUnidades(res.division_cache, '#edit_subunidad', function () {
                                    if (res.id_subunidad) {
                                        $('#edit_subunidad').val(res.id_subunidad);
                                    }
                                });
                            }
                        });
                    }
                });

                $('#modalEditar').modal('show');
                setTimeout(() => refreshModalFields('#modalEditar'), 400); 
            } catch (e) {
                console.error("Error cargar datos:", data);
                Swal.fire('Error', 'No se pudo cargar la información del registro.', 'error');
            }
        });
    }

    function guardarEdicion() {
        if (!$('#edit_subunidad').val()) {
            Swal.fire('Atención', 'Debe seleccionar una Unidad Policial válida.', 'warning');
            return;
        }

        const formData = $('#formEditar').serialize();
        $.post('../controlador/RendicionesControlador.php?op=editar', formData, function (data) {
            try {
                const res = (typeof data === 'string') ? JSON.parse(data) : data;
                if (res.status) {
                    Swal.fire('¡Éxito!', res.msg, 'success');
                    $('#modalEditar').modal('hide');
                    tabla.ajax.reload(null, false);
                    cargarStats();
                } else {
                    Swal.fire('Error', res.msg, 'error');
                }
            } catch (e) {
                console.error("Error guardar:", data);
                Swal.fire('Error de Sistema', 'No se pudo actualizar el registro.', 'error');
            }
        });
    }

    function highlightField($el) {
        if ($el.val() && $el.val().toString().trim() !== "") {
            $el.addClass('field-filled');
        } else {
            $el.removeClass('field-filled');
        }
    }

    function refreshModalFields(modalId) {
        $(`${modalId} input, ${modalId} select`).each(function() {
            highlightField($(this));
        });
    }

    function abrirConfiguracion() {
        $.get('../controlador/RendicionesControlador.php?op=get_config&clave=plantilla_notificacion', function(res) {
            try {
                const data = JSON.parse(res);
                if (data && data.valor) {
                    const vals = JSON.parse(data.valor);
                    $('#conf_header').val(vals.header || '');
                    $('#conf_intro').val(vals.intro || '');
                    $('#conf_item1').val(vals.item1 || '');
                    $('#conf_item2').val(vals.item2 || '');
                    $('#conf_item3').val(vals.item3 || '');
                    $('#conf_outro').val(vals.outro || '');
                    
                    $('#modalConfigurar').modal('show');
                } else {
                    Swal.fire('Error', 'No se pudo cargar la configuración.', 'error');
                }
            } catch (e) {
                console.error("Error config:", res);
                Swal.fire('Error', 'Error al procesar la plantilla.', 'error');
            }
        });
    }

    function guardarConfig() {
        const dataObj = {
            header: $('#conf_header').val(),
            intro: $('#conf_intro').val(),
            item1: $('#conf_item1').val(),
            item2: $('#conf_item2').val(),
            item3: $('#conf_item3').val(),
            outro: $('#conf_outro').val()
        };
        
        const params = {
            clave: 'plantilla_notificacion',
            valor: JSON.stringify(dataObj)
        };

        $.post('../controlador/RendicionesControlador.php?op=save_config', params, function(res) {
            try {
                const r = JSON.parse(res);
                if (r.status === 'success') {
                    Swal.fire('¡Éxito!', 'Plantilla actualizada correctamente.', 'success');
                    $('#modalConfigurar').modal('hide');
                } else {
                    Swal.fire('Error', 'No se pudo guardar: ' + r.message, 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'Error de respuesta del servidor.', 'error');
            }
        });
    }
</script>

<?php include 'layout/footer.php'; ?>