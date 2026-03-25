<?php

//segmentacion_anexo.php
declare(strict_types=1);
ob_start();

use Dompdf\Dompdf;
use Dompdf\Options;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../modelo/conexion.php';
require_once __DIR__ . '/../modelo/segmentacion_util.php';

// Asegurar supresión total de errores para PDF
error_reporting(0);
ini_set('display_errors', '0');
if (ob_get_length()) ob_clean(); 

mysqli_report(MYSQLI_REPORT_OFF);

/* ------------ Parámetros ------------ */
$refPac = isset($_GET['ref_pac']) ? trim((string) $_GET['ref_pac']) : '';
if ($refPac === '') {
  http_response_code(400);
  echo 'Falta el parámetro ref_pac.';
  exit;
}

// Año (nuevo parámetro para evitar ambigüedad)
$anioParam = isset($_GET['anio']) ? (int) $_GET['anio'] : 0;

/* ------------ Utilitarios ------------ */
function money(float $n): string
{
  return 'S/ ' . number_format($n, 2, '.', ',');
}

function locateImage(string $baseNoExt): ?string
{
  $dir = realpath(__DIR__ . '/../public/images');
  if (!$dir)
    return null;
  foreach (['.png', '.jpg', '.jpeg', '.webp'] as $ext) {
    $p = $dir . '/' . $baseNoExt . $ext;
    if (is_file($p))
      return $p;
  }
  return null;
}
function embedImg(?string $absPath): ?string
{
  if (!$absPath || !is_file($absPath))
    return null;
  $ext = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));
  $mime = ($ext === 'jpg' ? 'jpeg' : $ext);
  return 'data:image/' . $mime . ';base64,' . base64_encode(file_get_contents($absPath));
}

/** Píldora por resultado */
function pillClass(string $res): string
{
  $k = mb_strtolower($res, 'UTF-8');
  if ($k === 'crítico' || $k === 'critico')
    return 'pill danger';
  if ($k === 'operacional')
    return 'pill info';
  if ($k === 'estratégico' || $k === 'estrategico')
    return 'pill warn';
  return 'pill success'; // Rutinario
}

/** Interacción con el mercado (según tabla OSCE) */
function interactionByResult(string $res): string
{
  $k = mb_strtolower($res, 'UTF-8');
  switch ($k) {
    case 'rutinario':
      return 'Indagación básica';
    case 'operacional':
      return 'Indagación avanzada';
    case 'crítico':
    case 'critico':
      return 'Consulta al mercado básica';
    case 'estratégico':
    case 'estrategico':
      return 'Consulta al mercado avanzada';
    default:
      return 'Indagación básica';
  }
}

/* Normaliza Sí/No */
function yn($v): string
{
  $v = is_null($v) ? '' : trim((string) $v);
  return preg_match('/^(1|true|si|sí|sì)$/i', $v) ? 'sí' : 'no';
}


/* ------------ Datos principales ------------ */
// Modificar la consulta para incluir filtro por año si se proporciona
$sql = "
  SELECT
    s.id,
    s.ref_pac,
    s.cmn,
    s.objeto_contratacion,
    s.descripcion,
    s.cuantia,
    s.porcentaje,
    s.cuantia_categoria,
    s.riesgo_categoria,
    s.resultado_segmentacion,
    s.programado,
    s.declarado_desierto,
    s.pocos_postores,
    s.mercado_limitado,
    s.anio,
    COALESCE(tp.nombre, '') AS tipo_proceso
  FROM segmentacion s
  LEFT JOIN tipo_proceso tp ON tp.id = s.tipo_proceso_id
  WHERE s.ref_pac = ?";

// Si se proporciona el año, agregarlo al filtro
if ($anioParam > 0) {
  $sql .= " AND s.anio = ?";
}

$sql .= " LIMIT 1";

$stmt = $conn->prepare($sql);
if ($anioParam > 0) {
  $stmt->bind_param('si', $refPac, $anioParam);
} else {
  $stmt->bind_param('s', $refPac);
}
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
  http_response_code(404);
  echo 'No se encontró el N° PAC solicitado.';
  exit;
}

/* === Totales del PAC del año (misma lógica que la pantalla) === */
$anio = (int) $row['anio'];           // año REAL del registro
$totalPAC = getTotalPac($conn, $anio);   // respeta config_pac (manual/auto)
$porcentaje10 = getDiezPorciento($conn, $anio);


/* Ítems */
$items = [];
$sumIt = 0.0;
$it = $conn->prepare("
  SELECT COALESCE(NULLIF(orden,0), id) AS ord,
         descripcion_item, monto_item
  FROM segmentacion_items
  WHERE segmentacion_id = ?
  ORDER BY ord, id");
$segId = (int) $row['id'];
$it->bind_param('i', $segId);
$it->execute();
$rsi = $it->get_result();
while ($r = $rsi->fetch_assoc()) {
  $m = (float) $r['monto_item'];
  $sumIt += $m;
  $items[] = ['n' => (int) $r['ord'], 'desc' => (string) $r['descripcion_item'], 'm' => $m];
}

/* Logos */
$logoEscudo = embedImg(locateImage('escudo'));
$logoRegpol = embedImg(locateImage('logo_regpol'));

/* Cabeceras principales solicitadas */
$hdr1 = 'REGIÓN POLICIAL LIMA';
$hdr2 = 'UNIDAD DE ADMINISTRACIÓN';
$hdr3 = 'UE009 – VII DIRTEPOL LIMA';


/* Campos del anexo */
// $anio ya está definido en línea 127 desde la BD
$cuantia = (float) $row['cuantia'];
$tipoC = (string) $row['cuantia_categoria'];
$riesgo = (string) $row['riesgo_categoria'];
$resSeg = trim((string) $row['resultado_segmentacion']);

// % del PAC para este procedimiento (valor oficial de la BD)
$porcentajePac = 0;
if ($totalPAC > 0) {
  $porcentajePac = round(($cuantia / $totalPAC) * 100, 2);
}



$d1 = yn($row['declarado_desierto'] ?? $row['quedo_desierto'] ?? '');
$d2 = yn($row['pocos_postores'] ?? '');
$d3 = yn($row['mercado_limitado'] ?? '');

$interac = mb_strtoupper(interactionByResult($resSeg), 'UTF-8');

$pillCls = pillClass($resSeg);

/* ------------ HTML / CSS (estilo técnico) ------------ */
ob_start(); ?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <style>
    @page {
      margin: 12mm 12mm;
    }

    .footer-org {
      position: fixed;
      bottom: 2mm;
      left: 0;
      right: 0;
      text-align: center;
      font-size: 8px;
      color: #5a6b7a;
      /* gris institucional suave */
      letter-spacing: .2px;
    }

    body {
      font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
      color: #000;
      /* negro nítido */
      font-size: 10px;
    }


    /* Cabecera institucional */
    .hdr {
      width: 100%;
      margin-bottom: 8px;
      border-bottom: 3px solid #0b5ea8;
      padding-bottom: 6px;
    }

    .hdr td {
      vertical-align: top;
    }

    .logo {
      width: 80px;
    }

    .logo img {
      width: 64px;
      height: 64px;
      object-fit: contain;
    }

    .tit {
      text-align: center;
      line-height: 1.25;
    }

    .tit .l1 {
      font-size: 14px;
      font-weight: 800;
    }

    .tit .l2 {
      font-size: 11px;
      font-weight: 700;
    }

    .tit .l3 {
      font-size: 11px;
      font-weight: 700;
      letter-spacing: .2px;
    }

    .tit .l4 {
      font-size: 9px;
      font-weight: 700;
      color: #3b4a5e;
      margin-top: 2px;
    }

    .doc-title {
      text-align: center;
      font-weight: 800;
      font-size: 13px;
      margin: 10px 0 2px;
    }

    .doc-sub {
      text-align: center;
      color: #4b5f76;
      margin-bottom: 8px;
    }

    /* Tabla base */
    table {
      width: 100%;
      border-collapse: collapse;
    }

    .blk thead th {
      background: #0b5ea8;
      color: #fff;
      padding: 6px 6px;
      font-size: 10px;
      text-align: left;
      border: 1.8px solid #0b5ea8;
    }

    .blk td {
      border: 1.2px solid #cfe1f5;
      padding: 6px 6px;
      vertical-align: middle;
    }

    .blk .lbl {
      background: #e9f1fb;
      font-weight: 700;
      width: 24%;
    }

    /* Valores en tabla principal (sin negrita, legible, OSCE/Contraloría) */
    /* Valores (contenido) en mayúsculas, normal y negro parejo */

    .blk .value {
      font-weight: 400 !important;
      text-transform: uppercase !important;
      /* <- antes estaba uppercase */
      color: #000;
      line-height: 1.35;
      padding-top: 5px;
      padding-bottom: 5px;
    }

    .no-upper {
      text-transform: none !important;
    }

    /* Etiquetas (títulos de campo) más sobrias */
    .blk .lbl {
      background: #e9f1fb;
      font-weight: 600;
      color: #0b456a;
    }

    /* CABECERA de ÍTEMS → sí debe ser fuerte y centrada */
    .it thead th {
      background: #0b5ea8 !important;
      color: #fff !important;
      font-weight: 700 !important;
      /* ← negrita SOLO aquí */
      text-align: center;
      border: 1.8px solid #0b5ea8;
      padding: 6px;
    }

    /* CUERPO de ÍTEMS → texto normal en negro uniforme */
    .it tbody td {
      font-weight: 400 !important;
      color: #000 !important;
      border: 1.2px solid #cfe1f5;
      padding: 6px;
    }

    /* Alineaciones correctas */
    .it tbody td:nth-child(1),
    .it thead th:nth-child(1) {
      text-align: center;
      width: 7%;
    }

    .it tbody td:nth-child(2),
    .it thead th:nth-child(2) {
      text-align: left;
    }

    .it tbody td:nth-child(3),
    .it thead th:nth-child(3) {
      text-align: right;
      width: 22%;
    }

    /* Zebra sobria */
    .it tbody tr:nth-child(even) td {
      background: #f7fbff;
    }

    /* Sección: título de la tabla de ítems */
    /* Sección título de ítems */
    .sec-title {
      background: #0b5ea8;
      color: #fff;
      font-weight: 700;
      padding: 4px 8px;
      /* ↓ más bajo */
      font-size: 10.5px;
      /* ↑ un poco */
      text-align: left;
      border-radius: 3px 3px 0 0;
      margin-top: 6px;
      margin-bottom: 0;
    }

    /* Tabla de ítems */
    .it {
      width: 100%;
      border-collapse: collapse;
    }

    /* Cabecera (#, descripción, monto) */
    .it thead th {
      background: #0b5ea8;
      /* azul institucional consistente */
      color: #fff;
      font-weight: 600;
      /* no tan pesado */
      font-size: 10.2px;
      /* +1 punto aprox */
      padding: 4px 6px;
      /* ↓ altura */
      border-bottom: 1px solid #ffffffaa;
      /* línea blanca suave */
      text-align: center;
    }

    /* Cuerpo */
    .it tbody td {
      font-size: 9.8px;
      color: #000;
      padding: 5px 6px;
      /* ↓ un poco */
      border: 1px solid #d7e6f6;
    }

    /* Zebra */
    .it tbody tr:nth-child(even) td {
      background: #f7fbff;
    }

    /* Fila Total Ítems con borde completo y fondo destacado */
    .it tfoot td {
      background: #e8f1fb !important;
      font-weight: 700;
      color: #000;
      border: 1.5px solid #92bce8 !important;
      /* ← Borde completo */
      border-top: 2.4px solid #0b5ea8 !important;
      /* ← Resalta separación */
    }

    /* Texto de Total centrado en la celda intermedia */
    .it tfoot td:nth-child(2) {
      text-align: center;
    }

    /* Monto alineado a la derecha */
    .it tfoot td:last-child {
      text-align: right;
    }


    /* Celda de la etiqueta TOTAL ÍTEMS centrada */
    .it tfoot td.total-label {
      text-align: center !important;
    }

    /* Celda del monto TOTAL ÍTEMS alineada a la derecha */
    .it tfoot td.total-value {
      text-align: right !important;
    }


    /* Alineaciones */
    .center {
      text-align: center;
    }

    .right {
      text-align: right;
    }

    /* Firma institucional centrada */
    .sign {
      margin-top: 30mm;
      text-align: center;
    }

    .sign .line {
      width: 55%;
      margin: 0 auto 6px;
      border-bottom: 1.4px dashed #2c3a47;
      /* ← Línea punteada */
      height: 12px;
    }


    .sign .lbl {
      font-size: 10.5px;
      font-weight: 600;
      color: #2c3a47;
      text-transform: uppercase;
    }

    /* Píldoras visuales (Resultado de Segmentación) */
    .pill {
      display: inline-block;
      padding: 4px 12px;
      border-radius: 14px;
      font-size: 9.6px;
      font-weight: 700;
      color: #fff !important;
      text-transform: uppercase;
      /* ← ahora en mayúsculas */
      letter-spacing: 0.2px;
      /* ← mejora legibilidad */
      text-align: center;
      min-width: 82px;
      /* ← un poco más ancho para mayúsculas */
    }

    /* Rutinario → Verde */
    .pill.success {
      background: #1E8449 !important;
    }

    /* Crítico → Amarillo */
    .pill.danger {
      background: #F1C40F !important;
      color: #000 !important;
      /* Texto negro para contraste */
    }

    /* Operacional → Celeste */
    .pill.info {
      background: #17A2B8 !important;
    }

    /* Estratégico → Rojo */
    .pill.warn {
      background: #C0392B !important;
    }


    /* --- Bloque de totales con estilo uniforme con .it --- */
    .totales {
      width: 100%;
      border-collapse: collapse;
      margin-top: 12px;
      font-size: 10.5px;
    }

    .totales td {
      border: 1px solid #cfe1f5;
      padding: 6px 8px;
      color: #000;
    }

    /* Texto izquierda */
    .totales .label {
      background: #f7fbff;
      /* Igual que filas alternadas */
      font-weight: 600;
      text-align: left;
    }

    /* Columna monto alineada y misma anchura que la columna de la tabla principal */
    .amount {
      width: 22%;
      text-align: right;
      background: #f7fbff;
      font-weight: 700;
      white-space: nowrap;
      padding-right: 6px;
      /* ← Ajusta este valor si deseas más separación */
    }

    .amount::before {
      content: "S/ ";
      margin-right: 10px;
      /* ← Controla la separación entre S/ y el número */
    }


    /* Estilo para la fila de Total Ítems */
    .it tfoot tr td {
      background: #e8f1fb !important;
      /* Fondo tenue diferente */
      font-weight: 700;
      /* Negrita */
      color: #000;
    }

    /* Alineación del monto en Total Ítems */
    .it tfoot tr td:last-child {
      text-align: right;
    }

    .it tfoot td {
      background: #e8f1fb !important;
      font-weight: 700;
      /* negrita */
      border-top: 2px solid #92bce8 !important;
      /* separación elegante */
      color: #000;
    }

    .it tfoot td:last-child {
      text-align: right;
    }

    body {
      margin-top: 0mm;
      margin-bottom: 0mm;
    }

    table,
    tr,
    td,
    th {
      page-break-inside: avoid;
    }

    /* Chip de sí/no discreto */
    .badge-yn {
      display: inline-block;
      min-width: 36px;
      padding: 3px 10px;
      border-radius: 12px;
      font-size: 8.5px;
      font-weight: 600;
      text-transform: uppercase;
      /* <-- MAYÚSCULAS */
      background: #e9eef5;
      color: #0b2d50;
      border: 1px solid #c8d7e8;
      text-align: center;
      letter-spacing: 0.3px;
      /* <-- Más legible */
    }

    .uc {
      text-transform: uppercase;
    }
  </style>
</head>

<body>

  <table class="hdr">
    <tr>
      <td class="logo">
        <?php if ($logoEscudo): ?><img src="<?= $logoEscudo ?>" alt="Escudo"><?php endif; ?>
      </td>
      <td class="tit">
        <div class="l1"><?= htmlspecialchars($hdr1) ?></div>
        <div class="l2"><?= htmlspecialchars($hdr2) ?></div>
        <div class="l3"><?= htmlspecialchars($hdr3) ?></div>

      </td>
      <td class="logo" style="text-align:right">
        <?php if ($logoRegpol): ?><img src="<?= $logoRegpol ?>" alt="Logo"><?php endif; ?>
      </td>
    </tr>
  </table>

  <div class="doc-title">ANEXO 02 : SEGMENTACIÓN DE CONTRATACIONES</div>
  <br>


  <!-- Bloque principal -->
  <table class="blk">
    <tbody>
      <tr>
        <td class="lbl">N° REF. PAC</td>
        <td class="value"><?= htmlspecialchars((string) $row['ref_pac']) ?></td>
        <td class="lbl">CMN</td>
        <td class="value"><?= htmlspecialchars((string) ($row['cmn'] ?? '')) ?></td>
      </tr>
      <tr>
        <td class="lbl">Objeto</td>
        <td class="value" colspan="3"><?= htmlspecialchars((string) $row['objeto_contratacion'] ?: '—') ?></td>
      </tr>
      <tr>
        <td class="lbl">Descripción</td>
        <td class="value" colspan="3"><?= htmlspecialchars((string) $row['descripcion']) ?></td>
      </tr>

      <tr>
        <td class="lbl">Tipo de Proceso</td>
        <td class="value" colspan="3">
          <?= htmlspecialchars((string) $row['tipo_proceso'] ?: '—') ?>
        </td>
      </tr>


      <tr>
        <td class="lbl">Cuantía de la Contratación (S/.)</td>
        <td class="value"><?= money($cuantia) ?></td>
        <td class="lbl">% del PAC</td>
        <td class="value">
          <?= number_format($porcentajePac, 2, '.', ',') ?> %
        </td>
      </tr>

      <tr>
        <td class="lbl">Tipo de Cuantía</td>
        <td class="value uc"><?= htmlspecialchars($tipoC ?: '—') ?></td>
        <td class="lbl">Nivel de Riesgo</td>
        <td class="value uc"><?= htmlspecialchars($riesgo ?: '—') ?></td>
      </tr>

      <tr>
        <td class="lbl">Resultado Segmentación</td>
        <td class="value" colspan="3">
          <span class="<?= $pillCls ?>"><?= htmlspecialchars($resSeg ?: '—') ?></span>
        </td>
      </tr>


      <tr>
        <td class="lbl">Medición del Nivel de Riesgo</td>
        <td class="value" colspan="3" style="padding:0;">
          <table style="width:100%; border-collapse:collapse;">
            <colgroup>
              <col style="width:75%">
              <col style="width:25%">
            </colgroup>
            <tr>
              <td class="no-upper" style="padding:6px 8px; border:0;">
                ¿Quedó desierto en los últimos 2 años?
              </td>

              <td style="padding:6px 8px; border:0; text-align:right;">
                <span class="badge-yn"><?= $d1 ?></span>
              </td>
            </tr>
            <tr style="background:#f7fbff;">
              <td class="no-upper" style="padding:6px 8px; border:0;">
                ¿Promedio de postores en los últimos 2 años es ≤ 3?
              </td>
              <td style="padding:6px 8px; border:0; text-align:right;">
                <span class="badge-yn"><?= $d2 ?></span>
              </td>
            </tr>
            <tr>
              <td class="no-upper" style="padding:6px 8px; border:0;">
                ¿La disponibilidad del bien o servicio es limitada?
              </td>
              <td style="padding:6px 8px; border:0; text-align:right;">
                <span class="badge-yn"><?= $d3 ?></span>
              </td>
            </tr>
          </table>
        </td>
      </tr>




      <tr>
        <td class="lbl">Programación</td>
        <td class="value" colspan="3">
          <?= ($row['programado'] == 1 ? 'PROGRAMADO' : 'NO PROGRAMADO') ?>
        </td>
      </tr>
      <tr>
        <td class="lbl">Interacción con el mercado</td>

        <td class="value" colspan="3" style="font-weight:800;">

          <?= htmlspecialchars($interac) ?>
        </td>
      </tr>

    </tbody>
  </table>

  <div class="sec-title">Detalle de ítems</div>
  <table class="it">
    <thead>
      <tr>
        <th style="width:7%;" class="center">#</th>
        <th>Descripción del ítem</th>
        <th style="width:22%;" class="right">Monto (S/.)</th>
      </tr>
    </thead>

    <tbody>
      <?php if ($items): ?>
        <?php foreach ($items as $it): ?>
          <tr>
            <td class="center"><?= (int) $it['n'] ?></td>
            <td><?= htmlspecialchars((string) $it['desc']) ?></td>
            <td class="right"><?= number_format((float) $it['m'], 2, '.', ',') ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td class="center" colspan="3">– Sin ítems registrados –</td>
        </tr>
      <?php endif; ?>
    </tbody>

    <tfoot>
      <tr>
        <td></td>
        <td class="right total-label">Total ítems</td>
        <td class="right total-value"><?= number_format($sumIt, 2, '.', ',') ?></td>
      </tr>
    </tfoot>


  </table>

  <table class="totales">
    <tbody>
      <tr>
        <td class="label">MONTO TOTAL DE LA CONTRATACIÓN DEL PAC <?= $anio ?></td>
        <td class="amount"><?= number_format($totalPAC, 2, '.', ',') ?></td>

      </tr>
      <tr>
        <td class="label">10% (ART. 125.2 DEL REGLAMENTO)</td>
        <td class="amount"><?= number_format($porcentaje10, 2, '.', ',') ?></td>
      </tr>
    </tbody>
  </table>




  <div class="sign" style="margin-top: 20px;">
    <table style="width:100%; border-collapse:collapse;">
      <tr>
        <td style="width:50%; text-align:center; vertical-align:bottom; padding: 0 20px;">
          <!-- Texto -->
          <div class="lbl" style="font-weight:bold; font-size: 12px; text-transform: uppercase; margin-bottom: 60px;">APROBADO POR:</div>
        </td>
        <td style="width:50%; text-align:center; vertical-align:bottom; padding: 0 20px;">
          <!-- Texto -->
          <div class="lbl" style="font-weight:bold; font-size: 12px; text-transform: uppercase; margin-bottom: 60px;">ELABORADO POR:</div>
        </td>
      </tr>
    </table>
  </div>

  <div class="footer-org">
    OFICINA DE PROGRAMACIÓN – UNIDAD DE ADMINISTRACIÓN – UE009 - VII DIRTEPOL LIMA<br>
    RUC 20383430250 • Av. España 450 – Cercado de Lima<br>
    <span style="font-size:8px; opacity:0.8;">Fecha de impresión: <?= date('d/m/Y H:i:s') ?></span>
  </div>

</body>

</html>
<?php
$html = ob_get_clean();

/* ------------ PDF ------------ */
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Limpiar CUALQUIER salida accidental (espacios, warnings) antes del stream
if (ob_get_length()) ob_clean(); 

$dompdf->stream(
  'anexo01_segmentacion_' . preg_replace('/\D+/', '', (string) $row['ref_pac']) . '.pdf',
  ['Attachment' => false]
);
exit;
