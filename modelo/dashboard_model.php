<?php
require_once __DIR__ . "/conexion.php";
//dashboard_model.php
class DashboardModel {

    private $conn;

    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }

    /* =============================
       1. TOTAL DE PROCEDIMIENTOS
    ============================== */
    public function getTotalProcedimientos($anio) {
        $sql = "SELECT COUNT(*) total FROM segmentacion WHERE anio = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $anio);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()["total"] ?? 0;
    }

    /* ============================= 
        2. SUMA DE CUANTÍAS (PAC)
    ============================== */
    public function getTotalPAC($anio) {
        $sql = "SELECT SUM(cuantia) total FROM segmentacion WHERE anio=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $anio);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()["total"] ?? 0;
    }

    /* ============================= 
        3. TOTAL CRÍTICOS
    ============================== */
    public function getTotalCriticos($anio) {
        $sql = "SELECT COUNT(*) total 
                FROM segmentacion 
                WHERE anio=? AND resultado_segmentacion='Crítico'";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $anio);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()["total"] ?? 0;
    }

    /* ============================= 
        4. TOTAL ESTRATÉGICOS
    ============================== */
    public function getTotalEstrategicos($anio) {
        $sql = "SELECT COUNT(*) total 
                FROM segmentacion 
                WHERE anio=? AND resultado_segmentacion='Estratégico'";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $anio);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()["total"] ?? 0;
    }

    /* ============================= 
        4.1 TOTAL RUTINARIOS
    ============================== */
    public function getTotalRutinarios($anio) {
        $sql = "SELECT COUNT(*) total 
                FROM segmentacion 
                WHERE anio=? AND resultado_segmentacion='Rutinario'";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $anio);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()["total"] ?? 0;
    }

    /* ============================= 
        4.2 TOTAL OPERACIONALES
    ============================== */
    public function getTotalOperacionales($anio) {
        $sql = "SELECT COUNT(*) total 
                FROM segmentacion 
                WHERE anio=? AND resultado_segmentacion='Operacional'";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $anio);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()["total"] ?? 0;
    }

    /* ============================= 
        5. PROCEDIMIENTOS POR MES
    ============================== */
    public function getProcedimientosPorMes($anio) {
        $sql = "SELECT MONTH(fecha) mes, COUNT(*) total
                FROM segmentacion
                WHERE anio=?
                GROUP BY MONTH(fecha)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $anio);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /* ============================= 
        6. RESULTADOS (Rutinario, Crítico, etc.)
    ============================== */
public function getResultadoSegmentacion($anio) {

    // Lista fija de categorías oficiales
    $categorias = [
        "Rutinario"    => 0,
        "Operacional"  => 0,
        "Crítico"      => 0,
        "Estratégico"  => 0
    ];

    $sql = "SELECT resultado_segmentacion AS resultado, COUNT(*) total
            FROM segmentacion
            WHERE anio=?
            GROUP BY resultado_segmentacion";

    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $anio);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($result as $row) {

        // Normalización total: quita tildes, mayúsculas, espacios
        $clean = trim(
            mb_convert_case(
                str_replace(
                    ["á","é","í","ó","ú","Á","É","Í","Ó","Ú"],
                    ["a","e","i","o","u","A","E","I","O","U"],
                    $row["resultado"]
                ),
            MB_CASE_TITLE, "UTF-8")
        );

        // Se compara con claves normalizadas
        foreach ($categorias as $cat => $v) {
            $catClean = str_replace(
                ["á","é","í","ó","ú","Á","É","Í","Ó","Ú"],
                ["a","e","i","o","u","A","E","I","O","U"],
                $cat
            );
            if ($catClean === str_replace(
                ["á","é","í","ó","ú","Á","É","Í","Ó","Ú"],
                ["a","e","i","o","u","A","E","I","O","U"],
                $clean
            )) {
                $categorias[$cat] = $row["total"];
            }
        }
    }

    // Conversión al formato que espera Chart.js
    $output = [];
    foreach ($categorias as $nombre => $valor) {
        $output[] = [
            "resultado" => $nombre,
            "total" => $valor
        ];
    }

    return $output;
}


    /* ============================= 
        7. RIESGO (Alto / Bajo)
    ============================== */
    public function getRiesgoCategoria($anio) {
        $sql = "SELECT riesgo_categoria AS riesgo, COUNT(*) total
                FROM segmentacion
                WHERE anio=?
                GROUP BY riesgo_categoria";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $anio);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /* ============================= 
        8. CUANTÍA (Alta / Baja)
    ============================== */
    public function getCuantiaCategoria($anio) {
        $sql = "SELECT cuantia_categoria AS categoria, COUNT(*) total
                FROM segmentacion
                WHERE anio=?
                GROUP BY cuantia_categoria";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $anio);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /* ============================== 
        9. TIPO DE PROCESO
    =============================== */
    public function getTipoProceso($anio) {
        $sql = "SELECT tp.nombre AS tipo, COUNT(*) total
                FROM segmentacion s
                INNER JOIN tipo_proceso tp ON tp.id = s.tipo_proceso_id
                WHERE s.anio=?
                GROUP BY tp.nombre";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $anio);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    /* ============================== 
        10. TOP 5 MAYORES CUANTÍAS
    =============================== */
    public function getTop5Costosos($anio) {
        $sql = "SELECT descripcion, cuantia, resultado_segmentacion
                FROM segmentacion
                WHERE anio = ?
                ORDER BY cuantia DESC
                LIMIT 5";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $anio);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /* ============================== 
        11. AÑOS DISPONIBLES
    =============================== */
    public function getAniosDisponibles() {
        $sql = "SELECT DISTINCT anio FROM segmentacion ORDER BY anio DESC";
        $result = $this->conn->query($sql);
        $anios = [];
        while ($row = $result->fetch_assoc()) {
            $anios[] = $row['anio'];
        }
        return $anios;
    }
}
