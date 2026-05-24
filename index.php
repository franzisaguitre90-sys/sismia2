<?php
require_once __DIR__ . '/config/database.php';
$noticias = [];
try {
    $noticias = db()->query("SELECT * FROM noticias ORDER BY id DESC LIMIT 5")->fetchAll();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Portal informativo de la Dirección de Saneamiento Básico, Recursos Hídricos y Control Ambiental. Registro Ambiental Industrial - Ley 1333">
<title>SMIA2 | Dirección de Saneamiento Básico, Recursos Hídricos y Control Ambiental</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
<link rel="stylesheet" href="assets/css/portal.css">
</head>
<body data-bs-spy="scroll" data-bs-target="#mainNav" data-bs-offset="70">

<!-- NAVBAR -->
<nav id="mainNav" class="navbar navbar-expand-lg navbar-dark fixed-top nav-env">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="#inicio">
      <div class="brand-icon"><i class="fas fa-leaf"></i></div>
      <div>
        <div class="brand-title">SMIA<span class="text-warning">2</span></div>
        <div class="brand-sub">Control Ambiental</div>
      </div>
    </a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto me-3">
        <li class="nav-item"><a class="nav-link" href="#inicio">Inicio</a></li>
        <li class="nav-item"><a class="nav-link" href="#nosotros">Institución</a></li>
        <li class="nav-item"><a class="nav-link" href="#ley1333">Ley 1333</a></li>
        <li class="nav-item"><a class="nav-link" href="#rai">Trámite RAI</a></li>
        <li class="nav-item"><a class="nav-link" href="#conciencia">Concientización</a></li>
        <li class="nav-item"><a class="nav-link" href="#contacto">Contacto</a></li>
      </ul>
      <a href="login.php" class="btn btn-warning fw-bold px-4">
        <i class="fas fa-sign-in-alt me-1"></i> Acceder al SMIA2
      </a>
    </div>
  </div>
</nav>

<!-- HERO -->
<section id="inicio" class="hero-section">
  <div class="hero-overlay"></div>
  <div class="hero-particles"></div>
  <div class="container position-relative z-1">
    <div class="row align-items-center min-vh-100 py-5">
      <div class="col-lg-7 text-white animate__animated animate__fadeInLeft">
        <div class="badge bg-warning text-dark mb-3 px-3 py-2 fs-6">
          <i class="fas fa-shield-alt me-1"></i> Sistema Oficial – Bolivia
        </div>
        <h1 class="display-4 fw-bold lh-1 mb-3">
          Dirección de<br><span class="text-warning">Saneamiento Básico</span><br>
          Recursos Hídricos y<br>Control Ambiental
        </h1>
        <p class="lead mb-4 opacity-90">
          Sistema de Monitoreo e Información Ambiental para la gestión del
          <strong>Registro Ambiental Industrial (RAI)</strong> conforme a la
          <strong>Ley 1333</strong> de Medio Ambiente de Bolivia.
        </p>
        <div class="d-flex flex-wrap gap-3">
          <a href="login.php" class="btn btn-warning btn-lg fw-bold px-5 py-3 shadow-lg">
            <i class="fas fa-desktop me-2"></i>Acceder al Sistema SMIA2
          </a>
          <a href="#rai" class="btn btn-outline-light btn-lg px-4 py-3">
            <i class="fas fa-info-circle me-2"></i>¿Cómo tramitar?
          </a>
        </div>
        <div class="row mt-5 g-3">
          <div class="col-4 text-center counter-item">
            <div class="counter-num" data-target="1333">0</div>
            <small class="opacity-75">Ley de Medio Ambiente</small>
          </div>
          <div class="col-4 text-center counter-item">
            <div class="counter-num" data-target="3">0</div>
            <small class="opacity-75">Categorías RAI (A,B,C)</small>
          </div>
          <div class="col-4 text-center counter-item">
            <div class="counter-num" data-target="30">0</div>
            <small class="opacity-75">Días hábiles proceso</small>
          </div>
        </div>
      </div>
      <div class="col-lg-5 text-center animate__animated animate__fadeInRight d-none d-lg-block">
        <div class="hero-card">
          <div class="hero-card-inner">
            <i class="fas fa-leaf fa-5x text-success mb-3"></i>
            <h4 class="text-white">Comprometidos con el</h4>
            <h3 class="text-warning fw-bold">Medio Ambiente</h3>
            <p class="text-white-50 small mt-2">Protección · Control · Sostenibilidad</p>
            <div class="mt-3 d-flex justify-content-center gap-3">
              <div class="eco-badge"><i class="fas fa-water"></i><span>Agua</span></div>
              <div class="eco-badge"><i class="fas fa-tree"></i><span>Flora</span></div>
              <div class="eco-badge"><i class="fas fa-wind"></i><span>Aire</span></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="hero-wave">
    <svg viewBox="0 0 1200 120" preserveAspectRatio="none">
      <path d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z" fill="#f8f9fa"></path>
    </svg>
  </div>
</section>

<!-- NOTICIAS CAROUSEL -->
<?php if (!empty($noticias)): ?>
<section class="py-5 bg-white">
  <div class="container">
    <div class="text-center mb-4">
      <span class="badge bg-primary-soft text-primary px-3 py-2 mb-2">Últimas Novedades</span>
      <h2 class="fw-bold">Noticias y Avisos</h2>
      <div class="divider-primary mx-auto mt-3" style="width: 60px; height: 3px; background: #0d6efd; margin: 0 auto;"></div>
    </div>
    
    <div id="newsCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="4000">
      <div class="carousel-inner">
        <?php foreach($noticias as $index => $n): ?>
        <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
          <div class="row justify-content-center">
            <div class="col-md-8">
              <div class="card shadow-sm border-0 bg-light" style="border-radius: 16px;">
                <div class="card-body p-4 text-center">
                  <h4 class="card-title fw-bold text-dark mb-3"><?= htmlspecialchars($n['titulo']) ?></h4>
                  <p class="card-text text-muted mb-4" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                    <?= htmlspecialchars($n['contenido']) ?>
                  </p>
                  <a href="noticia.php?id=<?= $n['id'] ?>" class="btn btn-outline-primary px-4 rounded-pill">Leer Noticia Completa <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
                <div class="card-footer bg-transparent border-0 text-muted small pb-3 text-center">
                  <i class="fas fa-calendar-alt me-1"></i> <?= date('d/m/Y', strtotime($n['fecha_creacion'])) ?>
                </div>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <button class="carousel-control-prev" type="button" data-bs-target="#newsCarousel" data-bs-slide="prev" style="filter: invert(100%); width: 5%;">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Anterior</span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#newsCarousel" data-bs-slide="next" style="filter: invert(100%); width: 5%;">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Siguiente</span>
      </button>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- INSTITUCIÓN -->
<section id="nosotros" class="py-6 bg-light">
  <div class="container">
    <div class="text-center mb-5">
      <span class="badge bg-success-soft text-success px-3 py-2 mb-2">Nuestra Institución</span>
      <h2 class="fw-bold">Dirección de Saneamiento Básico,<br>Recursos Hídricos y Control Ambiental</h2>
      <div class="divider-green mx-auto mt-3"></div>
    </div>
    <div class="row g-4 align-items-center">
      <div class="col-lg-6">
        <div class="info-card">
          <div class="info-icon bg-primary-light"><i class="fas fa-bullseye text-primary"></i></div>
          <h4>Misión</h4>
          <p class="text-muted">Garantizar el acceso equitativo a servicios de saneamiento básico, la gestión sostenible de los recursos hídricos y el control ambiental de las actividades industriales y productivas, en cumplimiento de la normativa ambiental boliviana.</p>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="info-card">
          <div class="info-icon bg-success-light"><i class="fas fa-eye text-success"></i></div>
          <h4>Visión</h4>
          <p class="text-muted">Ser la institución líder en la gestión ambiental municipal, promoviendo el desarrollo sostenible, la protección de los ecosistemas y el bienestar de la población a través de la aplicación efectiva de la Ley 1333 y normativa complementaria.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="service-card text-center">
          <div class="service-icon"><i class="fas fa-tint"></i></div>
          <h5>Saneamiento Básico</h5>
          <p class="text-muted small">Control de sistemas de agua potable, alcantarillado y disposición de residuos sólidos municipales.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="service-card text-center">
          <div class="service-icon"><i class="fas fa-water"></i></div>
          <h5>Recursos Hídricos</h5>
          <p class="text-muted small">Gestión y protección de cuencas hidrográficas, fuentes de agua y control de la contaminación hídrica.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="service-card text-center">
          <div class="service-icon"><i class="fas fa-industry"></i></div>
          <h5>Control Ambiental</h5>
          <p class="text-muted small">Inspección, monitoreo y registro de actividades industriales a través del sistema RAI (Ley 1333).</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- LEY 1333 -->
<section id="ley1333" class="py-6 bg-gradient-green text-white">
  <div class="container">
    <div class="text-center mb-5">
      <span class="badge bg-warning text-dark px-3 py-2 mb-2">Marco Legal</span>
      <h2 class="fw-bold text-white">Ley N° 1333 – Ley del Medio Ambiente</h2>
      <p class="lead opacity-75">República de Bolivia – Promulgada el 27 de Abril de 1992</p>
      <div class="divider-yellow mx-auto mt-3"></div>
    </div>
    <div class="row g-4">
      <div class="col-lg-8">
        <div class="ley-card">
          <h4 class="text-warning"><i class="fas fa-book-open me-2"></i>¿Qué establece la Ley 1333?</h4>
          <p>La Ley 1333 es la norma marco ambiental de Bolivia que regula las actividades del ser humano con relación a la naturaleza y promueve el desarrollo sostenible del país.</p>
          <div class="row g-3 mt-2">
            <div class="col-md-6">
              <div class="ley-item"><i class="fas fa-check-circle text-warning me-2"></i>Protección y conservación del medio ambiente</div>
              <div class="ley-item"><i class="fas fa-check-circle text-warning me-2"></i>Evaluación de Impacto Ambiental (EIA)</div>
              <div class="ley-item"><i class="fas fa-check-circle text-warning me-2"></i>Control de la calidad ambiental</div>
              <div class="ley-item"><i class="fas fa-check-circle text-warning me-2"></i>Registro Ambiental Industrial (RAI)</div>
            </div>
            <div class="col-md-6">
              <div class="ley-item"><i class="fas fa-check-circle text-warning me-2"></i>Sanciones por daños ambientales</div>
              <div class="ley-item"><i class="fas fa-check-circle text-warning me-2"></i>Gestión de residuos sólidos y líquidos</div>
              <div class="ley-item"><i class="fas fa-check-circle text-warning me-2"></i>Protección de biodiversidad</div>
              <div class="ley-item"><i class="fas fa-check-circle text-warning me-2"></i>Participación ciudadana</div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="ley-stats">
          <div class="stat-item"><span class="stat-num">150+</span><span>Artículos</span></div>
          <div class="stat-item"><span class="stat-num">14</span><span>Capítulos</span></div>
          <div class="stat-item"><span class="stat-num">1992</span><span>Año promulgación</span></div>
          <div class="stat-item"><span class="stat-num">D.S. 24176</span><span>Reglamento</span></div>
        </div>
      </div>
    </div>
    <div class="row g-3 mt-3">
      <div class="col-md-3"><div class="ley-pillar"><i class="fas fa-globe-americas"></i><h6>Agua</h6><p class="small opacity-75">Protección y gestión de recursos hídricos</p></div></div>
      <div class="col-md-3"><div class="ley-pillar"><i class="fas fa-seedling"></i><h6>Suelo</h6><p class="small opacity-75">Conservación y uso responsable del suelo</p></div></div>
      <div class="col-md-3"><div class="ley-pillar"><i class="fas fa-smog"></i><h6>Aire</h6><p class="small opacity-75">Control de emisiones y calidad del aire</p></div></div>
      <div class="col-md-3"><div class="ley-pillar"><i class="fas fa-paw"></i><h6>Biodiversidad</h6><p class="small opacity-75">Protección de fauna y flora silvestre</p></div></div>
    </div>
  </div>
</section>

<!-- TRÁMITE RAI -->
<section id="rai" class="py-6">
  <div class="container">
    <div class="text-center mb-5">
      <span class="badge bg-primary-soft text-primary px-3 py-2 mb-2">Gestión de Trámites</span>
      <h2 class="fw-bold">Registro Ambiental Industrial (RAI)</h2>
      <p class="text-muted lead">Proceso de registro obligatorio para actividades industriales y productivas</p>
      <div class="divider-primary mx-auto mt-3"></div>
    </div>

    <!-- Categorías RAI -->
    <div class="row g-4 mb-5">
      <div class="col-md-4">
        <div class="rai-category border-danger">
          <div class="cat-header bg-danger text-white"><span class="cat-letter">A</span></div>
          <div class="cat-body">
            <h5>Categoría A</h5>
            <p class="text-muted small">Actividades de alto riesgo ambiental. Requieren Estudio de Evaluación de Impacto Ambiental (EEIA) completo.</p>
            <ul class="small text-muted">
              <li>Industria pesada y minería</li>
              <li>Plantas petroquímicas</li>
              <li>Grandes represas hidráulicas</li>
            </ul>
            <span class="badge bg-danger">Alto Riesgo</span>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="rai-category border-warning">
          <div class="cat-header bg-warning"><span class="cat-letter text-dark">B</span></div>
          <div class="cat-body">
            <h5>Categoría B</h5>
            <p class="text-muted small">Actividades de riesgo moderado. Requieren Evaluación de Impacto Ambiental Analítica (EEIA analítica).</p>
            <ul class="small text-muted">
              <li>Industria mediana</li>
              <li>Plantas de tratamiento</li>
              <li>Industria agroalimentaria</li>
            </ul>
            <span class="badge bg-warning text-dark">Riesgo Moderado</span>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="rai-category border-success">
          <div class="cat-header bg-success text-white"><span class="cat-letter">C</span></div>
          <div class="cat-body">
            <h5>Categoría C</h5>
            <p class="text-muted small">Actividades de bajo riesgo ambiental. Requieren solo el Registro Ambiental Industrial (RAI) simplificado.</p>
            <ul class="small text-muted">
              <li>Pequeña industria y artesanía</li>
              <li>Comercio y servicios</li>
              <li>Talleres y microempresas</li>
            </ul>
            <span class="badge bg-success">Bajo Riesgo</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Pasos del proceso -->
    <h4 class="text-center fw-bold mb-4">Proceso de Tramitación RAI en SMIA2</h4>
    <div class="process-steps">
      <div class="step-item">
        <div class="step-icon bg-warning"><span>1</span></div>
        <h6>Registro del Consultor</h6>
        <p class="small text-muted">El consultor ambiental se registra en el sistema SMIA2 con sus datos profesionales.</p>
      </div>
      <div class="step-arrow"><i class="fas fa-chevron-right"></i></div>
      <div class="step-item">
        <div class="step-icon bg-primary"><span>2</span></div>
        <h6>Ingreso Hoja de Ruta</h6>
        <p class="small text-muted">Se genera un código HDR único para el seguimiento del trámite.</p>
      </div>
      <div class="step-arrow"><i class="fas fa-chevron-right"></i></div>
      <div class="step-item">
        <div class="step-icon bg-info"><span>3</span></div>
        <h6>Carga de Documentos</h6>
        <p class="small text-muted">Se sube el formulario RAI y la documentación requerida según categoría.</p>
      </div>
      <div class="step-arrow"><i class="fas fa-chevron-right"></i></div>
      <div class="step-item">
        <div class="step-icon bg-secondary"><span>4</span></div>
        <h6>Asignación a Técnico</h6>
        <p class="small text-muted">El Director asigna el trámite al técnico ambiental correspondiente.</p>
      </div>
      <div class="step-arrow"><i class="fas fa-chevron-right"></i></div>
      <div class="step-item">
        <div class="step-icon bg-orange"><span>5</span></div>
        <h6>Revisión Técnica</h6>
        <p class="small text-muted">El técnico revisa documentos y da alta o baja al trámite con observaciones.</p>
      </div>
      <div class="step-arrow"><i class="fas fa-chevron-right"></i></div>
      <div class="step-item">
        <div class="step-icon bg-success"><span>6</span></div>
        <h6>Habilitación Ambiental</h6>
        <p class="small text-muted">La secretaria registra la habilitación o no habilitación del RAI.</p>
      </div>
    </div>

    <div class="text-center mt-5">
      <a href="login.php" class="btn btn-primary btn-lg px-5 py-3 fw-bold">
        <i class="fas fa-play-circle me-2"></i>Iniciar mi Trámite RAI
      </a>
      <p class="text-muted small mt-2">¿Aún no tiene cuenta? <a href="registro.php">Regístrese aquí como Consultor</a></p>
    </div>
  </div>
</section>

<!-- CONCIENTIZACIÓN AMBIENTAL -->
<section id="conciencia" class="py-6 bg-dark text-white">
  <div class="container">
    <div class="text-center mb-5">
      <span class="badge bg-success px-3 py-2 mb-2">Concientización Ambiental</span>
      <h2 class="fw-bold text-white">Cuidemos Nuestro Medio Ambiente</h2>
      <div class="divider-green mx-auto mt-3"></div>
    </div>
    <div class="row g-4">
      <div class="col-md-6 col-lg-3">
        <div class="aware-card">
          <div class="aware-icon"><i class="fas fa-recycle"></i></div>
          <h5>Reducir · Reutilizar · Reciclar</h5>
          <p class="small text-muted">Adopte prácticas de economía circular en su empresa para reducir el impacto ambiental de sus residuos industriales.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="aware-card">
          <div class="aware-icon"><i class="fas fa-tint"></i></div>
          <h5>Protección del Agua</h5>
          <p class="small text-muted">El agua es un derecho fundamental. Trate sus efluentes antes de descargarlos y contribuya a la protección de nuestras fuentes hídricas.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="aware-card">
          <div class="aware-icon"><i class="fas fa-smog"></i></div>
          <h5>Calidad del Aire</h5>
          <p class="small text-muted">Controle y minimice las emisiones atmosféricas de su actividad. El aire limpio es esencial para la salud pública y los ecosistemas.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="aware-card">
          <div class="aware-icon"><i class="fas fa-solar-panel"></i></div>
          <h5>Energía Limpia</h5>
          <p class="small text-muted">Migre hacia fuentes de energía renovable. La transición energética es clave para cumplir con la normativa ambiental boliviana.</p>
        </div>
      </div>
    </div>
    <div class="row mt-5 g-4 align-items-center">
      <div class="col-lg-6">
        <div class="impact-card">
          <h4 class="text-warning"><i class="fas fa-exclamation-triangle me-2"></i>¿Por qué es importante el RAI?</h4>
          <p>El Registro Ambiental Industrial no es solo un requisito legal. Es una herramienta para:</p>
          <div class="d-flex flex-column gap-2 mt-3">
            <div class="impact-item"><i class="fas fa-shield-alt text-success me-2"></i>Proteger la salud de la comunidad circundante</div>
            <div class="impact-item"><i class="fas fa-chart-line text-info me-2"></i>Mejorar la imagen y competitividad de su empresa</div>
            <div class="impact-item"><i class="fas fa-balance-scale text-warning me-2"></i>Cumplir con la normativa y evitar sanciones</div>
            <div class="impact-item"><i class="fas fa-handshake text-primary me-2"></i>Contribuir al desarrollo sostenible del municipio</div>
            <div class="impact-item"><i class="fas fa-globe-americas text-success me-2"></i>Conservar los recursos naturales para las futuras generaciones</div>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="row g-3 text-center">
          <div class="col-6"><div class="stat-box"><div class="stat-icon bg-primary"><i class="fas fa-industry"></i></div><div class="stat-num-lg">+2000</div><div>Empresas registradas</div></div></div>
          <div class="col-6"><div class="stat-box"><div class="stat-icon bg-success"><i class="fas fa-certificate"></i></div><div class="stat-num-lg">95%</div><div>Tasa de cumplimiento</div></div></div>
          <div class="col-6"><div class="stat-box"><div class="stat-icon bg-warning"><i class="fas fa-clock"></i></div><div class="stat-num-lg">30</div><div>Días hábiles proceso</div></div></div>
          <div class="col-6"><div class="stat-box"><div class="stat-icon bg-danger"><i class="fas fa-leaf"></i></div><div class="stat-num-lg">Ley 1333</div><div>Marco legal vigente</div></div></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CONTACTO -->
<section id="contacto" class="py-6 bg-light">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="fw-bold">Contacto e Información</h2>
      <div class="divider-primary mx-auto mt-3"></div>
    </div>
    <div class="row g-4 justify-content-center">
      <div class="col-md-4 text-center">
        <div class="contact-card">
          <i class="fas fa-map-marker-alt fa-2x text-danger mb-3"></i>
          <h5>Dirección</h5>
          <p class="text-muted">Dirección de Saneamiento Básico,<br>Recursos Hídricos y Control Ambiental<br>Bolivia</p>
        </div>
      </div>
      <div class="col-md-4 text-center">
        <div class="contact-card">
          <i class="fas fa-clock fa-2x text-primary mb-3"></i>
          <h5>Horario de Atención</h5>
          <p class="text-muted">Lunes a Viernes<br>08:00 – 12:00 / 14:30 – 18:30<br><small>Días hábiles</small></p>
        </div>
      </div>
      <div class="col-md-4 text-center">
        <div class="contact-card">
          <i class="fas fa-laptop fa-2x text-success mb-3"></i>
          <h5>Sistema en Línea 24/7</h5>
          <p class="text-muted">Gestione su trámite RAI en línea.<br>El sistema SMIA2 está disponible<br>las 24 horas.</p>
          <a href="login.php" class="btn btn-success mt-2">Acceder al SMIA2</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer class="footer-env py-4">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-md-6 text-white">
        <div class="d-flex align-items-center gap-2 mb-1">
          <i class="fas fa-leaf text-warning"></i>
          <strong>SMIA2</strong> – Sistema de Monitoreo e Información Ambiental
        </div>
        <small class="opacity-60">Dirección de Saneamiento Básico, Recursos Hídricos y Control Ambiental<br>República de Bolivia – Ley N° 1333</small>
      </div>
      <div class="col-md-6 text-end">
        <a href="login.php" class="btn btn-warning btn-sm px-4 fw-bold">
          <i class="fas fa-sign-in-alt me-1"></i> Acceder al SMIA2
        </a>
        <div class="text-white-50 small mt-2">© <?= date('Y') ?> SMIA2 v2.0 – Todos los derechos reservados</div>
      </div>
    </div>
  </div>
</footer>

<!-- Scroll-to-top -->
<button id="scrollTop" class="btn btn-success btn-sm rounded-circle shadow" style="position:fixed;bottom:25px;right:25px;width:44px;height:44px;display:none;z-index:999">
  <i class="fas fa-arrow-up"></i>
</button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/portal.js"></script>
</body>
</html>
