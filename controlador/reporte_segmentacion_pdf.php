<?php
declare(strict_types=1);
mysqli_report(MYSQLI_REPORT_OFF);
ob_start();
error_reporting(0);
ini_set('display_errors', '0');
ini_set('memory_limit', '1024M');
ini_set('pcre.backtrack_limit', '5000000');
set_time_limit(300);

use Dompdf\Dompdf;
use Dompdf\Options;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../modelo/conexion.php';

// Asegurar supresión de errores DESPUÉS de cargar la conexión (que podría re-activarlos)
error_reporting(0);
ini_set('display_errors', '0');
if (ob_get_length()) ob_clean(); // Limpiar rastro de cualquier include

mysqli_report(MYSQLI_REPORT_OFF); // Desactivar reportes automáticos para manejo manual

if (isset($conn) && $conn instanceof mysqli) {
    // ok
} elseif (isset($conexion) && $conexion instanceof mysqli) {
    $conn = $conexion;
} elseif (isset($mysqli) && $mysqli instanceof mysqli) {
    $conn = $mysqli;
} else {
    http_response_code(500);
    ob_clean();
    die('Error: No se pudo establecer conexión con la base de datos.'); 
}

$conn->set_charset('utf8mb4');
if ($conn->connect_error) {
    ob_clean();
    die("Error de conexión: " . $conn->connect_error);
}

/* ====== Filtros (mismos que la vista) ====== */
$anio         = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');
$programado   = isset($_GET['programado']) && $_GET['programado'] !== '' ? (int)$_GET['programado'] : null; // 1,0 o null
$texto        = trim($_GET['q'] ?? '');
$objeto       = trim($_GET['objeto'] ?? '');
$tipo_proceso = isset($_GET['tipo_proceso']) ? (int)$_GET['tipo_proceso'] : 0;
$resultado    = trim($_GET['resultado'] ?? '');
$mostrar_items = isset($_GET['items']) ? (int)$_GET['items'] : 0; // 0 resumido, 1 detallado

$where = ["s.anio={$anio}", "s.origen_registro = 'Segmentación'"];
if ($programado !== null) $where[] = "s.programado={$programado}";
if ($texto !== '')        $where[] = "CONCAT(s.descripcion,' ',s.ref_pac) LIKE '%".$conn->real_escape_string($texto)."%'";
if ($objeto !== '')       $where[] = "s.objeto_contratacion='".$conn->real_escape_string($objeto)."'";
if ($tipo_proceso>0)      $where[] = "s.tipo_proceso_id={$tipo_proceso}";
if ($resultado!=='')      $where[] = "s.resultado_segmentacion='".$conn->real_escape_string($resultado)."'";
$where_sql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

/* Etiqueta para el título */
$progLabel = ($programado === null)
  ? 'Programados y No programados'
  : ($programado ? 'Solo Programados' : 'Solo No programados');

/* ====== Consulta principal ====== */
$sql = "
SELECT
  s.id,
  s.ref_pac,
  s.cmn,
  s.objeto_contratacion,
  s.descripcion,
  s.cuantia,

-- %PAC dinámico (CORRECTO SIEMPRE)
ROUND(
    (s.cuantia / NULLIF((SELECT SUM(cuantia) FROM segmentacion WHERE anio = s.anio), 0)) * 100,
    2
) AS porcentaje,

s.cuantia_categoria,
s.riesgo_categoria,
s.resultado_segmentacion,
s.programado,
s.fecha,
tp.nombre AS tipo_proceso

FROM segmentacion s
LEFT JOIN tipo_proceso tp ON tp.id = s.tipo_proceso_id
{$where_sql}
ORDER BY s.programado DESC, s.ref_pac+0 ASC, s.id ASC";
$rs = $conn->query($sql);

if (!$rs) {
    ob_clean();
    header('Content-Type: text/plain');
    die("Error en la consulta principal: " . $conn->error . "\nSQL: " . $sql);
}

$rows = [];
while ($r = $rs->fetch_assoc()) $rows[] = $r;

/* ====== Ítems (si se pidió detallado) ====== */
$itemsBySeg = [];
if ($mostrar_items && $rows) {
  $ids = array_map('intval', array_column($rows,'id'));
    if ($ids) {
        $in = implode(',', $ids);
        $it = $conn->query("
          SELECT segmentacion_id,
                 COALESCE(NULLIF(orden,0), id) AS ord,
                 descripcion_item, monto_item, id
          FROM segmentacion_items
          WHERE segmentacion_id IN ($in)
          ORDER BY segmentacion_id, ord, id
        ");
        if ($it) {
            while($r = $it->fetch_assoc()){
                $sid = (int)$r['segmentacion_id'];
                $itemsBySeg[$sid][] = [
                    'orden' => (int)$r['ord'],
                    'desc'  => (string)$r['descripcion_item'],
                    'monto' => (float)$r['monto_item'],
                ];
            }
        }
    }
}

/* ====== Utilitarios ====== */
function locateImage(string $baseNoExt): ?string {
  $dir = realpath(__DIR__ . '/../public/images');
  if (!$dir) return null;
  foreach (['.png','.jpg','.jpeg','.webp'] as $ext) {
    $p = $dir . '/' . $baseNoExt . $ext;
    if (is_file($p)) return $p;
  }
  return null;
}
function embedImg(?string $absPath): ?string {
  if (!$absPath || !is_file($absPath)) return null;
  $ext  = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));
  $mime = ($ext==='jpg' ? 'jpeg' : $ext);
  return 'data:image/'.$mime.';base64,'.base64_encode(file_get_contents($absPath));
}
function money(float $n): string {
  return number_format($n, 2, '.', ',');
}
function badgeClassByResult(string $res): string {
  $k = mb_strtolower($res, 'UTF-8');
  if ($k === 'crítico' || $k === 'critico') return 'b-critico';
  if ($k === 'operacional') return 'b-operacional';
  if ($k === 'estratégico' || $k === 'estrategico') return 'b-estrategico';
  return 'b-rutinario';
}

function badgeInlineStyle(string $res): string {
    $k = $res;
    if (function_exists('mb_strtolower')) {
        $k = mb_strtolower($k, 'UTF-8');
    } else {
        $k = strtolower($k);
    }
    $k = trim($k);

    // Mapeo tolerante
    if (strpos($k, 'cr') !== false) return 'background:#F1C40F; color:#000; padding:2px 6px; border-radius:999px; font-weight:700; font-size:9px;';
    if (strpos($k, 'op') !== false) return 'background:#17A2B8; color:#fff; padding:2px 6px; border-radius:999px; font-weight:700; font-size:9px;';
    if (strpos($k, 'es') !== false) return 'background:#E74C3C; color:#fff; padding:2px 6px; border-radius:999px; font-weight:700; font-size:9px;';
    return 'background:#2ECC71; color:#fff; padding:2px 6px; border-radius:999px; font-weight:700; font-size:9px;';
}

/* ====== Función de impresión ====== */
function renderSection(string $titulo, array $rows, array $itemsBySeg, int $mostrarItems): array { ?>
  <div class="section-title"><?= htmlspecialchars($titulo) ?></div>
  <table class="data">
    <thead>
      <tr>
        <th style="width:5%">N° REF.<br>PAC</th>
        <th style="width:5%">CMN</th>
        <th style="width:9%">TIPO DE PROCESO</th>
        <th style="width:37%">DESCRIPCIÓN</th>
        <th style="width:14%">CUANTÍA (S/)</th>
        <th style="width:6%">% PAC</th>
        <th style="width:8%">CUANTÍA</th>
        <th style="width:8%">RIESGO</th>
        <th style="width:8%">RESULTADO</th>
      </tr>
    </thead>
    <tbody>
      <?php
        $total = 0.0;
        foreach ($rows as $row):
          $cuantia = (float)$row['cuantia'];
          $total  += $cuantia;
          $resTxt  = trim((string)($row['resultado_segmentacion'] ?? ''));
      ?>
      <tr>
        <td class="t-center"><?= htmlspecialchars((string)$row['ref_pac']) ?></td>
        <td class="t-center"><?= htmlspecialchars((string)($row['cmn'] ?? '')) ?></td>
        <td class="t-center"><?= htmlspecialchars((string)($row['tipo_proceso'] ?? '')) ?></td>
        <td class="just"><?= htmlspecialchars((string)$row['descripcion']) ?></td>
        <td class="t-right">S/ <?= money($cuantia) ?></td>
        <td class="t-center"> <?= number_format((float)($row['porcentaje'] ?? 0), 2, '.', '') ?>%</td>

        <td class="t-center"><?= htmlspecialchars((string)$row['cuantia_categoria']) ?></td>
        <td class="t-center"><?= htmlspecialchars((string)$row['riesgo_categoria']) ?></td>
        <td class="t-center">
            <span style="<?= badgeInlineStyle($resTxt) ?>">
                <?= htmlspecialchars($resTxt) ?>
            </span>
        </td>
      </tr>

      <?php if ($mostrarItems && !empty($itemsBySeg[$row['id']])): 
              $items = $itemsBySeg[$row['id']];
              $sum   = 0.0; foreach($items as $it){ $sum += (float)$it['monto']; }
              $mismatch = (abs($sum - $cuantia) > 0.01);
      ?>
        <tr class="avoid-break">
          <td colspan="9" class="items-cell">
            <div class="items-wrap">
              <div class="items-title">
                Ítems del procedimiento N° PAC <?= htmlspecialchars((string)$row['ref_pac']) ?>
                <span class="meta">(<?= count($items) ?> ítem<?= count($items)>1?'s':'' ?>, total S/ <?= money($sum) ?>)</span>
                <?php if($mismatch): ?><span class="mismatch">≠ cuantía de la fila</span><?php endif; ?>
              </div>
              <table class="items">
                <thead>
                  <tr>
                    <th style="width:7%; text-align:center">#</th>
                    <th>Descripción del ítem</th>
                    <th style="width:20%; text-align:right">Monto (S/.)</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach($items as $idx=>$it): $n = $it['orden'] ?: ($idx+1); ?>
                    <tr>
                      <td class="t-center"><?= $n ?></td>
                      <td><?= htmlspecialchars($it['desc']) ?></td>
                      <td class="t-right">S/ <?= money((float)$it['monto']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
                <tfoot>
                  <tr>
                    <td></td>
                    <td class="<?= $mismatch?'mismatch':'' ?>">Total ítems<?= $mismatch?' (≠ cuantía)':'' ?></td>
                    <td class="t-right <?= $mismatch?'mismatch':'' ?>">S/ <?= money($sum) ?></td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </td>
        </tr>
      <?php endif; ?>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="4"><strong>Total de procedimientos:</strong> <?= count($rows) ?></td>
        <td class="t-right"><strong>S/ <?= money($total) ?></strong></td>
        <td colspan="4"></td>
      </tr>
    </tfoot>
  </table>
<?php
  return ['cant'=>count($rows),'total'=>$total];
}


/* Logos */
$logoEscudo = embedImg(locateImage('escudo'));
$logoRegpol = embedImg(locateImage('logo_regpol'));

// Definir título de acuerdo a si muestra ítems o no
if ($mostrar_items) {
    $tituloAnexo = "ANEXO 03 - SEGMENTACIÓN DE BIENES Y SERVICIOS (CON ÍTEMS) – $anio";
} else {
    $tituloAnexo = "ANEXO 01: LISTADO DE PROCEDIMIENTOS DE SELECCIÓN DEL PAC DEL CMN DE LA UE 009  VII-DIRTEPOL-LIMA – $anio";
}


/* ====== HTML/CSS Layout ====== */
ob_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
  @page { margin: 12mm 12mm; }
  body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 9.5px; color:#111; }

  .header { width:100%; border-bottom:2px solid #0a6ebd; margin-bottom:8px; }
  .header td { vertical-align:middle; }
  .logo-box { width:90px; }
  .logo-img { width:70px; height:70px; object-fit:contain; display:block; }

  table.data { width:100%; border-collapse:collapse; table-layout:fixed; }
  table.data thead th{
    background:#0b7fc1; color:#fff; font-weight:700;
    padding:5px 4px; border:1px solid #e3eef7; font-size:9.5px;
    text-align:center; vertical-align:middle;
  }
  table.data td{
    padding:4px; border:1px solid #e9edf3; font-size:9.2px; vertical-align:middle;
  }

  .t-center { text-align:center; }
  .t-right  { text-align:right; }
  .just { text-align:justify; word-wrap:break-word; overflow-wrap:anywhere; hyphens:auto; }

  .badge{
    display:inline-block; padding:2px 6px; border-radius:999px;
    color:#fff; font-weight:700; font-size:9px;
  }
/* === COLORES OFICIALES SEGÚN GUÍA DE SEGMENTACIÓN === */
.b-rutinario   { background:#2ECC71; color:#fff; }   /* Verde */
.b-critico     { background:#F1C40F; color:#000; }   /* Amarillo */
.b-operacional { background:#17A2B8; color:#fff; }   /* Celeste / Turquesa */
.b-estrategico { background:#E74C3C; color:#fff; }   /* Rojo */


  .footer{ 
      position: fixed; 
      bottom: -8mm; 
      left: 0; 
      right: 0; 
      color: #666; 
      font-size: 8.5px; 
      text-align: center; 
      line-height: 1.3;
  }
  .footer .pageno:before { content: counter(page); }
  
  .section-title{
    margin:10px 0 4px;
    font-weight:700;
    border-left:4px solid #0b7fc1;
    padding-left:6px;
  }

  /* ===== Subtabla / bloque de ítems ===== */
  .items-cell { padding:0; border:0; background:#fbfdff; }
  .items-wrap{
    border:1px solid #d9e6f2;
    border-radius:4px;
    overflow:hidden;
    page-break-inside: avoid;
    margin-top:4px;
  }
  .items-title{
    background:#0e7490;
    color:#fff;
    font-weight:700;
    padding:5px 8px;
    font-size:9.3px;
  }
  .items-title .meta{ opacity:.95; font-weight:600; margin-left:6px; }
  .mismatch { color:#b3261e; font-weight:700; }

  table.data table.items thead th{
    background:#eef6ff !important;
    color:#0b3d62 !important;
    border-color:#dfeaf5 !important;
    text-align:left;
  }

  table.items { width:100%; border-collapse:collapse; font-size:9px; }
  table.items th, table.items td { border:1px solid #eef1f6; padding:3px 4px; }
  table.items tbody tr:nth-child(even){ background:#fafcff; }
  table.items tfoot td { font-weight:700; background:#f4f9ff; }

  .avoid-break { page-break-inside: avoid; }
</style>
</head>
<body>

<div class="footer">
  <div>OFICINA DE PROGRAMACIÓN – UNIDAD DE ADMINISTRACIÓN – UE009 - VII DIRTEPOL LIMA</div>
  <div>RUC 20383430250 • Av. España 450 – Cercado de Lima</div>
  <div style="margin-top:2px; font-size:8px; color:#888;">
    Fecha de impresión: <?= date('d/m/Y H:i:s') ?> &nbsp;|&nbsp; Página <span class="pageno"></span>
  </div>
</div>

<table class="header">
  <tr>
    <td class="logo-box">
      <?php if($logoEscudo): ?><img src="<?= $logoEscudo ?>" class="logo-img"><?php endif; ?>
    </td>
    <td>
      <div style="font-size:16px;font-weight:800;text-align:center;">
        <?= htmlspecialchars($tituloAnexo) ?>
      </div>
      <div style="font-size:10px;color:#555;text-align:center;">
        <strong><?= htmlspecialchars($progLabel) ?></strong>
      </div>
    </td>
    <td class="logo-box" style="text-align:right">
      <?php if($logoRegpol): ?><img src="<?= $logoRegpol ?>" class="logo-img"><?php endif; ?>
    </td>
  </tr>
</table>

<?php
/* ====== imprimir (1 sección o 2) ====== */
$granCant = 0; $granTotal = 0.0;

if ($programado === null) {
  $prog  = array_values(array_filter($rows, fn($r)=> (int)$r['programado'] === 1));
  $nprog = array_values(array_filter($rows, fn($r)=> (int)$r['programado'] === 0));

  if (!empty($prog)) {
    $res = renderSection('PROGRAMADOS', $prog, $itemsBySeg, (int)$mostrar_items);
    $granCant += $res['cant']; $granTotal += $res['total'];
    echo '<div style="height:8px"></div>';
  }
  if (!empty($nprog)) {
    $res = renderSection('NO PROGRAMADOS', $nprog, $itemsBySeg, (int)$mostrar_items);
    $granCant += $res['cant']; $granTotal += $res['total'];
  }

  if (!empty($prog) && !empty($nprog)) {
    echo '<div style="margin-top:8px;font-size:9.5px;text-align:right;">' .
         '<strong>Total general:</strong> Procedimientos: '.(int)$granCant .
         ' • S/ '.money($granTotal) .
         '</div>';
  }
} else {
  $titulo = $programado ? 'PROGRAMADOS' : 'NO PROGRAMADOS';
  renderSection($titulo, $rows, $itemsBySeg, (int)$mostrar_items);
}
?>

<div class="sign" style="margin-top: 20px;">
    <table style="width:100%; border-collapse:collapse;">
      <tr>
        <td style="width:50%; text-align:center; vertical-align:bottom; padding: 0 20px;">
          <div class="lbl" style="font-weight:bold; font-size: 12px; text-transform: uppercase; margin-bottom: 60px;">APROBADO POR:</div>
        </td>
        <td style="width:50%; text-align:center; vertical-align:bottom; padding: 0 20px;">
          <div class="lbl" style="font-weight:bold; font-size: 12px; text-transform: uppercase; margin-bottom: 60px;">ELABORADO POR:</div>
        </td>
      </tr>
    </table>
</div>

</body>
</html>
<?php
$html = ob_get_clean();

/* ====== Dompdf ====== */
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'landscape'); // horizontal
$dompdf->render();

// Limpiar CUALQUIER salida accidental (espacios, warnings) antes del stream
if (ob_get_length()) ob_clean(); 

$filename = 'reporte_segmentacion_' . date('Ymd_His') . '.pdf';
$dompdf->stream($filename, ['Attachment' => false]); // abre en el navegador
exit;
