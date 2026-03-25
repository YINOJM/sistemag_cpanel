<?php
/**
 * Controlador de Unidades Policiales
 * Sistema Integrado de Gestión - UE009 DIRTEPOL LIMA
 */

// 1. CARGAR CONEXIÓN Y ENTORNO (Centralizado)
require_once __DIR__ . '/../modelo/conexion.php';
require_once __DIR__ . '/../modelo/UnidadesPoliciales.php';

ob_start();
// session_start(); removido porque lo maneja conexion.php -> sesion_config.php

// Validar sesión
if (empty($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['status' => false, 'msg' => 'Sesión no válida']);
    exit();
}

header('Content-Type: application/json; charset=utf-8');

$modelo = new UnidadesPoliciales($conexion);
$op = $_GET['op'] ?? '';
$tipo = $_GET['tipo'] ?? 'subunidad'; // region, division, subunidad

try {
    switch ($op) {
        // ==========================================
        // OPERACIONES DE LISTADO
        // ==========================================
        case 'listar':
            if ($tipo === 'region') {
                $data = $modelo->obtenerRegiones();
            } elseif ($tipo === 'division') {
                $idRegion = $_GET['id_region'] ?? null;
                $data = $modelo->obtenerDivisiones($idRegion);
            } else {
                $idDivision = $_GET['id_division'] ?? null;
                $data = $modelo->obtenerSubUnidades($idDivision);
            }
            echo json_encode(['status' => true, 'data' => $data]);
            break;

        case 'listar_jerarquia':
            $data = $modelo->obtenerJerarquiaCompleta();
            echo json_encode(['status' => true, 'data' => $data]);
            break;

        case 'listar_distritos':
            $data = $modelo->obtenerDistritosUnicos();
            echo json_encode(['status' => true, 'data' => $data]);
            break;

        case 'listar_tipos':
            $data = $modelo->obtenerTiposUnidad();
            echo json_encode(['status' => true, 'data' => $data]);
            break;

        case 'listar_ubicaciones':
            $data = $modelo->obtenerUbicaciones();
            echo json_encode(['status' => true, 'data' => $data]);
            break;

        // ==========================================
        // OPERACIONES DE OBTENCIÓN INDIVIDUAL
        // ==========================================
        case 'obtener':
            $id = $_POST['id'] ?? 0;

            if ($tipo === 'region') {
                $data = $modelo->obtenerRegionPorId($id);
            } elseif ($tipo === 'division') {
                $data = $modelo->obtenerDivisionPorId($id);
            } else {
                $data = $modelo->obtenerSubUnidadPorId($id);
            }

            echo json_encode(['status' => true, 'data' => $data]);
            break;

        // ==========================================
        // OPERACIONES DE GUARDADO
        // ==========================================
        case 'guardar':
            $datos = $_POST;
            $datos['usuario_id'] = $_SESSION['id'];
            $id = $_POST['id'] ?? null;

            if ($tipo === 'region') {
                if (empty($datos['nombre_region'])) {
                    throw new Exception('El nombre de la región es obligatorio');
                }

                if ($id) {
                    $resultado = $modelo->actualizarRegion($id, $datos);
                    $mensaje = 'Región actualizada correctamente';
                } else {
                    $resultado = $modelo->crearRegion($datos);
                    $mensaje = 'Región creada correctamente';
                }
            } elseif ($tipo === 'division') {
                if (empty($datos['nombre_division']) || empty($datos['id_region'])) {
                    throw new Exception('El nombre de la división y la región son obligatorios');
                }

                if ($id) {
                    $resultado = $modelo->actualizarDivision($id, $datos);
                    $mensaje = 'División actualizada correctamente';
                } else {
                    $resultado = $modelo->crearDivision($datos);
                    $mensaje = 'División creada correctamente';
                }
            } else {
                if (empty($datos['nombre_subunidad']) || empty($datos['id_division'])) {
                    throw new Exception('El nombre de la sub-unidad y la división son obligatorios');
                }

                if ($id) {
                    $resultado = $modelo->actualizarSubUnidad($id, $datos);
                    $mensaje = 'Sub-unidad actualizada correctamente';
                } else {
                    $resultado = $modelo->crearSubUnidad($datos);
                    $mensaje = 'Sub-unidad creada correctamente';
                }
            }

            if ($resultado) {
                echo json_encode(['status' => true, 'msg' => $mensaje]);
            } else {
                throw new Exception('Error al guardar los datos');
            }
            break;

        // ==========================================
        // OPERACIONES DE ELIMINACIÓN
        // ==========================================
        case 'eliminar':
            $id = $_POST['id'] ?? 0;

            if ($tipo === 'region') {
                $resultado = $modelo->eliminarRegion($id);
                $mensaje = 'Región eliminada correctamente';
            } elseif ($tipo === 'division') {
                $resultado = $modelo->eliminarDivision($id);
                $mensaje = 'División eliminada correctamente';
            } else {
                $resultado = $modelo->eliminarSubUnidad($id);
                $mensaje = 'Sub-unidad eliminada correctamente';
            }

            if ($resultado) {
                echo json_encode(['status' => true, 'msg' => $mensaje]);
            } else {
                throw new Exception('Error al eliminar el registro');
            }
            break;

        // ==========================================
        // BÚSQUEDA
        // ==========================================
        case 'buscar':
            $termino = $_GET['termino'] ?? '';
            if (strlen($termino) < 2) {
                throw new Exception('Ingrese al menos 2 caracteres para buscar');
            }

            $data = $modelo->buscarUnidad($termino);
            echo json_encode(['status' => true, 'data' => $data]);
            break;

        // ==========================================
        // ESTADÍSTICAS
        // ==========================================
        case 'estadisticas':
            $data = $modelo->obtenerEstadisticas();
            echo json_encode(['status' => true, 'data' => $data]);
            break;

        case 'contar_tipos':
            $data = $modelo->contarPorTipo();
            echo json_encode(['status' => true, 'data' => $data]);
            break;

        // ==========================================
        // LIMPIAR TODOS LOS DATOS
        // ==========================================
        case 'limpiar_todos':
            // Verificar que sea administrador
            if ($_SESSION['rol'] !== 'Administrador' && $_SESSION['rol'] !== 'Super Administrador') {
                throw new Exception('No tienes permisos para realizar esta acción');
            }

            // Limpiar sub-unidades
            $stmt1 = $conexion->query("DELETE FROM sub_unidades_policiales");
            $subunidades = $conexion->affected_rows;

            // Limpiar divisiones
            $stmt2 = $conexion->query("DELETE FROM divisiones_policiales");
            $divisiones = $conexion->affected_rows;

            // Limpiar regiones
            $stmt3 = $conexion->query("DELETE FROM regiones_policiales");
            $regiones = $conexion->affected_rows;

            // Resetear auto_increment
            $conexion->query("ALTER TABLE sub_unidades_policiales AUTO_INCREMENT = 1");
            $conexion->query("ALTER TABLE divisiones_policiales AUTO_INCREMENT = 1");
            $conexion->query("ALTER TABLE regiones_policiales AUTO_INCREMENT = 1");

            echo json_encode([
                'status' => true,
                'msg' => 'Todos los datos han sido eliminados correctamente',
                'subunidades' => $subunidades,
                'divisiones' => $divisiones,
                'regiones' => $regiones
            ]);
            break;

        default:
            throw new Exception('Operación no válida');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => false, 'msg' => $e->getMessage()]);
}

ob_end_flush();
?>