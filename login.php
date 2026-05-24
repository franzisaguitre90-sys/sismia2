<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';

if (isLoggedIn()) {
    header("Location: /SMIA2/dashboard/" . currentUserRol() . ".php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Ingrese su usuario y contraseña.';
    } else {
        try {
            $stmt = db()->prepare(
                "SELECT u.*, r.slug as rol, r.nombre as rol_nombre, r.color_primary, r.color_secondary, r.color_accent
                 FROM usuarios u JOIN roles r ON u.rol_id = r.id
                 WHERE (u.username=? OR u.email=?) AND u.activo=1"
            );
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Invalidar sesión anterior (sesión única por usuario)
                db()->prepare("UPDATE sesiones SET activa=0 WHERE usuario_id=?")->execute([$user['id']]);

                // Crear nueva sesión
                $token = bin2hex(random_bytes(32));
                $exp   = date('Y-m-d H:i:s', strtotime('+8 hours'));
                db()->prepare(
                    "INSERT INTO sesiones (usuario_id,token,ip_address,user_agent,fecha_expiracion) VALUES (?,?,?,?,?)"
                )->execute([$user['id'], $token, $_SERVER['REMOTE_ADDR'], substr($_SERVER['HTTP_USER_AGENT']??'',0,255), $exp]);

                // Actualizar último login
                db()->prepare("UPDATE usuarios SET ultimo_login=NOW() WHERE id=?")->execute([$user['id']]);

                // Registrar sesión
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['rol']        = $user['rol'];
                $_SESSION['token']      = $token;
                $_SESSION['user']       = [
                    'id'             => $user['id'],
                    'nombre'         => $user['nombre'],
                    'apellido'       => $user['apellido'],
                    'email'          => $user['email'],
                    'username'       => $user['username'],
                    'rol'            => $user['rol'],
                    'rol_nombre'     => $user['rol_nombre'],
                    'color_primary'  => $user['color_primary'],
                    'color_secondary'=> $user['color_secondary'],
                    'color_accent'   => $user['color_accent'],
                    'primer_ingreso' => $user['primer_ingreso'],
                ];

                header("Location: /SMIA2/dashboard/" . $user['rol'] . ".php");
                exit;
            } else {
                $error = 'Usuario o contraseña incorrectos.';
            }
        } catch (PDOException $e) {
            $error = 'Error de conexión. Verifique la configuración de la base de datos.';
        }
    }
}

function sanitize(string $s): string { return trim(strip_tags($s)); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>SMIA2 – Ingresar al Sistema</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body{background:linear-gradient(135deg,#0d1b6e 0%,#1b5e20 100%);min-height:100vh;display:flex;align-items:center;font-family:'Segoe UI',sans-serif}
.login-wrap{max-width:460px;width:100%;margin:auto}
.card{border:none;border-radius:20px;overflow:hidden;box-shadow:0 24px 80px rgba(0,0,0,.4)}
.login-header{background:linear-gradient(135deg,#1a237e,#283593);padding:2.5rem 2rem;text-align:center}
.login-body{padding:2.5rem}
.brand-icon{width:72px;height:72px;background:linear-gradient(135deg,#f9a825,#ff6f00);border-radius:20px;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:2rem;color:white}
.role-badge{display:inline-flex;align-items:center;gap:6px;padding:.35rem .9rem;border-radius:20px;font-size:.78rem;font-weight:600;background:rgba(255,255,255,.12);color:rgba(255,255,255,.9);margin:.15rem}
.form-control{border-radius:12px;padding:.75rem 1rem;border:2px solid #e8eaf6}
.form-control:focus{border-color:#3f51b5;box-shadow:0 0 0 .2rem rgba(63,81,181,.15)}
.btn-login{background:linear-gradient(135deg,#1a237e,#283593);border:none;border-radius:12px;padding:.85rem;font-size:1rem;font-weight:700;letter-spacing:.5px}
.btn-login:hover{background:linear-gradient(135deg,#283593,#3949ab);transform:translateY(-1px);box-shadow:0 8px 20px rgba(26,35,126,.4)}
.input-icon{position:relative}
.input-icon i{position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:#9e9e9e}
.input-icon input{padding-left:2.75rem}
</style>
</head>
<body>
<div class="container">
<div class="login-wrap">

  <!-- Back to portal -->
  <div class="text-center mb-3">
    <a href="index.php" class="text-white-50 small text-decoration-none">
      <i class="fas fa-arrow-left me-1"></i> Volver al Portal Informativo
    </a>
  </div>

  <div class="card">
    <div class="login-header">
      <div class="brand-icon"><i class="fas fa-leaf"></i></div>
      <h4 class="text-white fw-bold mb-1">SMIA<span class="text-warning">2</span></h4>
      <p class="text-white-50 small mb-3">Sistema de Monitoreo e Información Ambiental</p>
      <div>
        <span class="role-badge"><i class="fas fa-user-tie"></i>Director</span>
        <span class="role-badge"><i class="fas fa-hard-hat"></i>Técnico</span>
        <span class="role-badge"><i class="fas fa-user-graduate"></i>Consultor</span>
        <span class="role-badge"><i class="fas fa-user-clock"></i>Secretaria</span>
      </div>
    </div>

    <div class="login-body">
      <h5 class="fw-bold mb-1">Iniciar Sesión</h5>
      <p class="text-muted small mb-4">Ingrese con su usuario y contraseña asignados</p>

      <?php if ($error): ?>
      <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php endif; ?>

      <?php if (!empty($_GET['error'])): ?>
      <div class="alert alert-warning rounded-3">
        <i class="fas fa-lock me-2"></i>Acceso denegado para su rol.
      </div>
      <?php endif; ?>

      <form method="POST" novalidate>
        <div class="mb-3">
          <label class="form-label fw-semibold">Usuario o Email</label>
          <div class="input-icon">
            <i class="fas fa-user"></i>
            <input type="text" name="username" class="form-control" placeholder="Usuario o correo electrónico"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autocomplete="username">
          </div>
        </div>
        <div class="mb-4">
          <label class="form-label fw-semibold d-flex justify-content-between">
            Contraseña
            <a href="recuperar_clave.php" class="text-decoration-none small">¿Olvidó su clave?</a>
          </label>
          <div class="input-icon">
            <i class="fas fa-lock"></i>
            <input type="password" name="password" id="passField" class="form-control" placeholder="Contraseña" required autocomplete="current-password">
            <i class="fas fa-eye position-absolute" style="right:1rem;left:auto;cursor:pointer" id="togglePass"></i>
          </div>
        </div>
        <div class="d-grid mb-3">
          <button type="submit" class="btn btn-primary btn-login text-white">
            <i class="fas fa-sign-in-alt me-2"></i>Ingresar al Sistema
          </button>
        </div>
      </form>

      <hr class="my-3">
      <div class="text-center">
        <p class="text-muted small mb-2">¿Es consultor y no tiene cuenta?</p>
        <a href="registro.php" class="btn btn-outline-primary rounded-pill px-4">
          <i class="fas fa-user-plus me-1"></i>Registrarse como Consultor
        </a>
      </div>
    </div>
  </div>

  <p class="text-center text-white-50 small mt-3">
    Ley 1333 – Control Ambiental · <?= date('Y') ?>
  </p>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('togglePass').addEventListener('click', function(){
  const f = document.getElementById('passField');
  f.type = f.type==='password'?'text':'password';
  this.classList.toggle('fa-eye-slash');
});
</script>
</body>
</html>
