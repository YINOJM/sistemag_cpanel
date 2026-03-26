<?php
// cmn_clasificacion_subir.php - Fase 2 (Clasificación)
session_start();
date_default_timezone_set('America/Lima');
require_once "../modelo/conexion.php";
$res_config = $conexion->query("SELECT valor FROM cmn_config WHERE clave = 'mantenimiento_fase2'");
$config = $res_config->fetch_assoc();
$estaCerrado = ($config && $config['valor'] === '1');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subir Anexo N° 02 (Clasificación) - CMN <?= defined('ANIO_CMN') ? ANIO_CMN : date('Y') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0d2a4a;      /* Azul oscuro premium */
            --secondary-color: #1a3c5f;    /* Tono medio */
            --accent-color: #28a745;       /* Verde para Clasificación */
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
            background-color: #218838;
            transform: scale(1.05);
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
            box-shadow: 0 12px 30px rgba(40, 167, 69, 0.5);
            filter: brightness(1.1);
        }

        .info-alert {
            background-color: #fff3cd;
            border-left: 5px solid var(--accent-color);
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

        .upload-section-disabled { opacity: 0.5; pointer-events: none; filter: grayscale(1); }
    </style>
</head>
<body>
    <div class="premium-topbar">
        <div class="topbar-logo">
            <i class="fa-solid fa-shield-halved"></i>
            (ii) FASE DE CLASIFICACIÓN Y PRIORIZACIÓN
        </div>
    </div>

    <div class="container">
        <div class="main-card shadow-lg">
            <div class="card-header-gradient">
                <h2 class="fw-bold mb-1 animate__animated animate__fadeInDown" style="letter-spacing: -0.5px;">SUBIR ANEXO N° 02 (CLASIFICACIÓN Y PRIORIZACIÓN)</h2>
                <p class="mb-0 opacity-75" style="font-size: 0.95rem;">SIAF-Web | Cuadro Multianual de Necesidades - CMN <?= defined('ANIO_CMN') ? ANIO_CMN : date('Y') ?></p>
            </div>

            <div class="card-body">
                <?php if($estaCerrado): ?>
                    <div class="alert alert-danger rounded-4 p-4 border-0 shadow-sm animate__animated animate__shakeX">
                        <div class="d-flex align-items-center gap-3">
                            <i class="fa-solid fa-clock-rotate-left fa-3x"></i>
                            <div>
                                <h5 class="fw-bold mb-1">RECEPCIÓN FINALIZADA</h5>
                                <p class="mb-0">El periodo para la carga del Anexo N° 02 ha concluido. Contacte con la Oficina de Programación si requiere asistencia.</p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <form id="formCmnAnexo" action="../controlador/cmn_anexo_fase2_save.php" method="POST" enctype="multipart/form-data">

                        <div class="info-alert mb-4 d-flex align-items-center">
                            <i class="fa-solid fa-circle-info fa-2x me-3 opacity-50"></i>
                            <div>
                                <strong>Paso Intermedio de la Programación:</strong>
                                Usted debe subir el <strong>Anexo N° 02: Cuadro Multianual de Necesidades - Fase de Clasificación y Priorización</strong> debidamente firmado y sellado por el responsable del área usuaria en formato PDF.
                            </div>
                        </div>

                        <div class="section-title">Identificación del Responsable</div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">Número de DNI</label>
                                <div class="input-group">
                                    <input type="text" id="dniInput" name="dni" class="form-control" maxlength="8" placeholder="12345678" required>
                                    <button class="btn btn-search" type="button" id="btnBuscarDNI"><i class="fa-solid fa-search"></i> BUSCAR DNI</button>
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
                                    <input type="hidden" id="region" name="region_policial">
                                </div>
                                <div class="readonly-field-group">
                                    <div class="readonly-label">División:</div>
                                    <div class="readonly-value"><i class="fa-solid fa-lock"></i> <span id="divopusSpan">---</span></div>
                                    <input type="hidden" id="divopus" name="divpol_divopus">
                                </div>
                                <div class="readonly-field-group">
                                    <div class="readonly-label">Subunidad:</div>
                                    <div class="readonly-value"><i class="fa-solid fa-lock"></i> <span id="subUnidadSpan">---</span></div>
                                    <input type="hidden" id="subUnidad" name="sub_unidad">
                                </div>
                            </div>
                        </div>

                        <div id="uploadZone" class="upload-section-disabled">
                            <div class="section-title mt-5">Adjuntar Anexo N° 02</div>
                            
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-check mb-3 p-3 border rounded bg-warning bg-opacity-10 border-warning">
                                        <input class="form-check-input ms-0 me-2" type="checkbox" id="checkFirmas" value="1" required style="width: 1.5em; height: 1.5em; border: 2px solid var(--accent-color);">
                                        <label class="form-check-label fw-bold text-dark" for="checkFirmas">
                                            <i class="fa-solid fa-check-double me-1 text-success"></i> DECLARO QUE MI PDF CUENTA CON TODAS LAS FIRMAS Y SELLOS DEL ANEXO N° 02.
                                        </label>
                                    </div>

                                    <input type="file" name="anexo_pdf" id="filePdf" class="d-none" accept=".pdf" required>

                                    <div class="file-upload-box" id="dropZone" onclick="
                                        if(!document.getElementById('checkFirmas').checked) { 
                                            Swal.fire({icon:'warning', title:'Atención', text:'Debe marcar la declaración antes de subir el archivo.', confirmButtonColor: '#0d2a4a'}); 
                                            return;
                                        }
                                        document.getElementById('filePdf').click(); 
                                    ">
                                        <div class="d-flex justify-content-center gap-3 mb-3">
                                            <i class="fa-solid fa-file-signature text-danger" style="font-size: 5rem;"></i>
                                            <i class="fa-solid fa-upload text-primary animate__animated animate__bounce animate__infinite" style="font-size: 2rem; position: absolute; transform: translate(40px, -20px);"></i>
                                        </div>

                                        <div id="fileInfo">
                                            <h5 class="mb-1 fw-bold" id="fileNameDisplay">Haga clic aquí para seleccionar el archivo PDF (Anexo 02)</h5>
                                            <p class="text-muted mb-0">Formatos aceptados: .pdf (Máx. 5MB)</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 text-center upload-section-disabled" id="submitZone">
                            <button type="submit" class="btn btn-main-submit w-100">
                                <i class="fa-solid fa-cloud-arrow-up me-2"></i> FINALIZAR Y ENVIAR REGISTRO
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const dniInput = document.getElementById('dniInput');
        
        function buscarDNI() {
            const dni = dniInput.value;
            if(dni.length === 8) {
                Swal.fire({ title: 'Buscando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                fetch('../controlador/cmn_get_data_dni.php?dni=' + dni + '&fase=2')
                    .then(r => r.json())
                    .then(data => {
                        Swal.close();
                        if(data.exists) {
                            if(data.has_annex) {
                                let det = data.annex_details;
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'DOCUMENTO YA REMITIDO',
                                    html: `<div class="text-start mt-2" style="font-size: 0.88rem;">
                                            <p class="mb-2">El <b>Anexo N° 02 - Fase de Clasificación</b> de su Sub Unidad ya fue enviado.</p>
                                            <div class="border rounded p-3 bg-light mb-2">
                                                <div class="mb-1"><b>Sub Unidad:</b> ${det.sub_unidad}</div>
                                                <div class="mb-1"><b>Remitido por:</b> ${det.responsable}</div>
                                                <div><b>Fecha:</b> ${det.fecha}</div>
                                            </div>
                                            <p class="mb-0 text-danger fw-bold">No es necesario realizar un nuevo envío.</p>
                                           </div>`,
                                    confirmButtonColor: '#0d2a4a',
                                    confirmButtonText: 'ENTENDIDO'
                                });
                                document.getElementById('uploadZone').classList.add('upload-section-disabled');
                                document.getElementById('submitZone').classList.add('upload-section-disabled');
                            } else {
                                document.getElementById('uploadZone').classList.remove('upload-section-disabled');
                                document.getElementById('submitZone').classList.remove('upload-section-disabled');
                                Swal.fire({ icon: 'success', title: '¡Identificado!', text: 'Puede subir el Anexo N° 02.', timer: 1500, showConfirmButton: false });
                            }

                            const name = data.data.grado + ' ' + data.data.apellidos + ', ' + data.data.nombres;
                            document.getElementById('nombreCompleto').value = name;
                            document.getElementById('nombreCompletoSpan').querySelector('span').innerText = name;
                            
                            document.getElementById('cargo').value = data.data.cargo || '--';
                            document.getElementById('cargoSpan').innerText = data.data.cargo || '--';

                            document.getElementById('subUnidad').value = data.data.sub_unidad;
                            document.getElementById('subUnidadSpan').innerText = data.data.sub_unidad;
                            document.getElementById('region').value = data.data.region_policial;
                            document.getElementById('regionSpan').innerText = data.data.region_policial;
                            document.getElementById('divopus').value = data.data.divpol_divopus;
                            document.getElementById('divopusSpan').innerText = data.data.divpol_divopus;
                        } else {
                            Swal.fire('No habilitado', 'Usted no figura en el Padrón de Responsables para el año <?= ANIO_CMN ?>.', 'info');
                        }
                    });
            }
        }

        document.getElementById('btnBuscarDNI').addEventListener('click', buscarDNI);

        dniInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            if(this.value.length === 8) buscarDNI();
        });

        document.getElementById('filePdf').addEventListener('change', function(e) {
            if(this.files && this.files.length > 0) {
                document.getElementById('fileNameDisplay').innerText = "Seleccionado: " + this.files[0].name;
                document.getElementById('fileNameDisplay').classList.add('text-success');
            }
        });
        document.addEventListener('DOMContentLoaded', function() {
            <?php if(isset($_SESSION['msg_status'])): ?>
                Swal.fire({
                    icon: '<?= $_SESSION['msg_status'] ?>',
                    title: '<?= $_SESSION['msg_status'] === 'success' ? '¡Todo listo!' : 'Ops...' ?>',
                    text: '<?= $_SESSION['msg_texto'] ?>',
                    confirmButtonColor: '<?= $_SESSION['msg_status'] === 'success' ? '#28a745' : '#d33' ?>'
                });
                <?php unset($_SESSION['msg_status'], $_SESSION['msg_texto']); ?>
            <?php endif; ?>
        });
    </script>
</body>
</html>
