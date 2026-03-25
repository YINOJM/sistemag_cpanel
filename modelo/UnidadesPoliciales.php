<?php
/**
 * Modelo para Unidades Policiales
 * Sistema Integrado de Gestión - UE009 DIRTEPOL LIMA
 */

class UnidadesPoliciales
{
    private $conexion;

    public function __construct($db)
    {
        $this->conexion = $db;
    }

    // ==========================================
    // REGIONES POLICIALES
    // ==========================================

    public function obtenerRegiones($soloActivas = true)
    {
        $where = $soloActivas ? "WHERE estado = 1" : "";
        $sql = "SELECT * FROM regiones_policiales $where ORDER BY nombre_region";
        $result = $this->conexion->query($sql);

        $regiones = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $regiones[] = $row;
            }
        }
        return $regiones;
    }

    public function obtenerRegionPorId($id)
    {
        $stmt = $this->conexion->prepare("SELECT * FROM regiones_policiales WHERE id_region = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function crearRegion($datos)
    {
        $stmt = $this->conexion->prepare(
            "INSERT INTO regiones_policiales (nombre_region, codigo_region, descripcion, usuario_creacion) 
             VALUES (?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "sssi",
            $datos['nombre_region'],
            $datos['codigo_region'],
            $datos['descripcion'],
            $datos['usuario_id']
        );

        return $stmt->execute();
    }

    public function actualizarRegion($id, $datos)
    {
        $stmt = $this->conexion->prepare(
            "UPDATE regiones_policiales 
             SET nombre_region = ?, codigo_region = ?, descripcion = ?, usuario_modificacion = ? 
             WHERE id_region = ?"
        );

        $stmt->bind_param(
            "sssii",
            $datos['nombre_region'],
            $datos['codigo_region'],
            $datos['descripcion'],
            $datos['usuario_id'],
            $id
        );

        return $stmt->execute();
    }

    public function eliminarRegion($id)
    {
        // Eliminación lógica
        $stmt = $this->conexion->prepare("DELETE FROM regiones_policiales WHERE id_region = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    // ==========================================
    // DIVISIONES POLICIALES
    // ==========================================

    public function obtenerDivisiones($idRegion = null, $soloActivas = true)
    {
        $where = [];
        if ($soloActivas)
            $where[] = "d.estado = 1";
        if ($idRegion)
            $where[] = "d.id_region = " . intval($idRegion);

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $sql = "SELECT d.*, r.nombre_region,
                (SELECT COUNT(*) FROM sub_unidades_policiales s WHERE s.id_division = d.id_division AND s.estado = 1) as total_subunidades
                FROM divisiones_policiales d
                INNER JOIN regiones_policiales r ON d.id_region = r.id_region
                $whereClause
                ORDER BY r.nombre_region, d.nombre_division";

        $result = $this->conexion->query($sql);

        $divisiones = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $divisiones[] = $row;
            }
        }
        return $divisiones;
    }

    public function obtenerDivisionPorId($id)
    {
        $stmt = $this->conexion->prepare(
            "SELECT d.*, r.nombre_region 
             FROM divisiones_policiales d
             INNER JOIN regiones_policiales r ON d.id_region = r.id_region
             WHERE d.id_division = ?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function crearDivision($datos)
    {
        $stmt = $this->conexion->prepare(
            "INSERT INTO divisiones_policiales 
             (id_region, nombre_division, codigo_division, descripcion, direccion, telefono, email, usuario_creacion) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "issssssi",
            $datos['id_region'],
            $datos['nombre_division'],
            $datos['codigo_division'],
            $datos['descripcion'],
            $datos['direccion'],
            $datos['telefono'],
            $datos['email'],
            $datos['usuario_id']
        );

        return $stmt->execute();
    }

    public function actualizarDivision($id, $datos)
    {
        $stmt = $this->conexion->prepare(
            "UPDATE divisiones_policiales 
             SET id_region = ?, nombre_division = ?, codigo_division = ?, descripcion = ?, 
                 direccion = ?, telefono = ?, email = ?, usuario_modificacion = ? 
             WHERE id_division = ?"
        );

        $stmt->bind_param(
            "issssssii",
            $datos['id_region'],
            $datos['nombre_division'],
            $datos['codigo_division'],
            $datos['descripcion'],
            $datos['direccion'],
            $datos['telefono'],
            $datos['email'],
            $datos['usuario_id'],
            $id
        );

        return $stmt->execute();
    }

    public function eliminarDivision($id)
    {
        $stmt = $this->conexion->prepare("DELETE FROM divisiones_policiales WHERE id_division = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    // ==========================================
    // SUB-UNIDADES POLICIALES
    // ==========================================

    public function obtenerSubUnidades($idDivision = null, $soloActivas = true)
    {
        $where = [];
        if ($soloActivas)
            $where[] = "s.estado = 1";
        if ($idDivision)
            $where[] = "s.id_division = " . intval($idDivision);

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $sql = "SELECT s.*, d.nombre_division, r.nombre_region 
                FROM sub_unidades_policiales s
                INNER JOIN divisiones_policiales d ON s.id_division = d.id_division
                INNER JOIN regiones_policiales r ON d.id_region = r.id_region
                $whereClause
                ORDER BY r.nombre_region, d.nombre_division, s.tipo_unidad, s.nombre_subunidad";

        $result = $this->conexion->query($sql);

        $subunidades = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $subunidades[] = $row;
            }
        }
        return $subunidades;
    }

    public function obtenerSubUnidadPorId($id)
    {
        $stmt = $this->conexion->prepare(
            "SELECT s.*, d.nombre_division, d.id_region, r.nombre_region 
             FROM sub_unidades_policiales s
             INNER JOIN divisiones_policiales d ON s.id_division = d.id_division
             INNER JOIN regiones_policiales r ON d.id_region = r.id_region
             WHERE s.id_subunidad = ?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function crearSubUnidad($datos)
    {
        $stmt = $this->conexion->prepare(
            "INSERT INTO sub_unidades_policiales 
             (id_division, nombre_subunidad, tipo_unidad, codigo_subunidad, descripcion, 
              direccion, telefono, email, responsable, departamento, provincia, distrito, usuario_creacion) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "isssssssssssi",
            $datos['id_division'],
            $datos['nombre_subunidad'],
            $datos['tipo_unidad'],
            $datos['codigo_subunidad'],
            $datos['descripcion'],
            $datos['direccion'],
            $datos['telefono'],
            $datos['email'],
            $datos['responsable'],
            $datos['departamento'],
            $datos['provincia'],
            $datos['distrito'],
            $datos['usuario_id']
        );

        return $stmt->execute();
    }

    public function actualizarSubUnidad($id, $datos)
    {
        $stmt = $this->conexion->prepare(
            "UPDATE sub_unidades_policiales 
             SET id_division = ?, nombre_subunidad = ?, tipo_unidad = ?, codigo_subunidad = ?, 
                 descripcion = ?, direccion = ?, telefono = ?, email = ?, responsable = ?, 
                 departamento = ?, provincia = ?, distrito = ?, estado = ?,
                 usuario_modificacion = ? 
             WHERE id_subunidad = ?"
        );

        $stmt->bind_param(
            "issssssssssssii",
            $datos['id_division'],
            $datos['nombre_subunidad'],
            $datos['tipo_unidad'],
            $datos['codigo_subunidad'],
            $datos['descripcion'],
            $datos['direccion'],
            $datos['telefono'],
            $datos['email'],
            $datos['responsable'],
            $datos['departamento'],
            $datos['provincia'],
            $datos['distrito'],
            $datos['estado'],
            $datos['usuario_id'],
            $id
        );

        return $stmt->execute();
    }

    public function eliminarSubUnidad($id)
    {
        $stmt = $this->conexion->prepare("DELETE FROM sub_unidades_policiales WHERE id_subunidad = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    // ==========================================
    // TIPOS DE UNIDAD
    // ==========================================

    public function obtenerTiposUnidad($soloActivos = true)
    {
        $where = $soloActivos ? "WHERE estado = 1" : "";
        $sql = "SELECT * FROM tipos_unidad_policial $where ORDER BY nombre_tipo";
        $result = $this->conexion->query($sql);

        $tipos = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $tipos[] = $row;
            }
        }
        return $tipos;
    }

    // ==========================================
    // CONSULTAS ESPECIALES
    // ==========================================

    public function buscarUnidad($termino)
    {
        $termino = "%$termino%";
        $stmt = $this->conexion->prepare(
            "SELECT s.*, d.nombre_division, r.nombre_region 
             FROM sub_unidades_policiales s
             INNER JOIN divisiones_policiales d ON s.id_division = d.id_division
             INNER JOIN regiones_policiales r ON d.id_region = r.id_region
             WHERE (s.nombre_subunidad LIKE ? OR d.nombre_division LIKE ? OR r.nombre_region LIKE ?)
             AND s.estado = 1
             ORDER BY r.nombre_region, d.nombre_division, s.nombre_subunidad
             LIMIT 50"
        );
        $stmt->bind_param("sss", $termino, $termino, $termino);
        $stmt->execute();
        $result = $stmt->get_result();

        $resultados = [];
        while ($row = $result->fetch_assoc()) {
            $resultados[] = $row;
        }
        return $resultados;
    }

    public function obtenerJerarquiaCompleta()
    {
        $sql = "SELECT * FROM vista_unidades_completa ORDER BY nombre_region, nombre_division, tipo_unidad, nombre_subunidad";
        $result = $this->conexion->query($sql);

        $unidades = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $unidades[] = $row;
            }
        }
        return $unidades;
    }

    public function obtenerDistritosUnicos()
    {
        $sql = "SELECT 
                    departamento,
                    provincia,
                    distrito,
                    COUNT(*) as total_comisarias
                FROM sub_unidades_policiales
                WHERE estado = 1 
                    AND departamento IS NOT NULL 
                    AND departamento != ''
                    AND distrito IS NOT NULL 
                    AND distrito != ''
                GROUP BY departamento, provincia, distrito
                ORDER BY departamento, provincia, distrito";

        $result = $this->conexion->query($sql);

        $distritos = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $distritos[] = $row;
            }
        }
        return $distritos;
    }

    public function obtenerEstadisticas()
    {
        $sql = "SELECT * FROM vista_estadisticas_divisiones";
        $result = $this->conexion->query($sql);

        $estadisticas = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $estadisticas[] = $row;
            }
        }
        return $estadisticas;
    }

    public function contarPorTipo()
    {
        $sql = "SELECT tipo_unidad, COUNT(*) as total 
                FROM sub_unidades_policiales 
                WHERE estado = 1 
                GROUP BY tipo_unidad 
                ORDER BY total DESC";
        $result = $this->conexion->query($sql);

        $conteo = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $conteo[] = $row;
            }
        }
        return $conteo;
    }

    public function obtenerUbicaciones()
    {
        $sql = "SELECT DISTINCT departamento, provincia, distrito 
                FROM sub_unidades_policiales 
                WHERE estado = 1 
                AND departamento IS NOT NULL AND departamento != '' 
                AND provincia IS NOT NULL AND provincia != '' 
                AND distrito IS NOT NULL AND distrito != ''
                ORDER BY departamento, provincia, distrito";
        
        $result = $this->conexion->query($sql);
        
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        return $data;
    }
}
?>