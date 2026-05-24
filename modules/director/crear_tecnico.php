<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole(['director','admin']);

$uid       = currentUserId();
$pageTitle = 'Gestión de Usuarios';
$success = '';
$error   = '';

// Crear técnico / usuario
$nuevoUsuario = null; // Para mostrar tarjeta de credenciales
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['accion']) && $_POST['accion']==='crear') {
    $nombre   = sanitize($_POST['nombre'] ?? '');
    $apellido = sanitize($_POST['apellido'] ?? '');
    $email    = sanitize($_POST['email'] ?? '');
    $rolSlug  = sanitize($_POST['rol'] ?? 'tecnico');
    $pass     = $_POST['password'] ?? '';
    $ci       = sanitize($_POST['ci'] ?? '');
    $cargo    = sanitize($_POST['cargo'] ?? '');
    // Permitir username personalizado o auto-generar
    $usernamePersonal = sanitize($_POST['username_custom'] ?? '');

    if (!$nombre||!$apellido||!$email||!$pass) {
        $error = 'Complete todos los campos obligatorios.';
    } elseif (strlen($pass) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } else {
        $sRol = db()->prepare("SELECT id FROM roles WHERE slug=?");
        $sRol->execute([$rolSlug]);
        $rolId = $sRol->fetchColumn();

        // Username: personalizado o auto-generado
        $username = $usernamePersonal
            ? strtolower(preg_replace('/\s+/','',$usernamePersonal))
            : strtolower($nombre[0].$apellido).rand(10,99);

        $hash  = password_hash($pass, PASSWORD_BCRYPT);
        $clave = generateClaveUnica();
        try {
            db()->prepare(
                "INSERT INTO usuarios (nombre,apellido,email,username,password_hash,clave_unica,rol_id,activo,primer_ingreso,ci,cargo,creado_por)
                 VALUES (?,?,?,?,?,?,?,1,0,?,?,?)"
            )->execute([$nombre,$apellido,$email,$username,$hash,$clave,$rolId,$ci,$cargo,$uid]);
            audit($uid,'CREAR_USUARIO','director',"Creado usuario $username con rol $rolSlug");
            // Guardar para mostrar tarjeta de credenciales
            $nuevoUsuario = [
                'nombre'   => "$nombre $apellido",
                'username' => $username,
                'email'    => $email,
                'password' => $pass,
                'clave'    => $clave,
                'rol'      => $rolSlug,
            ];
            $success = 'ok';
        } catch(PDOException $e) {
            $error = str_contains($e->getMessage(),'Duplicate') ? 'El email o username ya está registrado.' : 'Error al crear usuario: '.$e->getMessage();
        }
    }
}

// Cambiar contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['accion']) && $_POST['accion']==='cambiar_pass') {
    $uid2 = (int)$_POST['uid'];
    $np   = $_POST['new_pass'] ?? '';
    if (strlen($np) >= 8) {
        db()->prepare("UPDATE usuarios SET password_hash=?, primer_ingreso=1 WHERE id=?")->execute([password_hash($np,PASSWORD_BCRYPT),$uid2]);
        $success = 'Contraseña actualizada correctamente.';
    } else $error = 'La nueva contraseña debe tener al menos 8 caracteres.';
}

// Toggle activo
if (isset($_GET['toggle'])) {
    $tid = (int)$_GET['toggle'];
    db()->prepare("UPDATE usuarios SET activo = NOT activo WHERE id=? AND rol_id!=(SELECT id FROM roles WHERE slug='admin')")->execute([$tid]);
    header("Location: /SMIA2/modules/director/crear_tecnico.php?ok=1");
    exit;
}

// Lista de usuarios (todos excepto admin y el propio director)
$usuarios = db()->query("
    SELECT u.*, r.nombre rol_nombre, r.slug rol_slug, r.color_primary
    FROM usuarios u JOIN roles r ON u.rol_id=r.id
    WHERE r.slug!='admin' ORDER BY r.orden, u.nombre
")->fetchAll();

require_once __DIR__ . '/../../includes/dashboard_header.php';
?>

<?php if (!empty($_GET['ok'])): ?><div class="alert alert-success rounded-3 mb-3"><i class="fas fa-check-circle me-2"></i>Operación realizada correctamente.</div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger rounded-3 mb-3"><i class="fas fa-exclamation-circle me-2"></i><?= e($error) ?></div><?php endif; ?>

<?php if ($success === 'ok' && $nuevoUsuario): ?>
<div class="alert alert-success rounded-3 mb-3 p-0 overflow-hidden border-0 shadow">
  <div class="px-4 py-3 bg-success text-white d-flex align-items-center gap-2">
    <i class="fas fa-check-circle fa-lg"></i>
    <strong>Usuario creado exitosamente — Entregue estas credenciales al usuario</strong>
  </div>
  <div class="p-4 bg-white">
    <div class="row g-3 align-items-center">
      <div class="col-md-8">
        <table class="table table-bordered mb-0 small">
          <tr class="table-light">
            <th width="40%"><i class="fas fa-user me-1 text-primary"></i>Nombre completo</th>
            <td class="fw-bold"><?= e($nuevoUsuario['nombre']) ?></td>
          </tr>
          <tr>
            <th class="table-light"><i class="fas fa-at me-1 text-success"></i>Usuario de ingreso</th>
            <td><code class="fs-6 fw-bold text-success"><?= e($nuevoUsuario['username']) ?></code>
              <span class="badge bg-success ms-2">Usar esto para entrar</span></td>
          </tr>
          <tr class="table-light">
            <th><i class="fas fa-envelope me-1 text-info"></i>Email (también sirve)</th>
            <td><code><?= e($nuevoUsuario['email']) ?></code></td>
          </tr>
          <tr>
            <th class="table-light"><i class="fas fa-lock me-1 text-danger"></i>Contraseña inicial</th>
            <td><code class="fs-6 fw-bold text-danger"><?= e($nuevoUsuario['password']) ?></code>
              <span class="badge bg-danger ms-2">Cambiar al primer ingreso</span></td>
          </tr>
          <tr class="table-light">
            <th><i class="fas fa-id-card me-1 text-warning"></i>Clave Única (recuperación)</th>
            <td><code class="text-muted"><?= e($nuevoUsuario['clave']) ?></code>
              <div class="text-muted" style="font-size:.72rem">Solo para recuperar acceso, NO es la contraseña de login</div></td>
          </tr>
          <tr>
            <th class="table-light"><i class="fas fa-tag me-1"></i>Rol asignado</th>
            <td><?= ucfirst(e($nuevoUsuario['rol'])) ?></td>
          </tr>
        </table>
      </div>
      <div class="col-md-4 text-center">
        <div class="p-3 bg-light rounded-3 border">
          <i class="fas fa-sign-in-alt fa-2x text-primary mb-2 d-block"></i>
          <div class="small fw-bold mb-1">URL de acceso</div>
          <code class="small">http://localhost/SMIA2/login.php</code>
          <hr class="my-2">
          <div class="small text-muted">Ingresar con:</div>
          <div class="fw-bold"><?= e($nuevoUsuario['username']) ?></div>
          <div class="fw-bold text-danger"><?= e($nuevoUsuario['password']) ?></div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="row g-4">
  <!-- Formulario de creación -->
  <div class="col-lg-4">
    <div class="table-card p-4">
      <h5 class="fw-bold mb-4"><i class="fas fa-user-plus me-2 text-primary"></i>Crear Nuevo Usuario</h5>
      <form method="POST">
        <input type="hidden" name="accion" value="crear">
        <div class="mb-3">
          <label class="form-label fw-semibold">Rol <span class="text-danger">*</span></label>
          <select name="rol" class="form-select">
            <option value="tecnico">Técnico</option>
            <option value="director">Director</option>
            <option value="secretaria">Secretaria</option>
          </select>
        </div>
        <div class="row g-2 mb-2">
          <div class="col-6"><label class="form-label fw-semibold">Nombre *</label>
            <input name="nombre" class="form-control" required value="<?= e($_POST['nombre']??'') ?>">
          </div>
          <div class="col-6"><label class="form-label fw-semibold">Apellido *</label>
            <input name="apellido" class="form-control" required value="<?= e($_POST['apellido']??'') ?>">
          </div>
        </div>
        <div class="mb-2"><label class="form-label fw-semibold">Email *</label>
          <input type="email" name="email" class="form-control" required value="<?= e($_POST['email']??'') ?>">
        </div>
        <div class="row g-2 mb-2">
          <div class="col-6"><label class="form-label fw-semibold">C.I.</label>
            <input name="ci" class="form-control" value="<?= e($_POST['ci']??'') ?>">
          </div>
          <div class="col-6"><label class="form-label fw-semibold">Cargo</label>
            <input name="cargo" class="form-control" value="<?= e($_POST['cargo']??'') ?>">
          </div>
        </div>
        <div class="mb-2"><label class="form-label fw-semibold">Username personalizado <small class="text-muted">(opcional)</small></label>
          <input name="username_custom" class="form-control" placeholder="Dejar vacío para auto-generar"
                 pattern="[a-zA-Z0-9_]+" title="Solo letras, números y guión bajo">
          <div class="form-text">Si lo deja vacío se genera automáticamente.</div>
        </div>
        <div class="mb-3"><label class="form-label fw-semibold">Contraseña inicial <span class="text-danger">*</span> <small class="text-muted">(mín. 6 car.)</small></label>
          <div class="input-group">
            <input type="text" name="password" id="passNew" class="form-control" minlength="6" required placeholder="Ej: Smia2026!">
            <button type="button" class="btn btn-outline-secondary" onclick="
              const chars='ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#';
              let p=''; for(let i=0;i<10;i++) p+=chars[Math.floor(Math.random()*chars.length)];
              document.getElementById('passNew').value=p; document.getElementById('passNew').type='text';">
              <i class="fas fa-dice"></i> Generar
            </button>
          </div>
          <div class="form-text">Se mostrará al crear. Entréguele esta contraseña al usuario.</div>
        </div>
        <div class="d-grid">
          <button type="submit" class="btn btn-primary fw-bold"><i class="fas fa-user-plus me-2"></i>Crear Usuario</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Lista de usuarios -->
  <div class="col-lg-8">
    <div class="table-card">
      <div class="table-header">
        <h6 class="fw-bold mb-0"><i class="fas fa-users me-2"></i>Usuarios del Sistema (<?= count($usuarios) ?>)</h6>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead><tr><th>Nombre</th><th>Rol</th><th>Email</th><th>Clave Única</th><th>Estado</th><th>Último login</th><th>Acciones</th></tr></thead>
          <tbody>
          <?php foreach ($usuarios as $u): ?>
          <tr class="<?= !$u['activo']?'table-light opacity-50':'' ?>">
            <td>
              <div class="fw-semibold small"><?= e($u['nombre'].' '.$u['apellido']) ?></div>
              <div class="text-muted" style="font-size:.72rem">@<?= e($u['username']) ?></div>
            </td>
            <td><span class="badge" style="background:<?= $u['color_primary'] ?>"><?= e($u['rol_nombre']) ?></span></td>
            <td class="small"><?= e($u['email']) ?></td>
            <td><code class="small"><?= $u['clave_unica']?e($u['clave_unica']):'—' ?></code></td>
            <td><?= $u['activo']?'<span class="badge bg-success">Activo</span>':'<span class="badge bg-danger">Inactivo</span>' ?></td>
            <td class="small"><?= $u['ultimo_login']?fdate($u['ultimo_login'],'d/m/Y H:i'):'Nunca' ?></td>
            <td>
              <div class="d-flex gap-1">
                <!-- Cambiar contraseña -->
                <button class="btn btn-xs btn-outline-primary" style="font-size:.72rem;padding:.2rem .5rem"
                        data-bs-toggle="modal" data-bs-target="#passModal<?= $u['id'] ?>" title="Cambiar contraseña">
                  <i class="fas fa-key"></i>
                </button>
                <!-- Toggle activo -->
                <a href="?toggle=<?= $u['id'] ?>" class="btn btn-xs btn-outline-<?= $u['activo']?'danger':'success' ?>"
                   style="font-size:.72rem;padding:.2rem .5rem"
                   onclick="return confirm('<?= $u['activo']?'¿Desactivar':'¿Activar' ?> este usuario?')" title="<?= $u['activo']?'Desactivar':'Activar' ?>">
                  <i class="fas fa-<?= $u['activo']?'ban':'check' ?>"></i>
                </a>
              </div>
            </td>
          </tr>

          <!-- Modal de cambio de contraseña -->
          <div class="modal fade" id="passModal<?= $u['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-sm">
              <div class="modal-content">
                <div class="modal-header"><h6 class="modal-title">Cambiar contraseña – <?= e($u['nombre']) ?></h6><button class="btn-close" data-bs-dismiss="modal"></button></div>
                <form method="POST">
                  <div class="modal-body">
                    <input type="hidden" name="accion" value="cambiar_pass">
                    <input type="hidden" name="uid" value="<?= $u['id'] ?>">
                    <input type="password" name="new_pass" class="form-control" placeholder="Nueva contraseña (mín. 8)" minlength="8" required>
                  </div>
                  <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-sm">Cambiar</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/dashboard_footer.php'; ?>
