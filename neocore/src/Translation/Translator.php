<?php

declare(strict_types=1);

namespace NeoCore\Translation;

/**
 * Translator
 * 
 * Handles translations and localization
 */
class Translator
{
    protected string $locale;
    protected string $fallbackLocale;
    protected string $path;
    protected array $loaded = [];

    public function __construct(string $locale, string $fallbackLocale, string $path)
    {
        $this->locale = $locale;
        $this->fallbackLocale = $fallbackLocale;
        $this->path = rtrim($path, '/');
    }

    /**
     * Get translation
     */
    public function get(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale = $locale ?? $this->locale;

        $translation = $this->getLine($key, $locale);

        if ($translation === null && $locale !== $this->fallbackLocale) {
            $translation = $this->getLine($key, $this->fallbackLocale);
        }

        if ($translation === null) {
            return $key;
        }

        return $this->makeReplacements($translation, $replace);
    }

    /**
     * Get translation with choice (pluralization)
     */
    public function choice(string $key, int|float $number, array $replace = [], ?string $locale = null): string
    {
        $locale = $locale ?? $this->locale;

        $translation = $this->getLine($key, $locale);

        if ($translation === null && $locale !== $this->fallbackLocale) {
            $translation = $this->getLine($key, $this->fallbackLocale);
        }

        if ($translation === null) {
            return $key;
        }

        $replace['count'] = $number;

        $line = $this->extract($translation, $number);

        return $this->makeReplacements($line, $replace);
    }

    /**
     * Check if translation exists
     */
    public function has(string $key, ?string $locale = null): bool
    {
        $locale = $locale ?? $this->locale;

        return $this->getLine($key, $locale) !== null;
    }

    /**
     * Get locale
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * Set locale
     */
    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    /**
     * Get fallback locale
     */
    public function getFallbackLocale(): string
    {
        return $this->fallbackLocale;
    }

    /**
     * Set fallback locale
     */
    public function setFallbackLocale(string $locale): void
    {
        $this->fallbackLocale = $locale;
    }

    /**
     * Load translations for locale
     */
    protected function load(string $locale): array
    {
        if (isset($this->loaded[$locale])) {
            return $this->loaded[$locale];
        }

        $translations = [];

        // Load JSON file
        $jsonFile = "{$this->path}/{$locale}.json";
        if (file_exists($jsonFile)) {
            $json = json_decode(file_get_contents($jsonFile), true);
            if (is_array($json)) {
                $translations = array_merge($translations, $json);
            }
        }

        // Load PHP files from locale directory
        $localeDir = "{$this->path}/{$locale}";
        if (is_dir($localeDir)) {
            $files = glob("{$localeDir}/*.php");
            foreach ($files as $file) {
                $group = basename($file, '.php');
                $content = require $file;
                if (is_array($content)) {
                    $translations[$group] = $content;
                }
            }
        }

        $this->loaded[$locale] = $translations;

        return $translations;
    }

    /**
     * Get translation line
     */
    protected function getLine(string $key, string $locale): ?string
    {
        $translations = $this->load($locale);

        // Simple key
        if (isset($translations[$key])) {
            return $translations[$key];
        }

        // Nested key (e.g., 'messages.welcome')
        if (str_contains($key, '.')) {
            $segments = explode('.', $key);
            $value = $translations;

            foreach ($segments as $segment) {
                if (!is_array($value) || !isset($value[$segment])) {
                    return null;
                }
                $value = $value[$segment];
            }

            return is_string($value) ? $value : null;
        }

        return null;
    }

    /**
     * Make replacements in translation
     */
    protected function makeReplacements(string $line, array $replace): string
    {
        if (empty($replace)) {
            return $line;
        }

        $replace = $this->sortReplacements($replace);

        foreach ($replace as $key => $value) {
            $line = str_replace(
                [':' . $key, ':' . strtoupper($key), ':' . ucfirst($key)],
                [$value, strtoupper($value), ucfirst($value)],
                $line
            );
        }

        return $line;
    }

    /**
     * Sort replacements by length (longest first)
     */
    protected function sortReplacements(array $replace): array
    {
        uksort($replace, function ($a, $b) {
            return strlen($b) - strlen($a);
        });

        return $replace;
    }

    /**
     * Extract the appropriate plural form
     */
    protected function extract(string $line, int|float $number): string
    {
        $segments = explode('|', $line);

        if (count($segments) === 1) {
            return $line;
        }

        // Simple plural: "apple|apples"
        if (count($segments) === 2) {
            return $number === 1 ? $segments[0] : $segments[1];
        }

        // With ranges: "{0} no apples|{1} one apple|[2,*] :count apples"
        foreach ($segments as $segment) {
            $segment = trim($segment);

            // Check for range like {0}, {1}, [2,5], [6,*]
            if (preg_match('/^[\{\[]([0-9]+|[*]),([0-9]+|[*])[\}\]]\s*(.+)$/i', $segment, $matches)) {
                $from = $matches[1] === '*' ? -INF : (float) $matches[1];
                $to = $matches[2] === '*' ? INF : (float) $matches[2];

                if ($number >= $from && $number <= $to) {
                    return trim($matches[3]);
                }
            }

            // Check for exact match like {0}, {1}
            if (preg_match('/^\{([0-9]+)\}\s*(.+)$/i', $segment, $matches)) {
                if ($number == $matches[1]) {
                    return trim($matches[2]);
                }
            }

            // Check for range like [2,*]
            if (preg_match('/^\[([0-9]+),([0-9]+|[*])\]\s*(.+)$/i', $segment, $matches)) {
                $from = (float) $matches[1];
                $to = $matches[2] === '*' ? INF : (float) $matches[2];

                if ($number >= $from && $number <= $to) {
                    return trim($matches[3]);
                }
            }
        }

        // Default to last segment
        return trim($segments[count($segments) - 1]);
    }

    /**
     * Get all translations for locale
     */
    public function all(?string $locale = null): array
    {
        $locale = $locale ?? $this->locale;
        return $this->load($locale);
    }

    /**
     * Add translation at runtime
     */
    public function addLine(string $key, string $value, ?string $locale = null): void
    {
        $locale = $locale ?? $this->locale;

        if (!isset($this->loaded[$locale])) {
            $this->load($locale);
        }

        $this->loaded[$locale][$key] = $value;
    }

    /**
     * Add multiple translations at runtime
     */
    public function addLines(array $lines, ?string $locale = null): void
    {
        foreach ($lines as $key => $value) {
            $this->addLine($key, $value, $locale);
        }
    }
}
