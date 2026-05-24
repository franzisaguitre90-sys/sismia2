<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('admin');

$uid = currentUserId();
$pageTitle = 'Gestión de Noticias';
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        db()->prepare("DELETE FROM noticias WHERE id=?")->execute([$_POST['delete_id']]);
        $success = 'Noticia eliminada correctamente.';
    } else {
        $titulo = sanitize($_POST['titulo'] ?? '');
        $contenido = sanitize($_POST['contenido'] ?? '');
        if ($titulo && $contenido) {
            db()->prepare("INSERT INTO noticias (titulo, contenido, autor_id) VALUES (?, ?, ?)")->execute([$titulo, $contenido, $uid]);
            $success = 'Noticia creada exitosamente.';
        } else {
            $error = 'Todos los campos son obligatorios.';
        }
    }
}

$noticias = db()->query("SELECT n.*, u.nombre, u.apellido FROM noticias n JOIN usuarios u ON n.autor_id=u.id ORDER BY id DESC")->fetchAll();

require_once __DIR__ . '/../../includes/dashboard_header.php';
?>

<?php if ($error): ?><div class="alert alert-danger rounded-3"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success rounded-3"><?= e($success) ?></div><?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="table-card p-4">
            <h5 class="fw-bold mb-3"><i class="fas fa-plus-circle me-2 text-primary"></i>Nueva Noticia</h5>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Título</label>
                    <input type="text" name="titulo" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Contenido (1 plana max)</label>
                    <textarea name="contenido" class="form-control" rows="15" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary w-100">Publicar Noticia</button>
            </form>
        </div>
    </div>
    <div class="col-md-8">
        <div class="table-card p-4">
            <h5 class="fw-bold mb-3"><i class="fas fa-newspaper me-2 text-primary"></i>Noticias Publicadas (UEPS)</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Título</th><th>Fecha</th><th>Autor</th><th>Acción</th></tr></thead>
                    <tbody>
                        <?php if (empty($noticias)): ?>
                        <tr><td colspan="4" class="text-center text-muted">No hay noticias.</td></tr>
                        <?php endif; ?>
                        <?php foreach($noticias as $n): ?>
                        <tr>
                            <td><?= e($n['titulo']) ?></td>
                            <td><?= fdate($n['fecha_creacion'], 'd/m/Y H:i') ?></td>
                            <td><?= e($n['nombre'] . ' ' . $n['apellido']) ?></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('¿Eliminar noticia?');">
                                    <input type="hidden" name="delete_id" value="<?= $n['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/dashboard_footer.php'; ?>
