<?php
/**
 * CONFIGURACIÓN DE PERMISOS POR DEFECTO
 * =====================================
 * 
 * Este archivo centraliza la configuración de permisos que se asignan
 * PERMISOS POR DEFECTO PARA NUEVOS USUARIOS
 * ==========================================
 * 
 * Estos permisos se asignan automáticamente cuando se registra un nuevo usuario.
 * 
 * FILOSOFÍA:
 * - Los usuarios nuevos deben poder CONSULTAR toda la información operativa
 * - Solo tienen permiso VER (no pueden crear, editar ni eliminar)
 * - Los módulos administrativos (Configuración, Utilitarios, Historial) NO se incluyen
 * - Cuando un usuario necesite crear/editar, el administrador habilita esos permisos específicos
 * 
 * VENTAJAS:
 * ✅ Usuarios productivos desde el primer día
 * ✅ Pueden consultar información sin esperar configuración
 * ✅ Seguridad: Solo lectura, no pueden modificar nada
 * ✅ Menos trabajo administrativo
 */

// ============================================================================
// OPCIÓN RECOMENDADA: ACCESO COMPLETO DE LECTURA
// ============================================================================
// Nuevos usuarios pueden VER todos los módulos operativos
// NO pueden crear, editar ni eliminar (solo consultar)

$PERMISOS_POR_DEFECTO = [
    // Módulos de Consulta General
    ['DASHBOARD', 'VER'],              // Estadísticas y resumen
    ['SEGMENTACION', 'VER'],           // Datos de segmentación
    ['GESTION_DOCUMENTAL', 'VER'],     // Documentos del sistema
    ['UNIDADES_POLICIALES', 'VER'],    // Regiones, divisiones, comisarías
    ['INVENTARIO', 'VER'],             // Bienes patrimoniales
    ['REPOSITORIO', 'VER'],            // Archivos y documentos
    ['CAPACITACIONES', 'VER'],         // Cursos y capacitaciones

    // NOTA: Los siguientes módulos NO se incluyen por defecto:
    // - DESTINOS (Mantenimiento de destinos - solo administradores)
    // - USUARIOS (Gestión de usuarios - solo administradores)
    // - Configuración, Utilitarios, Historial (solo administradores)
];

// ============================================
// OPCIÓN 3: PERMISOS OPERATIVOS
// ============================================
// Lectura completa + Creación en módulos operativos
// Para usuarios que necesitan trabajar inmediatamente

/*
$PERMISOS_POR_DEFECTO = [
    // Lectura completa
    ['DASHBOARD', 'VER'],
    ['GESTION_DOCUMENTAL', 'VER'],
    ['INVENTARIO', 'VER'],
    ['REPOSITORIO', 'VER'],
    ['UNIDADES_POLICIALES', 'VER'],
    ['CAPACITACIONES', 'VER'],
    ['SEGMENTACION', 'VER'],
    ['DESTINOS', 'VER'],

    // Creación en módulos operativos
    ['GESTION_DOCUMENTAL', 'CREAR'],
    ['INVENTARIO', 'CREAR'],
    ['REPOSITORIO', 'CREAR']
];
*/

// ============================================
// OPCIÓN 4: PERSONALIZADO
// ============================================
// Crea tu propia configuración según tus necesidades

/*
$PERMISOS_POR_DEFECTO = [
    // Agrega aquí los permisos que necesites
    // Formato: ['MODULO', 'ACCION']

    // Ejemplo:
    // ['DASHBOARD', 'VER'],
    // ['GESTION_DOCUMENTAL', 'VER'],
    // ['GESTION_DOCUMENTAL', 'CREAR'],
];
*/

// ============================================
// MÓDULOS DISPONIBLES
// ============================================
/*
- DASHBOARD
- GESTION_DOCUMENTAL
- INVENTARIO
- REPOSITORIO
- UNIDADES_POLICIALES
- CAPACITACIONES
- SEGMENTACION
- DESTINOS
*/

// ============================================
// ACCIONES DISPONIBLES
// ============================================
/*
- VER        : Ver/Listar registros
- CREAR      : Crear nuevos registros
- EDITAR     : Modificar registros existentes
- ELIMINAR   : Eliminar registros
- EXPORTAR   : Exportar datos
- IMPORTAR   : Importar datos masivos
*/

?>