<?php
// DestinoControlador.php
ob_start(); // Iniciar buffer de salida
require_once __DIR__ . '/../modelo/DestinoModelo.php';

// Validar sesión activa (seguridad básica)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['status' => false, 'msg' => 'Sesión no válida']);
    exit();
}

if (isset($_GET['op'])) {
    $controlador = new DestinoControlador();
    $op = $_GET['op'];

    switch ($op) {
        case 'listar':
            $controlador->listar();
            break;
        case 'guardar':
            $controlador->guardar();
            break;
        case 'obtener':
            $controlador->obtener();
            break;
        case 'eliminar':
            $controlador->eliminar();
            break;
    }
}

class DestinoControlador
{
    private $modelo;

    public function __construct()
    {
        $this->modelo = new DestinoModelo();
    }

    public function listar()
    {
        // Limpiar cualquier buffer previo (espacios, warnings, etc.)
        ob_clean();
        $id_region = $_GET['id_region'] ?? null;
        $id_division = $_GET['id_division'] ?? null;
        
        $data = $this->modelo->listar($id_region, $id_division);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['data' => $data]);
        exit(); // Asegurar que nada más se imprima
    }

    public function obtener()
    {
        $id = $_POST['id'] ?? 0;
        $data = $this->modelo->obtener($id);
        echo json_encode($data);
    }

    public function guardar()
    {
        $id = $_POST['id_destino'] ?? '';
        $nombre = strtoupper(trim($_POST['nombre_destino'] ?? ''));
        $orden = isset($_POST['orden']) ? (int) $_POST['orden'] : 999;
        
        $id_region = !empty($_POST['id_region']) ? (int) $_POST['id_region'] : null;
        $id_division = !empty($_POST['id_division']) ? (int) $_POST['id_division'] : null;
        $id_subunidad = !empty($_POST['id_subunidad']) ? (int) $_POST['id_subunidad'] : null;

        if (empty($nombre)) {
            echo json_encode(['status' => false, 'msg' => 'El nombre es obligatorio.']);
            return;
        }

        $activo = isset($_POST['activo']) ? (int) $_POST['activo'] : 1;

        if (empty($id)) {
            // Nuevo
            $resp = $this->modelo->registrar($nombre, $orden, $id_region, $id_division, $id_subunidad);
        } else {
            // Editar
            $resp = $this->modelo->actualizar($id, $nombre, $activo, $orden, $id_region, $id_division, $id_subunidad);
        }
        echo json_encode($resp);
    }

    public function eliminar()
    {
        $id = $_POST['id'] ?? 0;
        $resp = $this->modelo->eliminar($id);
        echo json_encode($resp);
    }
}
