<?php
// controlador/segmentacion_detalle.php
// ====================================
// Devuelve en JSON el detalle de un procedimiento de segmentación
// (cabecera + ítems), garantizando que el %PAC, cuantía, riesgo y
// resultado estén recalculados según la regla vigente.
// ====================================

declare(strict_types=1);
date_default_timezone_set('America/Lima');

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../modelo/conexion.php';
require_once __DIR__ . '/../modelo/segmentacion_util.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {

    // ===========================
    // 1. Validaciones básicas
    // ===========================
    if (!isset($_GET['id'])) {
        echo json_encode(['ok' => false, 'msg' => 'Falta parámetro id']);
        exit;
    }

    $id = (int)$_GET['id'];
    if ($id <= 0) {
        echo json_encode(['ok' => false, 'msg' => 'ID inválido']);
        exit;
    }

    if ($conn instanceof mysqli) {
        $conn->set_charset('utf8mb4');
    }

    // ===========================
    // 2. Obtener año del registro
    // ===========================
    $anio = (int)date('Y');

    $stmtAnio = $conn->prepare("SELECT anio FROM segmentacion WHERE id = ? LIMIT 1");
    $stmtAnio->bind_param('i', $id);
    $stmtAnio->execute();
    $resAnio = $stmtAnio->get_result();
    if ($rowAnio = $resAnio->fetch_row()) {
        $anio = (int)$rowAnio[0];
    } else {
        // Si no existe el registro, salimos de inmediato
        echo json_encode(['ok' => false, 'msg' => 'El registro no existe']);
        exit;
    }
    $stmtAnio->close();

    // ======================================================
// 3. Recalcular %PAC, cuantía, riesgo y resultado
// ======================================================
recalcPorcentajeYCuantiaCategoria($conn, $id, $anio);

// 🔥 🔥 🔥 IMPORTANTE: RECALCULAR TODO EL AÑO
if (function_exists('recalcTodoAnio')) {
    recalcTodoAnio($conn, $anio);
}

    // ======================================================
    // 4. Leer cabecera ACTUALIZADA
    // ======================================================
    $sqlCab = "
        SELECT 
            s.id,
            COALESCE(s.ref_pac, '')              AS ref_pac,
            COALESCE(s.cmn, '')                  AS cmn,
            COALESCE(s.objeto_contratacion, '')  AS objeto_contratacion,
            COALESCE(s.descripcion, '')          AS descripcion,
            COALESCE(s.cuantia, 0)               AS cuantia,
            COALESCE(s.porcentaje, 0)            AS porcentaje,
            COALESCE(s.cuantia_categoria, '')    AS cuantia_categoria,
            COALESCE(s.riesgo_categoria, '')     AS riesgo_categoria,
            COALESCE(s.resultado_segmentacion,'') AS resultado_segmentacion,
            CASE WHEN s.programado = 1 THEN 1 ELSE 0 END AS programado,
            COALESCE(DATE_FORMAT(s.fecha, '%Y-%m-%d'), '') AS fecha,
            COALESCE(s.tipo_proceso_id, 0)       AS tipo_proceso_id,
            COALESCE(tp.nombre, '')              AS tipo_proceso,
            -- Factores de riesgo
            COALESCE(s.declarado_desierto, 'No') AS declarado_desierto,
            COALESCE(s.pocos_postores, 'No')     AS pocos_postores,
            COALESCE(s.mercado_limitado, 'No')   AS mercado_limitado,
            (
              SELECT ROUND(COALESCE(SUM(i.monto_item),0), 2)
              FROM segmentacion_items i
              WHERE i.segmentacion_id = s.id
            ) AS suma_items
        FROM segmentacion s
        LEFT JOIN tipo_proceso tp ON tp.id = s.tipo_proceso_id
        WHERE s.id = ?
        LIMIT 1
    ";

    $stmtCab = $conn->prepare($sqlCab);
    $stmtCab->bind_param('i', $id);
    $stmtCab->execute();
    $rsCab = $stmtCab->get_result();

    if ($rsCab->num_rows === 0) {
        echo json_encode(['ok' => false, 'msg' => 'El registro no existe']);
        exit;
    }

    $cab = $rsCab->fetch_assoc();
    $stmtCab->close();

    // Aseguramos tipos numéricos en la cabecera
    $cab['cuantia']       = (float)$cab['cuantia'];
    $cab['porcentaje']    = (float)$cab['porcentaje'];
    $cab['suma_items']    = (float)($cab['suma_items'] ?? 0);
    $cab['programado']    = (int)$cab['programado'];
    $cab['tipo_proceso_id'] = (int)$cab['tipo_proceso_id'];

    // ======================================================
    // 5. Leer ÍTEMS (ordenados)
    // ======================================================
    $sqlItems = "
        SELECT
            si.id,
            si.descripcion_item,
            si.monto_item,
            COALESCE(si.orden, 0) AS orden
        FROM segmentacion_items si
        WHERE si.segmentacion_id = ?
        ORDER BY
            CASE WHEN si.orden IS NULL OR si.orden = 0 THEN 1 ELSE 0 END,
            si.orden ASC,
            si.id ASC
    ";

    $stmtI = $conn->prepare($sqlItems);
    $stmtI->bind_param('i', $cab['id']);
    $stmtI->execute();
    $resI = $stmtI->get_result();

    $items = [];
    while ($row = $resI->fetch_assoc()) {
        $row['monto_item'] = (float)$row['monto_item'];
        $row['orden']      = (int)$row['orden'];
        $items[] = $row;
    }
    $stmtI->close();

    // ======================================================
    // 6. Respuesta JSON
    // ======================================================
    echo json_encode([
        'ok'   => true,
        'data' => [
            'cabecera' => $cab,
            'items'    => $items,
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (mysqli_sql_exception $e) {
    echo json_encode(['ok' => false, 'msg' => 'DB: ' . $e->getMessage()]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
