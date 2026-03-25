<?php
// controlador/segmentacion_update_campos.php
declare(strict_types=1);

date_default_timezone_set('America/Lima');

header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/../modelo/conexion.php';
require_once __DIR__ . '/../modelo/segmentacion_util.php';

function yn(string $v): string {
  $v = strtoupper(trim($v));
  return ($v === 'SI' || $v === 'SÍ' || $v === '1') ? 'Si' : 'No';
}

try {

  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'msg' => 'Método no permitido']);
    exit;
  }

  if (!isset($_POST['id'])) {
    echo json_encode(['ok' => false, 'msg' => 'Falta parámetro id']);
    exit;
  }

  if (!($conn instanceof mysqli)) {
    echo json_encode(['ok' => false, 'msg' => 'No hay conexión a BD']);
    exit;
  }

  $conn->set_charset('utf8mb4');

  $segId = (int)$_POST['id'];
  if ($segId <= 0) {
    echo json_encode(['ok' => false, 'msg' => 'ID inválido']);
    exit;
  }

  // ============================================
  // AÑO DEL REGISTRO
  // ============================================
  $stmt = $conn->prepare("SELECT anio FROM segmentacion WHERE id=? LIMIT 1");
  $stmt->bind_param('i', $segId);
  $stmt->execute();
  $anio = (int)($stmt->get_result()->fetch_column() ?? date('Y'));
  $stmt->close();

  // ============================================
  // CAMPOS CABECERA
  // ============================================
  // ============================================
  // CAMPOS CABECERA
  // ============================================
  $ref_pac     = trim($_POST['ref_pac'] ?? '');
  $cmn         = trim($_POST['cmn'] ?? '');
  $objeto      = trim($_POST['objeto_contratacion'] ?? '');
  $descripcion = trim($_POST['descripcion'] ?? '');

  // Cuantía
  if (isset($_POST['cuantia']) && trim((string)$_POST['cuantia']) !== '') {
    $cuantia = (float)$_POST['cuantia'];
  } else {
    $stmt = $conn->prepare("SELECT cuantia FROM segmentacion WHERE id=?");
    $stmt->bind_param('i', $segId);
    $stmt->execute();
    $cuantia = (float)$stmt->get_result()->fetch_column();
    $stmt->close();
  }

  $programado = (int)($_POST['programado'] ?? 0) ? 1 : 0;
  $fecha      = trim($_POST['fecha'] ?? '');

  // Factores de riesgo
  $declarado_desierto = yn($_POST['declarado_desierto'] ?? 'No');
  $pocos_postores     = yn($_POST['pocos_postores'] ?? 'No');
  $mercado_limitado   = yn($_POST['mercado_limitado'] ?? 'No');

  // Tipo proceso
  $tipo_proceso_id = ($_POST['tipo_proceso_id'] ?? '') !== '' ? (int)$_POST['tipo_proceso_id'] : null;

  if ($tipo_proceso_id !== null) {
    $st = $conn->prepare("SELECT 1 FROM tipo_proceso WHERE id=? AND estado=1");
    $st->bind_param('i', $tipo_proceso_id);
    $st->execute();
    if (!$st->get_result()->fetch_row()) {
      $tipo_proceso_id = null;
    }
    $st->close();
  }

  // ============================================
  // ÍTEMS
  // ============================================
  $ids        = $_POST['ed_items_id']    ?? [];
  $descs      = $_POST['ed_items_desc']  ?? [];
  $montos     = $_POST['ed_items_monto'] ?? [];
  $ordenes    = $_POST['ed_items_orden'] ?? [];

  $deletedCsv    = trim($_POST['ed_items_delete_csv'] ?? '');
  $syncFromItems = ((int)($_POST['sync_cuantia_from_items'] ?? 0) === 1);

  $conn->begin_transaction();

  // ============================================
  // ACTUALIZAR CABECERA
  // ============================================
  if ($tipo_proceso_id === null) {
    $sql = "
      UPDATE segmentacion
      SET ref_pac=?,
          cmn=?,
          objeto_contratacion=?,
          descripcion=?,
          cuantia=?,
          programado=?,
          fecha=?,
          tipo_proceso_id=NULL,
          declarado_desierto=?,
          pocos_postores=?,
          mercado_limitado=?
      WHERE id=?
    ";
    $st = $conn->prepare($sql);
    $st->bind_param('ssssdissssi',
      $ref_pac, $cmn, $objeto, $descripcion, $cuantia,
      $programado, $fecha, $declarado_desierto,
      $pocos_postores, $mercado_limitado, $segId
    );
  } else {
    $sql = "
      UPDATE segmentacion
      SET ref_pac=?,
          cmn=?,
          objeto_contratacion=?,
          descripcion=?,
          cuantia=?,
          programado=?,
          fecha=?,
          tipo_proceso_id=?,
          declarado_desierto=?,
          pocos_postores=?,
          mercado_limitado=?
      WHERE id=?
    ";
    $st = $conn->prepare($sql);
    $st->bind_param('ssssdisisssi',
      $ref_pac, $cmn, $objeto, $descripcion, $cuantia,
      $programado, $fecha, $tipo_proceso_id,
      $declarado_desierto, $pocos_postores,
      $mercado_limitado, $segId
    );
  }

  $st->execute();
  $st->close();

  // ============================================
  // ELIMINAR ÍTEMS
  // ============================================
  if ($deletedCsv !== '') {
    $idsDel = array_filter(array_map('intval', explode(',', $deletedCsv)));
    if ($idsDel) {
      $in = implode(',', $idsDel);
      $conn->query("
        DELETE FROM segmentacion_items
        WHERE segmentacion_id=$segId
          AND id IN($in)
      ");
    }
  }

  // ============================================
  // INSERTAR / ACTUALIZAR ÍTEMS
  // ============================================
  $sumItems = 0.0;
  $n = count($descs);

  for ($i = 0; $i < $n; $i++) {

    $iid   = (int)($ids[$i] ?? 0);
    $descI = trim($descs[$i] ?? '');
    $monto = round((float)($montos[$i] ?? 0), 2);
    $orden = max(1, (int)($ordenes[$i] ?? $i+1));

    if ($descI === '' || $monto <= 0) continue;

    if ($iid > 0) {
      $st = $conn->prepare("
        UPDATE segmentacion_items
        SET descripcion_item=?, monto_item=?, orden=?
        WHERE id=? AND segmentacion_id=?
      ");
      $st->bind_param('sdiii', $descI, $monto, $orden, $iid, $segId);
    } else {
      $st = $conn->prepare("
        INSERT INTO segmentacion_items (segmentacion_id, descripcion_item, monto_item, orden)
        VALUES (?,?,?,?)
      ");
      $st->bind_param('isdi', $segId, $descI, $monto, $orden);
    }

    $st->execute();
    $st->close();

    $sumItems += $monto;
  }

  // ============================================
  // SINCRONIZAR CUANTÍA DESDE ÍTEMS
  // ============================================
  if ($syncFromItems && $sumItems > 0) {
    $st = $conn->prepare("UPDATE segmentacion SET cuantia=? WHERE id=?");
    $st->bind_param('di', $sumItems, $segId);
    $st->execute();
    $st->close();
    $cuantia = $sumItems;
  }

  // ============================================
  // RECALCULAR SOLO ESTE REGISTRO
  // ============================================
  recalcPorcentajeYCuantiaCategoria($conn, $segId, $anio);

  // ============================================
  // 🔥 RECALCULAR TODO EL AÑO COMPLETO (CLAVE)
  // ============================================
  recalcTodoAnio($conn, $anio);

  // AUDITORÍA
  require_once __DIR__ . '/../modelo/audit_util.php';
  registrar_evento($conn, 'EDITAR SEGMENTACION', "ID Editado: $segId (Año: $anio)");

  $conn->commit();

  echo json_encode([
    'ok'  => true,
    'msg' => 'Guardado correctamente',
    'data' => [
      'cuantia'         => $cuantia,
      'sum_items'       => $sumItems,
      'total_pac'       => getTotalPac($conn, $anio),
      'diez_por_ciento' => getDiezPorciento($conn, $anio),
    ]
  ]);

} catch (Throwable $e) {

  try {
    if ($conn instanceof mysqli) $conn->rollback();
  } catch (Throwable $x) {}

  echo json_encode([
    'ok'  => false,
    'msg' => $e->getMessage(),
  ]);
}
