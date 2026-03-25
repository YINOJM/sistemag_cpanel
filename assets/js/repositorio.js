// assets/js/repositorio.js
$(document).ready(function () {
    let vistaActual = 'grid'; // 'grid' o 'lista'
    let documentos = [];

    // Inicializar
    cargarEstadisticas();
    cargarAnios();
    cargarDocumentos();
    verificarAlmacenamiento(); // Nuevo check

    // Event Listeners
    $('#btnSubirArchivo').on('click', function () {
        $('#modalSubirArchivo').modal('show');
    });

    $('#filtroAnio, #filtroCategoria').on('change', function () {
        cargarDocumentos();
    });

    $('#busqueda').on('keyup', debounce(function () {
        cargarDocumentos();
    }, 500));

    $('#btnLimpiarFiltros').on('click', function () {
        $('#filtroAnio').val('');
        $('#filtroCategoria').val('');
        $('#busqueda').val('');
        cargarDocumentos();
    });

    $('#vistaGrid').on('click', function () {
        vistaActual = 'grid';
        $('#vistaGrid').addClass('active');
        $('#vistaLista').removeClass('active');
        $('#documentosGrid').show();
        $('#documentosLista').hide();
    });

    $('#vistaLista').on('click', function () {
        vistaActual = 'lista';
        $('#vistaLista').addClass('active');
        $('#vistaGrid').removeClass('active');
        $('#documentosGrid').hide();
        $('#documentosLista').show();
    });

    // Subir archivo
    $('#formSubirArchivo').on('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        const btn = $('#btnGuardarArchivo');
        btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Subiendo...');

        $.ajax({
            url: '../controlador/RepositorioControlador.php?op=subir',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (response) {
                btn.prop('disabled', false).html('<i class="fa-solid fa-upload me-1"></i> Subir');

                if (response.status) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Documento Subido!',
                        text: response.msg,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    $('#modalSubirArchivo').modal('hide');
                    $('#formSubirArchivo')[0].reset();
                    cargarDocumentos();
                    cargarEstadisticas();
                } else {
                    Swal.fire('Error', response.msg, 'error');
                }
            },
            error: function () {
                btn.prop('disabled', false).html('<i class="fa-solid fa-upload me-1"></i> Subir');
                Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
            }
        });
    });

    // Editar documento
    $('#formEditarDocumento').on('submit', function (e) {
        e.preventDefault();

        const id = $('#editarId').val();
        const categoria = $('#editarCategoria').val();
        const descripcion = $('#editarDescripcion').val();

        $.ajax({
            url: '../controlador/RepositorioControlador.php?op=actualizar',
            method: 'POST',
            data: {
                id: id,
                categoria: categoria,
                descripcion: descripcion
            },
            dataType: 'json',
            success: function (response) {
                if (response.status) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Actualizado!',
                        text: response.msg,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    $('#modalEditarDocumento').modal('hide');
                    cargarDocumentos();
                } else {
                    Swal.fire('Error', response.msg, 'error');
                }
            },
            error: function () {
                Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
            }
        });
    });

    // Funciones
    function cargarEstadisticas() {
        $.ajax({
            url: '../controlador/RepositorioControlador.php?op=estadisticas',
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.status && response.data) {
                    const data = response.data;
                    $('#totalDocumentos').text(data.total_documentos || 0);
                    $('#totalAnios').text(data.total_anios || 0);
                    $('#totalCategorias').text(data.total_categorias || 0);

                    const espacioMB = ((data.espacio_total || 0) / 1048576).toFixed(2);
                    $('#espacioTotal').text(espacioMB + ' MB');
                }
            }
        });
    }

    function cargarAnios() {
        $.ajax({
            url: '../controlador/RepositorioControlador.php?op=obtenerAnios',
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.status && response.data) {
                    const select = $('#filtroAnio');
                    response.data.forEach(function (item) {
                        select.append(`<option value="${item.anio}">${item.anio}</option>`);
                    });
                }
            }
        });
    }

    function cargarDocumentos() {
        const anio = $('#filtroAnio').val();
        const categoria = $('#filtroCategoria').val();
        const busqueda = $('#busqueda').val();

        let url = '../controlador/RepositorioControlador.php?op=listar';
        if (anio) url += `&anio=${anio}`;
        if (categoria) url += `&categoria=${encodeURIComponent(categoria)}`;
        if (busqueda) url += `&busqueda=${encodeURIComponent(busqueda)}`;

        $.ajax({
            url: url,
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                console.log('Respuesta completa:', response);
                documentos = response.data || [];
                console.log('Total documentos:', documentos.length);

                if (vistaActual === 'grid') {
                    renderizarGrid();
                } else {
                    renderizarLista();
                }
            },
            error: function (xhr, status, error) {
                // Solo mostrar error si es un error real de servidor, no si simplemente no hay datos
                console.error('Error al cargar documentos:', error);
                documentos = [];
                if (vistaActual === 'grid') {
                    renderizarGrid();
                } else {
                    renderizarLista();
                }
            }
        });
    }

    function renderizarGrid() {
        const container = $('#documentosGrid');
        container.empty();

        if (documentos.length === 0) {
            container.html(`
                <div class="col-12 text-center py-5">
                    <i class="fa-solid fa-folder-open fa-4x text-muted mb-3"></i>
                    <p class="text-muted">No hay documentos para mostrar</p>
                </div>
            `);
            return;
        }

        documentos.forEach(function (doc, index) {
            const icono = obtenerIconoArchivo(doc.extension);
            const tamano = formatFileSize(doc.tamano);
            const delay = index * 50;

            // Icon color based on extension
            let iconColorClass = 'text-secondary';
            const ext = doc.extension.toLowerCase();
            if (['pdf'].includes(ext)) { iconColorClass = 'text-danger'; }
            else if (['xls', 'xlsx'].includes(ext)) { iconColorClass = 'text-success'; }
            else if (['doc', 'docx'].includes(ext)) { iconColorClass = 'text-primary'; }

            // Tooltip
            let tooltipContent = doc.descripcion ? doc.descripcion : doc.nombre_archivo;

            const card = $(`
                <div class="col-xl-3 col-lg-4 col-md-6 animate-card" style="animation-delay: ${delay}ms">
                    <!-- Onclick en toda la tarjeta -->
                    <div class="gov-card" onclick="manejarClicDocumento(${doc.id}, '${doc.nombre_archivo}', '${doc.extension}')">
                        
                        <!-- Left Thumb -->
                        <div class="gov-thumb">
                            ${ext === 'pdf'
                    ? `<div class="position-relative d-flex align-items-center justify-content-center ${iconColorClass}">
                                     <i class="fa-solid fa-file fa-3x"></i>
                                     <span class="position-absolute fw-bolder" style="bottom: 5px; width: 100%; text-align: center; font-size: 12px; color: white;">PDF</span>
                                   </div>`
                    : `<i class="${icono} fa-3x ${iconColorClass}"></i>`
                }
                        </div>

                        <!-- Right Body -->
                        <div class="gov-body">
                            <div>
                                <h6 class="gov-title" title="${doc.nombre_archivo}">
                                    ${doc.nombre_archivo}
                                </h6>
                                <div class="gov-meta">
                                    ${doc.extension.toUpperCase()} <span class="separator">|</span> ${tamano}
                                </div>
                                <!-- Indicador Visual de Ver -->
                                <div>
                                    <span class="btn-gov-preview">
                                        <i class="fas fa-eye"></i> Vista Previa
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Botón descarga con stopPropagation -->
                            <button class="btn-gov-download" onclick="event.stopPropagation(); descargarDocumento(${doc.id})">
                                <i class="fa-solid fa-download"></i> Descargar
                            </button>
                        </div>

                        <!-- Floating Actions (Admin) -->
                        <div class="gov-actions-floating" onclick="event.stopPropagation()">
                            ${PERMISO_EDITAR ? `
                            <button class="btn btn-sm btn-link text-secondary p-1" onclick="editarDocumento(${doc.id})" title="Editar">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            ` : ''}
                            ${PERMISO_ELIMINAR ? `
                            <button class="btn btn-sm btn-link text-danger p-1" onclick="eliminarDocumento(${doc.id}, '${doc.nombre_archivo}')" title="Eliminar">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `);

            container.append(card);
        });
    }

    function renderizarLista() {
        const tbody = $('#tablaDocumentos tbody');
        tbody.empty();

        if (documentos.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="7" class="text-center py-5">
                        <i class="fa-solid fa-folder-open fa-4x text-muted mb-3 d-block"></i>
                        <p class="text-muted">No hay documentos para mostrar</p>
                    </td>
                </tr>
            `);
            return;
        }

        documentos.forEach(function (doc) {
            const icono = obtenerIconoArchivo(doc.extension);
            const tamano = formatFileSize(doc.tamano);
            const fecha = new Date(doc.fecha_subida).toLocaleDateString('es-PE');
            const categoria = doc.categoria || '<span class="text-muted">Sin categoría</span>';

            // Tooltip content
            let tooltipContent = `<strong>${doc.nombre_archivo}</strong>`;
            if (doc.descripcion) {
                tooltipContent += `<br><em>${doc.descripcion}</em>`;
            }

            const row = $(`
                <tr>
                    <td>
                        <i class="${icono} me-2"></i>
                        <span data-bs-toggle="tooltip" data-bs-html="true" title="${tooltipContent}" style="cursor: help;">
                            ${doc.nombre_archivo}
                        </span>
                    </td>
                    <td>${categoria}</td>
                    <td><span class="badge bg-info">${doc.anio}</span></td>
                    <td>${tamano}</td>
                    <td>${doc.nombre_usuario || 'N/A'}</td>
                    <td>${fecha}</td>
                    <td>
                        <div class="btn-group btn-group-sm" role="group">
                            <button class="btn btn-outline-primary" onclick="descargarDocumento(${doc.id})" title="Descargar">
                                <i class="fa-solid fa-download"></i>
                            </button>
                            ${PERMISO_EDITAR ? `
                            <button class="btn btn-outline-secondary" onclick="editarDocumento(${doc.id})" title="Editar">
                                <i class="fa-solid fa-edit"></i>
                            </button>
                            ` : ''}
                            ${PERMISO_ELIMINAR ? `
                            <button class="btn btn-outline-danger" onclick="eliminarDocumento(${doc.id}, '${doc.nombre_archivo}')" title="Eliminar">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `);

            tbody.append(row);
        });

        // Inicializar Tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    }

    // Funciones globales
    window.descargarDocumento = function (id) {
        window.location.href = `../controlador/RepositorioControlador.php?op=descargar&id=${id}`;
    };

    window.editarDocumento = function (id) {
        const doc = documentos.find(d => d.id == id);
        if (!doc) return;

        $('#editarId').val(doc.id);
        $('#editarNombre').val(doc.nombre_archivo);
        $('#editarCategoria').val(doc.categoria || '');
        $('#editarDescripcion').val(doc.descripcion || '');
        $('#modalEditarDocumento').modal('show');
    };

    window.eliminarDocumento = function (id, nombre) {
        Swal.fire({
            title: '¿Eliminar documento?',
            text: nombre,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../controlador/RepositorioControlador.php?op=eliminar',
                    method: 'POST',
                    data: { id: id },
                    dataType: 'json',
                    success: function (response) {
                        if (response.status) {
                            Swal.fire('¡Eliminado!', response.msg, 'success');
                            cargarDocumentos();
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
    };

    // Función para previsualizar o descargar según el tipo
    window.manejarClicDocumento = function (id, nombre, extension) {
        const ext = extension.toLowerCase();
        const visualizables = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'txt'];

        if (visualizables.includes(ext)) {
            abrirVistaPrevia(id, nombre, ext);
        } else {
            // Si no es visualizable, descargar directamente
            descargarDocumento(id);
        }
    };

    function abrirVistaPrevia(id, nombre, ext) {
        const urlVisualizar = `../controlador/RepositorioControlador.php?op=visualizar&id=${id}`;
        const urlDescargar = `../controlador/RepositorioControlador.php?op=descargar&id=${id}`;

        $('#tituloVistaPrevia').html(`<i class="fas fa-eye me-2"></i> ${nombre}`);
        $('#btnDescargarVista').attr('onclick', `descargarDocumento(${id})`);

        const contenedor = $('#cuerpoVistaPrevia');
        contenedor.empty();
        contenedor.html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-3x text-primary"></i><p class="mt-2">Cargando vista previa...</p></div>');

        $('#modalVistaPrevia').modal('show');

        // Crear elemento según tipo
        setTimeout(() => {
            contenedor.empty();
            if (ext === 'pdf') {
                const iframe = $('<iframe>', {
                    src: urlVisualizar,
                    width: '100%',
                    height: '100%',
                    frameborder: '0',
                    css: { 'display': 'block' }
                });
                contenedor.append(iframe);
            } else if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) {
                const img = $('<img>', {
                    src: urlVisualizar,
                    class: 'img-fluid',
                    css: { 'max-height': '100%', 'object-fit': 'contain' }
                });
                contenedor.append(img);
            } else {
                const iframe = $('<iframe>', {
                    src: urlVisualizar,
                    width: '100%',
                    height: '100%',
                    style: 'border:none; background:white;'
                });
                contenedor.append(iframe);
            }
        }, 500);
    }

    // Helper: Icono por extensión
    // Helper: Icono por extensión
    function obtenerIconoArchivo(extension) {
        if (!extension) return 'fa-solid fa-file';
        const ext = extension.toLowerCase();

        switch (ext) {
            case 'pdf': return 'fa-solid fa-file-lines'; // Se cambia a genérico porque el icono PDF muestra PNG erróneamente
            case 'doc':
            case 'docx': return 'fa-solid fa-file-word';
            case 'xls':
            case 'xlsx': return 'fa-solid fa-file-excel';
            case 'ppt':
            case 'pptx': return 'fa-solid fa-file-powerpoint';
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'gif': return 'fa-solid fa-file-image';
            case 'zip':
            case 'rar':
            case '7z': return 'fa-solid fa-file-zipper';
            case 'txt': return 'fa-solid fa-file-lines';
            default: return 'fa-solid fa-file';
        }
    }

    function formatFileSize(bytes) {
        if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(2) + ' GB';
        if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
        if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB';
        return bytes + ' bytes';
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // ============================================
    // ADMINISTRACIÓN DE CATEGORÍAS
    // ============================================

    // Abrir modal de administrar categorías
    $('#btnAdministrarCategorias').on('click', function () {
        cargarCategoriasAdmin();
        $('#modalAdministrarCategorias').modal('show');
    });

    // Cargar categorías en el modal de administración
    function cargarCategoriasAdmin() {
        $.ajax({
            url: '../controlador/RepositorioControlador.php?op=listarCategoriasConConteo',
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.status && response.data) {
                    renderizarCategoriasAdmin(response.data);
                }
            },
            error: function () {
                Swal.fire('Error', 'No se pudieron cargar las categorías', 'error');
            }
        });
    }

    // Renderizar tabla de categorías en el modal
    function renderizarCategoriasAdmin(categorias) {
        const tbody = $('#tablaCategorias');
        tbody.empty();

        if (categorias.length === 0) {
            tbody.append(`
                <tr>
                    <td colspan="4" class="text-center text-muted">No hay categorías registradas</td>
                </tr>
            `);
            return;
        }

        categorias.forEach(cat => {
            tbody.append(`
                <tr>
                    <td class="fw-bold text-dark">${cat.nombre}</td>
                    <td class="text-muted text-truncate" style="max-width: 250px;" title="${cat.descripcion || ''}">${cat.descripcion || '-'}</td>
                    <td class="text-center">
                        <span class="badge bg-primary bg-opacity-75 rounded-pill px-3">${cat.total_documentos}</span>
                    </td>
                    <td class="text-center text-nowrap">
                        <button class="btn btn-sm btn-light text-secondary btnMoverCategoria py-0 px-1" data-id="${cat.id}" data-dir="up" title="Subir">
                            <i class="fa-solid fa-chevron-up"></i>
                        </button>
                        <button class="btn btn-sm btn-light text-secondary btnMoverCategoria py-0 px-1" data-id="${cat.id}" data-dir="down" title="Bajar">
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <span class="mx-1 text-muted">|</span>
                        <button class="btn btn-sm btn-light text-primary btnEditarCategoria py-0 px-1" 
                                data-id="${cat.id}" data-nombre="${cat.nombre}" 
                                data-descripcion="${cat.descripcion || ''}" title="Editar">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button class="btn btn-sm btn-light text-danger btnEliminarCategoria py-0 px-1" 
                                data-id="${cat.id}" data-nombre="${cat.nombre}" 
                                data-documentos="${cat.total_documentos}" title="Eliminar">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `);
        });

        // Event listeners para botones
        $('.btnMoverCategoria').on('click', function () {
            const id = $(this).data('id');
            const dir = $(this).data('dir');
            cambiarOrdenCategoria(id, dir);
        });

        $('.btnEditarCategoria').on('click', function () {
            const id = $(this).data('id');
            const nombre = $(this).data('nombre');
            const descripcion = $(this).data('descripcion');
            abrirModalEditarCategoria(id, nombre, descripcion);
        });

        $('.btnEliminarCategoria').on('click', function () {
            const id = $(this).data('id');
            const nombre = $(this).data('nombre');
            const documentos = $(this).data('documentos');
            eliminarCategoria(id, nombre, documentos);
        });
    }

    // Cambiar orden categoría
    function cambiarOrdenCategoria(id, direccion) {
        $.ajax({
            url: '../controlador/RepositorioControlador.php?op=cambiarOrdenCategoria',
            method: 'POST',
            data: {
                id: id,
                direccion: direccion
            },
            dataType: 'json',
            success: function (response) {
                if (response.status) {
                    cargarCategoriasAdmin();
                    cargarCategoriasFiltros(); // Actualizar filtros también
                } else {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'warning',
                        title: response.msg,
                        showConfirmButton: false,
                        timer: 2000
                    });
                }
            },
            error: function () {
                Swal.fire('Error', 'No se pudo cambiar el orden', 'error');
            }
        });
    }


    // Crear nueva categoría
    $('#formNuevaCategoria').on('submit', function (e) {
        e.preventDefault();

        const nombre = $('#nuevaCategoriaNombre').val().trim();
        const descripcion = $('#nuevaCategoriaDescripcion').val().trim();

        if (!nombre) {
            Swal.fire('Error', 'El nombre es obligatorio', 'error');
            return;
        }

        $.ajax({
            url: '../controlador/RepositorioControlador.php?op=crearCategoria',
            method: 'POST',
            data: {
                nombre: nombre,
                descripcion: descripcion,
                color: 'primary',
                orden: 0
            },
            dataType: 'json',
            success: function (response) {
                if (response.status) {
                    Swal.fire('Éxito', response.msg, 'success');
                    $('#formNuevaCategoria')[0].reset();
                    cargarCategoriasAdmin();
                    cargarCategoriasFiltros(); // Actualizar filtros
                } else {
                    Swal.fire('Error', response.msg, 'error');
                }
            },
            error: function () {
                Swal.fire('Error', 'No se pudo crear la categoría', 'error');
            }
        });
    });

    // Abrir modal editar categoría
    function abrirModalEditarCategoria(id, nombre, descripcion) {
        $('#editarCategoriaId').val(id);
        $('#editarCategoriaNombre').val(nombre);
        $('#editarCategoriaDescripcion').val(descripcion);
        $('#modalEditarCategoria').modal('show');
    }

    // Guardar cambios de categoría
    $('#formEditarCategoria').on('submit', function (e) {
        e.preventDefault();

        const id = $('#editarCategoriaId').val();
        const nombre = $('#editarCategoriaNombre').val().trim();
        const descripcion = $('#editarCategoriaDescripcion').val().trim();

        if (!nombre) {
            Swal.fire('Error', 'El nombre es obligatorio', 'error');
            return;
        }

        $.ajax({
            url: '../controlador/RepositorioControlador.php?op=actualizarCategoria',
            method: 'POST',
            data: {
                id: id,
                nombre: nombre,
                descripcion: descripcion,
                color: 'primary',
                orden: 0
            },
            dataType: 'json',
            success: function (response) {
                if (response.status) {
                    Swal.fire('Éxito', response.msg, 'success');
                    $('#modalEditarCategoria').modal('hide');
                    cargarCategoriasAdmin();
                    cargarCategoriasFiltros(); // Actualizar filtros
                    cargarDocumentos(); // Recargar documentos
                } else {
                    Swal.fire('Error', response.msg, 'error');
                }
            },
            error: function () {
                Swal.fire('Error', 'No se pudo actualizar la categoría', 'error');
            }
        });
    });

    // Eliminar categoría
    function eliminarCategoria(id, nombre, documentos) {
        if (documentos > 0) {
            Swal.fire({
                title: 'No se puede eliminar',
                text: `La categoría "${nombre}" tiene ${documentos} documento(s) asociado(s). Debes reasignar o eliminar esos documentos primero.`,
                icon: 'warning'
            });
            return;
        }

        Swal.fire({
            title: '¿Estás seguro?',
            text: `Se eliminará la categoría "${nombre}"`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../controlador/RepositorioControlador.php?op=eliminarCategoria',
                    method: 'POST',
                    data: { id: id },
                    dataType: 'json',
                    success: function (response) {
                        if (response.status) {
                            Swal.fire('Eliminado', response.msg, 'success');
                            cargarCategoriasAdmin();
                            cargarCategoriasFiltros(); // Actualizar filtros
                        } else {
                            Swal.fire('Error', response.msg, 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'No se pudo eliminar la categoría', 'error');
                    }
                });
            }
        });
    }

    // Cargar categorías en los filtros y formularios
    function cargarCategoriasFiltros() {
        $.ajax({
            url: '../controlador/RepositorioControlador.php?op=obtenerCategorias',
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.status && response.data) {
                    const categorias = response.data;

                    // Actualizar filtro
                    const filtro = $('#filtroCategoria');
                    const valorActual = filtro.val();
                    filtro.empty().append('<option value="">Todas las categorías</option>');
                    categorias.forEach(cat => {
                        filtro.append(`<option value="${cat.nombre}">${cat.nombre}</option>`);
                    });
                    filtro.val(valorActual);

                    // Actualizar select de subir
                    const selectSubir = $('#categoriaSubida');
                    const valorSubir = selectSubir.val();
                    selectSubir.empty().append('<option value="">Sin categoría</option>');
                    categorias.forEach(cat => {
                        selectSubir.append(`<option value="${cat.nombre}">${cat.nombre}</option>`);
                    });
                    selectSubir.val(valorSubir);

                    // Actualizar select de editar
                    const selectEditar = $('#editarCategoria');
                    const valorEditar = selectEditar.val();
                    selectEditar.empty().append('<option value="">Sin categoría</option>');
                    categorias.forEach(cat => {
                        selectEditar.append(`<option value="${cat.nombre}">${cat.nombre}</option>`);
                    });
                    selectEditar.val(valorEditar);
                }
            }
        });
    }

    // Cargar categorías al iniciar
    cargarCategoriasFiltros();

    // ============================================
    // ALERTA DE ALMACENAMIENTO
    // ============================================
    function verificarAlmacenamiento() {
        $.ajax({
            url: '../controlador/RepositorioControlador.php?op=verificarAlmacenamiento',
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.status && response.data) {
                    const data = response.data;
                    const alerta = $('#alertaAlmacenamiento');
                    const mensaje = $('#mensajeAlmacenamiento');

                    if (data.storage_status === 'critical' || data.storage_status === 'warning') {
                        alerta.removeClass('alert-warning alert-danger').addClass('d-flex').show();

                        if (data.storage_status === 'critical') {
                            alerta.addClass('alert-danger');
                        } else {
                            alerta.addClass('alert-warning');
                        }

                        mensaje.html(data.message + '<br><small>Espacio usado: ' +
                            (data.used_mb < 1000 ? data.used_mb + ' MB' : data.used_gb + ' GB') +
                            ' de ' + data.total_gb + ' GB (' + data.used_percentage + '%)</small>');
                    } else {
                        alerta.removeClass('d-flex').hide();
                    }
                }
            }
        });
    }

    // Actualizar también al subir o eliminar
    const originalCargarEstadisticas = cargarEstadisticas;
    cargarEstadisticas = function () {
        originalCargarEstadisticas();
        verificarAlmacenamiento();
    };
});
