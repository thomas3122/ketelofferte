<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$stmt = $pdo->prepare("
    SELECT
        slug,
        city,
        municipality,
        area_type,
        page_template,
        region,
        meta_description
    FROM landing_pages
    WHERE is_active = 1
    ORDER BY
        CASE
            WHEN area_type = 'city' THEN 0
            ELSE 1
        END,
        municipality ASC,
        city ASC
");

$stmt->execute();
$landingPages = $stmt->fetchAll();

$cityPages = [];
$pagesByMunicipality = [];

foreach ($landingPages as $page) {
    $municipality = trim((string) ($page['municipality'] ?? ''));

    if ($municipality === '') {
        $municipality = trim((string) ($page['city'] ?? 'Overige regio’s'));
    }

    $isCityPage = (($page['area_type'] ?? '') === 'city') || (($page['page_template'] ?? '') === 'city');

    if ($isCityPage) {
        $cityPages[$municipality] = $page;
        continue;
    }

    if (!isset($pagesByMunicipality[$municipality])) {
        $pagesByMunicipality[$municipality] = [];
    }

    $pagesByMunicipality[$municipality][] = $page;
}

ksort($pagesByMunicipality);

$pageTitle = 'Alle regio’s voor cv-ketel hulp | KetelOfferte24.nl';
$metaDescription = 'Bekijk alle steden, wijken en regio’s waar KetelOfferte24.nl helpt bij cv-ketel storing, lekkage, geen warm water, drukverlies of vervangen.';
$canonicalUrl = app_url('/regios/');
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
  <meta property="og:image:secure_url" content="<?= e($socialImageUrl) ?>">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">

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
  <section class="hero region-overview-hero">
    <div class="container hero-inner">
      <div class="eyebrow">
        <span class="eyebrow-dot"></span>
        Werkgebied Zuid-Holland
      </div>

      <h1>CV-ketel hulp per regio</h1>

      <p class="hero-subtitle">
        Bekijk per stad, wijk of gebied waar KetelOfferte24.nl helpt bij cv-ketel storing,
        lekkage, geen warm water, drukverlies of twijfel over repareren of vervangen.
      </p>

      <div class="hero-trust-strip" aria-label="Waarom KetelOfferte24.nl">
        <span>Gratis check</span>
        <span>Reactie binnen 24 uur</span>
        <span>Vrijblijvend</span>
        <span>Foto’s meesturen kan</span>
      </div>
    </div>
  </section>

  <?php if (!$landingPages): ?>
    <section class="section">
      <div class="container">
        <div class="section-head">
          <div class="section-label">Regio’s</div>
          <h2>Nog geen regio’s actief</h2>
          <p class="section-intro">Er zijn nog geen actieve regio’s ingesteld.</p>
        </div>
      </div>
    </section>
  <?php else: ?>

    <?php if (!empty($cityPages)): ?>
      <section class="section" aria-labelledby="main-cities-title">
        <div class="container">
          <div class="section-head">
            <div class="section-label">Hoofdregio’s</div>
            <h2 id="main-cities-title">Kies uw stad of gemeente</h2>
            <p class="section-intro">
              Deze hoofdpagina’s geven extra uitleg en verwijzen naar wijken en gebieden binnen dezelfde gemeente.
            </p>
          </div>

          <div class="regios-city-grid">
            <?php foreach ($cityPages as $page): ?>
              <a class="regios-city-card" href="/<?= e($page['slug']) ?>/">
                <span class="regios-city-pill">Hoofdpagina</span>
                <strong><?= e($page['city']) ?></strong>
                <small><?= e($page['region'] ?? 'Zuid-Holland') ?></small>
                <em><?= e($page['meta_description'] ?? '') ?></em>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <?php if (!empty($pagesByMunicipality)): ?>
      <section class="section section-soft" aria-labelledby="municipality-title">
        <div class="container">
          <div class="section-head">
            <div class="section-label">Wijken en gebieden</div>
            <h2 id="municipality-title">Regio’s per stad</h2>
            <p class="section-intro">
              Selecteer hieronder direct uw wijk, plaats of gebied. De pagina opent met een lokale ketelcheck.
            </p>
          </div>

          <div class="regios-groups">
            <?php foreach ($pagesByMunicipality as $municipality => $pages): ?>
              <?php
                $anchorId = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $municipality));
                $cityPage = $cityPages[$municipality] ?? null;
              ?>

              <section class="regios-group" aria-labelledby="regios-group-<?= e($anchorId) ?>">
                <div class="regios-group-head">
                  <div>
                    <h3 id="regios-group-<?= e($anchorId) ?>"><?= e($municipality) ?></h3>
                    <p>
                      <?= count($pages) ?> gebied<?= count($pages) === 1 ? '' : 'en' ?> beschikbaar voor cv-ketel hulp.
                    </p>
                  </div>

                  <?php if ($cityPage): ?>
                    <a class="regios-main-link" href="/<?= e($cityPage['slug']) ?>/">
                      Bekijk <?= e($municipality) ?>
                    </a>
                  <?php endif; ?>
                </div>

                <div class="regios-area-grid">
                  <?php foreach ($pages as $page): ?>
                    <a class="regios-area-card" href="/<?= e($page['slug']) ?>/">
                      <span>↗</span>
                      <strong><?= e($page['city']) ?></strong>
                    </a>
                  <?php endforeach; ?>
                </div>
              </section>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    <?php endif; ?>

  <?php endif; ?>

  <section class="section" aria-labelledby="seo-regios-title">
    <div class="container">
      <article class="regios-seo-card">
        <div class="section-label">Lokale cv-ketel hulp</div>
        <h2 id="seo-regios-title">CV-ketel kapot? Ontvang snel een offerte</h2>

        <p>
          CV-ketelproblemen verschillen per woning en per situatie. In sommige woningen is sprake van een eigen
          cv-ketel, terwijl andere gebouwen gebruikmaken van blokverwarming of een collectieve installatie.
          Daarom helpt een lokale pagina om sneller de juiste context te geven.
        </p>

        <p>
          Via de ketelcheck geeft u aan wat er aan de hand is, waar het probleem speelt en of u advies,
          reparatievergelijking of een offerte zoekt. Zo kan de aanvraag gerichter worden beoordeeld.
        </p>

        <div class="regios-seo-points">
          <div>
            <strong>Veelvoorkomende problemen</strong>
            <span>Storing, lekkage, geen warm water, drukverlies, lawaai of hoge energiekosten.</span>
          </div>

          <div>
            <strong>Repareren of vervangen</strong>
            <span>Bij oudere ketels kan vergelijken verstandig zijn voordat u kosten maakt.</span>
          </div>

          <div>
            <strong>Binnen 24 uur duidelijkheid</strong>
            <span>Na de aanvraag ontvangt u advies of een passende vervolgstap.</span>
          </div>
        </div>
      </article>
    </div>
  </section>

  <section class="section section-soft">
    <div class="container">
      <div class="region-strip">
        <h2>Problemen met uw cv-ketel?</h2>
        <p>
          Doe de snelle ketelcheck en ontvang binnen 24 uur duidelijkheid, advies of een passende offerte.
        </p>
        <br>
        <a href="/#ketelcheck" class="btn btn-primary">Start ketelcheck</a>
      </div>
    </div>
  </section>
</main>

<?= site_footer_html() ?>

<?= cookie_banner_html() ?>
<script src="/assets/js/legal-consent.js"></script>
</body>
</html>
