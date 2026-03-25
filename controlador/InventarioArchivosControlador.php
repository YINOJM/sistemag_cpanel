<?php
// controlador/InventarioArchivosControlador.php
declare(strict_types=1);

class InventarioArchivosControlador
{
    private $uploadDir = __DIR__ . '/../uploads/inventario/';
    private $maxFileSize = 10485760; // 10MB en bytes
    private $allowedExtensions = ['pdf', 'xlsx', 'xls', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif'];

    public function __construct()
    {
        // Crear directorio si no existe
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Subir archivo
     */
    public function subirArchivo()
    {
        header('Content-Type: application/json');

        if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['status' => false, 'msg' => 'No se recibió ningún archivo']);
            return;
        }

        $archivo = $_FILES['archivo'];
        $anio = $_POST['anio'] ?? date('Y');

        // Validar tamaño
        if ($archivo['size'] > $this->maxFileSize) {
            echo json_encode(['status' => false, 'msg' => 'El archivo excede el tamaño máximo de 10MB']);
            return;
        }

        // Validar extensión
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            echo json_encode([
                'status' => false,
                'msg' => 'Tipo de archivo no permitido. Permitidos: ' . implode(', ', $this->allowedExtensions)
            ]);
            return;
        }

        // Crear directorio del año si no existe
        $yearDir = $this->uploadDir . $anio . '/';
        if (!is_dir($yearDir)) {
            mkdir($yearDir, 0755, true);
        }

        // Generar nombre único
        $nombreOriginal = pathinfo($archivo['name'], PATHINFO_FILENAME);
        $nombreArchivo = date('Ymd_His') . '_' . $this->sanitizeFilename($nombreOriginal) . '.' . $extension;
        $rutaCompleta = $yearDir . $nombreArchivo;
        $rutaRelativa = 'uploads/inventario/' . $anio . '/' . $nombreArchivo;

        // Mover archivo
        if (move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
            $info = [
                'nombre_original' => $archivo['name'],
                'nombre_archivo' => $nombreArchivo,
                'ruta' => $rutaRelativa,
                'tamano' => $archivo['size'],
                'tipo' => $archivo['type'],
                'fecha_subida' => date('Y-m-d H:i:s')
            ];

            echo json_encode([
                'status' => true,
                'msg' => 'Archivo subido correctamente',
                'data' => $info
            ]);
        } else {
            echo json_encode(['status' => false, 'msg' => 'Error al guardar el archivo']);
        }
    }

    /**
     * Eliminar archivo
     */
    public function eliminarArchivo()
    {
        header('Content-Type: application/json');

        $ruta = $_POST['ruta'] ?? '';

        if (empty($ruta)) {
            echo json_encode(['status' => false, 'msg' => 'Ruta no especificada']);
            return;
        }

        $rutaCompleta = __DIR__ . '/../' . $ruta;

        if (file_exists($rutaCompleta)) {
            if (unlink($rutaCompleta)) {
                echo json_encode(['status' => true, 'msg' => 'Archivo eliminado']);
            } else {
                echo json_encode(['status' => false, 'msg' => 'Error al eliminar el archivo']);
            }
        } else {
            echo json_encode(['status' => false, 'msg' => 'Archivo no encontrado']);
        }
    }

    /**
     * Sanitizar nombre de archivo
     */
    private function sanitizeFilename($filename)
    {
        // Reemplazar caracteres especiales
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
        // Limitar longitud
        return substr($filename, 0, 50);
    }

    /**
     * Formatear tamaño de archivo
     */
    public static function formatFileSize($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}

// Enrutador
$op = $_GET['op'] ?? '';
$controlador = new InventarioArchivosControlador();

switch ($op) {
    case 'subir':
        $controlador->subirArchivo();
        break;
    case 'eliminar':
        $controlador->eliminarArchivo();
        break;
    default:
        echo json_encode(['status' => false, 'msg' => 'Operación no válida']);
}
