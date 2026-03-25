<?php
// vista/tipos_proceso.php

// 1. CARGAR CONEXIÓN Y ENTORNO
require_once __DIR__ . '/../modelo/conexion.php';

// Seguridad de Sesión (Ya manejada en topbar, pero reforzamos)
if (empty($_SESSION['id'])) {
    header("Location: " . (defined('BASE_URL') ? BASE_URL : '/sistem_job/') . "vista/login/login.php");
    exit();
}

$titulo_pagina = 'Tipos de Proceso - SIG';
require_once 'layout/topbar.php'; // Topbar ya abre <html>, <head> y <body>
require_once 'layout/sidebar.php';
?>

<!-- Estilos adicionales para esta página -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

    <!-- Content Wrapper -->
    <div class="page-content" style="padding-top: 100px;">

        <!-- Dashboard Header -->
        <div class="dashboard-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">Tipos de Proceso</h3>
                <p class="text-muted mb-0">Gestión del catálogo de procesos de selección</p>
            </div>
            <div class="d-flex gap-2">
                <a href="segmentacion_listado.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm d-flex align-items-center">
                    <i class="fas fa-arrow-left me-2"></i> Volver
                </a>
                <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" onclick="abrirModal()">
                    <i class="fas fa-plus me-2"></i> Nuevo Tipo
                </button>
            </div>
        </div>

        <!-- Tabla -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="tablaTipos" class="table table-hover align-middle w-100">
                        <thead class="bg-light">
                            <tr>
                                <th
                                    class="border-0 rounded-start-3 text-secondary text-uppercase small fw-bold px-4 py-3">
                                    ID</th>
                                <th class="border-0 text-secondary text-uppercase small fw-bold px-4 py-3">Nombre</th>
                                <th class="border-0 text-secondary text-uppercase small fw-bold px-4 py-3 text-center">
                                    Estado</th>
                                <th
                                    class="border-0 rounded-end-3 text-secondary text-uppercase small fw-bold px-4 py-3 text-center">
                                    Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- DataTables -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Registro/Edición -->
    <div class="modal fade" id="modalTipo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-primary text-white border-0">
                    <h5 class="modal-title fw-bold" id="modalTitulo">Nuevo Tipo de Proceso</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="formTipo">
                        <input type="hidden" id="id_tipo" name="id">
                        <div class="mb-3">
                            <label for="nombre" class="form-label fw-bold text-muted small text-uppercase">Nombre del
                                Proceso</label>
                            <input type="text" class="form-control form-control-lg bg-light border-0" id="nombre"
                                name="nombre" required placeholder="Ej. LICITACIÓN PÚBLICA">
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-sm">
                            <i class="fas fa-save me-2"></i> Guardar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php require_once 'layout/footer.php'; ?>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

    <script>
        let tabla;

        // Ejecución inmediata ya que estamos al final del body
        (function () {
            console.log("Iniciando DataTables (Modo Directo)...");
            try {
                if (typeof $ === 'undefined') {
                    console.error("jQuery no está cargado");
                    return;
                }
                if (!$.fn.DataTable) {
                    console.error("DataTables no está cargado");
                    return;
                }

                if ($.fn.DataTable.isDataTable('#tablaTipos')) {
                    $('#tablaTipos').DataTable().destroy();
                }

                tabla = $('#tablaTipos').DataTable({
                    ajax: {
                        url: '../controlador/TipoProcesoControlador.php?op=listar&t=' + new Date().getTime(),
                        type: 'GET',
                        dataSrc: 'data',
                        error: function (xhr, error, thrown) {
                            console.error("DataTables Error:", error, thrown);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error al cargar datos',
                                text: 'Estado: ' + xhr.status + ' - ' + (thrown || 'Respuesta inválida')
                            });
                        }
                    },
                    columns: [
                        { data: 'id', className: 'px-4' },
                        { data: 'nombre', className: 'px-4' },
                        {
                            data: 'estado',
                            className: 'text-center',
                            render: function (data, type, row) {
                                const checked = data == 1 ? 'checked' : '';
                                return `
                            <div class="form-check form-switch d-inline-block">
                                <input class="form-check-input" type="checkbox" onchange="cambiarEstado(${row.id}, this.checked)" ${checked}>
                            </div>
                        `;
                            }
                        },
                        {
                            data: null,
                            className: 'text-center',
                            render: function (data, type, row) {
                                return `
                            <div class="d-flex gap-2 justify-content-center">
                                <button class="btn btn-sm btn-edit-custom" onclick="editar(${row.id}, '${row.nombre}')" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-delete-custom" onclick="eliminar(${row.id})" title="Eliminar">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        `;
                            }
                        }
                    ],
                    language: { url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json" },
                    dom: 'Bfrtip',
                    buttons: []
                });

                $('#formTipo').on('submit', function (e) {
                    e.preventDefault();
                    const formData = new FormData(this);

                    $.ajax({
                        url: '../controlador/TipoProcesoControlador.php?op=guardar',
                        type: 'POST',
                        data: formData,
                        contentType: false,
                        processData: false,
                        success: function (response) {
                            if (response.status) {
                                Swal.fire({
                                    icon: 'success',
                                    title: '¡Guardado!',
                                    text: response.msg,
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                                $('#modalTipo').modal('hide');
                                tabla.ajax.reload();
                            } else {
                                Swal.fire('Error', response.msg, 'error');
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error(xhr.responseText);
                            Swal.fire('Error', 'Hubo un problema al guardar. Revisa la consola.', 'error');
                        }
                    });
                });
            } catch (e) {
                console.error("Error al inicializar DataTables:", e);
            }
        })();

        function abrirModal() {
            $('#id_tipo').val('');
            $('#formTipo')[0].reset();
            $('#modalTitulo').text('Nuevo Tipo de Proceso');
            $('#modalTipo').modal('show');
        }

        function editar(id, nombre) {
            $('#id_tipo').val(id);
            $('#nombre').val(nombre);
            $('#modalTitulo').text('Editar Tipo de Proceso');
            $('#modalTipo').modal('show');
        }

        function cambiarEstado(id, checked) {
            $.post('../controlador/TipoProcesoControlador.php?op=cambiar_estado', {
                id: id,
                estado: checked ? 1 : 0
            }, function (response) {
                if (response.status) {
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000
                    });
                    Toast.fire({ icon: 'success', title: 'Estado actualizado' });
                } else {
                    Swal.fire('Error', response.msg, 'error');
                    tabla.ajax.reload(); // Revertir si falló
                }
            });
        }

        function eliminar(id) {
            Swal.fire({
                title: '¿Estás seguro?',
                text: "Solo se podrá eliminar si no está siendo usado en ninguna segmentación.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('../controlador/TipoProcesoControlador.php?op=eliminar', { id: id }, function (response) {
                        if (response.status) {
                            Swal.fire('¡Eliminado!', response.msg, 'success');
                            tabla.ajax.reload();
                        } else {
                            Swal.fire('Error', response.msg, 'error');
                        }
                    }, 'json');
                }
            });
        }
    </script>
</body>

</html>