<?php
// modelo/PersonalModelo.php
declare(strict_types=1);

require_once __DIR__ . '/conexion.php';

class PersonalModelo
{
    private $db;

    public function __construct()
    {
        global $conexion;
        $this->db = $conexion;
    }

    // Listar todo el personal con sus Grados y Unidades, opcionalmente filtrando por mes de cumpleaños
    public function listar($mesCumple = null)
    {
        // Base de la consulta
        $sql = "SELECT p.*, 
                       g.nombre_grado, 
                       s.nombre_subunidad,
                       d.nombre_division,
                       r.nombre_region,
                       (SELECT estado FROM asistencia_novedades an 
                        WHERE an.id_personal = p.id_personal 
                        AND MONTH(an.fecha) = MONTH(CURRENT_DATE()) 
                        AND YEAR(an.fecha) = YEAR(CURRENT_DATE()) 
                        ORDER BY an.fecha DESC LIMIT 1) as estado_revista,
                       (SELECT observacion FROM asistencia_novedades an 
                        WHERE an.id_personal = p.id_personal 
                        AND MONTH(an.fecha) = MONTH(CURRENT_DATE()) 
                        AND YEAR(an.fecha) = YEAR(CURRENT_DATE()) 
                        ORDER BY an.fecha DESC LIMIT 1) as observacion_revista
                FROM mae_personal p
                LEFT JOIN mae_grados g ON p.id_grado = g.id_grado
                LEFT JOIN sub_unidades_policiales s ON p.id_subunidad = s.id_subunidad
                LEFT JOIN divisiones_policiales d ON s.id_division = d.id_division
                LEFT JOIN regiones_policiales r ON d.id_region = r.id_region
                WHERE p.estado = 'Activo'";

        // Filtro por Mes de Cumpleaños
        if ($mesCumple !== null && $mesCumple !== '') {
            $mesInt = (int)$mesCumple;
            // Filtrar por mes
            $sql .= " AND MONTH(p.fecha_nacimiento) = $mesInt";
            // Ordenar por DIA de nacimiento para ver quien cumple primero
            $sql .= " ORDER BY DAY(p.fecha_nacimiento) ASC";
        } else {
            // Orden normal si no hay filtro
            $sql .= " ORDER BY g.id_grado ASC, p.apellidos ASC";
        }

        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function registrar(array $datos)
    {
        $sql = "INSERT INTO mae_personal (id_grado, cip, dni, apellidos, nombres, sexo, fecha_nacimiento, id_subunidad, tipo_dni, situacion_especial, situacion_cip, cargo, funcion_horario, estado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Activo')";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("issssssssssss", 
            $datos['id_grado'],
            $datos['cip'],
            $datos['dni'],
            $datos['apellidos'],
            $datos['nombres'],
            $datos['sexo'],
            $datos['fecha_nacimiento'],
            $datos['id_subunidad'],
            $datos['tipo_dni'],
            $datos['situacion_especial'],
            $datos['situacion_cip'],
            $datos['cargo'],
            $datos['funcion_horario']
        );

        if ($stmt->execute()) {
            return ['status' => true, 'msg' => 'Personal registrado correctamente'];
        } else {
            // Verificar duplicados
            if ($this->db->errno === 1062) {
                return ['status' => false, 'msg' => 'El CIP ya está registrado'];
            }
            return ['status' => false, 'msg' => 'Error al registrar: ' . $stmt->error];
        }
    }

    public function actualizar(int $id, array $datos)
    {
        $sql = "UPDATE mae_personal SET 
                    id_grado = ?, 
                    cip = ?, 
                    dni = ?, 
                    apellidos = ?, 
                    nombres = ?, 
                    sexo = ?, 
                    fecha_nacimiento = ?, 
                    id_subunidad = ?,
                    tipo_dni = ?,
                    situacion_especial = ?,
                    situacion_cip = ?,
                    cargo = ?,
                    funcion_horario = ?
                WHERE id_personal = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("issssssssssssi", 
            $datos['id_grado'],
            $datos['cip'],
            $datos['dni'],
            $datos['apellidos'],
            $datos['nombres'],
            $datos['sexo'],
            $datos['fecha_nacimiento'],
            $datos['id_subunidad'],
            $datos['tipo_dni'],
            $datos['situacion_especial'],
            $datos['situacion_cip'],
            $datos['cargo'],
            $datos['funcion_horario'],
            $id
        );

        if ($stmt->execute()) {
            return ['status' => true, 'msg' => 'Datos actualizados correctamente'];
        } else {
            return ['status' => false, 'msg' => 'Error al actualizar: ' . $stmt->error];
        }
    }

    public function darbaja(int $id)
    {
        $sql = "UPDATE mae_personal SET estado = 'Baja' WHERE id_personal = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            return ['status' => true, 'msg' => 'Personal dado de baja correctamente'];
        } else {
            return ['status' => false, 'msg' => 'Error al dar de baja'];
        }
    }

    public function obtenerPorId($id)
    {
        $sql = "SELECT p.*, 
                g.nombre_grado, 
                s.nombre_subunidad, 
                d.nombre_division, 
                r.nombre_region,
                s.id_division,
                d.id_region
                FROM mae_personal p 
                LEFT JOIN mae_grados g ON p.id_grado = g.id_grado 
                LEFT JOIN sub_unidades_policiales s ON p.id_subunidad = s.id_subunidad 
                LEFT JOIN divisiones_policiales d ON s.id_division = d.id_division 
                LEFT JOIN regiones_policiales r ON d.id_region = r.id_region 
                WHERE p.id_personal = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Método auxiliar para Carga Masiva (Devuelve array ['NOMBRE' => ID])
    public function obtenerMapa($tabla, $colNombre, $colId)
    {
        $tablasPermitidas = ['mae_grados', 'sub_unidades_policiales', 'unidades_policiales'];
        if (!in_array($tabla, $tablasPermitidas)) return [];

        $mapa = [];
        // Convertimos a mayúsculas para asegurar coincidencia
        $sql = "SELECT $colId, UPPER($colNombre) as nombre FROM $tabla";
        
        // Si es unidades, solo activas
        if ($tabla === 'sub_unidades_policiales') {
            $sql .= " WHERE estado = 1";
        }
        
        $res = $this->db->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $mapa[trim($row['nombre'])] = $row[$colId];
            }
        }
        return $mapa;
    }

    // --- NUEVOS MÉTODOS PARA ASISTENCIA/REVISTA ---
    public function registrarAsistencia($id_personal, $estado, $observacion)
    {
        $sql = "INSERT INTO asistencia_novedades (id_personal, estado, observacion) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iss", $id_personal, $estado, $observacion);
        
        if ($stmt->execute()) {
            return ['status' => true, 'msg' => 'Revista registrada correctamente'];
        } else {
            return ['status' => false, 'msg' => 'Error al registrar: ' . $stmt->error];
        }
    }

    public function obtenerHistorialAsistencia($id_personal)
    {
        $sql = "SELECT * FROM asistencia_novedades WHERE id_personal = ? ORDER BY fecha DESC LIMIT 10";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id_personal);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function eliminarNovedad($id_novedad)
    {
        $sql = "DELETE FROM asistencia_novedades WHERE id_novedad = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id_novedad);
        return $stmt->execute();
    }

    // --- NUEVOS MÉTODOS PARA ASISTENCIA/REVISTA ---


    public function limpiar()
    {
        // Desactivar chequeo de claves foráneas temporalmente para permitir TRUNCATE
        $this->db->query("SET FOREIGN_KEY_CHECKS = 0");
        
        $res = $this->db->query("TRUNCATE TABLE mae_personal");
        if (!$res) {
             $res = $this->db->query("DELETE FROM mae_personal");
        }
        
        // Resetear auto-increment si se usó DELETE
        if ($res) {
            $this->db->query("ALTER TABLE mae_personal AUTO_INCREMENT = 1");
        }

        $this->db->query("SET FOREIGN_KEY_CHECKS = 1");
        
        return $res;
    }

    // --- MÉTODOS PARA DASHBOARD / ALERTAS ---
    public function obtenerCumpleanieros($dias = 30)
    {
        // Esta consulta calcula la fecha del próximo cumpleaños y filtra los que están en el rango
        $sql = "SELECT p.*, 
                       g.nombre_grado,
                       s.nombre_subunidad,
                       DATE_ADD(p.fecha_nacimiento, 
                            INTERVAL YEAR(CURDATE())-YEAR(p.fecha_nacimiento) + 
                                     IF(DATE_FORMAT(CURDATE(),'%m%d') > DATE_FORMAT(p.fecha_nacimiento,'%m%d'), 1, 0)
                            YEAR) AS proximo_cumpleanos,
                       TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) as edad_actual
                FROM mae_personal p
                LEFT JOIN mae_grados g ON p.id_grado = g.id_grado
                LEFT JOIN sub_unidades_policiales s ON p.id_subunidad = s.id_subunidad
                WHERE p.estado = 'Activo'
                HAVING proximo_cumpleanos BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                ORDER BY proximo_cumpleanos ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $dias); // 'i' para entero
        
        if ($stmt->execute()) {
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            return [];
        }
    }
}
