<?php
// vista/inventario_nuevo.php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE)
    session_start();

if (empty($_SESSION['id'])) {
    header("Location: ../vista/login/login.php");
    exit();
}

// VERIFICACIÓN DE PERMISOS: Solo usuarios con permiso CREAR pueden acceder
require_once '../modelo/PermisosModelo.php';
$permisosModelo = new PermisosModelo();

// Verificar si tiene permiso de CREAR en INVENTARIO
$puedeCrear = false;

// Super Administrador y Administrador siempre pueden
if ($_SESSION['rol'] === 'Super Administrador' || $_SESSION['rol'] === 'Administrador') {
    $puedeCrear = true;
} else {
    // Verificar permiso específico
    $puedeCrear = $permisosModelo->tienePermiso($_SESSION['id'], 'INVENTARIO', 'CREAR');
}

if (!$puedeCrear) {
    // Redirigir al listado con mensaje de error
    $_SESSION['error_permiso'] = 'No tienes permisos para crear items en el inventario';
    header("Location: inventario.php");
    exit();
}

include 'layout/topbar.php';
include 'layout/sidebar.php';
?>

<!-- Select2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<div class="page-content" style="padding: 20px; padding-top: 80px;">
    <div class="container-fluid">

        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow-lg border-0 rounded-lg">
                    <div class="card-header text-white d-flex justify-content-between align-items-center"
                        style="background-color: #00779e;">
                        <h4 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Registrar Inventario</h4>
                        <a href="inventario.php" class="btn btn-light btn-sm"
                            style="font-weight: 600; padding: 8px 16px;">
                            <i class="fa-solid fa-arrow-left me-2"></i> Volver al Inventario
                        </a>
                    </div>
                    <div class="card-body bg-light">
                        <form id="formInventario" autocomplete="off">
                            <!-- Campo Año (oculto, automático) -->
                            <input type="hidden" name="anio" value="<?= date('Y') ?>">

                            <!-- Fila 1: Código y Tipo de Bien -->
                            <!-- Fila 1: Código y Tipo de Bien -->
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold small">Código de Inventario <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="codigo_inventario" required
                                        placeholder="Ej: 000879">
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
                                        <option value="REGULAR" selected>REGULAR</option>
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
                                    <select class="form-select" name="situacion" required>
                                        <option value="Uso" selected>Uso (U) - En uso</option>
                                        <option value="Desuso">Desuso (D) - No se usa</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Fila 2: Descripción -->
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-bold small">Descripción del Bien <span
                                            class="text-danger">*</span></label>
                                    <textarea class="form-control" name="descripcion" rows="2" required
                                        placeholder="Ej: COMPUTADORA ALL IN ONE (TODO EN UNO)"></textarea>
                                </div>
                            </div>

                            <!-- Fila 3: Marca, Modelo, Serie y Dimensiones -->
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold small">Marca</label>
                                    <input type="text" class="form-control" name="marca" placeholder="Ej: LENOVO">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold small">Modelo</label>
                                    <input type="text" class="form-control" name="modelo" placeholder="Ej: 10NUA0100">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold small">Serie</label>
                                    <input type="text" class="form-control" name="serie" placeholder="Ej: MP2163V9">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold small">Dimensiones (Largo x Ancho x Alto)</label>
                                    <input type="text" class="form-control" name="dimensiones"
                                        placeholder="Ej: 1.20 x 0.60 x 0.75 m">
                                </div>
                            </div>

                            <!-- Fila 3.6: Otras Características -->
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-bold small">Otras Características / Especificaciones
                                        Técnicas</label>
                                    <textarea class="form-control" name="otras_caracteristicas" rows="2"
                                        placeholder="Ej: Procesador i7, 16GB RAM, Disco 1TB..."></textarea>
                                </div>
                            </div>


                            <!-- Fila 4: Color, Cantidad, Ubicación y Responsable -->
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold small">Color</label>
                                    <input type="text" class="form-control" name="color" placeholder="Ej: NEGRO">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold small">Cantidad</label>
                                    <input type="number" class="form-control" name="cantidad" value="1" min="1">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold small">Ubicación Física del Bien</label>
                                    <input type="text" class="form-control" name="ubicacion_fisica"
                                        placeholder="Ej: Oficina de Logística - Piso 2">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold small">Usuario Responsable</label>
                                    <input type="text" class="form-control" name="usuario_responsable"
                                        placeholder="Ej: S1 OMAR JARA">
                                </div>
                            </div>

                            <!-- Nueva Fila: Unidad Policial (Jerarquía) -->
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-bold small text-success"><i
                                            class="fas fa-shield-alt"></i> Unidad Policial Asignada (Jerarquía)</label>
                                    <?php if ($_SESSION['rol'] === 'Super Administrador'): ?>
                                        <select class="form-select select2" name="id_subunidad" required>
                                            <option value="">Seleccione Unidad...</option>
                                            <?php
                                            $sqlSub = "SELECT s.id_subunidad, s.nombre_subunidad, d.nombre_division, r.nombre_region 
                                                       FROM sub_unidades_policiales s
                                                       INNER JOIN divisiones_policiales d ON s.id_division = d.id_division
                                                       INNER JOIN regiones_policiales r ON d.id_region = r.id_region
                                                       ORDER BY r.nombre_region, d.nombre_division, s.nombre_subunidad";
                                            $resSub = $conexion->query($sqlSub);
                                            while ($sub = $resSub->fetch_assoc()) {
                                                $selected = ($_SESSION['id_subunidad'] == $sub['id_subunidad']) ? 'selected' : '';
                                                echo "<option value='{$sub['id_subunidad']}' $selected>{$sub['nombre_region']} - {$sub['nombre_division']} - {$sub['nombre_subunidad']}</option>";
                                            }
                                            ?>
                                        </select>
                                    <?php else: ?>
                                        <input type="hidden" name="id_subunidad"
                                            value="<?= $_SESSION['id_subunidad'] ?? '' ?>">
                                        <div class="form-control bg-light">
                                            <?= $_SESSION['nombre_region'] ? $_SESSION['nombre_region'] . ' - ' . $_SESSION['nombre_division'] . ' - ' . $_SESSION['nombre_subunidad'] : 'SIN UNIDAD ASIGNADA (Contacte al Administrador)' ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Fila 6: Observaciones -->
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-bold small">Observaciones</label>
                                    <textarea class="form-control" name="observaciones" rows="2"
                                        placeholder="Observaciones adicionales..."></textarea>
                                </div>
                            </div>

                            <!-- Botón Guardar -->
                            <div class="row">
                                <div class="col-md-12 text-center">
                                    <button type="submit" class="btn btn-success btn-lg" id="btnGuardar">
                                        <i class="fa-solid fa-save me-2"></i> Guardar Item
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
                $('#helper_raee').fadeIn();
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

        // En este nuevo formato, todos los campos están visibles por defecto
        // ya que el anexo pide llenar todo si aplica.
        // Se puede agregar lógica específica si el usuario lo pide, pero
        // por ahora dejamos todo accesible.

        // Guardar formulario
        $('#formInventario').on('submit', function (e) {
            e.preventDefault();

            const btn = $('#btnGuardar');
            btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i> Guardando...');

            $.ajax({
                url: '../controlador/InventarioControlador.php?op=guardar',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function (response) {
                    btn.prop('disabled', false).html('<i class="fa-solid fa-save me-2"></i> Guardar Item');

                    if (response.status) {
                        Swal.fire({
                            title: '¡Item Registrado!',
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
                    btn.prop('disabled', false).html('<i class="fa-solid fa-save me-2"></i> Guardar Item');
                    Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
                }
            });
        });
    });
</script>

<?php include 'layout/footer.php'; ?>