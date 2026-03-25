<?php
// vista/personal_listado.php
require_once __DIR__ . "/../modelo/conexion.php";

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Super Administrador', 'Oficina Personal'])) {
    header("Location: " . (defined('BASE_URL') ? BASE_URL : '/sistem_job/') . "vista/inicio.php");
    exit();
}

include __DIR__ . '/layout/topbar.php';
include __DIR__ . '/layout/sidebar.php';
?>

<!-- ESTILOS CLONADOS DE REGISTRO_USUARIO.PHP -->
<style>
  /* Etiquetas estilo Gestión Documental */
  .form-label {
    font-weight: 700;
    font-size: 14px;
    margin-bottom: 5px;
    color: #333;
    display: block;
  }
  
  /* Inputs Robustos Estilo Gestión Documental */
  .form-control, .form-select {
    min-height: 48px !important;
    border-radius: 8px !important;
    border: 1.5px solid #ced4da !important;
    font-size: 15px !important;
    background-color: #fff !important;
    transition: all 0.2s ease-in-out !important;
    padding: 10px 15px !important;
  }

  .form-control:focus, .form-select:focus {
    border-color: #00607a !important;
    box-shadow: 0 0 0 0.25rem rgba(0, 96, 122, 0.15) !important;
    outline: none !important;
  }

  /* Inputs deshabilitados */
  .form-control:disabled {
    background-color: #f5f5f5 !important;
    cursor: not-allowed !important;
    opacity: 0.7 !important;
  }

  /* LISTA DE BÚSQUEDA PERSONALIZADA */
  .custom-search-list {
    z-index: 10000 !important; /* Encima del modal */
    max-height: 250px;
    overflow-y: auto;
    width: auto; /* Dejar crecer según contenido */
    min-width: 100%; /* Mínimo el ancho del input */
    margin-top: 2px;
    border-radius: 8px;
    border: 1px solid #dee2e6;
    box-shadow: 0 8px 30px rgba(0,0,0,0.15) !important;
    background-color: #fff !important;
    display: none;
    position: absolute;
    left: 0;
    /* right: 0; Eliminamos right:0 para permitir que crezca */
    list-style: none;
    padding-left: 0;
  }

  .custom-search-list .list-group-item {
    padding: 12px 15px;
    cursor: pointer;
    font-size: 14px;
    border-bottom: 1px solid #f1f3f5;
    color: #333;
    white-space: normal; /* Permitir texto en varias lineas si es necesario */
    word-wrap: break-word;
  }

  /* LISTA MAS ANCHA SI EL CONTENIDO LO REQUIERE */
  .custom-search-list.auto-width {
      width: max-content !important;
      min-width: 100%;
      max-width: 450px; /* Limite razonable */
  }

  .custom-search-list .list-group-item:hover {
    background-color: #e7f3ff !important;
    color: #00607a !important;
    font-weight: 600;
  }

  /* FORZAR ESTILOS DE TABLA PREMIUM */
  #tablaPersonal {
      border-collapse: separate !important; /* Necesario para bordes redondeados */
      border-spacing: 0;
      width: 100% !important;
  }

  #tablaPersonal thead th {
      background-color: #00607a !important;
      color: white !important;
      text-transform: uppercase !important;
      font-weight: 600 !important;
      font-size: 0.8rem !important;
      letter-spacing: 0.5px !important;
      border-right: 1px solid rgba(255,255,255,0.15) !important; /* Separador más sutil */
      border-bottom: none !important;
      vertical-align: middle !important;
  }

  /* Bordes redondeados para el encabezado */
  #tablaPersonal thead th:first-child {
      border-top-left-radius: 8px !important;
  }
  #tablaPersonal thead th:last-child {
      border-top-right-radius: 8px !important;
      border-right: none !important;
  }

  /* Hover suave en filas */
  #tablaPersonal tbody tr:hover {
      background-color: rgba(0, 96, 122, 0.05) !important;
  }

  /* TOOLTIP PERSONALIZADO (PREMIUM) */
  .tooltip-inner {
      /* Fondo degradado sutil y atractivo */
      background: linear-gradient(145deg, #ffffff 0%, #f4fcff 100%) !important; 
      color: #2c3e50 !important;
      border: 1px solid #cceeff !important;
      border-left: 5px solid #00607a !important; /* Acento de marca */
      box-shadow: 0 8px 25px rgba(0, 96, 122, 0.25) !important; 
      font-size: 0.9rem !important;
      text-align: left;
      padding: 15px !important;
      max-width: 320px !important;
      opacity: 1 !important;
      border-radius: 6px !important;
  }
  .tooltip.show { opacity: 1 !important; } 
  .tooltip-arrow { display: none !important; } /* Ocultar flecha para estilo tarjeta */
  
  /* SweetAlert Z-Index Fix */
  .swal-top-zindex {
    z-index: 20000 !important;
  }

  /* Icono de Dropdown para Inputs Autocomplete */
  .input-icon-chevron {
      position: absolute;
      right: 25px; /* Ajustado para no chocar con el borde */
      top: 42px;   /* Ajustado según el label y altura del input */
      color: #aaa;
      pointer-events: none; /* Que el clic pase al input */
      font-size: 0.8rem;
  }
</style>

<div class="page-content" style="padding: 20px; padding-top: 80px;">
    <div class="container-fluid">

        <!-- Encabezado -->
        <!-- Encabezado con Buscador Integrado -->
        <div class="d-flex align-items-center mb-4 p-4 rounded shadow-sm bg-white" style="border-left: 5px solid #00607a;">
            <!-- 1. Título -->
            <div class="me-4 flex-shrink-0">
                <h4 class="mb-0 fw-bold" style="color: #00607a;"><i class="fa-solid fa-users-rectangle me-2"></i>Recursos Humanos</h4>
                <small class="text-muted">Gestión del Padrón General de Personal Policial</small>
            </div>

            <!-- 2. Buscador Central y Filtro de Cumpleaños -->
            <div class="flex-grow-1 mx-4 d-flex gap-2">
                <div class="input-group shadow-sm w-100">
                    <span class="input-group-text bg-white border-end-0 text-muted ps-3"><i class="fa-solid fa-search"></i></span>
                    <input type="text" id="filtro_busqueda" class="form-control border-start-0 ps-2 py-2" placeholder="Buscar personal..." style="font-size: 0.95rem;">
                </div>
                
                <!-- Filtro de Mes (Cumpleaños) -->
                <select class="form-select shadow-sm border-0 fw-semibold text-muted" id="filtro_mes_cumple" style="width: 210px; flex-shrink: 0; background-color: #f8f9fa;">
                    <option value="">🎂 Filtrar Cumpleaños</option>
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
            
            <!-- 3. Botones Acciones -->
            <div class="d-flex gap-2 flex-shrink-0">
                <button class="btn btn-primary btn-sm fw-bold px-3 shadow-sm" onclick="abrirModalNuevo()" style="background-color: #00607a; border: none;">
                    <i class="fa-solid fa-plus me-1"></i> Nuevo Efectivo
                </button>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle fw-bold shadow-sm" type="button" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-file-excel me-1"></i> Importación
                    </button>
                    <ul class="dropdown-menu shadow border-0">
                        <li>
                            <a class="dropdown-item py-2" href="../controlador/descargar_plantilla_personal.php">
                                <i class="fa-solid fa-download text-success me-2"></i> Descargar Plantilla
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="#" data-bs-toggle="modal" data-bs-target="#modalImportar">
                                <i class="fa-solid fa-upload text-primary me-2"></i> Importar Excel
                            </a>
                        </li>
                    </ul>
                </div>
                <!-- Reportes -->
                <a href="../controlador/reporte_personal_excel.php" target="_blank" class="btn btn-success btn-sm fw-bold shadow-sm" title="Descargar Reporte Excel">
                    <i class="fa-solid fa-file-excel"></i>
                </a>
                <a href="../controlador/reporte_personal_pdf.php" target="_blank" class="btn btn-danger btn-sm fw-bold shadow-sm" title="Descargar Reporte PDF">
                    <i class="fa-solid fa-file-pdf"></i>
                </a>
                
                <a href="../controlador/generar_manual_personal_pdf.php" target="_blank" class="btn btn-sm fw-bold ms-2" style="color: #00607a; border: 1px solid transparent;" onmouseover="this.style.backgroundColor='#eef7fb'; this.style.borderColor='#00607a';" onmouseout="this.style.backgroundColor='transparent'; this.style.borderColor='transparent';" title="Ver Tutorial de Usuario">
                    <i class="fas fa-book-open text-warning me-1"></i> Tutorial
                </a>
            </div>
        </div>



        <!-- Stats Counters -->
        <!-- Stats Counters (Clickables para filtrar) -->
        <div class="row mb-4">
             <div class="col-md-4">
                 <div class="card border-0 shadow-sm p-3 border-start border-5 border-primary h-100 zoom-hover" style="cursor: pointer;" onclick="filtrarTablaRevista('TODOS')">
                     <div class="d-flex justify-content-between align-items-center">
                         <div>
                             <small class="text-uppercase text-muted fw-bold">Total Efectivos</small>
                             <h3 class="mb-0 fw-bold text-primary" id="statTotal">...</h3>
                         </div>
                         <i class="fa-solid fa-users fa-2x text-black-50 opacity-25"></i>
                     </div>
                 </div>
             </div>
             <div class="col-md-4">
                 <div class="card border-0 shadow-sm p-3 border-start border-5 border-success h-100 zoom-hover" style="cursor: pointer;" onclick="filtrarTablaRevista('PASADOS')">
                     <div class="d-flex justify-content-between align-items-center">
                         <div>
                             <small class="text-uppercase text-muted fw-bold">Revista <?php echo strtoupper(['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'][date('n')-1]); ?></small>
                             <h3 class="mb-0 fw-bold text-success" id="statRevistaOK">...</h3>
                         </div>
                         <i class="fa-solid fa-check-circle fa-2x text-black-50 opacity-25"></i>
                     </div>
                 </div>
             </div>
             <div class="col-md-4">
                 <div class="card border-0 shadow-sm p-3 border-start border-5 border-danger h-100 zoom-hover" style="cursor: pointer;" onclick="filtrarTablaRevista('PENDIENTES')">
                     <div class="d-flex justify-content-between align-items-center">
                         <div>
                             <small class="text-uppercase text-muted fw-bold">Pendientes Revista</small>
                             <h3 class="mb-0 fw-bold text-danger" id="statPendientes">...</h3>
                         </div>
                         <i class="fa-solid fa-clock fa-2x text-black-50 opacity-25"></i>
                     </div>
                 </div>
             </div>
        </div>

        <style>
            .zoom-hover { transition: transform 0.2s; }
            .zoom-hover:hover { transform: translateY(-5px); background-color: #f8f9fa; }
        </style>

        <!-- Tabla -->
        <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="tablaPersonal" class="table table-hover align-middle mb-0" style="width:100%; font-size: 0.85rem;">
                        <thead class="text-white text-uppercase" style="background-color: #00607a;">
                                <th class="py-3 ps-3 text-center" style="width: 50px;">N°</th>
                                <th class="py-3">Grado</th>
                                <th class="py-3">Apellidos y Nombres</th>
                                <th class="py-3">DNI</th>
                                <!-- <th class="py-3">Tipo DOC</th> Ocultado y movido a Tooltip DNI -->
                                <th class="py-3">Sit. Especial</th>
                                <th class="py-3">CIP</th>
                                <th class="py-3">División</th>
                                <th class="py-3">Unidad / Sub-Unidad</th>
                                <th class="py-3">Cargo</th>
                                <!-- <th class="py-3">Sit. CIP</th> Ocultado -->
                                <th class="py-3">Función</th>
                                <th class="py-3 text-center">Revista</th>
                                <th class="py-3 text-center">Estado</th>
                                <th class="py-3 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL XL -->
<div class="modal fade" id="modalNuevoPersonal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0" style="border-radius: 12px;">
            <div class="modal-header text-white" style="background-color: #00607a; border-top-left-radius: 12px; border-top-right-radius: 12px;">
                <h5 class="modal-title fw-bold" id="modalTitulo"><i class="fa-solid fa-user-plus me-2"></i>Gestión de Efectivo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light p-4">
                <form id="formPersonal">
                    <input type="hidden" name="id_personal" id="id_personal">

                    <!-- SECCIÓN 1 -->
                    <h5 class="text-dark fw-bold mb-4 border-bottom pb-2">1. Datos Personales</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4 position-relative">
                            <label class="form-label">Grado Actual</label>
                            <input type="text" id="input_grado" class="form-control" placeholder="Buscar grado..." autocomplete="off">
                            <input type="hidden" name="id_grado" id="id_grado_hidden"> 
                            <ul id="lista_grados" class="custom-search-list shadow"></ul>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">CIP</label>
                            <input type="text" class="form-control" name="cip" id="cip" required maxlength="15">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Situación CIP</label>
                            <input type="text" class="form-control" name="situacion_cip" id="situacion_cip" placeholder="Ej: Actividad / Retiro">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Tipo DNI</label>
                            <select class="form-select" name="tipo_dni" id="tipo_dni">
                                <option value="DNI">ELECTRÓNICO</option>
                                <option value="CE">CARNET EXTRANJERÍA</option>
                                <option value="PAS">PASAPORTE</option>
                                <option value="OTRO">OTRO</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Num. Doc (DNI)</label>
                            <input type="text" class="form-control" name="dni" id="dni" maxlength="15">
                        </div>
                        <div class="col-md-4">
                             <label class="form-label">Sexo</label>
                             <select class="form-select" name="sexo" id="sexo">
                                <option value="M">Masculino</option>
                                <option value="F">Femenino</option>
                             </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Apellidos y Nombres Completos</label>
                            <input type="text" class="form-control text-uppercase" name="apellidos_nombres" id="apellidos_nombres" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Cumpleaños</label>
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
                        </div>
                    </div>

                    <!-- SECCIÓN 2: UBICACIÓN (CASCADA SERVIDOR) -->
                    <h5 class="text-dark fw-bold mb-4 border-bottom pb-2">
                        <i class="fas fa-shield-alt text-success me-2"></i> 2. Asignación de Unidad (Jerárquica)
                    </h5>
                    
                    <div class="row g-3">
                        <!-- REGIÓN -->
                        <div class="col-md-4 position-relative">
                            <label class="form-label text-secondary">1. Región Policial</label>
                            <input type="text" id="input_region" class="form-control" placeholder="Haga clic para ver regiones..." autocomplete="off">
                            <input type="hidden" id="id_region_hidden">
                            <ul id="lista_regiones" class="custom-search-list shadow"></ul>
                        </div>

                        <!-- DIVISIÓN -->
                        <div class="col-md-4 position-relative">
                            <label class="form-label text-secondary">2. División Policial</label>
                            <input type="text" id="input_division" class="form-control" placeholder="Escriba para buscar..." autocomplete="off" disabled>
                            <input type="hidden" id="id_division_hidden">
                            <ul id="lista_divisiones" class="custom-search-list shadow"></ul>
                        </div>

                        <!-- SUB-UNIDAD -->
                        <div class="col-md-4 position-relative">
                            <label class="form-label text-secondary">3. Sub-Unidad / Comisaría</label>
                            <input type="text" id="input_subunidad" class="form-control" placeholder="Escriba para buscar..." autocomplete="off" disabled>
                            <input type="hidden" name="id_subunidad" id="id_subunidad_hidden" required> 
                            <ul id="lista_subunidades" class="custom-search-list shadow"></ul>
                        </div>


                    </div>

                    <!-- SECCIÓN 3: DATOS COMPLEMENTARIOS -->
                    <h5 class="text-dark fw-bold mb-3 mt-4 border-bottom pb-2">
                        <i class="fas fa-briefcase text-primary me-2"></i> 3. Información Laboral
                    </h5>
                    <div class="alert alert-info border-0 bg-info bg-opacity-10 py-2 mb-4">
                        <small class="text-info-emphasis"><i class="fa-solid fa-keyboard me-2"></i><b>Nota:</b> Puede seleccionar una opción de la lista o <u>escribir manualmente</u> su propio texto si no existe.</small>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4 position-relative">
                            <label class="form-label">Cargo Actual</label>
                            <input type="text" class="form-control text-uppercase" name="cargo" id="cargo" placeholder="Ej: JEFE DE UNIDAD" autocomplete="off">
                            <i class="fa-solid fa-chevron-down input-icon-chevron"></i>
                            <ul id="lista_cargos" class="custom-search-list shadow auto-width"></ul>
                        </div>
                        <div class="col-md-4 position-relative">
                            <label class="form-label">Situación Especial</label>
                            <input type="text" class="form-control text-uppercase" name="situacion_especial" id="situacion_especial" placeholder="Ej: DE SERVICIO / COMISIÓN" autocomplete="off">
                            <i class="fa-solid fa-chevron-down input-icon-chevron"></i>
                            <ul id="lista_situaciones" class="custom-search-list shadow auto-width"></ul>
                        </div>
                        <div class="col-md-4 position-relative">
                            <label class="form-label">Función Policial / Modalidad Horario</label>
                            <input type="text" class="form-control text-uppercase" name="funcion_horario" id="funcion_horario" placeholder="Ej: ADMINISTRATIVO - 8X8" autocomplete="off">
                            <i class="fa-solid fa-chevron-down input-icon-chevron"></i>
                            <ul id="lista_funciones" class="custom-search-list shadow auto-width"></ul>
                        </div>
                    </div>

                </form>
            </div>
            <div class="modal-footer bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary fw-bold px-4" onclick="guardarPersonal()">
                    <i class="fa-solid fa-save me-1"></i> Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Importar -->
<div class="modal fade" id="modalImportar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Carga Masiva</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formImportar">
                    <div class="mb-3">
                         <label class="form-label">Archivo Excel (.xlsx)</label>
                         <input type="file" name="archivo" class="form-control" accept=".xlsx" required>
                    </div>
                    
                    <div class="p-3 mb-3 bg-danger bg-opacity-10 border border-danger rounded transition-hover">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="chkLimpiar" style="cursor: pointer;">
                            <label class="form-check-label fw-bold text-danger" for="chkLimpiar" style="cursor: pointer;">
                                <i class="fa-solid fa-trash-can me-2"></i>Limpiar Listado de Personal
                            </label>
                        </div>
                        <div class="small text-muted mt-2 ms-1" style="line-height: 1.4;">
                            <i class="fa-solid fa-triangle-exclamation text-warning me-1"></i>
                            <strong>¡Atención!</strong> Si activa esta opción, se eliminarán <u>todos</u> los registros del personal actual y se reiniciará la tabla.
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success w-100" onclick="ejecutarImportacion()">Procesar</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL VER PERSONAL (LEGAJO DIGITAL DIGITAL) -->
<div class="modal fade" id="modalVerPersonal" tabindex="-1" style="z-index: 9999 !important;">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            
            <!-- Encabezado con Fondo Institucional -->
            <div class="modal-header border-0 p-4" style="background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%); color: white;">
                <div class="d-flex align-items-center w-100">
                    <!-- Placeholder FOTO -->
                    <div class="bg-white rounded-circle d-flex justify-content-center align-items-center shadow-lg border border-3 border-white me-4" 
                         style="width: 80px; height: 80px; min-width: 80px; font-size: 2.5rem; color: #2c5364;">
                        <i class="fa-solid fa-user-tie"></i>
                    </div>
                    
                    <div class="flex-grow-1">
                        <small class="text-info fw-bold text-uppercase ls-1">Legajo Personal Policial</small>
                        <h3 id="lbl_nombre" class="fw-bold mb-1 text-uppercase text-white" style="letter-spacing: 0.5px;">-</h3>
                        <div class="d-flex align-items-center gap-3 opacity-75">
                            <span id="lbl_grado" class="badge bg-light text-dark fw-bold border">-</span>
                            <span class="small"><i class="fa-solid fa-briefcase me-1"></i> <span id="lbl_cargo">-</span></span>
                        </div>
                    </div>
                    
                    <button type="button" class="btn-close btn-close-white align-self-start" data-bs-dismiss="modal"></button>
                </div>
            </div>
            
            <div class="modal-body bg-light p-4">
                <div class="row g-4">
                    
                    <!-- COLUMNA IZQUIERDA: DATOS CLAVE -->
                    <div class="col-md-5">
                        <!-- Tarjeta CIP / DNI -->
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body p-3">
                                <h6 class="fw-bold text-secondary mb-3 border-bottom pb-2">Identificación</h6>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="small text-muted fw-bold">N° C.I.P.</label>
                                        <div id="lbl_cip" class="fs-5 fw-bold text-dark">-</div>
                                        <small id="lbl_sit_cip" class="text-success fw-bold" style="font-size: 0.7rem;">-</small>
                                    </div>
                                    <div class="col-6 border-start ps-3">
                                        <label class="small text-muted fw-bold">DNI</label>
                                        <div id="lbl_dni" class="fs-5 fw-bold text-dark">-</div>
                                        <small id="lbl_tipo_doc" class="text-muted" style="font-size: 0.7rem;">-</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Estado y Situación -->
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-3">
                                <h6 class="fw-bold text-secondary mb-3 border-bottom pb-2">Situación Actual</h6>
                                <div class="mb-2">
                                    <label class="d-block small text-muted">Condición Laboral</label>
                                    <div id="lbl_estado" class="mb-1">-</div>
                                </div>
                                <div class="mb-2">
                                    <label class="d-block small text-muted">Situación Especial</label>
                                    <span id="lbl_sit_especial" class="fw-bold text-dark">-</span>
                                </div>
                                <div>
                                    <label class="d-block small text-muted">Régimen Horario</label>
                                    <span id="lbl_horario" class="badge bg-secondary bg-opacity-10 text-dark border">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- COLUMNA DERECHA: ASIGNACIÓN -->
                    <div class="col-md-7">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center mb-3 text-secondary">
                                    <i class="fa-solid fa-building-user fs-4 me-2"></i>
                                    <h6 class="fw-bold mb-0">Unidad de Asignación</h6>
                                </div>
                                
                                <div class="timeline-simple ms-2 border-start border-2 ps-4 py-2 position-relative">
                                    
                                    <!-- Region -->
                                    <div class="mb-4 position-relative">
                                        <div class="position-absolute bg-primary rounded-circle" style="width: 12px; height: 12px; left: -31px; top: 5px;"></div>
                                        <label class="small text-muted fw-bold d-block text-uppercase">Región Policial</label>
                                        <span id="lbl_region" class="fs-6 fw-normal text-dark">-</span>
                                    </div>

                                    <!-- Division -->
                                    <div class="mb-4 position-relative">
                                        <div class="position-absolute bg-info rounded-circle" style="width: 12px; height: 12px; left: -31px; top: 5px;"></div>
                                        <label class="small text-muted fw-bold d-block text-uppercase">División / Jefatura</label>
                                        <span id="lbl_division" class="fs-6 fw-normal text-dark">-</span>
                                    </div>

                                    <!-- Subunidad -->
                                    <div class="position-relative">
                                        <div class="position-absolute bg-success rounded-circle" style="width: 12px; height: 12px; left: -31px; top: 5px;"></div>
                                        <label class="small text-muted fw-bold d-block text-uppercase">Sub-Unidad Actual</label>
                                        <span id="lbl_subunidad" class="fs-5 fw-bold text-success">-</span>
                                    </div>

                                </div>
                                
                                <div class="mt-4 pt-3 border-top">
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <small class="text-muted d-block">Sexo</small>
                                            <strong id="lbl_sexo" class="text-dark">-</strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Fecha Nac.</small>
                                            <strong id="lbl_nacimiento" class="text-dark">-</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Footer con Acciones de REVISTA -->
            <div class="modal-footer bg-light d-block p-3 border-top">
                <div class="row align-items-center mb-2">
                    <div class="col-8">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="small fw-bold text-secondary text-uppercase mb-0">
                                <i class="fa-solid fa-calendar-check me-1"></i> PASE DE REVISTA: <span id="lblPeriodoRevista" class="text-primary fw-bolder">MES ACTUAL</span>
                            </label>
                        </div>
                        
                        <!-- Alerta de Verificación Física -->
                        <div class="alert alert-warning py-2 px-3 mb-3 d-flex align-items-center shadow-sm" style="border-left: 4px solid #f59e0b;">
                            <i class="fa-solid fa-id-card fa-lg me-3 text-dark"></i>
                            <div style="line-height: 1.2;">
                                <strong class="d-block text-dark" style="font-size: 0.8rem;">VERIFICACIÓN FÍSICA OBLIGATORIA</strong>
                                <small class="text-muted" style="font-size: 0.75rem;">El efectivo debe mostrar físicamente su <strong class="text-dark">CIP</strong> y <strong class="text-dark">DNI</strong> para ser considerado "Presente".</small>
                            </div>
                        </div>
                        
                        <!-- Checkboxes de Documentacion Fïsica -->
                        <div class="d-flex gap-3 mb-3 bg-white p-2 border rounded">
                            <div class="form-check form-switch ps-5">
                                <input class="form-check-input" type="checkbox" id="chkPortaCIP" checked style="cursor: pointer; transform: scale(1.2);">
                                <label class="form-check-label small fw-bold ms-2 mt-1" for="chkPortaCIP">Porta Carnet (CIP)</label>
                            </div>
                            <div class="form-check form-switch ps-5 border-start">
                                <input class="form-check-input" type="checkbox" id="chkPortaDNI" checked style="cursor: pointer; transform: scale(1.2);">
                                <label class="form-check-label small fw-bold ms-2 mt-1" for="chkPortaDNI">Porta DNI</label>
                            </div>
                        </div>

                        <div class="btn-group w-100 shadow-sm" role="group" id="btnGroupAcciones">
                            <button type="button" class="btn btn-outline-success fw-bold" onclick="prepararNovedad('PRESENTE')"><i class="fa-solid fa-check me-1"></i> PRESENTE</button>
                            <button type="button" class="btn btn-outline-warning fw-bold text-dark" onclick="prepararNovedad('PERMISO')"><i class="fa-solid fa-clock me-1"></i> PERMISO</button>
                            <button type="button" class="btn btn-outline-info fw-bold text-dark" onclick="prepararNovedad('COMISION')"><i class="fa-solid fa-briefcase me-1"></i> COMISIÓN</button>
                            <button type="button" class="btn btn-outline-danger fw-bold" onclick="prepararNovedad('AUSENTE')"><i class="fa-solid fa-user-xmark me-1"></i> AUSENTE</button>
                        </div>

                        <!-- PANEL OCULTO PARA DETALLE -->
                        <div id="divDetalleNovedad" style="display: none;" class="mt-2 p-2 bg-white border rounded shadow-sm">
                            <label class="small fw-bold text-primary mb-1" id="lblDetalleTitulo">Detalle:</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light fw-bold" id="lblTipoNovedad">?</span>
                                <input type="text" id="txtObservacionNovedad" class="form-control" placeholder="Especifique motivo o lugar...">
                                <button class="btn btn-success" type="button" onclick="confirmarNovedad()"><i class="fa-solid fa-save"></i> Guardar</button>
                                <button class="btn btn-secondary" type="button" onclick="cancelarNovedad()"><i class="fa-solid fa-times"></i></button>
                            </div>
                        </div>

                    </div>
                     <div class="col-4 text-end align-self-start">
                        <button type="button" class="btn btn-secondary px-4 rounded-pill mt-4" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>

                <!-- Historial Rápido -->
                <div class="mt-3">
                    <small class="text-muted fw-bold d-block mb-1">Últimos Registros:</small>
                    <div class="table-responsive border rounded bg-white" style="max-height: 200px;">
                        <table class="table table-sm table-striped mb-0 small" id="tablaHistorialRapido">
                            <thead class="bg-light sticky-top">
                                <tr><th>Fecha</th><th>Estado</th><th>Obs</th><th></th></tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="4" class="text-center text-muted">- Sin historial reciente -</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?>

<!-- LIBRERÍAS -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
let tablaPersonal;
let dataGrados = []; // Cache simple para grados

$(document).ready(function() {
    cargarTabla();
    
    // Carga inicial: Grados y Regiones
    cargarGradosYRegiones();

    $('#filtro_busqueda').on('keyup', function() { tablaPersonal.search(this.value).draw(); });

    $('#filtro_busqueda').on('keyup', function() { tablaPersonal.search(this.value).draw(); });
    
    $('#filtro_busqueda').on('keyup', function() { tablaPersonal.search(this.value).draw(); });
    
    // 1. DEFINIR DATOS POR DEFECTO (Para que funcione INMEDIATAMENTE)
    let cargosData = ["JEFE DE UNIDAD", "LOGÍSTICO", "SECRETARIO", "MESA DE PARTES"];
    let sitData = ["NORMAL", "COMISIÓN", "VACACIONES", "PERMISO"];
    let funcData = ["ADMINISTRATIVO - EXCLUSIVIDAD", "ADMINISTRATIVO: TURNO MAÑANA", "ADMINISTRATIVO - 24 X 24"];

    // 2. INICIALIZAR INMEDIATAMENTE (Sin esperar al servidor)
    initFreeSearch('cargo', 'lista_cargos', cargosData);
    initFreeSearch('situacion_especial', 'lista_situaciones', sitData);
    initFreeSearch('funcion_horario', 'lista_funciones', funcData);

    // 3. ACTUALIZAR CON DATOS DE BD (Segundo plano)
    $.get('../controlador/PersonalControlador.php?op=listar_parametros', function(resp) {
        try {
            let data = JSON.parse(resp);
            
            // Fusionar datos (Prioridad: BD, pero manteniendo los defaults si no existen)
            // Usamos Set para evitar duplicados
            if(data.cargos && data.cargos.length > 0) cargosData = [...new Set([...cargosData, ...data.cargos])];
            if(data.situaciones && data.situaciones.length > 0) sitData = [...new Set([...sitData, ...data.situaciones])];
            if(data.funciones && data.funciones.length > 0) funcData = [...new Set([...funcData, ...data.funciones])];

            // Re-inicializar con la data completa
            initFreeSearch('cargo', 'lista_cargos', cargosData);
            initFreeSearch('situacion_especial', 'lista_situaciones', sitData);
            initFreeSearch('funcion_horario', 'lista_funciones', funcData);

        } catch(e) { console.error("Error cargando predicciones", e); }
    });

    // CERRAR LISTAS AL CLIC FUERA
    $(document).on('mousedown', function(e) {
        if (!$('.position-relative').has(e.target).length) {
            $('.custom-search-list').hide();
        }
    });
});

// --- BUSCADOR ESTRICTO Y ROBUSTO ---
function initCustomSearch(inputId, listId, hiddenId, dataFn, displayKey, idKey, callback) {
    const $input = $('#' + inputId);
    const $list = $('#' + listId);
    const $hidden = $('#' + hiddenId);
    
    const render = (filtro = '') => {
        const val = filtro.toUpperCase().trim();
        const data = dataFn(); 
        $list.empty();

        if(!data || data.length === 0) return $list.hide();

        const filtrados = data.filter(item => (item[displayKey] || '').toUpperCase().includes(val));
        
        if(filtrados.length > 0) {
            filtrados.forEach(item => {
                const $li = $('<li class="list-group-item"></li>').text(item[displayKey]);
                $li.on('mousedown', function(e) {
                    e.preventDefault();
                    seleccionarItem(item);
                });
                $list.append($li);
            });
            $list.show();
        } else {
            $list.hide();
        }
    };

    const seleccionarItem = (item) => {
        $input.val(item[displayKey]);
        $hidden.val(item[idKey]); 
        $list.hide();
        $input.addClass('is-valid').removeClass('is-invalid');
        setTimeout(() => $input.removeClass('is-valid'), 1000);
        if(callback) callback(item[idKey]);
    };

    $input.on('click focus', function() { this.select(); render(''); });

    $input.on('input', function() { 
        $hidden.val(''); 
        $input.removeClass('is-valid');
        render($(this).val()); 
    });

    $input.on('blur', function() {
        setTimeout(() => {
            $list.hide();
            if ($hidden.val()) return;

            const textoActual = $input.val().toUpperCase().trim();
            if (textoActual === '') return; 

            const data = dataFn();
            const matchExacto = data.find(item => item[displayKey].toUpperCase() === textoActual);

            if (matchExacto) {
                seleccionarItem(matchExacto);
            } else {
                $input.val(''); 
                $hidden.val(''); 
                $input.addClass('is-invalid');
                setTimeout(() => $input.removeClass('is-invalid'), 500);
                if(callback) callback(null); 
            }
        }, 200);
    });
}


// --- BUSCADOR FLEXIBLE (TEXTO LIBRE CON SUGERENCIAS) ---
function initFreeSearch(inputId, listId, dataArray) {
    const $input = $('#' + inputId);
    const $list = $('#' + listId);

    // Limpiar eventos previos para permitir re-inicialización segura
    $input.off('focus click input blur');

    const render = (filtro = '') => {
        const val = filtro.toUpperCase().trim();
        $list.empty();
        
        // Si está vacío, mostrar top 10 sugerencias por defecto
        let filtrados = [];
        if(val === '') {
             filtrados = dataArray.slice(0, 10);
        } else {
             filtrados = dataArray.filter(txt => txt.toUpperCase().includes(val));
        }

        if(filtrados.length > 0) {
            filtrados.forEach(txt => {
                const $li = $('<li class="list-group-item"></li>').text(txt);
                $li.on('mousedown', function(e) {
                    e.preventDefault(); // Evitar blur
                    $input.val(txt);
                    $list.hide();
                });
                $list.append($li);
            });
            $list.show();
        } else {
            $list.hide();
        }
    };

    // Eventos: Focus y Click para abrir siempre la lista
    $input.on('focus click', function() { render($(this).val()); });
    $input.on('input', function() { render($(this).val()); });
    $input.on('blur', function() { setTimeout(() => $list.hide(), 200); });
}

// --- LÓGICA DE CARGA (SERVIDOR) ---
function cargarGradosYRegiones() {
    // 1. Grados
    $.get('../controlador/PersonalControlador.php?op=listar_grados', function(resp) {
        if(resp) {
             dataGrados = JSON.parse(resp).data;
             initCustomSearch('input_grado', 'lista_grados', 'id_grado_hidden', () => dataGrados, 'nombre', 'id');
        }
    });

    // 2. Regiones (Usando get_unidades_ajax.php)
    $.get('../controlador/get_unidades_ajax.php?action=get_regiones', function(data) {
        // CORRECCIÓN: Las claves deben coincidir con la BD (nombre_region, id_region)
        initCustomSearch('input_region', 'lista_regiones', 'id_region_hidden', () => data, 'nombre_region', 'id_region', function(idReg) {
            
            // Al cambiar Region:
            resetInput('#input_division', '#id_division_hidden', 'Cargando...');
            resetInput('#input_subunidad', '#id_subunidad_hidden', 'Esperando división...');

            if(idReg) {
                // Pedir Divisiones al servidor filtradas por Region
                $.get('../controlador/get_unidades_ajax.php', { action: 'get_divisiones', id_region: idReg }, function(divs) {
                    if(divs && divs.length > 0) {
                        $('#input_division').prop('disabled', false).attr('placeholder', 'Buscar división...');
                        initCustomSearch('input_division', 'lista_divisiones', 'id_division_hidden', () => divs, 'nombre_division', 'id_division', function(idDiv) {
                            
                            // Al cambiar Division:
                            resetInput('#input_subunidad', '#id_subunidad_hidden', 'Cargando...');
                            
                            if(idDiv) {
                                // Pedir Subunidades al servidor filtradas por Division
                                $.get('../controlador/get_unidades_ajax.php', { action: 'get_subunidades', id_division: idDiv }, function(subs) {
                                    $('#input_subunidad').prop('disabled', false).attr('placeholder', 'Buscar sub-unidad...');
                                    initCustomSearch('input_subunidad', 'lista_subunidades', 'id_subunidad_hidden', () => subs, 'nombre_subunidad', 'id_subunidad');
                                }, 'json'); 
                            }
                        });
                    } else {
                        $('#input_division').attr('placeholder', 'Sin divisiones en esta región');
                    }
                }, 'json');
            }
        });
    }, 'json');
}

function resetInput(inputSel, hiddenSel, placeholder) {
    $(inputSel).val('').prop('disabled', true).attr('placeholder', placeholder).removeClass('is-valid is-invalid');
    $(hiddenSel).val('');
}

// --- EDICIÓN ---
function abrirModalNuevo() {
    $('#formPersonal')[0].reset();
    $('#id_personal').val('');
    $('#modalTitulo').html('<i class="fa-solid fa-user-plus me-2"></i>Nuevo Efectivo');
    $('input[type=hidden]').val('');
    resetInput('#input_division', '#id_division_hidden', 'Esperando región...');
    resetInput('#input_subunidad', '#id_subunidad_hidden', 'Esperando división...');
    $('#input_region').val('').prop('disabled', false).attr('placeholder', 'Haga clic para ver regiones...');
    $('#input_grado').val('');
    $('#dia_nac').val('');
    $('#mes_nac').val('');
    $('#input_grado').val('');
    $('#modalNuevoPersonal').modal('show');
}

function eliminarPersonal(id) {
    Swal.fire({
        title: '¿Dar de baja?',
        text: "El personal pasará a estado 'Baja' y no será visible en listas activas.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, dar de baja',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('../controlador/PersonalControlador.php?op=eliminar', { id: id }, function(r) {
                let resp = JSON.parse(r);
                if(resp.status) {
                    Swal.fire('Baja', resp.msg, 'success');
                    tablaPersonal.ajax.reload(null, false);
                } else {
                    Swal.fire('Error', resp.msg, 'error');
                }
            });
        }
    })
}

function editarPersonal(id) {
    abrirModalNuevo();
    $('#modalTitulo').html('<i class="fa-solid fa-pen-to-square me-2"></i>Editar Efectivo');
    $('#id_personal').val(id);

    $.get('../controlador/PersonalControlador.php?op=obtener&id=' + id, function(resp) {
        try {
            // Corrección CRÍTICA: jQuery ya parsea el JSON si el header es correcto.
            // Si intentamos parsear un objeto, falla.
            let data = (typeof resp === 'object') ? resp : JSON.parse(resp);
            
            if(data.error) {
                Swal.fire('Error', 'No se pudo cargar la data: ' + data.error, 'error');
                return;
            }

            if(data) {
                $('#cip').val(data.cip); // Linea unica
                $('#dni').val(data.dni || '');
                // Concatenar apellidos y nombres
                let full = (data.apellidos || '') + ' ' + (data.nombres || '');
                $('#apellidos_nombres').val(full.trim());
            
            $('#sexo').val(data.sexo || 'M');
            
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
            
            // Nuevos Campos
            $('#tipo_dni').val(data.tipo_dni || 'DNI');
            $('#situacion_cip').val(data.situacion_cip);
            $('#cargo').val(data.cargo);
            $('#situacion_especial').val(data.situacion_especial);
            $('#funcion_horario').val(data.funcion_horario);

            $('#id_grado_hidden').val(data.id_grado);
            let grado = dataGrados.find(g => g.id == data.id_grado);
            if(grado) $('#input_grado').val(grado.nombre);
 
            // ASIGNACIÓN DE UNIDAD (Recuperar Jerarquía)
            // 1. Región
            if(data.nombre_region) {
                $('#input_region').val(data.nombre_region);
                $('#id_region_hidden').val(data.id_region);
            } else {
                $('#input_region').val(''); 
                $('#id_region_hidden').val('');
            }

            // 2. División (Depende de región, pero si ya tenemos data la mostramos)
            if(data.nombre_division) {
                $('#input_division').val(data.nombre_division).prop('disabled', false); // Habilitar
                $('#id_division_hidden').val(data.id_division);
            } else {
                $('#input_division').val('').prop('disabled', true);
                $('#id_division_hidden').val('');
            }

            // 3. SubUnidad (La que se guarda en BD)
            if(data.nombre_subunidad) {
                $('#input_subunidad').val(data.nombre_subunidad).prop('disabled', false); // Habilitar
                $('#id_subunidad_hidden').val(data.id_subunidad);
            } else {
                $('#input_subunidad').val('').prop('disabled', true);
                $('#id_subunidad_hidden').val('');
            }
        }
    } catch(e) {
        console.error(e);
        Swal.fire('Error', 'Error al procesar datos del servidor.', 'error');
    }
    });
}

function cargarTabla() {
    // Definir la fuente de datos con parámetros dinámicos
    tablaPersonal = $('#tablaPersonal').DataTable({
        ajax: {
            url: '../controlador/PersonalControlador.php?op=listar',
            data: function(d) {
                d.mes = $('#filtro_mes_cumple').val();
            }
        },
        columns: [
            { data: null, className: 'text-center fw-bold text-secondary', render: (d,t,r,m) => m.row + 1 },
            { data: 'nombre_grado', className: 'small', render: d => d || '-' },
            { data: null, render: r => `<div>${r.apellidos}</div><div class="small text-muted">${r.nombres}</div>` },
            { 
                data: 'dni',
                render: (d, t, r) => {
                    // SI HAY FILTRO DE CUMPLEAÑOS ACTIVO, MOSTRAR LA FECHA
                    let mesFiltro = $('#filtro_mes_cumple').val();
                    if(mesFiltro) {
                        if(!r.fecha_nacimiento) return '-';
                        let partes = r.fecha_nacimiento.split('-'); // YYYY-MM-DD
                        let dia = partes[2];
                        
                        // Verificar si es HOY
                        let hoy = new Date();
                        let esHoy = (parseInt(dia) === hoy.getDate() && parseInt(mesFiltro) === (hoy.getMonth() + 1));
                        
                        let badge = esHoy ? '<i class="fa-solid fa-cake-candles text-warning ms-1 animate-bounce"></i>' : '';
                        let colorDia = esHoy ? 'text-danger fw-bold' : 'text-primary fw-bold';
                        
                        return `<div class="${colorDia}" style="font-size: 1.1rem;">${dia} <small class="text-secondary" style="font-size: 0.7rem;">/ ${mesFiltro}</small>${badge}</div>
                                <div class="small text-muted">${d}</div>`;
                    }

                    // TOOLTIP DNI NORMAL
                    const info = `<div class='mb-0'><b>TIPO DOC.</b> ${r.tipo_dni || '-'}</div>`;
                    return `<span style="cursor: help;" data-bs-toggle="tooltip" data-bs-html="true" title="${info}">${d}</span>`;
                }
            },
            // { data: 'tipo_dni', className: 'small text-muted', render: d => d || '' },
            { data: 'situacion_especial', className: 'small text-muted', render: d => d || '-' },
            { 
                data: 'cip', 
                className: 'text-center',
                render: (d, t, r) => {
                     // TOOLTIP CIP (Sit. CIP)
                    const info = `
                        <div><b>SIT. CIP:</b> ${r.situacion_cip || '-'}</div>
                    `;
                    return `<span style="cursor: help;" data-bs-toggle="tooltip" data-bs-html="true" title="${info}">${d}</span>`; 
                }
            }, 
            { data: 'nombre_division', className: 'small', render: d => d || '-' },
            { 
                data: 'nombre_subunidad', 
                className: 'small', 
                render: (d, t, r) => {
                    if(!d) return '<span class="badge bg-warning text-dark">Sin Asignar</span>';
                    
                    // TOOLTIP JERARQUÍA COMPLETA
                    const jerarquia = `
                        <div class='mb-1'><b>REGIÓN:</b> ${r.nombre_region || '-'}</div>
                        <div class='mb-1'><b>DIVISIÓN:</b> ${r.nombre_division || '-'}</div>
                        <div><b>SUB-UNIDAD:</b> ${d}</div>
                    `;
                    return `<span style="cursor:help" data-bs-toggle="tooltip" data-bs-html="true" title="${jerarquia}">${d}</span>`;
                }
            },
            { 
                data: 'cargo', 
                className: 'small text-center', 
                render: d => {
                     const val = (d || '').toUpperCase().trim();
                     if(!val || val === 'POR DEFINIR' || val === '-') {
                         // Estilo tipo "Badge de Alerta Suave" para indicar que falta definir
                         return '<span class="badge bg-warning bg-opacity-25 text-body-emphasis border border-warning border-opacity-50 rounded-pill ls-1" style="font-size: 0.7rem;"><i class="fa-solid fa-circle-question me-1"></i>POR DEFINIR</span>';
                     }
                     return '<span class="text-secondary fw-bold">' + d + '</span>';
                }
            },
            // { data: 'situacion_cip', className: 'small text-muted', render: d => d || '-' },
            { data: 'funcion_horario', className: 'small text-muted', render: d => d || '-' },
            { 
                data: 'estado_revista', 
                className: 'text-center', 
                render: (d, type, r) => {
                    if(d === 'PRESENTE') {
                        // Verificar si tiene observaciones "graves" (empezando con FALTA, o cualquier obs para PRESENTE)
                        if(r.observacion_revista && r.observacion_revista.trim().length > 0) {
                             return `<span class="badge bg-warning text-dark rounded-pill px-3" data-bs-toggle="tooltip" title="${r.observacion_revista}"><i class="fa-solid fa-triangle-exclamation me-1"></i>PASÓ (OBS)</span>`;
                        }
                        return '<span class="badge bg-success rounded-pill px-3"><i class="fa-solid fa-check me-1"></i>PASÓ</span>';
                    }
                    if(d === 'PERMISO') return `<span class="badge bg-warning text-dark rounded-pill px-3" data-bs-toggle="tooltip" title="${r.observacion_revista||''}">PERMISO</span>`;
                    if(d === 'AUSENTE') return `<span class="badge bg-danger rounded-pill px-3" data-bs-toggle="tooltip" title="${r.observacion_revista||''}">AUSENTE</span>`;
                    if(d === 'COMISIÓN') return `<span class="badge bg-info text-dark rounded-pill px-3" data-bs-toggle="tooltip" title="${r.observacion_revista||''}">COMISIÓN</span>`;
                    return '<span class="badge bg-light text-secondary border rounded-pill px-3"><i class="fa-regular fa-clock me-1"></i>PENDIENTE</span>';
                } 
            },
            { data: 'estado', className: 'text-center', render: d => d==='Activo'?'<span class="badge bg-success">Activo</span>':'<span class="badge bg-danger">Baja</span>' },
            { data: null, className: 'text-center', render: r => `
                <div class="text-nowrap">
                    <button class="btn btn-sm btn-light text-info border-0 shadow-sm me-1" onclick="verPersonal(${r.id_personal})" title="Ver Ficha"><i class="fa-solid fa-eye"></i></button>
                    <button class="btn btn-sm btn-light text-primary border-0 shadow-sm me-1" style="color: #00607a !important;" onclick="editarPersonal(${r.id_personal})" title="Editar"><i class="fa-solid fa-pen"></i></button>
                    <button class="btn btn-sm btn-light text-danger border-0 shadow-sm" onclick="eliminarPersonal(${r.id_personal})" title="Dar de Baja"><i class="fa-solid fa-trash"></i></button>
                </div>
            ` }
        ],
        language: { url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
        dom: 'rtip', 
        pageLength: 15, 
        order: [[2, 'asc']],
        drawCallback: function() {
            // Inicializar tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });

            // ACTUALIZAR CONTADORES (Estadísticas en tiempo real)
            try {
                let api = this.api();
                let data = api.rows({ search: 'applied' }).data().toArray();
                
                let total = data.length;
                // "Revista Pasada" debe incluir a TODOS los que ya tienen un estado registrado (Presente, Ausente, Permiso, Comisión)
                // para que la suma cuadre: Total = Pasaron + Pendientes
                let registrados = data.filter(r => r.estado_revista !== null && r.estado_revista !== '').length;
                let pendientes = total - registrados;

                $('#statTotal').text(total);
                // Usamos 'registrados' en lugar de 'revistaOK' para que cuadre con el total (104 = 4 + 100)
                $('#statRevistaOK').text(registrados);
                $('#statPendientes').text(pendientes);

            } catch(e) { console.error("Error stats", e); }
        }
    });
}

window.verPersonal = function(id) {
    console.log("Intentando ver personal ID:", id);
    
    $.ajax({
        url: '../controlador/PersonalControlador.php',
        type: 'GET',
        data: { op: 'obtener', id: id },
        dataType: 'text', // Forzamos texto para controlar el parseo manualmente y ver errores de PHP si los hay
        success: function(resp) {
            console.log("Respuesta bruta del servidor:", resp);
            try {
                let data = JSON.parse(resp);
                if(data && !data.error) {
                    // Llenar Ficha - Version Legajo Completo
                    $('#lbl_grado').text(data.nombre_grado || 'S/G');
                    $('#lbl_nombre').text((data.apellidos + ' ' + (data.nombres||'')));
                    $('#lbl_cargo').text(data.cargo || 'SIN CARGO');
                    
                    $('#lbl_cip').text(data.cip || '-');
                    $('#lbl_sit_cip').text(data.situacion_cip || 'ACTVIDAD');
                    
                    $('#lbl_dni').text(data.dni || '-');
                    $('#lbl_tipo_doc').text(data.tipo_dni || 'DOC');

                    // Ubicación
                    $('#lbl_subunidad').text(data.nombre_subunidad || 'Sin Asignar');
                    $('#lbl_division').text(data.nombre_division || '-');
                    $('#lbl_region').text(data.nombre_region || '-');

                    // Situación
                    $('#lbl_sit_especial').text(data.situacion_especial || 'NINGUNA');
                    $('#lbl_horario').text(data.funcion_horario || '-');
                    
                    // Datos Personales
                    $('#lbl_sexo').text(data.sexo === 'M' ? 'MASCULINO' : 'FEMENINO');
                    $('#lbl_nacimiento').text(data.fecha_nacimiento || '-');
                    $('#lbl_fecha_reg').text(data.fecha_registro || '-');

                    // Estado
                    if(data.estado === 'Activo') {
                        $('#lbl_estado').html('<span class="badge bg-success w-100 py-2">ACTIVO</span>');
                    } else {
                        $('#lbl_estado').html('<span class="badge bg-danger w-100 py-2">BAJA</span>');
                    }

                    // Mostrar Modal
                    window.currentViewId = id; // Guardar ID globalmente para usar en registro
                    $('#modalVerPersonal').modal('show');
                    
                    // Resetear checkboxes de documentos por defecto
                    $('#chkPortaCIP').prop('checked', true);
                    $('#chkPortaDNI').prop('checked', true);

                    // Cargar historial
                    cargarHistorial(id);
                    
                    // Actualizar Etiqueta del Mes en la Revista
                    const meses = ["ENERO", "FEBRERO", "MARZO", "ABRIL", "MAYO", "JUNIO", "JULIO", "AGOSTO", "SEPTIEMBRE", "OCTUBRE", "NOVIEMBRE", "DICIEMBRE"];
                    const hoy = new Date();
                    $('#lblPeriodoRevista').text(meses[hoy.getMonth()] + " " + hoy.getFullYear());

                } else {
                    Swal.fire('Error', 'No se encontraron datos para este personal.', 'error');
                }
            } catch(e) {
                console.error("Error parseando JSON:", e);
                Swal.fire('Error de Sistema', 'La respuesta del servidor no es válida.', 'error');
            }
        }
    });
}

function cargarHistorial(id) {
    $.get('../controlador/PersonalControlador.php?op=obtener_historial&id=' + id, function(resp) {
        let data = JSON.parse(resp).data;
        let html = '';
        if(data && data.length > 0) {
            data.forEach(h => {
                let badgeClass = h.estado === 'PRESENTE' ? 'bg-success' : (h.estado === 'AUSENTE' ? 'bg-danger' : 'bg-warning text-dark');
                html += `<tr>
                            <td>${h.fecha}</td>
                            <td><span class="badge ${badgeClass}">${h.estado}</span></td>
                            <td>${h.observacion || '-'}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-light text-danger border-0 btn-eliminar-novedad" data-id="${h.id_novedad}" title="Eliminar registro">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </td>
                         </tr>`;
            });
        } else {
            html = '<tr><td colspan="4" class="text-center text-muted">- Sin historial reciente -</td></tr>';
        }
        $('#tablaHistorialRapido tbody').html(html);
    });
}

// Delegación de Eventos para botones dinámicos (Más seguro)
$(document).on('click', '.btn-eliminar-novedad', function() {
    let id_novedad = $(this).data('id');
    console.log("Intentando eliminar novedad ID:", id_novedad);

    Swal.fire({
        title: '¿Eliminar registro?',
        text: "Esta acción borrará la marca de asistencia.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sí, borrar',
        cancelButtonText: 'Cancelar',
        // Forzar Z-Index manualmente a nivel de instancia de Swal
        backdrop: `rgba(0,0,0,0.4)`,
        customClass: {
            container: 'swal-top-zindex' 
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('../controlador/PersonalControlador.php?op=eliminar_novedad', { id_novedad: id_novedad }, function(resp) {
                try {
                    let r = JSON.parse(resp);
                    if(r.status) {
                        cargarHistorial(window.currentViewId);
                        const Toast = Swal.mixin({toast: true, position: 'top-end', showConfirmButton: false, timer: 2000, customClass: {popup: 'swal-top-zindex'}});
                        Toast.fire({icon: 'success', title: 'Registro eliminado'});
                    } else {
                        Swal.fire('Error', 'No se pudo eliminar.', 'error');
                    }
                } catch(e) {
                    console.error(e);
                }
            });
        }
    });
});

window.currentNovedadType = '';

window.prepararNovedad = function(estado) {
    if(!window.currentViewId) return;

    if(estado === 'PRESENTE') {
        // Logica para verificar checkboxes
        let tieneCIP = $('#chkPortaCIP').is(':checked');
        let tieneDNI = $('#chkPortaDNI').is(':checked');
        
        // 1. Si tiene todo, guardado rápido por defecto
        if(tieneCIP && tieneDNI) {
            enviarNovedadReal(estado, '');
            return;
        }

        // 2. Si falta algo, abrir panel de detalle para especificar la razón (ej. "Vencido", "Robo")
        let obsArr = [];
        if(!tieneCIP) obsArr.push("FALTA CARNET (CIP)");
        if(!tieneDNI) obsArr.push("FALTA DNI");

        let preMsg = obsArr.length > 0 ? obsArr.join(" Y ") + ": " : "";
        
        // Mismo flujo que Ausente/Permiso pero pre-llenado
        window.currentNovedadType = estado;
        $('#btnGroupAcciones').hide();
        $('#divDetalleNovedad').fadeIn();
        
        // Ajustar visualmente para indicar que es una observación de "Presente con Novedad"
        $('#lblTipoNovedad').removeClass('bg-light').addClass('bg-warning text-dark').text(estado);
        
        $('#txtObservacionNovedad').val(preMsg).focus();

    } else {
        // Mostrar panel de detalle y ocultar botones
        window.currentNovedadType = estado;
        $('#btnGroupAcciones').hide();
        $('#divDetalleNovedad').fadeIn();
        $('#lblTipoNovedad').text(estado);
        $('#txtObservacionNovedad').val('').focus();
    }
}

window.cancelarNovedad = function() {
    $('#divDetalleNovedad').hide();
    $('#btnGroupAcciones').fadeIn();
    window.currentNovedadType = '';
}

window.confirmarNovedad = function() {
    let obs = $('#txtObservacionNovedad').val();
    if(!obs.trim()) {
        const Toast = Swal.mixin({toast: true, position: 'top-end', showConfirmButton: false, timer: 3000});
        Toast.fire({icon: 'warning', title: 'Debe especificar un detalle/observación.'});
        return;
    }
    enviarNovedadReal(window.currentNovedadType, obs);
}

function enviarNovedadReal(estado, obs) {
    $.post('../controlador/PersonalControlador.php?op=registrar_asistencia', {
        id_personal: window.currentViewId,
        estado: estado,
        observacion: obs
    }, function(resp) {
        try {
            let r = JSON.parse(resp);
            if(r.status) {
                const Toast = Swal.mixin({toast: true, position: 'top-end', showConfirmButton: false, timer: 2000});
                Toast.fire({icon: 'success', title: 'Registrado: ' + estado});
                
                cargarHistorial(window.currentViewId); // Recargar tablita
                
                // Restaurar vista si estaba en modo detalle
                if(estado !== 'PRESENTE') cancelarNovedad();
                
            } else {
                Swal.fire('Error', r.msg, 'error');
            }
        } catch(e) {
            console.error(e);
            Swal.fire('Error', 'Respuesta inválida del servidor', 'error');
        }
    });
}

function guardarPersonal() {
    if(!$('#id_subunidad_hidden').val()) {
        Swal.fire('Atención', 'Debe seleccionar una Sub-Unidad válida y completa (Región -> División -> Subunidad).', 'warning');
        return;
    }
    const fd = new FormData(document.getElementById('formPersonal'));
    $.ajax({
        url: '../controlador/PersonalControlador.php?op=guardar', type: 'POST', data: fd, processData: false, contentType: false, dataType: 'json',
        success: r => {
            if(r.status) { $('#modalNuevoPersonal').modal('hide'); Swal.fire('Guardado', r.msg, 'success'); tablaPersonal.ajax.reload(null, false); }
            else Swal.fire('Error', r.msg, 'error');
        }
    });
}

function ejecutarImportacion() {
    const input = document.querySelector('#formImportar input[type=file]');
    const isLimpiar = $('#chkLimpiar').is(':checked');

    // Validación: Exigir archivo SOLO si NO se va a limpiar. 
    // Si se limpia, el archivo es opcional (puede ser limpieza pura).
    if ((!input.files || input.files.length === 0) && !isLimpiar) {
        Swal.fire('Atención', 'Por favor seleccione un archivo Excel o active la opción de limpiar.', 'warning');
        return;
    }



    const procesar = () => {
        const fd = new FormData(document.getElementById('formImportar'));
        if(isLimpiar) {
            fd.append('limpiar', 'true');
        }

        const btn = document.querySelector('#modalImportar .btn-success');
        const txt = btn.innerHTML; 
        btn.disabled = true; 
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Procesando...';
        
        $.ajax({
            url: '../controlador/PersonalControlador.php?op=importar', 
            type: 'POST', 
            data: fd, 
            processData: false, 
            contentType: false, 
            dataType: 'json',
            success: r => {
                btn.disabled = false; 
                btn.innerHTML = txt; 
                $('#modalImportar').modal('hide');
                
                if(r.status) { 
                    Swal.fire({
                        title: 'Importación Exitosa',
                        text: r.msg,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    tablaPersonal.ajax.reload(null, false); 
                } else { 
                    // Si hay errores parciales pero se cargó algo
                    if(r.errores && r.errores.length > 0) {
                        let htmlErr = `<ul class="text-start small" style="max-height: 200px; overflow-y: auto;">`;
                        r.errores.forEach(e => htmlErr += `<li>${e}</li>`);
                        htmlErr += `</ul>`;
                        Swal.fire({
                            title: 'Atención', 
                            html: `${r.msg}<br><br><strong>Errores detectados:</strong>${htmlErr}`, 
                            icon: 'warning',
                            width: '600px'
                        });
                        tablaPersonal.ajax.reload(null, false);
                    } else {
                        Swal.fire('Error', r.msg, 'error');
                    }
                }
            },
            error: (err) => { 
                btn.disabled = false; 
                btn.innerHTML = txt; 
                console.error(err);
                Swal.fire('Error del Servidor', 'Ocurrió un error al procesar la solicitud. Revise la consola.', 'error'); 
            }
        });
    };

    if (isLimpiar) {
        Swal.fire({
            title: '¿Está seguro de limpiar todo?',
            text: "Ha seleccionado la opción de LIMPIAR. Se ELIMINARÁ TODO EL PERSONAL registrado actualmente antes de importar el nuevo archivo. Esta acción es irreversible.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, borrar todo e importar',
            cancelButtonText: 'Cancelar, solo importar'
        }).then((result) => {
            if (result.isConfirmed) {
                procesar();
            }
        });
    } else {
        procesar();
    }
}
    // --- FUNCION DE FILTRADO POR TARJETAS ---
    window.filtrarTablaRevista = function(filtro) {
        if(!tablaPersonal) return;

        // Columna 10 es "Revista"
        let col = tablaPersonal.column(10);
        
        switch(filtro) {
            case 'TODOS':
                col.search('').draw();
                const Toast1 = Swal.mixin({toast: true, position: 'top-end', showConfirmButton: false, timer: 1500});
                Toast1.fire({icon: 'info', title: 'Mostrando todo el personal'});
                break;
            case 'PASADOS':
                // Busca PASÓ, PERMISO, AUSENTE, COMISIÓN (Regex)
                col.search('PASÓ|PERMISO|AUSENTE|COMISIÓN', true, false).draw();
                const Toast2 = Swal.mixin({toast: true, position: 'top-end', showConfirmButton: false, timer: 1500});
                Toast2.fire({icon: 'success', title: 'Filtrando personal con revista'});
                break;
            case 'PENDIENTES':
                col.search('PENDIENTE').draw();
                const Toast3 = Swal.mixin({toast: true, position: 'top-end', showConfirmButton: false, timer: 1500});
                Toast3.fire({icon: 'warning', title: 'Filtrando pendientes de revista'});
                break;
        }
    };
</script>

<script>
$(document).ready(function() {
    // Listener para el filtro de mes (Cumpleaños)
    $('#filtro_mes_cumple').on('change', function() {
        if(typeof tablaPersonal !== 'undefined') {
            tablaPersonal.ajax.reload();
        }
    });

    // Pequeño estilo extra para el select
    $('#filtro_mes_cumple').on('focus', function() { $(this).addClass('border-primary shadow'); });
    $('#filtro_mes_cumple').on('blur', function() { $(this).removeClass('border-primary shadow'); });
});
</script>
