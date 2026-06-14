# SEO voortgang KetelOfferte24.nl

Laatst bijgewerkt: 2026-05-25

## Status CSV import

- [x] CSV export/import gebouwd in admin
- [x] Eerste echte export gecontroleerd: `landing-pages-2026-05-25-015017.csv`
- [x] Fase-1 import gemaakt: `landing-pages-2026-05-25-fase1-geoptimaliseerd.csv`
- [x] Nieuwe export na import gecontroleerd: `landing-pages-2026-05-25-024349.csv`
- [x] Probleemclusters geïmporteerd en gecontroleerd: `landing-pages-2026-05-25-030004.csv`
- [x] Aantal pagina's gelijk gebleven: 34
- [x] Geen slugs kwijtgeraakt
- [x] `poepojes` verwijderd uit `binnenstad-noord-leiden`
- [x] `den-haag` gecorrigeerd naar `area_type=city`
- [x] `cv-ketel-vervangen-leiden` actief gezet

## Fase 1 - hoofdgeldpagina's

Deze pagina's hebben nu betere title, meta, hero, SEO-content en FAQ:

- [x] `/rotterdam/`
- [x] `/den-haag/`
- [x] `/delft/`
- [x] `/leiden/`
- [x] `/leidschendam/`
- [x] `/cv-ketel-vervangen-leiden/`

Nog controleren in browser:

- [ ] Hero en CTA boven de vouw
- [ ] Ketelcheck werkt op elke hoofdpagina
- [ ] Structured data blijft geldig
- [ ] Interne links naar regio's/kennisbank voelen logisch
- [ ] Geen interne SEO- of developerteksten zichtbaar
- [ ] Lokale browsercontrole opnieuw doen zodra databaseverbinding werkt

Pagina-indeling:

- [x] Lokale intro direct onder de hero geplaatst
- [x] Ketelcheck vroeg op de pagina gehouden
- [x] Unieke SEO-/wijkinhoud direct na de ketelcheck geplaatst
- [x] FAQ boven interne linkblokken geplaatst
- [x] Interne links naar wijken, kennisbank en regio's naar onderen verplaatst

## Huidige contentkwaliteit

Na voorbereide fase 4 CSV:

- Totaal landingspagina's na probleemclusters: 40
- Pagina's zonder SEO-content in nieuwste CSV: 0
- Pagina's zonder FAQ in nieuwste CSV: 0
- Grootste resterende risico: nieuwste CSV nog importeren en daarna browser/zoekresultaatcontrole doen

## Fase 2 - technische SEO veiligheid

Voordat we dunne wijkpagina's uitzetten:

- [x] Beslissen: inactieve landingspagina krijgt `noindex,follow`
- [x] Code aanpassen zodat `is_active=0` niet terugvalt naar de algemene homepage-content
- [x] Onbekende slugs geven nu HTTP 404 met `noindex,follow`
- [x] Sitemap controleert alleen indexeerbare pagina's
- [x] Canonical URLs controleren
- [x] Robots/noindex-strategie vastleggen

Aanbevolen: zwakke wijkpagina's tijdelijk niet indexeerbaar maken tot ze uniek genoeg zijn.

## Livegang security

- [x] Security headers actief via `includes/bootstrap.php`
- [x] Sessiecookies op `httponly`, `samesite=Lax` en HTTPS-only bij HTTPS
- [x] CSRF op leadformulier, adminacties en bedrijfs-offerteformulier
- [x] Honeypot op publieke leadaanvraag
- [x] Uploads buiten publieke directe toegang via `storage/.htaccess`
- [x] Leadbestanden alleen via gecontroleerde admin/bedrijf routes
- [x] `.env`, `.git`, `config`, `includes`, `storage` en `database` afgeschermd
- [x] CSV, SQL, Markdown, log- en backupbestanden afgeschermd via `.htaccess`
- [x] Apache-config syntax gecontroleerd
- [x] Databasefouten tonen geen technische details meer aan bezoekers
- [x] Publieke landingspagina's starten niet meer automatisch een PHP-sessie
- [x] Publieke CSRF-token werkt zonder sessie-cookie
- [ ] Productie-domein testen met echte HTTP-statuscodes
- [ ] Sterk adminwachtwoord en unieke server/database-wachtwoorden controleren
- [ ] Back-up en hersteltest controleren voor livegang

## Fase 3 - probleemclusters maken

Nieuwe SEO-pilaren:

- [x] CSV voorbereid: `landing-pages-2026-05-25-fase2-probleemclusters.csv`
- [x] CSV importeren met "Nieuwe slugs uit CSV als nieuwe pagina aanmaken"
- [x] Export na import controleren
- [x] `/cv-ketel-storing-zuid-holland/`
- [x] `/cv-ketel-lekt-zuid-holland/`
- [x] `/geen-warm-water-zuid-holland/`
- [x] `/drukverlies-cv-ketel-zuid-holland/`
- [x] `/cv-ketel-vervangen-zuid-holland/`
- [x] `/gecertificeerde-cv-monteur-zuid-holland/`

Per cluster nodig:

- [x] Concrete zoekintentie in title/H1
- [x] Veiligheidsblok bij gaslucht/lekkage/koolmonoxide
- [x] Repareren versus vervangen uitleg
- [x] Duidelijke CTA naar ketelcheck
- [x] 5 unieke FAQ's
- [x] Links naar relevante stadspagina's voorbereid in `landing-pages-2026-05-25-fase3-cluster-links.csv`
- [x] Fase-3 cluster-link CSV importeren
- [x] Export na fase-3 import controleren

## Fase 4 - wijkpagina's per batch

Alle wijkpagina's krijgen pas volledige waarde als ze unieke lokale context hebben.

### Delft batch

- [x] CSV voorbereid: `landing-pages-2026-05-25-fase4-delft-batch1.csv`
- [x] CSV voorbereid: `landing-pages-2026-05-25-fase4-delft-batch2-denhaag-batch1.csv`
- [x] CSV importeren
- [x] Export na import controleren
- [x] `/binnenstad-delft/` voorbereid
- [x] `/hof-van-delft/` voorbereid
- [x] `/schieweg-delft/` voorbereid
- [x] `/tanthof/` voorbereid
- [x] `/voorhof-buitenhof/` voorbereid
- [x] `/wippolder/` voorbereid

### Den Haag batch

- [x] CSV voorbereid: `landing-pages-2026-05-25-fase4-denhaag-batch2.csv`
- [x] CSV importeren
- [x] Export na import controleren
- [x] `/bouwlust-vrederust/` voorbereid
- [x] `/kijkduin-ockenburgh/` voorbereid
- [x] `/leidschenveen/` voorbereid
- [x] `/leyenburg-leyweg/` voorbereid
- [x] `/loosduinen/` voorbereid
- [x] `/mariahoeve-en-marlot/` voorbereid
- [x] `/moerwijk/` voorbereid
- [x] `/scheveningen/` voorbereid
- [x] `/schilderswijk-schildersbuurt/` voorbereid
- [x] `/spoorwijk-laakkwartier/` voorbereid
- [x] `/zuiderpark/` voorbereid

### Leiden batch

- [x] CSV voorbereid: `landing-pages-2026-05-25-fase4-leiden-rotterdam-batch.csv`
- [x] CSV importeren
- [x] Export na import controleren
- [x] `/binnenstad-noord-leiden/` voorbereid
- [x] `/binnenstad-zuid-leiden/` voorbereid
- [x] `/leiden-noord/` voorbereid
- [x] `/morsdistrict/` voorbereid
- [x] `/noordvest-leiden/` voorbereid

### Rotterdam batch

- [x] CSV voorbereid: `landing-pages-2026-05-25-fase4-leiden-rotterdam-batch.csv`
- [x] CSV importeren
- [x] Export na import controleren
- [x] `/hillegersberg-schiebroek/` voorbereid
- [x] `/kralingen-kralingen-oost/` voorbereid
- [x] `/rotterdam-noord/` voorbereid
- [x] `/oud-charlois-charlois/` voorbereid
- [x] `/oud-ijsselmonde/` voorbereid
- [x] `/overschie/` voorbereid

## Fase 5 - Zoetermeer

- [x] CSV voorbereid: `landing-pages-2026-05-25-fase5-zoetermeer.csv`
- [x] CSV v2 voorbereid met Zoetermeer-links in probleemclusters: `landing-pages-2026-05-25-fase5-zoetermeer-v2.csv`
- [ ] CSV importeren met "Nieuwe slugs uit CSV als nieuwe pagina aanmaken"
- [ ] Export na import controleren
- [x] `/zoetermeer/` voorbereid
- [x] `/oosterheem/` voorbereid
- [x] `/rokkeveen/` voorbereid
- [x] `/meerzicht/` voorbereid
- [x] `/buytenwegh/` voorbereid
- [x] `/de-leyens/` voorbereid
- [x] `/palenstein/` voorbereid
- [x] `/seghwaert/` voorbereid
- [x] `/noordhove/` voorbereid
- [x] `/dorp-zoetermeer/` voorbereid
- [x] `/driemanspolder/` voorbereid
- [x] `/stadscentrum-zoetermeer/` voorbereid
- [x] `/entree-zoetermeer/` bewust nog niet aangemaakt

## Conversieverbeteringen

- [x] Sticky mobiele CTA toegevoegd
- [x] Bewijsrij boven ketelcheck toegevoegd
- [x] Bedankpagina versterkt met vervolgstappen en foto-tip
- [x] Zoetermeer intern gekoppeld vanuit de probleemclusters in de nieuwe CSV

## Wijkpagina checklist

Elke wijkpagina moet minimaal hebben:

- [ ] Eigen zoekfocus
- [ ] Unieke lokale intro
- [ ] Woningcontext voor die wijk
- [ ] Eigen probleemhoek, bijvoorbeeld storing, lekkage, drukverlies of vervangen
- [ ] Minimaal 3 unieke FAQ's
- [ ] Duidelijke CTA naar ketelcheck
- [ ] Link naar stadspagina
- [ ] Link naar relevant probleemcluster
- [ ] Geen gekopieerde templatezinnen als kerninhoud

## Prioriteit volgende stap

Aanbevolen volgorde:

1. Technische SEO veiligheid voor `is_active=0`
2. Probleemclusters aanmaken
3. Delft + Den Haag wijkbatch verbeteren
4. Leiden + Rotterdam wijkbatch verbeteren
5. Browsercontrole en conversiecontrole uitvoeren
6. Zoetermeer importeren en export controleren
7. Daarna extra steden uitbreiden
