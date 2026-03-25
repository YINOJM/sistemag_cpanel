<?php
// vista/tipos_bien.php
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
require_once '../modelo/TipoBienModelo.php';
$modelo = new TipoBienModelo();
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
                <h4 class="m-0 fw-bold me-4"><i class="fa-solid fa-boxes-stacked me-2"></i>Mantenimiento: Tipos de Bien</h4>
            </div>

            <button class="btn btn-light fw-bold" style="color: #00779e;" onclick="abrirModal()">
                <i class="fa-solid fa-plus me-1"></i> Nuevo Tipo
            </button>
        </div>

        <!-- Tabla -->
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tablaTiposBien"
                        class="table table-sm table-hover table-striped table-bordered align-middle text-center w-100">
                        <thead class="bg-dark text-white">
                            <tr>
                                <th style="width: 10%;">ID</th>
                                <th>Nombre del Tipo de Bien</th>
                                <th style="width: 15%;">Estado</th>
                                <th style="width: 15%;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($lista)): ?>
                                <?php foreach ($lista as $row): ?>
                                    <tr>
                                        <td><?= $row['id_tipo_bien'] ?></td>
                                        <td class="text-start"><?= htmlspecialchars($row['nombre']) ?></td>
                                        <td>
                                            <?php if ($row['estado'] == 1): ?>
                                                <span class="badge bg-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary me-1"
                                                onclick="editar(<?= $row['id_tipo_bien'] ?>)" title="Editar">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            
                                            <button class="btn btn-sm btn-danger" onclick="eliminar(<?= $row['id_tipo_bien'] ?>)"
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
<div class="modal fade" id="modalTipoBien" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white" style="background-color: #00779e !important;">
                <h5 class="modal-title fw-bold" id="tituloModal">Nuevo Tipo de Bien</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formTipoBien">
                    <input type="hidden" name="id_tipo_bien" id="id_tipo_bien">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre del Tipo de Bien</label>
                        <input type="text" class="form-control" name="nombre" id="nombre"
                            placeholder="EJ. HERRAMIENTAS" required oninput="this.value = this.value.toUpperCase()">
                    </div>

                    <div class="mb-3 form-check form-switch" id="divEstado" style="display:none;">
                        <input class="form-check-input" type="checkbox" id="estado" name="estado" value="1" checked>
                        <label class="form-check-label fw-bold" for="estado">Activo</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary"
                    style="background-color: #00779e !important; border-color: #00779e;"
                    onclick="guardarTipo()">Guardar</button>
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
        tabla = $('#tablaTiposBien').DataTable({
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
        $('#id_tipo_bien').val('');
        $('#nombre').val('');
        $('#estado').prop('checked', true); 
        $('#divEstado').hide(); 
        $('#tituloModal').text('Nuevo Tipo de Bien');
        $('#modalTipoBien').modal('show');
    }

    function editar(id) {
        $.post("../controlador/TipoBienControlador.php?op=obtener", { id: id }, function (resp) {
            try {
                $('#id_tipo_bien').val(resp.id_tipo_bien);
                $('#nombre').val(resp.nombre);

                if (resp.estado == 1) {
                    $('#estado').prop('checked', true);
                } else {
                    $('#estado').prop('checked', false);
                }
                $('#divEstado').show(); 

                $('#tituloModal').text('Editar Tipo de Bien');
                $('#modalTipoBien').modal('show');
            } catch (e) {
                console.error(resp);
                Swal.fire('Error', 'Error al leer datos', 'error');
            }
        });
    }

    function guardarTipo() {
        const formData = new FormData(document.getElementById('formTipoBien'));

        if (!$('#estado').is(':checked')) {
            formData.append('estado', 0);
        } else {
            formData.set('estado', 1);
        }

        if (!formData.get('nombre').trim()) {
            Swal.fire('Error', 'El nombre es obligatorio', 'warning');
            return;
        }

        $.ajax({
            url: "../controlador/TipoBienControlador.php?op=guardar",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            success: function (res) {
                try {
                    if (res.status) {
                        Swal.fire('Guardado', res.msg, 'success').then(() => {
                            location.reload(); 
                        });
                        $('#modalTipoBien').modal('hide');
                    } else {
                        Swal.fire('Error', res.msg, 'error');
                    }
                } catch (e) {
                    console.error(res);
                    Swal.fire('Error', 'Respuesta inválida del servidor', 'error');
                }
            }
        });
    }

    function eliminar(id) {
        Swal.fire({
            title: '¿Eliminar tipo de bien?',
            text: "Esta acción no se puede deshacer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post("../controlador/TipoBienControlador.php?op=eliminar", { id: id }, function (res) {
                    try {
                        if (res.status) {
                            Swal.fire('Eliminado', res.msg, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', res.msg, 'error');
                        }
                    } catch (e) {
                        console.error(res);
                        Swal.fire('Error', 'Respuesta inválida del servidor', 'error');
                    }
                });
            }
        });
    }
</script>

<?php include 'layout/footer.php'; ?>
