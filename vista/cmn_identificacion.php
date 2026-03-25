<?php
require_once __DIR__ . '/../modelo/conexion.php';

// Verificación de seguridad
if (empty($_SESSION['id']) || !userCan('cmn')) {
    header("Location: " . (defined('BASE_URL') ? BASE_URL : '/sistemag_cpanel/') . "vista/inicio.php");
    exit();
}

$titulo_pagina = "CMN - Fase de Identificación";
$anio = isset($_GET['anio']) ? (int) $_GET['anio'] : 2026;

// 1. Estadísticas Sincronizadas (Filtrando solo los que remitieron documento en 2026)
$sql_stats = "SELECT 
    (SELECT COUNT(*) FROM cmn_responsables WHERE archivo_pdf IS NOT NULL AND anio_proceso = $anio) as total_unidades,
    (SELECT COUNT(DISTINCT sub_unidad_especifica) FROM cmn_responsables WHERE archivo_pdf IS NOT NULL AND anio_proceso = $anio) as completados_sync,
    (SELECT COUNT(*) FROM cmn_anexos_fase1 WHERE estado_revision = 0) as en_proceso,
    (SELECT COUNT(*) FROM cmn_anexos_fase1 WHERE estado_revision = 1) as completados,
    (SELECT SUM(monto_total) FROM cmn_anexos_fase1) as monto_acumulado";
$res_stats = $conexion->query($sql_stats);
$row_stats = $res_stats->fetch_assoc();

$estadisticas = [
    'total_unidades' => $row_stats['total_unidades'] ?? 0,
    'en_proceso' => $row_stats['completados_sync'] ?? 0,
    'completados' => $row_stats['completados'] ?? 0,
    'valor_total' => 'S/ ' . number_format($row_stats['monto_acumulado'] ?? 0, 2)
];

// 1.1 Estado del formulario (Mantenimiento / Plazo)
$res_config = $conexion->query("SELECT valor FROM cmn_config WHERE clave = 'mantenimiento'");
$config_mantenimiento = $res_config->fetch_assoc();
$esta_cerrado = ($config_mantenimiento && $config_mantenimiento['valor'] === '1');

// 2. Definir Filtros Dinámicos
$where_filtros = " AND r.anio_proceso = $anio ";
if (!empty($_GET['region'])) {
    $where_filtros .= " AND r.region_policial = '" . $conexion->real_escape_string($_GET['region']) . "' ";
}
if (!empty($_GET['divopus'])) {
    $where_filtros .= " AND r.divpol_divopus = '" . $conexion->real_escape_string($_GET['divopus']) . "' ";
}
if (!empty($_GET['sub_unidad'])) {
    $where_filtros .= " AND r.sub_unidad_especifica LIKE '%" . $conexion->real_escape_string($_GET['sub_unidad']) . "%' ";
}

// 3. Consulta de registros (SOLO LOS QUE REMITIERON DOCUMENTO)
$sql_tabla = "SELECT 
                r.dni, r.grado, CONCAT(r.apellidos, ' ', r.nombres) as nombres_completos, r.cip, 
                r.sub_unidad_especifica as sub_unidad, r.region_policial, r.divpol_divopus as div_frente, r.celular,
                a.id as anexo_id, a.fecha_subida, a.archivo_pdf as anexo_firmado, r.archivo_pdf as anexo_original, a.estado_revision
              FROM cmn_responsables r 
              LEFT JOIN cmn_anexos_fase1 a ON r.dni = a.dni_responsable 
              WHERE r.archivo_pdf IS NOT NULL $where_filtros 
              ORDER BY r.sub_unidad_especifica ASC";

$res_tabla = $conexion->query($sql_tabla);

if (!$res_tabla) {
    error_log("Error SQL FATAL en Identificación: " . $conexion->error);
}

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
            --primary-color: #008eb0;      /* Mejor ajustado a la imagen (teal) */
            --secondary-color: #005073;    /* Ajustado a la imagen */
            --bg-body: #f4f7f6;
            --card-radius: 12px;
            --mef-red: #da291c;
        }

        body { 
            font-family: 'Outfit', sans-serif; 
            background-color: var(--bg-body); 
            color: #333;
        }

        .page-content { 
            padding-top: 85px; 
            padding-left: 10px;    /* Más espacio para registros */
            padding-right: 10px;   /* Más espacio para registros */
            padding-bottom: 30px;
            transition: all 0.3s ease; 
        }


        /* Encabezado del Módulo (idéntico al dashboard original) */
        .module-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border-radius: var(--card-radius);
            padding: 2rem;
            color: white;
            box-shadow: 0 10px 30px rgba(0, 80, 115, 0.15);
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .module-header::after {
            content: '';
            position: absolute;
            right: -5%;
            top: -20%;
            width: 300px;
            height: 300px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
            pointer-events: none;
        }

        .btn-header-add {

            background: linear-gradient(135deg, #0d2a4a 0%, #008eb0 100%) !important;
            color: white !important;
            border: none !important;
            border-radius: 6px !important;
            padding: 8px 18px !important;
            font-weight: 600 !important;
            font-size: 0.8rem !important;
            box-shadow: 0 4px 10px rgba(0, 142, 176, 0.25) !important;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            height: 36px;
        }
        .btn-header-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 142, 176, 0.4) !important;
            filter: brightness(1.1);
        }

        .btn-copy-link {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
            color: white !important;
            border: none !important;
            border-radius: 6px !important;
            padding: 8px 18px !important;
            font-weight: 600 !important;
            font-size: 0.8rem !important;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 123, 255, 0.25) !important;
            display: inline-flex;
            align-items: center;
            height: 36px;
        }
        .btn-copy-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 123, 255, 0.35) !important;
            filter: brightness(1.1);
        }

        .btn-export-pro {
            min-width: 100px;
            height: 36px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 15px;
            gap: 8px;
            font-weight: 600;
            font-size: 0.75rem;
            transition: all 0.3s;
            border: none;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            color: white !important;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .btn-export-pro:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 6px 15px rgba(0,0,0,0.15); 
            filter: brightness(1.1); 
        }
        .bg-excel { background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%) !important; color: white !important; }
        .bg-pdf { background: linear-gradient(135deg, #dc3545 0%, #bd2130 100%) !important; color: white !important; }

        /* Iconos de Validación Estilo Responsables */
        .btn-status-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px; /* Redondeado suave estilo Responsables */
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            border: none;
            transition: all 0.2s;
        }
        .bg-status-check { background: #e8f5e9 !important; color: #2e7d32 !important; }
        .bg-status-seal { background: #e3f2fd !important; color: #1565c0 !important; }

        /* Botón de Validación en Modal */
        .btn-validate-modal {
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
            color: white !important;
            border: none;
            border-radius: 8px;
            padding: 8px 30px;
            font-weight: 700;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
            transition: all 0.3s;
        }
        .btn-validate-modal:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
            filter: brightness(1.1);
        }

        /* Botones de Acciones Estilo Responsables */
        .btn-action-round {
            width: 32px;
            height: 32px;
            border-radius: 8px; /* Cuadrado con bordes suaves */
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            transition: all 0.2s;
            border: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .btn-action-edit { background: #f3e5f5 !important; color: #7b1fa2 !important; }
        .btn-action-wa { background: #fff3e0 !important; color: #ef6c00 !important; }
        .btn-action-del { background: #ffebee !important; color: #c62828 !important; }
        .btn-action-round:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); filter: brightness(0.98); }

        /* Botones de Acción Compactos - COLORES VIVOS */
        .btn-action {
            width: 30px; height: 30px;
            border-radius: 7px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 0.82rem;
            border: none; cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            font-weight: 600;
        }
        .btn-action:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 14px rgba(0,0,0,0.18);
            filter: brightness(1.08);
        }
        /* Ojo — Previsualizar PDF → Azul sólido */
        .btn-pdf     { background: #1d4ed8 !important; color: #fff !important; box-shadow: 0 2px 6px rgba(29,78,216,0.35); }
        /* Check doble — Validado → Verde sólido */
        .btn-validado{ background: #16a34a !important; color: #fff !important; box-shadow: 0 2px 6px rgba(22,163,74,0.35);  }
        /* Factura — Cargo PDF → Rojo sólido */
        .btn-cargo   { background: #b91c1c !important; color: #fff !important; box-shadow: 0 2px 6px rgba(185,28,28,0.30); }
        /* WhatsApp — Observar → Verde WhatsApp */
        .btn-obs     { background: #25D366 !important; color: #fff !important; box-shadow: 0 2px 6px rgba(37,211,102,0.35); }
        /* Eliminar → Rojo oscuro */
        .btn-delete  { background: #dc2626 !important; color: #fff !important; box-shadow: 0 2px 6px rgba(220,38,38,0.30); }

        /* Badges de Estado - VIVOS Y DIFERENCIADOS */
        .badge-estado {
            display: inline-block; padding: 3px 10px; border-radius: 20px;
            font-size: 0.6rem; font-weight: 700; letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .badge-validado    { background: #16a34a; color: #fff; box-shadow: 0 2px 6px rgba(22,163,74,0.3); }
        .badge-recepcionado{ background: #0284c7; color: #fff; box-shadow: 0 2px 6px rgba(2,132,199,0.3); }
        .badge-observado   { background: #dc2626; color: #fff; box-shadow: 0 2px 6px rgba(220,38,38,0.3); }
        .badge-pendiente   { background: #94a3b8; color: #fff; }

        /* Tabla compacta con hover suave */
        .table-custom thead th {
            background: #1a2e4a;
            color: white;
            font-size: 0.72rem;
            letter-spacing: 0.5px;
            padding: 10px 8px;
            font-weight: 600;
        }
        .table-custom tbody tr:hover { background-color: rgba(0,142,176,0.04); }
        .table-custom td { padding: 8px 8px; vertical-align: middle; }

        /* Filas con estado especial — Bootstrap 5 pinta los <td>, no el <tr>, así que apuntamos a ambos */
        tr.row-validado     { border-left: 4px solid #16a34a !important; }
        tr.row-validado > td { background-color: rgba(22, 163, 74, 0.15) !important; }

        tr.row-recepcionado     { border-left: 4px solid #0284c7 !important; }
        tr.row-recepcionado > td { background-color: rgba(2, 132, 199, 0.09) !important; }

        tr.row-observado     { border-left: 4px solid #dc2626 !important; }
        tr.row-observado > td { background-color: rgba(220, 38, 38, 0.09) !important; }

        /* Símbolo de validación en columna ACCIONES */
        .validado-check {
            width: 32px; height: 32px;
            background: #16a34a;
            color: #fff;
            border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 1rem;
            box-shadow: 0 2px 8px rgba(22,163,74,0.4);
        }




        .btn-status-toggle {
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 20px;
            padding: 5px 15px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        /* Tarjetas de Estadísticas */
        .stat-card {
            background: white;
            border-radius: var(--card-radius);
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08); }

        .stat-icon {
            width: 55px; height: 55px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem; margin-right: 1.2rem;
        }

        .bg-icon-primary { background: rgba(0, 142, 176, 0.1); color: var(--primary-color); }
        .bg-icon-success { background: rgba(40, 167, 69, 0.1); color: #28a745; }
        .bg-icon-warning { background: rgba(255, 193, 7, 0.1); color: #ffc107; }

        /* Diagrama de Flujo / Proceso */
        .process-flow {
            background: white;
            border-radius: var(--card-radius);
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.04);
            margin-bottom: 2rem;
        }
        
        .flow-container { display: flex; justify-content: space-between; align-items: stretch; gap: 20px; }
        .flow-box { flex: 1; padding: 1.5rem; border-radius: 12px; position: relative; border: 2px dashed transparent; }

        .flow-box.insumos { background: #f8f9fa; border-color: #dee2e6; border-style: solid; border-width: 1px; }
        .flow-box.actividades { background: #e3f2fd; border-color: #90caf9; border-style: dashed; }
        .flow-box.producto { background: #e8f5e9; border-color: #a5d6a7; border-style: dashed; }

        .flow-title { font-weight: 700; margin-bottom: 1rem; display: flex; align-items: center; gap: 10px; font-size: 1.1rem; text-transform: uppercase; letter-spacing: 0.5px; }

        .insumos .flow-title { color: #6c757d; }
        .actividades .flow-title { color: #1976d2; }
        .producto .flow-title { color: #2e7d32; }

        .flow-list { list-style: none; padding: 0; margin: 0; }
        .flow-list li { font-size: 0.85rem; margin-bottom: 8px; padding-left: 20px; position: relative; color: #555; line-height: 1.4; }
        .flow-list li::before { content: '\f105'; font-family: 'Font Awesome 6 Free'; font-weight: 900; position: absolute; left: 0; color: currentColor; opacity: 0.5; }

        .product-card { background: white; padding: 1.5rem; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); text-align: center; border-left: 4px solid #4caf50; }

        /* Dashboard UI Elements Blueprints */
        .glass-panel { background: white; padding: 0.8rem; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); border: 1px solid #eef0f2; }
        .filter-card { background: white; padding: 10px 15px; border-radius: 8px; border: 1px solid #eef0f2; box-shadow: 0 2px 10px rgba(0,0,0,0.02); margin-bottom: 1rem; }



        .table-custom { border-collapse: collapse !important; width: 100%; border: 1px solid #c0c0c0 !important; }
        .table-custom thead th { 
            background-color: #0d2a4a !important; 
            color: #ffffff !important; 
            font-weight: 600 !important; 
            font-size: 0.72rem !important; 
            text-transform: uppercase !important;
            padding: 14px 8px !important; 
            border: 1px solid rgba(255,255,255,0.2) !important;
            text-align: center !important;
            vertical-align: middle !important;
        }
        .table-custom td { 
            vertical-align: middle !important; 
            font-size: 0.78rem;   /* Texto levemente más pequeño para mayor densidad de datos */
            color: #333; 
            padding: 8px 6px; 
            border: 1px solid #dee2e6 !important; 
            font-weight: 400 !important;
        }
        .table-custom tbody tr:hover { background-color: #f1f4f8; }

        .filter-label { font-size: 0.65rem; font-weight: 700; color: #8898aa; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 3px; display: block; }
        .filter-card { background: white; padding: 12px 15px; border-radius: 10px; border: 1px solid #eef0f2; box-shadow: 0 2px 12px rgba(0,0,0,0.03); }

        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 0.7rem; }

        .status-badge.bg-success-light { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
        .status-badge.bg-warning-light { background: #fff8e1; color: #ff8f00; border: 1px solid #ffe082; }

        .btn-action { border: none; background: rgba(0, 0, 0, 0.05); color: #555; border-radius: 6px; width: 32px; height: 32px; display: inline-flex; justify-content: center; align-items: center; transition: all 0.2s; }
        .btn-action:hover { background: #e0e0e0; color: #000; }

        .fw-bold { font-weight: 500 !important; } /* Suavizar negritas */

        /* Animaciones Premium para el Botón de Estado */
        @keyframes pulse-premium {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.4); }
            70% { transform: scale(1.02); box-shadow: 0 0 0 10px rgba(40, 167, 69, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
        }
        .btn-status-open {
            animation: pulse-premium 2s infinite ease-in-out;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
            border: none !important;
            color: white !important;
        }
        .btn-status-closed {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
            border: none !important;
            color: white !important;
        }

    </style>
</head>

<body>
    <?php require('./layout/topbar.php'); ?>
    <?php require('./layout/sidebar.php'); ?>

    <div class="page-content">
        <div class="container-fluid">
            
            <!-- Encabezado y Estadísticas Unificadas Nivel Pro -->
            <div class="row align-items-center mb-3">
                <div class="col-md-7">
                    <h2 class="fw-bold mb-0" style="color: #0d2a4a; font-size: 1.8rem;">
                        <i class="fa-solid fa-file-shield text-primary me-2"></i> Fase de Identificación N° 01
                        <span class="badge rounded-pill bg-danger animate__animated animate__pulse animate__infinite ms-2" style="font-size: 0.6rem; vertical-align: middle; background: #dc3545 !important;">
                            <i class="fa-solid fa-satellite-dish me-1"></i> EN VIVO
                        </span>
                    </h2>
                    <p class="text-muted small mb-0">Monitoreo dinámico de archivos PDF remitidos por las Subunidades Policiales.</p>
                </div>
                <div class="col-md-5 text-md-end d-flex gap-2 justify-content-md-end mt-2 mt-md-0">
                    <button class="btn btn-copy-link btn-sm px-3" onclick="copyPublicLink()">
                        <i class="fa-solid fa-link me-2"></i> Enlace Público
                    </button>
                    <a href="cmn_identificacion_subir.php" target="_blank" class="btn btn-header-add btn-sm px-3">
                        <i class="fa-solid fa-plus me-2"></i> Nuevo Registro
                    </a>
                    <div class="d-flex gap-2 border-start ps-3 ms-2 align-items-center">
                        <button class="btn btn-export-pro bg-excel" title="Exportar a Excel">
                            <i class="fa-solid fa-file-excel"></i> EXCEL
                        </button>
                        <button class="btn btn-export-pro bg-pdf" title="Exportar a PDF">
                            <i class="fa-solid fa-file-pdf"></i> REPORTE PDF
                        </button>
                    </div>


                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="glass-panel text-center py-2 border-0 shadow-sm" style="background: white; border-radius: 10px;">
                        <p class="text-muted small fw-bold mb-0 text-uppercase" style="font-size: 0.65rem;">Total Usuarios Identificados</p>
                        <h2 class="fw-bold mb-0 text-primary" style="font-size: 2rem;"><?= $estadisticas['total_unidades'] ?></h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-panel text-center py-2 border-0 shadow-sm" style="background: white; border-radius: 10px;">
                        <p class="text-muted small fw-bold mb-0 text-uppercase" style="font-size: 0.65rem;">Subunidades con Anexo N° 01</p>
                        <h2 class="fw-bold mb-0 text-success" style="font-size: 2rem;"><?= $estadisticas['completados'] ?></h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-panel text-center py-1 border-0 shadow-sm" style="background: white; border-radius: 10px;">
                        <p class="text-muted small fw-bold mb-1 text-uppercase" style="font-size: 0.65rem;">Estado de la Fase</p>
                        <div class="d-grid px-2">
                            <button id="btnToggleFase" 
                                class="btn fw-bold py-1 rounded-pill shadow-sm <?= $esta_cerrado ? 'btn-status-closed' : 'btn-status-open' ?>" 
                                style="font-size: 0.75rem;"
                                onclick="toggleFormStatus(<?= $esta_cerrado ? 0 : 1 ?>)">
                                <?php if($esta_cerrado): ?>
                                    <i class="fa-solid fa-circle-xmark me-2"></i> CERRADO (PLAZO VENCIDO)
                                <?php else: ?>
                                    <i class="fa-solid fa-circle-check me-2 shadow-sm animate__animated animate__heartBeat animate__infinite"></i> ABIERTO (EN PROCESO)
                                <?php endif; ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>






            <!-- Dashboard UI Cleaned (Flowchart Removed) -->
            
            <div class="filter-card border-0 shadow-sm mb-4">
                <form method="GET" class="row g-2 align-items-end" id="formFiltro">
                    <div class="col-md-2">
                        <label class="filter-label">Periodo</label>
                        <select name="anio" class="form-select form-select-sm rounded-2 border-light shadow-none bg-light" onchange="this.form.submit()">
                            <option value="2027" <?= $anio == 2027 ? 'selected' : '' ?>>2027</option>
                            <option value="2026" <?= $anio == 2026 ? 'selected' : '' ?>>2026</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="filter-label">Región Policial</label>
                        <select name="region" class="form-select form-select-sm rounded-2 shadow-none" id="filterRegion" onchange="this.form.submit()">
                            <option value="">-- Seleccione Región --</option>
                            <?php 
                            $q_reg = "SELECT DISTINCT region_policial FROM cmn_responsables ORDER BY region_policial";
                            $res_reg = $conexion->query($q_reg);
                            while($r_reg = $res_reg->fetch_assoc()): ?>
                                <option value="<?= $r_reg['region_policial'] ?>" <?= ($_GET['region'] ?? '') == $r_reg['region_policial'] ? 'selected' : '' ?>><?= $r_reg['region_policial'] ?></option>
                            <?php endwhile; ?>

                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="filter-label">División / Frente</label>
                        <select name="divopus" class="form-select form-select-sm rounded-2 shadow-none" id="filterDivopus" onchange="this.form.submit()" <?= empty($_GET['region']) ? 'disabled' : '' ?>>
                            <option value="">-- Todas las Divisiones --</option>
                            <?php if(!empty($_GET['region'])):
                                $q_div = "SELECT DISTINCT divpol_divopus as divopus FROM cmn_responsables WHERE region_policial = '".$conexion->real_escape_string($_GET['region'])."' ORDER BY divopus";
                                $res_div = $conexion->query($q_div);
                                while($r_div = $res_div->fetch_assoc()): ?>
                                    <option value="<?= $r_div['divopus'] ?>" <?= ($_GET['divopus'] ?? '') == $r_div['divopus'] ? 'selected' : '' ?>><?= $r_div['divopus'] ?></option>
                                <?php endwhile; endif; ?>

                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="filter-label">Buscador por Sub Unidad</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0 rounded-start-2 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="text" id="searchSubUnidad" class="form-control border-start-0 rounded-end-0 shadow-none" placeholder="Ingrese nombre de unidad..." value="<?= $_GET['sub_unidad'] ?? '' ?>">
                            <button class="btn btn-primary rounded-end-2 px-3 fw-bold" type="submit">FILTRAR</button>
                        </div>
                    </div>
                    <div class="col-md-1 text-end">
                        <a href="cmn_identificacion.php" class="btn btn-light btn-sm w-100 rounded-2 border" title="Resetear Filtros"><i class="fa-solid fa-redo-alt"></i></a>
                    </div>
                </form>
            </div>




            <!-- Tabla de Registros - Estilo Responsables -->
            <div class="glass-panel">
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                  <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-table-list me-2 text-primary"></i> Reporte de Recepción del Anexo N° 01 Firmado</h6>
                  <span class="text-muted small"><?= $res_tabla ? $res_tabla->num_rows : 0 ?> registros</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="text-center" style="width:3%">#</th>
                                <th style="width:7%; white-space:nowrap">GRADO</th>
                                <th style="width:18%">APELLIDOS Y NOMBRES</th>
                                <th class="text-center" style="width:7%; white-space:nowrap">DNI</th>
                                <th class="text-center" style="width:6%; white-space:nowrap">CIP</th>
                                <th style="width:9%; white-space:nowrap">REGIÓN POLICIAL</th>
                                <th style="width:10%; white-space:nowrap">DIVOPUS / FRENTE</th>
                                <th style="width:14%">SUB UNIDAD / ÁREA</th>
                                <th class="text-center" style="width:7%; white-space:nowrap">CELULAR</th>
                                <th class="text-center" style="width:9%">REGISTRO</th>
                                <th class="text-center" style="width:6%">PDF</th>
                                <th class="text-center" style="width:8%">ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody id="tableBodyIdentificacion">
                            <?php 
                            $n = 1;
                            if ($res_tabla && $res_tabla->num_rows > 0):
                                while ($row = $res_tabla->fetch_assoc()):
                                    $tiene_anexo   = !empty($row['anexo_id']);
                                    $estado_rev    = (int)($row['estado_revision'] ?? -1);
                                    // Colores de fila: VALIDADO=verde, RECEPCIONADO=azul, OBSERVADO=rojo, PENDIENTE=sin color
                                    if ($estado_rev === 1)      $clase_row = 'row-validado';
                                    elseif ($estado_rev === 2)  $clase_row = 'row-observado';
                                    elseif ($tiene_anexo)       $clase_row = 'row-recepcionado';
                                    else                        $clase_row = '';
                            ?>
                            <tr class="<?= $clase_row ?>">
                                <td class="text-center text-muted" style="font-size:0.75rem"><?= $n++ ?></td>
                                <td style="font-size:0.78rem; white-space:nowrap; color:#1e293b; font-weight:500"><?= htmlspecialchars($row['grado']) ?></td>
                                <td style="font-size:0.8rem; text-transform:uppercase; color:#0f172a; font-weight:600"><?= htmlspecialchars($row['nombres_completos']) ?></td>
                                <td class="text-center" style="font-size:0.8rem; white-space:nowrap; color:#1e293b"><?= $row['dni'] ?></td>
                                <td class="text-center" style="font-size:0.8rem; white-space:nowrap; color:#1e293b"><?= $row['cip'] ?></td>
                                <td style="font-size:0.72rem; white-space:nowrap; color:#334155"><?= htmlspecialchars($row['region_policial']) ?></td>
                                <td style="font-size:0.72rem; white-space:nowrap; color:#334155"><?= htmlspecialchars($row['div_frente']) ?></td>
                                <td style="font-size:0.78rem; color:#1e293b"><?= htmlspecialchars($row['sub_unidad']) ?></td>
                                <td class="text-center" style="font-size:0.8rem; white-space:nowrap; color:#1e293b"><?= $row['celular'] ?? '---' ?></td>

                                <!-- COLUMNA REGISTRO (fecha + badge) -->
                                <td class="text-center" style="min-width:90px">
                                    <?php if ($tiene_anexo): ?>
                                        <div class="small fw-bold mb-1" style="font-size:0.72rem"><?= date('d/m/Y', strtotime($row['fecha_subida'])) ?></div>
                                        <?php if ($estado_rev === 1): ?>
                                            <span class="badge-estado badge-validado">&#10003; VALIDADO</span>
                                        <?php elseif ($estado_rev === 2): ?>
                                            <span class="badge-estado badge-observado">&#x26A0; OBSERVADO</span>
                                        <?php else: ?>
                                            <span class="badge-estado badge-recepcionado">&#8595; RECEPCIONADO</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge-estado badge-pendiente">PENDIENTE</span>
                                    <?php endif; ?>
                                </td>

                                <!-- COLUMNA PDF (ver / validar) -->
                                <td class="text-center">
                                    <?php if ($tiene_anexo): ?>
                                        <?php if ($estado_rev === 1): ?>
                                            <!-- VALIDADO: solo icono check grande + cargo -->
                                            <div class="d-flex justify-content-center gap-1 align-items-center">
                                                <button type="button" class="btn-action btn-validado"
                                                    title="Ver Documento Validado"
                                                    onclick="verPdfModal('../<?= $row['anexo_firmado'] ?>', '<?= addslashes($row['nombres_completos'] . ' - ' . $row['sub_unidad']) ?>', <?= $row['anexo_id'] ?>, 1)">
                                                    <i class="fa-solid fa-check-double"></i>
                                                </button>
                                                <a href="../controlador/cmn_cargo_fase1.php?id=<?= $row['anexo_id'] ?>"
                                                   target="_blank" class="btn-action btn-cargo" title="Cargo PDF">
                                                    <i class="fa-solid fa-file-invoice"></i>
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <!-- RECEPCIONADO: ojo + cargo -->
                                            <div class="d-flex justify-content-center gap-1">
                                                <button type="button" class="btn-action btn-pdf"
                                                    title="Previsualizar y Validar"
                                                    onclick="verPdfModal('../<?= $row['anexo_firmado'] ?>', '<?= addslashes($row['nombres_completos'] . ' - ' . $row['sub_unidad']) ?>', <?= $row['anexo_id'] ?>)">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                                <a href="../controlador/cmn_cargo_fase1.php?id=<?= $row['anexo_id'] ?>"
                                                   target="_blank" class="btn-action btn-cargo" title="Cargo PDF">
                                                    <i class="fa-solid fa-file-invoice"></i>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">---</span>
                                    <?php endif; ?>
                                </td>

                                <!-- COLUMNA ACCIONES -->
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <?php if ($estado_rev === 1): ?>
                                            <!-- VALIDADO: solo WA por si necesita comunicarse -->
                                            <button class="btn-action btn-obs"
                                                title="Comunicar por WhatsApp"
                                                onclick="observarWhatsApp('<?= $row['celular'] ?>', '<?= addslashes($row['nombres_completos']) ?>', '<?= $row['anexo_id'] ?>', '<?= addslashes($row['grado']) ?>')">
                                                <i class="fa-brands fa-whatsapp"></i>
                                            </button>
                                        <?php elseif ($tiene_anexo): ?>
                                            <!-- RECEPCIONADO: WA -->
                                            <button class="btn-action btn-obs"
                                                title="Observar por WhatsApp"
                                                onclick="observarWhatsApp('<?= $row['celular'] ?>', '<?= addslashes($row['nombres_completos']) ?>', '<?= $row['anexo_id'] ?>', '<?= addslashes($row['grado']) ?>')">
                                                <i class="fa-brands fa-whatsapp"></i>
                                            </button>
                                        <?php else: ?>
                                            <!-- PENDIENTE: solo recordatorio WA -->
                                            <button class="btn-action btn-obs"
                                                title="Recordatorio por WhatsApp"
                                                onclick="observarWhatsApp('<?= $row['celular'] ?>', '<?= addslashes($row['nombres_completos']) ?>', null, '<?= addslashes($row['grado']) ?>')">
                                                <i class="fa-brands fa-whatsapp"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr>
                                <td colspan="12" class="text-center py-4 text-muted">No se encontraron registros.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!-- PAGINACIÓN MODERNA -->
                <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pt-2 border-top gap-2" id="paginationBar">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small">Mostrar</span>
                        <select id="rowsPerPageSelect" class="form-select form-select-sm" style="width:auto;border-radius:8px;border:1.5px solid #dee2e6;font-size:0.85rem;">
                            <option value="15" selected>15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span class="text-muted small">por página</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span id="paginationInfo" class="text-muted small fw-semibold"></span>
                        <div class="d-flex gap-1" id="paginationControls"></div>
                    </div>
                </div>
            </div>

            <!-- Modal para Previsualizar y Validar PDF -->
            <div class="modal fade" id="modalPdfView" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content" style="border-radius: 15px; overflow: hidden; height: 90vh;">
                        <input type="hidden" id="current_anexo_id">
                        <div class="modal-header bg-dark text-white px-4 py-2 d-flex justify-content-between align-items-center">
                            <h6 class="modal-title fw-bold" id="pdfViewTitle" style="font-size: 0.9rem;">Previsualización de Documento</h6>
                            <div class="d-flex gap-2 align-items-center">
                                <button type="button" class="btn btn-validate-modal" id="btnValidarFirma" onclick="validarFirmaAnexo()">
                                    <i class="fa-solid fa-signature me-2"></i> VALIDAR FIRMA Y SELLAR DOCUMENTO
                                </button>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                        </div>
                        <div class="modal-body p-0 bg-secondary">
                            <iframe id="pdfIframe" src="" width="100%" height="100%" frameborder="0"></iframe>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>




    <script>
        function copyPublicLink() {
            const link = "<?= FULL_BASE_URL . 'vista/cmn_identificacion_subir.php' ?>";
            navigator.clipboard.writeText(link).then(() => {
                Swal.fire({
                    icon: 'success',
                    title: '¡Súper Enlace Copiado!',
                    text: 'El enlace ya está en el portapapeles. ¡Listos para difundir!',
                    timer: 1500,
                    showConfirmButton: false,
                    background: '#f8f9fa'
                });
            });
        }

        // CONTROL DE FILTROS DINAMICOS NIVEL PRO
        document.getElementById('filterRegion').addEventListener('change', function() {
            this.form.submit();
        });

        // BUSQUEDA SENSIBLE (LIVE SEARCH)
        document.getElementById('searchSubUnidad').addEventListener('keyup', function() {
            const searchText = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('#tableBodyIdentificacion tr');
            
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                if (text.includes(searchText)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        function verPdfModal(url, titulo, id, yaValidado = 0) {
            const modal = new bootstrap.Modal(document.getElementById('modalPdfView'));
            document.getElementById('pdfViewTitle').innerText = titulo;
            document.getElementById('pdfIframe').src = url;
            
            const btnValidar = document.getElementById('btnValidarFirma');
            btnValidar.onclick = () => validarFirmaAnexo(id);
            
            // Si ya está validado, ocultamos el botón de validar para que solo sea previsualización
            if (yaValidado === 1) {
                btnValidar.style.display = 'none';
            } else {
                btnValidar.style.display = 'block';
            }
            
            modal.show();
        }

        function validarFirmaAnexo(id) {
            // The hidden input 'current_anexo_id' is no longer strictly needed if 'id' is passed directly.
            // However, to maintain consistency with the original structure or if other parts of the code rely on it,
            // we can still set it, or remove the line below if it's truly redundant.
            // For now, we'll use the 'id' passed as an argument.
            
            Swal.fire({
                title: '¿Confirmar Validación?',
                text: "Se marcará el documento como FIRMADO Y SELLADO.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                confirmButtonText: 'SÍ, VALIDAR AHORA',
                cancelButtonText: 'CANCELAR'
            }).then((result) => {
                if (result.isConfirmed) {
                    const fd = new FormData();
                    fd.append('id', id);
                    
                    fetch('../controlador/cmn_anexo_validar.php', {
                        method: 'POST',
                        body: fd
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('¡Éxito!', 'Documento validado correctamente.', 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    });
                }
            });
        }

function observarWhatsApp(celular, nombre, id, grado) {
    if (!celular || celular === '---') {
        Swal.fire({ icon: 'error', title: 'Celular no encontrado', text: 'El responsable no tiene un número de celular registrado.' });
        return;
    }

    const saludo = grado ? grado.toUpperCase() + ' ' + nombre.toUpperCase() : nombre.toUpperCase();
    const m = "*-- OBSERVACION CMN --*\n\nEstimado/a *" + saludo + "*,\n\nSe le informa que hemos recibido su documento del *ANEXO N\u00BA 01 - Fase de Identificaci\u00F3n*; sin embargo, se ha observado que el archivo presenta inconsistencias como *firmas y/o sellos faltantes del responsable de la Unidad Usuaria*.\n\nPor favor, s\u00EDrvase revisar detalladamente su archivo y volver a cargarlo con todos los requisitos completos. Quedamos atentos a la nueva versi\u00F3n.";

    Swal.fire({
        title: 'Observaci\u00F3n por WhatsApp',
        html: `<div class="text-start bg-light p-3 rounded border shadow-sm" style="font-size: 0.8rem; color: #555; white-space: pre-wrap;">${m.replace(/\*/g, '')}</div>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#25D366',
        confirmButtonText: 'S\u00CD, ENVIAR AHORA',
        cancelButtonText: 'CANCELAR'
    }).then((result) => {
        if (result.isConfirmed) {
            // Si hay un ID de anexo, lo marcamos como OBSERVADO en la BD primero
            if (id) {
                const fd = new FormData();
                fd.append('id', id);
                fetch('../controlador/cmn_anexo_observar.php', {
                    method: 'POST',
                    body: fd
                }).then(r => r.json())
                .then(data => {
                    const num = celular.replace(/[^0-9]/g, '');
                    const phone = (num.length === 9) ? '51' + num : num;
                    const url = "https://web.whatsapp.com/send?phone=" + phone + "&text=" + encodeURIComponent(m);
                    window.open(url, 'ventanaWA');
                    // Recargamos sutilmente después de un momento para ver el cambio de color en la tabla
                    setTimeout(() => { location.reload(); }, 2000);
                });
            } else {
                // Es un recordatorio (no tiene anexo aún)
                const num = celular.replace(/[^0-9]/g, '');
                const phone = (num.length === 9) ? '51' + num : num;
                const url = "https://web.whatsapp.com/send?phone=" + phone + "&text=" + encodeURIComponent(m);
                window.open(url, 'ventanaWA');
            }
        }
    });
}



        // AUTO-REFRESCO INTELIGENTE (Cada 60 segundos)
        let refreshInterval = setInterval(() => {
            const isModalOpen = document.getElementById('modalPdfView').classList.contains('show');
            const isTyping = document.activeElement === document.getElementById('searchSubUnidad');
            
            if (!isModalOpen && !isTyping) {
                location.reload();
            }
        }, 60000); 

        function eliminarRegistro(id) {
            // ... ya no se usa, pero lo dejamos por si acaso o lo quitamos definitivamente
        }

        function toggleFormStatus(newState) {
            const statusText = newState === 1 ? 'CERRAR la recepción de documentos' : 'ABRIR la recepción de documentos';
            const statusIcon = newState === 1 ? 'warning' : 'success';

            Swal.fire({
                title: '¿Confirmar cambio de estado?',
                text: `Vas a ${statusText}.`,
                icon: statusIcon,
                showCancelButton: true,
                confirmButtonColor: newState === 1 ? '#d33' : '#3085d6',
                cancelButtonColor: '#aaa',
                confirmButtonText: 'SÍ, CONFIRMAR'
            }).then((result) => {
                if (result.isConfirmed) {
                    const fd = new FormData();
                    fd.append('estado', newState);
                    fetch('../controlador/cmn_toggle_form.php', {
                        method: 'POST',
                        body: fd
                    }).then(r => r.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Estado actualizado',
                                text: 'Se ha cambiado el estado de la fase correctamente.',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => location.reload());
                        } else {
                            Swal.fire('Error', 'Hubo un problema al actualizar el estado.', 'error');
                        }
                    });
                }
            });
        }


        // ======================================================
        // MOTOR DE PAGINACIÓN MODERNO (Client-side)
        // ======================================================
        const tbody         = document.getElementById('tableBodyIdentificacion');
        const rowsSelect    = document.getElementById('rowsPerPageSelect');
        const paginInfo     = document.getElementById('paginationInfo');
        const paginControls = document.getElementById('paginationControls');

        let currentPage  = 1;
        let rowsPerPage  = 15;

        function getAllRows() {
            // Devuelve todas las filas que no están ocultas por el filtro de búsqueda
            return Array.from(tbody.querySelectorAll('tr')).filter(r => r.style.display !== 'none' || r.getAttribute('data-hidden') !== 'filter');
        }

        function applyPagination() {
            const allRows   = Array.from(tbody.querySelectorAll('tr'));
            const visible   = allRows.filter(r => r.getAttribute('data-hidden') !== 'filter');
            const total     = visible.length;
            const totalPages = Math.max(1, Math.ceil(total / rowsPerPage));
            if (currentPage > totalPages) currentPage = totalPages;

            const start = (currentPage - 1) * rowsPerPage;
            const end   = start + rowsPerPage;

            visible.forEach((row, i) => {
                row.style.display = (i >= start && i < end) ? '' : 'none';
            });
            // Aseguramos que las ocultas por filtro no aparezcan
            allRows.filter(r => r.getAttribute('data-hidden') === 'filter').forEach(r => r.style.display = 'none');

            // Info
            const from = total === 0 ? 0 : start + 1;
            const to   = Math.min(end, total);
            paginInfo.textContent = `Mostrando ${from}–${to} de ${total} registros`;

            // Controles de botones
            renderPaginControls(totalPages);
        }

        function renderPaginControls(totalPages) {
            paginControls.innerHTML = '';
            const btnStyle = (active) => `
                display:inline-flex;align-items:center;justify-content:center;
                width:32px;height:32px;border-radius:8px;border:1.5px solid;
                font-size:0.8rem;font-weight:600;cursor:pointer;transition:all .2s;
                background:${active ? 'var(--primary-color,#008eb0)' : 'white'};
                color:${active ? 'white' : '#555'};
                border-color:${active ? 'var(--primary-color,#008eb0)' : '#dee2e6'};`;

            const mkBtn = (label, page, active = false, disabled = false) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.style.cssText = btnStyle(active);
                btn.innerHTML = label;
                btn.disabled = disabled;
                if (disabled) btn.style.opacity = '0.4';
                btn.addEventListener('click', () => { currentPage = page; applyPagination(); });
                btn.addEventListener('mouseenter', () => { if (!active && !disabled) btn.style.background = '#f0f9fc'; });
                btn.addEventListener('mouseleave', () => { if (!active && !disabled) btn.style.background = 'white'; });
                return btn;
            };

            const mkDots = () => {
                const s = document.createElement('span');
                s.textContent = '…';
                s.style.cssText = 'display:inline-flex;align-items:center;justify-content:center;width:28px;height:32px;font-size:0.85rem;color:#999';
                return s;
            };

            // Primera / Anterior
            paginControls.appendChild(mkBtn('<i class="fa-solid fa-angles-left"></i>', 1, false, currentPage === 1));
            paginControls.appendChild(mkBtn('<i class="fa-solid fa-angle-left"></i>', currentPage - 1, false, currentPage === 1));

            // Páginas numéricas con puntos suspensivos
            const pageSet = new Set();
            pageSet.add(1);
            pageSet.add(totalPages);
            for (let p = Math.max(2, currentPage - 1); p <= Math.min(totalPages - 1, currentPage + 1); p++) pageSet.add(p);

            let pages = Array.from(pageSet).sort((a,b) => a-b);
            let prev = 0;
            pages.forEach(p => {
                if (p - prev > 1) paginControls.appendChild(mkDots());
                paginControls.appendChild(mkBtn(p, p, p === currentPage));
                prev = p;
            });

            // Siguiente / Última
            paginControls.appendChild(mkBtn('<i class="fa-solid fa-angle-right"></i>', currentPage + 1, false, currentPage === totalPages));
            paginControls.appendChild(mkBtn('<i class="fa-solid fa-angles-right"></i>', totalPages, false, currentPage === totalPages));
        }

        // Cambio de filas por página
        rowsSelect.addEventListener('change', function() {
            rowsPerPage = parseInt(this.value);
            currentPage = 1;
            applyPagination();
        });

        // Integrar con el filtro de búsqueda existente (sobrescribir el listener original)
        const searchInput = document.getElementById('searchSubUnidad');
        if (searchInput) {
            searchInput.removeEventListener('input', searchInput._paginListener);
            searchInput.addEventListener('input', function() {
                const q = this.value.toLowerCase().trim();
                Array.from(tbody.querySelectorAll('tr')).forEach(row => {
                    const match = row.innerText.toLowerCase().includes(q);
                    row.setAttribute('data-hidden', match ? '' : 'filter');
                });
                currentPage = 1;
                applyPagination();
            });
        }

        // Iniciar paginación al cargar
        document.addEventListener('DOMContentLoaded', applyPagination);
        // Fallback por si DOMContentLoaded ya pasó
        if (document.readyState !== 'loading') applyPagination();

    </script>
    <?php require('./layout/footer.php'); ?>
</body>
</html>
