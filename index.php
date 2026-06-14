<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$questions = require __DIR__ . '/config/questions.php';
$landingPage = resolve_landing_page($pdo);
$seoNoindex = !empty($landingPage['_seo_noindex']);

$isCityPage = (($landingPage['page_template'] ?? '') === 'city') || (($landingPage['area_type'] ?? '') === 'city');

$landingPagesStmt = $pdo->prepare("
    SELECT slug, city, region, municipality, area_type
    FROM landing_pages
    WHERE is_active = 1
    ORDER BY
      CASE
        WHEN area_type = 'city' THEN 0
        ELSE 1
      END,
      city ASC
    LIMIT 18
");

$landingPagesStmt->execute();
$landingPages = $landingPagesStmt->fetchAll();

$nearbyPages = [];

if (!empty($landingPage['municipality']) && !empty($landingPage['slug'])) {
    $nearbyPagesStmt = $pdo->prepare("
        SELECT slug, city, municipality, area_type
        FROM landing_pages
        WHERE is_active = 1
          AND municipality = :municipality
          AND slug != :current_slug
        ORDER BY
          CASE
            WHEN area_type = 'city' THEN 0
            WHEN area_type = 'district' THEN 1
            WHEN area_type = 'neighborhood' THEN 2
            ELSE 3
          END,
          city ASC
        LIMIT 8
    ");

    $nearbyPagesStmt->execute([
        ':municipality' => $landingPage['municipality'],
        ':current_slug' => $landingPage['slug'],
    ]);

    $nearbyPages = $nearbyPagesStmt->fetchAll();
}

$cityChildPages = [];

if ($isCityPage && !empty($landingPage['municipality']) && !empty($landingPage['slug'])) {
    $cityChildPagesStmt = $pdo->prepare("
        SELECT slug, city, municipality, area_type
        FROM landing_pages
        WHERE is_active = 1
          AND municipality = :municipality
          AND slug != :current_slug
          AND area_type != 'city'
        ORDER BY city ASC
    ");

    $cityChildPagesStmt->execute([
        ':municipality' => $landingPage['municipality'],
        ':current_slug' => $landingPage['slug'],
    ]);

    $cityChildPages = $cityChildPagesStmt->fetchAll();
}

$knowledgePagesStmt = $pdo->prepare("
    SELECT slug, hero_title, meta_description, related_problem_key
    FROM knowledge_pages
    WHERE is_active = 1
    ORDER BY title ASC
    LIMIT 6
");

$knowledgePagesStmt->execute();
$knowledgePages = $knowledgePagesStmt->fetchAll();

$leadError = $_SESSION['lead_error'] ?? null;
unset($_SESSION['lead_error']);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function get_utm_value(string $key): string
{
    return htmlspecialchars($_GET[$key] ?? '', ENT_QUOTES, 'UTF-8');
}

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$canonicalUrl = canonical_url_for_path($currentPath);
$socialImageUrl = app_url('/assets/img/social-preview.png');
$structuredData = get_structured_data($landingPage, $canonicalUrl);
$cityName = (string) ($landingPage['city'] ?? '');
$regionName = (string) ($landingPage['region'] ?? 'Zuid-Holland');
$localAreaLabel = $cityName !== '' ? $cityName : $regionName;
$localIntroLabel = trim((string) ($landingPage['hero_eyebrow'] ?? '')) ?: 'Lokale ketelcheck';
$localIntroTitle = trim((string) ($landingPage['intro_title'] ?? '')) ?: 'Snel duidelijkheid over uw cv-ketel';
$localIntroText = trim((string) ($landingPage['intro_text'] ?? '')) ?: 'Doe de ketelcheck bij storing, lekkage, geen warm water of twijfel over repareren of vervangen.';
$localRegionText = trim((string) ($landingPage['region_text'] ?? '')) ?: 'KetelOfferte24.nl helpt bij cv-ketelproblemen in Zuid-Holland.';
$pageTheme = get_landing_page_theme($landingPage);
$problemSolution = get_problem_solution_content($landingPage);
$localIntentPanels = get_local_intent_panels($landingPage);
$landingPageId = isset($landingPage['id']) ? (int) $landingPage['id'] : null;
$reviews = get_landing_page_reviews($pdo, $landingPageId);
$reviewsStructuredData = get_reviews_structured_data($reviews, $canonicalUrl);

$formSectionLabel = trim((string) ($landingPage['form_section_label'] ?? '')) ?: 'Gratis check';
$formSectionTitle = trim((string) ($landingPage['form_section_title'] ?? '')) ?: 'Start de gratis ketelcheck';
$formSectionIntro = trim((string) ($landingPage['form_section_intro'] ?? '')) ?: 'Beantwoord een paar korte vragen over uw cv-ketel. U kunt foto’s meesturen en ontvangt snel duidelijkheid over advies, reparatie of een passende offerte.';
$checkAsideTitle = trim((string) ($landingPage['check_aside_title'] ?? '')) ?: 'Ketelcheck';
$checkAsideText = trim((string) ($landingPage['check_aside_text'] ?? '')) ?: 'Vul kort uw klacht in. Wij bekijken of advies, reparatie of cv-ketel vervangen logisch is.';
$conversionLabel = trim((string) ($landingPage['conversion_label'] ?? '')) ?: 'Offerte aanvragen';
$conversionTitle = trim((string) ($landingPage['conversion_title'] ?? '')) ?: 'Snel weten wat verstandig is voor uw cv-ketel in ' . $localAreaLabel;
$conversionText = trim((string) ($landingPage['conversion_text'] ?? '')) ?: 'Een aanvraag is pas waardevol als die duidelijk is. Daarom vragen we eerst naar de klacht, de situatie en eventueel foto’s. Zo krijgt u gerichter advies en voorkomt u onnodig heen-en-weer bellen.';
$trustLabel = trim((string) ($landingPage['trust_label'] ?? '')) ?: 'Betrouwbare aanvraag';
$trustTitle = trim((string) ($landingPage['trust_title'] ?? '')) ?: 'Waarom kiezen voor KetelOfferte24.nl?';
$trustIntro = trim((string) ($landingPage['trust_intro'] ?? '')) ?: 'U krijgt snel duidelijkheid zonder direct ergens aan vast te zitten.';
$problemsLabel = trim((string) ($landingPage['problems_label'] ?? '')) ?: 'Problemen';
$problemsTitle = trim((string) ($landingPage['problems_title'] ?? '')) ?: 'Wij helpen bij';
$reviewsLabel = trim((string) ($landingPage['reviews_label'] ?? '')) ?: 'Ervaringen';
$reviewsTitle = trim((string) ($landingPage['reviews_title'] ?? '')) ?: 'Wat klanten zeggen over de ketelcheck';
$reviewsIntro = trim((string) ($landingPage['reviews_intro'] ?? '')) ?: 'Lees hoe andere klanten snel duidelijkheid kregen over hun cv-ketel.';

$heroBadges = decode_landing_json_list($landingPage['hero_badges_json'] ?? null, [
    'Storing',
    'Lekkage',
    'Geen warm water',
    'Oude ketel',
    'Lage druk',
]);

$trustCards = decode_landing_json_list($landingPage['trust_cards_json'] ?? null, [
    ['icon' => '24', 'title' => 'Binnen 24 uur duidelijkheid', 'text' => 'Uw aanvraag wordt beoordeeld zodat u snel weet welke vervolgstap logisch is.'],
    ['icon' => '✓', 'title' => 'Geen verplichting', 'text' => 'De ketelcheck helpt u eerst inzicht krijgen. U zit niet direct vast aan een opdracht.'],
    ['icon' => '📷', 'title' => 'Foto’s meesturen', 'text' => 'Met foto’s van uw cv-ketel kunnen wij sneller en gerichter meekijken.'],
    ['icon' => '↘', 'title' => 'Repareren of vervangen', 'text' => 'Bij twijfel kijken we mee of reparatie nog logisch is of vervangen verstandiger wordt.'],
]);

$problemCards = decode_landing_json_list($landingPage['problem_cards_json'] ?? null, [
    ['icon' => '⚠', 'title' => 'CV-ketel storing', 'text' => 'Snel duidelijkheid bij foutcodes of uitval.'],
    ['icon' => '♨', 'title' => 'Geen warm water', 'text' => 'Check of advies, reparatie of vervanging logisch is.'],
    ['icon' => '💧', 'title' => 'Lekkende ketel', 'text' => 'Een cv-ketel die lekt moet serieus bekeken worden.'],
    ['icon' => '↘', 'title' => 'Drukverlies', 'text' => 'Als de druk steeds zakt, kan er meer aan de hand zijn.'],
    ['icon' => '⌛', 'title' => 'Oude cv-ketel', 'text' => 'Bij oudere ketels is repareren of vervangen vergelijken verstandig.'],
    ['icon' => '€', 'title' => 'Hoge energiekosten', 'text' => 'Een oudere ketel kan onnodig veel verbruiken.'],
]);
?>
<!DOCTYPE html>
<html lang="nl">
<head>

<?= tracking_head_config() ?>

  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <title><?= e($landingPage['page_title']) ?></title>
  <meta name="description" content="<?= e($landingPage['meta_description']) ?>" />
  <?php if ($seoNoindex): ?>
    <meta name="robots" content="noindex,follow">
  <?php endif; ?>

  <meta property="og:type" content="website">
  <meta property="og:site_name" content="KetelOfferte24.nl">
  <meta property="og:title" content="<?= e($landingPage['hero_title']) ?>">
  <meta property="og:description" content="<?= e($landingPage['meta_description']) ?>">
  <meta property="og:url" content="<?= e($canonicalUrl) ?>">
  <meta property="og:image" content="<?= e($socialImageUrl) ?>">
  <meta property="og:image:secure_url" content="<?= e($socialImageUrl) ?>">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:image:alt" content="KetelOfferte24.nl - snelle ketelcheck voor cv-ketelproblemen">

  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= e($landingPage['hero_title']) ?>">
  <meta name="twitter:description" content="<?= e($landingPage['meta_description']) ?>">
  <meta name="twitter:image" content="<?= e($socialImageUrl) ?>">

  <script type="application/ld+json">
<?= $structuredData ?>
  </script>
  <?php if ($reviewsStructuredData !== ''): ?>
    <script type="application/ld+json">
<?= $reviewsStructuredData ?>
    </script>
  <?php endif; ?>

  <link rel="canonical" href="<?= e($canonicalUrl) ?>">
  <link rel="icon" type="image/png" href="/assets/img/favicon.png">
  <link rel="apple-touch-icon" href="/assets/img/favicon.png">

  <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="<?= e($pageTheme['class']) ?>">
<?php if (is_logged_in() && !empty($landingPage['id'])): ?>
  <div class="admin-preview-bar">
    <div class="container admin-preview-inner">
      <span>Admin preview: <?= e((string) ($landingPage['city'] ?? $landingPage['slug'] ?? 'SEO pagina')) ?></span>
      <a href="/admin/seo/landing-page-edit.php?id=<?= (int) $landingPage['id'] ?>">Bewerk pagina</a>
    </div>
  </div>
<?php endif; ?>

<header class="site-header">
  <div class="container header-inner">
    <a href="/" class="brand" aria-label="KetelOfferte24.nl home">
      <img src="/assets/img/logo.avif" alt="KetelOfferte24.nl" class="brand-logo">
      <span class="brand-text">
        KetelOfferte<span>24</span><small>.nl</small>
      </span>
    </a>

    <nav class="header-links" aria-label="Hoofdnavigatie">
      <a href="/regios/">Regio’s</a>
      <a href="/kennisbank/">Kennisbank</a>
      <a href="#ketelcheck">Ketelcheck</a>
    </nav>

    <button type="button" class="btn emergency-btn" id="openEmergencyModal">
      <span class="emergency-dot"></span>
      Spoedoproep
    </button>
  </div>
</header>

<main id="top">
  <section class="hero" aria-labelledby="hero-title">
    <div class="container hero-inner">
      <div class="eyebrow">
        <span class="eyebrow-dot"></span>
        <?= e($landingPage['hero_eyebrow']) ?>
      </div>

      <h1 id="hero-title"><?= e($landingPage['hero_title']) ?></h1>

      <p class="hero-subtitle">
        <?= e($landingPage['hero_subtitle']) ?>
      </p>

      <div class="hero-badges" aria-label="Veelvoorkomende cv-ketelproblemen">
        <?php foreach ($heroBadges as $badge): ?>
          <?php if (trim((string) $badge) !== ''): ?>
            <span class="badge"><?= e((string) $badge) ?></span>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>

      <div class="hero-actions">
        <a href="#ketelcheck" class="btn btn-primary btn-soft-attention">Start gratis ketelcheck</a>
        <a href="/regios/" class="btn btn-ghost">Bekijk werkgebied</a>
      </div>

      <div class="hero-trust-strip" aria-label="Waarom KetelOfferte24.nl">
        <span>Gratis ketelcheck</span>
        <span>Binnen 24 uur reactie</span>
        <span>Geen verplichting</span>
        <span>Foto’s meesturen mogelijk</span>
      </div>

      <div class="hero-proof-grid" aria-label="Zekerheden">
        <div>
          <strong>24 uur</strong>
          <span>reactie op uw aanvraag</span>
        </div>
        <div>
          <strong><?= e($localAreaLabel) ?></strong>
          <span>lokale ketelcheck</span>
        </div>
        <div>
          <strong>Offerte</strong>
          <span>alleen als dat logisch is</span>
        </div>
      </div>
    </div>
  </section>

  <section class="section local-intro-section" aria-labelledby="local-intro-title">
    <div class="container">
      <div class="local-intro">
        <div>
          <div class="section-label"><?= e($localIntroLabel) ?></div>
          <h2 id="local-intro-title"><?= e($localIntroTitle) ?></h2>
          <p>
            <?= e($localIntroText) ?>
          </p>
        </div>

        <aside class="local-intro-panel" aria-label="Lokale ketelcheck">
          <strong><?= !empty($landingPage['city']) ? e($landingPage['city']) : e($regionName) ?></strong>
          <span><?= e($localRegionText) ?></span>
        </aside>
      </div>
    </div>
  </section>

  <section class="section" id="ketelcheck" aria-labelledby="check-title">
    <div class="container">
      <div class="section-head">
        <div class="section-label"><?= e($formSectionLabel) ?></div>
        <h2 id="check-title"><?= e($formSectionTitle) ?></h2>
        <p class="section-intro">
          <?= e($formSectionIntro) ?>
        </p>
      </div>

      <div class="check-proof-row" aria-label="Zekerheden bij de ketelcheck">
        <span>Gratis aanvraag</span>
        <span>Foto’s meesturen</span>
        <span>Binnen 24 uur reactie</span>
        <span>Geen verplichting</span>
      </div>

      <div class="check-shell">
        <aside class="check-aside">
          <div>
            <h3><?= e($checkAsideTitle) ?></h3>
            <p><?= e($checkAsideText) ?></p>
          </div>

          <div>
            <div class="progress-meta">
              <span id="progressLabel">Stap 1 van <?= count($questions) ?></span>
              <span id="progressPercent">17%</span>
            </div>

            <div class="progress-track" aria-hidden="true">
              <div class="progress-bar" id="progressBar"></div>
            </div>

            <div class="aside-points">
              <div><span>✓</span> Binnen 24 uur reactie</div>
              <div><span>✓</span> Advies of passende offerte</div>
              <div><span>✓</span> Gericht op <?= e($regionName) ?></div>
            </div>
          </div>
        </aside>

        <form
          class="form-card"
          id="ketelForm"
          action="/actions/create_lead.php"
          method="post"
          enctype="multipart/form-data"
          novalidate
        >
          <input type="hidden" name="landing_page_id" value="<?= e((string) ($landingPage['id'] ?? '')) ?>">
          <input type="hidden" name="landing_slug" value="<?= e((string) ($landingPage['slug'] ?? '')) ?>">
          <input type="hidden" name="landing_city" value="<?= e((string) ($landingPage['city'] ?? '')) ?>">
          <input type="hidden" name="landing_region" value="<?= e((string) ($landingPage['region'] ?? '')) ?>">
          <input type="hidden" name="source_host" value="<?= e(get_source_host()) ?>">
          <?= csrf_field() ?>
          <input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="form-trap" aria-hidden="true">

          <input type="hidden" name="utm_source" value="<?= get_utm_value('utm_source') ?>">
          <input type="hidden" name="utm_medium" value="<?= get_utm_value('utm_medium') ?>">
          <input type="hidden" name="utm_campaign" value="<?= get_utm_value('utm_campaign') ?>">
          <input type="hidden" name="utm_term" value="<?= get_utm_value('utm_term') ?>">
          <input type="hidden" name="utm_content" value="<?= get_utm_value('utm_content') ?>">

          <?php foreach ($questions as $question): ?>
            <?php if (($question['type'] ?? '') === 'options'): ?>
              <input type="hidden" name="<?= e($question['key']) ?>" id="input_<?= e($question['key']) ?>">
            <?php endif; ?>
          <?php endforeach; ?>

          <div id="stepsWrap">
            <?php foreach ($questions as $index => $question): ?>
              <?php
                $stepNumber = (int) ($question['step'] ?? ($index + 1));
                $isActive = $stepNumber === 1;
              ?>

              <section class="form-step <?= $isActive ? 'active' : '' ?>" data-step="<?= $stepNumber ?>">
                <span class="step-kicker">Stap <?= $stepNumber ?></span>

                <h3 class="step-title"><?= e($question['label']) ?></h3>

                <?php if (!empty($question['help_text'])): ?>
                  <p class="step-help"><?= e($question['help_text']) ?></p>
                <?php endif; ?>

                <?php if (($question['type'] ?? '') === 'options'): ?>
                  <div class="option-grid" data-option-group="<?= e($question['key']) ?>">
                    <?php foreach (($question['options'] ?? []) as $option): ?>
                      <button
                        type="button"
                        class="option-card"
                        data-value="<?= e($option['value']) ?>"
                        data-label="<?= e($option['label']) ?>"
                      >
                        <span class="option-icon"><?= e($option['icon'] ?? '') ?></span>
                        <?= e($option['label']) ?>
                      </button>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>

                <?php if (($question['type'] ?? '') === 'fields'): ?>
                  <div class="field-grid">
                    <?php foreach (($question['fields'] ?? []) as $field): ?>
                      <?php
                        $fieldType = $field['type'] ?? 'text';
                        $fieldKey = $field['key'] ?? '';
                        $inputName = $fieldKey;

                        if ($fieldType === 'file' && !empty($field['multiple'])) {
                            $inputName .= '[]';
                        }
                      ?>

                      <div class="field <?= !empty($field['full_width']) ? 'full' : '' ?>">
                        <label for="<?= e($fieldKey) ?>"><?= e($field['label']) ?></label>

                        <?php if ($fieldType === 'file'): ?>
                          <input
                            id="<?= e($fieldKey) ?>"
                            name="<?= e($inputName) ?>"
                            type="file"
                            accept="<?= e($field['accept'] ?? '') ?>"
                            <?= !empty($field['multiple']) ? 'multiple' : '' ?>
                            <?= !empty($field['required']) ? 'required' : '' ?>
                          >
                        <?php elseif ($fieldType === 'textarea'): ?>
                          <textarea
                            id="<?= e($fieldKey) ?>"
                            name="<?= e($fieldKey) ?>"
                            placeholder="<?= e($field['placeholder'] ?? '') ?>"
                            <?= !empty($field['required']) ? 'required' : '' ?>
                          ></textarea>
                        <?php else: ?>
                          <input
                            id="<?= e($fieldKey) ?>"
                            name="<?= e($fieldKey) ?>"
                            type="<?= e($fieldType) ?>"
                            placeholder="<?= e($field['placeholder'] ?? '') ?>"
                            autocomplete="<?= e($field['autocomplete'] ?? '') ?>"
                            <?= !empty($field['required']) ? 'required' : '' ?>
                          >
                        <?php endif; ?>

                        <?php if (!empty($field['helper'])): ?>
                          <p class="field-helper"><?= e($field['helper']) ?></p>
                        <?php endif; ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </section>
            <?php endforeach; ?>
          </div>

          <p class="form-privacy-note">
            Door de ketelcheck te versturen verwerken we uw gegevens om uw aanvraag te behandelen.
            Lees onze <a href="/privacyverklaring/">privacyverklaring</a>.
          </p>

          <div class="error-message" id="errorMessage" role="alert">
            <?= $leadError ? e($leadError) : '' ?>
          </div>

          <div class="form-actions" id="formActions">
            <button type="button" class="btn btn-ghost" id="prevBtn">Vorige</button>
            <button type="button" class="btn btn-primary" id="nextBtn">Volgende</button>
          </div>
        </form>
      </div>
    </div>
  </section>

  <?php if (!empty($landingPage['seo_content_html'])): ?>
    <section class="section section-soft" aria-labelledby="seo-content-title">
      <div class="container">
        <article class="seo-content" id="seo-content-title">
          <?= $landingPage['seo_content_html'] ?>
        </article>

        <div class="inline-cta">
          <div>
            <strong>Wilt u weten of repareren of vervangen verstandig is?</strong>
            <span>Doe de ketelcheck en ontvang binnen 24 uur duidelijkheid of een passende offerte.</span>
          </div>
          <a href="#ketelcheck" class="btn btn-primary">Start ketelcheck</a>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <section class="section section-soft problem-solution-section" aria-labelledby="uitleg-title">
    <div class="container">
      <div class="section-head">
        <div class="section-label"><?= e($problemSolution['label']) ?></div>
        <h2 id="uitleg-title"><?= e($problemSolution['title']) ?></h2>
        <p class="section-intro">
          <?= e($problemSolution['intro']) ?>
        </p>
      </div>

      <div class="process-grid">
        <?php foreach ($problemSolution['steps'] as $step): ?>
          <article class="process-card">
            <h3><?= e($step['title']) ?></h3>
            <p><?= e($step['text']) ?></p>
          </article>
        <?php endforeach; ?>
      </div>

      <div class="intent-grid" aria-label="Lokale voordelen">
        <?php foreach ($localIntentPanels as $panel): ?>
          <article class="intent-card">
            <h3><?= e($panel['title']) ?></h3>
            <p><?= e($panel['text']) ?></p>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="section" aria-labelledby="why-local-title">
    <div class="container">
      <div class="conversion-band">
        <div>
          <div class="section-label"><?= e($conversionLabel) ?></div>
          <h2 id="why-local-title"><?= e($conversionTitle) ?></h2>
          <p><?= e($conversionText) ?></p>
        </div>

        <a href="#ketelcheck" class="btn btn-primary">Vraag offerte aan</a>
      </div>
    </div>
  </section>

  <section class="section" aria-labelledby="trust-title">
    <div class="container">
      <div class="section-head">
        <div class="section-label"><?= e($trustLabel) ?></div>
        <h2 id="trust-title"><?= e($trustTitle) ?></h2>
        <p class="section-intro">
          <?= e($trustIntro) ?>
        </p>
      </div>

      <div class="trust-grid">
        <?php foreach ($trustCards as $card): ?>
          <article class="trust-card">
            <div class="trust-icon"><?= e((string) ($card['icon'] ?? '✓')) ?></div>
            <h3><?= e((string) ($card['title'] ?? '')) ?></h3>
            <p><?= e((string) ($card['text'] ?? '')) ?></p>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="section section-soft" aria-labelledby="problemen-title">
    <div class="container">
      <div class="section-head">
        <div class="section-label"><?= e($problemsLabel) ?></div>
        <h2 id="problemen-title"><?= e($problemsTitle) ?></h2>
      </div>

      <div class="cards-grid">
        <?php foreach ($problemCards as $card): ?>
          <article class="info-card">
            <div class="info-card-icon"><?= e((string) ($card['icon'] ?? '')) ?></div>
            <h3><?= e((string) ($card['title'] ?? '')) ?></h3>
            <p><?= e((string) ($card['text'] ?? '')) ?></p>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <?php if (!empty($reviews)): ?>
    <section class="section section-soft" aria-labelledby="reviews-title">
      <div class="container">
        <div class="section-head">
          <div class="section-label"><?= e($reviewsLabel) ?></div>
          <h2 id="reviews-title"><?= e($reviewsTitle) ?></h2>
          <p class="section-intro"><?= e($reviewsIntro) ?></p>
        </div>

        <div class="reviews-grid">
          <?php foreach ($reviews as $review): ?>
            <article class="review-card">
              <div class="review-stars" aria-label="<?= (int) $review['rating'] ?> van 5 sterren">
                <?= str_repeat('★', max(1, min(5, (int) $review['rating']))) ?>
              </div>
              <p>“<?= e($review['quote_text']) ?>”</p>
              <strong><?= e($review['author_name']) ?></strong>
              <?php if (!empty($review['author_location'])): ?>
                <span><?= e($review['author_location']) ?></span>
              <?php endif; ?>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <?php if (!empty($landingPage['faq_html'])): ?>
    <section class="section section-soft" aria-labelledby="faq-title">
      <div class="container">
        <article class="faq-content" id="faq-title">
          <?= $landingPage['faq_html'] ?>
        </article>
      </div>
    </section>
  <?php endif; ?>

  <?php if ($isCityPage && !empty($cityChildPages)): ?>
    <section class="section" aria-labelledby="city-areas-title">
      <div class="container">
        <div class="section-head">
          <div class="section-label">Wijken en gebieden</div>
          <h2 id="city-areas-title">
            CV-ketel hulp in wijken van <?= e($landingPage['city']) ?>
          </h2>
          <p class="section-intro">
            Bekijk ook de wijkpagina’s binnen <?= e($landingPage['municipality']) ?>.
          </p>
        </div>

        <div class="city-area-grid">
          <?php foreach ($cityChildPages as $page): ?>
            <a class="city-area-card" href="/<?= e($page['slug']) ?>/">
              <span class="city-area-icon">↗</span>
              <span>
                <strong><?= e($page['city']) ?></strong>
                <small><?= e($page['municipality']) ?></small>
              </span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <?php if (!empty($nearbyPages) && !$isCityPage): ?>
    <section class="section section-soft" aria-labelledby="nearby-regions-title">
      <div class="container">
        <div class="section-head">
          <div class="section-label">In de buurt</div>
          <h2 id="nearby-regions-title">
            Ook actief in <?= e($landingPage['municipality']) ?>
          </h2>
          <p class="section-intro">
            Bekijk ook andere gebieden in de buurt waar wij helpen bij cv-ketelproblemen.
          </p>
        </div>

        <div class="nearby-links-grid">
          <?php foreach ($nearbyPages as $page): ?>
            <a class="nearby-link-card" href="/<?= e($page['slug']) ?>/">
              <span class="nearby-link-icon">↗</span>
              <span>
                <strong><?= e($page['city']) ?></strong>
                <small><?= e($page['municipality']) ?></small>
              </span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <?php if (!empty($knowledgePages)): ?>
    <section class="section" aria-labelledby="knowledge-links-title">
      <div class="container">
        <div class="section-head">
          <div class="section-label">Kennisbank</div>
          <h2 id="knowledge-links-title">Meer weten over cv-ketelproblemen?</h2>
          <p class="section-intro">
            Lees praktische uitleg over storingen, lekkage, drukverlies en vervangen.
          </p>
        </div>

        <div class="knowledge-link-grid">
          <?php foreach ($knowledgePages as $page): ?>
            <a class="knowledge-link-card" href="/kennisbank/<?= e($page['slug']) ?>/">
              <span class="knowledge-link-icon">↗</span>
              <span>
                <strong><?= e($page['hero_title']) ?></strong>
                <small><?= e($page['related_problem_key'] ?? 'cv-ketel') ?></small>
              </span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <?php if (!empty($landingPages)): ?>
    <section class="section" aria-labelledby="regio-links-title">
      <div class="container">
        <div class="section-head">
          <div class="section-label">Werkgebied</div>
          <h2 id="regio-links-title">CV-ketel hulp per regio</h2>
          <p class="section-intro">
            Bekijk direct de ketelcheck voor uw plaats in Zuid-Holland.
          </p>
        </div>

        <div class="region-links-grid">
          <?php foreach ($landingPages as $page): ?>
            <a class="region-link-card" href="/<?= e($page['slug']) ?>/">
              <span class="region-link-icon">↗</span>
              <span>
                <strong><?= e($page['city']) ?></strong>
                <small><?= e($page['region'] ?? 'Zuid-Holland') ?></small>
              </span>
            </a>
          <?php endforeach; ?>
        </div>

        <div class="region-links-action">
          <a href="/regios/" class="btn btn-ghost">Bekijk alle regio’s</a>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <section class="section">
    <div class="container">
      <div class="region-strip">
        <h2>
          <?= !empty($landingPage['city']) ? 'Actief in ' . e($landingPage['city']) : 'Actief in Zuid-Holland' ?>
        </h2>
        <p>
          <?= e($localRegionText) ?>
        </p>
      </div>
    </div>
  </section>
</main>

<div class="mobile-sticky-cta" aria-label="Start de ketelcheck">
  <div>
    <strong>Ketelprobleem?</strong>
    <span>Gratis check, geen verplichting</span>
  </div>
  <a href="#ketelcheck" class="btn btn-primary">Start check</a>
</div>

<?= site_footer_html() ?>

<div class="modal-backdrop" id="emergencyModal" aria-hidden="true">
  <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="emergencyTitle">
    <button type="button" class="modal-close" id="closeEmergencyModal" aria-label="Sluiten">×</button>

    <div class="modal-icon">!</div>

    <h2 id="emergencyTitle">Spoed met uw cv-ketel?</h2>

    <p class="modal-intro">
      Heeft u direct hulp nodig door uitval, lekkage, geen warm water of een onveilige situatie?
      Dan kunt u een spoedoproep doen.
    </p>

    <div class="modal-warning">
      <strong>Let op:</strong>
      spoedhulp kan afhankelijk zijn van beschikbaarheid, regio en het type probleem.
      Voor spoedklussen kunnen andere voorwaarden of kosten gelden.
    </div>

    <div class="modal-actions">
      <a href="tel:0612345678" class="btn btn-primary">Bel direct</a>
      <a href="#ketelcheck" class="btn btn-ghost" id="modalStartCheck">Eerst ketelcheck doen</a>
    </div>

    <p class="modal-small">
      Vul bij twijfel de ketelcheck in. Dan nemen we binnen 24 uur contact op met advies of een offerte.
    </p>
  </div>
</div>

<?= cookie_banner_html() ?>
<script src="/assets/js/legal-consent.js"></script>
<script src="/assets/js/app.js"></script>
</body>
</html>
