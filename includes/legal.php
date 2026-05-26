<?php
declare(strict_types=1);

function legal_e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function tracking_head_config(): string
{
    return <<<HTML
  <script>
    window.KO24_TRACKING = {
      gtmId: 'GTM-5WZJM7SF',
      googleAdsId: 'AW-18139929971'
    };
  </script>

HTML;
}

function cookie_banner_html(): string
{
    return <<<HTML
<div class="cookie-banner" id="cookieBanner" aria-live="polite" hidden>
  <div>
    <strong>Cookies op KetelOfferte24.nl</strong>
    <p>
      We gebruiken noodzakelijke cookies voor de werking van de site. Met uw toestemming gebruiken we ook
      analytische en marketingcookies om aanvragen te meten en de website te verbeteren.
    </p>
    <div class="cookie-links">
      <a href="/cookieverklaring/">Cookieverklaring</a>
      <a href="/privacyverklaring/">Privacyverklaring</a>
    </div>
  </div>
  <div class="cookie-actions">
    <button type="button" class="btn btn-ghost" data-cookie-choice="rejected">Weigeren</button>
    <button type="button" class="btn btn-primary" data-cookie-choice="accepted">Accepteren</button>
  </div>
</div>
HTML;
}

function site_footer_html(): string
{
    return <<<HTML
<footer class="site-footer">
  <div class="container footer-inner">
    <p>KetelOfferte24.nl - snelle ketelcheck voor cv-ketelproblemen in Zuid-Holland.</p>
    <nav class="footer-links" aria-label="Juridische links">
      <a href="/privacyverklaring/">Privacyverklaring</a>
      <a href="/cookieverklaring/">Cookieverklaring</a>
      <a href="/voorwaarden/">Voorwaarden</a>
      <button type="button" data-cookie-settings>Cookie-instellingen</button>
    </nav>
  </div>
</footer>
HTML;
}
