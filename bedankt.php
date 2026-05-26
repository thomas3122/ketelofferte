<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$publicId = $_SESSION['lead_success_public_id'] ?? null;
unset($_SESSION['lead_success_public_id']);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
<?= tracking_head_config() ?>

  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Aanvraag ontvangen | KetelOfferte24.nl</title>
  <meta name="description" content="Uw ketelcheck aanvraag is ontvangen. KetelOfferte24.nl neemt binnen 24 uur contact met u op.">
  <meta name="robots" content="noindex,follow">

  <link rel="stylesheet" href="/assets/css/style.css">

  <style>
    .thankyou-page {
      min-height: calc(100vh - 132px);
      display: flex;
      align-items: center;
      padding: 60px 0;
      background:
        radial-gradient(circle at 82% 8%, rgba(249, 115, 22, 0.14), transparent 28%),
        radial-gradient(circle at 8% 12%, rgba(16, 47, 86, 0.10), transparent 32%),
        linear-gradient(180deg, #ffffff 0%, #f7f9fc 100%);
    }

    .thankyou-card {
      max-width: 720px;
      margin: 0 auto;
      background: var(--white);
      border: 1px solid var(--line);
      border-radius: var(--radius-xl);
      padding: clamp(28px, 5vw, 48px);
      box-shadow: var(--shadow);
      text-align: center;
    }

    .thankyou-icon {
      width: 78px;
      height: 78px;
      border-radius: 26px;
      display: grid;
      place-items: center;
      margin: 0 auto 20px;
      background: var(--green-soft);
      color: var(--green);
      font-size: 38px;
      font-weight: 950;
    }

    .thankyou-card h1 {
      font-size: clamp(34px, 5vw, 56px);
      margin-bottom: 14px;
    }

    .thankyou-card p {
      color: var(--grey-dark);
      font-size: 18px;
      max-width: 560px;
      margin: 0 auto 22px;
    }

    .thankyou-ref {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin: 6px auto 24px;
      padding: 10px 14px;
      border-radius: 999px;
      background: var(--blue-soft);
      color: var(--navy);
      font-size: 14px;
      font-weight: 900;
    }

    .thankyou-steps {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 12px;
      margin: 28px 0;
      text-align: left;
    }

    .thankyou-step {
      background: var(--offwhite);
      border: 1px solid var(--line);
      border-radius: 18px;
      padding: 16px;
    }

    .thankyou-step strong {
      display: block;
      margin-bottom: 5px;
      color: var(--navy);
    }

    .thankyou-step span {
      color: var(--grey-dark);
      font-size: 14px;
    }

    .thankyou-actions {
      display: flex;
      justify-content: center;
      gap: 12px;
      flex-wrap: wrap;
      margin-top: 26px;
    }

    .thankyou-note {
      margin: 24px auto 0;
      max-width: 610px;
      border: 1px solid rgba(249, 115, 22, 0.22);
      border-radius: 20px;
      background: #fff7ed;
      padding: 18px;
      text-align: left;
    }

    .thankyou-note strong {
      display: block;
      color: var(--navy);
      margin-bottom: 6px;
      font-size: 16px;
    }

    .thankyou-note span {
      display: block;
      color: var(--grey-dark);
      font-size: 15px;
      line-height: 1.6;
    }

    @media (max-width: 700px) {
      .thankyou-steps {
        grid-template-columns: 1fr;
      }

      .thankyou-actions .btn {
        width: 100%;
      }
    }
  </style>
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

    <a href="/" class="btn btn-primary">Nieuwe check</a>
  </div>
</header>

<main class="thankyou-page">
  <div class="container">
    <section class="thankyou-card">
      <div class="thankyou-icon">✓</div>

      <h1>Aanvraag ontvangen</h1>

      <p>
        We bekijken uw cv-ketelprobleem en nemen binnen 24 uur contact met u op
        met duidelijkheid, advies of een passende offerte.
      </p>

      <?php if ($publicId): ?>
        <div class="thankyou-ref">
          Aanvraagnummer: <?= e((string) $publicId) ?>
        </div>
      <?php endif; ?>

      <div class="thankyou-steps">
        <div class="thankyou-step">
          <strong>1. Aanvraag ontvangen</strong>
          <span>Uw ketelcheck staat nu in ons systeem.</span>
        </div>

        <div class="thankyou-step">
          <strong>2. Situatie beoordelen</strong>
          <span>We kijken naar uw klacht, locatie en gewenste hulp.</span>
        </div>

        <div class="thankyou-step">
          <strong>3. Binnen 24 uur reactie</strong>
          <span>U ontvangt advies of een offerte voor de vervolgstap.</span>
        </div>
      </div>

      <div class="thankyou-note">
        <strong>Heeft u nog foto’s of extra informatie?</strong>
        <span>
          Houd uw aanvraagnummer bij de hand. Foto’s van de ketel, foutcode, typeplaatje
          en de plek waar de ketel hangt helpen om sneller gericht mee te kijken.
        </span>
      </div>

      <div class="thankyou-actions">
        <a href="/" class="btn btn-ghost">Terug naar website</a>
        <a href="/#ketelcheck" class="btn btn-primary">Nieuwe aanvraag</a>
      </div>
    </section>
  </div>
</main>

<?= site_footer_html() ?>

<?php if (!empty($publicId)): ?>
<script>
  window.KO24_PENDING_EVENTS = window.KO24_PENDING_EVENTS || [];
  window.KO24_PENDING_EVENTS.push({
    event: 'lead_submitted',
    lead_type: 'ketelcheck'
  });
</script>
<?php endif; ?>

<?= cookie_banner_html() ?>
<script src="/assets/js/legal-consent.js"></script>
</body>
</html>
