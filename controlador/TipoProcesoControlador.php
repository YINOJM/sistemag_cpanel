<?php
// controlador/TipoProcesoControlador.php
ob_start(); 
require_once __DIR__ . '/../modelo/TipoProcesoModelo.php'; 

// El modelo ya incluye conexion.php -> sesion_config.php (sesión ya configurada)
if (empty($_SESSION['id'])) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => false, 'msg' => 'Acceso denegado']);
    exit();
}

ob_clean();
header('Content-Type: application/json; charset=utf-8');

$modelo = new TipoProcesoModelo();
$op = $_GET['op'] ?? '';

switch ($op) {
    case 'listar':
        $datos = $modelo->listar();
        echo json_encode(['data' => $datos], JSON_UNESCAPED_UNICODE);
        break;

    case 'guardar':
        $nombre = strtoupper(trim($_POST['nombre'] ?? ''));
        $id = (int) ($_POST['id'] ?? 0);

        if (empty($nombre)) {
            echo json_encode(['status' => false, 'msg' => 'El nombre es obligatorio.']);
            exit;
        }

        if ($id > 0) {
            $res = $modelo->actualizar($id, $nombre);
            echo json_encode(['status' => $res, 'msg' => $res ? 'Actualizado correctamente' : 'Error al actualizar']);
        } else {
            $res = $modelo->registrar($nombre);
            echo json_encode(['status' => $res, 'msg' => $res ? 'Registrado correctamente' : 'Error al registrar']);
        }
        break;

    case 'cambiar_estado':
        $id = (int) ($_POST['id'] ?? 0);
        $estado = (int) ($_POST['estado'] ?? 1);
        $res = $modelo->cambiarEstado($id, $estado);
        echo json_encode(['status' => $res, 'msg' => $res ? 'Estado actualizado' : 'Error al actualizar estado']);
        break;

    case 'eliminar':
        $id = (int) ($_POST['id'] ?? 0);
        $res = $modelo->eliminar($id);
        if ($res) {
            echo json_encode(['status' => true, 'msg' => 'Eliminado correctamente']);
        } else {
            echo json_encode(['status' => false, 'msg' => 'No se puede eliminar porque está en uso o hubo un error']);
        }
        break;

    default:
        echo json_encode(['status' => false, 'msg' => 'Operación no válida']);
        break;
}
