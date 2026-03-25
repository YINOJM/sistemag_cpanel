<?php
// controlador/PersonalControlador.php
declare(strict_types=1);

// 1. CARGAR CONEXIÓN Y ENTORNO (Centralizado)
require_once __DIR__ . '/../modelo/conexion.php';
require_once __DIR__ . '/../modelo/PersonalModelo.php';
require_once __DIR__ . '/../vendor/autoload.php';

// session_start(); removido porque lo maneja conexion.php -> sesion_config.php

// Configuración de recursos para archivos grandes
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);

use PhpOffice\PhpSpreadsheet\IOFactory;

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Super Administrador', 'Oficina Personal'])) {
    echo json_encode(['status' => false, 'msg' => 'Acceso denegado']);
    exit;
}

class PersonalControlador
{
    private $modelo;

    public function __construct()
    {
        $this->modelo = new PersonalModelo();
    }

    public function listar()
    {
        $mes = isset($_GET['mes']) ? $_GET['mes'] : null;
        $data = $this->modelo->listar($mes);
        echo json_encode(['data' => $data]);
    }

    public function guardar()
    {
        $id = isset($_POST['id_personal']) ? (int)$_POST['id_personal'] : 0;
        
        $datos = [
            'id_grado' => !empty($_POST['id_grado']) ? (int)$_POST['id_grado'] : null,
            'cip' => trim($_POST['cip']),
            'dni' => trim($_POST['dni']),
            // Si viene el campo unificado, lo guardamos todo en apellidos y nombres vacio, o intentamos separar
            'apellidos' => isset($_POST['apellidos_nombres']) ? strtoupper(trim($_POST['apellidos_nombres'])) : strtoupper(trim($_POST['apellidos'])),
            'nombres' => '', // Dejamos nombres vacio para simplificar, ya que ahora todo va en apellidos
            'sexo' => $_POST['sexo'] ?? 'M',
            'fecha_nacimiento' => (!empty($_POST['mes_nac']) && !empty($_POST['dia_nac'])) 
                                    ? '2000-' . str_pad($_POST['mes_nac'], 2, '0', STR_PAD_LEFT) . '-' . str_pad($_POST['dia_nac'], 2, '0', STR_PAD_LEFT) 
                                    : (!empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : null),
            'id_subunidad' => !empty($_POST['id_subunidad']) ? (int)$_POST['id_subunidad'] : null,
            'tipo_dni' => $_POST['tipo_dni'] ?? 'DNI',
            'situacion_cip' => !empty($_POST['situacion_cip']) ? strtoupper(trim($_POST['situacion_cip'])) : null,
            'cargo' => !empty($_POST['cargo']) ? strtoupper(trim($_POST['cargo'])) : null,
            'situacion_especial' => !empty($_POST['situacion_especial']) ? strtoupper(trim($_POST['situacion_especial'])) : null,
            'funcion_horario' => !empty($_POST['funcion_horario']) ? strtoupper(trim($_POST['funcion_horario'])) : null
        ];

        if ($id > 0) {
            echo json_encode($this->modelo->actualizar($id, $datos));
        } else {
            echo json_encode($this->modelo->registrar($datos));
        }
    }

    public function eliminar()
    {
        $id = (int)$_POST['id'];
        echo json_encode($this->modelo->darbaja($id));
    }
    
    public function importar()
    {
        header('Content-Type: application/json');

        if (empty($_FILES) && empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
            $maxPost = ini_get('post_max_size');
            echo json_encode(['status' => false, 'msg' => "El archivo excede el límite de POST del servidor ($maxPost)."]);
            return;
        }

        // Validar variables
        $limpiar = isset($_POST['limpiar']) && $_POST['limpiar'] === 'true';
        $tieneArchivo = isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK;

        // Caso 1: Ni limpia ni sube archivo -> Error
        if (!$limpiar && !$tieneArchivo) {
            $err = $_FILES['archivo']['error'] ?? 'No file';
            $msg = 'No se seleccionó ningún archivo para importar.';
            if(!$limpiar) echo json_encode(['status' => false, 'msg' => $msg]);
            return;
        }

        // Caso 2: Limpieza solicitada (Independiente del archivo)
        if ($limpiar) {
            if (!$this->modelo->limpiar()) {
                echo json_encode(['status' => false, 'msg' => 'Error crítico: No se pudo limpiar la base de datos verificando claves foráneas.']);
                return;
            }
            // Si solo quería limpiar y no subió archivo, terminamos aquí
            if (!$tieneArchivo) {
                echo json_encode(['status' => true, 'msg' => 'Base de datos limpiada correctamente. No se realizó ninguna importación.']);
                return;
            }
        }

        // Si llegamos aquí sin archivo, es un error lógico (aunque cubierto por Caso 1), pero por seguridad:
        if (!$tieneArchivo) {
             echo json_encode(['status' => false, 'msg' => 'Error: Archivo no válido para importación.']);
             return;
        }

        try {
            $spreadsheet = IOFactory::load($_FILES['archivo']['tmp_name']);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            // (Ya se limpió arriba si se solicitó)

            $mapGrados = $this->modelo->obtenerMapa('mae_grados', 'nombre_grado', 'id_grado');
            $mapUnidades = $this->modelo->obtenerMapa('sub_unidades_policiales', 'nombre_subunidad', 'id_subunidad');

            $countExito = 0;
            $errores = [];

            // --- DETECCIÓN DE COLUMNAS ---
            $colCip = -1; $colGrado = -1; $colDni = -1; $colApellidos = -1; $colNombres = -1; $colUnidad = -1;
            $colNombreCompleto = -1;
            
            // Extras
            $colTipoDni = -1; $colSituacionEspecial = -1; $colSituacionCip = -1; $colCargo = -1; $colFuncion = -1;
            
            $colNombreCompleto = -1;
            $headerRowIndex = 0;
            $manualFallback = false;

            foreach ($rows as $idx => $row) {
                if ($idx > 5) break; 
                foreach ($row as $c => $val) {
                    $v = strtoupper(trim((string)$val));
                    if (strpos($v, 'CIP') !== false) $colCip = $c;
                    if (strpos($v, 'GRADO') !== false) $colGrado = $c;
                    if ($v === 'DNI' || strpos($v, 'DOC') !== false) $colDni = $c;
                    
                    if (strpos($v, 'UNIDAD') !== false && strpos($v, 'SUB') !== false) $colUnidad = $c; 
                    if ($colUnidad == -1 && strpos($v, 'UNIDAD') !== false) $colUnidad = $c; // Fallback simple

                    if (strpos($v, 'APELLIDO') !== false && strpos($v, 'NOMBRE') !== false) $colNombreCompleto = $c;
                    
                    // Columnas Adicionales
                    if (strpos($v, 'TIPO') !== false && strpos($v, 'DNI') !== false) $colTipoDni = $c;
                    if (strpos($v, 'SITUACION') !== false && strpos($v, 'ESPECIAL') !== false) $colSituacionEspecial = $c;
                    if (strpos($v, 'SITUACION') !== false && (strpos($v, 'CIP') !== false || strpos($v, 'DEL') !== false)) $colSituacionCip = $c;
                    if ($v === 'CARGO') $colCargo = $c;
                    if (strpos($v, 'FUNCION') !== false) $colFuncion = $c;
                }
                
                // Si encontramos GRADO y (CIP o NOMBRE COMPLETO), asumimos éxito
                if ($colGrado !== -1 && ($colCip !== -1 || $colNombreCompleto !== -1)) {
                    $headerRowIndex = $idx;
                    break;
                }
            }

            // --- FALLBACK (PLAN B) SI FALLA LA DETECCIÓN CORRECTA ---
            // Forzamos la estructura si la detección automática falló o dio resultados extraños (como CIP en col 10)
            if ($colGrado === -1 || $colCip === -1 || $colCip === 10) {
                $manualFallback = true;
                $headerRowIndex = 0; // Asumimos fila 0 es cabecera
                $colUnidad = 3;
                $colGrado = 4;
                $colNombreCompleto = 5;
                $colDni = 6;
                $colCip = 9; // FORZADO: Columna J (Index 9)
            }

            // --- PROCESAR FILAS ---
            for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $filaNum = $i + 1;

                // Extraer
                $cip = (isset($row[$colCip])) ? trim((string)$row[$colCip]) : '';
                
                // Validar CIP (Saltar encabezados repetidos o vacíos)
                if (empty($cip)) continue;
                if (strlen($cip) > 5 && !ctype_digit($cip) && preg_match('/[a-zA-Z]/', $cip)) continue;

                $txtGrado = ($colGrado !== -1 && isset($row[$colGrado])) ? trim((string)$row[$colGrado]) : '';
                $dni = ($colDni !== -1 && isset($row[$colDni])) ? trim((string)$row[$colDni]) : '';
                $txtUnidad = ($colUnidad !== -1 && isset($row[$colUnidad])) ? trim((string)$row[$colUnidad]) : '';

                // Datos Extra
                $tipoDni = ($colTipoDni !== -1 && isset($row[$colTipoDni])) ? trim((string)$row[$colTipoDni]) : null;
                $sitEspecial = ($colSituacionEspecial !== -1 && isset($row[$colSituacionEspecial])) ? trim((string)$row[$colSituacionEspecial]) : null;
                $sitCip = ($colSituacionCip !== -1 && isset($row[$colSituacionCip])) ? trim((string)$row[$colSituacionCip]) : null;
                $cargo = ($colCargo !== -1 && isset($row[$colCargo])) ? trim((string)$row[$colCargo]) : null;
                $funcion = ($colFuncion !== -1 && isset($row[$colFuncion])) ? trim((string)$row[$colFuncion]) : null;

                // Nombres
                $apellidos = ''; $nombres = '';
                if ($colNombreCompleto !== -1 && isset($row[$colNombreCompleto])) {
                    $full = trim((string)$row[$colNombreCompleto]);
                    // Separar por último espacio para intentar sacar al menos un nombre
                    $espacio = strrpos($full, ' ');
                    if ($espacio !== false) {
                        $apellidos = substr($full, 0, $espacio);
                        $nombres = substr($full, $espacio + 1);
                    } else {
                        $apellidos = $full;
                        $nombres = '.';
                    }
                } else {
                    $apellidos = ($colApellidos !== -1 && isset($row[$colApellidos])) ? trim((string)$row[$colApellidos]) : '';
                    $nombres = ($colNombres !== -1 && isset($row[$colNombres])) ? trim((string)$row[$colNombres]) : '';
                }

                // Identificar Grado
                $idGrado = null;
                if (!empty($txtGrado)) {
                    if (is_numeric($txtGrado)) {
                        if (in_array((int)$txtGrado, $mapGrados)) $idGrado = (int)$txtGrado;
                    } else {
                        // Búsqueda exacta
                        $idGrado = $mapGrados[strtoupper($txtGrado)] ?? null;

                        // Búsqueda difusa / normalizada si falla la exacta
                        if (!$idGrado) {
                            // Normalizar entrada: Mayúsculas, sin puntos, espacios simples
                            $inputNorm = strtoupper(trim(str_replace('.', '', $txtGrado)));
                            $inputNorm = preg_replace('/\s+/', ' ', $inputNorm);

                            // Recorrer mapa para buscar coincidencia normalizada
                            foreach ($mapGrados as $gNombre => $gId) {
                                // Normalizar nombre de DB
                                $dbNorm = strtoupper(trim(str_replace('.', '', $gNombre)));
                                $dbNorm = preg_replace('/\s+/', ' ', $dbNorm);

                                // 1. Coincidencia directa normalizada (Ej: "MAYOR S PNP" == "MAYOR S PNP")
                                if ($inputNorm === $dbNorm) {
                                    $idGrado = $gId;
                                    break;
                                }

                                // 2. Coincidencia con reordenamiento de "PNP" (Ej: "CORONEL PNP MA" vs "CORONEL MA PNP")
                                // Intentamos mover el sufijo después de PNP a antes de PNP
                                // Patrón: "CARGO PNP SUFIJO" -> "CARGO SUFIJO PNP"
                                if (preg_match('/^(.+) PNP ([A-Z0-9]+)$/', $inputNorm, $m)) {
                                    $swapCandidate = $m[1] . ' ' . $m[2] . ' PNP';
                                    if ($swapCandidate === $dbNorm) {
                                        $idGrado = $gId;
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }

                // Identificar Unidad (Búsqueda Inteligente)
                $idUnidad = null;
                if (!empty($txtUnidad)) {
                     // 1. Intento por ID directo
                     if (is_numeric($txtUnidad)) {
                        if (in_array((int)$txtUnidad, $mapUnidades)) $idUnidad = (int)$txtUnidad;
                     } 
                     // 2. Intento por Nombre Exacto
                     else {
                        $idUnidad = $mapUnidades[strtoupper($txtUnidad)] ?? null;
                     }

                     // 3. Intento por Aproximación (Ignorando tildes y mayúsculas)
                     if (!$idUnidad && !is_numeric($txtUnidad)) {
                        $inputClean = strtoupper(iconv('UTF-8', 'ASCII//TRANSLIT', $txtUnidad));
                        
                        foreach ($mapUnidades as $nombreDb => $idDb) {
                            $dbClean = strtoupper(iconv('UTF-8', 'ASCII//TRANSLIT', $nombreDb));
                            if ($inputClean === $dbClean) {
                                $idUnidad = $idDb;
                                break;
                            }
                        }
                     }
                }

                // Guardar
                $datos = [
                    'id_grado' => $idGrado,
                    'cip' => substr($cip, 0, 20),
                    'dni' => substr($dni, 0, 15),
                    'apellidos' => strtoupper($apellidos),
                    'nombres' => strtoupper($nombres),
                    'sexo' => 'M',
                    'fecha_nacimiento' => null,
                    'id_subunidad' => $idUnidad,
                    'tipo_dni' => $tipoDni,
                    'situacion_especial' => $sitEspecial,
                    'situacion_cip' => $sitCip,
                    'cargo' => $cargo,
                    'funcion_horario' => $funcion
                ];

                $res = $this->modelo->registrar($datos);
                if ($res['status']) $countExito++;
                else $errores[] = "Fila $filaNum ($cip): " . $res['msg'];
            }

            if ($countExito > 0) {
                echo json_encode([ 'status' => true, 'msg' => "Éxito: $countExito registros cargados.", 'errores' => $errores ]);
            } else {
                // Debug extendido si falla
                $usedCols = "Grado:$colGrado, CIP:$colCip, Nom:$colNombreCompleto";
                $debugMsg = $manualFallback ? " (Modo Fallback Activado)" : "";
                
                echo json_encode([ 
                    'status' => false, 
                    'msg' => "No se importó nada. Cols usadas: $usedCols $debugMsg", 
                    'errores' => $errores 
                ]);
            }

        } catch (Exception $e) {
            echo json_encode(['status' => false, 'msg' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function obtener()
    {
        // Limpiar cualquier buffer de salida previo para evitar basura en el JSON
        if (ob_get_length()) ob_clean();
        
        header('Content-Type: application/json; charset=utf-8');
        
        $id = (int)$_GET['id'];
        $data = $this->modelo->obtenerPorId($id);
        
        if (!$data) {
            echo json_encode(['error' => 'No encontrado']);
            exit;
        }

        // Asegurar UTF-8 en campos de texto propensos a error
        array_walk_recursive($data, function(&$item) {
            if (is_string($item) && !mb_check_encoding($item, 'UTF-8')) {
                $item = mb_convert_encoding($item, 'UTF-8', 'ISO-8859-1'); 
            }
        });

        // Usar flags para evitar fallos por caracteres extraños
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        
        if ($json === false) {
             echo json_encode(['error' => 'Error JSON: ' . json_last_error_msg()]);
        } else {
             echo $json;
        }
        exit; 
    }

    public function listar_grados()
    {
        global $conexion;
        $res = $this->modelo->obtenerMapa('mae_grados', 'nombre_grado', 'id_grado');
        $lista = [];
        foreach($res as $nombre => $id) {
            $lista[] = ['id' => $id, 'nombre' => $nombre];
        }
        echo json_encode(['data' => $lista]);
    }

    public function listar_divisiones()
    {
        // Traer ID Region padre
        global $conexion;
        $sql = "SELECT id_division, nombre_division, id_unidad as id_region FROM divisiones_policiales WHERE estado = 1";
        $res = $conexion->query($sql);
        $lista = [];
        while($r = $res->fetch_assoc()) {
            $lista[] = $r;
        }
        echo json_encode(['data' => $lista]);
    }

    public function listar_regiones()
    {
        $res = $this->modelo->obtenerMapa('unidades_policiales', 'nombre_unidad', 'id_unidad');
        $lista = [];
        foreach($res as $nombre => $id) {
            $lista[] = ['id_unidad' => $id, 'nombre_unidad' => $nombre];
        }
        echo json_encode(['data' => $lista]);
    }

    public function listar_subunidades()
    {
        // Traemos también id_division e id_unidad (region) para el filtrado en JS
        global $conexion;
        $sql = "SELECT s.id_subunidad, s.nombre_subunidad, s.id_division, d.id_unidad as id_region 
                FROM sub_unidades_policiales s
                INNER JOIN divisiones_policiales d ON s.id_division = d.id_division
                WHERE s.estado = 1 
                ORDER BY s.nombre_subunidad ASC";
        $res = $conexion->query($sql);
        $lista = [];
        while($r = $res->fetch_assoc()) {
            $lista[] = $r;
        }
        echo json_encode(['data' => $lista]);
    }

    public function registrar_asistencia()
    {
        if (!isset($_POST['id_personal'], $_POST['estado'])) {
             echo json_encode(['status' => false, 'msg' => 'Datos incompletos']);
             return;
        }

        $id = (int)$_POST['id_personal'];
        $estado = $_POST['estado'];
        $obs = trim($_POST['observacion'] ?? '');
        
        // Validación de servidor
        $estadosValidos = ['PRESENTE', 'PERMISO', 'COMISIÓN', 'AUSENTE'];
        if (!in_array($estado, $estadosValidos)) {
             echo json_encode(['status' => false, 'msg' => 'Estado no válido']);
             return;
        }

        if ($estado !== 'PRESENTE' && empty($obs)) {
             echo json_encode(['status' => false, 'msg' => 'Debe ingresar una observación para este estado']);
             return;
        }
        
        $res = $this->modelo->registrarAsistencia($id, $estado, $obs);
        echo json_encode($res);
    }

    public function obtener_historial()
    {
        $id = (int)$_GET['id'];
        $data = $this->modelo->obtenerHistorialAsistencia($id);
        echo json_encode(['data' => $data]);
    }

    public function eliminar_novedad()
    {
        $id = (int)$_POST['id_novedad'];
        $res = $this->modelo->eliminarNovedad($id);
        echo json_encode(['status' => $res]);
    }

    public function listar_parametros_predictivos()
    {
        global $conexion;
        
        $parametros = [
            'cargos' => [],
            'situaciones' => [],
            'funciones' => []
        ];

        // 1. Cargos
        $sql = "SELECT DISTINCT cargo FROM personal WHERE cargo IS NOT NULL AND cargo != '' ORDER BY cargo ASC";
        $res = $conexion->query($sql);
        while($r = $res->fetch_assoc()) $parametros['cargos'][] = $r['cargo'];

        // 2. Situaciones Especiales
        $sql = "SELECT DISTINCT situacion_especial FROM personal WHERE situacion_especial IS NOT NULL AND situacion_especial != '' ORDER BY situacion_especial ASC";
        $res = $conexion->query($sql);
        while($r = $res->fetch_assoc()) $parametros['situaciones'][] = $r['situacion_especial'];

        // 3. Funciones
        $sql = "SELECT DISTINCT funcion_horario FROM personal WHERE funcion_horario IS NOT NULL AND funcion_horario != '' ORDER BY funcion_horario ASC";
        $res = $conexion->query($sql);
        while($r = $res->fetch_assoc()) $parametros['funciones'][] = $r['funcion_horario'];

        echo json_encode($parametros);
    }
}

// Enrutador
$op = $_GET['op'] ?? '';
$controlador = new PersonalControlador();

switch ($op) {
    case 'listar': $controlador->listar(); break;
    case 'guardar': $controlador->guardar(); break;
    case 'eliminar': $controlador->eliminar(); break;
    case 'obtener': $controlador->obtener(); break;
    case 'importar': $controlador->importar(); break;
    case 'listar_grados': $controlador->listar_grados(); break;
    case 'listar_regiones': $controlador->listar_regiones(); break;
    case 'listar_divisiones': $controlador->listar_divisiones(); break;
    case 'listar_subunidades': $controlador->listar_subunidades(); break;
    case 'registrar_asistencia': $controlador->registrar_asistencia(); break;
    case 'obtener_historial': $controlador->obtener_historial(); break;
    case 'eliminar_novedad': $controlador->eliminar_novedad(); break;
    case 'listar_parametros': $controlador->listar_parametros_predictivos(); break; // Nuevo endpoint
}
