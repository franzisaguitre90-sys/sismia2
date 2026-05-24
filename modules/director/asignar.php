<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('director');

$uid       = currentUserId();
$pageTitle = 'Asignar Trámites';
$success = '';
$error   = '';

// Procesar asignación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hojaId     = (int)($_POST['hoja_id'] ?? 0);
    $tecnicoId  = (int)($_POST['tecnico_id'] ?? 0);
    $prioridad  = sanitize($_POST['prioridad'] ?? 'media');
    $diasLimite = (int)($_POST['dias_limite'] ?? 15);
    $obs        = sanitize($_POST['observaciones'] ?? '');

    if (!$hojaId || !$tecnicoId) {
        $error = 'Seleccione una hoja de ruta y un técnico.';
    } else {
        $sh = db()->prepare("SELECT * FROM hojas_de_ruta WHERE id=?");
        $sh->execute([$hojaId]);
        $hoja = $sh->fetch();

        if ($hoja) {
            $fechaLimite = date('Y-m-d H:i:s', strtotime("+$diasLimite days"));
            $estadoAnt   = $hoja['estado'];

            db()->prepare(
                "UPDATE hojas_de_ruta SET tecnico_id=?,director_id=?,estado='asignado',prioridad=?,
                 fecha_asignacion=NOW(),fecha_limite=?,observaciones_director=?,alerta_enviada=0
                 WHERE id=?"
            )->execute([$tecnicoId, $uid, $prioridad, $fechaLimite, $obs ?: null, $hojaId]);

            registrarEstado($hojaId, $estadoAnt, 'asignado', $uid, "Asignado por director a técnico ID $tecnicoId. $obs");
            updateKPI($tecnicoId);

            // Notificaciones
            $nTec = db()->prepare("SELECT nombre FROM usuarios WHERE id=?")->execute([$tecnicoId]);
            notificar($tecnicoId, 'Nuevo Trámite Asignado', "Se le asignó la Hoja de Ruta {$hoja['codigo']}. Prioridad: $prioridad.", 'info', 'fas fa-folder-plus', 'hoja_ruta', $hojaId, "/SMIA2/modules/tecnico/mis_tramites.php?id=$hojaId");
            notificar($hoja['consultor_id'], 'Trámite Asignado a Técnico', "Su hoja de ruta {$hoja['codigo']} fue asignada para revisión.", 'success', 'fas fa-user-check', 'hoja_ruta', $hojaId);

            audit($uid, 'ASIGNAR_HDR', 'director', "HDR {$hoja['codigo']} asignada a técnico $tecnicoId", $hojaId);
            $success = "Hoja de ruta <strong>{$hoja['codigo']}</strong> asignada correctamente al técnico.";
        }
    }
}

// Hojas pendientes de asignación
$pendientes = db()->query("
    SELECT hdr.*, CONCAT(uc.nombre,' ',uc.apellido) consultor, rai.razon_social
    FROM hojas_de_ruta hdr
    JOIN usuarios uc ON hdr.consultor_id=uc.id
    LEFT JOIN formularios_rai rai ON rai.hoja_ruta_id=hdr.id
    WHERE hdr.estado IN ('ingresado','asignado')
    ORDER BY FIELD(hdr.prioridad,'urgente','alta','media','baja'), hdr.fecha_ingreso ASC
")->fetchAll();

// Técnicos disponibles con carga actual
$tecnicos = db()->query("
    SELECT u.id, CONCAT(u.nombre,' ',u.apellido) nombre_completo,
           COUNT(h.id) tramites_activos,
           t.fecha turno_hoy, t.estado turno_estado
    FROM usuarios u
    JOIN roles r ON u.rol_id=r.id
    LEFT JOIN hojas_de_ruta h ON h.tecnico_id=u.id AND h.estado NOT IN ('finalizado','rechazado')
    LEFT JOIN turnos t ON t.tecnico_id=u.id AND t.fecha=CURDATE()
    WHERE r.slug='tecnico' AND u.activo=1
    GROUP BY u.id ORDER BY tramites_activos ASC
")->fetchAll();

// Hoja preseleccionada
$hojaPresel = null;
if (!empty($_GET['id'])) {
    $sp = db()->prepare("SELECT hdr.*, CONCAT(uc.nombre,' ',uc.apellido) consultor FROM hojas_de_ruta hdr JOIN usuarios uc ON hdr.consultor_id=uc.id WHERE hdr.id=?");
    $sp->execute([(int)$_GET['id']]);
    $hojaPresel = $sp->fetch();
}

require_once __DIR__ . '/../../includes/dashboard_header.php';
?>

<?php if ($error): ?><div class="alert alert-danger rounded-3"><i class="fas fa-exclamation-circle me-2"></i><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success rounded-3"><i class="fas fa-check-circle me-2"></i><?= $success ?></div><?php endif; ?>

<div class="row g-4">
  <!-- Formulario de asignación -->
  <div class="col-lg-5">
    <div class="table-card p-4">
      <h5 class="fw-bold mb-4"><i class="fas fa-user-check me-2 text-warning"></i>Asignar Trámite a Técnico</h5>
      <form method="POST">
        <div class="mb-3">
          <label class="form-label fw-semibold">Hoja de Ruta <span class="text-danger">*</span></label>
          <select name="hoja_id" class="form-select" required>
            <option value="">— Seleccione —</option>
            <?php foreach ($pendientes as $p): ?>
            <option value="<?= $p['id'] ?>" <?= ($hojaPresel && $hojaPresel['id']===$p['id'])||(!empty($_POST['hoja_id'])&&$_POST['hoja_id']==$p['id'])?'selected':'' ?>>
              <?= e($p['codigo']) ?> – <?= e($p['consultor']) ?> (<?= $p['estado'] ?>)
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Técnico Asignado <span class="text-danger">*</span></label>
          <select name="tecnico_id" class="form-select" required>
            <option value="">— Seleccione técnico —</option>
            <?php foreach ($tecnicos as $t): ?>
            <option value="<?= $t['id'] ?>" <?= (!empty($_POST['tecnico_id'])&&$_POST['tecnico_id']==$t['id'])?'selected':'' ?>>
              <?= e($t['nombre_completo']) ?> – <?= $t['tramites_activos'] ?> trámites activos
              <?= $t['turno_hoy']?'[Turno hoy: '.ucfirst($t['turno_estado']).']':'[Sin turno hoy]' ?>
            </option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">Técnicos ordenados por menor carga de trabajo.</div>
        </div>
        <div class="row g-2 mb-3">
          <div class="col-6">
            <label class="form-label fw-semibold">Prioridad</label>
            <select name="prioridad" class="form-select">
              <option value="baja">Baja</option>
              <option value="media" selected>Media</option>
              <option value="alta">Alta</option>
              <option value="urgente">Urgente</option>
            </select>
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold">Días para resolver</label>
            <input type="number" name="dias_limite" class="form-control" value="15" min="3" max="90">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Instrucciones al Técnico</label>
          <textarea name="observaciones" class="form-control" rows="3" placeholder="Instrucciones o comentarios para el técnico..."><?= e($_POST['observaciones']??'') ?></textarea>
        </div>
        <div class="d-grid">
          <button type="submit" class="btn btn-warning text-dark fw-bold py-2">
            <i class="fas fa-user-check me-2"></i>Asignar Trámite
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Lista de pendientes -->
  <div class="col-lg-7">
    <!-- Carga de técnicos -->
    <div class="table-card p-3 mb-4">
      <h6 class="fw-bold mb-3"><i class="fas fa-hard-hat me-2 text-success"></i>Carga Actual por Técnico</h6>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead><tr><th>Técnico</th><th class="text-center">Activos</th><th class="text-center">Turno Hoy</th><th class="text-center">Disponibilidad</th></tr></thead>
          <tbody>
          <?php foreach ($tecnicos as $t): $carga = min(100, ($t['tramites_activos']/10)*100); $clrCarga = $carga>80?'bg-danger':($carga>50?'bg-warning':'bg-success'); ?>
          <tr>
            <td class="small fw-semibold"><?= e($t['nombre_completo']) ?></td>
            <td class="text-center"><span class="badge bg-primary"><?= $t['tramites_activos'] ?></span></td>
            <td class="text-center">
              <?php if ($t['turno_hoy']): ?>
              <span class="badge bg-<?= $t['turno_estado']==='activo'?'success':($t['turno_estado']==='programado'?'info text-dark':'secondary') ?>"><?= ucfirst($t['turno_estado']) ?></span>
              <?php else: ?><span class="badge bg-light text-muted">Sin turno</span><?php endif; ?>
            </td>
            <td>
              <div class="progress" style="height:8px"><div class="progress-bar <?= $clrCarga ?>" style="width:<?= $carga ?>%"></div></div>
              <div class="text-muted" style="font-size:.7rem"><?= $t['tramites_activos'] ?>/10 máx.</div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($tecnicos)): ?><tr><td colspan="4" class="text-center text-muted">Sin técnicos activos</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pendientes de asignación -->
    <div class="table-card">
      <div class="table-header">
        <h6 class="fw-bold mb-0"><i class="fas fa-clock me-2 text-danger"></i>Pendientes de Asignación (<?= count($pendientes) ?>)</h6>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead><tr><th>Código</th><th>Consultor</th><th>Empresa</th><th>Estado</th><th>Ingreso</th><th>Acción</th></tr></thead>
          <tbody>
          <?php if (empty($pendientes)): ?><tr><td colspan="6" class="text-center text-muted py-4"><i class="fas fa-check-circle text-success me-2"></i>Sin pendientes</td></tr><?php endif; ?>
          <?php foreach ($pendientes as $p): ?>
          <tr>
            <td><code class="small text-primary"><?= e($p['codigo']) ?></code></td>
            <td class="small"><?= e($p['consultor']) ?></td>
            <td class="small"><?= $p['razon_social']?e($p['razon_social']):'—' ?></td>
            <td><?= estadoBadge($p['estado']) ?></td>
            <td class="small"><?= fdate($p['fecha_ingreso'],'d/m/Y') ?></td>
            <td><a href="?id=<?= $p['id'] ?>" class="btn btn-xs btn-warning" style="font-size:.75rem;padding:.2rem .6rem">Asignar</a></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/dashboard_footer.php'; ?>
