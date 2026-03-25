<?php
// vista/destinos.php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE)
    session_start();

if (empty($_SESSION['id'])) {
    header("Location: ../vista/login/login.php");
    exit();
}

// 1. Conexión y Permisos (Explicit Sync)
require_once '../modelo/conexion.php';
require_once '../controlador/autocargar_permisos.php';
if (isset($conn) && $conn instanceof mysqli) {
    recargarPermisosUsuario($_SESSION['id'], $conn);
} elseif (isset($conexion) && $conexion instanceof mysqli) {
    recargarPermisosUsuario($_SESSION['id'], $conexion);
}

// 2. Seguridad Estricta (Bloqueo VER)
if ($_SESSION['rol'] !== 'Super Administrador' && !isset($_SESSION['permisos']['DESTINOS']['VER'])) {
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

// --- SERVER SIDE LOGIC ---
require_once '../modelo/DestinoModelo.php';
$modeloDestino = new DestinoModelo();

// Determinar permisos
$pEditar = ($_SESSION['rol'] === 'Administrador' || isset($_SESSION['permisos']['DESTINOS']['EDITAR'])) ? true : false;
$pEliminar = ($_SESSION['rol'] === 'Administrador' || isset($_SESSION['permisos']['DESTINOS']['ELIMINAR'])) ? true : false;
$mostrarAcciones = ($pEditar || $pEliminar);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mantenimiento de Destinos - SIG</title>

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

    <style>
        .select2-container--bootstrap-5 .select2-selection {
            font-size: 0.875rem;
            min-height: calc(1.5em + 0.5rem + 2px);
        }
        .badge { font-weight: 600; }
    </style>
</head>

<body>

<div class="page-content" style="padding-top: 80px; padding-left: 20px; padding-right: 20px;">
    <div class="container-fluid">
        <!-- Barra de Navegación -->
        <div class="d-flex align-items-center justify-content-between sis-nav-container mb-3"
            style="background-color: #00779e; color: #fff; border-radius: 8px; padding: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div class="d-flex align-items-center">
                <h4 class="m-0 fw-bold me-4"><i class="fa-solid fa-building me-2"></i>Mantenimiento de Destinos</h4>

                <div class="d-flex gap-2">
                    <a href="gestion_documental.php" class="btn btn-outline-light btn-sm fw-bold">
                        <i class="fa-solid fa-arrow-left me-1"></i> Volver a Gestión Documental
                    </a>
                </div>
            </div>

            <?php if ($pEditar): ?>
            <button class="btn btn-light fw-bold" style="color: #00779e;" onclick="abrirModal()">
                <i class="fa-solid fa-plus me-1"></i> Nuevo Destino
            </button>
            <?php endif; ?>
        </div>

        <!-- Filtros de Matriz -->
        <div class="card shadow-sm border-0 mb-3" style="background-color: #f8f9fa; border-left: 5px solid #00779e !important;">
            <div class="card-body py-2">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted mb-1">Filtrar por Región</label>
                        <select id="filtro_region" class="form-select form-select-sm select2-basic">
                            <option value="">Todas las Regiones</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted mb-1">Filtrar por División</label>
                        <select id="filtro_division" class="form-select form-select-sm select2-basic">
                            <option value="">Todas las Divisiones</option>
                        </select>
                    </div>
                    <div class="col-md-4 text-end">
                        <button class="btn btn-sm btn-secondary" onclick="limpiarFiltros()">
                            <i class="fas fa-filter-circle-xmark me-1"></i> Limpiar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla -->
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tablaDestinos"
                        class="table table-sm table-hover table-striped table-bordered align-middle text-center w-100">
                        <thead class="bg-dark text-white">
                            <tr>
                                <th style="width: 5%;">ID</th>
                                <th>Nombre del Destino / Unidad / Área</th>
                                <th style="width: 15%;">Jurisdicción (Región/División)</th>
                                <th style="width: 10%;" data-bs-toggle="tooltip" title="Prioridad">
                                    <i class="fa-solid fa-sort-amount-down me-1"></i>Prioridad
                                </th>
                                <th style="width: 10%;">Estado</th>
                                <?php if ($mostrarAcciones): ?>
                                <th style="width: 12%;">Acciones</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Se llena vía AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nuevo/Editar -->
<div class="modal fade" id="modalDestino" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white" style="background-color: #00779e !important;">
                <h5 class="modal-title fw-bold" id="tituloModal">Nuevo Destino</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formDestino">
                    <input type="hidden" name="id_destino" id="id_destino">

                    <!-- Jerarquía PNP -->
                    <div class="p-3 mb-3 border rounded bg-light">
                        <h6 class="fw-bold mb-3 border-bottom pb-2 text-primary" style="font-size: 0.9rem;">
                            <i class="fas fa-sitemap me-2"></i>UBICACIÓN INSTITUCIONAL (OPCIONAL)
                        </h6>
                        <div class="row g-2">
                            <div class="col-md-6 mb-2">
                                <label class="form-label small fw-bold">Región Policial</label>
                                <select class="form-select form-select-sm" name="id_region" id="id_region">
                                    <option value="">- Seleccione Región -</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label small fw-bold">División / Unidad Superior</label>
                                <select class="form-select form-select-sm" name="id_division" id="id_division">
                                    <option value="">- Seleccione División -</option>
                                </select>
                            </div>
                            <div class="col-12 mb-1">
                                <label class="form-label small fw-bold">Vincular a Unidad de Matriz (Opcional)</label>
                                <select class="form-select form-select-sm" name="id_subunidad" id="id_subunidad">
                                    <option value="">- Seleccione Sub-Unidad -</option>
                                </select>
                                <small class="text-muted" style="font-size: 0.8rem;">Si selecciona una, el nombre se llenará automáticamente.</small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre del Destino (Oficina/Área/Unidad)</label>
                        <input type="text" class="form-control" name="nombre_destino" id="nombre_destino"
                            placeholder="EJ. ÁREA DE ABASTECIMIENTO" required oninput="this.value = this.value.toUpperCase()">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            Orden / Prioridad
                            <i class="fas fa-circle-info ms-1 text-muted" data-bs-toggle="tooltip"
                                title="Números menores aparecen primero en la lista. Ej: 1=Primera opción, 2=Segunda, etc. Use 999 para opciones sin prioridad"
                                style="font-size: 0.9rem; cursor: help;"></i>
                        </label>
                        <input type="number" class="form-control" name="orden" id="orden" placeholder="999" value="999"
                            min="1" max="9999">
                        <small class="text-muted">
                            <i class="fas fa-lightbulb me-1"></i>1-10 = Alta prioridad, 999 = Sin prioridad
                        </small>
                    </div>

                    <!-- Campo oculto por defecto, se muestra al editar -->
                    <div class="mb-3 form-check form-switch" id="divActivo" style="display:none;">
                        <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1" checked>
                        <label class="form-check-label fw-bold" for="activo">Destino Activo</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary"
                    style="background-color: #00779e !important; border-color: #00779e;"
                    onclick="guardarDestino()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    let tabla;
    const USER_REGION_ID = 2; // Default para LIMA CENTRO según requerimiento

    $(document).ready(function () {
        inicializarComponentes();
        inicializarFiltros();
        cargarTabla();
        inicializarEventosCascada();
    });

    function inicializarComponentes() {
        $('.select2-basic').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });

        // Tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    function inicializarFiltros() {
        // Cargar Regiones en filtros
        cargarRegiones('#filtro_region', function() {
            // Pre-seleccionar Lima Centro (ID 2) si el usuario lo desea o dejar limpio
            // $('#filtro_region').val(USER_REGION_ID).trigger('change');
        });

        $('#filtro_region').on('change', function () {
            const idRegion = $(this).val();
            cargarDivisiones('#filtro_division', idRegion);
            tabla.ajax.reload();
        });

        $('#filtro_division').on('change', function () {
            tabla.ajax.reload();
        });
    }

    function cargarTabla() {
        if ($.fn.DataTable.isDataTable('#tablaDestinos')) {
            $('#tablaDestinos').DataTable().destroy();
        }

        tabla = $('#tablaDestinos').DataTable({
            ajax: {
                url: "../controlador/DestinoControlador.php?op=listar",
                data: function (d) {
                    d.id_region = $('#filtro_region').val();
                    d.id_division = $('#filtro_division').val();
                }
            },
            columns: [
                { data: 'id_destino' },
                { 
                    data: 'nombre_destino',
                    className: 'text-start'
                },
                {
                    data: null,
                    render: function(data) {
                        if (!data.nombre_region) return '<span class="text-muted small">Sin vincular</span>';
                        return `<div class="small">
                                    <span class="badge bg-light text-dark border">${data.nombre_region}</span><br>
                                    <span class="text-muted">${data.nombre_division || ''}</span>
                                </div>`;
                    }
                },
                {
                    data: 'orden',
                    render: function (data) {
                        const orden = data || 999;
                        if (orden < 100) {
                            return `<span class="badge bg-warning text-dark"><i class="fa-solid fa-star"></i> ${orden}</span>`;
                        }
                        return `<span class="badge bg-secondary">${orden}</span>`;
                    }
                },
                {
                    data: 'activo',
                    render: function (data) {
                        return data == 1 
                            ? '<span class="badge bg-success">Activo</span>' 
                            : '<span class="badge bg-danger">Inactivo</span>';
                    }
                },
                {
                    data: null,
                    visible: <?= $mostrarAcciones ? 'true' : 'false' ?>,
                    render: function (data) {
                        let btns = '';
                        <?php if ($pEditar): ?>
                        btns += `<button class="btn btn-sm btn-primary me-1" onclick="editar(${data.id_destino})" title="Editar"><i class="fa-solid fa-pen"></i></button>`;
                        <?php endif; ?>
                        <?php if ($pEliminar): ?>
                        btns += `<button class="btn btn-sm btn-danger" onclick="eliminar(${data.id_destino})" title="Eliminar"><i class="fa-solid fa-trash"></i></button>`;
                        <?php endif; ?>
                        return `<div class="d-flex justify-content-center">${btns}</div>`;
                    }
                }
            ],
            language: {
                url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
            },
            order: [[3, "asc"], [1, "asc"]],
            responsive: true,
            pageLength: 10
        });
    }

    function inicializarEventosCascada() {
        // Modal Eventos
        $('#id_region').on('change', function () {
            cargarDivisiones('#id_division', $(this).val());
            $('#id_subunidad').html('<option value="">- Seleccione Sub-Unidad -</option>');
        });

        $('#id_division').on('change', function () {
            cargarSubUnidades('#id_subunidad', $(this).val());
        });

        $('#id_subunidad').on('change', function () {
            const nombre = $(this).find('option:selected').text();
            if ($(this).val()) {
                $('#nombre_destino').val(nombre);
            }
        });
    }

    // --- Helpers de Carga (Matrix) ---

    function cargarRegiones(selector, callback = null) {
        $.get("../controlador/UnidadesPoliciales.php?op=listar&tipo=region", function (res) {
            if (res.status) {
                let html = '<option value="">' + (selector.includes('filtro') ? 'Todas las Regiones' : '- Seleccione Región -') + '</option>';
                res.data.forEach(r => {
                    html += `<option value="${r.id_region}">${r.nombre_region}</option>`;
                });
                $(selector).html(html);
                if (callback) callback();
            }
        });
    }

    function cargarDivisiones(selector, idRegion, valorSeleccionado = null, callback = null) {
        if (!idRegion) {
            $(selector).html('<option value="">' + (selector.includes('filtro') ? 'Todas las Divisiones' : '- Seleccione División -') + '</option>');
            return;
        }
        $.get(`../controlador/UnidadesPoliciales.php?op=listar&tipo=division&id_region=${idRegion}`, function (res) {
            if (res.status) {
                let html = '<option value="">' + (selector.includes('filtro') ? 'Todas las Divisiones' : '- Seleccione División -') + '</option>';
                res.data.forEach(d => {
                    const selected = (valorSeleccionado && d.id_division == valorSeleccionado) ? 'selected' : '';
                    html += `<option value="${d.id_division}" ${selected}>${d.nombre_division}</option>`;
                });
                $(selector).html(html);
                if (callback) callback();
            }
        });
    }

    function cargarSubUnidades(selector, idDivision, valorSeleccionado = null) {
        if (!idDivision) {
            $(selector).html('<option value="">- Seleccione Sub-Unidad -</option>');
            return;
        }
        $.get(`../controlador/UnidadesPoliciales.php?op=listar&tipo=subunidad&id_division=${idDivision}`, function (res) {
            if (res.status) {
                let html = '<option value="">- Seleccione Sub-Unidad -</option>';
                res.data.forEach(s => {
                    const selected = (valorSeleccionado && s.id_subunidad == valorSeleccionado) ? 'selected' : '';
                    html += `<option value="${s.id_subunidad}" ${selected}>${s.nombre_subunidad}</option>`;
                });
                $(selector).html(html);
            }
        });
    }

    function limpiarFiltros() {
        $('#filtro_region').val('').trigger('change');
    }

    // --- CRUD ---

    function abrirModal() {
        $('#formDestino')[0].reset();
        $('#id_destino').val('');
        $('#divActivo').hide();
        $('#tituloModal').text('Nuevo Destino');
        
        cargarRegiones('#id_region', function() {
            // Predeterminar región de Lima Centro para facilitar
            $('#id_region').val(USER_REGION_ID).trigger('change');
        });

        $('#modalDestino').modal('show');
    }

    function editar(id) {
        $.post("../controlador/DestinoControlador.php?op=obtener", { id: id }, function (resp) {
            // Asumiendo que el controlador devuelve el objeto directamente
            const data = typeof resp === 'string' ? JSON.parse(resp) : resp;
            
            $('#id_destino').val(data.id_destino);
            $('#nombre_destino').val(data.nombre_destino);
            $('#orden').val(data.orden || 999);
            $('#activo').prop('checked', data.activo == 1);
            $('#divActivo').show();
            $('#tituloModal').text('Editar Destino');

            // Cargar jerarquía cascada
            cargarRegiones('#id_region', function() {
                if (data.id_region) {
                    $('#id_region').val(data.id_region);
                    cargarDivisiones('#id_division', data.id_region, data.id_division, function() {
                        if (data.id_subunidad) {
                            cargarSubUnidades('#id_subunidad', data.id_division, data.id_subunidad);
                        }
                    });
                } else {
                    $('#id_division').html('<option value="">- Seleccione División -</option>');
                    $('#id_subunidad').html('<option value="">- Seleccione Sub-Unidad -</option>');
                }
            });

            $('#modalDestino').modal('show');
        });
    }

    function guardarDestino() {
        const formData = new FormData(document.getElementById('formDestino'));
        if (!$('#activo').is(':checked')) formData.append('activo', 0);

        if (!formData.get('nombre_destino').trim()) {
            Swal.fire('Error', 'El nombre es obligatorio', 'warning');
            return;
        }

        $.ajax({
            url: "../controlador/DestinoControlador.php?op=guardar",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {
                try {
                    const res = typeof response === 'string' ? JSON.parse(response) : response;
                    if (res.status) {
                        Swal.fire('¡Éxito!', res.msg, 'success');
                        $('#modalDestino').modal('hide');
                        tabla.ajax.reload();
                    } else {
                        Swal.fire('Error', res.msg, 'error');
                    }
                } catch (e) {
                    console.error(response);
                    Swal.fire('Error', 'Error en el servidor', 'error');
                }
            }
        });
    }

    function eliminar(id) {
        Swal.fire({
            title: '¿Eliminar destino?',
            text: "Esta acción no se puede deshacer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post("../controlador/DestinoControlador.php?op=eliminar", { id: id }, function (response) {
                    const res = typeof response === 'string' ? JSON.parse(response) : response;
                    if (res.status) {
                        Swal.fire('Eliminado', res.msg, 'success');
                        tabla.ajax.reload();
                    } else {
                        Swal.fire('Error', res.msg, 'error');
                    }
                });
            }
        });
    }
</script>

<?php include 'layout/footer.php'; ?>
</body>
</html>