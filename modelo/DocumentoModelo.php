<?php
require_once 'conexion.php';

class DocumentoModelo
{
    private $db;

    public function __construct()
    {
        global $conexion;
        $this->db = $conexion;
    }

    /**
     * Lista documentos con filtros opcionales
     */
    public function listar($anio = null, $tipo = null)
    {
        $sql = "SELECT * FROM gestion_documental WHERE 1=1";
        $params = [];
        $types = "";

        if ($anio) {
            $sql .= " AND anio = ?";
            $params[] = $anio;
            $types .= "i";
        }

        if ($tipo) {
            $sql .= " AND tipo = ?";
            $params[] = $tipo;
            $types .= "s";
        }

        $sql .= " ORDER BY id DESC";

        $stmt = $this->db->prepare($sql);
        if ($types) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Obtiene el siguiente número correlativo usando SP
     */
    public function obtenerSiguienteNumero($anio, $tipo)
    {
        // Inicializamos variable de salida
        $nuevo_numero = 0;

        // Llamada al Stored Procedure
        $stmt = $this->db->prepare("CALL sp_generar_correlativo(?, ?, @nuevo_numero)");
        $stmt->bind_param("is", $anio, $tipo);
        $stmt->execute();
        $stmt->close();

        // Obtener el resultado
        $result = $this->db->query("SELECT @nuevo_numero as numero");
        $row = $result->fetch_assoc();
        return $row['numero'] ?? 1;
    }

    /**
     * Registra un nuevo documento
     */
    public function registrar($datos)
    {
        $sql = "INSERT INTO gestion_documental (
                    anio, tipo, numero, sufijo, siglas, hoja_tramite, fecha_emision,
                    clasificacion, destino, asunto, formulado_por, observaciones, prioridad, demora, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            "isisssssssssss",
            $datos['anio'],
            $datos['tipo'],
            $datos['numero'],
            $datos['sufijo'],
            $datos['siglas'],
            $datos['hoja_tramite'],
            $datos['fecha_emision'],
            $datos['clasificacion'],
            $datos['destino'],
            $datos['asunto'],
            $datos['formulado_por'],
            $datos['observaciones'],
            $datos['prioridad'],
            $datos['demora']
        );

        return $stmt->execute();
    }

    public function obtenerPorId($id)
    {
        $stmt = $this->db->prepare("
            SELECT d.*, m.nombre_destino 
            FROM documentos d 
            LEFT JOIN mae_destinos m ON d.id_destino = m.id_destino 
            WHERE d.id_documento = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function anular($id)
    {
        $stmt = $this->db->prepare("UPDATE gestion_documental SET estado = 'Anulado' WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    // --- V2 LOGIC (Adaptado del sistema de referencia) ---

    // 1. Obtener Destinos Uniificados (Legacy + Matriz)
    public function obtenerDestinos()
    {
        // A. Obtener destinos existentes (Legacy/Manuales)
        // Ordenar por prioridad
        $legacy = $this->db->query("SELECT id_destino, nombre_destino, orden FROM mae_destinos WHERE activo = 1")->fetch_all(MYSQLI_ASSOC);
        
        // Indexar para búsqueda rápida por nombre
        $existingNames = [];
        $finalList = [];
        
        // Helper closure to filter unwanted units
        $isRestricted = function($name) {
            $n = strtoupper(trim($name));
            // Palabras clave de unidades superiores que NO deben salir
            $forbidden = ['DIVPOL', 'REGPOL', 'REGION ', 'REGIÓN ', 'DIRTEPOL', 'MACREPOL', 'SEC ', 'SECCION '];
            foreach ($forbidden as $f) {
                if (strpos($n, $f) !== false) return true;
            }
            return false;
        };

        foreach ($legacy as $l) {
            $nameUpper = strtoupper(trim($l['nombre_destino']));
            
            // EXCLUIR DIVPOLES Y REGIONES
            if ($isRestricted($nameUpper)) {
                continue;
            }

            $existingNames[$nameUpper] = true;
            $finalList[] = $l;
        }

        // B. Obtener unidades de la Matriz
        $matrixSql = "SELECT id_subunidad, nombre_subunidad FROM sub_unidades_policiales 
                      WHERE estado = 1"; 
        $matrix = $this->db->query($matrixSql)->fetch_all(MYSQLI_ASSOC);

        foreach ($matrix as $m) {
            $name = strtoupper(trim($m['nombre_subunidad']));
            
            // APLICAR MISMO FILTRO A LA MATRIZ (Por seguridad)
            if ($isRestricted($name)) {
                continue;
            }

            if (!isset($existingNames[$name])) {
                $finalList[] = [
                    'id_destino' => 'M-' . $m['id_subunidad'], 
                    'nombre_destino' => $name,
                    'orden' => 999
                ];
            }
        }

        // Ordenar la lista final
        usort($finalList, function($a, $b) {
            // Comparar orden
            if ($a['orden'] != $b['orden']) {
                return $a['orden'] - $b['orden'];
            }
            // Comparar nombre
            return strcmp($a['nombre_destino'], $b['nombre_destino']);
        });

        return $finalList;
    }

    // 2. Obtener siguiente correlativo V2
    public function obtenerSiguienteCorrelativoV2($anio, $tipo)
    {
        $sql = "SELECT MAX(num_correlativo) as ultimo FROM documentos WHERE anio = ? AND cod_tipo = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("is", $anio, $tipo);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        return ($res['ultimo'] ?? 0) + 1;
    }

    // 3. Registrar V2 (Lógica exacta del reference + Auto-Link Destinos)
    public function registrarV2($datos)
    {
        // VERIFICACIÓN DE DESTINO VIRTUAL (De Matriz)
        $id_destino_final = $datos['id_destino'];
        
        // Si el ID viene como string 'M-123', es de la Matriz y no existe en mae_destinos
        if (is_string($id_destino_final) && strpos($id_destino_final, 'M-') === 0) {
            $id_subunidad = (int) str_replace('M-', '', $id_destino_final);
            
            // a. Verificar si ya se creó recientemente (condición de carrera)
            // Obtenemos el nombre de la subunidad
            $stmtSub = $this->db->prepare("SELECT nombre_subunidad FROM sub_unidades_policiales WHERE id_subunidad = ?");
            $stmtSub->bind_param("i", $id_subunidad);
            $stmtSub->execute();
            $subData = $stmtSub->get_result()->fetch_assoc();
            
            if ($subData) {
                $nombre_matriz = $subData['nombre_subunidad'];
                
                // Buscar si existe en mae_destinos por nombre
                $stmtCheck = $this->db->prepare("SELECT id_destino FROM mae_destinos WHERE nombre_destino = ?");
                $stmtCheck->bind_param("s", $nombre_matriz);
                $stmtCheck->execute();
                $checkRes = $stmtCheck->get_result()->fetch_assoc();
                
                if ($checkRes) {
                    // Ya existe, usamos ese
                    $id_destino_final = $checkRes['id_destino'];
                } else {
                    // No existe, lo creamos
                    $stmtIns = $this->db->prepare("INSERT INTO mae_destinos (nombre_destino, activo, orden, id_region, id_division, id_subunidad) VALUES (?, 1, 999, 2, 2, ?)");
                    $stmtIns->bind_param("si", $nombre_matriz, $id_subunidad);
                    if ($stmtIns->execute()) {
                        $id_destino_final = $stmtIns->insert_id;
                    } else {
                        return ['status' => false, 'msg' => 'Error al sincronizar destino de la matriz.'];
                    }
                }
            } else {
                return ['status' => false, 'msg' => 'Unidad de matriz no encontrada.'];
            }
        }


        // $datos: anio, tipo, asunto, id_destino, formulado_por, es_manual, num_manual, sufijo_manual, ht, se_solicita, observaciones, demora
        $anio = $datos['anio'];
        $tipo = $datos['tipo'];

        // Determinar número
        if (!empty($datos['es_manual']) && $datos['es_manual'] == 1) {
            $correlativo = $datos['num_manual'];
            // Convertir sufijo a mayúsculas
            $sufijo = !empty($datos['sufijo_manual']) ? strtoupper($datos['sufijo_manual']) : null;
        } else {
            $correlativo = $this->obtenerSiguienteCorrelativoV2($anio, $tipo);
            $sufijo = null;
        }

        // Generar num_completo
        $sufijoStr = $sufijo ? "-$sufijo" : "";
        $correlativo_padded = str_pad((string)$correlativo, 4, '0', STR_PAD_LEFT);
        $num_completo = "$tipo N° $correlativo_padded$sufijoStr";

        $sql = "INSERT INTO documentos (anio, cod_tipo, num_correlativo, num_sufijo, num_completo, asunto, id_destino, usuario_formulador, ht, se_solicita, observaciones, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $this->db->prepare($sql);
        // anio(i), cod_tipo(s), num_corr(i), num_suf(s), num_comp(s), asunto(s), id_dest(i), usu(s), ht(s), se_solicita(s), obs(s)
        $stmt->bind_param(
            "isisssissss",
            $anio,
            $tipo,
            $correlativo,
            $sufijo,
            $num_completo,
            $datos['asunto'],
            $id_destino_final, // Usamos el ID final resolto (Sea legacy o nuevo)
            $datos['formulado_por'],
            $datos['ht'],
            $datos['se_solicita'],
            $datos['observaciones']
        );

        if ($stmt->execute()) {
            // Generar solo_numero (número corto con sufijo si existe)
            $solo_numero = $sufijo ? "$correlativo_padded-$sufijo" : "$correlativo_padded";

            return [
                'status' => true,
                'msg' => "Documento Registrado: <b>$num_completo</b>",
                'numero' => $num_completo,        // "OFICIO N° 4" o "OFICIO N° 4-A"
                'solo_numero' => $solo_numero     // "4" o "4-A"
            ];
        } else {
            // Manejo de duplicados (Error 1062)
            if ($this->db->errno == 1062) {
                return ['status' => false, 'msg' => "El número $correlativo ya existe para este año. Intente de nuevo."];
            }
            return ['status' => false, 'msg' => $stmt->error];
        }
    }

    // 4. Listar V2
    public function listarV2($anio = null, $tipo = null, $fecha_inicio = null, $fecha_fin = null)
    {
        // Usamos nombre_destino que ahora es standard
        $sql = "SELECT d.*, m.nombre_destino 
                FROM documentos d 
                LEFT JOIN mae_destinos m ON d.id_destino = m.id_destino 
                WHERE 1=1";

        $params = [];
        $types = "";

        // Si hay rango de fechas, PRIMA sobre el filtro de año (permite cruzar años)
        if ($fecha_inicio && $fecha_fin) {
             // Castear a DATE para evitar problemas con horas
             $sql .= " AND DATE(d.created_at) BETWEEN ? AND ?";
             $params[] = $fecha_inicio;
             $params[] = $fecha_fin;
             $types .= "ss";
        } elseif ($anio) {
            // Si no hay fechas, usamos el año
            $sql .= " AND d.anio = ?";
            $params[] = $anio;
            $types .= "i";
        }

        if ($tipo) {
            // Si el tipo es 'OTROS', filtrar documentos que NO sean los principales
            if ($tipo === 'OTROS') {
                $sql .= " AND d.cod_tipo NOT IN ('OFICIO', 'INFORME', 'ORDEN TELEFONICA', 'MEMORANDUM')";
            } else {
                $sql .= " AND d.cod_tipo = ?";
                $params[] = $tipo;
                $types .= "s";
            }
        }

        $sql .= " ORDER BY d.id_documento DESC";

        $stmt = $this->db->prepare($sql);
        if ($types) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function anularV2($id)
    {
        $stmt = $this->db->prepare("UPDATE documentos SET estado = 'ANULADO' WHERE id_documento = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function eliminarV2($id)
    {
        $stmt = $this->db->prepare("DELETE FROM documentos WHERE id_documento = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function actualizarV2($datos)
    {
        try {
            // Verificar si viene fecha para actualizar (Solo Admins)
            $sqlFecha = "";
            $fecha_val = null;
            if (!empty($datos['fecha_documento'])) {
                $sqlFecha = ", created_at = ?";
                $fecha_val = $datos['fecha_documento'] . ' ' . date('H:i:s');
            }

            // Verificar si cambió el cod_tipo para actualizar correlativo
            $id_documento = $datos['id_documento'];
            $nuevo_tipo = $datos['cod_tipo'];
            
            $stmtCurrent = $this->db->prepare("SELECT anio, cod_tipo, num_sufijo FROM documentos WHERE id_documento = ?");
            $stmtCurrent->bind_param("i", $id_documento);
            $stmtCurrent->execute();
            $current = $stmtCurrent->get_result()->fetch_assoc();

            $sqlNumeracion = "";
            $nuevo_correlativo = null;
            $nuevo_num_completo = null;

            if ($current && $current['cod_tipo'] !== $nuevo_tipo) {
                $anio = $current['anio'];
                $sufijo = $current['num_sufijo'];
                $nuevo_correlativo = $this->obtenerSiguienteCorrelativoV2($anio, $nuevo_tipo);
                
                $correlativo_padded = str_pad((string)$nuevo_correlativo, 4, '0', STR_PAD_LEFT);
                $sufijoStr = $sufijo ? "-$sufijo" : "";
                $nuevo_num_completo = "$nuevo_tipo N° $correlativo_padded$sufijoStr";
                
                $sqlNumeracion = ", num_correlativo = ?, num_completo = ?";
            }

            // Armar params base
            $params = [
                $nuevo_tipo,
                $datos['ht'],
                $datos['se_solicita'],
                $datos['id_destino'],
                $datos['asunto'],
                $datos['formulado_por'],
                $datos['observaciones']
            ];
            $tipos = "sssisss"; 

            if ($sqlNumeracion !== "") {
                $params[] = $nuevo_correlativo;
                $params[] = $nuevo_num_completo;
                $tipos .= "is";
            }

            if ($sqlFecha !== "") {
                $params[] = $fecha_val;
                $tipos .= "s";
            }

            $params[] = $id_documento;
            $tipos .= "i";

            $stmt = $this->db->prepare("
                UPDATE documentos 
                SET cod_tipo = ?,
                    ht = ?, 
                    se_solicita = ?, 
                    id_destino = ?, 
                    asunto = ?, 
                    usuario_formulador = ?, 
                    observaciones = ?
                    $sqlNumeracion
                    $sqlFecha
                WHERE id_documento = ?
            ");

            $stmt->bind_param($tipos, ...$params);

            if ($stmt->execute()) {
                $msg = 'Documento actualizado correctamente';
                if ($sqlNumeracion !== "") {
                    $msg .= ". La nueva numeración generada es: $nuevo_num_completo";
                }
                return ['status' => true, 'msg' => $msg];
            } else {
                return ['status' => false, 'msg' => 'Error al actualizar el documento'];
            }
        } catch (Exception $e) {
            return ['status' => false, 'msg' => 'Error: ' . $e->getMessage()];
        }
    }
}
