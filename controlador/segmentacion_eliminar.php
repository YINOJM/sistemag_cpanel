<?php
// controlador/segmentacion_eliminar.php
declare(strict_types=1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once '../modelo/conexion.php';
require_once '../modelo/segmentacion_util.php';   // necesario

// QS para volver a la misma vista (viene ya codificado desde el listado)
$qs = isset($_GET['qs']) ? $_GET['qs'] : '';
$back = '../vista/segmentacion_listado.php' . ($qs ? ('?' . $qs) : '');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: ' . $back . (str_contains($back,'?')?'&':'?') . 'err=badid');
    exit;
}

try {

    // Obtener año ANTES de eliminar
    $stmtA = $conn->prepare("SELECT anio FROM segmentacion WHERE id=? LIMIT 1");
    $stmtA->bind_param('i', $id);
    $stmtA->execute();
    $row = $stmtA->get_result()->fetch_row();
    if (!$row) {
        header('Location: ' . $back . (str_contains($back,'?')?'&':'?') . 'err=notfound');
        exit;
    }
    $anio = (int)$row[0];
    $stmtA->close();

    // Transacción
    $conn->begin_transaction();

    // 1. Eliminar ítems
    $st1 = $conn->prepare("DELETE FROM segmentacion_items WHERE segmentacion_id=?");
    $st1->bind_param('i', $id);
    $st1->execute();
    $st1->close();

    // 2. Eliminar cabecera
    $st2 = $conn->prepare("DELETE FROM segmentacion WHERE id=?");
    $st2->bind_param('i', $id);
    $st2->execute();
    $st2->close();

    // 3. 🔥 RE-CALCULAR TODO EL AÑO
    // 3. 🔥 RE-CALCULAR TODO EL AÑO
    recalcTodoAnio($conn, $anio);

    // AUDITORÍA
    require_once __DIR__ . '/../modelo/audit_util.php';
    registrar_evento($conn, 'ELIMINAR SEGMENTACION', "ID eliminado: $id (Año: $anio)");

    $conn->commit();

    header('Location: ' . $back . (str_contains($back,'?')?'&':'?') . 'ok=1');

} catch (Throwable $e) {

    try { $conn->rollback(); } catch(Throwable $x) {}

    // Error de FK u otro error
    $err = ($e instanceof mysqli_sql_exception && (int)$e->getCode() === 1451) ? 'fk' : 'ex';
    header('Location: ' . $back . (str_contains($back,'?')?'&':'?') . 'err=' . $err);
}
