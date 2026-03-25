<?php
$pdf_id = isset($_GET['pdf_id']) ? $_GET['pdf_id'] : null;

if ($pdf_id) {
    // Recupera y muestra el PDF correspondiente
    header('Content-type: application/pdf');
    readfile('public/' . $pdf_id);
} else {
    // Maneja el caso en el que no se proporciona un ID de PDF válido
    echo 'PDF no encontrado.';
}
