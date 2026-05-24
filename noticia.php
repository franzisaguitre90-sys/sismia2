<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$noticia = null;
if ($id) {
    $stmt = db()->prepare("SELECT n.*, u.nombre, u.apellido FROM noticias n JOIN usuarios u ON n.autor_id=u.id WHERE n.id=?");
    $stmt->execute([$id]);
    $noticia = $stmt->fetch();
}
if (!$noticia) {
    die("Noticia no encontrada.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($noticia['titulo']) ?> | SMIA2</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="index.php"><i class="fas fa-leaf text-success me-2"></i>SMIA2</a>
  </div>
</nav>
<div class="container py-5">
    <a href="index.php" class="btn btn-outline-secondary mb-4"><i class="fas fa-arrow-left me-2"></i>Volver al Inicio</a>
    <div class="card shadow border-0" style="border-radius: 16px;">
        <div class="card-body p-4 p-md-5">
            <h1 class="card-title fw-bold text-success mb-3"><?= e($noticia['titulo']) ?></h1>
            <div class="text-muted small mb-4 border-bottom pb-3">
                <i class="fas fa-user-circle me-1"></i> Publicado por: <strong><?= e($noticia['nombre'] . ' ' . $noticia['apellido']) ?></strong> | 
                <i class="fas fa-calendar-alt ms-2 me-1"></i> <?= date('d/m/Y H:i', strtotime($noticia['fecha_creacion'])) ?>
            </div>
            <div class="card-text" style="white-space: pre-wrap; font-size: 1.1rem; line-height: 1.8; color: #333;">
                <?= e($noticia['contenido']) ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
