<?php
// 1. CARGAR CONEXIÓN Y ENTORNO (Centralizado)
require_once __DIR__ . "/../modelo/conexion.php";

if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'Super Administrador') {
    header("Location: inicio.php");
    exit();
}
?>
<!-- primero se carga el topbar (incluye head y header) -->
<?php require('./layout/topbar.php'); ?>
<!-- luego se carga el sidebar -->
<?php require('./layout/sidebar.php'); ?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

<style>
    :root {
        --glass-bg: rgba(255, 255, 255, 0.95);
        --primary-gradient: linear-gradient(135deg, #00779e 0%, #00a8cc 100%);
    }

    .page-content {
        background-color: #f4f7f9;
        min-height: calc(100vh - 70px);
    }

    .module-card {
        border-radius: 12px;
        border: none;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        overflow: hidden;
    }

    .module-icon-preview {
        width: 42px;
        height: 42px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: #fff;
        font-size: 1.1rem;
        margin-right: 15px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        color: #00779e;
        transition: all 0.3s ease;
    }

    tr:hover .module-icon-preview {
        transform: scale(1.1);
        background: #00779e;
        color: white;
    }

    /* Badges más sutiles y modernos */
    .badge-modern {
        padding: 6px 14px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.72rem;
        letter-spacing: 0.3px;
        text-transform: uppercase;
    }

    .badge-op { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
    .badge-aux { background: #f5f3ff; color: #6d28d9; border: 1px solid #ddd6fe; }
    .badge-sys { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
    .badge-util { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
    .badge-restringido { 
        background: #fff1f2; 
        color: #e11d48; 
        border: 1px solid #fecdd3;
        font-size: 0.65rem;
        padding: 2px 8px;
    }

    /* Estilo para la tabla */
    #tablaModulos {
        border-collapse: separate;
        border-spacing: 0 8px;
    }

    #tablaModulos thead th {
        background: transparent;
        border: none;
        color: #64748b;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.75rem;
        padding: 15px;
    }

    #tablaModulos tbody tr {
        background: white;
        transition: all 0.2s ease;
    }

    #tablaModulos tbody tr td {
        border: none;
        padding: 15px;
    }

    #tablaModulos tbody tr td:first-child { border-radius: 10px 0 0 10px; }
    #tablaModulos tbody tr td:last-child { border-radius: 0 10px 10px 0; }

    #tablaModulos tbody tr:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        z-index: 10;
    }

    .btn-action {
        width: 32px;
        height: 32px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        transition: all 0.2s;
    }
</style>

<!-- inicio del contenido principal -->
<div class="page-content">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12 d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0 text-primary font-weight-bold">Gestión de Módulos Dinámicos</h4>
                        <p class="text-muted small mb-0">Administra los módulos que aparecen en el menú y el sistema de permisos.</p>
                    </div>
                    <button class="btn btn-primary" onclick="nuevoModulo()">
                        <i class="fas fa-plus me-2"></i> Nuevo Módulo
                    </button>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tablaModulos" class="table table-hover align-middle" style="width:100%">
                            <thead class="bg-light">
                                <tr>
                                    <th width="50">ID</th>
                                    <th>Módulo</th>
                                    <th>Slug (Permiso)</th>
                                    <th>Categoría</th>
                                    <th>Enlace</th>
                                    <th>Orden</th>
                                    <th width="100">Estado</th>
                                    <th width="120">Acciones</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Modulo -->
    <div class="modal fade" id="modalModulo" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="modalTitle">Nuevo Módulo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formModulo">
                    <input type="hidden" name="id" id="mod_id">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Nombre Visible</label>
                                <input type="text" name="nombre" id="mod_nombre" class="form-control" placeholder="Ej: Recursos Humanos" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Slug (Identificador único)</label>
                                <input type="text" name="slug" id="mod_slug" class="form-control" placeholder="Ej: personal" required>
                                <div class="form-text small">Se usará para userCan('slug')</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Icono (FontAwesome)</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i id="iconPreview" class="fas fa-question"></i></span>
                                    <input type="text" name="icono" id="mod_icono" class="form-control" placeholder="fa-users" required onkeyup="updateIconPreview()">
                                </div>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label small fw-bold">Enlace (Ruta desde raíz/sistem_job/)</label>
                                <input type="text" name="enlace" id="mod_enlace" class="form-control" placeholder="vista/personal_listado.php" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Categoría</label>
                                <select name="categoria" id="mod_categoria" class="form-select" required>
                                    <option value="OPERATIVOS">OPERATIVOS (Menú principal)</option>
                                    <option value="AUXILIARES">AUXILIARES (Submenú Auxiliares)</option>
                                    <option value="UTILITARIOS">UTILITARIOS (Submenú Utilitarios)</option>
                                    <option value="SISTEMA">SISTEMA (Submenú Configuración)</option>
                                    <option value="OTROS">OTROS (Varios/General)</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Orden</label>
                                <input type="number" name="orden" id="mod_orden" class="form-control" value="10" required>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="es_restringido" id="mod_restringido">
                                    <label class="form-check-label small fw-bold" for="mod_restringido">Restringido</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="activo" id="mod_activo" checked>
                                    <label class="form-check-label small fw-bold" for="mod_activo">Módulo Activo</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="btnGuardar">Guardar Módulo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include "layout/footer.php"; ?>

    <!-- DataTables JS libraries -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

    <script>
        let tabla;
        $(document).ready(function() {
            tabla = $('#tablaModulos').DataTable({
                ajax: '../controlador/ModuloControlador.php?op=listar',
                columns: [
                    { data: 'id' },
                    { 
                        data: 'nombre',
                        render: function(data, type, row) {
                            return `<div class="d-flex align-items-center">
                                        <div class="module-icon-preview">
                                            <i class="fas ${row.icono}"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold" style="color:#334155">${data}</div>
                                            ${row.es_restringido ? '<span class="badge badge-restringido">Restringido</span>' : ''}
                                        </div>
                                    </div>`;
                        }
                    },
                    { data: 'slug', render: d => `<span style="color: #64748b; font-family: monospace; font-weight: 600; font-size: 0.9rem;">${d}</span>` },
                    { 
                        data: 'categoria',
                        render: function(cat) {
                            let cls = 'badge-op';
                            if(cat == 'AUXILIARES') cls = 'badge-aux';
                            if(cat == 'SISTEMA') cls = 'badge-sys';
                            if(cat == 'UTILITARIOS') cls = 'badge-util';
                            return `<span class="badge badge-modern ${cls}">${cat}</span>`;
                        }
                    },
                    { data: 'enlace', render: d => `<small class="text-muted">${d}</small>` },
                    { data: 'orden' },
                    { 
                        data: 'activo',
                        render: function(act, type, row) {
                            return `<div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" ${act == 1 ? 'checked' : ''} onclick="toggleEstado(${row.id})">
                                    </div>`;
                        }
                    },
                    {
                        data: null,
                        render: function(row) {
                            return `<div class="d-flex gap-2">
                                        <button class="btn btn-action btn-outline-primary" onclick='editarModulo(${JSON.stringify(row)})' title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-action btn-outline-danger" onclick="eliminarModulo(${row.id})" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>`;
                        }
                    }
                ],
                language: { url: "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json" },
                order: [[5, 'asc']]
            });

            $('#formModulo').submit(function(e) {
                e.preventDefault();
                const data = {
                    id: $('#mod_id').val(),
                    nombre: $('#mod_nombre').val(),
                    slug: $('#mod_slug').val(),
                    icono: $('#mod_icono').val(),
                    enlace: $('#mod_enlace').val(),
                    categoria: $('#mod_categoria').val(),
                    orden: $('#mod_orden').val(),
                    es_restringido: $('#mod_restringido').is(':checked'),
                    activo: $('#mod_activo').is(':checked')
                };

                $('#btnGuardar').attr('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Guardando...');

                fetch('../controlador/ModuloControlador.php?op=guardar', {
                    method: 'POST',
                    body: JSON.stringify(data),
                    headers: { 'Content-Type': 'application/json' }
                })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        Swal.fire('¡Éxito!', res.message, 'success');
                        $('#modalModulo').modal('hide');
                        tabla.ajax.reload();
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                })
                .finally(() => {
                    $('#btnGuardar').attr('disabled', false).text('Guardar Módulo');
                });
            });
        });

        function updateIconPreview() {
            let icon = $('#mod_icono').val();
            if(!icon.startsWith('fa-')) icon = 'fa-' + icon;
            $('#iconPreview').attr('class', 'fas ' + icon);
        }

        function nuevoModulo() {
            $('#formModulo')[0].reset();
            $('#mod_id').val('');
            $('#modalTitle').text('Nuevo Módulo');
            $('#iconPreview').attr('class', 'fas fa-question');
            $('#modalModulo').modal('show');
        }

        function editarModulo(data) {
            $('#mod_id').val(data.id);
            $('#mod_nombre').val(data.nombre);
            $('#mod_slug').val(data.slug);
            $('#mod_icono').val(data.icono);
            $('#mod_enlace').val(data.enlace);
            $('#mod_categoria').val(data.categoria);
            $('#mod_orden').val(data.orden);
            $('#mod_restringido').prop('checked', data.es_restringido == 1);
            $('#mod_activo').prop('checked', data.activo == 1);
            
            $('#modalTitle').text('Editar Módulo: ' + data.nombre);
            updateIconPreview();
            $('#modalModulo').modal('show');
        }

        function toggleEstado(id) {
            fetch(`../controlador/ModuloControlador.php?op=toggle&id=${id}`)
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        tabla.ajax.reload(null, false);
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                });
        }

        function eliminarModulo(id) {
            Swal.fire({
                title: '¿Estás seguro?',
                text: "Esto eliminará el módulo permanentemente. Se recomienda solo desactivarlo.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`../controlador/ModuloControlador.php?op=eliminar&id=${id}`)
                        .then(res => res.json())
                        .then(res => {
                            if (res.success) {
                                Swal.fire('Eliminado', res.message, 'success');
                                tabla.ajax.reload();
                            } else {
                                Swal.fire('Error', res.message, 'error');
                            }
                        });
                }
            });
        }
    </script>
</body>
</html>
