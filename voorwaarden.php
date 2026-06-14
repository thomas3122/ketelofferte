<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$pageTitle = 'Voorwaarden | KetelOfferte24.nl';
$metaDescription = 'Lees de voorwaarden en belangrijke informatie over het gebruik van KetelOfferte24.nl en de ketelcheck.';
$canonicalUrl = app_url('/voorwaarden/');
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
    <div class="section-label">Voorwaarden</div>
    <h1>Voorwaarden en belangrijke informatie</h1>
    <p class="legal-updated">Laatst bijgewerkt: 25 mei 2026</p>

    <section>
      <h2>1. Rol van KetelOfferte24.nl</h2>
      <p>
        KetelOfferte24.nl is een platform voor een snelle ketelcheck, advies en offerteaanvragen.
        Wij zijn niet in alle gevallen de uitvoerende installateur en garanderen niet dat er altijd direct een
        monteur beschikbaar is.
      </p>
    </section>

    <section>
      <h2>2. Offertes en opvolging</h2>
      <p>
        Na uw aanvraag kunnen wij uw situatie beoordelen en, waar passend, uw gegevens delen met een relevante partij
        voor advies of een offerte. Een offerte, prijs, planning of beschikbaarheid is pas definitief wanneer deze
        schriftelijk door de uitvoerende partij is bevestigd.
      </p>
    </section>

    <section>
      <h2>3. Veiligheid en spoed</h2>
      <p>
        Bij gaslucht, koolmonoxidegevaar, brand, ernstige lekkage of direct gevaar moet u direct de gaskraan sluiten
        als dat veilig kan, ventileren, de woning verlaten en contact opnemen met de juiste nood- of storingsdienst.
        De informatie op deze website vervangt geen noodhulp.
      </p>
    </section>

    <section>
      <h2>4. Gecertificeerde werkzaamheden</h2>
      <p>
        Voor werkzaamheden aan gasverbrandingsinstallaties hoort een CO-gecertificeerd bedrijf te worden ingeschakeld.
        Controleer bij de uitvoerende partij altijd zelf welke certificering, voorwaarden en garanties gelden.
      </p>
    </section>

    <section>
      <h2>5. Informatie van gebruikers</h2>
      <p>
        U bent verantwoordelijk voor het juist en volledig invullen van uw aanvraag. Onjuiste of ontbrekende informatie
        kan invloed hebben op advies, offerte, planning of uitvoerbaarheid.
      </p>
    </section>

    <section>
      <h2>6. Aansprakelijkheid</h2>
      <p>
        Wij doen ons best om de website beschikbaar, veilig en actueel te houden. Wij zijn niet aansprakelijk voor
        indirecte schade, gevolgschade, gemiste besparingen of schade door handelen van derden, behalve voor zover
        aansprakelijkheid wettelijk niet mag worden uitgesloten.
      </p>
    </section>

    <section>
      <h2>7. Contact</h2>
      <p>
        Vragen over deze voorwaarden kunt u sturen naar
        <a href="mailto:offerte@ketelofferte24.nl">offerte@ketelofferte24.nl</a>.
      </p>
    </section>
  </div>
</main>

<?= site_footer_html() ?>
<?= cookie_banner_html() ?>
<script src="/assets/js/legal-consent.js"></script>
</body>
</html>
