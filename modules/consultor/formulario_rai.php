<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('consultor');

$uid       = currentUserId();
$pageTitle = 'Formulario RAI';
$error = '';
$success = '';

// Obtener mis hojas de ruta para el selector
$misHojas = db()->prepare("SELECT id,codigo,modalidad,estado FROM hojas_de_ruta WHERE consultor_id=? AND estado NOT IN ('rechazado','finalizado') ORDER BY fecha_ingreso DESC");
$misHojas->execute([$uid]);
$misHojas = $misHojas->fetchAll();

// Hoja de ruta seleccionada
$hojaId = (int)($_GET['hoja'] ?? $_POST['hoja_id'] ?? 0);
$hoja   = null;
$rai    = null;

$caebList = db()->query("SELECT codigo, descripcion FROM caeb_diccionario ORDER BY codigo")->fetchAll();


if ($hojaId) {
    $sh = db()->prepare("SELECT * FROM hojas_de_ruta WHERE id=? AND consultor_id=?");
    $sh->execute([$hojaId, $uid]);
    $hoja = $sh->fetch();
    if ($hoja) {
        $sr = db()->prepare("SELECT * FROM formularios_rai WHERE hoja_ruta_id=?");
        $sr->execute([$hojaId]);
        $rai = $sr->fetch();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hojaId && $hoja) {
    $data = [
        'modalidad'             => $hoja['modalidad'],
        'numero_rai'            => sanitize($_POST['numero_rai'] ?? $hoja['numero_rai_existente'] ?? ''),
        'razon_social'          => sanitize($_POST['razon_social'] ?? ''),
        'nit'                   => sanitize($_POST['nit'] ?? ''),
        'matricula_comercial'   => sanitize($_POST['matricula_comercial'] ?? ''),
        'actividad_economica'   => 'Múltiples CAEB',
        'codigo_ciiu'           => json_encode(array_values(array_filter($_POST['caeb'] ?? [], 'strlen'))),
        'descripcion_actividad' => sanitize($_POST['descripcion_actividad'] ?? ''),
        'departamento'          => sanitize($_POST['departamento'] ?? 'La Paz'),
        'provincia'             => sanitize($_POST['provincia'] ?? ''),
        'municipio'             => sanitize($_POST['municipio'] ?? 'El Alto'),
        'distrito'              => (int)($_POST['distrito'] ?? 0),
        'zona_barrio'           => sanitize($_POST['zona_barrio'] ?? ''),
        'calle_avenida'         => sanitize($_POST['calle_avenida'] ?? ''),
        'numero_puerta'         => sanitize($_POST['numero_puerta'] ?? ''),
        'referencia_ubicacion'  => sanitize($_POST['referencia_ubicacion'] ?? ''),
        'latitud'               => (float)($_POST['latitud'] ?? 0),
        'longitud'              => (float)($_POST['longitud'] ?? 0),
        'altitud'               => (float)($_POST['altitud'] ?? 0),
        'categoria_rai'         => $_POST['categoria_rai'] ?? '4',
        'tipo_actividad'        => sanitize($_POST['tipo_actividad'] ?? ''),
        'personal_empleado_total'=> (int)($_POST['personal_empleado_total'] ?? 0),
        'personal_empleado_directo' => (int)($_POST['personal_empleado_directo'] ?? 0),
        'superficie_total_m2'   => (float)($_POST['superficie_total_m2'] ?? 0),
        'superficie_construida_m2' => (float)($_POST['superficie_construida_m2'] ?? 0),
        'representante_nombre'  => sanitize($_POST['representante_nombre'] ?? ''),
        'representante_ci'      => sanitize($_POST['representante_ci'] ?? ''),
        'representante_cargo'   => sanitize($_POST['representante_cargo'] ?? 'Representante Legal'),
        'representante_telefono'=> sanitize($_POST['representante_telefono'] ?? ''),
        'representante_celular' => sanitize($_POST['representante_celular'] ?? ''),
        'representante_email'   => sanitize($_POST['representante_email'] ?? ''),
        'tiene_tratamiento_residuos' => isset($_POST['tiene_tratamiento_residuos']) ? 1 : 0,
        'descripcion_tratamiento'    => sanitize($_POST['descripcion_tratamiento'] ?? ''),
        'tiene_plan_emergencias'     => isset($_POST['tiene_plan_emergencias']) ? 1 : 0,
        'volumen_agua_mensual_m3'    => (float)($_POST['volumen_agua_mensual_m3'] ?? 0),
        'tipo_energia'               => sanitize($_POST['tipo_energia'] ?? ''),
        'estado_formulario'          => ($_POST['accion'] ?? 'guardar') === 'enviar' ? 'enviado' : 'borrador',
    ];

    if (empty($data['razon_social'])) {
        $error = 'La Razón Social es obligatoria.';
    } else {
        try {
            if ($rai) {
                $cols = implode('=?,', array_keys($data)) . '=?';
                $vals = array_values($data);
                $vals[] = $rai['id'];
                db()->prepare("UPDATE formularios_rai SET $cols, ultima_modificacion=NOW()" . ($data['estado_formulario']==='enviado'?', fecha_envio=NOW()':'') . " WHERE id=?")->execute($vals);
            } else {
                $cols = implode(',', array_keys($data));
                $phs  = implode(',', array_fill(0, count($data), '?'));
                db()->prepare("INSERT INTO formularios_rai ($cols,hoja_ruta_id) VALUES ($phs,?)")->execute([...array_values($data), $hojaId]);
            }

            if ($data['estado_formulario'] === 'enviado') {
                db()->prepare("UPDATE hojas_de_ruta SET estado='asignado' WHERE id=? AND estado='ingresado'")->execute([$hojaId]);
                $success = '¡Formulario RAI enviado correctamente! El Director revisará y asignará su trámite a un técnico.';
                notificar($uid, 'Formulario RAI Enviado', "Su formulario RAI para {$hoja['codigo']} fue enviado.", 'success', 'fas fa-check-circle', 'hoja_ruta', $hojaId);
            } else {
                $success = 'Formulario guardado como borrador.';
            }
            // Reload RAI
            $sr->execute([$hojaId]);
            $rai = $sr->fetch();
        } catch (PDOException $e) {
            $error = 'Error al guardar el formulario.';
        }
    }
}

require_once __DIR__ . '/../../includes/dashboard_header.php';
?>

<?php if (empty($misHojas)): ?>
<div class="alert alert-warning rounded-3">
  <i class="fas fa-exclamation-triangle me-2"></i>
  No tiene hojas de ruta activas. <a href="/SMIA2/modules/consultor/nueva_hoja_ruta.php" class="btn btn-sm btn-warning ms-2">Crear una ahora</a>
</div>
<?php else: ?>

<!-- Selector de hoja de ruta -->
<div class="table-card p-3 mb-4">
  <div class="d-flex align-items-center gap-3 flex-wrap">
    <label class="fw-bold mb-0 text-nowrap"><i class="fas fa-route me-1"></i>Hoja de Ruta:</label>
    <select id="selHoja" class="form-select" style="max-width:350px" onchange="location.href='?hoja='+this.value">
      <option value="">— Seleccione una hoja de ruta —</option>
      <?php foreach ($misHojas as $h): ?>
      <option value="<?= $h['id'] ?>" <?= $hojaId===$h['id']?'selected':'' ?>>
        <?= e($h['codigo']) ?> — <?= $h['modalidad']==='nuevo_rai'?'Nuevo RAI':'RAI Asignado' ?> (<?= $h['estado'] ?>)
      </option>
      <?php endforeach; ?>
    </select>
    <?php if (!$hojaId): ?>
    <a href="/SMIA2/modules/consultor/nueva_hoja_ruta.php" class="btn btn-danger btn-sm"><i class="fas fa-plus me-1"></i>Nueva Hoja de Ruta</a>
    <?php endif; ?>
  </div>
</div>

<?php if ($hoja): ?>

<?php if ($error): ?><div class="alert alert-danger rounded-3"><i class="fas fa-exclamation-circle me-2"></i><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success rounded-3"><i class="fas fa-check-circle me-2"></i><?= $success ?></div><?php endif; ?>

<!-- Info de la hoja de ruta -->
<div class="alert alert-info rounded-3 mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
  <div>
    <strong>Hoja de Ruta:</strong> <code><?= e($hoja['codigo']) ?></code>
    &nbsp;|&nbsp; <strong>Modalidad:</strong> <?= $hoja['modalidad']==='nuevo_rai'?'Nuevo RAI':'RAI Asignado' ?>
    &nbsp;|&nbsp; <?= estadoBadge($hoja['estado']) ?>
    <?php if ($rai): ?>&nbsp;|&nbsp; <strong>Formulario:</strong> <?= estadoBadge($rai['estado_formulario']) ?><?php endif; ?>
  </div>
  <a href="/SMIA2/modules/consultor/subir_documentos.php?hoja=<?= $hoja['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-upload me-1"></i>Ir a Documentos</a>
</div>

<?php if ($rai && in_array($rai['estado_formulario'], ['aprobado','rechazado'])): ?>
<div class="alert alert-<?= $rai['estado_formulario']==='aprobado'?'success':'danger' ?> rounded-3 mb-4">
  <i class="fas fa-<?= $rai['estado_formulario']==='aprobado'?'check':'times' ?>-circle me-2"></i>
  Formulario <strong><?= strtoupper($rai['estado_formulario']) ?></strong> por el técnico.
  <?php if ($rai['observaciones_tecnico']): ?><br><strong>Observaciones:</strong> <?= e($rai['observaciones_tecnico']) ?><?php endif; ?>
</div>
<?php endif; ?>

<form method="POST" id="formRAI">
<input type="hidden" name="hoja_id" value="<?= $hoja['id'] ?>">

<!-- Datos de la empresa -->
<div class="table-card p-4 mb-4">
  <h5 class="fw-bold mb-4 border-bottom pb-2"><i class="fas fa-building me-2 text-primary"></i>I. Datos de la Empresa</h5>
  <div class="row g-3">
    <div class="col-md-8"><label class="form-label fw-semibold">Razón Social <span class="text-danger">*</span></label>
      <input name="razon_social" class="form-control" value="<?= e($rai['razon_social']??'') ?>" required <?= ($rai && in_array($rai['estado_formulario'],['aprobado','en_revision']))?'readonly':'' ?>>
    </div>
    <div class="col-md-4"><label class="form-label fw-semibold">Categoría RAI</label>
      <select name="categoria_rai" class="form-select">
        <option value="1" <?= ($rai['categoria_rai']??'')=='1'?'selected':'' ?>>Categoría 1 (Gobernación La Paz)</option>
        <option value="2" <?= ($rai['categoria_rai']??'')=='2'?'selected':'' ?>>Categoría 2 (Gobernación La Paz)</option>
        <option value="3" <?= ($rai['categoria_rai']??'')=='3'?'selected':'' ?>>Categoría 3 (Municipio El Alto)</option>
        <option value="4" <?= ($rai['categoria_rai']??'4')=='4'?'selected':'' ?>>Categoría 4 (Municipio El Alto)</option>
      </select>
    </div>
    <?php if ($hoja['modalidad']==='rai_asignado'): ?>
    <div class="col-md-4"><label class="form-label fw-semibold">N° RAI Existente</label>
      <input name="numero_rai" class="form-control" value="<?= e($rai['numero_rai']??$hoja['numero_rai_existente']??'') ?>">
    </div>
    <?php endif; ?>
    <div class="col-md-4"><label class="form-label fw-semibold">NIT</label>
      <input name="nit" class="form-control" value="<?= e($rai['nit']??'') ?>">
    </div>
    <div class="col-md-4"><label class="form-label fw-semibold">Matrícula de Comercio</label>
      <input name="matricula_comercial" class="form-control" value="<?= e($rai['matricula_comercial']??'') ?>">
    </div>
    <div class="col-md-12">
      <label class="form-label fw-semibold"><i class="fas fa-list-ol text-primary me-1"></i>Actividad Económica (Códigos CAEB)</label>
      <div id="caeb-container" class="d-flex flex-column gap-2">
        <?php 
        $decoded = json_decode($rai['codigo_ciiu'] ?? '[]', true);
        $selectedCaebs = is_array($decoded) ? $decoded : ($decoded ? [$decoded] : []);
        if (empty($selectedCaebs)) {
            $selectedCaebs = [''];
        }
        foreach ($selectedCaebs as $selVal):
        ?>
        <div class="caeb-row d-flex gap-2 align-items-center">
          <select name="caeb[]" class="form-select select-caeb">
            <option value="">— Seleccione una Actividad CAEB —</option>
            <?php foreach($caebList as $c): ?>
            <option value="<?= e($c['codigo']) ?>" <?= ($c['codigo'] == $selVal) ? 'selected' : '' ?>>
              <?= e($c['codigo']) ?> - <?= e($c['descripcion']) ?>
            </option>
            <?php endforeach; ?>
          </select>
          <button type="button" class="btn btn-outline-danger btn-remove-caeb px-3" onclick="removeCaebRow(this)">
            <i class="fas fa-trash-alt"></i>
          </button>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="mt-2">
        <button type="button" class="btn btn-sm btn-success px-3 py-1.5 rounded-pill shadow-sm" id="btn-add-caeb">
          <i class="fas fa-plus-circle me-1"></i>Agregar otra Actividad Económica (CAEB)
        </button>
      </div>
    </div>
    <div class="col-12"><label class="form-label fw-semibold">Descripción de la Actividad</label>
      <textarea name="descripcion_actividad" class="form-control" rows="3"><?= e($rai['descripcion_actividad']??'') ?></textarea>
    </div>
  </div>
</div>

<!-- Ubicación -->
<div class="table-card p-4 mb-4">
  <h5 class="fw-bold mb-4 border-bottom pb-2"><i class="fas fa-map-marker-alt me-2 text-danger"></i>II. Ubicación de la Empresa</h5>
  <div class="row g-3">
    <div class="col-md-3"><label class="form-label fw-semibold">Departamento</label>
      <select name="departamento" class="form-select">
        <option value="La Paz" selected>La Paz</option>
      </select>
    </div>
    <div class="col-md-3"><label class="form-label fw-semibold">Provincia</label>
      <input name="provincia" class="form-control" value="<?= e($rai['provincia']??'Murillo') ?>">
    </div>
    <div class="col-md-3"><label class="form-label fw-semibold">Municipio</label>
      <input name="municipio" class="form-control" value="<?= e($rai['municipio']??'El Alto') ?>" readonly>
    </div>
    <div class="col-md-3"><label class="form-label fw-semibold">Distrito</label>
      <select name="distrito" class="form-select">
        <option value="">— Seleccione —</option>
        <?php for($i=1; $i<=14; $i++): ?>
        <option value="<?= $i ?>" <?= ($rai['distrito']??0)==$i?'selected':'' ?>>Distrito <?= $i ?></option>
        <?php endfor; ?>
      </select>
    </div>
    <div class="col-md-4"><label class="form-label fw-semibold">Zona / Barrio</label>
      <input name="zona_barrio" class="form-control" value="<?= e($rai['zona_barrio']??'') ?>">
    </div>
    <div class="col-md-5"><label class="form-label fw-semibold">Calle / Avenida</label>
      <input name="calle_avenida" class="form-control" value="<?= e($rai['calle_avenida']??'') ?>">
    </div>
    <div class="col-md-3"><label class="form-label fw-semibold">N° Puerta</label>
      <input name="numero_puerta" class="form-control" value="<?= e($rai['numero_puerta']??'') ?>">
    </div>
    <div class="col-md-12"><label class="form-label fw-semibold">Referencia de Ubicación</label>
      <input name="referencia_ubicacion" class="form-control" value="<?= e($rai['referencia_ubicacion']??'') ?>">
    </div>
    <div class="col-md-4"><label class="form-label fw-semibold">Latitud</label>
      <input type="number" step="0.00000001" name="latitud" class="form-control" value="<?= $rai['latitud']??'' ?>">
    </div>
    <div class="col-md-4"><label class="form-label fw-semibold">Longitud</label>
      <input type="number" step="0.00000001" name="longitud" class="form-control" value="<?= $rai['longitud']??'' ?>">
    </div>
    <div class="col-md-4"><label class="form-label fw-semibold">Altitud (m.s.n.m)</label>
      <input type="number" step="0.01" name="altitud" class="form-control" value="<?= $rai['altitud']??'' ?>" placeholder="Ej: 4150">
    </div>
  </div>
</div>

<!-- Clasificación y personal -->
<div class="table-card p-4 mb-4">
  <h5 class="fw-bold mb-4 border-bottom pb-2"><i class="fas fa-industry me-2 text-warning"></i>III. Clasificación y Personal</h5>
  <div class="row g-3">
    <div class="col-md-4"><label class="form-label fw-semibold">Tipo de Actividad</label>
      <input name="tipo_actividad" class="form-control" value="<?= e($rai['tipo_actividad']??'') ?>" placeholder="Ej: Manufactura textil">
    </div>
    <div class="col-md-4"><label class="form-label fw-semibold">Personal Total</label>
      <input type="number" name="personal_empleado_total" class="form-control" value="<?= $rai['personal_empleado_total']??'' ?>" min="0">
    </div>
    <div class="col-md-4"><label class="form-label fw-semibold">Personal Directo</label>
      <input type="number" name="personal_empleado_directo" class="form-control" value="<?= $rai['personal_empleado_directo']??'' ?>" min="0">
    </div>
    <div class="col-md-3"><label class="form-label fw-semibold">Superficie Total (m²)</label>
      <input type="number" step="0.01" name="superficie_total_m2" class="form-control" value="<?= $rai['superficie_total_m2']??'' ?>">
    </div>
    <div class="col-md-3"><label class="form-label fw-semibold">Superficie Construida (m²)</label>
      <input type="number" step="0.01" name="superficie_construida_m2" class="form-control" value="<?= $rai['superficie_construida_m2']??'' ?>">
    </div>
    <div class="col-md-3"><label class="form-label fw-semibold">Consumo Agua (m³/mes)</label>
      <input type="number" step="0.01" name="volumen_agua_mensual_m3" class="form-control" value="<?= $rai['volumen_agua_mensual_m3']??'' ?>">
    </div>
    <div class="col-md-3"><label class="form-label fw-semibold">Tipo de Energía</label>
      <input name="tipo_energia" class="form-control" value="<?= e($rai['tipo_energia']??'') ?>" placeholder="Eléctrica, Gas, Solar...">
    </div>
  </div>
</div>

<!-- Representante legal -->
<div class="table-card p-4 mb-4">
  <h5 class="fw-bold mb-4 border-bottom pb-2"><i class="fas fa-user-tie me-2 text-primary"></i>IV. Representante Legal</h5>
  <div class="row g-3">
    <div class="col-md-6"><label class="form-label fw-semibold">Nombre Completo</label>
      <input name="representante_nombre" class="form-control" value="<?= e($rai['representante_nombre']??'') ?>">
    </div>
    <div class="col-md-3"><label class="form-label fw-semibold">Cédula de Identidad</label>
      <input name="representante_ci" class="form-control" value="<?= e($rai['representante_ci']??'') ?>">
    </div>
    <div class="col-md-3"><label class="form-label fw-semibold">Cargo</label>
      <input name="representante_cargo" class="form-control" value="<?= e($rai['representante_cargo']??'Representante Legal') ?>">
    </div>
    <div class="col-md-4"><label class="form-label fw-semibold">Teléfono Fijo</label>
      <input name="representante_telefono" class="form-control" value="<?= e($rai['representante_telefono']??'') ?>">
    </div>
    <div class="col-md-4"><label class="form-label fw-semibold">Celular</label>
      <input name="representante_celular" class="form-control" value="<?= e($rai['representante_celular']??'') ?>">
    </div>
    <div class="col-md-4"><label class="form-label fw-semibold">Email</label>
      <input type="email" name="representante_email" class="form-control" value="<?= e($rai['representante_email']??'') ?>">
    </div>
  </div>
</div>

<!-- Gestión ambiental -->
<div class="table-card p-4 mb-4">
  <h5 class="fw-bold mb-4 border-bottom pb-2"><i class="fas fa-leaf me-2 text-success"></i>V. Gestión Ambiental</h5>
  <div class="row g-3">
    <div class="col-md-6">
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="tiene_tratamiento_residuos" id="chkResid" <?= ($rai['tiene_tratamiento_residuos']??0)?'checked':'' ?>>
        <label class="form-check-label fw-semibold" for="chkResid">Cuenta con sistema de tratamiento de residuos</label>
      </div>
      <textarea name="descripcion_tratamiento" class="form-control mt-2" rows="2" placeholder="Describa el sistema..."><?= e($rai['descripcion_tratamiento']??'') ?></textarea>
    </div>
    <div class="col-md-6">
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="tiene_plan_emergencias" id="chkEmerg" <?= ($rai['tiene_plan_emergencias']??0)?'checked':'' ?>>
        <label class="form-check-label fw-semibold" for="chkEmerg">Cuenta con Plan de Emergencias Ambientales</label>
      </div>
    </div>
  </div>
</div>

<!-- Botones de acción -->
<div class="d-flex gap-3 flex-wrap mt-2 mb-4">
  <button type="submit" name="accion" value="guardar" class="btn btn-outline-primary btn-lg px-4">
    <i class="fas fa-save me-2"></i>Guardar como Borrador
  </button>
  <?php if (!$rai || in_array($rai['estado_formulario'],['borrador','con_observaciones'])): ?>
  <button type="submit" name="accion" value="enviar" class="btn btn-danger btn-lg px-5 fw-bold"
    onclick="return confirm('¿Confirma el envío del formulario RAI? No podrá editarlo después de enviado.')">
    <i class="fas fa-paper-plane me-2"></i>Enviar Formulario RAI
  </button>
  <?php endif; ?>
  <a href="/SMIA2/modules/consultor/subir_documentos.php?hoja=<?= $hoja['id'] ?>" class="btn btn-outline-warning btn-lg px-4">
    <i class="fas fa-upload me-2"></i>Ir a Documentos
  </a>
</div>
</form>

<?php endif; ?>
<?php endif; ?>

<script>
document.getElementById('btn-add-caeb').addEventListener('click', function() {
    const container = document.getElementById('caeb-container');
    const firstRow = container.querySelector('.caeb-row');
    if (!firstRow) return;
    
    // Clone the first row
    const newRow = firstRow.cloneNode(true);
    
    // Reset selected value in clone
    const select = newRow.querySelector('select');
    select.value = '';
    
    // Add to container
    container.appendChild(newRow);
});

function removeCaebRow(btn) {
    const container = document.getElementById('caeb-container');
    const rows = container.querySelectorAll('.caeb-row');
    if (rows.length > 1) {
        btn.closest('.caeb-row').remove();
    } else {
        // If it's the last row, just clear its selection
        container.querySelector('select').value = '';
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/dashboard_footer.php'; ?>
