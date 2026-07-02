<?php

namespace App\Services;

use App\Models\Feed;
use App\Models\FeedItem;
use App\Models\CmsBlogPost;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;
use DOMDocument;
use DOMXPath;
use Exception;
use Illuminate\Support\Str;

class FeedSyncService
{
    protected HttpClient $http;

    public function __construct()
    {
        $this->http = new HttpClient([
            'timeout' => 30,
            'verify' => env('APP_DEBUG', false) ? false : true,
            'headers' => [
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            ],
        ]);
    }

    /**
     * Fetch HTML content from a URL.
     */
    protected function httpGet(string $url): string
    {
        try {
            $response = $this->http->get($url);
            return (string) $response->getBody();
        } catch (RequestException $e) {
            \Log::warning("HTTP GET failed for $url: " . $e->getMessage());
            return '';
        } catch (Exception $e) {
            \Log::warning("HTTP GET error for $url: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Sync all active feeds.
     */
    public function syncAllActive(?int $feedId = null): int
    {
        $query = Feed::where('is_active', true);
        if ($feedId) {
            $query->where('id', $feedId);
        }

        $feeds = $query->orderBy('sync_interval_minutes')->get();
        $postsCreated = 0;

        foreach ($feeds as $feed) {
            // Skip if not yet due for sync
            if ($feed->last_synced_at && $feed->last_synced_at->gt(now()->subMinutes($feed->sync_interval_minutes))) {
                continue;
            }

            try {
                $postsCreated += $this->syncFeed($feed);
                $feed->touchLastSync();
            } catch (Exception $e) {
                \Log::error("Feed sync failed for '{$feed->name}' (ID: {$feed->id}): {$e->getMessage()}");
            }
        }

        return $postsCreated;
    }

    /**
     * Sync a single feed source.
     * Detects website type and fetches accordingly.
     */
    public function syncFeed(Feed $feed): int
    {
        $html = $this->fetchPageContent($feed->url);
        if (empty($html)) {
            return 0;
        }

        // Auto-detect website type and extract items
        $items = $this->detectAndExtract($feed, $html, $feed->url);

        $processed = 0;
        foreach ($items as $itemData) {
            // Skip if already imported
            $exists = FeedItem::where('source_url', $itemData['source_url'])->first();
            if ($exists) {
                continue;
            }

            // Save feed item
            $feedItem = FeedItem::create(array_merge($itemData, [
                'feed_id' => $feed->id,
                'imported_at' => now(),
            ]));

            // Convert to blog post
            $this->convertToBlogPost($feedItem);
            $processed++;
        }

        $feed->touchLastFetched();

        return $processed;
    }

    /**
     * Auto-detect website type and extract news items.
     */
    protected function detectAndExtract(Feed $feed, string $html, string $pageUrl): array
    {
        // Strategy 1: Check for Inertia SSR JSON (SPA sites like Disdukcapil)
        $inertiaItems = $this->extractInertiaItems($html, $feed);
        if (!empty($inertiaItems)) {
            \Log::info("Detected Inertia SSR site for feed '{$feed->name}', extracted " . count($inertiaItems) . " items");
            return $inertiaItems;
        }

        // Strategy 2: Check for WordPress article format (entry-title-link)
        $wordpressItems = $this->extractWordPressItems($feed, $html, $pageUrl);
        if (!empty($wordpressItems)) {
            \Log::info("Detected WordPress site for feed '{$feed->name}', extracted " . count($wordpressItems) . " items");
            return $wordpressItems;
        }

        // Strategy 3: Fallback to CSS-selector-based extraction (legacy)
        $fallbackItems = $this->extractItems($feed, $html, $pageUrl);
        if (!empty($fallbackItems)) {
            \Log::info("Fallback CSS extraction for feed '{$feed->name}', extracted " . count($fallbackItems) . " items");
            return $fallbackItems;
        }

        \Log::warning("No items extracted for feed '{$feed->name}' from URL {$pageUrl}");
        return [];
    }

    /**
     * Extract news from Inertia SSR JSON (for React/Laravel SPAs).
     *
     * Looks for <script type="application/json"> OR
     * <script data-page="app" type="application/json"> blocks containing
     * Inertia page data (component + props).
     */
    protected function extractInertiaItems(string $html, ?Feed $feed = null): array
    {
        $items = [];

        // Match SSR JSON blocks (with or without data-page attribute)
        preg_match_all(
            '/<script[^>]*type=["\']application\/json["\'][^>]*>(.*?)<\/script>/s',
            $html,
            $matches
        );

        foreach ($matches[1] ?? [] as $jsonString) {
            $trimmed = trim($jsonString);
            if ($trimmed === '' || $trimmed === '{}') continue;

            $data = json_decode($trimmed, true);
            if (!$data || !isset($data['props'])) continue;

            $props = $data['props'];

            // Check if this is a News/Blog component
            $component = strtolower($data['component'] ?? '');
            $isNewsComponent = str_contains($component, 'news') || str_contains($component, 'blog') || str_contains($component, 'berita');
            if (!$isNewsComponent) continue;

            // Try different data structures
            $newsList = null;

            // Structure: props.news.data[]
            if (isset($props['news']['data']) && is_array($props['news']['data'])) {
                $newsList = $props['news']['data'];
            }
            // Structure: props.articles[]
            elseif (isset($props['articles']) && is_array($props['articles'])) {
                $newsList = $props['articles'];
            }
            // Structure: props.beritas[]
            elseif (isset($props['beritas']) && is_array($props['beritas'])) {
                $newsList = $props['beritas'];
            }

            if (!$newsList) continue;

            foreach ($newsList as $item) {
                $title = $item['judul'] ?? $item['title'] ?? '';
                if (empty($title)) continue;

                // Build URL from slug
                $sourceUrl = $this->buildNewsUrl($item, $props, $data, $feed);
                if (empty($sourceUrl)) continue;

                // Format date
                $rawDate = $item['tanggal'] ?? $item['published_at'] ?? '';
                $rawTime = $item['jam'] ?? '';
                $publishedAt = $this->parseIndonesianDate($rawDate, $rawTime);

                // Get excerpt
                $excerptRaw = $item['isi_berita'] ?? $item['excerpt'] ?? '';
                $excerpt = $this->sanitizeText($excerptRaw);
                if (strlen($excerpt) > 500) {
                    $excerpt = substr($excerpt, 0, 500) . '...';
                }

                // Category
                $category = $item['kategori'] ?? $item['category'] ?? $item['tag'] ?? '';

                $items[] = [
                    'source_url' => $sourceUrl,
                    'title' => $this->sanitizeText($title),
                    'excerpt' => $excerpt,
                    'original_published_at' => $publishedAt,
                    'category' => $this->sanitizeText($category),
                ];
            }
        }

        return $items;
    }

    /**
     * Build source URL for Inertia news items.
     *
     * Constructs the detail URL by combining the feed's base URL with the item's SEO slug.
     * Handles common patterns:
     * - /news/slug → https://domain/news/slug
     * - /berita/slug → https://domain/berita/slug
     */
    protected function buildNewsUrl(array $item, array $props, array $fullData, ?Feed $feed = null): ?string
    {
        // 1. Direct URL in item data
        foreach (['url', 'href', 'link', 'detail_url', 'permalink'] as $key) {
            if (!empty($item[$key])) {
                $url = $item[$key];
                // Resolve to absolute if relative
                if (str_starts_with($url, 'http')) return $url;
                if (str_starts_with($url, '/')) return rtrim(parse_url($feed?->url ?? '', PHP_URL_SCHEME) ?: 'https', '/') . '://' . rtrim(parse_url($feed?->url ?? '', PHP_URL_HOST) ?: 'localhost', '/') . $url;
                return rtrim($feed?->url ?? 'http://localhost', '/') . '/' . $url;
            }
        }

        $slug = $item['judul_seo'] ?? $item['slug'] ?? '';
        if (empty($slug)) return null;

        // 2. Use feed URL as base + slug
        if ($feed && $feed->url) {
            $baseUrl = rtrim($feed->url, '/');
            $pageUrl = $fullData['url'] ?? '';

            if (!empty($pageUrl) && str_starts_with($pageUrl, '/')) {
                // The Inertia page route path (e.g., /news)
                // We assume the URL pattern is {route_path}/{slug}
                $slugPrefix = trim($pageUrl, '/');
                if (str_contains(strtolower($slug), $slugPrefix)) {
                    // URL already contains the prefix
                    return $baseUrl;
                }
                // Construct: base/route_path/slug
                return $baseUrl . '/' . ltrim($slug, '/');
            }

            // Fallback: just append slug
            return $baseUrl . '/' . ltrim($slug, '/');
        }

        // 3. Try to extract base domain from any known prop
        $siteUrl = $props['site_url'] ?? $props['base_url'] ?? '';
        if (!empty($siteUrl)) {
            return rtrim($siteUrl, '/') . '/' . ltrim($slug, '/');
        }

        return null;
    }

    /**
     * Parse Indonesian date format: "29 Jun 2026" + "02:19:56"
     */
    protected function parseIndonesianDate(string $rawDate, string $rawTime = ''): ?string
    {
        $months = [
            'Jan' => '01', 'Feb' => '02', 'Mar' => '03', 'Apr' => '04',
            'Mei' => '05', 'Jun' => '06', 'Jul' => '07', 'Agu' => '08',
            'Sep' => '09', 'Okt' => '10', 'Nov' => '11', 'Des' => '12',
            'January' => '01', 'February' => '02', 'March' => '03', 'April' => '04',
            'June' => '06', 'July' => '07', 'August' => '08', 'September' => '09',
            'October' => '10', 'November' => '11', 'December' => '12',
        ];

        $datePart = trim($rawDate);
        if (empty($datePart)) return null;

        // Replace Indonesian month abbreviations with English
        foreach ($months as $indonesian => $english) {
            $datePart = str_replace($indonesian, $english, $datePart);
        }

        $dateTime = "$datePart " . trim($rawTime);

        // Try various formats
        $formats = [
            'd M Y H:i:s',
            'd M Y',
            'j F Y H:i:s',
            'j F Y',
            'Y-m-d H:i:s',
            'Y-m-d',
        ];

        foreach ($formats as $format) {
            $dt = \DateTime::createFromFormat($format, $dateTime);
            if ($dt) {
                return $dt->format('Y-m-d H:i:s');
            }
        }

        $ts = strtotime($dateTime);
        if ($ts) {
            return date('Y-m-d H:i:s', $ts);
        }

        return null;
    }

    /**
     * Extract news from WordPress sites (entry-title-link pattern).
     */
    protected function extractWordPressItems(Feed $feed, string $html, string $pageUrl): array
    {
        $items = [];

        $dom = new DOMDocument();
        $internalErrors = libxml_use_internal_errors(true);
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        $xpath = new DOMXPath($dom);

        // Try multiple WordPress selectors in priority order
        $selectors = [
            '//a[contains(@class,"entry-title-link")]',
            '//a[contains(@class,"post-title-link")]',
            '//a[contains(@class,"card-title")]',
            '//h2[contains(@class,"entry-title")]//a',
            '//h3[contains(@class,"post-title")]//a',
            '//h2[contains(@class,"news-title")]//a',
            '//article//a',
        ];

        $titleAnchors = null;
        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes && $nodes->length > 0) {
                $titleAnchors = $nodes;
                break;
            }
        }

        if (!$titleAnchors) {
            return [];
        }

        foreach ($titleAnchors as $anchor) {
            $title = trim(html_entity_decode($anchor->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $href = trim($anchor->getAttribute('href'));

            if (empty($title) || empty($href) || strlen($title) < 10) continue;

            // Resolve URL
            $fullUrl = $this->resolveUrl($href, $pageUrl ?: $feed->url);

            // Skip non-article links
            if (!$this->isArticleLink($fullUrl, $feed)) {
                continue;
            }

            // Find ancestor article for date/content
            $parent = $anchor;
            $article = null;
            for ($depth = 0; $depth < 15; $depth++) {
                $parent = $parent->parentNode;
                if (!$parent || $parent->nodeName === 'body') break;
                if ($parent->nodeName === 'article' || $parent->tagName === 'article') {
                    $article = $parent;
                    break;
                }
            }

            $date = null;
            if ($article) {
                $timeNodes = $xpath->query(
                    './/time[starts-with(@datetime,"20") or starts-with(@datetime,"19")]',
                    $article
                );
                if ($timeNodes && $timeNodes->length > 0) {
                    $date = $timeNodes->item(0)->getAttribute('datetime');
                }
            }

            $excerpt = '';
            if ($article) {
                // Try entry-content or excerpt
                $contentNodes = $xpath->query(
                    './/div[contains(@class,"entry-content") or contains(@class,"post-content") or contains(@class,"article-content")]//p[1]',
                    $article
                );
                if ($contentNodes && $contentNodes->length > 0) {
                    $excerpt = trim($contentNodes->item(0)->textContent);
                    if (strlen($excerpt) > 500) {
                        $excerpt = substr($excerpt, 0, 500) . '...';
                    }
                }
            }

            $category = '';
            if ($feed->category_selector && $article) {
                $catNodes = $xpath->query($feed->category_selector, $article);
                if ($catNodes && $catNodes->length > 0) {
                    $category = $this->sanitizeText($catNodes->item(0)->textContent);
                }
            }

            $items[] = [
                'source_url' => $fullUrl,
                'title' => $title,
                'excerpt' => $this->sanitizeText($excerpt),
                'original_published_at' => $this->parseDate($date) ?? null,
                'category' => $this->sanitizeText($category),
            ];
        }

        return $items;
    }

    /**
     * Legacy: Extract news items using CSS selector -> XPath conversion.
     */
    protected function extractItems(Feed $feed, string $html, string $pageUrl = ''): array
    {
        $items = [];

        $dom = new DOMDocument();
        $internalErrors = libxml_use_internal_errors(true);
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('dc', 'http://purl.org/dc/elements/1.1/');

        if (!$feed->title_selector) {
            return $items;
        }

        // Convert CSS selector(s) to XPath expression(s)
        $xpathExpr = $this->cssToXpath($feed->title_selector);

        if (empty($xpathExpr)) {
            \Log::warning("Could not convert CSS selector '{$feed->title_selector}' to XPath for feed '{$feed->name}'");
            return $items;
        }

        $nodes = $xpath->query($xpathExpr);

        if (!$nodes || $nodes->length === 0) {
            \Log::debug("No nodes found with XPath: $xpathExpr for feed '{$feed->name}'");
            return $items;
        }

        foreach ($nodes as $node) {
            $title = trim($node->textContent);
            $href = $node->getAttribute('href');

            if (empty($title) || empty($href)) {
                continue;
            }

            // Resolve URL
            $fullUrl = $this->resolveUrl($href, $pageUrl ?: $feed->url);

            // Skip non-article links
            if (!$this->isArticleLink($fullUrl, $feed)) {
                continue;
            }

            $item = [
                'source_url' => $fullUrl,
                'title' => $this->sanitizeText($title),
            ];

            // Try to find date from the node's ancestor
            $ancestors = $node;
            $depth = 0;
            $dateFound = false;
            while ($ancestors = $ancestors->parentNode && $depth < 10) {
                $dateNodes = $xpath->query($feed->date_selector, $ancestors);
                if ($dateNodes && $dateNodes->length > 0) {
                    $dateNode = $dateNodes->item(0);
                    $item['original_published_at'] = $this->parseDate($dateNode->textContent);
                    $dateFound = true;
                    break;
                }
                $depth++;
            }

            // Fallback date from datetime attribute on <time> tags near the anchor
            if (!$dateFound) {
                $timeNodes = $xpath->query('.//time[starts-with(@datetime, "20") or starts-with(@datetime, "19")]', $node);
                if ($timeNodes && $timeNodes->length > 0) {
                    $item['original_published_at'] = $this->parseDate($timeNodes->item(0)->getAttribute('datetime'));
                }
            }

            // Excerpt from container
            if ($feed->excerpt_selector) {
                $excerptFound = false;
                $ancestors = $node;
                $depth = 0;
                while ($ancestors && $ancestors->parentNode && $depth < 10) {
                    $excerptNodes = $xpath->query($feed->excerpt_selector, $ancestors->parentNode);
                    if ($excerptNodes && $excerptNodes->length > 0) {
                        $item['excerpt'] = $this->sanitizeText($excerptNodes->item(0)->textContent);
                        $excerptFound = true;
                        break;
                    }
                    $ancestors = $ancestors->parentNode;
                    $depth++;
                }
                // Try sibling paragraphs
                if (!$excerptFound) {
                    $pNodes = $xpath->query('.//following-sibling::p[1]', $node->parentNode);
                    if ($pNodes && $pNodes->length > 0) {
                        $item['excerpt'] = $this->sanitizeText($pNodes->item(0)->textContent);
                    }
                }
            }

            // Category
            if ($feed->category_selector) {
                $catNodes = $xpath->query($feed->category_selector, $node->parentNode ?? $node);
                if ($catNodes && $catNodes->length > 0) {
                    $item['category'] = $this->sanitizeText($catNodes->item(0)->textContent);
                }
            }

            $items[] = $item;
        }

        // Deduplicate by source_url
        $seen = [];
        $uniqueItems = [];
        foreach ($items as $item) {
            $key = $item['source_url'];
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $uniqueItems[] = $item;
            }
        }

        return $uniqueItems;
    }

    /**
     * Check if a URL looks like an article page (not a category, tag, or archive link).
     */
    protected function isArticleLink(string $url, Feed $feed): bool
    {
        // Skip pagination, home, category, tag links
        if (preg_match('/\/page\/[0-9]+\/?$|\/tag\/|\/category\/(?!berita)/', $url)) {
            return false;
        }

        // Must contain some indicative path segment (has extension like .php/.html or trailing slash)
        if (!preg_match('/(\.(php|html))?(\/|$)/i', $url)) {
            return false;
        }

        return true;
    }

    /**
     * Convert a comma-separated CSS selector string to XPath.
     */
    protected function cssToXpath(string $cssSelectors): string
    {
        $parts = array_map('trim', explode(',', $cssSelectors));
        $xpathParts = [];

        foreach ($parts as $css) {
            if (empty($css)) continue;

            $xp = $this->parseSingleCssSelector($css);
            if ($xp) {
                $xpathParts[] = $xp;
            }
        }

        return implode(' | ', $xpathParts);
    }

    /**
     * Parse a single CSS selector to XPath.
     */
    protected function parseSingleCssSelector(string $css): ?string
    {
        $css = trim($css);
        if (empty($css)) return null;

        // Split into tag and rest
        if (preg_match('/^([a-zA-Z][a-zA-Z0-9-]*)\.([\w-]+(?:\s*[\.\w-]+)*)\s+(.+)$/', $css, $m)) {
            $tag = $m[1];
            $classList = $m[2];
            $descendant = $m[3];

            $classCondition = '';
            foreach (preg_split('/\s*\.\s*/', $classList) as $cls) {
                $cls = trim($cls);
                if (!empty($cls)) {
                    $classCondition .= " contains(@class, '{$cls}')";
                }
            }

            // Parse descendant part
            $descParts = preg_split('/\s+/', trim($descendant));
            $xpTag = array_shift($descParts);

            // Handle descendant selector like 'a' or 'a.something'
            if (preg_match('/^([a-zA-Z]+)\.([\w-]+)$/', $xpTag, $dm)) {
                $xpTag = "{$dm[1]}[contains(@class, '{$dm[2]}')]";
            }

            return "//{$tag}[{$classCondition}]//{$xpTag}";
        }

        // Pattern: .class descendant
        if (preg_match('/^\.([\w-]+(?:\s*[\.\w-]+)*)\s+(.+)$/', $css, $m)) {
            $classList = $m[1];
            $descendant = $m[2];

            $classCond = '';
            foreach (preg_split('/\./', $classList) as $cls) {
                $cls = trim($cls);
                if (!empty($cls)) {
                    $classCond .= " contains(@class, '{$cls}')";
                }
            }

            return "//*[{$classCond}]//{$descendant}";
        }

        // Pattern: element.class
        if (preg_match('/^([a-zA-Z][a-zA-Z0-9-]*)\.([\w-]+)$/', $css, $m)) {
            return "//{$m[1]}[contains(@class, '{$m[2]}')]";
        }

        // Plain element
        if (preg_match('/^([a-zA-Z][a-zA-Z0-9-]*)$/', $css)) {
            return "//{$css}";
        }

        return null;
    }

    /**
     * Resolve relative URLs to absolute.
     */
    protected function resolveUrl(string $href, string $baseUrl): string
    {
        $href = trim($href);

        if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
            return $href;
        }

        if (str_starts_with($href, '#')) {
            return $baseUrl;
        }

        if (str_starts_with($href, '//')) {
            return 'https:' . $href;
        }

        if (str_starts_with($href, '/')) {
            $parsed = parse_url($baseUrl);
            return ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '') . $href;
        }

        $parsedBase = parse_url($baseUrl);
        $basePath = dirname($parsedBase['path'] ?? '/') ?: '/';
        $basePath = rtrim($basePath, '/');
        return ($parsedBase['scheme'] ?? 'https') . '://' . ($parsedBase['host'] ?? '') . $basePath . '/' . ltrim($href, '/');
    }

    /**
     * Parse dates from various formats.
     */
    protected function parseDate(string $rawDate): ?string
    {
        $rawDate = trim($rawDate);

        // ISO format
        $dt = \DateTime::createFromFormat('Y-m-d\TH:i:sP', $rawDate);
        if ($dt) {
            return $dt->format('Y-m-d H:i:s');
        }

        $formats = [
            'Y-m-d H:i:s',
            'Y-m-d',
            'd F Y H:i',
            'd F Y',
            'd/m/Y H:i',
            'd/m/Y',
            'd-m-Y',
            'j F Y',
            'j F Y H:i',
        ];

        foreach ($formats as $format) {
            $dt = \DateTime::createFromFormat($format, $rawDate);
            if ($dt) {
                return $dt->format('Y-m-d H:i:s');
            }
        }

        $ts = strtotime($rawDate);
        if ($ts) {
            return date('Y-m-d H:i:s', $ts);
        }

        return null;
    }

    /**
     * Convert a FeedItem to a CmsBlogPost.
     */
    protected function convertToBlogPost(FeedItem $feedItem): void
    {
        // Fetch full article content if no excerpt
        $textContent = $feedItem->excerpt ? $feedItem->excerpt : '';

        if (empty($textContent)) {
            $html = $this->fetchPageContent($feedItem->source_url);
            $textContent = $this->cleanContentHtml($html);
        }

        $slug = Str::slug($feedItem->title, '-', 'UTF-8');

        CmsBlogPost::firstOrCreate(
            ['slug' => $slug],
            [
                'title' => $feedItem->title,
                'slug' => $slug,
                'excerpt' => $feedItem->excerpt ?: Str::limit($textContent, 300),
                'content' => $textContent ?: $feedItem->title,
                'author_name' => 'Tim Website',
                'status' => 'PUBLISHED',
                'published_at' => $feedItem->original_published_at ?: now(),
            ]
        );
    }

    /**
     * Fetch full article content from a source URL.
     */
    protected function fetchPageContent(string $url): string
    {
        $html = $this->httpGet($url);
        if (empty($html)) return '';

        $dom = new DOMDocument();
        $ie = libxml_use_internal_errors(true);
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();
        libxml_use_internal_errors($ie);

        $xpath = new DOMXPath($dom);

        $selectors = [
            '//article',
            '//div[contains(@class,"entry-content")]',
            '//div[contains(@class,"post-content")]',
            '//div[contains(@class,"content")]',
            '//div[contains(@class,"article-content")]',
            '//main',
            '//div[contains(@class,"post")]',
            '//div[@id="content"]',
        ];

        foreach ($selectors as $sel) {
            $nodes = $xpath->query($sel);
            if ($nodes && $nodes->length > 0) {
                return $this->extractText($nodes->item(0));
            }
        }

        // Fallback: paragraphs
        $paragraphs = $xpath->query('//p');
        $text = '';
        foreach ($paragraphs as $p) {
            $line = trim($p->textContent);
            if ($line) $text .= $line . "\n\n";
        }

        return $text ?: strip_tags($html);
    }

    /**
     * Extract readable text from a DOM node, removing noise.
     */
    protected function extractText(\DOMElement $node): string
    {
        $clone = $node->cloneNode(true);

        foreach (['script', 'style', 'nav', 'footer', 'aside', 'header'] as $tag) {
            foreach ($clone->getElementsByTagName($tag) as $el) {
                // Keep article header (h1 in header)
                if ($tag === 'header' && $el->getElementsByTagName('h1')->length > 0) continue;
                $el->parentNode->removeChild($el);
            }
        }

        return trim($clone->textContent);
    }

    /**
     * Clean content: extract text from HTML.
     */
    protected function cleanContentHtml(string $html): string
    {
        if (empty($html)) return '';

        $dom = new DOMDocument();
        $ie = libxml_use_internal_errors(true);
        @$dom->loadHTML('<html><body>' . $html . '</body></html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($ie);

        $xpath = new DOMXPath($dom);
        foreach (['script', 'style', 'a[contains(@class,"skip")]'] as $sel) {
            foreach ($xpath->query($sel) as $el) {
                $el->parentNode->removeChild($el);
            }
        }

        return trim($dom->saveHTML());
    }

    /**
     * Sanitize text: decode entities, normalize whitespace.
     */
    protected function sanitizeText(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    /**
     * Generate a unique slug.
     */
    protected function generateSlug(string $title): string
    {
        $slug = Str::slug($title, '-', 'UTF-8');
        return CmsBlogPost::makeUniqueSlug($slug);
    }
}
