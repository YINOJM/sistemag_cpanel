<?php
// vista/importar_datos.php
session_start();
include 'layout/topbar.php';
include 'layout/sidebar.php';
?>

<style>
    .upload-area {
        border: 3px dashed #00779e;
        border-radius: 10px;
        padding: 40px;
        text-align: center;
        background-color: #f8f9fa;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .upload-area:hover {
        background-color: #e9ecef;
        border-color: #005a7a;
    }

    .upload-area.dragover {
        background-color: #d1ecf1;
        border-color: #0c5460;
    }

    .file-info {
        display: none;
        margin-top: 20px;
    }

    .result-card {
        display: none;
    }

    .progress {
        display: none;
        height: 30px;
        margin-top: 20px;
    }
</style>

<div class="page-content" style="padding-top: 80px; padding-left: 20px; padding-right: 20px;">
    <div class="container-fluid">

        <!-- Botones de navegación -->
        <div class="mb-3">
            <a href="gestion_documental.php" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left"></i> Volver a la Bandeja
            </a>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow-lg border-0 rounded-lg">
                    <div class="card-header text-white" style="background-color: #00779e;">
                        <h4 class="mb-0"><i class="fas fa-file-import me-2"></i>Importar Documentos desde Excel</h4>
                    </div>
                    <div class="card-body">

                        <!-- Instrucciones -->
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle me-2"></i>Instrucciones</h5>
                            <ul class="mb-0">
                                <li>El archivo debe ser Excel (.xlsx o .xls)</li>
                                <li>Tamaño máximo: 10MB</li>
                                <li>Los encabezados son <strong>flexibles</strong>: acepta variaciones como NUM, NUMERO,
                                    Nro, etc.</li>
                                <li>Campos obligatorios: <strong>NUMERO, DESTINO, ASUNTO, FORMULADO_POR</strong></li>
                                <li>Campos opcionales: HT, FECHA, OBSERVACIONES</li>
                            </ul>
                        </div>

                        <!-- Botón Descargar Plantilla -->
                        <div class="alert alert-success d-flex align-items-center justify-content-between mb-4">
                            <div>
                                <h6 class="mb-1"><i class="fas fa-file-download me-2"></i>¿Primera vez importando?</h6>
                                <p class="mb-0 small">Descarga la plantilla Excel con ejemplos e instrucciones</p>
                            </div>
                            <a href="../controlador/DescargarPlantillaControlador.php" class="btn btn-success btn-lg"
                                download>
                                <i class="fas fa-download me-2"></i>Descargar Plantilla
                            </a>
                        </div>

                        <!-- Formulario de configuración -->
                        <form id="form-config">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Año del Documento</label>
                                    <input type="number" class="form-control" id="anio" name="anio" value="2025"
                                        min="2020" max="2030" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Tipo de Documento</label>
                                    <select class="form-select" id="tipo" name="tipo" required>
                                        <option value="">Seleccione...</option>
                                        <?php
                                        $tiposDocumento = require_once '../config/tipos_documento.php';
                                        foreach ($tiposDocumento as $codigo => $nombre) {
                                            echo "<option value=\"{$codigo}\">{$nombre}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Botones de Acción Previa -->
                            <div class="mb-4 d-flex align-items-center justify-content-between p-3 bg-light rounded border">
                                <div>
                                    <h6 class="text-danger fw-bold mb-1"><i class="fas fa-trash-alt me-2"></i>Limpieza Previa</h6>
                                    <small class="text-muted">Si desea borrar los datos existentes antes de subir el nuevo archivo.</small>
                                </div>
                                <button type="button" class="btn btn-outline-danger" id="btn-limpiar">
                                    <i class="fas fa-broom me-2"></i>Limpiar Datos Ahora
                                </button>
                            </div>
                        </form>

                        <!-- Área de carga -->
                        <div class="upload-area" id="upload-area">
                            <i class="fas fa-cloud-upload-alt fa-4x text-muted mb-3"></i>
                            <h5>Arrastra tu archivo Excel aquí</h5>
                            <p class="text-muted">o haz clic para seleccionar</p>
                            <input type="file" id="file-input" accept=".xlsx,.xls" style="display: none;">
                        </div>

                        <!-- Información del archivo -->
                        <div class="file-info" id="file-info">
                            <div class="alert alert-success">
                                <i class="fas fa-file-excel me-2"></i>
                                <strong>Archivo seleccionado:</strong> <span id="file-name"></span>
                                <br>
                                <strong>Tamaño:</strong> <span id="file-size"></span>
                            </div>
                        </div>

                        <!-- Barra de progreso -->
                        <div class="progress" id="progress-bar">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                                style="width: 0%">0%</div>
                        </div>

                        <!-- Botón de importar -->
                        <div class="d-grid gap-2 mt-4">
                            <button type="button" class="btn btn-lg text-white" id="btn-importar"
                                style="background-color: #00779e; display: none;">
                                <i class="fas fa-upload me-2"></i> Importar Documentos
                            </button>
                        </div>

                        <!-- Resultado -->
                        <div class="result-card mt-4" id="result-card">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Resultado de la Importación
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-md-4">
                                            <h3 class="text-primary" id="total-registros">0</h3>
                                            <p class="text-muted">Total Registros</p>
                                        </div>
                                        <div class="col-md-4">
                                            <h3 class="text-success" id="exitosos">0</h3>
                                            <p class="text-muted">Exitosos</p>
                                        </div>
                                        <div class="col-md-4">
                                            <h3 class="text-danger" id="errores">0</h3>
                                            <p class="text-muted">Errores</p>
                                        </div>
                                    </div>
                                    <div id="detalle-errores" class="mt-3"></div>
                                    <div class="d-grid gap-2 mt-3">
                                        <a href="gestion_documental.php" class="btn btn-primary">
                                            <i class="fas fa-list me-2"></i> Ver Documentos Importados
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    let archivoSeleccionado = null;

    const uploadArea = document.getElementById('upload-area');
    const fileInput = document.getElementById('file-input');
    const fileInfo = document.getElementById('file-info');
    const btnImportar = document.getElementById('btn-importar');
    const progressBar = document.getElementById('progress-bar');
    const resultCard = document.getElementById('result-card');

    // Click en el área de carga
    uploadArea.addEventListener('click', () => {
        fileInput.click();
    });

    // Selección de archivo
    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            manejarArchivo(e.target.files[0]);
        }
    });

    // Drag & Drop
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');

        if (e.dataTransfer.files.length > 0) {
            manejarArchivo(e.dataTransfer.files[0]);
        }
    });

    // Manejar archivo seleccionado
    function manejarArchivo(archivo) {
        // Validar extensión
        const extension = archivo.name.split('.').pop().toLowerCase();
        if (!['xlsx', 'xls'].includes(extension)) {
            Swal.fire('Error', 'El archivo debe ser de tipo Excel (.xlsx o .xls)', 'error');
            return;
        }

        // Validar tamaño (10MB)
        if (archivo.size > 10 * 1024 * 1024) {
            Swal.fire('Error', 'El archivo excede el tamaño máximo permitido (10MB)', 'error');
            return;
        }

        archivoSeleccionado = archivo;

        // Mostrar información del archivo
        document.getElementById('file-name').textContent = archivo.name;
        document.getElementById('file-size').textContent = formatBytes(archivo.size);
        fileInfo.style.display = 'block';
        btnImportar.style.display = 'block';
        resultCard.style.display = 'none';
    }

    // Importar archivo
    btnImportar.addEventListener('click', () => {
        if (!archivoSeleccionado) {
            Swal.fire('Error', 'Debe seleccionar un archivo', 'error');
            return;
        }

        const anio = document.getElementById('anio').value;
        const tipo = document.getElementById('tipo').value;

        if (!tipo) {
            Swal.fire('Error', 'Debe seleccionar el tipo de documento', 'error');
            return;
        }

        Swal.fire({
            title: '¿Confirmar importación?',
            html: `Se importarán los documentos del archivo:<br><strong>${archivoSeleccionado.name}</strong><br>Año: ${anio}<br>Tipo: ${tipo}`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#00779e',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, importar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                realizarImportacion(anio, tipo);
            }
        });
    });

    // Botón Limpiar Ahora
    document.getElementById('btn-limpiar').addEventListener('click', () => {
        const anio = document.getElementById('anio').value;
        const tipo = document.getElementById('tipo').value;

        if (!tipo) {
            Swal.fire('Error', 'Debe seleccionar el tipo de documento para limpiar', 'error');
            return;
        }

        Swal.fire({
            title: '¿Está seguro?',
            html: `Se eliminarán <strong>TODOS</strong> los documentos de tipo <strong>${tipo}</strong> del año <strong>${anio}</strong>.<br><br><span class="text-danger fw-bold">Esta acción no se puede deshacer.</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, borrar todo',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Ejecutar limpieza
                const formData = new FormData();
                formData.append('anio', anio);
                formData.append('tipo', tipo);

                fetch('../controlador/ImportacionControlador.php?op=limpiar', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.status) {
                        Swal.fire('Limpieza Completada', data.msg, 'success');
                    } else {
                        Swal.fire('Error', data.msg, 'error');
                    }
                })
                .catch(e => Swal.fire('Error', 'No se pudo conectar con el servidor', 'error'));
            }
        });
    });

    // Realizar importación
    function realizarImportacion(anio, tipo) {
        console.log('=== INICIO IMPORTACIÓN ===');
        console.log('Archivo:', archivoSeleccionado);
        console.log('Año:', anio);
        console.log('Tipo:', tipo);
        
        const formData = new FormData();
        formData.append('archivo', archivoSeleccionado);
        formData.append('anio', anio);
        formData.append('tipo', tipo);
        
        // Ya no enviamos flag de limpieza automática porque se hace manual
        
        console.log('FormData creado');

        // Mostrar progreso
        progressBar.style.display = 'block';
        btnImportar.disabled = true;
        btnImportar.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Procesando...';

        // Simular progreso
        let progreso = 0;
        const intervalo = setInterval(() => {
            progreso += 10;
            if (progreso <= 90) {
                actualizarProgreso(progreso);
            }
        }, 200);
        
        console.log('Enviando petición fetch...');

        // Enviar petición
        fetch('../controlador/ImportacionControlador.php?op=procesar', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'  // Importante: enviar cookies de sesión
        })
            .then(response => {
                console.log('Respuesta recibida:', response);
                console.log('Status:', response.status);
                console.log('OK:', response.ok);
                return response.text();
            })
            .then(text => {
                console.log('Texto de respuesta:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('JSON parseado:', data);
                    
                    clearInterval(intervalo);
                    actualizarProgreso(100);

                    setTimeout(() => {
                        progressBar.style.display = 'none';
                        btnImportar.disabled = false;
                        btnImportar.innerHTML = '<i class="fas fa-upload me-2"></i> Importar Documentos';

                        if (data.status) {
                            mostrarResultado(data.data);
                        } else {
                            let mensajeError = data.msg;
                            if (data.debug) {
                                console.error('Debug info:', data.debug);
                                mensajeError += '\n\nArchivo: ' + data.debug.file + '\nLínea: ' + data.debug.line;
                            }
                            Swal.fire('Error', mensajeError, 'error');
                        }
                    }, 500);
                } catch (e) {
                    console.error('Error parseando JSON:', e);
                    console.error('Texto recibido:', text);
                    clearInterval(intervalo);
                    progressBar.style.display = 'none';
                    btnImportar.disabled = false;
                    btnImportar.innerHTML = '<i class="fas fa-upload me-2"></i> Importar Documentos';
                    Swal.fire('Error', 'Respuesta inválida del servidor: ' + text.substring(0, 200), 'error');
                }
            })
            .catch(error => {
                console.error('=== ERROR EN FETCH ===');
                console.error('Error completo:', error);
                console.error('Tipo:', error.name);
                console.error('Mensaje:', error.message);
                console.error('Stack:', error.stack);
                
                clearInterval(intervalo);
                progressBar.style.display = 'none';
                btnImportar.disabled = false;
                btnImportar.innerHTML = '<i class="fas fa-upload me-2"></i> Importar Documentos';

                Swal.fire('Error', 'No se pudo conectar con el servidor. Error: ' + error.message, 'error');
            });
    }

    // Actualizar barra de progreso
    function actualizarProgreso(porcentaje) {
        const barra = progressBar.querySelector('.progress-bar');
        barra.style.width = porcentaje + '%';
        barra.textContent = porcentaje + '%';
    }

    // Mostrar resultado
    function mostrarResultado(resultado) {
        document.getElementById('total-registros').textContent = resultado.total;
        document.getElementById('exitosos').textContent = resultado.exitosos;
        document.getElementById('errores').textContent = resultado.errores;

        const detalleErrores = document.getElementById('detalle-errores');
        if (resultado.errores > 0) {
            let html = '<div class="alert alert-danger"><h6>Detalle de Errores:</h6><ul class="mb-0">';
            resultado.detalleErrores.forEach(error => {
                html += `<li>${error}</li>`;
            });
            html += '</ul></div>';
            detalleErrores.innerHTML = html;
        } else {
            detalleErrores.innerHTML = '';
        }

        resultCard.style.display = 'block';
        fileInfo.style.display = 'none';
        btnImportar.style.display = 'none';

        if (resultado.exitosos > 0) {
            Swal.fire({
                title: '¡Importación Exitosa!',
                html: `Se importaron <strong>${resultado.exitosos}</strong> documentos correctamente`,
                icon: 'success',
                confirmButtonColor: '#00779e'
            });
        }
    }

    // Formatear bytes
    function formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
</script>