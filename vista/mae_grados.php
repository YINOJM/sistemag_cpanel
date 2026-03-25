<?php
// vista/mae_grados.php
require_once __DIR__ . '/../modelo/conexion.php';

if (empty($_SESSION['id'])) {
    header("Location: " . (defined('BASE_URL') ? BASE_URL : '/sistem_job/') . "vista/login/login.php");
    exit();
}

// 1. Conexión
require_once '../modelo/conexion.php';

// 2. Seguridad Estricta (Solo Admin y SuperAdmin)
if ($_SESSION['rol'] !== 'Administrador' && $_SESSION['rol'] !== 'Super Administrador') {
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
                    text: "No tienes permisos para visualizar este módulo. Solo Administradores.",
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
require_once '../modelo/GradoModelo.php';
$modelo = new GradoModelo();
$lista = $modelo->listar();

// Todos los que entran aquí tienen permiso total (Admin/SuperAdmin)
$mostrarAcciones = true;
?>

<div class="page-content" style="padding-top: 80px; padding-left: 20px; padding-right: 20px;">
    <div class="container-fluid">
        <!-- Barra de Navegación -->
        <div class="d-flex align-items-center justify-content-between sis-nav-container mb-3"
            style="background-color: #00779e; color: #fff; border-radius: 8px; padding: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div class="d-flex align-items-center">
                <h4 class="m-0 fw-bold me-4"><i class="fa-solid fa-star me-2"></i>Mantenimiento de Grados / Cargos</h4>
            </div>

            <button class="btn btn-light fw-bold" style="color: #00779e;" onclick="abrirModal()">
                <i class="fa-solid fa-plus me-1"></i> Nuevo Grado
            </button>
        </div>

        <!-- Tabla -->
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tablaGrados"
                        class="table table-sm table-hover table-striped table-bordered align-middle text-center w-100">
                        <thead class="bg-dark text-white">
                            <tr>
                                <th style="width: 10%;">ID</th>
                                <th>Nombre del Grado / Cargo</th>
                                <th style="width: 15%;">Estado</th>
                                <th style="width: 15%;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($lista)): ?>
                                <?php foreach ($lista as $row): ?>
                                    <tr>
                                        <td><?= $row['id_grado'] ?></td>
                                        <td class="text-start"><?= htmlspecialchars($row['nombre_grado']) ?></td>
                                        <td>
                                            <?php if ($row['activo'] == 1): ?>
                                                <span class="badge bg-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary me-1"
                                                onclick="editar(<?= $row['id_grado'] ?>)" title="Editar">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            
                                            <button class="btn btn-sm btn-danger" onclick="eliminar(<?= $row['id_grado'] ?>)"
                                                title="Eliminar">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nuevo/Editar -->
<div class="modal fade" id="modalGrado" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white" style="background-color: #00779e !important;">
                <h5 class="modal-title fw-bold" id="tituloModal">Nuevo Grado</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formGrado">
                    <input type="hidden" name="id_grado" id="id_grado">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre del Grado / Cargo</label>
                        <input type="text" class="form-control" name="nombre_grado" id="nombre_grado"
                            placeholder="EJ. ST3 PNP" required oninput="this.value = this.value.toUpperCase()">
                    </div>

                    <!-- Campo oculto por defecto, se muestra al editar -->
                    <div class="mb-3 form-check form-switch" id="divActivo" style="display:none;">
                        <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1" checked>
                        <label class="form-check-label fw-bold" for="activo">Grado Activo</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary"
                    style="background-color: #00779e !important; border-color: #00779e;"
                    onclick="guardarGrado()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    let tabla;

    $(document).ready(function () {
        tabla = $('#tablaGrados').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json",
                "search": "Buscar:",
                "lengthMenu": "Mostrar _MENU_ registros",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                "infoFiltered": "(filtrado de _MAX_ registros totales)"
            },
            "order": [[1, "asc"]], 
            "pageLength": 10,
            "lengthChange": true, 
            "searching": true     
        });
    });

    function abrirModal() {
        $('#id_grado').val('');
        $('#nombre_grado').val('');
        $('#activo').prop('checked', true); 
        $('#divActivo').hide(); 
        $('#tituloModal').text('Nuevo Grado');

        $('#modalGrado').modal('show');
    }

    function editar(id) {
        $.post("../controlador/GradoControlador.php?op=obtener", { id: id }, function (data) {
            try {
                const resp = JSON.parse(data);
                $('#id_grado').val(resp.id_grado);
                $('#nombre_grado').val(resp.nombre_grado);

                if (resp.activo == 1) {
                    $('#activo').prop('checked', true);
                } else {
                    $('#activo').prop('checked', false);
                }
                $('#divActivo').show(); 

                $('#tituloModal').text('Editar Grado');
                $('#modalGrado').modal('show');
            } catch (e) {
                console.error(data);
                Swal.fire('Error', 'Error al leer datos', 'error');
            }
        });
    }

    function guardarGrado() {
        const formData = new FormData(document.getElementById('formGrado'));

        if (!$('#activo').is(':checked')) {
            formData.append('activo', 0);
        } else {
            formData.set('activo', 1);
        }

        if (!formData.get('nombre_grado').trim()) {
            Swal.fire('Error', 'El nombre es obligatorio', 'warning');
            return;
        }

        $.ajax({
            url: "../controlador/GradoControlador.php?op=guardar",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {
                try {
                    const res = JSON.parse(response);
                    if (res.status) {
                        Swal.fire('Guardado', res.msg, 'success').then(() => {
                            location.reload(); 
                        });
                        $('#modalGrado').modal('hide');
                    } else {
                        Swal.fire('Error', res.msg, 'error');
                    }
                } catch (e) {
                    console.error(response);
                    Swal.fire('Error', 'Respuesta inválida del servidor', 'error');
                }
            }
        });
    }

    function eliminar(id) {
        Swal.fire({
            title: '¿Eliminar grado?',
            text: "Esta acción no se puede deshacer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post("../controlador/GradoControlador.php?op=eliminar", { id: id }, function (response) {
                    try {
                        const res = JSON.parse(response);
                        if (res.status) {
                            Swal.fire('Eliminado', res.msg, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', res.msg, 'error');
                        }
                    } catch (e) {
                        console.error(response);
                        Swal.fire('Error', 'Respuesta inválida del servidor', 'error');
                    }
                });
            }
        });
    }
</script>

<?php include 'layout/footer.php'; ?>
