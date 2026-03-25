<?php
// vista/capacitaciones.php
require_once __DIR__ . "/../modelo/conexion.php";

if (!isset($_SESSION['id'])) {
    header("Location: " . (defined('BASE_URL') ? BASE_URL : '/sistem_job/') . "vista/login/login.php");
    exit();
}

require_once __DIR__ . '/../modelo/PermisosModelo.php';

// Verificar permisos
$permisosModelo = new PermisosModelo();
$idUsuario = $_SESSION['id'];
$puedeCrear = $_SESSION['rol'] === 'Super Administrador' || $permisosModelo->tienePermiso($idUsuario, 'CAPACITACIONES', 'CREAR');
$puedeEditar = $_SESSION['rol'] === 'Super Administrador' || $permisosModelo->tienePermiso($idUsuario, 'CAPACITACIONES', 'EDITAR');
$puedeEliminar = $_SESSION['rol'] === 'Super Administrador' || $permisosModelo->tienePermiso($idUsuario, 'CAPACITACIONES', 'ELIMINAR');

include 'layout/topbar.php';
include 'layout/sidebar.php';
?>

<style>
    /* Estilos PRO Nivel Promo */
    .content {
        margin-left: 260px;
        /* Ancho estimado del Sidebar */
        padding-top: 80px;
        /* Altura del Topbar */
        padding-left: 20px;
        padding-right: 20px;
        transition: all 0.3s;
    }

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

    /* MODALES PRO */
    .modal-content {
        border-radius: 20px;
        border: none;
        box-shadow: 0 15px 50px rgba(0, 0, 0, 0.3);
        overflow: hidden;
    }

    .modal-header {
        background: #f8f9fa;
        border-bottom: 1px solid #edf2f7;
        padding: 20px 30px;
    }

    .modal-title {
        font-weight: 800;
        letter-spacing: -0.5px;
    }

    .form-control {
        border-radius: 10px;
        border: 2px solid #edf2f7;
        padding: 12px 15px;
        font-size: 0.95rem;
        transition: all 0.3s;
        background-color: #fcfcfc;
    }

    .form-control:focus {
        border-color: #203a43;
        box-shadow: 0 0 0 4px rgba(32, 58, 67, 0.1);
        background-color: white;
    }

    .form-floating>label {
        padding-left: 15px;
    }

    .btn-gradient-primary {
        background: linear-gradient(135deg, #006db3 0%, #00a8cc 100%);
        border: none;
        color: white;
        font-weight: 700;
        letter-spacing: 0.5px;
        box-shadow: 0 4px 15px rgba(0, 168, 204, 0.4);
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .btn-gradient-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 168, 204, 0.6);
        color: white;
    }

    .btn-gradient-warning {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        border: none;
        color: white;
        font-weight: 700;
        letter-spacing: 0.5px;
        box-shadow: 0 4px 15px rgba(245, 87, 108, 0.4);
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .btn-gradient-warning:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(245, 87, 108, 0.6);
        color: white;
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

    .pro-header-content {
        position: relative;
        z-index: 2;
    }

    .pro-title {
        font-weight: 800;
        letter-spacing: -0.5px;
        margin-bottom: 10px;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }

    .pro-subtitle {
        font-weight: 300;
        opacity: 0.9;
        font-size: 1.1rem;
    }

    /* Buscador Flotante */
    .search-container {
        position: relative;
        max-width: 500px;
    }

    .search-input {
        border-radius: 50px;
        padding: 15px 25px 15px 50px;
        border: none;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .search-input:focus {
        box-shadow: 0 8px 25px rgba(0, 109, 179, 0.25);
        outline: none;
    }

    .search-icon {
        position: absolute;
        left: 20px;
        top: 50%;
        transform: translateY(-50%);
        color: #aaa;
    }

    /* Tarjetas PRO - VERSIÓN COMPACTA Y ESCALABLE */
    .video-card {
        border: none;
        border-radius: 12px;
        overflow: hidden;
        transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.3s ease;
        background: white;
        height: 100%;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .video-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
    }

    .thumbnail-container {
        position: relative;
        overflow: hidden;
        padding-top: 56.25%;
        /* 16:9 Aspect Ratio - Mantenido para calidad */
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .thumbnail-img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.4s ease, opacity 0.3s ease;
    }

    .video-card:hover .thumbnail-img {
        transform: scale(1.08);
        opacity: 0.9;
    }

    .play-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: all 0.3s ease;
        backdrop-filter: blur(3px);
    }

    .video-card:hover .play-overlay {
        opacity: 1;
    }

    .play-btn-circle {
        width: 56px;
        height: 56px;
        background: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #0f2027;
        font-size: 20px;
        box-shadow: 0 4px 20px rgba(255, 255, 255, 0.6);
        transform: scale(0.85);
        transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .video-card:hover .play-btn-circle {
        transform: scale(1.1);
    }

    .card-body-pro {
        padding: 14px 16px 12px 16px;
    }

    .card-title-pro {
        font-weight: 700;
        font-size: 0.95rem;
        color: #2c3e50;
        margin-bottom: 6px;
        line-height: 1.3;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 2.6rem;
    }

    .card-desc-pro {
        color: #7f8c8d;
        font-size: 0.82rem;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 2.3rem;
    }

    .card-footer-pro {
        padding: 10px 16px;
        border-top: 1px solid #f0f2f5;
        background: #fafbfc;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .card-footer-pro small {
        font-size: 0.75rem;
    }

    .badge-file {
        background: #e1f5fe;
        color: #0288d1;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .admin-actions {
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 10;
        display: flex;
        gap: 5px;
        opacity: 0;
        transition: opacity 0.2s;
    }

    .video-card:hover .admin-actions {
        opacity: 1;
    }

    .btn-action-pro {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transition: transform 0.2s;
    }

    .btn-action-pro:hover {
        transform: scale(1.1);
    }

    .btn-edit-pro {
        background: white;
        color: #f39c12;
    }

    .btn-del-pro {
        background: white;
        color: #e74c3c;
    }

    /* Animación de entrada */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-card {
        animation: fadeInUp 0.5s ease forwards;
        opacity: 0;
    }
</style>

<div class="content">
    <div class="container-fluid p-0">

        <!-- Hero Section -->
        <div class="pro-header">
            <div class="row align-items-center pro-header-content">
                <div class="col-md-7">
                    <h1 class="pro-title"><i class="fa-brands fa-youtube me-2" style="color: #ff0000;"></i>Centro de
                        Capacitaciones</h1>
                    <p class="pro-subtitle mb-0">Accede a videograbaciones, tutoriales y material de instrucción para
                        potenciar tus habilidades.</p>
                </div>
                <div class="col-md-5 text-md-end mt-3 mt-md-0">
                    <?php if ($puedeCrear): ?>
                        <button class="btn btn-light text-dark fw-bold px-4 py-2 shadow-sm rounded-pill"
                            data-bs-toggle="modal" data-bs-target="#modalNuevaCapacitacion">
                            <i class="fas fa-plus-circle me-2 text-primary"></i>Nueva Capacitación
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Barra de Herramientas -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="search-container">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchInput" class="search-input w-100"
                        placeholder="Buscar por título, temas...">
                </div>
            </div>
            <div class="col-md-6 text-end align-self-center d-none d-md-block text-muted">
                <small><i class="fas fa-layer-group me-1"></i> Mostrando contenido multimedia formativo</small>
            </div>
        </div>

        <!-- Grid de Capacitaciones -->
        <div class="row g-3" id="gridCapacitaciones">
            <!-- Se llenará vía JS con animación -->
        </div>

        <!-- Estado Vacío (Hidden by default) -->
        <div id="emptyState" class="text-center py-5 d-none">
            <img src="https://cdni.iconscout.com/illustration/premium/thumb/folder-is-empty-4064360-3363921.png"
                alt="Vacio" style="height: 200px; opacity: 0.7;">
            <h4 class="text-muted mt-3">No hay capacitaciones aún</h4>
            <p class="text-muted">¡Pronto subiremos nuevo material!</p>
        </div>

    </div>
</div>

<!-- Modal Nueva Capacitación -->
<div class="modal fade" id="modalNuevaCapacitacion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header bg-light border-0">
                <h5 class="modal-title fw-bold text-primary"><i class="fas fa-upload me-2"></i>Registrar Contenido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formCapacitacion">
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted text-uppercase">Título del Video</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-primary"><i
                                    class="fas fa-heading"></i></span>
                            <input type="text" class="form-control border-start-0 ps-0" id="titulo" name="titulo"
                                placeholder="Ej. Nuevo Procedimiento Operativo" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted text-uppercase">Descripción Breve</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-primary align-items-start pt-2"><i
                                    class="fas fa-align-left"></i></span>
                            <textarea class="form-control border-start-0 ps-0" id="descripcion" name="descripcion"
                                placeholder="Detalla de qué trata este contenido..." style="height: 100px"></textarea>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted text-uppercase">Enlace de YouTube</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-danger"><i
                                    class="fab fa-youtube"></i></span>
                            <input type="url" class="form-control border-start-0 ps-0" id="url_video" name="url_video"
                                placeholder="https://www.youtube.com/watch?v=..." required>
                        </div>
                        <div class="form-text small mt-1 ps-2"><i class="fas fa-info-circle me-1"></i> Copia y pega la
                            URL completa del video.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted text-uppercase">Material Adjunto
                            (Opcional)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-success"><i
                                    class="fas fa-file-pdf"></i></span>
                            <input type="file" class="form-control border-start-0 ps-0" name="archivo">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-gradient-primary w-100 py-3 rounded-pill fw-bold shadow-sm">
                        <i class="fas fa-save me-2"></i> Guardar Publicación
                    </button>
                    <div class="text-center mt-3">
                        <button type="button" class="btn btn-sm btn-link text-muted text-decoration-none"
                            data-bs-dismiss="modal">Cancelar operacion</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Capacitación -->
<div class="modal fade" id="modalEditarCapacitacion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header bg-light border-0">
                <h5 class="modal-title fw-bold text-warning"><i class="fas fa-edit me-2"></i>Editar Contenido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formEditarCapacitacion">
                    <input type="hidden" name="id" id="edit_id">

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted text-uppercase">Título del Video</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-warning"><i
                                    class="fas fa-heading"></i></span>
                            <input type="text" class="form-control border-start-0 ps-0" name="titulo" id="edit_titulo"
                                required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted text-uppercase">Descripción Breve</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-warning align-items-start pt-2"><i
                                    class="fas fa-align-left"></i></span>
                            <textarea class="form-control border-start-0 ps-0" name="descripcion" id="edit_descripcion"
                                rows="3"></textarea>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted text-uppercase">Enlace de YouTube</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-danger"><i
                                    class="fab fa-youtube"></i></span>
                            <input type="url" class="form-control border-start-0 ps-0" name="url_video"
                                id="edit_url_video" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted text-uppercase">Reemplazar Material
                            (Opcional)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-success"><i
                                    class="fas fa-file-pdf"></i></span>
                            <input type="file" class="form-control border-start-0 ps-0" name="archivo">
                        </div>
                        <div class="form-text small mt-1 ps-2">Deja vacío si no quieres cambiar el archivo actual.</div>
                    </div>

                    <button type="submit" class="btn btn-gradient-warning w-100 py-3 rounded-pill fw-bold shadow-sm">
                        <i class="fas fa-sync-alt me-2"></i> Actualizar Cambios
                    </button>
                    <div class="text-center mt-3">
                        <button type="button" class="btn btn-sm btn-link text-muted text-decoration-none"
                            data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ver Video (Teatro Mode) -->
<div class="modal fade" id="modalVerVideo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-0 shadow-lg" style="overflow: hidden; border-radius: 15px;">
            <div class="modal-header border-0 bg-transparent position-absolute w-100 z-3"
                style="background: linear-gradient(to bottom, rgba(0,0,0,0.8), transparent);">
                <h5 class="modal-title text-truncate pe-4" id="tituloVideo"></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 position-relative">
                <div class="ratio ratio-16x9">
                    <iframe id="iframeVideo" src="" allowfullscreen style="border:0;"></iframe>
                </div>
            </div>
            <div class="modal-footer bg-secondary border-0 d-flex justify-content-between align-items-center py-3 px-4">
                <div class="text-white-50 small" style="max-width: 60%;" id="descVideo"></div>
                <div id="btnDescargaContainer"></div>
            </div>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?>

<script>
    const canEdit = <?php echo $puedeEditar ? 'true' : 'false'; ?>;
    const canDelete = <?php echo $puedeEliminar ? 'true' : 'false'; ?>;
    let allCapacitaciones = []; // Para el buscador

    document.addEventListener('DOMContentLoaded', function () {
        cargarCapacitaciones();

        // Filtro Buscador
        document.getElementById('searchInput').addEventListener('keyup', function (e) {
            const term = e.target.value.toLowerCase();
            const filtered = allCapacitaciones.filter(cap =>
                cap.titulo.toLowerCase().includes(term) ||
                cap.descripcion.toLowerCase().includes(term)
            );
            renderizarGrid(filtered);
        });

        // Guardar
        document.getElementById('formCapacitacion').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('../controlador/CapacitacionControlador.php?op=guardar', { method: 'POST', body: formData })
                .then(r => r.json()).then(d => {
                    if (d.status) {
                        bootstrap.Modal.getInstance(document.getElementById('modalNuevaCapacitacion')).hide();
                        this.reset();
                        cargarCapacitaciones();
                        Swal.fire({
                            icon: 'success',
                            title: '¡Publicado!',
                            text: 'La capacitación se ha registrado correctamente.',
                            confirmButtonColor: '#006db3'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: d.msg,
                            confirmButtonColor: '#d33'
                        });
                    }
                });
        });

        // Editar
        document.getElementById('formEditarCapacitacion').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('../controlador/CapacitacionControlador.php?op=actualizar', { method: 'POST', body: formData })
                .then(r => r.json()).then(d => {
                    if (d.status) {
                        bootstrap.Modal.getInstance(document.getElementById('modalEditarCapacitacion')).hide();
                        this.reset();
                        cargarCapacitaciones();
                        Swal.fire({
                            icon: 'success',
                            title: '¡Actualizado!',
                            text: 'Los cambios se han guardado exitosamente.',
                            confirmButtonColor: '#006db3'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: d.msg,
                            confirmButtonColor: '#d33'
                        });
                    }
                });
        });
    });

    function cargarCapacitaciones() {
        fetch('../controlador/CapacitacionControlador.php?op=listar')
            .then(r => r.json())
            .then(data => {
                allCapacitaciones = data.data;
                renderizarGrid(allCapacitaciones);
            });
    }

    function renderizarGrid(data) {
        const contenedor = document.getElementById('gridCapacitaciones');
        const emptyState = document.getElementById('emptyState');
        contenedor.innerHTML = '';

        if (data.length === 0) {
            emptyState.classList.remove('d-none');
            return;
        }

        emptyState.classList.add('d-none');

        data.forEach((cap, index) => {
            const videoId = extraerVideoID(cap.url_video);
            const thumb = videoId ? `https://img.youtube.com/vi/${videoId}/hqdefault.jpg` : 'assets/img/video-placeholder.png';
            const hasFile = (cap.archivo_adjunto && cap.archivo_adjunto !== 'null' && cap.archivo_adjunto !== '');

            // Retraso de animación "scalera"
            const delay = index * 100; // 100ms por cada tarjeta

            // Botones Admin
            let adminActions = '';
            if (canEdit || canDelete) {
                adminActions = '<div class="admin-actions">';

                if (canEdit) {
                    adminActions += `
                    <button class="btn-action-pro btn-edit-pro" onclick="editarCapacitacion(${cap.id}, event)" title="Editar">
                        <i class="fas fa-pencil-alt"></i>
                    </button>
                `;
                }

                if (canDelete) {
                    adminActions += `
                    <button class="btn-action-pro btn-del-pro" onclick="eliminarCapacitacion(${cap.id}, event)" title="Eliminar">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                `;
                }

                adminActions += '</div>';
            }

            const cardHTML = `
            <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl-2 animate-card" style="animation-delay: ${delay}ms">
                <div class="video-card shadow-sm" onclick="verVideo('${cap.id}', '${cap.titulo}', '${videoId}', '${cap.archivo_adjunto || ''}', \`${cap.descripcion}\`)">
                    
                    <div class="thumbnail-container">
                        ${adminActions}
                        <img src="${thumb}" class="thumbnail-img" alt="${cap.titulo}">
                        <div class="play-overlay">
                            <div class="play-btn-circle">
                                <i class="fas fa-play" style="margin-left: 4px;"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body-pro">
                        <h5 class="card-title-pro">${cap.titulo}</h5>
                        <p class="card-desc-pro">${cap.descripcion || 'Sin descripción disponible.'}</p>
                    </div>
                    
                    <div class="card-footer-pro">
                        <div>
                            <small class="text-muted d-block"><i class="far fa-clock me-1"></i> ${formatearFecha(cap.fecha_creacion)}</small>
                        </div>
                        ${hasFile ? '<span class="badge-file"><i class="fas fa-paperclip me-1"></i>Material</span>' : ''}
                    </div>
                </div>
            </div>
        `;
            contenedor.innerHTML += cardHTML;
        });

        // Inicializar Tooltips de Bootstrap
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    }

    function formatearFecha(fecha) {
        if (!fecha) return '';
        const d = new Date(fecha.replace(/-/g, '/')); // Fix para compatibilidad Safari/Firefox
        return d.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }

    function extraerVideoID(url) {
        if (!url) return null;
        const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
        const match = url.match(regExp);
        return (match && match[2].length === 11) ? match[2] : null;
    }

    function verVideo(id, titulo, videoId, adjunto, descripcion) {
        document.getElementById('tituloVideo').innerText = titulo;
        if (videoId) {
            document.getElementById('iframeVideo').src = `https://www.youtube.com/embed/${videoId}?autoplay=1&rel=0`;
        }
        document.getElementById('descVideo').innerText = descripcion;

        const container = document.getElementById('btnDescargaContainer');
        if (adjunto && adjunto !== 'null' && adjunto !== '') {
            container.innerHTML = `<a href="${adjunto}" class="btn btn-outline-light btn-sm rounded-pill px-3" download target="_blank"><i class="fas fa-cloud-download-alt me-2"></i>Descargar Material</a>`;
        } else {
            container.innerHTML = '';
        }

        var modal = new bootstrap.Modal(document.getElementById('modalVerVideo'));
        modal.show();

        document.getElementById('modalVerVideo').addEventListener('hidden.bs.modal', function () {
            document.getElementById('iframeVideo').src = "";
        });
    }

    // Event para editar evitando que se abra el video al clickear el boton de edit
    function editarCapacitacion(id, event) {
        event.stopPropagation(); // Detiene click en la tarjeta
        fetch(`../controlador/CapacitacionControlador.php?op=obtener&id=${id}`)
            .then(res => res.json())
            .then(resp => {
                if (resp.status) {
                    const data = resp.data;
                    document.getElementById('edit_id').value = data.id;
                    document.getElementById('edit_titulo').value = data.titulo;
                    document.getElementById('edit_descripcion').value = data.descripcion;
                    document.getElementById('edit_url_video').value = data.url_video;

                    // Instanciar y mostrar modal (sin duplicar para no oscurecer la pantalla de forma permanente)
                    var editModal = new bootstrap.Modal(document.getElementById('modalEditarCapacitacion'));
                    editModal.show();
                } else {
                    Swal.fire('Error', resp.msg, 'error');
                }
            });
    }

    function eliminarCapacitacion(id, event) {
        event.stopPropagation();

        Swal.fire({
            title: '¿Estás seguro?',
            text: "¡No podrás revertir esto! La capacitación se eliminará permanentemente.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('id', id);

                fetch('../controlador/CapacitacionControlador.php?op=eliminar', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status) {
                            cargarCapacitaciones();
                            Swal.fire(
                                '¡Eliminado!',
                                'La capacitación ha sido eliminada.',
                                'success'
                            );
                        } else {
                            Swal.fire('Error', 'No se pudo eliminar el registro.', 'error');
                        }
                    });
            }
        });
    }
</script>