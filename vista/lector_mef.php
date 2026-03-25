<?php
// vista/lector_mef.php
require_once __DIR__ . "/../modelo/conexion.php";

if (empty($_SESSION['id'])) {
    header("Location: " . (defined('BASE_URL') ? BASE_URL : '/sistem_job/') . "vista/login/login.php");
    exit();
}

require_once __DIR__ . '/layout/topbar.php';
require_once __DIR__ . '/layout/sidebar.php';
?>

<!-- Estilos específicos para esta vista -->
<style>
    .header-box {
        background: linear-gradient(135deg, #006db3 0%, #00a8cc 100%);
        color: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 30px;
        text-align: center;
    }
    .upload-area {
        border: 2px dashed #cbd5e0;
        padding: 40px;
        text-align: center;
        border-radius: 10px;
        background: #f8fafc;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    .upload-area:hover {
        border-color: #006db3;
        background: #edf7fc;
    }
    .table-responsive {
        margin-top: 30px;
    }
    th {
        background-color: #f1f5f9 !important;
        font-weight: 600;
        font-size: 0.9rem;
        white-space: nowrap;
    }
    td {
        font-size: 0.85rem;
        vertical-align: middle;
    }
    /* Estilos personalizados para el buscador DataTables */
    .dataTables_filter input {
        width: 400px !important; /* Más largo */
        display: inline-block;
        margin-left: 0.5em;
        padding: 8px 15px;
        border-radius: 20px;
        border: 1px solid #ccc;
        transition: all 0.3s;
    }
    .dataTables_filter input:focus {
        width: 450px !important; /* Crece al enfocar (Sensible al toque) */
        border-color: #006db3;
        box-shadow: 0 0 0 0.2rem rgba(0, 109, 179, 0.25);
        outline: none;
    }
    .dataTables_filter label {
        font-weight: bold;
        color: #495057;
    }
    /* Separar botones de exportación */
    .dt-buttons .btn {
        margin-right: 10px !important;
        border-radius: 6px !important;
    }
    
    /* Footer Corporativo: Azul Corporativo Estándar (Flat) */
    .site-footer {
        background: #0087a3 !important; /* Un poco más claro para coincidir con el Teal del sistema */
        border-top: none !important;
        padding-top: 10px !important;
    }
    .site-footer small, .site-footer strong, .site-footer {
        color: #ffffff !important;
    }
</style>

<script>
    // Parche JS para forzar actualización de estilo (Anti-Caché)
    document.addEventListener("DOMContentLoaded", function() {
        var footer = document.querySelector('.site-footer');
        if(footer) {
            footer.style.backgroundColor = '#0087a3'; // Teal Corporativo
            footer.style.color = '#ffffff';
        }
    });
</script>

<!-- Contenido Principal -->
<div class="page-content">

    <div class="header-box">
        <h2 class="m-0"><i class="fa-solid fa-file-csv me-2"></i>Visor de Reportes</h2>
        <p class="m-0 mt-2 opacity-75">
            Suba sus archivos .CSV (SIAF, Bancos, etc.) para visualizar, buscar y exportar datos a Excel o PDF.<br>
            Compatible con cualquier formato delimitado por comas (,) o punto y coma (;).
        </p>
    </div>

    <?php if (!isset($_FILES['csvFile'])): ?>
    <div class="row justify-content-center">
        <div class="col-md-8">
            <form action="" method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="upload-area" onclick="document.getElementById('csvFile').click()">
                    <i class="fa-solid fa-cloud-arrow-up fa-3x text-primary mb-3"></i>
                    <h4>Seleccionar Archivo CSV</h4>
                    <p class="text-muted">Haga clic aquí para buscar el reporte descargado</p>
                    <input type="file" name="csvFile" id="csvFile" class="d-none" accept=".csv,.txt" onchange="document.getElementById('uploadForm').submit()">
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csvFile'])) {
        $file = $_FILES['csvFile'];        
        // Header Row: Botón Limpiar (Izquierda) + Botones Exportar (Derecha)
        echo '<div class="d-flex justify-content-between align-items-center mb-4">';
        echo '  <a href="lector_mef.php" class="btn btn-outline-secondary"><i class="fa-solid fa-rotate-left me-2"></i>Subir Nuevo Archivo / Limpiar</a>';
        echo '  <div id="buttonsContainer"></div>'; // Aquí se moverán los botones
        echo '</div>';

        if ($file['error'] === UPLOAD_ERR_OK) {
            $tmpPath = $file['tmp_name'];
            $filename = $file['name'];
            
            // Detectar codificación para evitar caracteres raros (tildes, ñ)
            $content = file_get_contents($tmpPath);
            
            // Intentar detectar UTF-8 vs ISO-8859-1 (Latin1)
            $encoding = mb_detect_encoding($content, 'UTF-8, ISO-8859-1', true);
            if ($encoding !== 'UTF-8') {
                $content = mb_convert_encoding($content, 'UTF-8', $encoding);
            }

            // Guardar temporalmente con codificación corregida para fgetcsv
            $tempUtf8 = tempnam(sys_get_temp_dir(), 'CSV_UTF8');
            file_put_contents($tempUtf8, $content);

            echo '<div id="successAlert" class="alert alert-success mt-4"><i class="fa-solid fa-check-circle me-2"></i>Archivo <strong>' . htmlspecialchars($filename) . '</strong> cargado correctamente.</div>';
            
            // Título por defecto: Nombre del archivo sin extensión
            $defaultTitle = pathinfo($filename, PATHINFO_FILENAME);
            $defaultTitle = str_replace(['_', '-'], ' ', $defaultTitle); // Limpiar un poco
            
            echo '<div class="row mb-3 align-items-end">';
            echo '  <div class="col-md-5">';
            echo '    <label class="form-label fw-bold">Título para el Reporte (Excel/PDF):</label>';
            echo '    <input type="text" id="customTitle" class="form-control" value="' . htmlspecialchars($defaultTitle) . '">';
            echo '  </div>';
            echo '  <div class="col-md-7 d-flex justify-content-end align-items-center" id="searchContainer">';
            echo '    <!-- El buscador se moverá aquí vía JS -->';
            echo '  </div>';
            echo '</div>';

            // Loader
            echo '<div id="tableLoader" class="text-center py-5"><i class="fa-solid fa-spinner fa-spin fa-3x text-primary"></i><h5 class="mt-3 text-muted">Procesando datos...</h5></div>';
            
            // Contenedor principal de la tabla (oculto inicialmente)
            echo '<div id="tableContainer" class="table-responsive" style="display:none">';
            echo '<table id="mefTable" class="table table-hover table-bordered table-striped" style="width:100%">';
            
            if (($handle = fopen($tempUtf8, "r")) !== FALSE) {
                // Detectar delimitador (Coma o Punto y Coma) en la primera línea
                $firstLine = fgets(fopen($tempUtf8, 'r'));
                $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
                
                // Reiniciar puntero
                rewind($handle);

                // Variable para almacenar el número de columnas de la cabecera
                $headerColumnCount = 0;

                // Cabecera
                if (($data = fgetcsv($handle, 10000, $delimiter)) !== FALSE) {
                    // Contar columnas de la cabecera
                    $headerColumnCount = count($data);
                    
                    echo '<thead><tr class="table-primary">';
                    foreach ($data as $cell) {
                        // Limpiar BOM
                        $cell = preg_replace('/[\x00-\x1F\x7F]/u', '', $cell);
                        echo '<th>' . htmlspecialchars($cell) . '</th>';
                    }
                    echo '</tr></thead>';
                    echo '<tbody>';
                }

                // Cuerpo
                while (($data = fgetcsv($handle, 10000, $delimiter)) !== FALSE) {
                    // Saltar filas completamente vacías
                    if (count($data) == 1 && empty($data[0])) continue;

                    // Asegurar que la fila tenga el mismo número de columnas que la cabecera
                    $currentColumnCount = count($data);
                    
                    // Si la fila tiene menos columnas, agregar celdas vacías
                    if ($currentColumnCount < $headerColumnCount) {
                        $data = array_pad($data, $headerColumnCount, '');
                    }
                    
                    // Si la fila tiene más columnas, truncar (esto es raro pero puede pasar)
                    if ($currentColumnCount > $headerColumnCount) {
                        $data = array_slice($data, 0, $headerColumnCount);
                    }

                    echo '<tr>';
                    foreach ($data as $cell) {
                        echo '<td>' . htmlspecialchars($cell) . '</td>';
                    }
                    echo '</tr>';
                }
                echo '</tbody>';
                fclose($handle);
            }
            echo '</table>';
            echo '</div>'; // table-responsive
            
            unlink($tempUtf8); // Borrar temporal

        } else {
            echo '<div class="alert alert-danger mt-4">Error al subir el archivo. Código: ' . $file['error'] . '</div>';
        }
    }
    ?>

</div>

<!-- Footer -->
<?php require('./layout/footer.php'); ?>

<!-- DataTables JS & Buttons (Cargamos explícitamente porque no todos los módulos lo tienen) -->
<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
    // Evitar el mensaje de "Confirmar reenvío del formulario" al refrescar (F5)
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }

    $(document).ready(function() {
        // Auto-eliminar la alerta de éxito después de 5 segundos
        setTimeout(function() {
            $('#successAlert').fadeOut('slow');
        }, 3000); 

        // Función para capturar el título dinámico
        var getReportTitle = function() {
            return $('#customTitle').val() || 'Reporte Procesado';
        };

        var table = $('#mefTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json',
                search: "🔍 Buscar en todo el reporte:",
                searchPlaceholder: "Escriba datos a buscar..."
            },
            search: {
                smart: true,
                return: false, // Búsqueda sensible
            },
            dom: 'frtip', // Quitamos 'B' de aquí para ponerlo manualmente
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: '<i class="fa-solid fa-file-excel me-1"></i> Exportar a Excel',
                    className: 'btn btn-success btn-sm',
                    title: getReportTitle, 
                    filename: getReportTitle 
                },
                {
                    extend: 'pdfHtml5',
                    text: '<i class="fa-solid fa-file-pdf me-1"></i> Exportar a PDF',
                    className: 'btn btn-danger btn-sm',
                    orientation: 'landscape', 
                    pageSize: 'A0', 
                    pageMargins: [20, 20, 20, 20],
                    title: getReportTitle, 
                    filename: getReportTitle,
                    customize: function (doc) {
                        doc.defaultStyle.fontSize = 12; 
                        doc.styles.tableHeader.fontSize = 13;
                        doc.content[1].table.widths = Array(doc.content[1].table.body[0].length + 1).join('*').split('');
                    }
                },
                {
                    extend: 'print',
                    text: '<i class="fa-solid fa-print me-1"></i> Imprimir',
                    className: 'btn btn-secondary btn-sm',
                    title: getReportTitle
                }
            ],
            scrollX: true,
            pageLength: 10, 
            initComplete: function(settings, json) {
                $('#tableLoader').remove();
                $('#tableContainer').fadeIn(); // Mostrar contenedor completo (inc. cabeceras)
                this.api().columns.adjust().draw();
                
                // Mover los botones al contenedor superior
                table.buttons().container().appendTo( '#buttonsContainer' );

                // Mover el buscador al contenedor dedicado (misma fila que título)
                $('#mefTable_filter').appendTo('#searchContainer');
            }
        });
    });
</script>
