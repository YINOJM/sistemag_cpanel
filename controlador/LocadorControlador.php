<?php
// controlador/LocadorControlador.php
require_once __DIR__ . '/../modelo/conexion.php';
require_once __DIR__ . '/../modelo/LocadorModelo.php';
$modelo = new LocadorModelo();
$op = $_GET['op'] ?? '';

switch ($op) {
    case 'listar':
        $mes = $_GET['mes'] ?? null;
        $data = $modelo->listar($mes);
        echo json_encode(['data' => $data]);
        break;

    case 'verificar_cumpleanos':
        // Verificar proximos 3 días por defecto o lo que permita la logica
        // El front pedirá si hay HOY.
        $data = $modelo->obtenerCumpleanieros(5); // 5 días de margen
        echo json_encode($data);
        break;

    case 'guardar':
        $id = isset($_POST['id']) && !empty($_POST['id']) ? (int)$_POST['id'] : null;

        // Manejo de Archivo PDF
        $archivo_pdf = '';
        if ($id) {
            // Si es edición, obtener el archivo actual para no perderlo si no suben uno nuevo
            $actual = $modelo->obtenerPorId($id);
            $archivo_pdf = $actual['archivo_pdf'] ?? '';
        }

        if (isset($_FILES['archivo_pdf']) && $_FILES['archivo_pdf']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['archivo_pdf']['tmp_name'];
            $fileName = $_FILES['archivo_pdf']['name'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));

            $allowedfileExtensions = array('pdf');
            if (in_array($fileExtension, $allowedfileExtensions)) {
                $uploadFileDir = '../uploads/locadores/';
                if (!file_exists($uploadFileDir)) {
                    mkdir($uploadFileDir, 0777, true);
                }
                
                // Nombre único: timestamp_dni_CMN.pdf
                $dni = $_POST['dni_ruc'] ?? 'unknown';
                $newFileName = time() . '_' . $dni . '_CMN.' . $fileExtension;
                $dest_path = $uploadFileDir . $newFileName;

                if(move_uploaded_file($fileTmpPath, $dest_path)) {
                    $archivo_pdf = $newFileName;
                }
            }
        }

        // Manejo de Archivo SIGA
        $archivo_siga = '';
        if ($id) {
            $actual = $modelo->obtenerPorId($id);
            $archivo_siga = $actual['archivo_siga'] ?? ''; // Mantener actual si no se sube nuevo
            if(empty($archivo_pdf)) $archivo_pdf = $actual['archivo_pdf'] ?? ''; // Fix: Ensure pdf is preserved
        }

        if (isset($_FILES['archivo_siga']) && $_FILES['archivo_siga']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['archivo_siga']['tmp_name'];
            $fileName = $_FILES['archivo_siga']['name'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));

            $allowedfileExtensions = array('pdf');
            if (in_array($fileExtension, $allowedfileExtensions)) {
                $uploadFileDir = '../uploads/locadores/';
                if (!file_exists($uploadFileDir)) {
                    mkdir($uploadFileDir, 0777, true);
                }
                
                // Nombre único: timestamp_dni_SIGA.pdf
                $dni = $_POST['dni_ruc'] ?? 'unknown';
                $newFileName = time() . '_' . $dni . '_SIGA.' . $fileExtension;
                $dest_path = $uploadFileDir . $newFileName;

                if(move_uploaded_file($fileTmpPath, $dest_path)) {
                    $archivo_siga = $newFileName;
                }
            }
        }

        $datos = [
            'dni_ruc' => $_POST['dni_ruc'] ?? '',
            'dni' => $_POST['dni'] ?? '',
            'nombres_apellidos' => mb_strtoupper($_POST['nombres_apellidos'] ?? '', 'UTF-8'),
            'sexo' => $_POST['sexo'] ?? 'M',
            'fecha_nacimiento' => !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : null,
            'correo' => strtolower($_POST['correo'] ?? ''),
            'celular' => $_POST['celular'] ?? '',
            'servicio_descripcion' => mb_strtoupper($_POST['servicio_descripcion'] ?? '', 'UTF-8'),
            'monto_mensual' => (float)($_POST['monto_mensual'] ?? 0),
            'retencion_aplicable' => 0, // Ya no se usa retencion por area usuaria
            'fecha_inicio' => $_POST['fecha_inicio'] ?? null,
            'fecha_fin' => $_POST['fecha_fin'] ?? null,
            'unidad_asignada' => mb_strtoupper($_POST['unidad_asignada'] ?? '', 'UTF-8'),
            'archivo_pdf' => $archivo_pdf,
            'archivo_siga' => $archivo_siga,
            'estado' => $_POST['estado'] ?? 'ACTIVO',
            // Nuevos Campos
            'meta' => $_POST['meta'] ?? '',
            'esp_gasto' => $_POST['esp_gasto'] ?? '',
            'num_pedido_siga' => $_POST['num_pedido_siga'] ?? '',
            'num_cmn' => $_POST['num_cmn'] ?? '',
            'codigo_siga' => $_POST['codigo_siga'] ?? '',
            // Meses
            'monto_ene' => (float)($_POST['monto_ene'] ?? 0),
            'monto_feb' => (float)($_POST['monto_feb'] ?? 0),
            'monto_mar' => (float)($_POST['monto_mar'] ?? 0),
            'monto_abr' => (float)($_POST['monto_abr'] ?? 0),
            'monto_may' => (float)($_POST['monto_may'] ?? 0),
            'monto_jun' => (float)($_POST['monto_jun'] ?? 0),
            'monto_jul' => (float)($_POST['monto_jul'] ?? 0),
            'monto_ago' => (float)($_POST['monto_ago'] ?? 0),
            'monto_set' => (float)($_POST['monto_set'] ?? 0),
            'monto_oct' => (float)($_POST['monto_oct'] ?? 0),
            'monto_nov' => (float)($_POST['monto_nov'] ?? 0),
            'monto_dic' => (float)($_POST['monto_dic'] ?? 0),
            // Entregables (Checkboxes: si no viene, es 0)
            'entregable_ene' => isset($_POST['entregable_ene']) ? 1 : 0,
            'entregable_feb' => isset($_POST['entregable_feb']) ? 1 : 0,
            'entregable_mar' => isset($_POST['entregable_mar']) ? 1 : 0,
            'entregable_abr' => isset($_POST['entregable_abr']) ? 1 : 0,
            'entregable_may' => isset($_POST['entregable_may']) ? 1 : 0,
            'entregable_jun' => isset($_POST['entregable_jun']) ? 1 : 0,
            'entregable_jul' => isset($_POST['entregable_jul']) ? 1 : 0,
            'entregable_ago' => isset($_POST['entregable_ago']) ? 1 : 0,
            'entregable_set' => isset($_POST['entregable_set']) ? 1 : 0,
            'entregable_oct' => isset($_POST['entregable_oct']) ? 1 : 0,
            'entregable_nov' => isset($_POST['entregable_nov']) ? 1 : 0,
            'entregable_dic' => isset($_POST['entregable_dic']) ? 1 : 0,
            // Evaluación
            'recontratacion' => $_POST['recontratacion'] ?? 'PENDIENTE'
        ];

        // Validación de Solapamiento de Fechas (Solo si el estado es ACTIVO)
        if (($_POST['estado'] ?? 'ACTIVO') === 'ACTIVO' && !empty($datos['fecha_inicio']) && !empty($datos['fecha_fin']) && !empty($datos['dni_ruc'])) {
            $solapamiento = $modelo->verificarSolapamiento(
                $id,
                $datos['dni_ruc'], 
                $datos['fecha_inicio'], 
                $datos['fecha_fin']
            );

            if ($solapamiento) {
                // Formatear fechas para mostrar
                $f_ini = !empty($solapamiento['fecha_inicio']) ? date("d/m/Y", strtotime($solapamiento['fecha_inicio'])) : 'N/A';
                $f_fin = !empty($solapamiento['fecha_fin']) ? date("d/m/Y", strtotime($solapamiento['fecha_fin'])) : 'N/A';

                // Mensaje HTML estilizado
                $msg = "
                    <div style='text-align: left;'>
                        <p class='text-muted mb-3'>No se puede procesar el registro porque las fechas seleccionadas se superponen con un contrato vigente.</p>
                        
                        <div class='p-3 rounded' style='background-color: #fff3cd; border-left: 5px solid #ffc107; color: #555;'>
                            <h6 style='color: #856404; font-weight: bold; margin-bottom: 8px;'>
                                <i class='fa-solid fa-triangle-exclamation me-2'></i>Conflicto Detectado
                            </h6>
                            <div style='font-size: 0.9rem; margin-bottom: 4px;'>
                                Contrato Vigente <strong>(ID: {$solapamiento['id']})</strong>
                            </div>
                            <div class='d-flex align-items-center mt-2 p-2 bg-white rounded border shadow-sm'>
                                <span class='fw-bold text-dark mx-auto' style='font-size: 1.1rem;'>
                                    {$f_ini} <span class='text-muted mx-2'>➔</span> {$f_fin}
                                </span>
                            </div>
                        </div>
                        <p class='text-center text-muted small mt-3 mb-0'>Por favor, verifique el rango de fechas.</p>
                    </div>
                ";

                echo json_encode([
                    'status' => false, 
                    'msg' => $msg
                ]);
                exit;
            }
        }

        if ($id) {
            echo json_encode($modelo->actualizar($id, $datos));
        } else {
            echo json_encode($modelo->registrar($datos));
        }
        break;

    case 'estadisticas_graficos':
        echo json_encode(['data' => $modelo->obtenerEstadisticasGraficos()]);
        break;
        
    case 'historial_dni':
        $dni = $_POST['dni'] ?? '';
        echo json_encode(['data' => $modelo->obtenerHistorialPorDNI($dni)]);
        break;

    case 'obtener':
        $id = (int)$_POST['id'];
        $data = $modelo->obtenerPorId($id);
        echo json_encode($data);
        break;

    case 'eliminar':
        $id = (int)$_POST['id'];
        echo json_encode($modelo->eliminar($id));
        break;

    case 'eliminar_todo':
        echo json_encode($modelo->eliminarTodo());
        break;

    case 'importar_excel':
        require_once '../vendor/autoload.php';
        
        if (isset($_FILES['archivo_excel']) && $_FILES['archivo_excel']['error'] == 0) {
            $inputFileName = $_FILES['archivo_excel']['tmp_name'];

            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileName);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();
                
                // Empezar desde fila 2 (evitar cabecera)
                $count = 0;
                $errores = 0;
                
                // Mapeo basado en imagen del usuario:
                // A: Nro (Ignorar)
                // B: LOCADOR (Nombres) -> Index 1
                // C: UNIDAD -> Index 2
                // D: META -> Index 3
                // E: ESP. GASTO -> Index 4
                // F: PEDIDO SIGA -> Index 5
                // G: CMN -> Index 6
                // H: PDF CMN -> Index 7 (Texto del nombre del archivo)
                // I: CODIGO SIGA -> Index 8
                // J: DESCRIPCION -> Index 9
                // K-V: ENE-DIC -> Index 10-21
                
                for ($i = 1; $i < count($rows); $i++) {
                    $r = $rows[$i];
                    
                    // Validar si nombre está vacío
                    if(empty($r[1])) continue;

                    $nombres = strtoupper(trim($r[1]));
                    
                    // Datos básicos
                    $unidad = strtoupper(trim($r[2] ?? ''));
                    $meta = trim($r[3] ?? '');
                    $esp = trim($r[4] ?? '');
                    $pedido = trim($r[5] ?? '');
                    $cmn = trim($r[6] ?? '');
                    $pdf_cmn = ''; // Columna eliminada del excel
                    $cod_siga = trim($r[7] ?? ''); // Antes 8
                    $cargo = strtoupper(trim($r[8] ?? '')); // Antes 9
                    
                    // Montos (limpieza rigurosa)
                    $cleanMonto = function($val) {
                        if (is_numeric($val)) return (float)$val;
                        // Remover S/, S., espacios y comas
                        $val = str_replace(array('S/', 'S.', ' ', ','), '', (string)$val);
                        return (float)$val;
                    };

                    $m_ene = $cleanMonto($r[9] ?? 0);
                    $m_feb = $cleanMonto($r[10] ?? 0);
                    $m_mar = $cleanMonto($r[11] ?? 0);
                    $m_abr = $cleanMonto($r[12] ?? 0);
                    $m_may = $cleanMonto($r[13] ?? 0);
                    $m_jun = $cleanMonto($r[14] ?? 0);
                    $m_jul = $cleanMonto($r[15] ?? 0);
                    $m_ago = $cleanMonto($r[16] ?? 0);
                    $m_set = $cleanMonto($r[17] ?? 0);
                    $m_oct = $cleanMonto($r[18] ?? 0);
                    $m_nov = $cleanMonto($r[19] ?? 0);
                    $m_dic = $cleanMonto($r[20] ?? 0);

                    // Calcular monto, inicio y fin basados en distribución mensual
                    $montos = [$m_ene, $m_feb, $m_mar, $m_abr, $m_may, $m_jun, $m_jul, $m_ago, $m_set, $m_oct, $m_nov, $m_dic];
                    $monto_ref = 0;
                    $idx_first = -1;
                    $idx_last = -1;

                    // Tolerancia para cero flota
                    $epsilon = 0.001;

                    foreach($montos as $idx => $m) { 
                        if($m > $epsilon) { 
                            if($monto_ref == 0) $monto_ref = $m; 
                            if($idx_first === -1) $idx_first = $idx;
                            $idx_last = $idx;
                        } 
                    }
                    
                    $anio = date('Y');
                    if($idx_first !== -1) {
                        $mes_ini = $idx_first + 1;
                        $mes_fin = $idx_last + 1;
                        $f_inicio = sprintf("%s-%02d-01", $anio, $mes_ini);
                        $f_fin = date("Y-m-t", strtotime(sprintf("%s-%02d-01", $anio, $mes_fin)));
                    } else {
                        $f_inicio = "$anio-01-01";
                        $f_fin = "$anio-12-31";
                    }

                    // Armar array para Modelo
                    $datos = [
                        'dni_ruc' => trim($r[0] ?? ''), // Intentar leer columna A (Nro/DNI)
                        'nombres_apellidos' => $nombres,
                        'correo' => '',
                        'celular' => '',
                        'servicio_descripcion' => $cargo,
                        'monto_mensual' => $monto_ref,
                        'retencion_aplicable' => 0,
                        'fecha_inicio' => $f_inicio,
                        'fecha_fin' => $f_fin,
                        'unidad_asignada' => $unidad,
                        'archivo_pdf' => $pdf_cmn,
                        'archivo_siga' => '',
                        'estado' => 'ACTIVO',
                        'meta' => $meta,
                        'esp_gasto' => $esp,
                        'num_pedido_siga' => $pedido,
                        'num_cmn' => $cmn,
                        'codigo_siga' => $cod_siga,
                        'monto_ene' => $m_ene, 'monto_feb' => $m_feb, 'monto_mar' => $m_mar, 'monto_abr' => $m_abr,
                        'monto_may' => $m_may, 'monto_jun' => $m_jun, 'monto_jul' => $m_jul, 'monto_ago' => $m_ago,
                        'monto_set' => $m_set, 'monto_oct' => $m_oct, 'monto_nov' => $m_nov, 'monto_dic' => $m_dic
                    ];

                    // Verificar si existe para actualizar o insertar
                    // Aquí simplificamos a registrar siempre, o podríamos buscar por nombre...
                    // Para evitar duplicados, idealmente buscaríamos por nombre:
                    // $existente = $modelo->buscarPorNombre($nombres); ...
                    // Por ahora, inserción directa como pide "importar".
                    
                    $res = $modelo->registrar($datos);
                    if($res['status']) $count++; else $errores++;
                }

                echo json_encode(['status' => true, 'msg' => "Proceso terminado. Registrados: $count. Errores: $errores"]);

            } catch (Exception $e) {
                echo json_encode(['status' => false, 'msg' => 'Error al leer Excel: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['status' => false, 'msg' => 'No se recibió ningún archivo válido.']);
        }
        break;

    case 'estadisticas':
        echo json_encode($modelo->obtenerEstadisticas());
        break;



    default:
        echo json_encode(['status' => false, 'msg' => 'Operación no válida']);
        break;
}
?>
