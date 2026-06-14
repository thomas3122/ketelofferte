<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$pageTitle = 'Privacyverklaring | KetelOfferte24.nl';
$metaDescription = 'Lees hoe KetelOfferte24.nl persoonsgegevens verwerkt bij de ketelcheck, offerteaanvragen, contact en websitebezoek.';
$canonicalUrl = app_url('/privacyverklaring/');
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
    <div class="section-label">Privacy</div>
    <h1>Privacyverklaring</h1>
    <p class="legal-updated">Laatst bijgewerkt: 25 mei 2026</p>

    <section>
      <h2>1. Wie is verantwoordelijk?</h2>
      <p>
        KetelOfferte24.nl verwerkt persoonsgegevens voor de ketelcheck, offerteaanvragen,
        contactmomenten en het verbeteren van de website. Voor privacyvragen kunt u contact opnemen via
        <a href="mailto:offerte@ketelofferte24.nl">offerte@ketelofferte24.nl</a>.
      </p>
      <p>
        Vul op deze pagina later uw officiële bedrijfsnaam, adres en KvK-nummer aan zodra deze gegevens definitief zijn.
      </p>
    </section>

    <section>
      <h2>2. Welke gegevens verwerken wij?</h2>
      <p>Wij kunnen de volgende gegevens verwerken wanneer u de ketelcheck invult of contact met ons heeft:</p>
      <ul>
        <li>naam, e-mailadres, telefoonnummer, postcode en woonplaats;</li>
        <li>informatie over uw cv-ketelprobleem, gewenste hulp, urgentie en woning-/installatiesituatie;</li>
        <li>foto’s die u vrijwillig meestuurt, bijvoorbeeld van de ketel, foutcode of typeplaatje;</li>
        <li>technische gegevens zoals bronpagina, UTM-campagnegegevens, IP-adres en beveiligingslogs;</li>
        <li>communicatie over uw aanvraag en interne notities die nodig zijn om de aanvraag op te volgen.</li>
      </ul>
    </section>

    <section>
      <h2>3. Waarvoor gebruiken wij deze gegevens?</h2>
      <p>Wij gebruiken persoonsgegevens voor:</p>
      <ul>
        <li>het behandelen van uw ketelcheck en offerteaanvraag;</li>
        <li>het beoordelen of advies, reparatie of vervanging logisch kan zijn;</li>
        <li>het doorzetten van uw aanvraag naar een passende partij wanneer dat nodig is voor opvolging;</li>
        <li>klantenservice, administratie, beveiliging en misbruikpreventie;</li>
        <li>het meten en verbeteren van de website, alleen voor marketing- en trackingcookies na uw toestemming.</li>
      </ul>
    </section>

    <section>
      <h2>4. Grondslagen</h2>
      <p>
        Wij verwerken gegevens omdat dit nodig is om uw aanvraag te behandelen, omdat wij een gerechtvaardigd
        belang hebben bij beveiliging en administratie, omdat wij aan wettelijke verplichtingen moeten voldoen
        of omdat u toestemming geeft voor niet-noodzakelijke cookies en metingen.
      </p>
    </section>

    <section>
      <h2>5. Delen met anderen</h2>
      <p>
        Wij delen gegevens alleen wanneer dat nodig is. Denk aan hosting-, e-mail- en IT-dienstverleners,
        administratieve dienstverleners en relevante cv- of installatiebedrijven die uw aanvraag kunnen beoordelen
        of opvolgen. Met dienstverleners maken wij passende afspraken over beveiliging en gebruik van gegevens.
      </p>
    </section>

    <section>
      <h2>6. Foto’s en gevoelige informatie</h2>
      <p>
        Stuur alleen foto’s mee die nodig zijn voor uw cv-ketelvraag. Vermijd documenten, gezichten,
        bankgegevens of andere informatie die niet nodig is voor de beoordeling van uw aanvraag.
      </p>
    </section>

    <section>
      <h2>7. Bewaartermijnen</h2>
      <p>
        Wij bewaren gegevens niet langer dan nodig. Aanvraaggegevens bewaren wij in principe maximaal 24 maanden,
        tenzij een langere bewaartermijn nodig is voor administratie, geschillen, wettelijke verplichtingen of beveiliging.
        Cookies en trackinggegevens worden bewaard volgens de termijnen in onze cookieverklaring en de instellingen
        van de betreffende diensten.
      </p>
    </section>

    <section>
      <h2>8. Beveiliging</h2>
      <p>
        Wij nemen technische en organisatorische maatregelen om persoonsgegevens te beschermen, waaronder HTTPS,
        beveiligde formulieren, toegangsbeperking, logging en afscherming van uploadbestanden.
      </p>
    </section>

    <section>
      <h2>9. Uw rechten</h2>
      <p>
        U heeft onder de AVG onder meer recht op inzage, correctie, verwijdering, beperking, bezwaar,
        overdraagbaarheid en het intrekken van toestemming. Mail uw verzoek naar
        <a href="mailto:offerte@ketelofferte24.nl">offerte@ketelofferte24.nl</a>.
        U heeft ook het recht om een klacht in te dienen bij de Autoriteit Persoonsgegevens.
      </p>
    </section>
  </div>
</main>

<?= site_footer_html() ?>
<?= cookie_banner_html() ?>
<script src="/assets/js/legal-consent.js"></script>
</body>
</html>
