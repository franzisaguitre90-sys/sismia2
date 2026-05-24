<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/functions.php';

$msg = '';
$tipo = '';
$step = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'buscar';

    if ($action === 'buscar') {
        $claveUnica = strtoupper(sanitize($_POST['clave_unica'] ?? ''));
        $email      = sanitize($_POST['email'] ?? '');

        if (!$claveUnica && !$email) {
            $msg = 'Ingrese su Clave Única o correo electrónico.';
            $tipo = 'danger';
        } else {
            try {
                if ($claveUnica) {
                    $s = db()->prepare("SELECT id, nombre, apellido, username, clave_unica, rol_id FROM usuarios WHERE clave_unica=? AND activo=1");
                    $s->execute([$claveUnica]);
                } else {
                    $s = db()->prepare("SELECT id, nombre, apellido, username, clave_unica, rol_id FROM usuarios WHERE email=? AND activo=1");
                    $s->execute([$email]);
                }
                $user = $s->fetch();
                if ($user) {
                    if ($user['clave_unica']) {
                        $msg  = "Su Clave Única de acceso es: <strong class='fs-5'>{$user['clave_unica']}</strong><br>Su nombre de usuario es: <strong>{$user['username']}</strong>";
                        $tipo = 'success';
                        $step = 2;
                    } else {
                        $msg  = 'Su cuenta aún no tiene Clave Única asignada. La Clave Única es generada por la Secretaria una vez finalizado su primer trámite.';
                        $tipo = 'warning';
                    }
                } else {
                    $msg  = 'No se encontró ninguna cuenta con esos datos.';
                    $tipo = 'danger';
                }
            } catch (PDOException $e) {
                $msg  = 'Error de conexión.';
                $tipo = 'danger';
            }
        }
    }

    if ($action === 'cambiar') {
        $userId  = (int)($_POST['uid'] ?? 0);
        $pass    = $_POST['new_pass'] ?? '';
        $pass2   = $_POST['new_pass2'] ?? '';
        if (strlen($pass) < 8) {
            $msg = 'La contraseña debe tener al menos 8 caracteres.';
            $tipo = 'danger';
        } elseif ($pass !== $pass2) {
            $msg = 'Las contraseñas no coinciden.';
            $tipo = 'danger';
        } else {
            db()->prepare("UPDATE usuarios SET password_hash=? WHERE id=?")->execute([password_hash($pass, PASSWORD_BCRYPT), $userId]);
            $msg  = '¡Contraseña actualizada! <a href="/SMIA2/login.php">Iniciar sesión</a>';
            $tipo = 'success';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>SMIA2 – Recuperar Acceso</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body{background:linear-gradient(135deg,#4a148c,#1a237e);min-height:100vh;display:flex;align-items:center;font-family:'Segoe UI',sans-serif}
.card{border:none;border-radius:20px;max-width:480px;width:100%;margin:auto;box-shadow:0 24px 80px rgba(0,0,0,.4)}
.rc-header{background:linear-gradient(135deg,#4a148c,#6a1b9a);padding:2rem;text-align:center}
.form-control{border-radius:10px;border:2px solid #e8eaf6}
.form-control:focus{border-color:#7b1fa2;box-shadow:0 0 0 .2rem rgba(123,31,162,.15)}
</style>
</head>
<body>
<div class="container">
<div class="text-center mb-3"><a href="login.php" class="text-white-50 text-decoration-none small"><i class="fas fa-arrow-left me-1"></i>Volver al Login</a></div>
<div class="card">
  <div class="rc-header">
    <i class="fas fa-key fa-2x text-warning mb-2 d-block"></i>
    <h4 class="text-white fw-bold mb-1">Recuperar Acceso</h4>
    <p class="text-white-50 small mb-0">SMIA2 – Sistema de Registro Ambiental</p>
  </div>
  <div class="card-body p-4">
    <p class="text-muted small mb-4">Use su <strong>Clave Única</strong> asignada por la Secretaria para recuperar su acceso, o ingrese su correo electrónico.</p>

    <?php if ($msg): ?>
    <div class="alert alert-<?= $tipo ?> rounded-3"><?= $msg ?></div>
    <?php endif; ?>

    <?php if ($step < 2): ?>
    <form method="POST">
      <input type="hidden" name="action" value="buscar">
      <div class="mb-3">
        <label class="form-label fw-semibold">Clave Única</label>
        <input name="clave_unica" class="form-control" placeholder="Ej: SMIA-A1B2C3D4" style="text-transform:uppercase">
        <div class="form-text">La Clave Única es otorgada por la Secretaria al finalizar su primer trámite.</div>
      </div>
      <div class="text-center my-2 text-muted">— o —</div>
      <div class="mb-4">
        <label class="form-label fw-semibold">Correo Electrónico</label>
        <input type="email" name="email" class="form-control" placeholder="correo@ejemplo.com">
      </div>
      <div class="d-grid">
        <button type="submit" class="btn btn-primary py-2 fw-bold"><i class="fas fa-search me-2"></i>Buscar mi cuenta</button>
      </div>
    </form>
    <?php endif; ?>

    <?php if ($tipo === 'success' && $step === 2): ?>
    <hr>
    <p class="text-muted small">¿Desea cambiar su contraseña?</p>
    <form method="POST">
      <input type="hidden" name="action" value="cambiar">
      <input type="hidden" name="uid" value="<?= $user['id'] ?>">
      <div class="mb-3">
        <label class="form-label fw-semibold">Nueva Contraseña</label>
        <input type="password" name="new_pass" class="form-control" minlength="8" required>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">Confirmar Contraseña</label>
        <input type="password" name="new_pass2" class="form-control" required>
      </div>
      <div class="d-grid">
        <button type="submit" class="btn btn-success py-2 fw-bold"><i class="fas fa-save me-2"></i>Cambiar Contraseña</button>
      </div>
    </form>
    <?php endif; ?>
  </div>
</div>
</div>
</body>
</html>
