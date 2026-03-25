<?php
// controlador/renovar_sesion.php
if (session_status() === PHP_SESSION_NONE) session_start();

if (isset($_SESSION['id'])) {
    $_SESSION['last_activity'] = time();
    echo json_encode(['status' => 'ok']);
} else {
    // Si no hay sesión válida
    echo json_encode(['status' => 'error']);
}
?>
