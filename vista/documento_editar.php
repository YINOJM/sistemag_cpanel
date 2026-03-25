<?php
// vista/documento_editar.php
include 'layout/topbar.php';
include 'layout/sidebar.php';
require_once '../modelo/DocumentoModelo.php';

$modelo = new DocumentoModelo();
$destinos = $modelo->obtenerDestinos();

// Obtener ID del documento
$id_documento = $_GET['id'] ?? 0;
if (!$id_documento) {
    header('Location: gestion_documental.php');
    exit;
}

// Verificar Permisos
$rol = $_SESSION['rol'] ?? '';
$esAdmin = ($rol === 'Administrador' || $rol === 'Super Administrador');
?>

<div class="page-content" style="padding: 40px 20px 20px 20px;">
    <div class="container-fluid">

        <div class="row justify-content-center mt-5">
            <div class="col-md-10">
                <div class="card shadow-lg border-0 rounded-lg">
                    <div class="card-header text-white d-flex justify-content-between align-items-center"
                        style="background-color: #00779e;">
                        <h4 class="mb-0"><i class="fas fa-edit me-2"></i>Editar Documento</h4>
                        <a href="gestion_documental.php" class="btn btn-light btn-sm">
                            <i class="fa-solid fa-arrow-left me-1"></i> Atrás
                        </a>
                    </div>
                    <div class="card-body bg-light">
                        <form id="form-editar-documento">
                            <input type="hidden" name="id_documento" id="id_documento"
                                value="<?php echo $id_documento; ?>">

                            <!-- Fila 1: Información del Documento -->
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold small">N° Documento</label>
                                    <input type="text" class="form-control" id="num_completo" readonly
                                        style="background-color: #e9ecef; cursor: not-allowed;">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold small">Tipo Documento</label>
                                    <select class="form-select" name="tipo" id="cod_tipo">
                                        <option value="OFICIO">OFICIO</option>
                                        <option value="INFORME">INFORME</option>
                                        <option value="MEMORANDUM">MEMORANDUM</option>
                                        <option value="ORDEN_TELEFONICA">ORDEN TELEFONICA</option>
                                        <option value="SOLICITUD">SOLICITUD</option>
                                        <option value="OTRO">OTRO</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold small">Año</label>
                                    <input type="text" class="form-control" id="anio" readonly
                                        style="background-color: #e9ecef; cursor: not-allowed;">
                                </div>
                                
                                <?php if ($esAdmin): ?>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold text-danger small">Fecha (Solo Admin)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-danger text-white border-danger"><i class="fa-solid fa-calendar"></i></span>
                                        <input type="date" class="form-control border-danger" name="fecha_documento" id="fecha_documento">
                                    </div>
                                </div>
                                <?php else: ?>
                                <div class="col-md-4">
                                     <label class="form-label fw-bold small">Fecha</label>
                                     <input type="text" class="form-control" id="fecha_vista" readonly style="background-color: #e9ecef;">
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Fila 2: HT / Referencia -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">HT / Referencia</label>
                                    <input type="text" class="form-control" name="ht" id="ht"
                                        placeholder="Ej. 20250015236">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-success">Clasificación / Se Solicita</label>
                                    <div class="position-relative">
                                        <input class="form-control" name="se_solicita" id="se_solicita"
                                            placeholder="Escriba o seleccione..." autocomplete="off">
                                        <ul id="listaSolicitudesCustom"
                                            class="list-group position-absolute w-100 shadow-sm"
                                            style="display:none; max-height: 300px; overflow-y: auto; z-index: 1050; top: 100%;">
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Fila 3: Destino -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">Destino (Unidad/Area)</label>
                                <div class="position-relative">
                                    <input class="form-control" type="text" id="nombre_destino_input"
                                        placeholder="Escriba para buscar..." autocomplete="off" required>
                                    <input type="hidden" name="id_destino" id="id_destino_hidden">
                                    <ul id="listaDestinosCustom" class="list-group position-absolute w-100 shadow-sm"
                                        style="display:none; max-height: 300px; overflow-y: auto; z-index: 1050; top: 100%;">
                                    </ul>
                                </div>
                            </div>

                            <!-- Fila 4: Asunto -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">Asunto</label>
                                <textarea class="form-control" name="asunto" id="asunto" rows="2"
                                    placeholder="Describa brevemente el asunto del documento..." required
                                    style="resize: none;"></textarea>
                            </div>

                            <!-- Fila 5: Formulado Por -->
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Formulado Por</label>
                                <input type="text" class="form-control" name="formulado_por" id="formulado_por" readonly
                                    style="background-color: #e9ecef; cursor: not-allowed;">
                            </div>

                            <!-- Fila 6: Observaciones -->
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Observaciones</label>
                                <textarea class="form-control" name="observaciones" id="observaciones" rows="2"
                                    placeholder="Observaciones adicionales..."></textarea>
                            </div>

                            <div class="row gap-2">
                                <div class="col">
                                    <button type="submit" class="btn btn-lg shadow-sm text-white w-100"
                                        style="background-color: #00779e; border-color: #00779e;">
                                        <i class="fas fa-save me-2"></i> Actualizar Documento
                                    </button>
                                </div>

                            </div>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<?php include 'layout/footer.php'; ?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .cursor-pointer {
        cursor: pointer;
    }
</style>

<script>
    // Pasar array PHP a JS
    const destinosData = <?php echo json_encode($destinos); ?>;
    const idDocumento = <?php echo $id_documento; ?>;

    const inputDestino = document.getElementById('nombre_destino_input');
    const ulDestinos = document.getElementById('listaDestinosCustom');
    const hiddenDestino = document.getElementById('id_destino_hidden');

    // Cargar datos del documento
    fetch(`../controlador/DocumentoControlador.php?op=obtener&id=${idDocumento}`)
        .then(res => res.json())
        .then(data => {
            if (data.status) {
                const doc = data.data;
                console.log('Documento cargado:', doc); // Debug
                document.getElementById('num_completo').value = doc.num_completo || '';
                document.getElementById('cod_tipo').value = doc.cod_tipo || '';
                // Guardar valores originales para detectar el cambio
                window.tipoOriginal = doc.cod_tipo || '';
                window.numOriginal = doc.num_completo || '';

                document.getElementById('anio').value = doc.anio || '';
                document.getElementById('ht').value = doc.ht || '';
                document.getElementById('se_solicita').value = doc.se_solicita ? doc.se_solicita : '';
                document.getElementById('asunto').value = doc.asunto || '';
                document.getElementById('formulado_por').value = doc.usuario_formulador || '';
                document.getElementById('observaciones').value = doc.observaciones || '';

                // Cargar fecha (created_at)
                let fechaRaw = doc.created_at || '';
                let fechaSolo = fechaRaw.split(' ')[0]; // Extraer YYYY-MM-DD
                
                if (document.getElementById('fecha_documento')) {
                    document.getElementById('fecha_documento').value = fechaSolo;
                }
                if (document.getElementById('fecha_vista')) {
                    // Convertir YYYY-MM-DD a DD/MM/YYYY para vista solo lectura
                    let partes = fechaSolo.split('-');
                    if(partes.length === 3) {
                         document.getElementById('fecha_vista').value = `${partes[2]}/${partes[1]}/${partes[0]}`;
                    }
                }

                // Setear destino
                document.getElementById('nombre_destino_input').value = doc.nombre_destino || '';
                document.getElementById('id_destino_hidden').value = doc.id_destino || '';
            } else {
                Swal.fire('Error', data.msg || 'No se pudo cargar el documento', 'error')
                    .then(() => window.location.href = 'gestion_documental.php');
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Error', 'Error al cargar el documento', 'error')
                .then(() => window.location.href = 'gestion_documental.php');
        });

    // Detectar cambio del Tipo de Documento en el Front-End para avisar al usuario
    document.getElementById('cod_tipo').addEventListener('change', function() {
        let inputNum = document.getElementById('num_completo');
        if (this.value !== window.tipoOriginal) {
            Swal.fire({
                title: 'Atención',
                html: `Vas a cambiar el tipo de documento de <b>${window.tipoOriginal}</b> a <b>${this.value}</b>.<br><br>Una vez que guardes, el sistema le asignará una <b>nueva numeración correlativa</b> correspondiente a ${this.value}.`,
                icon: 'warning',
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#00779e'
            });
            inputNum.value = 'Nuevo correlativo de ' + this.value;
            inputNum.classList.add('text-danger', 'fw-bold');
        } else {
            inputNum.value = window.numOriginal;
            inputNum.classList.remove('text-danger', 'fw-bold');
        }
    });

    // Autocompletado de destinos
    inputDestino.addEventListener('input', function () {
        const val = this.value.toUpperCase();
        ulDestinos.innerHTML = '';
        hiddenDestino.value = '';

        if (!val) {
            ulDestinos.innerHTML = '';
            destinosData.forEach(dest => {
                const li = document.createElement('li');
                li.className = 'list-group-item list-group-item-action cursor-pointer small';
                li.textContent = dest.nombre_destino;
                li.addEventListener('click', function () {
                    inputDestino.value = dest.nombre_destino;
                    hiddenDestino.value = dest.id_destino;
                    ulDestinos.style.display = 'none';
                });
                ulDestinos.appendChild(li);
            });
            ulDestinos.style.display = 'block';
            return;
        }

        const filtered = destinosData.filter(d => d.nombre_destino.toUpperCase().includes(val));
        if (filtered.length > 0) {
            filtered.forEach(dest => {
                const li = document.createElement('li');
                li.className = 'list-group-item list-group-item-action cursor-pointer small';
                li.textContent = dest.nombre_destino;
                li.addEventListener('click', function () {
                    inputDestino.value = dest.nombre_destino;
                    hiddenDestino.value = dest.id_destino;
                    ulDestinos.style.display = 'none';
                });
                ulDestinos.appendChild(li);
            });
            ulDestinos.style.display = 'block';
        } else {
            ulDestinos.style.display = 'none';
        }
    });

    inputDestino.addEventListener('focus', function () {
        // Mostrar todas las opciones al hacer clic
        ulDestinos.innerHTML = '';
        destinosData.forEach(dest => {
            const li = document.createElement('li');
            li.className = 'list-group-item list-group-item-action cursor-pointer small';
            li.textContent = dest.nombre_destino;
            li.addEventListener('click', function () {
                inputDestino.value = dest.nombre_destino;
                hiddenDestino.value = dest.id_destino;
                ulDestinos.style.display = 'none';
            });
            ulDestinos.appendChild(li);
        });
        ulDestinos.style.display = 'block';
    });

    document.addEventListener('click', function (e) {
        if (!ulDestinos.contains(e.target) && e.target !== inputDestino) {
            ulDestinos.style.display = 'none';
        }
    });

    // Enviar formulario
    document.getElementById('form-editar-documento').addEventListener('submit', function (e) {
        e.preventDefault();

        if (!hiddenDestino.value) {
            Swal.fire('Atención', 'Debe seleccionar un destino válido', 'warning');
            return;
        }

        const formData = new FormData(this);

        fetch('../controlador/DocumentoControlador.php?op=actualizar', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.status) {
                    Swal.fire('Éxito', data.msg, 'success')
                        .then(() => window.location.href = 'gestion_documental.php');
                } else {
                    Swal.fire('Error', data.msg, 'error');
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire('Error', 'No se pudo actualizar el documento', 'error');
            });
    });

    // Autocompletado personalizado para "Clasificación / Se Solicita"
    const solicitudesData = [
        'INTERACCIÓN CON EL MERCADO',
        'SOLICITA CCP',
        'PROPUESTA DE MODIFICACION',
        'SOLICITA GESTIONAR',
        'REMITE INFORMACION',
        'DEVOLUCION DE DOCUMENTO',
        'SOLICITA INFORMACION'
    ];

    const inputSolicita = document.getElementById('se_solicita');
    const ulSolicitudes = document.getElementById('listaSolicitudesCustom');

    inputSolicita.addEventListener('input', function () {
        const val = this.value.toUpperCase();
        ulSolicitudes.innerHTML = '';

        if (!val) {
            // Mostrar todas las opciones si está vacío
            solicitudesData.forEach(item => {
                const li = document.createElement('li');
                li.className = 'list-group-item list-group-item-action cursor-pointer small';
                li.textContent = item;
                li.addEventListener('click', function () {
                    inputSolicita.value = item;
                    ulSolicitudes.style.display = 'none';
                });
                ulSolicitudes.appendChild(li);
            });
            ulSolicitudes.style.display = 'block';
            return;
        }

        // Filtrar opciones
        const filtered = solicitudesData.filter(d => d.toUpperCase().includes(val));
        if (filtered.length > 0) {
            filtered.forEach(item => {
                const li = document.createElement('li');
                li.className = 'list-group-item list-group-item-action cursor-pointer small';
                li.textContent = item;
                li.addEventListener('click', function () {
                    inputSolicita.value = item;
                    ulSolicitudes.style.display = 'none';
                });
                ulSolicitudes.appendChild(li);
            });
            ulSolicitudes.style.display = 'block';
        } else {
            ulSolicitudes.style.display = 'none';
        }
    });

    inputSolicita.addEventListener('focus', function () {
        // Mostrar todas las opciones al hacer clic
        ulSolicitudes.innerHTML = '';
        solicitudesData.forEach(item => {
            const li = document.createElement('li');
            li.className = 'list-group-item list-group-item-action cursor-pointer small';
            li.textContent = item;
            li.addEventListener('click', function () {
                inputSolicita.value = item;
                ulSolicitudes.style.display = 'none';
            });
            ulSolicitudes.appendChild(li);
        });
        ulSolicitudes.style.display = 'block';
    });

    document.addEventListener('click', function (e) {
        if (!ulSolicitudes.contains(e.target) && e.target !== inputSolicita) {
            ulSolicitudes.style.display = 'none';
        }
    });
</script>