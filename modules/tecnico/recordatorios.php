<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('tecnico');

$uid = currentUserId();
$pageTitle = 'Recordatorios';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'crear') {
    $titulo = sanitize($_POST['titulo'] ?? '');
    $desc   = sanitize($_POST['descripcion'] ?? '');
    $fecha  = sanitize($_POST['fecha_recordatorio'] ?? '');
    $rep    = sanitize($_POST['repeticion'] ?? 'no');
    $hojaId = (int)($_POST['hoja_id'] ?? 0);

    if ($titulo && $fecha) {
        db()->prepare("INSERT INTO recordatorios (usuario_id,hoja_ruta_id,titulo,descripcion,fecha_recordatorio,tipo_repeticion) VALUES (?,?,?,?,?,?)")
            ->execute([$uid, $hojaId ?: null, $titulo, $desc ?: null, $fecha, $rep]);
        $success = 'Recordatorio creado.';
    }
}

if (isset($_GET['completar'])) {
    db()->prepare("UPDATE recordatorios SET completado=1,fecha_completado=NOW() WHERE id=? AND usuario_id=?")->execute([(int)$_GET['completar'],$uid]);
    header("Location: /SMIA2/modules/tecnico/recordatorios.php?ok=1");
    exit;
}

if (isset($_GET['eliminar'])) {
    db()->prepare("DELETE FROM recordatorios WHERE id=? AND usuario_id=?")->execute([(int)$_GET['eliminar'],$uid]);
    header("Location: /SMIA2/modules/tecnico/recordatorios.php?ok=1");
    exit;
}

$activos    = db()->prepare("SELECT r.*,hdr.codigo FROM recordatorios r LEFT JOIN hojas_de_ruta hdr ON r.hoja_ruta_id=hdr.id WHERE r.usuario_id=? AND r.completado=0 AND r.activo=1 ORDER BY r.fecha_recordatorio");
$activos->execute([$uid]);
$activos = $activos->fetchAll();

$completados = db()->prepare("SELECT r.*,hdr.codigo FROM recordatorios r LEFT JOIN hojas_de_ruta hdr ON r.hoja_ruta_id=hdr.id WHERE r.usuario_id=? AND r.completado=1 ORDER BY r.fecha_completado DESC LIMIT 10");
$completados->execute([$uid]);
$completados = $completados->fetchAll();

$misHojas = db()->prepare("SELECT id,codigo FROM hojas_de_ruta WHERE tecnico_id=? AND estado NOT IN ('finalizado','rechazado') ORDER BY fecha_ingreso DESC");
$misHojas->execute([$uid]);
$misHojas = $misHojas->fetchAll();

require_once __DIR__ . '/../../includes/dashboard_header.php';
?>

<?php if (!empty($_GET['ok']) || $success): ?><div class="alert alert-success rounded-3 mb-3"><i class="fas fa-check-circle me-2"></i>Operación realizada.</div><?php endif; ?>

<div class="row g-4">
  <!-- Formulario -->
  <div class="col-lg-4">
    <div class="table-card p-4">
      <h5 class="fw-bold mb-4"><i class="fas fa-bell me-2 text-warning"></i>Nuevo Recordatorio</h5>
      <form method="POST">
        <input type="hidden" name="accion" value="crear">
        <div class="mb-3"><label class="form-label fw-semibold">Título <span class="text-danger">*</span></label>
          <input name="titulo" class="form-control" required placeholder="Ej: Revisar documentos HDR-2026-000123">
        </div>
        <div class="mb-3"><label class="form-label fw-semibold">Relacionar con Hoja de Ruta</label>
          <select name="hoja_id" class="form-select">
            <option value="">— Ninguna —</option>
            <?php foreach ($misHojas as $h): ?>
            <option value="<?= $h['id'] ?>"><?= e($h['codigo']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3"><label class="form-label fw-semibold">Fecha y Hora <span class="text-danger">*</span></label>
          <input type="datetime-local" name="fecha_recordatorio" class="form-control" required>
        </div>
        <div class="mb-3"><label class="form-label fw-semibold">Repetición</label>
          <select name="repeticion" class="form-select">
            <option value="no">Sin repetición</option>
            <option value="diario">Diario</option>
            <option value="semanal">Semanal</option>
            <option value="mensual">Mensual</option>
          </select>
        </div>
        <div class="mb-4"><label class="form-label fw-semibold">Descripción</label>
          <textarea name="descripcion" class="form-control" rows="3" placeholder="Detalle del recordatorio..."></textarea>
        </div>
        <div class="d-grid">
          <button type="submit" class="btn btn-warning text-dark fw-bold"><i class="fas fa-plus me-2"></i>Crear Recordatorio</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Recordatorios activos -->
  <div class="col-lg-8">
    <div class="table-card mb-4">
      <div class="table-header"><h6 class="fw-bold mb-0"><i class="fas fa-clock me-2 text-danger"></i>Pendientes (<?= count($activos) ?>)</h6></div>
      <?php if (empty($activos)): ?>
      <div class="text-center py-5 text-muted"><i class="fas fa-check-circle fa-3x text-success mb-3 d-block"></i>Sin recordatorios pendientes</div>
      <?php else: ?>
      <div class="p-3">
        <?php foreach ($activos as $r):
          $dias = diasRestantes($r['fecha_recordatorio']);
          $vencido = $dias !== null && $dias < 0;
          $hoy     = $dias === 0;
          $urgente = $dias !== null && $dias <= 1;
        ?>
        <div class="d-flex gap-3 align-items-start mb-3 p-3 <?= $vencido?'bg-danger-subtle border border-danger':($hoy?'bg-warning-subtle border border-warning':'bg-light') ?> rounded-3">
          <div class="text-center flex-shrink-0" style="min-width:50px">
            <?php if ($vencido): ?><i class="fas fa-exclamation-circle fa-2x text-danger"></i>
            <?php elseif ($hoy): ?><i class="fas fa-bell fa-2x text-warning"></i>
            <?php else: ?><i class="fas fa-clock fa-2x text-muted"></i><?php endif; ?>
            <div style="font-size:.65rem" class="mt-1 text-muted"><?= $vencido?'Vencido':($hoy?'Hoy':"{$dias}d") ?></div>
          </div>
          <div class="flex-1">
            <div class="fw-bold"><?= e($r['titulo']) ?></div>
            <?php if ($r['descripcion']): ?><div class="text-muted small"><?= e($r['descripcion']) ?></div><?php endif; ?>
            <div class="text-muted small mt-1">
              <i class="fas fa-calendar me-1"></i><?= fdate($r['fecha_recordatorio'],'d/m/Y H:i') ?>
              <?php if ($r['codigo']): ?>&nbsp;·&nbsp;<code><?= e($r['codigo']) ?></code><?php endif; ?>
              <?php if ($r['tipo_repeticion']!=='no'): ?>&nbsp;·&nbsp;<span class="badge bg-light text-dark border"><?= ucfirst($r['tipo_repeticion']) ?></span><?php endif; ?>
            </div>
          </div>
          <div class="d-flex flex-column gap-1 flex-shrink-0">
            <a href="?completar=<?= $r['id'] ?>" class="btn btn-xs btn-success" style="font-size:.72rem;padding:.2rem .6rem" onclick="return confirm('¿Marcar como completado?')">
              <i class="fas fa-check me-1"></i>Hecho
            </a>
            <a href="?eliminar=<?= $r['id'] ?>" class="btn btn-xs btn-outline-danger" style="font-size:.72rem;padding:.2rem .6rem" onclick="return confirm('¿Eliminar recordatorio?')">
              <i class="fas fa-trash"></i>
            </a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Completados -->
    <?php if (!empty($completados)): ?>
    <div class="table-card">
      <div class="table-header"><h6 class="fw-bold mb-0 text-muted"><i class="fas fa-check-circle me-2"></i>Completados Recientes</h6></div>
      <div class="list-group list-group-flush">
        <?php foreach ($completados as $r): ?>
        <div class="list-group-item d-flex align-items-center gap-3 opacity-75">
          <i class="fas fa-check-circle text-success"></i>
          <div class="flex-1">
            <div class="small fw-semibold text-decoration-line-through"><?= e($r['titulo']) ?></div>
            <div class="text-muted" style="font-size:.72rem">Completado: <?= fdate($r['fecha_completado'],'d/m/Y H:i') ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/dashboard_footer.php'; ?>
