/**
 * Gestión de Permisos de Usuarios - Versión Completa con Modal de Permisos
 * @version 4.0.0
 * @date 2026-01-17 23:56
 */

// Variable global para almacenar permisos actuales
let permisosActuales = {};
let idUsuarioActual = 0;
let nombreUsuarioActual = '';

// La estructura de módulos se cargará dinámicamente desde la base de datos
let estructuraModulos = {
    'operativos': {},
    'administracion': {}
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', async function () {
    console.log('✅ Sistema de permisos completo cargado - v4.0.0');

    // Cargar estructura de módulos dinámicamente
    await cargarEstructuraModulos();

    // Configurar eventos de los botones de permisos usando DELEGACIÓN para DataTables
    $(document).on('click', '.btn-permisos', function () {
        idUsuarioActual = $(this).data('id-usuario');
        nombreUsuarioActual = $(this).data('nombre-usuario');
        console.log(`Abriendo permisos para usuario ${idUsuarioActual}: ${nombreUsuarioActual}`);
        cargarYMostrarPermisos(idUsuarioActual, nombreUsuarioActual);
    });
});

/**
 * Cargar estructura de módulos desde la base de datos
 */
async function cargarEstructuraModulos() {
    try {
        const response = await fetch(`../controlador/PermisosControlador.php?op=obtener_estructura&_=${new Date().getTime()}`, {
            credentials: 'same-origin'
        });
        const data = await response.json();

        if (data.success) {
            // Resetear
            estructuraModulos = {
                'operativos': {},
                'administracion': {}
            };

            // Clasificar módulos
            Object.keys(data.data).forEach(slug => {
                const modulo = data.data[slug];
                const cat = modulo.es_restringido ? 'administracion' : 'operativos';
                estructuraModulos[cat][slug.toUpperCase()] = {
                    nombre: modulo.nombre,
                    icono: modulo.icono,
                    acciones: modulo.acciones.map(a => a.toUpperCase())
                };
            });
            console.log('📦 Estructura de módulos cargada:', estructuraModulos);
        }
    } catch (error) {
        console.error('❌ Error al cargar estructura de módulos:', error);
    }
}

/**
 * Cargar permisos del usuario y mostrar modal
 */
async function cargarYMostrarPermisos(idUsuario, nombreUsuario) {
    // Mostrar loading
    Swal.fire({
        title: 'Cargando permisos...',
        html: 'Por favor espera',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const response = await fetch(`../controlador/PermisosControlador.php?op=obtener_permisos_usuario&id_usuario=${idUsuario}&_=${new Date().getTime()}`, {
            credentials: 'same-origin'
        });
        const data = await response.json();

        if (data.success) {
            permisosActuales = data.data.permisos || {};

            // AUTO-CORRECCIÓN: Asegurar consistencia lógica (Si tiene CREAR/EDITAR -> Tiene VER)
            sanitizarConsistenciaPermisos();

            mostrarModalPermisosCompleto(idUsuario, nombreUsuario);
        } else {
            // Si no hay permisos, inicializar vacío
            permisosActuales = {};
            mostrarModalPermisosCompleto(idUsuario, nombreUsuario);
        }
    } catch (error) {
        console.error('Error al cargar permisos:', error);
        // Continuar con permisos vacíos
        permisosActuales = {};
        mostrarModalPermisosCompleto(idUsuario, nombreUsuario);
    }
}

/**
 * Asegurar consistencia lógica de permisos
 * Si tiene cualquier permiso (CREAR, EDITAR, etc.), debe tener VER al cargar.
 */
function sanitizarConsistenciaPermisos() {
    if (!permisosActuales) return;

    const nuevosPermisos = {};

    Object.keys(permisosActuales).forEach(moduloKey => {
        const moduloUpper = moduloKey.toUpperCase();
        const modulo = permisosActuales[moduloKey];
        if (!modulo) return;

        // Normalizar acciones a mayúsculas también
        const moduloNormalizado = {};
        Object.keys(modulo).forEach(accKey => {
            moduloNormalizado[accKey.toUpperCase()] = modulo[accKey];
        });

        // Buscar si existe alguna acción avanzada
        const tieneAccionAvanzada = Object.keys(moduloNormalizado).some(acc => {
            return moduloNormalizado[acc] === true && acc !== 'VER';
        });

        if (tieneAccionAvanzada) {
            moduloNormalizado['VER'] = true;
        }

        nuevosPermisos[moduloUpper] = moduloNormalizado;
    });

    permisosActuales = nuevosPermisos;
}

/**
 * Mostrar modal completo de permisos - VERSIÓN OPTIMIZADA CON TABS
 */
function mostrarModalPermisosCompleto(idUsuario, nombreUsuario) {
    const html = `
        <div class="text-start">
            <!-- Header con info del usuario -->
            <div class="alert alert-light border mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fa-solid fa-user text-primary"></i>
                        <strong>${nombreUsuario}</strong>
                    </div>
                    <span class="badge bg-primary" id="resumenPermisos">${contarPermisosActivos()} permisos activos</span>
                </div>
            </div>
            
            <!-- Plantillas Rápidas - COMPACTAS -->
            <div class="mb-3">
                <label class="form-label fw-bold small">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> Plantillas Rápidas
                </label>
                <div class="btn-group w-100" role="group">
                    <button type="button" 
                            class="btn btn-outline-info btn-sm btn-plantilla-rapida" 
                            data-plantilla-id="2"
                            data-color="info"
                            onclick="aplicarPlantillaRapida(2)" 
                            title="Ver todos los módulos operativos sin permisos de edición">
                        <i class="fa-solid fa-eye"></i> Lectura
                    </button>
                    <button type="button" 
                            class="btn btn-outline-success btn-sm btn-plantilla-rapida" 
                            data-plantilla-id="3"
                            data-color="success"
                            onclick="aplicarPlantillaRapida(3)" 
                            title="Puede visualizar, crear nuevos registros y editar contenido existente">
                        <i class="fa-solid fa-pen"></i> Editor
                    </button>
                    <button type="button" 
                            class="btn btn-outline-warning btn-sm btn-plantilla-rapida" 
                            data-plantilla-id="4"
                            data-color="warning"
                            onclick="aplicarPlantillaRapida(4)" 
                            title="Control total de módulos operativos (Sin acceso a Seguridad/Configuración)">
                        <i class="fa-solid fa-screwdriver-wrench"></i> Gestor
                    </button>
                    <button type="button" 
                            class="btn btn-outline-danger btn-sm btn-plantilla-rapida" 
                            data-plantilla-id="5"
                            data-color="danger"
                            onclick="aplicarPlantillaRapida(5)" 
                            title="Control total del sistema incluyendo Configuración y Seguridad">
                        <i class="fa-solid fa-crown"></i> Admin
                    </button>
                </div>
            </div>
            
            <!-- Permisos por Módulo - TABS HORIZONTALES -->
            <div class="mb-2">
                <label class="form-label fw-bold small">
                    <i class="fa-solid fa-sliders"></i> Permisos Personalizados
                </label>
            </div>
            
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-light py-2">
                    <h6 class="mb-0 small fw-bold text-primary text-uppercase">
                        <i class="fa-solid fa-briefcase"></i> Módulos Operativos
                    </h6>
                </div>
                <div class="card-body p-2">
                    <ul class="nav nav-tabs nav-tabs-sm mb-2" id="modulosOperativosTabs" role="tablist">
                        ${generarNavTabs('operativos')}
                    </ul>
                    <div class="tab-content border border-top-0 p-3" id="modulosOperativosTabContent" style="max-height: 250px; overflow-y: auto;">
                        ${generarTabContent('operativos')}
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 border-start border-danger border-4">
                <div class="card-header bg-light py-2">
                    <h6 class="mb-0 small fw-bold text-danger text-uppercase">
                        <i class="fa-solid fa-shield-halved"></i> Administración y Sistema (Restringido)
                    </h6>
                </div>
                <div class="card-body p-2">
                    <ul class="nav nav-tabs nav-tabs-sm mb-2" id="modulosAdministrativosTabs" role="tablist">
                        ${generarNavTabs('administracion')}
                    </ul>
                    <div class="tab-content border border-top-0 p-3" id="modulosAdministrativosTabContent" style="max-height: 200px; overflow-y: auto;">
                        ${generarTabContent('administracion')}
                    </div>
                </div>
            </div>
            
            <!-- Leyenda de permisos -->
            <div class="mt-2">
                <small class="text-muted">
                    <i class="fa-solid fa-info-circle"></i>
                    <strong>Tip:</strong> Al marcar CREAR, EDITAR o ELIMINAR, VER se activa automáticamente
                </small>
            </div>
        </div>
    `;

    Swal.fire({
        title: '<i class="fa-solid fa-shield-halved"></i> Gestión de Permisos',
        html: html,
        width: 900,
        showCancelButton: true,
        confirmButtonText: '<i class="fa-solid fa-save"></i> Guardar Permisos',
        cancelButtonText: '<i class="fa-solid fa-times"></i> Cancelar',
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        customClass: {
            container: 'modal-permisos-container',
            popup: 'modal-permisos-popup'
        },
        didOpen: () => {
            // Activar el primer tab de la sección operativa por defecto
            const firstTab = document.querySelector('#modulosOperativosTabs .nav-link');
            if (firstTab) {
                firstTab.click();
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            guardarPermisosPersonalizados();
        }
    });
}

/**
 * Generar Nav Tabs - VERSIÓN COMPACTA
 */
function generarNavTabs(categoria) {
    let html = '';
    let index = 0;
    const modulos = estructuraModulos[categoria];

    for (const [modulo, datos] of Object.entries(modulos)) {
        const isActive = index === 0 ? 'active' : '';

        html += `
            <li class="nav-item" role="presentation">
                <button class="nav-link ${isActive} py-1 px-2 small" 
                        id="tab-${modulo}" 
                        data-bs-toggle="tab" 
                        data-bs-target="#content-${modulo}" 
                        type="button" 
                        role="tab">
                    <i class="fa-solid ${datos.icono}"></i> ${datos.nombre}
                </button>
            </li>
        `;
        index++;
    }

    return html;
}

/**
 * Generar Tab Content - GRID COMPACTO
 */
function generarTabContent(categoria) {
    let html = '';
    let index = 0;
    const modulos = estructuraModulos[categoria];

    for (const [modulo, datos] of Object.entries(modulos)) {
        const isActive = index === 0 ? 'show active' : '';

        html += `
            <div class="tab-pane fade ${isActive}" 
                 id="content-${modulo}" 
                 role="tabpanel" 
                 aria-labelledby="tab-${modulo}">
                <div class="row g-2">
                    ${generarCheckboxesAccionesCompacto(modulo, datos.acciones)}
                </div>
            </div>
        `;
        index++;
    }

    return html;
}

/**
 * Generar checkboxes de acciones - VERSIÓN COMPACTA
 */
function generarCheckboxesAccionesCompacto(modulo, acciones) {
    let html = '';


    // Helper para buscar permisos case-insensitive
    const checkPermisoInsensitive = (mod, acc) => {
        if (!permisosActuales) return false;
        const modKey = Object.keys(permisosActuales).find(k => k.toLowerCase() === mod.toLowerCase());
        if (!modKey || !permisosActuales[modKey]) return false;

        // Si el objeto permisosActuales[modKey] es booleano (caso raro), no es lo esperado.
        // Asumimos estructura { MODULO: { ACCION: true } }

        // A veces el backend puede devolver ACCION como mayuscula o minuscula
        const accKey = Object.keys(permisosActuales[modKey]).find(k => k.toLowerCase() === acc.toLowerCase());
        return accKey ? permisosActuales[modKey][accKey] : false;
    };

    acciones.forEach(accion => {
        const isChecked = checkPermisoInsensitive(modulo, accion) ? 'checked' : '';
        const colorClass = getColorAccion(accion);

        html += `
            <div class="col-6 col-md-4 col-lg-3">
                <div class="form-check form-switch">
                    <input class="form-check-input permiso-checkbox" 
                           type="checkbox" 
                           id="permiso_${modulo}_${accion}" 
                           data-modulo="${modulo}" 
                           data-accion="${accion}"
                           ${isChecked}
                           onchange="actualizarPermiso('${modulo}', '${accion}', this.checked)">
                    <label class="form-check-label small ${colorClass}" for="permiso_${modulo}_${accion}">
                        ${getIconoAccion(accion)} ${accion}
                    </label>
                </div>
            </div>
        `;
    });

    return html;
}

// --- Fin de funciones de apoyo ---

/**
 * Obtener icono según acción
 */
function getIconoAccion(accion) {
    const iconos = {
        'VER': '<i class="fa-solid fa-eye"></i>',
        'CREAR': '<i class="fa-solid fa-plus"></i>',
        'EDITAR': '<i class="fa-solid fa-pen"></i>',
        'ELIMINAR': '<i class="fa-solid fa-trash"></i>',
        'EXPORTAR': '<i class="fa-solid fa-download"></i>',
        'IMPORTAR': '<i class="fa-solid fa-upload"></i>'
    };
    return iconos[accion] || '<i class="fa-solid fa-check"></i>';
}

/**
 * Obtener color según acción
 */
function getColorAccion(accion) {
    const colores = {
        'VER': 'text-info',
        'CREAR': 'text-success',
        'EDITAR': 'text-warning',
        'ELIMINAR': 'text-danger',
        'EXPORTAR': 'text-primary',
        'IMPORTAR': 'text-secondary'
    };
    return colores[accion] || '';
}

/**
 * Actualizar permiso cuando se marca/desmarca checkbox
 */
function actualizarPermiso(modulo, accion, isChecked) {
    const modUpper = modulo.toUpperCase();
    const accUpper = accion.toUpperCase();

    if (!permisosActuales[modUpper]) {
        permisosActuales[modUpper] = {};
    }

    permisosActuales[modUpper][accUpper] = isChecked;

    // Auto-habilitar VER si se marca cualquier otro permiso
    if (isChecked && accUpper !== 'VER') {
        const checkboxVer = document.getElementById(`permiso_${modulo}_VER`);
        if (checkboxVer && !checkboxVer.checked) {
            checkboxVer.checked = true;
            permisosActuales[modUpper]['VER'] = true;
        }
    }
    // Auto-deshabilitar dependientes si se desmarca VER
    else if (!isChecked && accUpper === 'VER') {
        const checkboxes = document.querySelectorAll(`input[data-modulo="${modulo}"]`);
        checkboxes.forEach(cb => {
            const acc = cb.dataset.accion.toUpperCase();
            if (acc !== 'VER' && cb.checked) {
                cb.checked = false;
                permisosActuales[modUpper][acc] = false;
            }
        });
    }

    // Actualizar resumen
    document.getElementById('resumenPermisos').textContent = `${contarPermisosActivos()} permisos activos`;
}

/**
 * Contar permisos activos
 */
function contarPermisosActivos() {
    let count = 0;
    for (const modulo in permisosActuales) {
        for (const accion in permisosActuales[modulo]) {
            if (permisosActuales[modulo][accion]) {
                count++;
            }
        }
    }
    return count;
}

/**
 * Aplicar plantilla rápida - CON FEEDBACK VISUAL
 */
async function aplicarPlantillaRapida(idRol) {
    try {
        const response = await fetch(`../controlador/PermisosControlador.php?op=obtener_plantilla_rol&id_rol=${idRol}&_=${new Date().getTime()}`);
        const data = await response.json();

        if (data.success) {
            permisosActuales = data.data;

            // Actualizar todos los checkboxes/switches
            document.querySelectorAll('.permiso-checkbox').forEach(checkbox => {
                const modulo = checkbox.dataset.modulo;
                const accion = checkbox.dataset.accion;
                // Búsqueda insensible a mayúsculas/minúsculas
                let isChecked = false;
                if (permisosActuales) {
                    const modKey = Object.keys(permisosActuales).find(k => k.toLowerCase() === modulo.toLowerCase());
                    if (modKey && permisosActuales[modKey]) {
                        const accKey = Object.keys(permisosActuales[modKey]).find(k => k.toLowerCase() === accion.toLowerCase());
                        if (accKey) isChecked = permisosActuales[modKey][accKey];
                    }
                }
                checkbox.checked = isChecked;
            });

            // ✅ MARCAR BOTÓN COMO ACTIVO CON FEEDBACK VISUAL CLARO
            document.querySelectorAll('.btn-plantilla-rapida').forEach(btn => {
                // 1. Resetear todos a estado "outline" (inactivo)
                const colorBase = btn.dataset.color || 'primary'; // info, success, warning, danger

                // Limpiar clases sólidas y active
                btn.classList.remove('active', `btn-${colorBase}`);
                // Asegurar clase outline
                if (!btn.classList.contains(`btn-outline-${colorBase}`)) {
                    btn.classList.add(`btn-outline-${colorBase}`);
                }

                // 2. Si es el botón clickeado (match por ID de rol), activarlo
                // Nota: El ID del botón debe tener el id_rol. Asumimos que el botón clickeado disparó el evento.
                // Pero aquí estamos dentro del fetch, no tenemos referencia directa al botón clickeado salvo por el ID de rol.
                // Vamos a buscar el botón por su onclick o dataset si es posible, o pasamos el elemento.

                // Estrategia alternativa: El botón tiene un atributo onclick="aplicarPlantillaRapida(ID)".
                const btnUpdate = btn.getAttribute('onclick').includes(`(${idRol})`);

                if (btnUpdate) {
                    btn.classList.remove(`btn-outline-${colorBase}`);
                    btn.classList.add(`btn-${colorBase}`, 'active', 'shadow');
                } else {
                    btn.classList.remove('shadow');
                }
            });

            // Agregar clase 'active' al botón clickeado
            const btnActivo = document.querySelector(`[data-plantilla-id="${idRol}"]`);
            if (btnActivo) {
                btnActivo.classList.add('active');
                // Cambiar de outline a sólido
                if (btnActivo.classList.contains('btn-outline-info')) {
                    btnActivo.classList.remove('btn-outline-info');
                    btnActivo.classList.add('btn-info', 'text-white');
                } else if (btnActivo.classList.contains('btn-outline-success')) {
                    btnActivo.classList.remove('btn-outline-success');
                    btnActivo.classList.add('btn-success', 'text-white');
                } else if (btnActivo.classList.contains('btn-outline-warning')) {
                    btnActivo.classList.remove('btn-outline-warning');
                    btnActivo.classList.add('btn-warning', 'text-white');
                } else if (btnActivo.classList.contains('btn-outline-danger')) {
                    btnActivo.classList.remove('btn-outline-danger');
                    btnActivo.classList.add('btn-danger', 'text-white');
                }
            }

            // Actualizar resumen
            const resumen = document.getElementById('resumenPermisos');
            if (resumen) {
                const total = contarPermisosActivos();
                resumen.textContent = `¡Plantilla aplicada! (${total} activos)`;
                resumen.className = 'badge bg-success animate__animated animate__pulse';

                // Volver al estado normal después de 2 seg
                setTimeout(() => {
                    resumen.textContent = `${total} permisos activos`;
                    resumen.className = 'badge bg-primary';
                }, 2000);
            }
        }
    } catch (error) {
        console.error('Error al aplicar plantilla:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo aplicar la plantilla',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
    }
}

/**
 * Guardar permisos personalizados
 */
async function guardarPermisosPersonalizados() {
    // Mostrar loading
    Swal.fire({
        title: 'Guardando permisos...',
        html: 'Por favor espera',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const response = await fetch('../controlador/PermisosControlador.php?op=guardar_permisos', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id_usuario: idUsuarioActual,
                permisos: permisosActuales
            })
        });

        const data = await response.json();

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Permisos guardados!',
                html: `
                    <p>Los permisos se guardaron correctamente para:</p>
                    <p class="text-primary"><strong>${nombreUsuarioActual}</strong></p>
                    <div class="alert alert-warning mt-3">
                        <i class="fa-solid fa-exclamation-triangle"></i> 
                        <strong>Importante:</strong> El usuario debe cerrar sesión y volver a entrar para ver los cambios.
                    </div>
                `,
                confirmButtonText: 'Entendido'
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'No se pudieron guardar los permisos',
                confirmButtonText: 'Cerrar'
            });
        }
    } catch (error) {
        console.error('Error al guardar permisos:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error de conexión',
            text: 'No se pudo conectar con el servidor',
            confirmButtonText: 'Cerrar'
        });
    }
}

console.log('✅ Sistema de permisos completo cargado - Versión 4.0.0');
