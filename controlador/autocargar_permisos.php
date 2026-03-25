<?php
// controlador/autocargar_permisos.php - VERSIÓN ULTRA-ROBUSTA (CPANEL COMPATIBLE)

/**
 * Recarga los permisos del usuario en la sesión.
 * Optimizada para no fallar en entornos cPanel y ser 100% dinámica.
 */
function recargarPermisosUsuario($idUsuario, $dbConnection = null)
{
    if (!$idUsuario) return;

    global $conexion;
    $db = $dbConnection;

    // 1. Asegurar Conexión
    if (!$db) {
        if (isset($conexion) && $conexion instanceof mysqli) {
            $db = $conexion;
        } else {
            $rutaConexion = __DIR__ . '/../modelo/conexion.php';
            if (file_exists($rutaConexion)) {
                require_once $rutaConexion;
                $db = $conexion ?? $GLOBALS['conexion'] ?? $GLOBALS['conn'] ?? null;
            }
        }
    }

    if (!$db || !($db instanceof mysqli)) return;

    // 2. Obtener Datos del Usuario (USANDO BIND_RESULT PARA CPANEL)
    $stmtR = $db->prepare("SELECT rol FROM usuario WHERE id_usuario = ?");
    if ($stmtR) {
        $stmtR->bind_param("i", $idUsuario);
        $stmtR->execute();
        $stmtR->bind_result($rol_db);
        if ($stmtR->fetch()) {
            $_SESSION['rol'] = $rol_db;
        }
        $stmtR->close();
    }

    // 3. Reiniciar Permisos
    $_SESSION['permisos'] = [];
    $rol_u = strtoupper(trim($_SESSION['rol'] ?? ''));

    // 4. CARGA DINÁMICA DE PERMISOS
    if ($rol_u === 'SUPER ADMINISTRADOR') {
        $resMod = $db->query("SELECT slug FROM modulos WHERE activo = 1");
        $resAcc = $db->query("SELECT slug FROM acciones");
        
        $acciones = [];
        if ($resAcc) {
            while($a = $resAcc->fetch_object()) $acciones[] = strtoupper($a->slug);
        }
        
        if ($resMod) {
            while ($m = $resMod->fetch_object()) {
                $mod_slug = strtoupper($m->slug);
                foreach ($acciones as $acc_slug) {
                    $_SESSION['permisos'][$mod_slug][$acc_slug] = true;
                }
            }
        }
    } else {
        // CARGA PROTEGIDA (SIN VISTA SQL ROTA)
        // Reemplazamos vista_permisos_usuario por una consulta directa a las tablas
        $sqlP = "SELECT UPPER(m.slug) as mod_slug, UPPER(a.slug) as acc_slug 
                 FROM modulos m 
                 CROSS JOIN acciones a
                 LEFT JOIN usuario_plantilla up ON up.usuario_id = ?
                 LEFT JOIN plantilla_permisos_detalle ppd ON up.plantilla_id = ppd.plantilla_id 
                    AND m.id = ppd.modulo_id AND a.id = ppd.accion_id
                 LEFT JOIN usuario_permisos_personalizados upp ON upp.usuario_id = ? 
                    AND m.id = upp.modulo_id AND a.id = upp.accion_id
                 WHERE COALESCE(upp.permitido, ppd.permitido, 0) = 1";

        $stmtP = $db->prepare($sqlP);
        if ($stmtP) {
            $stmtP->bind_param("ii", $idUsuario, $idUsuario);
            $stmtP->execute();
            $resP = $stmtP->get_result(); // Usamos get_result solo si falla el bind_result, pero intentamos que resP sea funcional
            if ($resP) {
                while ($p = $resP->fetch_object()) {
                    $_SESSION['permisos'][$p->mod_slug][$p->acc_slug] = true;
                }
            } else {
                // FALLBACK SI GET_RESULT FALLA (COMPATIBILIDAD TOTAL)
                $stmtP->bind_result($m_s, $a_s);
                while($stmtP->fetch()) {
                    $_SESSION['permisos'][$m_s][$a_s] = true;
                }
            }
            $stmtP->close();
        }
    }
}
