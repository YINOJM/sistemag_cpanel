<?php
// cmn.php
// Página pública para que los responsables logísticos registren sus datos de acceso al SIAF-Web CMN 2026.
date_default_timezone_set('America/Lima');

// CONFIGURACIÓN DE PLAZO (Desde Base de Datos)
require_once "modelo/conexion.php";
$res_config = $conexion->query("SELECT valor FROM cmn_config WHERE clave = 'mantenimiento'");
$config = $res_config->fetch_assoc();
$estaCerrado = ($config && $config['valor'] === '1');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro Responsables SIAF - CMN 2026 | REGPOL LIMA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #003666;
            --secondary-color: #006699;
            --accent-color: #ffc107;
            --bg-color: #f4f7f6;
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-color);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0;
            background-image: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }

        .container {
            max-width: 850px;
        }

        .main-card {
            background: white;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            border: none;
        }

        .card-header-gradient {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
            border-bottom: 5px solid var(--accent-color);
        }

        .card-header-gradient img {
            max-height: 80px;
            margin-bottom: 15px;
        }

        .card-body {
            padding: 40px;
        }

        .section-title {
            color: var(--primary-color);
            font-weight: 700;
            border-left: 5px solid var(--secondary-color);
            padding-left: 15px;
            margin-bottom: 25px;
            text-transform: uppercase;
            font-size: 1.1rem;
            letter-spacing: 1px;
        }

        .form-label {
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
        }

        .form-control,
        .form-select {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #ddd;
            transition: all 0.3s ease;
        }

        .form-control:focus,
        .form-select:focus {
            box-shadow: 0 0 0 3px rgba(0, 102, 153, 0.1);
            border-color: var(--secondary-color);
        }

        /* Estilo para campos con contenido (Celeste suave) */
        .is-filled {
            background-color: #e8f4fd !important;
            border-color: #aed6f1 !important;
        }

        /* Ajuste para Tom Select cuando tiene valor */
        .ts-wrapper.is-filled .ts-control {
            background-color: #e8f4fd !important;
            border-color: #aed6f1 !important;
        }

        .btn-main-submit {
            background: linear-gradient(135deg, #0056b3 0%, #003666 100%);
            color: white !important;
            border: none;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 54, 102, 0.4);
        }

        .btn-main-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 54, 102, 0.5);
            background: linear-gradient(135deg, #004494 0%, #002b52 100%);
        }

        #btnLimpiarForm {
            border: 2px solid #6c757d;
            color: #495057;
            font-weight: 600;
            transition: all 0.2s ease;
            background: white;
        }

        #btnLimpiarForm:hover {
            background-color: #f8f9fa;
            color: #dc3545;
            border-color: #dc3545;
            transform: translateY(-2px);
        }

        .deadline-alert {
            background-color: #fff3cd;
            border-left: 5px solid #ffca2c;
            color: #856404;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 30px;
            font-size: 0.95rem;
        }

        .text-uppercase-input {
            text-transform: uppercase;
        }

        .file-upload-box {
            border: 2px dashed #ccc;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            background-color: #fafafa;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-upload-box:hover {
            background-color: #f0f4f8;
            border-color: var(--secondary-color);
        }

        .file-upload-box i {
            font-size: 40px;
            color: #aaa;
            margin-bottom: 10px;
        }

        /* Ampliar altura de los desplegables Tom Select */
        .ts-dropdown .ts-dropdown-content {
            max-height: 350px !important;
        }

        .ts-dropdown .option {
            padding: 10px 15px !important;
            font-size: 0.9rem;
        }

        .closed-message {
            text-align: center;
            padding: 50px 30px;
        }

        .closed-message i {
            font-size: 80px;
            color: #dc3545;
            margin-bottom: 20px;
        }

        .pnp-logo-float {
            position: absolute;
            top: 20px;
            left: 20px;
            opacity: 0.8;
        }

        .mef-logo-float {
            position: absolute;
            top: 20px;
            right: 20px;
            opacity: 0.8;
        }

        @media (max-width: 768px) {
            .card-body {
                padding: 25px;
            }

            .card-header-gradient {
                padding: 30px 20px;
            }
        }
    </style>
</head>

<body>

    <?php if ($estaCerrado): ?>
        <!-- PANTALLA DE FORMULARIO DESACTIVADO -->
        <div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
            <div class="card shadow-lg border-0 text-center p-5 rounded-4" style="max-width: 500px;">
                <div class="mb-4">
                    <i class="fa-solid fa-circle-exclamation text-danger" style="font-size: 5rem;"></i>
                </div>
                <h2 class="fw-bold text-dark">SISTEMA FUERA DE SERVICIO</h2>
                <p class="text-muted lead">El periodo de registro ha finalizado o el sistema se encuentra en mantenimiento
                    por orden superior.</p>
                <hr>
                <p class="small text-secondary">Para cualquier consulta, comuníquese con la Oficina de Programación - UE009-VII DIRTEPOL LIMA.</p>
            </div>
        </div>
    <?php else: ?>
        <!-- PANTALLA DEL FORMULARIO ACTIVO -->
        <div class="container py-5">
            <div class="main-card">
                <div class="card-header-gradient">
                    <div class="d-flex justify-content-center gap-4 mb-3">
                        <i class="fa-solid fa-shield-halved fa-3x" style="color: rgba(255,255,255,0.8)"></i>
                        <i class="fa-solid fa-file-invoice-dollar fa-3x" style="color: rgba(255,255,255,0.8)"></i>
                    </div>
                    <h1 class="h3 fw-bold mb-2">REGISTRO DE RESPONSABLES LOGÍSTICOS</h1>
                    <p class="mb-0 opacity-75">Plataforma SIAF-Web | Cuadro Multianual de Necesidades (CMN 2026)</p>
                </div>

                <div class="card-body">
                    <div class="deadline-alert mb-4 d-flex align-items-center">
                        <i class="fa-solid fa-circle-info fa-2x me-3 opacity-50"></i>
                        <div>
                            <strong>Directiva N° 0007-2025-EF/54.01:</strong>
                            Los accesos serán remitidos al correo electrónico proporcionado. Asegúrese de que los datos sean
                            correctos, especialmente el <strong>CIP, DNI y Correo</strong>.
                        </div>
                    </div>

                    <form id="formCmnRegistro" action="controlador/cmn_registro_save.php" method="POST"
                        enctype="multipart/form-data">

                        <div class="section-title">1. Datos del Personal Responsable</div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">DNI *</label>
                                <input type="text" id="dniInput" name="dni" class="form-control" maxlength="8"
                                    pattern="\d{8}" placeholder="INGRESE SU DNI" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Grado *</label>
                                <select name="grado" id="gradoSelect" class="form-select" required>
                                    <option value="">Seleccione Grado...</option>
                                    <option value="MAYOR PNP">MAYOR PNP</option>
                                    <option value="MAYOR S. PNP">MAYOR S. PNP</option>
                                    <option value="ALFÉREZ PNP">ALFÉREZ PNP</option>
                                    <option value="CAPITÁN PNP">CAPITÁN PNP</option>
                                    <option value="CAPITÁN S. PNP">CAPITÁN S. PNP</option>
                                    <option value="TENIENTE PNP">TENIENTE PNP</option>
                                    <option value="SS PNP">SS PNP</option>
                                    <option value="SS S. PNP">SS S. PNP</option>
                                    <option value="SB PNP">SB PNP</option>
                                    <option value="SB S. PNP">SB S. PNP</option>
                                    <option value="ST1 PNP">ST1 PNP</option>
                                    <option value="ST1 S. PNP">ST1 S. PNP</option>
                                    <option value="ST2 PNP">ST2 PNP</option>
                                    <option value="ST2 S. PNP">ST2 S. PNP</option>
                                    <option value="ST3 PNP">ST3 PNP</option>
                                    <option value="ST3 S. PNP">ST3 S. PNP</option>
                                    <option value="S1 PNP">S1 PNP</option>
                                    <option value="S1 S. PNP">S1 S. PNP</option>
                                    <option value="S2 PNP">S2 PNP</option>
                                    <option value="S2 S. PNP">S2 S. PNP</option>
                                    <option value="S3 PNP">S3 PNP</option>
                                    <option value="S3 S. PNP">S3 S. PNP</option>
                                    <option value="EC AC">EC AC</option>
                                    <option value="EC PB">EC PB</option>
                                    <option value="EC TA">EC TA</option>
                                    <option value="EMPLEADO CIVIL">EMPLEADO CIVIL</option>
                                    <option value="CAS PNP">CAS PNP</option>
                                    <option value="LOCADOR">LOCADOR</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">CIP *</label>
                                <input type="text" name="cip" class="form-control" maxlength="8" pattern="\d{8}"
                                    placeholder="CIP de 8 dígitos" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Apellidos *</label>
                                <input type="text" name="apellidos" class="form-control text-uppercase-input"
                                    placeholder="Completos" oninput="this.value = this.value.toUpperCase()" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nombres *</label>
                                <input type="text" name="nombres" class="form-control text-uppercase-input"
                                    placeholder="Completos" oninput="this.value = this.value.toUpperCase()" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Correo Electrónico (Para recibir accesos) *</label>
                                <input type="email" name="correo" class="form-control text-uppercase-input"
                                    placeholder="ejemplo@correo.com" oninput="this.value = this.value.toUpperCase()"
                                    required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Celular de Contacto *</label>
                                <input type="text" name="celular" class="form-control" maxlength="15" pattern="\d{9,15}"
                                    placeholder="Ej: 999888777" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Cargo Actual</label>
                                <input type="text" name="cargo" id="cargoInput" class="form-control bg-light"
                                    value="RESP. LOGÍSTICO" readonly>
                            </div>
                        </div>

                        <div class="section-title mt-5">2. Ubicación Orgánica de la Unidad</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Región Policial *</label>
                                <select name="region_policial" id="regionSelect" class="form-select" required>
                                    <option value="">Cargando regiones...</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">División Policial / DIVOPUS *</label>
                                <select name="divpol_divopus" id="divisionSelect" class="form-select" required disabled>
                                    <option value="">Seleccione Región primero...</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Nombre de la Sub-Unidad Policial (Específicamente) *</label>
                                <select name="sub_unidad" id="subUnidadSelect" class="form-select" required disabled>
                                    <option value="">Seleccione División primero...</option>
                                </select>
                                <small class="text-muted">Busque y seleccione el nombre oficial de su unidad o
                                    subunidad.</small>
                            </div>
                        </div>

                        <div class="section-title mt-5">3. Documento de Respaldo</div>

                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <div class="h-100 p-4 border rounded-4 bg-white shadow-sm d-flex flex-column justify-content-between"
                                    style="border-left: 5px solid #0d6efd !important;">
                                    <div>
                                        <div class="badge bg-primary mb-2">PASO 1: AUTO-GENERAR</div>
                                        <h5 class="fw-bold"><i class="fa-solid fa-file-pdf text-danger me-2"></i> Formato
                                            Pre-llenado</h5>
                                        <p class="small text-muted">Primero complete sus datos arriba, luego haga clic aquí
                                            para generar su Solicitud en <strong>PDF</strong> con sus datos ya escritos.</p>
                                    </div>
                                    <button type="button" id="btnGenerarExcel"
                                        class="btn btn-primary mt-3 w-100 py-2 fw-bold">
                                        <i class="fa-solid fa-file-pdf me-2"></i> Generar Mi Solicitud .PDF
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="h-100 p-4 border rounded-4 bg-white shadow-sm d-flex flex-column justify-content-between"
                                    style="border-left: 5px solid #dc3545 !important;">
                                    <div>
                                        <div class="badge bg-danger mb-2">PASO 2: FIRMAR Y ESCANEAR</div>
                                        <h5 class="fw-bold"><i class="fa-solid fa-pen-nib text-danger me-2"></i> Firmar /
                                            PDF</h5>
                                        <p class="small text-muted">Imprima el PDF generado, fírmelo, obtenga la firma del
                                            Jefe de unidad y escanee todo en un solo <strong>PDF</strong>.</p>
                                    </div>
                                    <div class="text-secondary small mt-3">
                                        <i class="fa-solid fa-print me-1"></i> Imprimir | <i
                                            class="fa-solid fa-stamp me-1"></i> Sellar | <i
                                            class="fa-solid fa-file-pdf me-1"></i> Escanear
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-danger border-start border-5 border-danger shadow-sm mb-4">
                            <div class="d-flex">
                                <i class="fa-solid fa-triangle-exclamation fa-2x me-3"></i>
                                <div>
                                    <h6 class="fw-bold mb-1">¡IMPORTANTE ANTES DE SUBIR EL PDF FINAL!</h6>
                                    <p class="small mb-2">El archivo PDF escaneado debe contener obligatoriamente:</p>
                                    <ul class="small mb-0">
                                        <li>Firma suya (Logístico).</li>
                                        <li>Firma y Sello del su Jefe de Unidad.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-check mb-3 p-3 border rounded bg-warning bg-opacity-10 border-warning">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" id="checkFirmas" required
                                        style="width: 1.5em; height: 1.5em; border: 2px solid #856404;">
                                    <label class="form-check-label fw-bold text-dark" for="checkFirmas">
                                        <i class="fa-solid fa-check-double me-1"></i> DECLARO QUE MI ARCHIVO YA TIENE TODAS
                                        LAS FIRMAS Y SELLOS.
                                    </label>
                                </div>

                                <label class="form-label">Subir Solicitud FIRMADA Y ESCANEADA (PDF) *</label>
                                <input type="file" name="solicitud_pdf" id="filePdf" class="d-none" accept=".pdf" required>
                                <div class="file-upload-box" onclick="
                                if (!documentoGenerado) { 
                                    Swal.fire({icon:'warning', title:'Paso 1 Incompleto', text:'Por seguridad, primero debe hacer clic en el botón azul para Generar su Solicitud en PDF y registrar su borrador.', confirmButtonColor:'#0d6efd'}); 
                                    return; 
                                } 
                                if(document.getElementById('checkFirmas').checked) { 
                                    document.getElementById('filePdf').click(); 
                                } else { 
                                    Swal.fire({icon:'warning', title:'Alto', text:'Marque la confirmación de firmas antes de subir su archivo.', confirmButtonColor:'#ffc107'}); 
                                }">
                                    <i class="fa-solid fa-file-pdf"></i>
                                    <div id="fileInfo">
                                        <h6 class="mb-1">Haga clic aquí para seleccionar el PDF</h6>
                                        <p class="small text-muted mb-0">Formato: Solo PDF (Máx. 5MB)</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 text-center">
                            <div class="form-check d-inline-block text-start mb-3">
                                <input class="form-check-input" type="checkbox" id="checkVerdad" required>
                                <label class="form-check-label small" for="checkVerdad">
                                    Declaro bajo juramento que los datos ingresados son correctos y corresponden al
                                    responsable designado.
                                </label>
                            </div>
                            <div class="col-12">
                                <button type="submit"
                                    class="btn btn-main-submit w-100 py-3 rounded-4 shadow-lg animate__animated animate__pulse animate__infinite animate__slow">
                                    <i class="fa-solid fa-cloud-arrow-up me-2"></i> ENVIAR REGISTRO
                                </button>
                            </div>
                            <div class="col-12 mt-3">
                                <button type="button" id="btnLimpiarForm" class="btn btn-outline-secondary px-4 rounded-4"
                                    title="Limpiar todos los datos escritos">
                                    <i class="fa-solid fa-user-plus me-1"></i> NUEVO REGISTRO / LIMPIAR PANTALLA
                                </button>
                            </div>
                        </div>

                    </form>
                </div>

                <div class="card-footer bg-light text-center py-4">
                    <div class="mb-3">
                        <p class="mb-1 fw-bold text-secondary" style="font-size: 0.85rem;">¿NECESITAS AYUDA CON EL SISTEMA?
                        </p>
                        <a href="https://wa.me/51951357961" target="_blank"
                            class="btn btn-sm btn-success rounded-pill px-3 shadow-sm"
                            style="background-color: #25D366; border: none;">
                            <i class="fa-brands fa-whatsapp me-2"></i>Escribir al WhatsApp: <strong>951 357 961</strong>
                        </a>
                    </div>
                    <hr class="mx-5 opacity-25">
                    <small class="text-muted" style="font-size: 0.65rem;">&copy; <?= date('Y') ?> | OFICINA DE PROGRAMACIÓN
                        - UNIDAD DE ADMINISTRACIÓN - UE009 - VII DIRTEPOL LIMA RUC 20383430250 &bull; AV. ESPAÑA 450 -
                        CERCADO DE LIMA</small>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
        // Cargo fijo
        const cargoInput = document.getElementById('cargoInput');

        function getCargoActual() {
            return "RESPONSABLE LOGÍSTICO";
        }

        // --- SISTEMA DE RECUPERACIÓN POR DNI (SERVIDOR + DB) ---
        const form = document.getElementById('formCmnRegistro');
        const inputDNI = document.getElementById('dniInput');
        const fieldsToMap = ['grado', 'cip', 'apellidos', 'nombres', 'correo', 'celular', 'region_policial', 'divpol_divopus', 'sub_unidad'];
        let lookUpTimeout = null;
        let documentoGenerado = false; // Control de seguridad: obliga a generar PDF primero

        // Función para resaltar campos llenos
        function actualizarEstiloCampos() {
            // Inputs normales
            document.querySelectorAll('.form-control, .form-select').forEach(el => {
                if (el.value && el.value.trim() !== '' && !el.readOnly) {
                    el.classList.add('is-filled');
                } else {
                    el.classList.remove('is-filled');
                }
            });

            // Tom Selects
            [tsGrado, tsRegion, tsDivision, tsSubUnidad].forEach(ts => {
                if (ts && ts.getValue() !== '') {
                    ts.wrapper.classList.add('is-filled');
                } else if (ts) {
                    ts.wrapper.classList.remove('is-filled');
                }
            });
        }

        // Escuchar cambios en todos los inputs
        document.querySelectorAll('.form-control, .form-select').forEach(el => {
            el.addEventListener('input', actualizarEstiloCampos);
            el.addEventListener('change', actualizarEstiloCampos);
        });

        // Inicializar Tom Select para Grado (Searchable Select)
        let tsGrado = new TomSelect("#gradoSelect", {
            create: false,
            sortField: false,
            placeholder: "Seleccione Grado..."
        });

        // Inicializar Tom Selects para Ubicación Orgánica
        let tsRegion = new TomSelect("#regionSelect", {
            create: false,
            sortField: { field: "text", direction: "asc" },
            placeholder: "Seleccione Región..."
        });

        let tsDivision = new TomSelect("#divisionSelect", {
            create: false,
            sortField: { field: "text", direction: "asc" },
            placeholder: "Esperando región..."
        });

        let tsSubUnidad = new TomSelect("#subUnidadSelect", {
            create: false,
            sortField: { field: "text", direction: "asc" },
            placeholder: "Esperando división..."
        });

        // Cargar Regiones Iniciales
        async function cargarRegiones() {
            try {
                const res = await fetch('controlador/cmn_get_unidades_ajax.php?op=regiones');
                const regiones = await res.json();
                tsRegion.clear();
                tsRegion.clearOptions();
                regiones.forEach(r => tsRegion.addOption({ value: r, text: r }));
                tsRegion.refreshOptions(false);
            } catch (err) { console.error('Error cargando regiones:', err); }
        }
        cargarRegiones();

        // Evento cambio de Región -> Carga Divisiones
        tsRegion.on('change', async function (value) {
            tsDivision.clear();
            tsDivision.clearOptions();
            tsSubUnidad.clear();
            tsSubUnidad.clearOptions();

            if (!value) {
                tsDivision.disable();
                tsSubUnidad.disable();
                return;
            }

            try {
                const res = await fetch(`controlador/cmn_get_unidades_ajax.php?op=divisiones&region=${encodeURIComponent(value)}`);
                const divisiones = await res.json();
                tsDivision.enable();
                divisiones.forEach(d => tsDivision.addOption({ value: d, text: d }));
                tsDivision.refreshOptions(false);
            } catch (err) { console.error('Error cargando divisiones:', err); }
        });

        // Evento cambio de División -> Carga Sub-Unidades
        tsDivision.on('change', async function (value) {
            tsSubUnidad.clear();
            tsSubUnidad.clearOptions();

            if (!value) {
                tsSubUnidad.disable();
                return;
            }

            try {
                const region = tsRegion.getValue();
                const res = await fetch(`controlador/cmn_get_unidades_ajax.php?op=unidades&region=${encodeURIComponent(region)}&division=${encodeURIComponent(value)}`);
                const unidades = await res.json();
                tsSubUnidad.enable();
                unidades.forEach(u => {
                    const texto = (typeof u === 'object') ? (u.nombre_subunidad || u.nombre || JSON.stringify(u)) : u;
                    const valor = (typeof u === 'object') ? (u.id_subunidad || u.nombre_subunidad || texto) : u;
                    tsSubUnidad.addOption({ value: valor, text: texto });
                });
                tsSubUnidad.refreshOptions(false);
            } catch (err) { console.error('Error cargando unidades:', err); }
        });

        inputDNI.addEventListener('input', function (e) {
            let dni = e.target.value.trim();
            if (dni.length === 8) {
                // Cancelar cualquier búsqueda previa en cola
                if (lookUpTimeout) clearTimeout(lookUpTimeout);

                Swal.fire({
                    title: 'Buscando Registro...',
                    text: 'Consultando base de datos por DNI: ' + dni,
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Velocidad rápida: 300ms es casi instantáneo pero deja ver que se validó
                setTimeout(() => {
                    fetch('controlador/cmn_get_data_dni.php?dni=' + dni)
                        .then(res => res.json())
                        .then(data => {
                            Swal.close();
                            if (data.exists) {
                                if (data.finalizado) {
                                    Swal.fire({
                                        icon: 'info',
                                        title: 'REGISTRO COMPLETADO',
                                        text: 'Usted ya ha completado su registro exitosamente. Si necesita corregir algún dato, comuníquese con la Unidad de Administración.',
                                        confirmButtonColor: '#003666'
                                    });
                                    form.reset();
                                    tsGrado.clear();
                                    return;
                                }

                                if (data.estado === 1) {
                                    Swal.fire({
                                        icon: 'warning',
                                        title: 'REGISTRO OBSERVADO',
                                        text: 'Su registro anterior ha sido OBSERVADO. Por favor, revise sus datos y suba el archivo PDF corregido (firmado y sellado).',
                                        confirmButtonColor: '#fb8c00'
                                    });
                                    // Permitir adjuntar archivo
                                    documentoGenerado = true;
                                }

                                // Mapeo detallado de campos con carga dinámica
                                if (data.data.grado) tsGrado.setValue(data.data.grado); else tsGrado.clear();
                                if (form.elements['cip']) form.elements['cip'].value = data.data.cip || '';
                                if (form.elements['apellidos']) form.elements['apellidos'].value = data.data.apellidos || '';
                                if (form.elements['nombres']) form.elements['nombres'].value = data.data.nombres || '';
                                if (form.elements['correo']) form.elements['correo'].value = data.data.correo || '';
                                if (form.elements['celular']) form.elements['celular'].value = data.data.celular || '';

                                // Cargar jerarquía de forma asíncrona para que los selects se llenen
                                if (data.data.region_policial) {
                                    tsRegion.setValue(data.data.region_policial);
                                    // Esperar a que se carguen las divisiones y luego poner el valor
                                    setTimeout(async () => {
                                        if (data.data.divpol_divopus) {
                                            tsDivision.setValue(data.data.divpol_divopus);
                                            // Esperar a que se carguen las unidades y luego poner el valor
                                            setTimeout(() => {
                                                if (data.data.sub_unidad) tsSubUnidad.setValue(data.data.sub_unidad);
                                                actualizarEstiloCampos(); // Resaltar todo al final de la carga
                                            }, 800);
                                        } else {
                                            actualizarEstiloCampos();
                                        }
                                    }, 800);
                                } else {
                                    actualizarEstiloCampos();
                                }

                                documentoGenerado = true; // Permite adjuntar si ya había un borrador guardado.
                                console.log("Datos recuperados servidor:", data.data);

                                Swal.fire({
                                    icon: 'success',
                                    title: '¡DATOS RECUPERADOS!',
                                    text: 'Se ha cargado su información guardada anteriormente.',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            } else {
                                // Si es nuevo, limpiar otros campos menos DNI
                                fieldsToMap.forEach(f => {
                                    if (f === 'grado') { tsGrado.clear(); }
                                    else if (form.elements[f] && f !== 'dni') form.elements[f].value = '';
                                });
                            }
                        })
                        .catch(err => {
                            Swal.close();
                            console.error('Error:', err);
                        });
                }, 2000);
            }
        });

        // Mostrar nombre del archivo al seleccionar (Paso 2)
        document.getElementById('filePdf').addEventListener('change', function (e) {
            if (!e.target.files.length) return;
            const file = e.target.files[0];

            // Validar tamaño (5MB) en JavaScript
            if (file.size > 5242880) {
                Swal.fire({ icon: 'error', title: 'Archivo muy pesado', text: 'El documento supera los 5MB requeridos. Debe comprimirlo en internet (por ejemplo en iLovePDF) antes de subirlo.', confirmButtonColor: '#003666' });
                e.target.value = '';
                document.getElementById('fileInfo').innerHTML = `
                <h6 class="mb-1">Haga clic aquí para seleccionar el PDF</h6>
                <p class="small text-muted mb-0">Formato: Solo PDF (Máx. 5MB)</p>`;
                return;
            }

            const fileName = file.name;
            document.getElementById('fileInfo').innerHTML = `
            <h6 class="mb-1 text-success fw-bold"><i class="fa-solid fa-check-circle me-2"></i> ${fileName}</h6>
            <p class="small text-muted mb-0">Archivo listo para subir.</p>
        `;
        });

        // Función enviada tras validar y guardar borrador
        function descargarPdfFinal(form) {
            const tempForm = document.createElement('form');
            tempForm.method = 'POST';
            tempForm.action = 'controlador/cmn_generar_pdf.php';
            tempForm.target = '_blank'; // Abrir en pestaña nueva para evitar bloqueos

            // Incluir TODOS los campos necesarios para el PDF
            const campos = [
                'grado', 'dni', 'cip', 'apellidos', 'nombres',
                'correo', 'celular', 'sub_unidad',
                'region_policial', 'divpol_divopus'
            ];

            campos.forEach(c => {
                const el = form.elements[c];
                if (el) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = c;
                    input.value = el.value;
                    tempForm.appendChild(input);
                }
            });

            const inputCargo = document.createElement('input');
            inputCargo.type = 'hidden';
            inputCargo.name = 'cargo';
            inputCargo.value = getCargoActual();
            tempForm.appendChild(inputCargo);

            document.body.appendChild(tempForm);
            tempForm.submit();

            // Limpiar después de un momento para no interrumpir la cola del navegador
            setTimeout(() => {
                if (document.body.contains(tempForm)) document.body.removeChild(tempForm);
            }, 1000);

            Swal.fire({
                icon: 'info',
                title: 'Generando Documento PDF...',
                text: 'Su solicitud oficial se está descargando. Por favor imprímala, fírmela y proceda al Paso 2.',
                timer: 4500
            });
        }

        // Botón: Generar Mi Solicitud .PDF (CON PRE-REGISTRO)
        document.getElementById('btnGenerarExcel').addEventListener('click', function () {
            // 1. Validar campos obligatorios
            const camposReq = ['grado', 'dni', 'cip', 'apellidos', 'nombres', 'correo', 'celular', 'sub_unidad'];
            let faltan = [];
            camposReq.forEach(c => {
                const inputEl = form.elements[c];
                if (!inputEl || !inputEl.value.trim()) {
                    const label = inputEl && inputEl.previousElementSibling ? inputEl.previousElementSibling.innerText : c;
                    faltan.push(label.replace('*', '').trim());
                }
            });

            if (faltan.length > 0) {
                Swal.fire('Datos Incompletos', 'Por favor, rellene:' + faltan.join(', '), 'warning');
                return;
            }

            // 2. Ejecutar descarga de PDF INMEDIATAMENTE para evitar bloqueadores de popups
            documentoGenerado = true; // Liberamos la carga del archivo PDF
            descargarPdfFinal(form);

            // 3. Guardado preventivo como "Borrador" en segundo plano
            const formData = new FormData(form);
            formData.append('es_borrador', '1');
            formData.append('cargo', getCargoActual());

            fetch('controlador/cmn_registro_save.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .catch(err => console.error('Error guardando borrador:', err));
        });

        // Manejo de envío de formulario por AJAX para mejor UX
        document.getElementById('formCmnRegistro').addEventListener('submit', function (e) {
            e.preventDefault();

            const form = this;
            let valorCargo = getCargoActual();

            const formData = new FormData(form);
            formData.set('cargo', valorCargo);

            Swal.fire({
                title: 'Procesando...',
                text: 'Estamos enviando su registro, por favor espere.',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            fetch(form.action, {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡REGISTRO EXITOSO!',
                            text: data.message,
                            showCancelButton: true,
                            confirmButtonColor: '#003666',
                            cancelButtonColor: '#28a745',
                            confirmButtonText: 'Terminar y Salir',
                            cancelButtonText: '<i class="fa-solid fa-download"></i> Descargar Constancia (Cargo)'
                        }).then((result) => {
                            if (result.dismiss === Swal.DismissReason.cancel) {
                                // El usuario hizo clic en "Descargar Cargo"
                                window.open('controlador/cmn_generar_cargo_pdf.php?dni=' + data.dni, '_blank');
                            }
                            // Limpiar la sesión específica del DNI enviado
                            const dniEnviado = formData.get('dni');
                            localStorage.removeItem('cmn_session_' + dniEnviado);
                            if (localStorage.getItem('cmn_last_dni') === dniEnviado) {
                                localStorage.removeItem('cmn_last_dni');
                            }
                            window.location.reload();
                        });
                    } else {
                        if (data.duplicated) {
                            Swal.fire({
                                icon: 'info',
                                title: 'YA REGISTRADO',
                                text: data.message,
                                showCancelButton: true,
                                confirmButtonColor: '#003666',
                                cancelButtonColor: '#28a745',
                                confirmButtonText: 'Entendido',
                                cancelButtonText: '<i class="fa-solid fa-download"></i> Descargar Cargo Nuevamente'
                            }).then((result) => {
                                if (result.dismiss === Swal.DismissReason.cancel) {
                                    window.open('controlador/cmn_generar_cargo_pdf.php?dni=' + data.dni, '_blank');
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'HUBO UN ERROR',
                                text: data.message,
                                confirmButtonColor: '#003666'
                            });
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'ERROR DE CONEXIÓN',
                        text: 'No se pudo conectar con el servidor. Inténtelo de nuevo más tarde.',
                        confirmButtonColor: '#003666'
                    });
                });
        });

        // Botón Limpiar (PARA COMPARTIR PC CON OTRO USUARIO)
        document.getElementById('btnLimpiarForm').addEventListener('click', () => {
            Swal.fire({
                title: '¿Limpiar pantalla?',
                text: "Se borrarán los datos actuales para que otro usuario pueda registrarse en esta computadora.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#003666',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, limpiar todo',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.reset();
                    tsGrado.clear();
                    tsRegion.clear();
                    tsDivision.clear();
                    tsSubUnidad.clear();
                    localStorage.clear();
                    Swal.fire({
                        title: 'Formulario Limpio',
                        text: 'La pantalla está lista para un nuevo registro.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            });
        });
    </script>

</body>

</html>