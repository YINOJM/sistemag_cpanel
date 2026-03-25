<!-- Modal Detalle Sub-Unidad -->
<div class="modal fade" id="modalDetalleSubUnidad" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <!-- Header con Gradiente Personalizado -->
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #006db3 0%, #00a8cc 100%);">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-info-circle me-2"></i>Ficha de la Unidad
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4">
                <!-- Encabezado de la Unidad -->
                <div class="text-center mb-4">
                    <div class="mb-2">
                        <span class="fa-stack fa-2x">
                            <i class="fas fa-circle fa-stack-2x text-light"></i>
                            <i class="fas fa-building fa-stack-1x text-primary"></i>
                        </span>
                    </div>
                    <h4 id="detalleNombre" class="text-dark fw-bold mb-1"></h4>
                    <span id="detalleTipo" class="badge rounded-pill shadow-sm" style="font-size: 0.9em; padding: 0.5em 1em;"></span>
                </div>

                <div class="row g-4">
                    <!-- Columna Jerarquía -->
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 h-100 border-start border-4 border-primary">
                            <h6 class="text-primary fw-bold text-uppercase mb-3" style="font-size: 0.8rem; letter-spacing: 1px;">
                                <i class="fas fa-sitemap me-2"></i>Jerarquía
                            </h6>
                            <div class="mb-2">
                                <small class="text-muted d-block fw-bold display-block">Región Policial</small>
                                <span id="detalleRegion" class="fs-6 text-dark fw-medium"></span>
                            </div>
                            <div>
                                <small class="text-muted d-block fw-bold">División Policial</small>
                                <span id="detalleDivision" class="fs-6 text-dark fw-medium"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Columna Ubicación -->
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 h-100 border-start border-4 border-info">
                            <h6 class="text-info fw-bold text-uppercase mb-3" style="font-size: 0.8rem; letter-spacing: 1px;">
                                <i class="fas fa-map-marker-alt me-2"></i>Ubicación
                            </h6>
                            <div class="mb-2">
                                <small class="text-muted d-block fw-bold">Departamento</small>
                                <span id="detalleDepartamento" class="fs-6 text-dark fw-medium"></span>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted d-block fw-bold">Provincia</small>
                                <span id="detalleProvincia" class="fs-6 text-dark fw-medium"></span>
                            </div>
                            <div>
                                <small class="text-muted d-block fw-bold">Distrito</small>
                                <span id="detalleDistrito" class="fs-6 text-dark fw-medium"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer border-top-0 bg-light rounded-bottom">
                <button type="button" class="btn btn-secondary px-4 fw-bold" data-bs-dismiss="modal">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>
