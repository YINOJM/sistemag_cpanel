<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../modelo/conexion.php';

// Verificación de seguridad
if (empty($_SESSION['id']) || !userCan('cmn')) {
    header("Location: " . (defined('BASE_URL') ? BASE_URL : '/sistem_job/') . "vista/inicio.php");
    exit();
}

$titulo_pagina = "CMN - Fase de Clasificación";
$anio = isset($_GET['anio']) ? (int) $_GET['anio'] : date('Y');


// Consulta de estadísticas para el dashboard
$sql_stats = "SELECT 
    (SELECT COUNT(*) FROM cmn_responsables) as total_unidades,
    (SELECT COUNT(*) FROM cmn_anexos_fase2 WHERE estado_revision = 0) as en_proceso,
    (SELECT COUNT(*) FROM cmn_anexos_fase2 WHERE estado_revision = 1) as completados,
    (SELECT SUM(monto_total) FROM cmn_anexos_fase2) as monto_acumulado";
$res_stats = $conexion->query($sql_stats);
$row_stats = $res_stats ? $res_stats->fetch_assoc() : [];


$estadisticas = [
    'total_unidades' => $row_stats['total_unidades'] ?? 0,
    'en_proceso' => $row_stats['en_proceso'] ?? 0,
    'completados' => $row_stats['completados'] ?? 0,
    'valor_total' => 'S/ ' . number_format($row_stats['monto_acumulado'] ?? 0, 2)
];

// Consulta de registros para la tabla
$where = " WHERE 1=1 ";
if (!empty($_GET['region'])) {
    $region = $conexion->real_escape_string($_GET['region']);
    $where .= " AND region_policial = '$region' ";
}

$sql_tabla = "SELECT * FROM cmn_anexos_fase2 $where ORDER BY fecha_subida DESC";
$res_tabla = $conexion->query($sql_tabla);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= $titulo_pagina ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #008eb0;      
            --secondary-color: #005073;    
            --bg-body: #f4f7f6;
            --card-radius: 12px;
        }
        body { font-family: 'Outfit', sans-serif; background-color: var(--bg-body); color: #333; }
        .page-content { padding-top: 90px; padding-left: 20px; padding-right: 20px; padding-bottom: 40px; }
        
        /* Premium Header */
        .premium-header {
            background: #ffffff;
            border-radius: var(--card-radius);
            padding: 1.5rem 2rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 10px 30px rgba(0,0,0,0.04);
            position: relative;
            overflow: hidden;
        }
        .premium-header::before {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0; width: 6px;
            background: linear-gradient(to bottom, #f59e0b, #d97706);
        }

        /* Buttons Elite */
        .btn-modern {
            padding: 10px 22px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            text-decoration: none;
        }
        .btn-modern:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.15); color: inherit; }
        .btn-modern-primary { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; }
        .btn-modern-outline { background: white; color: #555; border: 1px solid #e0e0e0; }
        
        .stat-card { background: white; border-radius: var(--card-radius); padding: 1.5rem; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03); border: 1px solid rgba(0,0,0,0.05); display: flex; align-items: center; }
        .stat-icon { width: 55px; height: 55px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; margin-right: 1.2rem; background: rgba(245, 158, 11, 0.1); color: #d97706; }
        .glass-panel { background: white; padding: 1.5rem; border-radius: var(--card-radius); box-shadow: 0 5px 25px rgba(0,0,0,0.05); border: 1px solid #e0e0e0; }
        .table-custom th { background-color: #f8f9fa; color: #555; font-weight: 600; font-size: 0.8rem; border-bottom: 2px solid #dee2e6; padding: 12px 15px; }
        .table-custom td { vertical-align: middle; font-size: 0.85rem; color: #444; padding: 12px 15px; border-bottom: 1px solid #f0f0f0; }
        .btn-status-toggle { background: rgba(40, 167, 69, 0.1); color: #28a745; border: 1px solid rgba(40,167,69,0.2); border-radius: 100px; padding: 6px 18px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
    </style>
</head>
<body>
    <?php require('./layout/topbar.php'); ?>
    <?php require('./layout/sidebar.php'); ?>

    <div class="page-content">
        <div class="container-fluid">
            
            <!-- Header Estilo Premium SIG -->
            <div class="premium-header">
                <div>
                    <h1 class="fw-bold mb-1" style="color: #212529; font-size: 1.8rem;">
                        <i class="fa-solid fa-tags text-warning me-2"></i> Fase de Clasificación CMN <?= $anio ?>
                    </h1>
                    <p class="text-muted mb-0 small">Recepción de Anexos N° 02 (Clasificación de Necesidades).</p>

                </div>
                <div class="d-flex gap-2">
                    <button class="btn-modern btn-modern-outline" onclick="copyPublicLink()">
                        <i class="fa-solid fa-link"></i> Enlace Público
                    </button>
                    <a href="cmn_clasificacion_subir.php" target="_blank" class="btn-modern btn-modern-primary">
                        <i class="fa-solid fa-plus"></i> Nuevo Registro
                    </a>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="stat-card border-0 shadow-sm">
                        <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                        <div>
                            <p class="text-muted small fw-bold mb-0">TOTAL UNIDADES</p>
                            <h2 class="fw-bold mb-0 text-primary"><?= $estadisticas['total_unidades'] ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card border-0 shadow-sm">
                        <div class="stat-icon" style="color:#28a745; background:rgba(40,167,69,0.1);"><i class="fa-solid fa-check-to-slot"></i></div>
                        <div>
                                                  <p class="text-muted small fw-bold mb-0">ANEXOS RECIBIDOS</p>
                            <h2 class="fw-bold mb-0 text-success"><?= $res_tabla ? $res_tabla->num_rows : '0' ?></h2>

                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card border-0 shadow-sm justify-content-between">
                        <div>
                            <p class="text-muted small fw-bold mb-0">ESTADO FASE 2</p>
                            <div class="mt-1">
                                <span class="btn-status-toggle">ACTIVO (ABIERTO)</span>
                            </div>
                        </div>
                        <div class="text-muted opacity-25">
                            <i class="fa-solid fa-power-off fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="glass-panel">
                <div class="table-responsive">
                    <table class="table table-custom align-middle mb-0">
                        <thead>
                            <tr>
                                <th width="3%" class="text-center">#</th>
                                <th width="25%">SUB UNIDAD / ÁREA USUARIA</th>
                                <th width="12%" class="text-center">DNI RESP.</th>
                                <th width="15%">REGIÓN POLICIAL</th>
                                <th width="15%">DIVOPUS / FRENTE</th>
                                <th width="12%" class="text-center">REGISTRO</th>
                                <th width="8%" class="text-center">ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $n = 1;
                            if ($res_tabla && $res_tabla->num_rows > 0):
                                while ($row = $res_tabla->fetch_assoc()): 
                            ?>
                            <tr>
                                <td class="text-center text-muted"><?= $n++ ?></td>
                                <td><div class="fw-bold" style="color: #2c3e50;"><?= $row['sub_unidad'] ?></div></td>
                                <td class="text-center"><span class="badge bg-light text-dark border fw-normal"><?= $row['dni_responsable'] ?></span></td>
                                <td><small class="fw-bold text-uppercase"><?= $row['region_policial'] ?></small></td>
                                <td><small class="text-uppercase"><?= $row['divopus'] ?></small></td>
                                <td class="text-center small"><?= date('d/m/Y', strtotime($row['fecha_subida'])) ?></td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a href="<?= $row['archivo_pdf'] ?>" target="_blank" class="btn btn-light btn-sm border" title="Ver PDF">
                                            <i class="fa-solid fa-file-pdf text-danger"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr><td colspan="8" class="text-center py-4 text-muted">Aún no se han recibido registros en esta fase.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function copyPublicLink() {
            const link = "<?= 'https://' . $_SERVER['HTTP_HOST'] . (defined('BASE_URL') ? BASE_URL : '/') . 'vista/cmn_clasificacion_subir.php' ?>";

            navigator.clipboard.writeText(link).then(() => {
                Swal.fire({
                    icon: 'success',
                    title: '¡Enlace Copiado!',
                    text: 'El enlace para Fase 2 ya está en tu portapapeles.',
                    timer: 1500,
                    showConfirmButton: false
                });
            });
        }
    </script>
    <?php require('./layout/footer.php'); ?>
</body>
</html>