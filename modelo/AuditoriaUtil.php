<?php
/**
 * Utilidad de Auditoría
 * 
 * Clase para registrar eventos de auditoría en el sistema
 * 
 * @version 1.0.0
 * @date 2026-01-18
 */

class AuditoriaUtil
{
    private $conexion;

    public function __construct()
    {
        require_once __DIR__ . '/conexion.php';
        global $conexion;
        $this->conexion = $conexion;
    }

    /**
     * Registrar evento de auditoría
     * 
     * @param int $usuario_id ID del usuario que ejecuta la acción
     * @param string $accion Tipo de acción (LOGIN, LOGOUT, CREAR, EDITAR, ELIMINAR, etc.)
     * @param string $modulo Módulo del sistema (USUARIOS, PERMISOS, INVENTARIO, etc.)
     * @param string $descripcion Descripción detallada de la acción
     * @param array $datos_adicionales Datos adicionales en formato array (se convertirá a JSON)
     * @return bool True si se registró correctamente
     */
    public function registrar($usuario_id, $accion, $modulo, $descripcion, $datos_adicionales = [])
    {
        try {
            // Obtener información del cliente
            $ip = $this->obtenerIP();
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido';

            // Convertir datos adicionales a JSON
            $datos_json = !empty($datos_adicionales) ? json_encode($datos_adicionales, JSON_UNESCAPED_UNICODE) : null;

            // Preparar consulta
            $sql = "INSERT INTO auditoria 
                    (usuario_id, accion, modulo, descripcion, datos_adicionales, ip_address, user_agent, fecha_hora) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $this->conexion->prepare($sql);

            if (!$stmt) {
                error_log("Error al preparar auditoría: " . $this->conexion->error);
                return false;
            }

            $stmt->bind_param(
                "issssss",
                $usuario_id,
                $accion,
                $modulo,
                $descripcion,
                $datos_json,
                $ip,
                $user_agent
            );

            $resultado = $stmt->execute();
            $stmt->close();

            return $resultado;
        } catch (Exception $e) {
            error_log("Error en auditoría: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registrar cambio de permisos
     * 
     * @param int $usuario_ejecutor_id Usuario que realiza el cambio
     * @param int $usuario_afectado_id Usuario afectado por el cambio
     * @param string $tipo_cambio Tipo de cambio (ASIGNAR_PLANTILLA, PERMISOS_PERSONALIZADOS, COPIAR_PERMISOS)
     * @param array $detalles Detalles del cambio
     * @return bool
     */
    public function registrarCambioPermisos($usuario_ejecutor_id, $usuario_afectado_id, $tipo_cambio, $detalles = [])
    {
        $descripcion = "Cambio de permisos: $tipo_cambio para usuario ID $usuario_afectado_id";

        $datos_adicionales = array_merge([
            'usuario_afectado' => $usuario_afectado_id,
            'tipo_cambio' => $tipo_cambio
        ], $detalles);

        return $this->registrar(
            $usuario_ejecutor_id,
            'CAMBIO_PERMISOS',
            'PERMISOS',
            $descripcion,
            $datos_adicionales
        );
    }

    /**
     * Obtener IP real del cliente
     * 
     * @return string
     */
    private function obtenerIP()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';
        }
    }

    /**
     * Obtener historial de auditoría
     * 
     * @param array $filtros Filtros opcionales (usuario_id, modulo, accion, fecha_desde, fecha_hasta)
     * @param int $limite Límite de registros
     * @return array
     */
    public function obtenerHistorial($filtros = [], $limite = 100)
    {
        try {
            $sql = "SELECT a.*, u.nombre, u.apellido 
                    FROM auditoria a 
                    LEFT JOIN usuario u ON a.usuario_id = u.id_usuario 
                    WHERE 1=1";

            $params = [];
            $types = "";

            // Aplicar filtros
            if (!empty($filtros['usuario_id'])) {
                $sql .= " AND a.usuario_id = ?";
                $params[] = $filtros['usuario_id'];
                $types .= "i";
            }

            if (!empty($filtros['modulo'])) {
                $sql .= " AND a.modulo = ?";
                $params[] = $filtros['modulo'];
                $types .= "s";
            }

            if (!empty($filtros['accion'])) {
                $sql .= " AND a.accion = ?";
                $params[] = $filtros['accion'];
                $types .= "s";
            }

            if (!empty($filtros['fecha_desde'])) {
                $sql .= " AND a.fecha_hora >= ?";
                $params[] = $filtros['fecha_desde'];
                $types .= "s";
            }

            if (!empty($filtros['fecha_hasta'])) {
                $sql .= " AND a.fecha_hora <= ?";
                $params[] = $filtros['fecha_hasta'];
                $types .= "s";
            }

            $sql .= " ORDER BY a.fecha_hora DESC LIMIT ?";
            $params[] = $limite;
            $types .= "i";

            $stmt = $this->conexion->prepare($sql);

            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            $historial = [];
            while ($row = $result->fetch_assoc()) {
                // Decodificar datos adicionales si existen
                if (!empty($row['datos_adicionales'])) {
                    $row['datos_adicionales'] = json_decode($row['datos_adicionales'], true);
                }
                $historial[] = $row;
            }

            $stmt->close();

            return $historial;
        } catch (Exception $e) {
            error_log("Error al obtener historial: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Limpiar registros antiguos de auditoría
     * 
     * @param int $dias_antiguedad Días de antigüedad para eliminar
     * @return int Número de registros eliminados
     */
    public function limpiarRegistrosAntiguos($dias_antiguedad = 90)
    {
        try {
            $sql = "DELETE FROM auditoria WHERE fecha_hora < DATE_SUB(NOW(), INTERVAL ? DAY)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bind_param("i", $dias_antiguedad);
            $stmt->execute();
            $eliminados = $stmt->affected_rows;
            $stmt->close();

            return $eliminados;
        } catch (Exception $e) {
            error_log("Error al limpiar auditoría: " . $e->getMessage());
            return 0;
        }
    }
}
