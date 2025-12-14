<?php

declare(strict_types=1);

namespace NeoCore\SEO;

use DateTimeImmutable;

/**
 * Sitemap Generator
 * 
 * Generates XML sitemaps for SEO
 */
class Sitemap
{
    protected array $urls = [];
    protected array $config;
    protected int $maxUrls = 50000;
    protected bool $includeImages = true;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        
        if (isset($config['sitemap']['max_urls'])) {
            $this->maxUrls = $config['sitemap']['max_urls'];
        }
        
        if (isset($config['sitemap']['images'])) {
            $this->includeImages = $config['sitemap']['images'];
        }
    }

    /**
     * Add URL to sitemap
     */
    public function add(
        string $loc,
        ?DateTimeImmutable $lastmod = null,
        string $changefreq = 'weekly',
        float $priority = 0.5
    ): SitemapUrl {
        $url = new SitemapUrl($loc, $lastmod, $changefreq, $priority);
        $this->urls[] = $url;
        return $url;
    }

    /**
     * Add URL object
     */
    public function addUrl(SitemapUrl $url): self
    {
        $this->urls[] = $url;
        return $this;
    }

    /**
     * Add multiple URLs
     */
    public function addUrls(array $urls): self
    {
        foreach ($urls as $url) {
            if ($url instanceof SitemapUrl) {
                $this->addUrl($url);
            }
        }
        return $this;
    }

    /**
     * Get all URLs
     */
    public function getUrls(): array
    {
        return $this->urls;
    }

    /**
     * Get URL count
     */
    public function count(): int
    {
        return count($this->urls);
    }

    /**
     * Check if URL should be excluded
     */
    protected function shouldExclude(string $url): bool
    {
        if (!isset($this->config['sitemap']['exclude_paths'])) {
            return false;
        }

        foreach ($this->config['sitemap']['exclude_paths'] as $pattern) {
            // Convert wildcard pattern to regex
            $regex = str_replace('\*', '.*', preg_quote($pattern, '/'));
            
            if (preg_match('/^' . $regex . '/', $url)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate XML sitemap
     */
    public function toXml(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
        
        if ($this->includeImages) {
            $xml .= ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"';
            $xml .= ' xmlns:video="http://www.google.com/schemas/sitemap-video/1.1"';
        }
        
        $xml .= ">\n";

        $count = 0;
        foreach ($this->urls as $url) {
            if ($count >= $this->maxUrls) {
                break;
            }
            
            if ($this->shouldExclude($url->loc)) {
                continue;
            }
            
            $xml .= $url->toXml();
            $count++;
        }

        $xml .= "</urlset>\n";

        return $xml;
    }

    /**
     * Generate sitemap index (for multiple sitemaps)
     */
    public function toIndex(array $sitemaps): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($sitemaps as $sitemap) {
            $xml .= "  <sitemap>\n";
            $xml .= "    <loc>" . htmlspecialchars($sitemap['loc'], ENT_XML1, 'UTF-8') . "</loc>\n";
            
            if (isset($sitemap['lastmod'])) {
                $xml .= "    <lastmod>" . $sitemap['lastmod']->format('Y-m-d\TH:i:sP') . "</lastmod>\n";
            }
            
            $xml .= "  </sitemap>\n";
        }

        $xml .= "</sitemapindex>\n";

        return $xml;
    }

    /**
     * Save sitemap to file
     */
    public function save(string $path): bool
    {
        $xml = $this->toXml();
        return file_put_contents($path, $xml) !== false;
    }

    /**
     * Generate from routes
     */
    public function generateFromRoutes(array $routes, string $baseUrl): self
    {
        foreach ($routes as $route) {
            if (isset($route['path'])) {
                $url = rtrim($baseUrl, '/') . '/' . ltrim($route['path'], '/');
                
                $priority = $route['priority'] ?? 0.5;
                $changefreq = $route['changefreq'] ?? 'weekly';
                
                $this->add($url, null, $changefreq, $priority);
            }
        }

        return $this;
    }

    /**
     * Convert to string
     */
    public function __toString(): string
    {
        return $this->toXml();
    }
}
