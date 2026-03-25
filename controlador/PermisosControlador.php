<?php
/**
 * Controlador de Permisos - Sistema RBAC
 */

// 1. CARGAR CONEXIÓN Y ENTORNO (Centralizado)
require_once __DIR__ . '/../modelo/conexion.php';
require_once __DIR__ . '/../modelo/PermisosModelo.php';
require_once __DIR__ . '/../modelo/PermisosMiddleware.php';

// session_start(); removido porque lo maneja conexion.php -> sesion_config.php

header('Content-Type: application/json; charset=utf-8');

// Verificar sesión activa
if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Sesión no válida'
    ]);
    exit;
}

$modelo = new PermisosModelo();
$middleware = new PermisosMiddleware();
$usuario_ejecutor_id = $_SESSION['id'];

// Obtener operación
$op = $_GET['op'] ?? $_POST['op'] ?? null;

if (!$op) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Operación no especificada'
    ]);
    exit;
}

// ============================================================================
// OPERACIONES
// ============================================================================

switch ($op) {

    // ------------------------------------------------------------------------
    // OBTENER PLANTILLAS DISPONIBLES
    // ------------------------------------------------------------------------
    case 'obtener_plantillas':
        try {
            $plantillas = $modelo->obtenerPlantillas();

            echo json_encode([
                'success' => true,
                'data' => $plantillas
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener plantillas: ' . $e->getMessage()
            ]);
        }
        break;

    // ------------------------------------------------------------------------
    // OBTENER PERMISOS DE UNA PLANTILLA
    // ------------------------------------------------------------------------
    case 'obtener_plantilla_rol':
        try {
            $id_rol = $_GET['id_rol'] ?? null;

            if (!$id_rol) {
                throw new Exception('ID de plantilla no especificado');
            }

            $permisos = $modelo->obtenerPermisosPlantilla($id_rol);

            echo json_encode([
                'success' => true,
                'data' => $permisos
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener permisos de plantilla: ' . $e->getMessage()
            ]);
        }
        break;

    // ------------------------------------------------------------------------
    // OBTENER PERMISOS DE UN USUARIO
    // ------------------------------------------------------------------------
    case 'obtener_permisos_usuario':
        try {
            $id_usuario = $_GET['id_usuario'] ?? null;

            if (!$id_usuario) {
                throw new Exception('ID de usuario no especificado');
            }

            // Verificar si puede gestionar permisos de este usuario
            if (!$middleware->puedeGestionarPermisos($usuario_ejecutor_id, $id_usuario)) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'No tienes permisos para gestionar este usuario'
                ]);
                exit;
            }

            $permisos = $modelo->obtenerPermisosUsuario($id_usuario);
            $resumen = $modelo->obtenerResumenPermisos($id_usuario);

            echo json_encode([
                'success' => true,
                'data' => [
                    'permisos' => $permisos,
                    'resumen' => $resumen
                ]
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener permisos: ' . $e->getMessage()
            ]);
        }
        break;

    // ------------------------------------------------------------------------
    // ASIGNAR PLANTILLA A UN USUARIO
    // ------------------------------------------------------------------------
    case 'asignar_plantilla':
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            $id_usuario = $data['id_usuario'] ?? null;
            $plantilla_id = $data['plantilla_id'] ?? null;
            $notas = $data['notas'] ?? null;

            if (!$id_usuario || !$plantilla_id) {
                throw new Exception('Datos incompletos');
            }

            // Verificar si puede gestionar permisos de este usuario
            if (!$middleware->puedeGestionarPermisos($usuario_ejecutor_id, $id_usuario)) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'No tienes permisos para gestionar este usuario'
                ]);
                exit;
            }

            $resultado = $modelo->asignarPlantilla($id_usuario, $plantilla_id, $usuario_ejecutor_id, $notas);

            if ($resultado) {
                // --- AUDITORIA ---
                $accion = "PERMISOS";
                $detalle = "Asignación de plantilla ID: " . $plantilla_id . ($notas ? " - Notas: $notas" : "");
                $stmtLog = $conexion->prepare("INSERT INTO log_actividad (id_ejecutor, id_afectado, accion, detalle) VALUES (?, ?, ?, ?)");
                $stmtLog->bind_param("iiss", $usuario_ejecutor_id, $id_usuario, $accion, $detalle);
                $stmtLog->execute();
                // -----------------

                echo json_encode([
                    'success' => true,
                    'message' => 'Plantilla asignada correctamente'
                ]);
            } else {
                throw new Exception('Error al asignar plantilla');
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
        break;

    // ------------------------------------------------------------------------
    // ASIGNAR PLANTILLA A MÚLTIPLES USUARIOS (MASIVO)
    // ------------------------------------------------------------------------
    case 'asignar_plantilla_masiva':
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            $usuarios_ids = $data['usuarios'] ?? [];
            $plantilla_id = $data['plantilla_id'] ?? null;

            if (empty($usuarios_ids) || !$plantilla_id) {
                throw new Exception('Datos incompletos');
            }

            // Verificar que puede gestionar todos los usuarios
            foreach ($usuarios_ids as $id_usuario) {
                if (!$middleware->puedeGestionarPermisos($usuario_ejecutor_id, $id_usuario)) {
                    http_response_code(403);
                    echo json_encode([
                        'success' => false,
                        'message' => 'No tienes permisos para gestionar uno o más usuarios seleccionados'
                    ]);
                    exit;
                }
            }

            $resultado = $modelo->asignarPlantillaMasiva($usuarios_ids, $plantilla_id, $usuario_ejecutor_id);

            echo json_encode($resultado);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
        break;

    // ------------------------------------------------------------------------
    // GUARDAR PERMISOS PERSONALIZADOS
    // ------------------------------------------------------------------------
    case 'guardar_permisos':
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            $id_usuario = $data['id_usuario'] ?? null;
            $permisos = $data['permisos'] ?? [];
            $razon = $data['razon'] ?? 'Permisos personalizados';

            if (!$id_usuario || empty($permisos)) {
                throw new Exception('Datos incompletos');
            }

            // Verificar si puede gestionar permisos de este usuario
            if (!$middleware->puedeGestionarPermisos($usuario_ejecutor_id, $id_usuario)) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'No tienes permisos para gestionar este usuario'
                ]);
                exit;
            }

            // Normalizar permisos para evitar duplicados por case-sensitivity y asegurar consistencia
            $permisos_normalizados = [];
            foreach ($permisos as $modulo_slug => $acciones) {
                // Convertir slug de módulo a minúsculas y trim
                $slug_mod_clean = strtolower(trim($modulo_slug));

                if (!isset($permisos_normalizados[$slug_mod_clean])) {
                    $permisos_normalizados[$slug_mod_clean] = [];
                }

                foreach ($acciones as $accion_slug => $permitido) {
                    // Convertir acción a mayúsculas y trim (VER, CREAR, etc.)
                    $slug_acc_clean = strtoupper(trim($accion_slug));

                    // Si hay conflicto (ej: VER=true y VER=false), se queda con el último valor.
                    // Idealmente priorizamos true, pero el último valor procesado mandará.
                    $permisos_normalizados[$slug_mod_clean][$slug_acc_clean] = $permitido;
                }
            }
            // Reemplazar array original
            $permisos = $permisos_normalizados;

            $resultado = $modelo->guardarPermisosPersonalizados($id_usuario, $permisos, $usuario_ejecutor_id, $razon);

            if ($resultado) {
                // --- AUDITORIA ---
                $accion = "PERMISOS";
                $detalle = "Modificación de permisos personalizados. Razón: " . $razon;
                $stmtLog = $conexion->prepare("INSERT INTO log_actividad (id_ejecutor, id_afectado, accion, detalle) VALUES (?, ?, ?, ?)");
                $stmtLog->bind_param("iiss", $usuario_ejecutor_id, $id_usuario, $accion, $detalle);
                $stmtLog->execute();
                // -----------------

                echo json_encode([
                    'success' => true,
                    'message' => 'Permisos guardados correctamente'
                ]);
            } else {
                throw new Exception('Error al guardar permisos');
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
        break;

    // ------------------------------------------------------------------------
    // COPIAR PERMISOS DE UN USUARIO A OTROS
    // ------------------------------------------------------------------------
    case 'copiar_permisos':
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            $usuario_origen_id = $data['usuario_origen_id'] ?? null;
            $usuarios_destino_ids = $data['usuarios_destino_ids'] ?? [];

            if (!$usuario_origen_id || empty($usuarios_destino_ids)) {
                throw new Exception('Datos incompletos');
            }

            // Verificar que puede gestionar todos los usuarios
            foreach ($usuarios_destino_ids as $id_usuario) {
                if (!$middleware->puedeGestionarPermisos($usuario_ejecutor_id, $id_usuario)) {
                    http_response_code(403);
                    echo json_encode([
                        'success' => false,
                        'message' => 'No tienes permisos para gestionar uno o más usuarios seleccionados'
                    ]);
                    exit;
                }
            }

            $resultado = $modelo->copiarPermisos($usuario_origen_id, $usuarios_destino_ids, $usuario_ejecutor_id);

            echo json_encode($resultado);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
        break;

    // ------------------------------------------------------------------------
    // RESTABLECER PERMISOS A PLANTILLA POR DEFECTO
    // ------------------------------------------------------------------------
    case 'restablecer_permisos':
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            $id_usuario = $data['id_usuario'] ?? null;

            if (!$id_usuario) {
                throw new Exception('ID de usuario no especificado');
            }

            // Verificar si puede gestionar permisos de este usuario
            if (!$middleware->puedeGestionarPermisos($usuario_ejecutor_id, $id_usuario)) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'No tienes permisos para gestionar este usuario'
                ]);
                exit;
            }

            $resultado = $modelo->restablecerPermisos($id_usuario, $usuario_ejecutor_id);

            if ($resultado) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Permisos restablecidos a plantilla por defecto'
                ]);
            } else {
                throw new Exception('Error al restablecer permisos');
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
        break;

    // ------------------------------------------------------------------------
    // OBTENER HISTORIAL DE PERMISOS
    // ------------------------------------------------------------------------
    case 'obtener_historial':
        try {
            $id_usuario = $_GET['id_usuario'] ?? null;
            $limite = $_GET['limite'] ?? 50;

            if (!$id_usuario) {
                throw new Exception('ID de usuario no especificado');
            }

            // Verificar si puede gestionar permisos de este usuario
            if (!$middleware->puedeGestionarPermisos($usuario_ejecutor_id, $id_usuario)) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'No tienes permisos para ver el historial de este usuario'
                ]);
                exit;
            }

            $historial = $modelo->obtenerHistorialPermisos($id_usuario, $limite);

            echo json_encode([
                'success' => true,
                'data' => $historial
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
        break;

    // ------------------------------------------------------------------------
    // OBTENER ESTRUCTURA DE PERMISOS
    // ------------------------------------------------------------------------
    case 'obtener_estructura':
        try {
            $estructura = $modelo->obtenerEstructuraPermisos();

            echo json_encode([
                'success' => true,
                'data' => $estructura
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
        break;

    // ------------------------------------------------------------------------
    // OPERACIÓN NO VÁLIDA
    // ------------------------------------------------------------------------
    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Operación no válida: ' . $op
        ]);
        break;
}
