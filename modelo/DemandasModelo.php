<?php
// modelo/DemandasModelo.php
require_once "conexion.php";

class DemandasModelo {
    
    // --- NUEVO: Listar demandas con filtro de año opcional ---
    public static function listar($anio = null) {
        global $conn;
        
        $where = "";
        // Si el año es un número válido y no es la palabra 'todos'
        if ($anio && $anio !== 'todos' && !empty($anio)) {
            $anio = (int)$anio; // Limpiamos el dato por seguridad
            $where = " WHERE YEAR(d.fecha_registro) = $anio ";
        }
        $sql = "SELECT d.*, u.nombre, u.apellido 
                FROM demandas_presupuestales d
                LEFT JOIN usuario u ON d.id_usuario = u.id_usuario
                $where
                ORDER BY d.id_demanda DESC";
                
        $resultado = $conn->query($sql);
        $demandas = [];
        if ($resultado && $resultado->num_rows > 0) {
            while ($fila = $resultado->fetch_assoc()) {
                $demandas[] = $fila;
            }
        }
        return $demandas;
    }
    // --- NUEVO: Obtener la lista de años registrados para el filtro ---
    public static function obtenerAnios() {
        global $conn;
        $sql = "SELECT DISTINCT YEAR(fecha_registro) as anio 
                FROM demandas_presupuestales 
                ORDER BY anio DESC";
        $res = $conn->query($sql);
        $anios = [];
        if ($res) {
            while($f = $res->fetch_assoc()) { 
                if($f['anio']) $anios[] = (int)$f['anio']; 
            }
        }
        
        // --- FORZAR AÑOS QUE DEBEN APARECER ---
        if(!in_array(2025, $anios)) $anios[] = 2025;
        if(!in_array(2026, $anios)) $anios[] = 2026;
        
        sort($anios);
        return array_reverse($anios); // Orden de más reciente a más antiguo
    }


    // Guardar Demanda (3 niveles)
    public static function guardarDemanda($datosCabecera, $datosItems) {
        global $conn;
        
        $conn->begin_transaction();
        
        try {
            // 1. Guardar Cabecera
            $stmtC = $conn->prepare("INSERT INTO demandas_presupuestales (cui, nro_expediente, descripcion_general, id_unidad, total_presupuesto, estado, id_usuario) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmtC->bind_param("sssidsi", 
                $datosCabecera['cui'],
                $datosCabecera['nro_expediente'],
                $datosCabecera['descripcion_general'],
                $datosCabecera['id_unidad'],
                $datosCabecera['total_presupuesto'],
                $datosCabecera['estado'],
                $datosCabecera['id_usuario']
            );
            
            if (!$stmtC->execute()) {
                throw new Exception("Error al guardar la cabecera: " . $stmtC->error);
            }
            
            $id_demanda = $conn->insert_id;
            
            // 2. Guardar Items y Prestaciones (Preparar una vez)
            $stmtI = $conn->prepare("INSERT INTO demandas_items (id_demanda, nro_item, descripcion_item) VALUES (?, ?, ?)");
            $stmtP = $conn->prepare("INSERT INTO demandas_prestaciones (id_item, descripcion_prestacion, unidad_medida, cantidad, precio_unitario, precio_total) VALUES (?, ?, ?, ?, ?, ?)");
                
            foreach ($datosItems as $item) {
                
                // Guardar Item
                $stmtI->bind_param("iis", 
                    $id_demanda,
                    $item['nro_item'],
                    $item['descripcion_item']
                );
                
                if (!$stmtI->execute()) {
                    throw new Exception("Error al guardar Ítem N° ".$item['nro_item'].": " . $stmtI->error);
                }
                
                $id_item = $conn->insert_id;
                
                // Guardar Prestaciones hijas
                if (!empty($item['prestaciones'])) {
                    foreach ($item['prestaciones'] as $p) {
                        $stmtP->bind_param("isssdd", 
                            $id_item,
                            $p['descripcion'],
                            $p['unidad_medida'],
                            $p['cantidad'],
                            $p['precio_unitario'],
                            $p['precio_total']
                        );
                        
                        if (!$stmtP->execute()) {
                            throw new Exception("Error al guardar prestación de Ítem N° ".$item['nro_item'].": " . $stmtP->error);
                        }
                    }
                }
            }
            
            $conn->commit();
            return ["status" => true, "msg" => "Demanda Presupuestal estructurada guardada correctamente."];
            
        } catch (Exception $e) {
            $conn->rollback();
            return ["status" => false, "msg" => $e->getMessage()];
        }
    }
    
    // Obtener cabecera
    public static function obtenerDemanda($id) {
        global $conn;
        $stmt = $conn->prepare("SELECT d.*, u.nombre, u.apellido 
                                FROM demandas_presupuestales d 
                                LEFT JOIN usuario u ON d.id_usuario = u.id_usuario 
                                WHERE d.id_demanda = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        return $resultado->fetch_assoc();
    }
    
    // Obtener Ítems con sus Prestaciones
    public static function obtenerItemsConPrestaciones($id_demanda) {
        global $conn;
        
        // Obtener Items
        $stmtI = $conn->prepare("SELECT * FROM demandas_items WHERE id_demanda = ? ORDER BY nro_item ASC");
        $stmtI->bind_param("i", $id_demanda);
        $stmtI->execute();
        $resItems = $stmtI->get_result();
        
        $items = [];
        
        // Preparar sentencia de prestaciones
        $stmtP = $conn->prepare("SELECT * FROM demandas_prestaciones WHERE id_item = ? ORDER BY id_prestacion ASC");
        
        while ($item = $resItems->fetch_assoc()) {
            
            $stmtP->bind_param("i", $item['id_item']);
            $stmtP->execute();
            $resPrestaciones = $stmtP->get_result();
            
            $prestaciones = [];
            while ($p = $resPrestaciones->fetch_assoc()) {
                $prestaciones[] = $p;
            }
            
            $item['prestaciones'] = $prestaciones;
            $items[] = $item;
        }
        return $items;
    }

    // Eliminar Demanda
    public static function eliminarDemanda($id_demanda) {
        global $conn;
        
        $conn->begin_transaction();
        
        try {
            // 1. Eliminar Prestaciones
            // Primero obtenemos los id de los items
            $stmtI = $conn->prepare("SELECT id_item FROM demandas_items WHERE id_demanda = ?");
            if ($stmtI) {
                $stmtI->bind_param("i", $id_demanda);
                $stmtI->execute();
                $resItems = $stmtI->get_result();
                
                $stmtP = $conn->prepare("DELETE FROM demandas_prestaciones WHERE id_item = ?");
                while ($item = $resItems->fetch_assoc()) {
                    $stmtP->bind_param("i", $item['id_item']);
                    $stmtP->execute();
                }
            }
            
            // 2. Eliminar Items
            $stmtDelItems = $conn->prepare("DELETE FROM demandas_items WHERE id_demanda = ?");
            if ($stmtDelItems) {
                $stmtDelItems->bind_param("i", $id_demanda);
                $stmtDelItems->execute();
            }
            
            // 3. Eliminar Cabecera
            $stmtCabecera = $conn->prepare("DELETE FROM demandas_presupuestales WHERE id_demanda = ?");
            if ($stmtCabecera) {
                $stmtCabecera->bind_param("i", $id_demanda);
                if (!$stmtCabecera->execute()) {
                    throw new Exception("Error al eliminar la demanda principal.");
                }
            }
            
            $conn->commit();
            return ["status" => true, "msg" => "Demanda eliminada correctamente."];
            
        } catch (Exception $e) {
            $conn->rollback();
            return ["status" => false, "msg" => $e->getMessage()];
        }
    }

    // Actualizar Demanda
    public static function actualizarDemanda($id_demanda, $datosCabecera, $datosItems) {
        global $conn;
        
        $conn->begin_transaction();
        
        try {
            // 1. Actualizar Cabecera
            $stmtC = $conn->prepare("UPDATE demandas_presupuestales SET cui = ?, nro_expediente = ?, descripcion_general = ?, total_presupuesto = ?, estado = ? WHERE id_demanda = ?");
            $stmtC->bind_param("sssdsi", 
                $datosCabecera['cui'],
                $datosCabecera['nro_expediente'],
                $datosCabecera['descripcion_general'],
                $datosCabecera['total_presupuesto'],
                $datosCabecera['estado'],
                $id_demanda
            );
            
            if (!$stmtC->execute()) {
                throw new Exception("Error al actualizar la cabecera: " . $stmtC->error);
            }
            
            // 2. Eliminar Prestaciones y luego Items antiguos (para reemplazarlos por los nuevos)
            $stmtI = $conn->prepare("SELECT id_item FROM demandas_items WHERE id_demanda = ?");
            if ($stmtI) {
                $stmtI->bind_param("i", $id_demanda);
                $stmtI->execute();
                $resItems = $stmtI->get_result();
                
                $stmtDelP = $conn->prepare("DELETE FROM demandas_prestaciones WHERE id_item = ?");
                while ($item = $resItems->fetch_assoc()) {
                    $stmtDelP->bind_param("i", $item['id_item']);
                    $stmtDelP->execute();
                }
            }
            
            $stmtDelItems = $conn->prepare("DELETE FROM demandas_items WHERE id_demanda = ?");
            if ($stmtDelItems) {
                $stmtDelItems->bind_param("i", $id_demanda);
                $stmtDelItems->execute();
            }
            
            // 3. Insertar Items y Prestaciones Nuevas
            $stmtInI = $conn->prepare("INSERT INTO demandas_items (id_demanda, nro_item, descripcion_item) VALUES (?, ?, ?)");
            $stmtInP = $conn->prepare("INSERT INTO demandas_prestaciones (id_item, descripcion_prestacion, unidad_medida, cantidad, precio_unitario, precio_total) VALUES (?, ?, ?, ?, ?, ?)");
                
            foreach ($datosItems as $item) {
                // Guardar Item
                $stmtInI->bind_param("iis", $id_demanda, $item['nro_item'], $item['descripcion_item']);
                if (!$stmtInI->execute()) {
                    throw new Exception("Error al re-guardar Ítem N° ".$item['nro_item'].": " . $stmtInI->error);
                }
                
                $id_item = $conn->insert_id;
                
                // Guardar Prestaciones hijas
                if (!empty($item['prestaciones'])) {
                    foreach ($item['prestaciones'] as $p) {
                        $stmtInP->bind_param("isssdd", 
                            $id_item,
                            $p['descripcion'],
                            $p['unidad_medida'],
                            $p['cantidad'],
                            $p['precio_unitario'],
                            $p['precio_total']
                        );
                        if (!$stmtInP->execute()) {
                            throw new Exception("Error al re-guardar prestación de Ítem N° ".$item['nro_item'].": " . $stmtInP->error);
                        }
                    }
                }
            }
            
            $conn->commit();
            return ["status" => true, "msg" => "Demanda Presupuestal actualizada correctamente."];
            
        } catch (Exception $e) {
            $conn->rollback();
            return ["status" => false, "msg" => "Error interno: " . $e->getMessage()];
        }
    }
}
