<?php
session_start();
// cmn_identificacion_subir.php
// Página pública para que los responsables logísticos suban el Anexo N° 01 firmado de la Fase de Identificación.

date_default_timezone_set('America/Lima');

// CONFIGURACIÓN DE PLAZO (Desde Base de Datos)
require_once "../modelo/conexion.php";
$res_config = $conexion->query("SELECT valor FROM cmn_config WHERE clave = 'mantenimiento'");
$config = $res_config->fetch_assoc();
// Permitimos que en localhost también se cierre si el valor es 1, para que el usuario pueda probar el botón
$estaCerrado = ($config && $config['valor'] === '1'); 

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subir Anexo N° 01 - CMN <?= ANIO_CMN ?> | REGPOL LIMA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    
    <style>
        :root {
            --primary-color: #0d2a4a;      /* Azul oscuro premium */
            --secondary-color: #1a3c5f;    /* Tono medio */
            --accent-color: #f59e0b;       /* Ámbar para Identificación (Fase 1) */
            --bg-color: #f0f2f5;
            --card-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-color);
            min-height: 100vh;
            padding: 0;
            margin: 0;
            background-image: radial-gradient(circle at top, #eef2f3 0%, #d5dde1 100%);
        }

        /* Top Bar Estilo Imagen */
        .premium-topbar {
            background: linear-gradient(to right, #001f3f, #083358);
            height: 70px;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 50px;
            border-bottom: 3px solid var(--accent-color);
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            position: relative;
        }

        .premium-topbar::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 200px;
            height: 8px;
            background: var(--accent-color);
            border-radius: 0 0 10px 10px;
            box-shadow: 0 0 15px var(--accent-color);
        }

        .topbar-logo {
            color: white;
            font-weight: 700;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .topbar-logo i { color: var(--accent-color); font-size: 1.5rem; }

        .container {
            max-width: 900px;
            margin: -30px auto 50px auto;
            position: relative;
            z-index: 10;
        }

        .main-card {
            background: white;
            border-radius: 0 0 20px 20px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            border: none;
        }

        .card-header-gradient {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 50px 30px;
            text-align: center;
            border-bottom: 4px solid var(--accent-color);
            position: relative;
        }

        .card-body {
            padding: 50px;
        }

        .section-title {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 25px;
            text-transform: uppercase;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title::after {
            content: '';
            flex: 1;
            height: 2px;
            background: linear-gradient(to right, var(--accent-color), transparent);
        }

        /* Campos bloqueados estilo imagen */
        .readonly-field-group {
            background-color: #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            border: 1px solid #ced4da;
        }

        .readonly-label {
            min-width: 150px;
            font-weight: 700;
            color: #495057;
            font-size: 0.9rem;
        }

        .readonly-value {
            color: var(--primary-color);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .readonly-value i { color: #6c757d; font-size: 0.8rem; }

        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 2px solid #dee2e6;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            box-shadow: 0 0 0 4px rgba(13, 42, 74, 0.1);
            border-color: var(--primary-color);
        }

        .input-group .btn-search {
            background-color: var(--accent-color);
            color: white;
            border: none;
            padding: 0 20px;
            font-weight: 700;
            transition: all 0.2s;
        }

        .input-group .btn-search:hover {
            background-color: #b08e4e;
            transform: scale(1.05);
        }

        /* Estilo para campos con contenido (Dorado suave) */
        .is-filled {
            background-color: #fffaf0 !important;
            border-color: var(--accent-color) !important;
            color: var(--primary-color) !important;
        }

        .btn-main-submit {
            background: linear-gradient(135deg, var(--primary-color) 0%, #001f3f 100%);
            color: #ffffff !important;
            font-size: 1.1rem;

            border: none;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            padding: 18px;
            border-radius: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(13, 42, 74, 0.4);
        }

        .btn-main-submit:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(13, 42, 74, 0.5);
            filter: brightness(1.1);
        }

        .info-alert {
            background-color: #fff3cd;
            border-left: 5px solid #ffca2c;
            color: #856404;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 30px;
            font-size: 0.95rem;
        }

        .file-upload-box {
            border: 2px dashed var(--primary-color);
            border-radius: 20px;
            padding: 50px 30px;
            text-align: center;
            background-color: #ffffff;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        .file-upload-box:hover {
            background-color: #f8f8ff;
            border-color: var(--secondary-color);
            transform: scale(1.01);
            box-shadow: 0 10px 20px rgba(75, 73, 172, 0.12);
        }

        .file-upload-box.dragover {
            background-color: #f0efff;
            border-color: var(--primary-color);
            border-style: solid;
        }

        .file-upload-box i {
            font-size: 60px;
            color: var(--primary-color);
            margin-bottom: 15px;
            transition: transform 0.3s ease;
        }

        .file-upload-box:hover i {
            transform: translateY(-5px);
        }

        /* Bloqueo visual hasta que se valide DNI */
        .upload-section-disabled {
            opacity: 0.4;
            pointer-events: none;
            filter: grayscale(1);
            transition: all 0.5s ease;
        }

        @media (max-width: 768px) {
            .card-body { padding: 25px; }
            .card-header-gradient { padding: 30px 20px; }
        }

        /* Estilos Fase Cerrada */
        .phase-closed-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(4px);
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
        }
        .phase-closed-card {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(220, 53, 69, 0.2);
            text-align: center;
            max-width: 400px;
            border: 2px solid #ffc107;
        }
    </style>
</head>

<body>

    <!-- Top Bar Estilo Premium -->
    <div class="premium-topbar">
        <div class="topbar-logo">
            <i class="fa-solid fa-shield-halved"></i>
            PORTAL DE CARGA DE DOCUMENTOS PDF
        </div>
        <div class="text-white small opacity-75">
            <i class="fa-regular fa-clock me-1"></i> Período de Recepción Activo
        </div>
    </div>

    <?php if ($estaCerrado): ?>
        <div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
            <div class="card shadow-lg border-0 text-center p-5 rounded-4" style="max-width: 500px;">
                <div class="mb-4">
                    <i class="fa-solid fa-circle-exclamation text-danger" style="font-size: 5rem;"></i>
                </div>
                <h2 class="fw-bold text-dark">SISTEMA FUERA DE SERVICIO</h2>
                <p class="text-muted lead">El periodo de recepción de anexos ha finalizado o el sistema está en mantenimiento.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="container py-5">
            <div class="main-card">
                <div class="card-header-gradient">
                    <div class="d-flex justify-content-center gap-4 mb-3">
                        <i class="fa-solid fa-file-invoice-dollar fa-3x" style="color: rgba(255,255,255,0.8)"></i>
                        <i class="fa-solid fa-upload fa-3x" style="color: rgba(255,255,255,0.8)"></i>
                    </div>
                    <h1 class="h3 fw-bold mb-2">REMISIÓN DE ANEXO N° 01 (FASE IDENTIFICACIÓN)</h1>
                    <p class="mb-0 opacity-75">SIAF-Web | Cuadro Multianual de Necesidades - CMN <?= ANIO_CMN ?></p>
                </div>

                <div class="card-body position-relative">
                    <?php if($estaCerrado): ?>
                        <div class="phase-closed-overlay">
                            <div class="phase-closed-card animate__animated animate__zoomIn">
                                <i class="fa-solid fa-clock-rotate-left fa-4x text-warning mb-3"></i>
                                <h3 class="fw-bold text-danger">FASE FINALIZADA</h3>
                                <p class="text-muted">El plazo para la remisión del <b>Anexo N° 01 (Fase de Identificación)</b> ha concluido según el cronograma institucional.</p>
                                <div class="bg-light p-2 rounded small text-dark border">
                                    Si requiere una habilitación excepcional, comuníquese con la <b>Oficina de Programación</b>.
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="info-alert mb-4 d-flex align-items-center <?= $estaCerrado ? 'upload-section-disabled' : '' ?>">
                        <i class="fa-solid fa-circle-info fa-2x me-3 opacity-50"></i>
                        <div>
                            <strong>Paso Inicial de la Programación:</strong>
                            Usted debe subir el <strong>Anexo N° 01: Cuadro Multianual de Necesidades - Fase de Identificación</strong> debidamente <strong>visado por el Jefe de Unidad / Responsable del Área Usuaria</strong> en formato PDF.
                        </div>
                    </div>

                    <form id="formCmnAnexo" action="../controlador/cmn_anexo_save.php" method="POST" enctype="multipart/form-data" class="<?= $estaCerrado ? 'upload-section-disabled' : '' ?>">

                        <div class="section-title">Identificación del Responsable</div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">Número de DNI</label>
                                <div class="input-group">
                                    <input type="text" id="dniInput" name="dni" class="form-control" maxlength="8" pattern="\d{8}" placeholder="12345678" >

                                    <button class="btn btn-search" type="button" id="btnBuscarDNI" formnovalidate><i class="fa-solid fa-search"></i> BUSCAR DNI</button>

                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">Nombre Completo del Responsable</label>
                                <div class="readonly-field-group">
                                    <div class="readonly-value w-100" id="nombreCompletoSpan"><i class="fa-solid fa-user"></i> <span>Esperando DNI...</span></div>
                                    <input type="hidden" id="nombreCompleto" name="nombre_completo">
                                </div>
                            </div>
                            
                            <div class="col-12 mt-2">
                                <div class="readonly-field-group">
                                    <div class="readonly-label">Cargo / Posición:</div>
                                    <div class="readonly-value"><i class="fa-solid fa-user-tie"></i> <span id="cargoSpan">---</span></div>
                                    <input type="hidden" id="cargo" name="cargo">
                                </div>
                                <div class="readonly-field-group">
                                    <div class="readonly-label">Región Policial:</div>
                                    <div class="readonly-value"><i class="fa-solid fa-lock"></i> <span id="regionSpan">---</span></div>
                                    <input type="hidden" id="regionPolicial" name="region_policial">
                                </div>
                                <div class="readonly-field-group">
                                    <div class="readonly-label">División:</div>
                                    <div class="readonly-value"><i class="fa-solid fa-lock"></i> <span id="divopusSpan">---</span></div>
                                    <input type="hidden" id="divopus" name="divopus">
                                </div>
                                <div class="readonly-field-group">
                                    <div class="readonly-label">Subunidad:</div>
                                    <div class="readonly-value"><i class="fa-solid fa-lock"></i> <span id="subUnidadSpan">---</span></div>
                                    <input type="hidden" id="subUnidad" name="sub_unidad">
                                </div>
                            </div>
                        </div>


                        <div id="uploadZone" class="upload-section-disabled">
                            <div class="section-title mt-5">Adjuntar Documento</div>
                            
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-check mb-3 p-3 border rounded bg-warning bg-opacity-10 border-warning">
                                        <input class="form-check-input ms-0 me-2" type="checkbox" id="checkFirmas" value="1" required style="width: 1.5em; height: 1.5em; border: 2px solid var(--accent-color);">
                                        <label class="form-check-label fw-bold text-dark" for="checkFirmas">
                                            <i class="fa-solid fa-check-double me-1 text-success"></i> DECLARO QUE MI PDF CUENTA CON LA FIRMA DEL RESPONSABLE DEL ÁREA USUARIA.
                                        </label>
                                    </div>

                                    <input type="file" name="anexo_pdf" id="filePdf" class="d-none" accept=".pdf" >

                                    
                                    <div class="file-upload-box" id="dropZone" onclick="
                                        if(!document.getElementById('checkFirmas').checked) { 
                                            Swal.fire({icon:'warning', title:'Atención', text:'Debe marcar la declaración de firmas antes de subir el archivo.', confirmButtonColor: '#0d2a4a'}); 
                                            return;
                                        }
                                        document.getElementById('filePdf').click(); 
                                    ">
                                        <div class="d-flex justify-content-center gap-3 mb-3">
                                            <i class="fa-solid fa-file-signature text-danger" style="font-size: 5rem;"></i>
                                            <i class="fa-solid fa-upload text-primary animate__animated animate__bounce animate__infinite" style="font-size: 2rem; position: absolute; transform: translate(40px, -20px);"></i>
                                        </div>

                                        <div id="fileInfo">
                                            <h5 class="mb-1 fw-bold">Arrastra y suelta tu documento PDF aquí o haz clic para subir</h5>
                                            <p class="text-muted mb-0">Formatos aceptados: .pdf (Máx. 5MB)</p>
                                            <button type="button" class="btn btn-sm btn-outline-primary mt-3 px-4 fw-bold">SELECCIONAR ARCHIVO</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 text-center upload-section-disabled" id="submitZone">
                            <div class="col-12">
                                <button type="submit" class="btn btn-main-submit w-100 py-3 rounded-4 shadow-lg animate__animated animate__pulse animate__infinite animate__slow">
                                    <i class="fa-solid fa-cloud-arrow-up me-2"></i> ENVIAR ANEXO Y REPORTAR CUMPLIMIENTO
                                </button>
                            </div>
                        </div>
                    </form>


                </div>
                
                <div class="card-footer bg-light text-center py-4">
                    <small class="text-muted" style="font-size: 0.7rem;">&copy; <?= date('Y') ?> | OFICINA DE PROGRAMACIÓN - UNIDAD DE ADMINISTRACIÓN PNP</small>
                </div>
            </div>
        </div>
    <?php endif; ?>


    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const inputDNI = document.getElementById('dniInput');

        const btnBuscarDNI = document.getElementById('btnBuscarDNI');
        const fileInput = document.getElementById('filePdf');
        const fileInfo = document.getElementById('fileInfo');
        const formCmn = document.getElementById('formCmnAnexo');

        if (!inputDNI || !btnBuscarDNI) {
            console.warn("Faltan elementos críticos del formulario.");
        }

        // VALIDACIÓN FINAL DEL FORMULARIO
        if (formCmn) {
            formCmn.addEventListener('submit', function(e) {
                if (inputDNI.value.length !== 8) {
                    e.preventDefault();
                    Swal.fire('Atención', 'Debe validar un DNI de 8 dígitos.', 'warning');
                    return;
                }
                if (!document.getElementById('checkFirmas').checked) {
                    e.preventDefault();
                    Swal.fire('Atención', 'Debe marcar la declaración de firmas.', 'warning');
                    return;
                }
                if (fileInput.files.length === 0) {
                    e.preventDefault();
                    Swal.fire('Atención', 'Debe seleccionar el archivo PDF del Anexo N° 01.', 'warning');
                    return;
                }
            });
        }

        function buscarDNI_Final() {
            let dni = inputDNI.value.trim();
            if (dni.length === 8) {
                Swal.fire({ 
                    title: 'Verificando Responsable...', 
                    text: 'DNI: ' + dni,
                    allowOutsideClick: false, 
                    didOpen: () => { Swal.showLoading(); }
                });

                // URL DETERMINISTA PARA LOCAL Y WEB
                const controllerPath = '<?= BASE_URL ?>controlador/cmn_get_data_dni.php';

                fetch(controllerPath + '?dni=' + dni + '&nocache=' + Math.random())
                    .then(res => res.json())
                    .then(data => {
                        Swal.close();
                        if (data.exists) {
                            if (data.has_annex) {
                                let det = data.annex_details;
                                Swal.fire({
                                    icon: 'warning',
                                    title: '⚠️ DOCUMENTO YA REMITIDO',
                                    html: `<div class="text-start mt-2" style="font-size: 0.88rem;">
                                            <p class="mb-2">El <b>Anexo N° 01 - Fase de Identificación</b> de su Sub Unidad ya fue enviado correctamente.</p>
                                            <div class="border rounded p-3 bg-light mb-3">
                                                <div class="mb-1"><i class="fa-solid fa-location-dot text-danger me-2"></i><b>Región:</b> ${det.region_policial || data.data.region_policial}</div>
                                                <div class="mb-1"><i class="fa-solid fa-building-columns text-warning me-2"></i><b>DIVOPUS:</b> ${det.divopus || data.data.divpol_divopus}</div>
                                                <div class="mb-2"><i class="fa-solid fa-shield-halved text-primary me-2"></i><b>Sub Unidad:</b> ${det.sub_unidad || data.data.sub_unidad}</div>
                                                <hr class="my-2">
                                                <div class="mb-1"><i class="fa-solid fa-user-check text-success me-2"></i><b>Remitido por:</b> ${det.responsable}</div>
                                                <div><i class="fa-regular fa-calendar-check text-info me-2"></i><b>Fecha y hora:</b> ${det.fecha}</div>
                                            </div>
                                            <p class="mb-0 text-danger fw-bold"><i class="fa-solid fa-ban me-1"></i>No es necesario realizar un nuevo envío para esta Sub Unidad.</p>
                                           </div>`,
                                    confirmButtonColor: '#0d2a4a',
                                    confirmButtonText: '✓ ENTENDIDO',
                                    width: 550
                                });
                                document.getElementById('uploadZone').classList.add('upload-section-disabled');
                                document.getElementById('submitZone').classList.add('upload-section-disabled');
                            } else {
                                document.getElementById('uploadZone').classList.remove('upload-section-disabled');
                                document.getElementById('submitZone').classList.remove('upload-section-disabled');
                                Swal.fire({ icon: 'success', title: '¡Identificado!', text: 'Ya puede subir su archivo del Anexo N° 01.', timer: 2000, showConfirmButton: false });
                            }

                            const fullName = data.data.grado + ' ' + data.data.apellidos + ', ' + data.data.nombres;
                            document.getElementById('nombreCompleto').value = fullName;
                            document.getElementById('nombreCompletoSpan').innerHTML = '<i class="fa-solid fa-user-check text-success"></i> <span>' + fullName + '</span>';
                            
                            document.getElementById('cargo').value = data.data.cargo || '--';
                            document.getElementById('cargoSpan').innerText = data.data.cargo || '--';

                            document.getElementById('regionPolicial').value = data.data.region_policial || '--';
                            document.getElementById('regionSpan').innerText = data.data.region_policial || '--';
                            
                            document.getElementById('divopus').value = data.data.divpol_divopus || '--';
                            document.getElementById('divopusSpan').innerText = data.data.divpol_divopus || '--';
                            
                            document.getElementById('subUnidad').value = data.data.sub_unidad || '--';
                            document.getElementById('subUnidadSpan').innerText = data.data.sub_unidad || '--';
                            
                            actualizarEstiloCampos();
                        } else {
                            Swal.fire({
                                icon: 'info',
                                title: 'ACCESO NO HABILITADO',
                                html: `<div class="text-start" style="font-size: 0.9rem;">
                                        <p>Su DNI <b>${dni}</b> no figura en el Padrón de Responsables Logísticos habilitados para la <b>Fase de Identificación CMN <?= ANIO_CMN ?></b>.</p>
                                        <div class="border-start border-4 border-warning ps-3 py-2 bg-light rounded mb-3">
                                            <p class="mb-1 fw-bold text-dark"><i class="fa-solid fa-phone me-2 text-success"></i>¿Eres responsable logístico de tu unidad?</p>
                                            <p class="mb-0 text-muted" style="font-size: 0.85rem;">Comuníquese con la <b>Oficina de Programación - UE 009 VII DIRTEPOL LIMA</b> para verificar y gestionar su habilitación de acceso, previa confirmación del jefe de su unidad policial.</p>
                                        </div>
                                        <p class="mb-0 text-muted small"><i class="fa-solid fa-circle-info me-1"></i>Si usted salió de vacaciones o fue reasignado recientemente, su acceso puede aún no estar actualizado en el sistema.</p>
                                       </div>`,
                                confirmButtonColor: '#0d2a4a',
                                confirmButtonText: 'ENTENDIDO',
                                width: 550
                            });
                        }


                    })
                    .catch(err => {
                        console.error("Error en búsqueda:", err);
                        Swal.fire('Error', 'Problema de conexión con el servidor local.', 'error');
                    });
            } else {
                Swal.fire({ icon: 'warning', title: 'Atención', text: 'El DNI debe tener 8 dígitos.', confirmButtonColor: '#4b49ac' });
            }
        }

        if (btnBuscarDNI) {
            btnBuscarDNI.addEventListener('click', function(e) {
                e.preventDefault();
                console.log("¡Clic en buscar!");
                buscarDNI_Final();
            });
        }

        inputDNI.addEventListener('input', function (e) {
            // Eliminar cualquier cosa que no sea número inmediatamente
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length === 8) buscarDNI_Final();
        });

        inputDNI.addEventListener('paste', function (e) {
            e.preventDefault();
            // Obtener texto del portapapeles y limpiar espacios/caracteres raros
            let pasteData = (e.clipboardData || window.clipboardData).getData('text');
            let cleanData = pasteData.replace(/[^0-9]/g, '').substring(0, 8);
            this.value = cleanData;
            if (this.value.length === 8) buscarDNI_Final();
        });

        inputDNI.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarDNI_Final();
            }
        });

        // Actualizar UI de archivo
        fileInput.addEventListener('change', function() {
            if (this.files && this.files.length > 0) updateFileUI(this.files[0]);
        });

        function updateFileUI(file) {
            if (file.size > 5242880) {
                Swal.fire('Archivo Excedido', 'El PDF no puede pesar más de 5MB.', 'error');
                fileInput.value = '';
                return;
            }
            fileInfo.innerHTML = `
                <div class="animate__animated animate__fadeIn">
                    <h5 class="mb-1 text-success fw-bold"><i class="fa-solid fa-circle-check me-2"></i> ${file.name}</h5>
                    <p class="text-muted mb-0 small">Listo para enviar (${(file.size / 1024 / 1024).toFixed(2)} MB)</p>
                </div>
            `;
            document.getElementById('dropZone').classList.add('is-filled');
        }

        function actualizarEstiloCampos() {
            document.querySelectorAll('.form-control').forEach(el => {
                if (el.value && el.value.trim() !== '') el.classList.add('is-filled');
            });
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php if (isset($_SESSION['msg_status'])): 
        $status = $_SESSION['msg_status'];
        $texto = $_SESSION['msg_texto'];
        $last_id = $_SESSION['last_anexo_id'] ?? '';
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($status === 'success' && !empty($last_id)): ?>
                Swal.fire({
                    title: '<h3 class="fw-bold mb-0" style="font-size: 2.2rem; color: #444;">¡REGISTRO EXITOSO!</h3>',
                    html: '<p style="color: #666; font-size: 1.1rem; margin-top: 10px;">Su registro ha sido completado con éxito.</p>',
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#0d2a4a',
                    confirmButtonText: '<i class="fa-solid fa-download me-2"></i> Descargar Constancia (Cargo)',
                    cancelButtonText: 'Terminar y Salir',
                    allowOutsideClick: false,
                    reverseButtons: true, // Esto pone el botón de descarga a la derecha según tu imagen
                    width: 600,
                    padding: '3em'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.open('../controlador/cmn_cargo_fase1.php?id=<?= $last_id ?>', '_blank');
                        // No refrescamos inmediatamente para que no desaparezca el modal mientras descarga
                    } else {
                        // Al dar clic en Terminar y Salir, redirigimos a donde desees o limpiamos
                        window.location.href = "login.php"; 
                    }
                });
            <?php else: ?>
                Swal.fire({
                    icon: '<?= $status ?>',
                    title: '<?= $status == 'success' ? '¡LOGRADO!' : '¡ATENCIÓN!' ?>',
                    text: '<?= addslashes($texto) ?>',
                    confirmButtonColor: '#0d2a4a'
                });
            <?php endif; ?>
        });
    </script>
    <?php unset($_SESSION['msg_status'], $_SESSION['msg_texto'], $_SESSION['last_anexo_id']); ?>
    <?php endif; ?>
</body>
</html>

