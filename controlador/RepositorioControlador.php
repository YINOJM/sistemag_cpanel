<?php
// controlador/RepositorioControlador.php
declare(strict_types=1);

// Limpiar cualquier salida previa
if (ob_get_level())
    ob_clean();

// Requerir conexión primero para asegurar que cargue sesion_config.php
require_once __DIR__ . '/../modelo/conexion.php';

require_once __DIR__ . '/../modelo/RepositorioModelo.php';
require_once __DIR__ . '/../modelo/RepositorioCategoriaModelo.php';

class RepositorioControlador
{
    private $modelo;
    private $uploadDir = __DIR__ . '/../uploads/repositorio/';
    private $maxFileSize = 52428800; // 50MB en bytes
    private $allowedExtensions = ['pdf', 'xlsx', 'xls', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif', 'zip', 'rar'];

    public function __construct()
    {
        try {
            $this->modelo = new RepositorioModelo();

            // Crear directorio si no existe
            if (!is_dir($this->uploadDir)) {
                mkdir($this->uploadDir, 0755, true);
            }
        } catch (Exception $e) {
            error_log("Error en RepositorioControlador: " . $e->getMessage());
            die(json_encode(['status' => false, 'msg' => 'Error al inicializar: ' . $e->getMessage()]));
        }
    }

    /**
     * Listar documentos
     */
    public function listar()
    {
        header('Content-Type: application/json');

        $anio = isset($_GET['anio']) ? (int) $_GET['anio'] : null;
        $categoria = $_GET['categoria'] ?? null;
        $busqueda = $_GET['busqueda'] ?? null;

        try {
            $data = $this->modelo->listar($anio, $categoria, $busqueda);
            error_log("Documentos encontrados: " . count($data));
            echo json_encode(['status' => true, 'data' => $data]);
        } catch (Exception $e) {
            error_log("Error en listar: " . $e->getMessage());
            echo json_encode(['status' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    /**
     * Obtener años disponibles
     */
    public function obtenerAnios()
    {
        header('Content-Type: application/json');
        $data = $this->modelo->obtenerAnios();
        echo json_encode(['status' => true, 'data' => $data]);
    }

    /**
     * Obtener categorías disponibles
     */
    public function obtenerCategorias()
    {
        header('Content-Type: application/json');
        $modeloCategoria = new RepositorioCategoriaModelo();
        $data = $modeloCategoria->listar();
        echo json_encode(['status' => true, 'data' => $data]);
    }

    /**
     * Subir archivo
     */
    public function subir()
    {
        header('Content-Type: application/json');

        try {
            if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['status' => false, 'msg' => 'No se recibió ningún archivo']);
                return;
            }

            $archivo = $_FILES['archivo'];
            $anio = (int) ($_POST['anio'] ?? date('Y'));
            $categoria = trim($_POST['categoria'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $usuarioId = (int) ($_SESSION['id'] ?? 0);

            if ($usuarioId === 0) {
                echo json_encode(['status' => false, 'msg' => 'Usuario no autenticado']);
                return;
            }

            // Validar tamaño
            if ($archivo['size'] > $this->maxFileSize) {
                echo json_encode(['status' => false, 'msg' => 'El archivo excede el tamaño máximo de 50MB']);
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
            $nombreSistema = date('Ymd_His') . '_' . $this->sanitizeFilename($nombreOriginal) . '.' . $extension;
            $rutaCompleta = $yearDir . $nombreSistema;
            $rutaRelativa = 'uploads/repositorio/' . $anio . '/' . $nombreSistema;

            // Mover archivo
            if (move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
                $datos = [
                    'nombre_archivo' => $archivo['name'],
                    'nombre_sistema' => $nombreSistema,
                    'ruta_archivo' => $rutaRelativa,
                    'anio' => $anio,
                    'categoria' => $categoria,
                    'descripcion' => $descripcion,
                    'tipo_archivo' => $archivo['type'],
                    'extension' => $extension,
                    'tamano' => $archivo['size'],
                    'usuario_subida' => $usuarioId
                ];

                $result = $this->modelo->registrar($datos);
                echo json_encode($result);
            } else {
                echo json_encode(['status' => false, 'msg' => 'Error al guardar el archivo en el servidor']);
            }
        } catch (Exception $e) {
            error_log("Error al subir archivo: " . $e->getMessage());
            echo json_encode(['status' => false, 'msg' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Actualizar información de documento
     */
    public function actualizar()
    {
        header('Content-Type: application/json');

        $id = (int) ($_POST['id'] ?? 0);
        $categoria = trim($_POST['categoria'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');

        if ($id <= 0) {
            echo json_encode(['status' => false, 'msg' => 'ID inválido']);
            return;
        }

        $datos = [
            'categoria' => $categoria,
            'descripcion' => $descripcion
        ];

        $result = $this->modelo->actualizar($id, $datos);
        echo json_encode($result);
    }

    /**
     * Eliminar documento
     */
    public function eliminar()
    {
        header('Content-Type: application/json');

        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['status' => false, 'msg' => 'ID inválido']);
            return;
        }

        // Obtener información del archivo
        $documento = $this->modelo->obtenerPorId($id);

        if (!$documento) {
            echo json_encode(['status' => false, 'msg' => 'Documento no encontrado']);
            return;
        }

        // Eliminar archivo físico
        $rutaCompleta = __DIR__ . '/../' . $documento['ruta_archivo'];
        if (file_exists($rutaCompleta)) {
            unlink($rutaCompleta);
        }

        // Eliminar registro de BD
        $result = $this->modelo->eliminar($id);
        echo json_encode($result);
    }

    /**
     * Descargar archivo
     */
    public function descargar()
    {
        $id = (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            die('ID inválido');
        }

        $documento = $this->modelo->obtenerPorId($id);

        if (!$documento) {
            die('Documento no encontrado');
        }

        $rutaCompleta = __DIR__ . '/../' . $documento['ruta_archivo'];

        if (!file_exists($rutaCompleta)) {
            die('Archivo no encontrado');
        }

        // Limpiar completamente cualquier salida previa
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Sanitizar el nombre del archivo
        $nombreArchivo = $documento['nombre_archivo'];

        // Usar el tipo MIME original del archivo
        $contentType = $documento['tipo_archivo'] ?: 'application/octet-stream';

        // Configurar headers para descarga
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . filesize($rutaCompleta));
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Expires: 0');

        // Leer y enviar el archivo en chunks para archivos grandes
        $handle = fopen($rutaCompleta, 'rb');
        while (!feof($handle)) {
            echo fread($handle, 8192);
            flush();
        }
        fclose($handle);
        exit;
    }

    /**
     * Visualizar archivo (Inline)
     */
    public function visualizar()
    {
        $id = (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            die('ID inválido');
        }

        $documento = $this->modelo->obtenerPorId($id);

        if (!$documento) {
            die('Documento no encontrado');
        }

        $rutaCompleta = __DIR__ . '/../' . $documento['ruta_archivo'];

        if (!file_exists($rutaCompleta)) {
            die('Archivo no encontrado');
        }

        // Limpiar
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Determinar MIME type basado en extensión para asegurar visualización
        $extension = strtolower(pathinfo($rutaCompleta, PATHINFO_EXTENSION));
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'txt' => 'text/plain'
        ];

        $contentType = $mimeTypes[$extension] ?? $documento['tipo_archivo'] ?? 'application/octet-stream';
        
        // Headers para visualización
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: inline; filename="' . $documento['nombre_archivo'] . '"');
        header('Content-Length: ' . filesize($rutaCompleta));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        readfile($rutaCompleta);
        exit;
    }

    /**
     * Obtener estadísticas
     */
    public function estadisticas()
    {
        header('Content-Type: application/json');
        $data = $this->modelo->obtenerEstadisticas();
        echo json_encode(['status' => true, 'data' => $data]);
    }

    /**
     * Verificar almacenamiento del repositorio
     */
    public function verificarAlmacenamiento()
    {
        header('Content-Type: application/json');

        // Ruta del directorio de uploads del repositorio
        $repositorioPath = __DIR__ . '/../uploads/repositorio/';

        // Límite máximo para el repositorio (en bytes) - 2 GB por defecto
        $maxRepositorioSize = 2 * 1073741824; // 2 GB

        // Calcular espacio usado por el repositorio
        $usedSpace = 0;
        if (is_dir($repositorioPath)) {
            $usedSpace = $this->getDirectorySize($repositorioPath);
        }

        // Calcular porcentaje usado
        $percentage = ($usedSpace / $maxRepositorioSize) * 100;

        // Definir umbrales
        $warningThreshold = 80; // 80% del límite del repositorio
        $criticalThreshold = 95; // 95% del límite del repositorio

        $status = 'normal';
        $message = '';

        if ($percentage >= $criticalThreshold) {
            $status = 'critical';
            $message = '¡Crítico! El repositorio está casi lleno (' . round($percentage, 1) . '% de ' . round($maxRepositorioSize / 1073741824, 2) . ' GB). No se podrán subir más archivos.';
        } elseif ($percentage >= $warningThreshold) {
            $status = 'warning';
            $message = 'Advertencia: El repositorio está al ' . round($percentage, 1) . '% de su capacidad (' . round($maxRepositorioSize / 1073741824, 2) . ' GB). Considere liberar espacio.';
        }

        echo json_encode([
            'status' => true,
            'data' => [
                'total_gb' => round($maxRepositorioSize / 1073741824, 2),
                'used_gb' => round($usedSpace / 1073741824, 2),
                'used_mb' => round($usedSpace / 1048576, 2),
                'free_gb' => round(($maxRepositorioSize - $usedSpace) / 1073741824, 2),
                'used_percentage' => round($percentage, 2),
                'storage_status' => $status,
                'message' => $message
            ]
        ]);
    }

    /**
     * Calcular tamaño de un directorio recursivamente
     */
    private function getDirectorySize($path)
    {
        $size = 0;

        if (!is_dir($path)) {
            return 0;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    /**
     * Sanitizar nombre de archivo
     */
    private function sanitizeFilename($filename)
    {
        // Reemplazar caracteres especiales
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
        // Limitar longitud
        return substr($filename, 0, 100);
    }

    /**
     * MÉTODOS PARA GESTIÓN DE CATEGORÍAS
     */

    public function listarCategorias()
    {
        header('Content-Type: application/json');
        $modeloCategoria = new RepositorioCategoriaModelo();
        $data = $modeloCategoria->listar();
        echo json_encode(['status' => true, 'data' => $data]);
    }

    public function listarCategoriasConConteo()
    {
        header('Content-Type: application/json');
        $modeloCategoria = new RepositorioCategoriaModelo();
        $data = $modeloCategoria->listarConConteo();
        echo json_encode(['status' => true, 'data' => $data]);
    }

    public function crearCategoria()
    {
        header('Content-Type: application/json');

        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $color = $_POST['color'] ?? 'primary';
        $orden = (int) ($_POST['orden'] ?? 0);

        if (empty($nombre)) {
            echo json_encode(['status' => false, 'msg' => 'El nombre es obligatorio']);
            return;
        }

        $datos = [
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'color' => $color,
            'orden' => $orden
        ];

        $modeloCategoria = new RepositorioCategoriaModelo();
        $result = $modeloCategoria->crear($datos);
        echo json_encode($result);
    }

    public function actualizarCategoria()
    {
        header('Content-Type: application/json');

        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $color = $_POST['color'] ?? 'primary';
        $orden = (int) ($_POST['orden'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['status' => false, 'msg' => 'ID inválido']);
            return;
        }

        if (empty($nombre)) {
            echo json_encode(['status' => false, 'msg' => 'El nombre es obligatorio']);
            return;
        }

        $datos = [
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'color' => $color,
            'orden' => $orden
        ];

        $modeloCategoria = new RepositorioCategoriaModelo();
        $result = $modeloCategoria->actualizar($id, $datos);
        echo json_encode($result);
    }

    public function eliminarCategoria()
    {
        header('Content-Type: application/json');

        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['status' => false, 'msg' => 'ID inválido']);
            return;
        }

        $modeloCategoria = new RepositorioCategoriaModelo();
        $result = $modeloCategoria->eliminar($id);
        echo json_encode($result);
    }
    public function cambiarOrdenCategoria()
    {
        header('Content-Type: application/json');

        $id = (int) ($_POST['id'] ?? 0);
        $direccion = $_POST['direccion'] ?? '';

        if ($id <= 0 || !in_array($direccion, ['up', 'down'])) {
            echo json_encode(['status' => false, 'msg' => 'Datos inválidos']);
            return;
        }

        $modeloCategoria = new RepositorioCategoriaModelo();
        $result = $modeloCategoria->cambiarOrden($id, $direccion);
        echo json_encode($result);
    }
}

// Enrutador
$op = $_GET['op'] ?? '';
$controlador = new RepositorioControlador();

switch ($op) {
    case 'listar':
        $controlador->listar();
        break;
    case 'verificarAlmacenamiento':
        $controlador->verificarAlmacenamiento();
        break;
    case 'obtenerAnios':
        $controlador->obtenerAnios();
        break;
    case 'obtenerCategorias':
        $controlador->obtenerCategorias();
        break;
    case 'subir':
        $controlador->subir();
        break;
    case 'actualizar':
        $controlador->actualizar();
        break;
    case 'eliminar':
        $controlador->eliminar();
        break;
    case 'descargar':
        $controlador->descargar();
        break;
    case 'visualizar':
        $controlador->visualizar();
        break;
    case 'estadisticas':
        $controlador->estadisticas();
        break;

    // Operaciones de categorías
    case 'listarCategorias':
        $controlador->listarCategorias();
        break;
    case 'listarCategoriasConConteo':
        $controlador->listarCategoriasConConteo();
        break;
    case 'crearCategoria':
        $controlador->crearCategoria();
        break;
    case 'actualizarCategoria':
        $controlador->actualizarCategoria();
        break;
    case 'eliminarCategoria':
        $controlador->eliminarCategoria();
        break;
    case 'cambiarOrdenCategoria':
        $controlador->cambiarOrdenCategoria();
        break;

    default:
        echo json_encode(['status' => false, 'msg' => 'Operación no válida']);
}
