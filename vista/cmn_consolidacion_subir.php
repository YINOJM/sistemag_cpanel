<?php
// cmn_consolidacion_subir.php - Fase Final (Consolidación)
date_default_timezone_set('America/Lima');
require_once "../modelo/conexion.php";
$res_config = $conexion->query("SELECT valor FROM cmn_config WHERE clave = 'mantenimiento_fase3'");
$config = $res_config->fetch_assoc();
$estaCerrado = ($config && $config['valor'] === '1');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subir CMN Final (Consolidado) - 2026</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary-color: #0d2a4a; --secondary-color: #1a3c5f; --accent-color: #d32f2f; --bg-color: #f0f2f5; }
        body { font-family: 'Outfit', sans-serif; background-color: var(--bg-color); min-height: 100vh; background-image: radial-gradient(circle at top, #fdfdfd 0%, #e0e0e0 100%); }
        .premium-topbar { background: linear-gradient(to right, #2c3e50, #000000); height: 75px; display: flex; align-items: center; justify-content: space-between; padding: 0 50px; border-bottom: 3px solid var(--accent-color); color: white; }
        .container { max-width: 900px; margin: 40px auto; }
        .main-card { background: white; border-radius: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.15); overflow: hidden; border: 1px solid #ddd; }
        .card-header-gradient { background: linear-gradient(135deg, #2c3e50 0%, #000000 100%); color: white; padding: 50px 30px; text-align: center; border-bottom: 5px solid var(--accent-color); }
        .card-body { padding: 45px; }
        .section-title { color: #2c3e50; font-weight: 800; margin-bottom: 30px; text-transform: uppercase; display: flex; align-items: center; gap: 15px; font-size: 1.1rem; }
        .section-title::after { content: ''; flex: 1; height: 3px; background: linear-gradient(to right, #d32f2f, transparent); }
        .readonly-field-group { background-color: #f8f9fa; border-radius: 12px; padding: 15px; display: flex; align-items: center; margin-bottom: 15px; border: 1px solid #dee2e6; }
        .btn-main-submit { background: linear-gradient(135deg, #d32f2f 0%, #b71c1c 100%); color: white !important; border: none; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; padding: 20px; border-radius: 12px; width: 100%; box-shadow: 0 10px 25px rgba(211,47,47,0.3); transition: all 0.3s; }
        .btn-main-submit:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(211,47,47,0.4); }
        .upload-section-disabled { opacity: 0.3; pointer-events: none; filter: grayscale(1); }
        .file-upload-box { border: 3px dashed #2c3e50; border-radius: 25px; padding: 50px; text-align: center; background-color: #fff; cursor: pointer; transition: all 0.4s; }
        .file-upload-box:hover { border-color: #d32f2f; background-color: #fff8f8; transform: scale(1.02); }
        .info-alert { border-left: 6px solid #d32f2f !important; border-radius: 10px; background-color: #fff5f5; }
    </style>
</head>
<body>
    <div class="premium-topbar">
        <div class="fw-bold"><i class="fa-solid fa-box-archive me-2" style="color:#d32f2f"></i> FASE FINAL: CONSOLIDACIÓN DE CMN 2026</div>
        <div class="small opacity-75">Período de Recepción Final</div>
    </div>

    <?php if ($estaCerrado): ?>
        <div class="container text-center py-5">
            <div class="mb-4"><i class="fa-solid fa-lock fa-5x text-danger opacity-50"></i></div>
            <h1 class="display-3 fw-bold">FASE BLOQUEADA</h1>
            <p class="lead">La fase de consolidación final aún no está disponible o ha finalizado por orden superior.</p>
        </div>
    <?php else: ?>
        <div class="container">
            <div class="main-card">
                <div class="card-header-gradient">
                    <h1 class="h2 fw-bold mb-2">SUBIR CMN FINAL (CONSOLIDADO)</h1>
                    <p class="mb-0 opacity-75">Expediente Completo del Cuadro Multianual de Necesidades - 2026</p>
                </div>
                <div class="card-body">
                    <form id="formCmnAnexo" action="../controlador/cmn_anexo_fase3_save.php" method="POST" enctype="multipart/form-data">
                        <div class="section-title">Datos del Responsable de Consolidación</div>
                        <div class="row g-4 mb-5">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">DNI Autorizado</label>
                                <div class="input-group">
                                    <input type="text" id="dniInput" name="dni" class="form-control form-control-lg" maxlength="8" required>
                                    <button class="btn btn-dark" type="button" id="btnBuscarDNI"><i class="fa-solid fa-search"></i></button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">Personal Validado</label>
                                <div class="readonly-field-group">
                                    <div id="nombreCompletoSpan" class="fw-bold text-dark">Validación pendiente...</div>
                                    <input type="hidden" id="nombreCompleto" name="nombre_completo">
                                </div>
                            </div>
                            <div class="col-12" id="infoExtra" style="display:none;">
                                <div class="readonly-field-group">
                                    <div class="fw-bold text-secondary me-3">Subunidad:</div>
                                    <div id="subUnidadSpan" class="fw-bold text-danger"></div>
                                    <input type="hidden" id="subUnidad" name="sub_unidad">
                                    <input type="hidden" id="region" name="region_policial">
                                    <input type="hidden" id="divopus" name="divopus">
                                </div>
                            </div>
                        </div>

                        <div id="uploadZone" class="upload-section-disabled">
                            <div class="section-title">Adjuntar Expediente Final PDF</div>
                            <div class="info-alert mb-4 p-4 border shadow-sm">
                                <h6 class="fw-bold text-danger mb-2"><i class="fa-solid fa-triangle-exclamation"></i> ADVERTENCIA FINAL:</h6>
                                <p class="small mb-3">Este documento será el respaldo oficial ante el MEF. Asegúrese de que todas las firmas de los responsables y jefes estén incluidas en un solo archivo PDF.</p>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">Monto Total Consolidado (S/)</label>
                                        <input type="number" step="0.01" name="monto_total" class="form-control" placeholder="0.00" required>
                                    </div>
                                </div>
                            </div>
                            
                            <input type="file" name="anexo_pdf" id="filePdf" class="d-none" accept=".pdf" required>
                            <div class="file-upload-box" onclick="document.getElementById('filePdf').click();">
                                <i class="fa-solid fa-file-shield fa-4x text-danger mb-4"></i>
                                <h5 id="fileNameDisplay" class="fw-bold">Seleccionar Expediente Final PDF</h5>
                                <p class="text-muted">Arrastre aquí o haga clic para buscar el archivo</p>
                            </div>
                            <button type="submit" class="btn btn-main-submit mt-5">
                                <i class="fa-solid fa-lock-check me-2"></i> FINALIZAR Y ENVIAR CONSOLIDACIÓN
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.getElementById('btnBuscarDNI').addEventListener('click', function() {
            const dni = document.getElementById('dniInput').value;
            if(dni.length === 8) {
                Swal.fire({ title: 'Validando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                fetch('../controlador/cmn_get_data_dni.php?dni=' + dni)
                    .then(r => r.json())
                    .then(data => {
                        Swal.close();
                        if(data.exists) {
                            const name = data.data.grado + ' ' + data.data.apellidos + ', ' + data.data.nombres;
                            document.getElementById('nombreCompleto').value = name;
                            document.getElementById('nombreCompletoSpan').innerText = name;
                            document.getElementById('subUnidad').value = data.data.sub_unidad;
                            document.getElementById('subUnidadSpan').innerText = data.data.sub_unidad;
                            document.getElementById('region').value = data.data.region_policial;
                            document.getElementById('divopus').value = data.data.divpol_divopus;
                            document.getElementById('infoExtra').style.display = 'block';
                            document.getElementById('uploadZone').classList.remove('upload-section-disabled');
                        } else {
                            Swal.fire('Veto', 'Este DNI no está autorizado para el cargo de Responsable Logístico.', 'error');
                        }
                    });
            }
        });

        document.getElementById('filePdf').addEventListener('change', function(e) {
            if(this.files && this.files.length > 0) {
                document.getElementById('fileNameDisplay').innerText = "VÁLIDO: " + this.files[0].name;
                document.getElementById('fileNameDisplay').classList.add('text-danger');
            }
        });
    </script>
</body>
</html>
