<!-- Modal para División Policial -->
<div class="modal fade" id="modalDivision" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-building me-2"></i>
                    <span id="tituloModalDivision">Nueva División Policial</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formDivision">
                <div class="modal-body">
                    <input type="hidden" id="division_id" name="id">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="division_region" class="form-label">Región <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="division_region" name="id_region" required>
                                <option value="">Seleccione una región</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="division_nombre" class="form-label">Nombre de la División <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="division_nombre" name="nombre_division" required
                                placeholder="Ej: DIVPOL NORTE 1">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="division_descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="division_descripcion" name="descripcion" rows="2"
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