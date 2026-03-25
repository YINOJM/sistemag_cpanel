<?php
// vista/documento_nuevo.php
// Copia exacta del diseño solicitado por el usuario (Sistema Reference)
include 'layout/topbar.php';
include 'layout/sidebar.php';
require_once '../modelo/DocumentoModelo.php';

$modelo = new DocumentoModelo();
$destinos = $modelo->obtenerDestinos();
?>

<div class="page-content" style="padding: 20px; padding-top: 80px;">
    <div class="container-fluid">

        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow-lg border-0 rounded-lg">
                    <div class="card-header text-white d-flex justify-content-between align-items-center"
                        style="background-color: #00779e;">
                        <h4 class="mb-0"><i class="fas fa-pen-nib me-2"></i>Registrar Nuevo Documento</h4>
                        <a href="gestion_documental.php" class="btn btn-light btn-sm"
                            style="font-weight: 600; padding: 8px 16px;">
                            <i class="fa-solid fa-arrow-left me-1"></i> Volver a la Bandeja
                        </a>
                    </div>
                    <div class="card-body bg-light">
                        <form id="form-documento">
                            <!-- Fila 1: Tipo de Documento (PRIMERO Y MÁS VISIBLE) -->
                            <div class="mb-3">
                                <label class="form-label fw-bold" style="font-size: 1.1rem; color: #00779e;">
                                    <i class="fas fa-file-alt me-2"></i>Tipo de Documento
                                </label>
                                <select class="form-select form-select-lg" name="tipo" id="tipo_documento"
                                    style="border: 2px solid #00779e; font-weight: 600;">
                                    <?php
                                    $tiposDocumento = require_once '../config/tipos_documento.php';
                                    foreach ($tiposDocumento as $codigo => $nombre) {
                                        // OFICIO como predeterminado
                                        $selected = ($codigo === 'OFICIO') ? 'selected' : '';
                                        echo "<option value=\"{$codigo}\" {$selected}>{$nombre}</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <!-- Fila 2: Año y HT -->
                            <div class="row mb-3">
                                <div class="col-md-2">
                                    <label class="form-label fw-bold small">Año</label>
                                    <input type="text" class="form-control text-center" name="anio"
                                        value="<?php echo date('Y'); ?>" readonly
                                        style="background-color: #e9ecef; cursor: not-allowed; border-color: #ced4da;">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">HT / Referencia <span
                                            class="text-muted">(Opcional)</span></label>
                                    <input type="text" class="form-control" name="ht" placeholder="Ej. 20250015236">
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <div class="alert alert-success py-2 w-100 mb-0 text-center shadow-sm small"
                                        style="background-color: #d4edda; border-color: #c3e6cb;">
                                        <i class="fas fa-magic"></i> <strong>Numeración automática</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- Fila 3: Clasificación y Destino -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-tag me-1 text-success"></i>Clasificación / Se Solicita
                                        <i class="fas fa-circle-info ms-1 text-muted" data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            title="Puede seleccionar una opción sugerida O escribir un texto personalizado"
                                            style="font-size: 0.9rem; cursor: help;"></i>
                                        <span class="text-muted small">(Opcional)</span>
                                    </label>
                                    <input class="form-control" list="listaSolicitudes" name="se_solicita"
                                        placeholder="Seleccione o escriba..." autocomplete="off">
                                    <datalist id="listaSolicitudes">
                                        <option value="INTERACCIÓN CON EL MERCADO">
                                        <option value="SOLICITA CCP">
                                        <option value="PROPUESTA DE MODIFICACION">
                                        <option value="SOLICITA GESTIONAR">
                                        <option value="REMITE INFORMACION">
                                        <option value="DEVOLUCION DE DOCUMENTO">
                                        <option value="SOLICITA INFORMACION">
                                    </datalist>
                                    <small class="text-muted d-block mt-1">
                                        <i class="fas fa-lightbulb me-1"></i>Sugerencias disponibles o escriba su propio
                                        texto
                                    </small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-building me-1 text-primary"></i>Destino (Unidad/Área) <span
                                            class="text-danger">*</span>
                                    </label>
                                    <div class="position-relative">
                                        <input class="form-control" type="text" id="nombre_destino_input"
                                            placeholder="Escriba para buscar..." autocomplete="off" required>
                                        <input type="hidden" name="id_destino" id="id_destino_hidden">
                                        <ul id="listaDestinosCustom"
                                            class="list-group position-absolute w-100 shadow-sm"
                                            style="display:none; max-height: 300px; overflow-y: auto; z-index: 1050; top: 100%;">
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Fila 4: Asunto -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-align-left me-1 text-info"></i>Asunto <span
                                        class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" name="asunto" rows="3"
                                    placeholder="Describa brevemente el asunto del documento..." required
                                    style="resize: vertical;"></textarea>
                            </div>

                            <!-- Fila 5: Formulado Por y Observaciones -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Formulado Por</label>
                                    <input type="text" class="form-control" name="formulado_por"
                                        value="<?php echo trim(($_SESSION['nombre'] ?? '') . ' ' . ($_SESSION['apellido'] ?? '')); ?>"
                                        readonly
                                        style="background-color: #e9ecef; cursor: not-allowed; border-color: #ced4da;">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Observaciones <span
                                            class="text-muted">(Opcional)</span></label>
                                    <input type="text" class="form-control" name="observaciones"
                                        placeholder="Observaciones adicionales...">
                                </div>
                            </div>

                            <!-- Sección Opciones Avanzadas (Acordeón) -->
                            <div class="accordion mb-3" id="accordionAdvanced">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed fw-bold text-secondary" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#collapseOptions">
                                            <i class="fas fa-cog me-2"></i> Opciones Avanzadas (Correcciones /
                                            Numeración Manual)
                                        </button>
                                    </h2>
                                    <div id="collapseOptions" class="accordion-collapse collapse"
                                        data-bs-parent="#accordionAdvanced">
                                        <div class="accordion-body bg-white">
                                            <div class="form-check form-switch mb-2">
                                                <input class="form-check-input" type="checkbox" id="checkManual"
                                                    name="es_manual" value="1">
                                                <label class="form-check-label text-danger fw-bold" for="checkManual">
                                                    Es una corrección / Necesito un número específico
                                                </label>
                                            </div>
                                            <div id="panelManual" class="row g-2" style="display:none;">
                                                <div class="col-md-4">
                                                    <label class="small text-muted">Número</label>
                                                    <input type="number" class="form-control" name="num_manual"
                                                        placeholder="Ej. 1623">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="small text-muted">Sufijo (Letra)</label>
                                                    <input type="text" class="form-control" name="sufijo_manual"
                                                        placeholder="Ej. A" maxlength="2"
                                                        style="text-transform:uppercase;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-lg shadow-sm text-white"
                                    style="background-color: #00779e; border-color: #00779e;">
                                    <i class="fas fa-save me-2"></i> Generar Documento
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* Estilizar el datalist para que tenga scroll (Chrome/Edge support) */
    datalist,
    #listaDestinosCustom {
        max-height: 300px;
        overflow-y: auto;
    }
</style>
<script>
    // Inicializar tooltips de Bootstrap
    document.addEventListener('DOMContentLoaded', function () {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });

    // Pasar array PHP a JS
    const destinosData = <?php echo json_encode($destinos); ?>;

    const inputDestino = document.getElementById('nombre_destino_input');
    const ulDestinos = document.getElementById('listaDestinosCustom');
    const hiddenDestino = document.getElementById('id_destino_hidden');

    inputDestino.addEventListener('input', function () {
        const val = this.value.toUpperCase();
        ulDestinos.innerHTML = ''; // Limpiar
        hiddenDestino.value = ''; // Reset ID

        if (!val) {
            // Si está vacío, mostrar TODOS
            ulDestinos.innerHTML = '';
            destinosData.forEach(dest => {
                const li = document.createElement('li');
                li.className = 'list-group-item list-group-item-action cursor-pointer small';
                li.textContent = dest.nombre_destino;
                li.addEventListener('click', function () {
                    inputDestino.value = dest.nombre_destino;
                    hiddenDestino.value = dest.id_destino;
                    ulDestinos.style.display = 'none';
                });
                ulDestinos.appendChild(li);
            });
            ulDestinos.style.display = 'block';
            return;
        }

        const filtered = destinosData.filter(d => d.nombre_destino.toUpperCase().includes(val));

        if (filtered.length > 0) {
            filtered.forEach(dest => {
                const li = document.createElement('li');
                li.className = 'list-group-item list-group-item-action cursor-pointer small';
                li.textContent = dest.nombre_destino;
                li.addEventListener('click', function () {
                    inputDestino.value = dest.nombre_destino;
                    hiddenDestino.value = dest.id_destino;
                    ulDestinos.style.display = 'none';
                });
                ulDestinos.appendChild(li);
            });
            ulDestinos.style.display = 'block';
        } else {
            ulDestinos.style.display = 'none';
        }
    });

    // Mostrar lista al hacer focus
    inputDestino.addEventListener('focus', function () {
        // Trigger input logic immediately to show full list or filtered list
        inputDestino.dispatchEvent(new Event('input'));
    });

    // Ocultar al hacer clic fuera
    document.addEventListener('click', function (e) {
        if (!inputDestino.contains(e.target) && !ulDestinos.contains(e.target)) {
            ulDestinos.style.display = 'none';
        }
    });

    // Validar manualmente si el usuario escribe el nombre exacto pero no hace click
    inputDestino.addEventListener('blur', function () {
        const val = this.value.toUpperCase();
        const found = destinosData.find(d => d.nombre_destino.toUpperCase() === val);
        if (found) {
            hiddenDestino.value = found.id_destino;
        }
    });

    // Lógica para mostrar/ocultar panel manual
    document.getElementById('checkManual').addEventListener('change', function () {
        document.getElementById('panelManual').style.display = this.checked ? 'flex' : 'none';
        if (!this.checked) {
            document.querySelector('[name="num_manual"]').value = '';
            document.querySelector('[name="sufijo_manual"]').value = '';
        }
    });

    // Convertir sufijo a mayúsculas automáticamente
    const sufijoInput = document.querySelector('[name="sufijo_manual"]');
    if (sufijoInput) {
        sufijoInput.addEventListener('input', function () {
            this.value = this.value.toUpperCase();
        });
    }

    // Envío del Formulario
    document.getElementById('form-documento').addEventListener('submit', function (e) {
        e.preventDefault();

        // Validar que se haya seleccionado un destino válido
        let displayDestino = document.getElementById('nombre_destino_input').value;
        let hiddenDestino = document.getElementById('id_destino_hidden').value;

        if (displayDestino.trim() !== '' && hiddenDestino === '') {
            Swal.fire('Atención', 'Debe seleccionar un Destino válido de la lista desplegable.', 'warning');
            return;
        }

        let formData = new FormData(this);
        let btn = this.querySelector('button[type="submit"]');

        Swal.fire({
            title: '¿Confirmar registro?',
            text: "Se generará un nuevo número de documento.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, generar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

                // Usamos fetch al nuevo controlador adaptado
                fetch('../controlador/DocumentoControlador.php?op=guardar_v2', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        // TAREA 4: Reactivar botón inmediatamente
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-save me-2"></i> Generar Documento';

                        if (data.status) {
                            // Modal simplificado y limpio
                            Swal.fire({
                                title: '¡Documento Generado!',
                                html: `
                                    <div class="mb-3">
                                        <p class="mb-3 text-muted">${data.numero}</p>
                                        <div class="d-flex justify-content-center mb-2">
                                            <span class="badge bg-light text-dark border fs-2 px-4 py-3" 
                                                  style="font-weight: 600; letter-spacing: 2px;">
                                                ${data.solo_numero}
                                            </span>
                                        </div>
                                        <p class="text-muted small mb-0">
                                            <i class="fas fa-info-circle"></i> Anota el número de documento
                                        </p>
                                    </div>
                                `,
                                icon: 'success',
                                showCancelButton: true,
                                showDenyButton: true,
                                confirmButtonText: '<i class="fas fa-copy me-1"></i> Copiar',
                                denyButtonText: '<i class="fas fa-plus me-1"></i> Registrar Otro',
                                cancelButtonText: 'Ver Listado',
                                confirmButtonColor: '#0d6efd',
                                denyButtonColor: '#28a745',
                                cancelButtonColor: '#6c757d',
                                allowOutsideClick: false,
                                allowEscapeKey: false,
                                reverseButtons: true
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // OPCIÓN 1: Copiar Número
                                    // Función de copiado robusta
                                    const copiarConFallback = (texto) => {
                                        const exito = () => {
                                             // Limpiar formulario para el siguiente documento
                                            document.getElementById('form-documento').reset();
                                            document.getElementById('id_destino_hidden').value = '';
                                            document.querySelector('[name="anio"]').value = new Date().getFullYear();
                                            window.scrollTo(0, 0);

                                            // Toast de confirmación
                                            const Toast = Swal.mixin({
                                                toast: true,
                                                position: 'top-end',
                                                showConfirmButton: false,
                                                timer: 1500,
                                                timerProgressBar: true
                                            });

                                            Toast.fire({
                                                icon: 'success',
                                                title: `Número ${data.solo_numero} copiado`
                                            });
                                        };

                                        if (navigator.clipboard && window.isSecureContext) {
                                            navigator.clipboard.writeText(texto).then(exito).catch(err => {
                                                console.error('API Clipboard falló, usando fallback', err);
                                                intentarFallback(texto, exito);
                                            });
                                        } else {
                                            intentarFallback(texto, exito);
                                        }
                                    };

                                    const intentarFallback = (texto, callbackExito) => {
                                        let textArea = document.createElement("textarea");
                                        textArea.value = texto;
                                        textArea.style.position = "fixed";
                                        textArea.style.top = "0";
                                        textArea.style.left = "0";
                                        textArea.style.opacity = "0";
                                        document.body.appendChild(textArea);
                                        textArea.focus();
                                        textArea.select();

                                        try {
                                            const successful = document.execCommand('copy');
                                            if (successful) {
                                                callbackExito();
                                            } else {
                                                Swal.fire('Error', 'No se pudo copiar automáticamente. Anote: ' + texto, 'error');
                                            }
                                        } catch (err) {
                                            console.error('Fallback error', err);
                                            Swal.fire('Error', 'No se pudo copiar automáticamente. Anote: ' + texto, 'error');
                                        }
                                        document.body.removeChild(textArea);
                                    };

                                    // Ejecutar copiado
                                    copiarConFallback(data.solo_numero);

                                } else if (result.isDenied) {
                                    // OPCIÓN 2: Registrar Otro
                                    document.getElementById('form-documento').reset();
                                    document.getElementById('id_destino_hidden').value = '';
                                    document.querySelector('[name="anio"]').value = new Date().getFullYear();
                                    window.scrollTo(0, 0);

                                    // Toast informativo
                                    const Toast = Swal.mixin({
                                        toast: true,
                                        position: 'top-end',
                                        showConfirmButton: false,
                                        timer: 1500,
                                        timerProgressBar: true
                                    });

                                    Toast.fire({
                                        icon: 'info',
                                        title: 'Formulario listo para nuevo documento'
                                    });

                                } else {
                                    // OPCIÓN 3: Ver Listado (Cancel button)
                                    window.location.href = 'gestion_documental.php';
                                }
                            });
                        } else {
                            Swal.fire('Error', data.msg, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error de Conexión', 'No se pudo contactar al servidor.', 'error');
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-save me-2"></i> Generar Documento';
                    });
            }
        });
    });
</script>