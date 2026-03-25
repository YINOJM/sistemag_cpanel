<?php
// controlador/CapacitacionControlador.php
require_once __DIR__ . '/../modelo/conexion.php';
require_once __DIR__ . '/../modelo/CapacitacionModelo.php';


class CapacitacionControlador {
    private $modelo;

    public function __construct() {
        $this->modelo = new CapacitacionModelo();
    }

    public function listar() {
        header('Content-Type: application/json');
        $data = $this->modelo->listar();
        echo json_encode(['data' => $data]);
    }

    public function obtenerPorId() {
        header('Content-Type: application/json');
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $data = $this->modelo->obtenerPorId($id);
        if ($data) {
            echo json_encode(['status' => true, 'data' => $data]);
        } else {
            echo json_encode(['status' => false, 'msg' => 'No encontrado']);
        }
    }

    public function guardar() {
        header('Content-Type: application/json');

        $titulo = $_POST['titulo'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $url_video = $_POST['url_video'] ?? '';
        
        // Manejo de archivo adjunto
        $archivo_adjunto = '';
        if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
            $nombre_archivo = time() . '_' . $_FILES['archivo']['name'];
            $ruta_destino = __DIR__ . '/../vista/uploads/capacitaciones/';
            
            if (!file_exists($ruta_destino)) {
                mkdir($ruta_destino, 0777, true);
            }
            
            if (move_uploaded_file($_FILES['archivo']['tmp_name'], $ruta_destino . $nombre_archivo)) {
                $archivo_adjunto = 'uploads/capacitaciones/' . $nombre_archivo;
            }
        }

        $datos = [
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'url_video' => $url_video,
            'archivo_adjunto' => $archivo_adjunto
        ];

        echo json_encode($this->modelo->registrar($datos));
    }

    public function actualizar() {
        header('Content-Type: application/json');

        $id = $_POST['id'] ?? 0;
        $titulo = $_POST['titulo'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $url_video = $_POST['url_video'] ?? '';
        
        // Recuperar datos actuales para mantener el archivo si no se sube uno nuevo
        $actual = $this->modelo->obtenerPorId($id);
        $archivo_adjunto = $actual['archivo_adjunto'];

        if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
            $nombre_archivo = time() . '_' . $_FILES['archivo']['name'];
            $ruta_destino = __DIR__ . '/../vista/uploads/capacitaciones/';
            
            if (!file_exists($ruta_destino)) {
                mkdir($ruta_destino, 0777, true);
            }
            
            if (move_uploaded_file($_FILES['archivo']['tmp_name'], $ruta_destino . $nombre_archivo)) {
                $archivo_adjunto = 'uploads/capacitaciones/' . $nombre_archivo;
            }
        }

        $datos = [
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'url_video' => $url_video,
            'archivo_adjunto' => $archivo_adjunto
        ];

        echo json_encode($this->modelo->actualizar($id, $datos));
    }

    public function eliminar() {
        header('Content-Type: application/json');
        $id = $_POST['id'] ?? 0;
        echo json_encode($this->modelo->eliminar($id));
    }
}

// Router Simple
if(isset($_GET['op'])) {
    $controller = new CapacitacionControlador();
    switch($_GET['op']) {
        case 'listar': $controller->listar(); break;
        case 'obtener': $controller->obtenerPorId(); break;
        case 'guardar': $controller->guardar(); break;
        case 'actualizar': $controller->actualizar(); break;
        case 'eliminar': $controller->eliminar(); break;
    }
}
?>
