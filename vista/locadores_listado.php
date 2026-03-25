<?php
// vista/locadores_listado.php
require_once __DIR__ . "/../modelo/conexion.php";

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Super Administrador') {
    header("Location: " . (defined('BASE_URL') ? BASE_URL : '/sistem_job/') . "vista/inicio.php");
    exit();
}

include __DIR__ . '/layout/topbar.php';
include __DIR__ . '/layout/sidebar.php';

// Pre-codificar imágenes para PDF
$path_escudo = '../public/images/escudo.png';
$path_regpol = '../public/images/logo_regpol.png';
$b64_escudo = '';
$b64_regpol = '';

if(file_exists($path_escudo)) {
    $type = pathinfo($path_escudo, PATHINFO_EXTENSION);
    $data = file_get_contents($path_escudo);
    $b64_escudo = 'data:image/' . $type . ';base64,' . base64_encode($data);
}
if(file_exists($path_regpol)) {
    $type = pathinfo($path_regpol, PATHINFO_EXTENSION);
    $data = file_get_contents($path_regpol);
    $b64_regpol = 'data:image/' . $type . ';base64,' . base64_encode($data);
}
?>

<!-- ESTILOS (Reutilizando los de personal_listado para consistencia) -->
<style>
  .card-stat {
      transition: transform 0.2s;
      border: none;
      border-radius: 12px;
  }
  .card-stat:hover {
      transform: translateY(-5px);
  }
  .form-label { font-weight: 600; font-size: 0.9rem; }
  
  /* Tabla Premium */
  #tablaLocadores thead th {
      background-color: #2c3e50 !important;
      color: white !important;
      text-transform: uppercase;
      font-size: 0.72rem; /* Texto más fino */
      font-weight: normal;
      letter-spacing: 0.5px;
      border: 1px solid #455a64; /* Borde sutil para separar headers */
      padding-top: 12px;
      padding-bottom: 12px;
      vertical-align: middle;
  }
  #tablaLocadores tbody td {
      font-size: 0.8rem; /* Cuerpo más pequeño */
      vertical-align: middle;
      color: #222222; /* Texto más oscuro */
      border: 1px solid #dee2e6; /* Rejilla gris clara */
  }
  #tablaLocadores tbody tr:hover {
      background-color: rgba(44, 62, 80, 0.05);
  }
  
  /* Force History Row Color */
  .table-history, .table-history td {
      background-color: #e0f7fa !important;
  }

  /* Tooltip Premium */
  .tooltip-container {
      position: relative;
      cursor: help;
  }
  .custom-tooltip {
      visibility: hidden;
      background-color: #e0f7fa; /* Cyan claro */
      color: #006064; /* Cyan oscuro */
      text-align: left;
      border-radius: 8px;
      padding: 12px;
      position: absolute;
      z-index: 9999;
      bottom: 100%; /* Arriba del elemento */
      left: 0;
      width: 100%; /* Ocupa todo el ancho del contenedor padre (la celda) */
      box-shadow: 0 8px 16px rgba(0,0,0,0.2);
      opacity: 0;
      transition: opacity 0.3s, transform 0.3s;
      transform: translateY(10px);
      font-size: 0.85rem;
      line-height: 1.4;
      border: 1px solid #b2ebf2;
      pointer-events: none; /* Dejar pasar clicks si es necesario */
  }
  .custom-tooltip::after {
      content: "";
      position: absolute;
      top: 100%;
      left: 20px; /* Flecha offset a la izquierda */
      margin-left: 0;
      border-width: 8px;
      border-style: solid;
      border-color: #e0f7fa transparent transparent transparent;
  }
  .tooltip-container:hover .custom-tooltip {
      visibility: visible;
      opacity: 1;
      transform: translateY(0);
  }
  .truncate-text {
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 350px; /* Fuerza el truncado visual */
      display: block;
      color: #333;
  }
</style>

<div class="page-content" style="padding: 20px; padding-top: 80px;">
    <div class="container-fluid">

        <!-- Header -->
        <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fa-solid fa-file-contract me-2"></i>Gestión de Locadores
            </h1>
            <p class="mb-0 text-muted">Administración y control de contratos de servicios</p>
        </div>
        <div class="d-flex gap-2">
            <!-- Filtro Cumpleaños -->
             <select class="form-select shadow-sm border-0 fw-bold text-secondary me-2" id="filtro_mes_cumple" style="width: 260px;" aria-label="Filtrar por cumpleaños">
                <option value="">🎂 Filtrar Cumpleañeros</option>
                <option value="1">Enero</option>
                <option value="2">Febrero</option>
                <option value="3">Marzo</option>
                <option value="4">Abril</option>
                <option value="5">Mayo</option>
                <option value="6">Junio</option>
                <option value="7">Julio</option>
                <option value="8">Agosto</option>
                <option value="9">Septiembre</option>
                <option value="10">Octubre</option>
                <option value="11">Noviembre</option>
                <option value="12">Diciembre</option>
            </select>
            <button class="btn btn-info text-white shadow-sm" onclick="verEstadisticas()">
                <i class="fa-solid fa-chart-pie me-1"></i> Analítica
            </button>
            <button class="btn btn-warning text-dark shadow-sm" id="btnFilterVencimientos" onclick="toggleVencimientos()">
                <i class="fa-solid fa-clock me-1"></i> Vencimientos
            </button>
            <button class="btn btn-success shadow-sm" onclick="abrirModalImportar()">
                <i class="fa-solid fa-file-excel me-1"></i> Importar Excel
            </button>
            <button class="btn btn-primary shadow-sm" onclick="abrirModal()">
                <i class="fa-solid fa-plus me-1"></i> Nuevo Contrato
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <!-- ... existing stats ... -->
        <style>
            .card-compact {
                border: none;
                border-radius: 12px;
                overflow: hidden;
                transition: transform 0.2s ease, box-shadow 0.2s ease;
                background: #fff;
            }
            .card-compact:hover {
                transform: translateY(-3px);
                box-shadow: 0 5px 15px rgba(0,0,0,0.08) !important;
            }
            .card-compact .card-body {
                padding: 1rem 1.25rem; /* Reduced padding */
            }
            .icon-box-compact {
                width: 42px;
                height: 42px;
                border-radius: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.25rem;
                flex-shrink: 0;
            }
            
            .text-primary-dark { color: #2c3e50; }
            .stat-label-compact { font-size: 0.7rem; letter-spacing: 0.5px; font-weight: 600; text-transform: uppercase; color: #8898aa; display: block; margin-bottom: 2px; }
            .stat-value-compact { font-size: 1.4rem; font-weight: 700; color: #2c3e50; line-height: 1.2; }
            
            .bg-gradient-primary-soft { background: linear-gradient(135deg, #eef2f8 0%, #dbe4f3 100%); }
            .bg-gradient-success-soft { background: linear-gradient(135deg, #eff9f3 0%, #dff2e6 100%); }
            .bg-gradient-warning-soft { background: linear-gradient(135deg, #fff8ec 0%, #fff0d4 100%); }
            
            .border-start-accent { border-left: 4px solid transparent; }
            .border-primary-accent { border-left-color: #4e73df !important; }
            .border-success-accent { border-left-color: #1cc88a !important; }
            .border-warning-accent { border-left-color: #f6c23e !important; }
        </style>

        <!-- Card 1: Costo Total -->
        <div class="col-xl col-md-6 mb-3">
            <div class="card card-compact shadow-sm h-100 border-start-accent border-primary-accent">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-box-compact bg-gradient-primary-soft text-primary me-3">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="flex-grow-1">
                        <span class="stat-label-compact">Costo Total Anual</span>
                        <div class="stat-value-compact" id="statMonto">S/ 0.00</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Locadores Activos -->
        <div class="col-xl col-md-6 mb-3">
            <div class="card card-compact shadow-sm h-100 border-start-accent border-success-accent" style="cursor: pointer;" onclick="toggleFiltroEstado('ACTIVO')" title="Filtrar Activos">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-box-compact bg-gradient-success-soft text-success me-3">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="flex-grow-1">
                        <span class="stat-label-compact">Locadores Activos</span>
                        <div class="stat-value-compact" id="statActivos">0</div>
                    </div>
                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-1" style="font-size: 0.65rem;">VIGENTES</span>
                </div>
            </div>
        </div>

        <!-- Card 3: Pendientes (Nuevo) -->
        <div class="col-xl col-md-6 mb-3">
            <div class="card card-compact shadow-sm h-100 border-start-accent border-info-accent" style="cursor: pointer; border-left-color: #36b9cc !important;" onclick="toggleFiltroEstado('PENDIENTE')" title="Filtrar Pendientes">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-box-compact bg-light text-info me-3" style="background-color: #e3f2fd;">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div class="flex-grow-1">
                        <span class="stat-label-compact">Pendientes</span>
                        <div class="stat-value-compact" id="statPendientes">0</div>
                    </div>
                    <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-2 py-1" style="font-size: 0.65rem;">TRÁMITE</span>
                </div>
            </div>
        </div>

        <!-- Card 4: Finalizados (Nuevo) -->
        <div class="col-xl col-md-6 mb-3">
            <div class="card card-compact shadow-sm h-100 border-start-accent border-secondary-accent" style="cursor: pointer; border-left-color: #858796 !important;" onclick="toggleFiltroEstado('FINALIZADO')" title="Filtrar Finalizados">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-box-compact bg-light text-secondary me-3">
                        <i class="fa-solid fa-archive"></i>
                    </div>
                    <div class="flex-grow-1">
                        <span class="stat-label-compact">Finalizados</span>
                        <div class="stat-value-compact" id="statFinalizados">0</div>
                    </div>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-2 py-1" style="font-size: 0.65rem;">HISTÓRICO</span>
                </div>
            </div>
        </div>

        <!-- Card 5: Vencimientos -->
        <div class="col-xl col-md-6 mb-3">
            <div class="card card-compact shadow-sm h-100 border-start-accent border-warning-accent" style="cursor: pointer;" onclick="toggleVencimientos()" title="Ver Vencimientos">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-box-compact bg-gradient-warning-soft text-warning me-3">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="flex-grow-1">
                        <span class="stat-label-compact">Por Vencer (30d)</span>
                        <div class="stat-value-compact" id="statVencimientos">0</div>
                    </div>
                     <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-2 py-1 blink-soft" style="font-size: 0.65rem;">URGENTE</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Estadisticas -->
    <div class="modal fade" id="modalEstadisticas" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fa-solid fa-chart-pie me-2"></i>Analítica de Locadores</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card shadow-sm h-100">
                                <div class="card-header bg-white font-weight-bold text-secondary">Locadores por Unidad</div>
                                <div class="card-body">
                                    <canvas id="chartUnidades"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="card shadow-sm h-100">
                                <div class="card-header bg-white font-weight-bold text-secondary">Gasto Proyectado por Unidad (Top 10)</div>
                                <div class="card-body">
                                    <canvas id="chartGasto"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Historial -->
    <div class="modal fade" id="modalHistorial" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title"><i class="fa-solid fa-history me-2"></i>Historial del Locador</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6 id="historialNombre" class="fw-bold mb-3"></h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-striped">
                            <thead>
                                <tr class="bg-light">
                                    <th>Unidad</th>
                                    <th>Servicio</th>
                                    <th>Periodo</th>
                                    <th>Monto</th>
                                    <th>Estado</th>
                                    <th>Evaluación</th>
                                </tr>
                            </thead>
                            <tbody id="tablaHistorialBody">
                                <!-- Ajax content -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>      <!-- Tabla CRUD -->
        <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="tablaLocadores" class="table table-bordered align-middle w-100" style="table-layout: fixed; min-width: 1400px;">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 50px;">#</th>
                                <th style="width: 45px;"></th> <!-- Columna Expansión -->
                                <th class="ps-4" style="width: 200px;">Locador</th>
                                <th class="text-center" style="width: 100px;">DNI</th>
                                <th style="width: 300px;">Servicio / Cargo</th>
                                <th class="text-end" style="width: 130px;">Monto Mensual (S/)</th>
                                <th class="text-end" style="width: 130px;">Monto Total (S/)</th>
                                <th class="text-center" style="width: 180px;">Vigencia</th>
                                <th style="width: 180px;">Unidad Solicitante</th>
                                <th class="text-center text-nowrap" style="width: 80px;">CMN</th>
                                <th class="text-center text-nowrap" style="width: 80px;">SIGA</th>
                                <th class="text-center" style="width: 120px;">Estado</th>
                                <!-- <th class="text-center" style="width: 130px;">Evaluación</th> -->
                                <!-- Campos ocultos para Exportación Excel Completa -->
                                <th class="d-none">Evaluación</th>
                                <th class="d-none">RUC / DNI</th>
                                <th class="d-none">Celular</th>
                                <th class="d-none">Correo</th>
                                <th class="d-none">F. Inicio</th>
                                <th class="d-none">F. Fin</th>
                                <th class="d-none">Meta</th>
                                <th class="d-none">Esp. Gasto</th>
                                <th class="d-none">Cod. SIGA</th>
                                <th class="d-none">Ene</th><th class="d-none">Feb</th><th class="d-none">Mar</th><th class="d-none">Abr</th>
                                <th class="d-none">May</th><th class="d-none">Jun</th><th class="d-none">Jul</th><th class="d-none">Ago</th>
                                <th class="d-none">Set</th><th class="d-none">Oct</th><th class="d-none">Nov</th><th class="d-none">Dic</th>
                                <!-- Fin campos ocultos -->
                                <th class="text-center" style="width: 130px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody><!-- DataTables --></tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal Principal -->
<div class="modal fade" id="modalLocador" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold" id="modalTitulo">Registro de Locador</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <form id="formLocador">
                    <input type="hidden" name="id" id="id">

                    <!-- Navbar Tabs -->
                    <ul class="nav nav-tabs mb-3" id="locadorTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" id="tab-general" data-bs-toggle="tab" data-bs-target="#content-general" type="button"><i class="fa-solid fa-user me-2"></i>Datos Generales</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="tab-admin" data-bs-toggle="tab" data-bs-target="#content-admin" type="button"><i class="fa-solid fa-folder-open me-2"></i>Datos Administrativos</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="tab-pagos" data-bs-toggle="tab" data-bs-target="#content-pagos" type="button"><i class="fa-solid fa-calendar-alt me-2"></i>Cronograma de Pagos</button>
                        </li>
                    </ul>

                    <div class="tab-content" id="myTabContent">
                        
                        <!-- TAB 1: GENERAL -->
                        <div class="tab-pane fade show active" id="content-general">
                            <div class="row g-3">
                                <div class="col-md-2">
                                    <label class="form-label">DNI</label>
                                    <input type="text" class="form-control" name="dni" id="input_dni" maxlength="8" placeholder="8 dígitos">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">RUC</label>
                                    <input type="text" class="form-control" id="input_ruc" maxlength="11" placeholder="11 dígitos">
                                </div>
                                <!-- Campo oculto para compatibilidad con backend -->
                                <input type="hidden" name="dni_ruc" id="dni_ruc">
                                <div class="col-md-7">
                                    <label class="form-label">Apellidos y Nombres <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control text-uppercase" name="nombres_apellidos" id="nombres_apellidos" required>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Cumpleaños <small class="text-muted">(Día - Mes)</small></label>
                                    <div class="input-group">
                                        <select class="form-select" name="dia_nac" id="dia_nac">
                                            <option value="">Día</option>
                                            <?php for($i=1;$i<=31;$i++) echo "<option value='$i'>$i</option>"; ?>
                                        </select>
                                        <select class="form-select" name="mes_nac" id="mes_nac">
                                            <option value="">Mes</option>
                                            <option value="1">Enero</option>
                                            <option value="2">Febrero</option>
                                            <option value="3">Marzo</option>
                                            <option value="4">Abril</option>
                                            <option value="5">Mayo</option>
                                            <option value="6">Junio</option>
                                            <option value="7">Julio</option>
                                            <option value="8">Agosto</option>
                                            <option value="9">Septiembre</option>
                                            <option value="10">Octubre</option>
                                            <option value="11">Noviembre</option>
                                            <option value="12">Diciembre</option>
                                        </select>
                                    </div>
                                    <!-- Input oculto para compatibilidad con backend -->
                                    <input type="hidden" name="fecha_nacimiento" id="fecha_nacimiento_hidden">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Género</label>
                                    <select class="form-select" name="sexo" id="sexo">
                                        <option value="M">Masculino</option>
                                        <option value="F">Femenino</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Correo Electrónico <small class="text-muted fw-normal">(Opcional)</small></label>
                                    <input type="email" class="form-control" name="correo" id="correo" placeholder="ejemplo@correo.com">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Celular <small class="text-muted fw-normal">(Opcional)</small></label>
                                    <input type="text" class="form-control" name="celular" id="celular" placeholder="999 999 999">
                                </div>
                                
                                <div class="col-md-12">
                                    <label class="form-label">Descripción del Servicio (Cargo) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control text-uppercase" name="servicio_descripcion" id="servicio_descripcion" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Fecha Inicio <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="fecha_inicio" id="fecha_inicio" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Fecha Fin <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="fecha_fin" id="fecha_fin" required>
                                </div>
                                
                                <div class="col-md-12">
                                    <label class="form-label">Unidad / Área / Oficina Solicitante <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control text-uppercase" name="unidad_asignada" id="unidad_asignada" placeholder="Ej: LOGÍSTICA" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Estado</label>
                                    <select class="form-select" name="estado" id="estado">
                                        <option value="ACTIVO">ACTIVO</option>
                                        <option value="PENDIENTE">PENDIENTE (Por definir)</option>
                                        <option value="FINALIZADO">FINALIZADO</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-primary">Evaluación / Recontratación</label>
                                    <select class="form-select fw-bold" name="recontratacion" id="recontratacion">
                                        <option value="PENDIENTE">PENDIENTE</option>
                                        <option value="RECOMENDADO" class="text-success">&#9989; RECOMENDADO</option>
                                        <option value="NO RECOMENDADO" class="text-danger">&#10060; NO RECOMENDADO</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: ADMINISTRATIVO -->
                        <div class="tab-pane fade" id="content-admin">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label text-muted">Meta (Opcional)</label>
                                    <input type="text" class="form-control" name="meta" id="meta" placeholder="Ej: 126">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted">Esp. Gasto (Opcional)</label>
                                    <input type="text" class="form-control" name="esp_gasto" id="esp_gasto" placeholder="Ej: 2.3.29.11">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted">Código SIGA (Opcional)</label>
                                    <input type="text" class="form-control" name="codigo_siga" id="codigo_siga" placeholder="Ej: 071100...">
                                </div>

                                <div class="col-md-6 border-top pt-3">
                                    <label class="form-label fw-bold text-success">N° PEDIDO SIGA <small class="text-muted fw-normal">(Opcional)</small></label>
                                    <input type="text" class="form-control fs-5" name="num_pedido_siga" id="num_pedido_siga" placeholder="#">
                                    <div class="mt-2">
                                        <label class="small fw-bold">Archivo Pedido SIGA (PDF)</label>
                                        <input type="file" class="form-control form-control-sm" name="archivo_siga" id="archivo_siga" accept="application/pdf">
                                        <div id="link_siga_actual" class="mt-1"></div>
                                    </div>
                                </div>

                                <div class="col-md-6 border-top pt-3">
                                    <label class="form-label fw-bold text-danger">N° CMN <small class="text-muted fw-normal">(Opcional)</small></label>
                                    <input type="text" class="form-control fs-5" name="num_cmn" id="num_cmn" placeholder="Ej: 2011">
                                    <div class="mt-2">
                                        <label class="small fw-bold">Archivo CMN (PDF)</label>
                                        <input type="file" class="form-control form-control-sm" name="archivo_pdf" id="archivo_pdf" accept="application/pdf">
                                        <div id="link_pdf_actual" class="mt-1"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: PAGOS -->
                        <div class="tab-pane fade" id="content-pagos">
                            <div class="row g-3 mb-3">
                                <div class="col-md-5">
                                    <label class="form-label">Monto Mensual Referencial</label>
                                    <div class="input-group">
                                        <span class="input-group-text">S/</span>
                                        <input type="number" step="0.01" class="form-control fw-bold" name="monto_mensual" id="monto_mensual">
                                        <button type="button" class="btn btn-primary" onclick="replicarMonto()" title="Llenar meses según fechas de contrato">
                                            <i class="fa-solid fa-magic me-1"></i> Distribuir
                                        </button>
                                    </div>
                                    <small class="text-muted"><i class="fa-solid fa-info-circle me-1"></i>Se distribuirá automáticamente según las fechas de vigencia.</small>
                                </div>
                                <div class="col-md-7 d-flex align-items-center justify-content-end">
                                    <div class="card bg-light border-0 w-100">
                                        <div class="card-body py-2 px-3">
                                            <small class="text-muted d-block text-end">TOTAL ANUAL PROGRAMADO</small>
                                            <span class="fs-4 fw-bold text-primary d-block text-end" id="totalAnual">S/ 0.00</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <h6 class="text-primary fw-bold mb-3"><i class="fa-solid fa-calendar-check me-2"></i>Distribución Mensual del Gasto & Entregables</h6>
                            <div class="alert alert-info py-2 small">
                                <i class="fa-solid fa-info-circle me-1"></i> Ingrese los montos proyectados por mes. Marque el check si ya presentó el entregable.
                            </div>
                            <div class="row g-3">
                                <?php 
                                $meses = ['ene','feb','mar','abr','may','jun','jul','ago','set','oct','nov','dic'];
                                foreach($meses as $mes): 
                                    $label = strtoupper($mes);
                                ?>
                                <div class="col-6 col-md-3 col-lg-2">
                                    <div class="card bg-light border-0">
                                        <div class="card-body p-2">
                                            <label class="form-label small fw-bold mb-1"><?= $label ?></label>
                                            <div class="input-group input-group-sm mb-1">
                                                <span class="input-group-text">S/</span>
                                                <input type="number" step="0.01" class="form-control input-monto" 
                                                       name="monto_<?= $mes ?>" id="monto_<?= $mes ?>" placeholder="0.00">
                                            </div>
                                            <div class="form-check form-check-sm">
                                                <input class="form-check-input" type="checkbox" name="entregable_<?= $mes ?>" id="entregable_<?= $mes ?>">
                                                <label class="form-check-label small text-muted" for="entregable_<?= $mes ?>">Conformidad</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </form>



            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-dark fw-bold" onclick="guardarLocador()">
                    <i class="fa-solid fa-save me-1"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Importar Excel -->
<div class="modal fade" id="modalImportar" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-file-excel me-2"></i>Importar Locadores</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formImportar" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Seleccione Archivo Excel (.xlsx, .xls)</label>
                        <input type="file" class="form-control" name="archivo_excel" id="archivo_excel" accept=".xlsx, .xls" required>
                        <small class="text-muted">Asegúrese de respetar el formato de columnas establecido.</small>
                    </div>
                    <div class="d-flex justify-content-end p-2 border bg-light rounded shadow-sm">
                        <a href="../plantilla_locadores.xlsx" class="text-decoration-none text-success fw-bold" download>
                            <i class="fa-solid fa-download me-1"></i> Descargar Plantilla Modelo
                        </a>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light d-flex justify-content-between">
                <button type="button" class="btn btn-outline-danger fw-bold" onclick="eliminarTodo()">
                    <i class="fa-solid fa-trash-can me-1"></i> Vaciar BD
                </button>
                <div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success fw-bold" onclick="procesarImportacion()">
                        <i class="fa-solid fa-upload me-1"></i> Procesar Importación
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<!-- DataTables Buttons & Export -->
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<script>
// Variable para controlar el modo de vista
let modoAgrupado = true; // Por defecto activado

// Función para inicializar la tabla
function initTable() {
    if ($.fn.DataTable.isDataTable('#tablaLocadores')) {
        $('#tablaLocadores').DataTable().destroy();
    }

    tabla = $('#tablaLocadores').DataTable({
        ajax: {
            url: '../controlador/LocadorControlador.php?op=listar',
            type: 'GET',
            data: function(d) {
                d.mes = $('#filtro_mes_cumple').val();
            },
            dataType: 'json',
            dataSrc: function(json) {
                let data = json.data || [];
                
                if (!modoAgrupado) return data;

                // Lógica de Agrupación
                let grouped = {};
                data.forEach(item => {
                    // Usar DNI como clave unica, o Nombre si no hay DNI
                    let key = item.dni && item.dni !== "null" ? item.dni : item.nombres_apellidos;
                    if (!grouped[key]) grouped[key] = [];
                    grouped[key].push(item);
                });

                let finalData = [];
                Object.values(grouped).forEach(group => {
                    // Ordenar por fecha inicio descendente (más reciente primero)
                    group.sort((a, b) => new Date(b.fecha_inicio) - new Date(a.fecha_inicio));

                    // El registro principal será el ACTIVO si existe, sino el más reciente (futuro PENDIENTE)
                    let primary = group.find(x => x.estado === 'ACTIVO');
                    if (!primary) primary = group[0]; // El más reciente (ej. renovación futura o pasado term)

                    // Los demás son sub-registros
                    let subs = group.filter(x => x.id !== primary.id);
                    // Ordenar subs por fecha
                    subs.sort((a, b) => new Date(b.fecha_inicio) - new Date(a.fecha_inicio));

                    primary.sub_records = subs;
                    primary.has_renewals = subs.length > 0;
                    
                    finalData.push(primary);
                });
                
                return finalData;
            }
        },
        createdRow: function(row, data, dataIndex) {
            if (data.estado === 'PENDIENTE') {
                $(row).addClass('table-warning');
            }
            // Eliminado borde grueso para mantener diseño limpio
            // if (data.has_renewals) { $(row).addClass('border-start border-4 border-info'); }
        },
        dom: '<"d-flex justify-content-between align-items-center mb-3"Bf>t<"d-flex justify-content-between align-items-center"ip>',
        buttons: [
            {
                text: modoAgrupado ? '<i class="fa-solid fa-layer-group me-1"></i> Desagrupar' : '<i class="fa-solid fa-layer-group me-1"></i> Agrupar',
                className: modoAgrupado ? 'btn btn-primary shadow-sm btn-sm' : 'btn btn-outline-secondary shadow-sm btn-sm',
                action: function (e, dt, node, config) {
                    modoAgrupado = !modoAgrupado;
                    initTable(); // Recargar tabla con nueva lógica
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fa-solid fa-file-excel me-1"></i> Excel',
                className: 'btn btn-success text-white shadow-sm btn-sm',
                exportOptions: { 
                    columns: ':not(.col-actions):not(.details-control)', // Excluir acciones y boton expandir
                    format: {
                        body: function ( data, row, column, node ) {
                             if (column === 0) return data; // Index
                             return data.replace( /<.*?>/g, "" );
                        }
                    }
                },
                filename: 'Reporte_Locadores_' + new Date().toISOString().split('T')[0],
                title: 'PADRÓN GENERAL DE LOCADORES'
            },
            {
                extend: 'pdfHtml5',
                text: '<i class="fa-solid fa-file-pdf me-1"></i> PDF',
                className: 'btn btn-danger text-white shadow-sm btn-sm',
                orientation: 'landscape',
                pageSize: 'LEGAL',
                exportOptions: { columns: ':visible:not(.col-actions):not(.details-control)' },
                customize: function (doc) {
                    // (Mantener personalización previa del PDF - simplificada aquí por brevedad, asumiendo que se mantiene la lógica si no se sobreescribe todo el bloque de buttons)
                    // ... Para asegurar consistencia, idealmente repetimos el codigo de customización del PDF
                    doc.pageMargins = [20, 30, 20, 30];
                    doc.defaultStyle.fontSize = 8;
                    doc.styles.tableHeader.fontSize = 9;
                    doc.styles.tableHeader.fillColor = '#2c3e50';
                    doc.content[1].table.widths = ['3%', '20%', '10%', '25%', '10%', '12%', '10%', '5%', '5%']; // Ajustado
                }
            }
        ],
        columns: [
            // 0. # (Index)
            { 
                 data: null, 
                 width: '50px',
                 defaultContent: '',
                 className: 'text-center'
            },
            // 0.1 Expand Button (Solo si grouped y tiene hijos)
            {
                className: 'details-control text-center px-0',
                orderable: false,
                data: null,
                defaultContent: '',
                width: '45px',
                render: function(data, type, row) {
                     if(modoAgrupado && row.has_renewals) {
                         // Badge contador
                         return `<button class="btn btn-sm btn-info text-white rounded-circle btn-expand" style="width: 24px; height: 24px; padding: 0; line-height: 24px;">
                                    <i class="fa-solid fa-plus" style="font-size: 10px;"></i>
                                 </button>`;
                     }
                     return '';
                }
            },
            // 1. Locador
            { 
                data: null,
                width: '200px',
                render: function(data, type, row) {
                    let badge = '';
                    if (modoAgrupado && row.has_renewals) {
                        badge = `<span class="badge bg-warning text-dark ms-1" style="font-size: 0.7rem;"><i class="fa-solid fa-files me-1"></i>+${row.sub_records.length}</span>`;
                    }
                    return `<div class="text-dark text-wrap" style="font-size: 0.85rem; line-height: 1.2;">
                                ${data.nombres_apellidos}
                                ${badge}
                                <a href="javascript:void(0)" onclick="verHistorial('${row.dni}', '${data.nombres_apellidos}')" class="ms-2 text-info opacity-50 hover-100" title="Ver Historial Completo">
                                    <i class="fa-solid fa-clock-rotate-left"></i>
                                </a>
                            </div>`;
                }
            },
            // 2. DNI
            {
                data: null,
                width: '100px',
                className: 'text-center',
                render: function(data, type, row) {
                    if (row.dni && row.dni.trim() !== '') return `<span class="text-dark font-monospace text-nowrap">${row.dni}</span>`;
                    let val = row.dni_ruc;
                    let str = String(val);
                    if (val && str.length <= 9) return `<span class="text-dark font-monospace text-nowrap">${val}</span>`;
                    if (val && str.length === 11 && str.startsWith('10')) return `<span class="text-dark font-monospace text-nowrap">${str.substring(2, 10)}</span>`;
                    return '<span class="text-muted">-</span>';
                }
            },
            // 3. Servicio / Cargo
            { 
                data: 'servicio_descripcion',
                width: '300px',
                render: function(data) {
                    if (!data) return '';
                    return `<div class="text-truncate" style="max-width: 300px;" title="${data}">${data}</div>`;
                }
            },
            // 4. Monto Mensual
            { 
                data: 'monto_mensual',
                render: function(data) { 
                    return parseFloat(data).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}); 
                },
                className: 'text-end text-dark'
            },
            // 5. Monto Total
            {
                data: null,
                width: '130px',
                className: 'text-end text-dark',
                render: function(data, type, row) {
                    let total = 0;
                    ['ene','feb','mar','abr','may','jun','jul','ago','set','oct','nov','dic'].forEach(m => {
                         total += parseFloat(row['monto_' + m] || 0);
                    });
                    return total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                }
            },
            // 5. Vigencia
            { 
                data: null, 
                className: 'small text-muted text-center',
                render: function(data) {
                    if(!data.fecha_inicio || !data.fecha_fin) return '-';
                    let p1 = data.fecha_inicio.split(' ')[0].split('-');
                    let p2 = data.fecha_fin.split(' ')[0].split('-');
                    var d1 = new Date(p1[0], p1[1]-1, p1[2]);
                    var d2 = new Date(p2[0], p2[1]-1, p2[2]);
                    // Formato corto
                    let f1 = d1.toLocaleDateString('es-PE', {day:'2-digit', month:'2-digit', year:'2-digit'});
                    let f2 = d2.toLocaleDateString('es-PE', {day:'2-digit', month:'2-digit', year:'2-digit'});
                    return `<span class="d-block text-dark">${f1}</span><span class="d-block text-secondary" style="font-size: 0.7em;">AL</span><span class="d-block text-dark">${f2}</span>`;
                }
            },
            // 6. Unidad Solicitante
            { data: 'unidad_asignada', className: 'small' },
            // 7. CMN
            { 
                data: 'num_cmn', 
                className: 'text-center text-nowrap',
                width: '80px',
                render: function(data, type, row) {
                    let pdfIcon = row.archivo_pdf ? `<a href="../uploads/locadores/${row.archivo_pdf}" target="_blank" class="text-danger ms-1"><i class="fa-solid fa-file-pdf"></i></a>` : '';
                    return `<div>${data||'-'}${pdfIcon}</div>`;
                }
            },
            // 8. SIGA
            { 
                data: 'num_pedido_siga', 
                className: 'text-center text-nowrap',
                width: '80px',
                render: function(data, type, row) {
                    let sigaIcon = row.archivo_siga ? `<a href="../uploads/locadores/${row.archivo_siga}" target="_blank" class="text-primary ms-1"><i class="fa-solid fa-file-pdf"></i></a>` : '';
                    return `<div>${data||'-'}${sigaIcon}</div>`;
                }
            },
            // 9. Estado
            { 
                data: 'estado',
                render: function(data, type, row) {
                    // Para filtrado y ordenamiento, devolver texto plano
                    if (type !== 'display') return data;

                    // Validar vencimiento visual
                    if(row.fecha_fin) {
                        let parts = row.fecha_fin.split('-');
                        let fFin = new Date(parts[0], parts[1]-1, parts[2]);
                        let today = new Date();
                        today.setHours(0,0,0,0);
                        if(fFin < today && data === 'ACTIVO') {
                            return `<span class="badge bg-danger shadow-sm">VENCIDO</span>`;
                        }
                    }
                    let color = 'secondary';
                    if (data === 'ACTIVO')   color = 'success';
                    if (data === 'FINALIZADO') color = 'dark';
                    if (data === 'PENDIENTE')  color = 'warning text-dark';
                    return `<span class="badge bg-${color} shadow-sm">${data}</span>`;
                },
                className: 'text-center'
            },
            // 10. Evaluación
            {
                data: 'recontratacion',
                visible: false,
                className: 'd-none',
                render: function(data, type, row) {
                    if (data === 'RECOMENDADO') return 'Recomendado';
                    if (data === 'NO RECOMENDADO') return 'No Rec.';
                    return 'Pendiente';
                }
            },
            // 11. Campos ocultos para Excel
            { data: 'dni_ruc', visible: false, className: 'd-none' },
            { data: 'celular', visible: false, className: 'd-none' },
            { data: 'correo', visible: false, className: 'd-none' },
            { data: 'fecha_inicio', visible: false, className: 'd-none' },
            { data: 'fecha_fin', visible: false, className: 'd-none' },
            { data: 'meta', visible: false, className: 'd-none' },
            { data: 'esp_gasto', visible: false, className: 'd-none' },
            { data: 'codigo_siga', visible: false, className: 'd-none' },
            { data: 'monto_ene', visible: false, className: 'd-none' },
            { data: 'monto_feb', visible: false, className: 'd-none' },
            { data: 'monto_mar', visible: false, className: 'd-none' },
            { data: 'monto_abr', visible: false, className: 'd-none' },
            { data: 'monto_may', visible: false, className: 'd-none' },
            { data: 'monto_jun', visible: false, className: 'd-none' },
            { data: 'monto_jul', visible: false, className: 'd-none' },
            { data: 'monto_ago', visible: false, className: 'd-none' },
            { data: 'monto_set', visible: false, className: 'd-none' },
            { data: 'monto_oct', visible: false, className: 'd-none' },
            { data: 'monto_nov', visible: false, className: 'd-none' },
            { data: 'monto_dic', visible: false, className: 'd-none' },
            // 12. Acciones
            {
                data: 'id',
                className: 'text-center col-actions',
                width: '130px',
                render: function(data, type, row) {
                    // Solo permitir renovar si NO es pendiente
                    let btnRenovar = '';
                    if (row.estado === 'PENDIENTE') {
                        btnRenovar = `<button class="btn btn-sm btn-icon btn-outline-secondary" disabled style="opacity: 0.4; cursor: not-allowed;" title="Debe ser ACTIVO para renovar"><i class="fa-solid fa-calendar-plus"></i></button>`;
                    } else {
                        btnRenovar = `<button class="btn btn-sm btn-icon btn-outline-success" onclick="renovar(${data})" title="Renovar"><i class="fa-solid fa-calendar-plus"></i></button>`;
                    }

                    return `<div class="d-flex justify-content-center gap-1">
                                <button class="btn btn-sm btn-icon btn-outline-primary" onclick="editar(${data})" title="Editar"><i class="fa-solid fa-pen"></i></button>
                                ${btnRenovar}
                                <button class="btn btn-sm btn-icon btn-outline-danger" onclick="eliminar(${data})" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
                            </div>`;
                }
            }
        ],
        language: {
            "emptyTable": "No hay información",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ Locadores",
            "infoEmpty": "Mostrando 0 a 0 de 0",
            "infoFiltered": "(filtrado)",
            "lengthMenu": "Mostrar _MENU_",
            "loadingRecords": "Cargando...",
            "processing": "Procesando...",
            "search": "Buscar:",
            "zeroRecords": "Sin resultados",
            "paginate": { "previous": "<", "next": ">" }
        }
    });

    // Numeración dinámica (1, 2, 3...) visual
    tabla.on('order.dt search.dt', function () {
        tabla.column(0, {search:'applied', order:'applied'}).nodes().each( function (cell, i) {
            cell.innerHTML = i+1;
        });
    }).draw();

    // Event listener para abrir child rows (Modo Acordeón)
    $('#tablaLocadores tbody').on('click', 'button.btn-expand', function () {
        var tr = $(this).closest('tr');
        var row = tabla.row(tr);
        var icon = $(this).find('i');

        if (row.child.isShown()) {
            // Cerrar la fila actual si ya está abierta
            row.child.hide();
            tr.removeClass('shown');
            icon.removeClass('fa-minus').addClass('fa-plus');
            $(this).removeClass('btn-secondary').addClass('btn-info');
        } else {
            // CERRAR TODAS las otras filas abiertas primero (Efecto Acordeón)
            tabla.rows().every(function() {
                if (this.child.isShown()) {
                    this.child.hide();
                    $(this.node()).removeClass('shown');
                    // Resetear botón de la fila cerrada
                    var btn = $(this.node()).find('button.btn-expand');
                    btn.removeClass('btn-secondary').addClass('btn-info');
                    btn.find('i').removeClass('fa-minus').addClass('fa-plus');
                }
            });

            // Abrir la fila actual con padding 0 y sin bordes para que la tabla interna ocupe todo el ancho real
            row.child(formatChildRow(row.data()), 'p-0 border-0').show();
            tr.addClass('shown');
            icon.removeClass('fa-plus').addClass('fa-minus');
            $(this).removeClass('btn-info').addClass('btn-secondary');

            // Force alignment after render
            setTimeout(function() {
                alignChildColumns(row);
            }, 0);
        }
    });
}

// Helper para sincronizar anchos (Grid Alignment Fix)
function alignChildColumns(row) {
    var $parent = $('#tablaLocadores');
    // Ensure we are targeting the table directly inside the shown child row
    var $childTable = row.child().find('table');
    var $cols = $childTable.find('colgroup col');
    var $headers = $parent.find('thead th:visible'); 

    if ($headers.length === $cols.length) {
        $headers.each(function(index) {
            var width = $(this).outerWidth(); // Get precise width including padding/border
            $cols.eq(index).css('width', width + 'px');
        });
        // Ensure the table takes the full width required
        $childTable.css('width', $parent.outerWidth() + 'px');
    }
}

// Keep alignment in sync on window resize
$(window).on('resize', function() {
    if (typeof tabla !== 'undefined') {
        tabla.rows().every(function() {
            if (this.child.isShown()) {
                alignChildColumns(this);
            }
        });
    }
});


// Función para generar HTML de los registros hijos (Renovaciones/Historial)
function formatChildRow(d) {
    if (!d.sub_records || d.sub_records.length === 0) return '';

    let rowsHtml = '';
    d.sub_records.forEach(sub => {
        // Lógica de Estado
        let badgeEstado = ''; 
        let esVencido = false;
        if(sub.fecha_fin) {
            let parts = sub.fecha_fin.split('-');
            let fFin = new Date(parts[0], parts[1]-1, parts[2]);
            let today = new Date();
            today.setHours(0,0,0,0);
            if(fFin < today && sub.estado === 'ACTIVO') {
                esVencido = true;
            }
        }

        if(esVencido) {
            badgeEstado = `<span class="badge bg-danger shadow-sm">VENCIDO</span>`;
        } else {
            let color = 'secondary';
            if (sub.estado === 'ACTIVO')   color = 'success';
            if (sub.estado === 'FINALIZADO') color = 'dark';
            if (sub.estado === 'PENDIENTE')  color = 'warning text-dark';
            badgeEstado = `<span class="badge bg-${color} shadow-sm">${sub.estado}</span>`;
        }
        
        // Lógica de Vigencia
        let vigenciaHtml = '-';
        if(sub.fecha_inicio && sub.fecha_fin) {
            let p1 = sub.fecha_inicio.split('-');
            let p2 = sub.fecha_fin.split('-');
            let f1 = `${p1[2]}/${p1[1]}/${p1[0].substring(2)}`;
            let f2 = `${p2[2]}/${p2[1]}/${p2[0].substring(2)}`;
            vigenciaHtml = `<span class="d-block text-dark">${f1}</span><span class="d-block text-secondary" style="font-size: 0.7em;">AL</span><span class="d-block text-dark">${f2}</span>`;
        }

        let evalBadge = '<span class="badge bg-light text-muted border">Pendiente</span>';
        if (sub.recontratacion === 'RECOMENDADO') evalBadge = '<span class="badge bg-success"><i class="fa-solid fa-thumbs-up me-1"></i>Recomendado</span>';
        if (sub.recontratacion === 'NO RECOMENDADO') evalBadge = '<span class="badge bg-danger"><i class="fa-solid fa-thumbs-down me-1"></i>No Rec.</span>';

        rowsHtml += `
            <tr class="table-history">
                <!-- 1. # -->
                <td class="text-center align-middle text-muted">
                    <i class="fa-solid fa-arrow-turn-up fa-rotate-90 text-primary opacity-50"></i>
                </td>
                
                <!-- 2. Expand -->
                <td></td> 
                
                <!-- 3. Locador -->
                <td class="align-middle ps-4">
                    <div class="text-dark text-wrap lh-sm">
                        ${sub.nombres_apellidos}
                        <span class="badge bg-light text-secondary border ms-1" style="font-size: 0.65rem;">HISTORIAL</span>
                    </div>
                </td>
                
                <!-- 4. DNI -->
                <td class="text-center align-middle">
                     <span class="text-dark font-monospace">${sub.dni || sub.dni_ruc || '-'}</span>
                </td> 

                <!-- 5. Servicio -->
                <td class="align-middle text-wrap">
                    <div class="text-secondary lh-sm" style="font-size: 0.9rem;">${sub.servicio_descripcion}</div>
                </td>
                
                <!-- 6. Monto Mensual -->
                <td class="text-end align-middle">
                    <span class="font-monospace text-dark">${parseFloat(sub.monto_mensual).toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
                </td>
                
                <!-- 6.5 Monto Total -->
                <td class="text-end align-middle">
                    <span class="font-monospace text-dark">${(() => {
                        let total = 0;
                        ['ene','feb','mar','abr','may','jun','jul','ago','set','oct','nov','dic'].forEach(m => {
                             total += parseFloat(sub['monto_' + m] || 0);
                        });
                        return total.toLocaleString('en-US', {minimumFractionDigits: 2});
                    })()}</span>
                </td>

                <!-- 7. Vigencia -->
                <td class="align-middle text-nowrap text-center">
                    ${vigenciaHtml}
                </td>
                
                <!-- 8. Unidad -->
                <td class="align-middle text-wrap">
                    <span class="text-secondary" style="font-size: 0.9rem;">${sub.unidad_asignada || '-'}</span>
                </td>
                
                <!-- 9. CMN -->
                <td class="text-center align-middle text-muted">${sub.num_cmn || '-'}</td>
                
                <!-- 10. SIGA -->
                <td class="text-center align-middle text-muted">${sub.num_pedido_siga || '-'}</td>
                
                <!-- 11. Estado -->
                <td class="align-middle text-center">
                    ${badgeEstado}
                </td>

                <!-- 14. Acciones -->
                <td class="text-center align-middle">
                     <div class="d-flex justify-content-center gap-1">
                        <button class="btn btn-sm btn-icon btn-outline-primary" onclick="editar(${sub.id})" title="Editar"><i class="fa-solid fa-pen"></i></button>
                        <button class="btn btn-sm btn-icon btn-outline-success invisible" style="cursor: default; pointer-events: none;" disabled><i class="fa-solid fa-calendar-plus"></i></button>
                        <button class="btn btn-sm btn-icon btn-outline-danger" onclick="eliminar(${sub.id})" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
                     </div>
                </td>
            </tr>
        `;
    });

    return `
        <table class="table table-bordered mb-0 align-middle w-100" style="table-layout: fixed;">
            <!-- Column group structure to mirror parent -->
            <colgroup>
                <col style="width: 30px;">
                <col style="width: 30px;">
                <col style="width: 200px;">
                <col style="width: 100px;">
                <col style="width: 300px;">
                <col style="width: 130px;">
                <col style="width: 130px;">
                <col style="width: 180px;">
                <col style="width: 180px;">
                <col style="width: 70px;">
                <col style="width: 70px;">
                <col style="width: 120px;">
                <col style="width: 100px;">
            </colgroup>
            <tbody>
                ${rowsHtml}
            </tbody>
        </table>
    `;
}

$(document).ready(function() {
    initTable(); // Iniciar tabla con la nueva lógica

    cargarEstadisticas();
    
    // Auto calcular al cambiar monto y Sincronizar Fechas
    $('#monto_mensual').on('input change', function(){ /* Solo validación visual si se desea */ });

    // Auto-llenado inteligente de DNI si insertan RUC 10...
    $('#input_ruc').on('input', function() {
        let ruc = $(this).val();
        if (ruc.length === 11 && ruc.startsWith('10')) {
             let dniAuto = ruc.substring(2, 10);
             // Solo sugerir si el campo DNI está vacío para no chancar edits manuales
             if ($('#input_dni').val() === '') {
                 $('#input_dni').val(dniAuto);
             }
        }
    });

    // Detectar cambios en los inputs de meses para actualizar fechas automágicamente
    $('.input-monto').on('input change', function() {
        calcularTotalAnual();
        actualizarFechasDesdeCronograma();
        calcularTotalAnual();
        calcularTotalAnual();
        actualizarFechasDesdeCronograma();
    });

    // Filtro Cumpleaños
    $('#filtro_mes_cumple').on('change', function() {
        initTable();
    });

    // Verificar Cumpleaños al cargar
    checkBirthday();
});


function cargarEstadisticas() {
    $.get('../controlador/LocadorControlador.php?op=estadisticas', function(res) {
        if(res.total_monto) {
            let montoFormateado = parseFloat(res.total_monto).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            $('#statMonto').text('S/ ' + montoFormateado);
        } else {
            $('#statMonto').text('S/ 0.00');
        }
        if(res.total_activos) $('#statActivos').text(res.total_activos);
        // NUEVOS CONTADORES
        if(res.total_pendientes !== undefined) $('#statPendientes').text(res.total_pendientes);
        if(res.total_finalizados !== undefined) $('#statFinalizados').text(res.total_finalizados);
        
        if(res.por_vencer) $('#statVencimientos').text(res.por_vencer);
    }, 'json');
}

function abrirModal(id) {
    $('#formLocador')[0].reset();
    $('#id').val('');
    $('#dia_nac').val('');
    $('#mes_nac').val('');
    $('#fecha_nacimiento_hidden').val('');
    $('#sexo').val('M'); // Resetear el campo de género
    $('#modalTitulo').text('Nuevo Contrato');
    $('#totalAnual').text('S/ 0.00');
    $('#link_pdf_actual').html(''); 
    $('#archivo_pdf').val(''); 
    $('#link_siga_actual').html(''); 
    $('#archivo_siga').val('');
    
    // Resetear campos específicos para nuevo contrato
    $('#recontratacion').val('PENDIENTE'); // Default para nuevo
    const meses = ['ene','feb','mar','abr','may','jun','jul','ago','set','oct','nov','dic'];
    meses.forEach(m => {
        $('#entregable_'+m).prop('checked', false); // Desmarcar todos los entregables
    });

    // Reset Tabs
    $('.nav-link').removeClass('active');
    $('.tab-pane').removeClass('show active');
    $('#tab-general').addClass('active');
    $('#content-general').addClass('show active');

    $('#modalLocador').modal('show');
}

function editar(id) {
    // Limpiar formulario previo para evitar que queden archivos seleccionados de otra persona
    $('#formLocador')[0].reset();
    $('#archivo_pdf').val('');
    $('#archivo_siga').val('');
    $('#link_pdf_actual').html('');
    $('#link_siga_actual').html('');

    $.post('../controlador/LocadorControlador.php?op=obtener', {id: id}, function(data) {
        $('#id').val(data.id);
        
        // Cargar DNI o RUC en su input correspondiente
        $('#input_dni').val(data.dni || ''); // Cargar DNI explícito si existe
        $('#input_ruc').val('');
        $('#input_dni').val(data.dni || ''); // Cargar DNI explícito si existe
        $('#input_ruc').val('');
        $('#dni_ruc').val(data.dni_ruc);
        
        // Parsear fecha nacimiento (YYYY-MM-DD) para llenar selects
        if (data.fecha_nacimiento) {
            const partes = data.fecha_nacimiento.split('-'); // [YYYY, MM, DD]
            if (partes.length === 3) {
                $('#mes_nac').val(parseInt(partes[1])); // Remover ceros iniciales
                $('#dia_nac').val(parseInt(partes[2]));
            }
        } else {
            $('#mes_nac').val('');
            $('#dia_nac').val('');
        }
        
        if(data.dni_ruc) {
            let docStr = String(data.dni_ruc);
            if(docStr.length === 11) {
                $('#input_ruc').val(docStr);
                
                // Si es un RUC de Persona Natural (empieza con 10) Y no tenemos DNI ya cargado
                if(!$('#input_dni').val() && docStr.startsWith('10')) {
                    let dniExtrahido = docStr.substring(2, 10);
                    $('#input_dni').val(dniExtrahido);
                }
            } else if (docStr.length === 8) {
                // Si lo guardado en dni_ruc es un DNI, mostrarlo si input_dni está vacío
                if(!$('#input_dni').val()) $('#input_dni').val(docStr);
            }
        }
        
        $('#nombres_apellidos').val(data.nombres_apellidos);
        $('#sexo').val(data.sexo || 'M');
        $('#correo').val(data.correo);
        $('#celular').val(data.celular);
        $('#servicio_descripcion').val(data.servicio_descripcion);
        $('#monto_mensual').val(data.monto_mensual);
        // $('#retencion_aplicable').prop('checked', data.retencion_aplicable == 1);
        $('#fecha_inicio').val(data.fecha_inicio);
        $('#fecha_fin').val(data.fecha_fin);
        $('#unidad_asignada').val(data.unidad_asignada);
        $('#estado').val(data.estado);
        
        // Admin Data
        $('#meta').val(data.meta);
        $('#esp_gasto').val(data.esp_gasto);
        $('#codigo_siga').val(data.codigo_siga);
        $('#num_pedido_siga').val(data.num_pedido_siga);
        $('#num_cmn').val(data.num_cmn);

        // Nuevos campos: recontratacion y entregables
        $('#recontratacion').val(data.recontratacion || 'PENDIENTE');
        
        // Meses y Entregables
        const meses = ['ene','feb','mar','abr','may','jun','jul','ago','set','oct','nov','dic'];
        meses.forEach(m => {
            $('#monto_'+m).val(data['monto_'+m]);
            // Cargar estado de los checkboxes de entregables
            $('#entregable_'+m).prop('checked', data['entregable_'+m] == 1);
        });

        // Función helper para limpiar nombre de archivo (visual)
        const formatFileName = (name) => {
            // Si tiene formato timestamp_..., intentamos quitar el timestamp para que se vea limpio
            let parts = name.split('_');
            if(parts.length > 1 && !isNaN(parts[0])) {
                return parts.slice(1).join('_'); // Retorna todo menos el primer pedazo
            }
            return name;
        };

        // Mostrar link si existe PDF
        if(data.archivo_pdf) {
            $('#link_pdf_actual').html(`
                <div class="mt-2 p-2 bg-white border rounded d-flex align-items-center shadow-sm">
                    <i class="fa-solid fa-file-pdf text-danger me-2 fa-lg"></i>
                    <div class="overflow-hidden me-auto" style="min-width: 0;">
                        <a href="../uploads/locadores/${data.archivo_pdf}" target="_blank" class="text-secondary text-decoration-none d-block text-truncate" style="font-size: 0.85rem;" title="${data.archivo_pdf}">
                            ${formatFileName(data.archivo_pdf)}
                        </a>
                    </div>
                    <a href="../uploads/locadores/${data.archivo_pdf}" target="_blank" class="text-muted ms-2 hover-dark"><i class="fa-solid fa-eye"></i></a>
                </div>
            `);
        } else {
            $('#link_pdf_actual').html('');
        }

        if(data.archivo_siga) {
            $('#link_siga_actual').html(`
                <div class="mt-2 p-2 bg-white border rounded d-flex align-items-center shadow-sm">
                    <i class="fa-solid fa-file-pdf text-danger me-2 fa-lg"></i>
                    <div class="overflow-hidden me-auto" style="min-width: 0;">
                        <a href="../uploads/locadores/${data.archivo_siga}" target="_blank" class="text-secondary text-decoration-none d-block text-truncate" style="font-size: 0.85rem;" title="${data.archivo_siga}">
                            ${formatFileName(data.archivo_siga)}
                        </a>
                    </div>
                    <a href="../uploads/locadores/${data.archivo_siga}" target="_blank" class="text-muted ms-2 hover-dark"><i class="fa-solid fa-eye"></i></a>
                </div>
            `);
        } else {
            $('#link_siga_actual').html('');
        }

        // Reset tabs
        $('.nav-link').removeClass('active');
        $('.tab-pane').removeClass('show active');
        $('#tab-general').addClass('active');
        $('#content-general').addClass('show active');

        calcularTotalAnual(); 
        
        $('#modalTitulo').text('Editar Contrato');
        $('#modalLocador').modal('show');
    }, 'json');
}

function renovar(id) {
    // 1. Limpiar formulario
    $('#formLocador')[0].reset();
    $('#id').val(''); // IMPORTANTE: Dejar vacío para que se cree NUEVO, no edit
    $('#archivo_pdf').val('');
    $('#archivo_siga').val('');
    $('#link_pdf_actual').html('');
    $('#link_siga_actual').html('');
    
    // 2. Traer datos del contrato anterior para pre-llenar
    $.post('../controlador/LocadorControlador.php?op=obtener', {id: id}, function(data) {
        // Datos Personales (Se mantienen)
        $('#input_dni').val(data.dni || '');
        $('#input_ruc').val('');
        $('#dni_ruc').val(data.dni_ruc);
        if(data.dni_ruc) {
            let docStr = String(data.dni_ruc);
            if(docStr.length === 11) {
                $('#input_ruc').val(docStr);
                if(!$('#input_dni').val() && docStr.startsWith('10')) {
                    $('#input_dni').val(docStr.substring(2, 10));
                }
            } else if (docStr.length === 8 && !$('#input_dni').val()) {
                $('#input_dni').val(docStr);
            }
        }
        
        $('#nombres_apellidos').val(data.nombres_apellidos);
        $('#fecha_nacimiento').val(data.fecha_nacimiento);
         // Parsear fecha nacimiento (YYYY-MM-DD) para llenar selects
        if (data.fecha_nacimiento) {
            const partes = data.fecha_nacimiento.split('-'); // [YYYY, MM, DD]
            if (partes.length === 3) {
                $('#mes_nac').val(parseInt(partes[1])); // Remover ceros iniciales
                $('#dia_nac').val(parseInt(partes[2]));
            }
        } else {
             $('#mes_nac').val('');
             $('#dia_nac').val('');
        }
        
        $('#sexo').val(data.sexo || 'M');

        $('#correo').val(data.correo);
        $('#celular').val(data.celular);
        
        // Datos de Servicio (Se mantienen sugeridos)
        $('#servicio_descripcion').val(data.servicio_descripcion);
        $('#unidad_asignada').val(data.unidad_asignada);
        $('#monto_mensual').val(data.monto_mensual);
        
        // Datos Admin (Se mantienen sugeridos, salvo nums de documento que cambian)
        $('#meta').val(data.meta);
        $('#esp_gasto').val(data.esp_gasto);
        $('#codigo_siga').val(data.codigo_siga);
        
        // Poner estado en PENDIENTE por defecto
        $('#estado').val('PENDIENTE');
        $('#recontratacion').val('PENDIENTE');

        // LIMPIAR FECHAS Y ARCHIVOS (Para que ingresen los nuevos)
        $('#fecha_inicio').val(''); 
        $('#fecha_fin').val('');
        $('#num_pedido_siga').val('');
        $('#num_cmn').val('');
        
        // Limpiar montos mensuales desglosados
        $('.input-monto').val('');
        $('input[type=checkbox][id^=entregable_]').prop('checked', false);
        
        $('#totalAnual').text('S/ 0.00');

        // Configurar UI
        $('.nav-link').removeClass('active');
        $('.tab-pane').removeClass('show active');
        $('#tab-general').addClass('active');
        $('#content-general').addClass('show active');
        
        // Título especial
        $('#modalTitulo').html('<i class="fa-solid fa-calendar-plus me-2"></i>Renovar Contrato');
        
        Swal.fire({
            title: 'Renovación de Contrato',
            text: 'Datos cargados. Ingrese las NUEVAS fechas. **Si el sueldo cambió, actualice el "Monto Mensual" y presione Distribuir.**',
            icon: 'info',
            timer: 5000
        });

        $('#modalLocador').modal('show');
    }, 'json');
}

function replicarMonto() {
    var monto = $('#monto_mensual').val();
    var fecInicio = $('#fecha_inicio').val();
    var fecFin = $('#fecha_fin').val();

    if(!monto) {
        Swal.fire('Atención', 'Ingrese primero un monto mensual referencial.', 'warning');
        return;
    }

    if(fecInicio && fecFin) {
        var partesIni = fecInicio.split('-');
        var partesFin = fecFin.split('-');
        var startYear = parseInt(partesIni[0]);
        var endYear = parseInt(partesFin[0]);
        var startMonth = parseInt(partesIni[1]) - 1; 
        var endMonth = parseInt(partesFin[1]) - 1;   

        var yearEvaluado = startYear; 
        var meses = ['ene','feb','mar','abr','may','jun','jul','ago','set','oct','nov','dic'];
        
        for(var i=0; i<meses.length; i++) {
            var m = meses[i];
            var mesActualAbs = (yearEvaluado * 12) + i;
            var inicioAbs = (startYear * 12) + startMonth;
            var finAbs = (endYear * 12) + endMonth;

            if(mesActualAbs >= inicioAbs && mesActualAbs <= finAbs) {
                $('#monto_'+m).val(monto);
            } else {
                $('#monto_'+m).val('');
            }
        }
        
        calcularTotalAnual();
        
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
        Toast.fire({
            icon: 'success',
            title: 'Montos distribuidos según vigencia'
        });

    } else {
        Swal.fire({
            title: 'Fechas no definidas',
            text: "No ha ingresado fechas de inicio/fin. ¿Desea llenar TODOS los meses del año?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, llenar todo',
            cancelButtonText: 'Cancelar'
        }).then(function(result) {
            if (result.isConfirmed) {
                $('.input-monto').val(monto);
                calcularTotalAnual();
            }
        });
        return; 
    }
    
    calcularTotalAnual();
}

function filtrarPorVencer() {
    if(typeof toggleVencimientos === 'function') {
        toggleVencimientos();
    } else {
        console.warn('Función toggleVencimientos no cargada aún');
    }
}

function calcularTotalAnual() {
    var total = 0;
    $('.input-monto').each(function() {
        var val = parseFloat($(this).val()) || 0;
        total += val;
        
        if(val > 0) {
            $(this).addClass('bg-success bg-opacity-10 fw-bold text-success');
            $(this).removeClass('bg-white');
        } else {
            $(this).removeClass('bg-success bg-opacity-10 fw-bold text-success');
            $(this).addClass('bg-white');
        }
    });
    $('#totalAnual').text('S/ ' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
}

// function updated to fix syntax error
function actualizarFechasDesdeCronograma() {
    let meses = ['ene','feb','mar','abr','may','jun','jul','ago','set','oct','nov','dic'];
    let primerMes = -1;
    let ultimoMes = -1;

    meses.forEach((m, index) => {
        let monto = parseFloat($('#monto_' + m).val()) || 0;
        if (monto > 0) {
            if (primerMes === -1) primerMes = index;
            ultimoMes = index;
        }
    });

    if (primerMes !== -1) {
        let anio = new Date().getFullYear();
        // Si ya hay una fecha de inicio, preservar ese año
        if ($('#fecha_inicio').val()) {
            anio = $('#fecha_inicio').val().split('-')[0];
        }

        let fIni = `${anio}-${String(primerMes + 1).padStart(2, '0')}-01`;
        
        // El último día del último mes con monto
        let ultimoDia = new Date(anio, ultimoMes + 1, 0).getDate();
        let fFin = `${anio}-${String(ultimoMes + 1).padStart(2, '0')}-${ultimoDia}`;

        $('#fecha_inicio').val(fIni);
        $('#fecha_fin').val(fFin);
        
        calcularTotalAnual();
    }
}

function abrirModalImportar() {
    $('#formImportar')[0].reset();
    $('#modalImportar').modal('show');
}

function procesarImportacion() {
    let formData = new FormData($('#formImportar')[0]);
    
    // Validar archivo
    let archivo = $('#archivo_excel').val();
    if(!archivo) {
        Swal.fire('Error', 'Seleccione un archivo Excel.', 'error');
        return;
    }

    Swal.fire({
        title: 'Procesando...',
        text: 'Por favor espere mientras se importan los datos.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    $.ajax({
        url: '../controlador/LocadorControlador.php?op=importar_excel',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
            Swal.close();
            if (res.status) {
                $('#modalImportar').modal('hide');
                tabla.ajax.reload();
                cargarEstadisticas();
                Swal.fire('Importación Exitosa', res.msg, 'success');
            } else {
                Swal.fire('Error en Importación', res.msg, 'error');
            }
        },
        error: function(xhr, status, error) {
            Swal.close();
            Swal.fire('Error del Servidor', 'Ocurrió un error al procesar el archivo. Verifique el formato.', 'error');
            console.error(error);
        }
    });
}

function guardarLocador() {
    // Sincronizar inputs: Mapeo independiente estricto
    let ruc = $('#input_ruc').val();
    let dni = $('#input_dni').val();
    
    // El input RUC tiene prioridad para dni_ruc, si no hay RUC usar DNI
    let dni_ruc_final = ruc && ruc.trim() !== '' ? ruc : dni;
    $('#dni_ruc').val(dni_ruc_final);
    
    // Validación básica: Solo pediremos Nombres que es lo esencial para ubicar al registro
    if (!$('#nombres_apellidos').val()) {
        Swal.fire({
            icon: 'warning',
            title: 'Nombres faltantes',
            text: 'Por favor, al menos debe ingresar los Apellidos y Nombres.'
        });
        return;
    }
    
    // Construir fecha nacimiento convertidita
    let dia = $('#dia_nac').val();
    let mes = $('#mes_nac').val();
    
    if(dia && mes) {
        // Usamos año bisiesto 2000 como referencia para que siempre exista el día (ej. 29 feb)
        let fechaFmt = `2000-${String(mes).padStart(2,'0')}-${String(dia).padStart(2,'0')}`;
        $('#fecha_nacimiento_hidden').val(fechaFmt);
    } else {
        $('#fecha_nacimiento_hidden').val('');
    }

    let formData = new FormData($('#formLocador')[0]);
    $.ajax({
        url: '../controlador/LocadorControlador.php?op=guardar',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
            if (res.status) {
                $('#modalLocador').modal('hide');
                tabla.ajax.reload();
                cargarEstadisticas();
                Swal.fire('Éxito', res.msg, 'success');
            } else {
                // Usamos html property para que se rendericen los saltos de línea <br>
                Swal.fire({
                    icon: 'error',
                    title: 'Atención',
                    html: res.msg
                });
            }
        },
        error: function(xhr, status, error) {
            console.error("Error en guardarLocador:", status, error);
            console.error("Respuesta del servidor:", xhr.responseText);
            Swal.fire('Error del Servidor', 'Ocurrió un error inesperado al guardar. Revise la consola para más detalles.', 'error');
        }
    });
}

function eliminar(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "Se eliminará este registro lógicamente.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('../controlador/LocadorControlador.php?op=eliminar', {id: id}, function(res) {
                if(res.status) {
                    tabla.ajax.reload();
                    cargarEstadisticas();
                    Swal.fire('Eliminado', res.msg, 'success');
                } else {
                    Swal.fire('Error', res.msg, 'error');
                }
            }, 'json');
        }
    });
}

// ==========================================
// NUEVAS FUNCIONES (Analitica, Historial, Filtros)
// ==========================================

function verEstadisticas() {
    $('#modalEstadisticas').modal('show');
    
    $.get('../controlador/LocadorControlador.php?op=estadisticas_graficos', function(response) {
        const data = response.data;
        
        // Chart Unidades (Pie)
        const ctxUnidades = document.getElementById('chartUnidades').getContext('2d');
        if(window.chart1) window.chart1.destroy();
        
        window.chart1 = new Chart(ctxUnidades, {
            type: 'doughnut',
            data: {
                labels: data.por_unidad.map(i => i.label || 'Sin Asignar'),
                datasets: [{
                    data: data.por_unidad.map(i => i.value),
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#5a5c69']
                }]
            },
            options: { maintainAspectRatio: false, cutout: '70%' }
        });

        // Chart Gasto (Bar)
        const ctxGasto = document.getElementById('chartGasto').getContext('2d');
        if(window.chart2) window.chart2.destroy();

        // Generar paleta de colores dinámica o fija
        const backgroundColors = [
            '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', 
            '#858796', '#5a5c69', '#2c3e50', '#d1d3e2', '#f8f9fc'
        ];

        window.chart2 = new Chart(ctxGasto, {
            type: 'bar',
            data: {
                labels: data.gasto_unidad.map(i => i.label || 'Sin Asignar'),
                datasets: [{
                    label: 'S/ Proyectado',
                    data: data.gasto_unidad.map(i => i.value),
                    backgroundColor: backgroundColors,
                    borderRadius: 5,
                    borderSkipped: false
                }]
            },
            options: { 
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false } // Ocultar leyenda redundante en gráfico de barras simple
                },
                scales: { 
                    y: { 
                        beginAtZero: true,
                        grid: { borderDash: [2, 2] } 
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }, 'json');
}

function verHistorial(dni, nombre) {
    if(!dni || dni === 'null' || dni === 'undefined') {
        Swal.fire('Info', 'Este locador no tiene DNI registrado para buscar historial.', 'info');
        return;
    }
    
    $('#historialNombre').text('Historial de: ' + nombre);
    $('#modalHistorial').modal('show');
    $('#tablaHistorialBody').html('<tr><td colspan="6" class="text-center">Cargando...</td></tr>');
    
    $.post('../controlador/LocadorControlador.php?op=historial_dni', {dni: dni}, function(response) {
        let rows = '';
        if(response.data && response.data.length > 0) {
            response.data.forEach(h => {
                let evalBadge = '';
                if(h.recontratacion === 'RECOMENDADO') evalBadge = '<span class="badge bg-success">✅ Rec.</span>';
                else if(h.recontratacion === 'NO RECOMENDADO') evalBadge = '<span class="badge bg-danger">❌ No Rec.</span>';
                else evalBadge = '<span class="badge bg-light text-muted">-</span>';
                
                rows += `<tr>
                            <td>${h.unidad_asignada || '-'}</td>
                            <td class="small">${h.servicio_descripcion || '-'}</td>
                            <td class="small">${h.fecha_inicio} al ${h.fecha_fin}</td>
                            <td class="text-end">S/ ${parseFloat(h.monto_mensual).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                            <td><span class="badge bg-secondary">${h.estado}</span></td>
                            <td class="text-center">${evalBadge}</td>
                         </tr>`;
            });
        } else {
            rows = '<tr><td colspan="6" class="text-center text-muted">No hay historial previo registrado.</td></tr>';
        }
        $('#tablaHistorialBody').html(rows);
    }, 'json');
}

// --- GESTIÓN DE FILTROS ---
let currentEstadoFiltro = null; // null, 'ACTIVO', 'PENDIENTE', 'FINALIZADO'
let vencimientosFilter = false;

function resetAllFilters() {
    // 1. Limpiar búsqueda de columnas (Estado)
    // Asegurarse de limpiar CUALQUIER filtro de búsqueda aplicado a la columna 11
    if(tabla) {
        try {
            tabla.column(11).search('').draw();
        
            // 2. Limpiar filtros custom (Vencimientos)
            if($.fn.dataTable.ext.search.length > 0) {
                $.fn.dataTable.ext.search.pop();
                tabla.draw();
            }
        } catch(e) { console.log("Error al resetear tabla", e); }
    }
    
    // 3. Reset Variables
    currentEstadoFiltro = null;
    vencimientosFilter = false;
    
    // 4. GUI Reset Classes
    $('.border-success-accent').removeClass('bg-success bg-opacity-10');
    $('.border-info-accent').removeClass('bg-info bg-opacity-10');
    $('.border-secondary-accent').removeClass('bg-secondary bg-opacity-10');
    $('#btnFilterVencimientos').removeClass('btn-danger text-white').addClass('btn-warning text-dark');
    $('.border-warning-accent').removeClass('bg-warning bg-opacity-10');
}

function toggleFiltroEstado(estado) {
    // Verificar si estamos desactivando el filtro actual
    if(currentEstadoFiltro === estado) {
        resetAllFilters();
        return;
    }
    
    // Resetear previos
    resetAllFilters();
    
    // Activar nuevo filtro
    currentEstadoFiltro = estado;
    
    // Aplicar filtro en DataTable columna 11 (Estado)
    try {
        if(tabla) tabla.column(11).search(estado).draw();
    } catch(e) { console.error("Error aplicando filtro tabla", e); }
    
    // Feedback Visual
    if(estado === 'ACTIVO') $('.border-success-accent').addClass('bg-success bg-opacity-10');
    else if(estado === 'PENDIENTE') $('.border-info-accent').addClass('bg-info bg-opacity-10');
    else if(estado === 'FINALIZADO') $('.border-secondary-accent').addClass('bg-secondary bg-opacity-10');
}

function toggleVencimientos() {
    if(vencimientosFilter) {
        resetAllFilters();
        return;
    }
    
    resetAllFilters();
    vencimientosFilter = true;
    
    $('#btnFilterVencimientos').removeClass('btn-warning text-dark').addClass('btn-danger text-white');
    $('.border-warning-accent').addClass('bg-warning bg-opacity-10');
    
    $.fn.dataTable.ext.search.push(
        function(settings, data, dataIndex) {
            try {
                let rowData = tabla.row(dataIndex).data();
                if(!rowData || !rowData.fecha_fin) return false;
                
                let parts = rowData.fecha_fin.split('-'); // YYYY-MM-DD
                let fFin = new Date(parts[0], parts[1]-1, parts[2]);
                let today = new Date();
                today.setHours(0,0,0,0);
                
                let diffTime = fFin - today;
                let diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                
                return (diffDays <= 30 && rowData.estado === 'ACTIVO');
            } catch(e) { return false; }
        }
    );
    tabla.draw();
}

function eliminarTodo() {
    Swal.fire({
        title: '¿LIMPIAR TODA LA DATA?',
        text: "¡Esto ELIMINARÁ TODOS los registros y reiniciará la base de datos! \n\nÚsalo solo antes de una carga masiva nueva para evitar duplicados.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, BORRAR TODO',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Limpiando...',
                text: 'Espere un momento',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            $.post('../controlador/LocadorControlador.php?op=eliminar_todo', function(res) {
                Swal.close();
                if(res.status) {
                    tabla.ajax.reload();
                    cargarEstadisticas();
                    Swal.fire('¡Limpieza Completa!', res.msg, 'success');
                } else {
                    Swal.fire('Error', res.msg, 'error');
                }
            }, 'json');
        }
    });
}

function checkBirthday() {
    $.get('../controlador/LocadorControlador.php?op=verificar_cumpleanos', function(data) {
        if (data && data.length > 0) {
            let cumpleanieros = data;
            let hayCumpleHoy = false;
            let minDiasRestantes = 999;
            let cumplesHoyCount = 0;
            let listHtml = '';
            
            // Procesar datos para validación y HTML
            cumpleanieros.forEach(c => {
                let fechaCumple = new Date(c.proximo_cumpleanos + 'T00:00:00');
                let hoy = new Date();
                hoy.setHours(0,0,0,0);
                
                // Diferencia en días
                let diffTime = fechaCumple - hoy;
                let dias = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                
                if (dias === 0) {
                    hayCumpleHoy = true;
                    cumplesHoyCount++;
                }
                if (dias >= 0 && dias < minDiasRestantes) {
                    minDiasRestantes = dias;
                }
                
                // Construir HTML item
                let meses = ['', 'Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
                let dia = fechaCumple.getDate();
                let mes = meses[fechaCumple.getMonth() + 1];
                let esHoy = (dias === 0);
                
                // Estilos dinámicos
                let bgItem = esHoy ? 'background: linear-gradient(90deg, rgba(255, 215, 0, 0.1), transparent); border-left: 4px solid #FFD700;' : 'border-left: 4px solid #444;';
                let badge = esHoy ? '<span class="badge bg-warning text-dark fw-bold shadow-sm animate-pulse">¡HOY!</span>' : '<span class="badge bg-secondary opacity-50">Próximamente</span>';
                let icon = esHoy ? '<i class="fa-solid fa-crown text-warning me-2"></i>' : '<i class="fa-regular fa-calendar text-muted me-2"></i>';
                
                listHtml += `
                    <div class="list-group-item border-0 p-3 d-flex align-items-center gap-3" style="background: transparent; border-bottom: 1px solid rgba(255,255,255,0.05); ${bgItem}">
                        <div class="d-flex flex-column align-items-center justify-content-center rounded-3 bg-dark border border-secondary border-opacity-25 shadow-sm" 
                                style="width: 60px; height: 60px; min-width: 60px;">
                            <span class="h4 mb-0 fw-bold text-white lh-1">${dia}</span>
                            <span class="text-uppercase fw-bold ${esHoy ? 'text-warning' : 'text-secondary'}" style="font-size: 11px; letter-spacing: 0.5px;">${mes.toUpperCase()}</span>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="mb-0 text-white text-truncate fw-bold">${c.nombres || c.nombres_apellidos}</h6>
                                ${badge}
                            </div>
                            <div class="small text-muted text-truncate">
                                ${icon}
                                ${c.nombre_grado || ''} - ${c.nombre_subunidad || ''}
                            </div>
                        </div>
                    </div>
                `;
            });
            
            // Lógica de visualización del Modal
            // Solo mostrar si es la primera visita del día o si hay cumple HOY y no se ha visto recientemente
            let lastSeenKey = 'locador_birthday_seen_' + new Date().toISOString().slice(0,10);
            let alreadySeen = sessionStorage.getItem(lastSeenKey);
            
            if (hayCumpleHoy && !alreadySeen) {
                // Configurar textos dinámicos
                let title = "¡HOY ES UN DÍA ESPECIAL!";
                let subtitle = cumplesHoyCount > 1 
                    ? `Hoy tenemos <strong>${cumplesHoyCount}</strong> locadores de fiesta. ¡Es una celebración múltiple!` 
                    : "Hoy celebramos el día de un compañero. ¡No olvides saludar!";
                
                $('#modalCelebracionTitle').text(title);
                $('#modalCelebracionSubtitle').html(subtitle);
                $('#modalCelebracionList').html(listHtml);
                $('#iconHeader').addClass('fa-cake-candles').removeClass('fa-hourglass-half');
                
                let modalEl = document.getElementById('modalCelebracion');
                var myModal = new bootstrap.Modal(modalEl);
                myModal.show();
                
                // Confeti
                var script = document.createElement('script');
                script.src = "https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js";
                script.onload = function() {
                    var duration = 3000;
                    var end = Date.now() + duration;
                    (function frame() {
                        confetti({ particleCount: 5, angle: 60, spread: 55, origin: { x: 0 } });
                        confetti({ particleCount: 5, angle: 120, spread: 55, origin: { x: 1 } });
                        if (Date.now() < end) requestAnimationFrame(frame);
                    }());
                };
                document.head.appendChild(script);
                
                sessionStorage.setItem(lastSeenKey, 'true');
            }
        }
    }, 'json');
}
</script>


<!-- CSS y Modal para Cumpleaños -->
<style>
    @keyframes bounce-subtle { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-5px); } }
    .animate-bounce { animation: bounce-subtle 2s infinite ease-in-out; }
    @keyframes pulse-glow { 0% { box-shadow: 0 0 0 0 rgba(255, 215, 0, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(255, 215, 0, 0); } 100% { box-shadow: 0 0 0 0 rgba(255, 215, 0, 0); } }
    .animate-pulse { animation: pulse-glow 2s infinite; }
</style>

<div class="modal fade" id="modalCelebracion" tabindex="-1" aria-hidden="true" style="z-index: 10600;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 overflow-hidden" style="background: rgba(20, 20, 25, 0.95); backdrop-filter: blur(20px); box-shadow: 0 0 50px rgba(0,0,0,0.8); border-radius: 24px;">
            
            <!-- Header con Gradiente Dorado -->
            <div class="position-relative p-4 text-center" style="background: linear-gradient(135deg, #FFD700 0%, #B8860B 100%);">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1); opacity: 0.8;"></button>
                
                <div class="mb-2">
                    <i id="iconHeader" class="fa-solid fa-3x text-white drop-shadow-md animate-bounce"></i>
                </div>
                <h3 id="modalCelebracionTitle" class="fw-bold text-white mb-0" style="text-shadow: 0 2px 4px rgba(0,0,0,0.3);"></h3>
                <small id="modalCelebracionSubtitle" class="text-white opacity-90 fw-semibold"></small>
            </div>

            <!-- Cuerpo del Modal -->
            <div class="modal-body p-0">
                <div id="modalCelebracionList" class="list-group list-group-flush" style="max-height: 350px; overflow-y: auto;">
                    <!-- Items dinámicos aquí -->
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?>
