<?php
// vista/limpiar_importacion.php
// Herramienta para limpiar datos importados incorrectamente
session_start();
include 'layout/topbar.php';
include 'layout/sidebar.php';

// Solo administradores pueden acceder
if (empty($_SESSION['id']) || ($_SESSION['rol'] !== 'Administrador' && $_SESSION['rol'] !== 'Super Administrador')) {
    header('Location: ../index.php');
    exit();
}

require_once '../modelo/conexion.php';

$mensaje = '';
$tipo_mensaje = '';

if (isset($_POST['confirmar_limpieza'])) {
    $anio = (int) $_POST['anio'];
    $tipo = $_POST['tipo_documento'];

    require_once '../modelo/conexion.php';

    try {
        $conexion->begin_transaction();

        if ($tipo === 'TODOS') {
            $sql = "DELETE FROM documentos WHERE anio = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param('i', $anio);
            $stmt->execute();
            $total = $stmt->affected_rows;
            
            if ($total > 0) {
                $mensaje = "Se eliminaron TODOS los documentos del año {$anio}. Total: {$total} registros.";
            } else {
                $mensaje = "No hay documentos del año {$anio} para eliminar. La base de datos ya está vacía para este año.";
            }
        } else {
            $sql = "DELETE FROM documentos WHERE anio = ? AND cod_tipo = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param('is', $anio, $tipo);
            $stmt->execute();
            $total = $stmt->affected_rows;
            
            if ($total > 0) {
                $mensaje = "Se eliminaron los documentos de tipo {$tipo} del año {$anio}. Total: {$total} registros.";
            } else {
                $mensaje = "No hay documentos de tipo {$tipo} del año {$anio} para eliminar. Ya están eliminados o no existen.";
            }
        }

        $conexion->commit();
        $tipo_mensaje = 'success';

    } catch (Exception $e) {
        $conexion->rollback();
        $mensaje = "Error al eliminar: " . $e->getMessage();
        $tipo_mensaje = 'danger';
    }
    
    // Guardar mensaje en sesión y redirigir (patrón POST-Redirect-GET)
    $_SESSION['mensaje_limpieza'] = $mensaje;
    $_SESSION['tipo_mensaje_limpieza'] = $tipo_mensaje;
    header('Location: limpiar_importacion.php');
    exit();
}

// Recuperar mensaje de la sesión si existe
$mensaje = '';
$tipo_mensaje = '';
if (isset($_SESSION['mensaje_limpieza'])) {
    $mensaje = $_SESSION['mensaje_limpieza'];
    $tipo_mensaje = $_SESSION['tipo_mensaje_limpieza'];
    unset($_SESSION['mensaje_limpieza']);
    unset($_SESSION['tipo_mensaje_limpieza']);
}
?>

<style>
    .danger-zone {
        border: 3px solid #dc3545;
        border-radius: 10px;
        padding: 30px;
        background-color: #fff5f5;
    }

    .warning-icon {
        font-size: 80px;
        color: #dc3545;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.5;
        }
    }
</style>

<div class="page-content" style="padding-top: 80px; padding-left: 20px; padding-right: 20px;">
    <div class="container-fluid">

        <!-- Botón Volver -->
        <a href="importar_datos.php" class="btn btn-outline-secondary mb-3">
            <i class="fa-solid fa-arrow-left"></i> Volver a Importación
        </a>

        <div class="row justify-content-center">
            <div class="col-md-8">

                <div class="danger-zone">
                    <div class="text-center mb-4">
                        <div class="warning-icon">⚠️</div>
                        <h2 class="text-danger mb-3">ZONA DE LIMPIEZA DE DATOS</h2>
                        <p class="text-muted">
                            Utilice esta herramienta solo si importó datos incorrectos y necesita eliminarlos
                            para volver a importar la versión correcta.
                        </p>
                    </div>

                    <div class="alert alert-warning">
                        <h5><i class="fas fa-exclamation-triangle me-2"></i>Advertencias Importantes:</h5>
                        <ul class="mb-0">
                            <li><strong>Esta acción NO se puede deshacer</strong></li>
                            <li>Solo elimina documentos del año y tipo seleccionado</li>
                            <li>Se recomienda hacer un backup de la base de datos antes</li>
                            <li>Solo administradores pueden acceder a esta herramienta</li>
                        </ul>
                    </div>

                    <form method="POST" id="form-limpieza">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Año a Limpiar</label>
                                <select class="form-select" name="anio" required>
                                    <?php
                                    $anioActual = date('Y');
                                    for ($year = 2023; $year <= $anioActual; $year++) {
                                        $selected = ($year == 2025) ? 'selected' : '';
                                        echo "<option value=\"{$year}\" {$selected}>{$year}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tipo de Documento</label>
                                <select class="form-select" name="tipo_documento" required>
                                    <option value="">Seleccione...</option>
                                    <?php
                                    $tiposDocumento = require_once '../config/tipos_documento.php';
                                    foreach ($tiposDocumento as $codigo => $nombre) {
                                        echo "<option value=\"{$codigo}\">{$nombre}</option>";
                                    }
                                    ?>
                                    <option value="TODOS" style="background-color: #dc3545; color: white;">
                                        ❌ TODOS LOS TIPOS (PELIGROSO)
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" name="confirmar_limpieza" class="btn btn-danger btn-lg">
                                <i class="fas fa-trash me-2"></i> ELIMINAR DATOS SELECCIONADOS
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if ($mensaje): ?>
<script>
    console.log('Mensaje detectado: <?php echo addslashes($mensaje); ?>');
    console.log('Tipo: <?php echo $tipo_mensaje; ?>');
    
    // Esperar a que el DOM esté listo
    window.addEventListener('load', function() {
        Swal.fire({
            title: '<?php echo $tipo_mensaje === 'success' ? '✅ Eliminación Exitosa' : '❌ Error'; ?>',
            html: '<?php echo addslashes($mensaje); ?>',
            icon: '<?php echo $tipo_mensaje === 'success' ? 'success' : 'error'; ?>',
            confirmButtonColor: '#00779e',
            confirmButtonText: 'Entendido',
            allowOutsideClick: false
        });
    });
</script>
<?php endif; ?>

<script>
    document.getElementById('form-limpieza').addEventListener('submit', function (e) {
        e.preventDefault();

        const anio = this.querySelector('[name="anio"]').value;
        const tipo = this.querySelector('[name="tipo_documento"]').value;
        const tipoTexto = this.querySelector('[name="tipo_documento"] option:checked').text;

        if (!tipo) {
            Swal.fire('Error', 'Debe seleccionar un tipo de documento', 'error');
            return;
        }

        let mensaje = `Se eliminarán TODOS los documentos de tipo <strong>${tipoTexto}</strong> del año <strong>${anio}</strong>`;

        if (tipo === 'TODOS') {
            mensaje = `<span style="color: red; font-size: 18px;">⚠️ SE ELIMINARÁN <strong>TODOS</strong> LOS DOCUMENTOS DEL AÑO ${anio}</span><br><br>Esta acción es <strong>IRREVERSIBLE</strong>`;
        }

        Swal.fire({
            title: '¿Está absolutamente seguro?',
            html: mensaje,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            input: 'checkbox',
            inputPlaceholder: 'Confirmo que quiero eliminar estos datos',
            inputValidator: (result) => {
                return !result && 'Debe confirmar marcando la casilla'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Agregar campo oculto para que PHP detecte la confirmación
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'confirmar_limpieza';
                hiddenInput.value = '1';
                this.appendChild(hiddenInput);
                
                // Enviar formulario
                this.submit();
            }
        });
    });
</script>