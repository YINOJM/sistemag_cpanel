<?php
/**
 * Middleware de Permisos - Sistema RBAC
 * 
 * Valida permisos de acceso a módulos y acciones del sistema
 * Implementa el patrón RBAC (Role-Based Access Control) con:
 * - Permisos basados en plantillas
 * - Overrides personalizados por usuario
 * - Auditoría de intentos de acceso
 * - Protección contra escalación de privilegios
 * 
 * @author Arquitecto de Software Senior
 * @version 1.0.0
 * @date 2026-01-18
 */

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/AuditoriaUtil.php';

class PermisosMiddleware
{
    private $conexion;
    private $auditoria;

    public function __construct()
    {
        global $conexion;
        $this->conexion = $conexion;
        $this->auditoria = new AuditoriaUtil();
    }

    /**
     * Proteger una ruta verificando permisos
     * 
     * @param string $modulo_slug Slug del módulo (ej: 'gestion_documental')
     * @param string $accion_slug Slug de la acción (ej: 'VER', 'CREAR', 'EDITAR')
     * @param bool $redirigir Si debe redirigir en caso de no tener permiso
     * @return bool True si tiene permiso, False si no
     */
    public function protegerRuta($modulo_slug, $accion_slug = 'VER', $redirigir = true)
    {
        // Verificar sesión activa
        if (!isset($_SESSION['id'])) {
            if ($redirigir) {
                header('Location: ' . (defined('BASE_URL') ? BASE_URL : '/sistem_job/') . 'vista/login/login.php');
                exit;
            }
            return false;
        }

        $usuario_id = $_SESSION['id'];
        $rol = $_SESSION['rol'] ?? 'Usuario';

        // Super Administrador tiene acceso total
        if ($rol === 'Super Administrador') {
            return true;
        }

        // Verificar permiso
        $tiene_permiso = $this->verificarPermiso($usuario_id, $modulo_slug, $accion_slug);

        if (!$tiene_permiso) {
            // Registrar intento no autorizado
            $this->registrarIntentoNoAutorizado($usuario_id, $modulo_slug, $accion_slug);

            if ($redirigir) {
                http_response_code(403);
                include __DIR__ . '/../vista/errores/403.php';
                exit;
            }
            return false;
        }

        return true;
    }

    /**
     * Verificar si un usuario tiene permiso para una acción en un módulo
     * 
     * Orden de prioridad:
     * 1. Override personalizado del usuario
     * 2. Permiso de la plantilla asignada
     * 3. Denegar por defecto
     * 
     * @param int $usuario_id ID del usuario
     * @param string $modulo_slug Slug del módulo
     * @param string $accion_slug Slug de la acción
     * @return bool True si tiene permiso, False si no
     */
    public function verificarPermiso($usuario_id, $modulo_slug, $accion_slug)
    {
        try {
            // Obtener rol del usuario
            $sql = "SELECT rol FROM usuario WHERE id_usuario = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bind_param('i', $usuario_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $usuario = $result->fetch_assoc();

            if (!$usuario) {
                return false;
            }

            // Super Administrador tiene acceso total
            if ($usuario['rol'] === 'Super Administrador') {
                return true;
            }

            // Obtener IDs de módulo y acción
            $sql = "SELECT id, es_restringido FROM modulos WHERE slug = ? AND activo = 1";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bind_param('s', $modulo_slug);
            $stmt->execute();
            $result = $stmt->get_result();
            $modulo = $result->fetch_assoc();

            if (!$modulo) {
                return false; // Módulo no existe o está inactivo
            }

            $sql = "SELECT id FROM acciones WHERE slug = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bind_param('s', $accion_slug);
            $stmt->execute();
            $result = $stmt->get_result();
            $accion = $result->fetch_assoc();

            if (!$accion) {
                return false; // Acción no existe
            }

            // Si el módulo es restringido y el usuario no es Admin, denegar
            if ($modulo['es_restringido'] == 1 && !in_array($usuario['rol'], ['Administrador', 'Super Administrador'])) {
                return false;
            }

            // Verificar permiso (override > plantilla > denegar)
            $sql = "SELECT COALESCE(
                        (SELECT permitido FROM usuario_permisos_personalizados 
                         WHERE usuario_id = ? AND modulo_id = ? AND accion_id = ?),
                        (SELECT ppd.permitido 
                         FROM usuario_plantilla up
                         JOIN plantilla_permisos_detalle ppd ON up.plantilla_id = ppd.plantilla_id
                         WHERE up.usuario_id = ? AND ppd.modulo_id = ? AND ppd.accion_id = ?),
                        0
                    ) AS tiene_permiso";

            $stmt = $this->conexion->prepare($sql);
            $stmt->bind_param(
                'iiiiii',
                $usuario_id,
                $modulo['id'],
                $accion['id'],
                $usuario_id,
                $modulo['id'],
                $accion['id']
            );
            $stmt->execute();
            $result = $stmt->get_result();
            $permiso = $result->fetch_assoc();

            return (bool) $permiso['tiene_permiso'];

        } catch (Exception $e) {
            error_log("Error al verificar permiso: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si un usuario puede gestionar permisos de otro usuario
     * 
     * Reglas:
     * - Super Administrador: puede gestionar a todos
     * - Administrador: puede gestionar a Usuarios y otros Admins (no SuperAdmin)
     * - Usuario: no puede gestionar permisos
     * 
     * @param int $usuario_ejecutor_id ID del usuario que quiere gestionar
     * @param int $usuario_afectado_id ID del usuario a gestionar
     * @return bool True si puede gestionar, False si no
     */
    public function puedeGestionarPermisos($usuario_ejecutor_id, $usuario_afectado_id)
    {
        try {
            // Obtener roles
            $sql = "SELECT id_usuario, rol FROM usuario WHERE id_usuario IN (?, ?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bind_param('ii', $usuario_ejecutor_id, $usuario_afectado_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $usuarios = [];
            while ($row = $result->fetch_assoc()) {
                $usuarios[$row['id_usuario']] = $row['rol'];
            }

            $rol_ejecutor = $usuarios[$usuario_ejecutor_id] ?? null;
            $rol_afectado = $usuarios[$usuario_afectado_id] ?? null;

            if (!$rol_ejecutor || !$rol_afectado) {
                return false;
            }

            // Super Administrador puede gestionar a todos
            if ($rol_ejecutor === 'Super Administrador') {
                return true;
            }

            // Administrador puede gestionar a Usuarios y otros Admins (no SuperAdmin)
            if ($rol_ejecutor === 'Administrador' && $rol_afectado !== 'Super Administrador') {
                return true;
            }

            // Usuario no puede gestionar permisos
            return false;

        } catch (Exception $e) {
            error_log("Error al verificar gestión de permisos: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registrar intento de acceso no autorizado
     * 
     * @param int $usuario_id ID del usuario
     * @param string $modulo_slug Slug del módulo
     * @param string $accion_slug Slug de la acción
     */
    private function registrarIntentoNoAutorizado($usuario_id, $modulo_slug, $accion_slug)
    {
        try {
            $descripcion = sprintf(
                "Intento de acceso no autorizado al módulo '%s' con acción '%s'",
                $modulo_slug,
                $accion_slug
            );

            $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';

            $sql = "INSERT INTO auditoria_permisos 
                    (usuario_afectado_id, tipo_operacion, descripcion, ip_address, user_agent) 
                    VALUES (?, 'ACCESO_DENEGADO', ?, ?, ?)";

            $stmt = $this->conexion->prepare($sql);
            $tipo = 'MODIFICAR_PERMISO'; // Usar un tipo válido del ENUM
            $stmt->bind_param('isss', $usuario_id, $descripcion, $ip, $user_agent);
            $stmt->execute();

            error_log("Acceso denegado: Usuario $usuario_id intentó acceder a $modulo_slug::$accion_slug");

        } catch (Exception $e) {
            error_log("Error al registrar intento no autorizado: " . $e->getMessage());
        }
    }

    /**
     * Obtener módulos accesibles para un usuario
     * 
     * @param int $usuario_id ID del usuario
     * @return array Lista de módulos con permisos
     */
    public function obtenerModulosAccesibles($usuario_id)
    {
        try {
            $sql = "SELECT DISTINCT 
                        m.id,
                        m.nombre,
                        m.slug,
                        m.icono,
                        m.color,
                        m.orden,
                        m.es_restringido
                    FROM modulos m
                    WHERE m.activo = 1
                    AND EXISTS (
                        SELECT 1 
                        FROM acciones a
                        LEFT JOIN usuario_plantilla up ON up.usuario_id = ?
                        LEFT JOIN plantilla_permisos_detalle ppd ON up.plantilla_id = ppd.plantilla_id 
                            AND m.id = ppd.modulo_id AND a.id = ppd.accion_id
                        LEFT JOIN usuario_permisos_personalizados upp ON upp.usuario_id = ? 
                            AND m.id = upp.modulo_id AND a.id = upp.accion_id
                        WHERE a.slug = 'VER'
                        AND COALESCE(upp.permitido, ppd.permitido, 0) = 1
                    )
                    ORDER BY m.orden, m.nombre";

            $stmt = $this->conexion->prepare($sql);
            $stmt->bind_param('ii', $usuario_id, $usuario_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $modulos = [];
            while ($row = $result->fetch_assoc()) {
                $modulos[] = $row;
            }

            return $modulos;

        } catch (Exception $e) {
            error_log("Error al obtener módulos accesibles: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Verificar múltiples permisos a la vez
     * 
     * @param int $usuario_id ID del usuario
     * @param array $permisos Array de ['modulo_slug' => ['accion1', 'accion2']]
     * @return array Array con resultados de cada permiso
     */
    public function verificarPermisosMultiples($usuario_id, $permisos)
    {
        $resultados = [];

        foreach ($permisos as $modulo_slug => $acciones) {
            $resultados[$modulo_slug] = [];
            foreach ($acciones as $accion_slug) {
                $resultados[$modulo_slug][$accion_slug] = $this->verificarPermiso(
                    $usuario_id,
                    $modulo_slug,
                    $accion_slug
                );
            }
        }

        return $resultados;
    }

    /**
     * Obtener resumen de permisos de un usuario
     * 
     * @param int $usuario_id ID del usuario
     * @return array Resumen con estadísticas
     */
    public function obtenerResumenPermisos($usuario_id)
    {
        try {
            $sql = "SELECT 
                        COUNT(DISTINCT m.id) as total_modulos,
                        COUNT(*) as total_permisos,
                        SUM(CASE WHEN COALESCE(upp.permitido, ppd.permitido, 0) = 1 THEN 1 ELSE 0 END) as permisos_activos,
                        COALESCE(pb.nombre, 'Sin plantilla') as plantilla_nombre,
                        CASE 
                            WHEN upp.permitido IS NOT NULL THEN 'PERSONALIZADO' 
                            WHEN ppd.permitido IS NOT NULL THEN 'PLANTILLA' 
                            ELSE 'NINGUNO' 
                        END AS origen_permiso
                    FROM modulos m
                    CROSS JOIN acciones a
                    LEFT JOIN usuario_plantilla up ON up.usuario_id = ?
                    LEFT JOIN plantillas_permisos_base pb ON up.plantilla_id = pb.id
                    LEFT JOIN plantilla_permisos_detalle ppd ON up.plantilla_id = ppd.plantilla_id 
                        AND m.id = ppd.modulo_id AND a.id = ppd.accion_id
                    LEFT JOIN usuario_permisos_personalizados upp ON upp.usuario_id = ? 
                        AND m.id = upp.modulo_id AND a.id = upp.accion_id
                    WHERE m.activo = 1
                    GROUP BY pb.nombre, origen_permiso";

            $stmt = $this->conexion->prepare($sql);
            $stmt->bind_param('ii', $usuario_id, $usuario_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $resumen = [
                'total_modulos' => 0,
                'total_permisos' => 0,
                'permisos_activos' => 0,
                'plantilla' => null,
                'tiene_overrides' => false
            ];

            while ($row = $result->fetch_assoc()) {
                $resumen['total_modulos'] += $row['total_modulos'];
                $resumen['total_permisos'] += $row['total_permisos'];
                $resumen['permisos_activos'] += $row['permisos_activos'];
                $resumen['plantilla'] = $row['plantilla_nombre'];
                if ($row['origen_permiso'] === 'PERSONALIZADO') {
                    $resumen['tiene_overrides'] = true;
                }
            }

            return $resumen;

        } catch (Exception $e) {
            error_log("Error al obtener resumen de permisos: " . $e->getMessage());
            return [
                'total_modulos' => 0,
                'total_permisos' => 0,
                'permisos_activos' => 0,
                'plantilla' => null,
                'tiene_overrides' => false
            ];
        }
    }
}
