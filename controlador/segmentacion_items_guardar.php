<?php
// SEGMENTACION_ITEMS_GUARDAR.PHP
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/../modelo/conexion.php';
require_once __DIR__ . '/../modelo/segmentacion_util.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/**
 * Normalizar conexión
 */
if (isset($conn) && $conn instanceof mysqli) {
    // ok
} elseif (isset($conexion) && $conexion instanceof mysqli) {
    $conn = $conexion;
} elseif (isset($mysqli) && $mysqli instanceof mysqli) {
    $conn = $mysqli;
} else {
    http_response_code(500);
    echo json_encode([
        'ok'  => false,
        'msg' => 'Error de conexión a base de datos'
    ]);
    exit;
}

$conn->set_charset('utf8mb4');

try {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $segId = (int)($_POST['segmentacion_id'] ?? 0);
    if ($segId <= 0) {
        throw new Exception('ID inválido');
    }

    $itemsJson = $_POST['items_json'] ?? '[]';
    $items = json_decode($itemsJson, true);
    if (!is_array($items)) {
        $items = [];
    }

    // Obtener año del registro
    $stmtAnio = $conn->prepare("SELECT anio FROM segmentacion WHERE id=? LIMIT 1");
    $stmtAnio->bind_param('i', $segId);
    $stmtAnio->execute();
    $anio = (int)($stmtAnio->get_result()->fetch_column() ?? date('Y'));
    $stmtAnio->close();

    $conn->begin_transaction();

    // Eliminar actuales
    $stmtDel = $conn->prepare("DELETE FROM segmentacion_items WHERE segmentacion_id=?");
    $stmtDel->bind_param('i', $segId);
    $stmtDel->execute();
    $stmtDel->close();

    // Insertar nuevos
    $stmtIns = $conn->prepare("
        INSERT INTO segmentacion_items (segmentacion_id, descripcion_item, monto_item, orden)
        VALUES (?,?,?,?)
    ");

    $suma = 0.0;
    $ordenAuto = 1;

    foreach ($items as $it) {

        $desc  = trim((string)($it['descripcion_item'] ?? ''));
        $monto = (float)($it['monto_item'] ?? 0);
        $orden = isset($it['orden']) ? (int)$it['orden'] : $ordenAuto;

        if ($desc === '' && $monto <= 0) continue;
        if ($orden <= 0) $orden = $ordenAuto;

        $stmtIns->bind_param('isdi', $segId, $desc, $monto, $orden);
        $stmtIns->execute();

        $suma      += $monto;
        $ordenAuto += 1;
    }

    $stmtIns->close();

    // Actualizar cuantía
    $suma = round($suma, 2);
    $stmtUpd = $conn->prepare("UPDATE segmentacion SET cuantia=? WHERE id=?");
    $stmtUpd->bind_param('di', $suma, $segId);
    $stmtUpd->execute();
    $stmtUpd->close();

    // Recalcular SOLO este registro
    recalcPorcentajeYCuantiaCategoria($conn, $segId, $anio);

    // 🔥 Recalcular TODO EL AÑO (clave para tu sistema)
    recalcTodoAnio($conn, $anio);

    // Obtener valores para mostrar
    $totalPac      = getTotalPac($conn, $anio);
    $diezPorciento = getDiezPorciento($conn, $anio);

    $conn->commit();

    echo json_encode([
        'ok'   => true,
        'data' => [
            'suma_items'      => $suma,
            'total_pac'       => $totalPac,
            'diez_por_ciento' => $diezPorciento
        ]
    ]);

} catch (Throwable $e) {

    if ($conn instanceof mysqli) {
        $conn->rollback();
    }

    http_response_code(500);
    echo json_encode([
        'ok'  => false,
        'msg' => $e->getMessage()
    ]);
}
