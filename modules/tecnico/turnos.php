<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('tecnico');

$uid       = currentUserId();
$pageTitle = 'Sistema de Turnos';
$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear') {
        $fecha    = sanitize($_POST['fecha'] ?? '');
        $horaIni  = sanitize($_POST['hora_inicio'] ?? '08:00');
        $horaFin  = sanitize($_POST['hora_fin'] ?? '17:00');
        $cap      = max(1, min(20, (int)($_POST['capacidad'] ?? 5)));
        $notas    = sanitize($_POST['notas'] ?? '');

        if (!$fecha) { $error = 'Ingrese la fecha del turno.'; }
        else {
            try {
                db()->prepare("INSERT INTO turnos (tecnico_id,fecha,hora_inicio,hora_fin,capacidad_tramites,notas) VALUES (?,?,?,?,?,?)")->execute([$uid,$fecha,$horaIni,$horaFin,$cap,$notas?:null]);
                $success = 'Turno programado correctamente.';
            } catch(PDOException $e) { $error = 'Error al crear turno.'; }
        }
    } elseif ($accion === 'cambiar') {
        $turnoId  = (int)($_POST['turno_id'] ?? 0);
        $nuevoEst = sanitize($_POST['estado'] ?? '');
        if ($turnoId && in_array($nuevoEst,['programado','activo','completado','cancelado'])) {
            db()->prepare("UPDATE turnos SET estado=? WHERE id=? AND tecnico_id=?")->execute([$nuevoEst,$turnoId,$uid]);
            $success = 'Estado de turno actualizado.';
        }
    }
}

// Turnos del técnico
$mesAct  = date('Y-m');
$turnos  = db()->prepare("SELECT * FROM turnos WHERE tecnico_id=? ORDER BY fecha DESC, hora_inicio");
$turnos->execute([$uid]);
$turnos  = $turnos->fetchAll();

// Recordatorios próximos
$recs = db()->prepare("SELECT * FROM recordatorios WHERE usuario_id=? AND completado=0 AND activo=1 ORDER BY fecha_recordatorio LIMIT 10");
$recs->execute([$uid]);
$recs = $recs->fetchAll();

require_once __DIR__ . '/../../includes/dashboard_header.php';
?>

<?php if ($error): ?><div class="alert alert-danger rounded-3 mb-3"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success rounded-3 mb-3"><i class="fas fa-check-circle me-2"></i><?= e($success) ?></div><?php endif; ?>

<div class="row g-4">
  <!-- Crear turno -->
  <div class="col-lg-4">
    <div class="table-card p-4">
      <h5 class="fw-bold mb-4"><i class="fas fa-calendar-plus me-2 text-success"></i>Programar Turno</h5>
      <form method="POST">
        <input type="hidden" name="accion" value="crear">
        <div class="mb-3"><label class="form-label fw-semibold">Fecha <span class="text-danger">*</span></label>
          <input type="date" name="fecha" class="form-control" min="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="row g-2 mb-3">
          <div class="col-6"><label class="form-label fw-semibold">Hora inicio</label>
            <input type="time" name="hora_inicio" class="form-control" value="08:00">
          </div>
          <div class="col-6"><label class="form-label fw-semibold">Hora fin</label>
            <input type="time" name="hora_fin" class="form-control" value="17:00">
          </div>
        </div>
        <div class="mb-3"><label class="form-label fw-semibold">Capacidad de trámites</label>
          <input type="number" name="capacidad" class="form-control" value="5" min="1" max="20">
        </div>
        <div class="mb-3"><label class="form-label fw-semibold">Notas</label>
          <textarea name="notas" class="form-control" rows="2" placeholder="Notas opcionales..."></textarea>
        </div>
        <div class="d-grid">
          <button type="submit" class="btn btn-success fw-bold"><i class="fas fa-calendar-plus me-2"></i>Programar Turno</button>
        </div>
      </form>
    </div>

    <!-- Añadir recordatorio -->
    <div class="table-card p-4 mt-4">
      <h5 class="fw-bold mb-4"><i class="fas fa-bell me-2 text-warning"></i>Nuevo Recordatorio</h5>
      <form method="POST" action="/SMIA2/modules/tecnico/recordatorios.php">
        <input type="hidden" name="accion" value="crear">
        <div class="mb-2"><label class="form-label fw-semibold">Título</label>
          <input name="titulo" class="form-control" required placeholder="Ej: Revisión HDR-2026-000123">
        </div>
        <div class="mb-2"><label class="form-label fw-semibold">Fecha y Hora</label>
          <input type="datetime-local" name="fecha_recordatorio" class="form-control" required>
        </div>
        <div class="mb-2"><label class="form-label fw-semibold">Repetición</label>
          <select name="repeticion" class="form-select">
            <option value="no">Sin repetición</option>
            <option value="diario">Diario</option>
            <option value="semanal">Semanal</option>
          </select>
        </div>
        <div class="mb-3"><label class="form-label fw-semibold">Descripción</label>
          <textarea name="descripcion" class="form-control" rows="2"></textarea>
        </div>
        <div class="d-grid">
          <button type="submit" class="btn btn-warning text-dark fw-bold"><i class="fas fa-bell me-2"></i>Crear Recordatorio</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Listado de turnos -->
  <div class="col-lg-8">
    <div class="table-card mb-4">
      <div class="table-header"><h6 class="fw-bold mb-0"><i class="fas fa-calendar-alt me-2 text-primary"></i>Mis Turnos Programados</h6></div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead><tr><th>Fecha</th><th>Horario</th><th>Capacidad</th><th>Estado</th><th>Notas</th><th>Acción</th></tr></thead>
          <tbody>
          <?php if (empty($turnos)): ?><tr><td colspan="6" class="text-center text-muted py-4">Sin turnos programados</td></tr><?php endif; ?>
          <?php foreach ($turnos as $t):
            $stBadge = ['programado'=>'info text-dark','activo'=>'success','completado'=>'dark','cancelado'=>'danger'][$t['estado']]??'secondary';
            $isPast = strtotime($t['fecha']) < strtotime('today');
          ?>
          <tr class="<?= $isPast&&$t['estado']==='programado'?'table-warning':'' ?>">
            <td>
              <div class="fw-bold"><?= date('d/m/Y',strtotime($t['fecha'])) ?></div>
              <div class="text-muted small"><?= date('l',strtotime($t['fecha'])) ?></div>
            </td>
            <td class="small"><?= date('H:i',strtotime($t['hora_inicio'])) ?> – <?= date('H:i',strtotime($t['hora_fin'])) ?></td>
            <td class="text-center">
              <div class="progress" style="height:8px;width:80px">
                <?php $pct = $t['capacidad_tramites']>0?min(100,($t['tramites_asignados']/$t['capacidad_tramites'])*100):0; ?>
                <div class="progress-bar <?= $pct>=80?'bg-danger':($pct>=50?'bg-warning':'bg-success') ?>" style="width:<?= $pct ?>%"></div>
              </div>
              <div class="text-muted" style="font-size:.72rem"><?= $t['tramites_asignados'] ?>/<?= $t['capacidad_tramites'] ?></div>
            </td>
            <td><span class="badge bg-<?= $stBadge ?>"><?= ucfirst($t['estado']) ?></span></td>
            <td class="small text-muted"><?= $t['notas']?e(substr($t['notas'],0,40)).'..':'—' ?></td>
            <td>
              <form method="POST" class="d-flex gap-1">
                <input type="hidden" name="accion" value="cambiar">
                <input type="hidden" name="turno_id" value="<?= $t['id'] ?>">
                <select name="estado" class="form-select form-select-sm" style="width:110px">
                  <?php foreach (['programado','activo','completado','cancelado'] as $e): ?>
                  <option value="<?= $e ?>" <?= $t['estado']===$e?'selected':'' ?>><?= ucfirst($e) ?></option>
                  <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fas fa-save"></i></button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Recordatorios próximos -->
    <div class="table-card p-3">
      <h6 class="fw-bold mb-3"><i class="fas fa-bell me-2 text-warning"></i>Próximos Recordatorios</h6>
      <?php if (empty($recs)): ?>
      <div class="text-center py-3 text-muted small"><i class="fas fa-check-circle me-1 text-success"></i>Sin recordatorios pendientes</div>
      <?php else: ?>
      <?php foreach ($recs as $r):
        $dias = diasRestantes($r['fecha_recordatorio']);
        $urgente = $dias !== null && $dias <= 0;
      ?>
      <div class="d-flex align-items-center gap-3 mb-2 p-2 <?= $urgente?'bg-danger-subtle':'bg-light' ?> rounded-3">
        <i class="fas fa-bell text-<?= $urgente?'danger':'warning' ?> fa-lg"></i>
        <div class="flex-1">
          <div class="fw-semibold small"><?= e($r['titulo']) ?></div>
          <div class="text-muted" style="font-size:.75rem"><?= fdate($r['fecha_recordatorio'],'d/m/Y H:i') ?></div>
        </div>
        <span class="badge bg-<?= $urgente?'danger':'secondary' ?>"><?= $urgente?'Vencido':($dias===0?'Hoy':"{$dias}d") ?></span>
        <a href="/SMIA2/modules/tecnico/recordatorios.php?completar=<?= $r['id'] ?>" class="btn btn-xs btn-success" style="font-size:.72rem;padding:.15rem .4rem"><i class="fas fa-check"></i></a>
      </div>
      <?php endforeach; ?>
      <div class="text-center mt-2">
        <a href="/SMIA2/modules/tecnico/recordatorios.php" class="btn btn-sm btn-outline-warning">Ver todos</a>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/dashboard_footer.php'; ?>
