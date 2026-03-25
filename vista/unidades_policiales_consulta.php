<?php
session_start();
if (empty($_SESSION['nombre']) and empty($_SESSION['apellido'])) {
    header('location:../login/login.php');
}
?>
<!-- Primero se carga el topbar -->
<?php require('./layout/topbar.php'); ?>
<!-- Luego se carga el sidebar -->
<?php require('./layout/sidebar.php'); ?>

<!-- Inicio del contenido principal -->
<div class="page-content">

    <h4 class="text-center text-secondary">
        CONSULTA DE ESTRUCTURA POLICIAL
    </h4>

    <div class="container-fluid mt-4">
        
        <!-- Sección de Control y Búsqueda -->
        <div class="row mb-4">
            <div class="col-md-4">
                <!-- Botones Importar / Plantilla -->
                <div class="d-flex gap-2">
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalImportar">
                        <i class="fa-solid fa-file-excel"></i> Importar
                    </button>
                    <a href="../controlador/UnidadPolicialControlador.php?op=descargarPrototipo" class="btn btn-outline-info" target="_blank">
                        <i class="fa-solid fa-download"></i> Descargar Plantilla
                    </a>
                </div>
            </div>
            <div class="col-md-5">
                <!-- Buscador Central -->
                <div class="input-group input-group-lg">
                    <span class="input-group-text bg-white"><i class="fa-solid fa-search text-secondary"></i></span>
                    <input type="text" id="txt-busqueda" class="form-control" placeholder="Buscar unidad (Lince, Centro, etc...)" autocomplete="off">
                </div>
            </div>
            <div class="col-md-3 text-end">
                <button class="btn btn-outline-primary" onclick="cargarArbol()">
                    <i class="fa-solid fa-sitemap"></i> Ver Árbol Completo
                </button>
            </div>
        </div>

        <!-- Resultados de Búsqueda -->
        <div id="resultados-busqueda" class="row d-none">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Resultados de Búsqueda</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group" id="lista-resultados">
                            <!-- Items dinámicos -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vista de Árbol (Oculta por defecto) -->
        <div id="vista-arbol" class="row mt-4 d-none">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Estructura Jerárquica Completa</h5>
                        <button class="btn btn-sm btn-light text-info" onclick="$('#vista-arbol').addClass('d-none')">Cerrar</button>
                    </div>
                    <div class="card-body">
                        <div id="arbol-contenedor">
                            <!-- Árbol dinámico -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal Importación -->
<div class="modal fade" id="modalImportar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fa-solid fa-file-import"></i> Importar Datos de Excel</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        
        <div class="alert alert-info mb-3">
            <i class="fa-solid fa-circle-info"></i> Selecciona el archivo Excel <strong>(.xlsx)</strong>.
        </div>
        
        <div class="mb-4">
            <label for="excel-simple-input" class="form-label fw-bold">Archivo a subir:</label>
            <input class="form-control form-control-lg" type="file" id="excel-simple-input" accept=".xlsx, .xls">
        </div>

        <div class="alert alert-warning py-2 small">
            <i class="fa-solid fa-triangle-exclamation"></i> 
            <strong>Atención:</strong> Esta acción borrará los registros anteriores y cargará los nuevos del Excel.
        </div>

        <div class="d-grid mt-3">
           <a href="../controlador/UnidadPolicialControlador.php?op=descargarPrototipo" class="btn btn-sm btn-outline-info text-decoration-none">
              <i class="fa-solid fa-download"></i> Descargar Plantilla Modelo (4 Columnas)
           </a>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success" id="btn-import-confirm" onclick="procesarImportacion()">
            <i class="fa-solid fa-upload"></i> Procesar Importación
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Fin del contenido principal -->

<?php require('./layout/footer.php'); ?>

<script>
// --- LÓGICA DE IMPORTACIÓN ---
function procesarImportacion() {
    const input = document.getElementById('excel-simple-input');
    
    if (!input || !input.files || input.files.length === 0) {
        Swal.fire({
            title: 'Atención',
            text: 'Por favor selecciona un archivo Excel para continuar.',
            icon: 'warning',
            confirmButtonColor: '#0d6efd'
        });
        return;
    }

    const file = input.files[0];
    let formData = new FormData();
    formData.append('archivo_excel', file);

    // Ocultar modal manualmente
    const modalEl = document.getElementById('modalImportar');
    const modal = bootstrap.Modal.getInstance(modalEl);
    modal.hide();

    Swal.fire({
        title: 'Procesando...',
        text: 'Importando datos de ' + file.name,
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    $.ajax({
        url: '../controlador/UnidadPolicialControlador.php?op=importar',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function(response) {
            // Limpiar input
            input.value = '';
            
            if (response.status) {
                Swal.fire('¡Éxito!', response.msg, 'success');
            } else {
                Swal.fire('Error', response.msg, 'error');
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error("AJAX Error:", textStatus, errorThrown);
            console.log("Response Text:", jqXHR.responseText);
            Swal.fire({
                title: 'Error de servidor (' + jqXHR.status + ')', 
                html: 'Hubo un problema al procesar la solicitud.<br><br><strong>Detalle:</strong> ' + (jqXHR.responseText ? jqXHR.responseText.substring(0, 300) + '...' : errorThrown), 
                icon: 'error'
            });
        }
    });
}

// Limpiar input al abrir modal
$(document).ready(function() {
    $('#modalImportar').on('show.bs.modal', function () {
        $('#excel-simple-input').val('');
    });




    // --- Búsqueda en Vivo ---
    let debounceTimer;
    $('#txt-busqueda').on('input', function() {
        clearTimeout(debounceTimer);
        let termino = $(this).val().trim();

        if (termino.length < 2) {
            $('#resultados-busqueda').addClass('d-none');
            return;
        }

        debounceTimer = setTimeout(function() {
            $.post('../controlador/UnidadPolicialControlador.php?op=buscar', { termino: termino }, function(data) {
                let html = '';
                if (data.length > 0) {
                    data.forEach(item => {
                        html += `
                            <a href="#" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1 text-primary fw-bold"><i class="fa-solid fa-building-shield"></i> ${item.sub_unidad || item.unidad_superior}</h5>
                                    <small class="text-muted">ID: ${item.id_unidad_ref}</small>
                                </div>
                                <p class="mb-1 mt-1">
                                    <span class="badge bg-secondary me-1"><i class="fa-solid fa-map"></i> ${item.region}</span> 
                                    <span class="badge bg-info text-dark me-1"><i class="fa-solid fa-shield-halved"></i> ${item.unidad_superior}</span>
                                    ${item.sub_unidad ? `<span class="badge bg-success"><i class="fa-solid fa-person-military-pointing"></i> ${item.sub_unidad}</span>` : ''}
                                    ${item.tipo_cpnp ? `<span class="badge bg-warning text-dark ms-1"><i class="fa-solid fa-tag"></i> ${item.tipo_cpnp}</span>` : ''}
                                </p>
                            </a>
                        `;
                    });
                    $('#lista-resultados').html(html);
                    $('#resultados-busqueda').removeClass('d-none');
                } else {
                    $('#lista-resultados').html('<div class="list-group-item text-center text-muted">No se encontraron coincidencias</div>');
                    $('#resultados-busqueda').removeClass('d-none');
                }
            }, 'json');
        }, 300);
    });

});

function cargarArbol() {
    Swal.fire({
        title: 'Cargando Estructura...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    $.get('../controlador/UnidadPolicialControlador.php?op=arbol', function(arbol) {
        let html = '<ul class="list-unstyled ms-2">';
        
        for (const [region, divisiones] of Object.entries(arbol)) {
            html += `
                <li class="mb-2">
                    <div class="d-flex align-items-center mb-1">
                        <i class="fa-solid fa-caret-right me-2 text-primary" style="cursor:pointer" onclick="$(this).toggleClass('fa-rotate-90'); $(this).closest('li').find('> ul').slideToggle()"></i>
                        <strong class="text-primary fs-5" style="cursor:pointer" onclick="$(this).prev().click()"><i class="fa-solid fa-map"></i> ${region}</strong>
                    </div>
                    <ul class="ms-4 border-start ps-3 border-secondary" style="display:none">
            `;
            
            for (const [division, subunidades] of Object.entries(divisiones)) {
                html += `
                    <li class="mb-2">
                        <div class="d-flex align-items-center">
                             <i class="fa-solid fa-caret-right me-2 text-info" style="cursor:pointer" onclick="$(this).toggleClass('fa-rotate-90'); $(this).closest('li').find('> ul').slideToggle()"></i>
                             <strong class="text-info" style="cursor:pointer" onclick="$(this).prev().click()"><i class="fa-solid fa-shield-halved"></i> ${division}</strong>
                        </div>
                        <ul class="ms-4 mt-1" style="display:none">
                `;
                
                subunidades.forEach(sub => {
                    html += `<li class="text-secondary py-1"><i class="fa-solid fa-building me-2"></i> ${sub}</li>`;
                });
                
                html += `</ul></li>`;
            }
            
            html += `</ul></li>`;
        }
        
        html += '</ul>';
        
        $('#arbol-contenedor').html(html);
        $('#vista-arbol').removeClass('d-none');
        Swal.close();
        
        // Scroll to tree
        $('html, body').animate({
            scrollTop: $("#vista-arbol").offset().top - 100
        }, 500);

    }, 'json');
}
</script>
