<?php
declare(strict_types=1);

/**
 * Eenvoudige, bestand-gebaseerde rate limiter voor publieke endpoints.
 * Geeft true terug zolang het aantal pogingen binnen het tijdvenster onder
 * $maxAttempts blijft. Roep daarna rate_limit_hit() aan om een poging te tellen.
 */
function rate_limit_dir(string $bucket): string
{
    $safeBucket = preg_replace('/[^a-z0-9_-]/i', '', $bucket) ?: 'default';
    $dir = dirname(__DIR__) . '/storage/cache/' . $safeBucket;

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    return $dir;
}

function rate_limit_path(string $bucket, string $key): string
{
    return rate_limit_dir($bucket) . '/' . hash('sha256', $key) . '.json';
}

function rate_limit_state(string $bucket, string $key, int $windowSeconds): array
{
    $path = rate_limit_path($bucket, $key);

    if (!is_file($path)) {
        return ['count' => 0, 'first_at' => time()];
    }

    $data = json_decode((string) file_get_contents($path), true);

    if (!is_array($data) || (($data['first_at'] ?? 0) < time() - $windowSeconds)) {
        return ['count' => 0, 'first_at' => time()];
    }

    return [
        'count' => (int) ($data['count'] ?? 0),
        'first_at' => (int) ($data['first_at'] ?? time()),
    ];
}

function rate_limit_allow(string $bucket, string $key, int $maxAttempts, int $windowSeconds): bool
{
    $state = rate_limit_state($bucket, $key, $windowSeconds);

    return $state['count'] < $maxAttempts;
}

function rate_limit_hit(string $bucket, string $key, int $windowSeconds): void
{
    $state = rate_limit_state($bucket, $key, $windowSeconds);
    $state['count']++;
    file_put_contents(rate_limit_path($bucket, $key), json_encode($state), LOCK_EX);
}

function get_current_slug(): ?string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

    if (!$path || $path === '/') {
        return null;
    }

    $path = trim($path, '/');

    if ($path === '') {
        return null;
    }

    $parts = explode('/', $path);

    return $parts[0] ?? null;
}

function get_landing_page_by_slug(PDO $pdo, ?string $slug, bool $activeOnly = true): ?array
{
    if ($slug === null || $slug === '') {
        return null;
    }

    $activeSql = $activeOnly ? 'AND is_active = 1' : '';

    $stmt = $pdo->prepare("
        SELECT *
        FROM landing_pages
        WHERE slug = :slug
          {$activeSql}
        LIMIT 1
    ");

    $stmt->execute([
        ':slug' => $slug,
    ]);

    $landingPage = $stmt->fetch();

    return $landingPage ?: null;
}

function get_default_landing_page(): array
{
    return [
        'id' => null,
        'slug' => null,
        'city' => null,
        'region' => 'Zuid-Holland',

        'page_title' => 'KetelOfferte24.nl | Snelle ketelcheck bij cv-ketelproblemen',
        'meta_description' => 'Problemen met uw cv-ketel in Zuid-Holland? Doe de snelle ketelcheck en ontvang binnen 24 uur duidelijkheid, advies of een cv-ketel offerte.',

        'hero_eyebrow' => 'Snelle ketelcheck voor Zuid-Holland',
        'hero_title' => 'Problemen met uw cv-ketel?',
        'hero_subtitle' => 'Doe de snelle ketelcheck en ontvang binnen 24 uur duidelijkheid of een offerte.',

        'intro_title' => 'Binnen enkele stappen duidelijkheid',
        'intro_text' => 'Bij cv-ketel storing, een cv-ketel die lekt, geen warm water of twijfel over repareren of vervangen.',

        'region_text' => 'KetelOfferte24.nl helpt bij cv-ketelproblemen in Rotterdam, Den Haag, Leiden, Delft, Zoetermeer, Gouda, Dordrecht en omgeving.',
    ];
}

function resolve_landing_page(PDO $pdo): array
{
    $slug = get_current_slug();

    if ($slug === null) {
        return get_default_landing_page();
    }

    $landingPage = get_landing_page_by_slug($pdo, $slug, false);

    if ($landingPage) {
        if ((int) ($landingPage['is_active'] ?? 1) !== 1) {
            $landingPage['_seo_noindex'] = true;
        }

        return $landingPage;
    }

    http_response_code(404);

    $notFoundPage = get_default_landing_page();
    $notFoundPage['_seo_noindex'] = true;
    $notFoundPage['_not_found'] = true;
    $notFoundPage['page_title'] = 'Pagina niet gevonden | KetelOfferte24.nl';
    $notFoundPage['meta_description'] = 'Deze pagina bestaat niet of is niet beschikbaar.';
    $notFoundPage['hero_eyebrow'] = 'Pagina niet gevonden';
    $notFoundPage['hero_title'] = 'Deze pagina bestaat niet';
    $notFoundPage['hero_subtitle'] = 'Ga terug naar de ketelcheck of bekijk de regio’s waar KetelOfferte24.nl actief is.';

    return $notFoundPage;
}

function get_source_host(): string
{
    return $_SERVER['HTTP_HOST'] ?? '';
}

function app_url(string $path = ''): string
{
    $baseUrl = defined('APP_URL') ? rtrim((string) APP_URL, '/') : 'https://ketelofferte24.nl';
    $path = '/' . ltrim($path, '/');

    return $baseUrl . ($path === '/' ? '/' : $path);
}

function canonical_url_for_path(string $path): string
{
    if ($path !== '/' && substr($path, -1) !== '/') {
        $path .= '/';
    }

    return app_url($path);
}

function app_table_exists(PDO $pdo, string $tableName): bool
{
    static $cache = [];

    if (array_key_exists($tableName, $cache)) {
        return $cache[$tableName];
    }

    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE :table_name");
        $stmt->execute([
            ':table_name' => $tableName,
        ]);

        $cache[$tableName] = (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        $cache[$tableName] = false;
    }

    return $cache[$tableName];
}

function app_column_exists(PDO $pdo, string $tableName, string $columnName): bool
{
    static $cache = [];

    $cacheKey = $tableName . '.' . $columnName;

    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$tableName}` LIKE :column_name");
        $stmt->execute([
            ':column_name' => $columnName,
        ]);

        $cache[$cacheKey] = (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        $cache[$cacheKey] = false;
    }

    return $cache[$cacheKey];
}

function extract_faq_items_from_html(?string $html): array
{
    $html = trim((string) $html);

    if ($html === '') {
        return [];
    }

    libxml_use_internal_errors(true);

    $document = new DOMDocument();
    $loaded = $document->loadHTML(
        '<?xml encoding="UTF-8"><div id="faq-root">' . $html . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );

    libxml_clear_errors();

    if (!$loaded) {
        return [];
    }

    $xpath = new DOMXPath($document);
    $headings = $xpath->query('//*[@id="faq-root"]//h3');

    if (!$headings || $headings->length === 0) {
        $headings = $xpath->query('//*[@id="faq-root"]//h2');
    }

    $items = [];

    if (!$headings) {
        return [];
    }

    foreach ($headings as $heading) {
        $question = trim((string) $heading->textContent);

        if ($question === '') {
            continue;
        }

        $answerParts = [];
        $node = $heading->nextSibling;

        while ($node !== null) {
            if ($node instanceof DOMElement && in_array(strtolower($node->tagName), ['h2', 'h3'], true)) {
                break;
            }

            $text = trim((string) $node->textContent);

            if ($text !== '') {
                $answerParts[] = $text;
            }

            $node = $node->nextSibling;
        }

        $answer = trim(implode(' ', $answerParts));

        if ($answer === '') {
            continue;
        }

        $items[] = [
            '@type' => 'Question',
            'name' => $question,
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $answer,
            ],
        ];
    }

    return $items;
}

function landing_page_variant_index(array $landingPage, int $count, string $salt = ''): int
{
    if ($count <= 1) {
        return 0;
    }

    $seed = implode('|', [
        (string) ($landingPage['slug'] ?? ''),
        (string) ($landingPage['city'] ?? ''),
        (string) ($landingPage['municipality'] ?? ''),
        (string) ($landingPage['area_type'] ?? ''),
        $salt,
    ]);

    return abs((int) crc32($seed)) % $count;
}

function landing_page_area_label(array $landingPage): string
{
    $city = trim((string) ($landingPage['city'] ?? ''));
    $region = trim((string) ($landingPage['region'] ?? 'Zuid-Holland'));

    return $city !== '' ? $city : $region;
}

function get_landing_page_theme(array $landingPage): array
{
    $areaType = (string) ($landingPage['area_type'] ?? '');
    $template = (string) ($landingPage['page_template'] ?? '');

    if ($template === 'city' || $areaType === 'city') {
        return [
            'key' => 'city',
            'class' => 'theme-city',
            'label' => 'Stadspagina',
        ];
    }

    if (in_array($areaType, ['district', 'neighborhood'], true)) {
        return [
            'key' => 'district',
            'class' => 'theme-district',
            'label' => 'Wijkpagina',
        ];
    }

    $themes = [
        [
            'key' => 'direct',
            'class' => 'theme-direct',
            'label' => 'Directe hulp',
        ],
        [
            'key' => 'advice',
            'class' => 'theme-advice',
            'label' => 'Advies en offerte',
        ],
        [
            'key' => 'compare',
            'class' => 'theme-compare',
            'label' => 'Repareren of vervangen',
        ],
    ];

    return $themes[landing_page_variant_index($landingPage, count($themes), 'theme')];
}

function get_problem_solution_content(array $landingPage): array
{
    $area = landing_page_area_label($landingPage);
    $municipality = trim((string) ($landingPage['municipality'] ?? $area));
    $theme = get_landing_page_theme($landingPage)['key'];

    $variants = [
        'city' => [
            'label' => 'Lokale aanpak',
            'title' => 'Van cv-ketelprobleem naar duidelijke vervolgstap in ' . $area,
            'intro' => 'Uw klacht, locatie en urgentie bepalen welke vervolgstap het meest logisch is.',
            'steps' => [
                [
                    'title' => 'Probleem herkennen',
                    'text' => 'Kies de klacht die het beste past: storing, lekkage, geen warm water, drukverlies of een oude ketel.',
                ],
                [
                    'title' => 'Situatie in ' . $area . ' doorgeven',
                    'text' => 'Met postcode, toelichting en foto’s wordt sneller duidelijk welke hulp of offerte logisch is.',
                ],
                [
                    'title' => 'Advies of offerte ontvangen',
                    'text' => 'U krijgt binnen 24 uur richting: repareren, vervangen, onderhoud of eerst extra controle.',
                ],
            ],
        ],
        'district' => [
            'label' => 'Wijkgerichte check',
            'title' => 'Snel duidelijkheid bij cv-ketelproblemen in ' . $area,
            'intro' => 'Geef kort door wat er speelt en waar u zit. Zo kunnen we beter inschatten welke hulp past in ' . $municipality . '.',
            'steps' => [
                [
                    'title' => 'Klacht kort vastleggen',
                    'text' => 'Geef aan of het gaat om uitval, lekkage, lawaai, warm water of drukverlies.',
                ],
                [
                    'title' => 'Foto’s of foutcode toevoegen',
                    'text' => 'Een foto van ketel, typeplaatje of foutcode maakt de beoordeling concreter.',
                ],
                [
                    'title' => 'Passende route kiezen',
                    'text' => 'Afhankelijk van leeftijd en klacht volgt advies, reparatie-inschatting of offerte voor vervanging.',
                ],
            ],
        ],
        'direct' => [
            'label' => 'Snelle triage',
            'title' => 'Van storing naar actie zonder onnodig wachten',
            'intro' => 'Bij een cv-ketelprobleem wilt u snel weten wat verstandig is. De check brengt de belangrijkste signalen direct in beeld.',
            'steps' => [
                [
                    'title' => 'Urgentie bepalen',
                    'text' => 'Geef aan of hulp vandaag, binnen 24 uur of later nodig is.',
                ],
                [
                    'title' => 'Belangrijkste symptomen verzamelen',
                    'text' => 'Warm water, verwarming, druk en leeftijd van de ketel geven snel richting.',
                ],
                [
                    'title' => 'Concrete vervolgstap',
                    'text' => 'U ontvangt duidelijkheid of een offerte wanneer dat de logische volgende stap is.',
                ],
            ],
        ],
        'advice' => [
            'label' => 'Advies eerst',
            'title' => 'Eerst begrijpen, daarna pas een offerte',
            'intro' => 'Niet ieder ketelprobleem vraagt direct om vervanging. Eerst kijken we wat er aan de hand is.',
            'steps' => [
                [
                    'title' => 'Klacht en context',
                    'text' => 'De check combineert probleem, ketelleeftijd, warm water en verwarming.',
                ],
                [
                    'title' => 'Reparatie of vervanging afwegen',
                    'text' => 'Bij oudere ketels of terugkerende storingen wordt vergelijken vaak verstandiger.',
                ],
                [
                    'title' => 'Offerte als het past',
                    'text' => 'Alleen wanneer een offerte zinvol is, wordt de aanvraag concreet doorgezet.',
                ],
            ],
        ],
        'compare' => [
            'label' => 'Keuzehulp',
            'title' => 'Repareren of vervangen duidelijker maken',
            'intro' => 'Voor veel bezoekers is niet de storing zelf het grootste probleem, maar de twijfel over kosten en levensduur.',
            'steps' => [
                [
                    'title' => 'Leeftijd en klacht combineren',
                    'text' => 'Een jonge ketel met storing vraagt om een andere route dan een ketel van 15 jaar oud.',
                ],
                [
                    'title' => 'Risico’s scherp krijgen',
                    'text' => 'Lekkage, drukverlies en uitval worden apart beoordeeld omdat ze andere oorzaken kunnen hebben.',
                ],
                [
                    'title' => 'Gerichte offerte aanvragen',
                    'text' => 'Als vervangen logisch lijkt, bevat de aanvraag direct de informatie die nodig is voor een betere offerte.',
                ],
            ],
        ],
    ];

    $content = $variants[$theme] ?? $variants['direct'];

    $overrides = [
        'label' => 'process_label',
        'title' => 'process_title',
        'intro' => 'process_intro',
    ];

    foreach ($overrides as $targetKey => $sourceKey) {
        $value = trim((string) ($landingPage[$sourceKey] ?? ''));

        if ($value !== '') {
            $content[$targetKey] = $value;
        }
    }

    for ($i = 1; $i <= 3; $i++) {
        $title = trim((string) ($landingPage['process_step_' . $i . '_title'] ?? ''));
        $text = trim((string) ($landingPage['process_step_' . $i . '_text'] ?? ''));

        if ($title !== '') {
            $content['steps'][$i - 1]['title'] = $title;
        }

        if ($text !== '') {
            $content['steps'][$i - 1]['text'] = $text;
        }
    }

    return $content;
}

function get_local_intent_panels(array $landingPage): array
{
    $area = landing_page_area_label($landingPage);
    $municipality = trim((string) ($landingPage['municipality'] ?? $area));
    $areaType = (string) ($landingPage['area_type'] ?? '');
    $isDistrict = in_array($areaType, ['district', 'neighborhood'], true);

    if ($isDistrict) {
        return [
            [
                'title' => 'Hulp in de buurt',
                'text' => 'Geef door waar het probleem speelt. Zo kunnen we beter inschatten welke hulp past in ' . $area . ' en omgeving.',
            ],
            [
                'title' => 'Uw situatie centraal',
                'text' => 'Een storing, lekkage of oude ketel vraagt per woning om een andere aanpak.',
            ],
            [
                'title' => 'Duidelijke vervolgstap',
                'text' => 'Na de ketelcheck weet u sneller of advies, reparatie of een offerte voor vervanging logisch is.',
            ],
        ];
    }

    return [
        [
            'title' => 'Lokale ketelcheck',
            'text' => 'Uw postcode en plaats helpen om de aanvraag goed te beoordelen voor ' . $area . ' en omgeving.',
        ],
        [
            'title' => 'Gericht op uw klacht',
            'text' => 'Kies wat er speelt, zoals storing, lekkage, drukverlies, geen warm water of een oude ketel.',
        ],
        [
            'title' => 'Snel duidelijkheid',
            'text' => 'U ontvangt advies of een passende offerte wanneer dat de beste vervolgstap is.',
        ],
    ];
}

function get_landing_page_reviews(PDO $pdo, ?int $landingPageId): array
{
    if (!$landingPageId || !app_table_exists($pdo, 'landing_page_reviews')) {
        return [];
    }

    try {
        $stmt = $pdo->prepare("
            SELECT id, author_name, author_location, rating, quote_text, is_active, sort_order
            FROM landing_page_reviews
            WHERE landing_page_id = :landing_page_id
              AND is_active = 1
            ORDER BY sort_order ASC, id ASC
            LIMIT 6
        ");

        $stmt->execute([
            ':landing_page_id' => $landingPageId,
        ]);

        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function get_reviews_structured_data(array $reviews, string $canonicalUrl): string
{
    if (!$reviews) {
        return '';
    }

    $data = [];

    foreach ($reviews as $review) {
        $rating = max(1, min(5, (int) ($review['rating'] ?? 5)));

        $data[] = [
            '@context' => 'https://schema.org',
            '@type' => 'Review',
            '@id' => $canonicalUrl . '#review-' . (int) $review['id'],
            'itemReviewed' => [
                '@type' => 'Service',
                'name' => 'KetelOfferte24.nl ketelcheck',
                'url' => $canonicalUrl,
            ],
            'author' => [
                '@type' => 'Person',
                'name' => (string) ($review['author_name'] ?? 'Klant'),
            ],
            'reviewRating' => [
                '@type' => 'Rating',
                'ratingValue' => $rating,
                'bestRating' => 5,
                'worstRating' => 1,
            ],
            'reviewBody' => (string) ($review['quote_text'] ?? ''),
        ];
    }

    return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

function decode_landing_json_list(?string $json, array $fallback): array
{
    $json = trim((string) $json);

    if ($json === '') {
        return $fallback;
    }

    $decoded = json_decode($json, true);

    if (!is_array($decoded)) {
        return $fallback;
    }

    return $decoded;
}

function get_structured_data(array $landingPage, string $canonicalUrl): string
{
    $city = (string) ($landingPage['city'] ?? 'Zuid-Holland');
    $municipality = (string) ($landingPage['municipality'] ?? $city);
    $region = (string) ($landingPage['region'] ?? 'Zuid-Holland');
    $description = (string) ($landingPage['meta_description'] ?? 'Snelle ketelcheck bij cv-ketelproblemen.');
    $title = (string) ($landingPage['page_title'] ?? 'KetelOfferte24.nl');

    $areaServed = $city;

    if ($municipality !== '' && $municipality !== $city) {
        $areaServed .= ', ' . $municipality;
    }

    if ($region !== '') {
        $areaServed .= ', ' . $region;
    }

    $data = [
        [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            '@id' => app_url('/#organization'),
            'name' => 'KetelOfferte24.nl',
            'url' => app_url('/'),
            'logo' => app_url('/assets/img/logo.png'),
            'description' => 'KetelOfferte24.nl helpt consumenten met cv-ketelproblemen via een snelle ketelcheck en passende offerte.',
            'areaServed' => [
                '@type' => 'AdministrativeArea',
                'name' => $region,
            ],
        ],
        [
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            '@id' => $canonicalUrl . '#service',
            'name' => 'CV-ketel hulp in ' . $city,
            'serviceType' => 'CV-ketel check, advies en offerte',
            'provider' => [
                '@id' => app_url('/#organization'),
            ],
            'areaServed' => [
                '@type' => 'Place',
                'name' => $areaServed,
            ],
            'description' => $description,
            'url' => $canonicalUrl,
        ],
        [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            '@id' => $canonicalUrl . '#webpage',
            'url' => $canonicalUrl,
            'name' => $title,
            'description' => $description,
            'isPartOf' => [
                '@type' => 'WebSite',
                '@id' => app_url('/#website'),
                'name' => 'KetelOfferte24.nl',
                'url' => app_url('/'),
            ],
            'about' => [
                '@id' => $canonicalUrl . '#service',
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
                    'name' => 'Regio’s',
                    'item' => app_url('/regios/'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => $city,
                    'item' => $canonicalUrl,
                ],
            ],
        ],
    ];

    $faqItems = extract_faq_items_from_html($landingPage['faq_html'] ?? null);

    if ($faqItems) {
        $data[] = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            '@id' => $canonicalUrl . '#faq',
            'mainEntity' => $faqItems,
        ];
    }

    return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}
