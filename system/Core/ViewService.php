<?php

/**
 * View Service - Latte Template Engine Integration
 * 
 * Provides easy access to Latte template engine
 */

namespace NeoCore\System\Core;

use Latte\Engine;
use Latte\Runtime\Html;

class ViewService
{
    private static ?Engine $engine = null;
    private static array $config = [];
    private static array $globals = [];

    /**
     * Initialize View service with configuration
     */
    public static function init(array $config): void
    {
        self::$config = $config;
    }

    /**
     * Get Latte engine instance
     */
    public static function getEngine(): Engine
    {
        if (self::$engine === null) {
            self::$engine = new Engine();
            
            // Set template cache directory
            $cacheDir = self::$config['cache_dir'] ?? STORAGE_PATH . '/cache/views';
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }
            self::$engine->setTempDirectory($cacheDir);

            // Auto-refresh in development
            if (self::$config['debug'] ?? false) {
                self::$engine->setAutoRefresh(true);
            }

            // Add global variables
            foreach (self::$globals as $name => $value) {
                self::$engine->addProvider($name, $value);
            }

            // Add custom filters
            self::addCustomFilters();
        }

        return self::$engine;
    }

    /**
     * Render a template
     */
    public static function render(string $template, array $params = []): string
    {
        $engine = self::getEngine();
        $templatePath = self::resolveTemplatePath($template);

        if (!file_exists($templatePath)) {
            throw new \RuntimeException("Template not found: {$template}");
        }

        return $engine->renderToString($templatePath, $params);
    }

    /**
     * Render template to output
     */
    public static function display(string $template, array $params = []): void
    {
        echo self::render($template, $params);
    }

    /**
     * Resolve template path
     */
    private static function resolveTemplatePath(string $template): string
    {
        $viewsDir = self::$config['views_dir'] ?? BASE_PATH . '/resources/views';

        // If template has extension, use as-is
        if (pathinfo($template, PATHINFO_EXTENSION)) {
            return $viewsDir . '/' . $template;
        }

        // Otherwise add .latte extension
        return $viewsDir . '/' . str_replace('.', '/', $template) . '.latte';
    }

    /**
     * Add global variable
     */
    public static function addGlobal(string $name, $value): void
    {
        self::$globals[$name] = $value;

        if (self::$engine !== null) {
            self::$engine->addProvider($name, $value);
        }
    }

    /**
     * Add custom filters to Latte
     */
    private static function addCustomFilters(): void
    {
        $engine = self::$engine;

        // URL filter
        $engine->addFilter('url', function (string $path): string {
            $baseUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/');
            return $baseUrl . '/' . ltrim($path, '/');
        });

        // Asset filter
        $engine->addFilter('asset', function (string $path): string {
            $baseUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/');
            return $baseUrl . '/assets/' . ltrim($path, '/');
        });

        // Date format filter
        $engine->addFilter('date', function ($date, string $format = 'Y-m-d H:i:s'): string {
            if ($date instanceof \DateTime) {
                return $date->format($format);
            }
            if (is_string($date)) {
                return date($format, strtotime($date));
            }
            return '';
        });

        // Number format filter
        $engine->addFilter('number', function ($number, int $decimals = 2): string {
            return number_format((float)$number, $decimals);
        });

        // JSON filter
        $engine->addFilter('json', function ($data): Html {
            return new Html(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        });

        // Truncate filter
        $engine->addFilter('truncate', function (string $text, int $length = 100): string {
            if (strlen($text) <= $length) {
                return $text;
            }
            return substr($text, 0, $length) . '...';
        });

        // Slug filter
        $engine->addFilter('slug', function (string $text): string {
            $text = strtolower($text);
            $text = preg_replace('/[^a-z0-9]+/', '-', $text);
            return trim($text, '-');
        });
    }

    /**
     * Clear view cache
     */
    public static function clearCache(): void
    {
        $cacheDir = self::$config['cache_dir'] ?? STORAGE_PATH . '/cache/views';
        
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Check if template exists
     */
    public static function exists(string $template): bool
    {
        $templatePath = self::resolveTemplatePath($template);
        return file_exists($templatePath);
    }
}
