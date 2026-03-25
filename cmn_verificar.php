<?php
require 'modelo/conexion.php';

$valid = null;
$error = null;
$codigo = $_GET['codigo'] ?? '';

if (!empty($codigo)) {
    // El código tiene formato CMN26-XXXXX-HASH
    $parts = explode('-', $codigo);
    if (count($parts) === 3 && $parts[0] === 'CMN' . substr(ANIO_CMN, -2)) {
        $id = (int)$parts[1];
        $hash_corto = $parts[2];

        $stmt = $conexion->prepare("SELECT * FROM cmn_responsables WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();

        if ($row) {
            // Verificar el Hash
            $hash_calc = strtoupper(substr(hash('sha256', $row['dni'] . $row['fecha_registro']), 0, 8));
            if ($hash_calc === $parts[2]) {
                $valid = $row;
            } else {
                $error = "El código de seguridad no coincide con los datos del registro.";
            }
        } else {
            $error = "No se encontró ningún registro con ese número de emisión.";
        }
    } else {
        $error = "Formato de código inválido. Ejemplo: CMN26-00001-A1B2C3D4";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validación de Constancia CMN 2026</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .validate-card { background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 100%; max-width: 500px; padding: 40px; }
        .header-logo { text-align: center; margin-bottom: 30px; }
        .btn-validate { background: #003666; color: white; border: none; padding: 12px; border-radius: 10px; width: 100%; font-weight: bold; transition: all 0.3s; }
        .btn-validate:hover { background: #002244; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,54,102,0.3); color: white; }
        .result-box { margin-top: 30px; padding: 20px; border-radius: 15px; border: 1px solid #eee; }
        .result-success { background: #e8f5e9; border-color: #c8e6c9; }
        .result-error { background: #ffebee; border-color: #ffcdd2; }
    </style>
</head>
<body>

<div class="validate-card">
    <div class="header-logo">
        <i class="fa-solid fa-shield-halved fa-3x text-primary mb-3"></i>
        <h4 class="fw-bold text-dark">VALIDAR CONSTANCIA</h4>
        <p class="text-muted small">Módulo de Verificación de CMN 2026</p>
    </div>

    <form action="" method="GET">
        <div class="mb-4">
            <label class="form-label fw-bold">Código de Emisión</label>
            <input type="text" name="codigo" class="form-control form-control-lg text-center fw-bold" placeholder="CMN26-00000-XXXXXXXX" value="<?= htmlspecialchars($codigo) ?>" required>
            <div class="form-text text-center mt-2">Ingrese el código tal cual aparece en su constancia.</div>
        </div>
        <button type="submit" class="btn-validate">
            <i class="fa-solid fa-magnifying-glass me-2"></i> VERIFICAR AHORA
        </button>
    </form>

    <?php if ($valid): ?>
        <div class="result-box result-success text-center animate__animated animate__fadeIn">
            <i class="fa-solid fa-circle-check text-success fa-3x mb-3"></i>
            <h5 class="fw-bold text-success">✅ DOCUMENTO VÁLIDO</h5>
            <hr>
            <div class="text-start">
                <p class="mb-2"><strong>Personal:</strong> <?= htmlspecialchars($valid['grado'] . ' ' . $valid['apellidos'] . ' ' . $valid['nombres']) ?></p>
                <p class="mb-2"><strong>DNI:</strong> <?= htmlspecialchars($valid['dni']) ?></p>
                <p class="mb-2"><strong>SubUnidad:</strong> <?= htmlspecialchars($valid['sub_unidad_especifica']) ?></p>
                <p class="mb-0"><strong>Fecha Registro:</strong> <?= date('d/m/Y', strtotime($valid['fecha_registro'])) ?></p>
            </div>
        </div>
    <?php elseif ($error): ?>
        <div class="result-box result-error text-center animate__animated animate__shakeX">
            <i class="fa-solid fa-circle-xmark text-danger fa-3x mb-3"></i>
            <h5 class="fw-bold text-danger">❌ DOCUMENTO NO VÁLIDO</h5>
            <p class="text-muted small mb-0"><?= $error ?></p>
        </div>
    <?php endif; ?>

    <div class="text-center mt-4 text-muted small">
        OFICINA DE PROGRAMACIÓN - UE009 - VII DIRTEPOL LIMA
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
