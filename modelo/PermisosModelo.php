<?php
/**
 * Modelo de Permisos - Sistema RBAC Completo
 * 
 * Gestiona permisos basados en roles con:
 * - Plantillas de permisos predefinidas
 * - Overrides personalizados por usuario
 * - Asignación masiva de plantillas
 * - Auditoría completa de cambios
 * - Copiar permisos entre usuarios
 * 
 * @author Arquitecto de Software Senior
 * @version 2.0.0
 * @date 2026-01-18
 */

require_once __DIR__ . '/conexion.php';

class PermisosModelo
{
    private $conexion;

    public function __construct()
    {
        global $conexion;
        $this->conexion = $conexion;
    }

    // ========================================================================
    // GESTIÓN DE PLANTILLAS
    // ========================================================================

    /**
     * Obtener todas las plantillas de permisos disponibles
     */
    public function obtenerPlantillas()
    {
        try {
            $sql = "SELECT * FROM plantillas_permisos_base WHERE activo = 1 ORDER BY nivel_acceso, nombre";
            $result = $this->conexion->query($sql);
            if (!$result) return [];

            $plantillas = [];
            while ($row = $result->fetch_assoc()) {
                $plantillas[] = $row;
            }
            return $plantillas;
        }
        catch (Exception $e) {
            error_log("Error al obtener plantillas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener permisos de una plantilla específica
     */
    public function obtenerPermisosPlantilla($plantilla_id)
    {
        try {
            $sql = "SELECT 
                        m.slug AS modulo_slug,
                        a.slug AS accion_slug,
                        ppd.permitido
                    FROM plantilla_permisos_detalle ppd
                    JOIN modulos m ON ppd.modulo_id = m.id
                    JOIN acciones a ON ppd.accion_id = a.id
                    WHERE ppd.plantilla_id = ? AND ppd.permitido = 1";

            $stmt = $this->conexion->prepare($sql);
            if (!$stmt) return [];
            $stmt->bind_param('i', $plantilla_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $permisos = [];
            while ($row = $result->fetch_assoc()) {
                if (!isset($permisos[$row['modulo_slug']])) {
                    $permisos[$row['modulo_slug']] = [];
                }
                $permisos[$row['modulo_slug']][$row['accion_slug']] = true;
            }

            return $permisos;
        }
        catch (Exception $e) {
            error_log("Error al obtener permisos de plantilla: " . $e->getMessage());
            return [];
        }
    }

    // ========================================================================
    // GESTIÓN DE PERMISOS DE USUARIO
    // ========================================================================

    /**
     * Obtener permisos completos de un usuario (plantilla + overrides)
     */
    public function obtenerPermisosUsuario($usuario_id)
    {
        try {
            $sql = "SELECT 
                        m.slug AS modulo_slug,
                        a.slug AS accion_slug,
                        COALESCE(upp.permitido, ppd.permitido, 0) AS tiene_permiso
                    FROM modulos m
                    CROSS JOIN acciones a
                    LEFT JOIN usuario_plantilla up ON up.usuario_id = ?
                    LEFT JOIN plantilla_permisos_detalle ppd ON up.plantilla_id = ppd.plantilla_id 
                        AND m.id = ppd.modulo_id AND a.id = ppd.accion_id
                    LEFT JOIN usuario_permisos_personalizados upp ON upp.usuario_id = ? 
                        AND m.id = upp.modulo_id AND a.id = upp.accion_id
                    WHERE m.activo = 1 
                    AND COALESCE(upp.permitido, ppd.permitido, 0) = 1";

            $stmt = $this->conexion->prepare($sql);
            if (!$stmt) return [];
            $stmt->bind_param('ii', $usuario_id, $usuario_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $permisos = [];
            while ($row = $result->fetch_assoc()) {
                if (!isset($permisos[$row['modulo_slug']])) {
                    $permisos[$row['modulo_slug']] = [];
                }
                $permisos[$row['modulo_slug']][$row['accion_slug']] = true;
            }

            return $permisos;
        }
        catch (Exception $e) {
            error_log("Error al obtener permisos de usuario: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Asignar plantilla a un usuario
     */
    public function asignarPlantilla($usuario_id, $plantilla_id, $asignado_por, $notas = null)
    {
        try {
            $this->conexion->begin_transaction();

            $sql = "DELETE FROM usuario_plantilla WHERE usuario_id = ?";
            $stmt = $this->conexion->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('i', $usuario_id);
                $stmt->execute();
            }

            $sql = "INSERT INTO usuario_plantilla (usuario_id, plantilla_id, asignado_por, notas) VALUES (?, ?, ?, ?)";
            $stmt = $this->conexion->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('iiis', $usuario_id, $plantilla_id, $asignado_por, $notas);
                $stmt->execute();
            }

            $this->registrarAuditoria($usuario_id, $asignado_por, 'ASIGNAR_PLANTILLA', null, null, $plantilla_id, null, null, "Plantilla ID $plantilla_id asignada");

            $this->conexion->commit();
            return true;
        } catch (Exception $e) {
            $this->conexion->rollback();
            return false;
        }
    }

    public function asignarPlantillaMasiva($usuarios_ids, $plantilla_id, $asignado_por)
    {
        try {
            $this->conexion->begin_transaction();
            $exitosos = 0;
            foreach ($usuarios_ids as $usuario_id) {
                if ($this->asignarPlantilla($usuario_id, $plantilla_id, $asignado_por, 'Masiva')) $exitosos++;
            }
            $this->conexion->commit();
            return ['success' => true, 'total' => count($usuarios_ids), 'exitosos' => $exitosos];
        } catch (Exception $e) {
            $this->conexion->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function guardarPermisosPersonalizados($usuario_id, $permisos, $asignado_por, $razon = null)
    {
        try {
            $this->conexion->begin_transaction();
            $this->conexion->query("DELETE FROM usuario_permisos_personalizados WHERE usuario_id = $usuario_id");

            $sql = "INSERT INTO usuario_permisos_personalizados (usuario_id, modulo_id, accion_id, permitido, asignado_por, razon)
                    SELECT ?, m.id, a.id, ?, ?, ? FROM modulos m CROSS JOIN acciones a
                    WHERE m.slug = ? AND a.slug = ?
                    ON DUPLICATE KEY UPDATE permitido = VALUES(permitido)";

            $stmt = $this->conexion->prepare($sql);
            if ($stmt) {
                foreach ($permisos as $m_slug => $accs) {
                    foreach ($accs as $a_slug => $val) {
                        $p_int = $val ? 1 : 0;
                        $stmt->bind_param('iiisss', $usuario_id, $p_int, $asignado_por, $razon, $m_slug, $a_slug);
                        $stmt->execute();
                    }
                }
            }
            $this->conexion->commit();
            return true;
        } catch (Exception $e) {
            $this->conexion->rollback();
            return false;
        }
    }

    public function copiarPermisos($u_origen, $u_destinos, $asignado_por)
    {
        try {
            $this->conexion->begin_transaction();
            $resP = $this->conexion->query("SELECT plantilla_id FROM usuario_plantilla WHERE usuario_id = $u_origen");
            $p_orig = $resP ? $resP->fetch_assoc() : null;
            if (!$p_orig) return false;

            foreach ($u_destinos as $u_dest) {
                $this->asignarPlantilla($u_dest, $p_orig['plantilla_id'], $asignado_por, "Copiado de $u_origen");
            }
            $this->conexion->commit();
            return ['success' => true, 'total' => count($u_destinos)];
        } catch (Exception $e) {
            $this->conexion->rollback();
            return false;
        }
    }

    public function tienePermiso($u_id, $m_slug, $a_slug)
    {
        try {
            $m = strtolower(trim($m_slug)); $a = strtoupper(trim($a_slug));
            $sql = "SELECT COALESCE(upp.permitido, ppd.permitido, 0) AS tiene_permiso
                    FROM modulos m CROSS JOIN acciones a
                    LEFT JOIN usuario_plantilla up ON up.usuario_id = ?
                    LEFT JOIN plantilla_permisos_detalle ppd ON up.plantilla_id = ppd.plantilla_id AND m.id = ppd.modulo_id AND a.id = ppd.accion_id
                    LEFT JOIN usuario_permisos_personalizados upp ON upp.usuario_id = ? AND m.id = upp.modulo_id AND a.id = upp.accion_id
                    WHERE m.slug = ? AND a.slug = ?";
            $stmt = $this->conexion->prepare($sql);
            if (!$stmt) return false;
            $stmt->bind_param('iiss', $u_id, $u_id, $m, $a);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) return (bool)$row['tiene_permiso'];
            return false;
        } catch (Exception $e) { return false; }
    }

    public function obtenerEstructuraPermisos()
    {
        try {
            $sql = "SELECT m.id, m.nombre, m.slug, m.icono, m.color, m.es_restringido, GROUP_CONCAT(a.slug ORDER BY a.orden) AS acciones
                    FROM modulos m CROSS JOIN acciones a WHERE m.activo = 1 GROUP BY m.id ORDER BY m.orden";
            $res = $this->conexion->query($sql);
            if (!$res) return [];
            $est = [];
            while ($row = $res->fetch_assoc()) {
                $est[$row['slug']] = ['id' => $row['id'], 'acciones' => explode(',', $row['acciones'])];
            }
            return $est;
        } catch (Exception $e) { return []; }
    }

    private function registrarAuditoria($af, $ej, $op, $m, $ac, $pl, $va, $vn, $ds) {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
            $sql = "INSERT INTO auditoria_permisos (usuario_afectado_id, usuario_ejecutor_id, tipo_operacion, modulo_id, accion_id, plantilla_id, valor_anterior, valor_nuevo, descripcion, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $st = $this->conexion->prepare($sql);
            if ($st) { $st->bind_param('iisiiissss', $af, $ej, $op, $m, $ac, $pl, $va, $vn, $ds, $ip); $st->execute(); }
        } catch (Exception $e) {}
    }
}
