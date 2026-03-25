<?php
// vista/demandas_listado.php
declare(strict_types=1);

// 1. Cargar conexión y sesión (Centralizado)
require_once __DIR__ . '/../modelo/conexion.php';

if (empty($_SESSION['id'])) {
    header("Location: " . (defined('BASE_URL') ? BASE_URL : '/sistem_job/') . "vista/login/login.php");
    exit();
}

// CONTROL DE ACCESO (Permitido para todos visualizar, restringido crear/editar/eliminar)
// Se eliminó la restricción de VER a pedido del usuario

include 'layout/topbar.php';
include 'layout/sidebar.php';
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<!-- CSS adicional -->
<style>
    .table-container { 
        background: #fff; padding: 20px; border-radius: 10px; 
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); 
    }
    
    #tablaDemandas {
        font-family: 'Poppins', sans-serif !important;
        border-collapse: separate !important; border-spacing: 0 !important;
        border: 1px solid #e2e8f0 !important;
    }

    #tablaDemandas thead th {
        font-size: 0.72rem !important; text-transform: uppercase !important;
        font-weight: 700 !important; letter-spacing: 0.8px !important;
        padding: 4px 10px !important; background-color: #004c8c !important; 
        color: #ffffff !important; border-right: 1px solid rgba(255,255,255,0.1) !important;
        border-bottom: 2px solid #003366 !important; vertical-align: middle !important;
        text-align: center;
    }

    #tablaDemandas td {
        padding: 6px 12px !important; vertical-align: middle;
        font-size: 0.78rem !important; color: #475569 !important;
        border-right: 1px solid #e2e8f0 !important; border-bottom: 1px solid #e2e8f0 !important;
    }

    .btn-acc-pro {
        width: 28px !important; height: 28px !important; padding: 0 !important;
        display: inline-flex !important; align-items: center; justify-content: center;
        border-radius: 6px !important; border: none !important; transition: all 0.2s ease;
        font-size: 11px !important; color: white !important;
    }
    .btn-acc-pro:hover { transform: translateY(-1px); filter: brightness(1.1); }
    .btn-acc-view { background-color: #38bdf8 !important; }
    .btn-acc-edit { background-color: #818cf8 !important; }
    .btn-acc-del { background-color: #fb7185 !important; }

/* --- BOTONES DE EXPORTACIÓN RECTANGULARES PRO --- */
.btn-export-pro {
    display: inline-flex !important; 
    align-items: center; 
    justify-content: center;
    height: 34px !important; /* Altura firme */
    width: auto !important;   /* Ancho automático según texto */
    padding: 0 20px !important; /* Lo hace alargado */
    border-radius: 6px !important; /* Forma rectangular sólida */
    border: none !important; 
    transition: all 0.3s ease !important; 
    box-shadow: 0 2px 5px rgba(0,0,0,0.15) !important;
    color: white !important; 
    text-decoration: none !important;
    font-weight: 700 !important;
    font-size: 11px !important;
    gap: 10px; /* Separación icono-texto */
    text-transform: uppercase;
}

.btn-export-excel { background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important; }
.btn-export-pdf   { background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%) !important; }

.btn-export-pro:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 5px 12px rgba(0,0,0,0.2) !important;
    filter: brightness(1.1) !important;
}

    .btn-export-pro i { font-size: 1rem !important; }

    .text-clamp-2 {
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
        overflow: hidden; line-height: 1.4; white-space: normal;
        text-transform: uppercase !important;
        letter-spacing: 0.2px;
    }
    .pe-cursor { cursor: pointer; }

    /* TOOLTIP PREMIUM EXTRA ANCHO */
    .tooltip-inner {
        background-color: #dcedff !important; color: #004d99 !important;
        border: 1px solid #3399ff !important; font-size: 12px;
        padding: 12px 18px; border-radius: 10px; text-align: left;
        max-width: 650px !important;
        text-transform: uppercase !important;
        font-weight: 500; line-height: 1.5;
    }
    .tooltip.bs-tooltip-top .tooltip-arrow::before { border-top-color: #3399ff !important; }
    
    /* SELECTOR DE AÑO NIVEL PRO */
#filtroAnio {
    border: 2px solid #00607a !important;
    background-color: #f8fafc !important;
    color: #004c8c !important;
    font-weight: 700 !important;
    text-transform: uppercase;
    transition: all 0.3s ease;
    padding-left: 30px !important;
    background-image: url('data:image/svg+xml;charset=UTF-8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="%2300607a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>') !important;
    background-repeat: no-repeat !important;
    background-position: left 8px center !important;
    background-size: 14px !important;
}

#filtroAnio:focus {
    box-shadow: 0 0 0 0.25rem rgba(0, 96, 122, 0.25) !important;
    border-color: #003366 !important;
    transform: scale(1.02);
}

</style>



<div class="page-content" style="padding: 20px; padding-top: 80px;">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i> Demandas Presupuestales</h2>
                    <p class="text-muted mb-0">Gestión y seguimiento de requerimientos presupuestales - UE009 VII DIRTEPOL LIMA.</p>
                </div>
<div class="d-flex align-items-center gap-2">
    
    <!-- SELECTOR DE AÑO FISCAL -->
<div class="me-2">
    <select id="filtroAnio" class="form-select form-select-sm fw-bold shadow-sm" 
            style="height: 34px; min-width: 155px; border-radius: 8px; font-size: 11px; cursor: pointer; border: 2px solid #00607a !important; color: #004c8c !important; padding-left: 30px !important; background-image: url('data:image/svg+xml;charset=UTF-8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%2300607a%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><rect x=%223%22 y=%224%22 width=%2218%22 height=%2218%22 rx=%222%22 ry=%222%22></rect><line x1=%2216%22 y1=%222%22 x2=%2216%22 y2=%226%22></line><line x1=%228%22 y1=%222%22 x2=%228%22 y2=%226%22></line><line x1=%223%22 y1=%2210%22 x2=%2221%22 y2=%2210%22></line></svg>') !important; background-repeat: no-repeat !important; background-position: left 8px center !important; background-size: 14px !important;">
        <option value="todos">MOSTRAR TODO</option>
        <!-- Los años 2025 y 2026 aparecerán aquí automáticamente -->
    </select>
</div>


    <!-- EXCEL RECTANGULAR -->
  <a href="../controlador/reporte_demandas_excel.php" id="btnExcel" class="btn-export-pro btn-export-excel">
    <i class="fa-solid fa-file-excel"></i> DESCARGAR EXCEL
</a>
    <!-- PDF RECTANGULAR -->
  <a href="../controlador/reporte_demandas_pdf.php" target="_blank" id="btnPdf" class="btn-export-pro btn-export-pdf">
    <i class="fa-solid fa-file-pdf"></i> DESCARGAR PDF
</a>
    <!-- NUEVA DEMANDA (Sincronizado con misma altura) -->
    <?php if ($_SESSION['rol'] === 'Super Administrador' || isset($_SESSION['permisos']['DEMANDAS_PRESUPUESTALES']['CREAR'])): ?>
    <a href="./demandas_nueva.php" class="btn shadow-sm fw-bold px-4" 
       style="background-color: #004c8c; border: none; color: white; border-radius: 6px; font-size: 11px; height: 34px; display: inline-flex; align-items: center; text-transform: uppercase;">
        <i class="fas fa-plus-circle me-2"></i> NUEVA DEMANDA
    </a>
    <?php endif; ?>
</div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="table-container">
                  <table id="tablaDemandas" class="table table-bordered table-striped table-hover align-middle w-100">

               <thead class="table-dark">
    <tr>
        <th style="width: 50px;"class="text-center">Nº</th>
        <th>Descripción General</th>
        <th style="width: 140px;">CUI</th>
        <th style="width: 150px;">Nº Exp.</th>
        <th style="width: 120px;">Monto Total</th>
        <th style="width: 130px;">Fecha Registro</th>
        <th style="width: 100px;">Estado</th>
        <th style="width: 110px;">Acciones</th>
    </tr>
</thead>

                        <tbody>
                            <!-- Llenado por Ajax -->
                        </tbody>
                        <tfoot class="border-white">
                            <tr>
                                <th colspan="4" class="text-end fw-bold align-middle border-0"></th>
                                <th colspan="4" class="text-start border-0 py-3">
                                    <div class="d-inline-flex justify-content-between align-items-center bg-white shadow-sm px-4 py-2" style="border: 2px solid #198754; border-radius: 8px; min-width: 380px;">
                                        <h5 class="mb-0 fw-bold text-success text-uppercase pe-4 text-nowrap" style="letter-spacing: 1px;"><i class="fas fa-coins me-2"></i> TOTAL ACUMULADO</h5>
                                        <h4 class="mb-0 fw-bold text-success text-nowrap" id="totalAcumulado">S/ 0.00</h4>
                                    </div>
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ver Detalle -->
<div class="modal fade" id="modalVerDetalle" tabindex="-1" aria-labelledby="modalVerDetalleLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title" id="modalVerDetalleLabel"><i class="fas fa-search me-2"></i> Detalle de Demanda Presupuestal</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="detalleContenido">
        <!-- Renderizado dinámico -->
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Cargando detalles...</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- DataTables JS y librerías -->
<!-- Solo cargamos lo necesario para la tabla -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>


<script>
    var tabla;
    $(document).ready(function() {
        // --- 1. CARGAMOS LOS AÑOS AUTOMÁTICAMENTE ANTES DE INICIAR ---
        $.get('../controlador/DemandasControlador.php?op=obtener_anios', function(data) {
            let anios = JSON.parse(data);
            let currentYear = new Date().getFullYear();
            
            anios.forEach(anio => {
                let selected = (anio == currentYear) ? 'selected' : '';
                $('#filtroAnio').append(`<option value="${anio}" ${selected}>AÑO FISCAL ${anio}</option>`);
            });
            
            // Inicializar tabla por primera vez con el año seleccionado (ej. 2026)
            let anioInicial = $('#filtroAnio').val();
            inicializarTabla('../controlador/DemandasControlador.php?op=listar&anio=' + anioInicial);
        });

        // --- 2. EVENTO: CUANDO CAMBIAS EL AÑO EN EL SELECTOR ---
        $('#filtroAnio').on('change', function() {
            let anio = $(this).val();
            let nuevaUrl = '../controlador/DemandasControlador.php?op=listar&anio=' + anio;
            
            // Recargamos los datos de la tabla con el nuevo año
            tabla.ajax.url(nuevaUrl).load();
            
            // Sincronizamos los links de descarga (Excel y PDF)
            $('#btnExcel').attr('href', '../controlador/reporte_demandas_excel.php?anio=' + anio);
            $('#btnPdf').attr('href', '../controlador/reporte_demandas_pdf.php?anio=' + anio);
        });
    });

    // --- FUNCIÓN QUE CONTIENE TODA TU LÓGICA ORIGINAL (DataTable) ---
    function inicializarTabla(urlAjax) {
        tabla = $('#tablaDemandas').DataTable({
            "ajax": {
                url: urlAjax,
                type: "GET",
                dataType: "json",
                error: function(e){ console.log(e.responseText); }
            },
            "language": { "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
            "order": [[ 0, "desc" ]], 

            // --- SE MANTIENE TU LÓGICA DE NUMERACIÓN (1, 2, 3...) ---
            "columnDefs": [{
                "targets": 0,
                "className": 'text-center',
                "render": function (data, type, row, meta) { return meta.row + 1; }
            }],

            // --- SE MANTIENE TUS TOOLTIPS PREMIUM ---
            "drawCallback": function(settings) {
                if (typeof bootstrap !== 'undefined') {
                    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
                    tooltipTriggerList.map(function (tooltipTriggerEl) { return new bootstrap.Tooltip(tooltipTriggerEl); });
                }
            },

            // --- SE MANTIENE TU TOTAL ACUMULADO ---
            "footerCallback": function (row, data, start, end, display) {
                var api = this.api();
                var floatVal = function (i) { return typeof i === 'string' ? i.replace(/[\$,\,]/g, '') * 1 : typeof i === 'number' ? i : 0; };
                var total = api.column(4, { search: 'applied' }).data().reduce(function (a, b) { return floatVal(a) + floatVal(b); }, 0);
                $('#totalAcumulado').html('S/ ' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            }
        });
    }


    function verDetalle(id_demanda) {
        $('#modalVerDetalle').modal('show');
        $('#detalleContenido').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Cargando detalles...</p></div>');
        
        $.ajax({
            url: '../controlador/DemandasControlador.php?op=ver_detalle',
            type: 'POST',
            data: { id_demanda: id_demanda },
            dataType: 'json',
            success: function(res) {
                if(res.status) {
                    let d = res.demanda;
                    let num = parseFloat(d.total_presupuesto);
                    let formatTotal = num.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    let html = `
                        <div class="row border-bottom pb-2 mb-4">
                            <div class="col-auto pe-4">
                                <h6 class="text-muted fw-bold mb-1">CUI</h6>
                                <p class="mb-0 fw-bold text-dark fs-5">${d.cui || '-'}</p>
                            </div>
                            <div class="col-auto pe-4">
                                <h6 class="text-muted fw-bold mb-1">Nº Expediente</h6>
                                <p class="mb-0 fw-bold text-dark fs-5">${d.nro_expediente || '-'}</p>
                            </div>
                            <div class="col">
                                <h6 class="text-muted fw-bold mb-1">Descripción General</h6>
                            <p class="mb-0 text-dark text-uppercase" style="font-size: 0.95rem;">${d.descripcion_general}</p>
                            </div>
                            <div class="col-auto text-end ps-4">
                                <h6 class="text-muted fw-bold mb-1">Registrado por</h6>
                                <p class="mb-0 text-primary fw-bold"><i class="fas fa-user-check me-1"></i> ${d.nombre ? d.nombre + ' ' + d.apellido : 'Desconocido'}</p>
                                <small class="text-muted"><i class="far fa-calendar-alt me-1"></i> ${new Date(d.fecha_registro).toLocaleString('es-PE')}</small>
                            </div>
                        </div>
                        <h5 class="text-center bg-light border border-secondary p-2 fw-bold text-secondary shadow-sm mb-4">CUANTÍA INICIAL DE LA CONTRATACIÓN</h5>
                    `;
                    
                    let items = res.items;
                    items.forEach(function(item) {
                        html += `
                        <div class="card mb-3 shadow-sm border-info" style="border-radius: 8px; border-left: 5px solid #0dcaf0 !important;">
                            <div class="card-header bg-white pb-0 border-0 pt-3">
                                <div class="d-flex align-items-start mb-2">
                                    <span class="badge bg-info text-dark px-3 py-2 me-3 shadow-sm flex-shrink-0" style="font-size: 0.95rem; border-radius: 6px; border: 1px solid #99dcf8;">ÍTEM N° ${item.nro_item}</span> 
                               <span class="fw-medium text-dark text-uppercase" style="font-size: 0.97rem; line-height: 1.4; padding-top: 3px;">${item.descripcion_item}</span>
                                </div>
                            </div>
                            <div class="card-body pt-2 pb-3">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm table-hover text-center align-middle mb-0">
                                        <thead class="bg-light text-secondary" style="font-size: 0.85rem;">
                                            <tr>
                                                <th style="width: 45%;">DESCRIPCIÓN DE LA PRESTACIÓN</th>
                                                <th style="width: 15%;">UNIDAD DE MEDIDA</th>
                                                <th style="width: 10%;">CANTIDAD</th>
                                                <th style="width: 15%;">PRECIO UNITARIO (S/)</th>
                                                <th style="width: 15%;">PRECIO TOTAL (S/)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                        `;
                        
                        let totalItem = 0;
                        item.prestaciones.forEach(function(p) {
                            totalItem += parseFloat(p.precio_total);
                            html += `
                                            <tr>
                                                <td class="text-start ps-3 text-secondary" style="font-size: 0.95rem;">${p.descripcion_prestacion}</td>
                                                <td><span class="badge bg-light text-dark border border-secondary shadow-sm fw-normal">${p.unidad_medida}</span></td>
                                                <td class="text-dark">${parseFloat(p.cantidad)}</td>
                                                <td class="text-end pe-3 text-secondary">${parseFloat(p.precio_unitario).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits:2})}</td>
                                                <td class="text-end pe-3 fw-semibold text-dark bg-light">${parseFloat(p.precio_total).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits:2})}</td>
                                            </tr>
                            `;
                        });
                        
                        html += `
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        `;
                    });
                    
                    html += `
                        <div class="card mt-4 shadow" style="border: 2px solid #198754; border-radius: 10px;">
                            <div class="card-body d-flex justify-content-between align-items-center bg-white" style="border-radius: 8px;">
                                <h4 class="mb-0 fw-bold text-success text-uppercase ps-2" style="letter-spacing: 1px;">
                                    <i class="fas fa-coins me-2"></i> TOTAL INC. IGV
                                </h4>
                                <h2 class="mb-0 fw-bold text-success pe-2" style="font-size: 2rem;">S/ ${formatTotal}</h2>
                            </div>
                        </div>
                    `;
                    $('#detalleContenido').html(html);
                } else {
                    $('#detalleContenido').html('<div class="alert alert-danger">'+res.msg+'</div>');
                }
            },
            error: function() {
                $('#detalleContenido').html('<div class="alert alert-danger">Ocurrió un error al cargar los datos.</div>');
            }
        });
    }

    function editarDemanda(id_demanda) {
        window.location.href = 'demandas_editar.php?id=' + id_demanda;
    }

    function eliminarDemanda(id_demanda) {
        Swal.fire({
            title: '¿Está completamente seguro?',
            text: "Se eliminará la Demanda Presupuestal, incluyendo todos sus Ítems y Prestaciones. ¡Esta acción es irreversible y no se puede deshacer!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar Demanda!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../controlador/DemandasControlador.php?op=eliminar',
                    type: 'POST',
                    data: { id_demanda: id_demanda },
                    dataType: 'json',
                    success: function(res) {
                        if(res.status) {
                            Swal.fire('¡Eliminado!', res.msg, 'success');
                            tabla.ajax.reload();
                        } else {
                            Swal.fire('Error', res.msg, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Ocurrió un error de red al intentar eliminar la demanda.', 'error');
                    }
                });
            }
        });
    }
</script>

<?php include 'layout/footer.php'; ?>
