<!-- Modal para Sub-Unidad Policial -->
<div class="modal fade" id="modalSubUnidad" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-building-shield me-2"></i>
                    <span id="tituloModalSubUnidad">Nueva Unidad / Sub-Unidad Policial</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formSubUnidad">
                <div class="modal-body">
                    <input type="hidden" id="subunidad_id" name="id">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="subunidad_region" class="form-label">Región <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="subunidad_region" required>
                                <option value="">Seleccione una región</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="subunidad_division" class="form-label">División <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="subunidad_division" name="id_division" required>
                                <option value="">Primero seleccione una región</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="subunidad_nombre" class="form-label">Nombre de la Unidad / Sub-Unidad <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="subunidad_nombre" name="nombre_subunidad"
                                required placeholder="Ej: SEINCRI - CPNP PUENTE PIEDRA">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="subunidad_tipo" class="form-label">Tipo</label>
                            <select class="form-select" id="subunidad_tipo" name="tipo_unidad">
                                <option value="">Seleccione</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="subunidad_departamento" class="form-label">Departamento</label>
                            <select class="form-select" id="subunidad_departamento" name="departamento">
                                <option value="">Seleccione</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="subunidad_provincia" class="form-label">Provincia</label>
                            <select class="form-select" id="subunidad_provincia" name="provincia">
                                <option value="">Seleccione</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="subunidad_distrito" class="form-label">Distrito <span class="text-danger">*</span></label>
                            <select class="form-select" id="subunidad_distrito" name="distrito" required>
                                <option value="">Seleccione</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="subunidad_estado" class="form-label">Estado de la Unidad</label>
                            <select class="form-select" id="subunidad_estado" name="estado">
                                <option value="1">Activo (Operativa)</option>
                                <option value="0">Inactivo (No Operativa / Baja)</option>
                            </select>
                        </div>
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