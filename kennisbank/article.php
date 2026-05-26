<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$slug = trim((string) ($_GET['slug'] ?? ''));

if ($slug === '' || !preg_match('/^[a-z0-9-]+$/', $slug)) {
    http_response_code(404);
    exit('Artikel niet gevonden.');
}

$stmt = $pdo->prepare("
    SELECT *
    FROM knowledge_pages
    WHERE slug = :slug
      AND is_active = 1
    LIMIT 1
");

$stmt->execute([
    ':slug' => $slug,
]);

$page = $stmt->fetch();

if (!$page) {
    http_response_code(404);
    exit('Artikel niet gevonden.');
}

$canonicalUrl = app_url('/kennisbank/' . $page['slug'] . '/');
$socialImageUrl = app_url('/assets/img/social-preview.png');

$structuredData = [
    [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        '@id' => $canonicalUrl . '#article',
        'headline' => $page['hero_title'],
        'description' => $page['meta_description'],
        'url' => $canonicalUrl,
        'publisher' => [
            '@type' => 'Organization',
            '@id' => app_url('/#organization'),
            'name' => 'KetelOfferte24.nl',
            'url' => app_url('/'),
            'logo' => app_url('/assets/img/logo.png'),
        ],
        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id' => $canonicalUrl . '#webpage',
        ],
    ],
    [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        '@id' => $canonicalUrl . '#breadcrumb',
        'itemListElement' => [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'Home',
                'item' => app_url('/'),
            ],
            [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => 'Kennisbank',
                'item' => app_url('/kennisbank/'),
            ],
            [
                '@type' => 'ListItem',
                'position' => 3,
                'name' => $page['hero_title'],
                'item' => $canonicalUrl,
            ],
        ],
    ],
];
?>
<!DOCTYPE html>
<html lang="nl">
<head>
<?= tracking_head_config() ?>

  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <title><?= e($page['title']) ?></title>
  <meta name="description" content="<?= e($page['meta_description']) ?>" />

  <meta property="og:type" content="article">
  <meta property="og:site_name" content="KetelOfferte24.nl">
  <meta property="og:title" content="<?= e($page['hero_title']) ?>">
  <meta property="og:description" content="<?= e($page['meta_description']) ?>">
  <meta property="og:url" content="<?= e($canonicalUrl) ?>">
  <meta property="og:image" content="<?= e($socialImageUrl) ?>">

  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= e($page['hero_title']) ?>">
  <meta name="twitter:description" content="<?= e($page['meta_description']) ?>">
  <meta name="twitter:image" content="<?= e($socialImageUrl) ?>">

  <link rel="canonical" href="<?= e($canonicalUrl) ?>">
  <link rel="icon" type="image/png" href="/assets/img/favicon.png">
  <link rel="apple-touch-icon" href="/assets/img/favicon.png">

  <script type="application/ld+json">
<?= json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
  </script>

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

      <h1><?= e($page['hero_title']) ?></h1>

      <p class="hero-subtitle">
        <?= e($page['hero_subtitle']) ?>
      </p>

      <a href="/#ketelcheck" class="btn btn-primary">Doe de ketelcheck</a>
    </div>
  </section>

  <section class="section">
    <div class="container knowledge-container">
      <?php if (!empty($page['intro_title']) || !empty($page['intro_text'])): ?>
        <div class="knowledge-intro">
          <?php if (!empty($page['intro_title'])): ?>
            <h2><?= e($page['intro_title']) ?></h2>
          <?php endif; ?>

          <?php if (!empty($page['intro_text'])): ?>
            <p><?= e($page['intro_text']) ?></p>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <article class="knowledge-content">
        <?= $page['content_html'] ?>
      </article>
    </div>
  </section>

  <?php if (!empty($page['cta_title']) || !empty($page['cta_text'])): ?>
    <section class="section section-soft">
      <div class="container">
        <div class="region-strip">
          <?php if (!empty($page['cta_title'])): ?>
            <h2><?= e($page['cta_title']) ?></h2>
          <?php endif; ?>

          <?php if (!empty($page['cta_text'])): ?>
            <p><?= e($page['cta_text']) ?></p>
          <?php endif; ?>

          <br>
          <a href="/#ketelcheck" class="btn btn-primary">Start ketelcheck</a>
        </div>
      </div>
    </section>
  <?php endif; ?>
</main>

<?= site_footer_html() ?>

<?= cookie_banner_html() ?>
<script src="/assets/js/legal-consent.js"></script>
</body>
</html>
