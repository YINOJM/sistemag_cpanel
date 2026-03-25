/**
 * JavaScript para el Módulo de Unidades Policiales
 * Sistema Integrado de Gestión - UE009 DIRTEPOL LIMA
 */

let ubicaciones = [];

$(document).ready(function () {
    // Inicializar módulo
    inicializarModulo();
    cargarEstadisticas();
    inicializarTablas();
    inicializarEventos();
});

// ==========================================
// INICIALIZACIÓN
// ==========================================

function inicializarModulo() {
    console.log('🚀 Módulo de Unidades Policiales iniciado');

    // Cargar datos iniciales
    cargarRegiones();
    cargarTiposUnidad();
    cargarUbicaciones();
}

function inicializarTablas() {
    // Tabla Jerárquica
    window.tablaJerarquia = $('#tablaJerarquia').DataTable({
        ajax: {
            url: '../controlador/UnidadesPoliciales.php?op=listar_jerarquia',
            dataSrc: 'data'
        },
        columns: [
            { data: 'departamento', defaultContent: '-' },
            { data: 'provincia', defaultContent: '-' },
            { data: 'distrito', defaultContent: '-' },
            { data: 'nombre_region' },
            { data: 'nombre_division' },
            { data: 'nombre_subunidad' },
            {
                data: 'tipo_unidad',
                render: function (data, type, row) {
                    return getBadgeTipo(data, row.nombre_subunidad);
                }
            },
            {
                data: 'estado',
                className: 'text-center',
                render: function (data) {
                    return getBadgeEstado(data);
                }
            },
            {
                data: null,
                render: function (data) {
                    let btns = `
                        <button class="btn btn-sm btn-info" onclick="verDetalle(${data.id_subunidad}, 'subunidad')" title="Ver detalle">
                            <i class="fas fa-eye"></i>
                        </button>
                    `;

                    if (typeof USER_PERMISSIONS !== 'undefined' && USER_PERMISSIONS.EDITAR) {
                        btns += `
                            <button class="btn btn-sm btn-warning" onclick="editarSubUnidad(${data.id_subunidad})" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                        `;
                    }

                    if (typeof USER_PERMISSIONS !== 'undefined' && USER_PERMISSIONS.ELIMINAR) {
                        btns += `
                            <button class="btn btn-sm btn-danger" onclick="eliminar(${data.id_subunidad}, 'subunidad')" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        `;
                    }

                    return `<div class="action-buttons">${btns}</div>`;
                }
            }
        ],
        language: {
            url: 'Spanish.json'
        },
        responsive: true,
        order: [[3, 'asc'], [4, 'asc'], [5, 'asc']],
        dom: 'lrtip' // Ocultar input de búsqueda por defecto ("f"), mantener length ("l")
    });

    // Tabla Regiones
    window.tablaRegiones = $('#tablaRegiones').DataTable({
        ajax: {
            url: '../controlador/UnidadesPoliciales.php?op=listar&tipo=region',
            dataSrc: 'data'
        },
        columns: [
            { data: 'id_region' },
            { data: 'nombre_region' },
            { data: 'descripcion', defaultContent: '-' },
            {
                data: null,
                render: function (data) {
                    if (typeof USER_PERMISSIONS !== 'undefined' && (USER_PERMISSIONS.EDITAR || USER_PERMISSIONS.ELIMINAR)) {
                        let btns = '';
                        if (USER_PERMISSIONS.EDITAR) {
                            btns += `
                                <button class="btn btn-sm btn-warning" onclick="editarRegion(${data.id_region})" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                            `;
                        }
                        if (USER_PERMISSIONS.ELIMINAR) {
                            btns += `
                                <button class="btn btn-sm btn-danger" onclick="eliminar(${data.id_region}, 'region')" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            `;
                        }
                        return `<div class="action-buttons">${btns}</div>`;
                    }
                    return '<div class="action-buttons"><span class="text-muted">-</span></div>';
                }
            }
        ],
        language: {
            url: 'Spanish.json'
        },
        responsive: true
    });

    // Tabla Divisiones
    window.tablaDivisiones = $('#tablaDivisiones').DataTable({
        ajax: {
            url: '../controlador/UnidadesPoliciales.php?op=listar&tipo=division',
            dataSrc: 'data'
        },
        columns: [
            { data: 'id_division' },
            { data: 'nombre_region' },
            { data: 'nombre_division' },
            {
                data: 'total_subunidades',
                render: function (data, type, row) {
                    const count = data || 0;
                    if (count > 0) {
                        return `<span class="badge bg-secondary rounded-pill" 
                                      style="cursor: pointer;" 
                                      onclick="verComisariasDeDivision(${row.id_division}, '${row.nombre_division}')"
                                      title="Ver lista de comisarías">
                                      ${count}
                                </span>`;
                    }
                    return `<span class="badge bg-light text-dark rounded-pill">${count}</span>`;
                }
            },
            {
                data: null,
                render: function (data) {
                    if (typeof USER_PERMISSIONS !== 'undefined' && (USER_PERMISSIONS.EDITAR || USER_PERMISSIONS.ELIMINAR)) {
                        let btns = '';
                        if (USER_PERMISSIONS.EDITAR) {
                            btns += `
                                <button class="btn btn-sm btn-warning" onclick="editarDivision(${data.id_division})" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                            `;
                        }
                        if (USER_PERMISSIONS.ELIMINAR) {
                            btns += `
                                <button class="btn btn-sm btn-danger" onclick="eliminar(${data.id_division}, 'division')" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            `;
                        }
                        return `<div class="action-buttons">${btns}</div>`;
                    }
                    return '<div class="action-buttons"><span class="text-muted">-</span></div>';
                }
            }
        ],
        language: {
            url: 'Spanish.json'
        },
        responsive: true
    });

    // Tabla Distritos
    window.tablaDistritos = $('#tablaDistritos').DataTable({
        ajax: {
            url: '../controlador/UnidadesPoliciales.php?op=listar_distritos',
            dataSrc: 'data'
        },
        columns: [
            { data: 'departamento' },
            { data: 'provincia' },
            { data: 'distrito' },
            {
                data: 'total_comisarias',
                render: function (data) {
                    return `<span class="badge bg-primary rounded-pill">${data}</span>`;
                }
            }
        ],
        language: {
            url: 'Spanish.json'
        },
        responsive: true
    });

    // Tabla Sub-Unidades
    window.tablaSubUnidades = $('#tablaSubUnidades').DataTable({
        ajax: {
            url: '../controlador/UnidadesPoliciales.php?op=listar&tipo=subunidad',
            dataSrc: 'data'
        },
        columns: [
            { data: 'id_subunidad' },
            { data: 'nombre_division' },
            { data: 'distrito', defaultContent: '-' },
            { data: 'nombre_subunidad' },
            {
                data: 'tipo_unidad',
                render: function (data) {
                    return getBadgeTipo(data);
                }
            },
            {
                data: null,
                render: function (data) {
                    if (typeof USER_PERMISSIONS !== 'undefined' && (USER_PERMISSIONS.EDITAR || USER_PERMISSIONS.ELIMINAR)) {
                        let btns = '';
                        if (USER_PERMISSIONS.EDITAR) {
                            btns += `
                                <button class="btn btn-sm btn-warning" onclick="editarSubUnidad(${data.id_subunidad})" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                            `;
                        }
                        if (USER_PERMISSIONS.ELIMINAR) {
                            btns += `
                                <button class="btn btn-sm btn-danger" onclick="eliminar(${data.id_subunidad}, 'subunidad')" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            `;
                        }
                        return `<div class="action-buttons">${btns}</div>`;
                    }
                    return '<div class="action-buttons"><span class="text-muted">-</span></div>';
                }
            }
        ],
        language: {
            url: 'Spanish.json'
        },
        responsive: true
    });
}

function inicializarEventos() {
    // Búsqueda global
    $('#busquedaGlobal').on('keyup', function () {
        tablaJerarquia.search(this.value).draw();
    });

    // Formulario Región
    $('#formRegion').on('submit', function (e) {
        e.preventDefault();
        guardarRegion();
    });

    // Formulario División
    $('#formDivision').on('submit', function (e) {
        e.preventDefault();
        guardarDivision();
    });

    // Formulario Sub-Unidad
    $('#formSubUnidad').on('submit', function (e) {
        e.preventDefault();
        guardarSubUnidad();
    });

    // Cambio de región en modal de sub-unidad (cascada)
    $('#subunidad_region').on('change', function () {
        const idRegion = $(this).val();
        cargarDivisionesPorRegion(idRegion);
    });

    // Filtros en Vista Jerárquica
    cargarRegionesEnSelect('#filtroRegion');

    $('#filtroRegion').on('change', function () {
        const idRegion = $(this).val();
        const textoRegion = $(this).find('option:selected').text();

        if (idRegion === '') {
            tablaJerarquia.column(3).search('').draw();
        } else {
            tablaJerarquia.column(3).search(`^${textoRegion}$`, true, false).draw();
        }

        cargarDivisionesPorRegionFiltro(idRegion);
    });

    $('#filtroDivision').on('change', function () {
        const textoDivision = $(this).find('option:selected').val();

        if (textoDivision === '') {
            tablaJerarquia.column(4).search('').draw();
        } else {
            tablaJerarquia.column(4).search(`^${textoDivision}$`, true, false).draw();
        }
    });
}

function cargarDivisionesPorRegionFiltro(idRegion) {
    const select = $('#filtroDivision');
    if (!idRegion) {
        select.html('<option value="">Todas las Divisiones</option>');
        return;
    }

    $.ajax({
        url: `../controlador/UnidadesPoliciales.php?op=listar&tipo=division&id_region=${idRegion}`,
        method: 'GET',
        success: function (response) {
            if (response.status) {
                select.html('<option value="">Todas las Divisiones</option>');
                response.data.forEach(division => {
                    select.append(`<option value="${division.nombre_division}">${division.nombre_division}</option>`);
                });
            }
        }
    });
}

// ==========================================
// ESTADÍSTICAS
// ==========================================

function cargarEstadisticas() {
    $.ajax({
        url: '../controlador/UnidadesPoliciales.php?op=listar&tipo=region',
        method: 'GET',
        success: function (response) {
            if (response.status) {
                $('#totalRegiones').text(response.data.length);
            }
        }
    });

    $.ajax({
        url: '../controlador/UnidadesPoliciales.php?op=listar&tipo=division',
        method: 'GET',
        success: function (response) {
            if (response.status) {
                $('#totalDivisiones').text(response.data.length);
            }
        }
    });

    $.ajax({
        url: '../controlador/UnidadesPoliciales.php?op=listar&tipo=subunidad',
        method: 'GET',
        success: function (response) {
            if (response.status) {
                const total = response.data.length;

                // Contar comisarías: tipos A, B, C o que contengan la palabra 'COMISARIA' en el nombre
                const comisarias = response.data.filter(u => {
                    const tipo = u.tipo_unidad ? u.tipo_unidad.toUpperCase() : '';
                    const nombre = u.nombre_subunidad ? u.nombre_subunidad.toUpperCase() : '';
                    return ['A', 'B', 'C'].includes(tipo) || nombre.includes('COMISAR');
                }).length;

                const otros = total - comisarias;

                $('#totalComisarias').text(comisarias);
                $('#totalOtros').text(otros);
            }
        }
    });

    // Cargar total de distritos
    $.ajax({
        url: '../controlador/UnidadesPoliciales.php?op=listar_distritos',
        method: 'GET',
        success: function (response) {
            if (response.status) {
                // response.data es un array de distritos únicos
                $('#totalDistritos').text(response.data.length);
            }
        }
    });
}

// ==========================================
// MODALES - ABRIR
// ==========================================

function abrirModalRegion() {
    $('#formRegion')[0].reset();
    $('#region_id').val('');
    $('#tituloModalRegion').text('Nueva Región Policial');
    $('#modalRegion').modal('show');
}

function abrirModalDivision() {
    $('#formDivision')[0].reset();
    $('#division_id').val('');
    $('#tituloModalDivision').text('Nueva División Policial');
    cargarRegionesEnSelect('#division_region');
    $('#modalDivision').modal('show');
}

function abrirModalSubUnidad() {
    $('#formSubUnidad')[0].reset();
    $('#subunidad_id').val('');
    $('#tituloModalSubUnidad').text('Nueva Sub-Unidad Policial');
    cargarRegionesEnSelect('#subunidad_region');
    $('#subunidad_division').html('<option value="">Primero seleccione una región</option>');
    $('#modalSubUnidad').modal('show');
}

// ==========================================
// EDITAR
// ==========================================

function editarRegion(id) {
    $.ajax({
        url: '../controlador/UnidadesPoliciales.php?op=obtener&tipo=region',
        method: 'POST',
        data: { id: id },
        success: function (response) {
            if (response.status && response.data) {
                const data = response.data;
                $('#region_id').val(data.id_region);
                $('#region_nombre').val(data.nombre_region);
                $('#region_codigo').val(data.codigo_region);
                $('#region_descripcion').val(data.descripcion);
                $('#tituloModalRegion').text('Editar Región Policial');
                $('#modalRegion').modal('show');
            }
        }
    });
}

function editarDivision(id) {
    $.ajax({
        url: '../controlador/UnidadesPoliciales.php?op=obtener&tipo=division',
        method: 'POST',
        data: { id: id },
        success: function (response) {
            if (response.status && response.data) {
                const data = response.data;
                cargarRegionesEnSelect('#division_region', data.id_region);

                $('#division_id').val(data.id_division);
                $('#division_nombre').val(data.nombre_division);
                $('#division_codigo').val(data.codigo_division);
                $('#division_telefono').val(data.telefono);
                $('#division_email').val(data.email);
                $('#division_direccion').val(data.direccion);
                $('#division_descripcion').val(data.descripcion);
                $('#tituloModalDivision').text('Editar División Policial');
                $('#modalDivision').modal('show');
            }
        }
    });
}

function editarSubUnidad(id) {
    $.ajax({
        url: '../controlador/UnidadesPoliciales.php?op=obtener&tipo=subunidad',
        method: 'POST',
        data: { id: id },
        success: function (response) {
            if (response.status && response.data) {
                const data = response.data;

                // Cargar región y luego división
                cargarRegionesEnSelect('#subunidad_region', data.id_region, function () {
                    cargarDivisionesPorRegion(data.id_region, data.id_division);
                });

                $('#subunidad_id').val(data.id_subunidad);
                $('#subunidad_nombre').val(data.nombre_subunidad);
                $('#subunidad_tipo').val(data.tipo_unidad);
                $('#subunidad_estado').val(data.estado !== null ? data.estado : 1);
                // Cargar ubicaciones
                cargarDepartamentos(data.departamento);
                cargarProvincias(data.departamento, data.provincia);
                cargarDistritos(data.departamento, data.provincia, data.distrito);
                $('#tituloModalSubUnidad').text('Editar Sub-Unidad Policial');
                $('#modalSubUnidad').modal('show');
            }
        }
    });
}

// ==========================================
// GUARDAR
// ==========================================

function guardarRegion() {
    const formData = $('#formRegion').serialize();
    const tipo = 'region';

    $.ajax({
        url: `../controlador/UnidadesPoliciales.php?op=guardar&tipo=${tipo}`,
        method: 'POST',
        data: formData,
        success: function (response) {
            if (response.status) {
                Swal.fire('¡Éxito!', response.msg, 'success');
                $('#modalRegion').modal('hide');
                tablaRegiones.ajax.reload();
                cargarEstadisticas();
            } else {
                Swal.fire('Error', response.msg, 'error');
            }
        },
        error: function () {
            Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
        }
    });
}

function guardarDivision() {
    const formData = $('#formDivision').serialize();
    const tipo = 'division';

    $.ajax({
        url: `../controlador/UnidadesPoliciales.php?op=guardar&tipo=${tipo}`,
        method: 'POST',
        data: formData,
        success: function (response) {
            if (response.status) {
                Swal.fire('¡Éxito!', response.msg, 'success');
                $('#modalDivision').modal('hide');
                tablaDivisiones.ajax.reload();
                cargarEstadisticas();
            } else {
                Swal.fire('Error', response.msg, 'error');
            }
        },
        error: function () {
            Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
        }
    });
}

function guardarSubUnidad() {
    const formData = $('#formSubUnidad').serialize();
    const tipo = 'subunidad';

    $.ajax({
        url: `../controlador/UnidadesPoliciales.php?op=guardar&tipo=${tipo}`,
        method: 'POST',
        data: formData,
        success: function (response) {
            if (response.status) {
                Swal.fire('¡Éxito!', response.msg, 'success');
                $('#modalSubUnidad').modal('hide');
                tablaSubUnidades.ajax.reload();
                tablaJerarquia.ajax.reload();
                cargarEstadisticas();
            } else {
                Swal.fire('Error', response.msg, 'error');
            }
        },
        error: function () {
            Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
        }
    });
}

// ==========================================
// ELIMINAR
// ==========================================

function eliminar(id, tipo) {
    const nombres = {
        'region': 'región',
        'division': 'división',
        'subunidad': 'sub-unidad'
    };

    Swal.fire({
        title: '¿Está seguro?',
        text: `Se eliminará esta ${nombres[tipo]}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `../controlador/UnidadesPoliciales.php?op=eliminar&tipo=${tipo}`,
                method: 'POST',
                data: { id: id },
                success: function (response) {
                    if (response.status) {
                        Swal.fire('¡Eliminado!', response.msg, 'success');

                        // Recargar tablas correspondientes
                        if (tipo === 'region') tablaRegiones.ajax.reload();
                        if (tipo === 'division') tablaDivisiones.ajax.reload();
                        if (tipo === 'subunidad') {
                            tablaSubUnidades.ajax.reload();
                            tablaJerarquia.ajax.reload();
                        }

                        cargarEstadisticas();
                    } else {
                        Swal.fire('Error', response.msg, 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
                }
            });
        }
    });
}

// ==========================================
// FUNCIONES AUXILIARES
// ==========================================

function cargarRegiones() {
    // Esta función se llama al inicio para tener las regiones disponibles
    $.ajax({
        url: '../controlador/UnidadesPoliciales.php?op=listar&tipo=region',
        method: 'GET',
        success: function (response) {
            if (response.status) {
                window.regionesData = response.data;
            }
        }
    });
}

function cargarTiposUnidad() {
    $.ajax({
        url: '../controlador/UnidadesPoliciales.php?op=listar_tipos',
        method: 'GET',
        success: function (response) {
            if (response.status) {
                const select = $('#subunidad_tipo');
                select.html('<option value="">Seleccione</option>');
                response.data.forEach(tipo => {
                    select.append(`<option value="${tipo.nombre_tipo}">${tipo.nombre_tipo}</option>`);
                });
            }
        }
    });
}

function cargarRegionesEnSelect(selector, valorSeleccionado = null, callback = null) {
    $.ajax({
        url: '../controlador/UnidadesPoliciales.php?op=listar&tipo=region',
        method: 'GET',
        success: function (response) {
            if (response.status) {
                const select = $(selector);
                select.html('<option value="">Seleccione una región</option>');
                response.data.forEach(region => {
                    const selected = (valorSeleccionado && region.id_region == valorSeleccionado) ? 'selected' : '';
                    select.append(`<option value="${region.id_region}" ${selected}>${region.nombre_region}</option>`);
                });

                if (callback) callback();
            }
        }
    });
}

function cargarDivisionesPorRegion(idRegion, valorSeleccionado = null) {
    if (!idRegion) {
        $('#subunidad_division').html('<option value="">Primero seleccione una región</option>');
        return;
    }

    $.ajax({
        url: `../controlador/UnidadesPoliciales.php?op=listar&tipo=division&id_region=${idRegion}`,
        method: 'GET',
        success: function (response) {
            if (response.status) {
                const select = $('#subunidad_division');
                select.html('<option value="">Seleccione una división</option>');
                response.data.forEach(division => {
                    const selected = (valorSeleccionado && division.id_division == valorSeleccionado) ? 'selected' : '';
                    select.append(`<option value="${division.id_division}" ${selected}>${division.nombre_division}</option>`);
                });
            }
        }
    });
}

function getBadgeTipo(tipo, nombreSubunidad = '') {
    const upperTipo = tipo ? tipo.toUpperCase().trim() : '';

    // Si el tipo es A, B o C, mostramos solo la letra
    if (['A', 'B', 'C'].includes(upperTipo)) {
        return `<span class="badge-tipo badge-comisaria" style="min-width: 30px; text-align: center;">${upperTipo}</span>`;
    }

    // Si tiene un tipo específico grabado (como JEFATURA, DEPARTAMENTO, etc.)
    if (upperTipo !== '') {
        const badges = {
            'JEFATURA': 'badge-jefatura',
            'DEPARTAMENTO': 'badge-departamento'
        };
        const badgeClass = badges[upperTipo] || 'badge-default';
        return `<span class="badge-tipo ${badgeClass}">${tipo}</span>`;
    }

    // Si no tiene tipo, lo dejamos vacío según lo solicitado
    return '';
}

function verDetalle(id, tipo) {
    $.ajax({
        url: '../controlador/UnidadesPoliciales.php?op=obtener',
        type: 'POST',
        data: { id: id, tipo: tipo },
        dataType: 'json',
        success: function (response) {
            if (response.status) {
                const data = response.data;

                // Llenar campos del modal
                $('#detalleNombre').text(data.nombre_subunidad);
                $('#detalleTipo').text(getBadgeTipo(data.tipo_unidad)); // Reutilizamos la función del badge
                $('#detalleTipo').html(getBadgeTipo(data.tipo_unidad)); // Usamos HTML porque getBadgeTipo devuelve un span

                $('#detalleRegion').text(data.nombre_region || '-');
                $('#detalleDivision').text(data.nombre_division || '-');

                $('#detalleDepartamento').text(data.departamento || '-');
                $('#detalleProvincia').text(data.provincia || '-');
                $('#detalleDistrito').text(data.distrito || '-');

                // Mostrar modal
                $('#modalDetalleSubUnidad').modal('show');
            } else {
                Swal.fire('Error', 'No se pudo cargar la información: ' + response.msg, 'error');
            }
        },
        error: function () {
            Swal.fire('Error', 'Error de conexión al obtener detalles', 'error');
        }
    });
}

function verComisariasDeDivision(idDivision, nombreDivision) {
    // Actualizar título
    $('#tituloModalDetalle').text(`Comisarías: ${nombreDivision}`);

    // Mostrar loading
    $('#tablaDetalleBody').html('<tr><td colspan="4" class="text-center">Cargando datos...</td></tr>');
    $('#modalDetalleComisarias').modal('show');

    // Solicitar datos
    $.ajax({
        url: `../controlador/UnidadesPoliciales.php?op=listar&tipo=subunidad&id_division=${idDivision}`,
        method: 'GET',
        success: function (response) {
            if (response.status) {
                const data = response.data;
                let html = '';

                if (data.length > 0) {
                    data.forEach((item, index) => {
                        html += `
                            <tr>
                                <td><strong>${index + 1}</strong></td>
                                <td>${item.nombre_subunidad}</td>
                                <td>${getBadgeTipo(item.tipo_unidad)}</td>
                                <td>${item.distrito || '-'}</td>
                                <td>${getBadgeEstado(item.estado)}</td>
                            </tr>
                        `;
                    });
                } else {
                    html = '<tr><td colspan="5" class="text-center">No hay comisarías registradas en esta división</td></tr>';
                }

                $('#tablaDetalleBody').html(html);
            } else {
                $('#tablaDetalleBody').html('<tr><td colspan="5" class="text-center text-danger">Error al cargar datos</td></tr>');
            }
        },
        error: function () {
            $('#tablaDetalleBody').html('<tr><td colspan="4" class="text-center text-danger">Error de conexión</td></tr>');
        }
    });
}

// ==========================================
// LIMPIAR TODOS LOS DATOS
// ==========================================

function limpiarTodosDatos() {
    Swal.fire({
        title: '⚠️ ¿Estás completamente seguro?',
        html: `
            <p><strong>Esta acción eliminará TODOS los datos del módulo:</strong></p>
            <ul style="text-align: left; margin: 20px auto; max-width: 400px;">
                <li>Todas las Sub-Unidades</li>
                <li>Todas las Divisiones</li>
                <li>Todas las Regiones</li>
            </ul>
            <p style="color: #d33; font-weight: bold;">⚠️ ESTA ACCIÓN NO SE PUEDE DESHACER</p>
            <p>Escribe <strong>"LIMPIAR"</strong> para confirmar:</p>
        `,
        input: 'text',
        inputPlaceholder: 'Escribe LIMPIAR',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar todo',
        cancelButtonText: 'Cancelar',
        preConfirm: (value) => {
            if (value !== 'LIMPIAR') {
                Swal.showValidationMessage('Debes escribir "LIMPIAR" para confirmar');
                return false;
            }
            return true;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Limpiando datos...',
                text: 'Por favor espera',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: '../controlador/UnidadesPoliciales.php?op=limpiar_todos',
                method: 'POST',
                success: function (response) {
                    if (response.status) {
                        Swal.fire({
                            title: '¡Limpieza Completada!',
                            html: `
                                <p><strong>${response.msg}</strong></p>
                                <ul style="text-align: left; margin: 20px auto; max-width: 300px;">
                                    <li>Sub-Unidades eliminadas: ${response.subunidades || 0}</li>
                                    <li>Divisiones eliminadas: ${response.divisiones || 0}</li>
                                    <li>Regiones eliminadas: ${response.regiones || 0}</li>
                                </ul>
                            `,
                            icon: 'success'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', response.msg, 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
                }
            });
        }
    });
}


// ==========================================
// CASCADING DROPDOWNS (UBIGEO)
// ==========================================

function cargarUbicaciones() {
    $.ajax({
        url: '../controlador/UnidadesPoliciales.php?op=listar_ubicaciones',
        method: 'GET',
        success: function (response) {
            if (response.status) {
                ubicaciones = response.data;
                configurarSelectsUbicacion();
            }
        }
    });
}

function configurarSelectsUbicacion() {
    // Evento al abrir modal subunidad: cargar departamentos
    $('#modalSubUnidad').on('show.bs.modal', function () {
        if ($('#subunidad_id').val() === '') { // Solo si es nuevo
            cargarDepartamentos();
            $('#subunidad_provincia').html('<option value="">Seleccione</option>');
            $('#subunidad_distrito').html('<option value="">Seleccione</option>');
        }
    });

    // Cambio en Departamento
    $('#subunidad_departamento').on('change', function () {
        const dep = $(this).val();
        cargarProvincias(dep);
        $('#subunidad_distrito').html('<option value="">Seleccione</option>');
    });

    // Cambio en Provincia
    $('#subunidad_provincia').on('change', function () {
        const dep = $('#subunidad_departamento').val();
        const prov = $(this).val();
        cargarDistritos(dep, prov);
    });
}

function cargarDepartamentos(selected = null) {
    const departamentos = [...new Set(ubicaciones.map(item => item.departamento))].sort();
    let html = '<option value="">Seleccione</option>';
    departamentos.forEach(dep => {
        const selectedAttr = dep === selected ? 'selected' : '';
        html += `<option value="${dep}" ${selectedAttr}>${dep}</option>`;
    });
    $('#subunidad_departamento').html(html);
}

function cargarProvincias(departamento, selected = null) {
    if (!departamento) {
        $('#subunidad_provincia').html('<option value="">Seleccione</option>');
        return;
    }
    const provincias = [...new Set(ubicaciones
        .filter(item => item.departamento === departamento)
        .map(item => item.provincia))].sort();

    let html = '<option value="">Seleccione</option>';
    provincias.forEach(prov => {
        const selectedAttr = prov === selected ? 'selected' : '';
        html += `<option value="${prov}" ${selectedAttr}>${prov}</option>`;
    });
    $('#subunidad_provincia').html(html);
}

function cargarDistritos(departamento, provincia, selected = null) {
    if (!departamento || !provincia) {
        $('#subunidad_distrito').html('<option value="">Seleccione</option>');
        return;
    }
    const distritos = [...new Set(ubicaciones
        .filter(item => item.departamento === departamento && item.provincia === provincia)
        .map(item => item.distrito))].sort();

    let html = '<option value="">Seleccione</option>';
    distritos.forEach(dist => {
        const selectedAttr = dist === selected ? 'selected' : '';
        html += `<option value="${dist}" ${selectedAttr}>${dist}</option>`;
    });
    $('#subunidad_distrito').html(html);
}

// Helper para badge de estado
function getBadgeEstado(estado) {
    if (estado === null || estado === undefined || estado == 1 || estado === '1' || estado === 'Activo') {
        return '<span class="badge bg-success">Activo</span>';
    } else {
        return '<span class="badge bg-danger">Inactivo</span>';
    }
}
