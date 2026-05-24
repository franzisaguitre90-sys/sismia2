<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) { header("Location: /SMIA2/dashboard/".currentUserRol().".php"); exit; }

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = sanitize($_POST['nombre'] ?? '');
    $apellido = sanitize($_POST['apellido'] ?? '');
    $email    = sanitize($_POST['email'] ?? '');
    $ci       = sanitize($_POST['ci'] ?? '');
    $empresa  = sanitize($_POST['empresa'] ?? '');
    $telefono = sanitize($_POST['telefono'] ?? '');
    $pass     = $_POST['password'] ?? '';
    $pass2    = $_POST['password2'] ?? '';

    if (!$nombre || !$apellido || !$email || !$pass) {
        $error = 'Complete todos los campos obligatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Formato de email inválido.';
    } elseif (strlen($pass) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($pass !== $pass2) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        try {
            // Verificar email único
            $check = db()->prepare("SELECT COUNT(*) FROM usuarios WHERE email=?");
            $check->execute([$email]);
            if ($check->fetchColumn() > 0) {
                $error = 'Ya existe una cuenta con ese correo electrónico.';
            } else {
                $rolId    = db()->query("SELECT id FROM roles WHERE slug='consultor'")->fetchColumn();
                $username = strtolower($nombre[0] . $apellido) . rand(10,99);
                $hash     = password_hash($pass, PASSWORD_BCRYPT);

                db()->prepare(
                    "INSERT INTO usuarios (nombre,apellido,email,username,password_hash,rol_id,activo,primer_ingreso,ci,empresa,telefono)
                     VALUES (?,?,?,?,?,?,1,0,?,?,?)"
                )->execute([$nombre,$apellido,$email,$username,$hash,$rolId,$ci,$empresa,$telefono]);

                $userId = (int)db()->lastInsertId();
                audit($userId, 'REGISTRO', 'consultor', 'Auto-registro de consultor');

                $success = "¡Registro exitoso! Su nombre de usuario es: <strong>$username</strong>. Ya puede iniciar sesión.";
            }
        } catch (PDOException $e) {
            $error = 'Error al registrar. Intente nuevamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>SMIA2 – Registro de Consultor</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body{background:linear-gradient(135deg,#bf360c 0%,#1b5e20 100%);min-height:100vh;font-family:'Segoe UI',sans-serif;padding:2rem 0}
.card{border:none;border-radius:20px;overflow:hidden;box-shadow:0 24px 80px rgba(0,0,0,.4);max-width:600px;margin:auto}
.reg-header{background:linear-gradient(135deg,#bf360c,#d84315);padding:2rem;text-align:center}
.brand-icon{width:64px;height:64px;background:rgba(255,255,255,.2);border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto .75rem;font-size:1.8rem;color:white}
.form-control{border-radius:10px;border:2px solid #e8eaf6;padding:.7rem 1rem}
.form-control:focus{border-color:#d84315;box-shadow:0 0 0 .2rem rgba(216,67,21,.15)}
.btn-reg{background:linear-gradient(135deg,#bf360c,#d84315);border:none;border-radius:12px;padding:.8rem;font-weight:700}
</style>
</head>
<body>
<div class="container">
<div class="text-center mb-3"><a href="login.php" class="text-white-50 text-decoration-none small"><i class="fas fa-arrow-left me-1"></i>Volver al Login</a></div>
<div class="card">
  <div class="reg-header">
    <div class="brand-icon"><i class="fas fa-user-graduate"></i></div>
    <h4 class="text-white fw-bold mb-1">Registro de Consultor</h4>
    <p class="text-white-50 small mb-0">SMIA2 – Sistema de Registro Ambiental</p>
  </div>
  <div class="card-body p-4">
    <?php if ($success): ?>
    <div class="alert alert-success rounded-3 text-center">
      <i class="fas fa-check-circle fa-2x text-success mb-2 d-block"></i>
      <?= $success ?><br>
      <a href="login.php" class="btn btn-success mt-3 px-4"><i class="fas fa-sign-in-alt me-1"></i>Iniciar Sesión</a>
    </div>
    <?php else: ?>
    <?php if ($error): ?><div class="alert alert-danger rounded-3"><i class="fas fa-exclamation-circle me-2"></i><?= e($error) ?></div><?php endif; ?>
    <form method="POST" novalidate>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
          <input name="nombre" class="form-control" value="<?= e($_POST['nombre']??'') ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Apellido <span class="text-danger">*</span></label>
          <input name="apellido" class="form-control" value="<?= e($_POST['apellido']??'') ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Correo Electrónico <span class="text-danger">*</span></label>
          <input type="email" name="email" class="form-control" value="<?= e($_POST['email']??'') ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Cédula de Identidad</label>
          <input name="ci" class="form-control" value="<?= e($_POST['ci']??'') ?>" placeholder="Número de CI">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Empresa / Consultora</label>
          <input name="empresa" class="form-control" value="<?= e($_POST['empresa']??'') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Teléfono / Celular</label>
          <input name="telefono" class="form-control" value="<?= e($_POST['telefono']??'') ?>" placeholder="+591 xxxxxxx">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Contraseña <span class="text-danger">*</span></label>
          <input type="password" name="password" class="form-control" minlength="8" required>
          <div class="form-text">Mínimo 8 caracteres</div>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Confirmar Contraseña <span class="text-danger">*</span></label>
          <input type="password" name="password2" class="form-control" required>
        </div>
      </div>
      <div class="form-check mt-3 mb-3">
        <input class="form-check-input" type="checkbox" id="terminos" required>
        <label class="form-check-label small" for="terminos">
          Acepto los términos y confirmo que la información proporcionada es verídica conforme a la <strong>Ley 1333</strong>.
        </label>
      </div>
      <div class="d-grid">
        <button type="submit" class="btn btn-danger btn-reg text-white">
          <i class="fas fa-user-plus me-2"></i>Crear Cuenta de Consultor
        </button>
      </div>
    </form>
    <?php endif; ?>
  </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
