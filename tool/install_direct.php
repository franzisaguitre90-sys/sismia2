<?php
// Script de instalación directa (uso único)
set_time_limit(120);
error_reporting(E_ALL);

$sqlFile  = __DIR__ . '/../smiabasedata.sql';
$lockFile = __DIR__ . '/../config/.installed';
$dbConfig = __DIR__ . '/../config/database.php';

$msg = [];

try {
    // Conectar sin base de datos
    $pdo = new PDO('mysql:host=localhost;port=3306;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $msg[] = '✅ Conexión MySQL exitosa (root sin contraseña)';

    // Ejecutar SQL
    $sql = file_get_contents($sqlFile);
    $queries = array_filter(array_map('trim', explode(";\n", $sql)));
    $ok = 0; $skip = 0;
    foreach ($queries as $q) {
        if (empty($q) || str_starts_with($q, '--')) { $skip++; continue; }
        try { $pdo->exec($q); $ok++; } catch (PDOException $e) { $skip++; }
    }
    $msg[] = "✅ SQL ejecutado: $ok sentencias, $skip omitidas";

    $pdo->exec("USE smiabasedata");

    // Eliminar admin previo si existe
    $pdo->exec("DELETE FROM usuarios WHERE username='admin'");

    // Crear admin con bcrypt de 'admin'
    $hash  = password_hash('admin', PASSWORD_BCRYPT);
    $clave = 'SMIA-ADMIN-' . strtoupper(bin2hex(random_bytes(3)));
    $pdo->prepare(
        "INSERT INTO usuarios (nombre,apellido,email,username,password_hash,clave_unica,rol_id,activo,primer_ingreso,cargo)
         VALUES ('Administrador','Sistema','admin@smia2.gob.bo','admin',?,?,1,1,0,'Administrador del Sistema')"
    )->execute([$hash, $clave]);
    $msg[] = "✅ Usuario admin creado. Clave única: <strong>$clave</strong>";

    // Reescribir config/database.php con root
    $cfg = <<<'PHP'
<?php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'smiabasedata');
define('APP_NAME', 'SMIA2');
define('APP_VERSION', '2.0.0');
define('APP_BASE', '/SMIA2');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 20 * 1024 * 1024); // 20MB

class Database {
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct() {
        $dsn = "mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";charset=utf8mb4";
        $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->pdo;
    }
}

function db(): PDO {
    return Database::getInstance()->getConnection();
}
PHP;
    file_put_contents($dbConfig, $cfg);
    $msg[] = '✅ config/database.php actualizado (root sin contraseña)';

    // Crear lock file
    file_put_contents($lockFile, date('Y-m-d H:i:s') . ' - instalado via install_direct.php');
    $msg[] = '✅ Lock file creado';

    $success = true;
} catch (PDOException $e) {
    $msg[] = '❌ Error: ' . $e->getMessage();
    $success = false;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>SMIA2 – Instalación directa</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:640px">
  <div class="card shadow-sm">
    <div class="card-header bg-<?= $success?'success':'danger' ?> text-white">
      <h5 class="mb-0"><?= $success?'✅ Instalación completada':'❌ Error de instalación' ?></h5>
    </div>
    <div class="card-body">
      <?php foreach ($msg as $m): ?>
      <p class="mb-1 small"><?= $m ?></p>
      <?php endforeach; ?>
      <?php if ($success): ?>
      <hr>
      <div class="alert alert-info mt-3">
        <strong>Credenciales de acceso:</strong><br>
        Usuario: <code>admin</code> &nbsp;|&nbsp; Contraseña: <code>admin</code>
      </div>
      <a href="/SMIA2/login.php" class="btn btn-primary w-100 mt-2">
        <i class="me-2">→</i> Ir al Login
      </a>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
