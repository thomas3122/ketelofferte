<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$pageTitle = 'Cookieverklaring | KetelOfferte24.nl';
$metaDescription = 'Lees welke cookies KetelOfferte24.nl gebruikt en hoe u uw cookievoorkeur kunt wijzigen.';
$canonicalUrl = app_url('/cookieverklaring/');
?>
<!DOCTYPE html>
<html lang="nl">
<head>
<?= tracking_head_config() ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  <meta name="description" content="<?= e($metaDescription) ?>">
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
      <span class="brand-text">KetelOfferte<span>24</span><small>.nl</small></span>
    </a>
    <a href="/#ketelcheck" class="btn btn-primary">Start ketelcheck</a>
  </div>
</header>

<main class="legal-page">
  <div class="container legal-container">
    <div class="section-label">Cookies</div>
    <h1>Cookieverklaring</h1>
    <p class="legal-updated">Laatst bijgewerkt: 25 mei 2026</p>

    <section>
      <h2>1. Wat zijn cookies?</h2>
      <p>
        Cookies en vergelijkbare technieken zijn kleine bestanden of browsergegevens die nodig kunnen zijn
        om een website goed te laten werken, voorkeuren te onthouden of websitegebruik te meten.
      </p>
    </section>

    <section>
      <h2>2. Noodzakelijke cookies</h2>
      <p>
        Deze cookies zijn nodig voor de werking en beveiliging van de website. Denk aan sessies voor beheer,
        CSRF-beveiliging bij formulieren en het onthouden van uw cookievoorkeur. Hiervoor vragen wij geen toestemming.
      </p>
    </section>

    <section>
      <h2>3. Analytische en marketingcookies</h2>
      <p>
        Wij laden Google Tag Manager en Google Ads/conversiemeting pas nadat u cookies accepteert. Daarmee kunnen
        we meten welke aanvragen uit campagnes komen en de website verbeteren. We plaatsen deze scripts niet wanneer
        u weigert.
      </p>
    </section>

    <section>
      <h2>4. Uw keuze aanpassen</h2>
      <p>
        U kunt uw keuze opnieuw openen via de knop “Cookie-instellingen” in de footer. U kunt cookies daarnaast
        verwijderen via de instellingen van uw browser.
      </p>
      <button type="button" class="btn btn-primary" data-cookie-settings>Cookie-instellingen openen</button>
    </section>
  </div>
</main>

<?= site_footer_html() ?>
<?= cookie_banner_html() ?>
<script src="/assets/js/legal-consent.js"></script>
</body>
</html>
