<?php
// controlador/InventarioControlador.php
declare(strict_types=1);

// 1. CARGAR CONEXIÓN Y ENTORNO (Centralizado)
require_once __DIR__ . '/../modelo/conexion.php';
require_once __DIR__ . '/../modelo/InventarioModelo.php';

// Configuración de recursos para archivos grandes
ob_start();
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 600);
@ob_clean(); // Limpiar buffer de salida

// session_start(); removido porque lo maneja conexion.php -> sesion_config.php

class InventarioControlador
{
    private $modelo;

    public function __construct()
    {
        $this->modelo = new InventarioModelo();
    }

    // Listar para DataTables
    public function listar()
    {
        header('Content-Type: application/json');
        $anio = isset($_GET['anio']) ? (int) $_GET['anio'] : null;
        $idSubunidadGet = isset($_GET['id_subunidad']) && !empty($_GET['id_subunidad']) ? (int) $_GET['id_subunidad'] : null;
        
        $rol = $_SESSION['rol'] ?? '';
        $idOficinaSesion = $_SESSION['id_oficina'] ?? null;
        $idSubunidadSesion = $_SESSION['id_subunidad'] ?? null;
        
        // Lógica de filtrado:
        // 1. Si es Super Admin o Administrador, puede filtrar por la subunidad que pida por GET.
        // 2. Si es un usuario normal, siempre se filtra por su subunidad de sesión.
        
        $idSubunidadFiltro = null;
        $idOficinaFiltro = null;

        if ($rol === 'Super Administrador' || $rol === 'Administrador') {
            $idSubunidadFiltro = $idSubunidadGet; // Puede ser null (ve todo) o el ID seleccionado
        } else {
            $idSubunidadFiltro = $idSubunidadSesion;
            $idOficinaFiltro = $idOficinaSesion;
        }
        
        $data = $this->modelo->listar($anio, $idOficinaFiltro, $idSubunidadFiltro);
        echo json_encode(['data' => $data]);
    }

    // Obtener por ID
    public function obtenerPorId()
    {
        header('Content-Type: application/json');
        $id = (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['status' => false, 'msg' => 'ID inválido']);
            return;
        }

        $data = $this->modelo->obtenerPorId($id);
        if ($data) {
            echo json_encode(['status' => true, 'data' => $data]);
        } else {
            echo json_encode(['status' => false, 'msg' => 'Item no encontrado']);
        }
    }

    // Guardar nuevo item
    public function guardar()
    {
        header('Content-Type: application/json');

        // Validaciones
        $codigo = trim($_POST['codigo_inventario'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $estado_bien = trim($_POST['estado_bien'] ?? '');

        if (empty($codigo) || empty($descripcion) || empty($estado_bien)) {
            echo json_encode(['status' => false, 'msg' => 'Campos obligatorios incompletos']);
            return;
        }

        // Verificar si el código ya existe
        if ($this->modelo->existeCodigo($codigo)) {
            echo json_encode(['status' => false, 'msg' => 'El código de inventario ya existe']);
            return;
        }


        $rol = $_SESSION['rol'] ?? '';
        $idOficina = $_SESSION['id_oficina'] ?? null;
        $idSubunidad = $_SESSION['id_subunidad'] ?? null;

        $datos = [
            'anio' => (int) ($_POST['anio'] ?? date('Y')),
            'codigo_inventario' => $codigo,
            'descripcion' => $descripcion,
            'marca' => trim($_POST['marca'] ?? ''),
            'serie' => trim($_POST['serie'] ?? ''),
            'modelo' => trim($_POST['modelo'] ?? ''),
            'tipo_bien' => trim($_POST['tipo_bien'] ?? 'Mobiliario'),
            'dimensiones' => trim($_POST['dimensiones'] ?? ''),
            'situacion' => trim($_POST['situacion'] ?? 'Uso'),
            'otras_caracteristicas' => trim($_POST['otras_caracteristicas'] ?? ''),
            'ubicacion_fisica' => trim($_POST['ubicacion_fisica'] ?? ''),
            'estado_bien' => $estado_bien,
            'color' => trim($_POST['color'] ?? ''),
            'cantidad' => (int) ($_POST['cantidad'] ?? 1),
            'observaciones' => trim($_POST['observaciones'] ?? ''),
            'usuario_responsable' => trim($_POST['usuario_responsable'] ?? ''),
            'archivos_adjuntos' => trim($_POST['archivos_adjuntos'] ?? ''),
            'id_oficina' => $idOficina,
            'id_subunidad' => $idSubunidad
        ];

        $result = $this->modelo->registrar($datos);
        echo json_encode($result);
    }

    // Actualizar item existente
    public function actualizar()
    {
        header('Content-Type: application/json');

        $id = (int) ($_POST['id'] ?? 0);
        $codigo = trim($_POST['codigo_inventario'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $estado_bien = trim($_POST['estado_bien'] ?? '');

        if ($id <= 0 || empty($codigo) || empty($descripcion) || empty($estado_bien)) {
            echo json_encode(['status' => false, 'msg' => 'Datos incompletos']);
            return;
        }

        // Verificar si el código ya existe (excluyendo el item actual)
        if ($this->modelo->existeCodigo($codigo, $id)) {
            echo json_encode(['status' => false, 'msg' => 'El código de inventario ya existe']);
            return;
        }


        $idOficina = $_SESSION['id_oficina'] ?? null;
        $idSubunidad = $_SESSION['id_subunidad'] ?? null;

        $datos = [
            'anio' => (int) ($_POST['anio'] ?? date('Y')),
            'codigo_inventario' => $codigo,
            'descripcion' => $descripcion,
            'marca' => trim($_POST['marca'] ?? ''),
            'serie' => trim($_POST['serie'] ?? ''),
            'modelo' => trim($_POST['modelo'] ?? ''),
            'tipo_bien' => trim($_POST['tipo_bien'] ?? 'Mobiliario'),
            'dimensiones' => trim($_POST['dimensiones'] ?? ''),
            'situacion' => trim($_POST['situacion'] ?? 'Uso'),
            'otras_caracteristicas' => trim($_POST['otras_caracteristicas'] ?? ''),
            'ubicacion_fisica' => trim($_POST['ubicacion_fisica'] ?? ''),
            'estado_bien' => $estado_bien,
            'color' => trim($_POST['color'] ?? ''),
            'cantidad' => (int) ($_POST['cantidad'] ?? 1),
            'observaciones' => trim($_POST['observaciones'] ?? ''),
            'usuario_responsable' => trim($_POST['usuario_responsable'] ?? ''),
            'archivos_adjuntos' => trim($_POST['archivos_adjuntos'] ?? ''),
            'id_oficina' => $idOficina,
            'id_subunidad' => $idSubunidad
        ];

        $result = $this->modelo->actualizar($id, $datos);
        echo json_encode($result);
    }

    // Eliminar (Soft Delete)
    public function eliminar()
    {
        header('Content-Type: application/json');

        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['status' => false, 'msg' => 'ID inválido']);
            return;
        }

        $result = $this->modelo->eliminar($id);
        echo json_encode($result);
    }

    // Importar desde Excel
    public function importar()
    {
        header('Content-Type: application/json');

        // Verificar que se subió un archivo
        if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['status' => false, 'msg' => 'No se recibió ningún archivo']);
            return;
        }

        $archivo = $_FILES['archivo'];
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

        // Validar extensión
        if (!in_array($extension, ['xls', 'xlsx'])) {
            echo json_encode(['status' => false, 'msg' => 'Solo se permiten archivos .xls o .xlsx']);
            return;
        }

        // Verificar que PhpSpreadsheet esté instalado
        if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
            echo json_encode(['status' => false, 'msg' => 'PhpSpreadsheet no está instalado. Ejecute: composer require phpoffice/phpspreadsheet']);
            return;
        }

        require_once __DIR__ . '/../vendor/autoload.php';

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($archivo['tmp_name']);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();

            $procesadas = 0;
            $omitidas = 0;
            $errores = [];

            // Obtener el año seleccionado del POST form data (o usar actual)
            $anio_importacion = isset($_POST['anio']) ? (int) $_POST['anio'] : (int) date('Y');

            // Limpiar inventario del año si se solicita
            if (isset($_POST['limpiar_anio']) && $_POST['limpiar_anio'] === 'true') {
                $this->modelo->eliminarAnio($anio_importacion);
            }


            // IMPORTANTE: Empezar desde la fila 5 (las primeras 4 son encabezados)
            for ($row = 5; $row <= $highestRow; $row++) {
                $codigo = trim((string) ($sheet->getCell("B{$row}")->getValue() ?? ''));
                $descripcion = trim((string) ($sheet->getCell("C{$row}")->getValue() ?? ''));
                $estado_bien = trim((string) ($sheet->getCell("I{$row}")->getValue() ?? ''));


                // Saltar filas vacías
                if (empty($codigo) && empty($descripcion)) {
                    continue;
                }

                // Validar campos obligatorios BÁSICOS (para TODOS los bienes)
                if (empty($codigo) || empty($descripcion) || empty($estado_bien)) {
                    $omitidas++;
                    $errores[] = [
                        'fila' => $row,
                        'razon' => 'Campos obligatorios vacíos (Código, Descripción o Estado)'
                    ];
                    continue;
                }

                // Detectar si es un equipo electrónico o de cómputo
                $descripcion_lower = strtolower($descripcion);
                $es_equipo_electronico = (
                    stripos($descripcion_lower, 'computadora') !== false ||
                    stripos($descripcion_lower, 'laptop') !== false ||
                    stripos($descripcion_lower, 'pc') !== false ||
                    stripos($descripcion_lower, 'impresora') !== false ||
                    stripos($descripcion_lower, 'scanner') !== false ||
                    stripos($descripcion_lower, 'monitor') !== false ||
                    stripos($descripcion_lower, 'proyector') !== false ||
                    stripos($descripcion_lower, 'tablet') !== false ||
                    stripos($descripcion_lower, 'servidor') !== false
                );

                // Leer los campos adicionales
                $marca = trim((string) ($sheet->getCell("D{$row}")->getValue() ?? ''));
                $serie = trim((string) ($sheet->getCell("E{$row}")->getValue() ?? ''));
                $modelo = trim((string) ($sheet->getCell("F{$row}")->getValue() ?? ''));

                // Validación ESPECÍFICA para equipos electrónicos
                if ($es_equipo_electronico) {
                    $errores_equipo = [];

                    if (empty($marca)) {
                        $errores_equipo[] = 'MARCA es obligatoria para equipos electrónicos';
                    }
                    if (empty($serie)) {
                        $errores_equipo[] = 'SERIE es obligatoria para equipos electrónicos';
                    }
                    if (empty($modelo)) {
                        $errores_equipo[] = 'MODELO es obligatorio para equipos electrónicos';
                    }

                    if (!empty($errores_equipo)) {
                        $omitidas++;
                        $errores[] = [
                            'fila' => $row,
                            'razon' => implode(', ', $errores_equipo)
                        ];
                        continue;
                    }
                }

                // Verificar si el código ya existe
                if ($this->modelo->existeCodigo($codigo)) {
                    $omitidas++;
                    $errores[] = [
                        'fila' => $row,
                        'razon' => "Código {$codigo} ya existe"
                    ];
                    continue;
                }


                // Mapear campos antiguos (disco duro) a otras_caracteristicas para mantener compatibilidad si el excel no cambia
                $otras_caracteristicas_excel = [];
                $ssd = trim((string) ($sheet->getCell("G{$row}")->getValue() ?? ''));
                $hdd = trim((string) ($sheet->getCell("H{$row}")->getValue() ?? ''));
                if ($ssd) $otras_caracteristicas_excel[] = "SSD: $ssd";
                if ($hdd) $otras_caracteristicas_excel[] = "HDD: $hdd";

                $datos = [
                    'anio' => $anio_importacion, // Usar el año seleccionado
                    'codigo_inventario' => $codigo,
                    'descripcion' => $descripcion,
                    'marca' => $marca,
                    'serie' => $serie,
                    'modelo' => $modelo,
                    'tipo_bien' => 'Mobiliario', // Valor por defecto en importación masiva antigua
                    'dimensiones' => '',
                    'situacion' => 'Uso',
                    'otras_caracteristicas' => implode(', ', $otras_caracteristicas_excel),
                    'ubicacion_fisica' => '',
                    'estado_bien' => $estado_bien,
                    'color' => trim((string) ($sheet->getCell("J{$row}")->getValue() ?? '')),
                    'cantidad' => (int) ($sheet->getCell("K{$row}")->getValue() ?? 1),
                    'observaciones' => trim((string) ($sheet->getCell("L{$row}")->getValue() ?? '')),
                    'archivos_adjuntos' => '',
                    'id_oficina' => (int) ($_SESSION['id_oficina'] ?? 0),
                    'id_subunidad' => (int) ($_SESSION['id_subunidad'] ?? 0)
                ];


                $result = $this->modelo->registrar($datos);
                if ($result['status']) {
                    $procesadas++;
                } else {
                    $omitidas++;
                    $errores[] = [
                        'fila' => $row,
                        'razon' => $result['msg']
                    ];
                }
            }

            echo json_encode([
                'status' => true,
                'msg' => "Importación completada",
                'estadisticas' => [
                    'total_filas' => $highestRow - 4, // Restar las 4 filas de encabezado
                    'procesadas' => $procesadas,
                    'omitidas' => $omitidas,
                    'errores' => $errores
                ]
            ]);

        } catch (Exception $e) {
            echo json_encode(['status' => false, 'msg' => 'Error al procesar el archivo: ' . $e->getMessage()]);
        }
    }
}

// Enrutador
$op = $_GET['op'] ?? '';
$controlador = new InventarioControlador();

switch ($op) {
    case 'listar':
        $controlador->listar();
        break;
    case 'obtenerPorId':
        $controlador->obtenerPorId();
        break;
    case 'guardar':
        $controlador->guardar();
        break;
    case 'actualizar':
        $controlador->actualizar();
        break;
    case 'eliminar':
        $controlador->eliminar();
        break;
    case 'importar':
        $controlador->importar();
        break;
    default:
        echo json_encode(['status' => false, 'msg' => 'Operación no válida']);
}
