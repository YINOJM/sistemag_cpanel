<?php
// GradoControlador.php
ob_start();
require_once __DIR__ . '/../modelo/conexion.php';
require_once __DIR__ . '/../modelo/GradoModelo.php';

if (empty($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['status' => false, 'msg' => 'Sesión no válida']);
    exit();
}

// RESTRICT ACCESS TO ADMINS ONLY
$rolUsuario = $_SESSION['rol'] ?? 'Usuario';
$esAdmin = ($rolUsuario === 'Administrador' || $rolUsuario === 'Super Administrador');

if (isset($_GET['op'])) {
    $controlador = new GradoControlador();
    $op = $_GET['op'];

    // If attempting write operations without admin privileges, deny
    if (in_array($op, ['guardar', 'eliminar']) && !$esAdmin) {
        echo json_encode(['status' => false, 'msg' => 'Acceso denegado. No tiene permisos suficientes.']);
        exit();
    }

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

class GradoControlador
{
    private $modelo;

    public function __construct()
    {
        $this->modelo = new GradoModelo();
    }

    public function listar()
    {
        ob_clean();
        $data = $this->modelo->listar();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['data' => $data]);
        exit();
    }

    public function obtener()
    {
        $id = $_POST['id'] ?? 0;
        $data = $this->modelo->obtener($id);
        echo json_encode($data);
    }

    public function guardar()
    {
        $id = $_POST['id_grado'] ?? '';
        $nombre = strtoupper(trim($_POST['nombre_grado'] ?? ''));
        
        if (empty($nombre)) {
            echo json_encode(['status' => false, 'msg' => 'El nombre es obligatorio.']);
            return;
        }

        $activo = isset($_POST['activo']) ? (int) $_POST['activo'] : 1;

        if (empty($id)) {
            // Nuevo
            $resp = $this->modelo->registrar($nombre);
        } else {
            // Editar
            $resp = $this->modelo->actualizar($id, $nombre, $activo);
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
?>
