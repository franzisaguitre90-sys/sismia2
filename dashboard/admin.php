<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

$uid       = currentUserId();
$pageTitle = 'Panel de Administración';

// Estadísticas generales
$stats = db()->query("
    SELECT
      (SELECT COUNT(*) FROM usuarios WHERE activo=1) total_usuarios,
      (SELECT COUNT(*) FROM hojas_de_ruta) total_hdr,
      (SELECT COUNT(*) FROM hojas_de_ruta WHERE estado='finalizado') finalizados,
      (SELECT COUNT(*) FROM habilitaciones_ambientales) habilitaciones,
      (SELECT COUNT(*) FROM usuarios WHERE rol_id=(SELECT id FROM roles WHERE slug='consultor') AND activo=1) consultores,
      (SELECT COUNT(*) FROM usuarios WHERE rol_id=(SELECT id FROM roles WHERE slug='tecnico') AND activo=1) tecnicos,
      (SELECT COUNT(*) FROM auditoria WHERE DATE(fecha)=CURDATE()) acciones_hoy,
      (SELECT COUNT(*) FROM sesiones WHERE activa=1) sesiones_activas
")->fetch();

// Usuarios por rol
$porRol = db()->query("
    SELECT r.nombre rol, r.color_primary color, COUNT(u.id) total, SUM(u.activo) activos
    FROM roles r
    LEFT JOIN usuarios u ON u.rol_id=r.id
    WHERE r.slug != 'admin'
    GROUP BY r.id ORDER BY r.nombre
")->fetchAll();

// Últimas acciones de auditoría
$auditoria = db()->query("
    SELECT a.*, CONCAT(u.nombre,' ',u.apellido) usuario, u.rol_id
    FROM auditoria a
    JOIN usuarios u ON a.usuario_id=u.id
    ORDER BY a.fecha DESC LIMIT 20
")->fetchAll();

// Sesiones activas
$sesiones = db()->query("
    SELECT s.*, CONCAT(u.nombre,' ',u.apellido) usuario, r.nombre rol, r.color_primary color
    FROM sesiones s
    JOIN usuarios u ON s.usuario_id=u.id
    JOIN roles r ON u.rol_id=r.id
    WHERE s.activa=1
    ORDER BY s.fecha_inicio DESC
")->fetchAll();

// Hojas de ruta recientes
$hojas = db()->query("
    SELECT hdr.*, CONCAT(uc.nombre,' ',uc.apellido) consultor,
           CONCAT(ut.nombre,' ',ut.apellido) tecnico, rai.razon_social
    FROM hojas_de_ruta hdr
    JOIN usuarios uc ON hdr.consultor_id=uc.id
    LEFT JOIN usuarios ut ON hdr.tecnico_id=ut.id
    LEFT JOIN formularios_rai rai ON rai.hoja_ruta_id=hdr.id
    ORDER BY hdr.fecha_ingreso DESC LIMIT 10
")->fetchAll();

require_once __DIR__ . '/../includes/dashboard_header.php';
?>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="stat-card text-center p-3">
      <div class="stat-value text-primary"><?= $stats['total_usuarios'] ?></div>
      <div class="stat-label">Usuarios Activos</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card text-center p-3">
      <div class="stat-value text-success"><?= $stats['total_hdr'] ?></div>
      <div class="stat-label">Hojas de Ruta</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card text-center p-3">
      <div class="stat-value text-warning"><?= $stats['habilitaciones'] ?></div>
      <div class="stat-label">Habilitaciones</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card text-center p-3">
      <div class="stat-value text-danger"><?= $stats['sesiones_activas'] ?></div>
      <div class="stat-label">Sesiones Activas</div>
    </div>
  </div>
</div>

<div class="row g-4">

  <!-- Columna izquierda -->
  <div class="col-lg-4">

    <!-- Usuarios por rol -->
    <div class="table-card p-4 mb-4">
      <h6 class="fw-bold mb-3"><i class="fas fa-users me-2"></i>Usuarios por Rol</h6>
      <?php foreach ($porRol as $r): ?>
      <div class="d-flex align-items-center gap-3 mb-3">
        <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
             style="width:40px;height:40px;background:<?= e($r['color']) ?>;flex-shrink:0;font-size:.8rem">
          <?= strtoupper(substr($r['rol'],0,2)) ?>
        </div>
        <div class="flex-1">
          <div class="fw-semibold small"><?= e($r['rol']) ?></div>
          <div class="text-muted" style="font-size:.75rem"><?= $r['activos'] ?> activos / <?= $r['total'] ?> total</div>
        </div>
        <span class="badge bg-light text-dark border"><?= $r['total'] ?></span>
      </div>
      <?php endforeach; ?>
      <div class="d-grid mt-3">
        <a href="/SMIA2/modules/director/crear_tecnico.php" class="btn btn-sm btn-primary">
          <i class="fas fa-user-plus me-1"></i>Gestionar Usuarios
        </a>
      </div>
    </div>

    <!-- Acciones rápidas -->
    <div class="table-card p-4 mb-4">
      <h6 class="fw-bold mb-3"><i class="fas fa-bolt me-2 text-warning"></i>Acciones Rápidas</h6>
      <div class="d-grid gap-2">
        <a href="/SMIA2/modules/director/crear_tecnico.php" class="btn btn-outline-primary btn-sm">
          <i class="fas fa-user-cog me-2"></i>Administrar Usuarios
        </a>
        <a href="/SMIA2/modules/director/hojas_ruta.php" class="btn btn-outline-success btn-sm">
          <i class="fas fa-folder-open me-2"></i>Ver Hojas de Ruta
        </a>
        <a href="/SMIA2/modules/director/kpi.php" class="btn btn-outline-info btn-sm">
          <i class="fas fa-chart-bar me-2"></i>KPI Técnicos
        </a>
        <a href="/SMIA2/modules/secretaria/verificar.php" class="btn btn-outline-secondary btn-sm">
          <i class="fas fa-certificate me-2"></i>Verificar Habilitaciones
        </a>
      </div>
    </div>

    <!-- Sesiones activas -->
    <div class="table-card p-4">
      <h6 class="fw-bold mb-3"><i class="fas fa-signal me-2 text-success"></i>Sesiones Activas (<?= count($sesiones) ?>)</h6>
      <?php if (empty($sesiones)): ?>
      <p class="text-muted small text-center">Sin sesiones activas</p>
      <?php else: ?>
      <?php foreach ($sesiones as $s): ?>
      <div class="d-flex align-items-center gap-2 mb-2 p-2 bg-light rounded-3">
        <span class="badge rounded-pill" style="background:<?= e($s['color']) ?>;font-size:.65rem">
          <?= e(substr($s['rol'],0,3)) ?>
        </span>
        <div class="flex-1" style="min-width:0">
          <div class="fw-semibold small text-truncate"><?= e($s['usuario']) ?></div>
          <div class="text-muted" style="font-size:.7rem"><?= fdate($s['fecha_inicio'],'d/m H:i') ?></div>
        </div>
        <i class="fas fa-circle text-success" style="font-size:.5rem"></i>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>

  <!-- Columna central/derecha -->
  <div class="col-lg-8">

    <!-- Estadísticas del sistema -->
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="stat-card text-center p-3">
          <div class="stat-value" style="color:var(--c-pri)"><?= $stats['consultores'] ?></div>
          <div class="stat-label">Consultores</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card text-center p-3">
          <div class="stat-value text-success"><?= $stats['tecnicos'] ?></div>
          <div class="stat-label">Técnicos</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card text-center p-3">
          <div class="stat-value text-primary"><?= $stats['finalizados'] ?></div>
          <div class="stat-label">HDR Finalizadas</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card text-center p-3">
          <div class="stat-value text-warning"><?= $stats['acciones_hoy'] ?></div>
          <div class="stat-label">Acciones Hoy</div>
        </div>
      </div>
    </div>

    <!-- Hojas de ruta recientes -->
    <div class="table-card mb-4">
      <div class="table-header">
        <h6 class="fw-bold mb-0"><i class="fas fa-folder me-2"></i>Hojas de Ruta Recientes</h6>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0 small">
          <thead><tr>
            <th>Código</th><th>Empresa</th><th>Estado</th><th>Consultor</th><th>Técnico</th><th>Fecha</th>
          </tr></thead>
          <tbody>
          <?php if (empty($hojas)): ?>
          <tr><td colspan="6" class="text-center text-muted py-3">Sin hojas de ruta</td></tr>
          <?php endif; ?>
          <?php foreach ($hojas as $h): ?>
          <tr>
            <td><code style="color:#6a1b9a;font-size:.8rem"><?= e($h['codigo']) ?></code></td>
            <td class="text-truncate" style="max-width:140px"><?= $h['razon_social'] ? e($h['razon_social']) : '<span class="text-muted">—</span>' ?></td>
            <td><?= estadoBadge($h['estado']) ?></td>
            <td><?= e($h['consultor']) ?></td>
            <td><?= $h['tecnico'] ? e($h['tecnico']) : '<span class="text-muted">—</span>' ?></td>
            <td><?= fdate($h['fecha_ingreso'], 'd/m/Y') ?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Log de auditoría -->
    <div class="table-card">
      <div class="table-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="fas fa-shield-alt me-2 text-danger"></i>Registro de Auditoría</h6>
        <span class="badge bg-danger"><?= $stats['acciones_hoy'] ?> hoy</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0 small">
          <thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Módulo</th><th>Detalle</th></tr></thead>
          <tbody>
          <?php if (empty($auditoria)): ?>
          <tr><td colspan="5" class="text-center text-muted py-3">Sin registros</td></tr>
          <?php endif; ?>
          <?php foreach ($auditoria as $a): ?>
          <tr>
            <td class="text-muted" style="white-space:nowrap"><?= fdate($a['fecha'], 'd/m H:i') ?></td>
            <td><?= e($a['usuario']) ?></td>
            <td><span class="badge bg-light text-dark border" style="font-size:.65rem"><?= e($a['accion']) ?></span></td>
            <td class="text-muted"><?= e($a['modulo']) ?></td>
            <td class="text-truncate text-muted" style="max-width:200px"><?= e($a['descripcion']) ?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
