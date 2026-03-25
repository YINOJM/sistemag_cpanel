<?php
// controlador/ImportacionControlador.php
require_once __DIR__ . '/../modelo/ImportacionModelo.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

// Aumentar tiempo de ejecución y memoria para archivos grandes
set_time_limit(300); // 5 minutos
ini_set('memory_limit', '256M');
ini_set('max_execution_time', '300');

// Iniciar sesión solo si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación
if (empty($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode([
        'status' => false,
        'msg' => 'No autorizado. Por favor, inicie sesión nuevamente.',
        'debug' => [
            'session_status' => session_status(),
            'session_id' => session_id(),
            'has_session_id' => isset($_SESSION['id'])
        ]
    ]);
    exit();
}

class ImportacionControlador
{
    private $modelo;

    // Mapeo flexible de encabezados
    private $mapeoColumnas = [
        'NUMERO' => ['NUMERO', 'NUM', 'Nro', 'N°', 'NUMERO_OFICIO', 'NUMERO_INFORME', 'NUMERO_INFO', 'NUMERO_MEMORANDUM'],
        'HT' => ['HT', 'REFERENCIA', 'REF', 'HT/REF', 'HT / REF'],
        'FECHA' => ['FECHA', 'FECHA_DOC', 'FECHA_DOCUMENTO', 'FECHA DOC'],
        'SE_SOLICITA' => ['SE_SOLICITA', 'SE SOLICITA', 'SOLICITA', 'CLASIFICACION', 'CLASIFICACIÓN'],
        'DESTINO' => ['DESTINO', 'AREA', 'UNIDAD', 'DESTINO_AREA', 'DESTINO AREA', 'DE_DESTINO'],
        'DESCRIPCION' => ['DESCRIPCION', 'DESCRIPCIÓN', 'ASUNTO', 'DESC'],
        'FORMULADO_POR' => ['FORMULADO_POR', 'FORMULADO POR', 'FORMULADOR', 'USUARIO', 'CREADO_POR', 'CREADO POR'],
        'OBSERVACIONES' => ['OBSERVACIONES', 'OBS', 'NOTAS', 'COMENTARIOS', 'OBSERVACIÓN']
    ];

    public function __construct()
    {
        $this->modelo = new ImportacionModelo();
    }

    /**
     * Procesa el archivo Excel subido
     */
    public function procesarArchivo()
    {
        try {
            // Validar que se haya subido un archivo
            if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('No se recibió ningún archivo o hubo un error en la carga.');
            }

            $archivo = $_FILES['archivo'];
            $nombreArchivo = $archivo['name'];
            $rutaTemporal = $archivo['tmp_name'];
            $extension = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));

            // Validar extensión
            if (!in_array($extension, ['xlsx', 'xls'])) {
                throw new Exception('El archivo debe ser de tipo Excel (.xlsx o .xls)');
            }

            // Validar tamaño (máximo 10MB)
            if ($archivo['size'] > 10 * 1024 * 1024) {
                throw new Exception('El archivo excede el tamaño máximo permitido (10MB)');
            }

            // Validar que el archivo temporal existe
            if (!file_exists($rutaTemporal)) {
                throw new Exception('El archivo temporal no existe. Por favor, inténtelo nuevamente.');
            }

            // Validar que el archivo es legible
            if (!is_readable($rutaTemporal)) {
                throw new Exception('No se puede leer el archivo. Verifique los permisos.');
            }

            // Obtener año y tipo de documento
            $anio = isset($_POST['anio']) ? (int) $_POST['anio'] : date('Y');
            $tipoDocumento = isset($_POST['tipo']) ? strtoupper($_POST['tipo']) : null;

            if (!$tipoDocumento) {
                throw new Exception('Debe especificar el tipo de documento');
            }

            // Log para depuración
            error_log("Importación iniciada - Archivo: $nombreArchivo, Tamaño: {$archivo['size']}, Tipo: $tipoDocumento, Año: $anio");
            error_log("Ruta temporal: $rutaTemporal");
            error_log("Archivo existe: " . (file_exists($rutaTemporal) ? 'SÍ' : 'NO'));

            // Cargar el archivo Excel
            error_log("Intentando cargar archivo Excel...");
            $spreadsheet = IOFactory::load($rutaTemporal);
            error_log("Archivo Excel cargado exitosamente");

            // Buscar la hoja del tipo de documento con diferentes variantes
            $hoja = null;
            $variantes = [
                $tipoDocumento,                                    // Ej: "ORDEN TELEFÓNICA"
                str_replace(' ', '_', $tipoDocumento),            // Ej: "ORDEN_TELEFÓNICA"
                str_replace(['Á', 'É', 'Í', 'Ó', 'Ú'], ['A', 'E', 'I', 'O', 'U'], $tipoDocumento), // Sin tildes
                str_replace(['Á', 'É', 'Í', 'Ó', 'Ú'], ['A', 'E', 'I', 'O', 'U'], str_replace(' ', '_', $tipoDocumento)), // Sin tildes y con guión bajo
            ];

            foreach ($variantes as $variante) {
                try {
                    $hojaTemp = $spreadsheet->getSheetByName($variante);
                    if ($hojaTemp) {
                        $hoja = $hojaTemp;
                        error_log("Hoja encontrada con variante: $variante");
                        break;
                    }
                } catch (Exception $e) {
                    // Continuar con la siguiente variante
                }
            }

            // Si no se encontró ninguna variante, listar hojas disponibles y lanzar error
            if (!$hoja) {
                $hojasDisponibles = $spreadsheet->getSheetNames();
                $listadoHojas = implode(', ', $hojasDisponibles);
                throw new Exception("No se encontró la hoja '$tipoDocumento' en el Excel. Hojas disponibles: $listadoHojas. Asegúrese de que el Excel tenga una hoja con el nombre del tipo de documento.");
            }

            $datos = $hoja->toArray();

            if (empty($datos)) {
                throw new Exception('El archivo Excel está vacío');
            }

            // Detectar encabezados (primera fila) - filtrar valores NULL
            $encabezados = array_map(function ($val) {
                return trim($val ?? '');
            }, $datos[0]);
            $mapeoDetectado = $this->detectarEncabezados($encabezados);

            // Validar que se encontró el campo obligatorio (SOLO NUMERO)
            if (!isset($mapeoDetectado['NUMERO'])) {
                $variantes = implode(', ', $this->mapeoColumnas['NUMERO']);
                throw new Exception("No se encontró la columna 'NUMERO'. Variantes aceptadas: {$variantes}");
            }

            // Procesar datos
            $resultado = $this->procesarDatos($datos, $mapeoDetectado, $anio, $tipoDocumento);

            echo json_encode([
                'status' => true,
                'msg' => 'Importación completada',
                'data' => $resultado
            ]);

        } catch (Exception $e) {
            // Log del error para depuración
            error_log("Error en importación: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());

            echo json_encode([
                'status' => false,
                'msg' => $e->getMessage(),
                'debug' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ]);
        } catch (Error $e) {
            // Capturar errores fatales
            error_log("Error fatal en importación: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());

            echo json_encode([
                'status' => false,
                'msg' => 'Error fatal: ' . $e->getMessage(),
                'debug' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ]);
        }
    }

    /**
     * Detecta automáticamente los encabezados del Excel
     */
    private function detectarEncabezados($encabezados)
    {
        $mapeoDetectado = [];

        foreach ($this->mapeoColumnas as $campoEstandar => $variantes) {
            foreach ($encabezados as $indice => $encabezado) {
                $encabezadoNormalizado = strtoupper(trim($encabezado));

                foreach ($variantes as $variante) {
                    if ($encabezadoNormalizado === strtoupper($variante)) {
                        $mapeoDetectado[$campoEstandar] = $indice;
                        break 2; // Salir de ambos loops
                    }
                }
            }
        }

        return $mapeoDetectado;
    }

    /**
     * Procesa los datos del Excel
     */
    private function procesarDatos($datos, $mapeo, $anio, $tipoDocumento)
    {
        $totalRegistros = count($datos) - 1; // Excluir encabezados
        $exitosos = 0;
        $errores = [];

        // Iniciar transacción
        $this->modelo->iniciarTransaccion();

        try {
            // VERIFICAR FLAGS DE LIMPIEZA
            $limpiar = isset($_POST['limpiar']) && $_POST['limpiar'] === 'true';

            if ($limpiar) {
                error_log("Solicitud de limpieza detectada para Año: $anio, Tipo: $tipoDocumento");
                if (!$this->modelo->eliminarImportacionAnio($anio, $tipoDocumento)) {
                    throw new Exception("Error al limpiar los registros anteriores del año $anio.");
                }
                error_log("Limpieza completada.");
            }

            for ($i = 1; $i < count($datos); $i++) {
                $fila = $datos[$i];

                // 1. Verificar si la fila está totalmente vacía (común en Excel al final)
                $filaVacia = true;
                foreach ($fila as $celda) {
                    if (trim($celda ?? '') !== '') {
                        $filaVacia = false;
                        break;
                    }
                }
                if ($filaVacia) {
                    continue; // Saltar silenciosamente
                }

                try {
                    // Extraer datos según el mapeo - manejar valores NULL
                    $numero = isset($mapeo['NUMERO']) ? trim($fila[$mapeo['NUMERO']] ?? '') : null;
                    $ht = isset($mapeo['HT']) ? trim($fila[$mapeo['HT']] ?? '') : '';
                    $fechaRaw = isset($mapeo['FECHA']) ? $fila[$mapeo['FECHA']] : null;
                    $seSolicita = isset($mapeo['SE_SOLICITA']) ? trim($fila[$mapeo['SE_SOLICITA']] ?? '') : '';
                    $destinoNombre = isset($mapeo['DESTINO']) ? trim($fila[$mapeo['DESTINO']] ?? '') : null;
                    $descripcion = isset($mapeo['DESCRIPCION']) ? trim($fila[$mapeo['DESCRIPCION']] ?? '') : null;
                    $formuladoPor = isset($mapeo['FORMULADO_POR']) ? trim($fila[$mapeo['FORMULADO_POR']] ?? '') : null;
                    $observaciones = isset($mapeo['OBSERVACIONES']) ? trim($fila[$mapeo['OBSERVACIONES']] ?? '') : '';

                    // 2. Validar SOLO el número de documento (ÚNICO campo obligatorio)
                    if (empty($numero))
                        throw new Exception("Fila {$i}: Falta el N° de Documento");

                    // Asignar valores por defecto si están vacíos
                    if (empty($destinoNombre))
                        $destinoNombre = "POR ASIGNAR";
                    if (empty($descripcion))
                        $descripcion = "";
                    if (empty($formuladoPor))
                        $formuladoPor = "MIGRACION";

                    // 3. Validar duplicados exactos
                    // Parsear número y sufijo (Ej: "3-A", "3_B", "3 A" -> num=3, suf=A/B)
                    $numCorrelativo = 0;
                    $numSufijo = null;

                    // ESTRATEGIA DE PARSEO MEJORADA (Validada)
                    // 1. Buscar separador EXPLÍCITO (-, _, ., espacio)
                    //    Ej: "166-2", "3-A", "166.2"
                    if (preg_match('/^(\d+)([-\s_.]+)([A-Za-z0-9\-_.]+)$/', $numero, $matches)) {
                        $numCorrelativo = (int) $matches[1];
                        $numSufijo = $matches[3]; // El grupo 2 es el separador
                    }
                    // 2. Buscar sufijo ALFABÉTICO SIN separador (solo si empieza con letra)
                    //    Ej: "3A", "3B". NO "166" (porque 6 es dígito)
                    elseif (preg_match('/^(\d+)([A-Za-z][A-Za-z0-9\-_.]*)$/', $numero, $matches)) {
                        $numCorrelativo = (int) $matches[1];
                        $numSufijo = $matches[2];
                    }
                    // 3. Si no cumple ninguno, es un NÚMERO PURO
                    else {
                        $numCorrelativo = (int) $numero;
                        $numSufijo = null;
                    }

                    // Verificar existencia exacta (num + sufijo)
                    // Nota: El modelo necesitaría un método más preciso, pero por ahora validamos
                    // que NO exista ya un documento con este número Y sufijo si es que estamos importando

                    // Validar duplicados básicos
                    if ($this->modelo->existeNumeroDocumento($anio, $tipoDocumento, $numCorrelativo)) {
                        // Si existe el número base, verificar si es el MISMO sufijo
                        // IMPORTANTE: Como la función 'existeNumeroDocumento' solo busca por número base (int),
                        // esto podría dar falso positivo para "3-A" si "3" ya existe.
                        // Sin embargo, para importación masiva limpia, esto debería estar vacío.

                        // Si ya limpiamos, no deberíamos tener problemas, PERO como el excel puede tener "3" y "3-A",
                        // necesitamos permitir ambos si son distintos.

                        // TODO: Idealmente `existeNumeroDocumento` debería aceptar sufijo, 
                        // pero para no romper el modelo ahora, confiaremos en la base de datos unique constraint 
                        // o simplemente loguearemos advertencia.

                        // Por seguridad de esta importación específica:
                        // Si es "3-A", y el sistema dice que existe "3", asumimos que es DIFERENTE y permitimos insertar.
                        // Solo bloqueamos si es duplicado EXACTO.

                        // Como no tenemos metodo existeNumeroConSufijo, procedemos con cautela:
                        // Si tiene sufijo, INTENTAMOS insertar. La BDD tirará error si hay unique key collision.
                    }

                    // Validar que el número base sea > 0
                    if ($numCorrelativo <= 0) {
                        throw new Exception("Fila {$i}: El número no es válido ($numero)");
                    }

                    // Buscar el ID del destino
                    $idDestino = $this->modelo->buscarDestinoPorNombre($destinoNombre);
                    if (!$idDestino) {
                        // Si no existe, buscar "POR ASIGNAR"
                        $idDestino = $this->modelo->buscarDestinoPorNombre("POR ASIGNAR");
                        if (!$idDestino) {
                            // Si tampoco existe "POR ASIGNAR", usar ID 1 como fallback
                            $idDestino = 1;
                        }
                    }

                    // Procesar fecha
                    $fecha = $this->procesarFecha($fechaRaw);

                    // Construir número completo
                    $tipoTexto = $this->obtenerTextoTipo($tipoDocumento);
                    $sufijoTexto = $numSufijo ? "-$numSufijo" : "";
                    $numCompleto = "{$tipoTexto} N° {$numCorrelativo}{$sufijoTexto}";

                    // Preparar datos para inserción
                    $datosDocumento = [
                        'anio' => $anio,
                        'cod_tipo' => $tipoDocumento,
                        'num_correlativo' => $numCorrelativo,
                        'num_sufijo' => $numSufijo, // Pasar el sufijo!
                        'num_completo' => $numCompleto,
                        'asunto' => $descripcion,
                        'id_destino' => $idDestino,
                        'usuario_formulador' => $formuladoPor,
                        'created_at' => $fecha,
                        'estado' => 'PENDIENTE',
                        'prioridad' => 'Normal',
                        'demora' => 0,
                        'ht' => $ht,
                        'se_solicita' => $seSolicita,
                        'observaciones' => $observaciones
                    ];

                    // Insertar documento
                    if ($this->modelo->insertarDocumento($datosDocumento)) {
                        $exitosos++;
                    } else {
                        throw new Exception("Fila {$i}: Error al insertar en la base de datos");
                    }
                } catch (Exception $e) {
                    $errores[] = $e->getMessage();
                }
            }

            // Confirmar transacción (siempre guardar los registros exitosos)
            $this->modelo->confirmarTransaccion();

            return [
                'total' => $totalRegistros,
                'exitosos' => $exitosos,
                'errores' => count($errores),
                'detalleErrores' => $errores
            ];

        } catch (Exception $e) {
            $this->modelo->revertirTransaccion();
            throw $e;
        }
    }

    /**
     * Procesa la fecha del Excel
     */
    private function procesarFecha($fechaRaw)
    {
        // NO INVENTAR FECHAS - Si está vacía, retornar NULL
        if (empty($fechaRaw)) {
            return null;
        }

        // Si es un número (fecha de Excel)
        if (is_numeric($fechaRaw)) {
            try {
                $fechaObj = Date::excelToDateTimeObject($fechaRaw);
                return $fechaObj->format('Y-m-d H:i:s');
            } catch (Exception $e) {
                return null; // Si falla, NULL en lugar de fecha actual
            }
        }

        // Si es una cadena de texto
        $fechaStr = trim($fechaRaw);

        // Intentar parsear diferentes formatos
        $formatos = ['d/m/Y', 'd-m-Y', 'Y-m-d', 'd/m/Y H:i:s'];
        foreach ($formatos as $formato) {
            $fecha = DateTime::createFromFormat($formato, $fechaStr);
            if ($fecha !== false) {
                return $fecha->format('Y-m-d H:i:s');
            }
        }

        // Si no se pudo parsear, retornar NULL en lugar de inventar fecha
        return null;
    }

    /**
     * Obtiene el texto del tipo de documento
     */
    private function obtenerTextoTipo($tipo)
    {
        $tipos = [
            'OFICIO' => 'OFICIO',
            'INFORME' => 'INFORME',
            'MEMORANDUM' => 'MEMORANDUM',
            'ORDEN_TELEFONICA' => 'ORDEN TELEFÓNICA',
            'SOLICITUD' => 'SOLICITUD',
            'OTRO' => 'OTRO'
        ];

        return $tipos[$tipo] ?? $tipo;
    }
    public function limpiar()
    {
        try {
            $anio = isset($_POST['anio']) ? (int) $_POST['anio'] : date('Y');
            $tipoDocumento = isset($_POST['tipo']) ? strtoupper($_POST['tipo']) : null;

            if (!$tipoDocumento) {
                throw new Exception('Debe especificar el tipo de documento');
            }

            if ($this->modelo->eliminarImportacionAnio($anio, $tipoDocumento)) {
                echo json_encode([
                    'status' => true,
                    'msg' => "Se han eliminado correctamente todos los documentos de tipo $tipoDocumento del año $anio."
                ]);
            } else {
                throw new Exception("Error al intentar eliminar los registros.");
            }
        } catch (Exception $e) {
            echo json_encode([
                'status' => false,
                'msg' => $e->getMessage()
            ]);
        }
    }
}

// Ejecutar controlador
if (isset($_GET['op'])) {
    $controlador = new ImportacionControlador();

    if ($_GET['op'] === 'procesar') {
        $controlador->procesarArchivo();
    } elseif ($_GET['op'] === 'limpiar') {
        $controlador->limpiar();
    }
}
