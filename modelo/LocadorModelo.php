<?php
// modelo/LocadorModelo.php
declare(strict_types=1);

require_once __DIR__ . '/conexion.php';

class LocadorModelo
{
    private $db;

    public function __construct()
    {
        global $conexion;
        $this->db = $conexion;
    }

    public function listar($mes = null)
    {
        $sql = "SELECT * FROM locadores WHERE estado != 'ELIMINADO'";
        
        if (!empty($mes)) {
            $sql .= " AND MONTH(fecha_nacimiento) = " . (int)$mes;
        }
        
        $sql .= " ORDER BY nombres_apellidos ASC";
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function registrar(array $datos)
    {
        $campos = "dni_ruc, dni, fecha_nacimiento, nombres_apellidos, sexo, correo, celular, servicio_descripcion, monto_mensual, retencion_aplicable, 
                   fecha_inicio, fecha_fin, unidad_asignada, archivo_pdf, archivo_siga, estado,
                   meta, esp_gasto, num_pedido_siga, num_cmn, codigo_siga,
                   monto_ene, monto_feb, monto_mar, monto_abr, monto_may, monto_jun, 
                   monto_jul, monto_ago, monto_set, monto_oct, monto_nov, monto_dic,
                   entregable_ene, entregable_feb, entregable_mar, entregable_abr, entregable_may, entregable_jun,
                   entregable_jul, entregable_ago, entregable_set, entregable_oct, entregable_nov, entregable_dic,
                   recontratacion";
                   
        $values = str_repeat("?, ", 45) . "?"; // 46 placeholders total
        
        $sql = "INSERT INTO locadores ($campos) VALUES ($values)";
        
        $stmt = $this->db->prepare($sql);
        $retencion = isset($datos['retencion_aplicable']) ? (int)$datos['retencion_aplicable'] : 1;
        $sexo = isset($datos['sexo']) ? $datos['sexo'] : 'M';
        
        // Total 46 params.
        // Old types: "sssssssdis..." (45 params)
        // Adding sexo(s) at pos 4 (after nombres_apellidos)
        // "ssss" + "s" + "ssdi..."
        $types = "ssssssssdisssssssssssddddddddddddiiiiiiiiiiiis";

        $stmt->bind_param($types, 
            $datos['dni_ruc'], $datos['dni'], $datos['fecha_nacimiento'], $datos['nombres_apellidos'], $sexo, $datos['correo'], $datos['celular'],
            $datos['servicio_descripcion'], $datos['monto_mensual'], $retencion,
            $datos['fecha_inicio'], $datos['fecha_fin'], $datos['unidad_asignada'],
            $datos['archivo_pdf'], $datos['archivo_siga'], $datos['estado'],
            $datos['meta'], $datos['esp_gasto'], $datos['num_pedido_siga'], $datos['num_cmn'], $datos['codigo_siga'],
            $datos['monto_ene'], $datos['monto_feb'], $datos['monto_mar'], $datos['monto_abr'],
            $datos['monto_may'], $datos['monto_jun'], $datos['monto_jul'], $datos['monto_ago'],
            $datos['monto_set'], $datos['monto_oct'], $datos['monto_nov'], $datos['monto_dic'],
            $datos['entregable_ene'], $datos['entregable_feb'], $datos['entregable_mar'], $datos['entregable_abr'],
            $datos['entregable_may'], $datos['entregable_jun'], $datos['entregable_jul'], $datos['entregable_ago'],
            $datos['entregable_set'], $datos['entregable_oct'], $datos['entregable_nov'], $datos['entregable_dic'],
            $datos['recontratacion']
        );

        try {
            if ($stmt->execute()) {
                return ['status' => true, 'msg' => 'Locador registrado correctamente'];
            } else {
                return ['status' => false, 'msg' => 'Error al registrar: ' . $stmt->error];
            }
        } catch (\mysqli_sql_exception $e) {
            return ['status' => false, 'msg' => 'Error de base de datos: ' . $e->getMessage()];
        }
    }

    public function actualizar(int $id, array $datos)
    {
        $sql = "UPDATE locadores SET 
                    dni_ruc = ?, dni = ?, fecha_nacimiento = ?, nombres_apellidos = ?, sexo = ?, correo = ?, celular = ?, servicio_descripcion = ?, monto_mensual = ?, 
                    retencion_aplicable = ?, fecha_inicio = ?, fecha_fin = ?, unidad_asignada = ?,
                    archivo_pdf = ?, archivo_siga = ?, estado = ?,
                    meta = ?, esp_gasto = ?, num_pedido_siga = ?, num_cmn = ?, codigo_siga = ?,
                    monto_ene = ?, monto_feb = ?, monto_mar = ?, monto_abr = ?, monto_may = ?, monto_jun = ?, 
                    monto_jul = ?, monto_ago = ?, monto_set = ?, monto_oct = ?, monto_nov = ?, monto_dic = ?,
                    entregable_ene = ?, entregable_feb = ?, entregable_mar = ?, entregable_abr = ?, entregable_may = ?, entregable_jun = ?,
                    entregable_jul = ?, entregable_ago = ?, entregable_set = ?, entregable_oct = ?, entregable_nov = ?, entregable_dic = ?,
                    recontratacion = ?
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $retencion = isset($datos['retencion_aplicable']) ? (int)$datos['retencion_aplicable'] : 1;
        $sexo = isset($datos['sexo']) ? $datos['sexo'] : 'M';
        
        // Types: register(46) + i (id) => 47
        $types = "ssssssssdisssssssssssddddddddddddiiiiiiiiiiiisi"; 

        $stmt->bind_param($types, 
            $datos['dni_ruc'], $datos['dni'], $datos['fecha_nacimiento'], $datos['nombres_apellidos'], $sexo, $datos['correo'], $datos['celular'],
            $datos['servicio_descripcion'], $datos['monto_mensual'], $retencion,
            $datos['fecha_inicio'], $datos['fecha_fin'], $datos['unidad_asignada'],
            $datos['archivo_pdf'], $datos['archivo_siga'], $datos['estado'],
            $datos['meta'], $datos['esp_gasto'], $datos['num_pedido_siga'], $datos['num_cmn'], $datos['codigo_siga'],
            $datos['monto_ene'], $datos['monto_feb'], $datos['monto_mar'], $datos['monto_abr'],
            $datos['monto_may'], $datos['monto_jun'], $datos['monto_jul'], $datos['monto_ago'],
            $datos['monto_set'], $datos['monto_oct'], $datos['monto_nov'], $datos['monto_dic'],
            $datos['entregable_ene'], $datos['entregable_feb'], $datos['entregable_mar'], $datos['entregable_abr'],
            $datos['entregable_may'], $datos['entregable_jun'], $datos['entregable_jul'], $datos['entregable_ago'],
            $datos['entregable_set'], $datos['entregable_oct'], $datos['entregable_nov'], $datos['entregable_dic'],
            $datos['recontratacion'],
            $id
        );

        try {
            if ($stmt->execute()) {
                return ['status' => true, 'msg' => 'Locador actualizado correctamente'];
            } else {
                return ['status' => false, 'msg' => 'Error al actualizar: ' . $stmt->error];
            }
        } catch (\mysqli_sql_exception $e) {
            return ['status' => false, 'msg' => 'Error de base de datos: ' . $e->getMessage()];
        }
    }

    public function obtener($id)
    {
        $id = (int)$id;
        $sql = "SELECT * FROM locadores WHERE id = $id";
        $result = $this->db->query($sql);
        return $result->fetch_assoc();
    }
    
    // Alias para compatibilidad con Controlador que usa obtenerPorId
    public function obtenerPorId($id)
    {
        return $this->obtener($id);
    }

    public function eliminar($id)
    {
        $id = (int)$id;
        // Baja lógica: cambiar estado a ELIMINADO
        $sql = "UPDATE locadores SET estado = 'ELIMINADO' WHERE id = $id";
        if ($this->db->query($sql)) {
            return ['status' => true, 'msg' => 'Registro eliminado correctamente'];
        } else {
            return ['status' => false, 'msg' => 'Error al eliminar el registro: ' . $this->db->error];
        }
    }
    
    public function verificarSolapamiento($id, $dniRuc, $fechaInicio, $fechaFin) {
        $id = $id ?? 0;
        $sql = "SELECT * FROM locadores 
                WHERE (dni_ruc = ? OR dni = ?) 
                  AND id != ? 
                  AND estado = 'ACTIVO'
                  AND (
                      (fecha_inicio BETWEEN ? AND ?) OR
                      (fecha_fin BETWEEN ? AND ?) OR
                      (fecha_inicio <= ? AND fecha_fin >= ?)
                  ) LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return null;
        
        $stmt->bind_param("ssissssss", $dniRuc, $dniRuc, $id, $fechaInicio, $fechaFin, $fechaInicio, $fechaFin, $fechaInicio, $fechaFin);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_assoc();
    }
    
    public function obtenerEstadisticas() {
        $sql = "SELECT 
                    SUM(CASE WHEN estado = 'ACTIVO' THEN
                        (COALESCE(monto_ene,0) + COALESCE(monto_feb,0) + COALESCE(monto_mar,0) + 
                        COALESCE(monto_abr,0) + COALESCE(monto_may,0) + COALESCE(monto_jun,0) + 
                        COALESCE(monto_jul,0) + COALESCE(monto_ago,0) + COALESCE(monto_set,0) + 
                        COALESCE(monto_oct,0) + COALESCE(monto_nov,0) + COALESCE(monto_dic,0))
                    ELSE 0 END) as total_monto,
                    SUM(CASE WHEN estado = 'ACTIVO' THEN 1 ELSE 0 END) as total_activos,
                    SUM(CASE WHEN estado = 'PENDIENTE' THEN 1 ELSE 0 END) as total_pendientes,
                    SUM(CASE WHEN estado = 'FINALIZADO' THEN 1 ELSE 0 END) as total_finalizados,
                    SUM(CASE WHEN estado = 'ACTIVO' AND fecha_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as por_vencer
                FROM locadores 
                WHERE estado != 'ELIMINADO'";
                
        $res = $this->db->query($sql);
        return $res->fetch_assoc();
    }
    
    public function obtenerEstadisticasGraficos() {
        // Datos para Gráfico 1: Por Unidad
        $sql1 = "SELECT Unidad_asignada as label, COUNT(*) as value 
                 FROM locadores 
                 WHERE estado = 'ACTIVO' 
                 GROUP BY Unidad_asignada 
                 ORDER BY value DESC";
        $res1 = $this->db->query($sql1);
        $por_unidad = $res1->fetch_all(MYSQLI_ASSOC);

        // Datos para Gráfico 2: Gasto por Unidad (Suma de montos mensuales)
        // Nota: Esto es un estimado simplificado multiplicando monto_mensual de activos
        // O idealmente sumando los montos desglosados
        $sql2 = "SELECT Unidad_asignada as label, 
                    SUM(
                        COALESCE(monto_ene,0) + COALESCE(monto_feb,0) + COALESCE(monto_mar,0) + 
                        COALESCE(monto_abr,0) + COALESCE(monto_may,0) + COALESCE(monto_jun,0) + 
                        COALESCE(monto_jul,0) + COALESCE(monto_ago,0) + COALESCE(monto_set,0) + 
                        COALESCE(monto_oct,0) + COALESCE(monto_nov,0) + COALESCE(monto_dic,0)
                    ) as value 
                 FROM locadores 
                 WHERE estado = 'ACTIVO' 
                 GROUP BY Unidad_asignada 
                 ORDER BY value DESC
                 LIMIT 10";
        $res2 = $this->db->query($sql2);
        $gasto_unidad = $res2->fetch_all(MYSQLI_ASSOC);

        return ['por_unidad' => $por_unidad, 'gasto_unidad' => $gasto_unidad];
    }
    
    public function obtenerHistorialPorDNI($dni) {
        $dni = $this->db->real_escape_string($dni);
        // Buscar por DNI o DNI_RUC
        $sql = "SELECT * FROM locadores 
                WHERE (dni = '$dni' OR dni_ruc LIKE '%$dni%') 
                ORDER BY fecha_inicio DESC"; // Historico completo
        $res = $this->db->query($sql);
        return $res->fetch_all(MYSQLI_ASSOC);
    }
    
    public function eliminarTodo() {
         // TRUNCATE es más limpio para resetear IDs, pero requiere permisos.
         // Si falla, usar DELETE
         $this->db->query("SET FOREIGN_KEY_CHECKS = 0");
         $sql = "TRUNCATE TABLE locadores";
         $res = $this->db->query($sql);
         if(!$res) {
             $sql = "DELETE FROM locadores";
             $res = $this->db->query($sql);
             $this->db->query("ALTER TABLE locadores AUTO_INCREMENT = 1");
         }
         $this->db->query("SET FOREIGN_KEY_CHECKS = 1");
         
         if($res) return ['status' => true, 'msg' => 'Base de datos vaciada correctamente.'];
         return ['status' => false, 'msg' => 'Error al vaciar BD: ' . $this->db->error];
    }

    // Nuevo: Obtener Cumpleañeros (Próximos N días)
    public function obtenerCumpleanieros($dias = 0)
    {
        // Calcular fechas límite
        $hoy = date('Y-m-d');
        $limite = date('Y-m-d', strtotime("+$dias days"));

        // Query que calcula el próximo cumpleaños
        $sql = "SELECT 
                    id, 
                    nombres_apellidos as nombres, 
                    nombres_apellidos as apellidos,
                    unidad_asignada as nombre_subunidad, 
                    servicio_descripcion as nombre_grado,
                    fecha_nacimiento,
                    sexo,
                    DATE_ADD(fecha_nacimiento, INTERVAL YEAR(CURDATE())-YEAR(fecha_nacimiento) + IF(DAYOFYEAR(CURDATE()) > DAYOFYEAR(fecha_nacimiento),1,0) YEAR) as proximo_cumpleanos
                FROM locadores 
                WHERE estado = 'ACTIVO' 
                  AND fecha_nacimiento IS NOT NULL 
                  AND fecha_nacimiento > '1900-01-01'
                HAVING proximo_cumpleanos BETWEEN '$hoy' AND '$limite'
                ORDER BY proximo_cumpleanos ASC";

        $res = $this->db->query($sql);
        if($res) {
            return $res->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }

    // Nuevo: Buscar Locador por DNI (Para el login/autobienvenida)
    public function buscarPorDni($dni) {
        // Buscar coincidencia exacta primero
        $dni = $this->db->real_escape_string($dni);
        $sql = "SELECT * FROM locadores WHERE (dni = '$dni' OR dni_ruc = '$dni') AND estado = 'ACTIVO' LIMIT 1";
        $res = $this->db->query($sql);
        if ($res && $res->num_rows > 0) {
            return $res->fetch_assoc();
        }
        
        // Fallback: Buscar like (para RUC que contiene DNI)
        $term = "%" . $dni . "%";
        $sql = "SELECT * FROM locadores WHERE dni_ruc LIKE '$term' AND estado = 'ACTIVO' LIMIT 1";
        $res = $this->db->query($sql);
        if ($res && $res->num_rows > 0) {
            return $res->fetch_assoc();
        }
        
        return null;
    }
}
?>
