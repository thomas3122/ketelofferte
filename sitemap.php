<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/xml; charset=UTF-8');

function xml_e(string $value): string
{
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

$baseUrl = defined('APP_URL') ? rtrim((string) APP_URL, '/') : 'https://ketelofferte24.nl';

$landingPagesStmt = $pdo->prepare("
    SELECT slug, updated_at, created_at
    FROM landing_pages
    WHERE is_active = 1
    ORDER BY city ASC
");

$landingPagesStmt->execute();
$landingPages = $landingPagesStmt->fetchAll();

$knowledgePagesStmt = $pdo->prepare("
    SELECT slug, updated_at, created_at
    FROM knowledge_pages
    WHERE is_active = 1
    ORDER BY title ASC
");

$knowledgePagesStmt->execute();
$knowledgePages = $knowledgePagesStmt->fetchAll();

$today = date('Y-m-d');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc><?= xml_e($baseUrl . '/') ?></loc>
    <lastmod><?= xml_e($today) ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>1.0</priority>
  </url>

  <url>
    <loc><?= xml_e($baseUrl . '/regios/') ?></loc>
    <lastmod><?= xml_e($today) ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>

  <url>
    <loc><?= xml_e($baseUrl . '/kennisbank/') ?></loc>
    <lastmod><?= xml_e($today) ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>

  <?php foreach ($landingPages as $page): ?>
    <?php
      $lastmod = $page['updated_at'] ?: $page['created_at'];
      $lastmodDate = $lastmod ? date('Y-m-d', strtotime((string) $lastmod)) : $today;
    ?>
    <url>
      <loc><?= xml_e($baseUrl . '/' . $page['slug'] . '/') ?></loc>
      <lastmod><?= xml_e($lastmodDate) ?></lastmod>
      <changefreq>weekly</changefreq>
      <priority>0.9</priority>
    </url>
  <?php endforeach; ?>

  <?php foreach ($knowledgePages as $page): ?>
    <?php
      $lastmod = $page['updated_at'] ?: $page['created_at'];
      $lastmodDate = $lastmod ? date('Y-m-d', strtotime((string) $lastmod)) : $today;
    ?>
    <url>
      <loc><?= xml_e($baseUrl . '/kennisbank/' . $page['slug'] . '/') ?></loc>
      <lastmod><?= xml_e($lastmodDate) ?></lastmod>
      <changefreq>monthly</changefreq>
      <priority>0.7</priority>
    </url>
  <?php endforeach; ?>
</urlset>
