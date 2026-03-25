<?php
// modelo/segmentacion_util.php
declare(strict_types=1);

require_once __DIR__ . '/conexion.php';

/**
 * Devuelve el Total PAC real del año
 * (sumatoria de TODAS las cuantías del año)
 */
function getTotalPac(mysqli $conn, int $anio): float {
    $sql = "
        SELECT COALESCE(SUM(cuantia), 0) AS tot
        FROM segmentacion
        WHERE anio = $anio
    ";
    $rs = $conn->query($sql);
    return ($rs && $row = $rs->fetch_assoc()) ? (float)$row['tot'] : 0.0;
}

/**
 * Recalcula la cuantía = suma de ítems del procedimiento
 */
function recalcCuantiaFromItems(mysqli $conn, int $idSeg): bool {
    $sql = "
        UPDATE segmentacion s
        JOIN (
            SELECT segmentacion_id, COALESCE(SUM(monto_item),0) AS suma
            FROM segmentacion_items
            WHERE segmentacion_id = $idSeg
        ) x ON x.segmentacion_id = s.id
        SET s.cuantia = x.suma
        WHERE s.id = $idSeg
    ";
    return (bool)$conn->query($sql);
}

/**
 * Recalcula SÓLO UN registro:
 * - porcentaje
 * - cuantia_categoria
 * - riesgo_categoria
 * - resultado_segmentacion
 */
function recalcPorcentajeYCuantiaCategoria(mysqli $conn, int $idSeg, int $anio): bool {

    // 1) Total PAC real del año
    $totalPac = getTotalPac($conn, $anio);

    // 2) Recalcular porcentaje y categoría de cuantía
    $sql1 = "
        UPDATE segmentacion
        SET porcentaje = CASE 
                WHEN $totalPac > 0 THEN ROUND((cuantia / $totalPac) * 100, 2)
                ELSE 0
            END,
            cuantia_categoria = CASE
                WHEN $totalPac > 0 
                     AND (cuantia / $totalPac) > 0.10
                THEN 'Alta'
                ELSE 'Baja'
            END
        WHERE id = $idSeg
    ";
    $conn->query($sql1);

    // 3) Recalcular riesgo
    $sql2 = "
        UPDATE segmentacion
        SET riesgo_categoria = CASE
                WHEN declarado_desierto='Si'
                  OR pocos_postores='Si'
                  OR mercado_limitado='Si'
                THEN 'Alto'
                ELSE 'Bajo'
            END
        WHERE id = $idSeg
    ";
    $conn->query($sql2);

    // 4) Recalcular resultado final (matriz)
    $sql3 = "
        UPDATE segmentacion
        SET resultado_segmentacion = CASE
            WHEN cuantia_categoria='Baja' AND riesgo_categoria='Bajo' THEN 'Rutinario'
            WHEN cuantia_categoria='Baja' AND riesgo_categoria='Alto' THEN 'Crítico'
            WHEN cuantia_categoria='Alta' AND riesgo_categoria='Bajo' THEN 'Operacional'
            WHEN cuantia_categoria='Alta' AND riesgo_categoria='Alto' THEN 'Estratégico'
            ELSE resultado_segmentacion
        END
        WHERE id = $idSeg
    ";

    return (bool)$conn->query($sql3);
}


/**
 * Recalcula TODO el AÑO completo:
 * - porcentaje
 * - cuantia_categoria
 * - riesgo_categoria
 * - resultado_segmentacion
 *
 * Esta es la función CLAVE que hace EXACTAMENTE lo que tú querías:
 * actualizar todo automáticamente.
 */
function recalcTodoAnio(mysqli $conn, int $anio): bool {

    // Total PAC
    $totalPac = getTotalPac($conn, $anio);

    // 1) Recalcular porcentajes + categoría cuantía
    $conn->query("
        UPDATE segmentacion
        SET porcentaje = CASE
                WHEN $totalPac > 0 THEN ROUND((cuantia / $totalPac) * 100, 2)
                ELSE 0
            END,
            cuantia_categoria = CASE
                WHEN $totalPac > 0 
                     AND (cuantia / $totalPac) > 0.10
                THEN 'Alta'
                ELSE 'Baja'
            END
        WHERE anio = $anio
    ");

    // 2) Recalcular riesgo
    $conn->query("
        UPDATE segmentacion
        SET riesgo_categoria = CASE
                WHEN declarado_desierto='Si'
                  OR pocos_postores='Si'
                  OR mercado_limitado='Si'
                THEN 'Alto'
                ELSE 'Bajo'
            END
        WHERE anio = $anio
    ");

    // 3) Recalcular resultado final para todos
    $conn->query("
        UPDATE segmentacion
        SET resultado_segmentacion = CASE
            WHEN cuantia_categoria='Baja' AND riesgo_categoria='Bajo' THEN 'Rutinario'
            WHEN cuantia_categoria='Baja' AND riesgo_categoria='Alto' THEN 'Crítico'
            WHEN cuantia_categoria='Alta' AND riesgo_categoria='Bajo' THEN 'Operacional'
            WHEN cuantia_categoria='Alta' AND riesgo_categoria='Alto' THEN 'Estratégico'
            ELSE resultado_segmentacion
        END
        WHERE anio = $anio
    ");

    return true;
}


/**
 * 10% del PAC real
 */
function getDiezPorciento(mysqli $conn, int $anio): float {
    return round(getTotalPac($conn, $anio) * 0.10, 2);
}

/**
 * Datos para reportes
 */
function obtenerReporteSegmentacion(mysqli $conn, int $anio): array {
    $sql = "
        SELECT 
            ref_pac,
            objeto_contratacion,
            descripcion,
            cuantia,
            porcentaje,
            cuantia_categoria,
            riesgo_categoria,
            resultado_segmentacion,
            DATE_FORMAT(fecha, '%d/%m/%Y') AS fecha
        FROM segmentacion
        WHERE anio = $anio
        ORDER BY ref_pac ASC
    ";
    $rs = $conn->query($sql);
    return $rs ? $rs->fetch_all(MYSQLI_ASSOC) : [];
}

?>
