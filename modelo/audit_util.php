<?php
// modelo/audit_util.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Registra un evento en la bitácora de forma SILENCIOSA (Fail-Safe).
 * Si falla, NO interrumpe el flujo principal.
 */
function registrar_evento(mysqli $conn, string $accion, string $detalle = ''): void {
    try {
        $usuario_id = $_SESSION['id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

        // Si el usuario es nulo, intentamos registrarlo como 0 o NULL según permita la DB
        // (En el create table pusimos DEFAULT NULL)

        $stmt = $conn->prepare("INSERT INTO bitacora (usuario_id, accion, detalle, ip) VALUES (?, ?, ?, ?)");
        if (!$stmt) return; // Fail-Safe

        $stmt->bind_param('isss', $usuario_id, $accion, $detalle, $ip);
        $stmt->execute();
        $stmt->close();

    } catch (Throwable $e) {
        // SILENCIOSO: No hacemos nada si falla la auditoría
        // error_log("Error auditoría: " . $e->getMessage());
    }
}
