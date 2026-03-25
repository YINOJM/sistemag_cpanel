<!-- Modal para Región Policial -->
<div class="modal fade" id="modalRegion" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-map-marked-alt me-2"></i>
                    <span id="tituloModalRegion">Nueva Región Policial</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formRegion">
                <div class="modal-body">
                    <input type="hidden" id="region_id" name="id">

                    <div class="mb-3">
                        <label for="region_nombre" class="form-label">Nombre de la Región <span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="region_nombre" name="nombre_region" required
                            placeholder="Ej: REGPOL LIMA">
                    </div>

                    <div class="mb-3">
                        <label for="region_codigo" class="form-label">Código</label>
                        <input type="text" class="form-control" id="region_codigo" name="codigo_region"
                            placeholder="Ej: REGPOL-LIM">
                    </div>

                    <div class="mb-3">
                        <label for="region_descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="region_descripcion" name="descripcion" rows="3"
                            placeholder="Descripción opcional"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>