<?php
// vista/inventario_editar.php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE)
    session_start();

if (empty($_SESSION['id'])) {
    header("Location: ../vista/login/login.php");
    exit();
}

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: inventario.php");
    exit();
}

include 'layout/topbar.php';
include 'layout/sidebar.php';
?>

<!-- Select2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<div class="page-content" style="padding: 20px; padding-top: 80px;">
    <div class="container-fluid">

        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow-lg border-0 rounded-lg">
                    <div class="card-header text-white d-flex justify-content-between align-items-center"
                        style="background-color: #00779e;">
                        <h4 class="mb-0"><i class="fas fa-edit me-2"></i>Editar Item</h4>
                        <a href="inventario.php" class="btn btn-light btn-sm"
                            style="font-weight: 600; padding: 8px 16px;">
                            <i class="fa-solid fa-arrow-left me-2"></i> Volver al Inventario
                        </a>
                    </div>
                    <div class="card-body bg-light">
                        <form id="formInventario" autocomplete="off">
                            <input type="hidden" name="id" id="id" value="<?= $id ?>">
                            <input type="hidden" name="anio" id="anio">

                            <!-- Fila 1: Código y Tipo de Bien -->
                            <!-- Fila 1: Código y Tipo de Bien -->
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold small">Código de Inventario <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="codigo_inventario"
                                        id="codigo_inventario" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold small">Tipo de Bien <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select" name="tipo_bien" id="tipo_bien" required>
                                        <option value="">Seleccione...</option>
                                        <?php
                                        require_once '../modelo/TipoBienModelo.php';
                                        $tipoModelo = new TipoBienModelo();
                                        $tipos = $tipoModelo->listar();
                                        foreach ($tipos as $t) {
                                            if ($t['estado'] == 1) {
                                                echo "<option value='{$t['nombre']}'>{$t['nombre']}</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold small">
                                        Estado de Conservación <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" name="estado_bien" id="estado_bien" required>
                                        <option value="BUENO">BUENO</option>
                                        <option value="REGULAR">REGULAR</option>
                                        <option value="MALO">MALO</option>
                                        <option value="CHATARRA">CHATARRA</option>
                                        <option value="RAEE">RAEE (Residuos Eléctricos y Electrónicos)</option>
                                    </select>
                                    <div id="helper_raee" class="mt-1" style="display:none;">
                                        <small class="text-primary fw-bold"><i class="fas fa-lightbulb me-1"></i> Residuos de Aparatos Eléctricos</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold small">Situación <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select" name="situacion" id="situacion" required>
                                        <option value="Uso">Uso (U) - En uso</option>
                                        <option value="Desuso">Desuso (D) - No se usa</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Fila 2: Descripción -->
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-bold small">Descripción del Bien <span
                                            class="text-danger">*</span></label>
                                    <textarea class="form-control" name="descripcion" id="descripcion" rows="2"
                                        required></textarea>
                                </div>
                            </div>

                            <!-- Fila 3: Marca, Modelo, Serie y Dimensiones -->
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold small">Marca</label>
                                    <input type="text" class="form-control" name="marca" id="marca">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold small">Modelo</label>
                                    <input type="text" class="form-control" name="modelo" id="modelo">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold small">Serie</label>
                                    <input type="text" class="form-control" name="serie" id="serie">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold small">Dimensiones (Largo x Ancho x Alto)</label>
                                    <input type="text" class="form-control" name="dimensiones" id="dimensiones">
                                </div>
                            </div>

                            <!-- Fila 3.6: Otras Características -->
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-bold small">Otras Características / Especificaciones Técnicas</label>
                                    <textarea class="form-control" name="otras_caracteristicas" id="otras_caracteristicas" rows="2"
                                        placeholder="Ej: Procesador i7, 16GB RAM, Disco 1TB..."></textarea>
                                </div>
                            </div>


                            <!-- Fila 4: Color, Cantidad, Ubicación y Responsable -->
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold small">Color</label>
                                    <input type="text" class="form-control" name="color" id="color">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold small">Cantidad</label>
                                    <input type="number" class="form-control" name="cantidad" id="cantidad" min="1">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold small">Ubicación Física del Bien</label>
                                    <input type="text" class="form-control" name="ubicacion_fisica" id="ubicacion_fisica">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold small">Usuario Responsable</label>
                                    <input type="text" class="form-control" name="usuario_responsable"
                                        id="usuario_responsable">
                                </div>
                            </div>

                            <!-- Nueva Fila: Unidad Policial (Jerarquía) -->
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-bold small text-primary"><i class="fas fa-shield-alt"></i> Unidad Policial Asignada (Jerarquía)</label>
                                    <?php if ($_SESSION['rol'] === 'Super Administrador'): ?>
                                        <select class="form-select select2" name="id_subunidad" id="id_subunidad_edit" required>
                                            <option value="">Seleccione Unidad...</option>
                                            <?php 
                                            // En el edit, lo poblamos vía JS o lo dejamos listo para que el admin lo cambie
                                            $sqlSub = "SELECT s.id_subunidad, s.nombre_subunidad, d.nombre_division, r.nombre_region 
                                                       FROM sub_unidades_policiales s
                                                       INNER JOIN divisiones_policiales d ON s.id_division = d.id_division
                                                       INNER JOIN regiones_policiales r ON d.id_region = r.id_region
                                                       ORDER BY r.nombre_region, d.nombre_division, s.nombre_subunidad";
                                            $resSub = $conexion->query($sqlSub);
                                            while ($sub = $resSub->fetch_assoc()) {
                                                echo "<option value='{$sub['id_subunidad']}'>{$sub['nombre_region']} - {$sub['nombre_division']} - {$sub['nombre_subunidad']}</option>";
                                            }
                                            ?>
                                        </select>
                                    <?php else: ?>
                                        <input type="hidden" name="id_subunidad" id="id_subunidad_hidden">
                                        <div class="form-control bg-light" id="nombre_subunidad_display">
                                            Cargando unidad...
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Fila 6: Observaciones -->
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-bold small">Observaciones</label>
                                    <textarea class="form-control" name="observaciones" id="observaciones"
                                        rows="2"></textarea>
                                </div>
                            </div>

                            <!-- Botón Actualizar -->
                            <div class="row">
                                <div class="col-md-12 text-center">
                                    <button type="submit" class="btn btn-primary btn-lg" id="btnActualizar">
                                        <i class="fa-solid fa-save me-2"></i> Actualizar Item
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        // Mostrar helper RAEE si se selecciona
        $('#estado_bien').on('change', function() {
            if($(this).val() === 'RAEE') {
                $('#helper_raee').show();
            } else {
                $('#helper_raee').hide();
            }
        });

        // Inicializar Tooltips de Bootstrap (Premium Look)
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Inicializar Select2
        if ($.fn.select2) {
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Seleccione una Unidad'
            });
        }

        // Cargar datos del item
        cargarDatos();

        // Lógica dinámica para mostrar/ocultar campos técnicos (ELIMINADA)
        // En este formato nuevo del Anexo 07, todos los campos son visibles.

        // Actualizar formulario
        $('#formInventario').on('submit', function (e) {
            e.preventDefault();

            const btn = $('#btnActualizar');
            btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i> Actualizando...');

            $.ajax({
                url: '../controlador/InventarioControlador.php?op=actualizar',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function (response) {
                    btn.prop('disabled', false).html('<i class="fa-solid fa-save me-2"></i> Actualizar Item');

                    if (response.status) {
                        Swal.fire({
                            title: '¡Item Actualizado!',
                            text: response.msg,
                            icon: 'success',
                            confirmButtonText: 'Ir al Inventario'
                        }).then(() => {
                            window.location.href = 'inventario.php';
                        });
                    } else {
                        Swal.fire('Error', response.msg, 'error');
                    }
                },
                error: function () {
                    btn.prop('disabled', false).html('<i class="fa-solid fa-save me-2"></i> Actualizar Item');
                    Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
                }
            });
        });
    });

    function cargarDatos() {
        const id = $('#id').val();

        $.ajax({
            url: `../controlador/InventarioControlador.php?op=obtenerPorId&id=${id}`,
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.status && response.data) {
                    const data = response.data;

                    // Llenar campos estándar
                    $('#anio').val(data.anio);
                    $('#codigo_inventario').val(data.codigo_inventario);
                    $('#descripcion').val(data.descripcion);
                    $('#marca').val(data.marca);
                    $('#serie').val(data.serie);
                    $('#modelo').val(data.modelo);
                    
                    // Llenar nuevos campos
                    $('#tipo_bien').val(data.tipo_bien && data.tipo_bien !== '' ? data.tipo_bien : 'Mobiliario');
                    $('#dimensiones').val(data.dimensiones);
                    $('#situacion').val(data.situacion && data.situacion !== '' ? data.situacion : 'Uso');
                    $('#otras_caracteristicas').val(data.otras_caracteristicas);
                    $('#ubicacion_fisica').val(data.ubicacion_fisica);

                    $('#estado_bien').val(data.estado_bien);
                    $('#color').val(data.color);
                    $('#cantidad').val(data.cantidad);
                    $('#observaciones').val(data.observaciones);
                    $('#usuario_responsable').val(data.usuario_responsable);

                    // Llenar unidad policial
                    if (data.id_subunidad) {
                        if ($('#id_subunidad_edit').length) {
                            $('#id_subunidad_edit').val(data.id_subunidad).trigger('change');
                        }
                        if ($('#id_subunidad_hidden').length) {
                            $('#id_subunidad_hidden').val(data.id_subunidad);
                            // Intentar poner el nombre (aunque no lo tengamos directo aquí, el modelo listar lo devolvía como subunidad_nombre)
                            $('#nombre_subunidad_display').text(data.subunidad_nombre || 'Unidad Asignada');
                        }
                    } else {
                        $('#nombre_subunidad_display').text('SIN UNIDAD ASIGNADA');
                    }
                } else {
                    Swal.fire('Error', 'No se pudo cargar el item', 'error').then(() => {
                        window.location.href = 'inventario.php';
                    });
                }
            },
            error: function () {
                Swal.fire('Error', 'No se pudo conectar con el servidor', 'error').then(() => {
                    window.location.href = 'inventario.php';
                });
            }
        });
    }
</script>

<?php include 'layout/footer.php'; ?>