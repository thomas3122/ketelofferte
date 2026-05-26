<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$stmt = $pdo->prepare("
    SELECT slug, title, meta_description, hero_title, hero_subtitle, related_problem_key
    FROM knowledge_pages
    WHERE is_active = 1
    ORDER BY title ASC
");

$stmt->execute();
$pages = $stmt->fetchAll();

$pageTitle = 'Kennisbank cv-ketel problemen | KetelOfferte24.nl';
$metaDescription = 'Lees praktische uitleg over cv-ketel storing, lekkage, geen warm water, drukverlies en wanneer repareren of vervangen verstandig is.';
$canonicalUrl = app_url('/kennisbank/');
$socialImageUrl = app_url('/assets/img/social-preview.png');
?>
<!DOCTYPE html>
<html lang="nl">
<head>
<?= tracking_head_config() ?>

  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <title><?= e($pageTitle) ?></title>
  <meta name="description" content="<?= e($metaDescription) ?>" />

  <meta property="og:type" content="website">
  <meta property="og:site_name" content="KetelOfferte24.nl">
  <meta property="og:title" content="<?= e($pageTitle) ?>">
  <meta property="og:description" content="<?= e($metaDescription) ?>">
  <meta property="og:url" content="<?= e($canonicalUrl) ?>">
  <meta property="og:image" content="<?= e($socialImageUrl) ?>">

  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= e($pageTitle) ?>">
  <meta name="twitter:description" content="<?= e($metaDescription) ?>">
  <meta name="twitter:image" content="<?= e($socialImageUrl) ?>">

  <link rel="canonical" href="<?= e($canonicalUrl) ?>">
  <link rel="icon" type="image/png" href="/assets/img/favicon.png">
  <link rel="apple-touch-icon" href="/assets/img/favicon.png">
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header class="site-header">
  <div class="container header-inner">
    <a href="/" class="brand" aria-label="KetelOfferte24.nl home">
      <img src="/assets/img/logo.png" alt="KetelOfferte24.nl" class="brand-logo">
      <span class="brand-text">
        KetelOfferte<span>24</span><small>.nl</small>
      </span>
    </a>

    <a href="/#ketelcheck" class="btn emergency-btn">
      <span class="emergency-dot"></span>
      Ketelcheck
    </a>
  </div>
</header>

<main>
  <section class="hero">
    <div class="container hero-inner">
      <div class="eyebrow">
        <span class="eyebrow-dot"></span>
        Kennisbank
      </div>

      <h1>Alles over cv-ketel problemen</h1>

      <p class="hero-subtitle">
        Praktische uitleg over storing, lekkage, geen warm water, drukverlies en wanneer repareren of vervangen verstandig is.
      </p>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div class="section-head">
        <div class="section-label">Artikelen</div>
        <h2>Populaire onderwerpen</h2>
        <p class="section-intro">
          Kies een onderwerp en lees wat u veilig zelf kunt controleren.
        </p>
      </div>

      <?php if (!$pages): ?>
        <div class="region-empty">
          Er staan nog geen kennisbankartikelen actief.
        </div>
      <?php else: ?>
        <div class="region-overview-grid">
          <?php foreach ($pages as $page): ?>
            <a class="region-overview-card" href="/kennisbank/<?= e($page['slug']) ?>/">
              <span class="region-overview-icon">↗</span>

              <span class="region-overview-content">
                <strong><?= e($page['hero_title']) ?></strong>
                <small><?= e($page['related_problem_key'] ?? 'cv-ketel') ?></small>
                <em><?= e($page['meta_description']) ?></em>
              </span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <section class="section section-soft">
    <div class="container">
      <div class="region-strip">
        <h2>Twijfelt u over uw cv-ketel?</h2>
        <p>
          Doe de snelle ketelcheck en ontvang binnen 24 uur duidelijkheid, advies of een passende offerte.
        </p>
      </div>
    </div>
  </section>
</main>

<?= site_footer_html() ?>

<?= cookie_banner_html() ?>
<script src="/assets/js/legal-consent.js"></script>
</body>
</html>
