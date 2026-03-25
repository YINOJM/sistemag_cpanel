<?php
// DocumentoControlador.php

// 1. CARGAR CONEXIÓN Y ENTORNO (Centralizado)
require_once __DIR__ . '/../modelo/conexion.php';
require_once __DIR__ . '/../modelo/DocumentoModelo.php';

// Validar sesión activa (session_start ya fue llamado por conexion.php)
if (empty($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['status' => false, 'msg' => 'Sesión no válida. Por favor, inicie sesión.']);
    exit();
}

// Manejo simple de rutas tipo API
if (isset($_GET['op'])) {
    $controlador = new DocumentoControlador();
    $op = $_GET['op'];

    if ($op === 'listar') {
        $controlador->listar();
    } elseif ($op === 'guardar') {
        $controlador->guardar();
    } elseif ($op === 'obtener_numero') {
        $controlador->obtenerSiguiente();
    } elseif ($op === 'guardar_v2') {
        $controlador->guardarV2();
    } elseif ($op === 'listar_v2') {
        $controlador->listarV2();
    } elseif ($op === 'eliminar') {
        $controlador->eliminar();
    } elseif ($op === 'obtener') {
        $controlador->obtener();
    } elseif ($op === 'actualizar') {
        $controlador->actualizar();
    }
}

class DocumentoControlador
{
    private $modelo;

    public function __construct()
    {
        $this->modelo = new DocumentoModelo();
    }

    public function listar()
    {
        $anio = $_GET['anio'] ?? date('Y');
        $tipo = $_GET['tipo'] ?? null;
        $data = $this->modelo->listar($anio, $tipo);
        echo json_encode(['data' => $data]);
    }

    public function listarV2()
    {
        $anio = $_GET['anio'] ?? date('Y');
        $tipo = $_GET['tipo'] ?? null;
        $fecha_inicio = $_GET['fecha_inicio'] ?? null;
        $fecha_fin = $_GET['fecha_fin'] ?? null;
        
        $data = $this->modelo->listarV2($anio, $tipo, $fecha_inicio, $fecha_fin);
        echo json_encode(['data' => $data]);
    }

    public function obtenerSiguiente()
    {
        $anio = $_POST['anio'] ?? date('Y');
        $tipo = $_POST['tipo'] ?? 'OFICIO';

        $siguiente = $this->modelo->obtenerSiguienteNumero($anio, $tipo);
        echo json_encode(['status' => true, 'numero' => $siguiente]);
    }

    public function guardar()
    {
        try {
            $anio = $_POST['anio'] ?? date('Y');
            $tipo = $_POST['tipo'] ?? 'OFICIO';

            // Verificamos si es numeración manual (input de texto) o automática
            // El frontend puede enviar un número forzado o dejar que el sistema calcule
            if (!empty($_POST['numero_manual'])) {
                $numero = (int) $_POST['numero_manual'];
            } else {
                $numero = $this->modelo->obtenerSiguienteNumero($anio, $tipo);
            }

            // Datos para guardar
            $datos = [
                'anio' => $anio,
                'tipo' => $tipo,
                'numero' => $numero,
                'sufijo' => $_POST['sufijo'] ?? null,
                'siglas' => $_POST['siglas'] ?? 'REGPOL-LIMA/UNIADM',
                'hoja_tramite' => $_POST['hoja_tramite'] ?? '',
                'fecha_emision' => $_POST['fecha_emision'] ?? date('Y-m-d H:i:s'),
                'clasificacion' => $_POST['clasificacion'] ?? '',
                'destino' => $_POST['destino'] ?? '',
                'asunto' => $_POST['asunto'] ?? '',
                'formulado_por' => $_POST['formulado_por'] ?? '',
                'observaciones' => $_POST['observaciones'] ?? '',
                'prioridad' => $_POST['prioridad'] ?? 'Normal',
                'demora' => $_POST['demora'] ?? ''
            ];

            $res = $this->modelo->registrar($datos);

            if ($res) {
                echo json_encode(['status' => true, 'msg' => "Documento {$tipo} N° {$numero}-{$anio} registrado correctamente."]);
            } else {
                echo json_encode(['status' => false, 'msg' => 'Error al registrar documento.']);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => false, 'msg' => 'Error interno: ' . $e->getMessage()]);
        }
    }

    public function guardarV2()
    {
        try {
            // Validar campos obligatorios
            if (empty($_POST['anio']) || empty($_POST['tipo']) || empty($_POST['asunto']) || empty($_POST['id_destino'])) {
                echo json_encode(['status' => false, 'msg' => 'Faltan campos obligatorios.']);
                return;
            }

            // Sanitizar y validar datos
            // Sanitizar y validar datos
            // Validar id_destino: Puede ser INT o STRING ('M-...')
            $raw_destino = $_POST['id_destino'];
            $id_destino_val = false;
            
            if (strpos($raw_destino, 'M-') === 0) {
                 // Es un destino virtual de matriz
                 $id_destino_val = htmlspecialchars(trim($raw_destino), ENT_QUOTES, 'UTF-8');
            } else {
                 $id_destino_val = filter_var($raw_destino, FILTER_VALIDATE_INT);
            }

            $datos = [
                'anio' => filter_var($_POST['anio'], FILTER_VALIDATE_INT),
                'tipo' => htmlspecialchars(trim($_POST['tipo']), ENT_QUOTES, 'UTF-8'),
                'asunto' => htmlspecialchars(trim($_POST['asunto']), ENT_QUOTES, 'UTF-8'),
                'id_destino' => $id_destino_val,
                'formulado_por' => htmlspecialchars(trim($_POST['formulado_por'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'es_manual' => $_POST['es_manual'] ?? 0,
                'num_manual' => filter_var($_POST['num_manual'] ?? 0, FILTER_VALIDATE_INT),
                'sufijo_manual' => isset($_POST['sufijo_manual']) ? htmlspecialchars(trim($_POST['sufijo_manual']), ENT_QUOTES, 'UTF-8') : null,
                'ht' => isset($_POST['ht']) ? htmlspecialchars(trim($_POST['ht']), ENT_QUOTES, 'UTF-8') : null,
                'se_solicita' => isset($_POST['se_solicita']) ? htmlspecialchars(trim($_POST['se_solicita']), ENT_QUOTES, 'UTF-8') : null,
                'observaciones' => isset($_POST['observaciones']) ? htmlspecialchars(trim($_POST['observaciones']), ENT_QUOTES, 'UTF-8') : null
            ];

            // Validar que año sea número válido y destino no haya fallado
            if ($datos['anio'] === false || $datos['id_destino'] === false) {
                echo json_encode(['status' => false, 'msg' => 'Datos inválidos en año o destino.']);
                return;
            }

            // Validar que el asunto tenga al menos 10 caracteres
            if (strlen($datos['asunto']) < 10) {
                echo json_encode(['status' => false, 'msg' => 'El asunto debe tener al menos 10 caracteres.']);
                return;
            }

            $res = $this->modelo->registrarV2($datos);
            echo json_encode($res);

        } catch (Exception $e) {
            echo json_encode(['status' => false, 'msg' => 'Error Excepción: ' . $e->getMessage()]);
        }
    }

    public function anular()
    {
        $id = $_POST['id'] ?? 0;
        if ($this->modelo->anular($id)) {
            echo json_encode(['status' => true, 'msg' => 'Documento anulado.']);
        } else {
            echo json_encode(['status' => false, 'msg' => 'Error al anular.']);
        }
    }

    public function eliminar()
    {
        try {
            $id = $_POST['id'] ?? 0;
            if ($this->modelo->eliminarV2($id)) {
                echo json_encode(['status' => true, 'msg' => 'Documento eliminado correctamente.']);
            } else {
                echo json_encode(['status' => false, 'msg' => 'Error al eliminar el documento.']);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => false, 'msg' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function obtener()
    {
        try {
            $id = $_GET['id'] ?? 0;
            $documento = $this->modelo->obtenerPorId($id);
            if ($documento) {
                echo json_encode(['status' => true, 'data' => $documento]);
            } else {
                echo json_encode(['status' => false, 'msg' => 'Documento no encontrado']);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => false, 'msg' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function actualizar()
    {
        try {
            // Validar campos obligatorios
            if (empty($_POST['id_documento']) || empty($_POST['tipo']) || empty($_POST['asunto']) || empty($_POST['id_destino'])) {
                echo json_encode(['status' => false, 'msg' => 'Faltan campos obligatorios.']);
                return;
            }

            // Sanitizar y validar datos
            $datos = [
                'id_documento' => filter_var($_POST['id_documento'], FILTER_VALIDATE_INT),
                'cod_tipo' => htmlspecialchars(trim($_POST['tipo']), ENT_QUOTES, 'UTF-8'),
                'ht' => isset($_POST['ht']) ? htmlspecialchars(trim($_POST['ht']), ENT_QUOTES, 'UTF-8') : null,
                'se_solicita' => isset($_POST['se_solicita']) ? htmlspecialchars(trim($_POST['se_solicita']), ENT_QUOTES, 'UTF-8') : null,
                'id_destino' => filter_var($_POST['id_destino'], FILTER_VALIDATE_INT),
                'asunto' => htmlspecialchars(trim($_POST['asunto']), ENT_QUOTES, 'UTF-8'),
                'formulado_por' => htmlspecialchars(trim($_POST['formulado_por'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'observaciones' => isset($_POST['observaciones']) ? htmlspecialchars(trim($_POST['observaciones']), ENT_QUOTES, 'UTF-8') : null,
                'fecha_documento' => null
            ];

            // Validar si quiere cambiar fecha (Solo Admin/Super)
            if (!empty($_POST['fecha_documento'])) {
                if (isset($_SESSION['rol']) && ($_SESSION['rol'] === 'Administrador' || $_SESSION['rol'] === 'Super Administrador')) {
                    $datos['fecha_documento'] = htmlspecialchars(trim($_POST['fecha_documento']), ENT_QUOTES, 'UTF-8');
                }
            }

            // Validar que id_documento e id_destino sean números válidos
            if ($datos['id_documento'] === false || $datos['id_destino'] === false) {
                echo json_encode(['status' => false, 'msg' => 'Datos inválidos.']);
                return;
            }

            // Validar que el asunto tenga al menos 10 caracteres
            if (strlen($datos['asunto']) < 10) {
                echo json_encode(['status' => false, 'msg' => 'El asunto debe tener al menos 10 caracteres.']);
                return;
            }

            $res = $this->modelo->actualizarV2($datos);
            echo json_encode($res);
        } catch (Exception $e) {
            echo json_encode(['status' => false, 'msg' => 'Error: ' . $e->getMessage()]);
        }
    }
}
