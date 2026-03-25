<?php
// cmn_clasificacion_subir.php - Fase 2 (Clasificación)
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
    <title>Subir Anexo N° 02 (Clasificación) - CMN 2026</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary-color: #0d2a4a; --secondary-color: #1a3c5f; --accent-color: #28a745; --bg-color: #f0f2f5; }
        body { font-family: 'Outfit', sans-serif; background-color: var(--bg-color); min-height: 100vh; background-image: radial-gradient(circle at top, #eef2f3 0%, #d5dde1 100%); }
        .premium-topbar { background: linear-gradient(to right, #001f3f, #083358); height: 70px; display: flex; align-items: center; justify-content: space-between; padding: 0 50px; border-bottom: 3px solid var(--accent-color); color: white; }
        .container { max-width: 900px; margin: 30px auto; }
        .main-card { background: white; border-radius: 20px; box-shadow: 0 15px 40px rgba(0,0,0,0.1); overflow: hidden; }
        .card-header-gradient { background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); color: white; padding: 40px 30px; text-align: center; border-bottom: 4px solid var(--accent-color); }
        .card-body { padding: 40px; }
        .section-title { color: var(--primary-color); font-weight: 700; margin-bottom: 25px; text-transform: uppercase; display: flex; align-items: center; gap: 10px; }
        .section-title::after { content: ''; flex: 1; height: 2px; background: linear-gradient(to right, var(--accent-color), transparent); }
        .readonly-field-group { background-color: #e9ecef; border-radius: 10px; padding: 12px 15px; display: flex; align-items: center; margin-bottom: 12px; border: 1px solid #ced4da; }
        .readonly-label { min-width: 150px; font-weight: 700; color: #495057; font-size: 0.9rem; }
        .btn-main-submit { background: linear-gradient(135deg, var(--accent-color) 0%, #1e7e34 100%); color: white !important; border: none; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; padding: 18px; border-radius: 12px; width: 100%; box-shadow: 0 8px 20px rgba(40,167,69,0.3); }
        .upload-section-disabled { opacity: 0.4; pointer-events: none; filter: grayscale(1); }
        .file-upload-box { border: 2px dashed var(--primary-color); border-radius: 20px; padding: 40px; text-align: center; background-color: #ffffff; cursor: pointer; transition: all 0.3s; }
        .file-upload-box:hover { background-color: #f8f8ff; transform: scale(1.01); }
    </style>
</head>
<body>
    <div class="premium-topbar">
        <div class="fw-bold"><i class="fa-solid fa-tags me-2" style="color:#28a745"></i> FASE 2: CLASIFICACIÓN DE NECESIDADES</div>
        <div class="small opacity-75">Período de Recepción Activo</div>
    </div>

    <?php if ($estaCerrado): ?>
        <div class="container text-center py-5">
            <h1 class="display-4 fw-bold">FASE CERRADA</h1>
            <p class="lead">El periodo de recepción de Anexos N° 02 ha finalizado.</p>
        </div>
    <?php else: ?>
        <div class="container">
            <div class="main-card">
                <div class="card-header-gradient">
                    <h1 class="h3 fw-bold mb-2">SUBIR ANEXO N° 02 (CLASIFICACIÓN)</h1>
                    <p class="mb-0 opacity-75">SIAF-Web | Cuadro Multianual de Necesidades - CMN 2026</p>
                </div>
                <div class="card-body">
                    <form id="formCmnAnexo" action="../controlador/cmn_anexo_fase2_save.php" method="POST" enctype="multipart/form-data">
                        <div class="section-title">Identificación del Responsable</div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">DNI del Responsable</label>
                                <div class="input-group">
                                    <input type="text" id="dniInput" name="dni" class="form-control" maxlength="8" required>
                                    <button class="btn btn-primary" type="button" id="btnBuscarDNI"><i class="fa-solid fa-search"></i></button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">Nombre Completo</label>
                                <div class="readonly-field-group">
                                    <div id="nombreCompletoSpan" class="fw-bold">Esperando DNI...</div>
                                    <input type="hidden" id="nombreCompleto" name="nombre_completo">
                                </div>
                            </div>
                            <div class="col-12 mt-2" id="infoExtra" style="display:none;">
                                <div class="readonly-field-group">
                                    <div class="readonly-label">Subunidad:</div>
                                    <div id="subUnidadSpan" class="fw-bold">---</div>
                                    <input type="hidden" id="subUnidad" name="sub_unidad">
                                    <input type="hidden" id="region" name="region_policial">
                                    <input type="hidden" id="divopus" name="divopus">
                                </div>
                            </div>
                        </div>

                        <div id="uploadZone" class="upload-section-disabled">
                            <div class="section-title mt-5">Adjuntar Anexo N° 02</div>
                            <div class="info-alert mb-3 p-3 bg-light border-start border-4 border-primary">
                                <i class="fa-solid fa-info-circle text-primary"></i> Suba el archivo PDF completo y debidamente firmado.
                                <div class="mt-2">
                                    <label class="form-label small fw-bold text-muted">Monto Total del Anexo 02 (S/)</label>
                                    <input type="number" step="0.01" name="monto_total" class="form-control" placeholder="0.00" required>
                                </div>
                            </div>
                            <input type="file" name="anexo_pdf" id="filePdf" class="d-none" accept=".pdf" required>
                            <div class="file-upload-box" onclick="document.getElementById('filePdf').click();">
                                <i class="fa-solid fa-cloud-arrow-up fa-3x text-primary mb-3"></i>
                                <h5 id="fileNameDisplay">Haga clic aquí para seleccionar el archivo PDF</h5>
                                <p class="text-muted small">Tamaño máximo: 5MB</p>
                            </div>
                            <button type="submit" class="btn btn-main-submit mt-4">
                                <i class="fa-solid fa-paper-plane me-2"></i> ENVIAR ANEXO DE CLASIFICACIÓN
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
                Swal.fire({ title: 'Buscando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
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
                            Swal.fire('No encontrado', 'DNI no registrado en el padrón.', 'error');
                        }
                    });
            }
        });

        document.getElementById('filePdf').addEventListener('change', function(e) {
            if(this.files && this.files.length > 0) {
                document.getElementById('fileNameDisplay').innerText = "Seleccionado: " + this.files[0].name;
                document.getElementById('fileNameDisplay').classList.add('text-success');
            }
        });
    </script>
</body>
</html>
