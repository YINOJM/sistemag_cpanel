<?php
/**
 * Importador de Unidades Policiales desde Excel
 * Sistema Integrado de Gestión - UE009 DIRTEPOL LIMA
 */

// Suprimir warnings deprecated de PHP 8.2+
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

session_start();

if (empty($_SESSION['nombre']) || ($_SESSION['rol'] !== 'Administrador' && $_SESSION['rol'] !== 'Super Administrador')) {
    header('Location: ../vista/inicio.php');
    exit;
}

require_once __DIR__ . '/../modelo/conexion.php';
require_once __DIR__ . '/../modelo/UnidadesPoliciales.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$mensaje = '';
$tipo = '';
$detalles = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_excel'])) {
    try {
        $archivo = $_FILES['archivo_excel'];

        // Validar archivo
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Error al subir el archivo');
        }

        $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($extension), ['xlsx', 'xls'])) {
            throw new Exception('Solo se permiten archivos Excel (.xlsx, .xls)');
        }

        // Cargar el archivo
        $spreadsheet = IOFactory::load($archivo['tmp_name']);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        $modelo = new UnidadesPoliciales($conexion);

        $regiones = [];
        $divisiones = [];
        $insertados = 0;
        $errores = 0;
        $omitidos = 0;

        // Detectar si la primera fila es encabezado
        $primeraFila = $rows[0] ?? [];
        $tieneEncabezado = false;

        // Verificar si contiene palabras típicas de encabezado
        foreach ($primeraFila as $celda) {
            $celda = strtolower(trim($celda));
            if (in_array($celda, ['departamento', 'provincia', 'distrito', 'regpol', 'divpol', 'comisaria', 'comisaría', 'tipo'])) {
                $tieneEncabezado = true;
                break;
            }
        }

        $inicioFila = $tieneEncabezado ? 1 : 0;

        // Procesar filas
        // Estructura esperada: DEPARTAMENTO | PROVINCIA | DISTRITO | REGPOL | DIVPOL/DIVOPUS | COMISARÍA | TIPO
        for ($i = $inicioFila; $i < count($rows); $i++) {
            $row = $rows[$i];

            // Limpiar y obtener valores según la estructura del Excel
            // Columna 0: DEPARTAMENTO
            // Columna 1: PROVINCIA
            // Columna 2: DISTRITO
            // Columna 3: REGPOL (Región Policial)
            // Columna 4: DIVPOL/DIVOPUS (División Policial)
            // Columna 5: COMISARÍA (Nombre de la sub-unidad)
            // Columna 6: TIPO (Tipo de unidad)

            $departamento = isset($row[0]) ? trim($row[0]) : '';
            $provincia = isset($row[1]) ? trim($row[1]) : '';
            $distrito = isset($row[2]) ? trim($row[2]) : '';
            $nombreRegion = isset($row[3]) ? trim($row[3]) : '';
            $nombreDivision = isset($row[4]) ? trim($row[4]) : '';
            $nombreSubUnidad = isset($row[5]) ? trim($row[5]) : '';
            $tipoUnidad = isset($row[6]) ? trim($row[6]) : '';

            // Debug: Mostrar las primeras 3 filas para verificar mapeo
            if ($i < ($inicioFila + 3)) {
                $detalles[] = "🔍 DEBUG Fila " . ($i + 1) . ": Depto='$departamento' | Prov='$provincia' | Dist='$distrito' | Reg='$nombreRegion' | Div='$nombreDivision' | SubU='$nombreSubUnidad' | Tipo='$tipoUnidad'";
            }

            // Normalizar tipo solo si viene en el Excel
            if (!empty($tipoUnidad)) {
                $tipoUnidad = strtoupper($tipoUnidad);
                if ($tipoUnidad === 'COMISARIA') {
                    $tipoUnidad = 'COMISARÍA';
                }
            }
            // Si está vacío, se mantiene vacío (no se detecta automáticamente)

            // Validar que tenga al menos región, división y sub-unidad
            if (empty($nombreRegion) || empty($nombreDivision) || empty($nombreSubUnidad)) {
                // Si todas están vacías, es una fila vacía, omitir sin contar como error
                if (empty($nombreRegion) && empty($nombreDivision) && empty($nombreSubUnidad)) {
                    $omitidos++;
                    continue;
                }

                $errores++;
                $detalles[] = "❌ Fila " . ($i + 1) . ": Datos incompletos (Región: '$nombreRegion', División: '$nombreDivision', Sub-Unidad: '$nombreSubUnidad')";
                continue;
            }

            // Normalizar nombres (mayúsculas)
            $nombreRegion = strtoupper($nombreRegion);
            $nombreDivision = strtoupper($nombreDivision);
            $nombreSubUnidad = strtoupper($nombreSubUnidad);

            try {
                // Obtener o crear región
                if (!isset($regiones[$nombreRegion])) {
                    $stmt = $conexion->prepare("SELECT id_region FROM regiones_policiales WHERE nombre_region = ?");
                    $stmt->bind_param("s", $nombreRegion);
                    $stmt->execute();
                    $resultRegion = $stmt->get_result();

                    if ($resultRegion && $resultRegion->num_rows > 0) {
                        $regiones[$nombreRegion] = $resultRegion->fetch_assoc()['id_region'];
                    } else {
                        // Crear nueva región
                        $datosRegion = [
                            'nombre_region' => $nombreRegion,
                            'codigo_region' => '',
                            'descripcion' => '',
                            'usuario_id' => $_SESSION['id']
                        ];

                        if ($modelo->crearRegion($datosRegion)) {
                            $regiones[$nombreRegion] = $conexion->insert_id;
                            $detalles[] = "🆕 Nueva región creada: $nombreRegion";
                        } else {
                            throw new Exception("Error al crear región: $nombreRegion");
                        }
                    }
                }

                $idRegion = $regiones[$nombreRegion];

                // Obtener o crear división
                $claveDivision = $nombreRegion . '|' . $nombreDivision;
                if (!isset($divisiones[$claveDivision])) {
                    $stmt = $conexion->prepare("SELECT id_division FROM divisiones_policiales WHERE nombre_division = ? AND id_region = ?");
                    $stmt->bind_param("si", $nombreDivision, $idRegion);
                    $stmt->execute();
                    $resultDivision = $stmt->get_result();

                    if ($resultDivision && $resultDivision->num_rows > 0) {
                        $divisiones[$claveDivision] = $resultDivision->fetch_assoc()['id_division'];
                    } else {
                        // Crear nueva división
                        $datosDivision = [
                            'id_region' => $idRegion,
                            'nombre_division' => $nombreDivision,
                            'codigo_division' => '',
                            'descripcion' => '',
                            'direccion' => '',
                            'telefono' => '',
                            'email' => '',
                            'usuario_id' => $_SESSION['id']
                        ];

                        if ($modelo->crearDivision($datosDivision)) {
                            $divisiones[$claveDivision] = $conexion->insert_id;
                            $detalles[] = "🆕 Nueva división creada: $nombreDivision";
                        } else {
                            throw new Exception("Error al crear división: $nombreDivision");
                        }
                    }
                }

                $idDivision = $divisiones[$claveDivision];

                // Verificar si la sub-unidad ya existe
                $stmt = $conexion->prepare("SELECT id_subunidad FROM sub_unidades_policiales WHERE nombre_subunidad = ? AND id_division = ?");
                $stmt->bind_param("si", $nombreSubUnidad, $idDivision);
                $stmt->execute();
                $resultSubUnidad = $stmt->get_result();

                if ($resultSubUnidad && $resultSubUnidad->num_rows > 0) {
                    $omitidos++;
                    $detalles[] = "⏭️ Fila " . ($i + 1) . ": Ya existe - $nombreSubUnidad";
                    continue;
                }

                // Crear sub-unidad
                $datosSubUnidad = [
                    'id_division' => $idDivision,
                    'nombre_subunidad' => $nombreSubUnidad,
                    'tipo_unidad' => $tipoUnidad,
                    'codigo_subunidad' => '',
                    'descripcion' => '',
                    'direccion' => '',
                    'telefono' => '',
                    'email' => '',
                    'responsable' => '',
                    'departamento' => strtoupper($departamento),
                    'provincia' => strtoupper($provincia),
                    'distrito' => strtoupper($distrito),
                    'usuario_id' => $_SESSION['id']
                ];

                if ($modelo->crearSubUnidad($datosSubUnidad)) {
                    $insertados++;
                    $detalles[] = "✅ Fila " . ($i + 1) . ": $nombreSubUnidad ($tipoUnidad)";
                } else {
                    throw new Exception("Error al crear sub-unidad");
                }

            } catch (Exception $e) {
                $errores++;
                $detalles[] = "❌ Fila " . ($i + 1) . ": " . $e->getMessage();
            }
        }

        $totalProcesadas = $insertados + $errores + $omitidos;
        $mensaje = "Importación completada. Total procesadas: $totalProcesadas | Insertados: $insertados | Omitidos (duplicados): $omitidos | Errores: $errores";
        $tipo = $errores > 0 ? 'warning' : 'success';

    } catch (Exception $e) {
        $mensaje = 'Error: ' . $e->getMessage();
        $tipo = 'danger';
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Unidades Policiales - SIG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        .page-content {
            padding: 40px;
            padding-top: 100px;
        }

        .import-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 0 auto;
        }

        .import-header {
            background: linear-gradient(135deg, #006db3 0%, #00a8cc 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }

        .upload-zone {
            border: 3px dashed #006db3;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }

        .upload-zone:hover {
            background: #e9ecef;
            border-color: #00a8cc;
        }

        .upload-zone i {
            font-size: 64px;
            color: #006db3;
            margin-bottom: 20px;
        }

        .detalles-box {
            max-height: 400px;
            overflow-y: auto;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .detalles-box p {
            margin: 5px 0;
            font-size: 13px;
        }
    </style>
</head>

<body>
    <?php require('./layout/topbar.php'); ?>
    <?php require('./layout/sidebar.php'); ?>

    <div class="page-content">
        <div class="container-fluid">
            <div class="import-card">
                <div class="import-header">
                    <div class="d-flex align-items-center justify-content-center mb-3">
                        <img src="../public/images/escudo.png" alt="Escudo PNP"
                            style="height: 60px; margin-right: 15px; filter: drop-shadow(0 2px 8px rgba(0,0,0,0.3));">
                        <div class="text-start">
                            <h2 class="mb-1"><i class="fas fa-file-excel me-2"></i>Importar Unidades Policiales desde
                                Excel</h2>
                            <p class="mb-0">Carga tu archivo Excel con la estructura de unidades policiales</p>
                        </div>
                    </div>
                </div>

                <?php if (false): // Deshabilitado - ahora usamos SweetAlert2 ?>
                    <div class="alert alert-<?= $tipo ?> alert-dismissible fade show" role="alert">
                        <strong>
                            <?= $mensaje ?>
                        </strong>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="upload-zone">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <h4>Selecciona tu archivo Excel</h4>
                        <p class="text-muted">Formatos permitidos: .xlsx, .xls</p>
                        <input type="file" name="archivo_excel" class="form-control mt-3" accept=".xlsx,.xls" required>
                    </div>

                    <div class="text-center mb-3">
                        <a href="../controlador/descargar_plantilla_unidades.php" class="btn btn-outline-primary">
                            <i class="fas fa-download me-2"></i>Descargar Plantilla Excel
                        </a>
                        <p class="text-muted mt-2 mb-0" style="font-size: 13px;">
                            <i class="fas fa-lightbulb me-1"></i>Descarga la plantilla con los encabezados correctos y
                            ejemplos
                        </p>
                    </div>

                    <div class="alert alert-info mt-4">
                        <h6><i class="fas fa-info-circle me-2"></i>Estructura del archivo Excel:</h6>
                        <ul class="mb-0">
                            <li><strong>Columna A:</strong> Departamento (Ej: LIMA)</li>
                            <li><strong>Columna B:</strong> Provincia (Ej: LIMA)</li>
                            <li><strong>Columna C:</strong> Distrito (Ej: PUENTE PIEDRA)</li>
                            <li><strong>Columna D:</strong> REGPOL - Región Policial (Ej: REGPOL LIMA)</li>
                            <li><strong>Columna E:</strong> DIVPOL/DIVOPUS - División Policial (Ej: DIVPOL NORTE 1)</li>
                            <li><strong>Columna F:</strong> UNIDAD POLICIAL - Nombre de la Sub-Unidad (Ej: COMISARÍA
                                PUENTE
                                PIEDRA, DEPINCRI LIMA, JEFATURA DE PROTECCIÓN)</li>
                            <li><strong>Columna G:</strong> TIPO - Tipo de Unidad (Ej: COMISARÍA, JEFATURA) - Opcional
                            </li>
                        </ul>
                    </div>

                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-upload me-2"></i>Importar Datos
                        </button>
                        <a href="unidades_policiales.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Volver al Módulo
                        </a>
                    </div>
                </form>

                <?php if (!empty($detalles)): ?>
                    <div class="detalles-box">
                        <h6><i class="fas fa-list me-2"></i>Detalles de la Importación:</h6>
                        <?php foreach ($detalles as $detalle): ?>
                            <p>
                                <?= htmlspecialchars($detalle) ?>
                            </p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php require('./layout/footer.php'); ?>

    <?php if ($mensaje): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                <?php if ($tipo === 'success' || $tipo === 'warning'): ?>
                    const totalProcesadas = <?= $insertados + $errores + $omitidos ?>;
                    const insertados = <?= $insertados ?>;
                    const omitidos = <?= $omitidos ?>;
                    const errores = <?= $errores ?>;

                    const icon = errores > 0 ? 'warning' : 'success';
                    const title = errores > 0 ? '⚠️ Importación Completada con Advertencias' : '🎉 ¡Importación Exitosa!';

                    Swal.fire({
                        title: title,
                        html: `
                        <div style="text-align: left; padding: 20px;">
                            <h5 style="color: #006db3; margin-bottom: 15px;">📊 Resumen de Importación:</h5>
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 10px; font-weight: 600;">Total Procesadas:</td>
                                    <td style="padding: 10px; text-align: right; font-size: 18px; color: #2c3e50;">${totalProcesadas}</td>
                                </tr>
                                <tr style="border-bottom: 1px solid #eee; background: #e8f5e9;">
                                    <td style="padding: 10px; font-weight: 600;">✅ Insertadas:</td>
                                    <td style="padding: 10px; text-align: right; font-size: 18px; color: #28a745;">${insertados}</td>
                                </tr>
                                <tr style="border-bottom: 1px solid #eee; background: #fff3cd;">
                                    <td style="padding: 10px; font-weight: 600;">⏭️ Omitidas (Duplicados):</td>
                                    <td style="padding: 10px; text-align: right; font-size: 18px; color: #ffc107;">${omitidos}</td>
                                </tr>
                                <tr style="background: ${errores > 0 ? '#f8d7da' : '#f8f9fa'};">
                                    <td style="padding: 10px; font-weight: 600;">❌ Errores:</td>
                                    <td style="padding: 10px; text-align: right; font-size: 18px; color: ${errores > 0 ? '#dc3545' : '#6c757d'};">${errores}</td>
                                </tr>
                            </table>
                        </div>
                    `,
                        icon: icon,
                        confirmButtonText: 'Ver Módulo',
                        confirmButtonColor: '#006db3',
                        showCancelButton: true,
                        cancelButtonText: 'Cerrar',
                        width: '600px'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'unidades_policiales.php';
                        }
                    });
                <?php else: ?>
                    Swal.fire({
                        title: '❌ Error en la Importación',
                        text: '<?= addslashes($mensaje) ?>',
                        icon: 'error',
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#dc3545'
                    });
                <?php endif; ?>
            });
        </script>
    <?php endif; ?>
</body>

</html>