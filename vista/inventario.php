<?php
// vista/inventario.php
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
if ($_SESSION['rol'] !== 'Administrador' && $_SESSION['rol'] !== 'Super Administrador' && !isset($_SESSION['permisos']['INVENTARIO']['VER'])) {
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

include 'layout/topbar.php';
include 'layout/sidebar.php';

// Determinar permisos para JS
$pCrear = ($_SESSION['rol'] === 'Administrador' || $_SESSION['rol'] === 'Super Administrador' || isset($_SESSION['permisos']['INVENTARIO']['CREAR'])) ? 'true' : 'false';
$pEditar = ($_SESSION['rol'] === 'Administrador' || $_SESSION['rol'] === 'Super Administrador' || isset($_SESSION['permisos']['INVENTARIO']['EDITAR'])) ? 'true' : 'false';
$pEliminar = ($_SESSION['rol'] === 'Administrador' || $_SESSION['rol'] === 'Super Administrador' || isset($_SESSION['permisos']['INVENTARIO']['ELIMINAR'])) ? 'true' : 'false';
$pImportar = ($_SESSION['rol'] === 'Administrador' || $_SESSION['rol'] === 'Super Administrador' || isset($_SESSION['permisos']['INVENTARIO']['IMPORTAR'])) ? 'true' : 'false';

// Determinar si se muestran acciones
$mostrarAcciones = ($pEditar === 'true' || $pEliminar === 'true') ? 'true' : 'false';
$esAdmin = ($_SESSION['rol'] === 'Administrador' || $_SESSION['rol'] === 'Super Administrador');

// Cargar Regiones Iniciales
$regiones = [];
if ($esAdmin) {
    $resReg = $conexion->query("SELECT id_region, nombre_region FROM regiones_policiales WHERE estado = 1 ORDER BY nombre_region");
    while($r = $resReg->fetch_assoc()) $regiones[] = $r;
}

// Obtener Grados para PDF
$sqlGrados = "SELECT nombre_grado FROM mae_grados WHERE activo = 1 ORDER BY nombre_grado ASC";
$resultGrados = $conexion->query($sqlGrados);
$grados = [];
if ($resultGrados) {
    while ($g = $resultGrados->fetch_assoc()) {
        $grados[] = $g['nombre_grado'];
    }
}
?>
<script>
    const PERMISO_EDITAR = <?= $pEditar ?>;
    const PERMISO_ELIMINAR = <?= $pEliminar ?>;
    const MOSTRAR_ACCIONES = <?= $mostrarAcciones ?>;
</script>

<style>
    .site-footer {
        background-color: #006080;
        color: #fff;
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        padding: 5px 0;
        font-size: 0.8rem;
        text-align: center;
        z-index: 1000;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    body {
        padding-bottom: 40px;
    }

    table.dataTable thead th {
        background-color: #495057 !important;
        color: white !important;
        font-weight: 600 !important;
        text-transform: uppercase !important;
        font-size: 0.7rem !important;
        letter-spacing: 0.5px !important;
        padding: 10px 6px !important;
        border-bottom: 2px solid #343a40 !important;
        vertical-align: middle !important;
        text-align: center !important;
        position: sticky !important;
        top: 0 !important;
        z-index: 10 !important;
    }

    table.dataTable thead th:hover {
        background-color: #5a6268 !important;
    }

    table.dataTable tbody tr {
        transition: background-color 0.2s ease;
    }

    table.dataTable tbody tr:hover {
        background-color: #f8f9fa !important;
    }

    table.dataTable td {
        border-bottom: 1px solid #e9ecef !important;
        vertical-align: middle !important;
        text-align: center !important;
        padding: 8px 6px !important;
        font-size: 0.85rem !important;
    }

    .filter-panel {
        background-color: #fcfcfc;
        border: 1px solid #e0e0e0;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }

    .filter-label {
        font-size: 0.75rem;
        font-weight: 700;
        color: #6c757d;
        text-transform: uppercase;
        margin-bottom: 5px;
        display: block;
    }

    /* BUSCADORES PERSONALIZADOS */
    .custom-search-list {
        z-index: 9999 !important;
        max-height: 250px;
        overflow-y: auto;
        width: 100%;
        margin-top: 2px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
        box-shadow: 0 8px 30px rgba(0,0,0,0.15) !important;
        background-color: #fff !important;
        display: none;
        position: absolute;
        left: 0;
        right: 0;
    }

    .custom-search-list .list-group-item {
        padding: 10px 15px;
        cursor: pointer;
        font-size: 13px;
        border: none;
        border-bottom: 1px solid #f1f3f5;
        background-color: #fff !important;
        color: #333 !important;
        text-align: left !important;
    }

    .custom-search-list .list-group-item:hover {
        background-color: #e7f3ff !important;
        color: #00779e !important;
        font-weight: 600;
    }

    .form-control:disabled {
        background-color: #f5f5f5 !important;
        cursor: not-allowed !important;
        opacity: 0.7 !important;
    }

    .filter-panel .form-control {
        font-size: 0.85rem !important;
        min-height: 38px !important;
        border-radius: 6px !important;
    }
</style>

<div class="page-content" style="padding: 20px; padding-top: 80px;">
    <div class="container-fluid">

        <?php if (isset($_SESSION['error_permiso'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-triangle-exclamation me-2"></i>
                <strong>Acceso Denegado:</strong> <?= $_SESSION['error_permiso'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error_permiso']); ?>
        <?php endif; ?>

        <!-- Barra de Navegación -->
        <div class="d-flex align-items-center justify-content-between mb-3 p-3 rounded"
            style="background-color: #00779e; color: white; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div class="d-flex align-items-center">
                <h4 class="mb-0 fw-bold"><i class="fa-solid fa-boxes-stacked me-2"></i>Inventario de Bienes</h4>
            </div>
            
            <div class="d-flex gap-2">
                <?php if ($pCrear === 'true'): ?>
                    <a href="inventario_nuevo.php" class="btn btn-light btn-sm fw-bold" style="color: #00779e;">
                        <i class="fa-solid fa-plus me-1"></i> Nuevo Item
                    </a>
                <?php endif; ?>

                <!-- Botón Reportes -->
                <div class="dropdown">
                    <button class="btn btn-warning btn-sm dropdown-toggle text-dark fw-bold" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-file-export me-1"></i> Reportes
                    </button>
                    <ul class="dropdown-menu shadow">
                        <li><a class="dropdown-item" href="#" onclick="descargarReporte('excel')"><i
                                    class="fa-solid fa-file-excel text-success me-2"></i> Reporte Excel</a></li>
                        <li><a class="dropdown-item" href="#" onclick="descargarReporte('pdf')"><i
                                    class="fa-solid fa-file-pdf text-danger me-2"></i> Reporte PDF</a></li>
                    </ul>
                </div>

                <?php if ($pImportar === 'true'): ?>
                    <button class="btn btn-success btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalImportar">
                        <i class="fa-solid fa-file-excel me-1"></i> Importar Excel
                    </button>
                <?php endif; ?>

                <!-- Botón Manual Tutorial PDF -->
                <a href="../controlador/generar_manual_inventario_pdf.php" target="_blank" class="btn btn-sm fw-bold" 
                   style="background: transparent; border: none; color: #ffffff !important; transition: all 0.3s ease;"
                   onmouseover="this.style.textShadow='0 0 8px rgba(255,255,255,0.5)';"
                   onmouseout="this.style.textShadow='none';"
                   title="Ver Manual de Usuario e Inventario">
                    <i class="fas fa-book-open me-1 text-warning"></i> Tutorial
                </a>
                
            </div>
        </div>

        <!-- Panel de Filtros -->
        <div class="filter-panel shadow-sm border-0">
            <div class="row g-3 align-items-end">
                <div class="col-md-1">
                    <label class="filter-label">Año</label>
                    <select class="form-select form-select-sm" id="filtroAnio" onchange="cargarTabla()">
                        <?php for ($y = date('Y') + 1; $y >= 2024; $y--): ?>
                            <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <?php if ($esAdmin): ?>
                <div class="col-md-3 position-relative">
                    <label class="filter-label">Macro Región / Región</label>
                    <input type="text" id="input_region" class="form-control" placeholder="Buscar región..." autocomplete="off">
                    <input type="hidden" id="id_region_hidden">
                    <ul id="lista_regiones" class="list-group custom-search-list shadow"></ul>
                </div>
                <div class="col-md-3 position-relative">
                    <label class="filter-label">División Policial</label>
                    <input type="text" id="input_division" class="form-control" placeholder="Esperando región..." autocomplete="off" disabled>
                    <input type="hidden" id="id_division_hidden">
                    <ul id="lista_divisiones" class="list-group custom-search-list shadow"></ul>
                </div>
                <div class="col-md-3 position-relative">
                    <label class="filter-label">Comisaría / Sub-Unidad</label>
                    <input type="text" id="input_subunidad" class="form-control" placeholder="Esperando división..." autocomplete="off" disabled>
                    <input type="hidden" id="id_subunidad_hidden">
                    <ul id="lista_subunidades" class="list-group custom-search-list shadow"></ul>
                </div>
                <div class="col-md-2 text-end">
                    <button class="btn btn-sm btn-outline-secondary fw-bold" onclick="resetFiltros()">
                        <i class="fa-solid fa-eraser me-1"></i> Limpiar
                    </button>
                </div>
                <?php else: ?>
                <div class="col-md-11 text-end">
                    <span class="badge bg-light text-dark border p-2">
                        <i class="fas fa-building me-1"></i> 
                        Oficina: <?= $_SESSION['oficina_nombre'] ?? 'S/D' ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tablaInventario" class="table table-hover table-striped table-bordered align-middle"
                        style="width:100%; font-size: 0.85rem;">
                        <thead class="bg-dark text-white text-center">
                            <tr>
                                <th>Cód. Patr.</th>
                                <th>Denominación</th>
                                <th>Marca</th>
                                <th>Modelo</th>
                                <th>Serie</th>
                                <th>Estado</th>
                                <th>Situación</th>
                                <?php if ($esAdmin): ?>
                                <th>Ubicación / Dependencia</th>
                                <?php endif; ?>
                                <th>Responsable</th>
                                <?php if ($mostrarAcciones === 'true'): ?>
                                    <th style="width: 80px;">Acciones</th>
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

<!-- Modal PDF -->
<div class="modal fade" id="modalPdfInventariador" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fa-solid fa-file-pdf me-2"></i>Datos del Reporte (Anexo 07)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formPdfDatos">
                    <p class="text-muted small mb-3">Ingrese los datos del <strong>Inventariador</strong> que aparecerá en el reporte:</p>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Grado del Inventariador</label>
                        <select class="form-select form-select-sm" id="pdf_grado">
                            <option value="">Seleccione...</option>
                            <?php foreach ($grados as $g): ?>
                                <option value="<?= $g ?>"><?= $g ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Apellidos y Nombres (Inventariador)</label>
                        <input type="text" class="form-control form-control-sm" id="pdf_nombres" placeholder="JUAN PEREZ">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">DNI / CIP del Inventariador</label>
                        <input type="text" class="form-control form-control-sm" id="pdf_dni">
                    </div>
                    <hr>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Equipo de Trabajo (Comisión)</label>
                        <input type="text" class="form-control form-control-sm" id="pdf_equipo" placeholder="COMISIÓN DE INVENTARIO 01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Tipo de Verificación</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="pdf_tipo_verificacion" id="v_fisica" value="fisica" checked>
                                <label class="form-check-label" for="v_fisica">Física</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="pdf_tipo_verificacion" id="v_digital" value="digital">
                                <label class="form-check-label" for="v_digital">Digital</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger btn-sm" onclick="abrirPdfFinal()">Generar PDF</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Importar -->
<div class="modal fade" id="modalImportar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #00779e; color: white;">
                <h5 class="modal-title"><i class="fa-solid fa-file-excel me-2"></i>Importar Excel</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formImportar" enctype="multipart/form-data">
                    <div class="alert alert-info py-2 small">
                        <i class="fa-solid fa-info-circle me-1"></i> Comenzar en <strong>fila 5</strong>.
                    </div>
                    <div class="text-center mb-3">
                        <a href="../plantilla_inventario.php" class="btn btn-xs btn-outline-primary" download>
                           <i class="fa-solid fa-download me-1"></i> Descargar Plantilla
                        </a>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Año</label>
                        <select class="form-select form-select-sm" name="anio" id="anioImportacion">
                            <?php for ($y = date('Y') + 1; $y >= 2024; $y--): ?>
                                <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="checkLimpiar" name="limpiar_anio" value="true">
                        <label class="form-check-label text-danger fw-bold small" for="checkLimpiar">Sobrescribir este año</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Archivo</label>
                        <input type="file" class="form-control form-control-sm" name="archivo" id="archivoExcel" accept=".xls,.xlsx">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success btn-sm" onclick="importarExcel()">Importar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    let tabla;
    const ES_ADMIN = <?= $esAdmin ? 'true' : 'false' ?>;
    const regionesData = <?php echo json_encode($regiones); ?>;
    let divisionesData = [];
    let subunidadesData = [];

    $(document).ready(function () {
        if (ES_ADMIN) {
            initFiltrosSensibles();
        }
        cargarTabla();
    });

    function initFiltrosSensibles() {
        initCustomSearch('input_region', 'lista_regiones', 'id_region_hidden', regionesData, 'nombre_region', 'id_region', function(id) {
            // Reset y Cargar Divisiones
            $('#input_division').val('').prop('disabled', false).css('background-color', '#fff').attr('placeholder', 'Cargando...');
            $('#id_division_hidden').val('');
            $('#input_subunidad').val('').prop('disabled', true).css('background-color', '#f5f5f5').attr('placeholder', 'Esperando división...');
            $('#id_subunidad_hidden').val('');
            
            $.get('../controlador/get_unidades_ajax.php', { action: 'get_divisiones', id_region: id }, function(data) {
                divisionesData = data;
                $('#input_division').attr('placeholder', 'Buscar división...');
                initCustomSearch('input_division', 'lista_divisiones', 'id_division_hidden', function() { return divisionesData; }, 'nombre_division', 'id_division', function(idDiv) {
                    // Reset y Cargar Subunidades
                    $('#input_subunidad').val('').prop('disabled', false).css('background-color', '#fff').attr('placeholder', 'Cargando...');
                    $('#id_subunidad_hidden').val('');
                    
                    $.get('../controlador/get_unidades_ajax.php', { action: 'get_subunidades', id_division: idDiv }, function(dataSub) {
                        subunidadesData = dataSub;
                        $('#input_subunidad').attr('placeholder', 'Buscar unidad...');
                        initCustomSearch('input_subunidad', 'lista_subunidades', 'id_subunidad_hidden', function() { return subunidadesData; }, 'nombre_subunidad', 'id_subunidad', function() {
                            cargarTabla();
                        });
                        cargarTabla();
                    });
                });
                cargarTabla();
            });
        });
    }

    function initCustomSearch(inputId, listId, hiddenId, dataArrayOrFn, displayKey, idKey, onSelectCallback) {
        const $input = $('#' + inputId);
        const $list = $('#' + listId);
        const $hidden = $('#' + hiddenId);
        let validSelection = false;
        let lastValidValue = '';

        $input.off('focus input blur clearSearch');

        // Escuchar evento de limpieza externa
        $input.on('clearSearch', function() {
            validSelection = false;
            lastValidValue = '';
            $input.val('');
            $hidden.val('');
        });

        const renderList = (filter = '') => {
            const val = filter.toUpperCase();
            let currentData = (typeof dataArrayOrFn === 'function') ? dataArrayOrFn() : dataArrayOrFn;
            $list.empty();
            if (!currentData || currentData.length === 0) { $list.hide(); return; }

            const filtered = currentData.filter(item => (item[displayKey] || '').toUpperCase().includes(val));
            if (filtered.length > 0) {
                filtered.forEach(item => {
                    const $li = $('<li class="list-group-item"></li>').text(item[displayKey]);
                    $li.on('mousedown', function(e) {
                        e.preventDefault();
                        $input.val(item[displayKey]);
                        $hidden.val(item[idKey]);
                        validSelection = true;
                        lastValidValue = item[displayKey];
                        $list.hide();
                        if (onSelectCallback) onSelectCallback(item[idKey]);
                    });
                    $list.append($li);
                });
                $list.show();
            } else { $list.hide(); }
        };

        $input.on('focus', function() { this.select(); renderList(''); });
        $input.on('input', function() { validSelection = false; renderList($(this).val()); });
        $input.on('blur', function() {
            setTimeout(() => {
                $list.hide();
                if (!validSelection) {
                    if (lastValidValue) {
                        $input.val(lastValidValue);
                    } else {
                        if ($input.val() !== '') {
                            $input.val('');
                            $hidden.val('');
                            if (onSelectCallback) onSelectCallback('');
                        }
                    }
                }
            }, 200);
        });
    }

    function resetFiltros() {
        // Disparar evento de limpieza para resetear estados internos de los buscadores
        $('#input_region, #input_division, #input_subunidad').trigger('clearSearch');
        
        // Resetear visualmente y deshabilitar niveles inferiores
        $('#input_division').prop('disabled', true).css('background-color', '#f5f5f5').attr('placeholder', 'Esperando región...');
        $('#input_subunidad').prop('disabled', true).css('background-color', '#f5f5f5').attr('placeholder', 'Esperando división...');
        
        cargarTabla();
    }

    function cargarTabla() {
        const anio = document.getElementById('filtroAnio').value;
        const idSubunidad = ES_ADMIN ? $('#id_subunidad_hidden').val() : '';
        if ($.fn.DataTable.isDataTable('#tablaInventario')) { $('#tablaInventario').DataTable().destroy(); }

        let columnas = [
            { "data": "codigo_inventario", "className": "text-center fw-bold" },
            { "data": "descripcion", "className": "text-start" },
            { "data": "marca" }, { "data": "modelo" }, { "data": "serie" },
            { "data": "estado_bien", "render": function(data) {
                let b = 'secondary';
                if(data==='BUENO'||data==='NUEVO') b='success';
                else if(data==='REGULAR') b='warning';
                else if(data==='MALO') b='danger';
                return `<span class="badge bg-${b}">${data}</span>`;
            }},
            { "data": "situacion" }
        ];

        if (ES_ADMIN) columnas.push({ "data": "subunidad_nombre", "render": d => `<small>${d||'OFICINA'}</small>` });
        columnas.push({ "data": "usuario_responsable" });

        if (MOSTRAR_ACCIONES) {
            columnas.push({ "data": null, "render": (d,t,row) => {
                let h = '<div class="d-flex gap-2 justify-content-center">';
                if(PERMISO_EDITAR) h += `<button class="btn btn-xs btn-primary" onclick="editar(${row.id})"><i class="fa-solid fa-pen"></i></button>`;
                if(PERMISO_ELIMINAR) h += `<button class="btn btn-xs btn-danger" onclick="eliminar(${row.id},'${row.codigo_inventario}')"><i class="fa-solid fa-trash"></i></button>`;
                return h + '</div>';
            }});
        }

        tabla = $('#tablaInventario').DataTable({
            "ajax": `../controlador/InventarioControlador.php?op=listar&anio=${anio}&id_subunidad=${idSubunidad}`,
            "columns": columnas, "order": [[0, "asc"]],
            "language": { "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
            "pageLength": 10
        });
    }

    function editar(id) { window.location.href = `inventario_editar.php?id=${id}`; }
    function eliminar(id, cod) {
        Swal.fire({ title: '¿Eliminar?', text: cod, icon: 'warning', showCancelButton: true }).then(r => {
            if(r.isConfirmed) $.post('../controlador/InventarioControlador.php?op=eliminar', {id:id}, () => { cargarTabla(); Swal.fire('Ok','Item eliminado','success'); });
        });
    }

    function descargarReporte(f) {
        if(f==='excel') {
            const a = $('#filtroAnio').val(); const s = $('#id_subunidad_hidden').val();
            window.open(`../controlador/reporte_inventario_excel.php?anio=${a}&id_subunidad=${s}`, '_blank');
        } else { $('#modalPdfInventariador').modal('show'); }
    }

    function abrirPdfFinal() {
        const a = $('#filtroAnio').val(); const s = $('#id_subunidad_hidden').val();
        const p = new URLSearchParams({ anio: a, id_subunidad: s, inv_grado: $('#pdf_grado').val(), inv_nombres: $('#pdf_nombres').val(), inv_dni: $('#pdf_dni').val(), inv_equipo: $('#pdf_equipo').val(), inv_tipo_verificacion: $('input[name="pdf_tipo_verificacion"]:checked').val() });
        window.open(`../controlador/reporte_inventario_pdf.php?${p.toString()}`, '_blank');
        
        // Limpiar campos para nueva redacción
        document.getElementById('formPdfDatos').reset();
        $('#modalPdfInventariador').modal('hide');
    }

    function importarExcel() {
        const fd = new FormData(document.getElementById('formImportar'));
        Swal.fire({title:'Importando...', didOpen:()=>Swal.showLoading()});
        $.ajax({
            url: '../controlador/InventarioControlador.php?op=importar',
            method: 'POST', data: fd, processData: false, contentType: false, dataType: 'json',
            success: r => { Swal.close(); if(r.status){ Swal.fire('Éxito', r.msg, 'success'); $('#modalImportar').modal('hide'); resetFiltros(); } else Swal.fire('Error', r.msg, 'error'); }
        });
    }
</script>

<?php include 'layout/footer.php'; ?>