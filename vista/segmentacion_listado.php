<?php
/* SEGMENTACION LISTADO.PHP */
declare(strict_types=1);

// 1. Cargar conexión y sesión (Centralizado)
require_once __DIR__ . '/../modelo/conexion.php';

// Seguridad de Sesión
if (empty($_SESSION['id'])) {
  header("Location: " . (defined('BASE_URL') ? BASE_URL : '/sistem_job/') . "vista/login/login.php");
  exit();
}

// Evitar Caché (Back Button Security)
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (isset($conn) && $conn instanceof mysqli) {
  // ok
} elseif (isset($conexion) && $conexion instanceof mysqli) {
  $conn = $conexion;
} elseif (isset($mysqli) && $mysqli instanceof mysqli) {
  $conn = $mysqli;
} else {
  die("Error: la conexión a BD no existe");
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset("utf8mb4");

// FORZAR RECARGA DE PERMISOS PARA QUE SE REFLEJEN AL INSTANTE
require_once __DIR__ . '/../controlador/autocargar_permisos.php';
if (!empty($_SESSION['id'])) {
  recargarPermisosUsuario($_SESSION['id'], $conn);
}

// Seguridad Estricta (Bloqueo VER)
if ($_SESSION['rol'] !== 'Administrador' && $_SESSION['rol'] !== 'Super Administrador' && !isset($_SESSION['permisos']['SEGMENTACION']['VER'])) {
  echo '<!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Acceso Denegado</title>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body style="background-color: #f4f6f9; font-family: sans-serif;">
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    title: "Acceso Denegado",
                    text: "No tienes permisos para visualizar este módulo.",
                    icon: "error",
                    confirmButtonColor: "#3085d6",
                    confirmButtonText: "Volver al Dashboard",
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = "../controlador/dashboard.php";
                    }
                });
            });
        </script>
    </body>
    </html>';
  exit();
}

// Variables de permisos
$pEditar = ($_SESSION['rol'] === 'Administrador' || $_SESSION['rol'] === 'Super Administrador' || isset($_SESSION['permisos']['SEGMENTACION']['EDITAR']));
$pEliminar = ($_SESSION['rol'] === 'Administrador' || $_SESSION['rol'] === 'Super Administrador' || isset($_SESSION['permisos']['SEGMENTACION']['ELIMINAR']));
$mostrarAcciones = ($pEditar || $pEliminar);


$anio = isset($_GET['anio']) ? (int) $_GET['anio'] : (int) date('Y');
$programado = isset($_GET['programado']) && $_GET['programado'] !== '' ? (int) $_GET['programado'] : null;
$texto = trim($_GET['q'] ?? '');
$objeto = trim($_GET['objeto'] ?? '');
$tipo_proceso = isset($_GET['tipo_proceso']) ? (int) $_GET['tipo_proceso'] : 0;
$resultado = trim($_GET['resultado'] ?? '');

$where = ["s.anio={$anio}", "s.origen_registro = 'Segmentación'"];
if ($programado !== null)
  $where[] = "s.programado={$programado}";
if ($texto !== '')
  $where[] = "CONCAT(s.descripcion,' ',s.ref_pac) LIKE '%" . $conn->real_escape_string($texto) . "%'";
// ojo: estos filtros afectan SOLO el “total cuantía (consulta)” y el “procedimientos”
if ($objeto !== '')
  $where[] = "s.objeto_contratacion='" . $conn->real_escape_string($objeto) . "'";
if ($tipo_proceso > 0)
  $where[] = "s.tipo_proceso_id={$tipo_proceso}";
if ($resultado !== '')
  $where[] = "s.resultado_segmentacion='" . $conn->real_escape_string($resultado) . "'";
$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

/* 1) Métricas de la tabla (respetan filtros) */
$agg = $conn->query("
  SELECT COUNT(*) cantidad, ROUND(COALESCE(SUM(cuantia),0),2) total_cuantia
  FROM segmentacion s {$where_sql}
")->fetch_assoc();


// === TOTAL PAC REAL ===
// Suma de TODAS las cuantías del año (sin filtros)
$totPac = $conn->query("
    SELECT ROUND(COALESCE(SUM(cuantia),0),2) total_pac
    FROM segmentacion 
    WHERE anio = {$anio} AND origen_registro = 'Segmentación'
")->fetch_assoc();

$total_pac = (float) $totPac['total_pac'];

// 10% para semáforo de cuantía
$pac_10 = $total_pac * 0.10;

// TOTAL PAC seguro para usar dentro del SQL (%PAC)
$total_pac_sql = $total_pac > 0
  ? number_format($total_pac, 6, '.', '')
  : '0';



/* ==== Paginación ==== */
$p = max(1, (int) ($_GET['p'] ?? 1));
$pp = (int) ($_GET['pp'] ?? 10);
$pp_opts = [10, 25, 50, 100];
if (!in_array($pp, $pp_opts, true))
  $pp = 10;
$offset = ($p - 1) * $pp;

/* ==== Combos ==== */
$tipos = $conn->query("SELECT id,nombre FROM tipo_proceso WHERE estado=1 ORDER BY nombre ASC");

/* ==== Conteo para paginación ==== */
$rowc = $conn->query("SELECT COUNT(*) total FROM segmentacion s {$where_sql}")->fetch_assoc();
$total_rows = (int) $rowc['total'];
$total_pages = max(1, (int) ceil($total_rows / $pp));
if ($p > $total_pages) {
  $p = $total_pages;
  $offset = ($p - 1) * $pp;
}

/* ==== Consulta principal ==== */
$sql = "
SELECT  
  s.id, 
  s.ref_pac,
  s.cmn, 
  s.objeto_contratacion, 
  s.descripcion,
  s.cuantia,

  ROUND(
      CASE 
          WHEN $total_pac_sql > 0 THEN (s.cuantia / $total_pac_sql) * 100
          ELSE 0
      END
  , 2) AS porcentaje,

  s.cuantia_categoria, 
  s.riesgo_categoria,
  s.resultado_segmentacion,
  s.programado, 
  s.fecha,

  COALESCE(tp.nombre, '') AS tipo_proceso,
  ROUND(COALESCE(SUM(i.monto_item),0),2) AS suma_items,
  COUNT(i.id) AS n_items
FROM segmentacion s
LEFT JOIN segmentacion_items i ON i.segmentacion_id=s.id
LEFT JOIN tipo_proceso tp ON tp.id=s.tipo_proceso_id
{$where_sql}
GROUP BY s.id
ORDER BY s.ref_pac+0 ASC, s.id ASC
LIMIT {$pp} OFFSET {$offset}";




$rs = $conn->query($sql);


/* ==== Helpers ==== */
function qs_without(array $skip = [])
{
  $params = $_GET;
  foreach ($skip as $k) {
    unset($params[$k]);
  }
  return http_build_query($params);
}

// Quita 'p', 'ok' y 'err' del querystring base para que no se propaguen
$base_qs = qs_without(['p', 'ok', 'err']);

// Para usar al volver después de eliminar (sin arrastrar ok/err)
$back_qs = qs_without(['ok', 'err']);

?>


<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Listado de Segmentación</title>

  <link rel="stylesheet" href="../public/bootstrap5/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../public/estilos/estilos.css?v=11">

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">


  <!-- Inyecta el total PAC del año actual al JS global -->
  <script>
    window.TOTAL_PAC_FILTRO = <?= json_encode($total_pac) ?>;
  </script>




  <style>
    /* ===== Ajustes mínimos por si falta algo en el CSS global ===== */
    /* Table Headers: Sticky fix with unified background on TH cells only */
    .table-fixed { table-layout: fixed; }
    .table-header, .table-header tr { 
        background: transparent !important; /* Remove background from parent rows */
        border: none !important;
    }
    .table-header th {
        z-index: 1010;
        background-color: #00779e !important;
        color: white !important;
        vertical-align: middle;
        padding: 0.4rem 0.5rem !important;
        font-size: 0.78rem !important;
        line-height: 1.2 !important;
        border: none !important;
        box-shadow: inset 0 -1px 0 rgba(255,255,255,0.2);
        white-space: nowrap;
    }

    .nowrap {
      white-space: nowrap
    }

    .text-clamp-3 {
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
      line-height: 1.25
    }

    .col-pac {
      width: 80px
    }

    .col-objeto {
      width: 100px
    }

    .col-proceso {
      width: 140px
    }

    .col-num {
      width: 120px
    }

    .col-pct {
      width: 65px;
      text-align: center
    }

    .col-badge {
      width: 90px
    }

    .col-prog {
      width: 50px;
      text-align: center
    }

    .col-fecha {
      width: 95px
    }

    .col-acciones {
      width: 110px;
      text-align: center
    }

    .col-desc {
      min-width: 250px;
      width: auto;
    }

    .badge-rutinario {
      background: #2e7d32
    }

    .badge-critico {
      background: #F1C40F !important;
      /* amarillo oficial */
      color: #000 !important;
      /* legible en amarillo */
    }

    .badge-operacional {
      background: #00acc1;
      color: #111
    }

    .badge-estrategico {
      background: #e53935
    }

    .btn-icon {
      width: 34px;
      height: 34px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 8px;
      border: none;
      font-size: 16px;
      cursor: pointer;
      transition: all .2s
    }

    .btn-view {
      background: #0d6efd;
      color: #fff
    }

    .btn-view:hover {
      background: #0b5ed7;
      transform: scale(1.05)
    }

    .btn-edit {
      background: #fd7e14;
      color: #fff
    }

    .btn-edit:hover {
      background: #e96b0b;
      transform: scale(1.05)
    }

    .btn-del {
      background: #dc3545;
      color: #fff
    }

    .btn-del:hover {
      background: #bb2d3b;
      transform: scale(1.05)
    }

    .table.table-clean>:not(caption)>*>* {
      border-color: #e9ecef
    }

    /* ===== Sticky footer (no afecta colores del encabezado) ===== */
    html,
    body {
      height: 100%;
    }

    body {
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }

    .content {
      flex: 1;
    }

    /* Paginación */
    .table-pagination {
      padding: .75rem 0 1rem;
    }

    .table-pagination .pagination .page-link {
      border-radius: .5rem;
    }

    .table-pagination .pagination .page-item.active .page-link {
      background: var(--brand-main, #0088aa);
      border-color: var(--brand-main, #0088aa);
    }

    /* Footer */
    .site-footer {
      background: var(--brand-900, #006f87);
      /* tono oscuro, no toca encabezado */
      color: #fff;
      border-top: 1px solid rgba(255, 255, 255, .15);
      box-shadow: 0 -2px 6px rgba(0, 0, 0, .06);
      text-align: center;
      font-size: .85rem;
    }

    .site-footer a {
      color: #fff;
      text-decoration: none;
    }

    .site-footer a:hover {
      text-decoration: underline;
    }

    /* Encabezado del modal en tono corporativo */
    .modal-modern .modal-header {
      background: linear-gradient(180deg, #0099bb 0%, #007f9e 100%);
      color: #fff;
    }

    /* === Ajustes al modal (colores más suaves y tipografía limpia) === */

    /* Fuente moderna y simple */
    .modal-modern {
      font-family: 'Inter', 'Segoe UI', 'Roboto', sans-serif;
    }

    /* Encabezado del modal */
    .modal-modern .modal-header {
      background: linear-gradient(180deg, #0099bb 0%, #007f9e 100%);
      color: #fff;
    }

    /* === Ajustes suaves para textos dentro del modal === */

    /* Etiquetas / títulos (N° PAC, Objeto, % PAC, etc.) */
    .modal-modern .kv small,
    .modal-modern .kpi .kpi-label,
    .modal-modern .items-card .card-header strong,
    .modal-modern .items-card .card-header .text-muted {
      color: #495057 !important;
      /* gris medio-claro */
      font-weight: 600;
      /* seminegrita */
      letter-spacing: .01em;
      /* un poco más definido */
      font-size: 0.85rem;
      /* tamaño controlado */
    }

    /* Valores principales (números, fechas, descripciones) */
    .modal-modern .kv .kv-v,
    .modal-modern .kpi-value {
      color: #495057 !important;
      /* gris medio nítido */
      font-weight: 500;
      /* un poco más liviano que los títulos */
      font-size: 0.95rem;
      /* ligeramente más grande */
    }

    /* Ítems dentro de la tabla (texto de filas) */
    .modal-modern .table-modern td {
      color: #495057 !important;
      /* gris medio consistente */
      font-weight: 500;
      font-size: 0.9rem;
    }

    /* Montos en la última columna de la tabla */
    .modal-modern .table-modern td:last-child {
      color: #495057 !important;
      /* gris medio */
      font-weight: 600;
      /* un poco más destacados */
    }

    /* Modal flotante ~85-90% del alto de la ventana */
    #modalRegistrar .modal-content {
      height: calc(100vh - 140px);
    }

    #modalRegistrar .modal-body {
      padding: 0;
      height: 100%;
    }

    #ifrRegistrar {
      width: 100%;
      height: 100%;
      border: 0;
      display: block;
    }

    /* Modal más ancho y elegante */
    @media (min-width: 1400px) {
      .modal-xxl {
        max-width: 1280px;
      }

      /* puedes subir a 1360 si lo prefieres */
    }

    @media (max-width: 1399.98px) {
      #modalRegistrar .modal-dialog {
        max-width: 96vw;
        margin: 1rem auto;
      }
    }

    #modalRegistrar .modal-content {
      border-radius: 12px;
    }

   


    /* Por si algún lib deja padding-right pegado, ignóralo */
    body.swal2-shown {
      padding-right: 0 !important;
    }

    .resumen-consulta {
      display: flex;
      justify-content: flex-start;
      /* a la izquierda */
      align-items: center;
      /* centra verticalmente dentro del chip */
      gap: 12px;
      font-size: 14px;
      color: #6c757d;
      font-family: 'Segoe UI', Roboto, sans-serif;
    }

    .chip.metric-soft {
      display: flex;
      /* flex interno */
      align-items: center;
      /* centra el texto verticalmente */
      background: #f8f9fa;
      border: 1px solid #e0e0e0;
      border-radius: 8px;
      padding: 6px 12px;
      /* igual margen arriba/abajo */
    }

    .chip.metric-soft .valor {
      font-weight: 600;
      color: #495057;
    }

    .tooltip {
      filter: drop-shadow(0 10px 22px rgba(0, 0, 0, .18));
      /* sombra suave y grande */
      z-index: 1085;
      /* sobre modals y tablas si hace falta */
    }

    .tooltip-inner {
      background-color: #007bb8 !important;
      /* celeste medio-oscuro */
      color: #ffffff !important;
      /* texto blanco para contraste */
      border: 1px solid #006a9d;
      /* borde más fuerte */
      font-size: 13px;
      padding: 8px 12px;
      border-radius: 8px;
      text-align: left;
      max-width: 520px;
      white-space: normal;
      word-break: break-word;

      /* Flotante con sombra fuerte */
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.25);
    }

    /* Tooltip Inner: estado inicial (zoom out) */
    .tooltip .tooltip-inner {
      transform: scale(0.8);
      transform-origin: center;
      transition: transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    /* Tooltip Inner: estado visible (zoom normal) */
    .tooltip.show .tooltip-inner {
      transform: scale(1);
    }

    /* flechita con el mismo color del fondo */
    .tooltip.bs-tooltip-top .tooltip-arrow::before {
      border-top-color: #007bb8 !important;
    }

    .tooltip.bs-tooltip-bottom .tooltip-arrow::before {
      border-bottom-color: #007bb8 !important;
    }

    .tooltip.bs-tooltip-end .tooltip-arrow::before {
      border-right-color: #007bb8 !important;
    }

    .tooltip.bs-tooltip-start .tooltip-arrow::before {
      border-left-color: #007bb8 !important;
    }

    /* ICONO CMN CON ZOOM AL PASAR MOUSE */
    .cmn-icon {
      display: inline-block;
      cursor: pointer !important; /* Cambiado a mano */
      font-size: 0.9em;
      color: rgba(255, 255, 255, 0.85);
      margin-left: 4px;
      transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), color 0.2s;
    }

    .cmn-icon:hover {
      transform: scale(1.35);
      color: #fff;
    }

    /* Botón limpiar */
    .btn-clean {
      background-color: #f8f9fa;
      /* gris claro */
      color: #495057;
      /* gris oscuro */
      border-color: #ced4da;
      font-weight: 500;
      transition: all 0.2s ease-in-out;
    }

    .btn-clean:hover {
      background-color: #e2e6ea;
      /* gris más oscuro al pasar */
      border-color: #adb5bd;
      color: #212529;
    }

    .item-no {
      min-width: 82px;
      font-weight: 600
    }

    .items-row .table-modern td,
    .items-row .table-modern th {
      font-size: .85rem;
    }

    .item-collapse {
      background: #EAFAFA;
    }


    /* ================================
   Suma ítems – versión final
   ================================ */

    /* base común */
    .btn-items {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: .25rem;
      line-height: 1;
      /* alto real, sin inflar */
      padding: 0 .55rem !important;
      /* súper compacto */
      height: 20px;
      /* alto fijo del chip */
      min-height: 20px !important;
      font-size: .60rem;
      border-radius: .42rem;
      border: 1px solid transparent;
      /* para poder controlar el borde */
    }

    /* detalles internos */
    .btn-items .bi {
      font-size: .9em;
      line-height: 1;
      position: relative;
      top: -1px;
    }

    .btn-items .count {
      min-width: 3em;
      text-align: center;
    }

    /* === Suma ítems – color un poco más intenso === */
    .btn-items {
      color: #075e6b;
      /* texto (antes #0b7285) */
      background-color: #dff2f7;
      /* fondo (antes #f3fbfd) */
      border-color: #9ee4f2;
      /* borde (antes #b6effb) */
    }

    .btn-items:hover {
      background-color: #cfeaf3;
      /* hover (antes #e9f7fb) */
      border-color: #63d3e6;
      /* hover borde (antes #66d9ef) */
    }

    /* come un pelín de espacio vertical en la celda */
    td .btn-items {
      margin-top: -.15rem;
      margin-bottom: -.15rem;
    }

    /* Si quedó `btn-info` en el HTML, neutraliza sus colores */
    .btn-info.btn-items {
      color: #075e6b !important;
      background-color: #dff2f7 !important;
      border-color: #9ee4f2 !important;
    }

    .btn-info.btn-items:hover {
      background-color: #cfeaf3 !important;
      border-color: #63d3e6 !important;
    }

    /* ===== Botón PDF (resumido) – contraste real sobre fondo claro ===== */
    .btn-outline-danger.btn-resumido {
      background-color: rgba(220, 53, 69, .22) !important;
      /* visible en fondos claros */
      color: #b02a37 !important;
      border-color: #dc3545 !important;
      border-width: 2px;
      border-radius: .65rem;
    }

    .btn-outline-danger.btn-resumido:hover,
    .btn-outline-danger.btn-resumido:focus {
      background-color: #dc3545 !important;
      color: #fff !important;
      border-color: #dc3545 !important;
    }

    .btn-outline-danger.btn-resumido:focus-visible {
      box-shadow: 0 0 0 .25rem rgba(220, 53, 69, .25) !important;
    }

    .btn-outline-danger.btn-resumido:disabled,
    .btn-outline-danger.btn-resumido.disabled {
      background-color: rgba(220, 53, 69, .18) !important;
      color: #b02a37 !important;
      opacity: .65;
    }

    /* Quitar el fondo blanco del chip "resumido" y mostrar un punto separador */
    .btn-resumido .sub {
      background: transparent !important;
      padding: 0 !important;
      border-radius: 0 !important;
      margin-left: .35rem;
      color: inherit;
      /* hereda el color del botón */
      opacity: .85;
    }

    .btn-resumido .sub::before {
      content: "•";
      margin: 0 .35rem 0 .25rem;
      opacity: .6;
    }

    /* ===== Variantes opcionales ===== */

    /* 1) Refuerzo de fondo (con texto oscuro) */
    .btn-outline-danger.btn-resumido.btn-resumido--bold {
      background-color: rgba(220, 53, 69, .32) !important;
    }

    /* 2) Texto blanco permanente + fondo más intenso */
    .btn-outline-danger.btn-resumido.btn-resumido--white {
      color: #fff !important;
      background-color: #d94b57 !important;
      /* contraste correcto */
      border-color: #d94b57 !important;
    }

    .btn-outline-danger.btn-resumido.btn-resumido--white:hover,
    .btn-outline-danger.btn-resumido.btn-resumido--white:focus {
      background-color: #dc3545 !important;
      color: #fff !important;
    }

    .btn-resumido.btn-resumido--white .sub,
    .btn-resumido.btn-resumido--white .sub::before {
      color: #fff !important;
      opacity: .9;
    }

    /* 2.1) Aún más intenso: combinar white + bold */
    .btn-outline-danger.btn-resumido.btn-resumido--white.btn-resumido--bold {
      background-color: #dc5461 !important;
      border-color: #dc5461 !important;
    }

    /* ===== Columnas más compactas ===== */
    .col-anexo {
      width: 96px;
      text-align: center;
    }

    /* antes sin control fijo */
    .col-acciones {
      width: 98px;
      text-align: center;
    }

    /* antes 132px, demasiado ancho */

    /* En pantallas medianas/grandes reduce un poco más */
    @media (max-width: 1399.98px) {
      .col-anexo {
        width: 88px;
      }

      .col-acciones {
        width: 92px;
      }
    }

    @media (max-width: 1199.98px) {
      .col-anexo {
        width: 82px;
      }

      .col-acciones {
        width: 88px;
      }
    }

    /* ===== Botones de acción y anexo en formato “chip” más angosto ===== */
    .actions {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }

    .btn-icon {
      width: 28px;
      height: 28px;
      border-radius: 6px;
      font-size: 14px;
    }

    .btn-icon .bi {
      font-size: 14px;
      line-height: 1;
    }

    /* Columna más compacta */
    .col-anexo {
      width: 60px !important;
      text-align: center;
      white-space: nowrap;
      padding: 0 !important;
    }

    /* Botón anexo versión comprimida */
    .btn-anexo {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 0 6px;
      height: 22px;
      font-size: .65rem;
      font-weight: 700;
      background: #fcd26a;
      /* amarillo suave */
      border: 1px solid #f5b800;
      color: #6b4a00;
      border-radius: 4px;
      line-height: 1;
      white-space: nowrap;
    }

    .btn-anexo:hover {
      filter: brightness(.92);
    }

    /* Quita relleno vertical extra de celdas para ganar aire */
    .table-sm td,
    .table-sm th {
      padding: .4rem .5rem;
    }

    /* Descripción: 2 líneas (en vez de 3) para evitar crecer en alto */
    .text-clamp-3 {
      -webkit-line-clamp: 2;
    }

    /* ===== Columnas más compactas ===== */
    .col-anexo {
      width: 96px;
      text-align: center;
    }

    /* antes sin control fijo */
    .col-acciones {
      width: 98px;
      text-align: center;
    }

    /* antes 132px, demasiado ancho */

    /* En pantallas medianas/grandes reduce un poco más */
    @media (max-width: 1399.98px) {
      .col-anexo {
        width: 88px;
      }

      .col-acciones {
        width: 92px;
      }
    }

    @media (max-width: 1199.98px) {
      .col-anexo {
        width: 82px;
      }

      .col-acciones {
        width: 88px;
      }
    }

    /* ===== Botones de acción y anexo en formato “chip” más angosto ===== */
    .actions {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }

    .btn-icon {
      width: 28px;
      height: 28px;
      border-radius: 6px;
      font-size: 14px;
    }

    .btn-icon .bi {
      font-size: 14px;
      line-height: 1;
    }

    .btn-anexo {
      --anx-bg: #ffecb5;
      --anx-bd: #ffe08a;
      --anx-tx: #7a5a00;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 0 8px;
      height: 28px;
      line-height: 1;
      font-size: .78rem;
      font-weight: 600;
      background: var(--anx-bg);
      border: 1px solid var(--anx-bd);
      color: var(--anx-tx);
      border-radius: 6px;
      white-space: nowrap;
    }

    .btn-anexo:hover {
      filter: brightness(.98);
    }

    /* Quita relleno vertical extra de celdas para ganar aire */
    .table-sm td,
    .table-sm th {
      padding: .4rem .5rem;
    }

    /* Descripción: 2 líneas (en vez de 3) para evitar crecer en alto */
    .text-clamp-3 {
      -webkit-line-clamp: 2;
    }

    .table-header th {
      text-align: center;
      vertical-align: middle;
    }

    /* Objeto (columna 2) */
    .table-fixed thead th:nth-child(2),
    .table-fixed tbody td:nth-child(2),

    /* Tipo Proceso (columna 4) */
    .table-fixed thead th:nth-child(4),
    .table-fixed tbody td:nth-child(4),

    /* Cuantía (categoría) (columna 8) */
    .table-fixed thead th:nth-child(8),
    .table-fixed tbody td:nth-child(8),

    /* Riesgo (columna 9) */
    .table-fixed thead th:nth-child(9),
    .table-fixed tbody td:nth-child(9),

    /* Resultado (columna 10) */
    .table-fixed thead th:nth-child(10),
    .table-fixed tbody td:nth-child(10),

    /* Prog. (columna 11) */
    .table-fixed thead th:nth-child(11),
    .table-fixed tbody td:nth-child(11),

    /* Fecha (columna 12) */
    .table-fixed thead th:nth-child(12),
    .table-fixed tbody td:nth-child(12) {
      text-align: center !important;
      vertical-align: middle;
    }

    /* ===== Scroll horizontal SOLO para la tabla ===== */
    .tabla-scroll {
      overflow-x: auto;
      overflow-y: hidden;
      width: 100%;
    }
    /* ===== REJILLA DE LA TABLA ===== */
    .table-fixed > tbody > tr > td {
        border: 1px solid #dee2e6 !important;
        vertical-align: middle;
    }
    .table-fixed > thead.table-header > tr > th {
        border: 1px solid rgba(255,255,255,0.25) !important;
    }
    .table-fixed > tbody > tr:hover > td {
        background-color: #f0f8fc !important;
    }
  </style>
</head>

<body class="bg-light">

<?php
require_once 'layout/topbar.php';
require_once 'layout/sidebar.php';
?>

  <div class="page-content" style="padding: 20px; padding-top: 80px;">

      <!-- Header actualizado -->
      <div class="page-head shadow-sm d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 px-3 py-2 rounded-3 bg-white border-bottom border-3" style="border-color: #007bb8 !important; border-top: 4px solid #0099bb !important;">

        <!-- Información de la unidad (izquierda) -->
        <div class="d-flex align-items-center gap-3">
          <div class="d-flex flex-column">
             <h4 class="mb-1 fw-bold" style="color: #007bb8;">
                 <i class="fa-solid fa-chart-pie me-2"></i> Segmentación
             </h4>
            <div class="subtitle text-secondary" style="font-size: 0.85rem;">UE: UE009-VII DIRTEPOL LIMA | Segmentación de Bienes y Servicios - LEY 32069</div>
          </div>
        </div>

        <!-- Derecha: métricas PAC -->
        <div class="d-flex align-items-center gap-2 mt-2 mt-md-0 flex-wrap">

          <!-- Total PAC -->
          <div class="d-flex align-items-center gap-2 px-3 py-2 rounded-3"
               style="background: #e8f4fd; border: 1px solid #b3d9f5;">
            <i class="fa-solid fa-sack-dollar" style="color:#007bb8; font-size:1.1rem;"></i>
            <div class="lh-1">
              <div class="text-muted" style="font-size:0.7rem; font-weight:600; letter-spacing:.5px;">TOTAL PAC (<?= $anio ?>)</div>
              <div class="fw-bold" style="color:#007bb8; font-size:0.95rem;">
                S/ <span id="m-total-pac"><?= number_format($total_pac, 2) ?></span>
              </div>
            </div>
          </div>

          <!-- 10% PAC -->
          <div class="d-flex align-items-center gap-2 px-3 py-2 rounded-3"
               style="background: #fff3e0; border: 1px solid #ffe0b2;">
            <i class="fa-solid fa-percent" style="color:#e65100; font-size:1rem;"></i>
            <div class="lh-1">
              <div class="text-muted" style="font-size:0.7rem; font-weight:600; letter-spacing:.5px;">10% UMBRAL</div>
              <div class="fw-bold" style="color:#e65100; font-size:0.95rem;">
                S/ <span id="m-pac-10"><?= number_format($pac_10, 2) ?></span>
              </div>
            </div>
          </div>

          <!-- Métricas de consulta actual -->
          <div class="resumen-consulta d-flex align-items-center gap-2 px-3 py-2 rounded-3"
               style="background: #e8f5e9; border: 1px solid #c8e6c9;">
            <div class="d-flex flex-column lh-1">
              <div class="text-muted" style="font-size:0.7rem; font-weight:600; letter-spacing:.5px;">PROCEDIMIENTOS</div>
              <div class="fw-bold" style="color:#2e7d32; font-size:0.95rem;">
                <span class="valor" id="m-count"><?= (int) $agg['cantidad'] ?></span>
              </div>
            </div>
            
            <div class="border-start border-success border-opacity-25 mx-2" style="height:25px;"></div>
            
            <div class="d-flex flex-column lh-1">
              <div class="text-muted" style="font-size:0.7rem; font-weight:600; letter-spacing:.5px;">TOTAL CUANTÍA</div>
              <div class="fw-bold" style="color:#2e7d32; font-size:0.95rem;">
                S/ <span class="valor" id="m-sum"><?= number_format((float) $agg['total_cuantia'], 2) ?></span>
              </div>
            </div>
          </div>

        </div>
      </div>



      <!-- Filtros + Botones en una sola fila compacta -->
      <form id="frmFiltros" class="card card-body mb-3 py-2" method="get">
        <?php
        $qsBase = $_GET;
        unset($qsBase['p'], $qsBase['ok'], $qsBase['err']);
        ?>

        <div class="d-flex flex-wrap align-items-end gap-3 w-100">

          <!-- ===== FILTROS ===== -->
          <div class="d-flex flex-wrap gap-2 align-items-end flex-grow-1">

            <!-- Año -->
            <div style="width: 80px;">
              <label class="form-label mb-1 small fw-semibold text-muted">Año</label>
              <input type="number" class="form-control form-control-sm" name="anio"
                     value="<?= htmlspecialchars((string) $anio) ?>">
            </div>

            <!-- Programado -->
            <div style="width: 125px;">
              <label class="form-label mb-1 small fw-semibold text-muted">Programado</label>
              <select class="form-select form-select-sm" name="programado">
                <option value="">-- Todos --</option>
                <option value="1" <?= $programado === 1 ? 'selected' : '' ?>>Programado</option>
                <option value="0" <?= $programado === 0 ? 'selected' : '' ?>>No programado</option>
              </select>
            </div>

            <!-- Texto -->
            <div style="flex: 1; min-width: 130px;">
              <label class="form-label mb-1 small fw-semibold text-muted">Texto</label>
              <input type="text" class="form-control form-control-sm" name="q"
                     value="<?= htmlspecialchars($texto) ?>" placeholder="Buscar ref/descr.">
            </div>

            <!-- Resultado -->
            <div style="width: 125px;">
              <label class="form-label mb-1 small fw-semibold text-muted">Resultado</label>
              <select class="form-select form-select-sm" name="resultado">
                <option value="">-- Todos --</option>
                <?php foreach (['Rutinario', 'Crítico', 'Operacional', 'Estratégico'] as $r): ?>
                  <option <?= $resultado === $r ? 'selected' : '' ?>><?= $r ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Limpiar -->
            <div>
              <a href="segmentacion_listado.php"
                 class="btn btn-secondary btn-sm fw-semibold d-flex align-items-center gap-1"
                 title="Limpiar filtros">
                <i class="bi bi-x-circle"></i> Limpiar
              </a>
            </div>

          </div>

          <!-- ===== BOTONES ===== -->
          <div class="d-flex flex-wrap gap-2 align-items-center">

            <!-- PDFs y Excel -->
            <a class="btn btn-sm btn-action fw-semibold d-flex align-items-center gap-2 shadow-sm rounded-3"
               href="../controlador/reporte_segmentacion_pdf.php?<?= http_build_query($qsBase) ?>"
               target="_blank" style="background:#0288D1;color:white;border:1px solid #0277bd;"
               title="PDF resumido">
              <i class="bi bi-file-earmark-pdf-fill"></i> Resumido
            </a>
            <a class="btn btn-danger btn-sm btn-action fw-semibold d-flex align-items-center gap-2 shadow-sm rounded-3 text-white"
               href="../controlador/reporte_segmentacion_pdf.php?<?= http_build_query(array_merge($qsBase, ['items' => 1])) ?>"
               target="_blank" style="border:1px solid #c62828;" title="PDF con ítems">
              <i class="bi bi-file-earmark-pdf-fill"></i> + Ítems
            </a>
            <a class="btn btn-success btn-sm btn-action fw-semibold d-flex align-items-center gap-2 shadow-sm rounded-3 text-white"
               style="background:#1f9d55;border:1px solid #1b5e20;"
               href="../controlador/reporte_segmentacion_excel.php?<?= http_build_query($qsBase) ?>"
               title="Exportar Excel">
              <i class="bi bi-file-earmark-excel-fill"></i> Excel
            </a>

            <!-- Nuevo e Importar (solo admins) -->
            <?php if ($_SESSION['rol'] === 'Administrador' || $_SESSION['rol'] === 'Super Administrador' || isset($_SESSION['permisos']['SEGMENTACION']['CREAR'])): ?>

            <button type="button" id="btnNuevaSeg"
                    class="btn btn-warning btn-sm btn-action fw-semibold text-dark d-flex align-items-center gap-2 shadow-sm rounded-3"
                    style="border:1px solid #f9a825;">
              <i class="bi bi-plus-circle"></i> Nuevo
            </button>
            <button type="button"
                    class="btn btn-sm btn-action fw-semibold d-flex align-items-center gap-2 shadow-sm rounded-3"
                    style="background:#8e44ad;color:white;border:1px solid #7b1fa2;"
                    data-bs-toggle="modal" data-bs-target="#modalImportar"
                    title="Importar desde Excel">
              <i class="bi bi-upload"></i> Importar
            </button>

            <?php endif; ?>

            <!-- Dashboard -->
            <a href="../controlador/dashboard.php?<?= http_build_query($qsBase) ?>"
               class="btn btn-sm btn-action fw-semibold d-flex align-items-center gap-2 shadow-sm rounded-3"
               style="background:#1565c0;color:white;border:1px solid #0d47a1;"
               title="Dashboard Estadístico">
              <i class="bi bi-bar-chart-line-fill"></i> Dash.
            </a>

            <!-- Tutorial -->
            <a href="../controlador/generar_manual_segmentacion_pdf.php" target="_blank"
               class="btn btn-light btn-sm btn-action fw-semibold d-flex align-items-center gap-2 shadow-sm rounded-3 text-info border"
               title="Manual de Usuario">
              <i class="bi bi-book-half text-warning"></i> Tutorial
            </a>

          </div>
        </div>

      </form>



      <!-- Tabla -->
      <div class="table-responsive tabla-scroll">

        <?php if ($rs->num_rows): ?>
          <table class="table table-fixed table-clean table-hover table-sm align-middle bg-white zebra">

            <thead class="table-header">
              <tr>
                <th class="col-pac">N° REF. PAC</th>
                <th class="text-center" style="width: 80px;">
                  CMN
                  <span class="cmn-icon" data-bs-toggle="tooltip" 
                        data-bs-placement="bottom" title="Cuadro Multianual de Necesidades">
                    <i class="bi bi-info-circle-fill"></i>
                  </span>
                </th>
                <th class="col-objeto th-center">Objeto</th>
                <th class="col-desc">Descripción</th>
                <th class="col-proceso col-hide-md th-center">Tipo Proceso</th>
                <th class="col-num text-end th-center">Cuantía Cont. S/.</th>
                <th class="col-num text-end col-hide-sm">Suma ítems</th>
                <th class="col-pct col-hide-md th-center"> % PAC
                  <span style="cursor:pointer; font-weight:bold;" data-bs-toggle="tooltip" data-bs-placement="top"
                    data-bs-title="Es el porcentaje que representa la cuantía de este procedimiento respecto del Total PAC del año. Se calcula como: (Cuantía / Total PAC del año) × 100, redondeado a 2 decimales. El Total PAC se obtiene como la suma de todas las cuantías del año (PAC real).">
                    ⓘ
                  </span>
                </th>

                <th class="col-badge col-hide-sm th-center">
                  Cuantía
                  <span style="cursor:pointer; font-weight:bold;" data-bs-toggle="tooltip" data-bs-placement="top"
                    data-bs-title="Se clasifica como Alta cuando la cuantía del procedimiento es mayor al 10 % del Total PAC del año. En caso contrario, se clasifica como Baja.">
                    ⓘ
                  </span>
                </th>

                <th class="col-badge col-hide-sm th-center">
                  Riesgo
                  <span style="cursor:pointer; font-weight:bold;" data-bs-toggle="tooltip" data-bs-placement="top"
                    data-bs-title="Se clasifica como Alto cuando al menos una de estas condiciones es 'Sí': (1) se declaró desierto en los últimos 2 años; (2) promedio de postores en los últimos 2 años: Bienes ≤ 3 o Servicios ≤ 2; (3) existe disponibilidad limitada en el mercado. Si todas son 'No', el riesgo es Bajo.">
                    ⓘ
                  </span>
                </th>

                <th class="col-badge th-center">
                  Resultado
                  <span style="cursor:pointer; font-weight:bold;" data-bs-toggle="tooltip" data-bs-placement="top"
                    data-bs-title="Se determina combinando Cuantía y Riesgo: Baja+Bajo=Rutinario; Baja+Alto=Crítico; Alta+Bajo=Operacional; Alta+Alto=Estratégico.">
                    ⓘ
                  </span>
                </th>

                <th class="col-prog col-hide-md th-center">Prog.</th>
                <th class="col-fecha col-hide-md th-center">Fecha</th>
                <th class="col-anexo text-center">Anexo</th>
                <th class="col-acciones">Acciones</th>

              </tr>
            </thead>

            <tbody>


              <?php while ($row = $rs->fetch_assoc()):
                $res = mb_strtolower(trim($row['resultado_segmentacion'] ?? ''), 'UTF-8');
                $badgeMap = [
                  'rutinario' => 'badge-rutinario',
                  'crítico' => 'badge-critico',
                  'critico' => 'badge-critico',
                  'operacional' => 'badge-operacional',
                  'estratégico' => 'badge-estrategico',
                  'estrategico' => 'badge-estrategico',
                ];
                $badgeClass = $badgeMap[$res] ?? 'bg-secondary';
                $suma = (float) $row['suma_items'];
                $cuantia = (float) $row['cuantia'];
                $pctPac = isset($row['porcentaje']) ? (float) $row['porcentaje'] : 0;

                ?>

                <tr>

                  <td class="text-center fw-semibold"><?= htmlspecialchars($row['ref_pac']) ?></td>
                  <td class="text-center fw-semibold">
                    <?= htmlspecialchars($row['cmn'] ?? '-') ?>
                  </td>
                  <td><?= htmlspecialchars($row['objeto_contratacion'] ?? '-') ?></td>
                  <td class="col-desc">

                    <div class="text-clamp-3" data-bs-toggle="tooltip" data-bs-placement="top"
                      data-bs-title="<?= htmlspecialchars($row['descripcion']) ?>">
                      <?= htmlspecialchars($row['descripcion']) ?>
                    </div>

                  </td>
                  <td class="col-hide-md"><?= htmlspecialchars($row['tipo_proceso'] ?? '-') ?></td>

                  <td class="text-end"><?= number_format($cuantia, 2) ?></td>
                  <?php $itemsCount = (int) ($row['n_items'] ?? 0); ?>
                  <td class="text-end col-hide-sm <?= abs($suma - $cuantia) > 0.01 ? 'text-danger fw-bold' : '' ?>">
                    <?= $suma ? number_format($suma, 2) : '-' ?>
                    <?php if ($itemsCount > 0): ?>

                      <button type="button" class="btn btn-items js-items" data-id="<?= (int) $row['id'] ?>"
                        data-bs-target="#items-<?= (int) $row['id'] ?>">
                        <i class="bi bi-list-ul"></i>
                        <span class="count"><?= (int) $itemsCount ?></span>
                      </button>

                    <?php endif; ?>
                  </td>

                  <td class="text-center col-hide-md">
                    <?= number_format($pctPac, 2) ?>%
                  </td>


                  <td class="col-hide-sm">
                    <span class="badge badge-s bg-light text-dark"><?= htmlspecialchars($row['cuantia_categoria']) ?></span>
                  </td>
                  <td class="col-hide-sm">
                    <span class="badge badge-s bg-light text-dark"><?= htmlspecialchars($row['riesgo_categoria']) ?></span>
                  </td>
                  <td>
                    <span
                      class="badge badge-s <?= $badgeClass ?>"><?= htmlspecialchars($row['resultado_segmentacion']) ?></span>
                  </td>
                  <td class="col-hide-md nowrap"><?= $row['programado'] ? 'Sí' : 'No' ?></td>
                  <td class="col-hide-md nowrap"><?= htmlspecialchars($row['fecha']) ?></td>

                  <!-- Columna ANEXO -->
                  <td class="text-center col-anexo">
                    <a href="../controlador/segmentacion_anexo.php?ref_pac=<?= urlencode($row['ref_pac']) ?>&anio=<?= $anio ?>&items=1"
                      class="btn-icon btn-pdf" target="_blank" title="Generar ANEXO 01 (PDF)">
                      <i class="bi bi-filetype-pdf"></i>
                    </a>
                  </td>


                  <td class="text-center">

                    <div class="actions">

                      <!-- Botón Ver -->

                      <!-- Botón Ver -->
                      <button type="button" class="btn-icon btn-view js-view" data-id="<?= (int) $row['id'] ?>"
                        data-url="../controlador/segmentacion_detalle.php" title="Ver">
                        <i class="bi bi-eye"></i>
                      </button>


                      <!-- Botón Editar -->
                      <?php if ($pEditar): ?>
                        <button type="button" class="btn-icon btn-edit js-edit" data-id="<?= (int) $row['id'] ?>"
                          title="Editar">
                          <i class="bi bi-pencil"></i>
                        </button>
                      <?php endif; ?>


                      <!-- Botón Eliminar -->
                      <?php if ($pEliminar): ?>
                        <button type="button" class="btn-icon btn-del js-del"
                          data-href="../controlador/segmentacion_eliminar.php?id=<?= (int) $row['id'] ?>&qs=<?= urlencode($back_qs) ?>"
                          title="Eliminar">
                          <i class="bi bi-trash3"></i>
                        </button>
                      <?php endif; ?>
                    </div>
                  </td>



                </tr>
                <?php if ($itemsCount > 0): ?>
                  <tr class="items-row">
                    <td colspan="15" class="p-0 border-0">

                      <div id="items-<?= (int) $row['id'] ?>" class="collapse item-collapse">
                        <div class="p-3 items-host" data-id="<?= (int) $row['id'] ?>" data-loaded="0">
                          <div class="text-muted small">
                            <span class="spinner-border spinner-border-sm me-2"></span>Cargando ítems…
                          </div>
                        </div>
                      </div>
                    </td>
                  </tr>
                <?php endif; ?>

              <?php endwhile; ?>
            </tbody>


          </table>
        <?php else: ?>
          <div class="alert alert-warning mb-0">No hay registros que coincidan con los filtros seleccionados.</div>
        <?php endif; ?>
      </div><!-- /table-responsive -->

      <!-- Paginación al final -->
      <!-- Paginación -->
      <?php if ($total_pages > 1): ?>
        <?php
        $from = ($p - 1) * $pp + 1;
        $to = min($from + $pp - 1, $total_rows);
        ?>
        <div class="table-pagination">
          <nav aria-label="Segmentación - paginación">
            <ul class="pagination pagination-lg pagination-modern justify-content-center mb-0">
              <?php
              $mk = function ($page, $lbl, $disabled = false, $active = false) use ($base_qs) {
                $qs = $base_qs ? ($base_qs . '&p=' . $page) : ('p=' . $page);
                $href = '?' . $qs;
                $cls = 'page-item';
                if ($disabled)
                  $cls .= ' disabled';
                if ($active)
                  $cls .= ' active';
                echo '<li class="' . $cls . '"><a class="page-link" href="' . ($disabled ? '#' : $href) . '" aria-label="' . $lbl . '">' . $lbl . '</a></li>';
              };
              $mk(1, '«', $p == 1);
              $mk(max(1, $p - 1), '‹', $p == 1);

              // Página actual (centrada)
              $mk($p, (string) $p, false, true);

              $mk(min($total_pages, $p + 1), '›', $p == $total_pages);
              $mk($total_pages, '»', $p == $total_pages);
              ?>
            </ul>
          </nav>

          <!-- Chip con rango mostrado y total -->
          <div class="page-stats">
            Mostrando <strong><?= number_format($from) ?>–<?= number_format($to) ?></strong>
            de <strong><?= number_format($total_rows) ?></strong>
          </div>
        </div>
      <?php endif; ?>


    </div><!-- /container-fluid -->
  </div><!-- /content -->

  <!-- MODAL: Registrar Segmentación -->
  <div class="modal fade" id="modalRegistrar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 1280px;">
      <div class="modal-content">
        <div class="modal-header py-2">
          <h5 class="modal-title m-0">
            <i class="bi bi-clipboard-plus me-2"></i> Registrar Procedimientos de Selección
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <!-- altura útil de la ventana, sin forzar scroll interno -->
        <div class="modal-body p-0" style="height: calc(100vh - 140px);">
          <iframe id="ifrRegistrar" src="about:blank" style="border:0;width:100%;height:100%;display:block;"></iframe>
        </div>
      </div>
    </div>
  </div>



  <!-- *********************************MODAL VER DETALLE *********************************-->

  <div class="modal fade" id="modalVer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content modal-modern shadow-lg border-0">
        <div class="modal-header py-3">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-clipboard2-data fs-4 opacity-75"></i>
            <h5 class="modal-title m-0">Detalle del procedimiento</h5>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>

        <div class="modal-body p-0">
          <div id="ver-loading" class="text-center text-muted py-4 d-none">
            <div class="spinner-border me-2" role="status"></div>Cargando…
          </div>

          <div class="body-wrap">
            <!-- fila cabecera -->
            <div class="row g-3 g-md-4">
              <div class="col-6 col-lg-3">
                <div class="kv">
                  <small>N° Ref. PAC</small>
                  <div id="ver-ref-pac" class="kv-v"></div>
                </div>
              </div>

              <!-- CMN View -->
              <div class="col-6 col-lg-3">
                <div class="kv">
                  <small>CMN</small>
                  <div id="ver-cmn" class="kv-v"></div>
                </div>
              </div>

              <div class="col-6 col-lg-3">
                <div class="kv">
                  <small>Objeto</small>
                  <div id="ver-objeto" class="kv-v"></div>
                </div>
              </div>
            </div>

            <!-- descripción -->
            <div class="mt-3">
              <div class="kv">
                <small>Tipo Proceso</small>
                <div id="ver-tipo-proceso" class="kv-v"></div>
              </div>
              <div class="kv mt-2">
                <small>Descripción</small>
                <div id="ver-descripcion" class="desc-box"></div>
              </div>
            </div>

            <!-- KPIs -->
            <div class="row g-3 g-md-4 mt-2">
              <div class="col-6 col-md-3">
                <div class="kpi">
                  <div class="kpi-label">% del PAC</div>
                  <div id="ver-porcentaje" class="kpi-value">-</div>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="kpi">
                  <div class="kpi-label">Cuantía</div>
                  <div id="ver-cuantia" class="kpi-value">-</div>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="kpi">
                  <div class="kpi-label">Suma ítems</div>
                  <div id="ver-suma-items" class="kpi-value">-</div>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="kpi">
                  <div class="kpi-label">Cuantía</div>
                  <div id="ver-cuantia-cat" class="kpi-value badge-soft badge-soft-neutral">-</div>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="kpi">
                  <div class="kpi-label">Riesgo</div>
                  <div id="ver-riesgo" class="kpi-value badge-soft badge-soft-neutral">-</div>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="kpi">
                  <div class="kpi-label">Resultado</div>
                  <div id="ver-resultado" class="kpi-value badge-soft badge-soft-neutral">-</div>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="kpi">
                  <div class="kpi-label">Programado</div>
                  <div id="ver-programado" class="kpi-value">-</div>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="kpi">
                  <div class="kpi-label">Fecha</div>
                  <div id="ver-fecha" class="kpi-value">-</div>
                </div>
              </div>
            </div>




            <!-- Ítems -->
            <div class="mt-4 items-card card border-0 shadow-sm">
              <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Ítems</strong><span class="text-muted small">Monto (S/.)</span>
              </div>
              <div class="table-responsive">
                <table class="table table-modern align-middle mb-0">
                  <tbody id="ver-items-tbody">
                    <tr>
                      <td class="text-muted">Sin ítems</td>
                      <td class="text-end text-muted">—</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

          </div>
        </div>


        <div class="modal-footer bg-light-subtle">
          <button class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="bi bi-x-circle me-1"></i> Cerrar
          </button>
          <a id="ver-ir" class="" href="#" target="_blank">

          </a>
        </div>
      </div>
    </div>
  </div>


  <!-- ********************************* MODAL EDITAR ********************************* -->
  <div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
      <!-- OJO: el form envuelve .modal-content para que el footer quede DENTRO -->
      <form id="formEdit" class="modal-content modal-modern shadow-lg border-0">
        <div class="modal-header py-3">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-pencil-square fs-4 opacity-75"></i>
            <h5 class="modal-title m-0">Editar procedimiento</h5>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>

        <div class="modal-body p-0">
          <div class="body-wrap">
            <input type="hidden" name="id" id="ed-id">

            <!-- fila cabecera -->
            <div class="row g-3 g-md-4">
              <div class="col-6 col-lg-3">
                <div class="kv">
                  <small>N° REFPAC</small>
                  <input id="ed-ref-pac" name="ref_pac" class="form-control" type="text" inputmode="numeric"
                    pattern="\d*">
                </div>
              </div>

              <!-- CMN NUEVO -->
              <div class="col-6 col-lg-3">
                <div class="kv">
                  <small title="Cuadro Multianual de Necesidades" style="cursor:help; text-decoration-style:dotted; text-decoration-line:underline;">CMN</small>
                  <input id="ed-cmn" name="cmn" class="form-control" type="text" placeholder="Código">
                </div>
              </div>

              <div class="col-6 col-lg-3">
                <div class="kv">
                  <small>Objeto</small>
                  <select id="ed-objeto" name="objeto_contratacion" class="form-select">
                    <option>BIENES</option>
                    <option>SERVICIOS</option>
                    <option>CONSULTORÍA DE OBRAS</option>
                  </select>
                </div>
              </div>

              <div class="col-12 col-lg-6">
                <div class="kv">
                  <small>Tipo Proceso</small>
                  <select id="ed-tipo-proceso" name="tipo_proceso_id" class="form-select">
                    <?php
                    $tipos2 = $conn->query("SELECT id,nombre FROM tipo_proceso WHERE estado=1 ORDER BY nombre");
                    while ($t = $tipos2->fetch_assoc()):
                      ?>
                      <option value="<?= (int) $t['id'] ?>"><?= htmlspecialchars($t['nombre']) ?></option>
                    <?php endwhile; ?>
                  </select>
                </div>
              </div>
            </div>

            <!-- descripción -->
            <div class="mt-3">
              <div class="kv">
                <small>Descripción</small>
                <textarea id="ed-descripcion" name="descripcion" class="form-control" rows="3"
                  maxlength="2000"></textarea>
              </div>
            </div>

            <!-- KPIs -->
            <div class="row g-3 g-md-4 mt-2">
              <div class="col-6 col-md-3">
                <div class="kpi">
                  <div class="kpi-label">% del PAC</div>
                  <input id="ed-porcentaje-view" type="text" class="form-control" readonly>
                </div>
              </div>


              <div class="col-6 col-md-3">
                <div class="kpi">
                  <div class="kpi-label">Cuantía (S/.)</div>
                  <input id="ed-cuantia" name="cuantia" type="number" step="0.01" min="0" class="form-control">
                </div>
              </div>

              <div class="col-6 col-md-3">
                <div class="kpi">
                  <div class="kpi-label">Suma ítems</div>
                  <input id="ed-suma-items" class="form-control" type="text" readonly>
                </div>
              </div>

              <div class="col-6 col-md-3">
                <div class="kpi">
                  <div class="kpi-label">Cuantía</div>
                  <select id="ed-cuantia-cat" name="cuantia_categoria" class="form-select" disabled>

                    <option value="">-</option>
                    <option value="Baja">Baja</option>
                    <option value="Alta">Alta</option>
                  </select>
                </div>
              </div>

              <div class="col-6 col-md-3">
                <div class="kpi">
                  <div class="kpi-label">Riesgo</div>

                  <select id="ed-riesgo" name="riesgo_categoria" class="form-select" disabled>

                    <option value="">-</option>
                    <option value="Bajo">Bajo</option>
                    <option value="Alto">Alto</option>
                  </select>
                </div>
              </div>

              <div class="col-6 col-md-3">
                <div class="kpi">
                  <div class="kpi-label">Resultado</div>
                  <select id="ed-resultado" name="resultado_segmentacion" class="form-select" disabled>
                    <option value="">-</option>
                    <option value="Rutinario">Rutinario</option>
                    <option value="Crítico">Crítico</option>
                    <option value="Operacional">Operacional</option>
                    <option value="Estratégico">Estratégico</option>
                  </select>
                </div>
              </div>

              <div class="col-6 col-md-3">
                <div class="kpi">
                  <div class="kpi-label">Programado</div>
                  <select id="ed-programado" name="programado" class="form-select">
                    <option value="1">Sí</option>
                    <option value="0">No</option>
                  </select>
                </div>
              </div>

              <div class="col-6 col-md-3">
                <div class="kpi">
                  <div class="kpi-label">Fecha</div>
                  <input id="ed-fecha" name="fecha" type="date" class="form-control">
                </div>
              </div>
            </div>

            <!-- === Factores de Riesgo (solo para Editar) === -->
            <div class="card border-0 shadow-sm mt-3">
              <div class="card-header">
                <strong>Factores de Riesgo</strong>
              </div>
              <div class="card-body">
                <div class="row g-3">

                  <div class="col-md-4">
                    <label class="form-label" for="ed-declarado-desierto">¿Se declaró desierto en los 2 últimos
                      años?</label>
                    <select id="ed-declarado-desierto" name="declarado_desierto" class="form-select">
                      <option value="No">No</option>
                      <option value="Si">Sí</option>
                    </select>
                  </div>

                  <div class="col-md-4">
                    <label class="form-label" for="ed-pocos-postores">Promedio postores últimos 2 años: Bienes ≤ 3 o
                      Servicios ≤ 2</label>
                    <select id="ed-pocos-postores" name="pocos_postores" class="form-select">
                      <option value="No">No</option>
                      <option value="Si">Sí</option>
                    </select>
                  </div>

                  <div class="col-md-4">
                    <label class="form-label" for="ed-mercado-limitado">¿Existe disponibilidad limitada en el
                      mercado?</label>
                    <select id="ed-mercado-limitado" name="mercado_limitado" class="form-select">
                      <option value="No">No</option>
                      <option value="Si">Sí</option>
                    </select>
                  </div>

                </div>
              </div>
            </div>

            <!-- Ítems -->
            <div id="ed-items-card" class="card border-0 shadow-sm mt-3 d-none">
              <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Ítems del procedimiento</strong>
                <div class="d-flex align-items-center gap-2">
                  <span class="text-muted small">Monto (S/.)</span>
                  <button type="button" class="btn btn-sm btn-outline-primary btn-add-item">
                    <i class="bi bi-plus"></i> Agregar ítem
                  </button>
                </div>
              </div>
              <div class="table-responsive">
                <table class="table table-modern align-middle mb-0">
                  <tbody id="ed-items-tbody"></tbody>
                </table>
              </div>
            </div>


          </div>
        </div>

        <div class="modal-footer bg-light-subtle">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="bi bi-x-circle me-1"></i> Cancelar
          </button>
          <button id="btnEditSave" class="btn btn-primary" type="button">
            <i class="bi bi-check2-circle me-1"></i> Guardar cambios
          </button>
        </div>
        <input type="hidden" name="ed_items_delete_csv" id="ed-items-deleted">
      </form>
    </div>
  </div>






  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const btn = document.getElementById('btnNuevaSeg');
      const modalEl = document.getElementById('modalRegistrar');
      // Asegurarse de que bootstrap está cargado
      if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap no está cargado. Asegúrese de incluirlo antes de este script.');
        return;
      }
      const modal = new bootstrap.Modal(modalEl);
      const ifr = document.getElementById('ifrRegistrar');

      // Abrir modal
      btn?.addEventListener('click', () => {
        const params = new URLSearchParams(location.search);
        const anio = params.get('anio') || '<?= (int) date('Y') ?>';
        ifr.src = 'segmentacion.php?anio=' + encodeURIComponent(anio) + '&embed=1';
        modal.show();
      });

      // Mensajes del iframe
      window.addEventListener('message', (ev) => {
        if (ev.origin && ev.origin !== location.origin) return;
        if (!ev.data) return;

        const type = ev?.data?.type || ev.data;

        if (type === 'SEGMENTACION_GUARDADA') {
          modal.hide();
          ifr.src = 'about:blank';

          if (window.Swal) {
            Swal.fire({
              icon: 'success',
              title: 'Segmentación registrada',
              text: 'El procedimiento se registró correctamente.',
              timer: 1800,
              timerProgressBar: true,
              showConfirmButton: false
            }).then(() => {
              if (typeof refreshMetrics === 'function') refreshMetrics();
              const form = document.getElementById('frmFiltros');
              if (form?.requestSubmit) form.requestSubmit(); else form.submit();
            });
          } else {
            alert('La segmentación se registró correctamente.');
            if (typeof refreshMetrics === 'function') refreshMetrics();
            const form = document.getElementById('frmFiltros');
            if (form?.requestSubmit) form.requestSubmit(); else form.submit();
          }
        }

        if (type === 'CLOSE_MODAL') {
          modal.hide();
          ifr.src = 'about:blank';
        }
      });
    });
  </script>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const modalEl = document.getElementById('modalVer');
      if (typeof bootstrap === 'undefined') return;
      const modal = new bootstrap.Modal(modalEl);

      const $ref = document.getElementById('ver-ref-pac');
      const $cmn = document.getElementById('ver-cmn'); // NUEVO
      const $obj = document.getElementById('ver-objeto');
      const $tp = document.getElementById('ver-tipo-proceso');
      const $desc = document.getElementById('ver-descripcion');
      const $pct = document.getElementById('ver-porcentaje');
      const $cuan = document.getElementById('ver-cuantia');
      const $sum = document.getElementById('ver-suma-items');
      const $ccat = document.getElementById('ver-cuantia-cat');
      const $ries = document.getElementById('ver-riesgo');
      const $res = document.getElementById('ver-resultado');
      const $prog = document.getElementById('ver-programado');
      const $fecha = document.getElementById('ver-fecha');
      const $tbody = document.getElementById('ver-items-tbody');
      const $link = document.getElementById('ver-ir');
      const $load = document.getElementById('ver-loading');

      function money(n) {
        return (Number(n) || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      }
      function resetModal() {
        $ref.textContent = $obj.textContent = $tp.textContent = $desc.textContent = '';
        if ($cmn) $cmn.textContent = ''; // NUEVO
        $pct.textContent = $cuan.textContent = $sum.textContent = '';
        $ccat.textContent = $ries.textContent = $res.textContent = $prog.textContent = $fecha.textContent = '';
        $tbody.innerHTML = '<tr><td class="text-muted" colspan="2">Sin ítems</td></tr>';
        $link.href = '#';
      }

      document.addEventListener('click', async (ev) => {
        const btn = ev.target.closest('.js-view');
        if (!btn) return;

        const id = parseInt(btn.dataset.id || '0', 10);
        if (!id) return;

        const baseUrl = btn.dataset.url || '../controlador/segmentacion_detalle.php';
        const url = `${baseUrl}?id=${id}`;

        // Abre modal de inmediato
        resetModal();
        $load.classList.remove('d-none');
        modal.show();

        try {
          console.log('[ver] GET', url);
          const res = await fetch(url, { credentials: 'same-origin' });
          const text = await res.text();

          let json;
          try { json = JSON.parse(text); }
          catch (e) { throw new Error('Respuesta no es JSON válido (¿warnings/errores PHP?).'); }

          if (!json.ok) throw new Error(json.msg || 'Error');

          const s = json.data.cabecera;
          const items = json.data.items || [];

          $ref.textContent = s.ref_pac ?? '';
          if ($cmn) $cmn.textContent = s.cmn ?? ''; // NUEVO
          $obj.textContent = s.objeto_contratacion ?? '';
          $tp.textContent = s.tipo_proceso || '-';
          $desc.textContent = s.descripcion || '';
          const pctVer = Number(s.porcentaje || 0);
          $pct.textContent = pctVer.toFixed(2) + '%';


          $cuan.textContent = 'S/ ' + money(s.cuantia ?? 0);
          $sum.textContent = 'S/ ' + money(s.suma_items ?? 0);

          $ccat.textContent = s.cuantia_categoria;
          $ries.textContent = s.riesgo_categoria;
          $res.textContent = s.resultado_segmentacion;
          $prog.textContent = s.programado ? 'Sí' : 'No';
          $fecha.textContent = s.fecha || '';

          $tbody.innerHTML = '';
          const arr = (items || []).sort((a, b) => (a.orden || 999) - (b.orden || 999) || a.id - b.id);
          if (arr.length) {
            arr.forEach((it, idx) => {
              const n = (Number(it.orden) || 0) > 0 ? Number(it.orden) : (idx + 1);
              const tr = document.createElement('tr');
              tr.innerHTML = `<td><span class="text-muted small me-2">Ítem ${n}.</span>${it.descripcion_item}</td>
                    <td class="text-end">S/ ${money(it.monto_item)}</td>`;
              $tbody.appendChild(tr);
            });
          } else {
            $tbody.innerHTML = '<tr><td class="text-muted" colspan="2">Sin ítems</td></tr>';
          }


          $link.href = `segmentacion.php?id=${id}`;
        } catch (err) {
          console.error('[ver] error:', err);
          if (window.Swal) Swal.fire({ icon: 'error', title: 'No se pudo cargar', text: String(err.message || err) });
          else alert('No se pudo cargar: ' + String(err.message || err));
        } finally {
          $load.classList.add('d-none');
        }
      });
    });
  </script>

  <!-- ********************************* JS EDITAR  ********************************* -->
  <script>

    document.addEventListener('DOMContentLoaded', function () {
      const modalEditEl = document.getElementById('modalEdit');
      if (typeof bootstrap === 'undefined') return;
      const modalEdit = new bootstrap.Modal(modalEditEl);

      // Campos base
      const $id = document.getElementById('ed-id');
      const $ref = document.getElementById('ed-ref-pac');
      const $cmn = document.getElementById('ed-cmn'); // NUEVO
      const $obj = document.getElementById('ed-objeto');
      const $tp = document.getElementById('ed-tipo-proceso');
      const $des = document.getElementById('ed-descripcion');

      // Ya no existe ed-porcentaje, así que eliminamos esa referencia
      const $pctHidden = { value: 0 }; // dummy para evitar errores
      const $pctView = document.getElementById('ed-porcentaje-view');
      $pctView?.setAttribute('readonly', 'readonly');

      $pctView?.setAttribute('readonly', 'readonly');

      // KPIs
      const $cua = document.getElementById('ed-cuantia');
      const $sum = document.getElementById('ed-suma-items');

      // Categorías / resultado
      const $ccat = document.getElementById('ed-cuantia-cat');
      const $ries = document.getElementById('ed-riesgo');
      const $res = document.getElementById('ed-resultado');

      // Otros
      const $prog = document.getElementById('ed-programado');
      const $fec = document.getElementById('ed-fecha');
      const $btnSave = document.getElementById('btnEditSave');

      // Ítems (solo lectura dentro de Editar)
      const $cardItems = document.getElementById('ed-items-card');
      const $tbItems = document.getElementById('ed-items-tbody');


      // ===================== CAMPOS DE FACTORES =====================
      const $fDesierto = document.getElementById('ed-declarado-desierto');
      const $fPostores = document.getElementById('ed-pocos-postores');
      const $fMercado = document.getElementById('ed-mercado-limitado');



      // Riesgo desde factores (valores "Si" / "No" tal como vienen del <select>)
      function calcRiesgoFromFactors() {
        const haySi = [$fDesierto?.value, $fPostores?.value, $fMercado?.value]
          .some(v => String(v || 'No').toLowerCase() === 'si');
        return haySi ? 'Alto' : 'Bajo';
      }


      // ✅ Detecta si hay ítems válidos en la tabla
      function hayItemsValidos() {
        let ok = false;
        $tbItems.querySelectorAll('tr').forEach(tr => {
          const d = tr.querySelector('.ed-item-desc')?.value?.trim() || '';
          const m = parseFloat(tr.querySelector('.ed-item-monto')?.value || '0');
          if (d !== '' && m > 0) ok = true;
        });
        return ok;
      }

      // Bloqueo de cuantía si hay ítems válidos
      function toggleCuantiaByItems() {
        if (hayItemsValidos()) {
          $cua.value = $sum.value;
          $cua.setAttribute('readonly', 'readonly');
          $cua.classList.add('bg-light');
        } else {
          $cua.removeAttribute('readonly');
          $cua.classList.remove('bg-light');
        }
      }


      // Actualiza riesgo + categoría + resultado
      function refreshRiskAndResult() {
        // Riesgo depende solo de factores
        $ries.value = calcRiesgoFromFactors();
        $ries.setAttribute('disabled', 'disabled');

        // 🔒 Cuantía (Alta/Baja) no se recalcula aquí, se mantiene la que proviene de BD
        $ccat.setAttribute('disabled', 'disabled');

        // Resultado siempre sale de la matriz oficial
        refreshResultado();
      }

      // Eventos factores
      [$fDesierto, $fPostores, $fMercado].forEach(sel => {
        sel?.addEventListener('change', refreshRiskAndResult);
      });

      // Si cuantía cambia sin ítems → recalcular
      $cua?.addEventListener('input', () => {
        toggleCuantiaByItems();
        refreshRiskAndResult();

      });

      function afterItemsChanged() {
        recalcItemsSum();        // recalcula suma y puede tocar cuantía
        toggleCuantiaByItems();  // bloquear o liberar cuantía según haya ítems
        recalcPctFromCuantia();  // 🔴 actualiza %PAC con la nueva cuantía
        refreshRiskAndResult();  // recalcular riesgo + resultado
      }


      // === Reemplaza tu itemRowEditable por esta versión (recibe idx) ===
      function itemRowEditable(it, idx) {
        const id = Number(it?.id || 0);
        const desc = (it?.descripcion_item || '').replace(/"/g, '&quot;');
        const monto = Number(it?.monto_item || 0).toFixed(2);
        const ord = Number(it?.orden || (idx ?? 0) + 1);

        return `
    <tr>
      <td style="width:70%">
        <input type="hidden" name="ed_items_id[]" value="${id}">
        <input type="hidden" name="ed_items_orden[]" value="${ord}">
        <div class="input-group">
          <span class="input-group-text item-no">Ítem ${ord}</span>
          <input type="text" class="form-control ed-item-desc" name="ed_items_desc[]" value="${desc}" placeholder="Descripción del ítem">
        </div>
      </td>
      <td style="width:25%">
        <!-- AQUÍ el cambio: min=0.01 -->
        <input type="number" step="0.01" min="0.01" class="form-control ed-item-monto text-end" name="ed_items_monto[]" value="${monto}">
      </td>
      <td style="width:5%" class="text-end">
        <button type="button" class="btn btn-sm btn-outline-danger ed-item-del" title="Eliminar ítem">
          <i class="bi bi-trash"></i>
        </button>
      </td>
    </tr>
  `;
      }


      // Renumera etiquetas y el hidden ed_items_orden[] en todas las filas
      function renumerarItems() {
        let i = 1;
        $tbItems.querySelectorAll('tr').forEach(tr => {
          const badge = tr.querySelector('.item-no');
          const hOrden = tr.querySelector('input[name="ed_items_orden[]"]');
          if (badge) badge.textContent = `Ítem ${i}`;
          if (hOrden) hOrden.value = i;
          i++;
        });
      }
      // --- Validación: si hay descripción debe haber monto (>0) y viceversa ---

      function validateItemsRequiredFields({ showAlert = true } = {}) {
        let ok = true, firstBad = null;

        // Recorre solo filas reales (con inputs)
        $tbItems.querySelectorAll('tr').forEach(tr => {
          const $d = tr.querySelector('.ed-item-desc');
          const $m = tr.querySelector('.ed-item-monto');

          // Si no hay inputs, es la fila "Sin ítems" => ignorar
          if (!$d && !$m) return;

          const d = ($d.value || '').trim();
          const m = parseFloat($m.value || '0');

          $d.classList.remove('is-invalid');
          $m.classList.remove('is-invalid');

          // Uno sin el otro => inválido
          if ((d === '' && m > 0) || (d !== '' && m <= 0)) {
            ok = false;
            if (d === '') $d.classList.add('is-invalid');
            if (m <= 0) $m.classList.add('is-invalid');
            if (!firstBad) firstBad = $d || $m;
          }
        });

        if (!ok && showAlert) {
          Swal.fire({
            icon: 'warning',
            title: 'Completa los ítems',
            text: 'Si agregas un ítem, debes ingresar descripción y un monto > 0 o eliminar la fila.'
          });
          firstBad?.scrollIntoView({ behavior: 'smooth', block: 'center' });
          firstBad?.focus();
        }
        return ok;
      }

      // ====== BLOQUE NUEVO: edición de ítems ======
      let deletedIds = [];

      function ensureDeletedInput() {
        let h = document.querySelector('#ed-items-deleted');
        if (!h) {
          h = document.createElement('input');
          h.type = 'hidden';
          h.name = 'ed_items_delete_csv';
          h.id = 'ed-items-deleted';
          document.getElementById('formEdit').appendChild(h);
        }
        return h;
      }

      function recalcItemsSum() {
        let total = 0;
        $tbItems.querySelectorAll('tr').forEach(tr => {
          const d = (tr.querySelector('.ed-item-desc')?.value || '').trim();
          const m = parseFloat(tr.querySelector('.ed-item-monto')?.value || '0');
          if (d !== '' && m > 0) total += m;
        });

        $sum.value = total.toFixed(2);

        if (total > 0) {
          $cua.value = $sum.value;
          $cua.readOnly = true;
          $cua.classList.add('bg-light');
        } else {
          $cua.readOnly = false;
          $cua.classList.remove('bg-light');
        }

      }





      // Escucha cambios en descripción o monto de los ítems
      $tbItems.addEventListener('input', (ev) => {
        if (ev.target.matches('.ed-item-monto, .ed-item-desc')) {
          validateItemsRequiredFields({ showAlert: false });
          afterItemsChanged();  // << AQUÍ
        }
      });

      // (opcional, por si cambian el número con flechitas o al salir del campo)
      $tbItems.addEventListener('change', (ev) => {
        if (ev.target.matches('.ed-item-monto, .ed-item-desc')) {
          afterItemsChanged();
        }
      });


      // Eliminar fila de ítem
      $tbItems.addEventListener('click', (ev) => {
        const btn = ev.target.closest('.ed-item-del');
        if (!btn) return;
        const tr = btn.closest('tr');
        const idVal = Number(tr.querySelector('input[name="ed_items_id[]"]').value || 0);
        if (idVal > 0) {
          deletedIds.push(idVal);
          ensureDeletedInput().value = deletedIds.join(',');
        }
        tr.remove();
        renumerarItems();
        afterItemsChanged();

      });


      // Botón "Agregar ítem" (delegado en el card)
      $cardItems?.addEventListener('click', (ev) => {
        const btn = ev.target.closest('.btn-add-item');
        if (!btn) return;

        // Quita "Sin ítems…" si existe
        $tbItems.querySelector('.empty-row')?.remove();

        // Cuenta filas reales (inputs)
        const idx = $tbItems.querySelectorAll('tr .ed-item-desc').length; // 0-based

        // Inserta nueva fila
        $tbItems.insertAdjacentHTML(
          'beforeend',
          itemRowEditable({ id: 0, descripcion_item: '', monto_item: 0, orden: idx + 1 }, idx)
        );

        renumerarItems();
        afterItemsChanged();

      });



      // ====== FIN BLOQUE NUEVO ======


      function money(n) {
        return (Number(n) || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      }

      // Reglas oficiales
      function calcResultado(cuantiaCat, riesgoCat) {
        const c = String(cuantiaCat || '').toLowerCase();
        const r = String(riesgoCat || '').toLowerCase();
        if (!c || !r) return '';
        if (c === 'baja' && r === 'bajo') return 'Rutinario';
        if (c === 'baja' && r === 'alto') return 'Crítico';
        if (c === 'alta' && r === 'bajo') return 'Operacional';
        if (c === 'alta' && r === 'alto') return 'Estratégico';
        return '';
      }
      function refreshResultado() {
        $res.value = calcResultado($ccat.value, $ries.value) || '';
      }
      $ccat.addEventListener('change', refreshResultado);
      $ries.addEventListener('change', refreshResultado);

      // % del PAC desde cuantía y total del año (inyectado arriba)
      function recalcPctFromCuantia() {
        const base = Number(window.TOTAL_PAC_FILTRO || 0);
        const cuantia = Number($cua?.value || 0);
        const pct = (base > 0 && cuantia >= 0)
          ? (cuantia / base) * 100
          : 0;

        const pct2 = pct.toFixed(2);     // 2 decimales
        // Ya no guardamos % en input oculto (se calcula en backend)
        $pctView.value = pct2 + '%';

      }


      $cua?.addEventListener('input', recalcPctFromCuantia);
      $cua?.addEventListener('change', recalcPctFromCuantia);


      function resetEdit() {
        $cardItems?.classList.add('d-none');
        if ($tbItems) $tbItems.innerHTML = '';
        deletedIds = [];
        const delH = document.getElementById('ed-items-deleted');
        if (delH) delH.value = '';
      }




      // === ABRIR MODAL EDITAR SEGMENTACIÓN ===
      document.addEventListener('click', async (ev) => {
        const btn = ev.target.closest('.js-edit');
        if (!btn) return;

        const id = parseInt(btn.dataset.id || '0', 10);
        if (!id) return;

        try {
          // 🔹 Mostrar loader sin bloquear el hilo asíncrono
          Swal.fire({
            title: 'Cargando...',
            text: 'Obteniendo información del procedimiento',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
          });

          // 🔹 Llamar al backend
          const res = await fetch(`../controlador/segmentacion_detalle.php?id=${id}`, { credentials: 'same-origin' });
          const data = await res.json();

          // 🔹 Cerrar loader
          Swal.close();

          if (!data.ok) {
            Swal.fire('Error', data.msg || 'No se encontró información del procedimiento.', 'error');
            return;
          }

          // 🔹 Limpiar el formulario solo una vez (ahora sí en el lugar correcto)
          resetEdit();

          // ===============================
          // 🔹 Cargar datos en el modal
          // ===============================
          const s = data.data.cabecera || {};
          const items = Array.isArray(data.data.items) ? data.data.items : [];

          // Cabecera
          $id.value = id;
          $ref.value = s.ref_pac ?? '';
          $cmn.value = s.cmn ?? ''; // NUEVO
          $obj.value = s.objeto_contratacion ?? 'BIENES';
          $tp.value = s.tipo_proceso_id ? String(s.tipo_proceso_id) : '';
          $des.value = s.descripcion ?? '';

          // Cuantía y suma de ítems (según cabecera)
          $cua.value = s.cuantia ? parseFloat(s.cuantia).toFixed(2) : '0.00';
          $sum.value = s.suma_items ? parseFloat(s.suma_items).toFixed(2) : '0.00';

          // 🔴 Recalcular SIEMPRE el % del PAC con el Total PAC actual
          // (usa window.TOTAL_PAC_FILTRO y la función recalcPctFromCuantia())
          recalcPctFromCuantia();

          // Categorías y factores de riesgo
          $ccat.value = s.cuantia_categoria ?? '';
          $ries.value = s.riesgo_categoria ?? '';
          $fDesierto.value = (s.declarado_desierto === 'Si') ? 'Si' : 'No';
          $fPostores.value = (s.pocos_postores === 'Si') ? 'Si' : 'No';
          $fMercado.value = (s.mercado_limitado === 'Si') ? 'Si' : 'No';

          // 🔴 Recalcula riesgo + resultado en función de cuantía + factores
          refreshRiskAndResult();   // esto ya llama internamente a refreshResultado()

          $prog.value = s.programado ? '1' : '0';
          $fec.value = (s.fecha || '').substring(0, 10);

          // 🔹 Cargar ítems del procedimiento
          $cardItems.classList.remove('d-none');
          const arr = (items || []).sort(
            (a, b) => (a.orden || 999) - (b.orden || 999) || a.id - b.id
          );

          if (arr.length) {
            $tbItems.innerHTML = arr.map((it, i) => itemRowEditable(it, i)).join('');
          } else {
            // Fila solo visual, no se envía nada
            $tbItems.innerHTML = `
        <tr class="empty-row">
          <td colspan="3" class="text-muted text-center py-2">Sin ítems registrados</td>
        </tr>
      `;
          }

          // 🔴 Ahora que los ítems ya están en la tabla, sincronizamos todo:
          afterItemsChanged();

          // Mostrar el modal ya consistente
          modalEdit.show();

        } catch (err) {
          // 🔹 Manejo de error general
          Swal.close();
          console.error('Error al cargar procedimiento:', err);
          Swal.fire({
            icon: 'error',
            title: 'Error al cargar',
            text: String(err.message || err),
          });
        }
      });



      // Guardar
      $btnSave.addEventListener('click', async (e) => {
        e.preventDefault();
        if (!validateItemsRequiredFields({ showAlert: true })) return;

        // recalculamos %PAC y riesgo/resultado ANTES de enviar

        refreshRiskAndResult();          // 🔴 IMPORTANTE

        const fd = new FormData(document.getElementById('formEdit'));

        // --- Señal para coherencia...
        let hayItems = false;
        $tbItems.querySelectorAll('tr').forEach(tr => {
          const desc = tr.querySelector('.ed-item-desc')?.value?.trim() || '';
          const mon = parseFloat(tr.querySelector('.ed-item-monto')?.value || '0');
          if (desc !== '' && mon > 0) hayItems = true;
        });
        fd.set('sync_cuantia_from_items', hayItems ? '1' : '0');

        const delH = document.getElementById('ed-items-deleted');
        if (delH && delH.value) fd.append('ed_items_delete_csv', delH.value);

        fd.set('ref_pac', ($ref.value || '').trim());
        fd.set('objeto_contratacion', $obj.value);

        const tpVal = parseInt($tp.value, 10);
        fd.set('tipo_proceso_id', Number.isFinite(tpVal) && tpVal > 0 ? String(tpVal) : '');

        fd.set('descripcion', ($des.value || '').trim());

        // 🔴 Estos tres NO viajan porque los <select> están disabled,
        // así que los forzamos aquí:
        fd.set('cuantia_categoria', $ccat.value || '');
        fd.set('riesgo_categoria', $ries.value || '');
        fd.set('resultado_segmentacion', $res.value || '');


        try {
          const r = await fetch('../controlador/segmentacion_update_campos.php', {
            method: 'POST', body: fd, credentials: 'same-origin'
          });
          const j = await r.json();
          if (!j.ok) throw new Error(j.msg || 'No se pudo guardar');

          Swal.fire({
            icon: 'success', title: 'Guardado',
            text: 'Los cambios se guardaron correctamente',
            timer: 1800, timerProgressBar: true, showConfirmButton: false
          });
          modalEdit.hide();

          setTimeout(() => {
            const form = document.getElementById('frmFiltros');
            if (form?.requestSubmit) form.requestSubmit(); else location.reload();
          }, 2000);
        } catch (err) {
          console.error(err);
          Swal.fire({ icon: 'error', title: 'Error al guardar', text: String(err.message || err) });
        }
      });
    });
  </script>


  <style>
    /* Asegurar visualización */
    .page-bar {
      overflow: visible !important;
    }

    .dropdown-menu {
      z-index: 2000 !important;
    }

    /* Clase show manual por si BS no la aplica */
    .dropdown-menu.show-manual {
      display: block !important;
      position: absolute;
      inset: 0px 0px auto auto;
      margin: 0px;
      transform: translate(0px, 40px);
    }
  </style>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      // 1. Tooltips (esto sí funciona con BS)
      if (typeof bootstrap !== 'undefined') {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el, { offset: [0, 52] }));
      }

      // Se eliminó la solución manual conflictiva del dropdown.
      // Bootstrap 5 (cargado en el footer.php) maneja el data-bs-toggle="dropdown" nativamente.
    });
  </script>

  <script>
    // Flash messages ?ok=1 / ?err=...
    <?php
    $flashOK = isset($_GET['ok']);
    $flashErr = $_GET['err'] ?? '';
    $msgs = [
      'badid' => 'ID inválido',
      'notfound' => 'El registro no existe',
      'fk' => 'No se puede eliminar: tiene datos relacionados',
      'csrf' => 'Token inválido',
      'ex' => 'Ocurrió un error inesperado',
    ];
    $errMsg = $msgs[$flashErr] ?? 'Error';
    ?>
      (function () {
        const ok = <?= $flashOK ? 'true' : 'false' ?>;
        const err = <?= json_encode($flashErr) ?>;
        if (ok) {

          // SweetAlert2 ya está cargado arriba
          Swal.fire({ icon: 'success', title: 'El registro se eliminó correctamente', timer: 2200, confirmButtonColor: '#198754' });
        } else if (err) {
          Swal.fire({ icon: 'error', title: <?= json_encode($errMsg) ?>, timer: 2800, confirmButtonColor: '#dc3545' });
        }
        // Limpia ok/err de la URL
        const url = new URL(window.location.href);
        if (url.searchParams.has('ok') || url.searchParams.has('err')) {
          url.searchParams.delete('ok'); url.searchParams.delete('err');
          window.history.replaceState({}, '', url.pathname + (url.searchParams.toString() ? ('?' + url.searchParams.toString()) : ''));
        }
      })();
  </script>

  <script>


    // === Botón Eliminar con SweetAlert2 (robusto, único) ===
    (function () {
      // Evita que el <i> del ícono “se trague” el click
      const style = document.createElement('style');
      style.textContent = '.btn-icon i{pointer-events:none !important;}';
      document.head.appendChild(style);

      function onDeleteClick(ev) {
        const btn = ev.target.closest('.js-del');
        if (!btn) return;

        ev.preventDefault();
        ev.stopPropagation();

        const href = btn.dataset.href || btn.getAttribute('data-href');
        if (!href) {
          console.warn('[DEL] Sin data-href en el botón.');
          return;
        }

        // Fallback si por algo el CDN no cargó
        if (typeof Swal === 'undefined') {
          if (confirm('¿Eliminar este procedimiento? Esta acción no se puede deshacer.')) {
            window.location.assign(href);
          }
          return;
        }

        Swal.fire({
          title: '¿Eliminar este procedimiento?',
          text: 'Esta acción no se puede deshacer.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Sí, eliminar',
          cancelButtonText: 'Cancelar',
          confirmButtonColor: '#dc3545',
          cancelButtonColor: '#6c757d',
          reverseButtons: true
        }).then(res => {
          if (res.isConfirmed) window.location.assign(href);
        });
      }

      // Fase de captura para adelantarnos a otros listeners
      document.addEventListener('click', onDeleteClick, true);
    })();
  </script>


  <!-- === FIX del modal oscuro === -->
  <script>
    const modalElFix = document.getElementById('modalVer');
    if (modalElFix) {
      modalElFix.addEventListener('hidden.bs.modal', () => {
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('padding-right');
        document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
      });
    }
  </script>


  <!-- === Auto–filtrado === -->
  <!-- === Auto–filtrado === -->
  <script>
    function debounce(fn, ms) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn.apply(this, a), ms); }; }

    document.addEventListener('DOMContentLoaded', () => {
      const form = document.getElementById('frmFiltros');
      if (!form) return;

      const performAjaxSearch = async () => {
        // 1. Refresca métricas superiores
        if (typeof refreshMetrics === 'function') refreshMetrics();
        
        try {
          const qs = new URLSearchParams(new FormData(form)).toString();
          
          // Efecto visual de carga suavizado
          const tableContainer = document.querySelector('.tabla-scroll');
          if (tableContainer) tableContainer.style.opacity = "0.5";
          
          const response = await fetch('segmentacion_listado.php?' + qs);
          if (!response.ok) throw new Error('Network err');
          
          const html = await response.text();
          const doc = new DOMParser().parseFromString(html, 'text/html');
          
          // 2. Reemplazar la tabla dinámicamente
          const newTable = doc.querySelector('.tabla-scroll');
          if (newTable && tableContainer) {
            tableContainer.innerHTML = newTable.innerHTML;
          }
          
          // 3. Reemplazar el contenedor de paginación
          const newPag = doc.querySelector('.table-pagination');
          const oldPag = document.querySelector('.table-pagination');
          if (newPag && oldPag) {
              oldPag.innerHTML = newPag.innerHTML;
          } else if (newPag && !oldPag) {
              tableContainer.insertAdjacentHTML('afterend', newPag.outerHTML);
          } else if (!newPag && oldPag) {
              oldPag.remove();
          }

          if (tableContainer) tableContainer.style.opacity = "1";
          
          // Rehabilitar tooltips bootstrap de la nueva tabla
          if (typeof bootstrap !== 'undefined') {
              const ttList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
              [...ttList].forEach(el => new bootstrap.Tooltip(el));
          }
          
        } catch (err) {
          console.warn('AJAX filter failed, falling back:', err);
          if (form.requestSubmit) form.requestSubmit(); else form.submit();
        }
      };

      // Disparador del live search/ajax con un delay más rápido
      const autoSubmit = debounce(performAjaxSearch, 250);

      // Detecta cambios en el select y numero (Año, Programado, etc)
      form.addEventListener('change', (ev) => {
        const el = ev.target;
        if (!el || !el.name) return;
        if (el.tagName === 'SELECT' || el.type === 'number') autoSubmit();
      });

      // BÚSQUEDA SENSIBLE: Al tipear detecta los cambios automáticamente
      const q = form.querySelector('input[name="q"]');
      q?.addEventListener('input', () => {
        autoSubmit(); // Aplica búsqueda sin usar Enter
      });
      // Bloquear Enter normal para evitar submits duplicados y recargas completas
      q?.addEventListener('keydown', (ev) => {
        if (ev.key === 'Enter') {
          ev.preventDefault();
          autoSubmit();
        }
      });
      
      form.addEventListener('submit', (ev) => {
         ev.preventDefault();
         autoSubmit();
      });
    });
  </script>


  <!-- Scripts al final, justo antes de </body> -->
  <script>
    function fmt(n) { return Number(n || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

    async function refreshMetrics() {
      try {
        const form = document.getElementById('frmFiltros');
        const qs = new URLSearchParams(new FormData(form));

        const r = await fetch('../controlador/segmentacion_metricas.php?' + qs.toString(), { cache: 'no-store' });
        const j = await r.json();
        if (!j.ok) return;

        const d = j.data || {};
        const $ = id => document.getElementById(id);

        // Header: PAC real del año
        if ($('m-total-pac')) $('m-total-pac').textContent = fmt(d.total_pac_calc);
        if ($('m-pac-10')) $('m-pac-10').textContent = fmt(d.pac_10_calc);

        // Chips de la consulta (respetan filtros)
        const countEl = $('m-count');
        const sumEl = $('m-sum');
        
        if (countEl) countEl.textContent = (d.cantidad || 0);
        if (sumEl) sumEl.textContent = fmt(d.total_cuantia);

        // Base para % del PAC en el modal de edición
        window.TOTAL_PAC_FILTRO = Number(d.total_pac_calc || 0);
      } catch (e) { console.error('refreshMetrics', e); }
    }

    document.addEventListener('DOMContentLoaded', refreshMetrics);
    window.addEventListener('message', (ev) => {
      if (ev?.data?.type === 'SEGMENTACION_GUARDADA') {
        refreshMetrics();
      }
    });
    ['change', 'input'].forEach(evt => {
      document.getElementById('frmFiltros')?.addEventListener(evt, () => refreshMetrics(), true);
    });


  </script>

  <script>
    (function () {
      const money = n => (Number(n) || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      const esc = s => String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

      // Carga una sola vez cuando se abre el collapse
      document.addEventListener('click', async (ev) => {
        const btn = ev.target.closest('.js-items');
        if (!btn) return;

        // Manual toggle logic
        const targetId = btn.getAttribute('data-bs-target');
        const targetEl = document.querySelector(targetId);
        if (targetEl && window.bootstrap) {
          const bsc = bootstrap.Collapse.getOrCreateInstance(targetEl, { toggle: false });
          bsc.toggle();
        }

        const id = parseInt(btn.dataset.id || '0', 10);
        const host = document.querySelector('#items-' + id + ' .items-host');
        if (!id || !host) return;

        // Cache: si ya se cargó, no vuelvas a pedir
        if (host.dataset.loaded === '1') return;

        try {
          const r = await fetch(`../controlador/segmentacion_detalle.php?id=${id}`, { credentials: 'same-origin' });
          const j = await r.json();
          if (!j.ok) throw new Error(j.msg || 'Error');

          const items = (j.data?.items || []).slice().sort((a, b) => (a.orden || 999) - (b.orden || 999) || a.id - b.id);

          if (!items.length) {
            host.innerHTML = '<div class="text-muted">Sin ítems.</div>';
            host.dataset.loaded = '1';
            return;
          }

          const rows = items.map((it, idx) => {
            const n = (Number(it.orden) || 0) > 0 ? Number(it.orden) : (idx + 1);
            return `<tr>
          <td class="text-center">${n}</td>
          <td>${esc(it.descripcion_item)}</td>
          <td class="text-end">S/ ${money(it.monto_item)}</td>
        </tr>`;
          }).join('');

          const total = items.reduce((a, b) => a + (Number(b.monto_item) || 0), 0);

          host.innerHTML = `
  <div class="d-flex justify-content-between align-items-center mb-2">
    <strong>Ítems del procedimiento</strong>
    <div>
        <a class="btn btn-sm btn-outline-secondary js-view me-1" data-id="${id}" data-url="../controlador/segmentacion_detalle.php">
          <i class="bi bi-eye me-1"></i>Ver detalle
        </a>
        <button type="button" class="btn btn-sm btn-outline-danger js-close-collapse" data-bs-target="#items-${id}">
            <i class="bi bi-x-lg me-1"></i> Cerrar
        </button>
    </div>
  </div>
  
  <div class="table-responsive">
     <table class="table table-sm table-modern items-table align-middle mb-0">

      <thead>
        <tr>
          <th style="width:80px" class="text-center">#</th>
          <th>Descripción</th>
          <th class="text-end" style="width:180px">Monto (S/.)</th>
        </tr>
      </thead>
      <tbody>${rows}</tbody>
      <tfoot>
        <tr>
          <th colspan="2" class="text-end">Total ítems</th>
          <th class="text-end">S/ ${money(total)}</th>
        </tr>
      </tfoot>
    </table>
  </div>
`;


          host.dataset.loaded = '1';
        } catch (e) {
          host.innerHTML = `<div class="text-danger">No se pudieron cargar los ítems: ${String(e.message || e)}</div>`;
        }
      });

      document.addEventListener('click', (ev) => {
        const btn = ev.target.closest('.js-close-collapse');
        if (!btn) return;
        const targetId = btn.getAttribute('data-bs-target');
        const targetEl = document.querySelector(targetId);
        if (targetEl && window.bootstrap) {
          const bsCollapse = bootstrap.Collapse.getOrCreateInstance(targetEl);
          bsCollapse.hide();
        }
      });
    })();
  </script>

  <script>
    // Cada vez que cambie la página, vuelve a pedir métricas actualizadas
    document.addEventListener('DOMContentLoaded', () => {
      const pagLinks = document.querySelectorAll('.pagination .page-link');
      pagLinks.forEach(a => {
        a.addEventListener('click', () => {
          setTimeout(() => {
            if (typeof refreshMetrics === 'function') {
              refreshMetrics();   // 🔥 ACTUALIZA TOTAL PAC
            }
          }, 400);
        });
      });
    });
  </script>

  <!-- MODAL IMPORTAR EXCEL -->
  <div class="modal fade" id="modalImportar" tabindex="-1" aria-labelledby="modalImportarLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header text-white" style="background-color: #732d91;">
          <h5 class="modal-title" id="modalImportarLabel"><i class="bi bi-file-earmark-arrow-up-fill me-2"></i>Importar desde Excel</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-info">
            <strong><i class="bi bi-info-circle-fill me-1"></i> Instrucciones:</strong><br>
            1. Descargue la plantilla Excel y llénela respetando las columnas.<br>
            2. No modifique los encabezados originales (Fila 5). Agregue la información desde la fila 6.<br>
            3. Si un Número de PAC (N° REF PAC) ya existe <b>en el mismo año</b>, será omitido (anti-duplicados).
          </div>
          <div class="mb-3 d-flex justify-content-between align-items-center">
            <span>¿No tiene la plantilla?</span>
            <a href="../controlador/segmentacion_plantilla_excel.php" target="_blank" class="btn btn-outline-info btn-sm">
              <i class="bi bi-download"></i> Descargar Plantilla .xlsx
            </a>
          </div>
          
          <form id="formImportarExcel">
            <div class="mb-3">
              <label for="excelFile" class="form-label fw-bold">Seleccionar archivo Excel (.xlsx, .xls)</label>
              <input class="form-control" type="file" id="excelFile" accept=".xlsx, .xls" required>
            </div>
            <div class="d-grid gap-2 text-center mt-4">
              <button type="submit" class="btn btn-primary" id="btnImportarSubmit" style="background-color: #8e44ad; border-color: #732d91;">
                <i class="bi bi-cloud-upload-fill me-1"></i> Importar Datos
              </button>
            </div>
          </form>

          <!-- Zona de resultados -->
          <div id="importResultArea" class="mt-4 d-none">
            <div class="alert alert-success d-flex align-items-center mb-2" role="alert">
               <i class="bi bi-check-circle-fill me-2 fs-4"></i>
               <div><span class="fw-bold">Importación completada.</span> Por favor revise el resumen.</div>
            </div>
            
            <table class="table table-bordered table-sm mb-2">
              <tr class="table-light"><th style="width: 50%;">Filas Procesadas (Nuevas)</th><td id="resProcesadas">-</td></tr>
              <tr class="table-light"><th>Filas Omitidas (Duplicados o vacíos)</th><td id="resOmitidas">-</td></tr>
              <tr class="table-danger"><th>Errores / Fallos</th><td id="resErrores">-</td></tr>
            </table>

            <div class="alert alert-warning p-2 small mb-0 d-none" id="logErroresCaja" style="max-height: 120px; overflow-y: auto;">
                <strong>Detalles del LOG:</strong><br>
                <div id="logErroresText"></div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", function() {
      const formImportar = document.getElementById("formImportarExcel");
      const btnSubmit = document.getElementById("btnImportarSubmit");
      const resultArea = document.getElementById("importResultArea");

      if(formImportar) {
        formImportar.addEventListener("submit", async function(e) {
          e.preventDefault();
          const fileInput = document.getElementById("excelFile");
          if (!fileInput.files.length) {
            Swal.fire("Atención", "Por favor seleccione un archivo Excel.", "warning");
            return;
          }

          const file = fileInput.files[0];
          const formData = new FormData();
          formData.append("excel_file", file);

          // UI
          const btnTextOriginal = btnSubmit.innerHTML;
          btnSubmit.disabled = true;
          btnSubmit.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Subiendo e importando...`;
          resultArea.classList.add("d-none");
          document.getElementById("logErroresCaja").classList.add("d-none");

          try {
            const resp = await fetch("../controlador/segmentacion_importar_excel.php", {
              method: "POST",
              body: formData
            });

            if(!resp.ok) throw new Error("Error en la petición: HTTP " + resp.status);
            
            const data = await resp.json();
            
            if (data.status === "success" || data.status === "warning") {
              const res = data.stats || { procesadas: 0, omitidas: 0, errores: 0 };
              document.getElementById("resProcesadas").textContent = res.procesadas;
              document.getElementById("resOmitidas").textContent = res.omitidas;
              document.getElementById("resErrores").textContent = res.errores;
              
              if(data.log && data.log.length > 0) {
                 document.getElementById("logErroresText").innerHTML = data.log.join("<br>");
                 document.getElementById("logErroresCaja").classList.remove("d-none");
              }

              resultArea.classList.remove("d-none");
              formImportar.reset();
              
              if (data.status === "warning") {
                Swal.fire({
                  icon: 'warning',
                  title: 'Importación con observaciones',
                  text: 'Hubo registros omitidos o con error. Revisa el resumen de abajo.',
                  confirmButtonColor: '#f39c12'
                });
              } else {
                Swal.fire({
                  icon: 'success',
                  title: 'Importación exitosa',
                  text: 'Todos los registros (nuevos) fueron importados correctamente.',
                  confirmButtonColor: '#27ae60'
                });
              }

              // Automáticamente recargar luego de cerrar el modal
              document.getElementById('modalImportar').addEventListener('hidden.bs.modal', function () {
                location.reload();
              }, {once: true});

            } else {
              Swal.fire("Error al importar", data.message || "Ocurrió un error desconocido.", "error");
            }
          } catch(err) {
            console.error(err);
            Swal.fire("Error crítico", err.message, "error");
          } finally {
            btnSubmit.innerHTML = btnTextOriginal;
            btnSubmit.disabled = false;
          }
        });
      }
    });
  </script>


  <!-- Scripts al final, justo antes de cerrar body -->
  </div> <!-- End page-content -->
  <?php require_once 'layout/footer.php'; ?>