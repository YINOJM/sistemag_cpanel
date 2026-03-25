<?php
// vista/demandas_nueva.php
declare(strict_types=1);

// 1. Forzamos la carga de la conexión (asegura sesiones y rutas en cPanel)
require_once __DIR__ . '/../modelo/conexion.php';

// 2. Verificación de sesión
if (empty($_SESSION['id'])) {
    header("Location: login/login.php"); // Ruta corregida para estar dentro de vista/
    exit();
}

// 3. Control de Acceso Robusto (Evita fallos por comparación estricta)
$rolActual = trim((string)$_SESSION['rol']);
$tienePermiso = isset($_SESSION['permisos']['DEMANDAS_PRESUPUESTALES']['CREAR']);

if ($rolActual !== 'Super Administrador' && !$tienePermiso) {
    header("Location: inicio.php");
    exit();
}


include 'layout/topbar.php';
include 'layout/sidebar.php';
?>

<div class="page-content" style="padding: 10px; padding-top: 70px;">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-xl-12"> <!-- Un poco más ancho para albergar los ítems -->
                <div class="card shadow-lg border-0 rounded-lg">
                    <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #004c8c; color: white;">
                        <h4 class="mb-0 fw-bold"><i class="fas fa-file-invoice-dollar me-2"></i>Nueva Demanda Presupuestal</h4>
                        <a href="demandas_listado.php" class="btn btn-outline-light btn-sm fw-bold px-3">
                            <i class="fa-solid fa-arrow-left me-1"></i> Volver a Listado
                        </a>
                    </div>
                    
                    <div class="card-body bg-light">
                        <form id="formNuevaDemanda" autocomplete="off">
                            
                            <!-- CABECERA -->
                            <div class="card mb-2 border-info shadow-sm">
                                <div class="card-header bg-info text-white py-2">
                                    <h5 class="mb-0"><i class="fas fa-info-circle me-1"></i> Datos Generales</h5>
                                </div>
                                <div class="card-body py-2">
                                    <div class="row g-3">
                                        <div class="col-md-2">
                                            <label class="form-label fw-bold small text-muted">CUI</label>
                                            <input type="text" class="form-control border-info" name="cui" id="cui" placeholder="Ej: 2690596" maxlength="15">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label fw-bold small text-muted">Nº Expediente</label>
                                            <input type="text" class="form-control border-info" name="nro_expediente" id="nro_expediente" placeholder="Ej: 001-2023" maxlength="20">
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label fw-bold small text-muted">Descripción Central del Proyecto (Cabecera) <span class="text-danger">*</span></label>
                                            <textarea class="form-control border-info" name="descripcion_general" id="descripcion_general" rows="2" required placeholder="Ej: ADQUISICIÓN DE CAMIONETAS PICK UP 4X4..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- ÍTEMS Y PRESTACIONES -->
                            <div class="d-flex justify-content-between align-items-center mb-2 mt-2 border-bottom pb-1">
                                <h5 class="text-primary mb-0"><i class="fas fa-cubes me-1"></i> Ítems y Prestaciones de la Cuantía</h5>
                                <button type="button" class="btn btn-primary shadow-sm" id="btnAgregarItem">
                                    <i class="fas fa-plus-circle me-1"></i> Añadir Nuevo ÍTEM
                                </button>
                            </div>
                            
                            <div id="contenedorItems">
                                <!-- Los Items dinámicos van aquí -->
                            </div>
                            
                            <!-- TOTAL GLOBAL -->
                            <div class="card mt-2 border-success shadow">
                                <div class="card-body bg-white d-flex justify-content-between align-items-center p-2 px-4">
                                    <h5 class="mb-0 fw-bold text-success">CUANTÍA TOTAL DE LA CONTRATACIÓN INC. IGV</h5>
                                    <h3 class="mb-0 fw-bold text-primary" id="visualTotalGlobal">S/ 0.00</h3>
                                </div>
                            </div>
                            
                            <!-- BOTÓN GUARDAR -->
                            <div class="row mt-2 mb-2">
                                <div class="col-md-12 text-center">
                                    <button type="submit" class="btn btn-success shadow px-5" id="btnGuardar">
                                        <i class="fa-solid fa-save me-2"></i> Confirmar y Guardar Demanda Presupuestal
                                    </button>
                                </div>
                            </div>
                            
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    let itemCount = 0;

    $(document).ready(function() {
        
        // Agregar el primer Item base automáticamente por comodidad
        agregarNuevoItem();

        // Boton Añadir Nuevo ITEM
        $('#btnAgregarItem').click(function() {
            agregarNuevoItem();
        });

        // Boton Añadir Prestación a un ITEM específico
        $(document).on('click', '.btnAñadirPrestacion', function() {
            let itemID = $(this).data('item-id');
            agregarPrestacion(itemID);
        });

        // Eliminar Item completo
        $(document).on('click', '.btnEliminarItem', function() {
            let itemCard = $(this).closest('.item-card');
            Swal.fire({
                title: '¿Eliminar ÍTEM?',
                text: "Se borrará este ítem completo junto a todas sus prestaciones de la pantalla. ¿Deseas continuar?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, quitar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    itemCard.remove();
                    recalcularNumeracionItems();
                    calcularTotalDelSistema();
                }
            });
        });

        // Eliminar fila de prestación
        $(document).on('click', '.btnEliminarPrestacion', function() {
            let tbody = $(this).closest('tbody');
            $(this).closest('tr').remove();
            calcularTotalDelSistema();
        });

        // Al escribir cant/precio se calcula la fila y luego todo
        $(document).on('input', '.calc-input', function() {
            let tr = $(this).closest('tr');
            let cant = parseFloat(tr.find('.input-cant').val()) || 0;
            let precio = parseFloat(tr.find('.input-precio').val()) || 0;
            
            let subtotal = cant * precio;
            tr.find('.span-subtotal').text(subtotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits:2}));
            tr.find('.input-subtotal').val(subtotal.toFixed(2));
            
            calcularTotalDelSistema();
        });

        // Guardar por AJAX (JSON en lugar de form-data normal por la complejidad)
        $('#formNuevaDemanda').on('submit', function(e) {
            e.preventDefault();

            if($('.item-card').length === 0) {
                Swal.fire('Atención', 'Debe agregar al menos un ÍTEM a la demanda.', 'warning');
                return;
            }
            
            let validarCampos = true;
            let demandasData = {};
            
            demandasData.cui = $('#cui').val();
            demandasData.nro_expediente = $('#nro_expediente').val();
            demandasData.descripcion_general = $('#descripcion_general').val();
            demandasData.total_presupuesto = parseFloat(localStorage.getItem('total_global_demanda') || 0);
            
            demandasData.items = [];

            $('.item-card').each(function() {
                let thisItemCard = $(this);
                let descItem = thisItemCard.find('.desc-item').val();
                
                if(!descItem) { validarCampos = false; }
                
                let prestacionesArr = [];
                
                thisItemCard.find('.fila-prestacion').each(function() {
                    let desc = $(this).find('.input-desc-prest').val();
                    let unidad = $(this).find('.input-unidad').val();
                    let cant = $(this).find('.input-cant').val();
                    let precio = $(this).find('.input-precio').val();
                    let sub = $(this).find('.input-subtotal').val();
                    
                    if(!desc || !unidad || cant <= 0 || precio < 0) { validarCampos = false; }
                    
                    prestacionesArr.push({
                        descripcion_prestacion: desc,
                        unidad_medida: unidad,
                        cantidad: cant,
                        precio_unitario: precio,
                        precio_total: sub
                    });
                });
                
                if(prestacionesArr.length === 0) { validarCampos = false; }
                
                demandasData.items.push({
                    descripcion_item: descItem,
                    prestaciones: prestacionesArr
                });
            });

            if(!validarCampos) {
                Swal.fire('Campos Incompletos', 'Verifique que todos los Ítems y Prestaciones tengan descripción, y que las cantidades/precios no estén vacíos ni en cero.', 'error');
                return;
            }

            const btn = $('#btnGuardar');
            btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i> Guardando...');

            $.ajax({
                url: '../controlador/DemandasControlador.php?op=guardar',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(demandasData),
                dataType: 'json',
                success: function(res) {
                    btn.prop('disabled', false).html('<i class="fa-solid fa-save me-2"></i> Confirmar y Guardar Demanda Presupuestal');
                    if(res.status) {
                        Swal.fire({ title: '¡Perfecto!', text: res.msg, icon: 'success' }).then(() => {
                            window.location.href = 'demandas_listado.php';
                        });
                    } else {
                        Swal.fire('Error', res.msg, 'error');
                    }
                }
            });
        });
    });

    //================ FUNCIONES ==================

    function agregarNuevoItem() {
        itemCount++;
        let uniqueID = Date.now() + Math.floor(Math.random() * 100); // Para relacionar tbody
        
        let htmlItem = `
        <div class="card mb-3 item-card shadow-sm border-secondary" id="tarjetaItem_${uniqueID}">
            <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center py-2">
                <h6 class="mb-0 fw-bold"><i class="fas fa-box-open me-1"></i> ÍTEM N° <span class="lbl-nro-item">${itemCount}</span></h6>
                <button type="button" class="btn btn-sm btn-outline-light btnEliminarItem"><i class="fas fa-trash-alt"></i> Quitar Ítem</button>
            </div>
            <div class="card-body pt-2 pb-0">
                
                <div class="mb-2">
                    <label class="form-label small fw-bold text-muted">Descripción Central del Ítem <span class="text-danger">*</span></label>
                    <input type="text" class="form-control desc-item shadow-sm" placeholder="Ej: ADQUISICIÓN DE CAMIONETAS (Desglose Principal)">
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle mb-2">
                        <thead class="bg-light text-muted text-center small">
                            <tr>
                                <th style="width: 40%;">DESCRIPCIÓN DE PRESTACIÓN</th>
                                <th style="width: 15%;">UNIDAD DE MEDIDA</th>
                                <th style="width: 10%;">CANTIDAD</th>
                                <th style="width: 15%;">PRECIO U. (S/)</th>
                                <th style="width: 15%;">PRECIO TOT. (S/)</th>
                                <th style="width: 5%;"></th>
                            </tr>
                        </thead>
                        <tbody id="tbody_${uniqueID}">
                            <!-- Prestaciones de este item -->
                        </tbody>
                    </table>
                </div>
                
                <div class="text-end mb-2">
                    <button type="button" class="btn btn-sm btn-success btnAñadirPrestacion" data-item-id="${uniqueID}">
                        <i class="fas fa-plus"></i> <i class="fas fa-list"></i> Agregar Sub-Prestación a este ÍTEM
                    </button>
                </div>
            </div>
        </div>
        `;
        
        $('#contenedorItems').append(htmlItem);
        // Automáticamente le añadimos su primera fila de prestación "Principal"
        agregarPrestacion(uniqueID);
        recalcularNumeracionItems();
    }

    function agregarPrestacion(itemID) {
        let filaHtml = `
            <tr class="fila-prestacion text-center">
                <td class="p-1">
                    <textarea class="form-control form-control-sm input-desc-prest shadow-sm" rows="1" placeholder="Ej: PRESTACIÓN PRINCIPAL / MANTENIMIENTO PREVENTIVO"></textarea>
                </td>
                <td class="p-1">
                    <select class="form-select form-select-sm shadow-sm input-unidad">
                        <option value="UNIDAD">UNIDAD</option>
                        <option value="GLOBAL">GLOBAL</option>
                        <option value="SERVICIO">SERVICIO</option>
                        <option value="MES">MES</option>
                        <option value="GLB">GLB</option>
                        <option value="OTROS">OTROS</option>
                    </select>
                </td>
                <td class="p-1">
                    <input type="number" class="form-control form-control-sm text-center calc-input input-cant shadow-sm" min="1" step="1" placeholder="0">
                </td>
                <td class="p-1">
                    <input type="number" class="form-control form-control-sm text-end calc-input input-precio shadow-sm" min="0" step="0.01" placeholder="0.00">
                </td>
                <td class="p-1 text-end align-middle fw-bold text-primary pe-3">
                    <span class="span-subtotal">0.00</span>
                    <input type="hidden" class="input-subtotal" value="0.00">
                </td>
                <td class="p-1 text-center align-middle">
                    <button type="button" class="btn btn-sm btn-outline-danger btnEliminarPrestacion"><i class="fas fa-times"></i></button>
                </td>
            </tr>
        `;
        $('#tbody_' + itemID).append(filaHtml);
    }

    function recalcularNumeracionItems() {
        let objItems = $('.lbl-nro-item');
        itemCount = objItems.length;
        objItems.each(function(index) {
            $(this).text(index + 1);
        });
    }

    function calcularTotalDelSistema() {
        let sum = 0;
        $('.input-subtotal').each(function() {
            sum += parseFloat($(this).val()) || 0;
        });
        
        // Guardar para AJAX
        localStorage.setItem('total_global_demanda', sum.toFixed(2));
        
        // Mostrar
        $('#visualTotalGlobal').text('S/ ' + sum.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits:2}));
    }

</script>

<?php include 'layout/footer.php'; ?>
