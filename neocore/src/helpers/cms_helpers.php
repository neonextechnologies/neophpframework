<?php

if (!function_exists('seo')) {
    /**
     * Get SEO manager instance
     */
    function seo(): \NeoCore\SEO\SEOManager
    {
        return app(\NeoCore\SEO\SEOManager::class);
    }
}

if (!function_exists('meta')) {
    /**
     * Get meta tag manager
     */
    function meta(): \NeoCore\SEO\MetaTag
    {
        return seo()->meta();
    }
}

if (!function_exists('og')) {
    /**
     * Get Open Graph manager
     */
    function og(): \NeoCore\SEO\OpenGraph
    {
        return seo()->og();
    }
}

if (!function_exists('twitter')) {
    /**
     * Get Twitter Card manager
     */
    function twitter(): \NeoCore\SEO\TwitterCard
    {
        return seo()->twitter();
    }
}

if (!function_exists('schema')) {
    /**
     * Get Schema manager
     */
    function schema(): \NeoCore\SEO\Schema
    {
        return seo()->schema();
    }
}

if (!function_exists('sitemap')) {
    /**
     * Create new sitemap
     */
    function sitemap(array $config = []): \NeoCore\SEO\Sitemap
    {
        return new \NeoCore\SEO\Sitemap($config);
    }
}

if (!function_exists('robots')) {
    /**
     * Create robots.txt manager
     */
    function robots(array $config = []): \NeoCore\SEO\RobotsTxt
    {
        return new \NeoCore\SEO\RobotsTxt($config);
    }
}

if (!function_exists('breadcrumb')) {
    /**
     * Get breadcrumb builder
     */
    function breadcrumb(): \NeoCore\CMS\Breadcrumb
    {
        static $instance;
        
        if (!$instance) {
            $instance = new \NeoCore\CMS\Breadcrumb(config('cms', []));
        }
        
        return $instance;
    }
}

if (!function_exists('menu')) {
    /**
     * Get menu builder
     */
    function menu(string $name = 'main'): \NeoCore\CMS\Menu
    {
        static $menus = [];
        
        if (!isset($menus[$name])) {
            $menus[$name] = new \NeoCore\CMS\Menu($name, config('cms', []));
        }
        
        return $menus[$name];
    }
}

if (!function_exists('page')) {
    /**
     * Get page repository
     */
    function page(): \NeoCore\CMS\PageRepository
    {
        return app(\NeoCore\CMS\PageRepository::class);
    }
}
