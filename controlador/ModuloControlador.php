<?php
// controlador/ModuloControlador.php

require_once __DIR__ . '/../modelo/conexion.php';
require_once __DIR__ . '/../modelo/ModuloModelo.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'Super Administrador') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

$modelo = new ModuloModelo();
$op = $_GET['op'] ?? $_POST['op'] ?? '';

switch ($op) {
    case 'listar':
        echo json_encode(['success' => true, 'data' => $modelo->listar()]);
        break;

    case 'guardar':
        $data = json_decode(file_get_contents('php://input'), true);
        if ($modelo->guardar($data)) {
            echo json_encode(['success' => true, 'message' => 'Módulo guardado correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar el módulo']);
        }
        break;

    case 'toggle':
        $id = $_GET['id'] ?? null;
        if ($id && $modelo->toggleEstado($id)) {
            echo json_encode(['success' => true, 'message' => 'Estado actualizado']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al cambiar estado']);
        }
        break;

    case 'eliminar':
        $id = $_GET['id'] ?? null;
        if ($id && $modelo->eliminar($id)) {
            echo json_encode(['success' => true, 'message' => 'Módulo eliminado']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar módulo']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Operación no válida']);
        break;
}
