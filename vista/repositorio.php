<?php
// vista/repositorio.php
require_once __DIR__ . "/../modelo/conexion.php";

if (empty($_SESSION['id'])) {
    header("Location: " . (defined('BASE_URL') ? BASE_URL : '/sistem_job/') . "vista/login/login.php");
    exit();
}

require_once __DIR__ . '/../controlador/autocargar_permisos.php';
if (isset($conn) && $conn instanceof mysqli) {
    recargarPermisosUsuario($_SESSION['id'], $conn);
} elseif (isset($conexion) && $conexion instanceof mysqli) {
    recargarPermisosUsuario($_SESSION['id'], $conexion);
}

// 2. Seguridad Estricta (Bloqueo VER)
if ($_SESSION['rol'] !== 'Administrador' && $_SESSION['rol'] !== 'Super Administrador' && !isset($_SESSION['permisos']['REPOSITORIO']['VER'])) {
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

// Determinar permisos para JS
$pEditar = ($_SESSION['rol'] === 'Administrador' || isset($_SESSION['permisos']['REPOSITORIO']['EDITAR'])) ? 'true' : 'false';
$pEliminar = ($_SESSION['rol'] === 'Administrador' || isset($_SESSION['permisos']['REPOSITORIO']['ELIMINAR'])) ? 'true' : 'false';

// Determinar si se muestran acciones
$mostrarAcciones = ($pEditar === 'true' || $pEliminar === 'true') ? 'true' : 'false';
?>
<script>
    const PERMISO_EDITAR = <?= $pEditar ?>;
    const PERMISO_ELIMINAR = <?= $pEliminar ?>;
    const MOSTRAR_ACCIONES = <?= $mostrarAcciones ?>;
</script>
<?php // Re-open PHP if needed
?>

<style>
    /* Estilos PRO - Repositorio */
    .pro-header {
        background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
        color: white;
        padding: 10px 30px;
        border-radius: 15px;
        position: relative;
        overflow: hidden;
        margin-bottom: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    }

    .pro-header::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        background: url('https://www.transparenttextures.com/patterns/cubes.png');
        opacity: 0.1;
    }

    .pro-title {
        font-weight: 800;
        letter-spacing: -0.5px;
        margin-bottom: 10px;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .pro-subtitle {
        font-weight: 300;
        opacity: 0.9;
        font-size: 1.1rem;
    }

    /* Estilos Government PRO (Inspirado en El Peruano) */
    .gov-card {
        background: white;
        padding: 15px;
        border: 1px solid #e0e0e0;
        display: flex;
        gap: 15px;
        transition: all 0.2s ease;
        height: 100%;
        position: relative;
        cursor: pointer; /* Toda la tarjeta es clickeable */
    }

    .gov-card:hover {
        border-color: #00a19a; /* Borde Teal al pasar el mouse */
        box-shadow: 0 8px 20px rgba(0,161,154,0.15); /* Sombra suave con color institucional */
        transform: translateY(-2px);
    }
    
    .gov-card:hover .gov-title {
        color: #007c76; /* Oscurecer un poco el título al hacer hover */
        text-decoration: underline;
    }

    /* La "hoja" de vista previa a la izquierda */
    .gov-thumb {
        width: 90px;
        height: 120px;
        background: #fff;
        border: 1px solid #ccc;
        box-shadow: 2px 2px 5px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0; /* No encoger */
        position: relative;
    }
    
    .gov-thumb::after {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        border: 4px solid rgba(255,255,255,0.5); /* Marco interno */
    }

    .gov-body {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .gov-title {
        color: #00a19a; /* Color Teal institucional */
        font-weight: 700;
        font-size: 1rem;
        line-height: 1.3;
        margin-bottom: 4px;
        /* Limitar a 3 líneas */
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .gov-meta {
        font-size: 0.85rem;
        color: #555;
        font-weight: 500;
        margin-bottom: 12px;
        text-transform: uppercase;
    }

    .gov-meta span.separator {
        color: #ccc;
        margin: 0 5px;
    }

    .btn-gov-download {
        background: white;
        color: #3f51b5; /* Azul institucional */
        border: 1px solid #3f51b5;
        font-weight: 600;
        padding: 6px 15px;
        width: 100%;
        text-align: center;
        border-radius: 0; /* Botones cuadrados como la imagen */
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-gov-download:hover {
        background: #3f51b5;
        color: white;
    }

    /* Acciones flotantes (editar/eliminar) discretas */
    .gov-actions-floating {
        position: absolute;
        top: 5px;
        right: 5px;
        opacity: 0;
        transition: opacity 0.2s;
        background: rgba(255,255,255,0.9);
        padding: 2px;
        border-radius: 4px;
        border: 1px solid #eee;
    }
    .gov-card:hover .gov-actions-floating {
        opacity: 1;
    }

    /* Botón de Previsualización (Pill Style) */
    .btn-gov-preview {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        border-radius: 50px; /* Pill shape */
        background-color: #e0f7fa; /* Light Teal background */
        color: #00838f !important; /* Dark Teal text */
        font-size: 0.8rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 1px solid transparent;
        margin-bottom: 8px;
    }

    .btn-gov-preview:hover {
        background-color: #00bcd4;
        color: white !important;
        transform: scale(1.05); /* Sutil efecto pop */
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
</style>

<div class="page-content" style="padding: 20px; padding-top: 80px;">
    <div class="container-fluid">

        <!-- Alerta de Almacenamiento -->
        <div id="alertaAlmacenamiento" class="alert alert-warning align-items-center mb-4 shadow-sm" role="alert"
            style="display: none; border-radius: 10px;">
            <i class="fa-solid fa-triangle-exclamation fa-2x me-3"></i>
            <div>
                <h6 class="alert-heading fw-bold mb-1">Advertencia de Almacenamiento</h6>
                <p class="mb-0" id="mensajeAlmacenamiento"></p>
            </div>
        </div>

        <!-- Pro Header -->
        <div class="pro-header">
            <div class="row align-items-center position-relative" style="z-index: 2;">
                <div class="col-md-7">
                    <h1 class="pro-title"><i class="fa-brands fa-youtube me-2" style="color: #ff0000;"></i>Repositorio de Documentos</h1>
                    <p class="pro-subtitle mb-0">Gestión centralizada de documentos institucionales, clasificados por año y categoría.</p>
                </div>
                <div class="col-md-5 text-md-end mt-3 mt-md-0">
                    <?php if ($_SESSION['rol'] === 'Administrador' || $_SESSION['rol'] === 'Super Administrador'): ?>
                        <button class="btn btn-light text-primary fw-bold px-3 py-2 shadow-sm rounded-pill me-2" id="btnAdministrarCategorias">
                            <i class="fa-solid fa-tags me-2"></i> Categorías
                        </button>
                    <?php endif; ?>
                    <?php if ($_SESSION['rol'] === 'Administrador' || $_SESSION['rol'] === 'Super Administrador' || isset($_SESSION['permisos']['REPOSITORIO']['CREAR'])): ?>
                        <button class="btn btn-light text-dark fw-bold px-4 py-2 shadow-sm rounded-pill" id="btnSubirArchivo">
                            <i class="fa-solid fa-cloud-upload me-2 text-primary"></i> Subir Documento
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Filtros y Búsqueda -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Año</label>
                        <select class="form-select" id="filtroAnio">
                            <option value="">Todos los años</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Categoría</label>
                        <select class="form-select" id="filtroCategoria">
                            <option value="">Todas las categorías</option>
                            <option value="Demandas">Demandas</option>
                            <option value="Órdenes">Órdenes</option>
                            <option value="Programas">Programas</option>
                            <option value="Documentos Administrativos">Documentos Administrativos</option>
                            <option value="Otros">Otros</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Buscar</label>
                        <input type="text" class="form-control" id="busqueda"
                            placeholder="Buscar por nombre o descripción...">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-secondary w-100" id="btnLimpiarFiltros">
                            <i class="fa-solid fa-filter-circle-xmark me-1"></i> Limpiar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="row mb-4" id="estadisticas">
            <div class="col-md-3">
                <div class="stat-card-modern">
                    <div class="stat-icon-circle bg-primary bg-opacity-10 text-primary">
                        <i class="fa-solid fa-file"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bold" id="totalDocumentos">0</h4>
                        <small class="text-muted">Documentos</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card-modern">
                    <div class="stat-icon-circle bg-success bg-opacity-10 text-success">
                        <i class="fa-solid fa-calendar"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bold" id="totalAnios">0</h4>
                        <small class="text-muted">Años Registrados</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card-modern">
                    <div class="stat-icon-circle bg-warning bg-opacity-10 text-warning">
                        <i class="fa-solid fa-tags"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bold" id="totalCategorias">0</h4>
                        <small class="text-muted">Categorías</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card-modern">
                    <div class="stat-icon-circle bg-info bg-opacity-10 text-info">
                        <i class="fa-solid fa-hard-drive"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bold" id="espacioTotal">0 MB</h4>
                        <small class="text-muted">Espacio Usado</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vista de Documentos -->
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Documentos</h5>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-secondary active" id="vistaGrid">
                        <i class="fa-solid fa-grip"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="vistaLista">
                        <i class="fa-solid fa-list"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Vista Grid (tipo Google Drive) -->
                <div id="documentosGrid" class="row g-3">
                    <!-- Los documentos se cargarán aquí dinámicamente -->
                </div>

                <!-- Vista Lista (tabla) -->
                <div id="documentosLista" style="display: none;">
                    <table class="table table-hover" id="tablaDocumentos">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Categoría</th>
                                <th>Año</th>
                                <th>Tamaño</th>
                                <th>Subido por</th>
                                <th>Fecha</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Los documentos se cargarán aquí -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal Vista Previa -->
<div class="modal fade" id="modalVistaPrevia" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="height: 90vh;">
            <div class="modal-header bg-dark text-white border-0 py-2">
                <h6 class="modal-title text-truncate" id="tituloVistaPrevia" style="max-width: 80%;">
                    <i class="fas fa-eye me-2"></i>Vista Previa
                </h6>
                <div class="ms-auto">
                    <button id="btnDescargarVista" class="btn btn-sm btn-outline-light me-2">
                        <i class="fas fa-download"></i>
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-0 bg-light d-flex align-items-center justify-content-center" id="cuerpoVistaPrevia" style="overflow: hidden;">
                <!-- Contenido dinámico (Iframe o Imagen) -->
            </div>
        </div>
    </div>
</div>

<!-- Modal Subir Archivo -->
<div class="modal fade" id="modalSubirArchivo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-cloud-upload me-2"></i>Subir Documento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formSubirArchivo" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Archivo <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" name="archivo" id="archivo" required
                            accept=".pdf,.xlsx,.xls,.doc,.docx,.jpg,.jpeg,.png,.gif,.zip,.rar">
                        <small class="text-muted">Máximo 50MB. Formatos: PDF, Excel, Word, Imágenes, ZIP, RAR</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Año <span class="text-danger">*</span></label>
                        <select class="form-select" name="anio" id="anioSubida" required>
                            <?php
                            $anioActual = date('Y');
                            for ($i = $anioActual; $i >= 2020; $i--) {
                                echo "<option value=\"{$i}\">{$i}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Categoría</label>
                        <select class="form-select" name="categoria" id="categoriaSubida">
                            <option value="">Sin categoría</option>
                            <option value="Demandas">Demandas</option>
                            <option value="Órdenes">Órdenes</option>
                            <option value="Programas">Programas</option>
                            <option value="Documentos Administrativos">Documentos Administrativos</option>
                            <option value="Otros">Otros</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Descripción</label>
                        <textarea class="form-control" name="descripcion" rows="3"
                            placeholder="Descripción opcional del documento..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarArchivo">
                        <i class="fa-solid fa-upload me-1"></i> Subir
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Documento -->
<div class="modal fade" id="modalEditarDocumento" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-edit me-2"></i>Editar Documento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditarDocumento">
                <input type="hidden" id="editarId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre del archivo</label>
                        <input type="text" class="form-control" id="editarNombre" readonly style="background-color: #e9ecef; cursor: not-allowed;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Categoría</label>
                        <select class="form-select" id="editarCategoria">
                            <option value="">Sin categoría</option>
                            <option value="Demandas">Demandas</option>
                            <option value="Órdenes">Órdenes</option>
                            <option value="Programas">Programas</option>
                            <option value="Documentos Administrativos">Documentos Administrativos</option>
                            <option value="Otros">Otros</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Descripción</label>
                        <textarea class="form-control" id="editarDescripcion" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-save me-1"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Administrar Categorías -->
<div class="modal fade" id="modalAdministrarCategorias" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-tags me-2"></i>Administrar Categorías</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Tabla de categorías -->
                <div class="table-responsive mb-3">
                    <table class="table table-hover table-sm align-middle" style="font-size: 0.85rem;">
                        <thead>
                            <tr>
                                <th>Categoría</th>
                                <th>Descripción</th>
                                <th class="text-center">Documentos</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tablaCategorias">
                            <!-- Se carga dinámicamente -->
                        </tbody>
                    </table>
                </div>

                <!-- Formulario para nueva categoría -->
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="card-title"><i class="fa-solid fa-plus-circle me-2"></i>Agregar Nueva Categoría</h6>
                        <form id="formNuevaCategoria">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <input type="text" class="form-control" id="nuevaCategoriaNombre"
                                        placeholder="Nombre *" required>
                                </div>
                                <div class="col-md-5">
                                    <input type="text" class="form-control" id="nuevaCategoriaDescripcion"
                                        placeholder="Descripción (opcional)">
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fa-solid fa-plus me-1"></i> Agregar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Categoría -->
<div class="modal fade" id="modalEditarCategoria" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-edit me-2"></i>Editar Categoría</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditarCategoria">
                <input type="hidden" id="editarCategoriaId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editarCategoriaNombre" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Descripción</label>
                        <input type="text" class="form-control" id="editarCategoriaDescripcion">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-save me-1"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Bootstrap JS se carga en topbar.php para evitar duplicados -->

<script src="../assets/js/repositorio.js?v=<?php echo time(); ?>"></script>

<?php include 'layout/footer.php'; ?>