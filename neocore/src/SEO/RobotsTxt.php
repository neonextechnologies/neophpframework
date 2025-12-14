<?php

declare(strict_types=1);

namespace NeoCore\SEO;

/**
 * Robots.txt Manager
 * 
 * Generates and manages robots.txt file
 */
class RobotsTxt
{
    protected array $rules = [];
    protected array $sitemaps = [];
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->loadConfig();
    }

    /**
     * Load configuration
     */
    protected function loadConfig(): void
    {
        if (!isset($this->config['robots'])) {
            return;
        }

        $robots = $this->config['robots'];
        
        $userAgent = $robots['user_agent'] ?? '*';
        
        if (isset($robots['allow'])) {
            foreach ($robots['allow'] as $path) {
                $this->allow($path, $userAgent);
            }
        }
        
        if (isset($robots['disallow'])) {
            foreach ($robots['disallow'] as $path) {
                $this->disallow($path, $userAgent);
            }
        }
        
        if (isset($robots['sitemap'])) {
            $this->addSitemap($robots['sitemap']);
        }
    }

    /**
     * Add user agent
     */
    public function userAgent(string $userAgent): self
    {
        if (!isset($this->rules[$userAgent])) {
            $this->rules[$userAgent] = [
                'allow' => [],
                'disallow' => [],
                'crawl_delay' => null,
            ];
        }
        return $this;
    }

    /**
     * Allow path for user agent
     */
    public function allow(string $path, string $userAgent = '*'): self
    {
        $this->userAgent($userAgent);
        $this->rules[$userAgent]['allow'][] = $path;
        return $this;
    }

    /**
     * Disallow path for user agent
     */
    public function disallow(string $path, string $userAgent = '*'): self
    {
        $this->userAgent($userAgent);
        $this->rules[$userAgent]['disallow'][] = $path;
        return $this;
    }

    /**
     * Set crawl delay for user agent
     */
    public function crawlDelay(int $seconds, string $userAgent = '*'): self
    {
        $this->userAgent($userAgent);
        $this->rules[$userAgent]['crawl_delay'] = $seconds;
        return $this;
    }

    /**
     * Add sitemap URL
     */
    public function addSitemap(string $url): self
    {
        $this->sitemaps[] = $url;
        return $this;
    }

    /**
     * Block all crawlers
     */
    public function blockAll(string $userAgent = '*'): self
    {
        return $this->disallow('/', $userAgent);
    }

    /**
     * Allow all crawlers
     */
    public function allowAll(string $userAgent = '*'): self
    {
        return $this->allow('/', $userAgent);
    }

    /**
     * Block specific bot
     */
    public function blockBot(string $botName): self
    {
        return $this->disallow('/', $botName);
    }

    /**
     * Generate robots.txt content
     */
    public function generate(): string
    {
        $content = '';

        // Add rules for each user agent
        foreach ($this->rules as $userAgent => $rules) {
            $content .= "User-agent: {$userAgent}\n";

            // Add allow rules
            foreach ($rules['allow'] as $path) {
                $content .= "Allow: {$path}\n";
            }

            // Add disallow rules
            foreach ($rules['disallow'] as $path) {
                $content .= "Disallow: {$path}\n";
            }

            // Add crawl delay
            if ($rules['crawl_delay'] !== null) {
                $content .= "Crawl-delay: {$rules['crawl_delay']}\n";
            }

            $content .= "\n";
        }

        // Add sitemaps
        foreach ($this->sitemaps as $sitemap) {
            $content .= "Sitemap: {$sitemap}\n";
        }

        return trim($content) . "\n";
    }

    /**
     * Save to file
     */
    public function save(string $path): bool
    {
        $content = $this->generate();
        return file_put_contents($path, $content) !== false;
    }

    /**
     * Common presets
     */
    public function allowSearchEngines(): self
    {
        return $this
            ->allowAll('Googlebot')
            ->allowAll('Bingbot')
            ->allowAll('Slurp')
            ->allowAll('DuckDuckBot')
            ->allowAll('Baiduspider')
            ->allowAll('YandexBot');
    }

    /**
     * Block bad bots
     */
    public function blockBadBots(): self
    {
        $badBots = [
            'AhrefsBot',
            'SemrushBot',
            'MJ12bot',
            'DotBot',
            'BLEXBot',
            'MegaIndex',
        ];

        foreach ($badBots as $bot) {
            $this->blockBot($bot);
        }

        return $this;
    }

    /**
     * Convert to string
     */
    public function __toString(): string
    {
        return $this->generate();
    }
}
