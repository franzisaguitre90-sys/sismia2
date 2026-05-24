<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('consultor');

$uid       = currentUserId();
$pageTitle = 'Nueva Hoja de Ruta';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $modalidad   = $_POST['modalidad'] ?? 'nuevo_rai';
    $prioridad   = $_POST['prioridad'] ?? 'media';
    $numRai      = sanitize($_POST['numero_rai_existente'] ?? '');
    $diasAlerta  = max(3, min(30, (int)($_POST['dias_alerta'] ?? 5)));
    $diasLimite  = (int)($_POST['dias_limite'] ?? 30);

    if ($modalidad === 'rai_asignado' && empty($numRai)) {
        $error = 'Para la modalidad RAI Asignado debe ingresar el número de RAI existente.';
    } else {
        try {
            $codigo      = generateHojaRutaCode();
            $fechaLimite = date('Y-m-d H:i:s', strtotime("+$diasLimite days"));

            db()->prepare(
                "INSERT INTO hojas_de_ruta (codigo,consultor_id,modalidad,prioridad,numero_rai_existente,fecha_limite,dias_alerta)
                 VALUES (?,?,?,?,?,?,?)"
            )->execute([$codigo, $uid, $modalidad, $prioridad, $numRai ?: null, $fechaLimite, $diasAlerta]);

            $hojaId = (int)db()->lastInsertId();
            registrarEstado($hojaId, null, 'ingresado', $uid, 'Ingreso inicial del trámite por el consultor.');

            // Notificar a directores
            $dirs = db()->query("SELECT id FROM usuarios WHERE rol_id=(SELECT id FROM roles WHERE slug='director') AND activo=1")->fetchAll();
            foreach ($dirs as $d) {
                notificar($d['id'], 'Nuevo Trámite Ingresado', "El consultor ingresó la Hoja de Ruta $codigo. Pendiente de asignación.", 'info', 'fas fa-route', 'hoja_ruta', $hojaId, "/SMIA2/modules/director/asignar.php?id=$hojaId");
            }

            audit($uid, 'CREAR_HDR', 'consultor', "Hoja de ruta $codigo creada", $hojaId);
            header("Location: /SMIA2/modules/consultor/formulario_rai.php?hoja=$hojaId&nuevo=1");
            exit;
        } catch (PDOException $e) {
            $error = 'Error al crear la hoja de ruta. Intente nuevamente.';
        }
    }
}

require_once __DIR__ . '/../../includes/dashboard_header.php';
?>
<div class="row justify-content-center">
<div class="col-lg-8">

<div class="table-card p-4">
  <div class="d-flex align-items-center gap-3 mb-4">
    <div class="stat-icon" style="background:#fbe9e7;color:#bf360c;width:56px;height:56px"><i class="fas fa-plus-circle fa-lg"></i></div>
    <div>
      <h4 class="mb-0 fw-bold">Nueva Hoja de Ruta</h4>
      <p class="text-muted small mb-0">Ingrese una nueva hoja de ruta para iniciar su trámite RAI (Ley 1333)</p>
    </div>
  </div>

  <?php if ($error): ?>
  <div class="alert alert-danger rounded-3"><i class="fas fa-exclamation-circle me-2"></i><?= e($error) ?></div>
  <?php endif; ?>

  <form method="POST" id="formHDR">
    <!-- Modalidad -->
    <div class="mb-4">
      <label class="form-label fw-bold">Modalidad de Trámite <span class="text-danger">*</span></label>
      <div class="row g-3">
        <div class="col-md-6">
          <div class="form-check modalidad-card" id="cardNuevo">
            <input class="form-check-input" type="radio" name="modalidad" id="modNuevo" value="nuevo_rai" checked>
            <label class="form-check-label" for="modNuevo">
              <div class="d-flex gap-3 align-items-start">
                <i class="fas fa-file-medical fa-2x text-danger mt-1"></i>
                <div>
                  <div class="fw-bold">Nuevo RAI</div>
                  <div class="text-muted small">Primera vez que registra esta empresa. Se asigna un número RAI nuevo.</div>
                </div>
              </div>
            </label>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-check modalidad-card" id="cardAsig">
            <input class="form-check-input" type="radio" name="modalidad" id="modAsig" value="rai_asignado">
            <label class="form-check-label" for="modAsig">
              <div class="d-flex gap-3 align-items-start">
                <i class="fas fa-file-signature fa-2x text-primary mt-1"></i>
                <div>
                  <div class="fw-bold">RAI Asignado</div>
                  <div class="text-muted small">La empresa ya tiene RAI. Trámite de renovación o actualización.</div>
                </div>
              </div>
            </label>
          </div>
        </div>
      </div>
    </div>

    <!-- Número RAI existente (solo para rai_asignado) -->
    <div class="mb-3" id="campoRaiExist" style="display:none">
      <label class="form-label fw-semibold">Número de RAI Existente <span class="text-danger">*</span></label>
      <input name="numero_rai_existente" class="form-control form-control-lg" placeholder="Ej: RAI-LP-2025-001234"
             value="<?= e($_POST['numero_rai_existente']??'') ?>" style="text-transform:uppercase">
      <div class="form-text">Ingrese el número de RAI que figura en el certificado anterior.</div>
    </div>

    <div class="row g-3 mb-3">
      <div class="col-md-4">
        <label class="form-label fw-semibold">Prioridad</label>
        <select name="prioridad" class="form-select">
          <option value="baja">Baja</option>
          <option value="media" selected>Media (recomendada)</option>
          <option value="alta">Alta</option>
          <option value="urgente">Urgente</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold">Plazo estimado (días) <span class="text-danger">*</span></label>
        <input type="number" name="dias_limite" class="form-control" value="30" min="7" max="180">
        <div class="form-text">Días hábiles para completar el trámite.</div>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold">Días de alerta antes del vencimiento</label>
        <input type="number" name="dias_alerta" class="form-control" value="5" min="1" max="30">
      </div>
    </div>

    <div class="alert alert-info rounded-3 mb-4">
      <i class="fas fa-info-circle me-2"></i>
      Al crear la hoja de ruta se generará automáticamente un <strong>código HDR único</strong> para el seguimiento de su trámite.
      A continuación deberá completar el <strong>Formulario RAI</strong> y cargar la documentación requerida.
    </div>

    <div class="d-flex gap-3">
      <button type="submit" class="btn btn-danger btn-lg px-5 fw-bold">
        <i class="fas fa-plus-circle me-2"></i>Crear Hoja de Ruta
      </button>
      <a href="/SMIA2/dashboard/consultor.php" class="btn btn-outline-secondary btn-lg">Cancelar</a>
    </div>
  </form>
</div>

<!-- Info sobre documentos requeridos -->
<div class="table-card mt-4 p-4">
  <h6 class="fw-bold mb-3"><i class="fas fa-list-check me-2 text-primary"></i>Documentos que deberá preparar</h6>
  <?php
  $tipos = db()->query("SELECT nombre,descripcion,requerido,modalidad FROM tipos_documento WHERE activo=1 ORDER BY orden")->fetchAll();
  ?>
  <div class="table-responsive">
    <table class="table table-sm">
      <thead><tr><th>Documento</th><th>Modalidad</th><th>Requerido</th></tr></thead>
      <tbody>
      <?php foreach ($tipos as $t): ?>
      <tr>
        <td class="small"><strong><?= e($t['nombre']) ?></strong><br><span class="text-muted"><?= e($t['descripcion']) ?></span></td>
        <td><span class="badge <?= $t['modalidad']==='ambos'?'bg-primary':($t['modalidad']==='nuevo_rai'?'bg-danger':'bg-secondary') ?>"><?= $t['modalidad']==='ambos'?'Ambos':($t['modalidad']==='nuevo_rai'?'Nuevo RAI':'RAI Asignado') ?></span></td>
        <td><?= $t['requerido'] ? '<span class="badge bg-danger">Obligatorio</span>' : '<span class="badge bg-light text-muted">Opcional</span>' ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

</div>
</div>

<style>
.modalidad-card{border:2px solid #e0e0e0;border-radius:12px;padding:1rem 1rem 1rem 3rem;cursor:pointer;transition:all .2s}
.modalidad-card:has(input:checked){border-color:var(--c-pri);background:var(--c-acc)}
</style>
<script>
const modNuevo = document.getElementById('modNuevo');
const modAsig  = document.getElementById('modAsig');
const campoRai = document.getElementById('campoRaiExist');
const cardN    = document.getElementById('cardNuevo');
const cardA    = document.getElementById('cardAsig');

function updateModalidad() {
  campoRai.style.display = modAsig.checked ? 'block' : 'none';
  cardN.style.borderColor = modNuevo.checked ? '#bf360c' : '#e0e0e0';
  cardA.style.borderColor = modAsig.checked  ? '#bf360c' : '#e0e0e0';
  cardN.style.background  = modNuevo.checked ? '#fbe9e7' : '';
  cardA.style.background  = modAsig.checked  ? '#fbe9e7' : '';
}
modNuevo.addEventListener('change', updateModalidad);
modAsig.addEventListener('change', updateModalidad);
updateModalidad();
</script>

<?php require_once __DIR__ . '/../../includes/dashboard_footer.php'; ?>
