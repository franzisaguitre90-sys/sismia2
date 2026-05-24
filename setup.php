<?php
// ============================================================
// SMIA2 - Script de instalación inicial (ejecutar una sola vez)
// URL: http://localhost/SMIA2/setup.php
// ============================================================

$baseDir = __DIR__;
$sqlFile = $baseDir . '/smiabasedata.sql';
$lockFile = $baseDir . '/config/.installed';

if (file_exists($lockFile)) {
    die('<div style="font-family:sans-serif;padding:40px;background:#e8f5e9;border-radius:8px;max-width:600px;margin:60px auto">
        <h2 style="color:#1b5e20">✅ SMIA2 ya está instalado</h2>
        <p>El sistema ya fue configurado. <a href="/SMIA2/login.php">Ir al Login</a></p>
        <p style="color:#666;font-size:13px">Si necesita reinstalar, elimine el archivo <code>config/.installed</code></p>
    </div>');
}

$errors = [];
$step   = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminNombre   = trim($_POST['admin_nombre'] ?? 'Administrador');
    $adminApellido = trim($_POST['admin_apellido'] ?? 'Sistema');
    $adminEmail    = trim($_POST['admin_email'] ?? 'admin@smia2.gob.bo');
    $adminUser     = trim($_POST['admin_user'] ?? 'admin');
    $adminPass     = $_POST['admin_pass'] ?? '';
    $adminPass2    = $_POST['admin_pass2'] ?? '';
    $dbUser        = trim($_POST['db_user'] ?? 'root');
    $dbPass        = $_POST['db_pass'] ?? '';
    $dbHost        = trim($_POST['db_host'] ?? 'localhost');

    if (strlen($adminPass) < 4) $errors[] = 'La contraseña debe tener al menos 4 caracteres.';
    if ($adminPass !== $adminPass2) $errors[] = 'Las contraseñas no coinciden.';
    if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido.';

    if (empty($errors)) {
        try {
            $pdo = new PDO(
                "mysql:host=$dbHost;port=3306;charset=utf8mb4",
                $dbUser, $dbPass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // Ejecutar SQL
            $sql = file_get_contents($sqlFile);
            foreach (array_filter(array_map('trim', explode(";\n", $sql))) as $query) {
                if (!empty($query) && !str_starts_with($query, '--')) {
                    try { $pdo->exec($query); } catch(Exception $e) { /* ignorar errores DROP */ }
                }
            }
            $pdo->exec("USE smiabasedata");

            // Crear admin
            $hash = password_hash($adminPass, PASSWORD_BCRYPT);
            $clave = 'SMIA-ADMIN-' . strtoupper(bin2hex(random_bytes(3)));
            $pdo->prepare(
                "INSERT INTO usuarios (nombre,apellido,email,username,password_hash,clave_unica,rol_id,activo,primer_ingreso,cargo)
                 VALUES (?,?,?,?,?,?,1,1,0,'Administrador del Sistema')"
            )->execute([$adminNombre,$adminApellido,$adminEmail,$adminUser,$hash,$clave]);

            // Actualizar config con credenciales DB reales
            $dbConfig = "<?php\ndefine('DB_HOST','$dbHost');\ndefine('DB_PORT','3306');\ndefine('DB_USER','$dbUser');\ndefine('DB_PASS','$dbPass');\ndefine('DB_NAME','smiabasedata');\ndefine('APP_NAME','SMIA2');\ndefine('APP_VERSION','2.0.0');\ndefine('APP_BASE','/SMIA2');\ndefine('UPLOAD_DIR',__DIR__.'/../uploads/');\ndefine('MAX_FILE_SIZE', 20 * 1024 * 1024);\n\nclass Database {\n    private static ?\$instance = null;\n    private PDO \$pdo;\n    private function __construct() {\n        \$this->pdo = new PDO('mysql:host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);\n    }\n    public static function getInstance(): Database { if (self::\$instance===null) self::\$instance=new self(); return self::\$instance; }\n    public function getConnection(): PDO { return \$this->pdo; }\n}\nfunction db(): PDO { return Database::getInstance()->getConnection(); }\n";
            file_put_contents($baseDir.'/config/database.php', $dbConfig);

            file_put_contents($lockFile, date('Y-m-d H:i:s'));
            $step = 3;
            $adminClaveUnica = $clave;
        } catch (PDOException $e) {
            $errors[] = 'Error de base de datos: ' . $e->getMessage();
        }
    }
    if (!empty($errors)) $step = 2;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>SMIA2 – Instalación</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body{background:linear-gradient(135deg,#1a237e,#1b5e20);min-height:100vh;display:flex;align-items:center}
.setup-card{border-radius:16px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.4)}
.setup-header{background:linear-gradient(135deg,#1a237e,#283593);color:#fff;padding:2rem}
</style>
</head>
<body>
<div class="container py-5">
<div class="row justify-content-center">
<div class="col-md-7">
<div class="card setup-card">
<div class="setup-header text-center">
  <i class="fas fa-leaf fa-2x mb-2"></i>
  <h3 class="mb-0">SMIA2 – Instalación del Sistema</h3>
  <small class="opacity-75">Dirección de Saneamiento Básico, Recursos Hídricos y Control Ambiental</small>
</div>
<div class="card-body p-4">

<?php if ($step === 3): ?>
<div class="text-center py-3">
  <div class="display-1 text-success mb-3"><i class="fas fa-check-circle"></i></div>
  <h4 class="text-success">¡Instalación Completada!</h4>
  <div class="alert alert-warning mt-3">
    <strong>Guarde esta información:</strong><br>
    Su <strong>Clave Única</strong> de administrador es: <code class="fs-5"><?= e($adminClaveUnica ?? '') ?></code>
  </div>
  <a href="/SMIA2/login.php" class="btn btn-primary btn-lg mt-2">
    <i class="fas fa-sign-in-alt me-2"></i>Ir al Login
  </a>
</div>

<?php elseif ($step >= 1): ?>
<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<form method="POST">
  <h5 class="text-muted mb-3"><i class="fas fa-database me-2"></i>Conexión a MySQL</h5>
  <div class="row g-3 mb-4">
    <div class="col-md-6"><label class="form-label">Host MySQL</label>
      <input name="db_host" class="form-control" value="localhost" required></div>
    <div class="col-md-6"><label class="form-label">Usuario MySQL</label>
      <input name="db_user" class="form-control" value="root" required></div>
    <div class="col-12"><label class="form-label">Contraseña MySQL <small class="text-muted">(dejar vacío si no tiene)</small></label>
      <input name="db_pass" type="password" class="form-control"></div>
  </div>
  <hr>
  <h5 class="text-muted mb-3"><i class="fas fa-user-shield me-2"></i>Cuenta Administrador</h5>
  <div class="row g-3">
    <div class="col-md-6"><label class="form-label">Nombre</label>
      <input name="admin_nombre" class="form-control" value="Administrador" required></div>
    <div class="col-md-6"><label class="form-label">Apellido</label>
      <input name="admin_apellido" class="form-control" value="Sistema" required></div>
    <div class="col-md-6"><label class="form-label">Email</label>
      <input name="admin_email" type="email" class="form-control" value="admin@smia2.gob.bo" required></div>
    <div class="col-md-6"><label class="form-label">Username</label>
      <input name="admin_user" class="form-control" value="admin" required></div>
    <div class="col-md-6"><label class="form-label">Contraseña <span class="text-danger">*</span></label>
      <input name="admin_pass" type="password" class="form-control" minlength="8" required></div>
    <div class="col-md-6"><label class="form-label">Confirmar Contraseña</label>
      <input name="admin_pass2" type="password" class="form-control" required></div>
  </div>
  <div class="d-grid mt-4">
    <button type="submit" class="btn btn-primary btn-lg">
      <i class="fas fa-rocket me-2"></i>Instalar SMIA2
    </button>
  </div>
</form>
<?php endif; ?>

</div>
</div>
</div>
</div>
</div>
</body>
</html>
